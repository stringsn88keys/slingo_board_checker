<?php
// Test bootstrap file

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Include autoloader if using Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Set up test environment
define('TESTING', true);

// Mock any global functions or constants needed for testing
if (!function_exists('json_encode')) {
    function json_encode($data) {
        return json_encode($data);
    }
}

if (!function_exists('json_decode')) {
    function json_decode($json, $assoc = false) {
        return json_decode($json, $assoc);
    }
}

// Set up test data directory
if (!defined('TEST_DATA_DIR')) {
    define('TEST_DATA_DIR', __DIR__ . '/data');
}

// Create test data directory if it doesn't exist
if (!is_dir(TEST_DATA_DIR)) {
    mkdir(TEST_DATA_DIR, 0755, true);
}
?>
