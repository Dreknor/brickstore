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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // BrickLink Item-Daten
            $table->string('item_type'); // MINIFIG, PART, SET, BOOK, GEAR, CATALOG, INSTRUCTION, UNSORTED_LOT, ORIGINAL_BOX
            $table->string('item_number'); // BrickLink Item Number (z.B. "3001")
            $table->string('item_name')->nullable();
            $table->string('color_id')->nullable();
            $table->string('color_name')->nullable();

            // Mengen & Preise
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);

            // Zustand
            $table->string('condition'); // N (New), U (Used)
            $table->string('completeness')->nullable(); // C (Complete), B (Incomplete), S (Sealed)

            // Beschreibung & Bemerkungen
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('item_type');
            $table->index('item_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
