<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Invoice $invoice) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $store = $this->invoice->store;

        return new Envelope(
            from: new Address(
                $store->smtp_from_email ?? $store->user->email,
                $store->smtp_from_name ?? $store->company_name
            ),
            subject: 'Ihre Rechnung '.$this->invoice->invoice_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'store' => $this->invoice->store,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $invoiceService = app(InvoiceService::class);
        $pdf = $invoiceService->generatePDF($this->invoice);

        return [
            Attachment::fromData(fn () => $pdf->output(), $this->invoice->invoice_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
