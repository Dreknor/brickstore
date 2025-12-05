<?php

namespace App\Models;

use App\Services\BrickLink\ImageCacheService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'item_type',
        'item_number',
        'item_name',
        'color_id',
        'color_name',
        'quantity',
        'unit_price',
        'total_price',
        'condition',
        'completeness',
        'description',
        'remarks',
        'store_location',
        'image_url',
        'is_packed',
        'packed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'is_packed' => 'boolean',
            'packed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the cached image URL or cache it if not already cached.
     */
    protected function cachedImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->image_url)) {
                    return null;
                }

                $cacheService = app(ImageCacheService::class);

                return $cacheService->cacheImage(
                    $this->image_url,
                    $this->item_type,
                    $this->item_number,
                    $this->color_id
                );
            }
        );
    }

    /**
     * Cache the image for this item.
     */
    public function cacheImage(): ?string
    {
        if (empty($this->image_url)) {
            return null;
        }

        $cacheService = app(ImageCacheService::class);

        return $cacheService->cacheImage(
            $this->image_url,
            $this->item_type,
            $this->item_number,
            $this->color_id
        );
    }
}
