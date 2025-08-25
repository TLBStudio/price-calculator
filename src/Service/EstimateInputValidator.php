<?php

namespace App\Service;

use App\Exception\PricingConfigurationException;

class EstimateInputValidator
{
    private array $pricingConfig;

    public function __construct(array $pricingConfig)
    {
        $this->pricingConfig = $pricingConfig;
    }

    /**
     * Validates input data for estimation.
     *
     * @throws PricingConfigurationException
     */
    public function validate(array $input): void
    {
        $this->validateProjectType($input);
        $this->validateFeatures($input);
        $this->validateBundles($input);
        $this->validateRequiredMultipliers($input);
        $this->validateOptionalMultipliers($input);
    }

    /**
     * Validates project type.
     */
    private function validateProjectType(array $input): void
    {
        if (!isset($input['projectType'])) {
            throw new PricingConfigurationException('Project type is required');
        }

        if (!isset($this->pricingConfig['project_types'][$input['projectType']])) {
            throw PricingConfigurationException::invalidProjectType($input['projectType']);
        }
    }

    /**
     * Validates features.
     */
    private function validateFeatures(array $input): void
    {
        if (isset($input['features'])) {
            foreach ($input['features'] as $feature) {
                if (!isset($this->pricingConfig['features'][$feature])) {
                    throw PricingConfigurationException::invalidFeature($feature);
                }
            }
        }
    }

    /**
     * Validates bundles.
     */
    private function validateBundles(array $input): void
    {
        if (isset($input['bundles'])) {
            $bundleQuantity = $input['bundles'];

            if (!is_numeric($bundleQuantity) || $bundleQuantity < 0) {
                throw new PricingConfigurationException('Bundle quantity must be a non-negative number');
            }

            $maxQuantity = $this->pricingConfig['bundles']['max_quantity'] ?? 50;
            if ($bundleQuantity > $maxQuantity) {
                throw new PricingConfigurationException(sprintf('Bundle quantity cannot exceed %d', $maxQuantity));
            }
        }
    }

    /**
     * Validates required multipliers.
     */
    private function validateRequiredMultipliers(array $input): void
    {
        $requiredMultipliers = ['complexity', 'risk', 'speed', 'discovery', 'support'];

        foreach ($requiredMultipliers as $multiplier) {
            if (!isset($input[$multiplier])) {
                throw new PricingConfigurationException(sprintf('Multiplier %s is required', $multiplier));
            }

            $configKey = $multiplier;
            if (!isset($this->pricingConfig['multipliers'][$configKey][$input[$multiplier]])) {
                throw PricingConfigurationException::invalidMultiplier($multiplier, $input[$multiplier], 'invalid value');
            }
        }
    }

    /**
     * Validates optional multipliers.
     */
    private function validateOptionalMultipliers(array $input): void
    {
        $optionalMultipliers = ['compliance', 'realTime'];

        foreach ($optionalMultipliers as $multiplier) {
            if (isset($input[$multiplier])) {
                $configKey = 'realTime' === $multiplier ? 'real_time' : $multiplier;
                if (!isset($this->pricingConfig['multipliers'][$configKey][$input[$multiplier]])) {
                    throw PricingConfigurationException::invalidMultiplier($multiplier, $input[$multiplier], 'invalid value');
                }
            }
        }
    }
}
