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
        Schema::table('inventories', function (Blueprint $table) {
            // Price guide columns
            $table->decimal('avg_price', 10, 2)->nullable()->after('unit_price');
            $table->decimal('min_price', 10, 2)->nullable()->after('avg_price');
            $table->decimal('max_price', 10, 2)->nullable()->after('min_price');
            $table->integer('qty_sold')->nullable()->after('max_price');
            $table->json('price_guide_data')->nullable()->after('qty_sold');
            $table->timestamp('price_guide_fetched_at')->nullable()->after('price_guide_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['avg_price', 'min_price', 'max_price', 'qty_sold', 'price_guide_data', 'price_guide_fetched_at']);
        });
    }
};

