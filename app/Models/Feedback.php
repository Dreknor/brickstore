<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'order_id',
        'direction',
        'rating',
        'comment',
        'rating_of_bs',
        'rating_of_td',
        'rating_of_comm',
        'rating_of_ship',
        'rating_of_pack',
        'can_edit',
        'can_reply',
        'feedback_date',
    ];

    protected function casts(): array
    {
        return [
            'can_edit' => 'boolean',
            'can_reply' => 'boolean',
            'feedback_date' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isFromBuyer(): bool
    {
        return $this->direction === 'from_buyer';
    }

    public function isToBuyer(): bool
    {
        return $this->direction === 'to_buyer';
    }

    public function getRatingLabel(): string
    {
        return match ($this->rating) {
            0 => 'Praise',
            1 => 'Neutral',
            2 => 'Complaint',
            default => 'Unbekannt',
        };
    }

    public function getRatingColor(): string
    {
        return match ($this->rating) {
            0 => 'green',
            1 => 'yellow',
            2 => 'red',
            default => 'gray',
        };
    }
}
