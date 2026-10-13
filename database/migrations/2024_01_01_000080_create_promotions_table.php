<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('banner')->nullable();
            $table->string('type', 20);                 // auto_discount | voucher | special
            $table->string('code', 50)->nullable();     // unik per hotel
            $table->string('discount_type', 20);        // percent | fixed | free_night
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('max_discount', 12, 2)->nullable();
            $table->unsignedTinyInteger('min_nights')->default(1);
            $table->decimal('min_total', 12, 2)->default(0);
            $table->date('booking_start')->nullable();  // periode pemesanan
            $table->date('booking_end')->nullable();
            $table->date('stay_start')->nullable();     // periode menginap
            $table->date('stay_end')->nullable();
            $table->json('valid_days')->nullable();     // [0..6] hari berlakunya (0=Minggu)
            $table->unsignedInteger('quota')->nullable();     // null = tanpa batas
            $table->unsignedInteger('quota_per_user')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->unique(['hotel_id', 'code']);
            $table->index(['hotel_id', 'is_active', 'type']);
        });

        // Tipe kamar yang berlaku untuk sebuah promo (kosong = semua tipe kamar).
        Schema::create('promotion_room_type', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['promotion_id', 'room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_room_type');
        Schema::dropIfExists('promotions');
    }
};
