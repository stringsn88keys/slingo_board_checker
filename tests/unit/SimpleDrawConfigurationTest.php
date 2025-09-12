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
    
    /**
     * Test Tile Placement Heuristic: Used when 5 super wilds to avoid memory issues
     */
    public function testTilePlacementHeuristic() {
        $config = new DrawConfiguration();
        $config->addRow(['super_wild', 'super_wild', 'super_wild', 'super_wild', 'super_wild']);
        
        $this->board->setCoveredPositions([[2, 2]]); // Cover center to test priority
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        $this->assertNotEmpty($placements, 'Should handle 5 super wilds with heuristic');
        
        if (!empty($placements)) {
            $placement = $placements[0];
            $this->assertStringContains('using tile heuristic', $placement['reasoning'], 
                'Should indicate tile heuristic was used');
            $this->assertCount(5, $placement['super_wild_placements'], 
                'Should place all 5 super wilds');
            
            // Should prioritize corners since center is covered
            $positions = [];
            foreach ($placement['super_wild_placements'] as $superWild) {
                $positions[] = [$superWild['row'] - 1, $superWild['column'] - 1]; // Convert to 0-indexed
            }
            
            // Check that at least some corners are used
            $corners = [[0,0], [0,4], [4,0], [4,4]];
            $cornerCount = 0;
            foreach ($positions as $pos) {
                if (in_array($pos, $corners)) {
                    $cornerCount++;
                }
            }
            
            $this->assertGreaterThan(0, $cornerCount, 'Should prioritize corner positions');
        }
    }
    
    /**
     * Test Multiple Draw Rows with Wilds
     */
    public function testMultipleDrawRowsWithWilds() {
        $config = new DrawConfiguration();
        $config->addRow(['super_wild', 'wild', 'none', 'none', 'none']);        // Row 1
        $config->addRow(['none', 'super_wild', 'wild', 'none', 'none']);        // Row 2
        $config->addRow(['super_wild', 'none', 'super_wild', 'wild', 'none']);  // Row 3
        
        $this->board->setCoveredPositions([[0, 0], [1, 1], [2, 2]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        $this->assertCount(3, $placements, 'Should have one recommendation per draw row');
        
        // Each recommendation should have proper structure
        foreach ($placements as $i => $placement) {
            $this->assertEquals($i + 1, $placement['row'], "Placement should be for row " . ($i + 1));
            $this->assertIsNumeric($placement['expected_score'], 'Should have numeric expected score');
            $this->assertIsString($placement['reasoning'], 'Should have string reasoning');
        }
    }
    
    /**
     * Test Memory Efficiency: 5 super wilds should not cause memory exhaustion
     */
    public function testMemoryEfficiencyWithManyWilds() {
        $config = new DrawConfiguration();
        $config->addRow(['super_wild', 'super_wild', 'super_wild', 'super_wild', 'super_wild']);
        
        $this->board->setCoveredPositions([[0, 0]]);
        
        // This should complete quickly without memory issues
        $startTime = microtime(true);
        $placements = $config->getOptimalWildPlacements($this->board);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        $this->assertNotEmpty($placements, 'Should generate placements');
        $this->assertLessThan(2.0, $executionTime, 'Should complete quickly (under 2 seconds)');
        
        if (!empty($placements)) {
            $this->assertStringContains('using tile heuristic', $placements[0]['reasoning'], 
                'Should use tile heuristic for efficiency');
        }
    }
    
    public function testStrategicWildPlacement() {
        // Create a board where row 0 has 4 covered positions (1 away from completion)
        $testNumbers = [
            [1, 16, 31, 46, 61],
            [2, 17, 32, 47, 62],
            [3, 18, 33, 48, 63],
            [4, 19, 34, 49, 64],
            [5, 20, 35, 50, 65]
        ];
        $strategicBoard = new SlingoBoard($testNumbers);
        $strategicBoard->setCoveredPositions([[0,0], [0,1], [0,2], [0,3]]); // Row 0 needs only position [0,4]
        
        $config = new DrawConfiguration();
        $config->addRow(['none', 'none', 'none', 'none', 'super_wild']); // Super wild in column 4
        
        $placements = $config->getOptimalWildPlacements($strategicBoard);
        
        $this->assertCount(1, $placements, 'Should have one placement recommendation');
        $this->assertGreaterThan(50000, $placements[0]['expected_score'], 'Should prioritize proximity to completion (>50000)');
        $this->assertStringContains('complet', strtolower($placements[0]['reasoning']), 'Reasoning should mention completion priority');
        
        // The super wild should be placed at [0,4] to complete the row
        $superWildPlacements = $placements[0]['super_wild_placements'];
        $this->assertCount(1, $superWildPlacements, 'Should place the super wild');
        $this->assertEquals(1, $superWildPlacements[0]['row'], 'Should be in row 1 (0-indexed becomes 1-indexed)');
        $this->assertEquals(5, $superWildPlacements[0]['column'], 'Should be in column 5 (0-indexed becomes 1-indexed)');
    }
    
    public function testDiagonalPriorityStrategy() {
        // Create a board where the main diagonal has 4 covered positions
        $testNumbers = [
            [1, 16, 31, 46, 61],
            [2, 17, 32, 47, 62],
            [3, 18, 33, 48, 63],
            [4, 19, 34, 49, 64],
            [5, 20, 35, 50, 65]
        ];
        $diagonalBoard = new SlingoBoard($testNumbers);
        $diagonalBoard->setCoveredPositions([[0,0], [1,1], [2,2], [3,3]]); // Main diagonal missing [4,4]
        
        $config = new DrawConfiguration();
        $config->addRow(['none', 'none', 'none', 'none', 'super_wild']); // Super wild in column 4
        
        $placements = $config->getOptimalWildPlacements($diagonalBoard);
        
        $this->assertCount(1, $placements, 'Should have one placement recommendation');
        // Diagonal completion should get extra bonus (60000 base + completion bonus)
        $this->assertGreaterThan(60000, $placements[0]['expected_score'], 'Should prioritize diagonal completion with extra bonus');
        
        $superWildPlacements = $placements[0]['super_wild_placements'];
        $this->assertEquals(5, $superWildPlacements[0]['row'], 'Should complete diagonal at row 5');
        $this->assertEquals(5, $superWildPlacements[0]['column'], 'Should complete diagonal at column 5');
    }
    
    public function testAvoidLowValuePlacements() {
        // Create a board where edge positions have poor strategic value
        $testNumbers = [
            [1, 16, 31, 46, 61],
            [2, 17, 32, 47, 62],
            [3, 18, 33, 48, 63],
            [4, 19, 34, 49, 64],
            [5, 20, 35, 50, 65]
        ];
        $poorValueBoard = new SlingoBoard($testNumbers);
        $poorValueBoard->setCoveredPositions([[0,1]]); // Only one position covered in row 0 (edge row)
        
        $config = new DrawConfiguration();
        $config->addRow(['super_wild', 'none', 'super_wild', 'none', 'none']); // Two super wilds: one in edge, one in center column
        
        $placements = $config->getOptimalWildPlacements($poorValueBoard);
        
        $this->assertCount(1, $placements, 'Should have one placement recommendation');
        
        $superWildPlacements = $placements[0]['super_wild_placements'];
        $this->assertCount(2, $superWildPlacements, 'Should place both super wilds');
        
        // The center column (2) should be prioritized over edge column (0)
        $centerPlacement = null;
        $edgePlacement = null;
        
        foreach ($superWildPlacements as $placement) {
            if ($placement['column'] === 3) { // Column 2 (0-indexed) becomes 3 (1-indexed)
                $centerPlacement = $placement;
            } elseif ($placement['column'] === 1) { // Column 0 (0-indexed) becomes 1 (1-indexed)
                $edgePlacement = $placement;
            }
        }
        
        $this->assertTrue($centerPlacement !== null, 'Should place a super wild in center column');
        $this->assertTrue($edgePlacement !== null, 'Should place a super wild in edge column');
        
        // Center should be positioned better (likely in middle rows for compound value)
        $this->assertTrue(
            ($centerPlacement['row'] >= 2 && $centerPlacement['row'] <= 4), 
            'Center column placement should favor middle positions for compound value'
        );
    }
}
?>
