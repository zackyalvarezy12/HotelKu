<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pivot fasilitas tipe kamar (mis. AC, TV di dalam kamar). */
    public function up(): void
    {
        Schema::create('facility_room_type', function (Blueprint $table) {
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['facility_id', 'room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_room_type');
    }
};
