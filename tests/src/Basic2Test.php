<?php

namespace Drupal\Tests\Odtphp;

use PHPUnit\Framework\TestCase;
use Odtphp\Odf;

require_once 'OdtTestCase.php';

class Basic2Test extends OdtTestCase {

  public function basic2($type): void {

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

    $odf = new Odf("files/input/BasicTest2.odt", $config);


    $odf->setVars('titre','Anaska formation');
    $message = "Anaska, leader Fran�ais de la formation informatique sur les technologies 
Open Source, propose un catalogue de plus de 50 formations dont certaines pr�parent 
aux certifications Linux, MySQL, PHP et PostgreSQL.";

    $odf->setVars('message', $message, TRUE, 'UTF8');

    $odf->setImage('image', 'files/images/anaska.jpg');

    $output_name = "files/output/BasicTest2" . $type_name . "Output.odt";
    // We export the file
    $odf->saveToDisk($output_name);

    //print("\nComparing files:\n  $output_name\n  files/$gold_dir/BasicTest2Gold.odt\n");
    $this->assertTrue($this->odtFilesAreIdentical($output_name, "files/$gold_dir/BasicTest2Gold.odt"));

    unlink($output_name);
  }

  public function testBasicPclZip2(): void {
    $this->basic2(self::PCLZIP_TYPE);
  }

  public function testBasicPhpZip2(): void {
    $this->basic2(self::PHPZIP_TYPE);
  }

}
