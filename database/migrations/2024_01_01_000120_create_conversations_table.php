<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu percakapan per pasangan (hotel_id, user_id) — dijaga unique.
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
