<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas Waktu Pembayaran (jam)
    |--------------------------------------------------------------------------
    | Booking berstatus `pending_payment` akan otomatis menjadi `expired`
    | setelah melewati batas waktu ini (diproses oleh command bookings:expire).
    */

    'payment_expiry_hours' => (int) env('BOOKING_PAYMENT_EXPIRY_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Prefiks Kode Booking
    |--------------------------------------------------------------------------
    | Kode booking berformat {prefiks}-YYMMDD-XXXXXX.
    */

    'code_prefix' => env('BOOKING_CODE_PREFIX', 'BK'),

];
