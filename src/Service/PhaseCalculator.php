<?php

namespace App\Service;

class PhaseCalculator
{
    private array $phaseConfig;

    public function __construct(array $pricingConfig)
    {
        $this->phaseConfig = $pricingConfig['phases'] ?? [];
    }

    /**
     * Calculate phase breakdown for a project.
     */
    public function calculatePhases(float $totalLow, float $totalHigh, float $discoveryFactor): array
    {
        $phasePercentages = $this->calculatePhasePercentages($discoveryFactor);

        return $this->calculatePhaseCosts($totalLow, $totalHigh, $phasePercentages);
    }

    /**
     * Calculate phase percentages based on discovery factor.
     */
    private function calculatePhasePercentages(float $discoveryFactor): array
    {
        // Calculate dynamic discovery percentage using only the decimal part of discovery factor
        $discoveryPercentage = $discoveryFactor - 1;

        // Get base phase percentages from configuration (percentages of remaining space)
        $basePercentages = $this->phaseConfig['base_percentages'] ?? [
            'project_management' => 0.15,
            'design' => 0.10,
            'build' => 0.55,
            'qa' => 0.15,
        ];

        // Calculate the remaining percentage after discovery
        $remainingPercentage = 1.0 - $discoveryPercentage;

        $phasePercentages = [
            'discovery' => round($discoveryPercentage, 3),
        ];

        // Calculate other phases as percentages of the remaining space
        foreach ($basePercentages as $phase => $basePercentage) {
            $phasePercentages[$phase] = round($basePercentage * $remainingPercentage, 3);
        }

        // Calculate Deployment as the remainder to ensure total equals exactly 1.0
        $deploymentPercentage = 1.0 - array_sum($phasePercentages);
        $phasePercentages['Deployment'] = round($deploymentPercentage, 3);

        return $phasePercentages;
    }

    /**
     * Calculate actual costs for each phase.
     */
    private function calculatePhaseCosts(float $totalLow, float $totalHigh, array $phasePercentages): array
    {
        $phases = [];

        foreach ($phasePercentages as $phase => $percent) {
            $phases[$phase] = [
                'low' => round($totalLow * $percent),
                'high' => round($totalHigh * $percent),
            ];
        }

        return $phases;
    }
}
