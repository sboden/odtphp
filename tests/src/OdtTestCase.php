<?php

namespace Drupal\Tests\Odtphp;

use PHPUnit\Framework\TestCase;
use DOMDocument;
use Exception;

// Make sure you have Zip extension or PclZip library loaded
// First : include the library
#require_once '../../vendor/autoload.php';

class OdtTestCase extends TestCase {

  const PHPZIP_TYPE = 1;
  const PCLZIP_TYPE = 2;

  protected function odfPhpZipConfig() {
    return [
      'ZIP_PROXY' => \Odtphp\Zip\PhpZipProxy::class,
      'DELIMITER_LEFT' => '{',
      'DELIMITER_RIGHT' => '}',
    ];
  }

  protected function odfPclZipConfig() {
    return [
      'ZIP_PROXY' => \Odtphp\Zip\PclZipProxy::class,
      'DELIMITER_LEFT' => '{',
      'DELIMITER_RIGHT' => '}',
    ];
  }


  protected function extractText($filename) {
    $file_parts = explode('.', $filename);
    $ext = end($file_parts);
    if ($ext == 'docx') {
      $dataFile = "word/document.xml";
    }
    else {
      $dataFile = "content.xml";
    }
    $zip = new \ZipArchive;
    if (TRUE === $zip->open($filename)) {
      if (($index = $zip->locateName($dataFile)) !== FALSE) {
        // Index found! Now read it to a string
        $text = $zip->getFromIndex($index);
        // Load XML from a string
        // Ignore errors and warnings
        $doc = new DOMDocument(); 
        $rcode = $doc->loadXML($text,
          LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
        // Remove XML formatting tags and return the text
        return strip_tags($doc->saveXML());
      }
      // Close the archive file
      $zip->close();
    }
    // In case of failure return a message
    return "File not found";
  }


  function odtFilesAreIdentical($file1, $file2)
  {
    // Ensure both files exist
    if (!file_exists($file1)) {
        throw new Exception("File $file1 does not exist.");
    }

    if (!file_exists($file2)) {
      throw new Exception("File $file2 does not exist.");
    }

    // Open the first .odt file as a ZIP archive and extract content.xml
    $zip1 = new \ZipArchive;
    $zip2 = new \ZipArchive;
    
    if ($zip1->open($file1) !== true) {
        throw new Exception("Unable to open file: " . $file1);
    }
    
    if ($zip2->open($file2) !== true) {
        $zip1->close();
        throw new Exception("Unable to open file: " . $file2);
    }

    // Read the content.xml files from both .odt files
    $content1 = $zip1->getFromName("content.xml");
    $content2 = $zip2->getFromName("content.xml");

    // Close the archives
    $zip1->close();
    $zip2->close();

    // Compare the contents of content.xml
    return $content1 === $content2;
  }
}
