<?php

namespace App\Enums;

/** Status pembayaran booking. */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Paid => 'Lunas',
            self::Failed => 'Gagal',
            self::Refunded => 'Dikembalikan',
        };

    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Refunded => 'gray',
        };
    }
}
