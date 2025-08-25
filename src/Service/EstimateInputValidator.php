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
     * Validates input data for estimation
     *
     * @throws PricingConfigurationException
     */
    public function validate(array $input): void
    {
        $this->validateProjectType($input);
        $this->validateFeatures($input);
        $this->validateRequiredMultipliers($input);
        $this->validateOptionalMultipliers($input);
    }

    /**
     * Validates project type
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
     * Validates features
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
     * Validates required multipliers
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
     * Validates optional multipliers
     */
    private function validateOptionalMultipliers(array $input): void
    {
        $optionalMultipliers = ['compliance', 'realTime'];

        foreach ($optionalMultipliers as $multiplier) {
            if (isset($input[$multiplier])) {
                $configKey = $multiplier === 'realTime' ? 'real_time' : $multiplier;
                if (!isset($this->pricingConfig['multipliers'][$configKey][$input[$multiplier]])) {
                    throw PricingConfigurationException::invalidMultiplier($multiplier, $input[$multiplier], 'invalid value');
                }
            }
        }
    }
}
