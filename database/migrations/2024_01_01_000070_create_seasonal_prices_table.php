<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Harga musiman: menimpa base_price pada rentang tanggal tertentu.
        Schema::create('seasonal_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->decimal('price', 12, 2);
            $table->string('label', 100)->nullable(); // mis. "Libur Lebaran"
            $table->timestamps();

            $table->index(['room_type_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasonal_prices');
    }
};
