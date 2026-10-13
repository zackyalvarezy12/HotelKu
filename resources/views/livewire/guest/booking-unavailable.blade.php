{{-- Hotel berhenti tampil publik (pending/suspended) saat form masih terbuka. --}}
<div class="card mx-auto max-w-lg p-8 text-center" data-testid="booking-unavailable">
    <svg class="mx-auto h-12 w-12 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
    </svg>

    <h1 class="mt-3 text-base font-semibold text-gray-900">Hotel ini sedang tidak menerima pemesanan</h1>
    <p class="mt-1 text-sm text-gray-500">
        Hotel mungkin sedang dinonaktifkan sementara. Silakan pilih hotel lain untuk melanjutkan.
    </p>

    <div class="mt-4 flex flex-col justify-center gap-2 sm:flex-row">
        <a href="{{ route('hotels.index') }}" class="btn-primary">Cari Hotel Lain</a>
        <a href="{{ route('bookings.index') }}" class="btn-secondary">Booking Saya</a>
    </div>
</div>
