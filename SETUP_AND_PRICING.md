# TLB Pricing - Setup & Pricing Configuration Guide

## Table of Contents
1. [Project Setup](#project-setup)
2. [Pricing Configuration](#pricing-configuration)
3. [Calculation Engine Breakdown](#calculation-engine-breakdown)
4. [Configuration Parameters](#configuration-parameters)
5. [Customization Guide](#customization-guide)

## Project Setup

### Prerequisites
- PHP 8.2 or higher
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

The `PricingEngine` service processes estimates through a multi-step calculation process. Here's the complete breakdown:

### Step 1: Factor Initialization
```php
// Initialize all multipliers from user input
$this->factors['complexity'] = $this->pricingConfig['multipliers']['complexity'][$input['complexity']] ?? 1;
$this->factors['risk'] = $this->pricingConfig['multipliers']['risk'][$input['risk']] ?? 1;
$this->factors['speed'] = $this->pricingConfig['multipliers']['speed'][$input['speed']] ?? 1;
$this->factors['discovery'] = $this->pricingConfig['multipliers']['discover'][$input['discovery']] ?? 1.05;
$this->factors['support'] = $this->pricingConfig['multipliers']['support'][$input['support']] ?? 1;
```

**Default Values:**
- Complexity: 1.0 (normal), 1.5 (medium), 2.0 (high), 3.0 (very high)
- Risk: 1.0 (normal), 1.1 (medium), 1.25 (high), 1.5 (very high)
- Speed: 1.0 (normal), 1.25 (tight), 1.5 (urgent)
- Discovery: 1.05 (normal), 1.1 (medium), 1.2 (high)
- Support: 1.0 (normal), 1.25 (medium), 1.5 (high)

### Step 2: Base Day Calculation
```php
// Start with project type base days
$days = floor($this->pricingConfig['project_types'][$input['projectType']]['days'] ?? 0);

// Add feature days
foreach ($input['features'] ?? [] as $f) {
    $days += $this->pricingConfig['features'][$f]['days'] ?? 0;
}
```

**Project Type Base Days:**
- Holding page: 2 days
- Landing page: 4 days
- Brochure site: 6 days
- Ecommerce: 10 days
- Internal portal: 20 days
- Web app: 20 days
- Learning platform: 30 days
- Mobile app: 30 days

**Feature Examples:**
- CMS integration: +8 days
- Ecommerce features: +13 days
- API development: +5 days
- Multi-language: +3 days

### Step 3: Multiplier Application
```php
// Apply all multipliers to base days
$days *= $this->factors['complexity'];
$days *= $this->factors['risk'];
$days *= $this->factors['speed'];
$days *= $this->factors['calibration_factor'];
```

**Calculation Order:**
1. Base days × Complexity multiplier
2. × Risk multiplier
3. × Speed multiplier
4. × Calibration factor (configurable global adjustment)

### Step 4: Cost Calculation
```php
// Calculate base costs using day rates
$low = $days * $this->rates['min'];   // £630/day
$high = $days * $this->rates['max'];  // £805/day

// Apply overhead percentages
$low *= 1 + $this->factors['project_management'];  // +15%
$high *= 1 + $this->factors['project_management']; // +15%

// Apply discovery factor
$low *= $this->factors['discovery'];   // +5% to +20%
$high *= $this->factors['discovery'];  // +5% to +20%

// Apply contingency
$low *= 1 + $this->factors['contingency'];   // +15%
$high *= 1 + $this->factors['contingency'];  // +15%
```

**Cost Breakdown Example:**
For a 10-day project with normal complexity:
- Base: 10 days × £630 = £6,300
- + Project Management (15%): £6,300 × 1.15 = £7,245
- + Discovery (5%): £7,245 × 1.05 = £7,607
- + Contingency (15%): £7,607 × 1.15 = £8,748

### Step 5: Phase Breakdown
The system dynamically calculates cost distribution across project phases based on discovery needs:

```php
private function phases(float $totalLow, float $totalHigh): array
{
    // Discovery percentage from discovery factor
    $discoveryPercentage = $this->factors['discovery'] - 1;

    // Calculate remaining percentage dynamically
    $remainingPercentage = 1.0 - $discoveryPercentage;

    // Get base proportions from configuration (these are relative, not absolute)
    $basePercentages = [
        'Project Management' => 0.13,
        'Design' => 0.10,
        'Build' => 0.55,
        'QA' => 0.12,
    ];

    // Scale phases proportionally to fill remaining space
    $totalBaseProportions = array_sum($basePercentages);
    foreach ($basePercentages as $phase => $baseProportion) {
        $phasePercentages[$phase] = ($baseProportion / $totalBaseProportions) * $remainingPercentage;
    }

    // Deployment fills the remainder to ensure total = 100%
    $deploymentPercentage = 1.0 - array_sum($phasePercentages);
}
```

**Phase Distribution Examples:**

**Normal Discovery (5%):**
- Discovery: 5%
- Project Management: 13.7%
- Design: 10.6%
- Build: 58.1%
- QA: 12.7%
- Deployment: 0%

**Medium Discovery (10%):**
- Discovery: 10%
- Project Management: 13%
- Design: 10%
- Build: 55%
- QA: 12%
- Deployment: 0%

**High Discovery (20%):**
- Discovery: 20%
- Project Management: 11.6%
- Design: 8.9%
- Build: 48.9%
- QA: 10.7%
- Deployment: 0%

**Key Benefits:**
- Discovery percentage is truly dynamic based on user input
- Other phases scale proportionally to fill remaining space
- Total always equals exactly 100%
- No fixed configuration values needed

### Step 6: Payment Schedule
Payment schedules are automatically calculated based on project value:

```php
private function paymentSchedule(float $totalLow, float $totalHigh): array
{
    if ($totalHigh < 500) {
        // Full payment on completion
        return [['label' => 'Full payment on completion', 'low' => $totalLow, 'high' => $totalHigh]];
    } elseif ($totalHigh >= 500 && $totalHigh <= 3000) {
        // 50% deposit, 50% on completion
        return [
            ['label' => 'Deposit (50%)', 'low' => $totalLow * 0.5, 'high' => $totalHigh * 0.5],
            ['label' => 'Final payment (50%)', 'low' => $totalLow * 0.5, 'high' => $totalHigh * 0.5]
        ];
    } else {
        // Over £3000 → 4 payments
        return [
            ['label' => 'Deposit (40%)', 'low' => $totalLow * 0.4, 'high' => $totalHigh * 0.4],
            ['label' => 'Design Sign Off (25%)', 'low' => $totalLow * 0.25, 'high' => $totalHigh * 0.2],
            ['label' => 'Initial Build Completed (25%)', 'low' => $totalLow * 0.25, 'high' => $totalHigh * 0.2],
            ['label' => 'Go Live (10%)', 'low' => $totalLow * 0.1, 'high' => $totalHigh * 0.2]
        ];
    }
}
```

**Payment Schedules:**
- **Under £500**: Full payment on completion
- **£500 - £3,000**: 50% deposit, 50% on completion
- **Over £3,000**: 40% deposit, 25% design sign-off, 25% build completion, 10% go-live

### Step 7: Support Cost Calculation
```php
private function support(float $totalLow): float
{
    // Base support coefficients by project size
    if ($totalLow < 5000) {
        $supportCoefficient = 0.04;      // 4% for small projects
    } elseif ($totalLow < 15000) {
        $supportCoefficient = 0.03;      // 3% for medium projects
    } else {
        $supportCoefficient = 0.02;      // 2% for large projects
    }

    // Apply support factor and complexity
    $supportCoefficient *= $this->factors['support'];
    $supportCost = ($totalLow * $supportCoefficient) * $this->factors['complexity'];

    // Cap at £900/month
    return $supportCost <= 900 ? $supportCost : 900;
}
```

**Support Cost Examples:**
- **£3,000 project**: £3,000 × 4% × 1.0 = £120/month
- **£10,000 project**: £10,000 × 3% × 1.0 = £300/month
- **£25,000 project**: £25,000 × 2% × 1.0 = £500/month

## Configuration Parameters

### Day Rates
```yaml
day_rate:
  min: 630  # £90/hour × 7 hours
  max: 805  # £115/hour × 7 hours
```

### Overhead Factors
```yaml
contingency: 0.15           # 15% buffer for unexpected issues
project_management: 0.15    # 15% for project management overhead
calibration_factor: 1.0     # Global adjustment multiplier
```

### Multiplier Ranges
```yaml
multipliers:
  complexity:
    normal: 1.0      # Standard complexity
    medium: 1.5      # Moderate complexity
    high: 2.0        # High complexity
    very_high: 3.0   # Very high complexity

  risk:
    normal: 1.0      # Standard risk
    medium: 1.1      # Moderate risk
    high: 1.25       # High risk
    very_high: 1.5   # Very high risk

  speed:
    normal: 1.0      # Standard timeline
    tight: 1.25      # Tight deadline
    urgent: 1.5      # Urgent deadline
```

## Customization Guide

### Adjusting Day Rates
Edit `config/packages/pricing.yaml`:
```yaml
day_rate:
  min: 700  # Your minimum day rate
  max: 900  # Your maximum day rate
```

### Modifying Overhead Percentages
```yaml
contingency: 0.20           # Increase to 20%
project_management: 0.20    # Increase to 20%
```

### Adding New Project Types
```yaml
project_types:
  custom_app:
    days: 25
    title: "Custom Application"
    description: "Bespoke software solution"
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
    normal: 1.0
    medium: 1.75     # Increase from 1.5
    high: 2.5        # Increase from 2.0
    very_high: 4.0   # Increase from 3.0
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

2. **Test Calculations**
   - Use the web interface to test various project combinations
   - Verify that multipliers are working correctly
   - Check that phase breakdowns add up to 100%
   - Ensure payment schedules are appropriate for your business

3. **Validate Business Logic**
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
   - This is normal due to rounding
   - The system automatically adjusts the Deployment phase

3. **Support costs seem unreasonable**
   - Verify support coefficients in the code
   - Check that the £900 cap is appropriate for your business
   - Adjust complexity multipliers if needed

## Advanced Customization

### Custom Calculation Logic
To modify the calculation engine, edit `src/Service/PricingEngine.php`:

1. **Add new factors** to the `$this->factors` array
2. **Modify the calculation flow** in the `estimate()` method
3. **Add new output fields** to the return array
4. **Customize phase calculations** in the `phases()` method

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

---

This documentation should provide you with a complete understanding of how the pricing system works and how to customize it for your specific business needs. For additional support or questions, refer to the main README or create an issue in the project repository.
