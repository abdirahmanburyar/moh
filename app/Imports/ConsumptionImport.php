<?php

namespace App\Imports;

use App\Models\AvarageMonthlyconsumption;
use App\Models\Product;
use App\Models\Category;
use App\Models\Dosage;
use App\Models\EligibleItem;
use App\Models\Facility;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class ConsumptionImport implements ToCollection
{
    protected $facilityId;
    protected $facilityType;
    protected $productCache = [];
    protected $eligibleItemsCache = [];
    protected $recordCount = 0;
    protected $records = [];
    protected $skippedItems = [];

    public function __construct($facilityId)
    {
        $this->facilityId = $facilityId;
        
        // Get facility type for eligible items check
        $facility = Facility::find($facilityId);
        if ($facility) {
            $this->facilityType = $facility->facility_type;
            
            // Pre-load eligible items for this facility type to improve performance
            $eligibleItems = EligibleItem::where('facility_type', $this->facilityType)
                ->with('product')
                ->get();
                
            foreach ($eligibleItems as $item) {
                if ($item->product) {
                    $this->eligibleItemsCache[strtolower($item->product->name)] = $item->product_id;
                }
            }
            
            Log::info("Loaded " . count($this->eligibleItemsCache) . " eligible items for facility ID: {$facilityId}, type: {$this->facilityType}");
        } else {
            Log::error("Facility not found with ID: {$facilityId}");
        }
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Get headers to identify month columns
        $headers = $rows->first();
        if (!$headers) return;
        
        $monthColumns = [];
        
        // Find SN and Item Description column indexes
        $snIndex = null;
        $itemDescIndex = null;
        
        foreach ($headers as $key => $header) {
            if (is_string($header)) {
                $header = trim($header);
                if (strtoupper($header) === 'SN') {
                    $snIndex = $key;
                } else if (strtoupper($header) === 'ITEM DESCRIPTION') {
                    $itemDescIndex = $key;
                } else if (!str_contains(strtoupper($header), 'AMC')) {
                    // It's a month column
                    $monthColumns[] = [
                        'key' => $key,
                        'name' => $header
                    ];
                }
            }
        }
        
        // Validate that we found the required columns
        if ($itemDescIndex === null) {
            throw new \Exception('Item Description column not found in Excel file');
        }
        
        if (count($monthColumns) === 0) {
            throw new \Exception('No valid month columns found in the Excel file');
        }
        
        // Process data rows
        foreach ($rows->skip(1) as $row) {
            // Skip empty rows
            if (empty($row[$itemDescIndex])) {
                continue;
            }
            
            $itemName = trim($row[$itemDescIndex]);
            
            // Check if this item is eligible for this facility type
            $productId = null;
            $itemNameLower = strtolower($itemName);
            
            if (isset($this->eligibleItemsCache[$itemNameLower])) {
                // Item is eligible, use the cached product ID
                $productId = $this->eligibleItemsCache[$itemNameLower];
                $this->productCache[$itemName] = $productId;
                Log::info("Found eligible item: {$itemName} with product ID: {$productId}");
            } else {
                // Check for similar item names with slight differences
                $found = false;
                foreach ($this->eligibleItemsCache as $eligibleName => $pid) {
                    // Check for similarity (e.g., case differences, extra spaces, etc.)
                    if (levenshtein($itemNameLower, $eligibleName) <= 3) { // Allow small differences
                        $productId = $pid;
                        $this->productCache[$itemName] = $productId;
                        $found = true;
                        Log::info("Found similar eligible item: {$eligibleName} for: {$itemName} with product ID: {$productId}");
                        break;
                    }
                }
                
                if (!$found) {
                    try {
                        // First check if the product already exists by name
                        $existingProduct = Product::where('name', $itemName)->first();
                        
                        if ($existingProduct) {
                            // Product exists, use it
                            $productId = $existingProduct->id;
                            $this->productCache[$itemName] = $productId;
                            
                            Log::info("Found existing product: {$itemName} with ID: {$productId}");
                        } else {
                            // Create the product - productID will be auto-generated in the Product model boot method
                            $product = new Product();
                            $product->name = $itemName;
                            
                            // Set category_id if needed
                            if (isset($row['category']) && !empty($row['category'])) {
                                $category = Category::firstOrCreate(['name' => $row['category']]);
                                $product->category_id = $category->id;
                            }
                            
                            // Set dosage_id if needed
                            if (isset($row['dosage_form']) && !empty($row['dosage_form'])) {
                                $dosage = Dosage::firstOrCreate(['name' => $row['dosage_form']]);
                                $product->dosage_id = $dosage->id;
                            }
                            
                            $product->save();
                            
                            $productId = $product->id;
                            $this->productCache[$itemName] = $productId;
                            
                            Log::info("Created new product: {$itemName} with ID: {$productId} and productID: {$product->productID}");
                        }
                        
                        // Check if this product is already in eligible_items for this facility type
                        $eligibleItem = EligibleItem::where('product_id', $productId)
                            ->where('facility_type', $this->facilityType)
                            ->first();
                            
                        if (!$eligibleItem) {
                            // Add this product to eligible items for this facility type
                            $eligibleItem = new EligibleItem();
                            $eligibleItem->product_id = $productId;
                            $eligibleItem->facility_type = $this->facilityType;
                            $eligibleItem->save();
                            
                            Log::info("Added product ID: {$productId} to eligible items for facility type: {$this->facilityType}");
                        }
                        
                        // Add to cache
                        $this->eligibleItemsCache[strtolower($itemName)] = $productId;
                    } catch (\Exception $e) {
                        Log::error("Failed to create product: {$itemName}. Error: {$e->getMessage()}");
                        $this->skippedItems[] = $itemName;
                        continue; // Skip this item
                    }
                }
            }
            
            // Process each month column
            foreach ($monthColumns as $monthCol) {
                $monthKey = $monthCol['key'];
                $monthName = $monthCol['name'];
                
                // Parse month-year from header (e.g., "Jan-25" to "2025-01")
                if (preg_match('/([A-Za-z]{3})-(\d{2})/', $monthName, $matches)) {
                    $month = $this->getMonthNumber($matches[1]);
                    $year = '20' . $matches[2]; // Convert "25" to "2025"
                    $monthYear = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
                    
                    // Get quantity from cell (handle empty or non-numeric values)
                    $quantity = 0;
                    if (isset($row[$monthKey]) && is_numeric($row[$monthKey])) {
                        $quantity = (float)$row[$monthKey];
                    }
                    
                    // Add to records
                    $this->records[] = [
                        'facility_id' => $this->facilityId,
                        'product_id' => $productId,
                        'month_year' => $monthYear,
                        'quantity' => $quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    $this->recordCount++;
                    
                    // Insert in batches to improve performance
                    if (count($this->records) >= 100) {
                        $this->saveRecords();
                    }
                }
            }
        }
        
        // Insert or update remaining records
        if (!empty($this->records)) {
            $this->saveRecords();
        }
    }
    
    /**
     * Save records to database
     */
    private function saveRecords()
    {
        if (empty($this->records)) {
            return;
        }
        
        // Filter out records with null product_id
        $validRecords = array_filter($this->records, function($record) {
            return !is_null($record['product_id']);
        });
        
        if (empty($validRecords)) {
            Log::warning("No valid records to save (all had null product_id)");
            $this->records = [];
            return;
        }
        
        try {
            // Use create instead of insert to ensure model events are triggered
            foreach ($validRecords as $record) {
                // Double-check that product_id is not null before creating the record
                if (!empty($record['product_id'])) {
                    try {
                        AvarageMonthlyconsumption::updateOrCreate(
                            [
                                'facility_id' => $record['facility_id'],
                                'product_id' => $record['product_id'],
                                'month_year' => $record['month_year']
                            ],
                            [
                                'quantity' => $record['quantity']
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::error("Error saving consumption record for product ID {$record['product_id']}: " . $e->getMessage());
                    }
                } else {
                    Log::warning("Skipped record with null product_id for month {$record['month_year']}");
                }
            }
            
            Log::info("Processed " . count($validRecords) . " consumption records");
        } catch (\Exception $e) {
            Log::error("Error saving consumption records: " . $e->getMessage());
        }
        
        // Clear records array
        $this->records = [];
    }
    
    /**
     * Get the record count
     */
    public function getRecordCount()
    {
        return $this->recordCount;
    }
    
    /**
     * Get skipped items
     */
    public function getSkippedItems()
    {
        return $this->skippedItems;
    }
    
    /**
     * Convert month name to number
     */
    private function getMonthNumber($monthName)
    {
        $months = [
            'Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6,
            'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12
        ];
        
        return $months[$monthName] ?? 1;
    }
}