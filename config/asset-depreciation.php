<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Asset Depreciation Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for asset depreciation calculations.
    | You can customize default values, calculation methods, and scheduling.
    |
    */

    // Default depreciation settings for new assets
    'default_useful_life_years' => env('ASSET_DEPRECIATION_DEFAULT_LIFE', 5),
    'default_salvage_value' => env('ASSET_DEPRECIATION_DEFAULT_SALVAGE', 0),
    'default_method' => env('ASSET_DEPRECIATION_DEFAULT_METHOD', 'straight_line'),

    // Calculation frequency
    'calculation_frequency' => env('ASSET_DEPRECIATION_FREQUENCY', 'monthly'), // daily, weekly, monthly, quarterly

    // Recalculation threshold (days)
    'recalculation_threshold_days' => env('ASSET_DEPRECIATION_RECALC_THRESHOLD', 30),

    // Queue settings
    'use_queue' => env('ASSET_DEPRECIATION_USE_QUEUE', true),
    'queue_name' => env('ASSET_DEPRECIATION_QUEUE', 'default'),

    // Logging
    'log_calculations' => env('ASSET_DEPRECIATION_LOG', true),
    'log_level' => env('ASSET_DEPRECIATION_LOG_LEVEL', 'info'),

    // Email notifications
    'notify_on_completion' => env('ASSET_DEPRECIATION_NOTIFY', false),
    'admin_email' => env('ASSET_DEPRECIATION_ADMIN_EMAIL', config('mail.admin_address')),

    // Performance settings
    'batch_size' => env('ASSET_DEPRECIATION_BATCH_SIZE', 100),
    'timeout_minutes' => env('ASSET_DEPRECIATION_TIMEOUT', 60),

    // Depreciation methods configuration
    'methods' => [
        'straight_line' => [
            'name' => 'Straight Line',
            'description' => 'Equal depreciation amount each year',
            'enabled' => true,
        ],
        'declining_balance' => [
            'name' => 'Declining Balance',
            'description' => 'Depreciation decreases over time',
            'enabled' => true,
            'rate_multiplier' => 2.0, // Double declining balance
        ],
        'sum_of_years' => [
            'name' => 'Sum of Years',
            'description' => 'Depreciation based on remaining useful life',
            'enabled' => true,
        ],
    ],

    // Asset category specific settings
    'category_overrides' => [
        // Example: Different useful life for different asset types
        'computers' => [
            'useful_life_years' => 3,
            'salvage_value' => 100,
            'depreciation_method' => 'declining_balance', // Computers depreciate faster
        ],
        'furniture' => [
            'useful_life_years' => 7,
            'salvage_value' => 0,
            'depreciation_method' => 'straight_line', // Furniture depreciates evenly
        ],
        'vehicles' => [
            'useful_life_years' => 5,
            'salvage_value' => 1000,
            'depreciation_method' => 'declining_balance', // Vehicles lose value quickly
        ],
        'buildings' => [
            'useful_life_years' => 30,
            'salvage_value' => 0,
            'depreciation_method' => 'straight_line', // Buildings depreciate slowly and evenly
        ],
        'machinery' => [
            'useful_life_years' => 10,
            'salvage_value' => 500,
            'depreciation_method' => 'sum_of_years', // Machinery has variable depreciation
        ],
        'office_equipment' => [
            'useful_life_years' => 4,
            'salvage_value' => 50,
            'depreciation_method' => 'declining_balance', // Office equipment becomes obsolete quickly
        ],
    ],

    // Validation rules
    'validation' => [
        'min_useful_life_years' => 1,
        'max_useful_life_years' => 100,
        'min_salvage_value' => 0,
        'max_salvage_value_percentage' => 50, // Max 50% of original value
    ],
];
