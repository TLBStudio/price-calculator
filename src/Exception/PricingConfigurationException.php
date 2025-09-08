<?php

namespace App\Exception;

class PricingConfigurationException extends \RuntimeException
{
    public static function missingRequiredConfig(string $configKey): self
    {
        return new self(sprintf('Missing required pricing configuration: %s', $configKey));
    }

    public static function invalidDayRate(string $rateType, mixed $value): self
    {
        return new self(sprintf('Invalid day rate for %s: %s. Must be a positive number.', $rateType, var_export($value, true)));
    }

    public static function invalidPercentage(string $configKey, mixed $value): self
    {
        return new self(sprintf('Invalid percentage for %s: %s. Must be a number between 0 and 1.', $configKey, var_export($value, true)));
    }

    public static function invalidMultiplier(string $multiplierType, string $key, mixed $value): self
    {
        return new self(sprintf('Invalid multiplier for %s[%s]: %s. Must be a positive number.', $multiplierType, $key, var_export($value, true)));
    }

    public static function invalidProjectType(string $projectType): self
    {
        return new self(sprintf('Invalid project type: %s', $projectType));
    }

    public static function invalidProjectTypeConfiguration(string $projectType, string $reason): self
    {
        return new self(sprintf('Invalid project type configuration for %s: %s', $projectType, $reason));
    }

    public static function invalidFeature(string $feature): self
    {
        return new self(sprintf('Invalid feature: %s', $feature));
    }

    public static function invalidFeatureConfiguration(string $feature, string $reason): self
    {
        return new self(sprintf('Invalid feature configuration for %s: %s', $feature, $reason));
    }

    public static function phasePercentagesMismatch(float $total, float $expected = 0.95): self
    {
        return new self(sprintf('Phase base percentages total %f, expected %f (95%% of remaining space after discovery). Check phase configuration.', $total, $expected));
    }

    public static function invalidPaymentSchedule(string $scheduleType, string $reason): self
    {
        return new self(sprintf('Invalid payment schedule for %s: %s', $scheduleType, $reason));
    }

    public static function invalidSupportThreshold(string $threshold, mixed $value): self
    {
        return new self(sprintf('Invalid support threshold for %s: %s. Must be a positive number.', $threshold, var_export($value, true)));
    }

    public static function invalidPaymentThreshold(string $threshold, mixed $value): self
    {
        return new self(sprintf('Invalid payment threshold for %s: %s. Must be a positive number.', $threshold, var_export($value, true)));
    }

    public static function missingRequiredField(string $fieldName): self
    {
        return new self(sprintf("%s is required", ucfirst($fieldName)));
    }

    public static function invalidBundleQuantity(string $message): self
    {
        return new self($message);
    }

    public static function missingRequiredMultiplier(string $multiplier): self
    {
        return new self(sprintf("Multiplier %s is required", $multiplier));
    }
}
