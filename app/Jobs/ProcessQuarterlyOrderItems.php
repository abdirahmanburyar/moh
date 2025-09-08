<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessQuarterlyOrderItems implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;
    protected $facilityId;
    protected $items;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [60, 180, 300]; // 1 min, 3 mins, 5 mins

    /**
     * Create a new job instance.
     */
    public function __construct(int $orderId, int $facilityId, array $items)
    {
        $this->orderId = $orderId;
        $this->facilityId = $facilityId;
        $this->items = $items;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        foreach ($this->items as $item) {
            try {
                DB::beginTransaction();

                // Calculate months for AMC
                $months = [];
                $currentDate = now()->subMonth();
                for ($i = 0; $i < 3; $i++) {
                    $months[] = $currentDate->format('Y-m');
                    $currentDate->subMonth();
                }

                // Get consumption data
                $consumptionData = DB::table('monthly_consumption_items')
                    ->join('monthly_consumption_reports', 'monthly_consumption_items.parent_id', '=', 'monthly_consumption_reports.id')
                    ->where('monthly_consumption_reports.facility_id', $this->facilityId)
                    ->where('monthly_consumption_items.product_id', $item->product_id)
                    ->whereIn('monthly_consumption_reports.month_year', $months)
                    ->sum('monthly_consumption_items.quantity');

                // Calculate AMC
                $amc = $consumptionData > 0 ? ($consumptionData / 3) : 10;

                // Get SOH
                $soh = DB::table('facility_inventory_items')
                    ->join('facility_inventories', 'facility_inventory_items.facility_inventory_id', '=', 'facility_inventories.id')
                    ->where('facility_inventories.facility_id', $this->facilityId)
                    ->where('facility_inventory_items.product_id', $item->product_id)
                    ->where('facility_inventory_items.expiry_date', '>=', now()->toDateString())
                    ->sum('facility_inventory_items.quantity');

                // Calculate needed quantity
                $neededQuantity = max(0, ceil(($amc * 3) - $soh));

                // Create order item
                $orderItemId = DB::table('order_items')->insertGetId([
                    'order_id' => $this->orderId,
                    'product_id' => $item->product_id,
                    'quantity' => $neededQuantity,
                    'quantity_on_order' => 0,
                    'soh' => $soh,
                    'amc' => $amc,
                    'quantity_to_release' => 0,
                    'no_of_days' => 120,
                    'days' => 120,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Process inventory allocation if needed
                if ($neededQuantity > 0) {
                    $inventories = DB::table('inventory_items')
                        ->where('product_id', $item->product_id)
                        ->where('quantity', '>', 0)
                        ->where('expiry_date', '>=', now()->toDateString())
                        ->orderBy('expiry_date')
                        ->get();

                    $totalAvailable = $inventories->sum('quantity');
                    $totalToAllocate = min($neededQuantity, $totalAvailable);

                    if ($totalToAllocate > 0) {
                        // Update order item with allocation
                        DB::table('order_items')
                            ->where('id', $orderItemId)
                            ->update(['quantity_to_release' => $totalToAllocate]);

                        $remainingToAllocate = $totalToAllocate;
                        foreach ($inventories as $inventory) {
                            if ($remainingToAllocate <= 0) break;

                            $batchAllocation = min($inventory->quantity, $remainingToAllocate);

                            // Create allocation
                            DB::table('inventory_allocations')->insert([
                                'order_item_id' => $orderItemId,
                                'product_id' => $item->product_id,
                                'warehouse_id' => $inventory->warehouse_id,
                                'location' => $inventory->location,
                                'batch_number' => $inventory->batch_number,
                                'expiry_date' => $inventory->expiry_date,
                                'allocated_quantity' => $batchAllocation,
                                'allocation_type' => 'quarterly',
                                'unit_cost' => $inventory->unit_cost,
                                'total_cost' => $inventory->unit_cost * $batchAllocation,
                                'uom' => $inventory->uom,
                                'notes' => "Quarterly allocation from batch {$inventory->batch_number}",
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            // Update inventory
                            DB::table('inventory_items')
                                ->where('id', $inventory->id)
                                ->update([
                                    'quantity' => DB::raw("quantity - {$batchAllocation}"),
                                    'total_cost' => DB::raw("unit_cost * (quantity - {$batchAllocation})")
                                ]);

                            $remainingToAllocate -= $batchAllocation;
                        }
                    }
                }

                DB::commit();
                Log::info("Processed order item", [
                    'order_id' => $this->orderId,
                    'product_id' => $item->product_id,
                    'quantity' => $neededQuantity,
                    'quantity_to_release' => $totalToAllocate ?? 0
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error processing order item", [
                    'order_id' => $this->orderId,
                    'product_id' => $item->product_id,
                    'error' => $e->getMessage()
                ]);
                
                // Don't throw the exception - let other items process
                continue;
            }

            // Small delay to prevent overwhelming the database
            usleep(100000); // 100ms delay
        }
    }
} 