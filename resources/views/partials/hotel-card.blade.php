@php
    // $hotel: App\Models\Hotel  |  $availableRooms: int|null
    $cover = $hotel->primaryPhoto;
    $availableRooms = $availableRooms ?? null;
    $displayPrice = $displayPrice ?? null;
    $originalPrice = $displayPrice['original'] ?? $hotel->min_price;
    $discountedPrice = $displayPrice['discounted'] ?? null;
@endphp

<article class="card overflow-hidden transition hover:shadow-md">
    <a href="{{ route('hotels.show', $hotel) }}" class="block sm:flex">
        <div class="relative h-44 w-full shrink-0 bg-gray-100 sm:h-auto sm:w-56 lg:w-64">
            @if ($cover)
                <img src="{{ $cover->url }}" alt="Foto {{ $hotel->name }}"
                     class="h-full w-full object-cover" loading="lazy">
            @else
                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-indigo-100 to-indigo-50 text-indigo-400">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14" />
                    </svg>
                </div>
            @endif

            <span class="absolute left-2 top-2 rounded-md bg-white/95 px-2 py-0.5 text-xs font-semibold text-amber-600 shadow-sm">
                {{ str_repeat('★', $hotel->star_rating) }}
            </span>
        </div>

        <div class="flex min-w-0 flex-1 flex-col p-4">
            <h3 class="truncate text-base font-semibold text-gray-900">{{ $hotel->name }}</h3>
            <p class="mt-0.5 flex items-center gap-1 text-sm text-gray-500">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z" />
                    <circle cx="12" cy="10" r="2.5" />
                </svg>
                <span class="truncate">{{ $hotel->city }}</span>
            </p>

            @if ($hotel->facilities->isNotEmpty())
                <ul class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($hotel->facilities->take(3) as $facility)
                        <li class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $facility->name }}</li>
                    @endforeach
                    @if ($hotel->facilities->count() > 3)
                        <li class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">
                            +{{ $hotel->facilities->count() - 3 }} lagi
                        </li>
                    @endif
                </ul>
            @endif

            <div class="mt-auto flex items-end justify-between gap-3 pt-3">
                <div class="text-xs text-gray-500">
                    @if ($hotel->public_room_types_count > 0)
                        {{ $hotel->public_room_types_count }} tipe kamar tersedia
                    @else
                        Belum ada kamar tersedia
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Mulai dari</p>
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
                            Rp{{ number_format((float) $hotel->min_price, 0, ',', '.') }}
                        </p>
                    @endif
                    <p class="text-xs text-gray-400">per malam</p>
                </div>
            </div>

            @if ($availableRooms !== null)
                <p class="mt-2 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700">
                    {{ $availableRooms }} kamar tersedia untuk tanggal yang Anda pilih
                </p>
            @endif
        </div>
    </a>
</article>
