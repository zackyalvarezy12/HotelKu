<?php

namespace App\Enums;

/** Tipe promosi: diskon otomatis, voucher berkode, atau promo khusus. */
enum PromotionType: string
{
    case AutoDiscount = 'auto_discount';
    case Voucher = 'voucher';
    case Special = 'special';

    public function label(): string
    {
        return match ($this) {
            self::AutoDiscount => 'Diskon Otomatis',
            self::Voucher => 'Voucher',
            self::Special => 'Promo Khusus',
        };
    }

    /** Opsi [value => label] untuk komponen Select Filament. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
