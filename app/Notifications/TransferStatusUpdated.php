<?php

namespace App\Notifications;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class TransferStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $transfer;
    protected $oldStatus;
    protected $newStatus;
    protected $changedBy;

    /**
     * Create a new notification instance.
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
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $statusLabels = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'in_process' => 'In Process',
            'dispatched' => 'Dispatched',
            'received' => 'Received',
            'rejected' => 'Rejected'
        ];

        $mailMessage = (new MailMessage)
            ->subject('Transfer Status Update: ' . $this->transfer->transferID)
            ->greeting('Hello!')
            ->line('A transfer status has been updated and requires your attention.')
            ->line(new HtmlString('<strong>Transfer ID:</strong> ' . $this->transfer->transferID))
            ->line(new HtmlString('<strong>Created Date:</strong> ' . Carbon::parse($this->transfer->transfer_date)->format('d/m/Y')))
            ->line(new HtmlString('<strong>Status Change:</strong> ' . 
                ($statusLabels[$this->oldStatus] ?? ucfirst($this->oldStatus)) . ' → ' . 
                ($statusLabels[$this->newStatus] ?? ucfirst($this->newStatus))));

        // Add source information
        if ($this->transfer->fromWarehouse) {
            $mailMessage->line(new HtmlString('<strong>From Warehouse:</strong> ' . $this->transfer->fromWarehouse->name));
        } elseif ($this->transfer->fromFacility) {
            $mailMessage->line(new HtmlString('<strong>From Facility:</strong> ' . $this->transfer->fromFacility->name));
        }

        // Add destination information
        if ($this->transfer->toWarehouse) {
            $mailMessage->line(new HtmlString('<strong>To Warehouse:</strong> ' . $this->transfer->toWarehouse->name));
        } elseif ($this->transfer->toFacility) {
            $mailMessage->line(new HtmlString('<strong>To Facility:</strong> ' . $this->transfer->toFacility->name));
        }

        // Add items table if there are items
        if ($this->transfer->items && count($this->transfer->items) > 0) {
            $itemsTable = '<table style="width:100%; border-collapse: collapse; margin-top: 15px;">';
            $itemsTable .= '<tr style="background-color: #f8f9fa; text-align: left;">';
            $itemsTable .= '<th style="padding: 8px; border: 1px solid #ddd;">Item</th>';
            $itemsTable .= '<th style="padding: 8px; border: 1px solid #ddd;">Quantity</th>';
            $itemsTable .= '<th style="padding: 8px; border: 1px solid #ddd;">UOM</th>';
            $itemsTable .= '</tr>';

            foreach ($this->transfer->items as $item) {
                $itemsTable .= '<tr>';
                $itemsTable .= '<td style="padding: 8px; border: 1px solid #ddd;">' . ($item->product ? $item->product->name : 'Unknown Product') . '</td>';
                $itemsTable .= '<td style="padding: 8px; border: 1px solid #ddd;">' . $item->quantity . '</td>';
                $itemsTable .= '<td style="padding: 8px; border: 1px solid #ddd;">' . ($item->uom ?? 'N/A') . '</td>';
                $itemsTable .= '</tr>';
            }

            $itemsTable .= '</table>';
            $mailMessage->line(new HtmlString('<strong>Transfer Items:</strong>'));
            $mailMessage->line(new HtmlString($itemsTable));
        }

        // Add action button to view the transfer
        $mailMessage->action('View Transfer', url('/transfers/' . $this->transfer->id));

        // Add notes if available
        if ($this->transfer->notes) {
            $mailMessage->line(new HtmlString('<strong>Notes:</strong> ' . $this->transfer->notes));
        }

        $mailMessage->line('Thank you for using our application!');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'transfer_id' => $this->transfer->id,
            'transfer_reference' => $this->transfer->transferID,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'changed_by' => $this->changedBy
        ];
    }
}
