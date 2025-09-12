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
    
    /**
     * Test Priority System: Completed Slingos get highest priority
     */
    public function testPrioritySystemCompletedSlingos() {
        $config = new DrawConfiguration();
        $config->addRow(['super_wild', 'none', 'none', 'none', 'none']);
        
        // Board where super wild can complete either row 1 or main diagonal
        $this->board->setCoveredPositions([
            [0, 1], [0, 2], [0, 3], [0, 4], // Row 1 needs [0,0] to complete
            [1, 1], [2, 2], [3, 3]          // Main diagonal needs [4,4] to complete
        ]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        $this->assertNotEmpty($placements, 'Should find optimal placements');
        $this->assertGreaterThan(10000, $placements[0]['expected_score'], 
            'Completed Slingo should have very high score');
    }
    
    /**
     * Test Priority System: Diagonal priority over horizontal/vertical
     */
    public function testPrioritySystemDiagonalPriority() {
        $config = new DrawConfiguration();
        $config->addRow(['super_wild', 'none', 'none', 'none', 'none']);
        
        // Board where super wild can complete either row 1 or main diagonal
        $this->board->setCoveredPositions([
            [0, 1], [0, 2], [0, 3], [0, 4], // Row 1 needs [0,0] to complete
            [1, 1], [2, 2], [3, 3]          // Main diagonal needs [4,4] to complete
        ]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        // Should prioritize diagonal completion at [4,4] over row completion at [0,0]
        $superWildPlacement = $placements[0]['super_wild_placements'][0];
        $this->assertEquals(5, $superWildPlacement['row'], 'Should prioritize diagonal (row 5)');
        $this->assertEquals(5, $superWildPlacement['column'], 'Should prioritize diagonal (column 5)');
    }
    
    /**
     * Test Priority System: Setup moves when no Slingos completable
     */
    public function testPrioritySystemSetupMoves() {
        $config = new DrawConfiguration();
        $config->addRow(['super_wild', 'wild', 'none', 'none', 'none']);
        
        // Board with minimal coverage - no completable Slingos
        $this->board->setCoveredPositions([[0, 0], [2, 2]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        $this->assertNotEmpty($placements, 'Should find setup moves');
        $this->assertLessThan(10000, $placements[0]['expected_score'], 
            'Setup moves should have lower score than completed Slingos');
        $this->assertGreaterThan(1000, $placements[0]['expected_score'], 
            'Setup moves should prioritize high-value setups');
    }
    
    /**
     * Test Depth-First Search: All combinations generated
     */
    public function testDepthFirstSearchCombinations() {
        $config = new DrawConfiguration();
        $config->addRow(['super_wild', 'wild', 'none', 'none', 'none']);
        
        $this->board->setCoveredPositions([[0, 0], [1, 3], [2, 2], [3, 3]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        $this->assertNotEmpty($placements, 'Should generate placement combinations');
        
        $placement = $placements[0];
        
        // Should have both wild and super wild placements
        $this->assertArrayHasKey('wild_placements', $placement, 'Should have wild placements');
        $this->assertArrayHasKey('super_wild_placements', $placement, 'Should have super wild placements');
        $this->assertNotEmpty($placement['wild_placements'], 'Should place wild');
        $this->assertNotEmpty($placement['super_wild_placements'], 'Should place super wild');
        
        // Wild should be constrained to column 2
        $this->assertEquals(2, $placement['wild_placements'][0]['column'], 
            'Wild should be placed in column 2');
    }
    
    /**
     * Test Wild Constraint: Wild must be placed in correct column
     */
    public function testWildColumnConstraint() {
        $config = new DrawConfiguration();
        $config->addRow(['none', 'none', 'wild', 'none', 'none']); // Wild in column 3
        
        $this->board->setCoveredPositions([[0, 0], [1, 1]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        if (!empty($placements) && !empty($placements[0]['wild_placements'])) {
            $wildPlacement = $placements[0]['wild_placements'][0];
            $this->assertEquals(3, $wildPlacement['column'], 
                'Wild should only be placed in its designated column (3)');
        }
    }
    
    /**
     * Test Super Wild Flexibility: Super wild can be placed anywhere
     */
    public function testSuperWildFlexibility() {
        $config = new DrawConfiguration();
        $config->addRow(['super_wild', 'none', 'none', 'none', 'none']);
        
        $this->board->setCoveredPositions([[0, 0], [1, 1], [2, 2], [3, 3]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        $this->assertNotEmpty($placements, 'Should find placements for super wild');
        $this->assertNotEmpty($placements[0]['super_wild_placements'], 'Should place super wild');
        
        // Super wild should be placed to complete main diagonal at [4,4]
        $superWildPlacement = $placements[0]['super_wild_placements'][0];
        $this->assertEquals(5, $superWildPlacement['row'], 'Super wild should complete diagonal');
        $this->assertEquals(5, $superWildPlacement['column'], 'Super wild should complete diagonal');
    }
    
    /**
     * Test Edge Case: No available positions for wild
     */
    public function testEdgeCaseNoWildPositions() {
        $config = new DrawConfiguration();
        $config->addRow(['none', 'wild', 'super_wild', 'none', 'none']);
        
        // Cover entire column 2 (index 1) where wild must be placed
        $this->board->setCoveredPositions([
            [0, 1], [1, 1], [2, 1], [3, 1], [4, 1]
        ]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        // Should still work with just super wild
        $this->assertNotEmpty($placements, 'Should handle case where wild cannot be placed');
        
        if (!empty($placements)) {
            $placement = $placements[0];
            $this->assertEmpty($placement['wild_placements'], 'Should have no wild placements');
            $this->assertNotEmpty($placement['super_wild_placements'], 'Should still place super wild');
        }
    }
    
    /**
     * Test Multiple Draws: Each draw evaluated independently
     */
    public function testMultipleDrawsEvaluation() {
        $config = new DrawConfiguration();
        $config->addRow(['wild', 'none', 'none', 'none', 'none']);
        $config->addRow(['none', 'super_wild', 'none', 'none', 'none']);
        
        $this->board->setCoveredPositions([[0, 0], [1, 1], [2, 2]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        $this->assertCount(2, $placements, 'Should evaluate each draw row separately');
        
        // Each placement should have a row number
        $this->assertEquals(1, $placements[0]['row'], 'First placement should be for row 1');
        $this->assertEquals(2, $placements[1]['row'], 'Second placement should be for row 2');
    }
    
    /**
     * Test Tile Placement Heuristic: Used when 5+ super wilds to avoid memory issues
     */
    public function testTilePlacementHeuristic() {
        $config = new DrawConfiguration();
        $config->addRow(['super_wild', 'super_wild', 'super_wild', 'super_wild', 'super_wild']);
        
        $this->board->setCoveredPositions([[2, 2]]); // Cover center to test priority
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        $this->assertNotEmpty($placements, 'Should handle 5 super wilds with heuristic');
        
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
        
        // Corners should be prioritized: [0,0], [0,4], [4,0], [4,4]
        $corners = [[0,0], [0,4], [4,0], [4,4]];
        $cornerCount = 0;
        foreach ($positions as $pos) {
            if (in_array($pos, $corners)) {
                $cornerCount++;
            }
        }
        
        $this->assertGreaterThan(0, $cornerCount, 'Should prioritize corner positions');
    }
    
    /**
     * Test Tile Priority Order: Center -> Corners -> Diagonal -> Others
     */
    public function testTilePriorityOrder() {
        $config = new DrawConfiguration();
        
        // Use reflection to test the private sorting method
        $reflection = new ReflectionClass($config);
        $sortMethod = $reflection->getMethod('sortPositionsByTilePriority');
        $sortMethod->setAccessible(true);
        
        // Create test positions in random order
        $testPositions = [
            ['row' => 1, 'col' => 2], // Random position
            ['row' => 0, 'col' => 0], // Corner
            ['row' => 2, 'col' => 2], // Center
            ['row' => 1, 'col' => 1], // Diagonal
            ['row' => 4, 'col' => 4], // Corner
        ];
        
        $sorted = $sortMethod->invoke($config, $testPositions);
        
        // Center should be first
        $this->assertEquals(2, $sorted[0]['row'], 'Center should be first priority');
        $this->assertEquals(2, $sorted[0]['col'], 'Center should be first priority');
        
        // Corners should come next
        $this->assertTrue(
            ($sorted[1]['row'] === 0 && $sorted[1]['col'] === 0) ||
            ($sorted[1]['row'] === 4 && $sorted[1]['col'] === 4),
            'Corners should have high priority'
        );
    }
    
    /**
     * Test Mixed Wilds and Super Wilds with Heuristic
     */
    public function testMixedWildsWithHeuristic() {
        $config = new DrawConfiguration();
        $config->addRow(['wild', 'super_wild', 'super_wild', 'super_wild', 'super_wild']); // 1 wild + 4 super wilds = 5 total
        
        $this->board->setCoveredPositions([[0, 0]]);
        
        $placements = $config->getOptimalWildPlacements($this->board);
        
        $this->assertNotEmpty($placements, 'Should handle mixed wilds with heuristic');
        
        $placement = $placements[0];
        $this->assertNotEmpty($placement['wild_placements'], 'Should place wild');
        $this->assertCount(4, $placement['super_wild_placements'], 'Should place 4 super wilds');
        
        // Wild should be in column 1 (0-indexed: column 0)
        $this->assertEquals(1, $placement['wild_placements'][0]['column'], 
            'Wild should be in its designated column');
    }
}
