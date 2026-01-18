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
        Schema::create('brickognize_identifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');

            // Upload-Informationen
            $table->string('image_path')->nullable(); // Temporärer Upload-Pfad
            $table->string('original_filename')->nullable();

            // Erkannte Daten (Top-Ergebnis)
            $table->string('identified_item_no')->nullable();
            $table->string('identified_item_name')->nullable();
            $table->integer('identified_color_id')->nullable();
            $table->string('identified_color_name')->nullable();
            $table->string('identified_item_type')->default('PART');
            $table->decimal('confidence_score', 5, 2)->nullable(); // 0-100

            // API-Response (JSON)
            $table->json('api_response')->nullable(); // Alle Ergebnisse

            // Benutzer-Aktion
            $table->enum('action_taken', ['none', 'quick_add', 'created_new', 'viewed_only'])->default('none');
            $table->foreignId('inventory_id')->nullable()->constrained()->onDelete('set null'); // Wenn hinzugefügt

            $table->timestamps();

            // Indizes
            $table->index('user_id');
            $table->index('store_id');
            $table->index('identified_item_no');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brickognize_identifications');
    }
};

