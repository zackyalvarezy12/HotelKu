<?php

namespace App\Livewire\Guest;

use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.public')]
class BookingDetail extends Component
{
    #[Locked]
    public int $bookingId;

    public function mount(int $booking): void
    {
        $this->bookingId = $this->findBooking($booking)->getKey();
    }

    private function findBooking(int $bookingId): Booking
    {
        return Booking::withoutGlobalScopes()
            ->with(['hotel', 'roomType', 'review', 'promotion', 'latestPayment'])
            ->where('user_id', auth()->id())
            ->findOrFail($bookingId);
    }

    public function render(): View
    {
        return view('livewire.guest.booking-detail', [
            'booking' => $this->findBooking($this->bookingId),
        ]);
    }
}