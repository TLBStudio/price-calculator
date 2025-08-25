<?php

namespace App\Service;

class PricingCalculator
{
    private array $pricingConfig;
    private array $factors;
    private array $rates;

    public function __construct(array $pricingConfig)
    {
        $this->pricingConfig = $pricingConfig;
        $this->initializeFactors();
        $this->initializeRates();
    }

    /**
     * Initialize calculation factors
     */
    private function initializeFactors(): void
    {
        $this->factors = [
            'project_management' => $this->pricingConfig['project_management'],
            'contingency' => $this->pricingConfig['contingency'],
            'calibration_factor' => $this->pricingConfig['calibration_factor'],
        ];
    }

    /**
     * Initialize day rates
     */
    private function initializeRates(): void
    {
        $this->rates = [
            'min' => $this->pricingConfig['day_rate']['min'],
            'max' => $this->pricingConfig['day_rate']['max'],
        ];
    }

    /**
     * Calculate project days based on input
     */
    public function calculateDays(array $input): float
    {
        $days = $this->calculateBaseDays($input);
        $days = $this->applyMultipliers($days, $input);
        $days = $this->applyCalibrationFactor($days);

        return round($days, 1);
    }

    /**
     * Calculate base days from project type and features
     */
    private function calculateBaseDays(array $input): float
    {
        $days = floor($this->pricingConfig['project_types'][$input['projectType']]['days'] ?? 0);

        foreach ($input['features'] ?? [] as $feature) {
            $days += $this->pricingConfig['features'][$feature]['days'] ?? 0;
        }

        return $days;
    }

    /**
     * Apply all multipliers to the base days
     */
    private function applyMultipliers(float $days, array $input): float
    {
        // Set multiplier factors
        $this->factors['complexity'] = $this->pricingConfig['multipliers']['complexity'][$input['complexity']] ?? 1;
        $this->factors['risk'] = $this->pricingConfig['multipliers']['risk'][$input['risk']] ?? 1;
        $this->factors['speed'] = $this->pricingConfig['multipliers']['speed'][$input['speed']] ?? 1;
        $this->factors['discovery'] = $this->pricingConfig['multipliers']['discovery'][$input['discovery']] ?? 1.05;
        $this->factors['support'] = $this->pricingConfig['multipliers']['support'][$input['support']] ?? 1;

        // Handle optional new multipliers
        if (isset($input['compliance']) && isset($this->pricingConfig['multipliers']['compliance'])) {
            $this->factors['compliance'] = $this->pricingConfig['multipliers']['compliance'][$input['compliance']] ?? 1;
        }

        if (isset($input['realTime']) && isset($this->pricingConfig['multipliers']['real_time'])) {
            $this->factors['real_time'] = $this->pricingConfig['multipliers']['real_time'][$input['realTime']] ?? 1;
        }

        // Apply multipliers
        $days *= $this->factors['complexity'];
        $days *= $this->factors['risk'];
        $days *= $this->factors['speed'];

        // Apply optional new multipliers
        if (isset($this->factors['compliance'])) {
            $days *= $this->factors['compliance'];
        }
        if (isset($this->factors['real_time'])) {
            $days *= $this->factors['real_time'];
        }

        return $days;
    }

    /**
     * Apply calibration factor
     */
    private function applyCalibrationFactor(float $days): float
    {
        return $days * $this->factors['calibration_factor'];
    }

    /**
     * Calculate pricing estimates
     */
    public function calculatePricing(float $days): array
    {
        $low = $this->calculateLowEstimate($days);
        $high = $this->calculateHighEstimate($days);

        return [
            'low' => round($low),
            'high' => round($high),
        ];
    }

    /**
     * Calculate low estimate
     */
    private function calculateLowEstimate(float $days): float
    {
        $estimate = $days * $this->rates['min'];
        $estimate = $this->applyProjectManagementFactor($estimate);
        $estimate = $this->applyDiscoveryFactor($estimate);
        $estimate = $this->applyContingencyFactor($estimate);

        return $estimate;
    }

    /**
     * Calculate high estimate
     */
    private function calculateHighEstimate(float $days): float
    {
        $estimate = $days * $this->rates['max'];
        $estimate = $this->applyProjectManagementFactor($estimate);
        $estimate = $this->applyDiscoveryFactor($estimate);
        $estimate = $this->applyContingencyFactor($estimate);

        return $estimate;
    }

    /**
     * Apply project management factor
     */
    private function applyProjectManagementFactor(float $estimate): float
    {
        return $estimate * (1 + $this->factors['project_management']);
    }

    /**
     * Apply discovery factor
     */
    private function applyDiscoveryFactor(float $estimate): float
    {
        return $estimate * $this->factors['discovery'];
    }

    /**
     * Apply contingency factor
     */
    private function applyContingencyFactor(float $estimate): float
    {
        return $estimate * (1 + $this->factors['contingency']);
    }

    /**
     * Get factors for use in other calculations
     */
    public function getFactors(): array
    {
        return $this->factors;
    }
}
