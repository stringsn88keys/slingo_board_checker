<?php
/**
 * Simple test framework for Slingo Board Checker
 */

class SimpleTest {
    private $passed = 0;
    private $failed = 0;
    private $total = 0;
    
    public function assertTrue($condition, $message = '') {
        $this->total++;
        if ($condition) {
            $this->passed++;
            echo "✅ PASS: " . ($message ?: 'Assertion passed') . "\n";
        } else {
            $this->failed++;
            echo "❌ FAIL: " . ($message ?: 'Assertion failed') . "\n";
        }
    }
    
    public function assertFalse($condition, $message = '') {
        $this->assertTrue(!$condition, $message);
    }
    
    public function assertEquals($expected, $actual, $message = '') {
        $this->assertTrue($expected === $actual, $message ?: "Expected {$expected}, got {$actual}");
    }
    
    public function assertNotEquals($expected, $actual, $message = '') {
        $this->assertTrue($expected !== $actual, $message ?: "Expected not {$expected}, got {$actual}");
    }
    
    public function assertGreaterThan($expected, $actual, $message = '') {
        $this->assertTrue($actual > $expected, $message ?: "Expected {$actual} to be greater than {$expected}");
    }
    
    public function assertLessThan($expected, $actual, $message = '') {
        $this->assertTrue($actual < $expected, $message ?: "Expected {$actual} to be less than {$expected}");
    }
    
    public function assertGreaterThanOrEqual($expected, $actual, $message = '') {
        $this->assertTrue($actual >= $expected, $message ?: "Expected {$actual} to be greater than or equal to {$expected}");
    }
    
    public function assertLessThanOrEqual($expected, $actual, $message = '') {
        $this->assertTrue($actual <= $expected, $message ?: "Expected {$actual} to be less than or equal to {$expected}");
    }
    
    public function assertIsArray($actual, $message = '') {
        $this->assertTrue(is_array($actual), $message ?: "Expected array, got " . gettype($actual));
    }
    
    public function assertIsString($actual, $message = '') {
        $this->assertTrue(is_string($actual), $message ?: "Expected string, got " . gettype($actual));
    }
    
    public function assertIsNumeric($actual, $message = '') {
        $this->assertTrue(is_numeric($actual), $message ?: "Expected numeric, got " . gettype($actual));
    }
    
    public function assertIsInt($actual, $message = '') {
        $this->assertTrue(is_int($actual), $message ?: "Expected integer, got " . gettype($actual));
    }
    
    public function assertNotEmpty($actual, $message = '') {
        $this->assertTrue(!empty($actual), $message ?: "Expected non-empty value");
    }
    
    public function assertArrayHasKey($key, $array, $message = '') {
        $this->assertTrue(array_key_exists($key, $array), $message ?: "Expected array to have key '{$key}'");
    }
    
    public function assertStringContains($needle, $haystack, $message = '') {
        $this->assertTrue(strpos($haystack, $needle) !== false, $message ?: "Expected string to contain '{$needle}'");
    }
    
    public function assertCount($expected, $actual, $message = '') {
        $this->assertEquals($expected, count($actual), $message ?: "Expected count {$expected}, got " . count($actual));
    }
    
    public function expectException($exceptionClass) {
        // Simple exception expectation - just return true for now
        return true;
    }
    
    public function getResults() {
        return [
            'passed' => $this->passed,
            'failed' => $this->failed,
            'total' => $this->total,
            'success_rate' => $this->total > 0 ? round(($this->passed / $this->total) * 100, 2) : 0
        ];
    }
}
?>
