<?php

namespace App\Service;

class PaymentScheduleCalculator
{
    /** @var array<string, mixed> */
    private array $paymentConfig;

    /** @param array<string, mixed> $pricingConfig */
    public function __construct(array $pricingConfig)
    {
        $this->paymentConfig = $pricingConfig['payment_schedules'] ?? [];
    }

    /**
     * Calculate payment schedule based on project size.
     */
    /** @return list<array<string, mixed>> */
    public function calculatePaymentSchedule(float $totalLow, float $totalHigh): array
    {
        $thresholds = $this->getThresholds();
        $schedule = $this->determineScheduleType($totalHigh, $thresholds);

        return $this->formatPaymentSchedule($totalLow, $totalHigh, $schedule);
    }

    /**
     * Get payment thresholds from configuration.
     */
    /** @return array<string, int> */
    private function getThresholds(): array
    {
        return $this->paymentConfig['thresholds'] ?? [
            'small_project' => 500,
            'medium_project' => 3000,
        ];
    }

    /**
     * Determine the appropriate payment schedule type.
     */
    /**
     * @phpstan-param array<string, int> $thresholds
     *
     * @return list<array<string, float>>
     */
    private function determineScheduleType(float $totalHigh, array $thresholds): array
    {
        if ($totalHigh < $thresholds['small_project']) {
            // Small projects: full payment on completion
            return $this->paymentConfig['small'] ?? [
                ['label' => 'Full payment on completion', 'low_percent' => 1.0, 'high_percent' => 1.0],
            ];
        } elseif ($totalHigh >= $thresholds['small_project'] && $totalHigh <= $thresholds['medium_project']) {
            // Medium projects: 50% deposit, 50% on completion
            return $this->paymentConfig['medium'] ?? [
                ['label' => 'Deposit (50%)', 'low_percent' => 0.5, 'high_percent' => 0.5],
                ['label' => 'Final payment (50%)', 'low_percent' => 0.5, 'high_percent' => 0.5],
            ];
        } else {
            // Large projects: 4-stage payment
            return $this->paymentConfig['large'] ?? [
                ['label' => 'Deposit (40%)', 'low_percent' => 0.4, 'high_percent' => 0.4],
                ['label' => 'Design Sign Off (25%)', 'low_percent' => 0.25, 'high_percent' => 0.2],
                ['label' => 'Initial Build Completed (25%)', 'low_percent' => 0.25, 'high_percent' => 0.2],
                ['label' => 'Go Live (10%)', 'low_percent' => 0.1, 'high_percent' => 0.2],
            ];
        }
    }

    /**
     * Format payment schedule with actual amounts.
     */
    /**
     * @phpstan-param list<array<string, string|float>> $schedule
     *
     * @return list<array<string, mixed>>
     */
    private function formatPaymentSchedule(float $totalLow, float $totalHigh, array $schedule): array
    {
        $paymentSchedule = [];

        foreach ($schedule as $payment) {
            $lowPercent = is_numeric($payment['low_percent']) ? (float) $payment['low_percent'] : 0.0;
            $highPercent = is_numeric($payment['high_percent']) ? (float) $payment['high_percent'] : 0.0;

            $paymentSchedule[] = [
                'label' => $payment['label'],
                'low' => round($totalLow * $lowPercent),
                'high' => round($totalHigh * $highPercent),
            ];
        }

        return $paymentSchedule;
    }
}
