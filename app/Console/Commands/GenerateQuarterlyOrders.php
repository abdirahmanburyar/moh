<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\MonthlyConsumptionItem;
use App\Models\Facility;
use App\Models\Order;
use App\Mail\QuarterlyOrderGenerated;

class GenerateQuarterlyOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:generate-quarterly {quarter? : Target quarter (1-4)} {year? : Target year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate quarterly orders for all active facilities. Optionally specify quarter (1-4) and year.';

    public function handle()
    {
        $this->info('Starting quarterly order generation...');
        
        try {
            // Calculate quarter and year
            $today = now();
            $targetQuarter = $this->argument('quarter') ?? ceil($today->month / 3);
            $year = $this->argument('year') ?? $today->year;
            
            if ($targetQuarter == 4 && !$this->argument('quarter')) {
                $year++;
            }

            // Check if today is the first day of a quarter (only if no arguments provided)
            if (!$this->argument('quarter') && !$this->argument('year')) {
                $quarterStartDates = [
                    1 => ['month' => 1, 'day' => 1],   // Q1: January 1st
                    2 => ['month' => 4, 'day' => 1],   // Q2: April 1st  
                    3 => ['month' => 7, 'day' => 1],   // Q3: July 1st
                    4 => ['month' => 10, 'day' => 1],  // Q4: October 1st
                ];

                $isQuarterStartDate = false;
                foreach ($quarterStartDates as $quarter => $dates) {
                    if ($today->month == $dates['month'] && $today->day == $dates['day']) {
                        $isQuarterStartDate = true;
                        $targetQuarter = $quarter;
                        break;
                    }
                }

                if (!$isQuarterStartDate) {
                    $this->warn("❌ Quarterly orders can only be generated on quarter start dates:");
                    $this->warn("   • Q1: January 1st");
                    $this->warn("   • Q2: April 1st"); 
                    $this->warn("   • Q3: July 1st");
                    $this->warn("   • Q4: October 1st");
                    $this->warn("Current date: {$today->format('Y-m-d')}");
                    $this->warn("To override this check, specify quarter and year arguments:");
                    $this->warn("   php artisan orders:generate-quarterly 3 2025");
                                        
                    return 1;
                }

                $this->info("✅ Confirmed: Today is the start of Q{$targetQuarter} {$year}");
            } else {
                $this->warn("⚠️ Manual override: Quarter {$targetQuarter} and year {$year} specified");
            }

            $this->info("Generating orders for Q{$targetQuarter} {$year}");

            // Get all facility types that have eligible items
            $facilityTypes = DB::table('eligible_items')
                ->distinct()
                ->pluck('facility_type')
                ->toArray();

            if (empty($facilityTypes)) {
                $this->error("No facility types found with eligible items");
                return 1;
            }

            $this->info("Found facility types with eligible items: " . implode(', ', $facilityTypes));
            $this->newLine();

            $totalProcessed = 0;
            $totalFailed = 0;
            $totalOrders = 0;
            $totalEmailsSent = 0;

            // Process each facility type and its facilities
            foreach ($facilityTypes as $facilityType) {
                $this->info("🏭 Processing facility type: {$facilityType}");
                
                // Get eligible items for this facility type
                $eligibleItems = DB::table('eligible_items')
                    ->where('facility_type', $facilityType)
                    ->join('products', 'eligible_items.product_id', '=', 'products.id')
                    ->select('eligible_items.id', 'eligible_items.product_id', 'products.name as product_name', 'eligible_items.facility_type')
                    ->orderBy('eligible_items.id')
                    ->get();

                $this->info("   📦 Found {$eligibleItems->count()} eligible items for {$facilityType}");

                // Get all active facilities of this type
                $facilities = DB::table('facilities')
                    ->where('facility_type', $facilityType)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->get();

                if ($facilities->isEmpty()) {
                    $this->warn("   ⚠️ No active facilities found for type: {$facilityType}");
                    continue;
                }

                $this->info("   🏥 Found {$facilities->count()} active facilities of type: {$facilityType}");
                $this->newLine();

                // Process each facility for this facility type
                foreach ($facilities as $index => $facility) {
                    $facilityName = isset($facility->name) ? $facility->name : 'Unknown';
                    $facilityNumber = $index + 1;
                    $this->info("      🏥 Processing facility {$facilityNumber}/{$facilities->count()}: {$facility->id} ({$facilityName})");
                    
                    try {
                        // Create order for this facility
                        $orderId = $this->createOrder($facility, $targetQuarter, $year);
                        if (!$orderId) {
                            $this->error("         ❌ Failed to create order for facility {$facility->id}");
                            $totalFailed++;
                            continue;
                        }

                        $this->info("         📋 Created order ID: {$orderId}");

                        // Process the eligible items for this specific facility
                        $result = $this->processFacilityItems($orderId, $facility, $eligibleItems);
                        
                        if ($result['success']) {
                            $totalProcessed++;
                            $totalOrders++;
                            $this->info("         ✅ Completed facility {$facility->id} - Processed: {$result['processed']}, Skipped: {$result['skipped']}, Errors: {$result['errors']}");
                            
                            // Send email notification
                            if ($this->sendOrderNotification($orderId, $facility, $result)) {
                                $totalEmailsSent++;
                            }
                        } else {
                            $totalFailed++;
                            $this->error("         ❌ Failed to process facility {$facility->id}");
                        }
                        
                    } catch (\Exception $e) {
                        $this->error("         ❌ Error processing facility {$facility->id}: " . $e->getMessage());
                        
                        $totalFailed++;
                    }
                    
                    // Small delay between facilities
                    usleep(50000); // 50ms delay
                }
                
                $this->newLine();
                $this->info("   ✅ Completed facility type: {$facilityType}");
                $this->newLine();
            }

            // Final summary
            $this->newLine();
            $this->info("🎯 Final Summary:");
            $this->info("   📊 Total facility types processed: " . count($facilityTypes));
            $this->info("   📋 Total orders created: {$totalOrders}");
            $this->info("   ✅ Successfully processed facilities: {$totalProcessed}");
            $this->info("   📧 Email notifications sent: {$totalEmailsSent}");
            if ($totalFailed > 0) {
                $this->warn("   ❌ Failed to process facilities: {$totalFailed}");
            }
            


            $this->info("✅ Quarterly order generation completed successfully!");
            if ($totalEmailsSent > 0) {
                $this->info("📧 Email notifications have been sent to facility managers with order details and links.");
            }
            return 0;

        } catch (\Exception $e) {
            $this->error("Fatal error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Create the order record
     */
    private function createOrder($facility, $targetQuarter, $year)
    {
        // Generate sequential order number for the quarter
        $orderNumber = $this->generateSequentialOrderNumber($targetQuarter, $year);
        
        $maxRetries = 3;
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                DB::beginTransaction();
                
                $orderId = DB::table('orders')->insertGetId([
                    'facility_id' => $facility->id,
                    'order_number' => $orderNumber,
                    'order_type' => 'Quarterly Q-'.$targetQuarter,
                    'status' => 'pending',
                    'order_date' => Carbon::create($year, ($targetQuarter - 1) * 3 + 1, 1),
                    'expected_date' => Carbon::create($year, ($targetQuarter - 1) * 3 + 1, 1)->addDays(7),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                DB::commit();
                $this->info("✅ Created order: {$orderNumber} (ID: {$orderId})");
                return $orderId;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $attempt++;
                
                if ($attempt >= $maxRetries) {
                    $this->error("❌ Failed to create order after {$maxRetries} attempts: " . $e->getMessage());
                    return null;
                }
                
                $this->warn("⚠️ Retrying order creation (attempt {$attempt})...");
                sleep(2); // Wait 2 seconds before retry
            }
        }
        
        return null;
    }

    /**
     * Generate sequential order number for the quarter
     */
    private function generateSequentialOrderNumber($targetQuarter, $year)
    {
        $quarterPrefix = "Q{$targetQuarter}-{$year}";
        
        // Get the highest existing order number for this quarter
        $lastOrderNumber = DB::table('orders')
            ->where('order_type', 'quarterly-' . $targetQuarter)
            ->where('order_date', '>=', Carbon::create($year, ($targetQuarter - 1) * 3 + 1, 1))
            ->where('order_date', '<', Carbon::create($year, $targetQuarter * 3 + 1, 1))
            ->where('order_number', 'LIKE', $quarterPrefix . '-%')
            ->orderBy('order_number', 'desc')
            ->value('order_number');

        if ($lastOrderNumber) {
            // Extract the sequence number from the last order
            $parts = explode('-', $lastOrderNumber);
            $lastSequence = intval(end($parts));
            $nextSequence = $lastSequence + 1;
        } else {
            // First order for this quarter
            $nextSequence = 1;
        }

        // Format as Q3-2025-0001
        return $quarterPrefix . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Process a single order item with micro-transaction (pattern from successful imports)
     */
    private function processOrderItem($orderId, $facilityId, $item, $months)
    {
        $maxRetries = 2;
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                DB::beginTransaction();
                
                // Get consumption data
                $consumptionData = MonthlyConsumptionItem::whereHas('monthlyConsumptionReport', function($query) use ($facilityId, $months) {
                    $query->where('facility_id', $facilityId)
                        ->whereIn('month_year', $months);
                })
                ->where('product_id', $item->product_id)
                ->sum('quantity');

                // Calculate AMC
                $amc = $consumptionData > 0 ? ($consumptionData / 3) : 10;

                // Get SOH
                $soh = DB::table('facility_inventory_items')
                    ->join('facility_inventories', 'facility_inventory_items.facility_inventory_id', '=', 'facility_inventories.id')
                    ->where('facility_inventories.facility_id', $facilityId)
                    ->where('facility_inventory_items.product_id', $item->product_id)
                    ->where('facility_inventory_items.expiry_date', '>=', now()->toDateString())
                    ->sum('facility_inventory_items.quantity');

                // Calculate needed quantity
                $neededQuantity = max(0, ceil(($amc * 3) - $soh));

                // Skip if no quantity needed
                if ($neededQuantity == 0) {
                    DB::commit();
                    return 'skipped';
                }

                // Create order item
                $orderItemId = DB::table('order_items')->insertGetId([
                    'order_id' => $orderId,
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
                        ->select('id', 'inventory_id', 'product_id', 'warehouse_id', 'location', 'batch_number', 'expiry_date', 'quantity', 'unit_cost', 'uom')
                        ->orderBy('expiry_date')
                        ->get();

                    $totalAvailable = $inventories->sum('quantity');
                    $totalToAllocate = min($neededQuantity, $totalAvailable);
                    $batchCount = $inventories->count();

                    if ($totalToAllocate > 0) {
                        // Log multi-batch allocation info
                        if ($batchCount > 1) {
                            Log::info("Multi-batch allocation", [
                                'product_id' => $item->product_id,
                                'product_name' => $item->product_name ?? 'Unknown',
                                'needed_quantity' => $neededQuantity,
                                'available_batches' => $batchCount,
                                'total_available' => $totalAvailable,
                                'will_allocate' => $totalToAllocate
                            ]);
                        }

                        // Update order item with allocation
                        DB::table('order_items')
                            ->where('id', $orderItemId)
                            ->update(['quantity_to_release' => $totalToAllocate]);

                        $remainingToAllocate = $totalToAllocate;
                        $allocatedBatches = [];
                        
                        foreach ($inventories as $inventory) {
                            if ($remainingToAllocate <= 0) break;

                            $batchAllocation = min($inventory->quantity, $remainingToAllocate);

                            // Track allocated batches for logging
                            $allocatedBatches[] = [
                                'batch_number' => $inventory->batch_number,
                                'allocated_quantity' => $batchAllocation,
                                'expiry_date' => $inventory->expiry_date
                            ];

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

                            // Update inventory item (specific batch)
                            DB::table('inventory_items')
                                ->where('id', $inventory->id)
                                ->update([
                                    'quantity' => DB::raw("quantity - {$batchAllocation}"),
                                    'total_cost' => DB::raw("unit_cost * (quantity - {$batchAllocation})")
                                ]);

                            // Update parent inventory (total quantity for product)
                            DB::table('inventories')
                                ->where('id', $inventory->inventory_id)
                                ->update([
                                    'quantity' => DB::raw("quantity - {$batchAllocation}")
                                ]);

                            $remainingToAllocate -= $batchAllocation;
                        }

                    }
                }

                DB::commit();
                return 'success';
                
            } catch (\Exception $e) {
                DB::rollBack();
                $attempt++;
                
                if ($attempt >= $maxRetries) {
                    return 'error';
                }
                
                // Brief wait before retry
                usleep(50000); // 50ms
            }
        }
        
        return 'error';
    }

    /**
     * Process the eligible items for a specific facility
     */
    private function processFacilityItems($orderId, $facility, $eligibleItems)
    {
        $processed = 0;
        $skipped = 0;
        $errors = 0;

        $this->info("   📦 Found {$eligibleItems->count()} eligible items for facility {$facility->id}");
        $bar = $this->output->createProgressBar($eligibleItems->count());
        $bar->start();

                 // Pre-calculate months for AMC to avoid repeated calculations
         $months = [];
         $currentDate = now()->subMonth();
         for ($i = 0; $i < 3; $i++) {
             $months[] = $currentDate->format('Y-m');
             $currentDate->subMonth();
         }

         foreach ($eligibleItems as $item) {
             $result = $this->processOrderItem($orderId, $facility->id, $item, $months);
            
            switch ($result) {
                case 'success':
                    $processed++;
                    break;
                case 'skipped':
                    $skipped++;
                    break;
                case 'error':
                default:
                    $errors++;
                    break;
            }
            
            $bar->advance();
            
            // Reduced delay for better performance with 76 facilities
            usleep(5000); // 5ms delay
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("   📊 Processing Summary for Facility {$facility->id}:");
        $this->info("      ✅ Successfully processed: {$processed} items");
        if ($skipped > 0) {
            $this->info("      ⏭️ Skipped (no quantity needed): {$skipped} items");
        }
        if ($errors > 0) {
            $this->warn("      ⚠️ Failed to process: {$errors} items");
        }
        
        Log::info("Facility quarterly order processing completed", [
            'facility_id' => $facility->id,
            'facility_type' => $facility->facility_type,
            'order_id' => $orderId,
            'processed' => $processed,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_eligible' => $eligibleItems->count()
        ]);

        return [
            'success' => $processed > 0,
            'processed' => $processed,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * Send email notification for quarterly order generation
     */
    private function sendOrderNotification($orderId, $facility, $result)
    {
        try {
            // Get the order with full details
            $order = Order::find($orderId);
            if (!$order) {
                $this->warn("         ⚠️ Could not find order {$orderId} for email notification");
                return false;
            }

            // Get facility with relationships
            $facilityModel = Facility::with('handledby')->find($facility->id);
            if (!$facilityModel) {
                $this->warn("         ⚠️ Could not find facility {$facility->id} for email notification");
                return false;
            }

            // Prepare order summary for email
            $orderSummary = [
                'total_items' => $result['processed'] + $result['skipped'] + $result['errors'],
                'processed' => $result['processed'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors']
            ];

            // Determine recipient email
            $recipientEmail = null;
            $recipientName = 'Facility Manager';

            // Priority 1: Check if facility has handled_by user with email
            if ($facilityModel->handledby && $facilityModel->handledby->email) {
                $recipientEmail = $facilityModel->handledby->email;
                $recipientName = $facilityModel->handledby->name ?? 'Facility Manager';
                $this->info("         📧 Sending to handled_by user: {$recipientName} ({$recipientEmail})");
            }
            // Priority 2: Use facility's direct email
            elseif ($facilityModel->email) {
                $recipientEmail = $facilityModel->email;
                $this->info("         📧 Sending to facility email: {$recipientEmail}");
            }
            else {
                $this->warn("         ⚠️ No email found for facility {$facility->id} - skipping notification");
                return false;
            }

            // Send the email
            Mail::to($recipientEmail, $recipientName)->send(
                new QuarterlyOrderGenerated($order, $facilityModel, $orderSummary)
            );

            $this->info("         ✅ Email notification sent to {$recipientEmail}");

            Log::info("Quarterly order email notification sent", [
                'order_id' => $orderId,
                'facility_id' => $facility->id,
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'order_summary' => $orderSummary
            ]);

            return true;

        } catch (\Exception $e) {
            $this->warn("         ⚠️ Failed to send email notification: " . $e->getMessage());
            Log::error("Failed to send quarterly order email notification", [
                'order_id' => $orderId,
                'facility_id' => $facility->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
