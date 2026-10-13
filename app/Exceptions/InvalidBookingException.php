<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dipakai bila permintaan booking tidak valid secara aturan bisnis:
 * tanggal salah, jumlah tamu melebihi kapasitas, kamar/hotel tidak aktif.
 */
class InvalidBookingException extends RuntimeException {}
