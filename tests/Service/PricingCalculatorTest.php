<?php

namespace App\Tests\Service;

use App\Service\PricingCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PricingCalculatorTest extends TestCase
{
    private array $pricingConfig;

    protected function setUp(): void
    {
        $this->pricingConfig = [
            'project_management' => 0.15,
            'contingency' => 0.10,
            'calibration_factor' => 1.05,
            'day_rate' => [
                'min' => 800,
                'max' => 1200,
            ],
            'project_types' => [
                'web_app' => ['days' => 20],
                'mobile_app' => ['days' => 25],
                'api' => ['days' => 15],
            ],
            'features' => [
                'authentication' => ['days' => 3],
                'payment_integration' => ['days' => 4],
                'reporting' => ['days' => 2],
            ],
            'bundles' => [
                'days_per_bundle' => 0.5,
            ],
            'multipliers' => [
                'complexity' => [
                    'simple' => 0.8,
                    'medium' => 1.0,
                    'complex' => 1.3,
                ],
                'risk' => [
                    'low' => 0.9,
                    'medium' => 1.0,
                    'high' => 1.2,
                ],
                'speed' => [
                    'normal' => 1.0,
                    'fast' => 1.2,
                    'rush' => 1.5,
                ],
                'discovery' => [
                    'yes' => 1.05,
                    'no' => 1.0,
                ],
                'support' => [
                    'yes' => 1.1,
                    'no' => 1.0,
                ],
                'compliance' => [
                    'basic' => 1.0,
                    'advanced' => 1.15,
                    'enterprise' => 1.3,
                ],
                'real_time' => [
                    'yes' => 1.1,
                    'no' => 1.0,
                ],
            ],
        ];
    }

    #[Test]
    public function constructorInitializesFactorsAndRates(): void
    {
        $calculator = new PricingCalculator($this->pricingConfig);

        $factors = $calculator->getFactors();

        $this->assertEquals(0.15, $factors['project_management']);
        $this->assertEquals(0.10, $factors['contingency']);
        $this->assertEquals(1.05, $factors['calibration_factor']);
    }

    #[Test]
    public function calculateDaysWithBasicInputAppliesAllMultipliersCorrectly(): void
    {
        $calculator = new PricingCalculator($this->pricingConfig);

        $input = [
            'projectType' => 'web_app',
            'features' => ['authentication'],
            'bundles' => 2,
            'complexity' => 'medium',
            'risk' => 'low',
            'speed' => 'normal',
            'discovery' => 'no',
            'support' => 'no',
        ];

        $days = $calculator->calculateDays($input);

        // Base: 20 days (web_app) + 3 days (authentication) + 1 day (2 bundles * 0.5)
        // Multipliers: 1.0 (complexity) * 0.9 (risk) * 1.0 (speed) * 1.0 (discovery) * 1.0 (support)
        // Calibration: 24 * 1.0 * 0.9 * 1.0 * 1.0 * 1.0 * 1.05 = 22.68
        $expectedDays = 22.7;

        $this->assertEquals($expectedDays, $days);
    }

    #[Test]
    public function calculateDaysWithComplexInputAppliesHighMultipliersCorrectly(): void
    {
        $calculator = new PricingCalculator($this->pricingConfig);

        $input = [
            'projectType' => 'mobile_app',
            'features' => ['authentication', 'payment_integration', 'reporting'],
            'bundles' => 4,
            'complexity' => 'complex',
            'risk' => 'high',
            'speed' => 'rush',
            'discovery' => 'yes',
            'support' => 'yes',
        ];

        $days = $calculator->calculateDays($input);

        // Base: 25 days (mobile_app) + 9 days (3 features) + 2 days (4 bundles * 0.5)
        // Multipliers: 1.3 (complexity) * 1.2 (risk) * 1.5 (speed) * 1.05 (discovery) * 1.1 (support)
        // Calibration: 36 * 1.3 * 1.2 * 1.5 * 1.05 * 1.1 * 1.05 = 88.5
        $expectedDays = 88.5;

        $this->assertEquals($expectedDays, $days);
    }

    #[Test]
    public function calculateDaysWithOptionalMultipliersAppliesComplianceAndRealTimeFactors(): void
    {
        $calculator = new PricingCalculator($this->pricingConfig);

        $input = [
            'projectType' => 'api',
            'features' => ['authentication'],
            'bundles' => 1,
            'complexity' => 'medium',
            'risk' => 'medium',
            'speed' => 'normal',
            'discovery' => 'no',
            'support' => 'no',
            'compliance' => 'enterprise',
            'realTime' => 'yes',
        ];

        $days = $calculator->calculateDays($input);

        // Base: 15 days (api) + 3 days (authentication) + 0.5 days (1 bundle * 0.5)
        // Multipliers: 1.0 (complexity) * 1.0 (risk) * 1.0 (speed) * 1.0 (discovery) * 1.0 (support) * 1.3 (compliance) * 1.1 (real_time)
        // Calibration: 18.5 * 1.0 * 1.0 * 1.0 * 1.0 * 1.0 * 1.3 * 1.1 * 1.05 = 27.8
        $expectedDays = 27.8;

        $this->assertEquals($expectedDays, $days);
    }

    #[Test]
    public function calculatePricingAppliesProjectManagementDiscoveryAndContingencyFactors(): void
    {
        $calculator = new PricingCalculator($this->pricingConfig);

        // First calculate days to initialize factors
        $input = [
            'projectType' => 'web_app',
            'complexity' => 'medium',
            'risk' => 'medium',
            'speed' => 'normal',
            'discovery' => 'no',
            'support' => 'no',
        ];
        $calculator->calculateDays($input);

        $days = 25.0;
        $pricing = $calculator->calculatePricing($days);

        // Low estimate: 25 * 800 * 1.15 (project management) * 1.0 (discovery) * 1.1 (contingency)
        $expectedLow = 25 * 800 * 1.15 * 1.0 * 1.1;

        // High estimate: 25 * 1200 * 1.15 (project management) * 1.0 (discovery) * 1.1 (contingency)
        $expectedHigh = 25 * 1200 * 1.15 * 1.0 * 1.1;

        $this->assertEquals(round($expectedLow), $pricing['low']);
        $this->assertEquals(round($expectedHigh), $pricing['high']);
    }

    #[Test]
    public function calculateDaysWithDiscoveryFactorDoesNotApplyDiscoveryToDays(): void
    {
        $calculator = new PricingCalculator($this->pricingConfig);

        $input = [
            'projectType' => 'web_app',
            'features' => [],
            'bundles' => 0,
            'complexity' => 'medium',
            'risk' => 'medium',
            'speed' => 'normal',
            'discovery' => 'yes',
            'support' => 'no',
        ];

        $days = $calculator->calculateDays($input);

        // Base: 20 days
        // Multipliers: 1.0 (complexity) * 1.0 (risk) * 1.0 (speed) = 1.0
        // Note: discovery and support factors are NOT applied to days calculation
        // Calibration: 20 * 1.0 * 1.05 = 21.0
        $expectedDays = 21.0;

        $this->assertEquals($expectedDays, $days);
    }

    #[Test]
    public function calculateDaysWithZeroFeaturesAndBundles(): void
    {
        $calculator = new PricingCalculator($this->pricingConfig);

        $input = [
            'projectType' => 'api',
            'features' => [],
            'bundles' => 0,
            'complexity' => 'simple',
            'risk' => 'low',
            'speed' => 'normal',
            'discovery' => 'no',
            'support' => 'no',
        ];

        $days = $calculator->calculateDays($input);

        // Base: 15 days (api) + 0 features + 0 bundles
        // Multipliers: 0.8 (complexity) * 0.9 (risk) * 1.0 (speed) * 1.0 (discovery) * 1.0 (support)
        // Calibration: 15 * 0.8 * 0.9 * 1.05 = 11.34
        $expectedDays = 11.3;

        $this->assertEquals($expectedDays, $days);
    }

    #[Test]
    public function getFactorsReturnsCorrectStructure(): void
    {
        $calculator = new PricingCalculator($this->pricingConfig);

        // Initialize factors by calculating days first
        $input = [
            'projectType' => 'web_app',
            'complexity' => 'medium',
            'risk' => 'medium',
            'speed' => 'normal',
            'discovery' => 'no',
            'support' => 'no',
        ];
        $calculator->calculateDays($input);

        $factors = $calculator->getFactors();

        $this->assertArrayHasKey('project_management', $factors);
        $this->assertArrayHasKey('contingency', $factors);
        $this->assertArrayHasKey('calibration_factor', $factors);
        $this->assertArrayHasKey('complexity', $factors);
        $this->assertArrayHasKey('risk', $factors);
        $this->assertArrayHasKey('speed', $factors);
        $this->assertArrayHasKey('discovery', $factors);
        $this->assertArrayHasKey('support', $factors);
    }

    #[Test]
    public function calculateDaysHandlesMissingInputValues(): void
    {
        $calculator = new PricingCalculator($this->pricingConfig);

        $input = [
            'projectType' => 'web_app',
            // Missing features, bundles, and multipliers
        ];

        $days = $calculator->calculateDays($input);

        // Base: 20 days (web_app) + 0 features + 0 bundles
        // Default multipliers: 1.0 for complexity, risk, speed (discovery and support not applied to days)
        // Calibration: 20 * 1.0 * 1.0 * 1.0 * 1.05 = 21.0
        $expectedDays = 21.0;

        $this->assertEquals($expectedDays, $days);
    }

    #[Test]
    public function calculateDaysWithHighMultipliers(): void
    {
        $calculator = new PricingCalculator($this->pricingConfig);

        $input = [
            'projectType' => 'web_app',
            'features' => [],
            'bundles' => 0,
            'complexity' => 'complex',
            'risk' => 'high',
            'speed' => 'rush',
            'discovery' => 'yes',
            'support' => 'yes',
        ];

        $days = $calculator->calculateDays($input);

        // Base: 20 days
        // Multipliers: 1.3 (complexity) * 1.2 (risk) * 1.5 (speed) * 1.05 (discovery) * 1.1 (support)
        // Calibration: 20 * 1.3 * 1.2 * 1.5 * 1.05 * 1.1 * 1.05 = 49.1
        $expectedDays = 49.1;

        $this->assertEquals($expectedDays, $days);
    }
}
