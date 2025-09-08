<?php

namespace App\Imports;

use App\Models\MonthlyConsumptionItem;
use App\Models\MonthlyConsumptionReport;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class MonthlyFacilityConsumptionImport implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue
{
    protected $facilityId;
    protected $monthColumns = [];
    
    public function __construct($facilityId)
    {
        $this->facilityId = $facilityId;
    }

    public function collection(Collection $rows)
    {
        try {
            if ($rows->isEmpty()) {
                return;
            }

            // Get the first row to identify month columns
            $firstRow = $rows->first();
            $this->identifyMonthColumns($firstRow);
            
            $allItems = [];
            
            // Process each row
            foreach ($rows as $row) {
                $description = $this->getRowValue($row, ['item_description']);
                
                if (empty($description)) {
                    continue;
                }
                
                // Find product by name (since ProductsImport uses 'name' field)
                $product = Product::where('name', $description)->first();
                
                if (!$product) {
                    Log::warning("Product not found: {$description}");
                    continue;
                }
                
                // Process each month column
                foreach ($this->monthColumns as $columnKey => $monthYear) {
                    $rawQuantity = $this->getRowValue($row, [$columnKey]);
                    // Handle empty, null, or non-numeric values by setting them to 0
                    $quantity = is_numeric($rawQuantity) ? (int)$rawQuantity : 0;
                    
                    // Create or find the monthly report for this month
                    $report = MonthlyConsumptionReport::updateOrCreate(
                        [
                            'facility_id' => $this->facilityId,
                            'month_year' => $monthYear
                        ],
                        ['generated_by' => auth()->user()->name ?? "System"]
                    );
                    
                    // Save all records, including those with 0 quantity
                    $allItems[] = [
                        'parent_id' => $report->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'month_year' => $monthYear,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
            
            // Group items by month_year for batch deletion and insertion
            $itemsByMonth = collect($allItems)->groupBy('month_year');
            
            foreach ($itemsByMonth as $monthYear => $monthItems) {
                // Find the report for this month
                $report = MonthlyConsumptionReport::where([
                    'facility_id' => $this->facilityId,
                    'month_year' => $monthYear
                ])->first();
                
                if ($report) {
                    // Delete existing items for this report
                    MonthlyConsumptionItem::where('parent_id', $report->id)->delete();
                    
                    // Prepare items for insertion (remove month_year as it's not in the table)
                    $itemsToInsert = $monthItems->map(function($item) {
                        unset($item['month_year']);
                        return $item;
                    })->toArray();
                    
                    // Batch insert for better performance
                    if (!empty($itemsToInsert)) {
                        MonthlyConsumptionItem::insert($itemsToInsert);
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Import failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Identify month-year columns from the first row
     */
    protected function identifyMonthColumns($firstRow)
    {
        $this->monthColumns = [];
        
        foreach ($firstRow as $columnKey => $value) {
            try {
                // Convert columnKey to string if it's not already
                if ($columnKey instanceof \DateTime) {
                    // If Excel interpreted the header as a date, convert it back to string
                    $columnKey = $columnKey->format('M-y'); // This will give us Jan-25 format
                } elseif (is_object($columnKey)) {
                    $columnKey = (string)$columnKey;
                } elseif (!is_string($columnKey)) {
                    $columnKey = (string)$columnKey;
                }
                
                $columnKey = strtolower(trim($columnKey));
                
                // Skip the item description column
                if (in_array($columnKey, ['item_description', 'item', 'description'])) {
                    continue;
                }
                
                // Check if column matches month-year pattern (Jan-25, Feb-25, etc.)
                if ($this->isMonthYearColumn($columnKey)) {
                    $monthYear = $this->parseMonthYear($columnKey);
                    if ($monthYear) {
                        $this->monthColumns[$columnKey] = $monthYear;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Error processing column key: " . json_encode($columnKey) . " - " . $e->getMessage());
                continue;
            }
        }
        
        Log::info("Identified month columns: " . json_encode($this->monthColumns));
    }
    
    /**
     * Check if a column name looks like a month-year format
     */
    protected function isMonthYearColumn($columnName)
    {
        // Pattern: Mon-YY or Month-YY (Jan-25, Feb-25, January-25, etc.)
        return preg_match('/^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|january|february|march|april|june|july|august|september|october|november|december)-\d{2}$/i', $columnName);
    }
    
    /**
     * Parse month-year column to standard format (YYYY-MM)
     */
    protected function parseMonthYear($columnName)
    {
        // Extract month and year
        if (preg_match('/^([a-z]+)-(\d{2})$/i', $columnName, $matches)) {
            $monthStr = strtolower($matches[1]);
            $year = '20' . $matches[2]; // Convert 25 to 2025
            
            // Map month names to numbers
            $monthMap = [
                'jan' => '01', 'january' => '01',
                'feb' => '02', 'february' => '02',
                'mar' => '03', 'march' => '03',
                'apr' => '04', 'april' => '04',
                'may' => '05',
                'jun' => '06', 'june' => '06',
                'jul' => '07', 'july' => '07',
                'aug' => '08', 'august' => '08',
                'sep' => '09', 'september' => '09',
                'oct' => '10', 'october' => '10',
                'nov' => '11', 'november' => '11',
                'dec' => '12', 'december' => '12',
            ];
            
            if (isset($monthMap[$monthStr])) {
                return $year . '-' . $monthMap[$monthStr];
            }
        }
        
        return null;
    }
    
    protected function getRowValue($row, array $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            // Ensure key is a string
            $key = is_string($key) ? $key : (string)$key;
            $key = strtolower($key);
            if (isset($row[$key])) {
                return $row[$key];
            }
        }
        return null;
    }
    
    public function chunkSize(): int
    {
        return 50;
    }
    
    public function batchSize(): int
    {
        return 50;
    }
}