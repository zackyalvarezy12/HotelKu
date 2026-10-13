<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\RoomType;
use App\Models\User;
use App\Services\BookingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Command bookings:expire: booking pending_payment yang lewat batas bayar jadi expired. */
class BookingExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected User $guest;

    protected Hotel $hotel;

    protected RoomType $roomType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->guest = User::factory()->guest()->create();
        $this->hotel = Hotel::factory()->create();
        $this->roomType = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'total_rooms' => 5,
        ]);
    }

    private function booking(BookingStatus $status, mixed $expiresAt): Booking
    {
        return Booking::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'user_id' => $this->guest->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'rooms_count' => 1,
            'status' => $status,
            'expires_at' => $expiresAt,
        ]);
    }

    public function test_booking_pending_yang_lewat_batas_bayar_menjadi_expired(): void
    {
        $booking = $this->booking(BookingStatus::PendingPayment, now()->subHour());

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);
    }

    public function test_booking_pending_yang_belum_lewat_batas_bayar_tidak_diubah(): void
    {
        $booking = $this->booking(BookingStatus::PendingPayment, now()->addHours(3));

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame(BookingStatus::PendingPayment, $booking->fresh()->status);
    }

    public function test_booking_pending_tanpa_expires_at_tidak_diubah(): void
    {
        $booking = $this->booking(BookingStatus::PendingPayment, null);

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame(BookingStatus::PendingPayment, $booking->fresh()->status);
    }

    public function test_booking_yang_sudah_dikonfirmasi_tidak_ikut_kedaluwarsa(): void
    {
        $confirmed = $this->booking(BookingStatus::Confirmed, now()->subDay());
        $checkedIn = $this->booking(BookingStatus::CheckedIn, now()->subDay());
        $completed = $this->booking(BookingStatus::Completed, now()->subDay());

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame(BookingStatus::Confirmed, $confirmed->fresh()->status);
        $this->assertSame(BookingStatus::CheckedIn, $checkedIn->fresh()->status);
        $this->assertSame(BookingStatus::Completed, $completed->fresh()->status);
    }

    public function test_batas_waktu_tepat_sama_dianggap_kedaluwarsa(): void
    {
        $batas = now()->subSecond();
        $booking = $this->booking(BookingStatus::PendingPayment, $batas);

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);
    }

    public function test_hanya_booking_yang_lewat_batas_yang_diproses(): void
    {
        $lewat = $this->booking(BookingStatus::PendingPayment, now()->subDay());
        $belum = $this->booking(BookingStatus::PendingPayment, now()->addDay());

        $count = app(BookingService::class)->expireOverdue();

        $this->assertSame(1, $count);
        $this->assertSame(BookingStatus::Expired, $lewat->fresh()->status);
        $this->assertSame(BookingStatus::PendingPayment, $belum->fresh()->status);
    }

    public function test_kuota_promo_dikembalikan_saat_booking_kedaluwarsa(): void
    {
        $promotion = Promotion::factory()->voucher('UJI50')->create([
            'hotel_id' => $this->hotel->id,
            'quota' => 10,
            'used_count' => 1,
        ]);

        $booking = $this->booking(BookingStatus::PendingPayment, now()->subHour());
        $booking->update(['promotion_id' => $promotion->id, 'voucher_code' => 'UJI50']);

        PromotionUsage::create([
            'promotion_id' => $promotion->id,
            'booking_id' => $booking->id,
            'user_id' => $this->guest->id,
            'discount_amount' => 50000,
            'used_at' => now(),
        ]);

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame(BookingStatus::Expired, $booking->fresh()->status);
        $this->assertSame(0, $promotion->fresh()->used_count);
        // Baris pemakaian dihapus agar kuota_per_user tamu tidak ikut terpakai.
        $this->assertDatabaseMissing('promotion_usages', [
            'promotion_id' => $promotion->id,
            'booking_id' => $booking->id,
        ]);
    }

    public function test_kuota_promo_tidak_pernah_negatif(): void
    {
        $promotion = Promotion::factory()->voucher('UJI0')->create([
            'hotel_id' => $this->hotel->id,
            'quota' => 5,
            'used_count' => 0,
        ]);

        $booking = $this->booking(BookingStatus::PendingPayment, now()->subHour());
        PromotionUsage::create([
            'promotion_id' => $promotion->id,
            'booking_id' => $booking->id,
            'user_id' => $this->guest->id,
            'discount_amount' => 10000,
            'used_at' => now(),
        ]);

        $this->artisan('bookings:expire')->assertSuccessful();

        $this->assertSame(0, $promotion->fresh()->used_count);
    }

    public function test_command_tenang_saat_tidak_ada_yang_kedaluwarsa(): void
    {
        $this->booking(BookingStatus::PendingPayment, now()->addDay());

        $this->artisan('bookings:expire')
            ->expectsOutput('Tidak ada booking yang kedaluwarsa.')
            ->assertSuccessful();
    }

    public function test_command_melaporkan_jumlah_booking_yang_kedaluwarsa(): void
    {
        $this->booking(BookingStatus::PendingPayment, now()->subDay());
        $this->booking(BookingStatus::PendingPayment, now()->subDay());

        $this->artisan('bookings:expire')
            ->expectsOutput('2 booking ditandai kedaluwarsa.')
            ->assertSuccessful();
    }

    public function test_command_terjadwal_setiap_lima_menit(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'bookings:expire'));

        $this->assertNotNull($event, 'Command bookings:expire belum dijadwalkan di app/Console/Kernel.php.');
        $this->assertSame('*/5 * * * *', $event->expression);
    }

    /** Verifikasi lewat CLI sungguhan (bukan hanya objek Schedule). */
    public function test_command_benar_benar_tampil_di_daftar_jadwal_cli(): void
    {
        $process = new Process([PHP_BINARY, 'artisan', 'schedule:list'], base_path());
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString('bookings:expire', $process->getOutput());
        $this->assertStringContainsString('*/5 * * * *', $process->getOutput());
    }
}
