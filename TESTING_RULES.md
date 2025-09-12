# Testing and Algorithm Rules for Slingo Board Checker

## Testing Requirements

### Rule: Always Add Tests for Additional Algorithm Logic

When implementing new algorithm logic or modifying existing algorithms, **ALWAYS** add comprehensive tests to the test suite. This includes:

1. **Unit tests** for individual components and methods
2. **Integration tests** for end-to-end algorithm behavior  
3. **Edge case tests** for boundary conditions and error scenarios
4. **Performance tests** for computationally intensive algorithms

### Test Coverage Requirements

#### 1. Priority System Tests
- **Priority 1**: Completed Slingos get highest priority (score >10,000)
- **Priority 2**: Diagonal Slingos prioritized over horizontal/vertical when multiple completions possible
- **Priority 3**: Setup moves when no Slingos can be completed (maximize potential future Slingos)

#### 2. Depth-First Search Algorithm Tests
- **Combination Generation**: Verify all valid placement combinations are generated
- **Constraint Handling**: Wilds constrained to specific columns, super wilds can go anywhere
- **Optimal Selection**: Best combination selected based on priority system

#### 3. Edge Case Tests
- **No Valid Positions**: Handle cases where wilds cannot be placed in their required columns
- **Full Boards**: Handle boards with minimal available positions
- **Multiple Draw Configurations**: Each draw row evaluated independently

## Algorithm Logic Rules

### Priority System Implementation

```
Priority 1: Completed Slingos (Score: 10,000+ per Slingo)
├── If multiple Slingos can be completed
│   └── Priority 2: Diagonal > Horizontal/Vertical (+1,000 bonus)
└── If no Slingos can be completed
    └── Priority 3: Setup Moves (Score: 1,000-9,999)
        ├── Maximize places in any single potential Slingo
        ├── Count potential Slingos with >1 place filled
        └── Use total filled positions as tie-breaker
```

### Depth-First Search Implementation

```
1. Generate all uncovered board positions
2. For each wild:
   ├── Find all valid positions in its required column
   └── Use DFS to try each position recursively
3. For each super wild:
   ├── Find all remaining uncovered positions  
   └── Use DFS to try each position recursively
4. Evaluate each complete combination using priority system
5. Return highest-scoring combination
```

### Constraint Rules

- **Wilds**: Must be placed in their designated column (column index from draw configuration)
- **Super Wilds**: Can be placed in any uncovered position on the board
- **No Conflicts**: Multiple wilds/super wilds cannot occupy the same position
- **Valid Positions**: Only uncovered board positions are available for placement

## Example Test Cases

### Test Case 1: Priority System Verification
```php
// Board: [[1,1], [2,4], [3,3], [4,4]] covered
// Draw: [super_wild, wild, none, none, none]
// Expected: Complete main diagonal (Priority 1 + 2)
// Result: Super wild at [5,5], Wild at [2,2]
```

### Test Case 2: Diagonal Priority
```php
// Board: Row 1 needs [0,1], Main diagonal needs [4,4]  
// Draw: [super_wild, none, none, none, none]
// Expected: Choose diagonal over row completion
// Result: Super wild at [5,5] (not [1,2])
```

### Test Case 3: Setup Moves
```php
// Board: Minimal coverage, no completable Slingos
// Draw: [super_wild, wild, none, none, none]
// Expected: Maximize potential Slingo places
// Result: Prioritize diagonal or high-coverage lines
```

## Continuous Testing

- Run tests after ANY algorithm modifications
- Add new tests for new features or bug fixes
- Maintain 100% test pass rate before committing changes
- Document expected behavior in test descriptions

## Test File Locations

- **Unit Tests**: `tests/unit/`
  - `SimpleSlingoAnalyzerTest.php` - Priority system and DFS tests
  - `DrawConfigurationTest.php` - Algorithm logic tests
  - `SlingoBoardTest.php` - Board state tests

- **Integration Tests**: `tests/integration/`
- **Performance Tests**: `tests/performance/`

## Running Tests

```bash
# Run all tests
php tests/simple_test_runner.php

# Run specific test file
php tests/simple_test_runner.php tests/unit/SimpleSlingoAnalyzerTest.php

# Run with PHPUnit (if available)
vendor/bin/phpunit tests/
```
