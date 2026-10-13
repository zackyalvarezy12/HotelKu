<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Logika ketersediaan kamar.
 *
 * Dua booking dianggap bentrok bila rentang tanggalnya beririsan:
 *     check_in < tanggal_keluar  AND  check_out > tanggal_masuk
 *
 * Kamar terpakai = SUM(rooms_count) booking berstatus
 * pending_payment / confirmed / checked_in (lihat BookingStatus::occupyingStatuses()).
 * Sisa kamar   = total_rooms - kamar terpakai.
 */
class AvailabilityService
{
    /** Status booking yang menahan stok kamar. */
    public function occupyingStatuses(): array
    {
        return BookingStatus::occupyingStatuses();
    }

    /** Sisa kamar untuk satu tipe kamar pada rentang tanggal. */
    public function availableRooms(RoomType|int $roomType, mixed $checkIn, mixed $checkOut): int
    {
        $roomType = $roomType instanceof RoomType
            ? $roomType
            : RoomType::withoutGlobalScopes()->findOrFail($roomType);

        return max(0, $roomType->total_rooms - $this->bookedRooms($roomType->getKey(), $checkIn, $checkOut));
    }

    /** Jumlah kamar terpakai untuk satu tipe kamar pada rentang tanggal. */
    public function bookedRooms(int $roomTypeId, mixed $checkIn, mixed $checkOut): int
    {
        return (int) Booking::withoutGlobalScopes()
            ->where('room_type_id', $roomTypeId)
            ->whereIn('status', $this->occupyingStatuses())
            ->where('check_in', '<', $this->date($checkOut))
            ->where('check_out', '>', $this->date($checkIn))
            ->sum('rooms_count');
    }

    /**
     * Sisa kamar tiap tipe kamar milik sebuah hotel.
     *
     * Memakai satu query agregat untuk semua tipe kamar sekaligus (menghindari N+1).
     *
     * @return Collection<int, int> [room_type_id => sisa kamar]
     */
    public function availabilityForHotel(Hotel $hotel, mixed $checkIn, mixed $checkOut): Collection
    {
        $roomTypeIds = $hotel->roomTypes()->publiclyVisible()->pluck('id');

        if ($roomTypeIds->isEmpty()) {
            return collect();
        }

        // Satu query agregat: total kamar terpakai per tipe kamar.
        $booked = Booking::withoutGlobalScopes()
            ->selectRaw('room_type_id, SUM(rooms_count) AS booked')
            ->whereIn('room_type_id', $roomTypeIds)
            ->whereIn('status', $this->occupyingStatuses())
            ->where('check_in', '<', $this->date($checkOut))
            ->where('check_out', '>', $this->date($checkIn))
            ->groupBy('room_type_id')
            ->pluck('booked', 'room_type_id');

        return $hotel->roomTypes()->publiclyVisible()->get(['id', 'total_rooms'])
            ->mapWithKeys(fn (RoomType $type) => [
                $type->getKey() => max(0, $type->total_rooms - (int) ($booked[$type->getKey()] ?? 0)),
            ]);
    }

    /**
     * Daftar hotel yang punya minimal satu tipe kamar tersedia.
     *
     * @return Collection<int, int> [hotel_id => total kamar tersedia]
     */
    public function availableHotels(mixed $checkIn, mixed $checkOut, int $guests = 1): Collection
    {
        $statuses = $this->occupyingStatuses();

        // Subquery berkorelasi: kamar terpakai per tipe kamar pada rentang tanggal.
        // Kolom hotel_id diberi alias agar dapat dipakai langsung sebagai kunci pluck().
        $sql = 'room_types.hotel_id AS hotel_id,'
            .' SUM(GREATEST(room_types.total_rooms - COALESCE(('
            .'   SELECT SUM(b.rooms_count) FROM bookings b'
            .'   WHERE b.room_type_id = room_types.id'
            .'     AND b.status IN (?, ?, ?)'
            .'     AND b.check_in < ? AND b.check_out > ?'
            .' ), 0), 0)) AS available_rooms';

        $bindings = array_merge($statuses, [$this->date($checkOut), $this->date($checkIn)]);

        return RoomType::withoutGlobalScopes()
            ->selectRaw($sql, $bindings)
            ->where('room_types.is_active', true)
            ->whereNull('room_types.deleted_at')
            ->where('room_types.capacity', '>=', max(1, $guests))
            ->groupBy('room_types.hotel_id')
            ->havingRaw('available_rooms >= 1')
            ->pluck('available_rooms', 'hotel_id');
    }

    /** Normalisasi tanggal menjadi 'Y-m-d' agar aman dipakai di klausa SQL. */
    private function date(mixed $value): string
    {
        return Carbon::parse($value)->toDateString();
    }
}
