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
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('store_location')->nullable()->after('remarks');
            $table->string('image_url')->nullable()->after('store_location');
            $table->boolean('is_packed')->default(false)->after('image_url');
            $table->dateTime('packed_at')->nullable()->after('is_packed');

            $table->index('store_location');
            $table->index('is_packed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['store_location']);
            $table->dropIndex(['is_packed']);
            $table->dropColumn(['store_location', 'image_url', 'is_packed', 'packed_at']);
        });
    }
};
