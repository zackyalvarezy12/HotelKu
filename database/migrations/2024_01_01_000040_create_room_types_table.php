<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_price', 12, 2);
            $table->unsignedTinyInteger('capacity')->default(2);   // jumlah tamu per kamar
            $table->string('bed_type', 50)->nullable();            // mis. King Bed
            $table->unsignedSmallInteger('size_m2')->nullable();   // luas kamar
            $table->unsignedSmallInteger('total_rooms');           // stok kamar tersedia
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hotel_id', 'is_active']);
            $table->index('base_price'); // filter harga
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
