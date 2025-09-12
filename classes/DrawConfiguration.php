<?php

class DrawConfiguration {
    private $rows = [];

    public function __construct($draws = []) {
        $this->rows = $draws;
    }

    /**
     * Add a draw row
     */
    public function addRow($positions) {
        if (count($positions) === 5) {
            $this->rows[] = $positions;
        }
    }

    /**
     * Validate configuration
     */
    public function validateConfiguration() {
        if (empty($this->rows)) {
            return ['valid' => false, 'message' => 'At least one draw row is required'];
        }

        if (count($this->rows) > 3) {
            return ['valid' => false, 'message' => 'Maximum 3 draw rows allowed'];
        }

        foreach ($this->rows as $index => $row) {
            if (count($row) !== 5) {
                return ['valid' => false, 'message' => "Draw row " . ($index + 1) . " must have exactly 5 positions"];
            }

            foreach ($row as $position) {
                if (!in_array($position, ['none', 'wild', 'super_wild'])) {
                    return ['valid' => false, 'message' => "Invalid position value: $position"];
                }
            }
        }

        return ['valid' => true];
    }

    /**
     * Get optimal wild placements for a given board using depth-first search
     */
    public function getOptimalWildPlacements($board) {
        $recommendations = [];
        
        foreach ($this->rows as $rowIndex => $row) {
            $wildPositions = [];
            $superWildPositions = [];
            
            // Identify wild and super wild positions
            foreach ($row as $colIndex => $position) {
                if ($position === 'wild') {
                    $wildPositions[] = $colIndex;
                } elseif ($position === 'super_wild') {
                    $superWildPositions[] = $colIndex;
                }
            }
            
            // Calculate optimal placements using depth-first search
            $optimalPlacement = $this->calculateOptimalPlacementDFS($board, $wildPositions, $superWildPositions);
            
            if ($optimalPlacement) {
                $recommendations[] = [
                    'row' => $rowIndex + 1,
                    'positions' => $optimalPlacement['positions'],
                    'expected_score' => $optimalPlacement['expected_score'],
                    'reasoning' => $optimalPlacement['reasoning'],
                    'wild_placements' => $optimalPlacement['wild_placements'],
                    'super_wild_placements' => $optimalPlacement['super_wild_placements']
                ];
            }
        }
        
        return $recommendations;
    }

    /**
     * Calculate optimal placement using depth-first search with priority system
     */
    private function calculateOptimalPlacementDFS($board, $wildPositions, $superWildPositions) {
        if (empty($wildPositions) && empty($superWildPositions)) {
            return null;
        }
        
        $bestResult = null;
        $bestScore = -1;
        
        // Generate all possible placement combinations using DFS
        $allCombinations = $this->generateAllPlacementCombinations($board, $wildPositions, $superWildPositions);
        
        // Evaluate each combination using our priority system
        foreach ($allCombinations as $combination) {
            $score = $this->evaluatePlacementWithPriority($board, $combination);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestResult = $combination;
            }
        }
        
        if ($bestResult) {
            return [
                'positions' => $this->convertPlacementsToPositions($wildPositions, $superWildPositions),
                'expected_score' => round($bestScore, 1),
                'reasoning' => $this->generateReasoningWithPlacements($board, $bestResult['wild_placements'], $bestResult['super_wild_placements']),
                'wild_placements' => $bestResult['wild_placements'],
                'super_wild_placements' => $bestResult['super_wild_placements']
            ];
        }
        
        return null;
    }
    
    /**
     * Generate all possible placement combinations using depth-first search
     */
    private function generateAllPlacementCombinations($board, $wildPositions, $superWildPositions) {
        $combinations = [];
        
        // Get all uncovered positions
        $uncoveredPositions = $this->getUncoveredPositions($board);
        
        // For wilds: they must be placed in their specific column
        $wildOptions = [];
        foreach ($wildPositions as $wildCol) {
            $wildOptions[$wildCol] = [];
            foreach ($uncoveredPositions as $pos) {
                if ($pos['col'] === $wildCol) {
                    $wildOptions[$wildCol][] = $pos;
                }
            }
        }
        
        // For super wilds: they can be placed anywhere
        $superWildOptions = $uncoveredPositions;
        
        // Use DFS to generate all combinations
        $this->dfsGenerateCombinations($wildOptions, $superWildOptions, [], [], 0, $wildPositions, $superWildPositions, $combinations);
        
        return $combinations;
    }
    
    /**
     * Depth-first search to generate placement combinations
     */
    private function dfsGenerateCombinations($wildOptions, $superWildOptions, $currentWildPlacements, $currentSuperWildPlacements, $wildIndex, $wildCols, $superWildCols, &$combinations) {
        // If we've placed all wilds, start placing super wilds
        if ($wildIndex >= count($wildCols)) {
            $this->dfsGenerateSuperWildCombinations($superWildOptions, $currentWildPlacements, $currentSuperWildPlacements, 0, $superWildCols, $combinations);
            return;
        }
        
        $wildCol = $wildCols[$wildIndex];
        
        // Check if there are any valid positions for this wild
        if (!isset($wildOptions[$wildCol]) || empty($wildOptions[$wildCol])) {
            // No valid positions for this wild, skip to next
            $this->dfsGenerateCombinations($wildOptions, $superWildOptions, $currentWildPlacements, $currentSuperWildPlacements, $wildIndex + 1, $wildCols, $superWildCols, $combinations);
            return;
        }
        
        // Try placing the wild in each available position in its column
        foreach ($wildOptions[$wildCol] as $position) {
            // Check if this position is already taken
            if (!$this->isPositionTaken($position, $currentWildPlacements, $currentSuperWildPlacements)) {
                $newWildPlacements = $currentWildPlacements;
                $newWildPlacements[] = ['row' => $position['row'] + 1, 'column' => $position['col'] + 1];
                
                // Recursively place next wild
                $this->dfsGenerateCombinations($wildOptions, $superWildOptions, $newWildPlacements, $currentSuperWildPlacements, $wildIndex + 1, $wildCols, $superWildCols, $combinations);
            }
        }
    }
    
    /**
     * DFS for super wild combinations
     */
    private function dfsGenerateSuperWildCombinations($superWildOptions, $wildPlacements, $currentSuperWildPlacements, $superWildIndex, $superWildCols, &$combinations) {
        // If we've placed all super wilds, add this combination
        if ($superWildIndex >= count($superWildCols)) {
            $combinations[] = [
                'wild_placements' => $wildPlacements,
                'super_wild_placements' => $currentSuperWildPlacements
            ];
            return;
        }
        
        // Check if there are any valid positions for super wilds
        if (empty($superWildOptions)) {
            // No valid positions, just add current combination
            $combinations[] = [
                'wild_placements' => $wildPlacements,
                'super_wild_placements' => $currentSuperWildPlacements
            ];
            return;
        }
        
        // Try placing the super wild in each available position
        foreach ($superWildOptions as $position) {
            // Check if this position is already taken
            if (!$this->isPositionTaken($position, $wildPlacements, $currentSuperWildPlacements)) {
                $newSuperWildPlacements = $currentSuperWildPlacements;
                $newSuperWildPlacements[] = ['row' => $position['row'] + 1, 'column' => $position['col'] + 1];
                
                // Recursively place next super wild
                $this->dfsGenerateSuperWildCombinations($superWildOptions, $wildPlacements, $newSuperWildPlacements, $superWildIndex + 1, $superWildCols, $combinations);
            }
        }
    }
    
    /**
     * Get all uncovered positions on the board
     */
    private function getUncoveredPositions($board) {
        $positions = [];
        for ($row = 0; $row < 5; $row++) {
            for ($col = 0; $col < 5; $col++) {
                if (!$board->isCovered($row, $col)) {
                    $positions[] = ['row' => $row, 'col' => $col];
                }
            }
        }
        return $positions;
    }
    
    /**
     * Check if a position is already taken by existing placements
     */
    private function isPositionTaken($position, $wildPlacements, $superWildPlacements) {
        foreach ($wildPlacements as $placement) {
            if ($placement['row'] - 1 === $position['row'] && $placement['column'] - 1 === $position['col']) {
                return true;
            }
        }
        foreach ($superWildPlacements as $placement) {
            if ($placement['row'] - 1 === $position['row'] && $placement['column'] - 1 === $position['col']) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Evaluate placement combination using priority system
     */
    private function evaluatePlacementWithPriority($board, $combination) {
        $wildPlacements = $combination['wild_placements'];
        $superWildPlacements = $combination['super_wild_placements'];
        
        // Priority 1: Count completed Slingos
        $completedSlingos = $this->countCompletedSlingos($board, $wildPlacements, $superWildPlacements);
        if ($completedSlingos > 0) {
            $score = $completedSlingos * 10000; // Very high base score for completed Slingos
            
            // Priority 2: Among completed Slingos, prioritize diagonals
            $diagonalSlingos = $this->countCompletedDiagonalSlingos($board, $wildPlacements, $superWildPlacements);
            $score += $diagonalSlingos * 1000; // Extra bonus for diagonal completions
            
            return $score;
        }
        
        // Priority 3: If no Slingos can be completed, find highest number of places for potential Slingos
        $potentialSlingoData = $this->calculatePotentialSlingoData($board, $wildPlacements, $superWildPlacements);
        
        // Prioritize by maximum places filled in any single Slingo
        $maxPlacesInSlingo = $potentialSlingoData['max_places'];
        
        // Count total potential Slingos with >1 place filled
        $multiPlaceSlingos = $potentialSlingoData['multi_place_slingos'];
        
        // Base score on maximum places in any single Slingo
        $score = $maxPlacesInSlingo * 1000;
        
        // Add bonus for multiple potential Slingos with >1 place
        $score += $multiPlaceSlingos * 100;
        
        // Small bonus for total filled positions (tie-breaker)
        $score += (count($wildPlacements) + count($superWildPlacements)) * 10;
        
        return $score;
    }
    
    /**
     * Count completed diagonal Slingos specifically
     */
    private function countCompletedDiagonalSlingos($board, $wildPlacements, $superWildPlacements) {
        $count = 0;
        
        if ($this->wouldCompleteMainDiagonalWithPlacements($board, $wildPlacements, $superWildPlacements)) {
            $count++;
        }
        if ($this->wouldCompleteAntiDiagonalWithPlacements($board, $wildPlacements, $superWildPlacements)) {
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Calculate potential Slingo data for priority evaluation
     */
    private function calculatePotentialSlingoData($board, $wildPlacements, $superWildPlacements) {
        $maxPlaces = 0;
        $multiPlaceSlingos = 0;
        
        // Check all possible Slingos (rows, columns, diagonals)
        $slingoTypes = [
            'rows' => range(0, 4),
            'cols' => range(0, 4),
            'diagonals' => ['main', 'anti']
        ];
        
        foreach ($slingoTypes as $type => $indices) {
            foreach ($indices as $index) {
                $placesInThisSlingo = 0;
                
                if ($type === 'rows') {
                    $placesInThisSlingo = $this->countPlacesInRow($board, $index, $wildPlacements, $superWildPlacements);
                } elseif ($type === 'cols') {
                    $placesInThisSlingo = $this->countPlacesInColumn($board, $index, $wildPlacements, $superWildPlacements);
                } elseif ($type === 'diagonals') {
                    if ($index === 'main') {
                        $placesInThisSlingo = $this->countPlacesInMainDiagonal($board, $wildPlacements, $superWildPlacements);
                    } else {
                        $placesInThisSlingo = $this->countPlacesInAntiDiagonal($board, $wildPlacements, $superWildPlacements);
                    }
                }
                
                $maxPlaces = max($maxPlaces, $placesInThisSlingo);
                if ($placesInThisSlingo > 1) {
                    $multiPlaceSlingos++;
                }
            }
        }
        
        return [
            'max_places' => $maxPlaces,
            'multi_place_slingos' => $multiPlaceSlingos
        ];
    }
    
    /**
     * Count total places (covered + wild placements) in a row
     */
    private function countPlacesInRow($board, $row, $wildPlacements, $superWildPlacements) {
        $count = 0;
        
        for ($col = 0; $col < 5; $col++) {
            if ($board->isCovered($row, $col)) {
                $count++;
            } else {
                // Check if there's a wild placement here
                foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $count++;
                        break;
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Count total places in a column
     */
    private function countPlacesInColumn($board, $col, $wildPlacements, $superWildPlacements) {
        $count = 0;
        
        for ($row = 0; $row < 5; $row++) {
            if ($board->isCovered($row, $col)) {
                $count++;
            } else {
                // Check if there's a wild placement here
                foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $count++;
                        break;
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Count total places in main diagonal
     */
    private function countPlacesInMainDiagonal($board, $wildPlacements, $superWildPlacements) {
        $count = 0;
        
        for ($i = 0; $i < 5; $i++) {
            if ($board->isCovered($i, $i)) {
                $count++;
            } else {
                // Check if there's a wild placement here
                foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
                    if ($placement['row'] - 1 === $i && $placement['column'] - 1 === $i) {
                        $count++;
                        break;
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Count total places in anti-diagonal
     */
    private function countPlacesInAntiDiagonal($board, $wildPlacements, $superWildPlacements) {
        $count = 0;
        
        for ($i = 0; $i < 5; $i++) {
            $row = $i;
            $col = 4 - $i;
            if ($board->isCovered($row, $col)) {
                $count++;
            } else {
                // Check if there's a wild placement here
                foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $count++;
                        break;
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Convert wild positions to position array format
     */
    private function convertPlacementsToPositions($wildPositions, $superWildPositions) {
        $positions = ['none', 'none', 'none', 'none', 'none'];
        
        foreach ($wildPositions as $col) {
            $positions[$col] = 'wild';
        }
        foreach ($superWildPositions as $col) {
            $positions[$col] = 'super_wild';
        }
        
        return $positions;
    }

    /**
     * Generate all possible combinations of wild placements
     */
    private function generateWildCombinations($wildPositions, $superWildPositions) {
        $combinations = [];
        
        // Start with base configuration (all 'none')
        $base = ['none', 'none', 'none', 'none', 'none'];
        
        // Add wild combinations
        foreach ($wildPositions as $wildCol) {
            $combination = $base;
            $combination[$wildCol] = 'wild';
            $combinations[] = $combination;
        }
        
        // Add super wild combinations
        foreach ($superWildPositions as $superWildCol) {
            $combination = $base;
            $combination[$superWildCol] = 'super_wild';
            $combinations[] = $combination;
        }
        
        // Add combined wild + super wild combinations
        foreach ($wildPositions as $wildCol) {
            foreach ($superWildPositions as $superWildCol) {
                $combination = $base;
                $combination[$wildCol] = 'wild';
                $combination[$superWildCol] = 'super_wild';
                $combinations[] = $combination;
            }
        }
        
        return $combinations;
    }

    /**
     * Calculate expected score for a given placement
     */
    private function calculateExpectedScore($board, $placement) {
        $score = 0;
        
        // Check for potential Slingo completions
        $potentialSlingos = $this->countPotentialSlingos($board, $placement);
        $score += $potentialSlingos * 25; // 25 points per Slingo
        
        // Check for setup moves (positions that help future Slingos)
        $setupValue = $this->calculateSetupValue($board, $placement);
        $score += $setupValue;
        
        // Bonus for super wilds (they can complete any line)
        $superWildCount = array_count_values($placement)['super_wild'] ?? 0;
        $score += $superWildCount * 10;
        
        return $score;
    }

    /**
     * Count potential Slingo completions
     */
    private function countPotentialSlingos($board, $placement) {
        $count = 0;
        
        // Check rows
        for ($row = 0; $row < 5; $row++) {
            if ($this->canCompleteRow($board, $row, $placement)) {
                $count++;
            }
        }
        
        // Check columns
        for ($col = 0; $col < 5; $col++) {
            if ($this->canCompleteColumn($board, $col, $placement)) {
                $count++;
            }
        }
        
        // Check diagonals
        if ($this->canCompleteDiagonal($board, true, $placement)) {
            $count++;
        }
        if ($this->canCompleteDiagonal($board, false, $placement)) {
            $count++;
        }
        
        return $count;
    }

    /**
     * Check if a row can be completed with given placement
     */
    private function canCompleteRow($board, $row, $placement) {
        $neededWilds = 0;
        for ($col = 0; $col < 5; $col++) {
            if (!$board->isCovered($row, $col)) {
                if ($placement[$col] === 'none') {
                    return false;
                }
                $neededWilds++;
            }
        }
        return $neededWilds > 0;
    }

    /**
     * Check if a column can be completed with given placement
     */
    private function canCompleteColumn($board, $col, $placement) {
        $neededWilds = 0;
        for ($row = 0; $row < 5; $row++) {
            if (!$board->isCovered($row, $col)) {
                if ($placement[$col] === 'none') {
                    return false;
                }
                $neededWilds++;
            }
        }
        return $neededWilds > 0;
    }

    /**
     * Check if a diagonal can be completed with given placement
     */
    private function canCompleteDiagonal($board, $mainDiagonal, $placement) {
        $neededWilds = 0;
        for ($i = 0; $i < 5; $i++) {
            $row = $i;
            $col = $mainDiagonal ? $i : (4 - $i);
            if (!$board->isCovered($row, $col)) {
                if ($placement[$col] === 'none') {
                    return false;
                }
                $neededWilds++;
            }
        }
        return $neededWilds > 0;
    }

    /**
     * Calculate setup value for future moves
     */
    private function calculateSetupValue($board, $placement) {
        $value = 0;
        
        // Count how many cells this placement would cover
        $coveredCells = 0;
        for ($col = 0; $col < 5; $col++) {
            if ($placement[$col] !== 'none') {
                // Count uncovered cells in this column
                for ($row = 0; $row < 5; $row++) {
                    if (!$board->isCovered($row, $col)) {
                        $coveredCells++;
                    }
                }
            }
        }
        
        $value += $coveredCells * 2; // 2 points per covered cell
        
        return $value;
    }

    /**
     * Generate reasoning for a placement
     */
    private function generateReasoning($board, $placement) {
        $reasons = [];
        
        $potentialSlingos = $this->countPotentialSlingos($board, $placement);
        if ($potentialSlingos > 0) {
            $reasons[] = "Completes {$potentialSlingos} Slingo" . ($potentialSlingos > 1 ? 's' : '');
        }
        
        $wildCount = array_count_values($placement)['wild'] ?? 0;
        $superWildCount = array_count_values($placement)['super_wild'] ?? 0;
        
        if ($wildCount > 0) {
            $reasons[] = "Uses {$wildCount} wild card" . ($wildCount > 1 ? 's' : '');
        }
        
        if ($superWildCount > 0) {
            $reasons[] = "Uses {$superWildCount} super wild card" . ($superWildCount > 1 ? 's' : '');
        }
        
        if (empty($reasons)) {
            $reasons[] = "Provides setup for future Slingo opportunities";
        }
        
        return implode(', ', $reasons);
    }

    /**
     * Find the best row for a wild card in a specific column
     */
    private function findBestRowForWild($board, $colIndex) {
        $bestRow = 0;
        $bestScore = 0;
        
        for ($row = 0; $row < 5; $row++) {
            if (!$board->isCovered($row, $colIndex)) {
                // Calculate score for placing wild in this row
                $score = $this->calculateWildRowScore($board, $row, $colIndex, 'wild');
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestRow = $row;
                }
            }
        }
        
        return $bestRow;
    }
    
    /**
     * Find the best row for a wild card in a specific column, avoiding super wild conflicts
     */
    private function findBestRowForWildAvoidingConflicts($board, $colIndex, $superWildPlacements) {
        $bestRow = 0;
        $bestScore = 0;
        
        for ($row = 0; $row < 5; $row++) {
            if (!$board->isCovered($row, $colIndex)) {
                // Check if this position conflicts with any super wild
                $hasConflict = false;
                foreach ($superWildPlacements as $superWild) {
                    if ($superWild['row'] - 1 === $row && $superWild['column'] - 1 === $colIndex) {
                        $hasConflict = true;
                        break;
                    }
                }
                
                if (!$hasConflict) {
                    // Calculate score for placing wild in this row
                    $score = $this->calculateWildRowScore($board, $row, $colIndex, 'wild');
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestRow = $row;
                    }
                }
            }
        }
        
        return $bestRow;
    }
    
    /**
     * Find the best row for a super wild card in a specific column
     */
    private function findBestRowForSuperWild($board, $colIndex) {
        $bestRow = 0;
        $bestScore = 0;
        
        for ($row = 0; $row < 5; $row++) {
            if (!$board->isCovered($row, $colIndex)) {
                // Calculate score for placing super wild in this row
                $score = $this->calculateWildRowScore($board, $row, $colIndex, 'super_wild');
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestRow = $row;
                }
            }
        }
        
        return $bestRow;
    }
    
    /**
     * Find the best position for a super wild card anywhere on the board
     */
    private function findBestPositionForSuperWild($board) {
        $bestRow = 0;
        $bestCol = 0;
        $bestScore = 0;
        
        for ($row = 0; $row < 5; $row++) {
            for ($col = 0; $col < 5; $col++) {
                if (!$board->isCovered($row, $col)) {
                    // Calculate score for placing super wild in this position
                    $score = $this->calculateWildRowScore($board, $row, $col, 'super_wild');
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestRow = $row;
                        $bestCol = $col;
                    }
                }
            }
        }
        
        return ['row' => $bestRow, 'col' => $bestCol];
    }
    
    /**
     * Find the best position for a super wild card, considering existing wild placements
     */
    private function findBestPositionForSuperWildWithWilds($board, $wildPlacements) {
        $bestRow = 0;
        $bestCol = 0;
        $bestScore = 0;
        
        for ($row = 0; $row < 5; $row++) {
            for ($col = 0; $col < 5; $col++) {
                if (!$board->isCovered($row, $col)) {
                    // Check if this position conflicts with any wild
                    $hasConflict = false;
                    foreach ($wildPlacements as $wild) {
                        if ($wild['row'] - 1 === $row && $wild['column'] - 1 === $col) {
                            $hasConflict = true;
                            break;
                        }
                    }
                    
                    if (!$hasConflict) {
                        // Calculate score for placing super wild in this position
                        $score = $this->calculateWildRowScore($board, $row, $col, 'super_wild');
                        if ($score > $bestScore) {
                            $bestScore = $score;
                            $bestRow = $row;
                            $bestCol = $col;
                        }
                    }
                }
            }
        }
        
        return ['row' => $bestRow, 'col' => $bestCol];
    }
    
    /**
     * Calculate score for placing a wild in a specific row/column
     */
    private function calculateWildRowScore($board, $row, $col, $wildType) {
        $score = 0;
        $completedSlingos = 0;
        $isDiagonal = false;
        
        // Priority 1: Completed Slingos (highest priority)
        // Check if this completes a horizontal Slingo
        if ($this->wouldCompleteHorizontalSlingo($board, $row)) {
            $completedSlingos++;
            $score += 1000; // Very high priority for completing Slingos
        }
        
        // Check if this completes a vertical Slingo
        if ($this->wouldCompleteVerticalSlingo($board, $col)) {
            $completedSlingos++;
            $score += 1000; // Very high priority for completing Slingos
        }
        
        // Check if this completes a diagonal Slingo
        if ($this->wouldCompleteDiagonalSlingo($board, $row, $col)) {
            $completedSlingos++;
            $score += 1200; // Highest priority for diagonal completion
            $isDiagonal = true;
        }
        
        // If we can complete Slingos, prioritize diagonal over others
        if ($completedSlingos > 0) {
            if ($isDiagonal) {
                $score += 500; // Extra bonus for diagonal completion
            }
        } else {
            // Priority 2: If no Slingos can be completed, prioritize setup moves
            $score += $this->calculateSetupMoveScore($board, $row, $col);
        }
        
        // Small bonus for super wilds
        if ($wildType === 'super_wild') {
            $score += 5;
        } else {
            $score += 2;
        }
        
        return $score;
    }
    
    /**
     * Calculate setup move score when no Slingos can be completed
     */
    private function calculateSetupMoveScore($board, $row, $col) {
        $score = 0;
        
        // Count how many cells are covered in the row
        $rowCoverage = $this->countRowCoverage($board, $row);
        $score += $rowCoverage * 10;
        
        // Count how many cells are covered in the column
        $colCoverage = $this->countColumnCoverage($board, $col);
        $score += $colCoverage * 10;
        
        // Check if this is on a diagonal and count diagonal coverage
        if ($row === $col) {
            $diagCoverage = $this->countMainDiagonalCoverage($board);
            $score += $diagCoverage * 15; // Diagonal setup gets higher priority
        }
        
        if ($row + $col === 4) {
            $diagCoverage = $this->countAntiDiagonalCoverage($board);
            $score += $diagCoverage * 15; // Diagonal setup gets higher priority
        }
        
        return $score;
    }
    
    /**
     * Check if placing a wild would complete a horizontal Slingo
     */
    private function wouldCompleteHorizontalSlingo($board, $row) {
        $coveredCount = 0;
        for ($col = 0; $col < 5; $col++) {
            if ($board->isCovered($row, $col)) {
                $coveredCount++;
            }
        }
        return $coveredCount >= 4; // 4 covered + 1 wild = complete Slingo
    }
    
    /**
     * Count how many cells are covered in a row
     */
    private function countRowCoverage($board, $row) {
        $count = 0;
        for ($col = 0; $col < 5; $col++) {
            if ($board->isCovered($row, $col)) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Count how many cells are covered in a column
     */
    private function countColumnCoverage($board, $col) {
        $count = 0;
        for ($row = 0; $row < 5; $row++) {
            if ($board->isCovered($row, $col)) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Count how many cells are covered in the main diagonal
     */
    private function countMainDiagonalCoverage($board) {
        $count = 0;
        for ($i = 0; $i < 5; $i++) {
            if ($board->isCovered($i, $i)) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Count how many cells are covered in the anti-diagonal
     */
    private function countAntiDiagonalCoverage($board) {
        $count = 0;
        for ($i = 0; $i < 5; $i++) {
            if ($board->isCovered($i, 4 - $i)) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * Check if placing a wild would complete a vertical Slingo
     */
    private function wouldCompleteVerticalSlingo($board, $col) {
        $coveredCount = 0;
        for ($row = 0; $row < 5; $row++) {
            if ($board->isCovered($row, $col)) {
                $coveredCount++;
            }
        }
        return $coveredCount >= 4; // 4 covered + 1 wild = complete Slingo
    }
    
    /**
     * Check if placing a wild would complete a diagonal Slingo
     */
    private function wouldCompleteDiagonalSlingo($board, $row, $col) {
        // Check main diagonal
        if ($row === $col) {
            $coveredCount = 0;
            for ($i = 0; $i < 5; $i++) {
                if ($board->isCovered($i, $i)) {
                    $coveredCount++;
                }
            }
            // If we have 4 covered cells and we're placing at the 5th position, it completes the diagonal
            // OR if we have 3 covered cells and we're placing at the 4th position, it completes the diagonal
            if ($coveredCount >= 4) return true;
        }
        
        // Check anti-diagonal
        if ($row + $col === 4) {
            $coveredCount = 0;
            for ($i = 0; $i < 5; $i++) {
                if ($board->isCovered($i, 4 - $i)) {
                    $coveredCount++;
                }
            }
            // If we have 4 covered cells and we're placing at the 5th position, it completes the diagonal
            // OR if we have 3 covered cells and we're placing at the 4th position, it completes the diagonal
            if ($coveredCount >= 4) return true;
        }
        
        return false;
    }
    
    /**
     * Calculate score based on actual wild placements
     */
    private function calculateScoreWithPlacements($board, $wildPlacements, $superWildPlacements) {
        $score = 0;
        
        // Count Slingos that would be completed
        $completedSlingos = $this->countCompletedSlingos($board, $wildPlacements, $superWildPlacements);
        $score += $completedSlingos * 25;
        
        // Add wild card bonuses
        $score += count($wildPlacements) * 10;
        $score += count($superWildPlacements) * 15;
        
        // Add setup bonuses
        $score += $this->calculateSetupBonus($board, $wildPlacements, $superWildPlacements);
        
        return $score;
    }
    
    /**
     * Count Slingos that would be completed with given placements
     */
    private function countCompletedSlingos($board, $wildPlacements, $superWildPlacements) {
        $completedSlingos = 0;
        
        // Check horizontal Slingos
        for ($row = 0; $row < 5; $row++) {
            if ($this->wouldCompleteRowWithPlacements($board, $row, $wildPlacements, $superWildPlacements)) {
                $completedSlingos++;
            }
        }
        
        // Check vertical Slingos
        for ($col = 0; $col < 5; $col++) {
            if ($this->wouldCompleteColumnWithPlacements($board, $col, $wildPlacements, $superWildPlacements)) {
                $completedSlingos++;
            }
        }
        
        // Check diagonal Slingos
        if ($this->wouldCompleteMainDiagonalWithPlacements($board, $wildPlacements, $superWildPlacements)) {
            $completedSlingos++;
        }
        if ($this->wouldCompleteAntiDiagonalWithPlacements($board, $wildPlacements, $superWildPlacements)) {
            $completedSlingos++;
        }
        
        return $completedSlingos;
    }
    
    /**
     * Check if a row would be completed with given placements
     */
    private function wouldCompleteRowWithPlacements($board, $row, $wildPlacements, $superWildPlacements) {
        $coveredCount = 0;
        $hasWild = false;
        
        for ($col = 0; $col < 5; $col++) {
            if ($board->isCovered($row, $col)) {
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
    private function wouldCompleteColumnWithPlacements($board, $col, $wildPlacements, $superWildPlacements) {
        $coveredCount = 0;
        $hasWild = false;
        
        for ($row = 0; $row < 5; $row++) {
            if ($board->isCovered($row, $col)) {
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
    private function wouldCompleteMainDiagonalWithPlacements($board, $wildPlacements, $superWildPlacements) {
        $coveredCount = 0;
        $wildCount = 0;
        
        for ($i = 0; $i < 5; $i++) {
            if ($board->isCovered($i, $i)) {
                $coveredCount++;
            } else {
                // Check if there's a wild or super wild for this position
                foreach ($wildPlacements as $placement) {
                    if ($placement['row'] - 1 === $i && $placement['column'] - 1 === $i) {
                        $wildCount++;
                        break;
                    }
                }
                foreach ($superWildPlacements as $placement) {
                    if ($placement['row'] - 1 === $i && $placement['column'] - 1 === $i) {
                        $wildCount++;
                        break;
                    }
                }
            }
        }
        
        return $coveredCount + $wildCount >= 5;
    }
    
    /**
     * Check if anti-diagonal would be completed with given placements
     */
    private function wouldCompleteAntiDiagonalWithPlacements($board, $wildPlacements, $superWildPlacements) {
        $coveredCount = 0;
        $wildCount = 0;
        
        for ($i = 0; $i < 5; $i++) {
            $row = $i;
            $col = 4 - $i;
            if ($board->isCovered($row, $col)) {
                $coveredCount++;
            } else {
                // Check if there's a wild or super wild for this position
                foreach ($wildPlacements as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $wildCount++;
                        break;
                    }
                }
                foreach ($superWildPlacements as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $wildCount++;
                        break;
                    }
                }
            }
        }
        
        return $coveredCount + $wildCount >= 5;
    }
    
    /**
     * Calculate setup bonus for placements
     */
    private function calculateSetupBonus($board, $wildPlacements, $superWildPlacements) {
        $bonus = 0;
        
        // Count how many additional cells would be covered
        $additionalCovered = count($wildPlacements) + count($superWildPlacements);
        $bonus += $additionalCovered * 2;
        
        return $bonus;
    }
    
    /**
     * Generate reasoning based on actual placements
     */
    private function generateReasoningWithPlacements($board, $wildPlacements, $superWildPlacements) {
        $reasons = [];
        
        $completedSlingos = $this->countCompletedSlingos($board, $wildPlacements, $superWildPlacements);
        if ($completedSlingos > 0) {
            $reasons[] = "Completes {$completedSlingos} Slingo" . ($completedSlingos > 1 ? 's' : '');
        }
        
        if (count($wildPlacements) > 0) {
            $reasons[] = "Uses " . count($wildPlacements) . " wild card" . (count($wildPlacements) > 1 ? 's' : '');
        }
        
        if (count($superWildPlacements) > 0) {
            $reasons[] = "Uses " . count($superWildPlacements) . " super wild card" . (count($superWildPlacements) > 1 ? 's' : '');
        }
        
        if (empty($reasons)) {
            $reasons[] = "Provides setup for future Slingo opportunities";
        }
        
        return implode(', ', $reasons);
    }
    
    /**
     * Get all draw rows
     */
    public function getRows() {
        return $this->rows;
    }
}
