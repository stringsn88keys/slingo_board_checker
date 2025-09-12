<?php

class ApiTest extends PHPUnit\Framework\TestCase {
    
    private $baseUrl = 'http://localhost:8000';
    
    public function testAnalyzeEndpointExists() {
        $url = $this->baseUrl . '/api/analyze.php';
        
        // Test that endpoint responds (even if with error for GET)
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $this->assertNotFalse($response);
    }
    
    public function testValidPostRequest() {
        $url = $this->baseUrl . '/api/analyze.php';
        
        $data = [
            'board_state' => [
                'covered_positions' => [[0, 0], [0, 1], [0, 3], [0, 4]],
                'board_numbers' => [
                    [1, 16, 31, 46, 61],
                    [2, 17, 32, 47, 62],
                    [3, 18, 33, 48, 63],
                    [4, 19, 34, 49, 64],
                    [5, 20, 35, 50, 65]
                ]
            ],
            'draws' => [
                [
                    'row' => 1,
                    'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']
                ]
            ]
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $this->assertNotFalse($response);
        
        $responseData = json_decode($response, true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('status', $responseData);
    }
    
    public function testSuccessfulAnalysis() {
        $url = $this->baseUrl . '/api/analyze.php';
        
        $data = [
            'board_state' => [
                'covered_positions' => [[0, 0], [0, 1], [0, 3], [0, 4]],
                'board_numbers' => [
                    [1, 16, 31, 46, 61],
                    [2, 17, 32, 47, 62],
                    [3, 18, 33, 48, 63],
                    [4, 19, 34, 49, 64],
                    [5, 20, 35, 50, 65]
                ]
            ],
            'draws' => [
                [
                    'row' => 1,
                    'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']
                ]
            ]
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $responseData = json_decode($response, true);
        
        if ($responseData['status'] === 'success') {
            $this->assertArrayHasKey('optimal_selections', $responseData);
            $this->assertArrayHasKey('analysis', $responseData);
            $this->assertIsArray($responseData['optimal_selections']);
            $this->assertIsArray($responseData['analysis']);
        }
    }
    
    public function testInvalidJsonRequest() {
        $url = $this->baseUrl . '/api/analyze.php';
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => 'invalid json',
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $responseData = json_decode($response, true);
        
        $this->assertEquals('error', $responseData['status']);
        $this->assertArrayHasKey('message', $responseData);
    }
    
    public function testMissingRequiredFields() {
        $url = $this->baseUrl . '/api/analyze.php';
        
        $data = [
            'board_state' => [
                'covered_positions' => [[0, 0], [0, 1]]
                // Missing board_numbers
            ]
            // Missing draws
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $responseData = json_decode($response, true);
        
        $this->assertEquals('error', $responseData['status']);
        $this->assertStringContains('Missing required fields', $responseData['message']);
    }
    
    public function testInvalidBoardState() {
        $url = $this->baseUrl . '/api/analyze.php';
        
        $data = [
            'board_state' => [
                'covered_positions' => 'invalid',
                'board_numbers' => 'invalid'
            ],
            'draws' => []
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $responseData = json_decode($response, true);
        
        $this->assertEquals('error', $responseData['status']);
    }
    
    public function testEmptyDraws() {
        $url = $this->baseUrl . '/api/analyze.php';
        
        $data = [
            'board_state' => [
                'covered_positions' => [[0, 0], [0, 1]],
                'board_numbers' => [
                    [1, 16, 31, 46, 61],
                    [2, 17, 32, 47, 62],
                    [3, 18, 33, 48, 63],
                    [4, 19, 34, 49, 64],
                    [5, 20, 35, 50, 65]
                ]
            ],
            'draws' => []
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $responseData = json_decode($response, true);
        
        $this->assertEquals('error', $responseData['status']);
        $this->assertStringContains('non-empty array', $responseData['message']);
    }
    
    public function testCorsHeaders() {
        $url = $this->baseUrl . '/api/analyze.php';
        
        $context = stream_context_create([
            'http' => [
                'method' => 'OPTIONS',
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        // Check if CORS headers are present in response headers
        $headers = $http_response_header ?? [];
        $corsHeaders = array_filter($headers, function($header) {
            return strpos($header, 'Access-Control-Allow-Origin') !== false;
        });
        
        $this->assertNotEmpty($corsHeaders);
    }
    
    public function testResponseTime() {
        $url = $this->baseUrl . '/api/analyze.php';
        
        $data = [
            'board_state' => [
                'covered_positions' => [[0, 0], [0, 1], [0, 3], [0, 4]],
                'board_numbers' => [
                    [1, 16, 31, 46, 61],
                    [2, 17, 32, 47, 62],
                    [3, 18, 33, 48, 63],
                    [4, 19, 34, 49, 64],
                    [5, 20, 35, 50, 65]
                ]
            ],
            'draws' => [
                [
                    'row' => 1,
                    'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']
                ]
            ]
        ];
        
        $startTime = microtime(true);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $endTime = microtime(true);
        
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Response should be under 2 seconds (2000ms)
        $this->assertLessThan(2000, $responseTime);
    }
    
    public function testMultipleDrawRows() {
        $url = $this->baseUrl . '/api/analyze.php';
        
        $data = [
            'board_state' => [
                'covered_positions' => [[0, 0], [0, 1], [0, 3], [0, 4]],
                'board_numbers' => [
                    [1, 16, 31, 46, 61],
                    [2, 17, 32, 47, 62],
                    [3, 18, 33, 48, 63],
                    [4, 19, 34, 49, 64],
                    [5, 20, 35, 50, 65]
                ]
            ],
            'draws' => [
                [
                    'row' => 1,
                    'positions' => ['wild', 'none', 'super_wild', 'none', 'wild']
                ],
                [
                    'row' => 2,
                    'positions' => ['none', 'wild', 'none', 'wild', 'none']
                ],
                [
                    'row' => 3,
                    'positions' => ['super_wild', 'none', 'wild', 'none', 'super_wild']
                ]
            ]
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $responseData = json_decode($response, true);
        
        if ($responseData['status'] === 'success') {
            $this->assertIsArray($responseData['optimal_selections']);
            $this->assertGreaterThan(0, count($responseData['optimal_selections']));
        }
    }
}
