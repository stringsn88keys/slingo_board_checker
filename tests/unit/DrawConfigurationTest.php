<?php

require_once __DIR__ . '/../../classes/DrawConfiguration.php';
require_once __DIR__ . '/../../classes/SlingoBoard.php';

class DrawConfigurationTest extends PHPUnit\Framework\TestCase {
    
    private $board;
    
    protected function setUp(): void {
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
        
        $this->assertCount(1, $rows);
        $this->assertEquals($positions, $rows[0]);
    }
    
    public function testAddMultipleRows() {
        $config = new DrawConfiguration();
        
        $config->addRow(['wild', 'none', 'none', 'none', 'none']);
        $config->addRow(['none', 'wild', 'none', 'none', 'none']);
        $config->addRow(['none', 'none', 'super_wild', 'none', 'none']);
        
        $rows = $config->getRows();
        $this->assertCount(3, $rows);
    }
    
    public function testValidationValidConfiguration() {
        $config = new DrawConfiguration();
        $config->addRow(['wild', 'none', 'super_wild', 'none', 'wild']);
        
        $validation = $config->validateConfiguration();
        $this->assertTrue($validation['valid']);
    }
    
    public function testValidationEmptyRows() {
        $config = new DrawConfiguration();
        
        $validation = $config->validateConfiguration();
        $this->assertFalse($validation['valid']);
        $this->assertStringContains('At least one draw row is required', $validation['message']);
    }
    
    public function testValidationTooManyRows() {
        $config = new DrawConfiguration();
        
        // Add 4 rows (exceeds maximum of 3)
        $config->addRow(['wild', 'none', 'none', 'none', 'none']);
        $config->addRow(['none', 'wild', 'none', 'none', 'none']);
        $config->addRow(['none', 'none', 'super_wild', 'none', 'none']);
        $config->addRow(['none', 'none', 'none', 'wild', 'none']);
        
        $validation = $config->validateConfiguration();
        $this->assertFalse($validation['valid']);
        $this->assertStringContains('Maximum 3 draw rows allowed', $validation['message']);
    }
    
    public function testValidationInvalidPositionCount() {
        $config = new DrawConfiguration();
        $config->addRow(['wild', 'none', 'super_wild']); // Only 3 positions
        
        $validation = $config->validateConfiguration();
        $this->assertFalse($validation['valid']);
        $this->assertStringContains('must have exactly 5 positions', $validation['message']);
    }
    
    public function testValidationInvalidPositionValue() {
        $config = new DrawConfiguration();
        $config->addRow(['wild', 'invalid', 'super_wild', 'none', 'wild']);
        
        $validation = $config->validateConfiguration();
        $this->assertFalse($validation['valid']);
        $this->assertStringContains('Invalid position value', $validation['message']);
    }
    
    public function testOptimalWildPlacements() {
        $config = new DrawConfiguration();
        $config->addRow(['wild', 'none', 'super_wild', 'none', 'wild']);
        
        // Set up board with some covered positions
        $this->board->setCoveredPositions([[0, 0], [0, 1], [0, 3], [0, 4]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        $this->assertIsArray($placements);
        $this->assertGreaterThan(0, count($placements));
        
        // Check structure of first placement
        $placement = $placements[0];
        $this->assertArrayHasKey('row', $placement);
        $this->assertArrayHasKey('positions', $placement);
        $this->assertArrayHasKey('expected_score', $placement);
        $this->assertArrayHasKey('reasoning', $placement);
        
        $this->assertCount(5, $placement['positions']);
        $this->assertIsNumeric($placement['expected_score']);
        $this->assertIsString($placement['reasoning']);
    }
    
    public function testWildCombinationGeneration() {
        $config = new DrawConfiguration();
        $config->addRow(['wild', 'none', 'super_wild', 'none', 'wild']);
        
        // Set up board
        $this->board->setCoveredPositions([[0, 0], [0, 1], [0, 3], [0, 4]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        // Should have multiple combinations
        $this->assertGreaterThan(1, count($placements));
        
        // Check that all combinations are valid
        foreach ($placements as $placement) {
            $positions = $placement['positions'];
            $this->assertCount(5, $positions);
            
            foreach ($positions as $position) {
                $this->assertContains($position, ['none', 'wild', 'super_wild']);
            }
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
            $this->assertGreaterThan(0, $placement['expected_score']);
        }
    }
    
    public function testReasoningGeneration() {
        $config = new DrawConfiguration();
        $config->addRow(['wild', 'none', 'super_wild', 'none', 'wild']);
        
        $this->board->setCoveredPositions([[0, 0], [0, 1], [0, 3], [0, 4]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        foreach ($placements as $placement) {
            $reasoning = $placement['reasoning'];
            $this->assertIsString($reasoning);
            $this->assertNotEmpty($reasoning);
            
            // Should contain relevant keywords
            $this->assertTrue(
                strpos($reasoning, 'Slingo') !== false ||
                strpos($reasoning, 'wild') !== false ||
                strpos($reasoning, 'setup') !== false
            );
        }
    }
    
    public function testNoOptimalPlacements() {
        $config = new DrawConfiguration();
        $config->addRow(['none', 'none', 'none', 'none', 'none']);
        
        // Set up board with no potential Slingos
        $this->board->setCoveredPositions([]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        // Should return empty array or null
        $this->assertTrue(empty($placements) || is_null($placements));
    }
    
    public function testEdgeCases() {
        $config = new DrawConfiguration();
        
        // Test with all wilds
        $config->addRow(['wild', 'wild', 'wild', 'wild', 'wild']);
        $this->board->setCoveredPositions([]);
        $placements = $config->getOptimalWildPlacements($this->board);
        $this->assertIsArray($placements);
        
        // Test with all super wilds
        $config = new DrawConfiguration();
        $config->addRow(['super_wild', 'super_wild', 'super_wild', 'super_wild', 'super_wild']);
        $this->board->setCoveredPositions([]);
        $placements = $config->getOptimalWildPlacements($this->board);
        $this->assertIsArray($placements);
    }
}
