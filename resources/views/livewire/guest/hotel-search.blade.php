<div x-data="{ filterOpen: false }" class="space-y-4">
    {{-- Ringkasan pencarian --}}
    @livewire('guest.search-form', ['compact' => true], key('search-summary'))

    {{-- Baris kontrol: judul, tombol filter (mobile), pengurutan --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-gray-900 sm:text-xl">
                @if ($city !== '')
                    Hotel di {{ $city }}
                @else
                    Semua Hotel
                @endif
            </h1>
            <p class="text-sm text-gray-500" wire:loading.remove>
                {{ $hotels->total() }} hotel ditemukan
                @if ($this->hasDates())
                    · {{ \Illuminate\Support\Carbon::parse($checkIn)->translatedFormat('d M') }}–{{ \Illuminate\Support\Carbon::parse($checkOut)->translatedFormat('d M') }}
                    · {{ $guests }} tamu
                @endif
            </p>
            <p class="text-sm text-gray-500" wire:loading>Memuat…</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                    class="btn-secondary lg:hidden"
                    @click="filterOpen = true">
                Filter
                @if ($this->activeFilterCount() > 0)
                    <span class="rounded-full bg-indigo-600 px-1.5 py-0.5 text-xs font-semibold text-white">
                        {{ $this->activeFilterCount() }}
                    </span>
                @endif
            </button>

            <label class="sr-only" for="sort">Urutkan</label>
            <select id="sort" wire:model.live="sort"
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="termurah">Termurah</option>
                <option value="termahal">Termahal</option>
                <option value="bintang">Bintang tertinggi</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        {{-- ================= Panel filter: desktop ================= --}}
        <aside class="hidden lg:col-span-3 lg:block">
            <div class="card sticky top-20 p-4">
                @include('livewire.guest.partials.filters')
            </div>
        </aside>

        {{-- ================= Panel filter: mobile (drawer) ================= --}}
        <div x-show="filterOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-gray-900/50" @click="filterOpen = false"></div>

            <div class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-2xl bg-white p-4 shadow-xl"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full"
                 x-transition:enter-end="translate-y-0">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Filter</h2>
                    <button type="button" class="rounded-lg p-1 text-gray-500 hover:bg-gray-100" @click="filterOpen = false" aria-label="Tutup">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @include('livewire.guest.partials.filters')

                <button type="button" class="btn-primary mt-4 w-full" @click="filterOpen = false">Terapkan Filter</button>
            </div>
        </div>

        {{-- ================= Hasil ================= --}}
        <section class="lg:col-span-9">
            <div class="space-y-3">
                @forelse ($hotels as $hotel)
                    @include('partials.hotel-card', [
                        'hotel' => $hotel,
                        'availableRooms' => $availability[$hotel->id] ?? null,
                        'displayPrice' => $displayPrices[$hotel->id] ?? null,
                    ])
                @empty
                    <div class="card p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
                        </svg>
                        <h2 class="mt-3 text-base font-semibold text-gray-900">Tidak ada hotel yang cocok</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Coba ubah kota, tanggal, atau kurangi filter yang dipilih.
                        </p>
                        <button type="button" wire:click="resetFilters" class="btn-secondary mt-4">Hapus semua filter</button>
                    </div>
                @endforelse
            </div>

            @if ($hotels->hasMorePages())
                <div class="mt-5 text-center" wire:loading.remove>
                    <button type="button" wire:click="loadMore" class="btn-secondary w-full sm:w-auto">
                        Muat lebih banyak ({{ $hotels->total() - $hotels->count() }} lagi)
                    </button>
                </div>
            @endif
        </section>
    </div>
</div>
