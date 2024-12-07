<?php

namespace Drupal\Tests\Odtphp;

use PHPUnit\Framework\TestCase;
use Odtphp\Odf;

require_once 'OdtTestCase.php';

class VariableTest extends OdtTestCase {
    public function testVariableExists(): void {
        $config = $this->odfPhpZipConfig();
        $odf = new Odf("files/input/VariableTest.odt", $config);
        
        $this->assertTrue($odf->variableExists('test_var'));
        $this->assertFalse($odf->variableExists('nonexistent_var'));
    }

    public function testSpecialCharacterEncoding(): void {
        $config = $this->odfPhpZipConfig();
        $odf = new Odf("files/input/VariableTest.odt", $config);
        
        $specialChars = "<>&'\"";
        $odf->setVars('test_var', $specialChars, true);
        
        $output_name = "files/output/VariableTestOutputSpecialChars.odt";
        $odf->saveToDisk($output_name);
        
        $this->assertTrue($this->odtFilesAreIdentical($output_name, "files/gold_phpzip/VariableTestSpecialCharsGold.odt"));
        unlink($output_name);
    }

    public function testMultilineText(): void {
        $config = $this->odfPhpZipConfig();
        $odf = new Odf("files/input/VariableTest.odt", $config);
        
        $multiline = "Line 1\nLine 2\nLine 3";
        $odf->setVars('test_var', $multiline, true);
        
        $output_name = "files/output/VariableTestOutputMultiline.odt";
        $odf->saveToDisk($output_name);
        
        $this->assertTrue($this->odtFilesAreIdentical($output_name, "files/gold_phpzip/VariableTestMultilineGold.odt"));
        unlink($output_name);
    }
}
