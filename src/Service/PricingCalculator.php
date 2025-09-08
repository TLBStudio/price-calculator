<?php

namespace App\Service;

class PricingCalculator
{
    // Default multiplier values
    private const DEFAULT_MULTIPLIER_FACTOR = 1.0;
    private const DEFAULT_DISCOVERY_FACTOR = 1.0;

    /** @var array<string, mixed> */
    private array $pricingConfig;

    /** @var array<string, float> */
    private array $factors;

    /** @var array<string, int> */
    private array $rates;

    /** @param array<string, mixed> $pricingConfig */
    public function __construct(array $pricingConfig)
    {
        $this->pricingConfig = $pricingConfig;
        $this->initializeFactors();
        $this->initializeRates();
    }

    /**
     * Initialize calculation factors.
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
     * Initialize day rates.
     */
    private function initializeRates(): void
    {
        $this->rates = [
            'min' => $this->pricingConfig['day_rate']['min'],
            'max' => $this->pricingConfig['day_rate']['max'],
        ];
    }

    /**
     * Calculate project days based on input.
     */
    /** @param array<string, mixed> $input */
    public function calculateDays(array $input): float
    {
        $days = $this->calculateBaseDays($input);
        $days = $this->applyMultipliers($days, $input);
        $days = $this->applyCalibrationFactor($days);

        return round($days, 1);
    }

    /**
     * Calculate base days from project type and features.
     */
    /** @param array<string, mixed> $input */
    private function calculateBaseDays(array $input): float
    {
        $days = floor($this->pricingConfig['project_types'][$input['projectType']]['days'] ?? 0);

        // Add days from selected features
        foreach ($input['features'] ?? [] as $feature) {
            $days += $this->pricingConfig['features'][$feature]['days'] ?? 0;
        }

        // Add days from bundles (each bundle adds 0.5 days)
        $bundleQuantity = $input['bundles'] ?? 0;
        if ($bundleQuantity > 0) {
            $daysPerBundle = $this->pricingConfig['bundles']['days_per_bundle'] ?? 0.5;
            $days += $bundleQuantity * $daysPerBundle;
        }

        return $days;
    }

    /**
     * Apply all multipliers to the base days.
     */
    /** @param array<string, mixed> $input */
    private function applyMultipliers(float $days, array $input): float
    {
        $this->setMultiplierFactors($input);
        return $this->applyAllMultipliers($days);
    }

    /**
     * Set all multiplier factors from input.
     */
    /** @param array<string, mixed> $input */
    private function setMultiplierFactors(array $input): void
    {
        $this->setRequiredMultiplierFactors($input);
        $this->setOptionalMultiplierFactors($input);
    }

    /**
     * Set required multiplier factors.
     */
    /** @param array<string, mixed> $input */
    private function setRequiredMultiplierFactors(array $input): void
    {
        $this->factors["complexity"] = $this->pricingConfig["multipliers"]["complexity"][$input["complexity"]] ?? self::DEFAULT_MULTIPLIER_FACTOR;
        $this->factors["risk"] = $this->pricingConfig["multipliers"]["risk"][$input["risk"]] ?? self::DEFAULT_MULTIPLIER_FACTOR;
        $this->factors["speed"] = $this->pricingConfig["multipliers"]["speed"][$input["speed"]] ?? self::DEFAULT_MULTIPLIER_FACTOR;
        $this->factors["discovery"] = $this->pricingConfig["multipliers"]["discovery"][$input["discovery"]] ?? self::DEFAULT_DISCOVERY_FACTOR;
        $this->factors["support"] = $this->pricingConfig["multipliers"]["support"][$input["support"]] ?? self::DEFAULT_MULTIPLIER_FACTOR;
    }

    /**
     * Set optional multiplier factors.
     */
    /** @param array<string, mixed> $input */
    private function setOptionalMultiplierFactors(array $input): void
    {
        if (isset($input["compliance"]) && isset($this->pricingConfig["multipliers"]["compliance"])) {
            $this->factors["compliance"] = $this->pricingConfig["multipliers"]["compliance"][$input["compliance"]] ?? self::DEFAULT_MULTIPLIER_FACTOR;
        }

        if (isset($input["realTime"]) && isset($this->pricingConfig["multipliers"]["real_time"])) {
            $this->factors["real_time"] = $this->pricingConfig["multipliers"]["real_time"][$input["realTime"]] ?? self::DEFAULT_MULTIPLIER_FACTOR;
        }
    }

    /**
     * Apply all set multipliers to days.
     */
    private function applyAllMultipliers(float $days): float
    {
        // Apply required multipliers
        $days *= $this->factors["complexity"];
        $days *= $this->factors["risk"];
        $days *= $this->factors["speed"];

        // Apply optional multipliers
        if (isset($this->factors["compliance"])) {
            $days *= $this->factors["compliance"];
        }
        if (isset($this->factors["real_time"])) {
            $days *= $this->factors["real_time"];
        }

        return $days;
    }

    /**
     * Apply calibration factor.
     */
    private function applyCalibrationFactor(float $days): float
    {
        return $days * $this->factors['calibration_factor'];
    }

    /**
     * Calculate pricing estimates.
     */
    /** @return array<string, float> */
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
     * Calculate estimate with the specified rate type.
     */
    private function calculateEstimate(float $days, string $rateType): float
    {
        $estimate = $days * $this->rates[$rateType];
        $estimate = $this->applyProjectManagementFactor($estimate);
        $estimate = $this->applyDiscoveryFactor($estimate);
        $estimate = $this->applyContingencyFactor($estimate);

        return $estimate;
    }

    /**
     * Calculate low estimate.
     */
    private function calculateLowEstimate(float $days): float
    {
        return $this->calculateEstimate($days, "min");
    }

    /**
     * Calculate high estimate.
     */
    private function calculateHighEstimate(float $days): float
    {
        return $this->calculateEstimate($days, "max");
    }
    /**
     * Apply project management factor.
     */
    private function applyProjectManagementFactor(float $estimate): float
    {
        return $estimate * (1 + $this->factors['project_management']);
    }

    /**
     * Apply discovery factor.
     */
    private function applyDiscoveryFactor(float $estimate): float
    {
        return $estimate * $this->factors['discovery'];
    }

    /**
     * Apply contingency factor.
     */
    private function applyContingencyFactor(float $estimate): float
    {
        return $estimate * (1 + $this->factors['contingency']);
    }

    /**
     * Get factors for use in other calculations.
     */
    /** @return array<string, float> */
    public function getFactors(): array
    {
        return $this->factors;
    }
}
