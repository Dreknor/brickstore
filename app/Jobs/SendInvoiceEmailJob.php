<?php

namespace App\Jobs;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(public Invoice $invoice) {}

    /**
     * Execute the job.
     */
    public function handle(InvoiceService $invoiceService): void
    {
        $store = $this->invoice->store;

        // Ensure PDF exists
        if (! $this->invoice->pdf_path) {
            $invoiceService->savePDF($this->invoice);
        }

        // Configure dynamic SMTP if store has credentials
        if ($store->hasSmtpCredentials()) {
            Config::set('mail.mailers.smtp', [
                'transport' => 'smtp',
                'host' => $store->smtp_host,
                'port' => $store->smtp_port,
                'encryption' => $store->smtp_encryption,
                'username' => $store->smtp_username,
                'password' => $store->smtp_password,
            ]);
        }

        try {
            Mail::to($this->invoice->customer_email)
                ->send(new InvoiceMail($this->invoice));

            // Mark as sent
            $invoiceService->markAsSent($this->invoice);

            Log::info("Invoice {$this->invoice->invoice_number} sent to {$this->invoice->customer_email}");
        } catch (\Exception $e) {
            Log::error("Failed to send invoice {$this->invoice->invoice_number}: {$e->getMessage()}");
            throw $e;
        }
    }
}
