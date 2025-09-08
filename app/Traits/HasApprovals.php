<?php

namespace App\Traits;

use App\Models\AssetApproval;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasApprovals
{
    /**
     * Get all approvals for this model.
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(AssetApproval::class, 'approvable');
    }

    /**
     * Get pending approvals for this model.
     */
    public function pendingApprovals()
    {
        return $this->approvals()->pending();
    }

    /**
     * Check if all required approvals are completed.
     */
    public function isFullyApproved(): bool
    {
        return $this->approvals()
            ->whereNotIn('status', ['approved', 'rejected'])
            ->doesntExist();
    }

    /**
     * Create approval steps for this model.
     */
    public function createApprovalSteps(array $steps)
    {
        foreach ($steps as $step) {
            $this->approvals()->create([
                'role_id' => $step['role_id'] ?? null,
                'action' => $step['action'],
                'sequence' => $step['sequence'] ?? 1,
                'status' => 'pending',
                'created_by' => auth()->id()
            ]);
        }
    }

    /**
     * Get the next pending approval step.
     */
    public function getNextApprovalStep()
    {
        return $this->approvals()
            ->whereIn('status', ['pending', 'reviewed'])
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Check if the current user can approve the next step.
     */
    public function canApproveNextStep(): bool
    {
        $nextStep = $this->getNextApprovalStep();
        if (!$nextStep) {
            return false;
        }

        // Permission-based checks (no roles): allow approval when the next step is an approval step or a reviewed step
        if (($nextStep->action === 'approve' && in_array($nextStep->status, ['pending'])) ||
            ($nextStep->action === 'review' && $nextStep->status === 'reviewed')) {
            return auth()->user()->can('asset_approve');
        }

        // Fallback: if the step is pending review, checking approval does not apply yet
        return false;
    }

    /**
     * Check if the current user can review the next step.
     */
    public function canReviewNextStep(): bool
    {
        $nextStep = $this->getNextApprovalStep();
        if (!$nextStep || !in_array($nextStep->action, ['approve','review']) || $nextStep->status !== 'pending') {
            return false;
        }

        // With no review, treat pending approval as reviewable only by approve permission
        return auth()->user()->can('asset_approve');
    }

    /**
     * Check if the current user can approve/reject the reviewed step.
     */
    public function canApproveRejectNextStep(): bool
    {
        $nextStep = $this->getNextApprovalStep();
        if (!$nextStep || !in_array($nextStep->action, ['approve','review']) || !in_array($nextStep->status, ['pending','reviewed'])) {
            return false;
        }

        // Permission-based: allow if user can approve or reject assets
        return auth()->user()->can('asset_approve') || auth()->user()->can('asset_reject');
    }
}
