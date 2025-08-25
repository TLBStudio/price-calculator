#!/bin/bash

# TLB Pricing Test Runner
# Usage: ./run-tests.sh [option]

echo "TLB Pricing Test Runner"
echo "======================="

case "${1:-all}" in
    "all")
        echo "Running all tests..."
        vendor/bin/phpunit --testdox
        ;;
    "quick")
        echo "Running tests without testdox..."
        vendor/bin/phpunit
        ;;
    "services")
        echo "Running service tests only..."
        vendor/bin/phpunit tests/Service/ --testdox
        ;;
    "controllers")
        echo "Running controller tests only..."
        vendor/bin/phpunit tests/Controller/ --testdox
        ;;
    "pricing")
        echo "Running pricing calculator tests..."
        vendor/bin/phpunit tests/Service/PricingCalculatorTest.php --testdox
        ;;
    "business")
        echo "Running business rule validator tests..."
        vendor/bin/phpunit tests/Service/BusinessRuleValidatorTest.php --testdox
        ;;
    "engine")
        echo "Running pricing engine tests..."
        vendor/bin/phpunit tests/Service/PricingEngineTest.php --testdox
        ;;
    "help"|"-h"|"--help")
        echo "Available options:"
        echo "  all       - Run all tests with testdox (default)"
        echo "  quick     - Run all tests without testdox"
        echo "  services  - Run only service tests"
        echo "  controllers - Run only controller tests"
        echo "  pricing   - Run only pricing calculator tests"
        echo "  business  - Run only business rule validator tests"
        echo "  engine    - Run only pricing engine tests"
        echo "  help      - Show this help message"
        ;;
    *)
        echo "Unknown option: $1"
        echo "Use 'help' to see available options"
        exit 1
        ;;
esac
