<?php
require_once 'classes/SlingoBoard.php';
require_once 'classes/DrawConfiguration.php';

// Test the specific board state
$board = new SlingoBoard();
$board->setCoveredPositions([[0,0],[1,3],[2,2],[3,3]]);

echo "Board state:\n";
for ($row = 0; $row < 5; $row++) {
    for ($col = 0; $col < 5; $col++) {
        if ($board->isCovered($row, $col)) {
            echo "X ";
        } else {
            echo ". ";
        }
    }
    echo "\n";
}

$config = new DrawConfiguration();
$config->addRow(['super_wild', 'wild', 'none', 'none', 'none']);

// Test the combination generation
$reflection = new ReflectionClass($config);
$method = $reflection->getMethod('generateWildCombinations');
$method->setAccessible(true);
$combinations = $method->invoke($config, [0], [0]); // wild in col 0, super wild in col 0

echo "\nCombinations:\n";
foreach ($combinations as $i => $combination) {
    echo "Combination $i: " . json_encode($combination) . "\n";
}

// Test each combination individually
$method = $reflection->getMethod('calculateOptimalPlacement');
$method->setAccessible(true);

echo "\nTesting each combination:\n";
foreach ($combinations as $i => $combination) {
    echo "\nCombination $i: " . json_encode($combination) . "\n";
    
    // Create a temporary config with this combination
    $tempConfig = new DrawConfiguration();
    $tempConfig->addRow($combination);
    
    $tempMethod = $reflection->getMethod('calculateOptimalPlacement');
    $tempMethod->setAccessible(true);
    $result = $tempMethod->invoke($tempConfig, $board, [0], [0]);
    
    echo "Score: " . $result['expected_score'] . "\n";
    echo "Wild placements: " . json_encode($result['wild_placements']) . "\n";
    echo "Super wild placements: " . json_encode($result['super_wild_placements']) . "\n";
}
?>
