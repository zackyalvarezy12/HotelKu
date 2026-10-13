<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daftar unit kamar fisik (opsional). Bila kosong, sistem memakai total_rooms.
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('room_number', 20);
            $table->string('floor', 10)->nullable();
            $table->string('status', 20)->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['room_type_id', 'room_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
