<?php

namespace App\Livewire\Guest;

use App\Enums\BookingStatus;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman riwayat booking milik tamu yang sedang login.
 *
 * Isolasi data memakai filter user_id (bukan global scope), karena global scope
 * ScopedToManagedHotels ditujukan untuk panel hotel. tanpaHotelIsolation()
 * dipanggil agar pemilik hotel yang juga bertindak sebagai tamu tetap melihat
 * booking pribadinya.
 */
#[Layout('layouts.public')]
class BookingHistory extends Component
{
    use WithPagination;

    /** Filter status; hanya menerima nilai status yang dikenal. */
    #[Url(as: 'status', except: '')]
    public string $status = '';

    public int $perPage = 8;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function loadMore(): void
    {
        $this->perPage += 8;
    }

    /** Jumlah booking per status untuk chip filter. */
    private function statusCounts(): array
    {
        return auth()->user()->bookings()
            ->withoutHotelIsolation()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    public function render(): View
    {
        $status = array_key_exists($this->status, BookingStatus::options()) ? $this->status : '';

        $bookings = auth()->user()->bookings()
            ->withoutHotelIsolation()
            ->with(['hotel.primaryPhoto', 'roomType', 'review'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate($this->perPage);

        return view('livewire.guest.booking-history', [
            'bookings' => $bookings,
            'statusCounts' => $this->statusCounts(),
            'totalBookings' => array_sum($this->statusCounts()),
        ]);
    }
}
