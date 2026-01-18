<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrickognizeIdentification extends Model
{
    protected $fillable = [
        'user_id',
        'store_id',
        'image_path',
        'original_filename',
        'identified_item_no',
        'identified_item_name',
        'identified_color_id',
        'identified_color_name',
        'identified_item_type',
        'confidence_score',
        'api_response',
        'action_taken',
        'inventory_id',
    ];

    protected $casts = [
        'api_response' => 'array',
        'confidence_score' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Beziehung zum User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Beziehung zum Store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Beziehung zum Inventory (wenn Aktion durchgeführt)
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * Scope für erfolgreiche Identifikationen
     */
    public function scopeSuccessful($query)
    {
        return $query->whereNotNull('identified_item_no');
    }

    /**
     * Scope für hohe Confidence (>= 80%)
     */
    public function scopeHighConfidence($query)
    {
        return $query->where('confidence_score', '>=', 80);
    }

    /**
     * Prüft ob die Identifikation erfolgreich war
     */
    public function isSuccessful(): bool
    {
        return !is_null($this->identified_item_no);
    }

    /**
     * Gibt die Confidence als Prozentsatz zurück
     */
    public function getConfidencePercentAttribute(): string
    {
        return number_format($this->confidence_score, 0) . '%';
    }
}

