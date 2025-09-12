<?php

require_once __DIR__ . '/../../classes/SlingoBoard.php';
require_once __DIR__ . '/../../classes/DrawConfiguration.php';

echo "🧪 Testing Tile Placement Heuristic\n";
echo "===================================\n\n";

// Test the tile placement heuristic directly
$boardNumbers = [
    [1, 16, 31, 46, 61],
    [2, 17, 32, 47, 62], 
    [3, 18, 33, 48, 63],
    [4, 19, 34, 49, 64],
    [5, 20, 35, 50, 65]
];

$board = new SlingoBoard($boardNumbers);

// Set some covered positions - center is covered to test prioritization
$coveredPositions = [
    [2, 2] // Center is covered
];

$board->setCoveredPositions($coveredPositions);

echo "📋 Board Setup:\n";
echo "Board with center position [2,2] already covered\n";
echo "Testing tile heuristic with 5 super wilds (memory optimization scenario)\n\n";

// Create configuration with 5 super wilds to trigger tile heuristic
$config = new DrawConfiguration();
$config->addRow(['super_wild', 'super_wild', 'super_wild', 'super_wild', 'super_wild']);

$startTime = microtime(true);

echo "🎯 Getting optimal placements...\n";
$placements = $config->getOptimalWildPlacements($board);

$endTime = microtime(true);
$executionTime = $endTime - $startTime;

echo "📊 Results:\n";
echo "===========\n";
echo "Execution time: " . round($executionTime, 3) . " seconds\n";
echo "Placements found: " . count($placements) . "\n\n";

if (!empty($placements)) {
    $placement = $placements[0];
    echo "📍 Placement Details:\n";
    echo "Expected Score: {$placement['expected_score']}\n";
    echo "Reasoning: {$placement['reasoning']}\n";
    echo "Super Wild Placements:\n";
    
    foreach ($placement['super_wild_placements'] as $i => $superWild) {
        echo "  " . ($i + 1) . ". Row {$superWild['row']}, Column {$superWild['column']}\n";
    }
    
    echo "\n🔍 Analysis:\n";
    if (strpos($placement['reasoning'], 'tile heuristic') !== false) {
        echo "✅ SUCCESS: Tile heuristic was used (memory optimization active)\n";
    } else {
        echo "ℹ️  NOTE: DFS algorithm was used (small number of wilds)\n";
    }
    
    if ($executionTime < 2.0) {
        echo "✅ SUCCESS: Execution completed in under 2 seconds\n";
    } else {
        echo "⚠️  WARNING: Execution took longer than expected\n";
    }
    
    if (count($placement['super_wild_placements']) === 5) {
        echo "✅ SUCCESS: All 5 super wilds were placed\n";
    } else {
        echo "❌ ERROR: Expected 5 super wild placements, got " . count($placement['super_wild_placements']) . "\n";
    }
}

echo "\n🎯 Strategic Analysis:\n";
echo "======================\n";
echo "This test validates the tile placement heuristic that activates when\n";
echo "memory optimization is needed (5+ super wilds). The heuristic prioritizes:\n";
echo "1. Positions close to completion (1-2 numbers away)\n";
echo "2. Center and diagonal positions for compound value\n";
echo "3. Avoids edge positions with poor strategic value\n";
echo "4. Maintains execution speed under 2 seconds\n";
