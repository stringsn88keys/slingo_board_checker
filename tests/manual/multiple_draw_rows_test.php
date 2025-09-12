<?php

require_once __DIR__ . '/../../classes/DrawConfiguration.php';
require_once __DIR__ . '/../../classes/SlingoBoard.php';

echo "🧪 Testing Multiple Draw Rows with Wilds\n";
echo "==========================================\n\n";

// Create a test board
$testNumbers = [
    [1, 16, 31, 46, 61],
    [2, 17, 32, 47, 62],
    [3, 18, 33, 48, 63],
    [4, 19, 34, 49, 64],
    [5, 20, 35, 50, 65]
];
$board = new SlingoBoard($testNumbers);
$board->setCoveredPositions([[0,0], [1,1]]); // Some initial coverage

// Create draw configuration with multiple rows
$config = new DrawConfiguration();

// Row 1: 1 super wild in column 2
$config->addRow(['none', 'none', 'super_wild', 'none', 'none']);

// Row 2: 2 super wilds in columns 0 and 4  
$config->addRow(['super_wild', 'none', 'none', 'none', 'super_wild']);

// Row 3: 1 wild in column 1
$config->addRow(['none', 'wild', 'none', 'none', 'none']);

echo "📋 Draw Configuration:\n";
echo "Row 1: [none, none, super_wild, none, none]\n";
echo "Row 2: [super_wild, none, none, none, super_wild]\n";
echo "Row 3: [none, wild, none, none, none]\n\n";

// Get recommendations
$recommendations = $config->getOptimalWildPlacements($board);

echo "🎯 Wild Placement Recommendations:\n";
echo "Total recommendations: " . count($recommendations) . "\n\n";

$totalWildsPlaced = 0;
$totalSuperWildsPlaced = 0;

foreach ($recommendations as $i => $rec) {
    echo "📍 Recommendation " . ($i + 1) . " (Row {$rec['row']}):\n";
    echo "   Expected Score: {$rec['expected_score']}\n";
    echo "   Reasoning: {$rec['reasoning']}\n";
    
    if (!empty($rec['wild_placements'])) {
        echo "   Wild Placements:\n";
        foreach ($rec['wild_placements'] as $wild) {
            echo "      - Row {$wild['row']}, Column {$wild['column']}\n";
            $totalWildsPlaced++;
        }
    }
    
    if (!empty($rec['super_wild_placements'])) {
        echo "   Super Wild Placements:\n";
        foreach ($rec['super_wild_placements'] as $superWild) {
            echo "      - Row {$superWild['row']}, Column {$superWild['column']}\n";
            $totalSuperWildsPlaced++;
        }
    }
    echo "\n";
}

echo "📊 Summary:\n";
echo "Total Wilds Placed: $totalWildsPlaced\n";
echo "Total Super Wilds Placed: $totalSuperWildsPlaced\n";
echo "Total Placements: " . ($totalWildsPlaced + $totalSuperWildsPlaced) . "\n";

if (($totalWildsPlaced + $totalSuperWildsPlaced) === 4) {
    echo "✅ CORRECT: All 4 wilds (1 + 2 + 1) were placed correctly!\n";
} else {
    echo "❌ ERROR: Expected 4 total wilds but got " . ($totalWildsPlaced + $totalSuperWildsPlaced) . "\n";
}
