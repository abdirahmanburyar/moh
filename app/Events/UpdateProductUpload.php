<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UpdateProductUpload implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $importId;
    public $progress;

    public function __construct($importId, $progress)
    {
        $this->importId = $importId;
        $this->progress = $progress;
    }

    public function broadcastOn()
    {
        return new Channel('import-progress.' . $this->importId);
    }

    public function broadcastAs()
    {
        return 'ImportProgressUpdated';
    }
}
