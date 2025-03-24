<?php

declare(strict_types=1);

namespace Drupal\Tests\Odtphp;

use Odtphp\Odf;
use Odtphp\Exceptions\OdfException;
use PHPUnit\Framework\TestCase;

/**
 * Test the customPropertyExists function.
 */
class CustomPropertyExistsTest extends TestCase {

  /**
   * Path to test files.
   */
  protected string $path;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->path = __DIR__ . '/files/';
  }

  /**
   * Test checking for existing custom properties.
   */
  public function testExistingCustomProperties(): void {
    $odf = new Odf($this->path . 'input/CustomPropertyExistsTest.odt');

    // Test properties that should exist in the template.
    $this->assertTrue($odf->customPropertyExists('Author'));
    $this->assertTrue($odf->customPropertyExists('Version'));
    $this->assertTrue($odf->customPropertyExists('Department'));
  }

  /**
   * Test checking for non-existing custom properties.
   */
  public function testNonExistingCustomProperties(): void {
    $odf = new Odf($this->path . 'input/CustomPropertyExistsTest.odt');

    // Test properties that should not exist in the template.
    $this->assertFalse($odf->customPropertyExists('NonExistentProperty'));
    $this->assertFalse($odf->customPropertyExists(''));
    $this->assertFalse($odf->customPropertyExists('Random Property'));
  }

  /**
   * Test checking properties with special characters.
   */
  public function testSpecialCharacterProperties(): void {
    $odf = new Odf($this->path . 'input/CustomPropertyExistsTest.odt');

    // Test properties with special characters that should exist.
    $this->assertTrue($odf->customPropertyExists('Special & Property'));
    $this->assertTrue($odf->customPropertyExists('Property <with> XML chars'));
  }

  /**
   * Test checking properties after setting them.
   */
  public function testPropertyExistsAfterSet(): void {
    $odf = new Odf($this->path . 'input/CustomPropertyExistsTest.odt');

    // Verify property exists before modifying.
    $this->assertTrue($odf->customPropertyExists('Author'));

    // Modify the property.
    $odf->setCustomProperty('Author', 'John Doe');

    // Verify property still exists after modifying.
    $this->assertTrue($odf->customPropertyExists('Author'));
  }

  /**
   * Test checking properties with case sensitivity.
   */
  public function testCaseSensitiveProperties(): void {
    $odf = new Odf($this->path . 'input/CustomPropertyExistsTest.odt');

    // Test case sensitivity.
    $this->assertTrue($odf->customPropertyExists('Author'));
    $this->assertFalse($odf->customPropertyExists('author'));
    $this->assertFalse($odf->customPropertyExists('AUTHOR'));
  }
} 