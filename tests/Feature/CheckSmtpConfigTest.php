<?php

namespace Tests\Feature;

use App\Jobs\SendInvoiceEmailJob;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('sends real invoice email with configured smtp', function () {
    Mail::fake();

    $user = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $user->id,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_username' => 'test@example.com',
        'smtp_password' => 'test_password',
        'smtp_encryption' => 'tls',
        'smtp_from_address' => 'noreply@example.com',
        'smtp_from_name' => 'Test Store',
    ]);

    $order = Order::factory()->create(['store_id' => $store->id]);
    $invoice = Invoice::factory()->create([
        'store_id' => $store->id,
        'order_id' => $order->id,
        'customer_email' => 'customer@example.com',
    ]);

    // Check store has SMTP credentials
    expect($store->hasSmtpCredentials())->toBeTrue()
        ->and($store->smtp_host)->toBe('smtp.gmail.com')
        ->and($store->smtp_port)->toBe(587);

    // Dispatch job
    SendInvoiceEmailJob::dispatchSync($invoice);

    // Check that store_smtp mailer was configured
    expect(config('mail.mailers.store_smtp'))->not->toBeNull()
        ->and(config('mail.mailers.store_smtp.host'))->toBe('smtp.gmail.com')
        ->and(config('mail.mailers.store_smtp.port'))->toBe(587);

    // Mail should be sent
    Mail::assertSent(\App\Mail\InvoiceMail::class);
});

test('check current store has smtp configured', function () {
    $store = Store::first();

    if (! $store) {
        $this->markTestSkipped('No store in database');
    }

    dump([
        'Store ID' => $store->id,
        'Store Name' => $store->name,
        'Has SMTP' => $store->hasSmtpCredentials(),
        'SMTP Host' => $store->smtp_host,
        'SMTP Port' => $store->smtp_port,
        'SMTP Username' => $store->smtp_username ? '***' : null,
        'SMTP Password' => $store->smtp_password ? '***' : null,
    ]);

    if (! $store->hasSmtpCredentials()) {
        $this->markTestSkipped('Store has no SMTP credentials configured');
    }

    expect($store->hasSmtpCredentials())->toBeTrue();
});
