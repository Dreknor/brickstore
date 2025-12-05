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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');

            // BrickLink Inventory ID
            $table->bigInteger('inventory_id')->unsigned()->unique();

            // Item details
            $table->string('item_no'); // Part/Set number
            $table->string('item_type'); // PART, SET, MINIFIG, BOOK, GEAR, CATALOG, INSTRUCTION, UNSORTED_LOT, ORIGINAL_BOX
            $table->string('color_id')->nullable(); // Color ID (null for sets/books/etc)
            $table->string('color_name')->nullable(); // Color name

            // Quantity & pricing
            $table->integer('quantity')->default(0);
            $table->decimal('new_or_used', 1, 0); // N=New, U=Used
            $table->string('completeness')->nullable(); // C=Complete, B=Incomplete, S=Sealed
            $table->decimal('unit_price', 10, 2);
            $table->integer('bind_id')->nullable(); // If this item is bound to other items

            // Description & remarks
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('bulk')->nullable(); // Bulk quantity (1 means sell in multiples of 1)
            $table->boolean('is_retain')->default(false); // Retain in inventory?
            $table->boolean('is_stock_room')->default(false); // In stockroom?
            $table->string('stock_room_id')->nullable();

            // Dates
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            // Tier pricing (stored as JSON)
            $table->json('tier_prices')->nullable(); // [{quantity: 10, unit_price: 0.50}, ...]

            // Sale/discount
            $table->decimal('sale_rate', 5, 2)->nullable(); // Discount percentage
            $table->decimal('my_cost', 10, 2)->nullable(); // Cost basis
            $table->integer('tier_quantity1')->nullable();
            $table->decimal('tier_price1', 10, 2)->nullable();
            $table->integer('tier_quantity2')->nullable();
            $table->decimal('tier_price2', 10, 2)->nullable();
            $table->integer('tier_quantity3')->nullable();
            $table->decimal('tier_price3', 10, 2)->nullable();

            // My weight (custom weight for item)
            $table->decimal('my_weight', 10, 2)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('store_id');
            $table->index('item_no');
            $table->index('item_type');
            $table->index(['store_id', 'item_no', 'color_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};

