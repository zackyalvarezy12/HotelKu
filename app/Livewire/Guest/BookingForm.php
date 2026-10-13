<?php

namespace App\Livewire\Guest;

use App\Exceptions\InvalidBookingException;
use App\Exceptions\InvalidVoucherException;
use App\Exceptions\RoomNotAvailableException;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use App\Services\PromotionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Halaman pemesanan kamar (langkah akhir alur booking tamu).
 *
 * Tanggal, jumlah tamu, dan tipe kamar tersinkron ke URL sehingga tautan dari
 * halaman detail hotel langsung membawa konteks pencariannya.
 *
 * Seluruh nilai uang dihitung di server (PricingService/BookingService); angka
 * apa pun yang dikirim dari peramban tidak dipercaya.
 */
#[Layout('layouts.public')]
class BookingForm extends Component
{
    /** Hotel yang dipesan; dikunci agar tidak bisa diganti dari sisi klien. */
    #[Locked]
    public int $hotelId = 0;

    #[Url(as: 'kamar', except: '')]
    public string $roomTypeId = '';

    #[Url(as: 'masuk', except: '')]
    public string $checkIn = '';

    #[Url(as: 'keluar', except: '')]
    public string $checkOut = '';

    #[Url(as: 'tamu', except: 2)]
    public int $guests = 2;

    #[Url(as: 'jml_kamar', except: 1)]
    public int $rooms = 1;

    public string $guestName = '';

    public string $guestPhone = '';

    public string $specialRequest = '';

    /** Kode voucher yang dimasukkan tamu (opsional). */
    public string $voucherCode = '';

    /** Pesan kesalahan dari lapisan bisnis (mis. kamar sudah penuh). */
    public ?string $bookingError = null;

    /** Pesan kesalahan voucher saat tombol "Terapkan" ditekan. */
    public ?string $voucherError = null;

    /** Hotel yang sedang dibuka; di-cache per request (tidak diserialisasi). */
    private ?Hotel $hotelCache = null;

    private bool $hotelResolved = false;

    public function mount(string $hotel): void
    {
        $this->hotelId = (int) Hotel::query()->publiclyVisible()->where('slug', $hotel)->value('id');

        // Hotel pending/suspended atau slug tidak dikenal => 404.
        if ($this->hotelId === 0) {
            abort(404);
        }

        $guest = auth()->user();
        $this->guestName = (string) $guest->name;
        $this->guestPhone = (string) ($guest->phone ?? '');

        if ($this->checkIn === '') {
            $this->checkIn = now()->toDateString();
        }

        if ($this->checkOut === '') {
            $this->checkOut = now()->addDay()->toDateString();
        }

        $this->guests = max(1, min(20, $this->guests));
        $this->rooms = max(1, min(10, $this->rooms));

        // Bila hotel hanya punya satu tipe kamar, pilih otomatis.
        if ($this->roomTypeId === '') {
            $ids = $this->roomTypes()->pluck('id');
            $this->roomTypeId = $ids->count() === 1 ? (string) $ids->first() : '';
        }
    }

    /** Bersihkan pesan error bisnis setiap kali input yang relevan berubah. */
    public function updated(string $property): void
    {
        if (in_array($property, ['roomTypeId', 'checkIn', 'checkOut', 'guests', 'rooms'], true)) {
            $this->bookingError = null;
        }

        // Kode voucher diubah => hasil validasi sebelumnya tidak berlaku lagi.
        if ($property === 'voucherCode') {
            $this->voucherError = null;
        }
    }

    /**
     * Memvalidasi voucher saat tombol "Terapkan" ditekan.
     *
     * Pratinjau diskon di ringkasan tetap dihitung senyap di render(), sehingga
     * tamu juga melihat potongan tanpa menekan tombol apa pun.
     */
    public function applyVoucher(): void
    {
        $this->voucherError = null;

        if (blank($this->voucherCode) || ! auth()->check()) {
            return;
        }

        $roomType = $this->selectedRoomType();

        if (! $roomType || ! $this->hasValidDates()) {
            $this->voucherError = 'Pilih tipe kamar dan tanggal terlebih dahulu.';

            return;
        }

        try {
            app(PromotionService::class)->validateVoucher(
                $this->voucherCode,
                $this->hotel(),
                $roomType,
                Carbon::parse($this->checkIn)->startOfDay(),
                Carbon::parse($this->checkOut)->startOfDay(),
                (float) app(PricingService::class)->subtotal($roomType, $this->checkIn, $this->checkOut, $this->rooms),
                $this->nights(),
                auth()->user(),
            );
        } catch (InvalidVoucherException $exception) {
            $this->voucherError = $exception->getMessage();
        }
    }

    /**
     * Hotel yang sedang dipesan, atau null bila hotel berhenti tampil publik
     * (mis. status diubah menjadi pending/suspended saat form masih terbuka).
     */
    public function hotel(): ?Hotel
    {
        if (! $this->hotelResolved) {
            $this->hotelCache = Hotel::query()->publiclyVisible()->find($this->hotelId);
            $this->hotelResolved = true;
        }

        return $this->hotelCache;
    }

    /** Tipe kamar aktif milik hotel ini, terurut dari termurah. */
    public function roomTypes(): Collection
    {
        return RoomType::query()
            ->publiclyVisible()
            ->where('hotel_id', $this->hotelId)
            ->with('photos')
            ->orderBy('base_price')
            ->get();
    }

    public function selectedRoomType(): ?RoomType
    {
        if ($this->roomTypeId === '' || ! ctype_digit($this->roomTypeId)) {
            return null;
        }

        return $this->roomTypes()->firstWhere('id', (int) $this->roomTypeId);
    }

    public function hasValidDates(): bool
    {
        if ($this->checkIn === '' || $this->checkOut === '') {
            return false;
        }

        return Carbon::parse($this->checkOut)->gt(Carbon::parse($this->checkIn));
    }

    public function nights(): int
    {
        return $this->hasValidDates()
            ? (int) Carbon::parse($this->checkIn)->diffInDays(Carbon::parse($this->checkOut))
            : 0;
    }

    public function rules(): array
    {
        return [
            'roomTypeId' => ['required', 'integer', 'min:1'],
            'checkIn' => ['required', 'date', 'after_or_equal:today'],
            'checkOut' => ['required', 'date', 'after:checkIn'],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
            'rooms' => ['required', 'integer', 'min:1', 'max:10'],
            'guestName' => ['required', 'string', 'max:100'],
            'guestPhone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{8,30}$/'],
            'specialRequest' => ['nullable', 'string', 'max:500'],
            'voucherCode' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function messages(): array
    {
        return [
            'roomTypeId.required' => 'Pilih tipe kamar terlebih dahulu.',
            'roomTypeId.min' => 'Pilih tipe kamar terlebih dahulu.',
            'checkIn.required' => 'Tanggal check-in wajib diisi.',
            'checkIn.after_or_equal' => 'Tanggal check-in tidak boleh sebelum hari ini.',
            'checkOut.required' => 'Tanggal check-out wajib diisi.',
            'checkOut.after' => 'Tanggal check-out harus setelah tanggal check-in.',
            'guests.min' => 'Jumlah tamu minimal 1 orang.',
            'rooms.min' => 'Jumlah kamar minimal 1.',
            'guestName.required' => 'Nama pemesan wajib diisi.',
            'guestPhone.required' => 'Nomor telepon wajib diisi.',
            'guestPhone.regex' => 'Nomor telepon tidak valid.',
            'voucherCode.max' => 'Kode voucher terlalu panjang.',
        ];
    }

    /** Memvalidasi input lalu menyerahkan pembuatan booking ke BookingService. */
    public function submit()
    {
        if (! auth()->check()) {
            return $this->redirectRoute('login');
        }

        $validated = $this->validate();

        $roomType = $this->roomTypes()->firstWhere('id', (int) $validated['roomTypeId']);

        if (! $roomType) {
            $this->addError('roomTypeId', 'Tipe kamar tidak tersedia untuk hotel ini.');

            return null;
        }

        try {
            $booking = app(BookingService::class)->create(auth()->user(), $roomType, [
                'check_in' => $validated['checkIn'],
                'check_out' => $validated['checkOut'],
                'guests' => (int) $validated['guests'],
                'rooms_count' => (int) $validated['rooms'],
                'guest_name' => $validated['guestName'],
                'guest_phone' => $validated['guestPhone'],
                'special_request' => $validated['specialRequest'] ?: null,
                'voucher_code' => $validated['voucherCode'] ?: null,
            ]);
        } catch (InvalidVoucherException $exception) {
            // Kode voucher ditolak: tampilkan di kolomnya, bukan sebagai error umum.
            $this->addError('voucherCode', $exception->getMessage());
            $this->voucherError = $exception->getMessage();

            return null;
        } catch (RoomNotAvailableException|InvalidBookingException $exception) {
            // Kesalahan aturan bisnis ditampilkan sebagai pesan, bukan halaman error.
            $this->bookingError = $exception->getMessage();

            return null;
        }

        session()->flash(
            'status',
            "Booking {$booking->code} berhasil dibuat. Selesaikan pembayaran sebelum "
            .$booking->expires_at->translatedFormat('d M Y, H:i').' WIB.'
        );

        return $this->redirectRoute('bookings.index');
    }

    public function render(): View
    {
        $hotel = $this->hotel();

        // Hotel tidak lagi menerima pemesanan: tampilkan pemberitahuan, bukan error.
        if (! $hotel) {
            return view('livewire.guest.booking-unavailable');
        }

        $selected = $this->selectedRoomType();
        $hasDates = $this->hasValidDates();
        $nights = $this->nights();

        // Subtotal dihitung ulang di server setiap kali input berubah.
        $subtotal = $selected && $hasDates
            ? app(PricingService::class)->subtotal($selected, $this->checkIn, $this->checkOut, $this->rooms)
            : null;

        // Pratinjau diskon voucher: divalidasi senyap (tanpa melempar exception)
        // agar ringkasan langsung menampilkan total setelah potongan.
        $voucherPreview = null;

        if ($subtotal !== null && $selected !== null && auth()->check()) {
            try {
                $promotion = app(PromotionService::class)->validateVoucher(
                    $this->voucherCode,
                    $hotel,
                    $selected,
                    Carbon::parse($this->checkIn)->startOfDay(),
                    Carbon::parse($this->checkOut)->startOfDay(),
                    (float) $subtotal,
                    $nights,
                    auth()->user(),
                );

                $discount = app(PromotionService::class)->discountFor(
                    $promotion,
                    (float) $subtotal,
                    $nights,
                    app(PricingService::class)->nightlyRates($selected, $this->checkIn, $this->checkOut),
                );

                if ($discount > 0) {
                    $voucherPreview = [
                        'promotion' => $promotion,
                        'discount' => $discount,
                        'total' => round($subtotal - $discount, 2),
                    ];
                }
            } catch (InvalidVoucherException) {
                // Kode belum valid / belum ditekan "Terapkan": tampilkan subtotal saja.
                $voucherPreview = null;
            }
        }

        return view('livewire.guest.booking-form', [
            'hotel' => $hotel,
            'roomTypes' => $this->roomTypes(),
            'selectedRoomType' => $selected,
            'hasDates' => $hasDates,
            'nights' => $nights,
            'remainingRooms' => $selected && $hasDates
                ? app(AvailabilityService::class)->availableRooms($selected, $this->checkIn, $this->checkOut)
                : null,
            'subtotal' => $subtotal,
            'voucherPreview' => $voucherPreview,
            'expiryHours' => max(1, (int) config('booking.payment_expiry_hours', 24)),
        ]);
    }
}
