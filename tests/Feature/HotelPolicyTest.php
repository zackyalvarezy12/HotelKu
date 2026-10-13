<?php

namespace Tests\Feature;

use App\Enums\HotelStatus;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\SeasonalPrice;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/** Isolasi data: owner/staff hanya boleh mengelola hotel miliknya. */
class HotelPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner1;

    protected User $owner2;

    protected User $admin;

    protected Hotel $hotel1;

    protected Hotel $hotel2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->owner1 = User::factory()->owner()->create();
        $this->owner2 = User::factory()->owner()->create();
        $this->admin = User::factory()->superAdmin()->create();

        $this->hotel1 = Hotel::factory()->create(['owner_id' => $this->owner1->id, 'name' => 'Hotel Satu']);
        $this->hotel2 = Hotel::factory()->create(['owner_id' => $this->owner2->id, 'name' => 'Hotel Dua']);
    }

    public function test_owner_hanya_boleh_mengubah_hotel_miliknya(): void
    {
        $this->assertTrue(Gate::forUser($this->owner1)->allows('update', $this->hotel1));
        $this->assertFalse(Gate::forUser($this->owner1)->allows('update', $this->hotel2));
    }

    public function test_owner_hanya_boleh_menghapus_hotel_miliknya(): void
    {
        $this->assertTrue(Gate::forUser($this->owner1)->allows('delete', $this->hotel1));
        $this->assertFalse(Gate::forUser($this->owner1)->allows('delete', $this->hotel2));
    }

    public function test_super_admin_boleh_mengubah_hotel_siapa_pun(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('update', $this->hotel2));
        $this->assertTrue(Gate::forUser($this->admin)->allows('delete', $this->hotel2));
    }

    public function test_owner_boleh_menambah_hotel_tetapi_staff_tidak(): void
    {
        $staff = User::factory()->staff()->create();
        $guest = User::factory()->guest()->create();

        $this->assertTrue(Gate::forUser($this->owner1)->allows('create', Hotel::class));
        $this->assertFalse(Gate::forUser($staff)->allows('create', Hotel::class));
        $this->assertFalse(Gate::forUser($guest)->allows('create', Hotel::class));
    }

    public function test_hanya_super_admin_yang_boleh_memverifikasi_hotel(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('verify', $this->hotel1));
        $this->assertFalse(Gate::forUser($this->owner1)->allows('verify', $this->hotel1));
        $this->assertFalse(Gate::forUser($this->owner2)->allows('verify', $this->hotel1));
    }

    public function test_hanya_super_admin_yang_boleh_menyuspend_hotel(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('suspend', $this->hotel1));
        $this->assertFalse(Gate::forUser($this->owner1)->allows('suspend', $this->hotel1));
    }

    public function test_verifikasi_hotel_mengubah_status_dan_mengisi_verified_at(): void
    {
        $hotel = Hotel::factory()->pending()->create(['owner_id' => $this->owner1->id]);

        $this->assertSame(HotelStatus::Pending, $hotel->status);
        $this->assertNull($hotel->verified_at);

        $hotel->markAsVerified();

        $this->assertSame(HotelStatus::Active, $hotel->fresh()->status);
        $this->assertNotNull($hotel->fresh()->verified_at);
    }

    public function test_suspend_dan_pending_kembali(): void
    {
        $this->hotel1->markAsSuspended();
        $this->assertSame(HotelStatus::Suspended, $this->hotel1->fresh()->status);

        $this->hotel1->markAsPending();
        $this->assertSame(HotelStatus::Pending, $this->hotel1->fresh()->status);
        $this->assertNull($this->hotel1->fresh()->verified_at);
    }

    public function test_owner_hanya_boleh_mengelola_tipe_kamar_hotel_miliknya(): void
    {
        $roomOwned = RoomType::factory()->create(['hotel_id' => $this->hotel1->id]);
        $roomForeign = RoomType::factory()->create(['hotel_id' => $this->hotel2->id]);

        $this->assertTrue(Gate::forUser($this->owner1)->allows('update', $roomOwned));
        $this->assertFalse(Gate::forUser($this->owner1)->allows('update', $roomForeign));
    }

    public function test_harga_musiman_diisolasi_lewat_tipe_kamar(): void
    {
        $roomOwned = RoomType::factory()->create(['hotel_id' => $this->hotel1->id]);
        $roomForeign = RoomType::factory()->create(['hotel_id' => $this->hotel2->id]);

        $priceOwned = SeasonalPrice::factory()->create(['room_type_id' => $roomOwned->id]);
        $priceForeign = SeasonalPrice::factory()->create(['room_type_id' => $roomForeign->id]);

        $this->assertTrue(Gate::forUser($this->owner1)->allows('update', $priceOwned));
        $this->assertFalse(Gate::forUser($this->owner1)->allows('update', $priceForeign));
    }

    public function test_global_scope_membatasi_query_hotel_untuk_owner(): void
    {
        $this->actingAs($this->owner1);
        $this->assertSame([$this->hotel1->id], Hotel::query()->pluck('id')->all());

        $this->actingAs($this->owner2);
        $this->assertSame([$this->hotel2->id], Hotel::query()->pluck('id')->all());

        $this->actingAs($this->admin);
        $this->assertEqualsCanonicalizing(
            [$this->hotel1->id, $this->hotel2->id],
            Hotel::query()->pluck('id')->all()
        );
    }
}
