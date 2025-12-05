<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'inventory_id',
        'item_no',
        'item_type',
        'color_id',
        'color_name',
        'quantity',
        'new_or_used',
        'completeness',
        'unit_price',
        'bind_id',
        'description',
        'remarks',
        'bulk',
        'is_retain',
        'is_stock_room',
        'stock_room_id',
        'date_created',
        'date_updated',
        'tier_prices',
        'sale_rate',
        'my_cost',
        'tier_quantity1',
        'tier_price1',
        'tier_quantity2',
        'tier_price2',
        'tier_quantity3',
        'tier_price3',
        'my_weight',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'is_retain' => 'boolean',
        'is_stock_room' => 'boolean',
        'date_created' => 'datetime',
        'date_updated' => 'datetime',
        'tier_prices' => 'array',
        'sale_rate' => 'decimal:2',
        'my_cost' => 'decimal:2',
        'tier_price1' => 'decimal:2',
        'tier_price2' => 'decimal:2',
        'tier_price3' => 'decimal:2',
        'my_weight' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function getConditionAttribute(): string
    {
        return $this->new_or_used === 'N' ? 'New' : 'Used';
    }

    public function getCompletenessTextAttribute(): ?string
    {
        return match($this->completeness) {
            'C' => 'Complete',
            'B' => 'Incomplete',
            'S' => 'Sealed',
            default => null,
        };
    }

    public function getItemTypeDisplayAttribute(): string
    {
        return match($this->item_type) {
            'PART' => 'Part',
            'SET' => 'Set',
            'MINIFIG' => 'Minifigure',
            'BOOK' => 'Book',
            'GEAR' => 'Gear',
            'CATALOG' => 'Catalog',
            'INSTRUCTION' => 'Instruction',
            'UNSORTED_LOT' => 'Unsorted Lot',
            'ORIGINAL_BOX' => 'Original Box',
            default => ucfirst(strtolower($this->item_type)),
        };
    }

    public function hasTierPricing(): bool
    {
        return !empty($this->tier_prices) || ($this->tier_quantity1 && $this->tier_price1);
    }

    public function getTierPricingAttribute(): array
    {
        if (!empty($this->tier_prices)) {
            return $this->tier_prices;
        }

        $tiers = [];
        if ($this->tier_quantity1 && $this->tier_price1) {
            $tiers[] = ['quantity' => $this->tier_quantity1, 'unit_price' => $this->tier_price1];
        }
        if ($this->tier_quantity2 && $this->tier_price2) {
            $tiers[] = ['quantity' => $this->tier_quantity2, 'unit_price' => $this->tier_price2];
        }
        if ($this->tier_quantity3 && $this->tier_price3) {
            $tiers[] = ['quantity' => $this->tier_quantity3, 'unit_price' => $this->tier_price3];
        }

        return $tiers;
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('item_type', strtoupper($type));
    }

    public function scopeCondition($query, string $condition)
    {
        $condition = strtoupper($condition) === 'NEW' ? 'N' : 'U';
        return $query->where('new_or_used', $condition);
    }

    public function scopeInStockRoom($query)
    {
        return $query->where('is_stock_room', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_stock_room', false);
    }
}
