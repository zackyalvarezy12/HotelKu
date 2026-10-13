<?php

namespace App\Filament\Owner\Widgets;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PromotionUsage;
use App\Models\RoomType;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OwnerOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $hotelIds = auth()->user()->managedHotelIds() ?? [];
        $roomCount = RoomType::query()
            ->whereIn('hotel_id', $hotelIds)
            ->where('is_active', true)
            ->sum('total_rooms');
        $occupiedRooms = Booking::withoutHotelIsolation()
            ->whereIn('hotel_id', $hotelIds)
            ->whereIn('status', BookingStatus::occupyingStatuses())
            ->whereDate('check_in', '<=', today())
            ->whereDate('check_out', '>', today())
            ->sum('rooms_count');
        $occupancy = $roomCount > 0 ? round(($occupiedRooms / $roomCount) * 100, 1) : 0;

        $todayBookings = Booking::withoutHotelIsolation()
            ->whereIn('hotel_id', $hotelIds)
            ->whereDate('created_at', today())
            ->count();
        $monthlyRevenue = Payment::query()
            ->where('status', PaymentStatus::Paid->value)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereHas('booking', fn ($query) => $query
                ->withoutHotelIsolation()
                ->whereIn('hotel_id', $hotelIds))
            ->sum('amount');
        $promotionDiscount = PromotionUsage::query()
            ->whereHas('promotion', fn ($query) => $query
                ->withoutGlobalScopes()
                ->whereIn('hotel_id', $hotelIds))
            ->whereBetween('used_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('discount_amount');

        return [
            Stat::make('Okupansi hari ini', $occupancy.'%')
                ->description($occupiedRooms.' dari '.$roomCount.' kamar aktif sedang terisi')
                ->color('success'),
            Stat::make('Pendapatan bulan ini', 'Rp'.number_format((float) $monthlyRevenue, 0, ',', '.'))
                ->description('Pembayaran berstatus lunas')
                ->color('primary'),
            Stat::make('Booking dibuat hari ini', number_format($todayBookings, 0, ',', '.'))
                ->description('Semua status booking')
                ->color('warning'),
            Stat::make('Diskon promo bulan ini', 'Rp'.number_format((float) $promotionDiscount, 0, ',', '.'))
                ->description('Total potongan dari pemakaian promo')
                ->color('info'),
        ];
    }
}