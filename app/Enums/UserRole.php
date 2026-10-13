<?php

namespace App\Enums;

/**
 * Peran pengguna aplikasi.
 * Nilai enum ini sekaligus dipakai sebagai nama role pada spatie/laravel-permission.
 */
enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Owner = 'owner';
    case Staff = 'staff';
    case Guest = 'guest';

    /** Label Bahasa Indonesia untuk ditampilkan di antarmuka. */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Owner => 'Pemilik Hotel',
            self::Staff => 'Staf Hotel',
            self::Guest => 'Tamu',
        };
    }

    /** Semua nilai enum (dipakai untuk seeding role). */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
