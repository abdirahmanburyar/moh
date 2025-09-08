<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\Category;
use App\Models\Dosage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use App\Models\Uom;

class PurchaseOrderItemsImport implements 
    ToModel, 
    WithHeadingRow, 
    WithChunkReading, 
    WithBatchInserts, 
    WithValidation,
    SkipsEmptyRows
{
    protected $purchaseOrderId;
    public function __construct($purchaseOrderId)
    {
        $this->purchaseOrderId = $purchaseOrderId;
    }

    public function model(array $row)
    {
       try {
            DB::beginTransaction();
            $category = Category::firstOrCreate(['name' => $row['category']]);
            $dosage = Dosage::firstOrCreate(['name' => $row['dosage_form']]);
            $uom = Uom::firstOrCreate(['name' => $row['uom']]);
            $product = Product::firstOrCreate([
                'name' => $row['item_description'],
                'category_id' => $category->id,
                'dosage_id' => $dosage->id,
            ]);
            PurchaseOrderItem::create([
                'purchase_order_id' => $this->purchaseOrderId,
                'product_id' => $product->id,
                'quantity' => $row['quantity'],
                'uom' => $uom->name,
                'unit_cost' => $row['unit_cost'],
                'total_cost' => $row['total_cost'],
                'edited_by' => auth()->user()->id,
            ]);
            DB::commit();
       } catch (\Throwable $th) {
        DB::rollBack();
        logger()->error($th->getMessage());
        throw $th;
       }
    }

    public function chunkSize(): int
    {
        return 50;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function rules(): array
    {
        return [];
    }
}
