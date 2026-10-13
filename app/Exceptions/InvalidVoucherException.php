<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dipakai bila kode voucher tidak berlaku pada pemesanan yang sedang dibuat:
 * kode tidak ditemukan, hotel/tipe kamar tidak sama, periode selesai,
 * minimum malam/transaksi belum terpenuhi, atau kuota habis.
 */
class InvalidVoucherException extends RuntimeException {}
