<?php

require_once __DIR__ . '/../../classes/SlingoAnalyzer.php';
require_once __DIR__ . '/../../classes/SlingoBoard.php';
require_once __DIR__ . '/../../classes/DrawConfiguration.php';

class SlingoAnalyzerTest extends PHPUnit\Framework\TestCase {
    
    private $analyzer;
    private $testBoardData;
    
    protected function setUp(): void {
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
        
        $this->assertArrayHasKey('recommendations', $results);
        $this->assertArrayHasKey('analysis', $results);
        $this->assertIsArray($results['recommendations']);
        $this->assertIsArray($results['analysis']);
    }
    
    public function testAnalysisStructure() {
        $draws = [
            ['row' => 1, 'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']]
        ];
        
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, $draws);
        $analysis = $results['analysis'];
        
        // Check required analysis fields
        $this->assertArrayHasKey('current_slingos', $analysis);
        $this->assertArrayHasKey('potential_slingos', $analysis);
        $this->assertArrayHasKey('covered_cells', $analysis);
        $this->assertArrayHasKey('total_cells', $analysis);
        $this->assertArrayHasKey('completion_percentage', $analysis);
        $this->assertArrayHasKey('probability_breakdown', $analysis);
        $this->assertArrayHasKey('board_state', $analysis);
        
        // Check data types
        $this->assertIsInt($analysis['current_slingos']);
        $this->assertIsInt($analysis['potential_slingos']);
        $this->assertIsInt($analysis['covered_cells']);
        $this->assertIsInt($analysis['total_cells']);
        $this->assertIsNumeric($analysis['completion_percentage']);
    }
    
    public function testCurrentSlingosCalculation() {
        // Complete first row
        $boardData = $this->testBoardData;
        $boardData['covered_positions'] = [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4]];
        
        $draws = [['row' => 1, 'positions' => ['none', 'none', 'none', 'none', 'none']]];
        $results = $this->analyzer->analyzeOptimalStrategy($boardData, $draws);
        
        $this->assertEquals(1, $results['analysis']['current_slingos']);
    }
    
    public function testPotentialSlingosCalculation() {
        $draws = [
            ['row' => 1, 'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']]
        ];
        
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, $draws);
        
        $this->assertGreaterThanOrEqual(0, $results['analysis']['potential_slingos']);
    }
    
    public function testCompletionPercentage() {
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, []);
        $analysis = $results['analysis'];
        
        $expectedPercentage = (count($this->testBoardData['covered_positions']) / 25) * 100;
        $this->assertEquals($expectedPercentage, $analysis['completion_percentage']);
    }
    
    public function testProbabilityBreakdown() {
        $draws = [
            ['row' => 1, 'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']],
            ['row' => 2, 'positions' => ['none', 'wild', 'none', 'wild', 'none']]
        ];
        
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, $draws);
        $breakdown = $results['analysis']['probability_breakdown'];
        
        $this->assertArrayHasKey('slingo_completion', $breakdown);
        $this->assertArrayHasKey('partial_completion', $breakdown);
        $this->assertArrayHasKey('setup_moves', $breakdown);
        
        // Percentages should add up to 100
        $total = $breakdown['slingo_completion'] + 
                $breakdown['partial_completion'] + 
                $breakdown['setup_moves'];
        $this->assertEquals(100, $total);
    }
    
    public function testCalculateExpectedValue() {
        // Create a board with some Slingos
        $boardData = $this->testBoardData;
        $boardData['covered_positions'] = [[0, 0], [0, 1], [0, 2], [0, 3], [0, 4]]; // Complete row
        
        $draws = [['row' => 1, 'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']]];
        
        $expectedValue = $this->analyzer->calculateExpectedValue($draws);
        $this->assertGreaterThan(0, $expectedValue);
    }
    
    public function testGenerateRecommendations() {
        $draws = [['row' => 1, 'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']]];
        
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, $draws);
        $recommendations = $results['recommendations'];
        
        $this->assertIsArray($recommendations);
        
        if (!empty($recommendations)) {
            $recommendation = $recommendations[0];
            $this->assertArrayHasKey('row', $recommendation);
            $this->assertArrayHasKey('positions', $recommendation);
            $this->assertArrayHasKey('expected_score', $recommendation);
            $this->assertArrayHasKey('reasoning', $recommendation);
        }
    }
    
    public function testInvalidBoardData() {
        $this->expectException(Exception::class);
        
        $invalidData = [
            'board_numbers' => 'invalid',
            'covered_positions' => 'invalid'
        ];
        
        $this->analyzer->analyzeOptimalStrategy($invalidData, []);
    }
    
    public function testInvalidDraws() {
        $this->expectException(Exception::class);
        
        $invalidDraws = [
            ['row' => 1, 'positions' => ['invalid', 'none', 'wild', 'none', 'wild']]
        ];
        
        $this->analyzer->analyzeOptimalStrategy($this->testBoardData, $invalidDraws);
    }
    
    public function testEmptyDraws() {
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, []);
        
        $this->assertIsArray($results['recommendations']);
        $this->assertIsArray($results['analysis']);
        $this->assertEquals(0, $results['analysis']['potential_slingos']);
    }
    
    public function testMultipleDrawRows() {
        $draws = [
            ['row' => 1, 'positions' => ['wild', 'none', 'none', 'none', 'none']],
            ['row' => 2, 'positions' => ['none', 'wild', 'none', 'none', 'none']],
            ['row' => 3, 'positions' => ['none', 'none', 'super_wild', 'none', 'none']]
        ];
        
        $results = $this->analyzer->analyzeOptimalStrategy($this->testBoardData, $draws);
        
        $this->assertIsArray($results['recommendations']);
        $this->assertIsArray($results['analysis']);
        
        // Should have recommendations for each draw row
        $this->assertGreaterThan(0, count($results['recommendations']));
    }
    
    public function testEdgeCases() {
        // Test with completely empty board
        $emptyBoardData = [
            'board_numbers' => $this->testBoardData['board_numbers'],
            'covered_positions' => []
        ];
        
        $draws = [['row' => 1, 'positions' => ['wild', 'wild', 'wild', 'wild', 'wild']]];
        $results = $this->analyzer->analyzeOptimalStrategy($emptyBoardData, $draws);
        
        $this->assertEquals(0, $results['analysis']['current_slingos']);
        $this->assertEquals(0, $results['analysis']['completion_percentage']);
        
        // Test with completely full board
        $fullBoardData = [
            'board_numbers' => $this->testBoardData['board_numbers'],
            'covered_positions' => array_merge(
                array_map(fn($i) => [0, $i], range(0, 4)),
                array_map(fn($i) => [1, $i], range(0, 4)),
                array_map(fn($i) => [2, $i], range(0, 4)),
                array_map(fn($i) => [3, $i], range(0, 4)),
                array_map(fn($i) => [4, $i], range(0, 4))
            )
        ];
        
        $results = $this->analyzer->analyzeOptimalStrategy($fullBoardData, $draws);
        $this->assertEquals(100, $results['analysis']['completion_percentage']);
    }
}
