<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Booking Hotel</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        p { color: #6b7280; margin: 0 0 16px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #ecfdf5; }
        .number { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
    <h1>Laporan Booking</h1>
    <p>Dibuat {{ $generatedAt->translatedFormat('d M Y, H:i') }} · {{ $bookings->count() }} booking</p>
    <table>
        <thead>
            <tr>
                <th>Kode</th><th>Hotel</th><th>Tamu</th><th>Check-in</th><th>Check-out</th><th>Status</th>
                <th class="number">Subtotal</th><th class="number">Diskon</th><th class="number">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookings as $booking)
                <tr>
                    <td>{{ $booking->code }}</td>
                    <td>{{ $booking->hotel?->name }}</td>
                    <td>{{ $booking->guest_name }}</td>
                    <td>{{ $booking->check_in->format('d/m/Y') }}</td>
                    <td>{{ $booking->check_out->format('d/m/Y') }}</td>
                    <td>{{ $booking->status->label() }}</td>
                    <td class="number">Rp{{ number_format((float) $booking->subtotal, 0, ',', '.') }}</td>
                    <td class="number">Rp{{ number_format((float) $booking->discount_amount, 0, ',', '.') }}</td>
                    <td class="number">Rp{{ number_format((float) $booking->total_price, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="9">Belum ada booking.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>