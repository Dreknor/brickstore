<?php

use App\Jobs\SendInvoiceEmailJob;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create([
        'user_id' => $this->user->id,
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => 587,
        'smtp_username' => 'test@example.com',
        'smtp_password' => 'password123',
        'smtp_encryption' => 'tls',
        'smtp_from_address' => 'noreply@example.com',
        'smtp_from_name' => 'Test Store',
    ]);
});

it('uses store smtp credentials when sending invoice email', function () {
    Mail::fake();

    $order = Order::factory()->create(['store_id' => $this->store->id]);
    $invoice = Invoice::factory()->create([
        'store_id' => $this->store->id,
        'order_id' => $order->id,
        'customer_email' => 'customer@example.com',
    ]);

    // Dispatch the job synchronously
    SendInvoiceEmailJob::dispatchSync($invoice);

    // Assert mail was sent
    Mail::assertSent(InvoiceMail::class);

    // Check that store_smtp mailer was configured
    expect(config('mail.mailers.store_smtp.host'))->toBe('smtp.example.com')
        ->and(config('mail.mailers.store_smtp.port'))->toBe(587)
        ->and(config('mail.mailers.store_smtp.username'))->toBe('test@example.com')
        ->and(config('mail.mailers.store_smtp.encryption'))->toBe('tls');
});

it('sends invoice email with correct from address', function () {
    Mail::fake();

    $order = Order::factory()->create(['store_id' => $this->store->id]);
    $invoice = Invoice::factory()->create([
        'store_id' => $this->store->id,
        'order_id' => $order->id,
        'customer_email' => 'customer@example.com',
    ]);

    // Send the email
    Mail::to($invoice->customer_email)->send(new InvoiceMail($invoice));

    // Assert mail was sent
    Mail::assertSent(InvoiceMail::class, function ($mail) use ($invoice) {
        $envelope = $mail->envelope();

        return $envelope->from->address === 'noreply@example.com'
            && $envelope->from->name === 'Test Store'
            && $envelope->subject === 'Ihre Rechnung '.$invoice->invoice_number;
    });
});

it('falls back to user email when store has no smtp from address', function () {
    Mail::fake();

    $storeWithoutSmtpFrom = Store::factory()->create([
        'user_id' => $this->user->id,
        'smtp_from_address' => null,
        'smtp_from_name' => null,
    ]);

    $order = Order::factory()->create(['store_id' => $storeWithoutSmtpFrom->id]);
    $invoice = Invoice::factory()->create([
        'store_id' => $storeWithoutSmtpFrom->id,
        'order_id' => $order->id,
        'customer_email' => 'customer@example.com',
    ]);

    // Send the email
    Mail::to($invoice->customer_email)->send(new InvoiceMail($invoice));

    // Assert mail was sent with fallback address
    Mail::assertSent(InvoiceMail::class, function ($mail) use ($storeWithoutSmtpFrom) {
        $envelope = $mail->envelope();

        return $envelope->from->address === $storeWithoutSmtpFrom->user->email
            && $envelope->from->name === $storeWithoutSmtpFrom->company_name;
    });
});

it('logs warning when store has no smtp credentials', function () {
    Mail::fake();
    Log::spy();

    $storeWithoutSmtp = Store::factory()->create([
        'user_id' => $this->user->id,
        'smtp_host' => null,
        'smtp_port' => null,
        'smtp_username' => null,
        'smtp_password' => null,
    ]);

    $order = Order::factory()->create(['store_id' => $storeWithoutSmtp->id]);
    $invoice = Invoice::factory()->create([
        'store_id' => $storeWithoutSmtp->id,
        'order_id' => $order->id,
    ]);

    // Dispatch the job
    SendInvoiceEmailJob::dispatch($invoice);

    // Assert warning was logged
    Log::shouldHaveReceived('warning')
        ->once()
        ->with("Store {$storeWithoutSmtp->id} has no SMTP credentials configured. Using default mail configuration.");
});

it('sends invoice via job with store smtp credentials', function () {
    Mail::fake();

    $order = Order::factory()->create(['store_id' => $this->store->id]);
    $invoice = Invoice::factory()->create([
        'store_id' => $this->store->id,
        'order_id' => $order->id,
        'customer_email' => 'customer@example.com',
    ]);

    // Dispatch the job synchronously
    SendInvoiceEmailJob::dispatchSync($invoice);

    // Assert mail was sent
    Mail::assertSent(InvoiceMail::class, function ($mail) use ($invoice) {
        $envelope = $mail->envelope();

        return $envelope->from->address === 'noreply@example.com'
            && $envelope->from->name === 'Test Store'
            && $envelope->subject === 'Ihre Rechnung '.$invoice->invoice_number;
    });

    // Verify mail was sent to correct recipient
    Mail::assertSent(InvoiceMail::class, function ($mail) use ($invoice) {
        return $mail->hasTo($invoice->customer_email);
    });
});

it('includes pdf attachment in invoice email', function () {
    Mail::fake();

    $order = Order::factory()->create(['store_id' => $this->store->id]);
    $invoice = Invoice::factory()->create([
        'store_id' => $this->store->id,
        'order_id' => $order->id,
        'customer_email' => 'customer@example.com',
    ]);

    // Send the email
    Mail::to($invoice->customer_email)->send(new InvoiceMail($invoice));

    // Assert mail has PDF attachment
    Mail::assertSent(InvoiceMail::class, function ($mail) use ($invoice) {
        $attachments = $mail->attachments();

        return count($attachments) === 1
            && str_contains($attachments[0]->as, $invoice->invoice_number.'.pdf');
    });
});

it('works with mailhog without username and password', function () {
    Mail::fake();

    $storeWithMailHog = Store::factory()->create([
        'user_id' => $this->user->id,
        'smtp_host' => '127.0.0.1',
        'smtp_port' => 1025,
        'smtp_username' => null,
        'smtp_password' => null,
        'smtp_encryption' => null,
        'smtp_from_address' => 'noreply@brickstore.local',
        'smtp_from_name' => 'Test Store',
    ]);

    // Should have credentials (host and port are enough)
    expect($storeWithMailHog->hasSmtpCredentials())->toBeTrue();

    $order = Order::factory()->create(['store_id' => $storeWithMailHog->id]);
    $invoice = Invoice::factory()->create([
        'store_id' => $storeWithMailHog->id,
        'order_id' => $order->id,
        'customer_email' => 'customer@example.com',
    ]);

    // Dispatch job
    SendInvoiceEmailJob::dispatchSync($invoice);

    // Mail should be sent
    Mail::assertSent(InvoiceMail::class);

    // Check that store_smtp mailer was configured
    expect(config('mail.mailers.store_smtp.host'))->toBe('127.0.0.1')
        ->and(config('mail.mailers.store_smtp.port'))->toBe(1025);
});
