<?php

require_once __DIR__ . '/../../classes/SlingoAnalyzer.php';
require_once __DIR__ . '/../../classes/SlingoBoard.php';

echo "🧪 Testing Cumulative Board State After Multiple Draw Rows\n";
echo "=========================================================\n\n";

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

echo "📋 Initial Setup:\n";
echo "=================\n";
echo "Board Numbers:\n";
for ($r = 0; $r < 5; $r++) {
    echo "Row $r: [";
    for ($c = 0; $c < 5; $c++) {
        echo sprintf("%2d", $boardState['board_numbers'][$r][$c]);
        if ($c < 4) echo ", ";
    }
    echo "]\n";
}
echo "\nInitially Covered Positions: [0,0], [1,1]\n";
echo "Draw Row 1: [none, none, super_wild, none, none] - 1 super wild\n";
echo "Draw Row 2: [super_wild, none, none, none, super_wild] - 2 super wilds\n";
echo "Draw Row 3: [none, wild, none, none, none] - 1 wild\n";
echo "Expected Total New Placements: 4 (1 + 2 + 1)\n\n";

// Analyze using SlingoAnalyzer
$analyzer = new SlingoAnalyzer();
$results = $analyzer->analyzeOptimalStrategy($boardState, $draws);

echo "🎯 Recommendation Analysis:\n";
echo "===========================\n";

// Create a board to track cumulative placements
$cumulativeBoard = new SlingoBoard($boardState['board_numbers']);
$cumulativeBoard->setCoveredPositions($boardState['covered_positions']);

$allPlacements = [];
$totalWildsProcessed = 0;
$totalSuperWildsProcessed = 0;

foreach ($results['recommendations'] as $i => $rec) {
    echo "📍 Draw Row " . ($i + 1) . " Analysis:\n";
    echo "   Expected Score: {$rec['expected_score']}\n";
    echo "   Reasoning: {$rec['reasoning']}\n";
    
    $rowPlacements = [];
    
    if (!empty($rec['wild_placements'])) {
        echo "   Wild Placements:\n";
        foreach ($rec['wild_placements'] as $wild) {
            $placement = [
                'type' => 'wild',
                'row' => $wild['row'] - 1, // Convert to 0-indexed
                'col' => $wild['column'] - 1, // Convert to 0-indexed
                'draw_row' => $i + 1
            ];
            echo "      - Board position [{$wild['row']}, {$wild['column']}] (0-indexed: [{$placement['row']}, {$placement['col']}])\n";
            $allPlacements[] = $placement;
            $rowPlacements[] = $placement;
            $totalWildsProcessed++;
        }
    }
    
    if (!empty($rec['super_wild_placements'])) {
        echo "   Super Wild Placements:\n";
        foreach ($rec['super_wild_placements'] as $superWild) {
            $placement = [
                'type' => 'super_wild',
                'row' => $superWild['row'] - 1, // Convert to 0-indexed
                'col' => $superWild['column'] - 1, // Convert to 0-indexed  
                'draw_row' => $i + 1
            ];
            echo "      - Board position [{$superWild['row']}, {$superWild['column']}] (0-indexed: [{$placement['row']}, {$placement['col']}])\n";
            $allPlacements[] = $placement;
            $rowPlacements[] = $placement;
            $totalSuperWildsProcessed++;
        }
    }
    
    echo "   Row " . ($i + 1) . " Total: " . count($rowPlacements) . " placements\n\n";
}

echo "📊 Cumulative Placement Summary:\n";
echo "================================\n";
echo "Total Wilds Processed: $totalWildsProcessed\n";
echo "Total Super Wilds Processed: $totalSuperWildsProcessed\n";
echo "Grand Total Placements: " . count($allPlacements) . "\n\n";

echo "🗺️  Final Board State Visualization:\n";
echo "====================================\n";

// Apply all placements to show final board state
$finalCoveredPositions = $boardState['covered_positions'];
foreach ($allPlacements as $placement) {
    $finalCoveredPositions[] = [$placement['row'], $placement['col']];
}

// Remove duplicates
$uniqueCoveredPositions = [];
foreach ($finalCoveredPositions as $pos) {
    $key = $pos[0] . ',' . $pos[1];
    if (!isset($uniqueCoveredPositions[$key])) {
        $uniqueCoveredPositions[$key] = $pos;
    }
}
$finalCoveredPositions = array_values($uniqueCoveredPositions);

echo "Board with all placements applied:\n";
echo "Legend: [XX] = Initially covered, [WW] = Wild placed, [SW] = Super Wild placed, [  ] = Empty\n\n";

for ($r = 0; $r < 5; $r++) {
    echo "Row $r: ";
    for ($c = 0; $c < 5; $c++) {
        $isInitiallyCovered = false;
        $isWildPlaced = false;
        $isSuperWildPlaced = false;
        
        // Check if initially covered
        foreach ($boardState['covered_positions'] as $pos) {
            if ($pos[0] === $r && $pos[1] === $c) {
                $isInitiallyCovered = true;
                break;
            }
        }
        
        // Check if wild was placed here
        foreach ($allPlacements as $placement) {
            if ($placement['row'] === $r && $placement['col'] === $c) {
                if ($placement['type'] === 'wild') {
                    $isWildPlaced = true;
                } elseif ($placement['type'] === 'super_wild') {
                    $isSuperWildPlaced = true;
                }
            }
        }
        
        if ($isInitiallyCovered) {
            echo "[XX]";
        } elseif ($isSuperWildPlaced) {
            echo "[SW]";
        } elseif ($isWildPlaced) {
            echo "[WW]";
        } else {
            echo "[  ]";
        }
        
        if ($c < 4) echo " ";
    }
    echo "\n";
}

echo "\n📍 Detailed Placement Timeline:\n";
echo "===============================\n";
$placementsByRow = [];
foreach ($allPlacements as $placement) {
    $placementsByRow[$placement['draw_row']][] = $placement;
}

foreach ($placementsByRow as $drawRow => $placements) {
    echo "Draw Row $drawRow:\n";
    foreach ($placements as $placement) {
        $type = ($placement['type'] === 'super_wild') ? 'Super Wild' : 'Wild';
        echo "  - $type placed at board position [{$placement['row']}, {$placement['col']}]\n";
    }
}

echo "\n✅ Verification:\n";
echo "================\n";
if (count($allPlacements) === 4) {
    echo "✅ SUCCESS: All 4 expected wilds were processed and placed!\n";
    echo "✅ SUCCESS: System correctly handles multiple draw rows!\n";
    echo "✅ SUCCESS: Each draw row contributes its wilds to the final board state!\n";
} else {
    echo "❌ ERROR: Expected 4 total placements but got " . count($allPlacements) . "\n";
}

echo "\n🔄 How to Use These Results:\n";
echo "============================\n";
echo "1. Process each recommendation sequentially\n";
echo "2. Apply the placements from each draw row to your board\n";
echo "3. The final board will have all " . count($allPlacements) . " wild placements applied\n";
echo "4. Each draw row's recommendation shows the optimal strategy for that specific draw\n";

?>
