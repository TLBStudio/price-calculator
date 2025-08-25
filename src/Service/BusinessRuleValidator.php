<?php

namespace App\Service;

class BusinessRuleValidator
{
    /** @var array<string, mixed> */
    private array $pricingConfig;

    /** @param array<string, mixed> $pricingConfig */
    public function __construct(array $pricingConfig)
    {
        $this->pricingConfig = $pricingConfig;
    }

    /**
     * Validates business rules beyond basic form validation.
     */
    /**
     * @phpstan-param array<string, string|array<string>|null> $data
     *
     * @return list<string>
     */
    public function validateBusinessRules(array $data): array
    {
        $warnings = [];

        // Check if project type and features are compatible
        if (isset($data['projectType']) && isset($data['features']) && is_array($data['features'])) {
            /** @var string $projectType */
            $projectType = $data['projectType'];
            /** @var array<string> $features */
            $features = $data['features'];
            $compatibilityIssues = $this->checkFeatureCompatibility($projectType, $features);
            if (!empty($compatibilityIssues['incompatible'])) {
                $warnings[] = $compatibilityIssues['message'];
            }
        }

        // Check for unrealistic complexity combinations
        if (isset($data['complexity']) && isset($data['speed'])) {
            if ('very_high' === $data['complexity'] && 'urgent' === $data['speed']) {
                $warnings[] = 'Very high complexity with urgent timeline may not be realistic. Consider extending the timeline or reducing complexity.';
            }
        }

        // Check for high-risk combinations
        if (isset($data['risk']) && isset($data['support'])) {
            if ('very_high' === $data['risk'] && 'high' === $data['support']) {
                $warnings[] = 'Very high risk with high support requirements may significantly impact long-term costs.';
            }
        }

        // Check for compliance and real-time combinations
        if (isset($data['compliance']) && isset($data['realTime'])) {
            if ('very_high' === $data['compliance'] && 'very_high' === $data['realTime']) {
                $warnings[] = 'Very high compliance requirements with very high real-time requirements may significantly increase project complexity and cost.';
            }
        }

        // Validate feature combinations
        if (isset($data['features']) && is_array($data['features'])) {
            /** @var array<string> $features */
            $features = $data['features'];
            $featureIssues = $this->validateFeatureCombinations($features);
            // Add all feature issues to warnings
            foreach ($featureIssues as $issue) {
                if (is_array($issue) && isset($issue['message']) && is_string($issue['message'])) {
                    $warnings[] = $issue['message'];
                }
            }
        }

        // Ensure we only return strings
        $stringWarnings = [];
        foreach ($warnings as $warning) {
            if (is_string($warning)) {
                $stringWarnings[] = $warning;
            }
        }
        return $stringWarnings;
    }

    /**
     * Get compatibility warnings for display in frontend.
     */
    /**
     * @phpstan-param array<string, string|array<string>|null> $data
     *
     * @return list<array<string, mixed>>
     */
    public function getCompatibilityWarnings(array $data): array
    {
        $warnings = [];

        if (isset($data['projectType']) && isset($data['features']) && is_array($data['features'])) {
            /** @var string $projectType */
            $projectType = $data['projectType'];
            /** @var array<string> $features */
            $features = $data['features'];
            $compatibilityIssues = $this->checkFeatureCompatibility($projectType, $features);
            if (!empty($compatibilityIssues['incompatible'])) {
                $warnings[] = [
                    'type' => 'incompatibility',
                    'message' => $compatibilityIssues['message'],
                    'incompatible_features' => $compatibilityIssues['incompatible'],
                ];
            }
        }

        if (isset($data['features']) && is_array($data['features'])) {
            /** @var array<string> $features */
            $features = $data['features'];
            $featureIssues = $this->validateFeatureCombinations($features);

            // Add all feature issues to warnings
            foreach ($featureIssues as $issue) {
                $warnings[] = $issue;
            }
        }

        return $warnings;
    }

    /**
     * Check if selected features are compatible with the project type.
     */
    /**
     * @phpstan-param array<string> $features
     *
     * @return array<string, array<string>|string>
     */
    private function checkFeatureCompatibility(string $projectType, array $features): array
    {
        $incompatible = [];
        $message = '';

        if (isset($this->pricingConfig['compatibility']['project_type_incompatibilities'][$projectType])) {
            $rule = $this->pricingConfig['compatibility']['project_type_incompatibilities'][$projectType];
            $incompatibleFeatures = $rule['incompatible_features'];
            $message = $rule['message'];

            foreach ($features as $feature) {
                if (in_array($feature, $incompatibleFeatures)) {
                    $incompatible[] = $feature;
                }
            }
        }

        return [
            'incompatible' => $incompatible,
            'message' => $message,
        ];
    }

    /**
     * Validate feature combinations for logical consistency.
     */
    /**
     * @phpstan-param array<string> $features
     *
     * @return list<array<string, array<string>|string>>
     */
    private function validateFeatureCombinations(array $features): array
    {
        $allIssues = [];

        // Check for all conflicting feature combinations defined in config
        if (isset($this->pricingConfig['compatibility']['feature_incompatibilities'])) {
            foreach ($this->pricingConfig['compatibility']['feature_incompatibilities'] as $conflictType => $rule) {
                if (isset($rule['conflicting_features'])) {
                    $conflictingFeatures = $rule['conflicting_features'];
                    $selectedConflictingFeatures = array_intersect($features, $conflictingFeatures);

                    if (count($selectedConflictingFeatures) > 1) {
                        $allIssues[] = [
                            'type' => 'conflict',
                            'message' => $rule['message'],
                            'conflicting_features' => array_values($selectedConflictingFeatures),
                        ];
                    }
                }
            }
        }

        // Check for feature dependencies
        if (isset($this->pricingConfig['compatibility']['feature_dependencies'])) {
            foreach ($this->pricingConfig['compatibility']['feature_dependencies'] as $featureType => $rule) {
                if (isset($rule['required_features'])) {
                    $requiredFeatures = $rule['required_features'];

                    if (in_array($featureType, $features)) {
                        $missingFeatures = array_diff($requiredFeatures, $features);
                        if (!empty($missingFeatures)) {
                            $allIssues[] = [
                                'type' => 'dependency',
                                'message' => $rule['message'],
                                'incompatible_features' => array_values($missingFeatures),
                            ];
                        }
                    }
                }
            }
        }

        return $allIssues;
    }
}
