<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dipakai bila sisa kamar tidak mencukupi pada saat booking dibuat.
 * Termasuk kondisi "kamar terakhir baru saja diambil orang lain".
 */
class RoomNotAvailableException extends RuntimeException {}
