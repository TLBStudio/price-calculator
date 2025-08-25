# TLB Pricing - Setup & Pricing Configuration Guide

## Table of Contents
1. [Project Setup](#project-setup)
2. [Pricing Configuration](#pricing-configuration)
3. [Calculation Engine Breakdown](#calculation-engine-breakdown)
4. [Configuration Parameters](#configuration-parameters)
5. [Testing](#testing)
6. [Customization Guide](#customization-guide)

## Project Setup

### Prerequisites
- PHP 8.4 or higher
- Composer
- Node.js 18+ and npm
- Docker (optional, for containerized development)

### Installation Steps

1. **Clone and Install Dependencies**
   ```bash
   git clone <repository-url>
   cd tlb-pricing
   composer install
   npm install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   # Edit .env with your database and application settings
   ```

3. **Database Setup** (if using database features)
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

4. **Build Assets**
   ```bash
   npm run build
   # or for development
   npm run watch
   ```

5. **Start Development Server**
   ```bash
   # Using Symfony CLI
   symfony server:start

   # Or using PHP built-in server
   php -S localhost:8000 -t public/
   ```

### Docker Setup (Alternative)
```bash
docker-compose up -d
docker-compose exec php composer install
docker-compose exec php npm install
docker-compose exec php npm run build
```

## Pricing Configuration

The pricing system is configured through `config/packages/pricing.yaml`. This file contains all the parameters that drive the calculation engine.

### Core Configuration Structure
```yaml
parameters:
  pricing:
    # Base rates and factors
    calibration_factor: 1.0
    day_rate:
      min: 630       # £90/hour × 7hr day
      max: 805       # £115/hour × 7hr day

    # Overhead percentages
    contingency: 0.15        # 15% contingency
    project_management: 0.15 # 15% project management

    # Project type definitions
    project_types: {...}

    # Feature definitions
    features: {...}

    # Multiplier definitions
    multipliers: {...}
```

## Calculation Engine Breakdown

The pricing system now uses a clean, service-oriented architecture with dedicated services for each responsibility:

### Core Services
1. **`PricingEngine`** - Main orchestrator (85 lines, simplified from 517)
2. **`PricingCalculator`** - Core pricing calculations
3. **`PhaseCalculator`** - Phase breakdown calculations
4. **`PaymentScheduleCalculator`** - Payment schedule logic
5. **`SupportCalculator`** - Support cost calculations

### Validation Services
1. **`PricingConfigurationValidator`** - Configuration validation
2. **`EstimateInputValidator`** - Input data validation
3. **`BusinessRuleValidator`** - Business rule validation

### Form Services
1. **`FormFieldFactory`** - Form field creation and configuration

### Step-by-Step Calculation Process

#### Step 1: Input Validation
```php
// Validate user input and business rules
$this->inputValidator->validate($input);
$this->businessRuleValidator->validateBusinessRules($input);
```

#### Step 2: Base Day Calculation
```php
// Calculate base days from project type and features
$days = $this->pricingCalculator->calculateDays($input);
```

#### Step 3: Multiplier Application
```php
// Apply complexity, risk, speed, and other multipliers
$days = $this->pricingCalculator->applyMultipliers($days, $input);
```

#### Step 4: Final Calculations
```php
// Calculate pricing, phases, payment schedules, and support
$pricing = $this->pricingCalculator->calculatePricing($days);
$phases = $this->phaseCalculator->calculatePhases($days, $pricing);
$paymentSchedule = $this->paymentScheduleCalculator->calculateSchedule($pricing);
$support = $this->supportCalculator->calculateSupport($pricing);
```

## Testing

The project now includes a comprehensive test suite with **37 tests and 162 assertions** covering all major functionality.

### Running Tests

#### Quick Test Execution
```bash
# Run all tests
./run-tests.sh

# Run specific test suites
./run-tests.sh services
./run-tests.sh controllers
```

#### Standard PHPUnit Commands
```bash
# All tests with detailed output
./vendor/bin/phpunit --testdox

# Specific test directories
./vendor/bin/phpunit tests/Service/
./vendor/bin/phpunit tests/Controller/

# Individual test classes
./vendor/bin/phpunit tests/Service/PricingCalculatorTest.php
```

### Test Coverage

#### Service Layer (100% Coverage)
- **PricingCalculator**: 12 tests covering all calculation scenarios
- **BusinessRuleValidator**: 15 tests covering all business rules
- **PricingEngine**: 7 tests covering orchestration and integration
- **Supporting Services**: Full coverage of phase, payment, and support calculations

#### Controller Layer
- **EstimatorController**: 5 tests covering basic functionality

#### Test Quality Features
- **Descriptive Names**: Clear test method names indicating purpose
- **Proper Mocking**: All dependencies properly mocked
- **Realistic Data**: Tests use actual business scenarios
- **Edge Case Testing**: Boundary conditions and error scenarios
- **Clean Execution**: No PHP warnings or errors

### Test Data and Scenarios

The test suite covers:
- Various project types (web app, mobile app, API)
- Different complexity levels and risk factors
- Feature combinations and conflicts
- Business rule validation scenarios
- Edge cases and missing input handling
- Configuration validation and error conditions

## Configuration Parameters

### Project Types
```yaml
project_types:
  holding_page:
    days: 5
    title: "Holding Page"
    description: "Simple single-page website"

  brochure_site:
    days: 10
    title: "Brochure Site"
    description: "Multi-page informational website"

  ecommerce:
    days: 25
    title: "E-commerce Site"
    description: "Online store with shopping cart"

  web_app:
    days: 20
    title: "Web Application"
    description: "Custom web application"

  mobile_app:
    days: 25
    title: "Mobile Application"
    description: "Native or hybrid mobile app"

  api:
    days: 15
    title: "API Development"
    description: "Backend API service"

  bespoke:
    days: 30
    title: "Bespoke Software"
    description: "Custom software solution"
```

### Features
```yaml
features:
  authentication:
    days: 3
    title: "User Authentication"
    description: "Login, registration, and user management"

  payment_integration:
    days: 4
    title: "Payment Integration"
    description: "Stripe, PayPal, or other payment gateways"

  reporting:
    days: 2
    title: "Reporting System"
    description: "Data analytics and reporting features"

  cms:
    days: 3
    title: "Content Management"
    description: "Admin panel for content editing"

  seo:
    days: 2
    title: "SEO Optimization"
    description: "Search engine optimization features"

  responsive_design:
    days: 2
    title: "Responsive Design"
    description: "Mobile-friendly design implementation"
```

### Multipliers
```yaml
multipliers:
  complexity:
    simple: 0.8
    medium: 1.0
    complex: 1.3

  risk:
    low: 0.9
    medium: 1.0
    high: 1.2

  speed:
    normal: 1.0
    fast: 1.2
    rush: 1.5

  discovery:
    no: 1.0
    yes: 1.05

  support:
    no: 1.0
    yes: 1.1

  compliance:
    basic: 1.0
    advanced: 1.15
    enterprise: 1.3

  real_time:
    no: 1.0
    yes: 1.1
```

### Phase Configuration
```yaml
phases:
  total_base: 0.90
  base_percentages:
    discovery: 0.13
    design: 0.10
    development: 0.55
    testing: 0.12
```

### Payment Schedules
```yaml
payment_schedules:
  thresholds:
    small_project: 500
    medium_project: 3000

  schedules:
    small_project:
      deposit: 0.5
      milestone: 0.25
      completion: 0.25

    medium_project:
      deposit: 0.3
      milestone: 0.4
      completion: 0.3

    large_project:
      deposit: 0.2
      milestone: 0.3
      completion: 0.5
```

### Support Configuration
```yaml
support:
  coefficients:
    small: 0.04
    medium: 0.03
    large: 0.02

  thresholds:
    small: 5000
    medium: 15000

  max_monthly: 900
```

## Customization Guide

### Adding New Project Types
```yaml
project_types:
  ai_integration:
    days: 20
    title: "AI Integration"
    description: "Machine learning and AI features"
```

### Adding New Features
```yaml
features:
  blockchain:
    days: 15
    title: "Blockchain Integration"
    description: "Cryptocurrency or smart contract features"
```

### Adjusting Multipliers
```yaml
multipliers:
  complexity:
    simple: 0.8
    medium: 1.0
    complex: 1.3
    very_complex: 2.0  # New complexity level
```

### Calibration Factor
The calibration factor is a global multiplier that affects all projects:
```yaml
calibration_factor: 1.1  # Increase all estimates by 10%
```

This is useful for:
- Market rate adjustments
- Seasonal pricing changes
- Team capacity adjustments
- Geographic pricing differences

## Testing Your Configuration

After making changes to the pricing configuration:

1. **Clear Cache**
   ```bash
   php bin/console cache:clear
   ```

2. **Run Tests**
   ```bash
   # Verify all tests still pass
   ./vendor/bin/phpunit

   # Run specific service tests
   ./vendor/bin/phpunit tests/Service/PricingCalculatorTest.php
   ```

3. **Test Calculations**
   - Use the web interface to test various project combinations
   - Verify that multipliers are working correctly
   - Check that phase breakdowns add up to 90% (base)
   - Ensure payment schedules are appropriate for your business

4. **Validate Business Logic**
   - Test edge cases (very small/large projects)
   - Verify support cost calculations
   - Check that contingency and overhead are reasonable

## Troubleshooting

### Common Issues

1. **Estimates seem too high/low**
   - Check your day rates in `pricing.yaml`
   - Verify multiplier values are appropriate
   - Adjust calibration factor if needed

2. **Phase percentages don't add up to 100%**
   - This is normal - the system uses a base of 90% with automatic adjustment
   - The Deployment phase is automatically calculated to reach 100%

3. **Support costs seem unreasonable**
   - Verify support coefficients in the configuration
   - Check that the max monthly cap is appropriate for your business
   - Adjust complexity multipliers if needed

4. **Tests failing after configuration changes**
   - Clear the cache: `php bin/console cache:clear`
   - Verify configuration syntax is valid YAML
   - Check that all required configuration sections are present

## Advanced Customization

### Custom Calculation Logic
The pricing system now uses a clean service architecture. To modify calculations:

1. **Add new factors** to the appropriate service class
2. **Modify calculation methods** in the relevant service
3. **Add new output fields** to the service return arrays
4. **Customize validation rules** in the validation services

### Integration with External Systems
The pricing engine can be extended to:
- Pull rates from external APIs
- Integrate with time tracking systems
- Connect to CRM systems for client data
- Export estimates to accounting software

### API Endpoints
Consider adding REST API endpoints for:
- Programmatic estimate generation
- Integration with other business tools
- Mobile applications
- Third-party integrations

## Development Workflow

### Making Changes
1. **Update Configuration**: Modify `config/packages/pricing.yaml`
2. **Run Tests**: Ensure all tests still pass
3. **Test Manually**: Use the web interface to verify changes
4. **Commit Changes**: Include configuration updates in version control

### Testing Changes
1. **Unit Tests**: Verify individual service behavior
2. **Integration Tests**: Test service orchestration
3. **Manual Testing**: Use the web interface with real scenarios
4. **Configuration Validation**: Ensure all configuration is valid

---

This documentation provides a complete understanding of the pricing system architecture, configuration options, and testing capabilities. The comprehensive test suite (37 tests, 162 assertions) ensures reliability and makes the system easy to maintain and extend.

For additional support or questions, refer to the main README, test coverage summary, or create an issue in the project repository.
