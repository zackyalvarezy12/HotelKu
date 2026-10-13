<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionUsage extends Model
{
    use HasFactory;

    protected $fillable = ['promotion_id', 'booking_id', 'user_id', 'discount_amount', 'used_at'];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
