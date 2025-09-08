<?php

namespace App\Events;

use App\Models\Transfer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransferStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transfer;
    public $oldStatus;
    public $newStatus;
    public $changedBy;

    /**
     * Create a new event instance.
     *
     * @param  Transfer  $transfer
     * @param  string  $oldStatus
     * @param  string  $newStatus
     * @param  int  $changedBy
     * @return void
     */
    public function __construct(Transfer $transfer, string $oldStatus, string $newStatus, int $changedBy)
    {
        $this->transfer = $transfer;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('transfer.'.$this->transfer->id);
    }
    
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'TransferStatusChanged';
    }
}
