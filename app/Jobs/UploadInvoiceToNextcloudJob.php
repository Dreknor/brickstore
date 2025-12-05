<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\NextcloudService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadInvoiceToNextcloudJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public Invoice $invoice) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if Nextcloud is configured
            if (! $this->invoice->store->nextcloud_url) {
                Log::info('Nextcloud upload skipped - not configured', [
                    'invoice_id' => $this->invoice->id,
                    'store_id' => $this->invoice->store_id,
                ]);

                return;
            }

            // Check if PDF exists locally
            if (! $this->invoice->pdf_path) {
                Log::warning('Invoice PDF path not set', [
                    'invoice_id' => $this->invoice->id,
                ]);

                return;
            }

            // Get local file path
            $localPath = storage_path('app/private/'.$this->invoice->pdf_path);

            // If PDF doesn't exist, regenerate it
            if (! file_exists($localPath)) {
                Log::warning('Invoice PDF file not found, regenerating', [
                    'invoice_id' => $this->invoice->id,
                    'path' => $localPath,
                ]);

                // Regenerate PDF using InvoiceService
                try {
                    $invoiceService = new \App\Services\InvoiceService();
                    $invoiceService->savePDF($this->invoice, false); // Don't upload again, avoid recursion

                    Log::info('Invoice PDF regenerated successfully', [
                        'invoice_id' => $this->invoice->id,
                        'path' => $localPath,
                    ]);
                } catch (\Exception $regenerateError) {
                    Log::error('Failed to regenerate invoice PDF', [
                        'invoice_id' => $this->invoice->id,
                        'error' => $regenerateError->getMessage(),
                    ]);
                    throw $regenerateError;
                }
            }

            // Initialize Nextcloud service
            $nextcloud = new \App\Services\NextcloudService($this->invoice->store);

            // Generate remote path with placeholders
            $remotePath = $nextcloud->generateUploadPath(
                $this->invoice->store->nextcloud_invoice_path ?? '/Rechnungen'
            );

            // Upload file
            $nextcloudPath = $nextcloud->uploadInvoicePDF($localPath, $remotePath);

            // Update invoice record
            $this->invoice->update([
                'uploaded_to_nextcloud' => true,
                'nextcloud_path' => $nextcloudPath,
                'uploaded_at' => now(),
            ]);

            Log::info('Invoice successfully uploaded to Nextcloud', [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'nextcloud_path' => $nextcloudPath,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload invoice to Nextcloud', [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // Re-throw to queue system for retry
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Exception $exception): void
    {
        Log::error('Invoice Nextcloud upload permanently failed after retries', [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'error' => $exception->getMessage(),
        ]);

        // Optionally: Send notification to admin or store owner
        // Notification::send($this->invoice->store->user, new InvoiceUploadFailedNotification($this->invoice));
    }
}

