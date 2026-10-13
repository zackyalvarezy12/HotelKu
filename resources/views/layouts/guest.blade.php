<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'HotelKu') }} — Akun</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white font-sans text-gray-900 antialiased">
        <main class="grid min-h-screen lg:grid-cols-2">
            <aside class="relative isolate flex min-h-52 overflow-hidden bg-gray-950 sm:min-h-60 lg:min-h-screen">
                <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1800&q=85"
                     alt="Suasana resor hotel untuk beristirahat dan menikmati perjalanan"
                     fetchpriority="high"
                     class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-950/65 via-gray-950/30 to-emerald-950/75"></div>

                <div class="relative z-10 flex w-full flex-col justify-between p-5 text-white sm:p-8 lg:p-12">
                    <a href="{{ route('home') }}" class="inline-flex w-fit items-center gap-3 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                        <span class="flex h-10 w-10 items-center justify-center rounded-md border border-white/35 bg-white/10 text-sm font-bold tracking-wide">HK</span>
                        <span class="text-xl font-bold">HotelKu</span>
                    </a>

                    <div class="hidden max-w-lg lg:block">
                        <p class="text-xs font-semibold uppercase text-white/75">Ruang untuk perjalanan Anda</p>
                        <h2 class="mt-3 text-3xl font-bold leading-tight xl:text-4xl">Singgah nyaman. Jelajahi lebih jauh.</h2>
                        <p class="mt-4 max-w-md text-sm leading-relaxed text-white/80">
                            Temukan tempat menginap yang tepat dan nikmati setiap cerita di sepanjang perjalanan.
                        </p>
                    </div>

                    <p class="hidden text-xs text-white/65 lg:block">© {{ date('Y') }} HotelKu · Jelajahi Indonesia</p>
                </div>
            </aside>

            <section class="flex items-center justify-center px-5 py-8 sm:px-10 sm:py-12 lg:px-14">
                <div class="w-full max-w-md">
                    <a href="{{ route('home') }}" class="mb-7 inline-flex text-sm font-medium text-gray-500 transition hover:text-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 lg:hidden">
                        ← Kembali ke beranda
                    </a>

                    {{ $slot }}

                    <p class="mt-8 hidden text-xs text-gray-400 lg:block">
                        Ingin kembali menjelajah? <a href="{{ route('home') }}" class="font-medium text-emerald-800 hover:underline">Kunjungi beranda HotelKu</a>.
                    </p>
                </div>
            </section>
        </main>
    </body>
</html>
