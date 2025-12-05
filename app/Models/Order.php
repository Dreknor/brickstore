<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'bricklink_order_id',
        'order_date',
        'date_ordered',
        'date_status_changed',
        'status',
        'total_count',
        'unique_count',
        'buyer_name',
        'buyer_email',
        'buyer_username',
        'buyer_order_count',
        'shipping_name',
        'shipping_address1',
        'shipping_address2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'subtotal',
        'grand_total',
        'final_total',
        'shipping_cost',
        'insurance',
        'tax',
        'vat_collected_by_bl',
        'vat_rate',
        'vat_amount',
        'salesTax_collected_by_bl',
        'discount',
        'etc1',
        'etc2',
        'credit',
        'credit_coupon',
        'currency_code',
        'display_currency_code',
        'disp_subtotal',
        'disp_grand_total',
        'disp_final_total',
        'disp_shipping',
        'disp_insurance',
        'disp_etc1',
        'disp_etc2',
        'disp_vat',
        'shipping_method',
        'tracking_number',
        'tracking_link',
        'shipped_date',
        'is_paid',
        'is_filed',
        'drive_thru_sent',
        'paid_date',
        'payment_method',
        'buyer_remarks',
        'seller_remarks',
        'internal_notes',
        'last_synced_at',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'datetime',
            'date_ordered' => 'datetime',
            'date_status_changed' => 'datetime',
            'shipped_date' => 'datetime',
            'paid_date' => 'datetime',
            'last_synced_at' => 'datetime',
            'is_paid' => 'boolean',
            'is_filed' => 'boolean',
            'drive_thru_sent' => 'boolean',
            'vat_collected_by_bl' => 'boolean',
            'salesTax_collected_by_bl' => 'boolean',
            'total_count' => 'integer',
            'unique_count' => 'integer',
            'buyer_order_count' => 'integer',
            'subtotal' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'final_total' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'insurance' => 'decimal:2',
            'tax' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'etc1' => 'decimal:2',
            'etc2' => 'decimal:2',
            'credit' => 'decimal:2',
            'credit_coupon' => 'decimal:2',
            'disp_subtotal' => 'decimal:2',
            'disp_grand_total' => 'decimal:2',
            'disp_final_total' => 'decimal:2',
            'disp_shipping' => 'decimal:2',
            'disp_insurance' => 'decimal:2',
            'disp_etc1' => 'decimal:2',
            'disp_etc2' => 'decimal:2',
            'disp_vat' => 'decimal:2',
            'raw_data' => 'array',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function feedbackFromBuyer(): HasOne
    {
        return $this->hasOne(Feedback::class)->where('direction', 'from_buyer');
    }

    public function feedbackToBuyer(): HasOne
    {
        return $this->hasOne(Feedback::class)->where('direction', 'to_buyer');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function scopeShipped($query)
    {
        return $query->whereNotNull('shipped_date');
    }

    public function scopeUnshipped($query)
    {
        return $query->whereNull('shipped_date');
    }
}
