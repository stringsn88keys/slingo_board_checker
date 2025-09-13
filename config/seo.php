<?php
/**
 * SEO Configuration for Slingo Board Checker
 */

// SEO Configuration
const SEO_CONFIG = [
    'site_name' => 'Slingo Board Checker',
    'site_description' => 'Get optimal wild card placement recommendations for your Slingo game. Analyze your 5x5 board and maximize your chances of completing Slingos.',
    'site_keywords' => 'slingo, board checker, wild cards, super wild, game strategy, optimal placement, bingo, casino game, strategy tool',
    'author' => 'Slingo Board Checker',
    'canonical_url' => 'https://slingoboardchecker.com',
    'og_image' => '/images/slingo-board-checker-og.jpg',
    'twitter_handle' => '@SlingoChecker',
    'language' => 'en-US',
    'robots' => 'index, follow',
    'viewport' => 'width=device-width, initial-scale=1.0'
];

/**
 * Render SEO meta tags
 * 
 * @param array $customMeta Optional custom meta tags to override defaults
 * @return void
 */
function renderSEOMetaTags($customMeta = []) {
    $meta = array_merge(SEO_CONFIG, $customMeta);
    
    echo "<!-- Primary Meta Tags -->\n";
    echo "<title>{$meta['site_name']} - Optimal Wild Card Strategy Tool</title>\n";
    echo "<meta name=\"title\" content=\"{$meta['site_name']} - Optimal Wild Card Strategy Tool\">\n";
    echo "<meta name=\"description\" content=\"{$meta['site_description']}\">\n";
    echo "<meta name=\"keywords\" content=\"{$meta['site_keywords']}\">\n";
    echo "<meta name=\"author\" content=\"{$meta['author']}\">\n";
    echo "<meta name=\"robots\" content=\"{$meta['robots']}\">\n";
    echo "<meta name=\"language\" content=\"{$meta['language']}\">\n";
    echo "<meta name=\"viewport\" content=\"{$meta['viewport']}\">\n";
    echo "<link rel=\"canonical\" href=\"{$meta['canonical_url']}\">\n";
    
    echo "\n<!-- Open Graph / Facebook -->\n";
    echo "<meta property=\"og:type\" content=\"website\">\n";
    echo "<meta property=\"og:url\" content=\"{$meta['canonical_url']}\">\n";
    echo "<meta property=\"og:title\" content=\"{$meta['site_name']}\">\n";
    echo "<meta property=\"og:description\" content=\"{$meta['site_description']}\">\n";
    echo "<meta property=\"og:image\" content=\"{$meta['canonical_url']}{$meta['og_image']}\">\n";
    echo "<meta property=\"og:site_name\" content=\"{$meta['site_name']}\">\n";
    echo "<meta property=\"og:locale\" content=\"en_US\">\n";
    
    echo "\n<!-- Twitter -->\n";
    echo "<meta property=\"twitter:card\" content=\"summary_large_image\">\n";
    echo "<meta property=\"twitter:url\" content=\"{$meta['canonical_url']}\">\n";
    echo "<meta property=\"twitter:title\" content=\"{$meta['site_name']}\">\n";
    echo "<meta property=\"twitter:description\" content=\"{$meta['site_description']}\">\n";
    echo "<meta property=\"twitter:image\" content=\"{$meta['canonical_url']}{$meta['og_image']}\">\n";
    echo "<meta property=\"twitter:creator\" content=\"{$meta['twitter_handle']}\">\n";
    
    echo "\n<!-- Additional SEO Tags -->\n";
    echo "<meta name=\"theme-color\" content=\"#2196f3\">\n";
    echo "<meta name=\"msapplication-TileColor\" content=\"#2196f3\">\n";
    echo "<meta name=\"application-name\" content=\"{$meta['site_name']}\">\n";
    echo "<meta name=\"apple-mobile-web-app-title\" content=\"{$meta['site_name']}\">\n";
    echo "<meta name=\"apple-mobile-web-app-capable\" content=\"yes\">\n";
    echo "<meta name=\"apple-mobile-web-app-status-bar-style\" content=\"default\">\n";
    echo "<meta name=\"mobile-web-app-capable\" content=\"yes\">\n";
}

/**
 * Render structured data (JSON-LD)
 * 
 * @return void
 */
function renderStructuredData() {
    $config = SEO_CONFIG;
    
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => $config['site_name'],
        'description' => $config['site_description'],
        'url' => $config['canonical_url'],
        'applicationCategory' => 'GameApplication',
        'operatingSystem' => 'Web Browser',
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'USD'
        ],
        'author' => [
            '@type' => 'Organization',
            'name' => $config['author']
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $config['author']
        ],
        'potentialAction' => [
            '@type' => 'UseAction',
            'target' => $config['canonical_url'],
            'name' => 'Use Slingo Board Checker'
        ],
        'featureList' => [
            '5x5 Slingo board analysis',
            'Optimal wild card placement recommendations',
            'Super wild card strategy',
            'Real-time board state tracking',
            'Strategic game analysis'
        ]
    ];
    
    echo "\n<!-- Structured Data (JSON-LD) -->\n";
    echo "<script type=\"application/ld+json\">\n";
    echo json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n</script>\n";
}

/**
 * Render additional SEO elements
 * 
 * @return void
 */
function renderAdditionalSEO() {
    $config = SEO_CONFIG;
    
    echo "\n<!-- Additional SEO Elements -->\n";
    echo "<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n";
    echo "<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
    echo "<link rel=\"dns-prefetch\" href=\"//www.googletagmanager.com\">\n";
    echo "<link rel=\"dns-prefetch\" href=\"//fonts.googleapis.com\">\n";
    echo "<link rel=\"dns-prefetch\" href=\"//fonts.gstatic.com\">\n";
    
    // Favicon and app icons
    echo "<link rel=\"icon\" type=\"image/x-icon\" href=\"/favicon.ico\">\n";
    echo "<link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/apple-touch-icon.png\">\n";
    echo "<link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"/favicon-32x32.png\">\n";
    echo "<link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"/favicon-16x16.png\">\n";
    echo "<link rel=\"manifest\" href=\"/site.webmanifest\">\n";
}

/**
 * Get page title for specific pages
 * 
 * @param string $page The page identifier
 * @return string The formatted page title
 */
function getPageTitle($page = 'home') {
    $titles = [
        'home' => 'Slingo Board Checker - Optimal Wild Card Strategy Tool',
        'about' => 'About Slingo Board Checker - Game Strategy Tool',
        'help' => 'Help - How to Use Slingo Board Checker',
        'privacy' => 'Privacy Policy - Slingo Board Checker',
        'terms' => 'Terms of Service - Slingo Board Checker'
    ];
    
    return $titles[$page] ?? $titles['home'];
}

/**
 * Get page description for specific pages
 * 
 * @param string $page The page identifier
 * @return string The page description
 */
function getPageDescription($page = 'home') {
    $descriptions = [
        'home' => 'Get optimal wild card placement recommendations for your Slingo game. Analyze your 5x5 board and maximize your chances of completing Slingos with strategic wild and super wild placements.',
        'about' => 'Learn about the Slingo Board Checker tool and how it helps players optimize their wild card strategy for maximum Slingo completion.',
        'help' => 'Complete guide on how to use the Slingo Board Checker tool to improve your game strategy and wild card placement decisions.',
        'privacy' => 'Privacy policy for Slingo Board Checker - how we collect, use, and protect your data.',
        'terms' => 'Terms of service for using the Slingo Board Checker tool and website.'
    ];
    
    return $descriptions[$page] ?? $descriptions['home'];
}
?>
