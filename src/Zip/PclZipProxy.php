<?php

namespace Odtphp\Zip;

use Odtphp\Zip\ZipInterface;
use Odtphp\Exceptions\PclZipProxyException;

/**
 * Proxy class for the PclZip library.
 *
 * You need PHP 8.1 at least.
 * You need Zip Extension or PclZip library.
 * Encoding: ISO-8859-1.
 *
 * @copyright GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license http://www.gnu.org/copyleft/gpl.html GPL License
 * @version 1.3
 */
class PclZipProxy implements ZipInterface {

  /**
   * Temporary directory path for processing zip files.
   *
   * @var string
   */
  protected $tmpDir;

  /**
   * Flag indicating if the zip file is opened.
   *
   * @var bool
   */
  protected $opened = FALSE;

  /**
   * Path to the current zip file.
   *
   * @var string
   */
  protected $filename;

  /**
   * PclZip instance for handling zip operations.
   *
   * @var \PclZip
   */
  protected $pclzip;

  /**
   * Class constructor.
   *
   * @throws \Odtphp\Exceptions\PclZipProxyException
   *   When PclZip library is not loaded.
   */
  public function __construct() {
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
    $this->tmpDir = sys_get_temp_dir() . '/tmpdir_odtphp_' . uniqid(sprintf('%04X%04X%04X%04X%d',
        mt_rand(0, 65535), mt_rand(0, 65535),
        mt_rand(0, 65535), mt_rand(0, 65535), $pseudo_pid),
        TRUE);
  }

  /**
   * Open a Zip archive.
   *
   * @param string $filename
   *   The name of the archive to open.
   *
   * @return bool
   *   TRUE if opening has succeeded.
   */
  public function open($filename) {
    if (TRUE === $this->opened) {
      $this->close();
    }
    $this->filename = $filename;
    $this->pclzip = new \PclZip($this->filename);
    if (!file_exists($this->tmpDir)) {
      if (mkdir($this->tmpDir)) {
        // Created a new directory.
        $this->opened = TRUE;
        return TRUE;
      }
      else {
        // Failed to create a directory.
        $this->opened = FALSE;
        return FALSE;
      }
    }
    else {
      // Directory already existed.
      $this->opened = FALSE;
      return FALSE;
    }
  }

  /**
   * Retrieve the content of a file within the archive from its name.
   *
   * @param string $name
   *   The name of the file to extract.
   *
   * @return string|bool
   *   The content of the file as a string, or FALSE if retrieval fails.
   */
  public function getFromName($name) {
    if (FALSE === $this->opened) {
      return FALSE;
    }
    $name = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $name);
    $extraction = $this->pclzip->extract(PCLZIP_OPT_BY_NAME, $name, PCLZIP_OPT_EXTRACT_AS_STRING);
    if (!empty($extraction)) {
      return $extraction[0]['content'];
    }
    return FALSE;
  }

  /**
   * Add a file within the archive from a string.
   *
   * @param string $localname
   *   The local path to the file in the archive.
   * @param string $contents
   *   The content of the file.
   *
   * @return bool
   *   TRUE if the file has been successfully added.
   */
  public function addFromString($localname, $contents) {
    if (FALSE === $this->opened) {
      return FALSE;
    }
    if (file_exists($this->filename) && !is_writable($this->filename)) {
      return FALSE;
    }
    $localname = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $localname);
    $localpath = dirname($localname);
    $tmpfilename = $this->tmpDir . '/' . basename($localname);
    if (FALSE !== file_put_contents($tmpfilename, $contents)) {
      $this->pclzip->delete(PCLZIP_OPT_BY_NAME, $localname);
      $add = $this->pclzip->add($tmpfilename, PCLZIP_OPT_REMOVE_PATH, $this->tmpDir, PCLZIP_OPT_ADD_PATH, $localpath);
      unlink($tmpfilename);
      if (!empty($add)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Add a file within the archive from a file.
   *
   * @param string $filename
   *   The path to the file we want to add.
   * @param string|null $localname
   *   The local path to the file in the archive.
   *
   * @return bool
   *   TRUE if the file has been successfully added.
   */
  public function addFile($filename, $localname = NULL) {
    if (FALSE === $this->opened) {
      return FALSE;
    }
    if ((file_exists($this->filename) && !is_writable($this->filename))
      || !file_exists($filename)) {
      return FALSE;
    }
    if (isset($localname)) {
      $localname = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $localname);
      $localpath = dirname($localname);
      $tmpfilename = $this->tmpDir . '/' . basename($localname);
    }
    else {
      $localname = basename($filename);
      $tmpfilename = $this->tmpDir . '/' . $localname;
      $localpath = '';
    }
    if (file_exists($filename)) {
      copy($filename, $tmpfilename);
      $this->pclzip->delete(PCLZIP_OPT_BY_NAME, $localname);
      $this->pclzip->add($tmpfilename, PCLZIP_OPT_REMOVE_PATH, $this->tmpDir, PCLZIP_OPT_ADD_PATH, $localpath);
      unlink($tmpfilename);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Close the Zip archive.
   *
   * @return bool
   *   TRUE if the archive was closed successfully.
   */
  public function close() {
    if (FALSE === $this->opened) {
      return FALSE;
    }
    $this->pclzip = $this->filename = NULL;
    $this->opened = FALSE;
    if (file_exists($this->tmpDir)) {
      $this->removeDir($this->tmpDir);
    }
    return TRUE;
  }

  /**
   * Remove directory recursively.
   *
   * @param string $dir
   *   The directory to remove.
   *
   * @return bool
   *   TRUE if the directory was successfully removed.
   */
  private function removeDir($dir): bool {
    if ($handle = opendir($dir)) {
      while (FALSE !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
          if (is_dir($dir . '/' . $file)) {
            $this->removeDir($dir . '/' . $file);
            rmdir($dir . '/' . $file);
          }
          else {
            unlink($dir . '/' . $file);
          }
        }
      }
      closedir($handle);
    }
    return rmdir($dir);
  }

}
