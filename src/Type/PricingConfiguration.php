<?php

namespace App\Type;

/**
 * Type definitions for pricing configuration arrays
 * This helps PHPStan understand the structure of our configuration arrays
 */
class PricingConfiguration
{
    /**
     * @var array<string, mixed>
     */
    public const PROJECT_TYPES = [
        'holding_page' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
        'brochure_site' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
        'ecommerce' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
        'web_app' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
        'mobile_app' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
        'api' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
        'bespoke' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
    ];

    /**
     * @var array<string, mixed>
     */
    public const FEATURES = [
        'authentication' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
        'payment_integration' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
        'reporting' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
        'cms' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
        'seo' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
        'responsive_design' => ['days' => 'int', 'title' => 'string', 'description' => 'string'],
    ];

    /**
     * @var array<string, mixed>
     */
    public const MULTIPLIERS = [
        'complexity' => ['simple' => 'float', 'medium' => 'float', 'complex' => 'float'],
        'risk' => ['low' => 'float', 'medium' => 'float', 'high' => 'float'],
        'speed' => ['normal' => 'float', 'fast' => 'float', 'rush' => 'float'],
        'discovery' => ['no' => 'float', 'yes' => 'float'],
        'support' => ['no' => 'float', 'yes' => 'float'],
        'compliance' => ['basic' => 'float', 'advanced' => 'float', 'enterprise' => 'float'],
        'real_time' => ['yes' => 'float', 'no' => 'float'],
    ];

    /**
     * @var array<string, mixed>
     */
    public const PHASES = [
        'total_base' => 'float',
        'base_percentages' => [
            'discovery' => 'float',
            'design' => 'float',
            'development' => 'float',
            'testing' => 'float',
        ],
    ];

    /**
     * @var array<string, mixed>
     */
    public const PAYMENT_SCHEDULES = [
        'thresholds' => [
            'small_project' => 'int',
            'medium_project' => 'int',
        ],
        'schedules' => [
            'small_project' => ['deposit' => 'float', 'milestone' => 'float', 'completion' => 'float'],
            'medium_project' => ['deposit' => 'float', 'milestone' => 'float', 'completion' => 'float'],
            'large_project' => ['deposit' => 'float', 'milestone' => 'float', 'completion' => 'float'],
        ],
    ];

    /**
     * @var array<string, mixed>
     */
    public const SUPPORT = [
        'coefficients' => ['small' => 'float', 'medium' => 'float', 'large' => 'float'],
        'thresholds' => ['small' => 'int', 'medium' => 'int'],
        'max_monthly' => 'int',
    ];

    /**
     * @var array<string, mixed>
     */
    public const BUNDLES = [
        'days_per_bundle' => 'float',
    ];

    /**
     * @var array<string, mixed>
     */
    public const DAY_RATE = [
        'min' => 'int',
        'max' => 'int',
    ];
}
