<?php

namespace Odtphp\Zip;

use Odtphp\Zip\ZipInterface;
use Odtphp\Exceptions\PclZipProxyException;

/**
 * Proxy class for the PclZip library
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 * Encoding : ISO-8859-1
 * Last commit by $Author: neveldo $
 * Date - $Date: 2009-05-29 10:05:11 +0200 (ven., 29 mai 2009) $
 * SVN Revision - $Rev: 28 $
 * Id : $Id: odf.php 28 2009-05-29 08:05:11Z neveldo $
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.3
 */
class PclZipProxy implements ZipInterface
{
  protected $tmp_dir;
  protected $openned = false;
  protected $filename;
  protected $pclzip;
  /**
   * Class constructor
   *
   * @throws PclZipProxyException
   */
  public function __construct()
  {
    if (!class_exists('PclZip')) {
      throw new PclZipProxyException('PclZip class not loaded - PclZip library is required for using PclZipProxy');
    }

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
    $this->tmp_dir = sys_get_temp_dir() . '/tmpdir_odtphp_' . uniqid(sprintf('%04X%04X%04X%04X%d',
        mt_rand(0, 65535), mt_rand(0, 65535),
        mt_rand(0, 65535), mt_rand(0, 65535), $pseudo_pid),
        TRUE);
  }
  /**
   * Open a Zip archive
   *
   * @param string $filename the name of the archive to open
   * @return true if openning has succeeded
   */
  public function open($filename)
  {
    if (true === $this->openned) {
      $this->close();
    }
    $this->filename = $filename;
    $this->pclzip = new \PclZip($this->filename);
    if (!file_exists($this->tmp_dir)) {
      if (mkdir($this->tmp_dir)) {
        // Created a new directory.
        $this->openned = true;
        return true;
      }
      else {
        // Failed to create a directory.
        $this->openned = false;
        return false;
      }
    }
    else {
      // Directory already existed.
      $this->openned = false;
      return false;
    }
  }

  /**
   * Retrieve the content of a file within the archive from its name
   *
   * @param string $name the name of the file to extract
   * @return the content of the file in a string
   */
  public function getFromName($name)
  {
    if (false === $this->openned) {
      return false;
    }
    $name = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $name);
    $extraction = $this->pclzip->extract(PCLZIP_OPT_BY_NAME, $name, PCLZIP_OPT_EXTRACT_AS_STRING);
    if (!empty($extraction)) {
      return $extraction[0]['content'];
    }
    return false;
  }

  /**
   * Add a file within the archive from a string
   *
   * @param string $localname the local path to the file in the archive
   * @param string $contents the content of the file
   * @return true if the file has been successful added
   */
  public function addFromString($localname, $contents)
  {
    if (false === $this->openned) {
      return false;
    }
    if (file_exists($this->filename) && !is_writable($this->filename)) {
      return false;
    }
    $localname = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $localname);
    $localpath = dirname($localname);
    $tmpfilename = $this->tmp_dir . '/' . basename($localname);
    if (false !== file_put_contents($tmpfilename, $contents)) {
      $this->pclzip->delete(PCLZIP_OPT_BY_NAME, $localname);
      $add = $this->pclzip->add($tmpfilename, PCLZIP_OPT_REMOVE_PATH, $this->tmp_dir, PCLZIP_OPT_ADD_PATH, $localpath);
      unlink($tmpfilename);
      if (!empty($add)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Add a file within the archive from a file
   *
   * @param string $filename the path to the file we want to add
   * @param string $localname the local path to the file in the archive
   * @return true if the file has been successful added
   */
  public function addFile($filename, $localname = null)
  {
    if (false === $this->openned) {
      return false;
    }
    if ((file_exists($this->filename) && !is_writable($this->filename))
      || !file_exists($filename)) {
      return false;
    }
    if (isset($localname)) {
      $localname = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $localname);
      $localpath = dirname($localname);
      $tmpfilename = $this->tmp_dir . '/' . basename($localname);
    } else {
      $localname = basename($filename);
      $tmpfilename = $this->tmp_dir . '/' . $localname;
      $localpath = '';
    }
    if (file_exists($filename)) {
      copy($filename, $tmpfilename);
      $this->pclzip->delete(PCLZIP_OPT_BY_NAME, $localname);
      $this->pclzip->add($tmpfilename, PCLZIP_OPT_REMOVE_PATH, $this->tmp_dir, PCLZIP_OPT_ADD_PATH, $localpath);
      unlink($tmpfilename);
      return true;
    }
    return false;
  }

  /**
   * Close the Zip archive
   * @return true
   */
  public function close()
  {
    if (false === $this->openned) {
      return false;
    }
    $this->pclzip = $this->filename = null;
    $this->openned = false;
    if (file_exists($this->tmp_dir)) {
      $this->_rrmdir($this->tmp_dir);
      rmdir($this->tmp_dir);
    }
    return true;
  }

  /**
   * Empty the temporary working directory recursively
   * @param $dir the temporary working directory
   * @return void
   */
  private function _rrmdir($dir)
  {
    if ($handle = opendir($dir)) {
      while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
          if (is_dir($dir . '/' . $file)) {
            $this->_rrmdir($dir . '/' . $file);
            rmdir($dir . '/' . $file);
          } else {
            unlink($dir . '/' . $file);
          }
        }
      }
      closedir($handle);
    }
  }
}
