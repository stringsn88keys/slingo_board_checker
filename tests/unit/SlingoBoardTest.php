<?php

require_once __DIR__ . '/../../classes/SlingoBoard.php';

class SlingoBoardTest extends PHPUnit\Framework\TestCase {
    
    private $board;
    
    protected function setUp(): void {
        // Create a test board with known numbers
        $testNumbers = [
            [1, 16, 31, 46, 61],
            [2, 17, 32, 47, 62],
            [3, 18, 33, 48, 63],
            [4, 19, 34, 49, 64],
            [5, 20, 35, 50, 65]
        ];
        $this->board = new SlingoBoard($testNumbers);
    }
    
    public function testBoardCreation() {
        $this->assertInstanceOf(SlingoBoard::class, $this->board);
        $numbers = $this->board->getBoardNumbers();
        $this->assertCount(5, $numbers);
        $this->assertCount(5, $numbers[0]);
    }
    
    public function testSetCoveredPositions() {
        $positions = [[0, 0], [1, 1], [2, 2]];
        $this->board->setCoveredPositions($positions);
        
        $this->assertTrue($this->board->isCovered(0, 0));
        $this->assertTrue($this->board->isCovered(1, 1));
        $this->assertTrue($this->board->isCovered(2, 2));
        $this->assertFalse($this->board->isCovered(0, 1));
    }
    
    public function testHorizontalSlingoDetection() {
        // Complete first row
        $this->board->setCoveredPositions([[0, 0], [0, 1], [0, 2], [0, 3], [0, 4]]);
        $this->assertEquals(1, $this->board->getCurrentSlingos());
        
        // Complete second row
        $this->board->setCoveredPositions([
            [0, 0], [0, 1], [0, 2], [0, 3], [0, 4],
            [1, 0], [1, 1], [1, 2], [1, 3], [1, 4]
        ]);
        $this->assertEquals(2, $this->board->getCurrentSlingos());
    }
    
    public function testVerticalSlingoDetection() {
        // Complete first column
        $this->board->setCoveredPositions([[0, 0], [1, 0], [2, 0], [3, 0], [4, 0]]);
        $this->assertEquals(1, $this->board->getCurrentSlingos());
        
        // Complete second column
        $this->board->setCoveredPositions([
            [0, 0], [1, 0], [2, 0], [3, 0], [4, 0],
            [0, 1], [1, 1], [2, 1], [3, 1], [4, 1]
        ]);
        $this->assertEquals(2, $this->board->getCurrentSlingos());
    }
    
    public function testDiagonalSlingoDetection() {
        // Complete main diagonal
        $this->board->setCoveredPositions([[0, 0], [1, 1], [2, 2], [3, 3], [4, 4]]);
        $this->assertEquals(1, $this->board->getCurrentSlingos());
        
        // Complete anti-diagonal
        $this->board->setCoveredPositions([[0, 4], [1, 3], [2, 2], [3, 1], [4, 0]]);
        $this->assertEquals(1, $this->board->getCurrentSlingos());
    }
    
    public function testMultipleSlingos() {
        // Complete both diagonals
        $this->board->setCoveredPositions([
            [0, 0], [1, 1], [2, 2], [3, 3], [4, 4], // Main diagonal
            [0, 4], [1, 3], [2, 2], [3, 1], [4, 0]  // Anti-diagonal
        ]);
        $this->assertEquals(2, $this->board->getCurrentSlingos());
    }
    
    public function testPotentialSlingosWithWilds() {
        // Set up a row that can be completed with wilds
        $this->board->setCoveredPositions([[0, 0], [0, 1], [0, 3], [0, 4]]);
        
        $draws = [
            ['positions' => ['none', 'wild', 'none', 'wild', 'none']]
        ];
        
        $potentialSlingos = $this->board->getPotentialSlingos($draws);
        $this->assertGreaterThan(0, $potentialSlingos);
    }
    
    public function testPotentialSlingosWithSuperWilds() {
        // Set up a column that can be completed with super wilds
        $this->board->setCoveredPositions([[0, 0], [1, 0], [3, 0], [4, 0]]);
        
        $draws = [
            ['positions' => ['super_wild', 'none', 'none', 'none', 'none']]
        ];
        
        $potentialSlingos = $this->board->getPotentialSlingos($draws);
        $this->assertGreaterThan(0, $potentialSlingos);
    }
    
    public function testNoPotentialSlingosWithoutWilds() {
        // Set up a row that cannot be completed without wilds
        $this->board->setCoveredPositions([[0, 0], [0, 1], [0, 3], [0, 4]]);
        
        $draws = [
            ['positions' => ['none', 'none', 'none', 'none', 'none']]
        ];
        
        $potentialSlingos = $this->board->getPotentialSlingos($draws);
        $this->assertEquals(0, $potentialSlingos);
    }
    
    public function testBoardState() {
        $this->board->setCoveredPositions([[0, 0], [1, 1]]);
        $state = $this->board->getBoardState();
        
        $this->assertArrayHasKey('board_numbers', $state);
        $this->assertArrayHasKey('covered_positions', $state);
        $this->assertArrayHasKey('current_slingos', $state);
        $this->assertCount(2, $state['covered_positions']);
    }
    
    public function testRandomBoardGeneration() {
        $randomBoard = new SlingoBoard();
        $numbers = $randomBoard->getBoardNumbers();
        
        // Check column ranges
        for ($col = 0; $col < 5; $col++) {
            $min = $col * 15 + 1;
            $max = ($col + 1) * 15;
            
            for ($row = 0; $row < 5; $row++) {
                $this->assertGreaterThanOrEqual($min, $numbers[$row][$col]);
                $this->assertLessThanOrEqual($max, $numbers[$row][$col]);
            }
        }
    }
    
    public function testEdgeCases() {
        // Test empty covered positions
        $this->board->setCoveredPositions([]);
        $this->assertEquals(0, $this->board->getCurrentSlingos());
        
        // Test invalid position format
        $this->board->setCoveredPositions([['invalid'], [0, 1, 2]]);
        $this->assertEquals(0, $this->board->getCurrentSlingos());
        
        // Test out of bounds positions
        $this->board->setCoveredPositions([[5, 5], [-1, -1]]);
        $this->assertFalse($this->board->isCovered(5, 5));
        $this->assertFalse($this->board->isCovered(-1, -1));
    }
}
