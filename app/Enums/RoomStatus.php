<?php

namespace App\Enums;

/** Status operasional satu unit kamar. */
enum RoomStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Cleaning = 'cleaning';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Occupied => 'Terisi',
            self::Cleaning => 'Dibersihkan',
            self::Maintenance => 'Perbaikan',
        };
    }

    /** Opsi [value => label] untuk komponen Select Filament. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
