<div class="mx-auto max-w-4xl space-y-4">
    <nav class="flex items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
        <a href="{{ route('bookings.index') }}" class="hover:text-emerald-700">Booking Saya</a>
        <span>/</span>
        <span class="font-mono text-gray-700">{{ $booking->code }}</span>
    </nav>

    <section class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-mono text-xs text-gray-500">{{ $booking->code }}</p>
                <h1 class="mt-1 text-lg font-bold text-gray-900">{{ $booking->hotel?->name ?? 'Hotel tidak tersedia' }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $booking->roomType?->name }} · {{ $booking->hotel?->city }}</p>
            </div>
            <span @class([
                'rounded-full px-2.5 py-1 text-xs font-semibold',
                'bg-amber-50 text-amber-700' => $booking->status->value === 'pending_payment',
                'bg-blue-50 text-blue-700' => $booking->status->value === 'confirmed',
                'bg-indigo-50 text-indigo-700' => $booking->status->value === 'checked_in',
                'bg-green-50 text-green-700' => $booking->status->value === 'completed',
                'bg-red-50 text-red-700' => $booking->status->value === 'cancelled',
                'bg-gray-100 text-gray-600' => $booking->status->value === 'expired',
            ])>{{ $booking->status->label() }}</span>
        </div>

        <dl class="mt-5 grid grid-cols-2 gap-4 border-y border-gray-100 py-4 text-sm sm:grid-cols-4">
            <div><dt class="text-xs text-gray-400">Check-in</dt><dd class="mt-1 font-medium">{{ $booking->check_in->translatedFormat('d M Y') }}</dd></div>
            <div><dt class="text-xs text-gray-400">Check-out</dt><dd class="mt-1 font-medium">{{ $booking->check_out->translatedFormat('d M Y') }}</dd></div>
            <div><dt class="text-xs text-gray-400">Durasi</dt><dd class="mt-1 font-medium">{{ $booking->nights }} malam</dd></div>
            <div><dt class="text-xs text-gray-400">Kamar / tamu</dt><dd class="mt-1 font-medium">{{ $booking->rooms_count }} kamar · {{ $booking->guests }} orang</dd></div>
        </dl>

        <div class="ml-auto mt-4 max-w-sm space-y-2 text-sm">
            <div class="flex justify-between gap-3"><span class="text-gray-500">Subtotal</span><span>Rp{{ number_format((float) $booking->subtotal, 0, ',', '.') }}</span></div>
            @if ((float) $booking->discount_amount > 0)
                <div class="flex justify-between gap-3 text-emerald-700">
                    <span>{{ $booking->voucher_code ?: $booking->promotion?->name ?: 'Diskon promosi' }}</span>
                    <span>− Rp{{ number_format((float) $booking->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="flex justify-between gap-3 border-t border-gray-100 pt-2 font-semibold">
                <span>Total</span><span>Rp{{ number_format((float) $booking->total_price, 0, ',', '.') }}</span>
            </div>
        </div>

        @if ($booking->status->value === 'pending_payment')
            @if ($booking->latestPayment?->status === \App\Enums\PaymentStatus::Pending)
                <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Pembayaran menunggu konfirmasi</p>
                    <p class="mt-1 text-amber-800/90">Bukti sudah dikirim dan sedang diperiksa oleh pemilik hotel.</p>
                </div>
            @elseif ($booking->latestPayment?->status === \App\Enums\PaymentStatus::Rejected)
                <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">
                    <p class="font-semibold">Bukti pembayaran ditolak</p>
                    <p class="mt-1">{{ $booking->latestPayment->rejection_reason }}</p>
                    <a href="{{ route('bookings.payment.form', $booking) }}" class="btn-primary mt-3 justify-center">Unggah Ulang Bukti</a>
                </div>
            @else
                <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold">Pembayaran menunggu</p>
                            <p class="mt-1 text-amber-800/90">Silakan kirim bukti transfer agar reservasi dapat diproses.</p>
                        </div>
                        <a href="{{ route('bookings.payment.form', $booking) }}" class="btn-primary justify-center">Bayar Sekarang</a>
                    </div>
                </div>
            @endif
        @endif

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <a href="{{ route('bookings.index') }}" class="btn-secondary justify-center">Kembali ke booking</a>
            <div class="flex flex-col gap-2 sm:flex-row">
                @if ($booking->status->value === 'completed' && ! $booking->review)
                    <a href="{{ route('bookings.review', $booking->id) }}" class="btn-secondary justify-center">Tulis Ulasan</a>
                @endif
                <a href="{{ route('chat.inbox', ['booking' => $booking->id]) }}" class="btn-primary justify-center">Chat Pemilik</a>
            </div>
        </div>
    </section>
</div>