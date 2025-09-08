<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\EligibleItem;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Validators\Failure;

class EligibleItemImport implements 
    ToModel, 
    WithHeadingRow, 
    WithChunkReading, 
    WithBatchInserts, 
    WithValidation, 
    SkipsOnError, 
    SkipsEmptyRows,
    WithCalculatedFormulas
{
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $importId;
    protected $validFacilityTypes = [
        'Health Centre',
        'Primary Health Unit',
        'District Hospital',
        'Regional Hospital'
    ];

    /**
     * Create a new import instance.
     */
    public function __construct($importId = null)
    {
        $this->importId = $importId;
    }

    /**
     * @param array $row
     */
    public function model(array $row)
    {
        try {
            // Skip if no item description or facility type
            if (empty($row['item_description']) || empty($row['facility_type'])) {
                $this->skippedCount++;
                return null;
            }

            $itemName = trim($row['item_description']);
            $facilityType = trim($row['facility_type']);

            // Validate facility type
            if (!in_array($facilityType, $this->validFacilityTypes)) {
                $this->errors[] = "Invalid facility type: {$facilityType}";
                $this->skippedCount++;
                return null;
            }

            // Find the product
            $product = Product::where('name', $itemName)->first();
            if (!$product) {
                $this->errors[] = "Product not found: {$itemName}";
                $this->skippedCount++;
                return null;
            }

            // Check if eligible item already exists
            if (EligibleItem::where('product_id', $product->id)
                           ->where('facility_type', $facilityType)
                           ->exists()) {
                $this->errors[] = "Eligible item already exists for product '{$itemName}' and facility type '{$facilityType}'";
                $this->skippedCount++;
                return null;
            }

            $this->importedCount++;

            return new EligibleItem([
                'product_id' => $product->id,
                'facility_type' => $facilityType
            ]);

        } catch (\Exception $e) {
            $this->errors[] = "Error processing row: " . $e->getMessage();
            $this->skippedCount++;
            return null;
        }
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'item_description' => 'required|string|max:255',
            'facility_type' => 'required|string|in:' . implode(',', $this->validFacilityTypes),
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'item_description.required' => 'Item description is required.',
            'facility_type.required' => 'Facility type is required.',
            'facility_type.in' => 'Facility type must be one of: ' . implode(', ', $this->validFacilityTypes),
        ];
    }

    /**
     * @param Failure[] $failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = "Row {$failure->row()}: {$failure->errors()[0]}";
            $this->skippedCount++;
        }
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000; // Process 1000 rows at a time
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return 100; // Insert 100 records at a time
    }

    /**
     * Get the results of the import
     */
    public function getResults()
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors,
        ];
    }

    /**
     * Get the errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get the imported count
     */
    public function getImportedCount()
    {
        return $this->importedCount;
    }

    /**
     * Get the skipped count
     */
    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    /**
     * Handle errors during import
     */
    public function onError(\Throwable $e)
    {
        $this->errors[] = "Error: " . $e->getMessage();
        $this->skippedCount++;
    }


} 