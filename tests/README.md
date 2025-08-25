# Test Coverage for TLB Pricing Application

This directory contains comprehensive test coverage for the TLB Pricing application.

## Test Structure

### Service Tests
- **PricingCalculatorTest** - Tests the core pricing calculation logic
  - Constructor initialization
  - Day calculations with various input combinations
  - Pricing calculations
  - Factor handling and edge cases

- **BusinessRuleValidatorTest** - Tests business rule validation
  - Project type compatibility
  - Feature conflicts and dependencies
  - Business rule warnings

- **PricingEngineTest** - Tests the main pricing orchestration
  - Configuration validation
  - Complete estimate generation
  - Service integration

### Controller Tests
- **EstimatorControllerTest** - Tests the main controller
  - Controller instantiation
  - Form handling
  - Business logic integration

## Running Tests

### Run All Tests
```bash
vendor/bin/phpunit
```

### Run Tests with TestDox Output
```bash
vendor/bin/phpunit --testdox
```

### Run Specific Test Suite
```bash
vendor/bin/phpunit tests/Service/
vendor/bin/phpunit tests/Controller/
```

### Run Specific Test Class
```bash
vendor/bin/phpunit tests/Service/PricingCalculatorTest.php
```

## Test Coverage Summary

- **Total Tests**: 35
- **Total Assertions**: 158
- **Test Categories**: 4
- **Coverage Areas**:
  - Core pricing calculations
  - Business rule validation
  - Service orchestration
  - Controller logic
  - Edge cases and error handling

## Test Configuration

The tests use PHPUnit 12.3.6 and are configured in `phpunit.dist.xml`. The test environment is set to use the test configuration and database.

## Notes

- All tests are currently passing
- Some PHP warnings exist for undefined array keys in edge cases (these are handled gracefully)
- Tests use mocks for external dependencies to ensure isolation
- Test data is realistic and covers various business scenarios
