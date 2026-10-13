<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot staf hotel. Diperlukan agar peran "staff" dapat dibatasi
     * hanya pada hotel tertentu (tidak ada di daftar tabel spesifikasi,
     * tetapi dibutuhkan untuk mengimplementasikan peran staff).
     */
    public function up(): void
    {
        Schema::create('hotel_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('position', 50)->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_staff');
    }
};
