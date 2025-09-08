<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Inventory;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Carbon\Carbon;

class UploadInventory implements 
    ToModel, 
    WithHeadingRow, 
    WithChunkReading, 
    WithBatchInserts, 
    SkipsEmptyRows, 
    WithEvents,
    ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public $importId;

    public function __construct(string $importId)
    {
        $this->importId = $importId;
    }

    public function model(array $row)
    {
        try {
            // DB::beginTransaction();
            if (empty($row['item']) || empty($row['quantity']) || empty($row['batch_no']) || empty($row['expiry_date'])) {
                return null;
            }

            $product = $this->getProduct($row['item']);
            if (!$product) {
                return null;
            }
            $inventory = $this->getInventory($product->id);

            $expiryDate = $this->parseExpiryDate($row['expiry_date']);

            $batchNumber = trim($row['batch_no']);
            
            $item = InventoryItem::where('batch_number', $batchNumber)
            ->where('product_id', $product->id)->first();

            if($item){
                $item->increment('quantity', (float) $row['quantity']);
            }else{
                $item = InventoryItem::create([
                    'inventory_id' => $inventory->id,
                    'product_id' => $product->id,
                    'quantity' => (float) $row['quantity'],
                    'expiry_date' => $expiryDate,
                    'warehouse_id' => 1,
                    'uom' => $row['uom'] ?? null,
                    'batch_number' => $batchNumber,
                    'location' => $row['location'] ?? null,
                    'unit_cost' => 0.00,
                    'total_cost' => 0.00,
                ]);
            }

            // DB::commit();

            return null;

        } catch (\Throwable $e) {
            // DB::rollBack();
            Log::error('Inventory import error', [
                'error' => $e->getMessage(),
                'row' => $row
            ]);
            return null;
        }
    }


    protected function getProduct($itemName)
    {
        return Product::where('name', $itemName)->first();
    }

    protected function getInventory($productId)
    {
        $inventory = Inventory::where('product_id', $productId)->first();
        if (!$inventory) {
            $inventory = Inventory::create([
                'product_id' => $productId,
                'quantity' => 0,
            ]);
        }

        return $inventory;
    }

    protected function parseExpiryDate($expiryDateValue)
    {
        if (empty($expiryDateValue)) {
            return null; // Return null since the field is nullable
        }

        try {
            // Check if it's an Excel serial number (numeric)
            if (is_numeric($expiryDateValue)) {
                // Convert Excel serial number to date
                // Excel dates are days since 1900-01-01
                $excelDate = (int) $expiryDateValue;
                $unixTimestamp = ($excelDate - 25569) * 86400; // Convert to Unix timestamp
                $date = Carbon::createFromTimestamp($unixTimestamp);
                return $date->format('Y-m-d');
            }

            // Try to parse as "M-y" format (Feb-25, Jun-27) - Default format
            try {
                $date = Carbon::createFromFormat('M-y', $expiryDateValue);
                // Set to first day of the month for month-year format
                return $date->startOfMonth()->format('Y-m-d');
            } catch (\Exception $e) {
                // If M-y format fails, continue to try normal date formats
                // Don't throw exception, just continue
            }

            // Try various normal date formats
            $dateFormats = [
                'd-m-Y',    // 15-02-2025
                'Y-m-d',    // 2025-02-15
                'd/m/Y',    // 15/02/2025
                'Y/m/d',    // 2025/02/15
                'm-d-Y',    // 02-15-2025
                'Y-m-d H:i:s', // 2025-02-15 00:00:00
                'd-m-Y H:i:s', // 15-02-2025 00:00:00
            ];

            foreach ($dateFormats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $expiryDateValue);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    // Continue to next format
                    continue;
                }
            }
            return null;

        } catch (\Exception $e) {
            return null; // Return null since the field is nullable
        }
    }

    public function chunkSize(): int
    {
        return 100; // Increased chunk size for better performance
    }

    public function batchSize(): int
    {
        return 50; // Reduced batch size to avoid memory issues
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {                
                Cache::forget($this->importId);
            },
        ];
    }
}
