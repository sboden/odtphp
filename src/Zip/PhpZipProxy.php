<?php

namespace Odtphp\Zip;

use Odtphp\Zip\ZipInterface;
use Odtphp\Exceptions\PhpZipProxyException;

/**
 * Proxy class for the PHP Zip Extension.
 *
 * You need PHP 8.1 at least.
 * You need Zip Extension or PclZip library.
 * Encoding: ISO-8859-1.
 *
 * @copyright GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license http://www.gnu.org/copyleft/gpl.html GPL License
 * @version 1.3
 */
class PhpZipProxy implements ZipInterface {

  /**
   * ZipArchive instance for handling zip operations.
   *
   * @var \ZipArchive
   */
  protected $zipArchive;

  /**
   * Path to the current zip file.
   *
   * @var string
   */
  protected $filename;

  /**
   * Class constructor.
   *
   * @throws \Odtphp\Exceptions\PhpZipProxyException
   *   When Zip extension is not loaded.
   */
  public function __construct() {
    if (!class_exists('ZipArchive')) {
      throw new PhpZipProxyException('Zip extension not loaded - check your php settings, PHP5.2 minimum with zip extension is required for using PhpZipProxy');
    }
    $this->zipArchive = new \ZipArchive();
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
    $this->filename = $filename;
    return $this->zipArchive->open($filename, \ZIPARCHIVE::CREATE);
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
    return $this->zipArchive->getFromName($name);
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
    if (file_exists($this->filename) && !is_writable($this->filename)) {
      return FALSE;
    }
    return $this->zipArchive->addFromString($localname, $contents);
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
    if ((file_exists($this->filename) && !is_writable($this->filename))
      || !file_exists($filename)) {
      return FALSE;
    }
    return $this->zipArchive->addFile($filename, $localname);
  }

  /**
   * Close the Zip archive.
   *
   * @return bool
   *   TRUE if the archive was closed successfully.
   */
  public function close() {
    return $this->zipArchive->close();
  }

}
