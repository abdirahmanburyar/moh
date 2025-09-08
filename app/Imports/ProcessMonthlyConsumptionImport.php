<?php

namespace App\Imports;

use App\Models\MonthlyConsumptionReport;
use App\Models\MonthlyConsumptionItem;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

class ProcessMonthlyConsumptionImport
{
    protected $filePath;
    protected $facilityId;
    protected $importId;
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $reader;
    protected $monthColumns = [];

    // Define possible column header variations
    protected $itemDescriptionHeaders = [
        'item description',
        'item_description',
        'itemdescription',
        'description',
        'item',
        'product',
        'product name',
        'product_name'
    ];

    /**
     * Create a new import instance.
     */
    public function __construct($filePath, $facilityId, $importId)
    {
        $this->filePath = $filePath;
        $this->facilityId = $facilityId;
        $this->importId = $importId;
    }

    /**
     * Import the data.
     */
    public function import()
    {
        try {
            if (!file_exists($this->filePath)) {
                throw new \Exception("Import file not found: {$this->filePath}");
            }

            // Create the reader based on file extension
            $this->reader = ReaderEntityFactory::createReaderFromFile($this->filePath);
            $this->reader->open($this->filePath);

            $allItems = [];
            $headers = null;
            $itemDescriptionIndex = null;

            // Iterate through all sheets
            foreach ($this->reader->getSheetIterator() as $sheet) {
                // Iterate through all rows
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $values = $row->toArray();

                    // Handle headers
                    if (!$headers) {
                        $headers = $values;
                        
                        // Identify month columns and item description column
                        $this->identifyColumns($headers);
                        
                        // Find item description column
                        foreach ($headers as $index => $header) {
                            $headerLower = strtolower(trim($header));
                            if (in_array($headerLower, $this->itemDescriptionHeaders)) {
                                $itemDescriptionIndex = $index;
                                break;
                            }
                        }

                        if ($itemDescriptionIndex === null) {
                            throw new \Exception('Item Description column not found in Excel file.');
                        }
                        
                        if (empty($this->monthColumns)) {
                            throw new \Exception('No month-year columns found in Excel file. Please ensure columns are named like "Jan-25", "Feb-25", etc.');
                        }
                        
                        continue;
                    }

                    // Skip empty rows
                    if (empty(array_filter($values))) {
                        continue;
                    }

                    // Process data row
                    $description = trim($values[$itemDescriptionIndex] ?? '');

                    if (empty($description)) {
                        continue;
                    }

                    // Find product by description
                    $product = Product::where('name', $description)->first();

                    if (!$product) {
                        $this->errors[] = "Product not found: {$description}";
                        $this->skippedCount++;
                        continue;
                    }

                    // Process each month column
                    foreach ($this->monthColumns as $columnIndex => $monthYear) {
                        // Handle empty, null, or non-numeric values by setting them to 0
                        $rawValue = isset($values[$columnIndex]) ? $values[$columnIndex] : '';
                        $quantity = is_numeric($rawValue) ? (int)$rawValue : 0;
                        
                        // Save all records, including those with 0 quantity
                        $allItems[] = [
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'month_year' => $monthYear,
                            'product_name' => $description
                        ];
                        $this->importedCount++;
                    }
                }
            }

            // Close the reader before processing the data
            $this->reader->close();
            $this->reader = null;

            // Group items by month_year and process each month
            $itemsByMonth = collect($allItems)->groupBy('month_year');
            
            foreach ($itemsByMonth as $monthYear => $monthItems) {
                // Create or find the monthly report for this month
                $report = MonthlyConsumptionReport::updateOrCreate(
                    [
                        'facility_id' => $this->facilityId,
                        'month_year' => $monthYear
                    ],
                    ['generated_by' => auth()->user()->name ?? "System"]
                );

                // Delete existing items for this report
                MonthlyConsumptionItem::where('parent_id', $report->id)->delete();

                // Prepare items for insertion
                $itemsToInsert = $monthItems->map(function($item) use ($report) {
                    return [
                        'parent_id' => $report->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                })->toArray();

                // Batch insert for better performance
                if (!empty($itemsToInsert)) {
                    MonthlyConsumptionItem::insert($itemsToInsert);
                }
            }

            // Clean up the file
            $this->cleanupFile();

            return [
                'imported' => $this->importedCount,
                'skipped' => $this->skippedCount,
                'errors' => $this->errors
            ];

        } catch (\Exception $e) {
            // Make sure to close the reader if it's open
            if ($this->reader !== null) {
                try {
                    $this->reader->close();
                } catch (\Exception $closeException) {
                   
                }
                $this->reader = null;
            }

            // Clean up the file
            $this->cleanupFile();

            throw $e;
        }
    }

    /**
     * Identify month columns and item description column from headers
     */
    protected function identifyColumns($headers)
    {
        $this->monthColumns = [];
        
        foreach ($headers as $index => $header) {
            try {
                // Convert header to string if it's not already (handle DateTime objects from Excel)
                if ($header instanceof \DateTime) {
                    // If Excel interpreted the header as a date, convert it back to string
                    $header = $header->format('M-y'); // This will give us Jan-25 format
                } elseif (is_object($header)) {
                    $header = (string)$header;
                } elseif (!is_string($header)) {
                    $header = (string)$header;
                }
                
                $headerLower = strtolower(trim($header));
                
                // Skip the item description column
                if (in_array($headerLower, $this->itemDescriptionHeaders)) {
                    continue;
                }
                
                // Check if column matches month-year pattern (Jan-25, Feb-25, etc.)
                if ($this->isMonthYearColumn($headerLower)) {
                    $monthYear = $this->parseMonthYear($headerLower);
                    if ($monthYear) {
                        $this->monthColumns[$index] = $monthYear;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Error processing column header: " . json_encode($header) . " - " . $e->getMessage());
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

    /**
     * Clean up the imported file with retries
     */
    protected function cleanupFile()
    {
        if (!file_exists($this->filePath)) {
            return;
        }

        $maxRetries = 3;
        $retryDelay = 1; // seconds

        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                if (@unlink($this->filePath)) {
                    return;
                }
                sleep($retryDelay);
            } catch (\Exception $e) {
               
                if ($i < $maxRetries - 1) {
                    sleep($retryDelay);
                }
            }
        }

        Log::error("Failed to delete file after {$maxRetries} attempts", [
            'file' => $this->filePath
        ]);
    }
} 