<?php

require_once __DIR__ . '/../SimpleTest.php';
require_once __DIR__ . '/../../classes/DrawConfiguration.php';
require_once __DIR__ . '/../../classes/SlingoBoard.php';

class SimpleDrawConfigurationTest extends SimpleTest {
    
    private $board;
    
    public function setUp() {
        // Create a test board
        $testNumbers = [
            [1, 16, 31, 46, 61],
            [2, 17, 32, 47, 62],
            [3, 18, 33, 48, 63],
            [4, 19, 34, 49, 64],
            [5, 20, 35, 50, 65]
        ];
        $this->board = new SlingoBoard($testNumbers);
    }
    
    public function testAddRow() {
        $config = new DrawConfiguration();
        $positions = ['wild', 'none', 'super_wild', 'none', 'wild'];
        
        $config->addRow($positions);
        $rows = $config->getRows();
        
        $this->assertCount(1, $rows, 'Should have 1 row after adding');
        $this->assertEquals($positions, $rows[0], 'Row should match added positions');
    }
    
    public function testValidationValidConfiguration() {
        $config = new DrawConfiguration();
        $config->addRow(['wild', 'none', 'super_wild', 'none', 'wild']);
        
        $validation = $config->validateConfiguration();
        $this->assertTrue($validation['valid'], 'Valid configuration should pass validation');
    }
    
    public function testValidationEmptyRows() {
        $config = new DrawConfiguration();
        
        $validation = $config->validateConfiguration();
        $this->assertFalse($validation['valid'], 'Empty configuration should fail validation');
        $this->assertStringContains('At least one draw row is required', $validation['message'], 'Should have correct error message');
    }
    
    public function testValidationTooManyRows() {
        $config = new DrawConfiguration();
        
        // Add 4 rows (exceeds maximum of 3)
        $config->addRow(['wild', 'none', 'none', 'none', 'none']);
        $config->addRow(['none', 'wild', 'none', 'none', 'none']);
        $config->addRow(['none', 'none', 'super_wild', 'none', 'none']);
        $config->addRow(['none', 'none', 'none', 'wild', 'none']);
        
        $validation = $config->validateConfiguration();
        $this->assertFalse($validation['valid'], 'Too many rows should fail validation');
        $this->assertStringContains('Maximum 3 draw rows allowed', $validation['message'], 'Should have correct error message');
    }
    
    public function testOptimalWildPlacements() {
        $config = new DrawConfiguration();
        $config->addRow(['wild', 'none', 'super_wild', 'none', 'wild']);
        
        // Set up board with some covered positions
        $this->board->setCoveredPositions([[0, 0], [0, 1], [0, 3], [0, 4]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        $this->assertIsArray($placements, 'Placements should be an array');
        $this->assertGreaterThan(0, count($placements), 'Should have at least one placement');
        
        // Check structure of first placement
        if (!empty($placements)) {
            $placement = $placements[0];
            $this->assertArrayHasKey('row', $placement, 'Placement should have row');
            $this->assertArrayHasKey('positions', $placement, 'Placement should have positions');
            $this->assertArrayHasKey('expected_score', $placement, 'Placement should have expected_score');
            $this->assertArrayHasKey('reasoning', $placement, 'Placement should have reasoning');
            
            $this->assertCount(5, $placement['positions'], 'Positions should have 5 elements');
            $this->assertIsNumeric($placement['expected_score'], 'Expected score should be numeric');
            $this->assertIsString($placement['reasoning'], 'Reasoning should be string');
        }
    }
    
    public function testExpectedScoreCalculation() {
        $config = new DrawConfiguration();
        $config->addRow(['wild', 'none', 'super_wild', 'none', 'wild']);
        
        // Set up board for Slingo completion
        $this->board->setCoveredPositions([[0, 0], [0, 1], [0, 3], [0, 4]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        // Should have positive expected scores
        foreach ($placements as $placement) {
            $this->assertGreaterThan(0, $placement['expected_score'], 'Expected score should be positive');
        }
    }
}
?>
