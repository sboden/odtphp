<?php

namespace Drupal\Tests\Odtphp;

use PHPUnit\Framework\TestCase;
use Odtphp\Odf;
use ReflectionClass;
use ReflectionMethod;

require_once 'OdtTestCase.php';

class NullValueRegressionTest extends OdtTestCase {

    /**
     * Test that demonstrates the behavior that was fixed.
     * This test shows that the current implementation correctly handles null values
     * without throwing errors, which was the issue before the fix.
     */
    public function testNullValueHandlingAfterFix(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        // Test the current behavior (after the fix)
        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // These calls should not throw any errors after the fix
        $result = $method->invoke($odf, null);
        $this->assertEquals('', $result);

        $result = $method->invoke($odf, [null, 'test', null]);
        $this->assertEquals(['', 'test', ''], $result);
    }

    /**
     * Test that demonstrates the integration behavior after the fix.
     * This shows that setVars and setVariable methods work correctly with null values.
     */
    public function testIntegrationBehaviorAfterFix(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        // Test that setVars works with null values and encoding enabled
        $odf->setVars('titre', null, true, 'UTF-8');

        // Test that setVariable works with null values
        $odf->setVariable('message', null, true);

        // Test that both variables were set (as empty strings after encoding)
        $this->assertTrue($odf->variableExists('titre'));
        $this->assertTrue($odf->variableExists('message'));
    }

    /**
     * Test that demonstrates the behavior with various edge cases that were problematic before the fix.
     */
    public function testEdgeCaseHandlingAfterFix(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Test various edge cases that would have caused issues before the fix
        $edgeCases = [
            null,
            [null],
            [null, null, null],
            ['test', null, 'value'],
            [null, 'test', null],
            [[null], 'test'],
            ['test', [null]],
            [[null, 'test'], null],
        ];

        foreach ($edgeCases as $case) {
            $result = $method->invoke($odf, $case);
            // Just verify it doesn't throw an error and returns something
            $this->assertNotNull($result, "Failed for case: " . var_export($case, true));
        }
    }

    /**
     * Test that demonstrates the behavior with complex nested structures.
     */
    public function testComplexNestedStructuresAfterFix(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Test with complex nested structure containing nulls
        $complexStructure = [
            'users' => [
                [
                    'name' => 'John',
                    'email' => null,
                    'preferences' => [
                        'theme' => 'dark',
                        'notifications' => null
                    ]
                ],
                [
                    'name' => null,
                    'email' => 'jane@example.com',
                    'preferences' => null
                ],
                null, // This would have been problematic before the fix
                [
                    'name' => 'Bob',
                    'email' => 'bob@example.com',
                    'preferences' => [
                        'theme' => null,
                        'notifications' => true
                    ]
                ]
            ],
            'settings' => null,
            'metadata' => [
                'version' => '1.0',
                'author' => null,
                'tags' => [null, 'important', null]
            ]
        ];

        $result = $method->invoke($odf, $complexStructure);
        
        // Verify the structure is processed correctly
        $this->assertIsArray($result);
        $this->assertArrayHasKey('users', $result);
        $this->assertArrayHasKey('settings', $result);
        $this->assertArrayHasKey('metadata', $result);
        
        // Verify null values are converted to empty strings
        $this->assertEquals('', $result['settings']);
        $this->assertEquals('', $result['metadata']['author']);
    }

    /**
     * Test that demonstrates the behavior with different data types.
     */
    public function testDataTypeHandlingAfterFix(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Test with different data types including null
        $mixedData = [
            'string' => 'test',
            'null' => null,
            'integer' => 123,
            'float' => 123.45,
            'boolean_true' => true,
            'boolean_false' => false,
            'empty_string' => '',
            'zero' => 0,
            'array_with_nulls' => [null, 'test', null],
            'nested_null' => [['deep' => null]]
        ];

        $result = $method->invoke($odf, $mixedData);
        
        // Verify all values are processed without errors
        $this->assertEquals('test', $result['string']);
        $this->assertEquals('', $result['null']);
        $this->assertEquals('123', $result['integer']);
        $this->assertEquals('123.45', $result['float']);
        $this->assertEquals('1', $result['boolean_true']);
        $this->assertEquals('', $result['boolean_false']);
        $this->assertEquals('', $result['empty_string']);
        $this->assertEquals('0', $result['zero']);
        $this->assertEquals(['', 'test', ''], $result['array_with_nulls']);
        $this->assertEquals([['deep' => '']], $result['nested_null']);
    }

    /**
     * Test that demonstrates the behavior with HTML special characters and null values mixed.
     */
    public function testHtmlSpecialCharsWithNullsAfterFix(): void {
        $config = $this->odfPhpZipConfig();
        $input_file = __DIR__ . '/files/input/BasicTest1.odt';
        $odf = new Odf($input_file, $config);

        $reflection = new ReflectionClass($odf);
        $method = $reflection->getMethod('recursiveHtmlspecialchars');
        $method->setAccessible(true);

        // Test with HTML special characters mixed with null values
        $htmlWithNulls = [
            'safe_text' => 'Normal text',
            'null_value' => null,
            'html_script' => '<script>alert("test")</script>',
            'html_entities' => '&lt;test&gt;',
            'mixed_array' => [
                'safe' => 'Safe content',
                'null' => null,
                'html' => '<div>Content</div>',
                'empty' => ''
            ],
            'nested_html' => [
                'level1' => [
                    'level2' => [
                        'content' => '<p>Paragraph</p>',
                        'null_content' => null
                    ]
                ]
            ]
        ];

        $result = $method->invoke($odf, $htmlWithNulls);
        
        // Verify HTML is properly encoded
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;', $result['html_script']);
        $this->assertEquals('&amp;lt;test&amp;gt;', $result['html_entities']);
        $this->assertEquals('&lt;div&gt;Content&lt;/div&gt;', $result['mixed_array']['html']);
        $this->assertEquals('&lt;p&gt;Paragraph&lt;/p&gt;', $result['nested_html']['level1']['level2']['content']);
        
        // Verify null values are converted to empty strings
        $this->assertEquals('', $result['null_value']);
        $this->assertEquals('', $result['mixed_array']['null']);
        $this->assertEquals('', $result['nested_html']['level1']['level2']['null_content']);
    }
} 