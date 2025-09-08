<?php

namespace App\Imports;

use App\Models\WarehouseAmc;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Events\BeforeImport;

class WarehouseAmcImport implements 
    ToModel, 
    WithHeadingRow, 
    WithChunkReading, 
    WithBatchInserts, 
    WithValidation, 
    SkipsEmptyRows, 
    SkipsOnFailure,
    WithEvents,
    ShouldQueue
{
    use InteractsWithQueue, Queueable;
    protected $importedCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $productCache = [];
        protected $importId;
    protected $storedFilePath;
    protected $monthYears = [];

    public function __construct(string $importId, string $storedFilePath = null)
    {
        $this->importId = $importId;
        $this->storedFilePath = $storedFilePath;

        // Get existing month years from database
        $this->monthYears = WarehouseAmc::select('month_year')
            ->distinct()
            ->orderBy('month_year', 'desc')
            ->pluck('month_year')
            ->toArray();
    }

    public function model(array $row)
    {
        try {
            // Log the raw row data for debugging
            Log::info("Processing row", [
                'import_id' => $this->importId,
                'row_keys' => array_keys($row),
                'row_data' => $row
            ]);
            
            // Check if item field exists and has a value
            if (!isset($row['item']) || empty(trim($row['item'] ?? ''))) {
                Log::info("Skipping row with empty item field", ['row' => $row]);
                $this->skippedCount++;
                return null;
            }

            $itemName = trim($row['item']);

            // Find the product by name
            $product = Product::where('name', $itemName)->first();
            
            if (!$product) {
                // Product doesn't exist, skip this row and continue
                $this->errors[] = "Product not found: {$itemName} - Skipped";
                $this->skippedCount++;
                return null;
            }

            // Debug: Log product found
            Log::info("Processing product: {$itemName} (ID: {$product->id})");

            // Update progress in cache
            Cache::increment($this->importId);

            // Process each month column and create/update WarehouseAmc records
            $processedMonths = 0;
            Log::info("Row keys: " . implode(', ', array_keys($row)));
            
            foreach ($row as $key => $value) {
                        // Skip non-month columns
        if (in_array($key, ['item', 'AMC'])) {
            Log::info("Skipping non-month column: {$key} = {$value}");
            continue;
        }

                // Convert formatted month back to YYYY-MM format
                $monthYear = $this->parseMonthYear($key);
                if (!$monthYear) {
                    Log::info("Could not parse month from column: {$key} = {$value}");
                    continue;
                }

                Log::info("Processing month: {$key} -> {$monthYear} = {$value}");

                // Clean and validate quantity
                $quantity = $this->cleanQuantity($value);
                
                // If quantity is null or empty, skip this month (don't update existing data)
                if ($quantity === null) {
                    Log::info("Skipping null/empty quantity for month: {$monthYear} (value: '{$value}')");
                    continue;
                }
                
                Log::info("Quantity cleaned: '{$value}' -> {$quantity}");

                Log::info("Creating/updating WarehouseAmc: product_id={$product->id}, month_year={$monthYear}, quantity={$quantity}");

                // Create or update WarehouseAmc record
                $warehouseAmc = WarehouseAmc::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'month_year' => $monthYear,
                    ],
                    [
                        'quantity' => $quantity,
                    ]
                );

                if ($warehouseAmc->wasRecentlyCreated) {
                    $this->importedCount++;
                    Log::info("Created new WarehouseAmc record: ID={$warehouseAmc->id}");
                } else {
                    $this->updatedCount++;
                    Log::info("Updated existing WarehouseAmc record: ID={$warehouseAmc->id}");
                }
                
                $processedMonths++;
            }

            // If no months were processed, count as skipped
            if ($processedMonths === 0) {
                $this->skippedCount++;
                return null;
            }

            // Return null since we're handling the creation manually
            return null;

        } catch (\Exception $e) {
            $this->errors[] = "Error processing row for '{$row['item']}': " . $e->getMessage();
            $this->skippedCount++;
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'item' => 'nullable|string|max:255', // Changed from required to nullable
            'AMC' => 'nullable|string|max:255',
        ];
    }

    /**
     * Custom validation failure handling
     */
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = "Row {$failure->row()}: {$failure->attribute()} - {$failure->errors()[0]}";
            $this->skippedCount++;
        }
        
        Log::warning('Validation failures in import', [
            'import_id' => $this->importId,
            'failures' => $this->errors
        ]);
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
            BeforeImport::class => function (BeforeImport $event) {
                Log::info('=== WAREHOUSE AMC IMPORT STARTING ===', [
                    'import_id' => $this->importId,
                    'stored_file_path' => $this->storedFilePath
                ]);
            },
            AfterImport::class => function (AfterImport $event) {
                // Update cache with final results
                $results = $this->getResults();
                $message = "Import completed successfully. ";
                if ($results['imported'] > 0) {
                    $message .= "Created: {$results['imported']} new AMC records. ";
                }
                if ($results['updated'] > 0) {
                    $message .= "Updated: {$results['updated']} existing AMC records. ";
                }
                if ($results['skipped'] > 0) {
                    $message .= "Skipped: {$results['skipped']} rows. ";
                }

                Cache::put("warehouse_amc_import_{$this->importId}", [
                    'status' => 'completed',
                    'progress' => 100,
                    'message' => trim($message),
                    'results' => $results
                ], 3600);
                
                // Log completion
                Log::info("Warehouse AMC import completed", [
                    'import_id' => $this->importId,
                    'imported' => $this->importedCount,
                    'updated' => $this->updatedCount,
                    'skipped' => $this->skippedCount,
                    'errors' => $this->errors
                ]);
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

    /**
     * Parse month year from formatted header (e.g., "Feb 2025" -> "2025-02")
     */
    private function parseMonthYear($headerValue)
    {
        if (!$headerValue || trim($headerValue) === '') {
            return null;
        }

        $headerValue = trim($headerValue);

        // Handle the format "jan_2025", "feb_2025", etc.
        if (preg_match('/^([a-z]{3})_(\d{4})$/i', $headerValue, $matches)) {
            $monthAbbr = strtolower($matches[1]);
            $year = $matches[2];
            
            // Map month abbreviations to numbers
            $monthMap = [
                'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
                'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08',
                'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12'
            ];
            
            if (isset($monthMap[$monthAbbr])) {
                $result = $year . '-' . $monthMap[$monthAbbr];
                Log::info("Successfully parsed month: {$headerValue} -> {$result}");
                return $result;
            }
        }

        // Try to parse various date formats
        $formats = [
            'M Y',      // Feb 2025
            'F Y',      // February 2025
            'Y-m',      // 2025-02
            'm/Y',      // 02/2025
            'm-Y',      // 02-2025
        ];

        foreach ($formats as $format) {
            try {
                $date = \DateTime::createFromFormat($format, $headerValue);
                if ($date) {
                    return $date->format('Y-m');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // If no format matches, try to extract year and month manually
        if (preg_match('/(\w+)\s+(\d{4})/', $headerValue, $matches)) {
            $monthName = $matches[1];
            $year = $matches[2];
            
            $monthNumber = date('m', strtotime($monthName . ' 1'));
            if ($monthNumber) {
                return $year . '-' . str_pad($monthNumber, 2, '0', STR_PAD_LEFT);
            }
        }

        // Try to handle Excel's date format (e.g., "2025-01-01" -> "2025-01")
        if (preg_match('/^(\d{4})-(\d{1,2})/', $headerValue, $matches)) {
            $year = $matches[1];
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            return $year . '-' . $month;
        }

        return null;
    }

    /**
     * Clean and validate quantity value
     */
    private function cleanQuantity($value)
    {
        // Return null for empty or null values
        if ($value === null || $value === '' || trim($value) === '') {
            return null;
        }

        // Remove any non-numeric characters except decimal point and minus
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
        
        if ($cleaned === '' || $cleaned === '-') {
            return null;
        }

        $quantity = (float) $cleaned;
        
        // Ensure non-negative
        return max(0, $quantity);
    }
}


