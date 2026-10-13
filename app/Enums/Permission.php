<?php

namespace App\Enums;

/**
 * Daftar izin aplikasi (spatie/laravel-permission).
 * Dipakai oleh seeder, Policy, dan panel Filament.
 */
enum Permission: string
{
    case ManageHotels = 'hotels.manage';
    case VerifyHotels = 'hotels.verify';
    case SuspendHotels = 'hotels.suspend';

    case ManageRooms = 'rooms.manage';
    case ManageBookings = 'bookings.manage';
    case ManagePromotions = 'promotions.manage';
    case ManageChat = 'chat.manage';

    case ManageUsers = 'users.manage';
    case ViewHotelReports = 'reports.hotel';
    case ViewGlobalReports = 'reports.global';

    case CreateBooking = 'bookings.create';

    public function label(): string
    {
        return match ($this) {
            self::ManageHotels => 'Kelola hotel',
            self::VerifyHotels => 'Verifikasi hotel',
            self::SuspendHotels => 'Suspend hotel',
            self::ManageRooms => 'Kelola tipe kamar & harga',
            self::ManageBookings => 'Kelola booking',
            self::ManagePromotions => 'Kelola promosi',
            self::ManageChat => 'Balas chat tamu',
            self::ManageUsers => 'Kelola pengguna',
            self::ViewHotelReports => 'Lihat laporan hotel',
            self::ViewGlobalReports => 'Lihat laporan global',
            self::CreateBooking => 'Membuat booking',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** Peta izin per peran, dipakai saat seeding. */
    public static function forRole(UserRole $role): array
    {
        return match ($role) {
            UserRole::SuperAdmin => self::values(),
            UserRole::Owner => [
                self::ManageHotels->value,
                self::ManageRooms->value,
                self::ManageBookings->value,
                self::ManagePromotions->value,
                self::ManageChat->value,
                self::ViewHotelReports->value,
            ],
            UserRole::Staff => [
                self::ManageBookings->value,
                self::ManageChat->value,
            ],
            UserRole::Guest => [
                self::CreateBooking->value,
            ],
        };
    }
}
