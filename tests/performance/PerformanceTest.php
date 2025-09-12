<?php

class PerformanceTest extends PHPUnit\Framework\TestCase {
    
    private $baseUrl = 'http://localhost:8000';
    
    public function testApiResponseTime() {
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
        
        $startTime = microtime(true);
        $response = file_get_contents($url, false, $context);
        $endTime = microtime(true);
        
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Should be under 2 seconds as per spec
        $this->assertLessThan(2000, $responseTime, "API response time was {$responseTime}ms, should be under 2000ms");
    }
    
    public function testConcurrentRequests() {
        $url = $this->baseUrl . '/api/analyze.php';
        $concurrentRequests = 5;
        $processes = [];
        
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
        
        // Simulate concurrent requests using curl
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            
            $processes[] = $ch;
        }
        
        // Execute all requests
        $multiHandle = curl_multi_init();
        foreach ($processes as $ch) {
            curl_multi_add_handle($multiHandle, $ch);
        }
        
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);
        
        // Clean up
        foreach ($processes as $ch) {
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }
        curl_multi_close($multiHandle);
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        // All concurrent requests should complete within reasonable time
        $this->assertLessThan(10000, $totalTime, "Concurrent requests took {$totalTime}ms, should be under 10000ms");
    }
    
    public function testMemoryUsage() {
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
        
        // Check that response is reasonable size (not too large)
        $responseSize = strlen($response);
        $this->assertLessThan(10000, $responseSize, "Response size was {$responseSize} bytes, should be under 10KB");
    }
    
    public function testLargeBoardData() {
        $url = $this->baseUrl . '/api/analyze.php';
        
        // Create a board with many covered positions
        $coveredPositions = [];
        for ($i = 0; $i < 20; $i++) {
            $coveredPositions[] = [rand(0, 4), rand(0, 4)];
        }
        
        $data = [
            'board_state' => [
                'covered_positions' => $coveredPositions,
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
                    'positions' => ['wild', 'wild', 'wild', 'wild', 'wild']
                ],
                [
                    'row' => 2,
                    'positions' => ['super_wild', 'super_wild', 'super_wild', 'super_wild', 'super_wild']
                ],
                [
                    'row' => 3,
                    'positions' => ['wild', 'super_wild', 'wild', 'super_wild', 'wild']
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
        
        $responseTime = ($endTime - $startTime) * 1000;
        
        // Should still be under 2 seconds even with complex data
        $this->assertLessThan(2000, $responseTime, "Complex data response time was {$responseTime}ms, should be under 2000ms");
        
        $responseData = json_decode($response, true);
        $this->assertArrayHasKey('status', $responseData);
    }
    
    public function testStressTest() {
        $url = $this->baseUrl . '/api/analyze.php';
        $iterations = 10;
        $totalTime = 0;
        $successfulRequests = 0;
        
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
        
        for ($i = 0; $i < $iterations; $i++) {
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
            
            $responseTime = ($endTime - $startTime) * 1000;
            $totalTime += $responseTime;
            
            $responseData = json_decode($response, true);
            if ($responseData && isset($responseData['status'])) {
                $successfulRequests++;
            }
            
            // Small delay between requests
            usleep(100000); // 100ms
        }
        
        $averageTime = $totalTime / $iterations;
        $successRate = ($successfulRequests / $iterations) * 100;
        
        // Average response time should be reasonable
        $this->assertLessThan(1000, $averageTime, "Average response time was {$averageTime}ms, should be under 1000ms");
        
        // Success rate should be high
        $this->assertGreaterThan(90, $successRate, "Success rate was {$successRate}%, should be over 90%");
    }
}
