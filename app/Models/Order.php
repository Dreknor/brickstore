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
        'status',
        'buyer_name',
        'buyer_email',
        'buyer_username',
        'shipping_name',
        'shipping_address1',
        'shipping_address2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'subtotal',
        'grand_total',
        'shipping_cost',
        'insurance',
        'tax',
        'discount',
        'currency_code',
        'shipping_method',
        'tracking_number',
        'shipped_date',
        'is_paid',
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
            'shipped_date' => 'datetime',
            'paid_date' => 'datetime',
            'last_synced_at' => 'datetime',
            'is_paid' => 'boolean',
            'subtotal' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'insurance' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
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
