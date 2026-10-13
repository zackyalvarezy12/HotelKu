<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', $title ?? 'HotelKu — Cari & Pesan Hotel')</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('styles')
    </head>
    <body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
        @auth
            @php($unreadChatCount = \App\Models\Conversation::unreadTotalFor(auth()->user()))
        @endauth
        <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur" x-data="{ open: false }">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-indigo-700">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5" />
                    </svg>
                    <span class="text-lg">HotelKu</span>
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium text-gray-600 md:flex">
                    <a href="{{ route('home') }}" class="hover:text-indigo-700">Beranda</a>
                    <a href="{{ route('hotels.index') }}" class="hover:text-indigo-700">Cari Hotel</a>
                    @auth
                        <a href="{{ route('chat.inbox') }}" class="inline-flex items-center gap-1.5 hover:text-indigo-700">
                            Kotak Pesan
                            @if ($unreadChatCount > 0)
                                <span class="rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $unreadChatCount > 9 ? '9+' : $unreadChatCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('bookings.index') }}" class="hover:text-indigo-700">Booking Saya</a>
                    @endauth
                </nav>

                <div class="hidden items-center gap-2 md:flex">
                    @auth
                        @if (auth()->user()->isSuperAdmin() || auth()->user()->isOwner() || auth()->user()->isStaff())
                            <a href="{{ auth()->user()->isSuperAdmin() ? url('/admin') : url('/owner') }}" class="btn-secondary">
                                Panel
                            </a>
                        @endif
                        <a href="{{ route('dashboard') }}" class="btn-secondary">Akun Saya</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary">Masuk</a>
                        <a href="{{ route('register') }}" class="btn-primary">Daftar</a>
                    @endauth
                </div>

                <button type="button"
                        class="inline-flex items-center rounded-lg p-2 text-gray-600 hover:bg-gray-100 md:hidden"
                        @click="open = ! open"
                        :aria-expanded="open.toString()"
                        aria-label="Buka menu">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Menu mobile --}}
            <div x-show="open" x-cloak x-transition class="border-t border-gray-200 bg-white md:hidden">
                <nav class="mx-auto max-w-7xl space-y-1 px-4 py-3 text-sm font-medium">
                    <a href="{{ route('home') }}" class="block rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-50">Beranda</a>
                    <a href="{{ route('hotels.index') }}" class="block rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-50">Cari Hotel</a>

                    @auth
                        <a href="{{ route('chat.inbox') }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-50">
                            <span>Kotak Pesan</span>
                            @if ($unreadChatCount > 0)
                                <span class="rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $unreadChatCount > 9 ? '9+' : $unreadChatCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('bookings.index') }}" class="block rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-50">Booking Saya</a>
                        @if (auth()->user()->isSuperAdmin() || auth()->user()->isOwner() || auth()->user()->isStaff())
                            <a href="{{ auth()->user()->isSuperAdmin() ? url('/admin') : url('/owner') }}"
                               class="block rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-50">Panel</a>
                        @endif
                        <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-50">Akun Saya</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-red-600 hover:bg-red-50">
                                Keluar
                            </button>
                        </form>
                    @else
                        <div class="flex gap-2 pt-2">
                            <a href="{{ route('login') }}" class="btn-secondary flex-1">Masuk</a>
                            <a href="{{ route('register') }}" class="btn-primary flex-1">Daftar</a>
                        </div>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-5 sm:px-6 sm:py-8 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
        </main>

        <footer class="mt-10 border-t border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-6 text-sm text-gray-500 sm:px-6 lg:px-8">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'HotelKu') }} — Sistem reservasi hotel multi-hotel.</p>
            </div>
        </footer>

        @livewireScripts
        @stack('scripts')
    </body>
</html>
