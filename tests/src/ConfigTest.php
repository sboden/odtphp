<?php

namespace Drupal\Tests\Odtphp;

use PHPUnit\Framework\TestCase;
use Odtphp\Odf;
use Odtphp\Exceptions\OdfException;

require_once 'OdtTestCase.php';

class ConfigTest extends OdtTestCase {
    /**
     * Test handling of invalid configuration type.
     */
    public function testInvalidConfigType(): void {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument #2 ($config) must be of type array');
        new Odf(__DIR__ . '/files/input/BasicTest1.odt', 'invalid');
    }

    public function testCustomDelimiters(): void {
        $config = array_merge($this->odfPhpZipConfig(), [
            'DELIMITER_LEFT' => '[[',
            'DELIMITER_RIGHT' => ']]'
        ]);
        
        $odf = new Odf(__DIR__ . "/files/input/ConfigTest.odt", $config);
        $odf->setVars('test_var', 'test value');
        
        $output_name = __DIR__ . "/files/output/ConfigTestOutput.odt";
        $odf->saveToDisk($output_name);
        
        $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/gold_phpzip/ConfigTestGold.odt"));
        unlink($output_name);
    }

    public function testInvalidTempPath(): void {
        $config = array_merge($this->odfPhpZipConfig(), [
            'PATH_TO_TMP' => '/nonexistent/path'
        ]);
        
        $this->expectException(OdfException::class);
        new Odf(__DIR__ . "/files/input/BasicTest1.odt", $config);
    }
}
