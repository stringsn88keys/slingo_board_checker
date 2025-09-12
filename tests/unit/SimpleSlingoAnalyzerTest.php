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
}
?>
