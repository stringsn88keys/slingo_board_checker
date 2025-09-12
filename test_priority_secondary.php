<?php

require_once 'classes/SlingoBoard.php';
require_once 'classes/DrawConfiguration.php';
require_once 'classes/SlingoAnalyzer.php';

// Test case where no Slingos can be completed - should prioritize setup moves
$boardNumbers = [
    [1, 16, 31, 46, 61],
    [2, 17, 32, 47, 62], 
    [3, 18, 33, 48, 63],
    [4, 19, 34, 49, 64],
    [5, 20, 35, 50, 65]
];

$board = new SlingoBoard($boardNumbers);

// Set covered positions - only a few scattered positions
$coveredPositions = [
    [0, 0], // [1,1]
    [2, 2]  // [3,3]  
];

$board->setCoveredPositions($coveredPositions);

// Create draw configuration 
$drawConfig = new DrawConfiguration();
$drawConfig->addRow(['super_wild', 'wild', 'none', 'none', 'none']);

echo "=== Testing Secondary Priority System (No Slingos Completable) ===\n";
echo "Board covered positions: " . json_encode($coveredPositions) . "\n";
echo "Draw configuration: [super_wild, wild, none, none, none]\n\n";

// Test the optimal placement
$optimalPlacements = $drawConfig->getOptimalWildPlacements($board);

if (!empty($optimalPlacements)) {
    $placement = $optimalPlacements[0];
    
    echo "=== Optimal Placement Results ===\n";
    echo "Expected Score: " . $placement['expected_score'] . "\n";
    echo "Reasoning: " . $placement['reasoning'] . "\n";
    
    echo "\nWild Placements:\n";
    foreach ($placement['wild_placements'] as $wildPlace) {
        echo "  Wild at Row " . $wildPlace['row'] . ", Column " . $wildPlace['column'] . " (0-indexed: [" . ($wildPlace['row']-1) . "," . ($wildPlace['column']-1) . "])\n";
    }
    echo "\nSuper Wild Placements:\n";
    foreach ($placement['super_wild_placements'] as $superWildPlace) {
        echo "  Super Wild at Row " . $superWildPlace['row'] . ", Column " . $superWildPlace['column'] . " (0-indexed: [" . ($superWildPlace['row']-1) . "," . ($superWildPlace['column']-1) . "])\n";
    }
    
    // Analyze potential Slingos
    echo "\n=== Potential Slingo Analysis ===\n";
    
    $lines = [
        'Row 1' => [[0,0], [0,1], [0,2], [0,3], [0,4]],
        'Row 2' => [[1,0], [1,1], [1,2], [1,3], [1,4]], 
        'Row 3' => [[2,0], [2,1], [2,2], [2,3], [2,4]],
        'Row 4' => [[3,0], [3,1], [3,2], [3,3], [3,4]],
        'Row 5' => [[4,0], [4,1], [4,2], [4,3], [4,4]],
        'Col 1' => [[0,0], [1,0], [2,0], [3,0], [4,0]],
        'Col 2' => [[0,1], [1,1], [2,1], [3,1], [4,1]],
        'Col 3' => [[0,2], [1,2], [2,2], [3,2], [4,2]], 
        'Col 4' => [[0,3], [1,3], [2,3], [3,3], [4,3]],
        'Col 5' => [[0,4], [1,4], [2,4], [3,4], [4,4]],
        'Main Diagonal' => [[0,0], [1,1], [2,2], [3,3], [4,4]],
        'Anti Diagonal' => [[0,4], [1,3], [2,2], [3,1], [4,0]]
    ];
    
    $maxPlaces = 0;
    $bestLines = [];
    
    foreach ($lines as $lineName => $positions) {
        $coveredCount = 0;
        $wildCount = 0;
        
        foreach ($positions as $pos) {
            if ($board->isCovered($pos[0], $pos[1])) {
                $coveredCount++;
            } else {
                // Check if wild placement covers this position
                foreach ($placement['wild_placements'] as $wildPlace) {
                    if ($wildPlace['row'] - 1 === $pos[0] && $wildPlace['column'] - 1 === $pos[1]) {
                        $wildCount++;
                        break;
                    }
                }
                foreach ($placement['super_wild_placements'] as $superWildPlace) {
                    if ($superWildPlace['row'] - 1 === $pos[0] && $superWildPlace['column'] - 1 === $pos[1]) {
                        $wildCount++;
                        break;
                    }
                }
            }
        }
        
        $totalCovered = $coveredCount + $wildCount;
        if ($totalCovered > 1) {
            echo "$lineName: $totalCovered/5 positions ($coveredCount covered + $wildCount wild)\n";
            if ($totalCovered > $maxPlaces) {
                $maxPlaces = $totalCovered;
                $bestLines = [$lineName];
            } elseif ($totalCovered === $maxPlaces) {
                $bestLines[] = $lineName;
            }
        }
    }
    
    echo "\nBest setup lines (most places filled): " . implode(', ', $bestLines) . " with $maxPlaces places\n";
    
} else {
    echo "No optimal placements found.\n";
}
