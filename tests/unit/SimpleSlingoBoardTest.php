<?php

require_once __DIR__ . '/../SimpleTest.php';
require_once __DIR__ . '/../../classes/SlingoBoard.php';

class SimpleSlingoBoardTest extends SimpleTest {
    
    private $board;
    
    public function setUp() {
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
        $this->assertTrue($this->board instanceof SlingoBoard, 'Board should be instance of SlingoBoard');
        $numbers = $this->board->getBoardNumbers();
        $this->assertCount(5, $numbers, 'Board should have 5 rows');
        $this->assertCount(5, $numbers[0], 'Board should have 5 columns');
    }
    
    public function testSetCoveredPositions() {
        $positions = [[0, 0], [1, 1], [2, 2]];
        $this->board->setCoveredPositions($positions);
        
        $this->assertTrue($this->board->isCovered(0, 0), 'Position [0,0] should be covered');
        $this->assertTrue($this->board->isCovered(1, 1), 'Position [1,1] should be covered');
        $this->assertTrue($this->board->isCovered(2, 2), 'Position [2,2] should be covered');
        $this->assertFalse($this->board->isCovered(0, 1), 'Position [0,1] should not be covered');
    }
    
    public function testHorizontalSlingoDetection() {
        // Complete first row
        $this->board->setCoveredPositions([[0, 0], [0, 1], [0, 2], [0, 3], [0, 4]]);
        $slingos = $this->board->getCurrentSlingos();
        $this->assertEquals(1, $slingos, 'Should detect 1 horizontal Slingo');
        
        // Complete second row
        $this->board->setCoveredPositions([
            [0, 0], [0, 1], [0, 2], [0, 3], [0, 4],
            [1, 0], [1, 1], [1, 2], [1, 3], [1, 4]
        ]);
        $slingos = $this->board->getCurrentSlingos();
        $this->assertEquals(2, $slingos, 'Should detect 2 horizontal Slingos');
    }
    
    public function testVerticalSlingoDetection() {
        // Complete first column
        $this->board->setCoveredPositions([[0, 0], [1, 0], [2, 0], [3, 0], [4, 0]]);
        $slingos = $this->board->getCurrentSlingos();
        $this->assertEquals(1, $slingos, 'Should detect 1 vertical Slingo');
    }
    
    public function testDiagonalSlingoDetection() {
        // Complete main diagonal
        $this->board->setCoveredPositions([[0, 0], [1, 1], [2, 2], [3, 3], [4, 4]]);
        $slingos = $this->board->getCurrentSlingos();
        $this->assertEquals(1, $slingos, 'Should detect 1 diagonal Slingo');
    }
    
    public function testPotentialSlingosWithWilds() {
        // Set up a row that can be completed with wilds
        $this->board->setCoveredPositions([[0, 0], [0, 1], [0, 3], [0, 4]]);
        
        $draws = [
            ['positions' => ['none', 'wild', 'none', 'wild', 'none']]
        ];
        
        $potentialSlingos = $this->board->getPotentialSlingos($draws);
        $this->assertGreaterThan(0, $potentialSlingos, 'Should have potential Slingos with wilds');
    }
    
    public function testRandomBoardGeneration() {
        $randomBoard = new SlingoBoard();
        $numbers = $randomBoard->getBoardNumbers();
        
        // Check column ranges
        for ($col = 0; $col < 5; $col++) {
            $min = $col * 15 + 1;
            $max = ($col + 1) * 15;
            
            for ($row = 0; $row < 5; $row++) {
                $this->assertGreaterThanOrEqual($min, $numbers[$row][$col], "Number in column {$col} should be >= {$min}");
                $this->assertLessThanOrEqual($max, $numbers[$row][$col], "Number in column {$col} should be <= {$max}");
            }
        }
    }
    
    public function testBoardState() {
        $this->board->setCoveredPositions([[0, 0], [1, 1]]);
        $state = $this->board->getBoardState();
        
        $this->assertArrayHasKey('board_numbers', $state, 'Board state should have board_numbers');
        $this->assertArrayHasKey('covered_positions', $state, 'Board state should have covered_positions');
        $this->assertArrayHasKey('current_slingos', $state, 'Board state should have current_slingos');
        $this->assertCount(2, $state['covered_positions'], 'Should have 2 covered positions');
    }
}
?>
