<?php

namespace App\Tests\Service;

use App\Service\BusinessRuleValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BusinessRuleValidatorTest extends TestCase
{
    private array $pricingConfig;

    protected function setUp(): void
    {
        $this->pricingConfig = [
            'compatibility' => [
                'project_type_incompatibilities' => [
                    'mobile_app' => [
                        'incompatible_features' => ['desktop_only_feature'],
                        'message' => 'Mobile apps cannot use desktop-only features.',
                    ],
                    'api' => [
                        'incompatible_features' => ['ui_heavy_feature'],
                        'message' => 'APIs should not include UI-heavy features.',
                    ],
                ],
                'feature_incompatibilities' => [
                    'conflict_1' => [
                        'conflicting_features' => ['feature_a', 'feature_b'],
                        'message' => 'Feature A and Feature B cannot be used together.',
                    ],
                    'conflict_2' => [
                        'conflicting_features' => ['feature_x', 'feature_y', 'feature_z'],
                        'message' => 'Features X, Y, and Z are mutually exclusive.',
                    ],
                ],
                'feature_dependencies' => [
                    'advanced_reporting' => [
                        'required_features' => ['basic_reporting'],
                        'message' => 'Advanced reporting requires basic reporting to be enabled.',
                    ],
                    'payment_processing' => [
                        'required_features' => ['authentication'],
                        'message' => 'Payment processing requires authentication to be enabled.',
                    ],
                ],
            ],
        ];
    }

    #[Test]
    public function constructorInitializesWithConfig(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        // Test that the object was created successfully
        $this->assertInstanceOf(BusinessRuleValidator::class, $validator);
    }

    #[Test]
    public function validateBusinessRulesWithNoIssues(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            'features' => ['authentication', 'basic_reporting'],
            'complexity' => 'medium',
            'speed' => 'normal',
            'risk' => 'low',
            'support' => 'low',
        ];

        $warnings = $validator->validateBusinessRules($data);

        $this->assertEmpty($warnings);
    }

    #[Test]
    public function validateBusinessRulesDetectsUnrealisticComplexitySpeedCombination(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            'complexity' => 'very_high',
            'speed' => 'urgent',
        ];

        $warnings = $validator->validateBusinessRules($data);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Very high complexity with urgent timeline may not be realistic', $warnings[0]);
    }

    #[Test]
    public function validateBusinessRulesDetectsHighRiskHighSupportCostImpact(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            'risk' => 'very_high',
            'support' => 'high',
        ];

        $warnings = $validator->validateBusinessRules($data);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Very high risk with high support requirements may significantly impact long-term costs', $warnings[0]);
    }

    #[Test]
    public function validateBusinessRulesDetectsHighComplianceHighRealTimeComplexityImpact(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            'compliance' => 'very_high',
            'realTime' => 'very_high',
        ];

        $warnings = $validator->validateBusinessRules($data);

        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Very high compliance requirements with very high real-time requirements may significantly increase project complexity and cost', $warnings[0]);
    }

    #[Test]
    public function getCompatibilityWarningsWithProjectTypeIncompatibility(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'mobile_app',
            'features' => ['desktop_only_feature', 'authentication'],
        ];

        $warnings = $validator->getCompatibilityWarnings($data);

        $this->assertCount(1, $warnings);
        $this->assertEquals('incompatibility', $warnings[0]['type']);
        $this->assertEquals('Mobile apps cannot use desktop-only features.', $warnings[0]['message']);
        $this->assertEquals(['desktop_only_feature'], $warnings[0]['incompatible_features']);
    }

    #[Test]
    public function getCompatibilityWarningsWithFeatureConflict(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            'features' => ['feature_a', 'feature_b', 'authentication'],
        ];

        $warnings = $validator->getCompatibilityWarnings($data);

        $this->assertCount(1, $warnings);
        $this->assertEquals('conflict', $warnings[0]['type']);
        $this->assertEquals('Feature A and Feature B cannot be used together.', $warnings[0]['message']);
        $this->assertEquals(['feature_a', 'feature_b'], $warnings[0]['conflicting_features']);
    }

    #[Test]
    public function getCompatibilityWarningsWithFeatureDependency(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            'features' => ['advanced_reporting'], // Missing required 'basic_reporting'
        ];

        $warnings = $validator->getCompatibilityWarnings($data);

        $this->assertCount(1, $warnings);
        $this->assertEquals('dependency', $warnings[0]['type']);
        $this->assertEquals('Advanced reporting requires basic reporting to be enabled.', $warnings[0]['message']);
        $this->assertEquals(['basic_reporting'], $warnings[0]['incompatible_features']);
    }

    #[Test]
    public function getCompatibilityWarningsWithMultipleIssues(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'mobile_app',
            'features' => ['desktop_only_feature', 'feature_a', 'feature_b'],
        ];

        $warnings = $validator->getCompatibilityWarnings($data);

        // Should have 2 warnings: project type incompatibility and feature conflict
        $this->assertCount(2, $warnings);

        // Check project type incompatibility
        $incompatibilityWarning = array_filter($warnings, fn ($w) => 'incompatibility' === $w['type']);
        $this->assertCount(1, $incompatibilityWarning);

        // Check feature conflict
        $conflictWarning = array_filter($warnings, fn ($w) => 'conflict' === $w['type']);
        $this->assertCount(1, $conflictWarning);
    }

    #[Test]
    public function getCompatibilityWarningsWithNoIssues(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            'features' => ['authentication', 'basic_reporting'],
        ];

        $warnings = $validator->getCompatibilityWarnings($data);

        $this->assertEmpty($warnings);
    }

    #[Test]
    public function getCompatibilityWarningsWithMissingData(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            // No features specified
        ];

        $warnings = $validator->getCompatibilityWarnings($data);

        $this->assertEmpty($warnings);
    }

    #[Test]
    public function validateBusinessRulesWithMissingData(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            // Minimal data
        ];

        $warnings = $validator->validateBusinessRules($data);

        $this->assertEmpty($warnings);
    }

    #[Test]
    public function validateBusinessRulesWithPartialDataDetectsNoConflict(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            'complexity' => 'very_high',
            // Missing speed, so no conflict should be detected
        ];

        $warnings = $validator->validateBusinessRules($data);

        $this->assertEmpty($warnings);
    }

    #[Test]
    public function getCompatibilityWarningsWithThreeWayFeatureConflict(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            'features' => ['feature_x', 'feature_y', 'feature_z'],
        ];

        $warnings = $validator->getCompatibilityWarnings($data);

        $this->assertCount(1, $warnings);
        $this->assertEquals('conflict', $warnings[0]['type']);
        $this->assertEquals('Features X, Y, and Z are mutually exclusive.', $warnings[0]['message']);
        $this->assertEquals(['feature_x', 'feature_y', 'feature_z'], $warnings[0]['conflicting_features']);
    }

    #[Test]
    public function getCompatibilityWarningsWithCompatibleFeaturesReturnsNoWarnings(): void
    {
        $validator = new BusinessRuleValidator($this->pricingConfig);

        $data = [
            'projectType' => 'web_app',
            'features' => ['authentication', 'basic_reporting'], // These features have no conflicts
        ];

        $warnings = $validator->getCompatibilityWarnings($data);

        // Should not trigger any warnings since these features are compatible
        $this->assertEmpty($warnings);
    }
}
