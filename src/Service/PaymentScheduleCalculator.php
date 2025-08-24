<?php

namespace App\Service;

class PaymentScheduleCalculator
{
    private array $paymentConfig;

    public function __construct(array $pricingConfig)
    {
        $this->paymentConfig = $pricingConfig['payment_schedules'] ?? [];
    }

    /**
     * Calculate payment schedule based on project size
     */
    public function calculatePaymentSchedule(float $totalLow, float $totalHigh): array
    {
        $thresholds = $this->getThresholds();
        $schedule = $this->determineScheduleType($totalHigh, $thresholds);

        return $this->formatPaymentSchedule($totalLow, $totalHigh, $schedule);
    }

    /**
     * Get payment thresholds from configuration
     */
    private function getThresholds(): array
    {
        return $this->paymentConfig['thresholds'] ?? [
            'small_project' => 500,
            'medium_project' => 3000,
        ];
    }

    /**
     * Determine the appropriate payment schedule type
     */
    private function determineScheduleType(float $totalHigh, array $thresholds): array
    {
        if ($totalHigh < $thresholds['small_project']) {
            // Small projects: full payment on completion
            return $this->paymentConfig['small'] ?? [
                ['label' => 'Full payment on completion', 'low_percent' => 1.0, 'high_percent' => 1.0]
            ];
        } elseif ($totalHigh >= $thresholds['small_project'] && $totalHigh <= $thresholds['medium_project']) {
            // Medium projects: 50% deposit, 50% on completion
            return $this->paymentConfig['medium'] ?? [
                ['label' => 'Deposit (50%)', 'low_percent' => 0.5, 'high_percent' => 0.5],
                ['label' => 'Final payment (50%)', 'low_percent' => 0.5, 'high_percent' => 0.5]
            ];
        } else {
            // Large projects: 4-stage payment
            return $this->paymentConfig['large'] ?? [
                ['label' => 'Deposit (40%)', 'low_percent' => 0.4, 'high_percent' => 0.4],
                ['label' => 'Design Sign Off (25%)', 'low_percent' => 0.25, 'high_percent' => 0.2],
                ['label' => 'Initial Build Completed (25%)', 'low_percent' => 0.25, 'high_percent' => 0.2],
                ['label' => 'Go Live (10%)', 'low_percent' => 0.1, 'high_percent' => 0.2]
            ];
        }
    }

    /**
     * Format payment schedule with actual amounts
     */
    private function formatPaymentSchedule(float $totalLow, float $totalHigh, array $schedule): array
    {
        $paymentSchedule = [];

        foreach ($schedule as $payment) {
            $paymentSchedule[] = [
                'label' => $payment['label'],
                'low' => round($totalLow * $payment['low_percent']),
                'high' => round($totalHigh * $payment['high_percent']),
            ];
        }

        return $paymentSchedule;
    }
}
