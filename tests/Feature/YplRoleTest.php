<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YplRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_peran_hanya_superadmin(): void
    {
        $this->seed(RoleSeeder::class);
        $sa = User::factory()->create(['role' => 'superadmin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);

        // Non-SA ditolak di semua endpoint.
        $this->actingAs($admin)->get(route('sa.roles'))->assertForbidden();
        $this->actingAs($staff)->get(route('sa.roles'))->assertForbidden();
        $this->actingAs($staff)->post(route('sa.roles.store'), ['slug' => 'x', 'name' => 'X', 'priority' => 1])->assertForbidden();

        // SA: daftar + buat.
        $this->actingAs($sa)->get(route('sa.roles'))->assertOk()->assertSee('Definisi Peran');
        $this->actingAs($sa)->post(route('sa.roles.store'), [
            'slug' => 'wali_kelas', 'name' => 'Wali Kelas', 'priority' => 14,
            'collections' => ['handbook', 'academic', 'boarding'],
            'quick' => ['handbook', 'calendar'], 'explore' => ['learning'],
        ])->assertRedirect();
        $role = Role::where('slug', 'wali_kelas')->firstOrFail();
        $this->assertEquals(['collections' => ['handbook', 'academic', 'boarding']], $role->scopes);
        $this->assertEquals(14, $role->priority);

        // SA: ubah prioritas + scope.
        $this->actingAs($sa)->put(route('sa.roles.update', $role), [
            'slug' => 'wali_kelas', 'name' => 'Wali Kelas', 'priority' => 9,
            'collections' => ['handbook'],
        ])->assertRedirect();
        $this->assertEquals(9, $role->fresh()->priority);

        // SA: hapus peran biasa.
        $this->actingAs($sa)->delete(route('sa.roles.destroy', $role))->assertRedirect();
        $this->assertDatabaseMissing('roles', ['slug' => 'wali_kelas']);
    }

    public function test_peran_sistem_dilindungi(): void
    {
        $this->seed(RoleSeeder::class);
        $sa = User::factory()->create(['role' => 'superadmin']);
        $sys = Role::where('slug', 'admin')->firstOrFail();

        // Hapus ditolak.
        $this->actingAs($sa)->delete(route('sa.roles.destroy', $sys))->assertSessionHasErrors('role');
        $this->assertDatabaseHas('roles', ['slug' => 'admin']);

        // Slug sistem tak berubah walau dikirim berbeda.
        $this->actingAs($sa)->put(route('sa.roles.update', $sys), [
            'slug' => 'diubah', 'name' => 'Admin', 'priority' => 20,
        ])->assertRedirect();
        $this->assertEquals('admin', $sys->fresh()->slug);

        // Peran dipakai pengguna tak bisa dihapus.
        $biasa = Role::create(['slug' => 'coba', 'name' => 'Coba', 'priority' => 80]);
        User::factory()->create(['role' => 'coba']);
        $this->actingAs($sa)->delete(route('sa.roles.destroy', $biasa))->assertSessionHasErrors('role');
    }
}
