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
        // This is actually 100cmx100cm of the original image.
        $odf->setImage('test_image', __DIR__ . "/files/images/test.jpg", -1, 100, 100);
        $output_name = __DIR__ . "/files/output/ImageTestResizedOutput.odt";
        $odf->saveToDisk($output_name);
        $this->assertFileExists($output_name);
        $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/gold_phpzip/ImageTestResizedGold.odt"));
        unlink($output_name);
    }

    public function testImageResizingMm(): void {
        $config = $this->odfPclZipConfig();
        $odf = new Odf(__DIR__ . "/files/input/ImageTest.odt", $config);
        $odf->setImageMm('test_image', __DIR__ . "/files/images/test.jpg", -1);
        // 50mm x 50mm of the original image.
        $odf->setImageMm('small_png', __DIR__ . "/files/images/test.jpg", -1, 50, 50);
        // 100mm x 100mm of the original image.
        $odf->setImageMm('large_jpg', __DIR__ . "/files/images/test.jpg", -1, 100, 100);       
        $output_name = __DIR__ . "/files/output/ImageTestResizedMmOutput.odt";
        $odf->saveToDisk($output_name);
        $this->assertFileExists($output_name);
        $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/gold_phpzip/ImageTestResizedMmGold.odt"));
        unlink($output_name);
    }

    public function testImageResizingPixel(): void {
        $config = $this->odfPclZipConfig();
        $odf = new Odf(__DIR__ . "/files/input/ImageTest.odt", $config);
        $odf->setImagePixel('test_image', __DIR__ . "/files/images/test.jpg", -1);
        // About 50mm x 50mm of the original image in pixels.
        $odf->setImagePixel('small_png', __DIR__ . "/files/images/test.jpg", -1, 188.97637795, 188.97637795);
        // About 100mm x 100mm of the original image in pixels.
        $odf->setImagePixel('large_jpg', __DIR__ . "/files/images/test.jpg", -1, 377.95275591, 377.95275591);       
        $output_name = __DIR__ . "/files/output/ImageTestResizedPixelOutput.odt";
        $odf->saveToDisk($output_name);
        $this->assertFileExists($output_name);
        $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/gold_phpzip/ImageTestResizedPixelGold.odt"));
        unlink($output_name);
    }

    public function testImageResizingMmOffsetIgnored(): void {
        $config = $this->odfPclZipConfig();
        $odf = new Odf(__DIR__ . "/files/input/ImageTest.odt", $config);
        $odf->setImageMm('test_image', __DIR__ . "/files/images/test.jpg", -1);
        // 50mm x 50mm of the original image.
        $odf->setImageMm('small_png', __DIR__ . "/files/images/test.jpg", -1, 50, 50, 50, 100);
        // 100mm x 100mm of the original image.
        $odf->setImageMm('large_jpg', __DIR__ . "/files/images/test.jpg", -1, 100, 100, 80, 70);       
        $output_name = __DIR__ . "/files/output/ImageTestResizedMmOffsetIgnoredOutput.odt";
        $odf->saveToDisk($output_name);
        $this->assertFileExists($output_name);
        $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/gold_phpzip/ImageTestResizedMmOffsetIgnoredGold.odt"));
        unlink($output_name);
    }

    public function testImageResizingPixelOffsetIgnored(): void {
        $config = $this->odfPclZipConfig();
        $odf = new Odf(__DIR__ . "/files/input/ImageTest.odt", $config);
        $odf->setImagePixel('test_image', __DIR__ . "/files/images/test.jpg", -1);
        // About 50mm x 50mm of the original image in pixels.
        $odf->setImagePixel('small_png', __DIR__ . "/files/images/test.jpg", -1, 188.97637795, 188.97637795, 50, 100);
        // About 100mm x 100mm of the original image in pixels.
        $odf->setImagePixel('large_jpg', __DIR__ . "/files/images/test.jpg", -1, 377.95275591, 377.95275591, 80, 70);       
        $output_name = __DIR__ . "/files/output/ImageTestResizedPixelOffsetIgnoredOutput.odt";
        $odf->saveToDisk($output_name);
        $this->assertFileExists($output_name);
        $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/gold_phpzip/ImageTestResizedPixelOffsetIgnoredGold.odt"));
        unlink($output_name);
    }

    public function testImageResizingMmOffset(): void {
        $config = $this->odfPclZipConfig();
        $odf = new Odf(__DIR__ . "/files/input/ImageTest.odt", $config);
        $odf->setImageMm('test_image', __DIR__ . "/files/images/test.jpg", 1, 100, 100, 80, 70);       
        $output_name = __DIR__ . "/files/output/ImageTestResizedMmOffsetOutput.odt";
        $odf->saveToDisk($output_name);
        $this->assertFileExists($output_name);
        $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/gold_phpzip/ImageTestResizedMmOffsetGold.odt"));
        unlink($output_name);
    }

    public function testImageResizingPixelOffset(): void {
        $config = $this->odfPclZipConfig();
        $odf = new Odf(__DIR__ . "/files/input/ImageTest.odt", $config);
        $odf->setImagePixel('test_image', __DIR__ . "/files/images/test.jpg", 1, 377.95275591, 377.95275591, 80, 70);       
        $output_name = __DIR__ . "/files/output/ImageTestResizedPixelOffsetOutput.odt";
        $odf->saveToDisk($output_name);
        $this->assertFileExists($output_name);
        $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/gold_phpzip/ImageTestResizedPixelOffsetGold.odt"));
        unlink($output_name);
    }


    public function testInvalidImagePath(): void {
        $config = $this->odfPclZipConfig();
        $odf = new Odf(__DIR__ . "/files/input/ImageTest.odt", $config);
        $this->expectException(OdfException::class);
        $odf->setImage('test_image', __DIR__ . "/files/images/nonexistent.png");
    }
}
