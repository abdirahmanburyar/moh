# Asset Depreciation Configuration Guide

## **🎯 Overview**

Your asset depreciation system now supports **dynamic configuration** instead of hardcoded values. This means you can easily change useful life years, salvage values, and depreciation methods for different asset categories without modifying code.

## **⚙️ Configuration Management Commands**

### **1. View Current Configuration**
```bash
php artisan assets:config-depreciation show
```

**Output Example:**
```
📊 Asset Depreciation Configuration
================================

🔧 Default Settings:
+------------------------+--------+----------------------------------+
| Setting               | Value  | Description                      |
+------------------------+--------+----------------------------------+
| Default Useful Life   | 5 years| Default useful life for new assets|
| Default Salvage Value | $0     | Default salvage value for new assets|
| Default Method        | straight_line | Default depreciation method    |
| Calculation Frequency | monthly| How often depreciation is calculated|
+------------------------+--------+----------------------------------+

🏷️  Category-Specific Overrides:
+----------+------------+---------------+
| Category | Useful Life| Salvage Value |
+----------+------------+---------------+
| Computers| 3 years    | $100          |
| Furniture| 7 years    | $0            |
| Vehicles | 5 years    | $1000         |
| Buildings| 30 years   | $0            |
+----------+------------+---------------+
```

### **2. Change Default Settings**

#### **Change Default Useful Life for All Assets**
```bash
php artisan assets:config-depreciation set --key=default_useful_life_years --value=7
```
**Result:** All new assets will default to 7 years useful life

#### **Change Default Salvage Value**
```bash
php artisan assets:config-depreciation set --key=default_salvage_value --value=100
```
**Result:** All new assets will default to $100 salvage value

#### **Change Default Depreciation Method**
```bash
php artisan assets:config-depreciation set --key=default_method --value=declining_balance
```
**Result:** All new assets will use declining balance depreciation

### **3. Set Category-Specific Overrides**

#### **Set Useful Life for Computers**
```bash
php artisan assets:config-depreciation set --category=computers --key=useful_life_years --value=4
```
**Result:** Computer assets will use 4 years useful life instead of default

#### **Set Salvage Value for Vehicles**
```bash
php artisan assets:config-depreciation set --category=vehicles --key=salvage_value --value=2000
```
**Result:** Vehicle assets will have $2000 salvage value instead of default

#### **Set Useful Life for New Category**
```bash
php artisan assets:config-depreciation set --category=electronics --key=useful_life_years --value=2
```
**Result:** Creates new category 'electronics' with 2 years useful life

### **4. Reset Configuration to Defaults**
```bash
php artisan assets:config-depreciation reset
```
**Result:** All settings return to factory defaults

## **🏷️ Asset Categories and Defaults**

### **Current Category Overrides**
| Category | Useful Life | Salvage Value | Method | Reason |
|----------|-------------|---------------|---------|---------|
| **Computers** | 3 years | $100 | Declining Balance | Technology becomes obsolete quickly |
| **Furniture** | 7 years | $0 | Straight Line | Physical wear is consistent |
| **Vehicles** | 5 years | $1,000 | Declining Balance | Rapid initial value loss |
| **Buildings** | 30 years | $0 | Straight Line | Long-term, stable depreciation |
| **Machinery** | 10 years | $500 | Sum of Years | Variable depreciation pattern |
| **Office Equipment** | 4 years | $50 | Declining Balance | Quick obsolescence |

### **Adding New Categories**
```bash
# Add medical equipment category
php artisan assets:config-depreciation set --category=medical_equipment --key=useful_life_years --value=8
php artisan assets:config-depreciation set --category=medical_equipment --key=salvage_value --value=200

# Add software category
php artisan assets:config-depreciation set --category=software --key=useful_life_years --value=3
php artisan assets:config-depreciation set --category=software --key=salvage_value --value=0
```

## **📊 Depreciation Methods Explained**

### **1. Straight Line (Default)**
- **Formula:** `(Original Value - Salvage Value) ÷ Useful Life Years`
- **Best for:** Buildings, furniture, long-term assets
- **Advantage:** Simple, predictable, consistent

### **2. Declining Balance**
- **Formula:** `Current Book Value × Depreciation Rate`
- **Best for:** Technology, vehicles, fast-depreciating assets
- **Advantage:** Matches real-world value loss patterns

### **3. Sum of Years Digits**
- **Formula:** `(Original Value - Salvage Value) × (Remaining Life ÷ Sum of Years)`
- **Best for:** Machinery, equipment with variable depreciation
- **Advantage:** Front-loaded depreciation, realistic for many assets

## **🔧 Configuration File Structure**

The configuration is stored in `config/asset-depreciation.php`:

```php
return [
    // Default settings for new assets
    'default_useful_life_years' => 5,
    'default_salvage_value' => 0,
    'default_method' => 'straight_line',
    
    // Category-specific overrides
    'category_overrides' => [
        'computers' => [
            'useful_life_years' => 3,
            'salvage_value' => 100,
            'depreciation_method' => 'declining_balance',
        ],
        // ... more categories
    ],
    
    // Available methods
    'methods' => [
        'straight_line' => ['enabled' => true],
        'declining_balance' => ['enabled' => true],
        'sum_of_years' => ['enabled' => true],
    ],
];
```

## **📈 How Configuration Affects Depreciation**

### **Example 1: Computer Asset**
- **Category:** Computers
- **Original Value:** $2,000
- **Useful Life:** 3 years (from category override)
- **Salvage Value:** $100 (from category override)
- **Method:** Declining Balance (from category override)

**Depreciation Schedule:**
- Year 1: $2,000 × 20% = **$400**
- Year 2: $1,600 × 20% = **$320**
- Year 3: $1,280 × 20% = **$256**
- Final Value: **$1,024** (above salvage value)

### **Example 2: Furniture Asset**
- **Category:** Furniture
- **Original Value:** $1,500
- **Useful Life:** 7 years (from category override)
- **Salvage Value:** $0 (from category override)
- **Method:** Straight Line (from category override)

**Depreciation Schedule:**
- Annual Depreciation: ($1,500 - $0) ÷ 7 = **$214.29**
- Every year: **$214.29**
- Final Value: **$0**

## **🔄 Updating Existing Assets**

### **After Changing Configuration**

1. **New assets** automatically use new settings
2. **Existing assets** keep their current depreciation records
3. **To update existing assets:**
   ```bash
   # Force recalculation for all assets
   php artisan assets:calculate-depreciation --force
   
   # Update specific asset
   php artisan assets:calculate-depreciation --asset-id=123
   ```

### **Bulk Updates**
```bash
# Update all computer assets to new settings
php artisan assets:calculate-depreciation --force

# The system will use new category overrides for calculations
```

## **📋 Best Practices**

### **1. Category Naming**
- Use lowercase, underscore-separated names
- Be specific: `medical_equipment` not just `medical`
- Group similar assets together

### **2. Useful Life Years**
- **Technology:** 2-5 years
- **Vehicles:** 3-8 years
- **Machinery:** 5-15 years
- **Furniture:** 5-10 years
- **Buildings:** 20-50 years

### **3. Salvage Values**
- **Technology:** 5-10% of original value
- **Vehicles:** 10-20% of original value
- **Machinery:** 10-15% of original value
- **Furniture:** 0-5% of original value
- **Buildings:** 0-10% of original value

### **4. Depreciation Methods**
- **Straight Line:** Long-term, stable assets
- **Declining Balance:** Fast-depreciating assets
- **Sum of Years:** Variable depreciation patterns

## **🚨 Important Notes**

### **Configuration Changes**
- ✅ **Immediate effect** on new assets
- ✅ **No impact** on existing depreciation records
- ✅ **Requires recalculation** to update existing assets

### **Validation**
- Useful life: 1-100 years
- Salvage value: 0-50% of original value
- Methods: Must be enabled in configuration

### **Backup**
- Configuration changes are written to `config/asset-depreciation.php`
- Always backup before major changes
- Use `--dry-run` to test commands

## **📞 Troubleshooting**

### **Common Issues**

1. **Configuration not taking effect:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

2. **Category not found:**
   - Check spelling (case-sensitive)
   - Verify category exists in asset_items table

3. **Invalid values:**
   - Useful life must be 1-100 years
   - Salvage value must be positive number
   - Method must be enabled

### **Debug Commands**
```bash
# Show current configuration
php artisan assets:config-depreciation show

# Check asset categories
php artisan tinker
>>> App\Models\AssetCategory::pluck('name', 'id')
```

---

**🎉 Your depreciation system is now fully configurable!** 

No more hardcoded values - everything can be managed through simple commands or by editing the configuration file directly.
