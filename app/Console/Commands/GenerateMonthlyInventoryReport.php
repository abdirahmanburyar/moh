<?php

namespace App\Console\Commands;

use App\Models\MonthlyInventoryReport;
use App\Models\Product;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyInventoryReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:generate-inventory {month_year? : The month and year in YYYY-MM format} {--warehouse_id= : Generate report for a specific warehouse}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly inventory reports for all products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the month-year from argument or use previous month if not provided
        $monthYear = $this->argument('month_year') ?? date('Y-m');
        $warehouseId = $this->option('warehouse_id');
        
        $this->info("Generating inventory report for {$monthYear}" . ($warehouseId ? " for warehouse ID {$warehouseId}" : " for all warehouses"));
        
        // Log to a file for debugging
        $logFile = storage_path('logs/monthly-inventory-report.log');
        file_put_contents($logFile, "\n\n[" . date('Y-m-d H:i:s') . "] Starting report generation for {$monthYear}\n", FILE_APPEND);
        
        // Calculate previous month
        $prevMonthYear = date('Y-m', strtotime($monthYear . '-01 -1 month'));
        file_put_contents($logFile, "Previous month: {$prevMonthYear}\n", FILE_APPEND);
        
        // Calculate start and end dates for the month
        $startDate = $monthYear . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // Calculate inventory data directly without looping through warehouses and products
        $reportData = $this->calculateInventoryData($monthYear, $prevMonthYear, $startDate, $endDate);
        file_put_contents($logFile, "Calculated report data: " . json_encode($reportData) . "\n", FILE_APPEND);
        
        // Store the report
        try {
            // Check if a report for this month already exists
            $existingReport = MonthlyInventoryReport::where('month_year', $monthYear)->first();
            
            if ($existingReport) {
                // Update existing report
                $existingReport->beginning_balance = $reportData['beginning_balance'];
                $existingReport->stock_received = $reportData['stock_received'];
                $existingReport->stock_issued = $reportData['stock_issued'];
                $existingReport->negative_adjustment = $reportData['negative_adjustment'];
                $existingReport->positive_adjustment = $reportData['positive_adjustment'];
                $existingReport->closing_balance = $reportData['closing_balance'];
                $existingReport->generated_at = now();
                $existingReport->save();
                
                $this->info("Updated existing report for {$monthYear}");
                file_put_contents($logFile, "Updated existing report for {$monthYear}\n", FILE_APPEND);
            } else {
                // Create new report
                $report = new MonthlyInventoryReport();
                $report->month_year = $monthYear;
                $report->beginning_balance = $reportData['beginning_balance'];
                $report->stock_received = $reportData['stock_received'];
                $report->stock_issued = $reportData['stock_issued'];
                $report->negative_adjustment = $reportData['negative_adjustment'];
                $report->positive_adjustment = $reportData['positive_adjustment'];
                $report->closing_balance = $reportData['closing_balance'];
                $report->generated_at = now();
                $report->save();
                
                $this->info("Created new report for {$monthYear}");
                file_put_contents($logFile, "Created new report for {$monthYear}\n", FILE_APPEND);
            }
            
            file_put_contents($logFile, "Report data: " . json_encode($reportData) . "\n", FILE_APPEND);
            $this->info("Successfully generated inventory report for {$monthYear}");
            
        } catch (\Exception $e) {
            file_put_contents($logFile, "Error saving report: {$e->getMessage()}\n", FILE_APPEND);
            file_put_contents($logFile, "Report data: " . json_encode($reportData) . "\n", FILE_APPEND);
            $this->error("Error saving report: {$e->getMessage()}");
        }
        
        // Display generated report
        $report = MonthlyInventoryReport::where('month_year', $monthYear)->first();
            
        if ($report) {
            $this->info('Generated report:');
            $this->info("Report for month {$report->month_year}: Beginning: {$report->beginning_balance}, Received: {$report->stock_received}, Issued: {$report->stock_issued}, Closing: {$report->closing_balance}");
        } else {
            $this->info('No report was generated.');
        }
        
        return 0;
    }

    /**
     * Calculate inventory data for all products and warehouses combined
     * 
     * @param string $monthYear Current month in YYYY-MM format
     * @param string $prevMonthYear Previous month in YYYY-MM format
     * @param string $startDate Start date of the month
     * @param string $endDate End date of the month
     * @return array
     */
    private function calculateInventoryData($monthYear, $prevMonthYear, $startDate, $endDate)
    {
        // Get previous month's report for beginning balance
        $previousReport = MonthlyInventoryReport::where('month_year', $prevMonthYear)->first();
            
        $beginningBalance = $previousReport ? $previousReport->closing_balance : 0;
        
        // If no previous report, calculate beginning balance from all inventories
        if (!$previousReport) {
            $beginningBalance = DB::table('inventories')->sum('quantity');
        }

        // Get stock received this month from monthly_quantity_receiveds
        $stockReceived = DB::table('monthly_quantity_receiveds')
            ->where('month_year', $monthYear)
            ->sum('total_quantity');
        
        $this->info("Stock received for {$monthYear}: {$stockReceived}");
        
        // Get stock issued this month from issue_quantity_reports
        $stockIssued = DB::table('issue_quantity_reports')
            ->where('month_year', $monthYear)
            ->sum('total_quantity');
            
        $this->info("Stock issued for {$monthYear}: {$stockIssued}");
        
        // Get negative adjustments this month
        $negativeAdjustment = DB::table('inventory_adjustments')
            ->where('difference', '<', 0)
            ->where('status', 'approved')
            ->whereBetween('adjustment_date', [$startDate, $endDate])
            ->sum(DB::raw('ABS(difference)'));
        
        // Get positive adjustments this month
        $positiveAdjustment = DB::table('inventory_adjustments')
            ->where('difference', '>', 0)
            ->where('status', 'approved')
            ->whereBetween('adjustment_date', [$startDate, $endDate])
            ->sum('difference');
        
        // Calculate closing balance using the formula
        $closingBalance = $beginningBalance + $stockReceived - $stockIssued - $negativeAdjustment + $positiveAdjustment;
        
        return [
            'beginning_balance' => $beginningBalance,
            'stock_received' => $stockReceived,
            'stock_issued' => $stockIssued,
            'negative_adjustment' => $negativeAdjustment,
            'positive_adjustment' => $positiveAdjustment,
            'closing_balance' => $closingBalance
        ];
    }
}
