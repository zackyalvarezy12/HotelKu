<?php

namespace App\Livewire\Guest;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.public')]
class BookingReview extends Component
{
    #[Locked]
    public int $bookingId;

    public int $rating = 5;

    public string $comment = '';

    public function mount(int $booking): void
    {
        $model = $this->findBooking($booking);
        abort_unless($model->status === BookingStatus::Completed, 404);
        abort_if($model->review()->exists(), 404);

        $this->bookingId = $model->getKey();
    }

    public function submit()
    {
        $validated = $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'rating.between' => 'Pilih nilai ulasan antara 1 sampai 5.',
            'comment.max' => 'Ulasan maksimal 2.000 karakter.',
        ]);

        $created = DB::transaction(function () use ($validated) {
            $booking = Booking::withoutGlobalScopes()
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->findOrFail($this->bookingId);

            if ($booking->status !== BookingStatus::Completed || $booking->review()->exists()) {
                return false;
            }

            $booking->review()->create([
                'user_id' => auth()->id(),
                'hotel_id' => $booking->hotel_id,
                'rating' => $validated['rating'],
                'comment' => filled($validated['comment']) ? trim($validated['comment']) : null,
            ]);

            return true;
        });

        if (! $created) {
            $this->addError('rating', 'Ulasan untuk booking ini sudah dikirim.');

            return null;
        }

        return redirect()->route('bookings.index')->with('status', 'Terima kasih, ulasan Anda berhasil dikirim.');
    }

    private function findBooking(int $bookingId): Booking
    {
        return Booking::withoutGlobalScopes()
            ->with('hotel')
            ->where('user_id', auth()->id())
            ->findOrFail($bookingId);
    }

    public function render(): View
    {
        return view('livewire.guest.booking-review', [
            'booking' => $this->findBooking($this->bookingId),
        ]);
    }
}