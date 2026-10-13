@php
    $formatted = fn (float|int|null $value) => number_format((float) $value, 0, ',', '.');
    $kamarTidakCukup = $remainingRooms !== null && $remainingRooms < $rooms;
@endphp

<div class="space-y-4">
    {{-- ================= Breadcrumb ================= --}}
    <nav class="flex items-center gap-1.5 text-sm text-gray-500" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Beranda</a>
        <span>/</span>
        <a href="{{ route('hotels.show', $hotel->slug) }}" class="truncate hover:text-indigo-600">{{ $hotel->name }}</a>
        <span>/</span>
        <span class="font-medium text-gray-700">Pesan</span>
    </nav>

    <header>
        <h1 class="text-lg font-bold text-gray-900 sm:text-xl">Pemesanan Kamar</h1>
        <p class="mt-0.5 text-sm text-gray-500">
            {{ $hotel->name }} · {{ $hotel->city }}
        </p>
    </header>

    {{-- Pesan kesalahan dari lapisan bisnis (mis. kamar sudah penuh). --}}
    @if ($bookingError)
        <div class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
             role="alert" data-testid="booking-error">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
            </svg>
            <span>{{ $bookingError }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- ================= Form ================= --}}
        <form wire:submit="submit" class="space-y-4 lg:col-span-2">
            <section class="card p-4 sm:p-5">
                <h2 class="text-base font-semibold text-gray-900">Detail Menginap</h2>

                {{-- Tipe kamar --}}
                <div class="mt-3">
                    <label for="booking-room-type" class="form-label">Tipe Kamar</label>
                    <select id="booking-room-type" wire:model.live="roomTypeId"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">— Pilih tipe kamar —</option>
                        @foreach ($roomTypes as $roomType)
                            <option value="{{ $roomType->id }}">
                                {{ $roomType->name }} — Rp{{ $formatted($roomType->base_price) }}/malam
                                ({{ $roomType->capacity }} tamu)
                            </option>
                        @endforeach
                    </select>
                    @error('roomTypeId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                    @if ($roomTypes->isEmpty())
                        <p class="mt-2 text-sm text-amber-700">
                            Hotel ini belum memiliki tipe kamar aktif, pemesanan belum dapat dilakukan.
                        </p>
                    @endif
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    {{-- Check-in --}}
                    <div>
                        <label for="booking-check-in" class="form-label">Check-in</label>
                        <input id="booking-check-in" type="date" wire:model.live="checkIn"
                               min="{{ now()->toDateString() }}"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('checkIn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Check-out --}}
                    <div>
                        <label for="booking-check-out" class="form-label">Check-out</label>
                        <input id="booking-check-out" type="date" wire:model.live="checkOut"
                               min="{{ now()->addDay()->toDateString() }}"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('checkOut') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Tamu --}}
                    <div>
                        <label for="booking-guests" class="form-label">Jumlah Tamu</label>
                        <select id="booking-guests" wire:model.live="guests"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (range(1, 20) as $n)
                                <option value="{{ $n }}">{{ $n }} orang</option>
                            @endforeach
                        </select>
                        @error('guests') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @if ($selectedRoomType && $guests > $selectedRoomType->capacity * $rooms)
                            <p class="mt-1 text-xs text-red-600">
                                Kapasitas {{ $rooms }} kamar ini hanya
                                {{ $selectedRoomType->capacity * $rooms }} orang.
                            </p>
                        @endif
                    </div>

                    {{-- Jumlah kamar --}}
                    <div>
                        <label for="booking-rooms" class="form-label">Jumlah Kamar</label>
                        <select id="booking-rooms" wire:model.live="rooms"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach (range(1, 10) as $n)
                                <option value="{{ $n }}">{{ $n }} kamar</option>
                            @endforeach
                        </select>
                        @error('rooms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="card p-4 sm:p-5">
                <h2 class="text-base font-semibold text-gray-900">Data Pemesan</h2>

                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="booking-name" class="form-label">Nama Lengkap</label>
                        <input id="booking-name" type="text" wire:model="guestName"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('guestName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="booking-phone" class="form-label">Nomor Telepon</label>
                        <input id="booking-phone" type="tel" wire:model="guestPhone" placeholder="08xxxxxxxxxx"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('guestPhone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="booking-request" class="form-label">Permintaan Khusus (opsional)</label>
                        <textarea id="booking-request" rows="3" wire:model="specialRequest"
                                  placeholder="Contoh: kamar lantai atas, tempat tidur tambahan."
                                  class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        @error('specialRequest') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="card p-4 sm:p-5">
                <h2 class="text-base font-semibold text-gray-900">Kode Promo</h2>
                <p class="mt-0.5 text-xs text-gray-500">
                    Punya voucher atau sedang ada promo otomatis? Masukkan kodenya di sini.
                </p>

                <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start">
                    <div class="flex-1">
                        <label for="booking-voucher" class="form-label">Kode Voucher</label>
                        <input id="booking-voucher" type="text" wire:model.live="voucherCode"
                               placeholder="Contoh: HEMAT10" autocomplete="off"
                               class="w-full rounded-lg border-gray-300 text-sm uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('voucherCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @if ($voucherError && ! $errors->has('voucherCode'))
                            <p class="mt-1 text-xs text-red-600">{{ $voucherError }}</p>
                        @endif
                    </div>

                    <button type="button" wire:click="applyVoucher" wire:loading.attr="disabled"
                            class="btn-secondary shrink-0 sm:mt-7">
                        <span wire:loading.remove wire:target="applyVoucher">Terapkan</span>
                        <span wire:loading wire:target="applyVoucher">Memeriksa…</span>
                    </button>
                </div>

                @if ($voucherPreview)
                    <p class="mt-3 rounded-md bg-green-50 px-2 py-1.5 text-xs font-medium text-green-700"
                       data-testid="voucher-applied">
                        Voucher <strong>{{ $voucherPreview['promotion']->name }}</strong> aktif —
                        Anda hemat Rp{{ $formatted($voucherPreview['discount']) }}.
                    </p>
                @elseif ($subtotal !== null)
                    <p class="mt-3 rounded-md bg-gray-50 px-2 py-1.5 text-xs text-gray-500">
                        Tanpa voucher, total tetap dihitung dari harga kamar yang berlaku.
                    </p>
                @endif
            </section>

            {{-- Tombol kirim: mobile-first, melebar penuh di layar kecil. --}}
            <div class="lg:hidden">
                <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled"
                        @disabled($roomTypes->isEmpty())>
                    <span wire:loading.remove wire:target="submit">Pesan Sekarang</span>
                    <span wire:loading wire:target="submit">Memproses…</span>
                </button>
            </div>
        </form>

        {{-- ================= Ringkasan ================= --}}
        <aside class="space-y-4">
            <div class="card p-4 lg:sticky lg:top-20">
                <h2 class="text-base font-semibold text-gray-900">Ringkasan Pesanan</h2>

                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Tipe kamar</dt>
                        <dd class="text-right font-medium text-gray-900">
                            {{ $selectedRoomType?->name ?? '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Tanggal</dt>
                        <dd class="text-right font-medium text-gray-900">
                            @if ($hasDates)
                                {{ \Illuminate\Support\Carbon::parse($checkIn)->translatedFormat('d M') }} –
                                {{ \Illuminate\Support\Carbon::parse($checkOut)->translatedFormat('d M Y') }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Durasi</dt>
                        <dd class="font-medium text-gray-900">{{ $nights }} malam · {{ $rooms }} kamar</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Tamu</dt>
                        <dd class="font-medium text-gray-900">{{ $guests }} orang</dd>
                    </div>
                </dl>

                @if ($remainingRooms !== null)
                    <p class="mt-3 rounded-md px-2 py-1 text-xs font-medium {{ $kamarTidakCukup ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }}">
                        @if ($kamarTidakCukup)
                            Hanya tersisa {{ $remainingRooms }} kamar untuk tanggal ini.
                        @else
                            Tersedia {{ $remainingRooms }} kamar untuk tanggal ini.
                        @endif
                    </p>
                @endif

                <div class="mt-3 border-t border-gray-100 pt-3">
                    @if ($subtotal !== null)
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-sm text-gray-500">Subtotal</span>
                            <span class="text-sm {{ $voucherPreview ? 'text-gray-400 line-through' : 'text-gray-900' }}">
                                Rp{{ $formatted($subtotal) }}
                            </span>
                        </div>

                        @if ($voucherPreview)
                            <div class="mt-1 flex items-baseline justify-between gap-3">
                                <span class="text-sm text-green-700">Potongan voucher</span>
                                <span class="text-sm font-semibold text-green-700" data-testid="voucher-discount">
                                    − Rp{{ $formatted($voucherPreview['discount']) }}
                                </span>
                            </div>
                        @endif

                        <div class="mt-2 flex items-baseline justify-between gap-3 border-t border-gray-100 pt-2">
                            <span class="text-sm font-medium text-gray-700">Total</span>
                            <span class="text-lg font-bold text-indigo-700" data-testid="booking-total">
                                Rp{{ $formatted($voucherPreview['total'] ?? $subtotal) }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-right text-xs text-gray-400">
                            Rp{{ $formatted(($voucherPreview['total'] ?? $subtotal) / max(1, $nights * $rooms)) }} per kamar per malam
                        </p>
                    @else
                        <p class="text-sm text-gray-500">
                            Pilih tipe kamar dan tanggal untuk melihat total harga.
                        </p>
                    @endif
                </div>

                <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    Booking berstatus <strong>menunggu pembayaran</strong> selama {{ $expiryHours }} jam.
                    Jika lewat batas waktu, booking otomatis kedaluwarsa dan kamar dilepas kembali.
                </p>

                {{-- Di luar <form>, jadi memanggil aksi Livewire secara langsung. --}}
                <button type="button" wire:click="submit" wire:loading.attr="disabled"
                        class="btn-primary mt-4 hidden w-full lg:inline-flex"
                        @disabled($roomTypes->isEmpty())>
                    <span wire:loading.remove wire:target="submit">Pesan Sekarang</span>
                    <span wire:loading wire:target="submit">Memproses…</span>
                </button>
            </div>
        </aside>
    </div>
</div>
