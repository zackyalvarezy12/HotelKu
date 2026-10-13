<?php

namespace Tests\Feature;

use App\Enums\HotelStatus;
use App\Filament\Owner\Resources\HotelResource as OwnerHotelResource;
use App\Filament\Owner\Resources\HotelResource\Pages\CreateHotel;
use App\Filament\Owner\Resources\HotelResource\Pages\EditHotel;
use App\Filament\Owner\Resources\HotelResource\RelationManagers\PhotosRelationManager;
use App\Filament\Owner\Resources\RoomTypeResource as OwnerRoomTypeResource;
use App\Filament\Owner\Resources\RoomTypeResource\Pages\EditRoomType;
use App\Filament\Owner\Resources\RoomTypeResource\RelationManagers\PhotosRelationManager as RoomTypePhotosRelationManager;
use App\Filament\Owner\Resources\SeasonalPriceResource as OwnerSeasonalPriceResource;
use App\Filament\Resources\HotelResource as AdminHotelResource;
use App\Models\Facility;
use App\Models\Hotel;
use App\Models\HotelPhoto;
use App\Models\RoomType;
use App\Models\RoomTypePhoto;
use App\Models\SeasonalPrice;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Panel Filament: isolasi data owner + aksi verifikasi super admin. */
class OwnerPanelTest extends TestCase
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

        $this->hotel1 = Hotel::factory()->create(['owner_id' => $this->owner1->id, 'name' => 'Hotel Milik Satu']);
        $this->hotel2 = Hotel::factory()->create(['owner_id' => $this->owner2->id, 'name' => 'Hotel Milik Dua']);

        // Panel default untuk pengujian komponen Livewire Filament.
        $this->usePanel('admin');
    }

    /** Menetapkan panel aktif untuk pengujian komponen Livewire Filament. */
    private function usePanel(string $panel): void
    {
        Filament::setCurrentPanel(Filament::getPanel($panel));
    }

    public function test_halaman_daftar_hotel_owner_dapat_dibuka(): void
    {
        $this->actingAs($this->owner1)
            ->get('/owner/hotels')
            ->assertSuccessful();
    }

    public function test_owner_hanya_melihat_hotel_miliknya_di_panel(): void
    {
        $this->actingAs($this->owner1)
            ->get('/owner/hotels')
            ->assertSuccessful()
            ->assertSee('Hotel Milik Satu')
            ->assertDontSee('Hotel Milik Dua');
    }

    public function test_query_resource_owner_terbatas_pada_hotel_miliknya(): void
    {
        $this->actingAs($this->owner1);
        $this->assertSame([$this->hotel1->id], OwnerHotelResource::getEloquentQuery()->pluck('id')->all());

        $this->actingAs($this->admin);
        $this->assertEqualsCanonicalizing(
            [$this->hotel1->id, $this->hotel2->id],
            OwnerHotelResource::getEloquentQuery()->pluck('id')->all()
        );
    }

    public function test_owner_tidak_dapat_membuka_halaman_ubah_hotel_orang_lain(): void
    {
        // Hotel memakai slug sebagai route key, jadi URL panel pun memakai slug.
        $this->actingAs($this->owner1)
            ->get('/owner/hotels/'.$this->hotel1->slug.'/edit')
            ->assertSuccessful();

        $this->actingAs($this->owner1)
            ->get('/owner/hotels/'.$this->hotel2->slug.'/edit')
            ->assertNotFound();

        // Panel owner hanya untuk owner/staff. Super admin memakai /admin,
        // karena form owner menetapkan owner_id = pengguna yang login.
        $this->actingAs($this->admin)
            ->get('/owner/hotels/'.$this->hotel2->slug.'/edit')
            ->assertForbidden();
    }

    public function test_owner_dapat_menambah_hotel_dan_statusnya_pending(): void
    {
        $facility = Facility::factory()->create(['name' => 'Kolam Renang Uji']);

        $this->actingAs($this->owner1);
        $this->usePanel('owner');

        Livewire::test(CreateHotel::class)
            ->fillForm([
                'name' => 'Hotel Baru Bali',
                'city' => 'Bali',
                'address' => 'Jl. Pantai Kuta No. 1',
                'star_rating' => 4,
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
                'facilities' => [$facility->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $hotel = Hotel::withoutGlobalScopes()->where('name', 'Hotel Baru Bali')->firstOrFail();

        // owner_id diisi otomatis dari pengguna yang login, bukan dari form.
        $this->assertSame($this->owner1->id, $hotel->owner_id);
        $this->assertSame(HotelStatus::Pending, $hotel->status);
        $this->assertNull($hotel->verified_at);
        $this->assertNotNull($hotel->slug);
        $this->assertSame('Bali', $hotel->city);
        $this->assertTrue($hotel->facilities->contains($facility));
    }

    public function test_owner_dapat_menambah_tipe_kamar_untuk_hotelnya(): void
    {
        $this->actingAs($this->owner1);
        $this->usePanel('owner');

        Livewire::test(OwnerRoomTypeResource\Pages\CreateRoomType::class)
            ->fillForm([
                'hotel_id' => $this->hotel1->id,
                'name' => 'Deluxe Uji',
                'base_price' => 750000,
                'total_rooms' => 5,
                'capacity' => 2,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('room_types', [
            'hotel_id' => $this->hotel1->id,
            'name' => 'Deluxe Uji',
            'total_rooms' => 5,
        ]);
    }

    public function test_owner_dapat_mengubah_hotel_tanpa_mengubah_status_verifikasi(): void
    {
        // Hotel aktif; owner mengubah nama tidak boleh mengubah status/verifikasi.
        $this->hotel1->markAsVerified();
        $verifiedAt = $this->hotel1->fresh()->verified_at;

        $this->actingAs($this->owner1);
        $this->usePanel('owner');

        Livewire::test(EditHotel::class, ['record' => $this->hotel1->slug])
            ->fillForm(['name' => 'Hotel Satu (Nama Baru)'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $this->hotel1->fresh();
        $this->assertSame('Hotel Satu (Nama Baru)', $fresh->name);
        $this->assertSame(HotelStatus::Active, $fresh->status);
        $this->assertTrue($verifiedAt->equalTo($fresh->verified_at));
    }

    public function test_owner_dapat_mengunggah_foto_hotel_lewat_relation_manager(): void
    {
        Storage::fake('public');

        $this->actingAs($this->owner1);
        $this->usePanel('owner');

        Livewire::test(PhotosRelationManager::class, [
            'ownerRecord' => $this->hotel1,
            'pageClass' => EditHotel::class,
        ])
            ->callTableAction('create', data: [
                'path' => UploadedFile::fake()->image('foto-hotel.jpg', 1200, 800),
                'is_cover' => true,
                'sort_order' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $photo = HotelPhoto::where('hotel_id', $this->hotel1->id)->firstOrFail();

        // Foto tersimpan di disk publik (storage/app/public).
        $this->assertTrue($photo->is_cover);
        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_hanya_satu_foto_utama_per_hotel(): void
    {
        $lama = HotelPhoto::factory()->cover()->create(['hotel_id' => $this->hotel1->id]);
        $baru = HotelPhoto::factory()->cover()->create(['hotel_id' => $this->hotel1->id]);

        $this->assertFalse($lama->fresh()->is_cover);
        $this->assertTrue($baru->fresh()->is_cover);
    }

    public function test_owner_dapat_mengunggah_foto_tipe_kamar(): void
    {
        Storage::fake('public');

        $roomType = RoomType::factory()->create(['hotel_id' => $this->hotel1->id]);

        $this->actingAs($this->owner1);
        $this->usePanel('owner');

        Livewire::test(RoomTypePhotosRelationManager::class, [
            'ownerRecord' => $roomType,
            'pageClass' => EditRoomType::class,
        ])
            ->callTableAction('create', data: [
                'path' => UploadedFile::fake()->image('foto-kamar.jpg', 1024, 768),
                'sort_order' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $photo = RoomTypePhoto::where('room_type_id', $roomType->id)->firstOrFail();

        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_super_admin_mengelola_hotel_lewat_panel_admin(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/hotels/'.$this->hotel2->slug.'/edit')
            ->assertSuccessful();
    }

    public function test_halaman_tipe_kamar_dan_harga_musiman_dapat_dibuka(): void
    {
        RoomType::factory()->create(['hotel_id' => $this->hotel1->id]);
        RoomType::factory()->create(['hotel_id' => $this->hotel2->id]);

        $this->actingAs($this->owner1)->get('/owner/room-types')->assertSuccessful();
        $this->actingAs($this->owner1)->get('/owner/seasonal-prices')->assertSuccessful();
    }

    public function test_query_tipe_kamar_terbatas_pada_hotel_miliknya(): void
    {
        $roomOwned = RoomType::factory()->create(['hotel_id' => $this->hotel1->id]);
        RoomType::factory()->create(['hotel_id' => $this->hotel2->id]);

        $this->actingAs($this->owner1);
        $this->assertSame([$roomOwned->id], OwnerRoomTypeResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_query_harga_musiman_terbatas_pada_hotel_miliknya(): void
    {
        $roomOwned = RoomType::factory()->create(['hotel_id' => $this->hotel1->id]);
        $roomForeign = RoomType::factory()->create(['hotel_id' => $this->hotel2->id]);

        $priceOwned = SeasonalPrice::factory()->create(['room_type_id' => $roomOwned->id]);
        SeasonalPrice::factory()->create(['room_type_id' => $roomForeign->id]);

        $this->actingAs($this->owner1);
        $this->assertSame([$priceOwned->id], OwnerSeasonalPriceResource::getEloquentQuery()->pluck('id')->all());

        $this->actingAs($this->admin);
        $this->assertCount(2, OwnerSeasonalPriceResource::getEloquentQuery()->pluck('id'));
    }

    public function test_staff_yang_terhubung_hanya_melihat_hotel_tempat_ia_bekerja(): void
    {
        $staff = User::factory()->staff()->create();
        $staff->staffedHotels()->attach($this->hotel1->id);

        $this->actingAs($staff);
        $this->assertSame([$this->hotel1->id], OwnerHotelResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_panel_admin_menampilkan_semua_hotel(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/hotels')
            ->assertSuccessful()
            ->assertSee('Hotel Milik Satu')
            ->assertSee('Hotel Milik Dua');

        $this->actingAs($this->admin);
        $this->assertCount(2, AdminHotelResource::getEloquentQuery()->pluck('id'));
    }

    public function test_owner_tidak_dapat_membuka_panel_admin(): void
    {
        $this->actingAs($this->owner1)->get('/admin/hotels')->assertForbidden();
    }

    public function test_super_admin_dapat_memverifikasi_hotel_lewat_aksi_panel(): void
    {
        $pending = Hotel::factory()->pending()->create(['owner_id' => $this->owner1->id]);

        $this->actingAs($this->admin);
        $this->usePanel('admin');

        Livewire::test(AdminHotelResource\Pages\ListHotels::class)
            ->callTableAction('verify', $pending)
            ->assertHasNoTableActionErrors();

        $pending->refresh();
        $this->assertSame(HotelStatus::Active, $pending->status);
        $this->assertNotNull($pending->verified_at);
    }

    public function test_aksi_verifikasi_tidak_tersedia_untuk_hotel_yang_sudah_aktif(): void
    {
        $this->actingAs($this->admin);
        $this->usePanel('admin');

        Livewire::test(AdminHotelResource\Pages\ListHotels::class)
            ->assertTableActionHidden('verify', $this->hotel1)
            ->assertTableActionVisible('suspend', $this->hotel1);
    }
}
