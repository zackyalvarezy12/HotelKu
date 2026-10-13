<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentProofRejected extends Notification
{
    use Queueable;

    public function __construct(private readonly Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->payment->loadMissing('booking');

        return [
            'title' => 'Bukti pembayaran ditolak',
            'message' => $this->payment->rejection_reason,
            'booking_code' => $this->payment->booking->code,
            'url' => route('bookings.show', $this->payment->booking),
        ];
    }
}