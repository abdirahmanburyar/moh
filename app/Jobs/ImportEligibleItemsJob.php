<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\EligibleItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportEligibleItemsJob implements ShouldQueue
{
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $facilityTypes = ["Regional Hospital", "District Hospital", "Health Centre", "Primary Health Unit"];

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle()
    {
        try {
            set_time_limit(300); // Set PHP timeout to 5 minutes
            
            // Load data in chunks
            $data = Excel::toCollection(null, $this->filePath)[0];
            $chunks = $data->chunk(100); // Process 100 rows at a time

            // Remove header row if present
            if ($data->count() > 0 && 
                strtolower($data[0][0]) === 'item_description' && 
                strtolower($data[0][1]) === 'facility_type') {
                $data = $data->slice(1);
            }

            DB::beginTransaction();
            $successCount = 0;
            $errorItems = [];

            foreach ($chunks as $chunk) {
                foreach ($chunk as $row) {
                    $itemDescription = trim($row[0] ?? '');
                $facilityType = trim($row[1] ?? '');

                // Skip empty rows
                if (empty($itemDescription) || empty($facilityType)) continue;

                // Check if facility type is valid
                if (!in_array($facilityType, $this->facilityTypes)) {
                    $errorItems[] = ["item" => $itemDescription, "error" => "Invalid facility type: {$facilityType}"];
                    continue;
                }

                // Find product by name (case-insensitive exact match)
                $product = Product::whereRaw('LOWER(name) = ?', [strtolower($itemDescription)])->first();
                logger()->info($product);

                // If not found, try with a more flexible search
                if (!$product) {
                    $product = Product::where('name', 'like', "{$itemDescription}")->first();
                }

                if (!$product) {
                    $errorItems[] = ["item" => $itemDescription, "error" => "Product not found"];
                    continue;
                }

                // Check if eligible item already exists
                $exists = EligibleItem::where('product_id', $product->id)
                    ->where('facility_type', $facilityType)
                    ->exists();

                if (!$exists) {
                    EligibleItem::create([
                        'product_id' => $product->id,
                        'facility_type' => $facilityType
                    ]);
                    $successCount++;
                }
            }

                }
                // Commit after each chunk
                DB::commit();
                DB::beginTransaction();

            // Delete the temporary file
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Delete the temporary file
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
            
            logger()->error('Import error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'error_items' => $errorItems ?? []
            ]);
            
            throw $e;
        }
        
        // Log success information
        logger()->info('Import completed:', [
            'success_count' => $successCount ?? 0,
            'error_count' => count($errorItems ?? [])
        ]);
    }
}
