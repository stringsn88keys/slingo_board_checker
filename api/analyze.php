<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Only POST requests are accepted.'
    ]);
    exit();
}

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate JSON input
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON input: ' . json_last_error_msg()
    ]);
    exit();
}

// Validate required fields
if (!isset($data['board_state']) || !isset($data['draws'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: board_state and draws are required'
    ]);
    exit();
}

// Validate board_state structure
if (!isset($data['board_state']['covered_positions']) || !isset($data['board_state']['board_numbers'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid board_state: covered_positions and board_numbers are required'
    ]);
    exit();
}

// Validate draws structure
if (!is_array($data['draws']) || empty($data['draws'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid draws: must be a non-empty array'
    ]);
    exit();
}

try {
    // Include required classes
    require_once '../classes/SlingoBoard.php';
    require_once '../classes/SlingoAnalyzer.php';
    require_once '../classes/DrawConfiguration.php';
    
    // Create board instance
    $board = new SlingoBoard($data['board_state']['board_numbers']);
    $board->setCoveredPositions($data['board_state']['covered_positions']);
    
    // Create analyzer
    $analyzer = new SlingoAnalyzer();
    
    // Perform analysis
    $results = $analyzer->analyzeOptimalStrategy(
        $data['board_state'],
        $data['draws']
    );
    
    // Return successful response
    echo json_encode([
        'status' => 'success',
        'optimal_selections' => $results['recommendations'],
        'analysis' => $results['analysis']
    ]);
    
} catch (Exception $e) {
    // Log error (in production, you might want to log to a file)
    error_log("Slingo Analyzer Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Analysis failed: ' . $e->getMessage()
    ]);
}
?>
