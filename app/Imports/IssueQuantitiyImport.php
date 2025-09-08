<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use App\Models\Product;
use App\Models\IssueQuantityReport;
use App\Models\IssueQuantityItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IssueQuantitiyImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    protected $month_year;
    protected $user_id;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes
    
    /**
     * The chunk size for the import.
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000;
    }
    
    /**
     * The batch size for the import.
     *
     * @return int
     */
    public function batchSize(): int
    {
        return 1000;
    }

    public function __construct($month_year, $user_id)
    {
        $this->month_year = $month_year;
        $this->user_id = $user_id;
    }

    public function collection(Collection $rows)
    {
        Log::info('Starting import', ['month_year' => $this->month_year, 'user_id' => $this->user_id]);

        DB::beginTransaction();
        
        try {
            // Find or create report
            $report = IssueQuantityReport::firstOrNew(['month_year' => $this->month_year]);
            $report->fill([
                'total_quantity' => 0,
                'status' => 'processing',
                'generated_by' => $this->user_id
            ]);
            
            if ($report->exists) {
                // Delete existing items if report exists
                IssueQuantityItem::where('parent_id', $report->id)->delete();
                $report->save();
            } else {
                $report->created_by = $this->user_id;
                $report->save();
            }

            $totalQuantity = 0;
            $processedRows = 0;

            foreach ($rows as $row) {
                $description = $row['item_description'] ?? null;
                $quantity = (float)($row['quantity'] ?? 0);

                if (empty($description)) {
                    continue;
                }

                $product = Product::where('description', $description)->first();
                if (!$product) {
                    Log::warning('Product not found', ['description' => $description]);
                    continue;
                }

                IssueQuantityItem::create([
                    'parent_id' => $report->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $totalQuantity += $quantity;
                $processedRows++;

                // Log progress every 100 rows
                if ($processedRows % 100 === 0) {
                    Log::info('Import progress', [
                        'processed' => $processedRows,
                        'total' => $rows->count(),
                        'memory' => memory_get_usage(true) / 1024 / 1024 . 'MB'
                    ]);
                }
            }

            // Update report with final quantities
            $report->update([
                'total_quantity' => $totalQuantity,
                'status' => 'completed'
            ]);

            DB::commit();
            Log::info('Import completed successfully', [
                'report_id' => $report->id,
                'total_quantity' => $totalQuantity,
                'processed_rows' => $processedRows
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (isset($report)) {
                $report->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }
            
            throw $e;
        }
    }
}