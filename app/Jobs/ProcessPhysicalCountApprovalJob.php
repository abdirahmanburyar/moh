<?php

namespace App\Jobs;

use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentItem;
use App\Models\ReceivedBackorder;
use App\Models\ReceivedBackorderItem;
use App\Models\Liquidate;
use App\Models\LiquidateItem;
use App\Notifications\PhysicalCountApprovalCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProcessPhysicalCountApprovalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3; // Retry 3 times if it fails

    protected $adjustmentId;
    protected $userId;
    protected $receivedBackorderId;

    /**
     * Create a new job instance.
     */
    public function __construct($adjustmentId, $userId, $receivedBackorderId)
    {
        $this->adjustmentId = $adjustmentId;
        $this->userId = $userId;
        $this->receivedBackorderId = $receivedBackorderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting physical count approval processing for adjustment ID: {$this->adjustmentId}");
            Log::info("Job parameters", [
                'adjustmentId' => $this->adjustmentId,
                'userId' => $this->userId,
                'receivedBackorderId' => $this->receivedBackorderId
            ]);
            
            $adjustment = InventoryAdjustment::findOrFail($this->adjustmentId);
            
            // Check if adjustment is still in reviewed status
            if ($adjustment->status !== 'reviewed') {
                Log::warning("Adjustment {$this->adjustmentId} is not in reviewed status. Current status: {$adjustment->status}");
                return;
            }

            // Set status to processing to prevent duplicate processing
            $adjustment->update(['status' => 'processing']);

            // Process adjustment items in chunks to handle large datasets
            $totalItems = InventoryAdjustmentItem::where('parent_id', $this->adjustmentId)->count();
            $positiveDifferences = InventoryAdjustmentItem::where('parent_id', $this->adjustmentId)
                ->where('difference', '>', 0)
                ->count();
            
            Log::info("Starting to process adjustment items", [
                'adjustment_id' => $this->adjustmentId,
                'total_items' => $totalItems,
                'positive_differences' => $positiveDifferences,
                'received_backorder_id' => $this->receivedBackorderId
            ]);
            
            InventoryAdjustmentItem::where('parent_id', $this->adjustmentId)
                ->with('warehouse')
                ->chunkById(100, function ($chunk) use ($adjustment) {
                    foreach ($chunk as $adjustmentItem) {
                        $this->processAdjustmentItem($adjustmentItem, $adjustment);
                    }
                });

            // Update adjustment status to approved
            $adjustment->update([
                'status' => 'approved',
                'approved_by' => $this->userId,
                'approved_at' => now()
            ]);

            // Send notification to the user who approved it
            $user = \App\Models\User::find($this->userId);
            if ($user) {
                $user->notify(new PhysicalCountApprovalCompleted($adjustment));
            }

            Log::info("Successfully completed physical count approval processing for adjustment ID: {$this->adjustmentId}");

        } catch (\Exception $e) {
            Log::error("Error processing physical count approval for adjustment ID {$this->adjustmentId}: " . $e->getMessage());
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Process a single adjustment item
     */
    protected function processAdjustmentItem($adjustmentItem, $adjustment): void
    {
        $difference = $adjustmentItem->difference;
        
        Log::info("Processing adjustment item", [
            'adjustment_item_id' => $adjustmentItem->id,
            'product_id' => $adjustmentItem->product_id,
            'difference' => $difference,
            'received_backorder_id' => $this->receivedBackorderId
        ]);
        
        if ($difference > 0) {
            // Positive difference - create ReceivedBackorder
            // $receivedBackorder = ReceivedBackorder::create([
            //     'received_by' => $this->userId,
            //     'received_at' => now(),
            //     'status' => 'pending',
            //     'type' => 'physical_count_adjustment',
            //     'warehouse_id' => $adjustmentItem->warehouse_id,
            //     'inventory_adjustment_id' => $adjustment->id,
            //     'note' => 'Physical count adjustment - positive difference'
            // ]);
            
            // Get current location from InventoryItem to ensure we have the most up-to-date location
            $currentInventoryItem = \App\Models\InventoryItem::where('product_id', $adjustmentItem->product_id)
                ->where('batch_number', $adjustmentItem->batch_number)
                ->where('warehouse_id', $adjustmentItem->warehouse_id)
                ->first();
            
            $location = $currentInventoryItem ? $currentInventoryItem->location : $adjustmentItem->location;
            
            // Create ReceivedBackorderItem
            ReceivedBackorderItem::create([
                'received_backorder_id' => $this->receivedBackorderId,
                'product_id' => $adjustmentItem->product_id,
                'quantity' => $difference,
                'barcode' => $adjustmentItem->barcode,
                'expire_date' => $adjustmentItem->expiry_date,
                'batch_number' => $adjustmentItem->batch_number === 'N/A' ? null : $adjustmentItem->batch_number,
                'uom' => $adjustmentItem->uom,
                'location' => $location,
                'warehouse_id' => $adjustmentItem->warehouse_id,
                'unit_cost' => $adjustmentItem->unit_cost,
                'total_cost' => $adjustmentItem->quantity * $adjustmentItem->unit_cost,
                'note' => $adjustmentItem->remarks
            ]);
            
            Log::info("Created ReceivedBackorderItem", [
                'received_backorder_id' => $this->receivedBackorderId,
                'product_id' => $adjustmentItem->product_id,
                'quantity' => $difference,
                'adjustment_item_id' => $adjustmentItem->id
            ]);
            
        } elseif ($difference < 0) {
            // Negative difference - create Liquidate
            $liquidate = Liquidate::create([
                'liquidated_by' => $this->userId,
                'liquidated_at' => now(),
                'status' => 'pending',
                'source' => 'physical_count_adjustment',
                'warehouse' => $adjustmentItem->warehouse->name ?? 'Unknown',
                'inventory_adjustment_id' => $adjustment->id
            ]);
            
            // Get current location from InventoryItem for liquidate items too
            $currentInventoryItem = \App\Models\InventoryItem::where('product_id', $adjustmentItem->product_id)
                ->where('batch_number', $adjustmentItem->batch_number)
                ->where('warehouse_id', $adjustmentItem->warehouse_id)
                ->first();
            
            $location = $currentInventoryItem ? $currentInventoryItem->location : $adjustmentItem->location;
            
            // Create LiquidateItem
            LiquidateItem::create([
                'liquidate_id' => $liquidate->id,
                'product_id' => $adjustmentItem->product_id,
                'quantity' => abs($difference),
                'barcode' => $adjustmentItem->barcode,
                'expire_date' => $adjustmentItem->expiry_date,
                'batch_number' => $adjustmentItem->batch_number === 'N/A' ? null : $adjustmentItem->batch_number,
                'uom' => $adjustmentItem->uom,
                'location' => $location,
                'note' => $adjustmentItem->remarks,
                'type' => 'physical_count_adjustment'
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Physical count approval job failed for adjustment ID {$this->adjustmentId}: " . $exception->getMessage());
        
        // Optionally, you could send a notification to admin or update the adjustment status
        // $adjustment = InventoryAdjustment::find($this->adjustmentId);
        // if ($adjustment) {
        //     $adjustment->update(['status' => 'failed']);
        // }
    }
} 