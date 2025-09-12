# Slingo Board Checker - Implementation Rules & Clarifications

## Testing Requirements (MANDATORY)

### Unit Tests Required
- **Calculation Algorithms**: All mathematical calculations must have unit tests
- **Slingo Detection**: Test horizontal, vertical, and diagonal line completion
- **Wild Card Optimization**: Test all placement strategies and scoring
- **Probability Calculations**: Test expected value and success rate calculations
- **Board State Management**: Test covered position tracking and validation

### Integration Tests Required
- **API Endpoints**: Test all POST requests to `/api/analyze.php`
- **Error Handling**: Test invalid inputs, malformed JSON, missing fields
- **Response Format**: Verify JSON structure matches specification exactly
- **CORS Headers**: Test cross-origin requests work correctly

### Performance Tests Required
- **Response Time**: AJAX responses must be < 2 seconds
- **UI Responsiveness**: Interactions must respond < 100ms
- **Mobile Performance**: Test on actual mobile devices
- **Load Testing**: Test with multiple concurrent users

## Core Algorithm Requirements

### Slingo Detection Logic
- **Horizontal Lines**: Check all 5 rows for complete coverage
- **Vertical Lines**: Check all 5 columns for complete coverage  
- **Diagonal Lines**: Check both main diagonal (0,0 to 4,4) and anti-diagonal (0,4 to 4,0)
- **Wild Card Support**: Wilds and super wilds can complete any line

### Wild Card Optimization
- **Priority System**: 
  1. Complete existing Slingos (highest priority)
  2. Setup moves for future Slingos
  3. Cover isolated cells for points
- **Scoring Algorithm**:
  - Slingo completion: 25 points each
  - Wild card bonus: 10 points each
  - Super wild bonus: 15 points each
  - Setup moves: 2 points per covered cell

### Board Number Generation
- **Column Ranges**: 
  - Column 1: 1-15
  - Column 2: 16-30
  - Column 3: 31-45
  - Column 4: 46-60
  - Column 5: 61-75
- **Random Generation**: Each cell gets random number within its column range

## API Specification Compliance

### Request Format (EXACT)
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

### Response Format (EXACT)
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

## UI/UX Requirements

### Board Interaction
- **Click to Toggle**: Single click toggles covered/uncovered state
- **Visual Feedback**: Covered cells show green background with checkmark
- **Hover Effects**: Cells scale up and show shadow on hover
- **Reset Functionality**: One-click reset clears all selections

### Draw Configuration
- **Row Management**: Add/remove rows (min 1, max 3)
- **Position Options**: Dropdown with "none", "wild", "super_wild"
- **Validation**: Prevent invalid configurations
- **Dynamic Updates**: Real-time validation and feedback

### Results Display
- **Loading States**: Show spinner during analysis
- **Error Handling**: Clear error messages for failures
- **Score Display**: Highlight expected scores prominently
- **Reasoning**: Explain why each recommendation was made

## Security Requirements

### Input Validation
- **JSON Validation**: Verify proper JSON structure
- **Data Sanitization**: Clean all user inputs
- **Range Checking**: Validate array bounds and values
- **Type Checking**: Ensure correct data types

### Headers Required
- **CORS**: Allow cross-origin requests
- **Content-Type**: application/json for API responses
- **Security**: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection

## Performance Requirements

### Response Times
- **AJAX Analysis**: < 2 seconds for optimization
- **UI Interactions**: < 100ms for clicks/hovers
- **Page Load**: < 3 seconds initial load
- **Mobile**: Smooth performance on mobile devices

### Optimization
- **Algorithm Efficiency**: O(n) complexity where possible
- **Memory Usage**: Minimal memory footprint
- **Caching**: Cache calculations when possible
- **Progressive Loading**: Show partial results for complex calculations

## Error Handling

### Client-Side Errors
- **Network Failures**: Retry mechanism with user feedback
- **Invalid Inputs**: Real-time validation with clear messages
- **Browser Compatibility**: Graceful degradation for older browsers

### Server-Side Errors
- **PHP Errors**: Log errors, return user-friendly messages
- **Invalid Requests**: 400 status with specific error details
- **Server Overload**: 503 status with retry instructions
- **Data Validation**: 422 status for validation errors

## Mobile Requirements

### Responsive Design
- **Touch Targets**: Minimum 44px touch targets
- **Viewport**: Proper viewport meta tag
- **Scrolling**: Smooth scrolling on all devices
- **Orientation**: Works in portrait and landscape

### Performance
- **Touch Response**: < 100ms touch response time
- **Smooth Animations**: 60fps animations
- **Memory Usage**: Efficient memory management
- **Battery**: Minimal battery drain

## Testing Strategy

### Unit Test Coverage
- **Minimum 90%**: Code coverage for all classes
- **Edge Cases**: Test boundary conditions
- **Error Paths**: Test all error scenarios
- **Mocking**: Mock external dependencies

### Integration Test Coverage
- **API Endpoints**: Test all request/response combinations
- **Database**: Test data persistence (if added)
- **External Services**: Test third-party integrations
- **End-to-End**: Test complete user workflows

### Browser Testing
- **Chrome**: Latest 2 versions
- **Firefox**: Latest 2 versions  
- **Safari**: Latest 2 versions
- **Edge**: Latest 2 versions
- **Mobile**: iOS Safari, Android Chrome

## Code Quality Standards

### PHP Standards
- **PSR-12**: Follow PSR-12 coding standards
- **Documentation**: PHPDoc for all public methods
- **Error Handling**: Try-catch blocks for all operations
- **Type Hints**: Use type hints where possible

### JavaScript Standards
- **ES6+**: Use modern JavaScript features
- **JSDoc**: Document all functions
- **Error Handling**: Proper error handling and logging
- **Performance**: Optimize for performance

### CSS Standards
- **BEM**: Use BEM naming convention
- **Responsive**: Mobile-first responsive design
- **Accessibility**: WCAG 2.1 AA compliance
- **Performance**: Optimize CSS for loading speed

## Deployment Requirements

### Production Readiness
- **Error Logging**: Comprehensive error logging
- **Monitoring**: Application performance monitoring
- **Backup**: Regular data backups
- **Security**: Regular security updates

### Environment Configuration
- **Development**: Debug mode enabled
- **Staging**: Production-like environment
- **Production**: Optimized for performance and security
- **Configuration**: Environment-specific settings
