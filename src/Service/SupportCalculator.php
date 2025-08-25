<?php

namespace App\Service;

class SupportCalculator
{
    /** @var array<string, mixed> */
    private array $supportConfig;

    /** @param array<string, mixed> $pricingConfig */
    public function __construct(array $pricingConfig)
    {
        $this->supportConfig = $pricingConfig['support'] ?? [];
    }

    /**
     * Calculate support costs based on project size and factors.
     */
    public function calculateSupport(float $totalLow, float $supportFactor, float $complexityFactor): float
    {
        $coefficients = $this->getSupportCoefficients();
        $thresholds = $this->getSupportThresholds();
        $maxMonthly = $this->getMaxMonthlySupport();

        $supportCoefficient = $this->determineSupportCoefficient($totalLow, $coefficients, $thresholds);
        $supportCost = $this->calculateSupportCost($totalLow, $supportCoefficient, $supportFactor, $complexityFactor);

        return $this->capSupportCost($supportCost, $maxMonthly);
    }

    /**
     * Get support coefficients from configuration.
     */
    /** @return array<string, float> */
    private function getSupportCoefficients(): array
    {
        return $this->supportConfig['coefficients'] ?? [
            'small' => 0.04,
            'medium' => 0.03,
            'large' => 0.02,
        ];
    }

    /**
     * Get support thresholds from configuration.
     */
    /** @return array<string, int> */
    private function getSupportThresholds(): array
    {
        return $this->supportConfig['thresholds'] ?? [
            'small' => 5000,
            'medium' => 15000,
        ];
    }

    /**
     * Get maximum monthly support cost from configuration.
     */
    private function getMaxMonthlySupport(): float
    {
        return $this->supportConfig['max_monthly'] ?? 900;
    }

    /**
     * Determine support coefficient based on project size.
     */
    /**
     * @phpstan-param array<string, float> $coefficients
     * @phpstan-param array<string, int> $thresholds
     */
    private function determineSupportCoefficient(float $totalLow, array $coefficients, array $thresholds): float
    {
        if ($totalLow < $thresholds['small']) {
            return $coefficients['small'];
        } elseif ($totalLow < $thresholds['medium']) {
            return $coefficients['medium'];
        } else {
            return $coefficients['large'];
        }
    }

    /**
     * Calculate support cost with factors applied.
     */
    private function calculateSupportCost(float $totalLow, float $supportCoefficient, float $supportFactor, float $complexityFactor): float
    {
        // Apply support factor and complexity
        $supportCoefficient *= $supportFactor;

        return round(($totalLow * $supportCoefficient) * $complexityFactor);
    }

    /**
     * Cap support cost at configured maximum.
     */
    private function capSupportCost(float $supportCost, float $maxMonthly): float
    {
        return $supportCost <= $maxMonthly ? $supportCost : $maxMonthly;
    }
}
