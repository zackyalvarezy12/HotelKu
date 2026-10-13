<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city', 100)->index();          // kolom pencarian utama
            $table->string('address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->time('check_in_time')->default('14:00:00');
            $table->time('check_out_time')->default('12:00:00');
            $table->unsignedTinyInteger('star_rating')->default(3); // 1-5 bintang
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index gabungan untuk filter kota + status (halaman pencarian).
            $table->index(['city', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
