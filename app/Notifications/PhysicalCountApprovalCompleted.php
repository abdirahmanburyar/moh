<?php

namespace App\Notifications;

use App\Models\InventoryAdjustment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PhysicalCountApprovalCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $adjustment;

    /**
     * Create a new notification instance.
     */
    public function __construct(InventoryAdjustment $adjustment)
    {
        $this->adjustment = $adjustment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Physical Count Approval Completed')
            ->line('The physical count approval process has been completed successfully.')
            ->line('Adjustment ID: ' . $this->adjustment->id)
            ->line('Month/Year: ' . $this->adjustment->month_year)
            ->line('Completed at: ' . $this->adjustment->approved_at->format('Y-m-d H:i:s'))
            ->action('View Details', url('/reports/physical-count'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Physical Count Approval Completed',
            'message' => 'The physical count approval process has been completed successfully.',
            'adjustment_id' => $this->adjustment->id,
            'month_year' => $this->adjustment->month_year,
            'completed_at' => $this->adjustment->approved_at,
            'type' => 'physical_count_approval'
        ];
    }
} 