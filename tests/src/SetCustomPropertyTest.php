<?php

namespace Drupal\Tests\Odtphp;

use Odtphp\Odf;
use Odtphp\Exceptions\OdfException;

require_once 'OdtTestCase.php';

/**
 * Test the setCustomProperty functionality.
 */
class SetCustomPropertyTest extends OdtTestCase
{
    protected $filename;
    protected $odf;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Use the example file from the tests directory
        $sourcePath = __DIR__ . '/files/input/CustomPropertiesTest.odt';
        $this->filename = tempnam(sys_get_temp_dir(), 'odt_test_');
        copy($sourcePath, $this->filename);
        $this->odf = new Odf($this->filename);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    /**
     * Test setting a basic custom property.
     */
    public function testSetBasicCustomProperty(): void
    {
        $this->odf->setCustomProperty('Name', 'Anja');
        $metaXml = $this->odf->getMetaXml();
        
        $this->assertStringContainsString('<meta:user-defined meta:name="Name">Anja</meta:user-defined>', $metaXml);
        $this->assertStringNotContainsString('<meta:user-defined meta:name="Name">Sven Boden</meta:user-defined>', $metaXml);
    }

    /**
     * Test setting a date custom property.
     */
    public function testSetDateCustomProperty(): void
    {
        $this->odf->setCustomProperty('Creation Date', '01/11/9999');
        $metaXml = $this->odf->getMetaXml();
        
        $this->assertStringContainsString('<meta:user-defined meta:name="Creation Date" meta:value-type="date">01/11/9999</meta:user-defined>', $metaXml);
        $this->assertStringNotContainsString('<meta:user-defined meta:name="Creation Date" meta:value-type="date">2025-03-20</meta:user-defined>', $metaXml);
    }

    /**
     * Test setting custom property with HTML encoding.
     */
    public function testSetCustomPropertyWithEncoding(): void
    {
        $this->odf->setCustomProperty('Name', '<test>&"\'', TRUE);
        $metaXml = $this->odf->getMetaXml();
        
        $this->assertStringContainsString('&lt;test&gt;&amp;&quot;&apos;', $metaXml);
    }

    /**
     * Test setting custom property without HTML encoding.
     */
    public function testSetCustomPropertyWithoutEncoding(): void
    {
        $this->odf->setCustomProperty('Name', '<test>&"\'', FALSE);
        $metaXml = $this->odf->getMetaXml();
        
        $this->assertStringContainsString('<test>&"\'', $metaXml);
    }

    /**
     * Test setting custom property with invalid input.
     */
    public function testSetCustomPropertyWithInvalidInput(): void
    {
        $this->expectException(OdfException::class);
        $this->expectExceptionMessage('Key and value must be strings');
        $this->odf->setCustomProperty(['invalid'], 'value');
    }

    /**
     * Test setting non-existent custom property.
     */
    public function testSetNonExistentCustomProperty(): void
    {
        $this->expectException(OdfException::class);
        $this->expectExceptionMessage("Custom property 'NonExistent' not found in meta.xml");
        $this->odf->setCustomProperty('NonExistent', 'value');
    }

    /**
     * Test error handling when meta.xml extraction fails.
     */
    public function testMetaXmlExtractionError(): void
    {
        // Create a mock zip file without meta.xml
        $badFile = tempnam(sys_get_temp_dir(), 'bad_odt_');
        file_put_contents($badFile, 'corrupted content');
        
        $this->expectException(OdfException::class);
        $this->expectExceptionMessage("Error opening file '$badFile'");
        
        // Create a new ODT instance with the bad file - this should throw the exception
        new Odf($badFile);
        
        unlink($badFile);
    }

    /**
     * Compare output with gold files.
     */
    public function goldFileComparison($type): void
    {
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

        $odf = new Odf(__DIR__ . '/files/input/CustomPropertiesTest.odt', $config);
        $odf->setCustomProperty('Name', "Snow White");
        $odf->setCustomProperty('Creation Date', '2100-01-01');

        $output_name = __DIR__ . "/files/output/SetCustomPropertyTest" . $type_name . "Output.odt";
        $odf->saveToDisk($output_name);

        $this->assertTrue($this->odtFilesAreIdentical($output_name, __DIR__ . "/files/$gold_dir/SetCustomPropertyTestGold.odt"));
        unlink($output_name);
    }

    /**
     * Test with PclZip.
     */
    public function testSetCustomPropertyPclZip(): void
    {
        $this->goldFileComparison(self::PCLZIP_TYPE);
    }

    /**
     * Test with PhpZip.
     */
    public function testSetCustomPropertyPhpZip(): void
    {
        $this->goldFileComparison(self::PHPZIP_TYPE);
    }
} 
