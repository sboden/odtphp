<?php

namespace Odtphp;

use Odtphp\Segment;
use Odtphp\Exceptions\OdfException;
use Odtphp\Zip\PclZipProxy;
use Odtphp\Zip\PhpZipProxy;

/**
 * Templating class for odt file
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 * Encoding : ISO-8859-1
 * Author: neveldo $
 * Modified by: Vikas Mahajan http://vikasmahajan.wordpress.com
 * Date - $Date: 2011-03-06 11:11:57
 * SVN Revision - $Rev: 42 $
 * Id : $Id: odf.php 42 2009-06-17 09:11:57Z neveldo $
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.3
 */
class Odf
{
    protected $config = array(
        'ZIP_PROXY' => \Odtphp\Zip\PhpZipProxy::class,
        'DELIMITER_LEFT' => '{',
        'DELIMITER_RIGHT' => '}',
        'PATH_TO_TMP' => null
    );
    protected $file;
    protected $contentXml;      // To store content of content.xml file
    protected $manifestXml;     // To store content of manifest.xml file
    protected $stylesXml;       // To store content of styles.xml file
    protected $tmpfile;
    protected $images = array();
    protected $vars = array();
    protected $manif_vars = array(); // array to store image names
    protected $segments = array();
    const PIXEL_TO_CM = 0.026458333;

    /**
     * Class constructor
     *
     * @param string $filename the name of the odt file
     * @throws OdfException
     */
    public function __construct($filename, $config = array())
    {
        if (!is_array($config)) {
            throw new OdfException('Configuration data must be provided as array');
        }
        foreach ($config as $configKey => $configValue) {
            if (array_key_exists($configKey, $this->config)) {
                $this->config[$configKey] = $configValue;
            }
        }

        // Set tmp directory properly when not defined.
        if (empty($config['PATH_TO_TMP'])) {
          $this->config['PATH_TO_TMP'] = sys_get_temp_dir();
        }
        $this->config['PATH_TO_TMP'] = realpath($this->config['PATH_TO_TMP']);
        if (!$this->config['PATH_TO_TMP']) {
          throw new OdfException('Directory "' . sys_get_temp_dir() . '" does not exist');
        }
        $this->config['PATH_TO_TMP'] .= '/';

        if (!is_subclass_of($this->config['ZIP_PROXY'], Zip\ZipInterface::class)) {
          throw new OdfException($this->config['ZIP_PROXY'] . ' class must be ZipInterface instance - check your config');
        }
        $zipHandler = $this->config['ZIP_PROXY'];
        $this->file = new $zipHandler();
        if ($this->file->open($filename) !== true) {
            throw new OdfException("Error while Opening the file '$filename' - Check your odt file");
        }
        if (($this->contentXml = $this->file->getFromName('content.xml')) === false) {
            throw new OdfException("Nothing to parse - check that the content.xml file is correctly formed");
        }
        if (($this->stylesXml = $this->file->getFromName('styles.xml')) === false) {
            throw new OdfException("Nothing to parse - Check that the styles.xml file is correctly formed in source file '$filename'");
        }
        if (($this->manifestXml = $this->file->getFromName('META-INF/manifest.xml')) === false) {
            throw new OdfException("Something is wrong with META-INF/manifest.xml in source file '$filename'");
        }
        
        $this->file->close();
        
        if (!function_exists("getmypid")) {
          $pseudo_pid = mt_rand(1, 99999);
        }
        else {
          $pseudo_pid = getmypid();
          if (!$pseudo_pid) {
            $pseudo_pid = mt_rand(1, 99999);
          }
        }

        // Make a name that is unique enough for parallel processing: uniqid() by itself
        // is not unique enough, that's just a hex representation of the system time.
        $tmp = tempnam($this->config['PATH_TO_TMP'], uniqid('tmp_odtphp_' . sprintf('%04X%04X%04X%04X%d',
                                                                    mt_rand(0, 65535), mt_rand(0, 65535),
                                                                    mt_rand(0, 65535), mt_rand(0, 65535), $pseudo_pid),
                                                 TRUE));
        if (!copy($filename, $tmp)) {
          throw new OdfException("Could not copy template file '$filename' to temporary file '$tmp'");
        }
        $this->tmpfile = $tmp;
        $this->_moveRowSegments();
    }

    /**
     * Delete the temporary file when the object is destroyed
     */
    public function __destruct()
    {
        if (file_exists($this->tmpfile)) {
            unlink($this->tmpfile);
        }
    }

    /**
     * Assign a template variable.
     *
     * @param string $key
     *   Name of the variable within the template.
     * @param string $value
     *   Replacement value.
     * @param bool $encode
     *   If true, special XML characters are encoded.
     *
     * @throws OdfException
     * @return odf
     */
    public function setVars($key, $value, $encode = true, $charset = 'ISO-8859')
    {
        $tag= $this->config['DELIMITER_LEFT'] . $key . $this->config['DELIMITER_RIGHT'];
        if (strpos($this->contentXml, $tag) === false && strpos($this->stylesXml, $tag) === false) {
            throw new OdfException("var $key not found in the document");
        }
        $value = $encode ? $this->recursiveHtmlspecialchars($value) : $value;
        $value = ($charset == 'ISO-8859') ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
        $this->vars[$tag] = str_replace("\n", "<text:line-break/>", $value);
        return $this;
    }

    /**
     * Set the value of a variable in a template.
     *
     * @param string $key
     *  Name of the variable within the template.
     * @param string $value
     *  Replacement value.
     * @param bool
     *  $encode if true, special XML characters are encoded.
     *
     * @throws OdfException
     * @return $this
     */
    public function setVariable($key, $value, $encode = true)
    {
      $tag= $this->config['DELIMITER_LEFT'] . $key . $this->config['DELIMITER_RIGHT'];
      if (strpos($this->contentXml, $tag) === false && strpos($this->stylesXml, $tag) === false) {
        throw new OdfException("var $key not found in the document");
      }
      if (empty($value)) {
        $value = '';
      }
      else {
        $value = $encode ? $this->recursiveHtmlspecialchars($value) : $value;
      }
      $this->vars[$tag] = str_replace("\n", "<text:line-break/>", $value);
      return $this;
    }

    /**
     * Check if the a variable exists in the template
     *
     * @param string $key
     *  Name of the variable within the template to verify.
     *
     * @throws OdfException
     * @return bool
     *   TRUE when variable is in template, else FALSE.
     */
    public function variableExists($key)
    {
      $tag = $this->config['DELIMITER_LEFT'] . $key . $this->config['DELIMITER_RIGHT'];
      return strpos($this->contentXml, $tag) === true || strpos($this->stylesXml, $tag) === true;
    }

    /**
     * Assign a template variable as a picture
     *
     * @param string $key name of the variable within the template
     * @param string $value path to the picture
     * @param integer $page anchor to page number (or -1 if anchor-type is as-char)
     * @param integer $width width of picture (keep original if null)
     * @param integer $height height of picture (keep original if null)
     * @param integer $offsetX offset by horizontal (not used if $page = -1)
     * @param integer $offsetY offset by vertical (not used if $page = -1)
     * @throws OdfException
     * @return odf
     */
    public function setImage($key, $value, $page = -1, $width = null, $height = null, $offsetX = null, $offsetY = null)
    {
        $filename = strtok(strrchr($value, '/'), '/.');
        $file = substr(strrchr($value, '/'), 1);
        $size = @getimagesize($value);
        if ($size === false) {
            throw new OdfException("Invalid image");
        }
        if (!$width && !$height) {
            list ($width, $height) = $size;
            $width *= Odf::PIXEL_TO_CM;
            $height *= Odf::PIXEL_TO_CM;
        }
        $anchor = $page == -1 ? 'text:anchor-type="as-char"' : "text:anchor-type=\"page\" text:anchor-page-number=\"{$page}\" svg:x=\"{$offsetX}cm\" svg:y=\"{$offsetY}cm\"";
        $xml = <<<IMG
<draw:frame draw:style-name="fr1" draw:name="$filename" {$anchor} svg:width="{$width}cm" svg:height="{$height}cm" draw:z-index="3"><draw:image xlink:href="Pictures/$file" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/></draw:frame>
IMG;
        
        $this->images[$value] = $file;
        $this->manif_vars[] = $file;    //save image name as array element
        $this->setVars($key, $xml, false);
        return $this;
    }

    /**
     * Move segment tags for lines of tables
     * Called automatically within the constructor
     *
     * @return void
     */
    private function _moveRowSegments()
    {
        // Search all possible rows in the document
        $reg1 = "#<table:table-row[^>]*>(.*)</table:table-row>#smU";
        preg_match_all($reg1, $this->contentXml, $matches);
        for ($i = 0, $size = count($matches[0]); $i < $size; $i++) {
            // Check if the current row contains a segment row.*
            $reg2 = '#\[!--\sBEGIN\s(row.[\S]*)\s--\](.*)\[!--\sEND\s\\1\s--\]#smU';
            if (preg_match($reg2, $matches[0][$i], $matches2)) {
                $balise = str_replace('row.', '', $matches2[1]);
                // Move segment tags around the row
                $replace = array(
                    '[!-- BEGIN ' . $matches2[1] . ' --]'   => '',
                    '[!-- END ' . $matches2[1] . ' --]'     => '',
                    '<table:table-row'                          => '[!-- BEGIN ' . $balise . ' --]<table:table-row',
                    '</table:table-row>'                        => '</table:table-row>[!-- END ' . $balise . ' --]'
                );
                $replacedXML = str_replace(array_keys($replace), array_values($replace), $matches[0][$i]);
                $this->contentXml = str_replace($matches[0][$i], $replacedXML, $this->contentXml);
            }
        }
    }

    /**
     * Merge template variables
     * Called automatically for a save
     *
     * @return void
     */
    private function _parse()
    {
        $this->contentXml = str_replace(array_keys($this->vars), array_values($this->vars), $this->contentXml);
        $this->stylesXml  = str_replace(array_keys($this->vars), array_values($this->vars), $this->stylesXml);
    }
    /**
     * Add the merged segment to the document
     *
     * @param Segment $segment
     * @throws OdfException
     * @return odf
     */
    public function mergeSegment(Segment $segment)
    {
        if (! array_key_exists($segment->getName(), $this->segments)) {
            throw new OdfException($segment->getName() . 'cannot be parsed, has it been set yet ?');
        }
        $string = $segment->getName();
        // $reg = '@<text:p[^>]*>\[!--\sBEGIN\s' . $string . '\s--\](.*)\[!--.+END\s' . $string . '\s--\]<\/text:p>@smU';
        $reg = '@\[!--\sBEGIN\s' . $string . '\s--\](.*)\[!--.+END\s' . $string . '\s--\]@smU';
        $this->contentXml = preg_replace($reg, $segment->getXmlParsed(), $this->contentXml);
        foreach ($segment->manif_vars as $val) {
            $this->manif_vars[] = $val;   //copy all segment image names into current array
        }
        return $this;
    }

    /**
     * Display all the current template variables
     *
     * @return string
     */
    public function printVars()
    {
        return print_r('<pre>' . print_r($this->vars, true) . '</pre>', true);
    }

    /**
     * Display the XML content of the file from odt document
     * as it is at the moment
     *
     * @return string
     */
    public function __toString()
    {
        return $this->contentXml;
    }

    /**
     * Display loop segments declared with setSegment()
     *
     * @return string
     */
    public function printDeclaredSegments()
    {
        return '<pre>' . print_r(implode(' ', array_keys($this->segments)), true) . '</pre>';
    }

    /**
     * Check if the specified segment exists in the document.
     *
     * @param $segment
     *
     * @return bool
     *   TRUE when segment exist, else FALSE.
     */
    public function segmentExists($segment) {
      $reg = "#\[!--\sBEGIN\s$segment\s--](.*?)\[!--\sEND\s$segment\s--]#smU";
      return preg_match($reg, html_entity_decode($this->contentXml), $m) != 0;
    }

    /**
     * Declare a segment in order to use it in a loop
     *
     * @param string $segment
     * @throws OdfException
     * @return Segment
     */
    public function setSegment($segment)
    {
        if (array_key_exists($segment, $this->segments)) {
            return $this->segments[$segment];
        }
        $reg = "#\[!--\sBEGIN\s$segment\s--\](.*?)\[!--\sEND\s$segment\s--\]#smU";
        if (preg_match($reg, html_entity_decode($this->contentXml), $m) == 0) {
            throw new OdfException("'$segment' segment not found in the document");
        }
        $this->segments[$segment] = new Segment($segment, $m[1], $this);
        return $this->segments[$segment];
    }

    /**
     * Save the odt file on the disk
     *
     * @param string $file name of the desired file
     * @throws OdfException
     * @return void
     */
    public function saveToDisk($file = null)
    {
        if ($file !== null && is_string($file)) {
            if (file_exists($file) && !(is_file($file) && is_writable($file))) {
                throw new OdfException('Permission denied : can\'t create ' . $file);
            }
            $this->_save();
            copy($this->tmpfile, $file);
        } else {
            $this->_save();
        }
    }

    /**
     * Internal save
     *
     * @throws OdfException
     * @return void
     */
    private function _save()
    {
        $this->file->open($this->tmpfile);
        $this->_parse();
        if (!$this->file->addFromString('content.xml', $this->contentXml) || !$this->file->addFromString('styles.xml', $this->stylesXml)) {
            throw new OdfException('Error during file export addFromString');
        }
        $lastpos=strrpos($this->manifestXml, "\n", -15); //find second last newline in the manifest.xml file
        $manifdata = "";

        // Enter all images description in $manifdata variable.
        foreach ($this->manif_vars as $val) {
            $ext = substr(strrchr($val, '.'), 1);
            $manifdata = $manifdata.'<manifest:file-entry manifest:media-type="image/'.$ext.'" manifest:full-path="Pictures/'.$val.'"/>'."\n";
        }

        // Place content of $manifdata variable in manifest.xml file at appropriate place.
        $replace = '<manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>';
        if ((strlen($manifdata) > 0) && (strpos($this->manifestXml, $replace) !== FALSE)) {
          $this->manifestXml = str_replace($replace,
            $replace . "\n" . $manifdata, $this->manifestXml);
        }
        else {
          // This branch is a fail-safe but normally should not be used.
          $this->manifestXml = substr_replace($this->manifestXml, "\n".$manifdata, $lastpos+1, 0);
        }

        if (! $this->file->addFromString('META-INF/manifest.xml', $this->manifestXml)) {
            throw new OdfException('Error during manifest file export');
        }
        foreach ($this->images as $imageKey => $imageValue) {
            $this->file->addFile($imageKey, 'Pictures/' . $imageValue);
        }
        $this->file->close(); // seems to bug on windows CLI sometimes
    }

    /**
     * Export the file as attached file by HTTP
     *
     * @param string $name (optionnal)
     * @throws OdfException
     * @return void
     */
    public function exportAsAttachedFile($name = "")
    {
        $this->_save();
        if (headers_sent($filename, $linenum)) {
            throw new OdfException("headers already sent ($filename at $linenum)");
        }
        
        if ($name == "") {
          if (!function_exists("getmypid")) {
            $pseudo_pid = mt_rand(1, 99999);
          }
          else {
            $pseudo_pid = getmypid();
            if (!$pseudo_pid) {
              $pseudo_pid = mt_rand(1, 99999);
            }
          }

          // Make a name that is unique enough for parallel processing: uniqid() by itself
          // is not unique enough, it's just a hex representation of the system time.
          $name = uniqid(sprintf('%04X%04X%04X%04X%d',
                            mt_rand(0, 65535), mt_rand(0, 65535),
                            mt_rand(0, 65535), mt_rand(0, 65535), $pseudo_pid),
                            TRUE) . ".odt";
        }
        
        header('Content-type: application/vnd.oasis.opendocument.text');
        header('Content-Disposition: attachment; filename="'.$name.'"');
        readfile($this->tmpfile);
    }

    /**
     * Returns a variable of configuration
     *
     * @return string The requested variable of configuration
     */
    public function getConfig($configKey)
    {
        if (array_key_exists($configKey, $this->config)) {
            return $this->config[$configKey];
        }
        return false;
    }

    /**
     * Returns the temporary working file
     *
     * @return string le chemin vers le fichier temporaire de travail
     */
    public function getTmpfile()
    {
        return $this->tmpfile;
    }


    /**
     * Recursive htmlspecialchars
     */
    protected function recursiveHtmlspecialchars($value)
    {
        if (is_array($value)) {
            return array_map(array($this, 'recursiveHtmlspecialchars'), $value);
        } else {
            return htmlspecialchars($value);
        }
    }
}
