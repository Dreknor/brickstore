<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            // BrickLink Order-Daten
            $table->string('bricklink_order_id')->unique();
            $table->dateTime('order_date');
            $table->string('status'); // Pending, Updated, Processing, Ready, Paid, Packed, Shipped, Received, Completed, Cancelled

            // Käufer-Informationen
            $table->string('buyer_name');
            $table->string('buyer_email')->nullable();
            $table->string('buyer_username')->nullable();

            // Versandadresse
            $table->string('shipping_name')->nullable();
            $table->string('shipping_address1')->nullable();
            $table->string('shipping_address2')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->string('shipping_country')->nullable();

            // Zahlungsinformationen
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('insurance', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('currency_code', 3)->default('EUR');

            // Versand-Informationen
            $table->string('shipping_method')->nullable();
            $table->string('tracking_number')->nullable();
            $table->dateTime('shipped_date')->nullable();

            // Zahlungs-Status
            $table->boolean('is_paid')->default(false);
            $table->dateTime('paid_date')->nullable();
            $table->string('payment_method')->nullable();

            // Bemerkungen & Notizen
            $table->text('buyer_remarks')->nullable();
            $table->text('seller_remarks')->nullable();
            $table->text('internal_notes')->nullable();

            // Sync-Status
            $table->dateTime('last_synced_at')->nullable();
            $table->json('raw_data')->nullable(); // Original BrickLink API Response

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('order_date');
            $table->index('is_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
