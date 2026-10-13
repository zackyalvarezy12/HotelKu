<?php

namespace App\Enums;

/** Status verifikasi hotel. */
enum HotelStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Active => 'Aktif',
            self::Suspended => 'Ditangguhkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'success',
            self::Suspended => 'danger',
        };
    }

    /** Opsi [value => label] untuk komponen Select Filament. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
