<?php

require_once 'classes/SlingoBoard.php';
require_once 'classes/DrawConfiguration.php';
require_once 'classes/SlingoAnalyzer.php';

// Test case where multiple Slingos could be completed - should prioritize diagonal
$boardNumbers = [
    [1, 16, 31, 46, 61],
    [2, 17, 32, 47, 62], 
    [3, 18, 33, 48, 63],
    [4, 19, 34, 49, 64],
    [5, 20, 35, 50, 65]
];

$board = new SlingoBoard($boardNumbers);

// Set covered positions to create multiple potential completable Slingos
$coveredPositions = [
    [0, 0], [0, 2], [0, 3], [0, 4], // Row 1 needs position [0,1] to complete
    [1, 1], [2, 2], [3, 3]          // Main diagonal needs [4,4] to complete
];

$board->setCoveredPositions($coveredPositions);

// Create draw configuration - super wild can complete either line
$drawConfig = new DrawConfiguration();
$drawConfig->addRow(['super_wild', 'none', 'none', 'none', 'none']);

echo "=== Testing Diagonal Priority (Multiple Completable Slingos) ===\n";
echo "Board covered positions: " . json_encode($coveredPositions) . "\n";
echo "Draw configuration: [super_wild, none, none, none, none]\n";
echo "Possible completions:\n";
echo "  - Row 1: needs [0,1] to complete\n";
echo "  - Main Diagonal: needs [4,4] to complete\n";
echo "  - Should prioritize diagonal completion\n\n";

// Test the optimal placement
$optimalPlacements = $drawConfig->getOptimalWildPlacements($board);

if (!empty($optimalPlacements)) {
    $placement = $optimalPlacements[0];
    
    echo "=== Optimal Placement Results ===\n";
    echo "Expected Score: " . $placement['expected_score'] . "\n";
    echo "Reasoning: " . $placement['reasoning'] . "\n";
    
    echo "\nSuper Wild Placements:\n";
    foreach ($placement['super_wild_placements'] as $superWildPlace) {
        echo "  Super Wild at Row " . $superWildPlace['row'] . ", Column " . $superWildPlace['column'] . " (0-indexed: [" . ($superWildPlace['row']-1) . "," . ($superWildPlace['column']-1) . "])\n";
    }
    
    // Check which Slingo was completed
    echo "\n=== Completed Slingo Analysis ===\n";
    
    $superWildPos = $placement['super_wild_placements'][0];
    $superWildRow = $superWildPos['row'] - 1;
    $superWildCol = $superWildPos['column'] - 1;
    
    if ($superWildRow === 0 && $superWildCol === 1) {
        echo "✅ Completed Row 1 (horizontal Slingo)\n";
    } elseif ($superWildRow === 4 && $superWildCol === 4) {
        echo "✅ Completed Main Diagonal Slingo (PRIORITY CORRECTLY GIVEN TO DIAGONAL!)\n";
    } else {
        echo "Completed some other Slingo at [$superWildRow, $superWildCol]\n";
    }
    
} else {
    echo "No optimal placements found.\n";
}
