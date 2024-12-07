<?php

namespace Drupal\Tests\Odtphp;

use PHPUnit\Framework\TestCase;
use Odtphp\Odf;

require_once 'OdtTestCase.php';

class ImageTest extends OdtTestCase {
    public function testImageInsertion(): void {
        $config = $this->odfPhpZipConfig();
        $odf = new Odf("files/input/ImageTest.odt", $config);
        
        $odf->setImage('test_image', 'files/images/test.jpg');
        
        $output_name = "files/output/ImageTestOutput.odt";
        $odf->saveToDisk($output_name);
        
        $this->assertTrue($this->odtFilesAreIdentical($output_name, "files/gold_phpzip/ImageTestGold.odt"));
        unlink($output_name);
    }

    public function testImageResizing(): void {
        $config = $this->odfPhpZipConfig();
        $odf = new Odf("files/input/ImageTest.odt", $config);
        
        $odf->setImage('test_image', 'files/images/test.jpg', -1, 100, 100);
        
        $output_name = "files/output/ImageTestResizedOutput.odt";
        $odf->saveToDisk($output_name);
        
        $this->assertTrue($this->odtFilesAreIdentical($output_name, "files/gold_phpzip/ImageTestResizedGold.odt"));
        unlink($output_name);
    }

    public function testInvalidImagePath(): void {
        $config = $this->odfPhpZipConfig();
        $odf = new Odf("files/input/ImageTest.odt", $config);
        
        $this->expectException(\Exception::class);
        $odf->setImage('test_image', 'nonexistent/image.jpg');
    }
}
