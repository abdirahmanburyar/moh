<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Asset;

class AssetWarrantyExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public $asset;

    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Asset Warranty Expiring Soon')
            ->greeting('Hello Asset Manager,')
            ->line("The warranty for asset '{$this->asset->asset_tag}' is expiring soon.")
            ->line('---')
            ->line('Asset Tag: ' . $this->asset->asset_tag)
            ->line('Name: ' . ($this->asset->name ?? 'N/A'))
            ->line('Category: ' . ($this->asset->category->name ?? 'N/A'))
            ->line('Serial Number: ' . ($this->asset->serial_number ?? 'N/A'))
            ->line('Model: ' . ($this->asset->model ?? 'N/A'))
            ->line('Location: ' . ($this->asset->location->name ?? 'N/A'))
            ->line('Status: ' . ($this->asset->status ?? 'N/A'))
            ->line('Warranty Start Date: ' . ($this->asset->asset_warranty_start ? date('F j, Y', strtotime($this->asset->asset_warranty_start)) : 'N/A'))
            ->line('Warranty End Date: ' . ($this->asset->asset_warranty_end ? date('F j, Y', strtotime($this->asset->asset_warranty_end)) : 'N/A'))
            ->line('---')
            ->action('View Asset', route('assets.show', $this->asset->id))
            ->line('This is an automated reminder. Please take necessary action.');
    }
}
