<?php

namespace App\Tests\Service;

use App\Service\PricingEngine;
use App\Service\PricingConfigurationValidator;
use App\Service\EstimateInputValidator;
use App\Service\PricingCalculator;
use App\Service\PhaseCalculator;
use App\Service\PaymentScheduleCalculator;
use App\Service\SupportCalculator;
use App\Exception\PricingConfigurationException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

class PricingEngineTest extends TestCase
{
    private PricingEngine $pricingEngine;
    private mixed $configValidator;
    private mixed $inputValidator;
    private mixed $pricingCalculator;
    private mixed $phaseCalculator;
    private mixed $paymentCalculator;
    private mixed $supportCalculator;
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
            ],
            'features' => [
                'authentication' => ['days' => 3],
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
            ],
        ];

        $this->configValidator = $this->createMock(PricingConfigurationValidator::class);
        $this->inputValidator = $this->createMock(EstimateInputValidator::class);
        $this->pricingCalculator = $this->createMock(PricingCalculator::class);
        $this->phaseCalculator = $this->createMock(PhaseCalculator::class);
        $this->paymentCalculator = $this->createMock(PaymentScheduleCalculator::class);
        $this->supportCalculator = $this->createMock(SupportCalculator::class);

        $this->pricingEngine = new PricingEngine(
            $this->configValidator,
            $this->inputValidator,
            $this->pricingCalculator,
            $this->phaseCalculator,
            $this->paymentCalculator,
            $this->supportCalculator,
            $this->pricingConfig
        );
    }

    #[Test]
    public function constructorValidatesConfiguration(): void
    {
        $this->configValidator->expects($this->once())
            ->method('validate')
            ->with($this->pricingConfig);

        new PricingEngine(
            $this->configValidator,
            $this->inputValidator,
            $this->pricingCalculator,
            $this->phaseCalculator,
            $this->paymentCalculator,
            $this->supportCalculator,
            $this->pricingConfig
        );
    }

    #[Test]
    public function constructorThrowsExceptionOnInvalidConfiguration(): void
    {
        $this->configValidator->expects($this->once())
            ->method('validate')
            ->willThrowException(new PricingConfigurationException('Invalid configuration'));

        $this->expectException(PricingConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration');

        new PricingEngine(
            $this->configValidator,
            $this->inputValidator,
            $this->pricingCalculator,
            $this->phaseCalculator,
            $this->paymentCalculator,
            $this->supportCalculator,
            $this->pricingConfig
        );
    }

    #[Test]
    public function estimateGeneratesCompleteEstimate(): void
    {
        $input = [
            'projectType' => 'web_app',
            'features' => ['authentication'],
            'complexity' => 'medium',
            'risk' => 'low',
            'speed' => 'normal',
            'discovery' => 'no',
            'support' => 'yes',
        ];

        $expectedDays = 24.2;
        $expectedPricing = ['low' => 20000, 'high' => 30000];
        $expectedFactors = [
            'discovery' => 1.0,
            'support' => 1.1,
            'complexity' => 1.0,
        ];
        $expectedPhases = [
            'phase1' => ['low' => 10000, 'high' => 15000],
            'phase2' => ['low' => 10000, 'high' => 15000],
        ];
        $expectedPaymentSchedule = [
            'deposit' => 6000,
            'milestone1' => 8000,
            'milestone2' => 8000,
            'final' => 8000,
        ];
        $expectedSupport = 2000.0; // SupportCalculator returns a float, not an array

        // Set up mock expectations
        $this->inputValidator->expects($this->once())
            ->method('validate')
            ->with($input);

        $this->pricingCalculator->expects($this->once())
            ->method('calculateDays')
            ->with($input)
            ->willReturn($expectedDays);

        $this->pricingCalculator->expects($this->once())
            ->method('calculatePricing')
            ->with($expectedDays)
            ->willReturn($expectedPricing);

        $this->pricingCalculator->expects($this->once())
            ->method('getFactors')
            ->willReturn($expectedFactors);

        $this->phaseCalculator->expects($this->once())
            ->method('calculatePhases')
            ->with($expectedPricing['low'], $expectedPricing['high'], $expectedFactors['discovery'])
            ->willReturn($expectedPhases);

        $this->paymentCalculator->expects($this->once())
            ->method('calculatePaymentSchedule')
            ->with($expectedPricing['low'], $expectedPricing['high'])
            ->willReturn($expectedPaymentSchedule);

        $this->supportCalculator->expects($this->once())
            ->method('calculateSupport')
            ->with($expectedPricing['low'], $expectedFactors['support'], $expectedFactors['complexity'])
            ->willReturn(2000.0);

        // Execute the method
        $result = $this->pricingEngine->estimate($input);

        // Assert the result structure and values
        $this->assertEquals($expectedDays, $result['days']);
        $this->assertEquals($expectedPricing['low'], $result['low']);
        $this->assertEquals($expectedPricing['high'], $result['high']);
        $this->assertEquals($expectedPhases, $result['phases']);
        $this->assertEquals($expectedPaymentSchedule, $result['paymentSchedule']);
        $this->assertEquals($expectedSupport, $result['support']);
    }

    #[Test]
    public function estimateWithDiscoveryFactor(): void
    {
        $input = [
            'projectType' => 'web_app',
            'features' => [],
            'complexity' => 'medium',
            'risk' => 'medium',
            'speed' => 'normal',
            'discovery' => 'yes',
            'support' => 'no',
        ];

        $expectedDays = 22.05;
        $expectedPricing = ['low' => 18000, 'high' => 27000];
        $expectedFactors = [
            'discovery' => 1.05,
            'support' => 1.0,
            'complexity' => 1.0,
        ];

        $this->inputValidator->expects($this->once())
            ->method('validate')
            ->with($input);

        $this->pricingCalculator->expects($this->once())
            ->method('calculateDays')
            ->with($input)
            ->willReturn($expectedDays);

        $this->pricingCalculator->expects($this->once())
            ->method('calculatePricing')
            ->with($expectedDays)
            ->willReturn($expectedPricing);

        $this->pricingCalculator->expects($this->once())
            ->method('getFactors')
            ->willReturn($expectedFactors);

        $this->phaseCalculator->expects($this->once())
            ->method('calculatePhases')
            ->with($expectedPricing['low'], $expectedPricing['high'], $expectedFactors['discovery'])
            ->willReturn([]);

        $this->paymentCalculator->expects($this->once())
            ->method('calculatePaymentSchedule')
            ->with($expectedPricing['low'], $expectedPricing['high'])
            ->willReturn([]);

        $this->supportCalculator->expects($this->once())
            ->method('calculateSupport')
            ->with($expectedPricing['low'], $expectedFactors['support'], $expectedFactors['complexity'])
            ->willReturn(2000.0);

        $result = $this->pricingEngine->estimate($input);

        $this->assertEquals($expectedDays, $result['days']);
        $this->assertEquals($expectedPricing['low'], $result['low']);
        $this->assertEquals($expectedPricing['high'], $result['high']);
    }

    #[Test]
    public function estimateWithComplexProject(): void
    {
        $input = [
            'projectType' => 'web_app',
            'features' => ['authentication', 'payment_integration'],
            'complexity' => 'complex',
            'risk' => 'high',
            'speed' => 'rush',
            'discovery' => 'yes',
            'support' => 'yes',
        ];

        $expectedDays = 56.6;
        $expectedPricing = ['low' => 50000, 'high' => 75000];
        $expectedFactors = [
            'discovery' => 1.05,
            'support' => 1.1,
            'complexity' => 1.3,
        ];

        $this->inputValidator->expects($this->once())
            ->method('validate')
            ->with($input);

        $this->pricingCalculator->expects($this->once())
            ->method('calculateDays')
            ->with($input)
            ->willReturn($expectedDays);

        $this->pricingCalculator->expects($this->once())
            ->method('calculatePricing')
            ->with($expectedDays)
            ->willReturn($expectedPricing);

        $this->pricingCalculator->expects($this->once())
            ->method('getFactors')
            ->willReturn($expectedFactors);

        $this->phaseCalculator->expects($this->once())
            ->method('calculatePhases')
            ->with($expectedPricing['low'], $expectedPricing['high'], $expectedFactors['discovery'])
            ->willReturn([]);

        $this->paymentCalculator->expects($this->once())
            ->method('calculatePaymentSchedule')
            ->with($expectedPricing['low'], $expectedPricing['high'])
            ->willReturn([]);

        $this->supportCalculator->expects($this->once())
            ->method('calculateSupport')
            ->with($expectedPricing['low'], $expectedFactors['support'], $expectedFactors['complexity'])
            ->willReturn(2000.0);

        $result = $this->pricingEngine->estimate($input);

        $this->assertEquals($expectedDays, $result['days']);
        $this->assertEquals($expectedPricing['low'], $result['low']);
        $this->assertEquals($expectedPricing['high'], $result['high']);
    }

    #[Test]
    public function estimateWithMinimalInput(): void
    {
        $input = [
            'projectType' => 'web_app',
        ];

        $expectedDays = 21.0;
        $expectedPricing = ['low' => 17000, 'high' => 25000];
        $expectedFactors = [
            'discovery' => 1.0,
            'support' => 1.0,
            'complexity' => 1.0,
        ];

        $this->inputValidator->expects($this->once())
            ->method('validate')
            ->with($input);

        $this->pricingCalculator->expects($this->once())
            ->method('calculateDays')
            ->with($input)
            ->willReturn($expectedDays);

        $this->pricingCalculator->expects($this->once())
            ->method('calculatePricing')
            ->with($expectedDays)
            ->willReturn($expectedPricing);

        $this->pricingCalculator->expects($this->once())
            ->method('getFactors')
            ->willReturn($expectedFactors);

        $this->phaseCalculator->expects($this->once())
            ->method('calculatePhases')
            ->with($expectedPricing['low'], $expectedPricing['high'], $expectedFactors['discovery'])
            ->willReturn([]);

        $this->paymentCalculator->expects($this->once())
            ->method('calculatePaymentSchedule')
            ->with($expectedPricing['low'], $expectedPricing['high'])
            ->willReturn([]);

        $this->supportCalculator->expects($this->once())
            ->method('calculateSupport')
            ->with($expectedPricing['low'], $expectedFactors['support'], $expectedFactors['complexity'])
            ->willReturn(2000.0);

        $result = $this->pricingEngine->estimate($input);

        $this->assertEquals($expectedDays, $result['days']);
        $this->assertEquals($expectedPricing['low'], $result['low']);
        $this->assertEquals($expectedPricing['high'], $result['high']);
    }

    #[Test]
    public function estimateWithHighSupportAndComplexity(): void
    {
        $input = [
            'projectType' => 'web_app',
            'features' => ['authentication'],
            'complexity' => 'complex',
            'risk' => 'medium',
            'speed' => 'normal',
            'discovery' => 'no',
            'support' => 'yes',
        ];

        $expectedDays = 27.3;
        $expectedPricing = ['low' => 22000, 'high' => 33000];
        $expectedFactors = [
            'discovery' => 1.0,
            'support' => 1.1,
            'complexity' => 1.3,
        ];

        $this->inputValidator->expects($this->once())
            ->method('validate')
            ->with($input);

        $this->pricingCalculator->expects($this->once())
            ->method('calculateDays')
            ->with($input)
            ->willReturn($expectedDays);

        $this->pricingCalculator->expects($this->once())
            ->method('calculatePricing')
            ->with($expectedDays)
            ->willReturn($expectedPricing);

        $this->pricingCalculator->expects($this->once())
            ->method('getFactors')
            ->willReturn($expectedFactors);

        $this->phaseCalculator->expects($this->once())
            ->method('calculatePhases')
            ->with($expectedPricing['low'], $expectedPricing['high'], $expectedFactors['discovery'])
            ->willReturn([]);

        $this->paymentCalculator->expects($this->once())
            ->method('calculatePaymentSchedule')
            ->with($expectedPricing['low'], $expectedPricing['high'])
            ->willReturn([]);

        $this->supportCalculator->expects($this->once())
            ->method('calculateSupport')
            ->with($expectedPricing['low'], $expectedFactors['support'], $expectedFactors['complexity'])
            ->willReturn(2000.0);

        $result = $this->pricingEngine->estimate($input);

        $this->assertEquals($expectedDays, $result['days']);
        $this->assertEquals($expectedPricing['low'], $result['low']);
        $this->assertEquals($expectedPricing['high'], $result['high']);
    }
}
