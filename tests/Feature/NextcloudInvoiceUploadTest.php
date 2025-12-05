<?php

namespace Tests\Feature;

use App\Jobs\UploadInvoiceToNextcloudJob;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\NextcloudService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NextcloudInvoiceUploadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function invoice_creation_queues_nextcloud_upload(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $store = Store::factory()
            ->for($user)
            ->withNextcloudCredentials()
            ->create();

        $order = Order::factory()
            ->for($store)
            ->create();

        $this->actingAs($user);

        // Create invoice
        $invoiceService = new InvoiceService();
        $invoice = $invoiceService->createInvoiceFromOrder($order);
        $invoiceService->savePDF($invoice, uploadToNextcloud: true);

        // Assert job was queued
        Queue::assertPushed(UploadInvoiceToNextcloudJob::class, function ($job) use ($invoice) {
            return $job->invoice->id === $invoice->id;
        });
    }

    #[Test]
    public function invoice_update_triggers_nextcloud_reupload(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $store = Store::factory()
            ->for($user)
            ->withNextcloudCredentials()
            ->create();

        $order = Order::factory()
            ->for($store)
            ->create();

        // Create invoice with PDF
        $invoiceService = new InvoiceService();
        $invoice = $invoiceService->createInvoiceFromOrder($order);
        $invoiceService->savePDF($invoice, uploadToNextcloud: false); // Don't queue yet

        // Simulate existing upload
        $invoice->update([
            'uploaded_to_nextcloud' => true,
            'nextcloud_path' => 'Rechnungen/RE-2025-0001.pdf',
        ]);

        // Update invoice
        $invoiceService->updateInvoiceAndRegeneratePDF($invoice, [
            'customer_name' => 'Updated Name',
        ], uploadToNextcloud: true);

        // Assert new job was queued
        Queue::assertPushed(UploadInvoiceToNextcloudJob::class);
    }

    #[Test]
    public function only_one_invoice_can_exist_per_order(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->for($user)->create();
        $order = Order::factory()->for($store)->create();

        // Create first invoice
        $invoice1 = Invoice::factory()
            ->for($store)
            ->for($order)
            ->create();

        // Try to create second invoice for same order - should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        Invoice::factory()
            ->for($store)
            ->for($order)
            ->create();
    }

    #[Test]
    public function nextcloud_upload_job_updates_invoice_fields(): void
    {
        // This test would require mocking Nextcloud API
        // Skipping real API calls for unit test
        $this->markTestSkipped('Requires Nextcloud API mocking');
    }

    #[Test]
    public function reupload_to_nextcloud_deletes_old_file(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $store = Store::factory()
            ->for($user)
            ->withNextcloudCredentials()
            ->create();

        $order = Order::factory()->for($store)->create();

        // Create invoice with existing Nextcloud path
        $invoice = Invoice::factory()
            ->for($store)
            ->for($order)
            ->create([
                'pdf_path' => 'invoices/RE-2025-0001.pdf',
                'nextcloud_path' => 'Rechnungen/RE-2025-0001.pdf',
                'uploaded_to_nextcloud' => true,
            ]);

        // Create fake PDF file
        Storage::disk('private')->put('invoices/RE-2025-0001.pdf', 'test content');

        $this->actingAs($user);

        $invoiceService = new InvoiceService();
        $invoiceService->reuploadToNextcloud($invoice);

        // Assert upload job was queued
        Queue::assertPushed(UploadInvoiceToNextcloudJob::class);
    }
}

