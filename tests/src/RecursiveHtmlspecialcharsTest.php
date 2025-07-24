<?php

namespace Drupal\Tests\Odtphp;

use PHPUnit\Framework\TestCase;
use Odtphp\Odf;
use ReflectionClass;
use ReflectionMethod;

require_once 'OdtTestCase.php';

class RecursiveHtmlspecialcharsTest extends OdtTestCase {

    /**
     * Test that recursiveHtmlspecialchars handles null values correctly.
     * This tests the fix from the GitHub commit where null values were causing errors.
     */
    public function testRecursiveHtmlspecialcharsWithNullValue(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        // Use reflection to access the protected method
        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Test with null value - should not throw an error and should return empty string
        $result = $method->invoke($odf, null);
        $this->assertEquals('', $result);
    }

    /**
     * Test that recursiveHtmlspecialchars handles various null-like values correctly.
     */
    public function testRecursiveHtmlspecialcharsWithNullLikeValues(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Test with various null-like values (avoid duplicate keys)
        $testCases = [
            null => '',
            '' => '',
            '0' => '0', // string key only
            true => '1',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $method->invoke($odf, $input);
            $this->assertEquals($expected, $result, "Failed for input: " . var_export($input, true));
        }
        // Add explicit tests for integer 0 and boolean false
        $result = $method->invoke($odf, 0);
        $this->assertEquals('0', $result, "Failed for input: 0 (int)");
        $result = $method->invoke($odf, false);
        $this->assertEquals('', $result, "Failed for input: false (bool)");
    }

    /**
     * Test that recursiveHtmlspecialchars handles arrays with null values correctly.
     */
    public function testRecursiveHtmlspecialcharsWithArrayContainingNull(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Test with array containing null values
        $input = ['test', null, 'value', null];
        $expected = ['test', '', 'value', ''];
        
        $result = $method->invoke($odf, $input);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that recursiveHtmlspecialchars handles nested arrays with null values correctly.
     */
    public function testRecursiveHtmlspecialcharsWithNestedArrays(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Test with nested arrays containing null values
        $input = [
            'level1' => [
                'level2' => [
                    'value1' => 'test',
                    'value2' => null,
                    'value3' => 'another'
                ],
                'level2b' => null
            ],
            'top_level' => null
        ];

        $expected = [
            'level1' => [
                'level2' => [
                    'value1' => 'test',
                    'value2' => '',
                    'value3' => 'another'
                ],
                'level2b' => ''
            ],
            'top_level' => ''
        ];

        $result = $method->invoke($odf, $input);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that recursiveHtmlspecialchars properly encodes HTML special characters.
     */
    public function testRecursiveHtmlspecialcharsWithSpecialCharacters(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Test with HTML special characters
        $input = '<script>alert("test")</script>';
        $expected = '&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;';
        
        $result = $method->invoke($odf, $input);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that setVars method handles null values correctly when encoding is enabled.
     */
    public function testSetVarsWithNullValueAndEncoding(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        // This should not throw an error even with null value and encoding enabled
        $odf->setVars('titre', null, true, 'UTF-8');
        
        // Verify the variable was set (should be empty string after encoding)
        $this->assertTrue($odf->variableExists('titre'));
    }

    /**
     * Test that setVars method handles null values correctly when encoding is disabled.
     * Note: This test demonstrates that the setVars method needs additional null handling.
     */
    public function testSetVarsWithNullValueAndNoEncoding(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        // This test shows that setVars needs additional null handling when encoding is disabled
        // because str_replace() receives null as the third parameter
        $this->expectException(\TypeError::class);
        $odf->setVars('titre', null, false, 'UTF-8');
    }

    /**
     * Test that setVariable method (alias for setVars) handles null values correctly.
     */
    public function testSetVariableWithNullValue(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        // This should not throw an error
        $odf->setVariable('titre', null, true);
        
        // Verify the variable was set
        $this->assertTrue($odf->variableExists('titre'));
    }

    /**
     * Test integration with actual ODT file processing using null values.
     */
    public function testIntegrationWithNullValues(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        // Set various types of values including null
        // Only use variables that exist in the BasicTest1.odt template
        $odf->setVars('titre', null, true, 'UTF-8');
        $odf->setVars('message', 'Normal message', true, 'UTF-8');

        $output_name = __DIR__ . '/files/output/NullValueTestOutput.odt';
        
        // This should not throw any errors
        $odf->saveToDisk($output_name);
        
        // Verify the file was created successfully
        $this->assertFileExists($output_name);
        
        // Clean up
        unlink($output_name);
    }

    /**
     * Test that the fix works with both PHPZip and PclZip configurations.
     */
    public function testNullValueHandlingWithPclZip(): void {
        $config = $this->odfPclZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Test with null value using PclZip configuration
        $result = $method->invoke($odf, null);
        $this->assertEquals('', $result);
    }

    /**
     * Test edge case with mixed data types in arrays.
     */
    public function testMixedDataTypesInArrays(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Test with mixed data types including null
        $input = [
            'string' => 'test',
            'null' => null,
            'integer' => 123,
            'boolean' => true,
            'empty_string' => '',
            'zero' => 0,
            'false' => false
        ];

        $expected = [
            'string' => 'test',
            'null' => '',
            'integer' => '123',
            'boolean' => '1',
            'empty_string' => '',
            'zero' => '0',
            'false' => ''
        ];

        $result = $method->invoke($odf, $input);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that the method handles very deep nested arrays correctly.
     */
    public function testDeepNestedArrays(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Create a deeply nested array
        $input = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'level5' => [
                                'value' => null,
                                'text' => '<script>alert("test")</script>'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $expected = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'level5' => [
                                'value' => '',
                                'text' => '&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $method->invoke($odf, $input);
        $this->assertEquals($expected, $result);
    }
} 