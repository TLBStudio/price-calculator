<?php

namespace App\Service;

class BusinessRuleValidator
{
    private array $pricingConfig;

    public function __construct(array $pricingConfig)
    {
        $this->pricingConfig = $pricingConfig;
    }

    /**
     * Validates business rules beyond basic form validation
     */
    public function validateBusinessRules(array $data): array
    {
        $warnings = [];

        // Check if project type and features are compatible
        if (isset($data['projectType']) && isset($data['features'])) {
            $compatibilityIssues = $this->checkFeatureCompatibility($data['projectType'], $data['features']);
            if (!empty($compatibilityIssues['incompatible'])) {
                $warnings[] = $compatibilityIssues['message'];
            }
        }

        // Check for unrealistic complexity combinations
        if (isset($data['complexity']) && isset($data['speed'])) {
            if ($data['complexity'] === 'very_high' && $data['speed'] === 'urgent') {
                $warnings[] = 'Very high complexity with urgent timeline may not be realistic. Consider extending the timeline or reducing complexity.';
            }
        }

        // Check for high-risk combinations
        if (isset($data['risk']) && isset($data['support'])) {
            if ($data['risk'] === 'very_high' && $data['support'] === 'high') {
                $warnings[] = 'Very high risk with high support requirements may significantly impact long-term costs.';
            }
        }

        // Check for compliance and real-time combinations
        if (isset($data['compliance']) && isset($data['realTime'])) {
            if ($data['compliance'] === 'very_high' && $data['realTime'] === 'very_high') {
                $warnings[] = 'Very high compliance requirements with very high real-time requirements may significantly increase project complexity and cost.';
            }
        }

        // Validate feature combinations
        if (isset($data['features'])) {
            $featureIssues = $this->validateFeatureCombinations($data['features']);
            if (!empty($featureIssues['incompatible'])) {
                $warnings[] = $featureIssues['message'];
            }
        }

        return $warnings;
    }

    /**
     * Get compatibility warnings for display in frontend
     */
    public function getCompatibilityWarnings(array $data): array
    {
        $warnings = [];

        if (isset($data['projectType']) && isset($data['features'])) {
            $compatibilityIssues = $this->checkFeatureCompatibility($data['projectType'], $data['features']);
            if (!empty($compatibilityIssues['incompatible'])) {
                $warnings[] = [
                    'type' => 'incompatibility',
                    'message' => $compatibilityIssues['message'],
                    'incompatible_features' => $compatibilityIssues['incompatible']
                ];
            }
        }

        if (isset($data['features'])) {
            $featureIssues = $this->validateFeatureCombinations($data['features']);

            // Add all feature issues to warnings
            foreach ($featureIssues as $issue) {
                $warnings[] = $issue;
            }
        }

        return $warnings;
    }

    /**
     * Check if selected features are compatible with the project type
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
            'message' => $message
        ];
    }

    /**
     * Validate feature combinations for logical consistency
     */
    private function validateFeatureCombinations(array $features): array
    {
        $allIssues = [];

        // Check for conflicting design features
        if (isset($this->pricingConfig['compatibility']['feature_incompatibilities']['design_features'])) {
            $rule = $this->pricingConfig['compatibility']['feature_incompatibilities']['design_features'];
            $conflictingFeatures = $rule['conflicting_features'];
            $selectedConflictingFeatures = array_intersect($features, $conflictingFeatures);

            if (count($selectedConflictingFeatures) > 1) {
                $allIssues[] = [
                    'type' => 'conflict',
                    'message' => $rule['message'],
                    'conflicting_features' => array_values($selectedConflictingFeatures)
                ];
            }
        }

        // Check for conflicting integration features
        if (isset($this->pricingConfig['compatibility']['feature_incompatibilities']['integration_features'])) {
            $rule = $this->pricingConfig['compatibility']['feature_incompatibilities']['integration_features'];
            $conflictingFeatures = $rule['conflicting_features'];
            $selectedConflictingFeatures = array_intersect($features, $conflictingFeatures);

            if (count($selectedConflictingFeatures) > 1) {
                $allIssues[] = [
                    'type' => 'conflict',
                    'message' => $rule['message'],
                    'conflicting_features' => array_values($selectedConflictingFeatures)
                ];
            }
        }

        // Check for ecommerce dependencies
        if (isset($this->pricingConfig['compatibility']['feature_dependencies']['ecommerce_features'])) {
            $rule = $this->pricingConfig['compatibility']['feature_dependencies']['ecommerce_features'];
            $requiredFeatures = $rule['required_features'];

            if (in_array('ecommerce_features', $features)) {
                $missingFeatures = array_diff($requiredFeatures, $features);
                if (!empty($missingFeatures)) {
                    $allIssues[] = [
                        'type' => 'dependency',
                        'message' => $rule['message'],
                        'incompatible_features' => array_values($missingFeatures)
                    ];
                }
            }
        }

        return $allIssues;
    }
}
