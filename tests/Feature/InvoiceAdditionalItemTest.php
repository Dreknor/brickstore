<?php

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\InvoiceService;
use Mockery\MockInterface;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create([
        'user_id' => $this->user->id,
        'is_setup_complete' => true,
        'is_small_business' => false,
    ]);
    $this->order = Order::factory()->create([
        'store_id' => $this->store->id,
        'subtotal' => 100,
        'shipping_cost' => 5,
        'grand_total' => 105,
        'final_total' => 105,
    ]);
    $this->invoice = Invoice::factory()->create([
        'store_id' => $this->store->id,
        'order_id' => $this->order->id,
        'subtotal' => 100,
        'shipping_cost' => 5,
        'tax_rate' => 19,
        'tax_amount' => 0,
        'total' => 105,
        'is_small_business' => false,
    ]);
});

it('adds an additional invoice position and updates totals and tax', function () {
    $this->partialMock(InvoiceService::class, function (MockInterface $mock) {
        $mock->shouldReceive('updateInvoiceAndRegeneratePDF')
            ->once()
            ->andReturnUsing(fn (Invoice $invoice) => $invoice);
    });

    $this->actingAs($this->user)
        ->post(route('invoices.items.store', $this->invoice), [
            'description' => 'Verpackungsmaterial',
            'quantity' => 2,
            'unit_price' => '3.50',
        ])
        ->assertRedirect(route('invoices.show', $this->invoice))
        ->assertSessionHas('success');

    expect($this->invoice->fresh()->subtotal)->toBe('107.00')
        ->and($this->invoice->fresh()->tax_amount)->toBe('1.33')
        ->and($this->invoice->fresh()->total)->toBe('113.33');

    $this->assertDatabaseHas('invoice_items', [
        'invoice_id' => $this->invoice->id,
        'description' => 'Verpackungsmaterial',
        'quantity' => 2,
        'unit_price' => 3.50,
        'total_price' => 7.00,
    ]);
});

it('removes an additional invoice position and recalculates totals', function () {
    $item = $this->invoice->items()->create([
        'description' => 'Verpackungsmaterial',
        'quantity' => 2,
        'unit_price' => 3.50,
        'total_price' => 7.00,
    ]);
    $this->invoice->update([
        'subtotal' => 107,
        'tax_amount' => 1.33,
        'total' => 113.33,
    ]);

    $this->partialMock(InvoiceService::class, function (MockInterface $mock) {
        $mock->shouldReceive('updateInvoiceAndRegeneratePDF')
            ->once()
            ->andReturnUsing(fn (Invoice $invoice) => $invoice);
    });

    $this->actingAs($this->user)
        ->delete(route('invoices.items.destroy', [$this->invoice, $item]))
        ->assertRedirect(route('invoices.show', $this->invoice))
        ->assertSessionHas('success');

    expect($this->invoice->fresh()->subtotal)->toBe('100.00')
        ->and($this->invoice->fresh()->tax_amount)->toBe('0.00')
        ->and($this->invoice->fresh()->total)->toBe('105.00');

    $this->assertDatabaseMissing('invoice_items', ['id' => $item->id]);
});

it('renders additional invoice positions in the PDF', function () {
    $item = $this->invoice->items()->create([
        'description' => 'Verpackungsmaterial',
        'quantity' => 2,
        'unit_price' => 3.50,
        'total_price' => 7.00,
    ]);

    $html = view('invoices.pdf', [
        'invoice' => $this->invoice,
        'store' => $this->store,
        'order' => $this->order,
        'items' => $this->order->items,
        'additionalItems' => collect([$item]),
    ])->render();

    expect($html)->toContain('Verpackungsmaterial')
        ->and($html)->toContain('Zusätzliche Position');
});
