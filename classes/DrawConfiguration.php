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
        
        // Count total super wilds in this specific draw row
        $totalSuperWilds = count($superWildPositions);
        
        // If 5 super wilds (the maximum for a single row), use tile placement priority heuristic 
        // instead of DFS to avoid memory exhaustion from combinatorial explosion
        if ($totalSuperWilds >= 5) {
            return $this->calculateOptimalPlacementWithTileHeuristic($board, $wildPositions, $superWildPositions);
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
     * Calculate optimal placement using tile priority heuristic for many super wilds
     * Priority: Center -> Corners -> Other diagonal positions
     */
    private function calculateOptimalPlacementWithTileHeuristic($board, $wildPositions, $superWildPositions) {
        $wildPlacements = [];
        $superWildPlacements = [];
        
        // First, place wilds in their required columns
        foreach ($wildPositions as $wildCol) {
            $bestRow = $this->findBestRowForWildWithHeuristic($board, $wildCol);
            if ($bestRow !== null) {
                $wildPlacements[] = ['row' => $bestRow + 1, 'column' => $wildCol + 1];
            }
        }
        
        // Then place super wilds using tile priority heuristic
        $uncoveredPositions = $this->getUncoveredPositions($board);
        
        // Remove positions already taken by wilds
        $availablePositions = [];
        foreach ($uncoveredPositions as $pos) {
            $isTaken = false;
            foreach ($wildPlacements as $wildPlace) {
                if ($wildPlace['row'] - 1 === $pos['row'] && $wildPlace['column'] - 1 === $pos['col']) {
                    $isTaken = true;
                    break;
                }
            }
            if (!$isTaken) {
                $availablePositions[] = $pos;
            }
        }
        
        // Sort positions by tile priority heuristic
        $prioritizedPositions = $this->sortPositionsByTilePriority($availablePositions, $board);
        
        // Place super wilds in order of priority
        $superWildCount = count($superWildPositions);
        for ($i = 0; $i < $superWildCount && $i < count($prioritizedPositions); $i++) {
            $pos = $prioritizedPositions[$i];
            $superWildPlacements[] = ['row' => $pos['row'] + 1, 'column' => $pos['col'] + 1];
        }
        
        // Calculate score using the same priority system
        $combination = [
            'wild_placements' => $wildPlacements,
            'super_wild_placements' => $superWildPlacements
        ];
        
        $score = $this->evaluatePlacementWithPriority($board, $combination);
        
        return [
            'positions' => $this->convertPlacementsToPositions($wildPositions, $superWildPositions),
            'expected_score' => round($score, 1),
            'reasoning' => $this->generateReasoningWithPlacements($board, $wildPlacements, $superWildPlacements) . " (using tile heuristic)",
            'wild_placements' => $wildPlacements,
            'super_wild_placements' => $superWildPlacements
        ];
    }
    
    /**
     * Sort positions by strategic priority: Completion proximity -> Center/Diagonals -> Others
     */
    private function sortPositionsByTilePriority($positions, $board) {
        // Create a temporary board state to evaluate positions
        $positionsWithScores = [];
        
        foreach ($positions as $pos) {
            $row = $pos['row'];
            $col = $pos['col'];
            $score = 0;
            
            // Priority 1: Proximity to completion
            // Count existing coverage in this row
            $rowCovered = 0;
            for ($c = 0; $c < 5; $c++) {
                if ($board->isCovered($row, $c)) {
                    $rowCovered++;
                }
            }
            
            // Count existing coverage in this column
            $colCovered = 0;
            for ($r = 0; $r < 5; $r++) {
                if ($board->isCovered($r, $col)) {
                    $colCovered++;
                }
            }
            
            // Prioritize positions that are 1 away from completion
            if ($rowCovered === 4) {
                $score += 50000;
            } elseif ($rowCovered === 3) {
                $score += 15000;
            } elseif ($rowCovered === 2) {
                $score += 3000;
            }
            
            if ($colCovered === 4) {
                $score += 50000;
            } elseif ($colCovered === 3) {
                $score += 15000;
            } elseif ($colCovered === 2) {
                $score += 3000;
            }
            
            // Check diagonals
            if ($row === $col) {
                $mainDiagCovered = 0;
                for ($i = 0; $i < 5; $i++) {
                    if ($board->isCovered($i, $i)) {
                        $mainDiagCovered++;
                    }
                }
                if ($mainDiagCovered === 4) {
                    $score += 60000; // Extra bonus for diagonal completion
                } elseif ($mainDiagCovered === 3) {
                    $score += 20000;
                } elseif ($mainDiagCovered === 2) {
                    $score += 5000;
                }
            }
            
            if ($row + $col === 4) {
                $antiDiagCovered = 0;
                for ($i = 0; $i < 5; $i++) {
                    if ($board->isCovered($i, 4 - $i)) {
                        $antiDiagCovered++;
                    }
                }
                if ($antiDiagCovered === 4) {
                    $score += 60000; // Extra bonus for diagonal completion
                } elseif ($antiDiagCovered === 3) {
                    $score += 20000;
                } elseif ($antiDiagCovered === 2) {
                    $score += 5000;
                }
            }
            
            // Priority 2: Center and diagonal compound value
            if ($row === 2 && $col === 2) {
                $score += 8000; // Center - contributes to 4 lines
            } elseif ($row === $col || $row + $col === 4) {
                $score += 5000; // Diagonal positions
            } elseif ($row === 2 || $col === 2) {
                $score += 2000; // Middle positions
            }
            
            // Priority 3: Avoid edge positions with poor strategic value
            if ($row === 0 || $row === 4 || $col === 0 || $col === 4) {
                $score -= 1000; // Slight penalty for edges
            }
            
            $positionsWithScores[] = [
                'position' => $pos,
                'score' => $score
            ];
        }
        
        // Sort by score (highest first)
        usort($positionsWithScores, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // Extract sorted positions
        $prioritized = [];
        foreach ($positionsWithScores as $item) {
            $prioritized[] = $item['position'];
        }
        
        return $prioritized;
    }
    
    /**
     * Find best row for wild using simple heuristic (highest coverage in that column)
     */
    private function findBestRowForWildWithHeuristic($board, $wildCol) {
        $bestRow = null;
        $bestScore = -1;
        
        for ($row = 0; $row < 5; $row++) {
            if (!$board->isCovered($row, $wildCol)) {
                // Simple heuristic: prefer rows with more coverage
                $rowCoverage = 0;
                for ($col = 0; $col < 5; $col++) {
                    if ($board->isCovered($row, $col)) {
                        $rowCoverage++;
                    }
                }
                
                if ($rowCoverage > $bestScore) {
                    $bestScore = $rowCoverage;
                    $bestRow = $row;
                }
            }
        }
        
        return $bestRow;
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
     * Evaluate placement combination using optimal strategic criteria
     */
    private function evaluatePlacementWithPriority($board, $combination) {
        $wildPlacements = $combination['wild_placements'];
        $superWildPlacements = $combination['super_wild_placements'];
        $score = 0;
        
        // 🎯 Priority 1: Complete Slingos (lines) for points/bonuses
        $completedSlingos = $this->countCompletedSlingos($board, $wildPlacements, $superWildPlacements);
        if ($completedSlingos > 0) {
            $score += $completedSlingos * 1000000; // Much higher priority to ensure Slingo completion always wins
            
            // Extra bonus for diagonal completions (contribute to multiple Slingos)
            $diagonalSlingos = $this->countCompletedDiagonalSlingos($board, $wildPlacements, $superWildPlacements);
            $score += $diagonalSlingos * 10000;
            
            // Super wild bonus for completing Slingos (premium resource usage)
            $superWildsUsedForCompletion = $this->countSuperWildsUsedForCompletion($board, $wildPlacements, $superWildPlacements);
            $score += $superWildsUsedForCompletion * 5000;
            
            // If multiple positions complete Slingos, prefer the first one (deterministic)
            $score += $this->calculatePositionPriority($wildPlacements, $superWildPlacements);
            
            return $score;
        }
        
        // 🎯 Priority 2: Target rows/columns close to completion (only if no Slingos completed)
        $proximityScore = $this->calculateProximityToCompletionScore($board, $wildPlacements, $superWildPlacements);
        $score += $proximityScore;
        
        // 🎯 Priority 3: Favor center and diagonals for compound value
        $centerDiagonalScore = $this->calculateCenterDiagonalScore($board, $wildPlacements, $superWildPlacements);
        $score += $centerDiagonalScore;
        
        // 🎯 Priority 4: Double Slingo potential (one placement completing two lines)
        $doubleSlingoScore = $this->calculateDoubleSlingoScore($board, $wildPlacements, $superWildPlacements);
        $score += $doubleSlingoScore;
        
        // 🎯 Priority 5: Use super wilds strategically (premium resource management)
        $superWildEfficiencyScore = $this->calculateSuperWildEfficiencyScore($board, $wildPlacements, $superWildPlacements);
        $score += $superWildEfficiencyScore;
        
        // 🎯 Priority 6: Avoid low-value placements (edge rows with multiple gaps)
        $avoidancePenalty = $this->calculateAvoidancePenalty($board, $wildPlacements, $superWildPlacements);
        $score -= $avoidancePenalty;
        
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
     * Calculate score based on proximity to Slingo completion
     * 1 number away = highest priority, 2 numbers away = next priority
     */
    private function calculateProximityToCompletionScore($board, $wildPlacements, $superWildPlacements) {
        $score = 0;
        
        // Check all rows, columns, and diagonals
        for ($row = 0; $row < 5; $row++) {
            $placesInRow = $this->countPlacesInRow($board, $row, $wildPlacements, $superWildPlacements);
            if ($placesInRow === 4) {
                $score += 15000; // 1 away from completion - highest priority
            } elseif ($placesInRow === 3) {
                $score += 5000; // 2 away from completion - next priority
            } elseif ($placesInRow === 2) {
                $score += 1000; // 3 away - some value for progression
            }
        }
        
        for ($col = 0; $col < 5; $col++) {
            $placesInCol = $this->countPlacesInColumn($board, $col, $wildPlacements, $superWildPlacements);
            if ($placesInCol === 4) {
                $score += 15000; // 1 away from completion - highest priority
            } elseif ($placesInCol === 3) {
                $score += 5000; // 2 away from completion - next priority
            } elseif ($placesInCol === 2) {
                $score += 1000; // 3 away - some value for progression
            }
        }
        
        // Diagonals
        $mainDiagPlaces = $this->countPlacesInMainDiagonal($board, $wildPlacements, $superWildPlacements);
        if ($mainDiagPlaces === 4) {
            $score += 20000; // Extra bonus for diagonal completion
        } elseif ($mainDiagPlaces === 3) {
            $score += 7000;
        } elseif ($mainDiagPlaces === 2) {
            $score += 1500;
        }
        
        $antiDiagPlaces = $this->countPlacesInAntiDiagonal($board, $wildPlacements, $superWildPlacements);
        if ($antiDiagPlaces === 4) {
            $score += 20000; // Extra bonus for diagonal completion
        } elseif ($antiDiagPlaces === 3) {
            $score += 7000;
        } elseif ($antiDiagPlaces === 2) {
            $score += 1500;
        }
        
        return $score;
    }
    
    /**
     * Calculate score for center and diagonal positions (compound value)
     */
    private function calculateCenterDiagonalScore($board, $wildPlacements, $superWildPlacements) {
        $score = 0;
        
        foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
            $row = $placement['row'] - 1;
            $col = $placement['column'] - 1;
            
            // Center position (2,2) - contributes to row, column, and both diagonals
            if ($row === 2 && $col === 2) {
                $score += 3000;
            }
            // Main diagonal positions
            elseif ($row === $col) {
                $score += 2000;
            }
            // Anti-diagonal positions
            elseif ($row + $col === 4) {
                $score += 2000;
            }
            // Middle row/column positions (contribute to more potential combinations)
            elseif ($row === 2 || $col === 2) {
                $score += 1000;
            }
        }
        
        return $score;
    }
    
    /**
     * Calculate score for double Slingo potential (one placement completing two lines)
     */
    private function calculateDoubleSlingoScore($board, $wildPlacements, $superWildPlacements) {
        $score = 0;
        
        foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
            $row = $placement['row'] - 1;
            $col = $placement['column'] - 1;
            
            $linesThisPositionCanComplete = 0;
            
            // Check if placing here would complete the row
            if ($this->wouldPositionCompleteRow($board, $row, $col, $wildPlacements, $superWildPlacements)) {
                $linesThisPositionCanComplete++;
            }
            
            // Check if placing here would complete the column
            if ($this->wouldPositionCompleteColumn($board, $row, $col, $wildPlacements, $superWildPlacements)) {
                $linesThisPositionCanComplete++;
            }
            
            // Check diagonals
            if ($row === $col && $this->wouldPositionCompleteMainDiagonal($board, $row, $col, $wildPlacements, $superWildPlacements)) {
                $linesThisPositionCanComplete++;
            }
            
            if ($row + $col === 4 && $this->wouldPositionCompleteAntiDiagonal($board, $row, $col, $wildPlacements, $superWildPlacements)) {
                $linesThisPositionCanComplete++;
            }
            
            // Score based on number of lines this position can complete
            if ($linesThisPositionCanComplete >= 2) {
                $score += $linesThisPositionCanComplete * 8000; // Double/triple/quad Slingo bonus
            }
        }
        
        return $score;
    }
    
    /**
     * Calculate super wild efficiency score (premium resource management)
     */
    private function calculateSuperWildEfficiencyScore($board, $wildPlacements, $superWildPlacements) {
        $score = 0;
        
        foreach ($superWildPlacements as $placement) {
            $row = $placement['row'] - 1;
            $col = $placement['column'] - 1;
            
            // Bonus for using super wilds to complete Slingos
            if ($this->wouldPositionCompleteAnySlingo($board, $row, $col, $wildPlacements, $superWildPlacements)) {
                $score += 4000;
            }
            
            // Bonus for using super wilds in high-value positions (center, diagonals)
            if ($row === 2 && $col === 2) {
                $score += 2000; // Center
            } elseif ($row === $col || $row + $col === 4) {
                $score += 1500; // Diagonals
            }
            
            // Bonus for using super wilds where they contribute to multiple potential Slingos
            $potentialContributions = $this->countPotentialSlingoContributions($board, $row, $col);
            $score += $potentialContributions * 500;
        }
        
        return $score;
    }
    
    /**
     * Calculate penalty for low-value placements (edge rows with multiple gaps)
     */
    private function calculateAvoidancePenalty($board, $wildPlacements, $superWildPlacements) {
        $penalty = 0;
        
        foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
            $row = $placement['row'] - 1;
            $col = $placement['column'] - 1;
            
            // Penalty for placing on rows/columns with many gaps
            $rowGaps = $this->countGapsInRow($board, $row, $wildPlacements, $superWildPlacements);
            $colGaps = $this->countGapsInColumn($board, $col, $wildPlacements, $superWildPlacements);
            
            if ($rowGaps >= 3) {
                $penalty += 1000; // Avoid rows with multiple remaining numbers
            }
            if ($colGaps >= 3) {
                $penalty += 1000; // Avoid columns with multiple remaining numbers
            }
            
            // Extra penalty for edge positions with poor strategic value
            if (($row === 0 || $row === 4 || $col === 0 || $col === 4) && 
                ($rowGaps >= 2 || $colGaps >= 2)) {
                $penalty += 500;
            }
        }
        
        return $penalty;
    }
    
    /**
     * Count super wilds used for completing Slingos
     */
    private function countSuperWildsUsedForCompletion($board, $wildPlacements, $superWildPlacements) {
        $count = 0;
        
        foreach ($superWildPlacements as $placement) {
            $row = $placement['row'] - 1;
            $col = $placement['column'] - 1;
            
            if ($this->wouldPositionCompleteAnySlingo($board, $row, $col, $wildPlacements, $superWildPlacements)) {
                $count++;
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
        return $coveredCount === 4; // Exactly 4 covered + 1 wild = complete Slingo
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
        return $coveredCount === 4; // Exactly 4 covered + 1 wild = complete Slingo
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
            // If we have exactly 4 covered cells and we're placing at the 5th position, it completes the diagonal
            if ($coveredCount === 4) return true;
        }
        
        // Check anti-diagonal
        if ($row + $col === 4) {
            $coveredCount = 0;
            for ($i = 0; $i < 5; $i++) {
                if ($board->isCovered($i, 4 - $i)) {
                    $coveredCount++;
                }
            }
            // If we have exactly 4 covered cells and we're placing at the 5th position, it completes the diagonal
            if ($coveredCount === 4) return true;
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
     * Check if a position would complete any Slingo line
     */
    private function wouldPositionCompleteAnySlingo($board, $row, $col, $wildPlacements, $superWildPlacements) {
        return $this->wouldPositionCompleteRow($board, $row, $col, $wildPlacements, $superWildPlacements) ||
               $this->wouldPositionCompleteColumn($board, $row, $col, $wildPlacements, $superWildPlacements) ||
               ($row === $col && $this->wouldPositionCompleteMainDiagonal($board, $row, $col, $wildPlacements, $superWildPlacements)) ||
               ($row + $col === 4 && $this->wouldPositionCompleteAntiDiagonal($board, $row, $col, $wildPlacements, $superWildPlacements));
    }
    
    /**
     * Check if placing at position would complete the row
     */
    private function wouldPositionCompleteRow($board, $targetRow, $targetCol, $wildPlacements, $superWildPlacements) {
        $coveredCount = 0;
        
        for ($col = 0; $col < 5; $col++) {
            if ($board->isCovered($targetRow, $col)) {
                $coveredCount++;
            } elseif ($col === $targetCol) {
                $coveredCount++; // This position would be covered by our placement
            } else {
                // Check if another wild covers this position
                $hasWild = false;
                foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
                    if ($placement['row'] - 1 === $targetRow && $placement['column'] - 1 === $col) {
                        $hasWild = true;
                        break;
                    }
                }
                if ($hasWild) {
                    $coveredCount++;
                }
            }
        }
        
        return $coveredCount >= 5;
    }
    
    /**
     * Check if placing at position would complete the column
     */
    private function wouldPositionCompleteColumn($board, $targetRow, $targetCol, $wildPlacements, $superWildPlacements) {
        $coveredCount = 0;
        
        for ($row = 0; $row < 5; $row++) {
            if ($board->isCovered($row, $targetCol)) {
                $coveredCount++;
            } elseif ($row === $targetRow) {
                $coveredCount++; // This position would be covered by our placement
            } else {
                // Check if another wild covers this position
                $hasWild = false;
                foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $targetCol) {
                        $hasWild = true;
                        break;
                    }
                }
                if ($hasWild) {
                    $coveredCount++;
                }
            }
        }
        
        return $coveredCount >= 5;
    }
    
    /**
     * Check if placing at position would complete main diagonal
     */
    private function wouldPositionCompleteMainDiagonal($board, $targetRow, $targetCol, $wildPlacements, $superWildPlacements) {
        if ($targetRow !== $targetCol) {
            return false; // Not on main diagonal
        }
        
        $coveredCount = 0;
        
        for ($i = 0; $i < 5; $i++) {
            if ($board->isCovered($i, $i)) {
                $coveredCount++;
            } elseif ($i === $targetRow) {
                $coveredCount++; // This position would be covered by our placement
            } else {
                // Check if another wild covers this position
                $hasWild = false;
                foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
                    if ($placement['row'] - 1 === $i && $placement['column'] - 1 === $i) {
                        $hasWild = true;
                        break;
                    }
                }
                if ($hasWild) {
                    $coveredCount++;
                }
            }
        }
        
        return $coveredCount >= 5;
    }
    
    /**
     * Check if placing at position would complete anti-diagonal
     */
    private function wouldPositionCompleteAntiDiagonal($board, $targetRow, $targetCol, $wildPlacements, $superWildPlacements) {
        if ($targetRow + $targetCol !== 4) {
            return false; // Not on anti-diagonal
        }
        
        $coveredCount = 0;
        
        for ($i = 0; $i < 5; $i++) {
            $row = $i;
            $col = 4 - $i;
            if ($board->isCovered($row, $col)) {
                $coveredCount++;
            } elseif ($row === $targetRow && $col === $targetCol) {
                $coveredCount++; // This position would be covered by our placement
            } else {
                // Check if another wild covers this position
                $hasWild = false;
                foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $hasWild = true;
                        break;
                    }
                }
                if ($hasWild) {
                    $coveredCount++;
                }
            }
        }
        
        return $coveredCount >= 5;
    }
    
    /**
     * Count potential Slingo contributions for a position
     */
    private function countPotentialSlingoContributions($board, $row, $col) {
        $contributions = 0;
        
        // Check row contribution
        $rowPlaces = 0;
        for ($c = 0; $c < 5; $c++) {
            if ($board->isCovered($row, $c)) {
                $rowPlaces++;
            }
        }
        if ($rowPlaces >= 2) { // Row has potential
            $contributions++;
        }
        
        // Check column contribution
        $colPlaces = 0;
        for ($r = 0; $r < 5; $r++) {
            if ($board->isCovered($r, $col)) {
                $colPlaces++;
            }
        }
        if ($colPlaces >= 2) { // Column has potential
            $contributions++;
        }
        
        // Check main diagonal
        if ($row === $col) {
            $diagPlaces = 0;
            for ($i = 0; $i < 5; $i++) {
                if ($board->isCovered($i, $i)) {
                    $diagPlaces++;
                }
            }
            if ($diagPlaces >= 2) {
                $contributions++;
            }
        }
        
        // Check anti-diagonal
        if ($row + $col === 4) {
            $antiDiagPlaces = 0;
            for ($i = 0; $i < 5; $i++) {
                if ($board->isCovered($i, 4 - $i)) {
                    $antiDiagPlaces++;
                }
            }
            if ($antiDiagPlaces >= 2) {
                $contributions++;
            }
        }
        
        return $contributions;
    }
    
    /**
     * Count gaps (uncovered positions) in a row
     */
    private function countGapsInRow($board, $row, $wildPlacements, $superWildPlacements) {
        $gaps = 0;
        
        for ($col = 0; $col < 5; $col++) {
            if (!$board->isCovered($row, $col)) {
                // Check if this position is covered by a wild placement
                $hasWild = false;
                foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $hasWild = true;
                        break;
                    }
                }
                if (!$hasWild) {
                    $gaps++;
                }
            }
        }
        
        return $gaps;
    }
    
    /**
     * Count gaps (uncovered positions) in a column
     */
    private function countGapsInColumn($board, $col, $wildPlacements, $superWildPlacements) {
        $gaps = 0;
        
        for ($row = 0; $row < 5; $row++) {
            if (!$board->isCovered($row, $col)) {
                // Check if this position is covered by a wild placement
                $hasWild = false;
                foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
                    if ($placement['row'] - 1 === $row && $placement['column'] - 1 === $col) {
                        $hasWild = true;
                        break;
                    }
                }
                if (!$hasWild) {
                    $gaps++;
                }
            }
        }
        
        return $gaps;
    }
    
    /**
     * Calculate position priority for deterministic ordering
     * Lower row/column numbers get higher priority
     */
    private function calculatePositionPriority($wildPlacements, $superWildPlacements) {
        $priority = 0;
        
        // Give priority to positions with lower row/column numbers
        foreach (array_merge($wildPlacements, $superWildPlacements) as $placement) {
            $row = $placement['row'];
            $col = $placement['column'];
            // Lower numbers get higher priority (subtract from a large number)
            $priority += (100 - $row) * 100 + (100 - $col);
        }
        
        return $priority;
    }
    
    /**
     * Get all draw rows
     */
    public function getRows() {
        return $this->rows;
    }
}
