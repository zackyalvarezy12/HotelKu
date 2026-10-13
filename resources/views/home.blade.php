@extends('layouts.public')

@section('title', 'HotelKu — Cari & Pesan Hotel di Indonesia')

@section('content')
    {{-- ================= Hero foto + form pencarian ================= --}}
    <section>
        <div class="relative isolate flex min-h-[19rem] overflow-hidden rounded-xl bg-gray-900 sm:min-h-[24rem] lg:min-h-[27rem]">
            <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2400&q=85"
                 alt="Resor hotel dengan kolam renang di tengah taman tropis"
                 fetchpriority="high"
                 class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-gray-950/80 via-gray-950/55 to-gray-950/10"></div>

            <div class="relative z-10 flex w-full items-center px-5 py-10 text-white sm:px-10 sm:py-14 lg:px-14">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase text-white/80 sm:text-sm">HotelKu · Jelajahi Indonesia</p>
                    <h1 class="mt-3 text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl">
                        Temukan hotel nyaman untuk menginap
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/90 sm:text-base">
                        Bandingkan {{ $totalHotels }} hotel terverifikasi di berbagai kota, lengkap dengan harga,
                        fasilitas, dan ketersediaan kamar.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <p class="text-xl font-semibold leading-snug text-gray-900 sm:text-2xl">
                Berangkat dengan rencana. Pulang membawa cerita.
            </p>
            <p class="max-w-xl text-sm leading-relaxed text-gray-500">
                Temukan tempat menginap yang pas, lalu nikmati lebih banyak waktu untuk menjelajahi tujuan.
            </p>
        </div>

        <div class="mt-4">
            @livewire('guest.search-form')
        </div>
    </section>

    {{-- ================= Kota populer ================= --}}
    @if ($cities->isNotEmpty())
        <section class="mt-8">
            <h2 class="text-base font-semibold text-gray-900 sm:text-lg">Kota Populer</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($cities as $city)
                    <a href="{{ route('hotels.index', ['kota' => $city->city]) }}"
                       class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-sm
                              font-medium text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">
                        {{ $city->city }}
                        <span class="text-xs text-gray-400">({{ $city->total }})</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ================= Hotel pilihan ================= --}}
    <section class="mt-8">
        <div class="flex items-end justify-between gap-3">
            <h2 class="text-base font-semibold text-gray-900 sm:text-lg">Hotel Pilihan</h2>
            <a href="{{ route('hotels.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">
                Lihat semua
            </a>
        </div>

        @if ($featured->isEmpty())
            <div class="card mt-3 p-6 text-center text-sm text-gray-500">
                Belum ada hotel aktif yang tersedia.
            </div>
        @else
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $hotel)
                    @include('partials.hotel-card', ['hotel' => $hotel, 'availableRooms' => null])
                @endforeach
            </div>
        @endif
    </section>

    {{-- ================= Keunggulan ================= --}}
    <section class="mt-10 grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ([
            ['Hotel terverifikasi', 'Setiap hotel diverifikasi langsung oleh tim kami.'],
            ['Harga transparan', 'Harga per malam ditampilkan apa adanya, tanpa biaya tersembunyi.'],
            ['Ketersediaan nyata', 'Sisa kamar dihitung dari pemesanan yang sedang berjalan.'],
        ] as [$judul, $deskripsi])
            <div class="card p-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ $judul }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $deskripsi }}</p>
            </div>
        @endforeach
    </section>
@endsection
