<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearFailedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:clear-failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all failed jobs from the queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing failed jobs...');
        
        try {
            $deleted = DB::table('failed_jobs')->delete();
            $this->info("Successfully cleared {$deleted} failed jobs.");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to clear failed jobs: " . $e->getMessage());
            return 1;
        }
    }
} 