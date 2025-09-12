<?php
/**
 * Simple test runner for Slingo Board Checker
 * Run this file to execute all tests
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include test files
require_once 'SimpleTest.php';
require_once 'unit/SimpleSlingoBoardTest.php';
require_once 'unit/SimpleDrawConfigurationTest.php';
require_once 'unit/SimpleSlingoAnalyzerTest.php';

echo "🧪 Running Slingo Board Checker Tests...\n";
echo str_repeat("=", 60) . "\n\n";

$totalPassed = 0;
$totalFailed = 0;
$totalTests = 0;

// Test classes and their methods
$testClasses = [
    'SimpleSlingoBoardTest' => [
        'testBoardCreation',
        'testSetCoveredPositions',
        'testHorizontalSlingoDetection',
        'testVerticalSlingoDetection',
        'testDiagonalSlingoDetection',
        'testPotentialSlingosWithWilds',
        'testRandomBoardGeneration',
        'testBoardState'
    ],
    'SimpleDrawConfigurationTest' => [
        'testAddRow',
        'testValidationValidConfiguration',
        'testValidationEmptyRows',
        'testValidationTooManyRows',
        'testOptimalWildPlacements',
        'testExpectedScoreCalculation',
        'testTilePlacementHeuristic',
        'testMultipleDrawRowsWithWilds',
        'testMemoryEfficiencyWithManyWilds',
        'testStrategicWildPlacement',
        'testDiagonalPriorityStrategy',
        'testAvoidLowValuePlacements'
    ],
    'SimpleSlingoAnalyzerTest' => [
        'testAnalyzeOptimalStrategy',
        'testAnalysisStructure',
        'testCurrentSlingosCalculation',
        'testCompletionPercentage',
        'testProbabilityBreakdown',
        'testCalculateExpectedValue',
        'testEmptyDraws',
        'testPriority1CompletedSlingos',
        'testPriority2DiagonalPriority',
        'testPriority3SetupMoves',
        'testDepthFirstSearchValidCombinations',
        'testSpecificExampleFromRequirements',
        'testEdgeCasesHandling'
    ]
];

foreach ($testClasses as $className => $methods) {
    echo "📋 Running {$className}...\n";
    echo str_repeat("-", 40) . "\n";
    
    $test = new $className();
    $test->setUp();
    
    foreach ($methods as $method) {
        $totalTests++;
        echo "  Running {$method}... ";
        
        try {
            $test->$method();
            echo "✅ PASSED\n";
            $totalPassed++;
        } catch (Exception $e) {
            echo "❌ FAILED\n";
            echo "     Error: " . $e->getMessage() . "\n";
            $totalFailed++;
        }
    }
    
    echo "\n";
}

// Summary
echo str_repeat("=", 60) . "\n";
echo "📊 Test Results Summary:\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Passed: {$totalPassed}\n";
echo "❌ Failed: {$totalFailed}\n";
echo "📈 Total: {$totalTests}\n";
echo "🎯 Success Rate: " . round(($totalPassed / $totalTests) * 100, 2) . "%\n";

if ($totalFailed > 0) {
    echo "\n❌ Some tests failed! Please check the errors above.\n";
    exit(1);
} else {
    echo "\n🎉 All tests passed! The Slingo Board Checker is working correctly.\n";
    exit(0);
}
?>
