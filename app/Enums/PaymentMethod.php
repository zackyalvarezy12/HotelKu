<?php

namespace App\Enums;

/** Metode pembayaran yang didukung. */
enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Qris = 'qris';
    case Gateway = 'gateway';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Transfer Bank',
            self::Cash => 'Bayar di Tempat',
            self::Qris => 'QRIS',
            self::Gateway => 'Payment Gateway',
        };
    }

    /** Opsi [value => label] untuk komponen Select Filament. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
