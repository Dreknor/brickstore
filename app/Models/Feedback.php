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
        'date_rated',
        'from',
        'to',
        'feedback_id',
    ];

    protected function casts(): array
    {
        return [
            'date_rated' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id' ,'bricklink_order_id');
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
