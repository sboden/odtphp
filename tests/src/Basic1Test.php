<?php

namespace Drupal\Tests\Odtphp;

use PHPUnit\Framework\TestCase;
use Odtphp\Odf;

require_once 'OdtTestCase.php';

class Basic1Test extends OdtTestCase {

  public function basic1($type): void {

    if ($type === self::PHPZIP_TYPE) {
      $config = $this->odfPhpZipConfig();
      $gold_dir = 'gold_phpzip';
      $type_name = 'PhpZip';
    }
    else {
      $config = $this->odfPclZipConfig();
      $gold_dir = 'gold_pclzip';
      $type_name = 'PclZip';
    }

    $input_file = __DIR__ . '/files/input/BasicTest1.odt';
    $odf = new Odf($input_file, $config);

    $odf->setVars('titre',
      'PHP: Hypertext Preprocessor');

    $message = "PHP (sigle de PHP: Hypertext Preprocessor), est un langage de scripts libre 
principalement utilisé pour produire des pages Web dynamiques via un serveur HTTP, mais 
pouvant également fonctionner comme n'importe quel langage interprété de façon locale, 
en exécutant les programmes en ligne de commande.";

    $odf->setVars('message', $message, TRUE, 'UTF-8');

    $output_name = __DIR__ . "/files/output/BasicTest1" . $type_name . "Output.odt";
    // We export the file
    $odf->saveToDisk($output_name);

    // print("\nComparing files:\n  $output_name\n  files/$gold_dir/BasicTest1Gold.odt\n");
    $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/$gold_dir/BasicTest1Gold.odt"));

    unlink($output_name);
  }

  public function testBasicPclZip1(): void {
    $this->basic1(self::PCLZIP_TYPE);
  }

  public function testBasicPhpZip1(): void {
    $this->basic1(self::PHPZIP_TYPE);
  }

}
