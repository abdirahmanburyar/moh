<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CalculateAssetDepreciationJob;
use Illuminate\Support\Facades\Log;

class ScheduleAssetDepreciation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:schedule-depreciation 
                            {--frequency=monthly : Frequency of calculation (daily, weekly, monthly, quarterly)}
                            {--queue : Process in background queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule asset depreciation calculation based on frequency';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $frequency = $this->option('frequency');
        $useQueue = $this->option('queue');
        
        $this->info("Scheduling asset depreciation calculation with frequency: {$frequency}");
        
        try {
            if ($useQueue) {
                // Dispatch to background queue
                CalculateAssetDepreciationJob::dispatch();
                $this->info('Depreciation calculation job dispatched to background queue');
            } else {
                // Run synchronously
                $this->call('assets:calculate-depreciation');
            }
            
            Log::info("Asset depreciation scheduled", [
                'frequency' => $frequency,
                'use_queue' => $useQueue,
                'scheduled_at' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            $this->error('Error scheduling depreciation calculation: ' . $e->getMessage());
            Log::error('Error scheduling depreciation calculation: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
