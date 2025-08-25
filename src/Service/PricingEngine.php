<?php

namespace App\Service;

use App\Exception\PricingConfigurationException;

class PricingEngine
{
    public function __construct(
        private PricingConfigurationValidator $configValidator,
        private EstimateInputValidator $inputValidator,
        private PricingCalculator $pricingCalculator,
        private PhaseCalculator $phaseCalculator,
        private PaymentScheduleCalculator $paymentCalculator,
        private SupportCalculator $supportCalculator,
        /** @var array<string, mixed> */
        private array $pricingConfig,
    ) {
        $this->validateConfiguration();
    }

    /**
     * Validates the pricing configuration for required fields and valid values.
     *
     * @throws PricingConfigurationException
     */
    private function validateConfiguration(): void
    {
        $this->configValidator->validate($this->pricingConfig);
    }

    /**
     * Generate a complete project estimate.
     */
    /**
     * @phpstan-param array<string, string|array<string>|int|float|null> $input
     *
     * @return array<string, mixed>
     */
    public function estimate(array $input): array
    {
        // Validate input data
        $this->inputValidator->validate($input);

        // Calculate project days
        $days = $this->pricingCalculator->calculateDays($input);

        // Calculate pricing estimates
        $pricing = $this->pricingCalculator->calculatePricing($days);

        // Get factors for other calculations
        $factors = $this->pricingCalculator->getFactors();

        // Calculate phases
        $phases = $this->phaseCalculator->calculatePhases(
            $pricing['low'],
            $pricing['high'],
            $factors['discovery']
        );

        // Calculate payment schedule
        $paymentSchedule = $this->paymentCalculator->calculatePaymentSchedule(
            $pricing['low'],
            $pricing['high']
        );

        // Calculate support costs
        $support = $this->supportCalculator->calculateSupport(
            $pricing['low'],
            $factors['support'],
            $factors['complexity']
        );

        return [
            'days' => $days,
            'low' => $pricing['low'],
            'high' => $pricing['high'],
            'phases' => $phases,
            'paymentSchedule' => $paymentSchedule,
            'support' => $support,
        ];
    }
}
