<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AssetMaintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asset_maintenance';

    protected $fillable = [
        'asset_id',
        'maintenance_type',
        'completed_date',
        'created_by',
        'maintenance_range',
    ];

    protected $casts = [
        'completed_date' => 'date',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Maintenance range constants
    const RANGE_ONE_TIME = 0;
    const RANGE_1_MONTH = 1;
    const RANGE_2_MONTHS = 2;
    const RANGE_3_MONTHS = 3;
    const RANGE_6_MONTHS = 6;
    const RANGE_12_MONTHS = 12;

    public static function getMaintenanceRanges(): array
    {
        return [
            self::RANGE_ONE_TIME => 'One-time',
            self::RANGE_1_MONTH => 'Every 1 Month',
            self::RANGE_2_MONTHS => 'Every 2 Months',
            self::RANGE_3_MONTHS => 'Every 3 Months',
            self::RANGE_6_MONTHS => 'Every 6 Months',
            self::RANGE_12_MONTHS => 'Every 12 Months',
        ];
    }

    // Helper methods
    public function getAssetName(): string
    {
        return $this->asset->asset_number ?? 'Unknown Asset';
    }

    public function getAssetNumber(): string
    {
        return $this->asset->asset_number ?? 'Unknown';
    }

    public function getCreatedByName(): string
    {
        return $this->createdBy->name ?? 'Unknown';
    }

    public function getMaintenanceRangeText(): string
    {
        $range = $this->maintenance_range;
        if ($range === 0) return 'One-time';
        if ($range === 1) return 'Every 1 Month';
        if ($range === 2) return 'Every 2 Months';
        if ($range === 3) return 'Every 3 Months';
        if ($range === 6) return 'Every 6 Months';
        if ($range === 12) return 'Every 12 Months';
        return "Every {$range} Month(s)";
    }

    public function getNextMaintenanceDate(): ?string
    {
        if (!$this->completed_date || $this->maintenance_range === 0) {
            return null;
        }
        
        return Carbon::parse($this->completed_date)->addMonths($this->maintenance_range)->format('Y-m-d');
    }

    public function isMaintenanceDue(): bool
    {
        if (!$this->completed_date || $this->maintenance_range === 0) {
            return false;
        }
        
        $nextDue = Carbon::parse($this->completed_date)->addMonths($this->maintenance_range);
        return $nextDue->isPast();
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('maintenance_type', $type);
    }

    public function scopeByAsset($query, $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    public function scopeByRange($query, $range)
    {
        return $query->where('maintenance_range', $range);
    }

    public function scopeByCreator($query, $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    public function scopeDueForMaintenance($query)
    {
        return $query->whereNotNull('completed_date')
                    ->where('maintenance_range', '>', 0)
                    ->whereRaw('DATE_ADD(completed_date, INTERVAL maintenance_range MONTH) <= CURDATE()');
    }

    public function scopeCompletedInRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('completed_date', [$startDate, $endDate]);
    }
}
