<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

/** Menandai booking pending_payment yang melewati batas bayar sebagai kedaluwarsa. */
class ExpireBookings extends Command
{
    protected $signature = 'bookings:expire';

    protected $description = 'Mengubah booking pending_payment yang melewati expires_at menjadi expired dan mengembalikan kuota promonya';

    public function handle(BookingService $bookings): int
    {
        $count = $bookings->expireOverdue();

        if ($count > 0) {
            $this->info("{$count} booking ditandai kedaluwarsa.");
        } else {
            $this->info('Tidak ada booking yang kedaluwarsa.');
        }

        return self::SUCCESS;
    }
}
