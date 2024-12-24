<?php

namespace Drupal\Tests\Odtphp;

use PHPUnit\Framework\TestCase;
use Odtphp\Odf;
use Exception;

require_once 'OdtTestCase.php';

class EdgeCaseTest extends OdtTestCase {

    public function edgeCase($type, $testName): void {
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

        switch ($testName) {
            case 'LargeVariableSubstitution':
                $input_file = __DIR__ . '/files/input/LargeVariableTest.odt';
                $output_name = __DIR__ . '/files/output/LargeVariableTest' . $type_name . 'Output.odt';
                $gold_file = __DIR__ . '/files/' . $gold_dir . '/LargeVariableTestGold.odt';

                $odf = new Odf($input_file, $config);
                
                // Generate a large text block
                $largeText = str_repeat("This is a large text block with many repetitions. ", 1000);
                
                $odf->setVars('large_content', $largeText, true, 'UTF-8');
                
                $odf->saveToDisk($output_name);
                
                $this->assertTrue($this->odtFilesAreIdentical($output_name, $gold_file));
                
                unlink($output_name);
                break;

            case 'ComplexSegmentMerging':
                $input_file = __DIR__ . '/files/input/NestedSegmentTest.odt';
                $output_name = __DIR__ . '/files/output/NestedSegmentTest' . $type_name . 'Output.odt';
                $gold_file = __DIR__ . '/files/' . $gold_dir . '/NestedSegmentTestGold.odt';
                
                $odf = new Odf($input_file, $config);
                
                // Create a nested data structure
                $departments = [
                    [
                        'name' => 'Engineering',
                        'employees' => [
                            ['name' => 'Alice', 'role' => 'Senior Developer'],
                            ['name' => 'Bob', 'role' => 'Junior Developer']
                        ]
                    ],
                    [
                        'name' => 'Marketing',
                        'employees' => [
                            ['name' => 'Charlie', 'role' => 'Marketing Manager'],
                            ['name' => 'David', 'role' => 'Content Strategist']
                        ]
                    ]
                ];
                
                $departmentSegment = $odf->setSegment('departments');
                
                foreach ($departments as $department) {
                    $departmentSegment->setVars('department_name', $department['name']);
                    
                    $employeeSegment = $departmentSegment->setSegment('employees');
                    
                    foreach ($department['employees'] as $employee) {
                        $employeeSegment->setVars('employee_name', $employee['name']);
                        $employeeSegment->setVars('employee_role', $employee['role']);
                        $employeeSegment->merge();
                    }
                    $departmentSegment->merge();
                }
                $odf->mergeSegment($departmentSegment);
                $odf->saveToDisk($output_name);
                
                $this->assertTrue($this->odtFilesAreIdentical($output_name, $gold_file));
                
                unlink($output_name);
                break;

            case 'SpecialCharacterEncoding':
                $input_file = __DIR__ . '/files/input/EncodingTest.odt';
                $output_name = __DIR__ . '/files/output/EncodingTest' . $type_name . 'Output.odt';
                $gold_file = __DIR__ . '/files/' . $gold_dir . '/EncodingTestGold.odt';
                
                $specialCharText = "Special characters: éèà€ñ¿ § ® © µ";
                
                $odf = new Odf($input_file, $config);
                
                $odf->setVars('special_chars', $specialCharText, true, 'UTF-8');
                
                $odf->saveToDisk($output_name);
                
                $this->assertTrue($this->odtFilesAreIdentical($output_name, $gold_file));
                
                unlink($output_name);
                break;

            case 'AdvancedImageInsertion':
                $input_file = __DIR__ . '/files/input/ImageTest.odt';
                $output_name = __DIR__ . '/files/output/AdvancedImageTest' . $type_name . 'Output.odt';
                $gold_file = __DIR__ . '/files/' . $gold_dir . '/AdvancedImageTestGold.odt';
                
                $odf = new Odf($input_file, $config);
                
                // Test different image types and sizes
                $images = [
                    'small_png' => __DIR__ . '/files/images/voronoi_sphere.png',
                    'large_jpg' => __DIR__ . '/files/images/test.jpg',
                    'transparent_gif' => __DIR__ . '/files/images/circle_transparent.gif'
                ];
                
                foreach ($images as $key => $imagePath) {
                    $odf->setImage($key, $imagePath);
                }
                
                $odf->saveToDisk($output_name);
                
                $this->assertTrue($this->odtFilesAreIdentical($output_name, $gold_file));
                
                unlink($output_name);
                break;
        }
    }

    public function testLargeVariableSubstitutionPclZip(): void {
        $this->edgeCase(self::PCLZIP_TYPE, 'LargeVariableSubstitution');
    }

    public function testLargeVariableSubstitutionPhpZip(): void {
        $this->edgeCase(self::PHPZIP_TYPE, 'LargeVariableSubstitution');
    }

    public function testComplexSegmentMergingPclZip(): void {
        $this->edgeCase(self::PCLZIP_TYPE, 'ComplexSegmentMerging');
    }

    public function testComplexSegmentMergingPhpZip(): void {
        $this->edgeCase(self::PHPZIP_TYPE, 'ComplexSegmentMerging');
    }

    public function testSpecialCharacterEncodingPclZip(): void {
        $this->edgeCase(self::PCLZIP_TYPE, 'SpecialCharacterEncoding');
    }

    public function testSpecialCharacterEncodingPhpZip(): void {
        $this->edgeCase(self::PHPZIP_TYPE, 'SpecialCharacterEncoding');
    }

    public function testAdvancedImageInsertionPclZip(): void {
        $this->edgeCase(self::PCLZIP_TYPE, 'AdvancedImageInsertion');
    }

    public function testAdvancedImageInsertionPhpZip(): void {
        $this->edgeCase(self::PHPZIP_TYPE, 'AdvancedImageInsertion');
    }

    /**
     * Test handling of invalid template files.
     */
    public function testInvalidTemplateHandling(): void {
        $this->expectException(\Odtphp\Exceptions\OdfException::class);
        $this->expectExceptionMessage("File '" . __DIR__ . "/files/input/NonexistentTemplate.odt' does not exist");
        new Odf(__DIR__ . '/files/input/NonexistentTemplate.odt');
    }
}
