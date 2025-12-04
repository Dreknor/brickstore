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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            // Rechnungsnummer & Datum
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('service_date')->nullable(); // Leistungsdatum
            $table->date('due_date')->nullable(); // Fälligkeitsdatum

            // Kunden-Daten
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_address1')->nullable();
            $table->string('customer_address2')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_postal_code')->nullable();
            $table->string('customer_country')->nullable();

            // Beträge
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0); // 19.00 für 19%
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('EUR');

            // Status
            $table->string('status')->default('draft'); // draft, sent, paid, cancelled
            $table->boolean('is_paid')->default(false);
            $table->date('paid_date')->nullable();

            // Kleinunternehmerregelung
            $table->boolean('is_small_business')->default(false);

            // Dateien & Versand
            $table->string('pdf_path')->nullable();
            $table->boolean('sent_via_email')->default(false);
            $table->dateTime('email_sent_at')->nullable();
            $table->boolean('uploaded_to_nextcloud')->default(false);
            $table->string('nextcloud_path')->nullable();
            $table->dateTime('uploaded_at')->nullable();

            // Bemerkungen
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('invoice_number');
            $table->index('invoice_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
