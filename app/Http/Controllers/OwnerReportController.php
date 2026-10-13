<?php

namespace App\Http\Controllers;

use App\Exports\BookingsExport;
use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OwnerReportController extends Controller
{
    public function excel(): BinaryFileResponse
    {
        return Excel::download(new BookingsExport($this->hotelIds()), 'laporan-booking.xlsx');
    }

    public function pdf(): Response
    {
        $hotelIds = $this->hotelIds();
        $bookings = Booking::withoutHotelIsolation()
            ->with('hotel')
            ->whereIn('hotel_id', $hotelIds)
            ->latest('id')
            ->get();

        return Pdf::loadView('reports.bookings-pdf', [
            'bookings' => $bookings,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download('laporan-booking.pdf');
    }

    private function hotelIds(): array
    {
        $user = auth()->user();
        abort_unless($user && $user->isOwner(), 403);

        return $user->managedHotelIds() ?? [];
    }
}