<?php

namespace App\Jobs;

use App\Models\InventoryReport;
use App\Models\InventoryReportItem;
use App\Models\Product;
use App\Models\MonthlyQuantityReceived;
use App\Models\IssueQuantityReport;
use App\Mail\MonthlyInventoryReportGenerated;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GenerateMonthlyInventoryReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $monthYear;
    
    /**
     * Create a new job instance.
     */
    public function __construct($monthYear = null)
    {
        $this->monthYear = $monthYear ?? Carbon::now()->format('Y-m');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info("Starting monthly inventory report generation for {$this->monthYear}");
            echo "Starting monthly inventory report generation for {$this->monthYear}\n";
            
            // Parse the month year
            $date = Carbon::createFromFormat('Y-m', $this->monthYear);
            $previousDate = $date->copy()->subMonth();
            
            echo "Processing date: {$date->format('Y-m')}\n";
            
            // Check if report already exists
            echo "Checking for existing report...\n";
            $existingReport = InventoryReport::where('month_year', $this->monthYear)->first();
            
            if ($existingReport) {
                Log::info("Report for {$this->monthYear} already exists. Updating...");
                echo "Report already exists. Updating...\n";
                // Delete existing items
                $existingReport->items()->delete();
                $report = $existingReport;
            } else {
                echo "Creating new report...\n";
                // Create new report
                $report = new InventoryReport();
                $report->month_year = $this->monthYear;
                $report->status = 'generated';
            }
            
            $report->generated_by = 1; // System user ID
            $report->generated_at = now();
            $report->save();
            
            echo "Report saved with ID: {$report->id}\n";
            
            // Generate report items for each product
            $itemsGenerated = $this->generateReportItems($report, $date, $previousDate);
            
            echo "Generated {$itemsGenerated} items\n";
            Log::info("Successfully generated {$itemsGenerated} items for inventory report {$this->monthYear}");
            
            // Send email notification with report details - Skip for now to test
            // Mail::to('buryar313@gmail.com')->send(new MonthlyInventoryReportGenerated($report, $itemsGenerated));
            
            echo "Email notification skipped for testing\n";
            Log::info("Monthly inventory report generation completed for {$this->monthYear}");
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            Log::error("Error generating monthly inventory report for {$this->monthYear}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            // Send error email notification - Skip for now
            // Mail::to('buryar313@gmail.com')->send(new MonthlyInventoryReportGenerated(null, 0, $e->getMessage()));
            
            throw $e; // Re-throw to mark job as failed
        }
    }
    
    /**
     * Generate report items for all products
     */
    private function generateReportItems(InventoryReport $report, Carbon $date, Carbon $previousDate)
    {
        // First check if required reports exist for this month
        echo "Checking for required reports for {$date->format('Y-m')}...\n";
        
        // Check for received quantity report
        $receivedReport = MonthlyQuantityReceived::where('month_year', $date->format('Y-m'))->first();
        if (!$receivedReport) {
            throw new \Exception("Received quantity report for {$date->format('Y-m')} not found. Please generate it first.");
        }
        
        // Check for issued quantity report  
        $issuedReport = IssueQuantityReport::where('month_year', $date->format('Y-m'))->first();
        if (!$issuedReport) {
            throw new \Exception("Issued quantity report for {$date->format('Y-m')} not found. Please generate it first.");
        }
        
        echo "Found required reports. Proceeding with inventory report generation...\n";
        
        // Get received items from the generated report
        $receivedItems = $receivedReport->items()->with('product')->get();
        echo "Found {$receivedItems->count()} received items\n";
        
        // Get issued items from the generated report
        $issuedItems = $issuedReport->items()->with('product')->get();
        echo "Found {$issuedItems->count()} issued items\n";
        
        // Get all unique products from both reports
        $allProductIds = $receivedItems->pluck('product_id')->merge($issuedItems->pluck('product_id'))->unique();
        echo "Processing {$allProductIds->count()} unique products\n";
        
        $itemsGenerated = 0;
        $processedCount = 0;
        
        foreach ($allProductIds as $productId) {
            $processedCount++;
            
            // Show progress every 10 products
            if ($processedCount % 10 == 0) {
                echo "Processed {$processedCount}/{$allProductIds->count()} products\n";
            }
            
            // Get the product
            $product = Product::find($productId);
            if (!$product) {
                continue;
            }
            
            if ($this->generateProductReportItemFromReports($report, $product, $receivedItems, $issuedItems, $previousDate)) {
                $itemsGenerated++;
            }
        }
        
        echo "Completed processing all products. Items generated: {$itemsGenerated}\n";
        
        return $itemsGenerated;
    }
    
    /**
     * Generate report item for a specific product using data from generated reports
     */
    private function generateProductReportItemFromReports(InventoryReport $report, $product, $receivedItems, $issuedItems, Carbon $previousDate)
    {
        echo "Processing product: {$product->productID} - {$product->name}\n";
        
        // Get beginning balance from previous month's report
        $beginningBalance = $this->getPreviousMonthClosingBalance($product->id, $previousDate->format('Y-m'));
        echo "  Beginning balance: {$beginningBalance}\n";
        
        // Get received quantity from the received report items
        $receivedQuantity = $receivedItems->where('product_id', $product->id)->sum('quantity');
        echo "  Received quantity: {$receivedQuantity}\n";
        
        // Get issued quantity from the issued report items
        $issuedQuantity = $issuedItems->where('product_id', $product->id)->sum('quantity');
        echo "  Issued quantity: {$issuedQuantity}\n";
        
        // Set adjustments to 0 (will be entered manually)
        $positiveAdjustment = 0;
        $negativeAdjustment = 0;
        
        // Calculate closing balance
        $closingBalance = $beginningBalance + $receivedQuantity - $issuedQuantity - $negativeAdjustment + $positiveAdjustment;
        echo "  Closing balance: {$closingBalance}\n";
        
        // Only create report item if there's any movement or balance
        if ($beginningBalance > 0 || $receivedQuantity > 0 || $issuedQuantity > 0 || $closingBalance > 0) {
            
            // Get all received and issued items for this product
            $productReceivedItems = $receivedItems->where('product_id', $product->id);
            $productIssuedItems = $issuedItems->where('product_id', $product->id);
            
            echo "  Found " . $productReceivedItems->count() . " received items and " . $productIssuedItems->count() . " issued items for this product\n";
            
            // Determine batch number priority: received items first, then issued items, then fallback
            $batchNumber = 'N/A';
            $unitCost = 0;
            
            // First, try to get batch number from received items (most reliable for cost)
            if ($productReceivedItems->count() > 0) {
                // Group received items by batch number
                $receivedBatches = $productReceivedItems->groupBy('batch_number');
                
                echo "  Received batches: " . $receivedBatches->keys()->implode(', ') . "\n";
                
                // Calculate weighted average cost from all received items
                $totalReceivedCost = $productReceivedItems->sum('total_cost');
                $totalReceivedQuantity = $productReceivedItems->sum('quantity');
                
                if ($totalReceivedQuantity > 0 && $totalReceivedCost > 0) {
                    $unitCost = $totalReceivedCost / $totalReceivedQuantity;
                    echo "  Calculated weighted average unit cost from received items: {$unitCost}\n";
                }
                
                // Use the first non-null batch number from received items
                $firstReceivedBatch = $productReceivedItems->where('batch_number', '!=', null)->first();
                if ($firstReceivedBatch && $firstReceivedBatch->batch_number) {
                    $batchNumber = $firstReceivedBatch->batch_number;
                    echo "  Using batch number from received items: {$batchNumber}\n";
                }
                
            } else if ($productIssuedItems->count() > 0) {
                // If no received items, try issued items for batch number
                echo "  No received items, checking issued items for batch number...\n";
                
                // Group issued items by batch number
                $issuedBatches = $productIssuedItems->groupBy('batch_number');
                echo "  Issued batches: " . $issuedBatches->keys()->implode(', ') . "\n";
                
                // Use the first non-null batch number from issued items
                $firstIssuedBatch = $productIssuedItems->where('batch_number', '!=', null)->first();
                if ($firstIssuedBatch && $firstIssuedBatch->batch_number) {
                    $batchNumber = $firstIssuedBatch->batch_number;
                    echo "  Using batch number from issued items: {$batchNumber}\n";
                    
                    // If issued items have cost information and we don't have unit cost yet
                    if ($unitCost == 0) {
                        $totalIssuedCost = $productIssuedItems->sum('total_cost');
                        $totalIssuedQuantity = $productIssuedItems->sum('quantity');
                        
                        if ($totalIssuedQuantity > 0 && $totalIssuedCost > 0) {
                            $unitCost = $totalIssuedCost / $totalIssuedQuantity;
                            echo "  Calculated unit cost from issued items: {$unitCost}\n";
                        }
                    }
                }
            }
            
            // If we still don't have a unit cost, try previous month's report
            if ($unitCost == 0) {
                echo "  No cost data from current month items, checking previous month...\n";
                $previousReportItem = InventoryReportItem::whereHas('report', function($q) use ($previousDate) {
                    $q->where('month_year', $previousDate->format('Y-m'));
                })->where('product_id', $product->id)->first();
                
                if ($previousReportItem && $previousReportItem->unit_cost > 0) {
                    $unitCost = $previousReportItem->unit_cost;
                    echo "  Using previous month's unit cost: {$unitCost}\n";
                    
                    // Also use previous month's batch number if we don't have one
                    if ($batchNumber == 'N/A' && $previousReportItem->batch_number) {
                        $batchNumber = $previousReportItem->batch_number;
                        echo "  Using previous month's batch number: {$batchNumber}\n";
                    }
                } else {
                    echo "  No previous month's cost data found, using 0\n";
                }
            }
            
            // Handle common batch numbers between received and issued items
            if ($productReceivedItems->count() > 0 && $productIssuedItems->count() > 0) {
                $receivedBatchNumbers = $productReceivedItems->pluck('batch_number')->filter()->unique();
                $issuedBatchNumbers = $productIssuedItems->pluck('batch_number')->filter()->unique();
                $commonBatches = $receivedBatchNumbers->intersect($issuedBatchNumbers);
                
                if ($commonBatches->count() > 0) {
                    echo "  Found common batch numbers: " . $commonBatches->implode(', ') . "\n";
                    echo "  Using cost from received items for matching batches\n";
                    
                    // Use the first common batch number
                    $batchNumber = $commonBatches->first();
                    echo "  Selected common batch number: {$batchNumber}\n";
                }
            }
            
            // Calculate total cost based on closing balance and unit cost
            $totalCost = abs($closingBalance) * $unitCost;
            echo "  Calculated total cost: {$totalCost} (closing balance: {$closingBalance} × unit cost: {$unitCost})\n";
            
                        
            // Determine expiry date priority: received items first, then issued items, then product, then default
            $expiryDate = now()->addYears(5)->format('Y-m-d'); // Default fallback
            
            if ($productReceivedItems->count() > 0) {
                $firstReceivedWithExpiry = $productReceivedItems->where('expiry_date', '!=', null)->first();
                if ($firstReceivedWithExpiry && $firstReceivedWithExpiry->expiry_date) {
                    $expiryDate = $firstReceivedWithExpiry->expiry_date;
                }
            } elseif ($productIssuedItems->count() > 0) {
                $firstIssuedWithExpiry = $productIssuedItems->where('expiry_date', '!=', null)->first();
                if ($firstIssuedWithExpiry && $firstIssuedWithExpiry->expiry_date) {
                    $expiryDate = $firstIssuedWithExpiry->expiry_date;
                }
            } elseif ($product->expiry_date) {
                $expiryDate = $product->expiry_date;
            }
            
            echo "  Final values - Batch: {$batchNumber}, Unit Cost: " . round($unitCost, 2) . ", Total Cost: " . round($totalCost, 2) . ", Expiry: {$expiryDate}\n";
            
            InventoryReportItem::create([
                'inventory_report_id' => $report->id,
                'product_id' => $product->id,
                'uom' => $product->uom ?? 'pcs',
                'batch_number' => $batchNumber,
                'expiry_date' => $expiryDate,
                'beginning_balance' => $beginningBalance,
                'received_quantity' => $receivedQuantity,
                'issued_quantity' => $issuedQuantity,
                'other_quantity_out' => 0,
                'positive_adjustment' => $positiveAdjustment,
                'negative_adjustment' => $negativeAdjustment,
                'closing_balance' => $closingBalance,
                'total_closing_balance' => $closingBalance,
                'average_monthly_consumption' => $issuedQuantity > 0 ? $issuedQuantity : 1,
                'months_of_stock' => 'N/A',
                'quantity_in_pipeline' => 0,
                'unit_cost' => round($unitCost, 2),
                'total_cost' => round($totalCost, 2),
            ]);
            
            echo "  ✓ Report item created successfully\n\n";
            return true; // Item was created
        }
        
        echo "  ✗ No report item created (no movement or balance)\n\n";
        return false; // No item created
    }
    
    /**
     * Get closing balance from previous month's report
     */
    private function getPreviousMonthClosingBalance($productId, $monthYear)
    {
        $previousReportItem = InventoryReportItem::whereHas('report', function($q) use ($monthYear) {
            $q->where('month_year', $monthYear);
        })->where('product_id', $productId)->first();
        
        return $previousReportItem ? $previousReportItem->closing_balance : 0;
    }
}
