<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingPaymentController extends Controller
{
    public function show(Booking $booking): View
    {
        $this->ensureOwnBooking($booking);
        $booking->load('latestPayment');

        return view('bookings.payment', [
            'booking' => $booking,
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureOwnBooking($booking);

        if ($booking->status !== BookingStatus::PendingPayment) {
            return redirect()->route('bookings.show', $booking)
                ->with('error', 'Booking ini tidak memerlukan pembayaran lagi.');
        }

        $latestPayment = $booking->latestPayment;
        if ($latestPayment && $latestPayment->status !== PaymentStatus::Rejected) {
            return redirect()->route('bookings.show', $booking)
                ->with('error', 'Bukti pembayaran sebelumnya masih diproses atau booking ini sudah dibayar.');
        }

        $method = $request->validate([
            'method' => ['required', Rule::in(array_keys(PaymentMethod::options()))],
            'notes' => ['nullable', 'string', 'max:500'],
            'proof' => [$request->input('method') !== PaymentMethod::Cash->value ? 'required' : 'nullable', 'file', 'image', 'max:2048'],
        ]);

        $proofPath = null;
        if (($method['method'] ?? null) !== PaymentMethod::Cash->value && $request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payment-proofs', 'local');
        }

        Payment::create([
            'booking_id' => $booking->id,
            'method' => $method['method'],
            'amount' => $booking->total_price,
            'proof_path' => $proofPath,
            'status' => PaymentStatus::Pending,
            'notes' => $method['notes'] ?? null,
        ]);

        return redirect()->route('bookings.show', $booking)
            ->with('status', 'Bukti pembayaran berhasil dikirim. Menunggu konfirmasi dari pemilik hotel.');
    }

    public function showProof(Payment $payment)
    {
        $user = auth()->user();
        $booking = $payment->booking;

        abort_unless($user && ($user->isOwner() || $user->isStaff()), 403);
        abort_unless($booking?->hotel, 404);
        abort_unless(in_array((int) $booking->hotel_id, array_map('intval', $user->managedHotelIds() ?? []), true), 403);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        $filePath = Storage::disk('local')->path($payment->proof_path);

        return response()->stream(function () use ($filePath): void {
            $stream = fopen($filePath, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => mime_content_type($filePath) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.basename($payment->proof_path).'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function confirmOwner(Booking $booking): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($user && ($user->isOwner() || $user->isStaff()), 403);
        abort_unless($booking->hotel && in_array($booking->hotel_id, $user->managedHotelIds() ?? [], true), 403);

        if ($booking->status !== BookingStatus::PendingPayment) {
            return back()->with('error', 'Booking ini tidak lagi menunggu pembayaran.');
        }

        if (! $booking->confirmPayment()) {
            return back()->with('error', 'Tidak ada pembayaran yang menunggu konfirmasi.');
        }

        return back()->with('status', 'Pembayaran booking berhasil dikonfirmasi.');
    }

    private function ensureOwnBooking(Booking $booking): void
    {
        abort_if((int) $booking->user_id !== (int) auth()->id(), 404);
    }
}
