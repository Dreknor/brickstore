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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Store Grunddaten
            $table->string('name');
            $table->string('bricklink_store_name')->unique();
            $table->boolean('is_active')->default(true);

            // BrickLink API Credentials (verschlüsselt)
            $table->text('bl_consumer_key')->nullable();
            $table->text('bl_consumer_secret')->nullable();
            $table->text('bl_token')->nullable();
            $table->text('bl_token_secret')->nullable();

            // SMTP Einstellungen (verschlüsselt)
            $table->string('smtp_host')->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('smtp_encryption')->nullable(); // tls, ssl
            $table->string('smtp_from_address')->nullable();
            $table->string('smtp_from_name')->nullable();

            // Nextcloud WebDAV
            $table->string('nextcloud_url')->nullable();
            $table->string('nextcloud_username')->nullable();
            $table->text('nextcloud_password')->nullable();
            $table->string('nextcloud_invoice_path')->default('/Rechnungen/{year}/{month}');

            // Rechnungseinstellungen
            $table->string('invoice_number_format')->default('RE-{year}-{number}');
            $table->integer('invoice_number_counter')->default(0);
            $table->boolean('is_small_business')->default(false); // Kleinunternehmerregelung

            // Store-Adresse für Rechnungen
            $table->string('company_name')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Deutschland');
            $table->string('tax_number')->nullable(); // Steuernummer
            $table->string('vat_id')->nullable(); // USt-IdNr.
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Bank-Daten für Rechnungen
            $table->string('bank_name')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->string('iban')->nullable();
            $table->string('bic')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
