<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuarterlyOrderGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $facility;
    public $orderSummary;

    /**
     * Create a new message instance.
     */
    public function __construct($order, $facility, $orderSummary)
    {
        $this->order = $order;
        $this->facility = $facility;
        $this->orderSummary = $orderSummary;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("Quarterly Order Generated: {$this->order->order_number}")
                    ->view('emails.quarterly-order-generated')
                    ->with([
                        'orderNumber' => $this->order->order_number,
                        'facilityName' => $this->facility->name,
                        'facilityId' => $this->facility->id,
                        'orderDate' => $this->order->order_date,
                        'expectedDate' => $this->order->expected_date,
                        'orderType' => $this->order->order_type,
                        'status' => $this->order->status,
                        'totalItems' => $this->orderSummary['total_items'],
                        'processedItems' => $this->orderSummary['processed'],
                        'skippedItems' => $this->orderSummary['skipped'],
                        'errorItems' => $this->orderSummary['errors'],
                        'orderViewUrl' => route('orders.show', $this->order->id),
                        'generatedAt' => now()->format('F j, Y \a\t g:i A')
                    ]);
    }
} 