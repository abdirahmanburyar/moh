<?php

namespace App\Events;

use App\Models\Inventory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $importId;
    public $progress;

    public function __construct($importId, $progress)
    {
        $this->importId = $importId;
        $this->progress = $progress;
    }

    public function broadcastOn(): array
    {
        if ($this->progress === 'completed') {
            return [
                new Channel('inventory'),
            ];
        }
        
        return [
            new PrivateChannel('import-progress.' . $this->importId),
        ];
    }

    public function broadcastAs(): string
    {
        if ($this->progress === 'completed') {
            return 'refresh';
        }
        
        return 'ImportProgressUpdated';
    }

    public function broadcastWith()
    {
        if ($this->progress === 'completed') {
            return [
                'message' => 'Inventory import completed',
                'completed' => true,
            ];
        }
        
        return [
            'progress' => $this->progress,
            'importId' => $this->importId,
        ];
    }
} 