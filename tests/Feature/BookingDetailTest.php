<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Owner\Resources\BookingResource\Pages\ListBookings;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use App\Notifications\PaymentProofRejected;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BookingDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $guest;

    private Hotel $hotel;

    private RoomType $roomType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->guest = User::factory()->guest()->create();
        $this->hotel = Hotel::factory()->create(['name' => 'Hotel Detail Uji']);
        $this->roomType = RoomType::factory()->create(['hotel_id' => $this->hotel->id, 'name' => 'Kamar Detail']);
    }

    public function test_tamu_melihat_detail_booking_dan_tombol_chat_pemilik(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'code' => 'BK-DETAIL-0001',
        ]);

        $this->actingAs($this->guest)
            ->get(route('bookings.show', $booking->id))
            ->assertSuccessful()
            ->assertSee('BK-DETAIL-0001')
            ->assertSee('Hotel Detail Uji')
            ->assertSee('Kamar Detail')
            ->assertSee('Chat Pemilik');
    }

    public function test_tamu_tidak_dapat_membuka_detail_booking_tamu_lain(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
        ]);
        $otherGuest = User::factory()->guest()->create();

        $this->actingAs($otherGuest)
            ->get(route('bookings.show', $booking->id))
            ->assertNotFound();
    }

    public function test_tamu_dapat_mengunggah_bukti_pembayaran_untuk_booking_pending(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'code' => 'BK-PAY-0001',
            'status' => BookingStatus::PendingPayment,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($this->guest)
            ->post(route('bookings.payment.store', $booking), [
                'method' => PaymentMethod::BankTransfer->value,
                'notes' => 'Transfer via BCA',
                'proof' => UploadedFile::fake()->image('bukti.jpg'),
            ])
            ->assertRedirect(route('bookings.show', $booking));

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'method' => PaymentMethod::BankTransfer->value,
            'status' => 'pending',
            'notes' => 'Transfer via BCA',
        ]);

        $payment = $booking->payments()->firstOrFail();
        Storage::disk('local')->assertExists($payment->proof_path);
        Storage::disk('public')->assertMissing($payment->proof_path);
    }

    public function test_owner_dapat_mengesahkan_pembayaran_booking(): void
    {
        $owner = User::factory()->owner()->create();
        $this->hotel->update(['owner_id' => $owner->id]);

        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'status' => BookingStatus::PendingPayment,
        ]);

        $payment = $booking->payments()->create([
            'method' => PaymentMethod::BankTransfer,
            'amount' => $booking->total_price,
            'proof_path' => 'payment-proofs/test.jpg',
            'status' => PaymentStatus::Pending,
            'notes' => 'Transfer dari BCA',
        ]);

        $this->actingAs($owner)
            ->post(route('owner.bookings.payment.confirm', $booking))
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Paid->value,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Confirmed->value,
        ]);
    }

    public function test_owner_bisa_melihat_bukti_transfer_di_panel_booking(): void
    {
        Storage::fake('local');
        $owner = User::factory()->owner()->create();
        $this->hotel->update(['owner_id' => $owner->id]);

        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'status' => BookingStatus::PendingPayment,
        ]);

        $proofPath = UploadedFile::fake()->image('test.jpg')->store('payment-proofs', 'local');
        $payment = $booking->payments()->create([
            'method' => PaymentMethod::BankTransfer,
            'amount' => $booking->total_price,
            'proof_path' => $proofPath,
            'status' => PaymentStatus::Pending,
            'notes' => 'Transfer dari BCA',
        ]);

        $this->actingAs($owner)
            ->get('/owner/bookings')
            ->assertOk()
            ->assertSee('Bukti Transfer')
            ->assertSee(route('owner.payments.proof', $payment));

        $this->get(route('owner.payments.proof', $payment))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_owner_tidak_bisa_melihat_bukti_transfer_hotel_lain(): void
    {
        Storage::fake('local');
        $owner = User::factory()->owner()->create();
        $otherOwner = User::factory()->owner()->create();

        $this->hotel->update(['owner_id' => $owner->id]);
        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
        ]);
        $proofPath = UploadedFile::fake()->image('test.jpg')->store('payment-proofs', 'local');
        $payment = $booking->payments()->create([
            'method' => PaymentMethod::BankTransfer,
            'amount' => $booking->total_price,
            'proof_path' => $proofPath,
            'status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($otherOwner)
            ->get(route('owner.payments.proof', $payment))
            ->assertNotFound();
    }

    public function test_command_memindahkan_bukti_lama_dari_storage_public(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
        ]);
        $proofPath = UploadedFile::fake()->image('legacy.jpg')->store('payment-proofs', 'public');
        $booking->payments()->create([
            'method' => PaymentMethod::BankTransfer,
            'amount' => $booking->total_price,
            'proof_path' => $proofPath,
            'status' => PaymentStatus::Pending,
        ]);

        $this->artisan('payments:secure-proofs')
            ->expectsOutput('Secured 1 payment proof(s).')
            ->assertSuccessful();

        Storage::disk('local')->assertExists($proofPath);
        Storage::disk('public')->assertMissing($proofPath);
    }

    public function test_owner_dapat_menolak_bukti_dan_tamu_dapat_mengunggah_ulang(): void
    {
        Storage::fake('local');
        $owner = User::factory()->owner()->create();
        $this->hotel->update(['owner_id' => $owner->id]);

        $booking = Booking::factory()->create([
            'user_id' => $this->guest->id,
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'status' => BookingStatus::PendingPayment,
        ]);
        $proofPath = UploadedFile::fake()->image('initial.jpg')->store('payment-proofs', 'local');
        $payment = $booking->payments()->create([
            'method' => PaymentMethod::BankTransfer,
            'amount' => $booking->total_price,
            'proof_path' => $proofPath,
            'status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('owner'));
        Livewire::test(ListBookings::class)
            ->assertTableActionVisible('reject_payment', $booking)
            ->callTableAction('reject_payment', $booking, data: [
                'rejection_reason' => 'Bukti transfer tidak terbaca.',
            ])
            ->assertHasNoTableActionErrors()
            ->assertTableActionHidden('confirm_payment', $booking->fresh());

        $payment->refresh();
        $this->assertSame(PaymentStatus::Rejected, $payment->status);
        $this->assertSame('Bukti transfer tidak terbaca.', $payment->rejection_reason);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->guest->id,
            'type' => PaymentProofRejected::class,
        ]);
        $this->assertFalse($booking->fresh()->confirmPayment());

        $this->actingAs($this->guest)
            ->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee('Bukti pembayaran ditolak')
            ->assertSee('Bukti transfer tidak terbaca.')
            ->assertSee('Unggah Ulang Bukti');

        $this->get(route('bookings.payment.form', $booking))
            ->assertOk()
            ->assertSee('Bukti transfer tidak terbaca.');

        $paymentData = [
            'method' => PaymentMethod::BankTransfer->value,
            'proof' => UploadedFile::fake()->image('replacement.jpg'),
        ];

        $this->post(route('bookings.payment.store', $booking), $paymentData)
            ->assertRedirect(route('bookings.show', $booking));

        $replacement = $booking->fresh()->latestPayment;
        $this->assertNotSame($payment->id, $replacement->id);
        $this->assertSame(PaymentStatus::Pending, $replacement->status);
        $this->assertSame(2, $booking->payments()->count());

        $this->post(route('bookings.payment.store', $booking), $paymentData)
            ->assertRedirect(route('bookings.show', $booking));

        $this->assertSame(2, $booking->payments()->count());
    }
}