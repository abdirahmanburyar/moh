<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Facility;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\EligibleItem;
use App\Models\InventoryAllocation;
use App\Models\FacilityInventoryItem;
use App\Models\MonthlyConsumptionItem;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;

class ProcessFacilityQuarterlyOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $facilityId;
    protected $targetQuarter;
    protected $year;

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
    public function __construct(int $facilityId, int $targetQuarter, int $year)
    {
        $this->facilityId = $facilityId;
        $this->targetQuarter = $targetQuarter;
        $this->year = $year;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info("Starting quarterly order processing for facility {$this->facilityId}");

            // Get facility
            $facility = DB::table('facilities')
                ->where('id', $this->facilityId)
                ->where('is_active', true)
                ->first();

            if (!$facility) {
                Log::error("Facility {$this->facilityId} not found or not active");
                return;
            }

            // Create order
            $timestamp = now()->format('His');
            $orderNumber = "OR-{$this->targetQuarter}-{$this->year}-{$timestamp}-" . str_pad($facility->id, 4, '0', STR_PAD_LEFT);
            
            DB::beginTransaction();
            
            $orderId = DB::table('orders')->insertGetId([
                'facility_id' => $facility->id,
                'order_number' => $orderNumber,
                'order_type' => 'quarterly-'.$this->targetQuarter,
                'status' => 'pending',
                'order_date' => Carbon::create($this->year, ($this->targetQuarter - 1) * 3 + 1, 1),
                'expected_date' => Carbon::create($this->year, ($this->targetQuarter - 1) * 3 + 1, 1)->addDays(7),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            DB::commit();
            
            Log::info("Created order: {$orderNumber} with ID: {$orderId}");

            // Get eligible items in chunks
            $chunkSize = 10;
            $chunks = [];
            
            DB::table('eligible_items')
                ->where('facility_type', $facility->facility_type)
                ->join('products', 'eligible_items.product_id', '=', 'products.id')
                ->select('eligible_items.*', 'products.id as product_id')
                ->orderBy('eligible_items.id')
                ->chunk($chunkSize, function($items) use ($orderId, $facility, &$chunks) {
                    $chunks[] = new ProcessQuarterlyOrderItems($orderId, $facility->id, $items->toArray());
                });

            Log::info("Created " . count($chunks) . " job chunks for order {$orderNumber}");

            // Dispatch chunks in smaller batches to avoid lock timeouts
            $batchGroups = array_chunk($chunks, 2); // Process 2 chunks at a time
            
            foreach ($batchGroups as $groupIndex => $batchChunks) {
                $maxRetries = 3;
                $attempt = 0;
                $batch = null;

                while ($attempt < $maxRetries && !$batch) {
                    try {
                        if ($attempt > 0) {
                            sleep(pow(2, $attempt)); // Exponential backoff
                            Log::info("Retrying batch group " . ($groupIndex + 1) . " (attempt {$attempt})");
                        }

                        $batch = Bus::batch($batchChunks)
                            ->name("quarterly-order-{$orderNumber}-group-" . ($groupIndex + 1))
                            ->allowFailures()
                            ->onQueue('quarterly-orders')
                            ->dispatch();

                        Log::info("Successfully queued batch group " . ($groupIndex + 1) . " with ID: " . $batch->id);
                        
                        // Small delay between batch groups
                        if ($groupIndex < count($batchGroups) - 1) {
                            sleep(1);
                        }
                        
                        break;

                    } catch (\Exception $e) {
                        $attempt++;
                        if ($attempt >= $maxRetries) {
                            Log::error("Failed to create batch group " . ($groupIndex + 1) . " after {$maxRetries} attempts: " . $e->getMessage());
                            throw $e;
                        }
                    }
                }
            }

            Log::info("Successfully queued all job batches for order {$orderNumber}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing quarterly order for facility {$this->facilityId}: " . $e->getMessage());
            throw $e;
        }
    }
} 