# Slingo Board Checker

A PHP web application that allows users to interact with a Slingo board, mark covered spots, configure draw options, and receive optimal wild card placement recommendations via AJAX.

## Features

- **Interactive 5x5 Slingo Board**: Click cells to mark them as covered
- **Dynamic Draw Configuration**: Configure 1-3 draw rows with wild/super wild options
- **Real-time Analysis**: Get optimal wild card placement recommendations
- **Responsive Design**: Works on desktop and mobile devices
- **AJAX-powered**: Smooth user experience without page reloads

## Installation

1. **Requirements**:
   - PHP 7.4 or higher
   - Web server (Apache/Nginx)
   - No database required

2. **Setup**:
   ```bash
   # Clone or download the project
   cd slingo_board_checker
   
   # Make sure your web server can access the files
   # Point your document root to this directory
   ```

3. **Configuration**:
   - Edit `config/config.php` if you need to modify settings
   - The application works out of the box with default settings

## Usage

1. **Open the Application**:
   - Navigate to `index.php` in your web browser
   - You'll see a 5x5 Slingo board with random numbers

2. **Mark Covered Cells**:
   - Click on any cell to mark it as covered (green with checkmark)
   - Click again to unmark it

3. **Configure Draw Rows**:
   - Use "Add Draw Row" to add up to 3 draw rows
   - For each position in a row, select:
     - **None**: No wild card
     - **Wild**: Regular wild card
     - **Super Wild**: Super wild card

4. **Get Recommendations**:
   - Click "Get Optimal Strategy" to analyze your board
   - View recommended wild card placements with expected scores
   - See analysis of current and potential Slingos

## File Structure

```
slingo_board_checker/
├── index.php              # Main application interface
├── api/
│   └── analyze.php        # AJAX endpoint for analysis
├── classes/
│   ├── SlingoBoard.php    # Board management class
│   ├── SlingoAnalyzer.php # Analysis and optimization
│   └── DrawConfiguration.php # Draw configuration management
├── js/
│   └── slingo.js          # Frontend JavaScript
├── css/
│   └── slingo.css         # Styling
├── config/
│   └── config.php         # Configuration settings
├── tests/                 # Test suite
│   ├── bootstrap.php      # Test bootstrapping
│   ├── simple_test_runner.php # Main test runner
│   ├── unit/              # Unit tests
│   ├── integration/       # Integration tests
│   ├── performance/       # Performance tests
│   └── manual/            # Manual/exploratory tests
└── README.md              # This file
```

## Testing

The project includes a comprehensive test suite:

### Running Tests

**Automated Test Suite**:
```bash
# Run all automated tests
php tests/simple_test_runner.php

# Run specific test categories
php tests/unit/SlingoBoardTest.php
php tests/integration/ApiTest.php
php tests/performance/PerformanceTest.php
```

**Manual Test Suite**:
```bash
# Run all manual tests (for development/debugging)
php tests/manual/run_manual_tests.php

# Run individual manual tests
php tests/manual/multiple_draw_rows_test.php
php tests/manual/api_multiple_rows_test.php
```

### Test Structure

- **Unit Tests** (`tests/unit/`): Test individual classes and methods
- **Integration Tests** (`tests/integration/`): Test API endpoints and component interaction
- **Performance Tests** (`tests/performance/`): Validate execution speed and memory usage
- **Manual Tests** (`tests/manual/`): Exploratory tests for specific scenarios and debugging

The automated test suite validates core functionality, while manual tests provide detailed output for debugging complex scenarios like multi-row wild placement strategies.

## Continuous Integration

The project includes GitHub Actions workflows for automated testing:

### CI Workflows

- **Main CI** (`.github/workflows/ci.yml`): Fast feedback with PHP 8.1
- **Full Test Suite** (`.github/workflows/tests.yml`): Comprehensive testing across PHP versions 7.4-8.3
- **Quick Tests** (`.github/workflows/quick-tests.yml`): Basic validation for rapid iteration

### What Gets Tested

- **PHP Syntax Check**: Validates all PHP files for syntax errors
- **Automated Test Suite**: Runs all unit, integration, and performance tests
- **Manual Test Validation**: Ensures manual tests can execute without errors
- **Application Startup**: Verifies the web application can start and serve requests
- **API Endpoint Testing**: Validates that the analyze.php endpoint responds correctly

### Running Locally

Before pushing changes, run the same checks locally:

```bash
# Syntax check
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;

# Run test suite
php tests/simple_test_runner.php

# Test manual tests
php tests/manual/run_manual_tests.php

# Start local server for testing
php -S localhost:8000 -t .
```

### CI Status

[![CI](https://github.com/stringsn88keys/slingo_board_checker/workflows/CI/badge.svg)](https://github.com/stringsn88keys/slingo_board_checker/actions)

## API Endpoints

### POST /api/analyze.php

Analyzes board state and returns optimal wild card placements.

**Request Body**:
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

**Response**:
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

## Algorithm

The optimization algorithm considers:

1. **Slingo Completion**: Prioritizes moves that complete horizontal, vertical, or diagonal Slingos
2. **Setup Moves**: Considers placements that create future opportunities
3. **Wild Card Value**: Differentiates between regular wilds and super wilds
4. **Expected Score**: Calculates potential point improvement for each placement

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## Mobile Support

The application is fully responsive and works on:
- iOS Safari 12+
- Android Chrome 60+
- Mobile Firefox 55+

## Security

- Input validation and sanitization
- CORS headers for API access
- XSS protection headers
- No database dependencies (reduces attack surface)

## Performance

- AJAX response time typically < 1 second
- Optimized for mobile devices
- Minimal JavaScript footprint
- Efficient PHP algorithms

## Troubleshooting

**Common Issues**:

1. **AJAX requests failing**:
   - Check that your web server supports PHP
   - Verify file permissions
   - Check browser console for errors

2. **Styling not loading**:
   - Ensure CSS file path is correct
   - Check web server configuration

3. **Analysis not working**:
   - Verify PHP classes are loading correctly
   - Check PHP error logs

## Future Enhancements

- Save/load board configurations
- Historical analysis tracking
- Multiple board size support
- Advanced probability visualizations
- User accounts and preferences

## License

This project is open source. See LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Support

For issues or questions, please check the troubleshooting section above or create an issue in the project repository.
