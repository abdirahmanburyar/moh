<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use App\Models\User;
use App\Notifications\AssetMaintenanceDue;
use Illuminate\Support\Facades\Notification;

class NotifyAssetMaintenanceDue extends Command
{
    protected $signature = 'assets:notify-maintenance-due';
    protected $description = 'Notify asset managers of maintenance due today';

    public function handle(): int
    {
        $assets = Asset::whereNotNull('metadata')->get();
        $dueToday = $assets->filter(function ($asset) {
            $metadata = is_array($asset->metadata) ? $asset->metadata : (json_decode($asset->metadata, true) ?: []);
            return ($metadata['maintenance_due_date'] ?? null) === now()->toDateString();
        });

        if ($dueToday->isEmpty()) {
            $this->info('No assets due for maintenance today.');
            return 0;
        }

        $managers = User::permission('asset-manage')->get();
        foreach ($dueToday as $asset) {
            Notification::send($managers, new AssetMaintenanceDue($asset));
        }

        $this->info('Maintenance notifications sent: ' . $dueToday->count());
        return 0;
    }
}

