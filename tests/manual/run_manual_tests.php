#!/usr/bin/env php
<?php

echo "🧪 Running All Manual Tests\n";
echo "===========================\n\n";

$testDir = __DIR__;
$testFiles = [
    'multiple_draw_rows_test.php',
    'api_multiple_rows_test.php', 
    'cumulative_board_state_test.php',
    'tile_heuristic_test.php',
    'multiple_draw_configurations_test.php'
];

$totalTests = count($testFiles);
$passedTests = 0;
$testResults = [];

foreach ($testFiles as $i => $testFile) {
    $testPath = $testDir . '/' . $testFile;
    
    if (!file_exists($testPath)) {
        echo "❌ Test file not found: $testFile\n";
        continue;
    }
    
    echo "📋 Running Test " . ($i + 1) . "/$totalTests: $testFile\n";
    echo str_repeat("=", 60) . "\n";
    
    ob_start();
    $startTime = microtime(true);
    
    try {
        include $testPath;
        $endTime = microtime(true);
        $output = ob_get_clean();
        
        $executionTime = round($endTime - $startTime, 3);
        
        // Simple success detection - look for success indicators
        $hasSuccess = (strpos($output, '✅ SUCCESS') !== false || 
                      strpos($output, '✅ CORRECT') !== false ||
                      strpos($output, 'All') !== false);
        
        if ($hasSuccess) {
            $passedTests++;
            $status = "✅ PASSED";
        } else {
            $status = "⚠️  COMPLETED";
        }
        
        echo $output;
        echo "\n" . str_repeat("-", 40) . "\n";
        echo "Status: $status | Time: {$executionTime}s\n\n";
        
        $testResults[] = [
            'file' => $testFile,
            'status' => $status,
            'time' => $executionTime,
            'success' => $hasSuccess
        ];
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
        
        $testResults[] = [
            'file' => $testFile,
            'status' => "❌ FAILED",
            'time' => 0,
            'success' => false
        ];
    }
}

echo "📊 Manual Test Summary\n";
echo "=====================\n";

foreach ($testResults as $result) {
    $file = isset($result['file']) ? $result['file'] : 'Unknown';
    $status = isset($result['status']) ? $result['status'] : 'Unknown';
    $time = isset($result['time']) ? $result['time'] : '0';
    
    echo sprintf("%-40s %s (%ss)\n", $file, $status, $time);
}

echo "\n🎯 Overall Results:\n";
echo "==================\n";
echo "Total Tests: $totalTests\n";
echo "Passed/Completed: $passedTests\n";

if ($totalTests > 0) {
    echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n";
} else {
    echo "Success Rate: 0%\n";
}

if ($passedTests === $totalTests) {
    echo "\n🎉 All manual tests completed successfully!\n";
} else {
    echo "\n⚠️  Some tests may need review.\n";
}

echo "\nℹ️  Note: These are exploratory tests for development and debugging.\n";
echo "   For automated testing, use: php tests/simple_test_runner.php\n";

?>
