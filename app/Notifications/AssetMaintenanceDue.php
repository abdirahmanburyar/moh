<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Asset;

class AssetMaintenanceDue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Asset $asset) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Asset Maintenance Due')
            ->line('Maintenance is due for asset: ' . ($this->asset->tag_no ?? $this->asset->asset_tag))
            ->action('View Asset', url(route('assets.show', $this->asset->id)));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'asset_id' => $this->asset->id,
            'tag_no' => $this->asset->tag_no ?? $this->asset->asset_tag,
            'message' => 'Maintenance due',
        ];
    }
}

