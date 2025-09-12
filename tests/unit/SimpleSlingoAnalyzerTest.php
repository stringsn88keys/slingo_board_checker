<?php

require_once __DIR__ . '/../SimpleTest.php';
require_once __DIR__ . '/../../classes/SlingoAnalyzer.php';
require_once __DIR__ . '/../../classes/SlingoBoard.php';
require_once __DIR__ . '/../../classes/DrawConfiguration.php';

class SimpleSlingoAnalyzerTest extends SimpleTest {
    
    private $analyzer;
    private $testBoardData;
    
    public function setUp() {
        $this->analyzer = new SlingoAnalyzer();
        
        // Create test board data
        $this->testBoardData = [
            'board_numbers' => [
                [1, 16, 31, 46, 61],
                [2, 17, 32, 47, 62],
                [3, 18, 33, 48, 63],
                [4, 19, 34, 49, 64],
                [5, 20, 35, 50, 65]
            ],
            'covered_positions' => [[0, 0], [0, 1], [0, 3], [0, 4]]
        ];
    }
    
    public function testAnalyzeOptimalStrategy() {
        $draws = [
            ['row' => 1, 'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']],
            ['row' => 2, 'positions' => ['none', 'wild', 'none', 'wild', 'none']]
        ];
        
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, $draws);
        
        $this->assertArrayHasKey('recommendations', $results, 'Results should have recommendations');
        $this->assertArrayHasKey('analysis', $results, 'Results should have analysis');
        $this->assertIsArray($results['recommendations'], 'Recommendations should be array');
        $this->assertIsArray($results['analysis'], 'Analysis should be array');
    }
    
    public function testAnalysisStructure() {
        $draws = [
            ['row' => 1, 'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']]
        ];
        
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, $draws);
        $analysis = $results['analysis'];
        
        // Check required analysis fields
        $this->assertArrayHasKey('current_slingos', $analysis, 'Analysis should have current_slingos');
        $this->assertArrayHasKey('potential_slingos', $analysis, 'Analysis should have potential_slingos');
        $this->assertArrayHasKey('covered_cells', $analysis, 'Analysis should have covered_cells');
        $this->assertArrayHasKey('total_cells', $analysis, 'Analysis should have total_cells');
        $this->assertArrayHasKey('completion_percentage', $analysis, 'Analysis should have completion_percentage');
        $this->assertArrayHasKey('probability_breakdown', $analysis, 'Analysis should have probability_breakdown');
        $this->assertArrayHasKey('board_state', $analysis, 'Analysis should have board_state');
        
        // Check data types
        $this->assertIsInt($analysis['current_slingos'], 'Current Slingos should be integer');
        $this->assertIsInt($analysis['potential_slingos'], 'Potential Slingos should be integer');
        $this->assertIsInt($analysis['covered_cells'], 'Covered cells should be integer');
        $this->assertIsInt($analysis['total_cells'], 'Total cells should be integer');
        $this->assertIsNumeric($analysis['completion_percentage'], 'Completion percentage should be numeric');
    }
    
    public function testCurrentSlingosCalculation() {
        // Complete first row
        $boardData = $this->testBoardData;
        $boardData['covered_positions'] = [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4]];
        
        $draws = [['row' => 1, 'positions' => ['none', 'none', 'none', 'none', 'none']]];
        $results = $this->analyzer->analyzeOptimalStrategy($boardData, $draws);
        
        $this->assertEquals(1, $results['analysis']['current_slingos'], 'Should detect 1 current Slingo');
    }
    
    public function testCompletionPercentage() {
        // Use a valid draw configuration for this test
        $draws = [['row' => 1, 'positions' => ['none', 'none', 'none', 'none', 'none']]];
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, $draws);
        $analysis = $results['analysis'];
        
        $expectedPercentage = (count($this->testBoardData['covered_positions']) / 25) * 100;
        $this->assertEquals($expectedPercentage, $analysis['completion_percentage'], 'Completion percentage should match expected');
    }
    
    public function testProbabilityBreakdown() {
        $draws = [
            ['row' => 1, 'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']],
            ['row' => 2, 'positions' => ['none', 'wild', 'none', 'wild', 'none']]
        ];
        
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, $draws);
        $breakdown = $results['analysis']['probability_breakdown'];
        
        $this->assertArrayHasKey('slingo_completion', $breakdown, 'Breakdown should have slingo_completion');
        $this->assertArrayHasKey('partial_completion', $breakdown, 'Breakdown should have partial_completion');
        $this->assertArrayHasKey('setup_moves', $breakdown, 'Breakdown should have setup_moves');
        
        // Percentages should add up to 100
        $total = $breakdown['slingo_completion'] + 
                $breakdown['partial_completion'] + 
                $breakdown['setup_moves'];
        $this->assertEquals(100, $total, 'Probability breakdown should add up to 100%');
    }
    
    public function testCalculateExpectedValue() {
        // Create a board with some Slingos
        $boardData = $this->testBoardData;
        $boardData['covered_positions'] = [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4]]; // Complete row
        
        $draws = [['row' => 1, 'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']]];
        
        $expectedValue = $this->analyzer->calculateExpectedValue($draws);
        $this->assertGreaterThan(0, $expectedValue, 'Expected value should be positive');
    }
    
    public function testEmptyDraws() {
        // Test with minimal valid draw configuration
        $draws = [['row' => 1, 'positions' => ['none', 'none', 'none', 'none', 'none']]];
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, $draws);
        
        $this->assertIsArray($results['recommendations'], 'Recommendations should be array');
        $this->assertIsArray($results['analysis'], 'Analysis should be array');
        $this->assertGreaterThanOrEqual(0, $results['analysis']['potential_slingos'], 'Potential Slingos should be non-negative');
    }
    
    /**
     * Test Priority 1: Completed Slingos get highest priority
     */
    public function testPriority1CompletedSlingos() {
        // Board with positions that can complete a Slingo
        $boardData = [
            'board_numbers' => [
                [1, 16, 31, 46, 61],
                [2, 17, 32, 47, 62],
                [3, 18, 33, 48, 63],
                [4, 19, 34, 49, 64],
                [5, 20, 35, 50, 65]
            ],
            'covered_positions' => [[0, 0], [1, 3], [2, 2], [3, 3]] // Can complete main diagonal with [4,4]
        ];
        
        $draws = [['row' => 1, 'positions' => ['super_wild', 'wild', 'none', 'none', 'none']]];
        $results = $this->analyzer->analyzeOptimalStrategy($boardData, $draws);
        
        $this->assertGreaterThan(10000, $results['recommendations'][0]['expected_score'], 
            'Completed Slingo should have very high score (>10000)');
        $this->assertStringContains('Completes', $results['recommendations'][0]['reasoning'], 
            'Reasoning should mention completing Slingo');
    }
    
    /**
     * Test Priority 2: Diagonal Slingos get priority over horizontal/vertical
     */
    public function testPriority2DiagonalPriority() {
        // Board where both horizontal and diagonal Slingos can be completed
        $boardData = [
            'board_numbers' => [
                [1, 16, 31, 46, 61],
                [2, 17, 32, 47, 62],
                [3, 18, 33, 48, 63],
                [4, 19, 34, 49, 64],
                [5, 20, 35, 50, 65]
            ],
            'covered_positions' => [
                [0, 0], [0, 2], [0, 3], [0, 4], // Row 1 needs [0,1] to complete
                [1, 1], [2, 2], [3, 3]          // Main diagonal needs [4,4] to complete
            ]
        ];
        
        $draws = [['row' => 1, 'positions' => ['super_wild', 'none', 'none', 'none', 'none']]];
        $results = $this->analyzer->analyzeOptimalStrategy($boardData, $draws);
        
        // Should place super wild at [4,4] for diagonal, not [0,1] for row
        $placement = $results['recommendations'][0]['super_wild_placements'][0];
        $this->assertEquals(5, $placement['row'], 'Should prioritize diagonal completion at row 5');
        $this->assertEquals(5, $placement['column'], 'Should prioritize diagonal completion at column 5');
    }
    
    /**
     * Test Priority 3: Setup moves when no Slingos can be completed
     */
    public function testPriority3SetupMoves() {
        // Board with scattered coverage, no completable Slingos
        $boardData = [
            'board_numbers' => [
                [1, 16, 31, 46, 61],
                [2, 17, 32, 47, 62],
                [3, 18, 33, 48, 63],
                [4, 19, 34, 49, 64],
                [5, 20, 35, 50, 65]
            ],
            'covered_positions' => [[0, 0], [2, 2]] // Only 2 scattered positions
        ];
        
        $draws = [['row' => 1, 'positions' => ['super_wild', 'wild', 'none', 'none', 'none']]];
        $results = $this->analyzer->analyzeOptimalStrategy($boardData, $draws);
        
        // Should have lower score since no Slingos completed, but should maximize potential
        $this->assertLessThan(10000, $results['recommendations'][0]['expected_score'], 
            'Setup moves should have lower score than completed Slingos');
        $this->assertGreaterThan(0, $results['recommendations'][0]['expected_score'], 
            'Setup moves should still have positive score');
    }
    
    /**
     * Test depth-first search generates valid combinations
     */
    public function testDepthFirstSearchValidCombinations() {
        $boardData = [
            'board_numbers' => [
                [1, 16, 31, 46, 61],
                [2, 17, 32, 47, 62],
                [3, 18, 33, 48, 63],
                [4, 19, 34, 49, 64],
                [5, 20, 35, 50, 65]
            ],
            'covered_positions' => [[0, 0], [1, 3], [2, 2], [3, 3]]
        ];
        
        $draws = [['row' => 1, 'positions' => ['super_wild', 'wild', 'none', 'none', 'none']]];
        $results = $this->analyzer->analyzeOptimalStrategy($boardData, $draws);
        
        $recommendation = $results['recommendations'][0];
        
        // Should have both wild and super wild placements
        $this->assertNotEmpty($recommendation['wild_placements'], 'Should have wild placements');
        $this->assertNotEmpty($recommendation['super_wild_placements'], 'Should have super wild placements');
        
        // Wild should be in column 2 (index 1, display as column 2)
        $wildPlacement = $recommendation['wild_placements'][0];
        $this->assertEquals(2, $wildPlacement['column'], 'Wild should be placed in column 2');
        
        // Placements should be on uncovered positions
        foreach ($recommendation['wild_placements'] as $placement) {
            $row = $placement['row'] - 1;
            $col = $placement['column'] - 1;
            $board = new \SlingoBoard($boardData['board_numbers']);
            $board->setCoveredPositions($boardData['covered_positions']);
            $this->assertFalse($board->isCovered($row, $col), 
                "Wild placement at [$row, $col] should be on uncovered position");
        }
    }
    
    /**
     * Test specific example from requirements
     */
    public function testSpecificExampleFromRequirements() {
        // [[1,1], [2,4], [3,3], [4,4]] with [super wild, wild, none, none, none]
        $boardData = [
            'board_numbers' => [
                [1, 16, 31, 46, 61],
                [2, 17, 32, 47, 62],
                [3, 18, 33, 48, 63],
                [4, 19, 34, 49, 64],
                [5, 20, 35, 50, 65]
            ],
            'covered_positions' => [[0, 0], [1, 3], [2, 2], [3, 3]] // Converting 1-indexed to 0-indexed
        ];
        
        $draws = [['row' => 1, 'positions' => ['super_wild', 'wild', 'none', 'none', 'none']]];
        $results = $this->analyzer->analyzeOptimalStrategy($boardData, $draws);
        
        $recommendation = $results['recommendations'][0];
        
        // Should complete a Slingo (main diagonal)
        $this->assertStringContains('Completes', $recommendation['reasoning'], 
            'Should complete a Slingo');
        
        // Should have high score for completed Slingo
        $this->assertGreaterThan(10000, $recommendation['expected_score'], 
            'Should have high score for completed Slingo');
        
        // Should place both wild and super wild
        $this->assertCount(1, $recommendation['wild_placements'], 'Should place exactly 1 wild');
        $this->assertCount(1, $recommendation['super_wild_placements'], 'Should place exactly 1 super wild');
        
        // Wild should be constrained to column 2
        $this->assertEquals(2, $recommendation['wild_placements'][0]['column'], 
            'Wild should be in column 2');
    }
    
    /**
     * Test that algorithm handles edge cases properly
     */
    public function testEdgeCasesHandling() {
        // Test with board where wild column has no available positions
        $boardData = [
            'board_numbers' => [
                [1, 16, 31, 46, 61],
                [2, 17, 32, 47, 62],
                [3, 18, 33, 48, 63],
                [4, 19, 34, 49, 64],
                [5, 20, 35, 50, 65]
            ],
            // Cover entire column 2 (index 1)
            'covered_positions' => [[0, 1], [1, 1], [2, 1], [3, 1], [4, 1]]
        ];
        
        $draws = [['row' => 1, 'positions' => ['none', 'wild', 'super_wild', 'none', 'none']]];
        $results = $this->analyzer->analyzeOptimalStrategy($boardData, $draws);
        
        // Should still provide a recommendation even when wild can't be placed
        $this->assertNotEmpty($results['recommendations'], 'Should handle edge case gracefully');
        
        $recommendation = $results['recommendations'][0];
        // Should only have super wild placement since wild column is full
        $this->assertTrue(empty($recommendation['wild_placements']), 'No wild placements when column is full');
        $this->assertNotEmpty($recommendation['super_wild_placements'], 'Should still place super wild');
    }
}
?>
