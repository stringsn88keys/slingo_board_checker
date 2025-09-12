<?php

require_once __DIR__ . '/../../classes/SlingoAnalyzer.php';
require_once __DIR__ . '/../../classes/SlingoBoard.php';

echo "🧪 Testing Multiple Draw Rows via SlingoAnalyzer\n";
echo "================================================\n\n";

// Create a test board state
$boardState = [
    'board_numbers' => [
        [1, 16, 31, 46, 61],
        [2, 17, 32, 47, 62],
        [3, 18, 33, 48, 63],
        [4, 19, 34, 49, 64],
        [5, 20, 35, 50, 65]
    ],
    'covered_positions' => [[0,0], [1,1]] // Some initial coverage
];

// Create draws with multiple rows containing wilds
$draws = [
    ['positions' => ['none', 'none', 'super_wild', 'none', 'none']],      // Row 1: 1 super wild
    ['positions' => ['super_wild', 'none', 'none', 'none', 'super_wild']], // Row 2: 2 super wilds  
    ['positions' => ['none', 'wild', 'none', 'none', 'none']]             // Row 3: 1 wild
];

echo "📋 Input Configuration:\n";
echo "Board: 5x5 with positions [0,0] and [1,1] covered\n";
echo "Draw Row 1: [none, none, super_wild, none, none] - 1 super wild\n";
echo "Draw Row 2: [super_wild, none, none, none, super_wild] - 2 super wilds\n";
echo "Draw Row 3: [none, wild, none, none, none] - 1 wild\n";
echo "Expected Total Wilds: 4 (1 + 2 + 1)\n\n";

// Analyze using SlingoAnalyzer (like the API does)
$analyzer = new SlingoAnalyzer();
$results = $analyzer->analyzeOptimalStrategy($boardState, $draws);

echo "🎯 Analysis Results:\n";
echo "Recommendations count: " . count($results['recommendations']) . "\n\n";

$totalWildsRecommended = 0;
$totalSuperWildsRecommended = 0;

foreach ($results['recommendations'] as $i => $rec) {
    echo "📍 Recommendation " . ($i + 1) . " (Draw Row {$rec['row']}):\n";
    echo "   Expected Score: {$rec['expected_score']}\n";
    echo "   Reasoning: {$rec['reasoning']}\n";
    
    if (!empty($rec['wild_placements'])) {
        echo "   Wild Placements (" . count($rec['wild_placements']) . "):\n";
        foreach ($rec['wild_placements'] as $wild) {
            echo "      - Board position [{$wild['row']}, {$wild['column']}]\n";
            $totalWildsRecommended++;
        }
    }
    
    if (!empty($rec['super_wild_placements'])) {
        echo "   Super Wild Placements (" . count($rec['super_wild_placements']) . "):\n";
        foreach ($rec['super_wild_placements'] as $superWild) {
            echo "      - Board position [{$superWild['row']}, {$superWild['column']}]\n";
            $totalSuperWildsRecommended++;
        }
    }
    echo "\n";
}

echo "📊 Total Placements Summary:\n";
echo "Total Wilds Recommended: $totalWildsRecommended\n";
echo "Total Super Wilds Recommended: $totalSuperWildsRecommended\n";
echo "Grand Total: " . ($totalWildsRecommended + $totalSuperWildsRecommended) . "\n\n";

if (($totalWildsRecommended + $totalSuperWildsRecommended) === 4) {
    echo "✅ SUCCESS: All 4 wilds are being processed and placed!\n";
} else {
    echo "❌ ISSUE: Expected 4 wilds but got " . ($totalWildsRecommended + $totalSuperWildsRecommended) . "\n";
}

echo "\n🔍 API Response Format (what you'd see in the frontend):\n";
echo json_encode([
    'status' => 'success',
    'optimal_selections' => $results['recommendations'],
    'analysis' => $results['analysis']
], JSON_PRETTY_PRINT);
