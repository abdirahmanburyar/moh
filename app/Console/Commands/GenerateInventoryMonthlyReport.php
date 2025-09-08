<?php

namespace App\Console\Commands;

use App\Jobs\GenerateMonthlyInventoryReportJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateInventoryMonthlyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:generate-monthly-report {--month= : The month in YYYY-MM format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly inventory reports using InventoryReport and InventoryReportItem models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Default to previous month since inventory reports are generated on the first day for the previous month
        $monthYear = $this->option('month') ?? Carbon::now()->subMonth()->format('Y-m');
        
        $this->info("Generating monthly inventory report for {$monthYear}");
        
        try {
            // Execute the job directly (synchronously) to avoid queue serialization issues
            $job = new GenerateMonthlyInventoryReportJob($monthYear);
            $job->handle();
            
            $this->info("Monthly inventory report generated successfully for {$monthYear}");
            $this->info("An email notification has been sent to buryar313@gmail.com");
            
            Log::info("Monthly inventory report generated successfully for {$monthYear}");
            
            return 0;
        } catch (\Exception $e) {
            Log::error('Error generating inventory monthly report: ' . $e->getMessage());
            $this->error("Error generating report: {$e->getMessage()}");
            return 1;
        }
    }
}
