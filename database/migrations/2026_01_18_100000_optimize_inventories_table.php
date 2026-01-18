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
            // Add image_url column if it doesn't exist
            if (!Schema::hasColumn('inventories', 'image_url')) {
                $table->string('image_url')->nullable()->after('color_name');
            }

            // Add price guide columns if they don't exist
            if (!Schema::hasColumn('inventories', 'avg_price')) {
                $table->decimal('avg_price', 10, 3)->nullable()->after('my_weight');
                $table->decimal('min_price', 10, 3)->nullable()->after('avg_price');
                $table->decimal('max_price', 10, 3)->nullable()->after('min_price');
                $table->integer('qty_sold')->nullable()->after('max_price');
                $table->json('price_guide_data')->nullable()->after('qty_sold');
                $table->timestamp('price_guide_fetched_at')->nullable()->after('price_guide_data');
            }

            // Add composite index for faster sync lookups
            if (!Schema::hasIndex('inventories', 'inventories_store_id_inventory_id_index')) {
                $table->index(['store_id', 'inventory_id'], 'inventories_store_id_inventory_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex('inventories_store_id_inventory_id_index');

            if (Schema::hasColumn('inventories', 'image_url')) {
                $table->dropColumn('image_url');
            }

            if (Schema::hasColumn('inventories', 'avg_price')) {
                $table->dropColumn([
                    'avg_price',
                    'min_price',
                    'max_price',
                    'qty_sold',
                    'price_guide_data',
                    'price_guide_fetched_at',
                ]);
            }
        });
    }
};
