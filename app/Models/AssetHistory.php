<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_item_id',
        'action',
        'action_type', // 'status_change', 'transfer', 'retirement', 'approval'
        'old_value',
        'new_value',
        'notes',
        'performed_by',
        'performed_at',
        'approval_id', // Reference to the approval that triggered this action
        'assignee_id', // New custodian (FK to assignees)
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    /**
     * Get the asset item that this history belongs to.
     */
    public function assetItem()
    {
        return $this->belongsTo(AssetItem::class);
    }

    /**
     * Get the user who performed this action.
     */
    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the approval that triggered this action.
     */
    public function approval()
    {
        return $this->belongsTo(AssetApproval::class);
    }

    /**
     * New assignee relation (custodian at the time of action)
     */
    public function assignee()
    {
        return $this->belongsTo(Assignee::class);
    }

    /**
     * Scope a query to only include status changes.
     */
    public function scopeStatusChanges($query)
    {
        return $query->where('action_type', 'status_change');
    }

    /**
     * Scope a query to only include transfers.
     */
    public function scopeTransfers($query)
    {
        return $query->where('action_type', 'transfer');
    }

    /**
     * Scope a query to only include retirements.
     */
    public function scopeRetirements($query)
    {
        return $query->where('action_type', 'retirement');
    }

    /**
     * Scope a query to only include approvals.
     */
    public function scopeApprovals($query)
    {
        return $query->where('action_type', 'approval');
    }

    /**
     * Scope a query to only include actions by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('performed_by', $userId);
    }

    /**
     * Scope a query to only include actions within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('performed_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include recent actions.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('performed_at', '>=', now()->subDays($days));
    }

    /**
     * Get formatted action title for display.
     */
    public function getActionTitleAttribute()
    {
        $titles = [
            'reviewed' => 'Asset Reviewed',
            'approved' => 'Asset Approved',
            'rejected' => 'Asset Rejected',
            'transfer_reviewed' => 'Transfer Reviewed',
            'transfer_approved' => 'Transfer Approved',
            'transfer_rejected' => 'Transfer Rejected',
            'retirement_reviewed' => 'Retirement Reviewed',
            'retirement_approved' => 'Retirement Approved',
            'retirement_rejected' => 'Retirement Rejected',
            'status_changed' => 'Status Changed',
    
        ];

        return $titles[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    /**
     * Get the formatted old value for display.
     */
    public function getFormattedOldValueAttribute()
    {
        if (!$this->old_value) return null;
        
        if (is_array($this->old_value)) {
            return json_encode($this->old_value, JSON_PRETTY_PRINT);
        }
        
        return $this->old_value;
    }

    /**
     * Get the formatted new value for display.
     */
    public function getFormattedNewValueAttribute()
    {
        if (!$this->new_value) return null;
        
        if (is_array($this->new_value)) {
            return json_encode($this->new_value, JSON_PRETTY_PRINT);
        }
        
        return $this->new_value;
    }
}
