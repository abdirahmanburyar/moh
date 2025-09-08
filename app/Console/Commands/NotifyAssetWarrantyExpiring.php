<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use App\Models\User;
use App\Notifications\AssetWarrantyExpiring;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class NotifyAssetWarrantyExpiring extends Command
{
    protected $signature = 'assets:notify-warranty-expiring';
    protected $description = 'Notify asset managers when asset warranty is expiring in one month (every 3 days)';

    public function handle()
    {
        $today = Carbon::today();
        $assets = Asset::whereNotNull('asset_warranty_end')
            // ->whereIn('status', ['active', 'in_use'])
            ->whereDate('asset_warranty_end', '>', $today)
            ->whereDate('asset_warranty_end', '<=', $today->copy()->addDays(30))
            ->get();

        $notifyDays = [30, 27, 24, 20, 15, 10, 5, 2, 1];
        $managers = User::permission('asset.audit')->get();
        if ($managers->isEmpty()) {
            $this->info('No asset managers found.');
            return 0;
        }

        foreach ($assets as $asset) {
            $end = Carbon::parse($asset->asset_warranty_end);
            $daysLeft = $today->diffInDays($end, false);
            if (in_array($daysLeft, $notifyDays)) {
                Notification::send($managers, new AssetWarrantyExpiring($asset));
            }
        }

        return 0;
    }
}
