<?php

namespace App\Exports;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BookingsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly array $hotelIds) {}

    public function query(): Builder
    {
        return Booking::withoutHotelIsolation()
            ->with(['hotel', 'user'])
            ->whereIn('hotel_id', $this->hotelIds)
            ->orderBy('id');
    }

    public function headings(): array
    {
        return ['Kode Booking', 'Hotel', 'Tamu', 'Check-in', 'Check-out', 'Status', 'Subtotal', 'Diskon', 'Total'];
    }

    public function map($booking): array
    {
        return [
            $booking->code,
            $booking->hotel?->name,
            $booking->guest_name,
            $booking->check_in->format('d/m/Y'),
            $booking->check_out->format('d/m/Y'),
            $booking->status->label(),
            (float) $booking->subtotal,
            (float) $booking->discount_amount,
            (float) $booking->total_price,
        ];
    }
}