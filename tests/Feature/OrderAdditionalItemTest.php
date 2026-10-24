<?php

use App\Jobs\SyncInventoryItemToBrickLinkJob;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Queue;
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
        'subtotal' => 50,
        'shipping_cost' => 0,
        'grand_total' => 59.5,
        'final_total' => 59.5,
        'vat_amount' => 9.5,
        'vat_rate' => 19,
    ]);
    $this->inventory = Inventory::factory()->create([
        'store_id' => $this->store->id,
        'item_no' => '3001',
        'item_type' => 'PART',
        'description' => 'Stein 2 x 4',
        'color_name' => 'Rot',
        'new_or_used' => 'N',
        'quantity' => 5,
        'unit_price' => 2,
        'sale_rate' => null,
        'is_retain' => false,
        'is_stock_room' => false,
    ]);
});

it('adds an inventory item to an order, deducts stock, and increases the invoice total', function () {
    Queue::fake();

    $invoice = Invoice::factory()->create([
        'store_id' => $this->store->id,
        'order_id' => $this->order->id,
        'subtotal' => 50,
        'shipping_cost' => 0,
        'tax_rate' => 19,
        'tax_amount' => 9.5,
        'total' => 59.5,
        'is_small_business' => false,
        'status' => 'paid',
        'is_paid' => true,
    ]);

    $this->mock(InvoiceService::class, function (MockInterface $mock) use ($invoice) {
        $mock->shouldReceive('updateInvoiceAndRegeneratePDF')
            ->once()
            ->withArgs(fn (Invoice $updatedInvoice, array $data) => $updatedInvoice->is($invoice) && $data === [])
            ->andReturn($invoice);
    });

    $this->actingAs($this->user)
        ->from(route('orders.show', $this->order))
        ->post(route('orders.items.store', $this->order), [
            'inventory_id' => $this->inventory->id,
            'quantity' => 2,
            'unit_price' => '2.00',
        ])
        ->assertRedirect(route('orders.show', $this->order))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('order_items', [
        'order_id' => $this->order->id,
        'inventory_id' => $this->inventory->id,
        'item_number' => '3001',
        'quantity' => 2,
        'unit_price' => 2,
        'total_price' => 4,
        'is_manual' => true,
    ]);
    $this->assertDatabaseHas('inventories', [
        'id' => $this->inventory->id,
        'quantity' => 3,
    ]);
    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'subtotal' => 54,
        'tax_amount' => 10.26,
        'total' => 64.26,
        'is_paid' => true,
    ]);
    Queue::assertPushed(SyncInventoryItemToBrickLinkJob::class);
});

it('includes manually added inventory items when creating an invoice later', function () {
    $item = OrderItem::factory()->create([
        'order_id' => $this->order->id,
        'inventory_id' => $this->inventory->id,
        'is_manual' => true,
        'quantity' => 2,
        'unit_price' => 2,
        'total_price' => 4,
    ]);

    $invoice = app(InvoiceService::class)->createInvoiceFromOrder($this->order);

    expect($invoice->subtotal)->toBe('54.00')
        ->and($invoice->tax_amount)->toBe('10.26')
        ->and($invoice->total)->toBe('64.26');
});

it('rejects quantities that exceed available stock', function () {
    $this->actingAs($this->user)
        ->from(route('orders.show', $this->order))
        ->post(route('orders.items.store', $this->order), [
            'inventory_id' => $this->inventory->id,
            'quantity' => 6,
            'unit_price' => '2.00',
        ])
        ->assertSessionHasErrors('quantity');

    expect($this->inventory->fresh()->quantity)->toBe(5)
        ->and($this->order->items()->count())->toBe(0);
});

it('does not allow using inventory from another store', function () {
    $otherStore = Store::factory()->create();
    $otherInventory = Inventory::factory()->create(['store_id' => $otherStore->id]);

    $this->actingAs($this->user)
        ->from(route('orders.show', $this->order))
        ->post(route('orders.items.store', $this->order), [
            'inventory_id' => $otherInventory->id,
            'quantity' => 1,
            'unit_price' => '2.00',
        ])
        ->assertSessionHasErrors('inventory_id');

    expect($otherInventory->fresh()->quantity)->toBe($otherInventory->quantity)
        ->and($this->order->items()->count())->toBe(0);
});

it('shows the add-item form and only returns available, non-retained store inventory in search', function () {
    Inventory::factory()->create([
        'store_id' => $this->store->id,
        'item_no' => '3001-reserved',
        'quantity' => 4,
        'is_retain' => true,
    ]);

    $this->actingAs($this->user)
        ->get(route('orders.show', $this->order))
        ->assertOk()
        ->assertSee('Zusätzliche Teile hinzufügen');

    $this->getJson(route('orders.inventory-search', [
        'order' => $this->order,
        'search' => '3001',
    ]))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => $this->inventory->id]);
});

it('returns the discounted inventory price when adding an item to an order', function () {
    $this->inventory->update(['sale_rate' => 10]);

    $this->actingAs($this->user)
        ->getJson(route('orders.inventory-search', [
            'order' => $this->order,
            'search' => '3001',
        ]))
        ->assertOk()
        ->assertJsonFragment([
            'id' => $this->inventory->id,
            'unit_price' => 1.8,
        ]);
});
