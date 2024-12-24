<?php

namespace Drupal\Tests\Odtphp;

use PHPUnit\Framework\TestCase;
use Odtphp\Odf;
use Odtphp\Exceptions\OdfException;

require_once 'OdtTestCase.php';

class ImageTest extends OdtTestCase {
    public function testImageInsertion(): void {
        $config = $this->odfPclZipConfig();
        $odf = new Odf(__DIR__ . "/files/input/ImageTest.odt", $config);
        $odf->setImage('test_image', __DIR__ . "/files/images/voronoi_sphere.png");
        $output_name = __DIR__ . "/files/output/ImageTestOutput.odt";
        $odf->saveToDisk($output_name);
        $this->assertFileExists($output_name);
        $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/gold_phpzip/ImageTestGold.odt"));
        unlink($output_name);
    }

    public function testImageResizing(): void {
        $config = $this->odfPclZipConfig();
        $odf = new Odf(__DIR__ . "/files/input/ImageTest.odt", $config);
        $odf->setImage('test_image', __DIR__ . "/files/images/test.jpg", -1, 100, 100);
        $output_name = __DIR__ . "/files/output/ImageTestResizedOutput.odt";
        $odf->saveToDisk($output_name);
        $this->assertFileExists($output_name);
        $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/gold_phpzip/ImageTestResizedGold.odt"));
        unlink($output_name);
    }

    public function testInvalidImagePath(): void {
        $config = $this->odfPclZipConfig();
        $odf = new Odf(__DIR__ . "/files/input/ImageTest.odt", $config);
        $this->expectException(OdfException::class);
        $odf->setImage('test_image', __DIR__ . "/files/images/nonexistent.png");
    }
}
