<?php

namespace App\Enums;

/** Siklus hidup pemesanan (booking). */
enum BookingStatus: string
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu Pembayaran',
            self::Confirmed => 'Terkonfirmasi',
            self::CheckedIn => 'Check-in',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Confirmed => 'info',
            self::CheckedIn => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
            self::Expired => 'gray',
        };
    }

    /**
     * Status yang kamarnya dianggap "terpakai" (menahan stok kamar).
     * Dipakai untuk perhitungan ketersediaan kamar.
     */
    public static function occupyingStatuses(): array
    {
        return [
            self::PendingPayment->value,
            self::Confirmed->value,
            self::CheckedIn->value,
        ];
    }

    /** Opsi [value => label] untuk komponen Select Filament. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }

    /** Status yang boleh dibatalkan sendiri oleh tamu. */
    public function cancellable(): bool
    {
        return in_array($this, [self::PendingPayment, self::Confirmed], true);
    }
}
