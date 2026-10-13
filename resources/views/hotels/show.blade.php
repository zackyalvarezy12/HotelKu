@extends('layouts.public')

@section('title', $hotel->name.' — '.$hotel->city.' | HotelKu')

@php
    $gallery = $hotel->photos->map(fn ($photo) => $photo->url)->values();
@endphp

@section('content')
    {{-- ================= Breadcrumb ================= --}}
    <nav class="mb-3 flex items-center gap-1.5 text-sm text-gray-500" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Beranda</a>
        <span>/</span>
        <a href="{{ route('hotels.index', ['kota' => $hotel->city]) }}" class="hover:text-indigo-600">{{ $hotel->city }}</a>
        <span>/</span>
        <span class="truncate font-medium text-gray-700">{{ $hotel->name }}</span>
    </nav>

    {{-- ================= Judul ================= --}}
    <header class="mb-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ $hotel->name }}</h1>
                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
                    <span class="font-semibold text-amber-500">{{ str_repeat('★', $hotel->star_rating) }}</span>
                    <span class="flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z" />
                            <circle cx="12" cy="10" r="2.5" />
                        </svg>
                        {{ $hotel->address }}
                    </span>
                </div>
            </div>

            <a href="{{ route('chat.inbox', ['hotel' => $hotel->slug]) }}" class="btn-secondary shrink-0">
                Chat Pemilik
            </a>

            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 1 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd" />
                </svg>
                Terverifikasi
            </span>
        </div>
    </header>

    {{-- ================= Galeri ================= --}}
    <section x-data="{ current: 0, images: {{ Illuminate\Support\Js::from($gallery) }} }" class="mb-6">
        @if ($gallery->isEmpty())
            <div class="flex h-56 w-full items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 to-indigo-50 text-indigo-400 sm:h-80">
                <svg class="h-12 w-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14" />
                </svg>
            </div>
        @else
            <div class="overflow-hidden rounded-xl bg-gray-100">
                <img :src="images[current]" alt="Foto {{ $hotel->name }}"
                     class="h-56 w-full object-cover sm:h-96" loading="lazy">
            </div>

            @if ($gallery->count() > 1)
                <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
                    @foreach ($gallery as $index => $url)
                        <button type="button" @click="current = {{ $index }}"
                                class="h-16 w-24 shrink-0 overflow-hidden rounded-lg border-2 transition"
                                :class="current === {{ $index }} ? 'border-indigo-600' : 'border-transparent opacity-70 hover:opacity-100'"
                                aria-label="Lihat foto {{ $index + 1 }}">
                            <img src="{{ $url }}" alt="" class="h-full w-full object-cover" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif
        @endif
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- ================= Kolom utama ================= --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Deskripsi --}}
            @if (filled($hotel->description))
                <section class="card p-4 sm:p-5">
                    <h2 class="text-base font-semibold text-gray-900">Tentang Hotel</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-600">{{ $hotel->description }}</p>
                </section>
            @endif

            <section class="card p-4 sm:p-5">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <h2 class="text-base font-semibold text-gray-900">Ulasan Tamu</h2>
                    @if ($reviews->isNotEmpty())
                        <p class="text-sm text-gray-600">
                            <span class="font-semibold text-amber-600">{{ number_format($ratingAverage, 1) }} ★</span>
                            · {{ $hotel->reviews()->count() }} ulasan
                        </p>
                    @endif
                </div>
                @if ($reviews->isEmpty())
                    <p class="mt-2 text-sm text-gray-500">Belum ada ulasan untuk hotel ini.</p>
                @else
                    <div class="mt-3 divide-y divide-gray-100">
                        @foreach ($reviews as $review)
                            <article class="py-3 first:pt-0 last:pb-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ $review->user->name }}</p>
                                        <p class="text-xs text-amber-600">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</p>
                                    </div>
                                    <time class="shrink-0 text-xs text-gray-400">{{ $review->created_at->translatedFormat('d M Y') }}</time>
                                </div>
                                @if (filled($review->comment))
                                    <p class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ $review->comment }}</p>
                                @endif
                                @if (filled($review->reply))
                                    <div class="mt-2 border-l-2 border-emerald-500 pl-3">
                                        <p class="text-xs font-semibold text-emerald-800">Balasan pemilik</p>
                                        <p class="mt-1 text-sm text-gray-600">{{ $review->reply }}</p>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Fasilitas --}}
            <section class="card p-4 sm:p-5">
                <h2 class="text-base font-semibold text-gray-900">Fasilitas</h2>
                @if ($hotel->facilities->isEmpty())
                    <p class="mt-2 text-sm text-gray-500">Belum ada data fasilitas.</p>
                @else
                    <ul class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($hotel->facilities as $facility)
                            <li class="flex items-center gap-2 text-sm text-gray-700">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                                    </svg>
                                </span>
                                <span class="truncate">{{ $facility->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Tipe kamar --}}
            <section class="card p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-gray-900">Tipe Kamar</h2>
                    @if ($hasDates)
                        <p class="text-xs text-gray-500">
                            Sisa kamar untuk {{ \Illuminate\Support\Carbon::parse($checkIn)->translatedFormat('d M') }}–{{ \Illuminate\Support\Carbon::parse($checkOut)->translatedFormat('d M') }}
                            · {{ $guests }} tamu
                        </p>
                    @endif
                </div>

                @if ($hotel->roomTypes->isEmpty())
                    <p class="mt-3 text-sm text-gray-500">Hotel ini belum memiliki tipe kamar aktif.</p>
                @else
                    <div class="mt-3 space-y-3">
                        @foreach ($hotel->roomTypes as $roomType)
                            @php
                                $photo = $roomType->photos->first();
                                $remaining = $hasDates ? ($remainingRooms[$roomType->id] ?? 0) : null;
                                $fitsGuests = $roomType->capacity >= $guests;
                                $canBook = $fitsGuests && ($remaining === null || $remaining > 0);
                                $displayPrice = $prices[$roomType->id] ?? null;
                                $originalPrice = $displayPrice['original'] ?? $roomType->base_price;
                                $discountedPrice = $displayPrice['discounted'] ?? null;
                            @endphp

                            <article class="flex flex-col gap-3 rounded-xl border border-gray-200 p-3 sm:flex-row">
                                <div class="h-32 w-full shrink-0 overflow-hidden rounded-lg bg-gray-100 sm:h-28 sm:w-40">
                                    @if ($photo)
                                        <img src="{{ $photo->url }}" alt="Foto {{ $roomType->name }}"
                                             class="h-full w-full object-cover" loading="lazy">
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-semibold text-gray-900">{{ $roomType->name }}</h3>

                                    <ul class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-500">
                                        <li>{{ $roomType->capacity }} tamu</li>
                                        @if ($roomType->bed_type)
                                            <li>{{ $roomType->bed_type }}</li>
                                        @endif
                                        @if ($roomType->size_m2)
                                            <li>{{ $roomType->size_m2 }} m²</li>
                                        @endif
                                    </ul>

                                    @if ($roomType->facilities->isNotEmpty())
                                        <ul class="mt-2 flex flex-wrap gap-1">
                                            @foreach ($roomType->facilities->take(4) as $facility)
                                                <li class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $facility->name }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($remaining !== null)
                                        <p class="mt-2 text-xs font-medium {{ $canBook ? 'text-green-700' : 'text-red-600' }}">
                                            @if (! $fitsGuests)
                                                Kapasitas hanya {{ $roomType->capacity }} tamu
                                            @elseif ($remaining > 0)
                                                Tersedia {{ $remaining }} kamar
                                            @else
                                                Kamar penuh untuk tanggal ini
                                            @endif
                                        </p>
                                    @endif
                                </div>

                                <div class="shrink-0 border-t border-gray-100 pt-3 text-right sm:border-l sm:border-t-0 sm:pl-3 sm:pt-0">
                                    @if ($roomType->seasonalPrices->isNotEmpty())
                                        <span class="mb-1 inline-block rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700">
                                            Harga musiman berlaku
                                        </span>
                                    @endif
                                    @if ($discountedPrice !== null)
                                        <p class="text-xs text-gray-400 line-through">
                                            Rp{{ number_format((float) $originalPrice, 0, ',', '.') }}
                                        </p>
                                        <p class="text-base font-bold text-rose-700">
                                            Rp{{ number_format((float) $discountedPrice, 0, ',', '.') }}
                                        </p>
                                        @if (! empty($displayPrice['promotion']))
                                            <p class="text-xs font-medium text-rose-700">{{ $displayPrice['promotion']->name }}</p>
                                        @endif
                                    @else
                                        <p class="text-base font-bold text-indigo-700">
                                            Rp{{ number_format((float) $originalPrice, 0, ',', '.') }}
                                        </p>
                                    @endif
                                    <p class="text-xs text-gray-400">per malam</p>

                                    @if ($canBook)
                                        <a href="{{ route('bookings.create', array_filter([
                                                'hotel' => $hotel->slug,
                                                'kamar' => $roomType->id,
                                                'masuk' => $checkIn,
                                                'keluar' => $checkOut,
                                                'tamu' => $guests,
                                            ], fn ($value) => filled($value))) }}"
                                           class="btn-primary mt-3 w-full sm:w-auto">
                                            Pesan
                                        </a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">
                        Pemesanan dilakukan langsung di situs ini. Setelah memesan, selesaikan pembayaran
                        dalam {{ max(1, (int) config('booking.payment_expiry_hours', 24)) }} jam agar booking tidak kedaluwarsa.
                    </p>
                @endif
            </section>

            {{-- Peta --}}
            <section class="card p-4 sm:p-5">
                <h2 class="text-base font-semibold text-gray-900">Lokasi</h2>

                @if ($hotel->latitude && $hotel->longitude)
                    <div class="mt-3 h-64 w-full overflow-hidden rounded-xl bg-gray-100 sm:h-80"
                         data-hotel-map
                         data-lat="{{ $hotel->latitude }}"
                         data-lng="{{ $hotel->longitude }}"
                         data-label="{{ $hotel->name }}"></div>
                    <p class="mt-2 text-xs text-gray-500">
                        Peta memerlukan koneksi internet untuk memuat ubin OpenStreetMap.
                    </p>
                @else
                    <p class="mt-2 text-sm text-gray-500">Koordinat hotel belum tersedia.</p>
                @endif
            </section>
        </div>

        {{-- ================= Sidebar info ================= --}}
        <aside class="space-y-4">
            <div class="card p-4 lg:sticky lg:top-20">
                <h2 class="text-base font-semibold text-gray-900">Informasi Hotel</h2>

                <dl class="mt-3 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Check-in</dt>
                        <dd class="font-medium text-gray-900">{{ substr($hotel->check_in_time, 0, 5) }} WIB</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Check-out</dt>
                        <dd class="font-medium text-gray-900">{{ substr($hotel->check_out_time, 0, 5) }} WIB</dd>
                    </div>
                    @if ($hotel->phone)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Telepon</dt>
                            <dd class="font-medium text-gray-900">{{ $hotel->phone }}</dd>
                        </div>
                    @endif
                    @if ($hotel->email)
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Email</dt>
                            <dd class="break-all text-right font-medium text-gray-900">{{ $hotel->email }}</dd>
                        </div>
                    @endif
                    <div class="pt-1">
                        <dt class="text-gray-500">Alamat</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $hotel->address }}</dd>
                    </div>
                </dl>

                <a href="{{ route('hotels.index', ['kota' => $hotel->city]) }}" class="btn-secondary mt-4 w-full">
                    Lihat hotel lain di {{ $hotel->city }}
                </a>
            </div>
        </aside>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/map.js')
@endpush
