<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'order_id',
        'invoice_number',
        'invoice_date',
        'service_date',
        'due_date',
        'customer_name',
        'customer_email',
        'customer_address1',
        'customer_address2',
        'customer_city',
        'customer_postal_code',
        'customer_country',
        'subtotal',
        'shipping_cost',
        'tax_rate',
        'tax_amount',
        'total',
        'currency',
        'status',
        'is_paid',
        'paid_date',
        'is_small_business',
        'pdf_path',
        'sent_via_email',
        'email_sent_at',
        'uploaded_to_nextcloud',
        'nextcloud_path',
        'uploaded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'service_date' => 'date',
            'due_date' => 'date',
            'paid_date' => 'date',
            'email_sent_at' => 'datetime',
            'uploaded_at' => 'datetime',
            'is_paid' => 'boolean',
            'is_small_business' => 'boolean',
            'sent_via_email' => 'boolean',
            'uploaded_to_nextcloud' => 'boolean',
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }
}
