<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // Satu booking hanya bisa diulas satu kali.
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->text('reply')->nullable();     // balasan pemilik hotel
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
