<?php

namespace App\Mail;

use App\Models\InventoryReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyInventoryReportGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public $report;
    public $itemsGenerated;
    public $errorMessage;

    /**
     * Create a new message instance.
     */
    public function __construct($report = null, $itemsGenerated = 0, $errorMessage = null)
    {
        $this->report = $report;
        $this->itemsGenerated = $itemsGenerated;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->errorMessage 
            ? 'Monthly Inventory Report Generation Failed'
            : 'Monthly Inventory Report Generated Successfully';
            
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.monthly-inventory-report-generated',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
