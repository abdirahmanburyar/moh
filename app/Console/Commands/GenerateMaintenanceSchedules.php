<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AssetMaintenance;
use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class GenerateMaintenanceSchedules extends Command
{
    protected $signature = 'assets:generate-maintenance-schedules';
    protected $description = 'Generate next maintenance schedules for recurring maintenance and send notifications';

    public function handle(): int
    {
        $this->info('Starting maintenance schedule generation...');
        
        $scheduled = 0;
        $notifications = 0;

        // Get all completed recurring maintenance records that need next scheduling
        $recurringMaintenance = AssetMaintenance::where('maintenance_range', '>', 0)
            ->whereNotNull('completed_date')
            ->get();

        foreach ($recurringMaintenance as $maintenance) {
            // Calculate next due date based on completed_date + maintenance_range
            $nextDueDate = Carbon::parse($maintenance->completed_date)->addMonths($maintenance->maintenance_range);
            
            // Check if next maintenance is due (within 30 days or overdue)
            if ($nextDueDate->isPast() || $nextDueDate->diffInDays(now()) <= 30) {
                // Create a new maintenance record for the next cycle
                $newMaintenance = AssetMaintenance::create([
                    'asset_id' => $maintenance->asset_id,
                    'maintenance_type' => $maintenance->maintenance_type,
                    'maintenance_range' => $maintenance->maintenance_range,
                    'created_by' => $maintenance->created_by,
                    // completed_date is null for new scheduled maintenance
                ]);

                $scheduled++;

                // Send notification to asset owner/concerned people
                $this->sendMaintenanceNotification($newMaintenance);
                $notifications++;
            }
        }

        // Send reminders for upcoming maintenance (1 month before)
        $this->sendMaintenanceReminders();

        $this->info("Scheduled {$scheduled} new maintenance records");
        $this->info("Sent {$notifications} notifications");
        
        return 0;
    }

    private function sendMaintenanceNotification($maintenance)
    {
        try {
            $asset = Asset::find($maintenance->asset_id);
            if (!$asset) return;

            // Get asset owner or concerned people (you can customize this logic)
            $recipients = $this->getMaintenanceRecipients($asset, $maintenance);
            
            foreach ($recipients as $email) {
                $assetUrl = url(route('assets.show', $asset->id));
                Mail::raw("New maintenance scheduled for asset: {$asset->asset_tag}\n\n" .
                         "Type: {$maintenance->maintenance_type}\n" .
                         "Range: Every {$maintenance->maintenance_range} month(s)\n" .
                         "Asset: {$asset->asset_tag}\n" .
                         "Asset Name: {$asset->name}\n\n" .
                         "View Asset: {$assetUrl}\n\n" .
                         "Please review and take necessary action.", function ($message) use ($email, $asset) {
                    $message->to($email)
                            ->subject("New Maintenance Scheduled: {$asset->asset_tag}");
                });
            }
        } catch (\Exception $e) {
            $this->error("Failed to send notification: " . $e->getMessage());
        }
    }

    private function sendMaintenanceReminders()
    {
        // Get maintenance records that are due soon (based on completed_date + maintenance_range)
        $maintenanceDueSoon = AssetMaintenance::where('maintenance_range', '>', 0)
            ->whereNotNull('completed_date')
            ->get()
            ->filter(function ($maintenance) {
                $nextDueDate = Carbon::parse($maintenance->completed_date)->addMonths($maintenance->maintenance_range);
                return $nextDueDate->diffInDays(now()) <= 30 && $nextDueDate->isFuture();
            });

        foreach ($maintenanceDueSoon as $maintenance) {
            $asset = Asset::find($maintenance->asset_id);
            if (!$asset) continue;

            $nextDueDate = Carbon::parse($maintenance->completed_date)->addMonths($maintenance->maintenance_range);
            $daysUntil = $nextDueDate->diffInDays(now());
            
            $recipients = $this->getMaintenanceRecipients($asset, $maintenance);
            
            foreach ($recipients as $email) {
                $this->sendReminderEmail($email, $maintenance, $asset, $daysUntil);
            }
        }
    }

    private function sendReminderEmail($email, $maintenance, $asset, $daysUntil)
    {
        try {
            $assetUrl = url(route('assets.show', $asset->id));
            Mail::raw("Maintenance Reminder for asset: {$asset->asset_tag}\n\n" .
                     "Type: {$maintenance->maintenance_type}\n" .
                     "Range: Every {$maintenance->maintenance_range} month(s)\n" .
                     "Next Due Date: " . Carbon::parse($maintenance->completed_date)->addMonths($maintenance->maintenance_range)->format('Y-m-d') . "\n" .
                     "Days Until Due: {$daysUntil}\n" .
                     "Asset: {$asset->asset_tag}\n" .
                     "Asset Name: {$asset->name}\n\n" .
                     "View Asset: {$assetUrl}\n\n" .
                     "Please ensure maintenance is completed on time.", function ($message) use ($email, $asset, $daysUntil) {
                $message->to($email)
                        ->subject("Maintenance Reminder: {$asset->asset_tag} - Due in {$daysUntil} days");
            });
        } catch (\Exception $e) {
            $this->error("Failed to send reminder to {$email}: " . $e->getMessage());
        }
    }

    private function getMaintenanceRecipients($asset, $maintenance = null)
    {
        // Customize this method to get the right people to notify
        $recipients = [];
        
        // 1. Always include the maintenance creator's email
        if ($maintenance && $maintenance->created_by) {
            $creator = \App\Models\User::find($maintenance->created_by);
            if ($creator && $creator->email) {
                $recipients[] = $creator->email;
            }
        }
        
        // 2. Include asset owner/creator email if available
        if ($asset->created_by) {
            $assetOwner = \App\Models\User::find($asset->created_by);
            if ($assetOwner && $assetOwner->email) {
                $recipients[] = $assetOwner->email;
            }
        }
        
        // 3. Add default maintenance team emails (customize as needed)
        $recipients[] = 'maintenance@example.com';
        
        return array_unique($recipients);
    }
}

