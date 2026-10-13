<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();  // nama ikon (mis. heroicon-o-wifi)
            $table->string('category', 50)->nullable();
            $table->timestamps();
        });

        // Pivot fasilitas hotel (fasilitas umum hotel).
        Schema::create('facility_hotel', function (Blueprint $table) {
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->primary(['facility_id', 'hotel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_hotel');
        Schema::dropIfExists('facilities');
    }
};
