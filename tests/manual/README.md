# Manual Tests

This directory contains manual test scripts for exploring and validating specific behaviors of the Slingo Board Checker system. These tests are designed for development and debugging purposes.

## Test Files

### Core Functionality Tests

#### `multiple_draw_rows_test.php`
- **Purpose**: Tests basic multiple draw row processing
- **Validates**: Each draw row is processed independently and all wilds are placed
- **Usage**: `php tests/manual/multiple_draw_rows_test.php`

#### `api_multiple_rows_test.php`
- **Purpose**: Tests multiple draw rows through the SlingoAnalyzer (API path)
- **Validates**: API correctly processes all draw rows and returns proper JSON format
- **Usage**: `php tests/manual/api_multiple_rows_test.php`

#### `cumulative_board_state_test.php`
- **Purpose**: Shows cumulative board state after applying all wild placements
- **Validates**: Visual representation of final board with all wilds placed
- **Usage**: `php tests/manual/cumulative_board_state_test.php`

### Performance & Algorithm Tests

#### `tile_heuristic_test.php`
- **Purpose**: Tests tile placement heuristic for memory optimization
- **Validates**: Heuristic activates with 5+ super wilds and completes in <2 seconds
- **Usage**: `php tests/manual/tile_heuristic_test.php`

#### `multiple_draw_configurations_test.php`
- **Purpose**: Compares direct vs API methods with various draw configurations
- **Validates**: Consistency between different access methods
- **Usage**: `php tests/manual/multiple_draw_configurations_test.php`

## Running Manual Tests

### Individual Tests
```bash
# Test specific functionality
php tests/manual/multiple_draw_rows_test.php
php tests/manual/cumulative_board_state_test.php
php tests/manual/tile_heuristic_test.php
```

### All Manual Tests
```bash
# Run all manual tests
for test in tests/manual/*_test.php; do
    echo "Running $test..."
    php "$test"
    echo "----------------------------------------"
done
```

## Test Categories

### ✅ Validation Tests
- Verify that multiple draw rows are processed correctly
- Confirm all wilds are placed as expected
- Validate API response format and structure

### 🔍 Exploratory Tests  
- Visual board state representation
- Performance analysis with large wild counts
- Algorithm behavior comparison

### 🚀 Performance Tests
- Memory optimization validation
- Execution time verification
- Scalability testing

## Expected Outputs

All tests should show:
- ✅ **SUCCESS** messages for correct behavior
- 📊 **Summary** statistics matching expectations
- 🎯 **Detailed** placement information
- 🔍 **Analysis** of strategic decisions

## Integration with Main Test Suite

These manual tests complement the main automated test suite (`tests/simple_test_runner.php`) by providing:
- Visual confirmation of correct behavior
- Performance validation under various conditions
- Detailed analysis for debugging complex scenarios

## Development Usage

Use these tests during development to:
1. **Debug** specific issues with wild placement
2. **Validate** new algorithm implementations
3. **Verify** performance optimizations
4. **Explore** edge cases and unusual scenarios

## Notes

- All tests use relative paths (`../../classes/`) to work from the tests directory
- Tests are designed to be self-contained and require no external dependencies
- Output includes both summary statistics and detailed analysis
- Tests can be run independently or as part of debugging workflows
