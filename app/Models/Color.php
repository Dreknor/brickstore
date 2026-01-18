<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Color extends Model
{
    use HasFactory;

    protected $fillable = [
        'color_id',           // BrickLink: id
        'color_name',         // BrickLink: name
        'color_code',         // BrickLink: color (Hex-Code ohne #)
        'color_type',         // BrickLink: type (Solid, Transparent, Metallic, etc.)
    ];

    protected $casts = [
        'color_id' => 'integer',
        'color_type' => 'string',
    ];

    // Füge Boot-Methode für Logging hinzu
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            \Log::debug('Color saving', [
                'id' => $model->id,
                'color_id' => $model->color_id,
                'color_name' => $model->color_name,
                'color_code' => $model->color_code,
                'color_type' => $model->color_type,
            ]);
        });

        static::saved(function ($model) {
            \Log::debug('Color saved', [
                'id' => $model->id,
                'color_id' => $model->color_id,
                'color_name' => $model->color_name,
            ]);
        });
    }

    /**
     * Get display name for color with type
     */
    public function getDisplayName(): string
    {
        $type = $this->color_type ? " ({$this->color_type})" : '';
        return "{$this->color_name}{$type}";
    }

    /**
     * Get CSS color value for display (with # prefix)
     */
    public function getCssColor(): string
    {
        return $this->color_code ? "#{$this->color_code}" : '#CCCCCC';
    }

    /**
     * Check if color is transparent based on type
     */
    public function isTransparent(): bool
    {
        return stripos($this->color_type, 'transparent') !== false;
    }

    /**
     * Check if color is metallic based on type
     */
    public function isMetallic(): bool
    {
        return stripos($this->color_type, 'metallic') !== false;
    }

    /**
     * Scope: find by BrickLink color ID
     */
    public function scopeByColorId($query, int $colorId)
    {
        return $query->where('color_id', $colorId);
    }

    /**
     * Scope: find by color name
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('color_name', 'like', "%{$name}%");
    }
}

