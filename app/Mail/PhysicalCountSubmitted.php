<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\InventoryAdjustment;

class PhysicalCountSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $adjustment;
    public $approvalLink;
    public $submittedBy;

    /**
     * Create a new message instance.
     */
    public function __construct(InventoryAdjustment $adjustment, $approvalLink, $submittedBy)
    {
        $this->adjustment = $adjustment;
        $this->approvalLink = $approvalLink;
        $this->submittedBy = $submittedBy;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Physical Count Report Submitted - Awaiting Review',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.physical-count-submitted',
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
