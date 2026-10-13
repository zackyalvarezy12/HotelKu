<div class="space-y-4">
    <div>
        <h1 class="text-lg font-bold text-gray-900 sm:text-xl">Riwayat Booking</h1>
        <p class="text-sm text-gray-500">
            {{ $totalBookings }} pemesanan atas nama Anda.
        </p>
    </div>

    {{-- Chip filter status --}}
    <div class="flex gap-2 overflow-x-auto pb-1">
        <button type="button" wire:click="$set('status', '')"
                class="shrink-0 rounded-full border px-3 py-1.5 text-sm font-medium transition
                       {{ $status === '' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
            Semua
        </button>

        @foreach (\App\Enums\BookingStatus::cases() as $case)
            @php $count = $statusCounts[$case->value] ?? 0; @endphp
            @if ($count > 0)
                <button type="button" wire:click="$set('status', '{{ $case->value }}')"
                        class="shrink-0 rounded-full border px-3 py-1.5 text-sm font-medium transition
                               {{ $status === $case->value ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                    {{ $case->label() }}
                    <span class="text-xs text-gray-400">({{ $count }})</span>
                </button>
            @endif
        @endforeach
    </div>

    {{-- Daftar booking --}}
    <div class="space-y-3">
        @forelse ($bookings as $booking)
            @php
                $deadline = $booking->expires_at;
                $lewatBatasWaktu = $booking->status === \App\Enums\BookingStatus::PendingPayment
                    && $deadline !== null
                    && $deadline->isPast();
            @endphp

            <article class="card overflow-hidden">
                <div class="flex flex-col gap-3 p-4 sm:flex-row">
                    <div class="h-28 w-full shrink-0 overflow-hidden rounded-lg bg-gray-100 sm:h-24 sm:w-32">
                        @if ($booking->hotel?->primaryPhoto)
                            <img src="{{ $booking->hotel->primaryPhoto->url }}" alt="Foto {{ $booking->hotel->name }}"
                                 class="h-full w-full object-cover" loading="lazy">
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-semibold text-gray-900">
                                    {{ $booking->hotel?->name ?? 'Hotel tidak tersedia' }}
                                </h2>
                                <p class="text-xs text-gray-500">
                                    {{ $booking->hotel?->city }}
                                    · {{ $booking->roomType?->name }}
                                </p>
                            </div>

                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold
                                @class([
                                    'bg-amber-50 text-amber-700' => $booking->status === \App\Enums\BookingStatus::PendingPayment,
                                    'bg-blue-50 text-blue-700' => $booking->status === \App\Enums\BookingStatus::Confirmed,
                                    'bg-indigo-50 text-indigo-700' => $booking->status === \App\Enums\BookingStatus::CheckedIn,
                                    'bg-green-50 text-green-700' => $booking->status === \App\Enums\BookingStatus::Completed,
                                    'bg-red-50 text-red-700' => $booking->status === \App\Enums\BookingStatus::Cancelled,
                                    'bg-gray-100 text-gray-600' => $booking->status === \App\Enums\BookingStatus::Expired,
                                ])">
                                {{ $booking->status->label() }}
                            </span>
                        </div>

                        <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-gray-600 sm:grid-cols-4">
                            <div>
                                <dt class="text-gray-400">Check-in</dt>
                                <dd class="font-medium">{{ $booking->check_in->translatedFormat('d M Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400">Check-out</dt>
                                <dd class="font-medium">{{ $booking->check_out->translatedFormat('d M Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400">Durasi</dt>
                                <dd class="font-medium">{{ $booking->nights }} malam · {{ $booking->rooms_count }} kamar</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400">Tamu</dt>
                                <dd class="font-medium">{{ $booking->guests }} orang</dd>
                            </div>
                        </dl>

                        <p class="mt-2 font-mono text-xs text-gray-500">{{ $booking->code }}</p>

                        @if ($booking->status === \App\Enums\BookingStatus::PendingPayment && $deadline !== null)
                            <p class="mt-1 rounded-md px-2 py-1 text-xs font-medium
                                      {{ $lewatBatasWaktu ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }}">
                                @if ($lewatBatasWaktu)
                                    Batas waktu pembayaran sudah lewat, booking akan segera kedaluwarsa.
                                @else
                                    Selesaikan pembayaran sebelum
                                    {{ $deadline->translatedFormat('d M Y, H:i') }} WIB.
                                @endif
                            </p>
                        @endif
                    </div>

                    <div class="shrink-0 border-t border-gray-100 pt-3 text-right sm:border-l sm:border-t-0 sm:pl-4 sm:pt-0">
                        <p class="text-xs text-gray-400">Total</p>
                        <p class="text-base font-bold text-indigo-700">
                            Rp{{ number_format((float) $booking->total_price, 0, ',', '.') }}
                        </p>
                        @if ((float) $booking->discount_amount > 0)
                            <p class="text-xs text-green-600">
                                Hemat Rp{{ number_format((float) $booking->discount_amount, 0, ',', '.') }}
                            </p>
                        @endif
                        @if ($booking->status === \App\Enums\BookingStatus::Completed)
                            @if ($booking->review)
                                <p class="mt-3 text-xs font-medium text-emerald-700">Ulasan terkirim · {{ $booking->review->rating }}/5</p>
                            @else
                                <a href="{{ route('bookings.review', $booking->id) }}"
                                   class="btn-secondary mt-3 inline-flex w-full justify-center sm:w-auto">
                                    Tulis Ulasan
                                </a>
                            @endif
                        @endif
                        <a href="{{ route('bookings.show', $booking->id) }}"
                           class="btn-secondary mt-3 inline-flex w-full justify-center sm:w-auto">
                            Detail Booking
                        </a>
                        <a href="{{ route('chat.inbox', ['booking' => $booking->id]) }}"
                           class="btn-secondary mt-3 inline-flex w-full justify-center sm:w-auto">
                            Chat Pemilik
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="card p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z" />
                </svg>
                <h2 class="mt-3 text-base font-semibold text-gray-900">
                    {{ $status === '' ? 'Belum ada booking' : 'Tidak ada booking dengan status ini' }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    @if ($status === '')
                        Yuk cari hotel dan mulai pesan kamar pertama Anda.
                    @else
                        Coba pilih filter "Semua" untuk melihat seluruh pemesanan.
                    @endif
                </p>
                <a href="{{ route('hotels.index') }}" class="btn-primary mt-4">Cari Hotel</a>
            </div>
        @endforelse
    </div>

    @if ($bookings->hasMorePages())
        <div class="text-center">
            <button type="button" wire:click="loadMore" class="btn-secondary w-full sm:w-auto">
                Muat lebih banyak ({{ $bookings->total() - $bookings->count() }} lagi)
            </button>
        </div>
    @endif
</div>
