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
        Schema::table('orders', function (Blueprint $table) {
            // Timestamps from BrickLink API
            $table->dateTime('date_status_changed')->nullable()->after('order_date');

            // Order Counts & Statistics
            $table->integer('total_count')->default(0)->after('status'); // Total number of items
            $table->integer('unique_count')->default(0)->after('total_count'); // Number of unique items
            $table->integer('buyer_order_count')->default(0)->after('buyer_username'); // Buyer's total order count with this store

            // VAT & Tax Information
            $table->boolean('vat_collected_by_bl')->default(false)->after('tax');
            $table->decimal('vat_rate', 5, 2)->nullable()->after('vat_collected_by_bl');
            $table->decimal('vat_amount', 10, 2)->default(0)->after('vat_rate');

            // Additional Cost Fields
            $table->decimal('etc1', 10, 2)->default(0)->after('discount'); // Additional cost 1 (credit)
            $table->decimal('etc2', 10, 2)->default(0)->after('etc1'); // Additional cost 2
            $table->decimal('credit', 10, 2)->default(0)->after('etc2'); // Store credit applied
            $table->decimal('credit_coupon', 10, 2)->default(0)->after('credit'); // Coupon credit
            $table->decimal('final_total', 10, 2)->default(0)->after('grand_total'); // After all credits/coupons

            // Display Costs (for different currency display)
            $table->string('display_currency_code', 3)->nullable()->after('currency_code');
            $table->decimal('disp_subtotal', 10, 2)->nullable()->after('display_currency_code');
            $table->decimal('disp_grand_total', 10, 2)->nullable()->after('disp_subtotal');
            $table->decimal('disp_final_total', 10, 2)->nullable()->after('disp_grand_total');
            $table->decimal('disp_shipping', 10, 2)->nullable()->after('disp_final_total');
            $table->decimal('disp_insurance', 10, 2)->nullable()->after('disp_shipping');
            $table->decimal('disp_etc1', 10, 2)->nullable()->after('disp_insurance');
            $table->decimal('disp_etc2', 10, 2)->nullable()->after('disp_etc1');
            $table->decimal('disp_vat', 10, 2)->nullable()->after('disp_etc2');

            // Order Flags
            $table->boolean('is_filed')->default(false)->after('is_paid'); // Order is filed/archived
            $table->boolean('drive_thru_sent')->default(false)->after('is_filed'); // Drive-thru order sent
            $table->boolean('salesTax_collected_by_bl')->default(false)->after('vat_collected_by_bl'); // US Sales tax

            // Shipping Details
            $table->string('tracking_link')->nullable()->after('tracking_number');

            // Timestamps
            $table->dateTime('date_ordered')->nullable()->after('order_date'); // Original order timestamp from BL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'date_status_changed',
                'total_count',
                'unique_count',
                'buyer_order_count',
                'vat_collected_by_bl',
                'vat_rate',
                'vat_amount',
                'etc1',
                'etc2',
                'credit',
                'credit_coupon',
                'final_total',
                'display_currency_code',
                'disp_subtotal',
                'disp_grand_total',
                'disp_final_total',
                'disp_shipping',
                'disp_insurance',
                'disp_etc1',
                'disp_etc2',
                'disp_vat',
                'is_filed',
                'drive_thru_sent',
                'salesTax_collected_by_bl',
                'tracking_link',
                'date_ordered',
            ]);
        });
    }
};
