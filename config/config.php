<?php
// Slingo Board Checker Configuration

// Application settings
define('APP_NAME', 'Slingo Board Checker');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', true);

// Database settings (if needed in future)
define('DB_HOST', 'localhost');
define('DB_NAME', 'slingo_checker');
define('DB_USER', 'root');
define('DB_PASS', '');

// API settings
define('API_RATE_LIMIT', 100); // requests per hour
define('API_TIMEOUT', 30); // seconds

// Slingo game settings
define('BOARD_SIZE', 5);
define('MAX_DRAW_ROWS', 3);
define('MIN_DRAW_ROWS', 1);
define('SLINGO_POINTS', 25); // points per Slingo
define('WILD_BONUS', 10); // bonus points for wild cards
define('SUPER_WILD_BONUS', 15); // bonus points for super wild cards

// File paths
define('CLASSES_PATH', __DIR__ . '/../classes/');
define('API_PATH', __DIR__ . '/../api/');
define('JS_PATH', __DIR__ . '/../js/');
define('CSS_PATH', __DIR__ . '/../css/');

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('UTC');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS

// CORS settings
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
?>
