# Slingo Board Checker - Technical Specification

## Overview
A PHP web application that allows users to interact with a Slingo board, mark covered spots, configure draw options, and receive optimal wild card placement recommendations via AJAX.

## Core Requirements

### 1. Slingo Board Interface
- **Board Layout**: 5x5 grid representing a standard Slingo board
- **Interactive Cells**: Each cell should be clickable/toggleable
- **Visual States**: 
  - Uncovered: Default state (empty or with number)
  - Covered: Visually distinct (checkmark, different color, strikethrough)
  - Hover effect for better UX
- **Board Reset**: Button to clear all selections

### 2. Draw Configuration Panel
- **Variable Rows**: 1-3 configurable draw rows
- **Row Controls**: 
  - Add/Remove row buttons (min 1, max 3)
  - Each row has 5 positions corresponding to board columns
- **Draw Options per Position**:
  - `none` (default)
  - `wild`
  - `super wild`
- **Selection Method**: Dropdown, radio buttons, or toggle buttons

### 3. AJAX Submission System
- **Submit Button**: Triggers optimization calculation
- **Data Payload**:
  ```json
  {
    "board_state": {
      "covered_positions": [[row, col], [row, col], ...],
      "board_numbers": [[5x5 array of numbers]]
    },
    "draws": [
      {
        "row": 1,
        "positions": ["none", "wild", "none", "super_wild", "none"]
      }
    ]
  }
  ```
- **Response Format**:
  ```json
  {
    "status": "success",
    "optimal_selections": [
      {
        "row": 1,
        "positions": ["none", "wild", "none", "super_wild", "none"],
        "expected_score": 85.5,
        "reasoning": "This combination maximizes Slingo potential"
      }
    ],
    "analysis": {
      "current_slingos": 2,
      "potential_slingos": 4,
      "probability_breakdown": {...}
    }
  }
  ```

## Technical Implementation

### Frontend (HTML/CSS/JavaScript)

#### HTML Structure
```html
<div id="slingo-container">
  <div id="slingo-board">
    <!-- 5x5 grid of clickable cells -->
  </div>
  
  <div id="draw-configuration">
    <div id="draw-rows">
      <!-- Dynamic draw rows -->
    </div>
    <button id="add-row">Add Draw Row</button>
    <button id="remove-row">Remove Row</button>
  </div>
  
  <div id="controls">
    <button id="reset-board">Reset Board</button>
    <button id="submit-analysis">Get Optimal Strategy</button>
  </div>
  
  <div id="results">
    <!-- AJAX response display -->
  </div>
</div>
```

#### CSS Requirements
- Responsive grid layout for the board
- Clear visual distinction between covered/uncovered cells
- Intuitive styling for draw option selectors
- Loading states and animations
- Mobile-friendly design

#### JavaScript Functionality
```javascript
// Core functions needed:
- initializeBoard()
- toggleCell(row, col)
- addDrawRow()
- removeDrawRow()
- updateDrawOption(row, position, value)
- submitAnalysis()
- displayResults(data)
- resetBoard()
```

### Backend (PHP)

#### File Structure
```
/slingo-checker/
├── index.php (main interface)
├── api/
│   └── analyze.php (AJAX endpoint)
├── classes/
│   ├── SlingoBoard.php
│   ├── SlingoAnalyzer.php
│   └── DrawConfiguration.php
├── js/
│   └── slingo.js
├── css/
│   └── slingo.css
└── config/
    └── config.php
```

#### Core PHP Classes

**SlingoBoard.php**
```php
class SlingoBoard {
    private $board = [];
    private $coveredPositions = [];
    
    public function setCoveredPositions($positions) {}
    public function isCovered($row, $col) {}
    public function getCurrentSlingos() {}
    public function getPotentialSlingos($draws) {}
}
```

**SlingoAnalyzer.php**
```php
class SlingoAnalyzer {
    public function analyzeOptimalStrategy($board, $draws) {}
    public function calculateExpectedValue($configuration) {}
    public function generateRecommendations() {}
    private function simulateOutcomes() {}
}
```

**DrawConfiguration.php**
```php
class DrawConfiguration {
    private $rows = [];
    
    public function addRow($positions) {}
    public function validateConfiguration() {}
    public function getOptimalWildPlacements($board) {}
}
```

#### API Endpoint (/api/analyze.php)
```php
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $board = new SlingoBoard();
    $board->setCoveredPositions($input['board_state']['covered_positions']);
    
    $analyzer = new SlingoAnalyzer();
    $results = $analyzer->analyzeOptimalStrategy($board, $input['draws']);
    
    echo json_encode([
        'status' => 'success',
        'optimal_selections' => $results['recommendations'],
        'analysis' => $results['analysis']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
```

## Algorithm Requirements

### Optimization Logic
The analyzer should determine where to place existing wilds for maximum benefit:
- **Wild Cards**: Given a wild in column X, determine which row to target for optimal Slingo completion
- **Super Wilds**: Given a super wild, determine the exact row/column position to mark for maximum score
- **Priority System**: Prioritize Slingo completions, then setup moves for future draws
- **Scoring Algorithm**: Calculate expected point improvement for each possible placement

### Placement Strategy
- **Slingo Completion**: Prioritize moves that complete horizontal, vertical, or diagonal Slingos
- **Setup Moves**: Consider placements that create future opportunities
- **Risk vs Reward**: Balance guaranteed points vs potential high-value completions
- **Multi-Wild Coordination**: When multiple wilds are available, coordinate their placement for maximum synergy

## User Experience Requirements

### Interaction Flow
1. User loads page with default 5x5 Slingo board
2. User clicks cells to mark them as covered
3. User configures 1-3 draw rows with wild/super wild options
4. User clicks "Get Optimal Strategy"
5. AJAX call processes the configuration
6. Results display optimal wild placements with reasoning

### Error Handling
- Invalid board states
- Network connectivity issues
- Server-side calculation errors
- User input validation

### Performance Requirements
- AJAX response time < 2 seconds
- Smooth UI interactions (< 100ms response)
- Mobile-responsive design
- Progressive loading for complex calculations

## Security Considerations
- Input validation and sanitization
- Rate limiting on API endpoint
- CSRF protection for form submissions
- SQL injection prevention (if using database)

## Testing Requirements
- Unit tests for calculation algorithms
- Integration tests for API endpoints
- Cross-browser compatibility testing
- Mobile device testing
- Load testing for concurrent users

## Future Enhancements
- Save/load board configurations
- Historical analysis tracking
- Multiple board size support
- Advanced probability visualizations
- User accounts and preferences