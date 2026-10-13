<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();  // kode booking, mis. HTL-20240101-XXXX
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();

            $table->string('guest_name');
            $table->string('guest_phone', 30);
            $table->date('check_in')->index();
            $table->date('check_out')->index();
            $table->unsignedSmallInteger('nights');
            $table->unsignedSmallInteger('guests');
            $table->unsignedSmallInteger('rooms_count')->default(1);

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);

            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('voucher_code', 50)->nullable();
            $table->text('special_request')->nullable();

            $table->string('status', 30)->default('pending_payment')->index();
            $table->timestamp('expires_at')->nullable()->index(); // batas bayar (default 24 jam)
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            // Index komposit untuk pengecekan ketersediaan (irisan rentang tanggal).
            $table->index(['room_type_id', 'status', 'check_in', 'check_out'], 'bookings_availability_index');
            $table->index(['hotel_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
