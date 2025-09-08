<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class LogFailedQueueJob
{
    /**
     * Handle the event.
     *
     * @param  \Illuminate\Queue\Events\JobFailed  $event
     * @return void
     */
    public function handle(JobFailed $event)
    {
        $job = $event->job;
        $exception = $event->exception;
        
        Log::error('Queue job failed', [
            'job' => $job->resolveName(),
            'queue' => $job->getQueue(),
            'connection' => $job->getConnectionName(),
            'exception' => [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ],
            'payload' => $job->payload(),
        ]);
    }
}
