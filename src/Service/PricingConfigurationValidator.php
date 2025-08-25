<?php

namespace App\Service;

use App\Exception\PricingConfigurationException;

class PricingConfigurationValidator
{
    /**
     * Validates the complete pricing configuration
     *
     * @throws PricingConfigurationException
     */
    public function validate(array $pricingConfig): void
    {
        $this->validateRequiredConfiguration($pricingConfig);
        $this->validateDayRates($pricingConfig);
        $this->validatePercentages($pricingConfig);
        $this->validateCalibrationFactor($pricingConfig);
        $this->validateMultipliers($pricingConfig);
        $this->validateProjectTypes($pricingConfig);
        $this->validateFeatures($pricingConfig);
        $this->validateOptionalConfigurations($pricingConfig);
    }

    /**
     * Validates required top-level configuration
     */
    private function validateRequiredConfiguration(array $pricingConfig): void
    {
        $requiredKeys = ['day_rate', 'contingency', 'project_management', 'calibration_factor', 'multipliers', 'project_types', 'features'];

        foreach ($requiredKeys as $key) {
            if (!isset($pricingConfig[$key])) {
                throw PricingConfigurationException::missingRequiredConfig($key);
            }
        }
    }

    /**
     * Validates day rate configuration
     */
    private function validateDayRates(array $pricingConfig): void
    {
        if (!isset($pricingConfig['day_rate']['min']) || !isset($pricingConfig['day_rate']['max'])) {
            throw PricingConfigurationException::missingRequiredConfig('day_rate.min or day_rate.max');
        }

        if (!is_numeric($pricingConfig['day_rate']['min']) || $pricingConfig['day_rate']['min'] <= 0) {
            throw PricingConfigurationException::invalidDayRate('min', $pricingConfig['day_rate']['min']);
        }

        if (!is_numeric($pricingConfig['day_rate']['max']) || $pricingConfig['day_rate']['max'] <= 0) {
            throw PricingConfigurationException::invalidDayRate('max', $pricingConfig['day_rate']['max']);
        }

        if ($pricingConfig['day_rate']['min'] > $pricingConfig['day_rate']['max']) {
            throw new PricingConfigurationException('Day rate min cannot be greater than max');
        }
    }

    /**
     * Validates percentage-based configurations
     */
    private function validatePercentages(array $pricingConfig): void
    {
        $this->validatePercentage('contingency', $pricingConfig['contingency']);
        $this->validatePercentage('project_management', $pricingConfig['project_management']);
    }

    /**
     * Validates calibration factor
     */
    private function validateCalibrationFactor(array $pricingConfig): void
    {
        if (!is_numeric($pricingConfig['calibration_factor']) || $pricingConfig['calibration_factor'] <= 0) {
            throw PricingConfigurationException::invalidMultiplier('calibration_factor', 'global', $pricingConfig['calibration_factor']);
        }
    }

    /**
     * Validates multiplier configurations
     */
    private function validateMultipliers(array $pricingConfig): void
    {
        $multiplierTypes = ['complexity', 'risk', 'speed', 'support', 'discovery', 'compliance', 'real_time'];

        foreach ($multiplierTypes as $type) {
            if (!isset($pricingConfig['multipliers'][$type])) {
                continue; // Skip validation for optional multipliers
            }

            foreach ($pricingConfig['multipliers'][$type] as $key => $value) {
                if (!is_numeric($value) || $value <= 0) {
                    throw PricingConfigurationException::invalidMultiplier($type, $key, $value);
                }
            }
        }
    }

    /**
     * Validates project type configurations
     */
    private function validateProjectTypes(array $pricingConfig): void
    {
        foreach ($pricingConfig['project_types'] as $type => $config) {
            if (!isset($config['days'])) {
                throw PricingConfigurationException::invalidProjectTypeConfiguration($type, 'Missing days configuration');
            }

            if (!is_numeric($config['days'])) {
                throw PricingConfigurationException::invalidProjectTypeConfiguration($type, 'Days must be a number');
            }

            if ($config['days'] < 0) {
                throw PricingConfigurationException::invalidProjectTypeConfiguration($type, 'Days cannot be negative');
            }
        }
    }

    /**
     * Validates feature configurations
     */
    private function validateFeatures(array $pricingConfig): void
    {
        foreach ($pricingConfig['features'] as $feature => $config) {
            if (!isset($config['days'])) {
                throw PricingConfigurationException::invalidFeatureConfiguration($feature, 'Missing days configuration');
            }

            if (!is_numeric($config['days'])) {
                throw PricingConfigurationException::invalidFeatureConfiguration($feature, 'Days must be a number');
            }

            if ($config['days'] < 0) {
                throw PricingConfigurationException::invalidFeatureConfiguration($feature, 'Days cannot be negative');
            }
        }
    }

    /**
     * Validates optional configurations if present
     */
    private function validateOptionalConfigurations(array $pricingConfig): void
    {
        $this->validatePhaseConfiguration($pricingConfig);
        $this->validatePaymentScheduleConfiguration($pricingConfig);
        $this->validateSupportConfiguration($pricingConfig);
    }

    /**
     * Validates phase configuration
     */
    private function validatePhaseConfiguration(array $pricingConfig): void
    {
        if (!isset($pricingConfig['phases'])) {
            return;
        }

        $phases = $pricingConfig['phases'];

        if (isset($phases['base_percentages'])) {
            $total = array_sum($phases['base_percentages']);
            if (abs($total - 0.95) > 0.001) {
                throw PricingConfigurationException::phasePercentagesMismatch($total);
            }
        }
    }

    /**
     * Validates payment schedule configuration
     */
    private function validatePaymentScheduleConfiguration(array $pricingConfig): void
    {
        if (!isset($pricingConfig['payment_schedules'])) {
            return;
        }

        $schedules = $pricingConfig['payment_schedules'];

        if (isset($schedules['thresholds'])) {
            $this->validatePaymentThresholds($schedules['thresholds']);
        }

        $scheduleTypes = ['small', 'medium', 'large'];
        foreach ($scheduleTypes as $type) {
            if (isset($schedules[$type])) {
                $this->validateScheduleDefinition($type, $schedules[$type]);
            }
        }
    }

    /**
     * Validates payment thresholds
     */
    private function validatePaymentThresholds(array $thresholds): void
    {
        if (isset($thresholds['small_project']) && (!is_numeric($thresholds['small_project']) || $thresholds['small_project'] <= 0)) {
            throw PricingConfigurationException::invalidPaymentThreshold('small_project', $thresholds['small_project']);
        }

        if (isset($thresholds['medium_project']) && (!is_numeric($thresholds['medium_project']) || $thresholds['medium_project'] <= 0)) {
            throw PricingConfigurationException::invalidPaymentThreshold('medium_project', $thresholds['medium_project']);
        }
    }

    /**
     * Validates a schedule definition
     */
    private function validateScheduleDefinition(string $type, array $schedule): void
    {
        foreach ($schedule as $payment) {
            if (!isset($payment['label']) || !isset($payment['low_percent']) || !isset($payment['high_percent'])) {
                throw PricingConfigurationException::invalidPaymentSchedule($type, 'Missing required fields (label, low_percent, high_percent)');
            }

            $this->validatePercentage("payment_schedules.{$type}.low_percent", $payment['low_percent']);
            $this->validatePercentage("payment_schedules.{$type}.high_percent", $payment['high_percent']);
        }
    }

    /**
     * Validates support configuration
     */
    private function validateSupportConfiguration(array $pricingConfig): void
    {
        if (!isset($pricingConfig['support'])) {
            return;
        }

        $support = $pricingConfig['support'];

        if (isset($support['coefficients'])) {
            foreach ($support['coefficients'] as $tier => $coefficient) {
                $this->validatePercentage("support.coefficients.{$tier}", $coefficient);
            }
        }

        if (isset($support['thresholds'])) {
            $this->validateSupportThreshold('small', $support['thresholds']['small'] ?? null);
            $this->validateSupportThreshold('medium', $support['thresholds']['medium'] ?? null);
        }

        if (isset($support['max_monthly'])) {
            if (!is_numeric($support['max_monthly']) || $support['max_monthly'] <= 0) {
                throw PricingConfigurationException::invalidSupportThreshold('max_monthly', $support['max_monthly']);
            }
        }
    }

    /**
     * Validates a percentage value
     */
    private function validatePercentage(string $configKey, mixed $value): void
    {
        if (!is_numeric($value) || $value < 0 || $value > 1) {
            throw PricingConfigurationException::invalidPercentage($configKey, $value);
        }
    }

    /**
     * Validates a support threshold value
     */
    private function validateSupportThreshold(string $threshold, mixed $value): void
    {
        if ($value === null) {
            return; // Optional threshold
        }

        if (!is_numeric($value) || $value <= 0) {
            throw PricingConfigurationException::invalidSupportThreshold($threshold, $value);
        }
    }
}
