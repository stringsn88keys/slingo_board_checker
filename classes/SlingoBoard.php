<?php

class SlingoBoard {
    private $board = [];
    private $coveredPositions = [];
    private $boardNumbers = [];

    public function __construct($boardNumbers = null) {
        if ($boardNumbers) {
            $this->boardNumbers = $boardNumbers;
        } else {
            $this->generateRandomBoard();
        }
    }

    /**
     * Generate a random 5x5 Slingo board
     */
    private function generateRandomBoard() {
        $this->boardNumbers = [];
        for ($row = 0; $row < 5; $row++) {
            $this->boardNumbers[$row] = [];
            for ($col = 0; $col < 5; $col++) {
                // Generate numbers 1-75 for each column
                $min = $col * 15 + 1;
                $max = ($col + 1) * 15;
                $this->boardNumbers[$row][$col] = rand($min, $max);
            }
        }
    }

    /**
     * Set covered positions on the board
     */
    public function setCoveredPositions($positions) {
        $this->coveredPositions = [];
        foreach ($positions as $position) {
            if (is_array($position) && count($position) === 2) {
                $this->coveredPositions[] = $position;
            }
        }
    }

    /**
     * Check if a position is covered
     */
    public function isCovered($row, $col) {
        foreach ($this->coveredPositions as $position) {
            if ($position[0] === $row && $position[1] === $col) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get current Slingos (completed lines)
     */
    public function getCurrentSlingos() {
        $slingos = 0;
        
        // Check horizontal lines
        for ($row = 0; $row < 5; $row++) {
            if ($this->isRowComplete($row)) {
                $slingos++;
            }
        }
        
        // Check vertical lines
        for ($col = 0; $col < 5; $col++) {
            if ($this->isColumnComplete($col)) {
                $slingos++;
            }
        }
        
        // Check diagonals
        if ($this->isDiagonalComplete(true)) { // Main diagonal
            $slingos++;
        }
        if ($this->isDiagonalComplete(false)) { // Anti-diagonal
            $slingos++;
        }
        
        return $slingos;
    }

    /**
     * Check if a row is complete (all cells covered)
     */
    private function isRowComplete($row) {
        for ($col = 0; $col < 5; $col++) {
            if (!$this->isCovered($row, $col)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if a column is complete (all cells covered)
     */
    private function isColumnComplete($col) {
        for ($row = 0; $row < 5; $row++) {
            if (!$this->isCovered($row, $col)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if a diagonal is complete
     */
    private function isDiagonalComplete($mainDiagonal) {
        for ($i = 0; $i < 5; $i++) {
            $row = $i;
            $col = $mainDiagonal ? $i : (4 - $i);
            if (!$this->isCovered($row, $col)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get potential Slingos with given draw configuration
     */
    public function getPotentialSlingos($draws) {
        $potentialSlingos = 0;
        
        foreach ($draws as $draw) {
            $positions = $draw['positions'];
            
            // Check each row for potential completion
            for ($row = 0; $row < 5; $row++) {
                if ($this->canCompleteRow($row, $positions)) {
                    $potentialSlingos++;
                }
            }
            
            // Check each column for potential completion
            for ($col = 0; $col < 5; $col++) {
                if ($this->canCompleteColumn($col, $positions)) {
                    $potentialSlingos++;
                }
            }
            
            // Check diagonals
            if ($this->canCompleteDiagonal(true, $positions)) {
                $potentialSlingos++;
            }
            if ($this->canCompleteDiagonal(false, $positions)) {
                $potentialSlingos++;
            }
        }
        
        return $potentialSlingos;
    }

    /**
     * Check if a row can be completed with given draw positions
     */
    private function canCompleteRow($row, $positions) {
        $neededWilds = 0;
        for ($col = 0; $col < 5; $col++) {
            if (!$this->isCovered($row, $col)) {
                if ($positions[$col] === 'none') {
                    return false; // Can't complete without wild
                }
                $neededWilds++;
            }
        }
        return $neededWilds > 0; // At least one wild needed
    }

    /**
     * Check if a column can be completed with given draw positions
     */
    private function canCompleteColumn($col, $positions) {
        $neededWilds = 0;
        for ($row = 0; $row < 5; $row++) {
            if (!$this->isCovered($row, $col)) {
                if ($positions[$col] === 'none') {
                    return false; // Can't complete without wild
                }
                $neededWilds++;
            }
        }
        return $neededWilds > 0; // At least one wild needed
    }

    /**
     * Check if a diagonal can be completed with given draw positions
     */
    private function canCompleteDiagonal($mainDiagonal, $positions) {
        $neededWilds = 0;
        for ($i = 0; $i < 5; $i++) {
            $row = $i;
            $col = $mainDiagonal ? $i : (4 - $i);
            if (!$this->isCovered($row, $col)) {
                if ($positions[$col] === 'none') {
                    return false; // Can't complete without wild
                }
                $neededWilds++;
            }
        }
        return $neededWilds > 0; // At least one wild needed
    }

    /**
     * Get board numbers
     */
    public function getBoardNumbers() {
        return $this->boardNumbers;
    }

    /**
     * Get covered positions
     */
    public function getCoveredPositions() {
        return $this->coveredPositions;
    }

    /**
     * Get board state for analysis
     */
    public function getBoardState() {
        return [
            'board_numbers' => $this->boardNumbers,
            'covered_positions' => $this->coveredPositions,
            'current_slingos' => $this->getCurrentSlingos()
        ];
    }
}
