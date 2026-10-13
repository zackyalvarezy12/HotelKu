@extends('layouts.public')

@section('title', 'Pembayaran Booking')

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Pembayaran</p>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Konfirmasi Pembayaran</h1>
            </div>
            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Menunggu Pembayaran</span>
        </div>

        @if ($booking->latestPayment?->status === \App\Enums\PaymentStatus::Rejected)
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <p class="font-semibold">Bukti pembayaran ditolak</p>
                <p class="mt-1">{{ $booking->latestPayment->rejection_reason }}</p>
                <p class="mt-2 text-red-700">Silakan periksa alasan di atas, lalu unggah bukti yang benar.</p>
            </div>
        @endif

        <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
            <div class="flex items-center justify-between gap-3">
                <span>Kode Booking</span>
                <span class="font-semibold text-gray-900">{{ $booking->code }}</span>
            </div>
            <div class="mt-2 flex items-center justify-between gap-3">
                <span>Total Tagihan</span>
                <span class="font-semibold text-gray-900">Rp{{ number_format((float) $booking->total_price, 0, ',', '.') }}</span>
            </div>
        </div>

        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('bookings.payment.store', $booking) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="method" class="mb-1 block text-sm font-medium text-gray-700">Metode Pembayaran</label>
                <select name="method" id="method" class="form-select w-full" required>
                    <option value="">Pilih metode pembayaran</option>
                    @foreach ($paymentMethods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="proof" class="mb-1 block text-sm font-medium text-gray-700">Bukti Transfer</label>
                <input id="proof" name="proof" type="file" accept="image/*" class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" required>
                <p class="mt-2 text-xs text-gray-500">Upload foto bukti transfer atau pembayaran yang sudah dilakukan.</p>
            </div>

            <div>
                <label for="notes" class="mb-1 block text-sm font-medium text-gray-700">Catatan (opsional)</label>
                <textarea id="notes" name="notes" rows="3" class="form-textarea w-full" placeholder="Contoh: transfer dari rekening BCA atas nama ...">{{ old('notes') }}</textarea>
            </div>

            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-between">
                <a href="{{ route('bookings.show', $booking) }}" class="btn-secondary justify-center">Kembali</a>
                <button type="submit" class="btn-primary justify-center">Kirim Bukti Pembayaran</button>
            </div>
        </form>
    </div>
</div>
@endsection
