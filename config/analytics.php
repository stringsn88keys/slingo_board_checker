<?php
/**
 * Google Analytics Configuration
 * 
 * To enable Google Analytics:
 * 1. Get your Google Analytics 4 Measurement ID (format: G-XXXXXXXXXX)
 * 2. Set the MEASUREMENT_ID constant below
 * 3. Call renderGoogleAnalytics() in your HTML head section
 */

// Google Analytics 4 Measurement ID
// Replace with your actual measurement ID (e.g., 'G-XXXXXXXXXX')
// Leave empty to disable Google Analytics
const GA_MEASUREMENT_ID = 'G-2KCK25VVE5';

/**
 * Render Google Analytics code
 * 
 * This function outputs the standard Google Analytics 4 code as recommended by Google
 * 
 * @param string $measurementId Optional measurement ID override
 * @return void
 */
function renderGoogleAnalytics($measurementId = null) {
    $id = $measurementId ?: GA_MEASUREMENT_ID;
    
    if (empty($id)) {
        return; // No measurement ID configured
    }
    
    // Output the standard Google Analytics 4 code
    echo "<!-- Google tag (gtag.js) -->\n";
    echo "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$id}\"></script>\n";
    echo "<script>\n";
    echo "  window.dataLayer = window.dataLayer || [];\n";
    echo "  function gtag(){dataLayer.push(arguments);}\n";
    echo "  gtag('js', new Date());\n";
    echo "  gtag('config', '{$id}');\n";
    echo "</script>\n";
}

/**
 * Check if Google Analytics is enabled
 * 
 * @return bool True if Google Analytics is configured and enabled
 */
function isGoogleAnalyticsEnabled() {
    return !empty(GA_MEASUREMENT_ID);
}

/**
 * Get Google Analytics measurement ID
 * 
 * @return string|null The measurement ID or null if not configured
 */
function getGoogleAnalyticsId() {
    return !empty(GA_MEASUREMENT_ID) ? GA_MEASUREMENT_ID : null;
}
?>
