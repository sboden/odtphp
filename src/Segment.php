<?php

namespace Odtphp;

use Odtphp\SegmentIterator;
use Odtphp\Exceptions\SegmentException;
use Odtphp\Exceptions\OdfException;

/**
 * Class for handling templating segments with ODT files.
 *
 * You need PHP 8.1 at least.
 * You need Zip Extension or PclZip library.
 * Encoding: ISO-8859-1.
 *
 * @copyright GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license http://www.gnu.org/copyleft/gpl.html GPL License
 * @version 1.3
 */
class Segment implements \IteratorAggregate, \Countable {
  /**
   * XML content of the segment.
   *
   * @var string
   */
  protected $xml;

  /**
   * Parsed XML content of the segment.
   *
   * @var string
   */
  protected $xmlParsed = '';

  /**
   * Name of the segment.
   *
   * @var string
   */
  protected $name;

  /**
   * Child segments of this segment.
   *
   * @var Segment[]
   */
  protected $children = [];

  /**
   * Variables to be replaced in the segment.
   *
   * @var array
   */
  protected $vars = [];

  /**
   * Manifest variables for the segment.
   *
   * @var array
   */
  public $manifestVars = [];

  /**
   * Images used in the segment.
   *
   * @var array
   */
  protected $images = [];

  /**
   * ODT file object.
   *
   * @var object
   */
  protected $odf;

  /**
   * Zip file handler.
   *
   * @var object
   */
  protected $file;

  /**
   * Constructor.
   *
   * @param string $name
   *   Name of the segment to construct.
   * @param string $xml
   *   XML tree of the segment.
   * @param object $odf
   *   ODT file object.
   */
  public function __construct($name, $xml, $odf) {
    $this->name = (string) $name;
    $this->xml = (string) $xml;
    $this->odf = $odf;
    $zipHandler = $this->odf->getConfig('ZIP_PROXY');
    $this->file = new $zipHandler();
    $this->analyseChildren($this->xml);
  }

  /**
   * Returns the name of the segment.
   *
   * @return string
   *   The name of the segment.
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Checks if the segment has children.
   *
   * @return bool
   *   TRUE if the segment has children, FALSE otherwise.
   */
  public function hasChildren(): bool {
    return !empty($this->children);
  }

  /**
   * Implements the Countable interface.
   *
   * @return int
   *   Number of children in the segment.
   */
  #[\ReturnTypeWillChange]
  public function count(): int {
    return count($this->children);
  }

  /**
   * Implements the IteratorAggregate interface.
   *
   * @return \RecursiveIteratorIterator
   *   Iterator for the segment's children.
   */
  #[\ReturnTypeWillChange]
  public function getIterator(): \RecursiveIteratorIterator {
    return new \RecursiveIteratorIterator(new SegmentIterator($this->children), 1);
  }

  /**
   * Replace variables of the template in the XML code.
   *
   * All the children are also processed.
   *
   * @return string
   *   The merged XML content with variables replaced.
   */
  public function merge(): string {
    $this->xmlParsed .= str_replace(array_keys($this->vars), array_values($this->vars), $this->xml);
    if ($this->hasChildren()) {
      foreach ($this->children as $child) {
        $this->xmlParsed = str_replace($child->xml, ($child->xmlParsed == "") ? $child->merge() : $child->xmlParsed, $this->xmlParsed);
        $child->xmlParsed = '';
        // Store all image names used in child segments in current segment array.
        foreach ($child->manifestVars as $file) {
          $this->manifestVars[] = $file;
        }

        $child->manifestVars = [];
      }
    }
    $reg = "/\[!--\sBEGIN\s$this->name\s--\](.*)\[!--\sEND\s$this->name\s--\]/smU";
    $this->xmlParsed = preg_replace($reg, '$1', $this->xmlParsed);
    $this->file->open($this->odf->getTmpfile());
    foreach ($this->images as $imageKey => $imageValue) {
      if ($this->file->getFromName('Pictures/' . $imageValue) === FALSE) {
        $this->file->addFile($imageKey, 'Pictures/' . $imageValue);
      }
    }

    $this->file->close();
    return $this->xmlParsed;
  }

  /**
   * Analyse the XML code to find children segments.
   *
   * @param string $xml
   *   XML content to analyse.
   *
   * @return $this
   *   The current segment instance.
   */
  protected function analyseChildren($xml): self {
    $reg2 = "#\[!--\sBEGIN\s([\S]*)\s--\](.*)\[!--\sEND\s(\\1)\s--\]#smU";
    preg_match_all($reg2, $xml, $matches);
    for ($i = 0, $size = count($matches[0]); $i < $size; $i++) {
      if ($matches[1][$i] != $this->name) {
        $this->children[$matches[1][$i]] = new self($matches[1][$i], $matches[0][$i], $this->odf);
      }
      else {
        $this->analyseChildren($matches[2][$i]);
      }
    }
    return $this;
  }

  /**
   * Assign a template variable to replace.
   *
   * @param string $key
   *   The variable key to replace.
   * @param string $value
   *   The value to replace the variable with.
   * @param bool $encode
   *   Whether to HTML encode the value.
   * @param string $charset
   *   Character set for encoding.
   *
   * @throws \Odtphp\Exceptions\SegmentException
   *   If the variable is not found in the segment.
   *
   * @return $this
   *   The current segment instance.
   */
  public function setVars($key, $value, $encode = TRUE, $charset = 'UTF-8'): self {
    if (strpos($this->xml, $this->odf->getConfig('DELIMITER_LEFT') . $key . $this->odf->getConfig('DELIMITER_RIGHT')) === FALSE) {
      throw new SegmentException("var $key not found in {$this->getName()}");
    }
    $value = $encode ? htmlspecialchars($value) : $value;
    $value = ($charset != 'UTF-8') ? mb_convert_encoding($value, 'UTF-8', $charset) : $value;
    $this->vars[$this->odf->getConfig('DELIMITER_LEFT') . $key . $this->odf->getConfig('DELIMITER_RIGHT')] = str_replace("\n", "<text:line-break/>", $value);
    return $this;
  }

  /**
   * Assign a template variable as a picture.
   *
   * @param string $key
   *   Name of the variable within the template.
   * @param string $value
   *   Path to the picture.
   * @param int|null $page
   *   Anchor to page number (or -1 if anchor-type is aschar).
   * @param string|null $width
   *   Width of picture (keep original if null).
   * @param string|null $height
   *   Height of picture (keep original if null).
   * @param string|null $offsetX
   *   Offset by horizontal (not used if $page = -1).
   * @param string|null $offsetY
   *   Offset by vertical (not used if $page = -1).
   *
   * @throws \Odtphp\Exceptions\OdfException
   *   If the image is invalid.
   *
   * @return $this
   *   The current segment instance.
   */
  public function setImage($key, $value, $page = -1, $width = NULL, $height = NULL, $offsetX = NULL, $offsetY = NULL): self {
    $filename = strtok(strrchr($value, '/'), '/.');
    $file = substr(strrchr($value, '/'), 1);
    $size = @getimagesize($value);
    if ($size === FALSE) {
      throw new OdfException("Invalid image");
    }
    if (!$width && !$height) {
      [$width, $height] = $size;
      $width *= $this->odf->getPixelToCm();
      $height *= $this->odf->getPixelToCm();
    }
    $anchor = $page == -1 ? 'text:anchor-type="as-char"' : "text:anchor-type=\"page\" text:anchor-page-number=\"{$page}\" svg:x=\"{$offsetX}cm\" svg:y=\"{$offsetY}cm\"";
    $xml = <<<IMG
<draw:frame draw:style-name="fr1" draw:name="$filename" {$anchor} svg:width="{$width}cm" svg:height="{$height}cm" draw:z-index="3"><draw:image xlink:href="Pictures/$file" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/></draw:frame>
IMG;
    $this->images[$value] = $file;
    $this->manifestVars[] = $file;
    $this->setVars($key, $xml, FALSE);
    return $this;
  }

  /**
   * Shortcut to retrieve a child.
   *
   * @param string $prop
   *   The name of the child segment to retrieve.
   *
   * @return Segment
   *   The child segment instance if it exists.
   *
   * @throws \Odtphp\Exceptions\SegmentException
   *   If the child segment does not exist.
   */
  public function __get($prop): Segment {
    if (array_key_exists($prop, $this->children)) {
      return $this->children[$prop];
    }
    else {
      throw new SegmentException('child ' . $prop . ' does not exist');
    }
  }

  /**
   * Proxy for setVars.
   *
   * @param string $meth
   *   The method name being called.
   * @param array $args
   *   The arguments passed to the method.
   *
   * @return Segment
   *   The current segment instance after setting variables.
   *
   * @throws \Odtphp\Exceptions\SegmentException
   *   If the method or variable does not exist.
   */
  public function __call($meth, $args): Segment {
    try {
      array_unshift($args, $meth);
      return call_user_func_array([$this, 'setVars'], $args);
    }
    catch (SegmentException $e) {
      throw new SegmentException("method $meth nor var $meth exist");
    }
  }

  /**
   * Retrieve the parsed XML content.
   *
   * @return string
   *   The parsed XML content of the segment.
   */
  public function getXmlParsed(): string {
    return $this->xmlParsed;
  }

  /**
   * Create a new child segment.
   *
   * @param string $name
   *   Name of the child segment.
   *
   * @return Segment
   *   The child segment with the specified name.
   *
   * @throws \Odtphp\Exceptions\SegmentException
   *   If the segment does not exist.
   */
  public function setSegment($name): Segment {
    if (!isset($this->children[$name])) {
      throw new SegmentException("Segment '$name' does not exist");
    }
    return $this->children[$name];
  }

  /**
   * Retrieve the XML content of the segment.
   *
   * @return string
   *   The original XML content of the segment.
   */
  public function getXml(): string {
    return $this->xml;
  }

  /**
   * Retrieve the segment's children.
   *
   * @return Segment[]
   *   An array of child segments.
   */
  public function getChildren(): array {
    return $this->children;
  }

  /**
   * Retrieve the segment's variables.
   *
   * @return array
   *   An array of variables in the segment.
   */
  public function getVars(): array {
    return $this->vars;
  }

}
