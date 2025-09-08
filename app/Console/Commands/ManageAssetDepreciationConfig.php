<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class ManageAssetDepreciationConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:config-depreciation 
                            {action : Action to perform (show, set, reset)}
                            {--key= : Configuration key to set}
                            {--value= : Value to set for the key}
                            {--category= : Asset category for category-specific settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage asset depreciation configuration settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        switch ($action) {
            case 'show':
                $this->showConfiguration();
                break;
            case 'set':
                $this->setConfiguration();
                break;
            case 'reset':
                $this->resetConfiguration();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->error("Valid actions: show, set, reset");
                return 1;
        }
        
        return 0;
    }
    
    /**
     * Show current configuration
     */
    private function showConfiguration(): void
    {
        $this->info('📊 Asset Depreciation Configuration');
        $this->info('================================');
        
        // Get configuration from settings
        $config = \App\Models\AssetDepreciationSetting::getConfigurationArray();
        
        // Default settings
        $this->info("\n🔧 Default Settings:");
        $this->table(
            ['Setting', 'Value', 'Description'],
            [
                ['Default Useful Life', ($config['default_useful_life_years'] ?? 5) . ' years', 'Default useful life for new assets'],
                ['Default Salvage Value', '$' . ($config['default_salvage_value'] ?? 0), 'Default salvage value for new assets'],
                ['Default Method', $config['default_method'] ?? 'straight_line', 'Default depreciation method'],
                ['Calculation Frequency', $config['calculation_frequency'] ?? 'monthly', 'How often depreciation is calculated'],
            ]
        );
        
        // Category overrides
        $categoryOverrides = $config['category_overrides'] ?? [];
        if (!empty($categoryOverrides)) {
            $this->info("\n🏷️  Category-Specific Overrides:");
            $categoryData = [];
            foreach ($categoryOverrides as $category => $settings) {
                $categoryData[] = [
                    ucfirst($category),
                    ($settings['useful_life_years'] ?? 'N/A') . ' years',
                    '$' . ($settings['salvage_value'] ?? 'N/A'),
                ];
            }
            $this->table(
                ['Category', 'Useful Life', 'Salvage Value'],
                $categoryData
            );
        } else {
            $this->info("\n🏷️  Category-Specific Overrides: None configured");
        }
        
        // Methods
        $this->info("\n📈 Available Depreciation Methods:");
        $methods = $config['methods'] ?? [];
        $methodData = [];
        foreach ($methods as $key => $method) {
            $methodData[] = [
                $key,
                $method['name'] ?? $key,
                $method['description'] ?? 'No description',
                ($method['enabled'] ?? true) ? '✅ Enabled' : '❌ Disabled',
            ];
        }
        $this->table(
            ['Key', 'Name', 'Description', 'Status'],
            $methodData
        );
    }
    
    /**
     * Set configuration value
     */
    private function setConfiguration(): void
    {
        $key = $this->option('key');
        $value = $this->option('value');
        $category = $this->option('category');
        
        if (!$key || !$value) {
            $this->error('Both --key and --value are required for set action');
            $this->error('Example: php artisan assets:config-depreciation set --key=default_useful_life_years --value=7');
            return;
        }
        
        if ($category) {
            $this->setCategoryOverride($category, $key, $value);
        } else {
            $this->setDefaultSetting($key, $value);
        }
    }
    
    /**
     * Set default setting
     */
    private function setDefaultSetting(string $key, string $value): void
    {
        $configPath = config_path('asset-depreciation.php');
        
        if (!File::exists($configPath)) {
            $this->error("Configuration file not found: {$configPath}");
            return;
        }
        
        $config = require $configPath;
        
        // Validate key
        $validKeys = [
            'default_useful_life_years' => 'integer',
            'default_salvage_value' => 'numeric',
            'default_method' => 'string',
            'calculation_frequency' => 'string',
        ];
        
        if (!isset($validKeys[$key])) {
            $this->error("Invalid key: {$key}");
            $this->error("Valid keys: " . implode(', ', array_keys($validKeys)));
            return;
        }
        
        // Validate value type
        $expectedType = $validKeys[$key];
        if ($expectedType === 'integer' && !is_numeric($value)) {
            $this->error("Value must be a number for key: {$key}");
            return;
        }
        
        if ($expectedType === 'numeric' && !is_numeric($value)) {
            $this->error("Value must be a number for key: {$key}");
            return;
        }
        
        // Update config
        $config[$key] = $expectedType === 'integer' ? (int) $value : $value;
        
        // Write back to file
        $this->writeConfigFile($configPath, $config);
        
        $this->info("✅ Updated {$key} = {$value}");
        $this->info("Configuration saved to {$configPath}");
    }
    
    /**
     * Set category override
     */
    private function setCategoryOverride(string $category, string $key, string $value): void
    {
        $configPath = config_path('asset-depreciation.php');
        
        if (!File::exists($configPath)) {
            $this->error("Configuration file not found: {$configPath}");
            return;
        }
        
        $config = require $configPath;
        
        // Initialize category_overrides if it doesn't exist
        if (!isset($config['category_overrides'])) {
            $config['category_overrides'] = [];
        }
        
        // Initialize category if it doesn't exist
        $categoryLower = strtolower($category);
        if (!isset($config['category_overrides'][$categoryLower])) {
            $config['category_overrides'][$categoryLower] = [];
        }
        
        // Validate key
        $validKeys = [
            'useful_life_years' => 'integer',
            'salvage_value' => 'numeric',
        ];
        
        if (!isset($validKeys[$key])) {
            $this->error("Invalid key for category override: {$key}");
            $this->error("Valid keys: " . implode(', ', array_keys($validKeys)));
            return;
        }
        
        // Validate value type
        $expectedType = $validKeys[$key];
        if ($expectedType === 'integer' && !is_numeric($value)) {
            $this->error("Value must be a number for key: {$key}");
            return;
        }
        
        if ($expectedType === 'numeric' && !is_numeric($value)) {
            $this->error("Value must be a number for key: {$key}");
            return;
        }
        
        // Update config
        $config['category_overrides'][$categoryLower][$key] = $expectedType === 'integer' ? (int) $value : (float) $value;
        
        // Write back to file
        $this->writeConfigFile($configPath, $config);
        
        $this->info("✅ Updated category '{$category}' {$key} = {$value}");
        $this->info("Configuration saved to {$configPath}");
    }
    
    /**
     * Reset configuration to defaults
     */
    private function resetConfiguration(): void
    {
        $configPath = config_path('asset-depreciation.php');
        
        if (!File::exists($configPath)) {
            $this->error("Configuration file not found: {$configPath}");
            return;
        }
        
        if ($this->confirm('Are you sure you want to reset all configuration to defaults?')) {
            // Create default config
            $defaultConfig = [
                'default_useful_life_years' => 5,
                'default_salvage_value' => 0,
                'default_method' => 'straight_line',
                'calculation_frequency' => 'monthly',
                'recalculation_threshold_days' => 30,
                'use_queue' => true,
                'queue_name' => 'default',
                'log_calculations' => true,
                'log_level' => 'info',
                'notify_on_completion' => false,
                'admin_email' => config('mail.admin_address'),
                'batch_size' => 100,
                'timeout_minutes' => 60,
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
                        'rate_multiplier' => 2.0,
                    ],
                    'sum_of_years' => [
                        'name' => 'Sum of Years',
                        'description' => 'Depreciation based on remaining useful life',
                        'enabled' => true,
                    ],
                ],
                'category_overrides' => [
                    'computers' => [
                        'useful_life_years' => 3,
                        'salvage_value' => 100,
                    ],
                    'furniture' => [
                        'useful_life_years' => 7,
                        'salvage_value' => 0,
                    ],
                    'vehicles' => [
                        'useful_life_years' => 5,
                        'salvage_value' => 1000,
                    ],
                    'buildings' => [
                        'useful_life_years' => 30,
                        'salvage_value' => 0,
                    ],
                ],
                'validation' => [
                    'min_useful_life_years' => 1,
                    'max_useful_life_years' => 100,
                    'min_salvage_value' => 0,
                    'max_salvage_value_percentage' => 50,
                ],
            ];
            
            $this->writeConfigFile($configPath, $defaultConfig);
            
            $this->info("✅ Configuration reset to defaults");
            $this->info("Configuration saved to {$configPath}");
        } else {
            $this->info("Configuration reset cancelled");
        }
    }
    
    /**
     * Write configuration to file
     */
    private function writeConfigFile(string $path, array $config): void
    {
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        File::put($path, $content);
    }
}
