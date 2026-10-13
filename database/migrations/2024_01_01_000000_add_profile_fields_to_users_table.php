<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nomor telepon tamu/staf, dipakai juga untuk konfirmasi booking.
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->timestamp('last_seen_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'avatar', 'last_seen_at']);
        });
    }
};
