# GitHub Actions CI/CD Setup

This document describes the continuous integration and deployment setup for the Slingo Board Checker project.

## Overview

The project uses GitHub Actions to automatically test code changes on every push and pull request. We have three complementary workflows that provide different levels of testing coverage.

## Workflows

### 1. Main CI Workflow (`.github/workflows/ci.yml`)

**Purpose**: Fast feedback loop for developers
**Triggers**: Push to main/develop/simplify-heuristic branches, PRs to main/develop
**PHP Version**: 8.1 (primary development version)

**What it does**:
- Syntax check all PHP files
- Run the complete automated test suite
- Test application startup with built-in server
- Basic compatibility test across PHP 7.4, 8.0, 8.3

**Execution time**: ~2-3 minutes

### 2. Comprehensive Test Suite (`.github/workflows/tests.yml`)

**Purpose**: Thorough validation across environments
**Triggers**: Same as main CI
**PHP Versions**: 7.4, 8.0, 8.1, 8.2, 8.3 (matrix build)

**What it does**:
- Full compatibility testing across PHP versions
- Code quality checks (if phpcs/phpstan configs exist)
- Security vulnerability scanning
- Deployment simulation with real HTTP requests
- Manual test validation

**Execution time**: ~8-12 minutes

### 3. Quick Tests (`.github/workflows/quick-tests.yml`)

**Purpose**: Minimal validation for rapid iteration
**Triggers**: Same as others
**PHP Version**: 8.1

**What it does**:
- Basic syntax validation
- Core test suite execution
- Simple application startup test

**Execution time**: ~1-2 minutes

## Test Coverage

### Automated Tests
- **Unit Tests**: Individual class and method testing
- **Integration Tests**: API endpoint and component interaction
- **Performance Tests**: Execution speed and memory validation

### Manual Test Validation
- Ensures all manual/exploratory tests can execute
- Validates complex scenarios like multi-row wild placement
- Non-blocking (continues on error)

### Application Testing
- **Startup Test**: Verifies PHP built-in server can start
- **Main Page**: Validates index.php loads without errors
- **API Endpoint**: Tests analyze.php with sample data

## Local Development

Before pushing changes, run the same checks locally:

```bash
# Syntax check
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;

# Run automated tests
php tests/simple_test_runner.php

# Validate manual tests
php tests/manual/run_manual_tests.php

# Test application startup
php -S localhost:8000 -t .
curl http://localhost:8000/index.php
```

## Configuration

### Dependencies
- Workflows handle projects with or without Composer dependencies
- Automatic detection of composer.json and conditional installation
- Optimized autoloader for better performance

### Caching
- Composer cache for faster dependency installation
- Separate cache keys per PHP version and OS

### Error Handling
- Manual tests are non-blocking (continue-on-error: true)
- Code quality checks are informational only
- Security scans continue on error but report issues

## Status Badges

Add this badge to your README.md to show CI status:

```markdown
[![CI](https://github.com/stringsn88keys/slingo_board_checker/workflows/CI/badge.svg)](https://github.com/stringsn88keys/slingo_board_checker/actions)
```

## Troubleshooting

### Common Issues

1. **Composer not found**: Workflows check for composer.json existence
2. **Test failures**: Check local test execution first
3. **Timeout issues**: Performance tests validate execution speed

### Debug Steps

1. Check the Actions tab in GitHub repository
2. Review specific job logs for detailed error information
3. Run tests locally with same PHP version
4. Validate workflow syntax with `./validate-workflows.sh`

## Future Enhancements

- [ ] Code coverage reporting
- [ ] Deployment to staging environment
- [ ] Performance regression detection
- [ ] Automated dependency updates with Dependabot
- [ ] Slack/Discord notifications for build status

## Maintenance

- Workflows are designed to be low-maintenance
- PHP version matrix should be updated annually
- GitHub Actions versions are pinned to major versions for stability
- Review and update dependencies quarterly
