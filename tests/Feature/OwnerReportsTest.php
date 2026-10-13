<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Exports\BookingsExport;
use App\Filament\Owner\Widgets\OwnerOverview;
use App\Filament\Owner\Widgets\PromotionUsageReport;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Hotel $hotel;

    private RoomType $roomType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::factory()->owner()->create();
        $this->hotel = Hotel::factory()->create(['owner_id' => $this->owner->id]);
        $this->roomType = RoomType::factory()->create([
            'hotel_id' => $this->hotel->id,
            'total_rooms' => 4,
        ]);
    }

    public function test_owner_melihat_dashboard_dan_halaman_laporan(): void
    {
        Booking::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'status' => BookingStatus::Confirmed,
        ]);

        $this->actingAs($this->owner);
        Filament::setCurrentPanel(Filament::getPanel('owner'));

        $this->get('/owner')
            ->assertSuccessful();

        Livewire::test(OwnerOverview::class)
            ->assertSee('Okupansi hari ini')
            ->assertSee('Pendapatan bulan ini');

        Livewire::test(PromotionUsageReport::class)
            ->assertSee('Pemakaian Promosi');

        $this->get('/owner/reports')
            ->assertSuccessful()
            ->assertSee('Unduh Excel')
            ->assertSee('Unduh PDF');
    }

    public function test_owner_dashboard_hari_ini_tidak_gagal_tanpa_pembayaran(): void
    {
        $this->actingAs($this->owner);
        Filament::setCurrentPanel(Filament::getPanel('owner'));

        Livewire::test(OwnerOverview::class)
            ->assertSee('Booking dibuat hari ini');
    }

    public function test_owner_dapat_mengunduh_excel_dan_pdf_booking(): void
    {
        Booking::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'code' => 'BK-OWNER-EXPORT',
        ]);

        $this->actingAs($this->owner)
            ->get(route('owner.reports.excel'))
            ->assertSuccessful()
            ->assertHeader('content-disposition');

        $this->actingAs($this->owner)
            ->get(route('owner.reports.pdf'))
            ->assertSuccessful()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_laporan_excel_menggunakan_hanya_id_hotel_kelolaan(): void
    {
        $booking = Booking::factory()->create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'code' => 'BK-MILIK-OWNER',
        ]);
        $otherOwner = User::factory()->owner()->create();
        $otherHotel = Hotel::factory()->create(['owner_id' => $otherOwner->id]);
        $otherRoom = RoomType::factory()->create(['hotel_id' => $otherHotel->id]);
        Booking::factory()->create([
            'hotel_id' => $otherHotel->id,
            'room_type_id' => $otherRoom->id,
            'code' => 'BK-MILIK-ORANG-LAIN',
        ]);

        $codes = (new BookingsExport([$this->hotel->id]))->query()->pluck('code')->all();

        $this->assertSame([$booking->code], $codes);
    }

    public function test_guest_tidak_dapat_mengunduh_laporan_owner(): void
    {
        $guest = User::factory()->guest()->create();

        $this->actingAs($guest)
            ->get(route('owner.reports.pdf'))
            ->assertForbidden();
    }
}