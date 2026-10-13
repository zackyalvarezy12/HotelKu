<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class BookingVoucherController extends Controller
{
    public function download(Booking $booking): Response
    {
        abort_if((int) $booking->user_id !== (int) auth()->id(), 404);
        abort_unless(in_array($booking->status, [
            BookingStatus::Confirmed,
            BookingStatus::CheckedIn,
            BookingStatus::Completed,
        ], true), 404);

        $booking->load(['hotel', 'roomType', 'latestPayment']);

        return Pdf::loadView('bookings.voucher', [
            'booking' => $booking,
            'generatedAt' => now(),
        ])->setPaper('a4')->download("voucher-{$booking->code}.pdf");
    }
}