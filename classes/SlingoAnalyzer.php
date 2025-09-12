<?php

require_once 'SlingoBoard.php';
require_once 'DrawConfiguration.php';

class SlingoAnalyzer {
    private $board;
    private $drawConfig;

    public function __construct() {
        // Constructor can be empty as we'll set board and config in analyze method
    }

    /**
     * Analyze optimal strategy for given board and draws
     */
    public function analyzeOptimalStrategy($boardData, $draws) {
        try {
            // Create board instance
            $this->board = new SlingoBoard($boardData['board_numbers']);
            $this->board->setCoveredPositions($boardData['covered_positions']);
            
            // Create draw configuration
            $this->drawConfig = new DrawConfiguration();
            foreach ($draws as $draw) {
                $this->drawConfig->addRow($draw['positions']);
            }
            
            // Validate configuration
            $validation = $this->drawConfig->validateConfiguration();
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }
            
            // Get optimal placements
            $optimalSelections = $this->drawConfig->getOptimalWildPlacements($this->board);
            
            // Generate analysis
            $analysis = $this->generateAnalysis();
            
            return [
                'recommendations' => $optimalSelections,
                'analysis' => $analysis
            ];
            
        } catch (Exception $e) {
            throw new Exception("Analysis failed: " . $e->getMessage());
        }
    }

    /**
     * Calculate expected value for a configuration
     */
    public function calculateExpectedValue($configuration) {
        $expectedValue = 0;
        
        // Base score from current Slingos
        $currentSlingos = $this->board->getCurrentSlingos();
        $expectedValue += $currentSlingos * 25;
        
        // Potential score from optimal placements
        $optimalPlacements = $this->drawConfig->getOptimalWildPlacements($this->board);
        foreach ($optimalPlacements as $placement) {
            $expectedValue += $placement['expected_score'];
        }
        
        return $expectedValue;
    }

    /**
     * Generate recommendations based on analysis
     */
    public function generateRecommendations() {
        return $this->drawConfig->getOptimalWildPlacements($this->board);
    }

    /**
     * Simulate possible outcomes
     */
    private function simulateOutcomes() {
        $outcomes = [];
        
        // Simulate different wild placements
        $optimalPlacements = $this->drawConfig->getOptimalWildPlacements($this->board);
        
        foreach ($optimalPlacements as $placement) {
            $outcomes[] = [
                'placement' => $placement,
                'probability' => $this->calculateProbability($placement),
                'expected_value' => $placement['expected_score']
            ];
        }
        
        return $outcomes;
    }

    /**
     * Calculate probability of success for a placement
     */
    private function calculateProbability($placement) {
        $wildCount = array_count_values($placement)['wild'] ?? 0;
        $superWildCount = array_count_values($placement)['super_wild'] ?? 0;
        
        // Base probability based on wild types
        $baseProbability = 0.8; // 80% base success rate
        
        // Adjust for super wilds (higher success rate)
        if ($superWildCount > 0) {
            $baseProbability += 0.15 * $superWildCount;
        }
        
        // Adjust for regular wilds
        if ($wildCount > 0) {
            $baseProbability += 0.1 * $wildCount;
        }
        
        // Cap at 95%
        return min($baseProbability, 0.95);
    }

    /**
     * Generate comprehensive analysis
     */
    private function generateAnalysis() {
        $currentSlingos = $this->board->getCurrentSlingos();
        $coveredPositions = $this->board->getCoveredPositions();
        $totalCells = 25;
        $originalCoveredCells = count($coveredPositions);
        
        // Calculate potential Slingos with actual placements
        $potentialSlingos = $this->calculatePotentialSlingosWithPlacements();
        
        // Calculate total covered cells including wild placements
        $totalCoveredCells = $this->calculateTotalCoveredCells();
        
        // Calculate board completion percentage
        $completionPercentage = round(($totalCoveredCells / $totalCells) * 100, 1);
        
        // Calculate probability breakdown
        $probabilityBreakdown = $this->calculateProbabilityBreakdown();
        
        return [
            'current_slingos' => $currentSlingos,
            'potential_slingos' => $potentialSlingos,
            'covered_cells' => $totalCoveredCells,
            'original_covered_cells' => $originalCoveredCells,
            'total_cells' => $totalCells,
            'completion_percentage' => $completionPercentage,
            'probability_breakdown' => $probabilityBreakdown,
            'board_state' => $this->board->getBoardState()
        ];
    }
    
    /**
     * Calculate total covered cells including wild placements
     */
    private function calculateTotalCoveredCells() {
        $originalCovered = count($this->board->getCoveredPositions());
        $optimalPlacements = $this->drawConfig->getOptimalWildPlacements($this->board);
        
        $wildPlacements = 0;
        $superWildPlacements = 0;
        
        foreach ($optimalPlacements as $placement) {
            if (isset($placement['wild_placements'])) {
                $wildPlacements += count($placement['wild_placements']);
            }
            if (isset($placement['super_wild_placements'])) {
                $superWildPlacements += count($placement['super_wild_placements']);
            }
        }
        
        return $originalCovered + $wildPlacements + $superWildPlacements;
    }

    /**
     * Calculate potential Slingos across all draw configurations
     */
    private function calculatePotentialSlingos() {
        $totalPotential = 0;
        $drawRows = $this->drawConfig->getRows();
        
        foreach ($drawRows as $row) {
            $potential = $this->countPotentialSlingosForRow($row);
            $totalPotential += $potential;
        }
        
        return $totalPotential;
    }
    
    /**
     * Calculate potential Slingos with actual wild placements
     */
    private function calculatePotentialSlingosWithPlacements() {
        $totalPotential = 0;
        $optimalPlacements = $this->drawConfig->getOptimalWildPlacements($this->board);
        
        foreach ($optimalPlacements as $placement) {
            if (isset($placement['wild_placements']) && isset($placement['super_wild_placements'])) {
                $completedSlingos = $this->countCompletedSlingosWithPlacements(
                    $placement['wild_placements'], 
                    $placement['super_wild_placements']
                );
                $totalPotential += $completedSlingos;
            }
        }
        
        return $totalPotential;
    }
    
    /**
     * Count completed Slingos with specific placements
     */
    private function countCompletedSlingosWithPlacements($wildPlacements, $superWildPlacements) {
        $completedSlingos = 0;
        
        // Check horizontal Slingos
        for ($row = 0; $row < 5; $row++) {
            if ($this->wouldCompleteRowWithPlacements($row, $wildPlacements, $superWildPlacements)) {
                $completedSlingos++;
            }
        }
        
        // Check vertical Slingos
        for ($col = 0; $col < 5; $col++) {
            if ($this->wouldCompleteColumnWithPlacements($col, $wildPlacements, $superWildPlacements)) {
                $completedSlingos++;
            }
        }
        
        // Check diagonal Slingos
        if ($this->wouldCompleteMainDiagonalWithPlacements($wildPlacements, $superWildPlacements)) {
            $completedSlingos++;
        }
        if ($this->wouldCompleteAntiDiagonalWithPlacements($wildPlacements, $superWildPlacements)) {
            $completedSlingos++;
        }
        
        return $completedSlingos;
    }
    
    /**
     * Check if a row would be completed with given placements
     */
    private function wouldCompleteRowWithPlacements($row, $wildPlacements, $superWildPlacements) {
        $coveredCount = 0;
        $hasWild = false;
        
        for ($col = 0; $col < 5; $col++) {
            if ($this->board->isCovered($row, $col)) {
                $coveredCount++;
            } else {
                // Check if there's a wild or super wild for this position
                foreach ($wildPlacements as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $hasWild = true;
                        break;
                    }
                }
                foreach ($superWildPlacements as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $hasWild = true;
                        break;
                    }
                }
            }
        }
        
        return $coveredCount + ($hasWild ? 1 : 0) >= 5;
    }
    
    /**
     * Check if a column would be completed with given placements
     */
    private function wouldCompleteColumnWithPlacements($col, $wildPlacements, $superWildPlacements) {
        $coveredCount = 0;
        $hasWild = false;
        
        for ($row = 0; $row < 5; $row++) {
            if ($this->board->isCovered($row, $col)) {
                $coveredCount++;
            } else {
                // Check if there's a wild or super wild for this position
                foreach ($wildPlacements as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $hasWild = true;
                        break;
                    }
                }
                foreach ($superWildPlacements as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $hasWild = true;
                        break;
                    }
                }
            }
        }
        
        return $coveredCount + ($hasWild ? 1 : 0) >= 5;
    }
    
    /**
     * Check if main diagonal would be completed with given placements
     */
    private function wouldCompleteMainDiagonalWithPlacements($wildPlacements, $superWildPlacements) {
        $coveredCount = 0;
        $hasWild = false;
        
        for ($i = 0; $i < 5; $i++) {
            if ($this->board->isCovered($i, $i)) {
                $coveredCount++;
            } else {
                // Check if there's a wild or super wild for this position
                foreach ($wildPlacements as $placement) {
                    if ($placement['row'] - 1 === $i && $placement['column'] - 1 === $i) {
                        $hasWild = true;
                        break;
                    }
                }
                foreach ($superWildPlacements as $placement) {
                    if ($placement['row'] - 1 === $i && $placement['column'] - 1 === $i) {
                        $hasWild = true;
                        break;
                    }
                }
            }
        }
        
        return $coveredCount + ($hasWild ? 1 : 0) >= 5;
    }
    
    /**
     * Check if anti-diagonal would be completed with given placements
     */
    private function wouldCompleteAntiDiagonalWithPlacements($wildPlacements, $superWildPlacements) {
        $coveredCount = 0;
        $hasWild = false;
        
        for ($i = 0; $i < 5; $i++) {
            $row = $i;
            $col = 4 - $i;
            if ($this->board->isCovered($row, $col)) {
                $coveredCount++;
            } else {
                // Check if there's a wild or super wild for this position
                foreach ($wildPlacements as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $hasWild = true;
                        break;
                    }
                }
                foreach ($superWildPlacements as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $hasWild = true;
                        break;
                    }
                }
            }
        }
        
        return $coveredCount + ($hasWild ? 1 : 0) >= 5;
    }

    /**
     * Count potential Slingos for a specific draw row
     */
    private function countPotentialSlingosForRow($row) {
        $count = 0;
        
        // Check rows
        for ($boardRow = 0; $boardRow < 5; $boardRow++) {
            if ($this->canCompleteRow($boardRow, $row)) {
                $count++;
            }
        }
        
        // Check columns
        for ($col = 0; $col < 5; $col++) {
            if ($this->canCompleteColumn($col, $row)) {
                $count++;
            }
        }
        
        // Check diagonals
        if ($this->canCompleteDiagonal(true, $row)) {
            $count++;
        }
        if ($this->canCompleteDiagonal(false, $row)) {
            $count++;
        }
        
        return $count;
    }

    /**
     * Check if a board row can be completed with given draw row
     */
    private function canCompleteRow($boardRow, $drawRow) {
        $neededWilds = 0;
        for ($col = 0; $col < 5; $col++) {
            if (!$this->board->isCovered($boardRow, $col)) {
                if ($drawRow[$col] === 'none') {
                    return false;
                }
                $neededWilds++;
            }
        }
        return $neededWilds > 0;
    }

    /**
     * Check if a board column can be completed with given draw row
     */
    private function canCompleteColumn($boardCol, $drawRow) {
        $neededWilds = 0;
        for ($row = 0; $row < 5; $row++) {
            if (!$this->board->isCovered($row, $boardCol)) {
                if ($drawRow[$boardCol] === 'none') {
                    return false;
                }
                $neededWilds++;
            }
        }
        return $neededWilds > 0;
    }

    /**
     * Check if a diagonal can be completed with given draw row
     */
    private function canCompleteDiagonal($mainDiagonal, $drawRow) {
        $neededWilds = 0;
        for ($i = 0; $i < 5; $i++) {
            $row = $i;
            $col = $mainDiagonal ? $i : (4 - $i);
            if (!$this->board->isCovered($row, $col)) {
                if ($drawRow[$col] === 'none') {
                    return false;
                }
                $neededWilds++;
            }
        }
        return $neededWilds > 0;
    }

    /**
     * Calculate probability breakdown for different scenarios
     */
    private function calculateProbabilityBreakdown() {
        $breakdown = [
            'slingo_completion' => 0,
            'partial_completion' => 0,
            'setup_moves' => 0
        ];
        
        $drawRows = $this->drawConfig->getRows();
        $totalScenarios = count($drawRows);
        
        if ($totalScenarios === 0) {
            return $breakdown;
        }
        
        foreach ($drawRows as $row) {
            $potentialSlingos = $this->countPotentialSlingosForRow($row);
            
            if ($potentialSlingos >= 2) {
                $breakdown['slingo_completion']++;
            } elseif ($potentialSlingos === 1) {
                $breakdown['partial_completion']++;
            } else {
                $breakdown['setup_moves']++;
            }
        }
        
        // Convert to percentages
        foreach ($breakdown as $key => $value) {
            $breakdown[$key] = round(($value / $totalScenarios) * 100, 1);
        }
        
        return $breakdown;
    }
}
