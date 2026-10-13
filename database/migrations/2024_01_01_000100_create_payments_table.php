<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('method', 30);
            $table->decimal('amount', 12, 2);
            $table->string('proof_path')->nullable();   // bukti transfer
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('gateway_ref')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
