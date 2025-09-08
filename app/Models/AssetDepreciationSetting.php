<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetDepreciationSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asset_depreciation_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'category',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'value' => 'json',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // Setting types
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_STRING = 'string';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';

    // Setting categories
    const CATEGORY_DEFAULT = 'default';
    const CATEGORY_CATEGORY_OVERRIDE = 'category_override';
    const CATEGORY_METHOD = 'method';
    const CATEGORY_SYSTEM = 'system';

    /**
     * Get setting value with type casting
     */
    public function getTypedValue()
    {
        $value = $this->value;
        
        switch ($this->type) {
            case self::TYPE_INTEGER:
                return (int) $value;
            case self::TYPE_FLOAT:
                return (float) $value;
            case self::TYPE_BOOLEAN:
                return (bool) $value;
            case self::TYPE_JSON:
                return is_string($value) ? json_decode($value, true) : $value;
            default:
                return $value;
        }
    }

    /**
     * Set setting value with automatic type detection
     */
    public function setTypedValue($value)
    {
        if (is_int($value)) {
            $this->type = self::TYPE_INTEGER;
        } elseif (is_float($value)) {
            $this->type = self::TYPE_FLOAT;
        } elseif (is_bool($value)) {
            $this->type = self::TYPE_BOOLEAN;
        } elseif (is_array($value) || is_object($value)) {
            $this->type = self::TYPE_JSON;
        } else {
            $this->type = self::TYPE_STRING;
        }
        
        $this->value = $value;
    }

    /**
     * Scope for active settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for settings by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for settings by key
     */
    public function scopeByKey($query, $key)
    {
        return $query->where('key', $key);
    }

    /**
     * Get all settings as a configuration array
     */
    public static function getConfigurationArray(): array
    {
        $settings = self::active()->get();
        $config = [];

        foreach ($settings as $setting) {
            $value = $setting->getTypedValue();
            
            if ($setting->category === self::CATEGORY_DEFAULT) {
                $config[$setting->key] = $value;
            } elseif ($setting->category === self::CATEGORY_CATEGORY_OVERRIDE) {
                $metadata = $setting->metadata ?? [];
                $assetCategory = $metadata['asset_category'] ?? 'general';
                
                if (!isset($config['category_overrides'])) {
                    $config['category_overrides'] = [];
                }
                
                if (!isset($config['category_overrides'][$assetCategory])) {
                    $config['category_overrides'][$assetCategory] = [];
                }
                
                $config['category_overrides'][$assetCategory][$setting->key] = $value;
            } elseif ($setting->category === self::CATEGORY_METHOD) {
                $metadata = $setting->metadata ?? [];
                $methodName = $metadata['method_name'] ?? 'general';
                
                if (!isset($config['methods'])) {
                    $config['methods'] = [];
                }
                
                if (!isset($config['methods'][$methodName])) {
                    $config['methods'][$methodName] = [];
                }
                
                $config['methods'][$methodName][$setting->key] = $value;
            } elseif ($setting->category === self::CATEGORY_SYSTEM) {
                $config[$setting->key] = $value;
            }
        }

        return $config;
    }

    /**
     * Get a specific setting value
     */
    public static function getValue(string $key, $default = null, string $category = null, array $metadata = [])
    {
        $query = self::active()->where('key', $key);
        
        if ($category) {
            $query->where('category', $category);
        }
        
        if (!empty($metadata)) {
            foreach ($metadata as $key => $value) {
                $query->where("metadata->{$key}", $value);
            }
        }
        
        $setting = $query->first();
        
        if (!$setting) {
            return $default;
        }
        
        return $setting->getTypedValue();
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, $value, string $category = self::CATEGORY_DEFAULT, string $description = null, array $metadata = [])
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            $setting = new self();
            $setting->key = $key;
            $setting->category = $category;
            $setting->description = $description;
        }
        
        $setting->setTypedValue($value);
        $setting->metadata = array_merge($setting->metadata ?? [], $metadata);
        $setting->is_active = true;
        $setting->save();
        
        return $setting;
    }

    /**
     * Get default settings for new installation
     */
    public static function getDefaultSettings(): array
    {
        return [
            // Default settings
            [
                'key' => 'default_useful_life_years',
                'value' => 5,
                'type' => self::TYPE_INTEGER,
                'description' => 'Default useful life in years for new assets',
                'category' => self::CATEGORY_DEFAULT,
                'metadata' => ['min' => 1, 'max' => 100],
            ],
            [
                'key' => 'default_salvage_value',
                'value' => 0,
                'type' => self::TYPE_FLOAT,
                'description' => 'Default salvage value for new assets',
                'category' => self::CATEGORY_DEFAULT,
                'metadata' => ['min' => 0],
            ],
            [
                'key' => 'default_method',
                'value' => 'straight_line',
                'type' => self::TYPE_STRING,
                'description' => 'Default depreciation method for new assets',
                'category' => self::CATEGORY_DEFAULT,
                'metadata' => ['options' => ['straight_line', 'declining_balance', 'sum_of_years']],
            ],
            [
                'key' => 'calculation_frequency',
                'value' => 'monthly',
                'type' => self::TYPE_STRING,
                'description' => 'How often depreciation is calculated',
                'category' => self::CATEGORY_DEFAULT,
                'metadata' => ['options' => ['daily', 'weekly', 'monthly', 'quarterly']],
            ],
            
            // System settings
            [
                'key' => 'recalculation_threshold_days',
                'value' => 30,
                'type' => self::TYPE_INTEGER,
                'description' => 'Days between recalculation attempts',
                'category' => self::CATEGORY_SYSTEM,
                'metadata' => ['min' => 1, 'max' => 365],
            ],
            [
                'key' => 'use_queue',
                'value' => true,
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Use background queue for calculations',
                'category' => self::CATEGORY_SYSTEM,
            ],
            [
                'key' => 'log_calculations',
                'value' => true,
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Log all depreciation calculations',
                'category' => self::CATEGORY_SYSTEM,
            ],
            
            // Method settings
            [
                'key' => 'enabled',
                'value' => true,
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Enable straight line depreciation method',
                'category' => self::CATEGORY_METHOD,
                'metadata' => ['method_name' => 'straight_line'],
            ],
            [
                'key' => 'enabled',
                'value' => true,
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Enable declining balance depreciation method',
                'category' => self::CATEGORY_METHOD,
                'metadata' => ['method_name' => 'declining_balance'],
            ],
            [
                'key' => 'enabled',
                'value' => true,
                'type' => self::TYPE_BOOLEAN,
                'description' => 'Enable sum of years depreciation method',
                'category' => self::CATEGORY_METHOD,
                'metadata' => ['method_name' => 'sum_of_years'],
            ],
            [
                'key' => 'rate_multiplier',
                'value' => 2.0,
                'type' => self::TYPE_FLOAT,
                'description' => 'Declining balance rate multiplier',
                'category' => self::CATEGORY_METHOD,
                'metadata' => ['method_name' => 'declining_balance', 'min' => 1.0, 'max' => 3.0],
            ],
            
            // Category overrides
            [
                'key' => 'useful_life_years',
                'value' => 3,
                'type' => self::TYPE_INTEGER,
                'description' => 'Useful life for computer assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'computers', 'min' => 1, 'max' => 10],
            ],
            [
                'key' => 'salvage_value',
                'value' => 100,
                'type' => self::TYPE_FLOAT,
                'description' => 'Salvage value for computer assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'computers', 'min' => 0],
            ],
            [
                'key' => 'depreciation_method',
                'value' => 'declining_balance',
                'type' => self::TYPE_STRING,
                'description' => 'Depreciation method for computer assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'computers', 'options' => ['straight_line', 'declining_balance', 'sum_of_years']],
            ],
            
            [
                'key' => 'useful_life_years',
                'value' => 7,
                'type' => self::TYPE_INTEGER,
                'description' => 'Useful life for furniture assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'furniture', 'min' => 1, 'max' => 20],
            ],
            [
                'key' => 'salvage_value',
                'value' => 0,
                'type' => self::TYPE_FLOAT,
                'description' => 'Salvage value for furniture assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'furniture', 'min' => 0],
            ],
            [
                'key' => 'depreciation_method',
                'value' => 'straight_line',
                'type' => self::TYPE_STRING,
                'description' => 'Depreciation method for furniture assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'furniture', 'options' => ['straight_line', 'declining_balance', 'sum_of_years']],
            ],
            
            [
                'key' => 'useful_life_years',
                'value' => 5,
                'type' => self::TYPE_INTEGER,
                'description' => 'Useful life for vehicle assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'vehicles', 'min' => 1, 'max' => 15],
            ],
            [
                'key' => 'salvage_value',
                'value' => 1000,
                'type' => self::TYPE_FLOAT,
                'description' => 'Salvage value for vehicle assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'vehicles', 'min' => 0],
            ],
            [
                'key' => 'depreciation_method',
                'value' => 'declining_balance',
                'type' => self::TYPE_STRING,
                'description' => 'Depreciation method for vehicle assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'vehicles', 'options' => ['straight_line', 'declining_balance', 'sum_of_years']],
            ],
            
            [
                'key' => 'useful_life_years',
                'value' => 30,
                'type' => self::TYPE_INTEGER,
                'description' => 'Useful life for building assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'buildings', 'min' => 10, 'max' => 100],
            ],
            [
                'key' => 'salvage_value',
                'value' => 0,
                'type' => self::TYPE_FLOAT,
                'description' => 'Salvage value for building assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'buildings', 'min' => 0],
            ],
            [
                'key' => 'depreciation_method',
                'value' => 'straight_line',
                'type' => self::TYPE_STRING,
                'description' => 'Depreciation method for building assets',
                'category' => self::CATEGORY_CATEGORY_OVERRIDE,
                'metadata' => ['asset_category' => 'buildings', 'options' => ['straight_line', 'declining_balance', 'sum_of_years']],
            ],
        ];
    }

    /**
     * Install default settings
     */
    public static function installDefaults()
    {
        $defaults = self::getDefaultSettings();
        
        foreach ($defaults as $default) {
            self::setValue(
                $default['key'],
                $default['value'],
                $default['category'],
                $default['description'],
                $default['metadata'] ?? []
            );
        }
    }

    /**
     * Get validation rules for a setting
     */
    public function getValidationRules(): array
    {
        $rules = [];
        
        if ($this->type === self::TYPE_INTEGER) {
            $rules[] = 'integer';
            if (isset($this->metadata['min'])) {
                $rules[] = 'min:' . $this->metadata['min'];
            }
            if (isset($this->metadata['max'])) {
                $rules[] = 'max:' . $this->metadata['max'];
            }
        } elseif ($this->type === self::TYPE_FLOAT) {
            $rules[] = 'numeric';
            if (isset($this->metadata['min'])) {
                $rules[] = 'min:' . $this->metadata['min'];
            }
            if (isset($this->metadata['max'])) {
                $rules[] = 'max:' . $this->metadata['max'];
            }
        } elseif ($this->type === self::TYPE_STRING) {
            $rules[] = 'string';
            if (isset($this->metadata['options'])) {
                $rules[] = 'in:' . implode(',', $this->metadata['options']);
            }
        } elseif ($this->type === self::TYPE_BOOLEAN) {
            $rules[] = 'boolean';
        }
        
        return $rules;
    }
}
