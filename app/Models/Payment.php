<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'method',
        'amount',
        'proof_path',
        'status',
        'paid_at',
        'gateway_ref',
        'notes',
    ];

    protected $casts = [
        'status' => PaymentStatus::class,
        'method' => PaymentMethod::class,
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_path ? asset('storage/'.$this->proof_path) : null;
    }
}
