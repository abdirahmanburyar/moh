# Asset Depreciation System - Update Summary

## **Key Change: Single Record Per Asset**

The depreciation system has been updated to **maintain only ONE depreciation record per asset** instead of creating new rows for each calculation. This ensures:

- ✅ **No duplicate records** in the database
- ✅ **Efficient updates** of existing records
- ✅ **Cleaner database structure**
- ✅ **Better performance** for queries

## **How It Works Now**

### **1. Single Record Management**
- Each `AssetItem` has exactly **one** `AssetDepreciation` record
- When depreciation is calculated, the **existing record is updated**
- New records are only created if none exist

### **2. Automatic Cleanup**
- The system automatically detects and removes duplicate records
- Keeps the **oldest record** and deletes newer duplicates
- Logs all cleanup activities for audit purposes

### **3. Smart Update Logic**
- **New assets**: Creates initial depreciation record
- **Existing assets**: Updates current record with new calculations
- **Value changes**: Automatically recalculates depreciation

## **Updated Components**

### **AssetItem Model**
- `ensureSingleDepreciationRecord()` - Ensures only one record exists
- `getDepreciationRecord()` - Gets the single depreciation record
- Automatic cleanup of duplicates

### **Console Commands**
- `assets:calculate-depreciation` - Updates existing records
- `assets:cleanup-depreciation` - Removes duplicate records
- `assets:schedule-depreciation` - Scheduled updates

### **Background Jobs**
- `CalculateAssetDepreciationJob` - Updates existing records
- No new records created unless necessary

### **Observer**
- `AssetItemObserver` - Updates existing records on value changes
- Prevents duplicate creation

## **Usage Examples**

### **Manual Depreciation Calculation**
```bash
# Calculate depreciation for all assets (updates existing records)
php artisan assets:calculate-depreciation

# Force recalculation (updates existing records)
php artisan assets:calculate-depreciation --force

# Calculate for specific asset (updates existing record)
php artisan assets:calculate-depreciation --asset-id=123

# Dry run to see what would be updated
php artisan assets:calculate-depreciation --dry-run
```

### **Cleanup Duplicate Records**
```bash
# See what duplicates exist (dry run)
php artisan assets:cleanup-depreciation --dry-run

# Remove duplicate records
php artisan assets:cleanup-depreciation
```

### **Scheduled Updates**
```bash
# Schedule monthly depreciation calculation
php artisan assets:schedule-depreciation --frequency=monthly --queue
```

## **Database Structure**

### **Before (Multiple Records)**
```
asset_depreciation table:
- id: 1, asset_item_id: 123, current_value: 1000, created_at: 2024-01-01
- id: 2, asset_item_id: 123, current_value: 950,  created_at: 2024-02-01  ❌ DUPLICATE
- id: 3, asset_item_id: 123, current_value: 900,  created_at: 2024-03-01  ❌ DUPLICATE
```

### **After (Single Record)**
```
asset_depreciation table:
- id: 1, asset_item_id: 123, current_value: 900, last_calculated_date: 2024-03-01 ✅ UPDATED
```

## **Benefits of the New System**

### **Performance**
- Faster queries (no need to find latest record)
- Smaller database size
- Better indexing

### **Data Integrity**
- No duplicate records
- Consistent depreciation history
- Easier to audit

### **Maintenance**
- Automatic cleanup of duplicates
- Single source of truth for each asset
- Simplified reporting

## **Migration from Old System**

If you have existing duplicate depreciation records:

1. **Run cleanup command**:
   ```bash
   php artisan assets:cleanup-depreciation --dry-run
   php artisan assets:cleanup-depreciation
   ```

2. **Verify cleanup**:
   ```bash
   php artisan assets:calculate-depreciation --dry-run
   ```

3. **Run actual calculation**:
   ```bash
   php artisan assets:calculate-depreciation
   ```

## **Configuration**

The system uses the `config/asset-depreciation.php` file for:
- Default useful life years
- Default salvage value
- Depreciation methods
- Calculation frequency
- Cleanup settings

## **Monitoring & Logging**

All depreciation activities are logged:
- Record creation/updates
- Duplicate cleanup
- Calculation errors
- Performance metrics

Check logs in `storage/logs/laravel.log` for depreciation-related activities.

## **Scheduled Execution**

The system automatically runs monthly via Laravel's task scheduler:
- **When**: 1st day of each month at 2:00 AM
- **What**: Updates all asset depreciation values
- **How**: Background job processing
- **Logging**: All activities logged with timestamps

## **Troubleshooting**

### **Common Issues**

1. **Duplicate records still exist**:
   ```bash
   php artisan assets:cleanup-depreciation
   ```

2. **Depreciation not calculating**:
   ```bash
   php artisan assets:calculate-depreciation --force
   ```

3. **Performance issues**:
   - Check queue worker status
   - Monitor database performance
   - Review log files

### **Log Locations**
- **Application logs**: `storage/logs/laravel.log`
- **Depreciation logs**: `storage/logs/asset-depreciation.log`
- **Queue logs**: `storage/logs/queue.log`

## **Best Practices**

1. **Run cleanup monthly** to prevent duplicates
2. **Monitor logs** for calculation errors
3. **Use dry-run** before making changes
4. **Schedule regular updates** for accurate values
5. **Backup database** before major cleanup operations

---

**Note**: This system ensures that each asset maintains exactly one depreciation record that gets updated over time, providing accurate financial reporting without database bloat.
