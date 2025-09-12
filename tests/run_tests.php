<?php
/**
 * Simple test runner for Slingo Board Checker
 * Run this file to execute all tests without PHPUnit
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include test files
require_once 'unit/SlingoBoardTest.php';
require_once 'unit/DrawConfigurationTest.php';
require_once 'unit/SlingoAnalyzerTest.php';
require_once 'integration/ApiTest.php';
require_once 'performance/PerformanceTest.php';

// Simple test runner class
class SimpleTestRunner {
    private $tests = [];
    private $passed = 0;
    private $failed = 0;
    private $total = 0;
    
    public function addTest($testClass, $method) {
        $this->tests[] = ['class' => $testClass, 'method' => $method];
    }
    
    public function runTests() {
        echo "Running Slingo Board Checker Tests...\n";
        echo str_repeat("=", 50) . "\n\n";
        
        foreach ($this->tests as $test) {
            $this->runTest($test['class'], $test['method']);
        }
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Test Results:\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total: {$this->total}\n";
        echo "Success Rate: " . round(($this->passed / $this->total) * 100, 2) . "%\n";
        
        if ($this->failed > 0) {
            echo "\n❌ Some tests failed!\n";
            exit(1);
        } else {
            echo "\n✅ All tests passed!\n";
        }
    }
    
    private function runTest($testClass, $method) {
        $this->total++;
        echo "Running {$testClass}::{$method}... ";
        
        try {
            $test = new $testClass();
            $test->setUp();
            $test->$method();
            echo "✅ PASSED\n";
            $this->passed++;
        } catch (Exception $e) {
            echo "❌ FAILED\n";
            echo "   Error: " . $e->getMessage() . "\n";
            $this->failed++;
        }
    }
}

// Create test runner
$runner = new SimpleTestRunner();

// Add unit tests
$runner->addTest('SlingoBoardTest', 'testBoardCreation');
$runner->addTest('SlingoBoardTest', 'testSetCoveredPositions');
$runner->addTest('SlingoBoardTest', 'testHorizontalSlingoDetection');
$runner->addTest('SlingoBoardTest', 'testVerticalSlingoDetection');
$runner->addTest('SlingoBoardTest', 'testDiagonalSlingoDetection');
$runner->addTest('SlingoBoardTest', 'testMultipleSlingos');
$runner->addTest('SlingoBoardTest', 'testPotentialSlingosWithWilds');
$runner->addTest('SlingoBoardTest', 'testRandomBoardGeneration');

$runner->addTest('DrawConfigurationTest', 'testAddRow');
$runner->addTest('DrawConfigurationTest', 'testValidationValidConfiguration');
$runner->addTest('DrawConfigurationTest', 'testValidationEmptyRows');
$runner->addTest('DrawConfigurationTest', 'testOptimalWildPlacements');
$runner->addTest('DrawConfigurationTest', 'testExpectedScoreCalculation');

$runner->addTest('SlingoAnalyzerTest', 'testAnalyzeOptimalStrategy');
$runner->addTest('SlingoAnalyzerTest', 'testAnalysisStructure');
$runner->addTest('SlingoAnalyzerTest', 'testCurrentSlingosCalculation');
$runner->addTest('SlingoAnalyzerTest', 'testCompletionPercentage');

// Add integration tests (only if server is running)
$runner->addTest('ApiTest', 'testAnalyzeEndpointExists');
$runner->addTest('ApiTest', 'testValidPostRequest');

// Add performance tests (only if server is running)
$runner->addTest('PerformanceTest', 'testApiResponseTime');
$runner->addTest('PerformanceTest', 'testMemoryUsage');

// Run all tests
$runner->runTests();
?>
