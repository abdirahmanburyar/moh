<?php

namespace App\Imports;

use App\Models\ReorderLevel;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;

class ReorderLevelImport implements 
    ToModel, 
    WithHeadingRow, 
    WithChunkReading, 
    WithBatchInserts, 
    WithValidation, 
    SkipsEmptyRows, 
    WithEvents
{

    protected $importedCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $productCache = [];
    protected $importId;

    public function __construct(string $importId)
    {
        $this->importId = $importId;
    }

    public function model(array $row)
    {
        try {
            if (empty($row['item_description'])) {
                $this->skippedCount++;
                return null;
            }

            $itemDescription = trim($row['item_description']);

            // Find the product by name - check if it exists
            $product = Product::where('name', $itemDescription)->first();
            
            if (!$product) {
                // Product doesn't exist, skip this row and continue
                $this->errors[] = "Product not found: {$itemDescription} - Skipped";
                $this->skippedCount++;
                return null; // Return null to skip this row
            }

            // Product exists, proceed with validation
            // Validate AMC
            $amc = !empty($row['amc']) ? (float) $row['amc'] : 0;
            if ($amc < 0) {
                $this->errors[] = "Invalid AMC value for {$itemDescription}: {$amc} - Skipped";
                $this->skippedCount++;
                return null;
            }

            // Validate Lead Time (default to 5 if empty)
            $leadTime = !empty($row['lead_time']) ? (int) $row['lead_time'] : 5;
            if ($leadTime < 1) {
                $this->errors[] = "Invalid Lead Time value for {$itemDescription}: {$leadTime} - Skipped";
                $this->skippedCount++;
                return null;
            }

            // Update progress in cache
            Cache::increment($this->importId);

            // Calculate reorder level: AMC * Lead Time
            $reorderLevel = $amc * $leadTime;

            // Check if reorder level already exists for this product
            $existingReorderLevel = ReorderLevel::where('product_id', $product->id)->first();

            if ($existingReorderLevel) {
                // Update existing reorder level
                $existingReorderLevel->update([
                    'amc' => $amc,
                    'lead_time' => $leadTime,
                    'reorder_level' => $reorderLevel,
                ]);
                $this->updatedCount++;
            } else {
                // Create new reorder level
                ReorderLevel::create([
                    'product_id' => $product->id,
                    'amc' => $amc,
                    'lead_time' => $leadTime,
                    'reorder_level' => $reorderLevel,
                ]);
                $this->importedCount++;
            }

        } catch (\Exception $e) {
            $this->errors[] = "Error processing row: " . $e->getMessage();
            $this->skippedCount++;
            return null; // Return null to skip this row and continue
        }
    }

    public function rules(): array
    {
        return [
            'item_description' => 'required|string|max:255',
            'amc' => 'nullable|numeric|min:0',
            'lead_time' => 'nullable|integer|min:1',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                Cache::forget($this->importId);
            },
        ];
    }

    public function getResults()
    {
        return [
            'imported' => $this->importedCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors,
        ];
    }
}
