<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Memastikan isolasi akses panel Filament sesuai peran. */
class FilamentPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Role harus ada lebih dulu karena factory menetapkan role spatie.
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_dapat_membuka_panel_admin(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get('/admin')->assertSuccessful();
    }

    public function test_owner_tidak_dapat_membuka_panel_admin(): void
    {
        $user = User::factory()->owner()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_owner_dapat_membuka_panel_owner(): void
    {
        $user = User::factory()->owner()->create();

        $this->actingAs($user)->get('/owner')->assertSuccessful();
    }

    public function test_staff_dapat_membuka_panel_owner(): void
    {
        $user = User::factory()->staff()->create();

        $this->actingAs($user)->get('/owner')->assertSuccessful();
    }

    public function test_tamu_tidak_dapat_membuka_panel_owner(): void
    {
        $user = User::factory()->guest()->create();

        $this->actingAs($user)->get('/owner')->assertForbidden();
    }

    public function test_pengguna_belum_login_diarahkan_ke_halaman_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/owner')->assertRedirect('/owner/login');
    }
}
