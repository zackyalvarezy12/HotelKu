<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Voucher Booking {{ $booking->code }}</title>
    <style>
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        .header { border-bottom: 2px solid #047857; margin-bottom: 24px; padding-bottom: 16px; }
        .brand { color: #047857; font-size: 20px; font-weight: bold; }
        .muted { color: #6b7280; }
        .code { background: #ecfdf5; border: 1px solid #a7f3d0; font-size: 16px; font-weight: bold; padding: 12px; }
        h2 { font-size: 15px; margin: 22px 0 8px; }
        table { border-collapse: collapse; width: 100%; }
        td { border-bottom: 1px solid #e5e7eb; padding: 9px 6px; vertical-align: top; }
        td:first-child { color: #6b7280; width: 34%; }
        .total { color: #047857; font-size: 15px; font-weight: bold; }
        .footer { border-top: 1px solid #e5e7eb; margin-top: 28px; padding-top: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">HotelKu</div>
        <div class="muted">Voucher konfirmasi reservasi</div>
    </div>

    <div class="code">Kode Booking: {{ $booking->code }}</div>

    <h2>Informasi Hotel</h2>
    <table>
        <tr><td>Hotel</td><td>{{ $booking->hotel?->name ?? '-' }}</td></tr>
        <tr><td>Alamat</td><td>{{ $booking->hotel?->address ?? '-' }}{{ $booking->hotel?->city ? ', '.$booking->hotel->city : '' }}</td></tr>
        <tr><td>Tipe kamar</td><td>{{ $booking->roomType?->name ?? '-' }}</td></tr>
    </table>

    <h2>Informasi Tamu dan Menginap</h2>
    <table>
        <tr><td>Nama tamu</td><td>{{ $booking->guest_name }}</td></tr>
        <tr><td>Telepon</td><td>{{ $booking->guest_phone ?: '-' }}</td></tr>
        <tr><td>Check-in</td><td>{{ $booking->check_in->translatedFormat('d F Y') }}</td></tr>
        <tr><td>Check-out</td><td>{{ $booking->check_out->translatedFormat('d F Y') }}</td></tr>
        <tr><td>Durasi</td><td>{{ $booking->nights }} malam</td></tr>
        <tr><td>Kamar dan tamu</td><td>{{ $booking->rooms_count }} kamar, {{ $booking->guests }} orang</td></tr>
        <tr><td>Status</td><td>{{ $booking->status->label() }}</td></tr>
    </table>

    <h2>Rincian Pembayaran</h2>
    <table>
        <tr><td>Subtotal</td><td>Rp{{ number_format((float) $booking->subtotal, 0, ',', '.') }}</td></tr>
        @if ((float) $booking->discount_amount > 0)
            <tr><td>Voucher / promo</td><td>{{ $booking->voucher_code ?: $booking->promotion?->name ?: 'Promo' }} (−Rp{{ number_format((float) $booking->discount_amount, 0, ',', '.') }})</td></tr>
        @endif
        <tr><td>Total dibayar</td><td class="total">Rp{{ number_format((float) $booking->total_price, 0, ',', '.') }}</td></tr>
        <tr><td>Metode</td><td>{{ $booking->latestPayment?->method?->label() ?? '-' }}</td></tr>
    </table>

    <div class="footer muted">
        Tunjukkan voucher ini kepada petugas hotel saat check-in.<br>
        Dibuat {{ $generatedAt->translatedFormat('d F Y, H:i') }}.
    </div>
</body>
</html>