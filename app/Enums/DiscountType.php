<?php

namespace App\Enums;

/** Cara menghitung potongan harga pada promosi. */
enum DiscountType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case FreeNight = 'free_night';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Persen (%)',
            self::Fixed => 'Nominal (Rp)',
            self::FreeNight => 'Gratis 1 Malam',
        };
    }

    /** Opsi [value => label] untuk komponen Select Filament. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
