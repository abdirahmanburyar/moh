<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\IssueQuantitiyImport;

class ProcessIssueQuantityImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $monthYear;
    protected $userId;

    public function __construct($filePath, $monthYear, $userId)
    {
        $this->filePath = $filePath;
        $this->monthYear = $monthYear;
        $this->userId = $userId;
    }

    public function handle()
    {
        // Convert relative path to absolute path
        $filePath = $this->filePath;
        if (!str_starts_with($filePath, '/')) {
            $filePath = storage_path('app/' . ltrim($filePath, '/'));
        }

        if (!file_exists($filePath)) {
            throw new \Exception("File not found at path: " . $filePath);
        }

        try {
            Excel::import(
                new IssueQuantitiyImport($this->monthYear, $this->userId),
                $filePath
            );
            
            // Delete the file after successful import
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        } catch (\Exception $e) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Import job failed', [
            'error' => $exception->getMessage(),
            'file' => $this->filePath
        ]);
        
        // You might want to notify the user here
    }
}