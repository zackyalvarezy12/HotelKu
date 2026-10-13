<?php

namespace App\Livewire\Guest;

use App\Models\Facility;
use App\Models\Hotel;
use App\Services\AvailabilityService;
use App\Services\PromotionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Hasil pencarian hotel + filter (kota, tanggal, tamu, harga, bintang, fasilitas).
 *
 * Hanya hotel berstatus `active` yang ditampilkan (scope Hotel::publiclyVisible()).
 */
#[Layout('layouts.public')]
class HotelSearch extends Component
{
    use WithPagination;

    #[Url(as: 'kota', except: '')]
    public string $city = '';

    #[Url(as: 'masuk', except: '')]
    public string $checkIn = '';

    #[Url(as: 'keluar', except: '')]
    public string $checkOut = '';

    #[Url(as: 'tamu', except: 2)]
    public int $guests = 2;

    #[Url(as: 'harga_min', except: '')]
    public string $minPrice = '';

    #[Url(as: 'harga_max', except: '')]
    public string $maxPrice = '';

    /** Pilihan bintang (1-5), bisa lebih dari satu. */
    #[Url(as: 'bintang', except: [])]
    public array $stars = [];

    /** Pilihan ID fasilitas. Hotel harus memiliki SEMUA fasilitas terpilih. */
    #[Url(as: 'fasilitas', except: [])]
    public array $facilities = [];

    #[Url(as: 'urutkan', except: 'termurah')]
    public string $sort = 'termurah';

    /** Jumlah kartu per halaman; dinaikkan oleh tombol "muat lebih banyak". */
    public int $perPage = 6;

    private ?Collection $availabilityCache = null;

    public function mount(): void
    {
        $this->normalizeInput();
    }

    /** Bersihkan nilai filter dari input pengguna sebelum dipakai di query. */
    private function normalizeInput(): void
    {
        $this->stars = array_values(array_intersect(array_map('intval', $this->stars), [1, 2, 3, 4, 5]));
        $this->facilities = array_values(array_filter(array_map('intval', $this->facilities)));
        $this->guests = max(1, min(20, $this->guests));

        if (! in_array($this->sort, ['termurah', 'termahal', 'bintang'], true)) {
            $this->sort = 'termurah';
        }

        $this->minPrice = is_numeric($this->minPrice) ? (string) max(0, (float) $this->minPrice) : '';
        $this->maxPrice = is_numeric($this->maxPrice) ? (string) max(0, (float) $this->maxPrice) : '';
    }

    /** Setiap perubahan filter mengembalikan daftar ke halaman pertama. */
    public function updated(string $property): void
    {
        if ($property === 'perPage') {
            return;
        }

        $this->normalizeInput();
        $this->perPage = 6;
        $this->availabilityCache = null;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['city', 'checkIn', 'checkOut', 'minPrice', 'maxPrice', 'stars', 'facilities']);
        $this->guests = 2;
        $this->sort = 'termurah';
        $this->perPage = 6;
        $this->availabilityCache = null;
        $this->resetPage();
    }

    public function loadMore(): void
    {
        $this->perPage += 6;
    }

    /** Filter tanggal hanya aktif bila kedua tanggal terisi. */
    public function hasDates(): bool
    {
        return $this->checkIn !== '' && $this->checkOut !== '';
    }

    /** Jumlah filter aktif, dipakai untuk badge pada tombol Filter (mobile). */
    public function activeFilterCount(): int
    {
        return collect([
            $this->city !== '',
            $this->hasDates(),
            $this->minPrice !== '',
            $this->maxPrice !== '',
            $this->stars !== [],
            $this->facilities !== [],
        ])->filter()->count();
    }

    /** [hotel_id => sisa kamar] untuk tanggal terpilih; kosong bila tanpa tanggal. */
    public function availability(): Collection
    {
        if ($this->availabilityCache instanceof Collection) {
            return $this->availabilityCache;
        }

        return $this->availabilityCache = $this->hasDates()
            ? app(AvailabilityService::class)->availableHotels($this->checkIn, $this->checkOut, $this->guests)
            : collect();
    }

    /** Query hotel yang tampil di halaman tamu beserta seluruh filternya. */
    private function hotelQuery(): Builder
    {
        // forListing(): hanya hotel aktif + relasi kartu + harga mulai dari.
        // publicRoomTypes ikut dimuat agar harga coret tidak memicu query tambahan.
        $query = Hotel::query()->forListing()->with('publicRoomTypes:id,hotel_id,base_price');

        if ($this->city !== '') {
            $query->where('hotels.city', $this->city);
        }

        if ($this->stars !== []) {
            $query->whereIn('hotels.star_rating', $this->stars);
        }

        if ($this->facilities !== []) {
            foreach ($this->facilities as $facilityId) {
                $query->whereHas('facilities', fn (Builder $q) => $q->where('facilities.id', $facilityId));
            }
        }

        if ($this->minPrice !== '' || $this->maxPrice !== '') {
            $query->whereHas('publicRoomTypes', function (Builder $q) {
                if ($this->minPrice !== '') {
                    $q->where('base_price', '>=', (float) $this->minPrice);
                }
                if ($this->maxPrice !== '') {
                    $q->where('base_price', '<=', (float) $this->maxPrice);
                }
            });
        }

        // Jumlah tamu selalu menyaring kapasitas kamar, walaupun tanggal kosong.
        $query->whereHas('publicRoomTypes', fn (Builder $q) => $q->where('capacity', '>=', $this->guests));

        // Filter ketersediaan berdasarkan tanggal & jumlah tamu.
        if ($this->hasDates()) {
            $availableIds = $this->availability()->keys();

            $availableIds->isEmpty()
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('hotels.id', $availableIds);
        }

        match ($this->sort) {
            'termahal' => $query->orderByDesc('min_price'),
            'bintang' => $query->orderByDesc('hotels.star_rating'),
            default => $query->orderBy('min_price'),
        };

        return $query->orderBy('hotels.name');
    }

    /**
     * Tanggal yang dipakai untuk menilai promo pada kartu pencarian.
     *
     * Bila tamu belum memilih tanggal, dipakai 1 malam mulai hari ini supaya
     * promo dengan periode menginap tetap bisa dinilai secara adil.
     */
    private function displayDates(): array
    {
        return [
            Carbon::parse($this->checkIn ?: now())->startOfDay(),
            Carbon::parse($this->checkOut ?: now()->addDay())->startOfDay(),
        ];
    }

    public function render(): View
    {
        $hotels = $this->hotelQuery()->paginate($this->perPage);

        [$displayCheckIn, $displayCheckOut] = $this->displayDates();

        // Harga coret: satu query promo untuk seluruh hotel pada halaman ini.
        $displayPrices = app(PromotionService::class)->displayPricesForHotels(
            $hotels->getCollection(),
            $hotels->getCollection()->pluck('publicRoomTypes')->flatten()->values(),
            $displayCheckIn,
            $displayCheckOut,
        );

        return view('livewire.guest.hotel-search', [
            'hotels' => $hotels,
            'availability' => $this->availability(),
            'displayPrices' => $displayPrices,
            'cityOptions' => Hotel::query()->publiclyVisible()->distinct()->orderBy('city')->pluck('city'),
            'facilityOptions' => Facility::query()
                ->whereHas('hotels', fn (Builder $q) => $q->publiclyVisible())
                ->orderBy('name')
                ->get(['id', 'name', 'icon']),
        ]);
    }
}
