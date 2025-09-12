<?php

require_once __DIR__ . '/../../classes/SlingoBoard.php';
require_once __DIR__ . '/../../classes/DrawConfiguration.php';
require_once __DIR__ . '/../../classes/SlingoAnalyzer.php';

echo "🧪 Testing Multiple Draw Configurations\n";
echo "======================================\n\n";

// Test multiple draw rows to see current behavior
$boardNumbers = [
    [1, 16, 31, 46, 61],
    [2, 17, 32, 47, 62], 
    [3, 18, 33, 48, 63],
    [4, 19, 34, 49, 64],
    [5, 20, 35, 50, 65]
];

$board = new SlingoBoard($boardNumbers);

// Set some covered positions
$coveredPositions = [
    [0, 0], [1, 1], [2, 2]
];

$board->setCoveredPositions($coveredPositions);

echo "📋 Initial Board State:\n";
echo "Covered positions: [0,0], [1,1], [2,2]\n\n";

// Test 1: Single draw row
echo "🎯 Test 1: Single Draw Row\n";
echo "==========================\n";
$config1 = new DrawConfiguration();
$config1->addRow(['wild', 'none', 'super_wild', 'none', 'wild']);

$placements1 = $config1->getOptimalWildPlacements($board);
echo "Placements found: " . count($placements1) . "\n";
if (!empty($placements1)) {
    $p = $placements1[0];
    echo "Score: {$p['expected_score']}, Reasoning: {$p['reasoning']}\n";
    echo "Wilds: " . count($p['wild_placements']) . ", Super Wilds: " . count($p['super_wild_placements']) . "\n";
}
echo "\n";

// Test 2: Multiple draw rows
echo "🎯 Test 2: Multiple Draw Rows\n";
echo "=============================\n";
$config2 = new DrawConfiguration();
$config2->addRow(['wild', 'none', 'none', 'none', 'none']);
$config2->addRow(['none', 'super_wild', 'none', 'none', 'none']);
$config2->addRow(['none', 'none', 'wild', 'super_wild', 'none']);

$placements2 = $config2->getOptimalWildPlacements($board);
echo "Placements found: " . count($placements2) . "\n";

$totalWilds = 0;
$totalSuperWilds = 0;

foreach ($placements2 as $i => $p) {
    echo "Row " . ($i + 1) . ": Score {$p['expected_score']}\n";
    echo "  Wilds: " . count($p['wild_placements']) . ", Super Wilds: " . count($p['super_wild_placements']) . "\n";
    $totalWilds += count($p['wild_placements']);
    $totalSuperWilds += count($p['super_wild_placements']);
}

echo "Total across all rows: $totalWilds wilds, $totalSuperWilds super wilds\n\n";

// Test 3: Using SlingoAnalyzer (API simulation)
echo "🎯 Test 3: Via SlingoAnalyzer (API Path)\n";
echo "=======================================\n";

$boardState = [
    'board_numbers' => $boardNumbers,
    'covered_positions' => $coveredPositions
];

$draws = [
    ['positions' => ['wild', 'none', 'none', 'none', 'none']],
    ['positions' => ['none', 'super_wild', 'none', 'none', 'none']],
    ['positions' => ['none', 'none', 'wild', 'super_wild', 'none']]
];

$analyzer = new SlingoAnalyzer();
$results = $analyzer->analyzeOptimalStrategy($boardState, $draws);

echo "Recommendations: " . count($results['recommendations']) . "\n";

$apiTotalWilds = 0;
$apiTotalSuperWilds = 0;

foreach ($results['recommendations'] as $i => $rec) {
    echo "API Row " . ($i + 1) . ": Score {$rec['expected_score']}\n";
    echo "  Wilds: " . count($rec['wild_placements']) . ", Super Wilds: " . count($rec['super_wild_placements']) . "\n";
    $apiTotalWilds += count($rec['wild_placements']);
    $apiTotalSuperWilds += count($rec['super_wild_placements']);
}

echo "API Total: $apiTotalWilds wilds, $apiTotalSuperWilds super wilds\n\n";

// Comparison
echo "📊 Summary Comparison:\n";
echo "=====================\n";
echo "Direct DrawConfiguration: $totalWilds wilds, $totalSuperWilds super wilds\n";
echo "Via SlingoAnalyzer (API): $apiTotalWilds wilds, $apiTotalSuperWilds super wilds\n";

if ($totalWilds === $apiTotalWilds && $totalSuperWilds === $apiTotalSuperWilds) {
    echo "✅ SUCCESS: Both methods produce identical results\n";
} else {
    echo "⚠️  WARNING: Results differ between direct and API methods\n";
}

echo "\n🔍 Key Insights:\n";
echo "================\n";
echo "- Each draw row is processed independently\n";
echo "- Multiple rows can target the same optimal positions\n";
echo "- Total placements = sum of all individual row placements\n";
echo "- API and direct methods should produce identical results\n";
