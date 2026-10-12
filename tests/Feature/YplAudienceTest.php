<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\RoleRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YplAudienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_menetapkan_audiens(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'anggota']);

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'anggota',
            'audience' => 'guru',
        ])->assertRedirect();

        $this->assertEquals('guru', $user->fresh()->audience);
    }

    public function test_audiens_tidak_valid_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'anggota']);

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'anggota',
            'audience' => 'dewa',
        ])->assertSessionHasErrors('audience');

        $this->assertNull($user->fresh()->audience);
    }

    public function test_superadmin_menetapkan_audiens(): void
    {
        $sa = User::factory()->create(['role' => 'superadmin']);
        $user = User::factory()->create(['role' => 'anggota']);

        $this->actingAs($sa)->put(route('sa.users.role', $user), [
            'role' => 'anggota',
            'audience' => 'orang_tua',
        ])->assertRedirect();

        $this->assertEquals('orang_tua', $user->fresh()->audience);

        // Kosongkan kembali
        $this->actingAs($sa)->put(route('sa.users.role', $user), [
            'role' => 'anggota',
            'audience' => '',
        ])->assertRedirect();
        $this->assertNull($user->fresh()->audience);
    }

    public function test_anggota_mengajukan_audiens_lalu_disetujui(): void
    {
        $anggota = User::factory()->create(['role' => 'anggota']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($anggota)->post(route('profile.role-request'), [
            'requested_audience' => 'siswa',
            'reason' => 'Saya siswa SMP Lokon',
        ])->assertRedirect();

        $this->assertDatabaseHas('role_requests', [
            'user_id' => $anggota->id,
            'requested_audience' => 'siswa',
            'requested_role' => null,
            'status' => 'pending',
        ]);

        $req = RoleRequest::pending()->first();
        $this->actingAs($admin)->post(route('admin.approvals.approve', $req))->assertRedirect();

        $this->assertEquals('siswa', $anggota->fresh()->audience);
        $this->assertEquals('approved', $req->fresh()->status);
    }

    public function test_pengajuan_audiens_ditolak_tidak_mengubah_audiens(): void
    {
        $anggota = User::factory()->create(['role' => 'anggota']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($anggota)->post(route('profile.role-request'), [
            'requested_audience' => 'mitra',
        ])->assertRedirect();

        $req = RoleRequest::pending()->first();
        $this->actingAs($admin)->post(route('admin.approvals.reject', $req))->assertRedirect();

        $this->assertNull($anggota->fresh()->audience);
        $this->assertEquals('rejected', $req->fresh()->status);
    }

    public function test_pengajuan_tanpa_pilihan_ditolak(): void
    {
        $anggota = User::factory()->create(['role' => 'anggota']);

        $this->actingAs($anggota)->post(route('profile.role-request'), [
            'reason' => 'Bingung',
        ])->assertSessionHasErrors('requested_role');

        $this->assertDatabaseCount('role_requests', 0);
    }

    public function test_audiens_sama_ditolak_diajukan(): void
    {
        $anggota = User::factory()->audience('guru')->create(['role' => 'anggota']);

        $this->actingAs($anggota)->post(route('profile.role-request'), [
            'requested_audience' => 'guru',
        ])->assertSessionHasErrors('requested_audience');
    }

    public function test_beranda_mengikuti_audiens(): void
    {
        $guru = User::factory()->audience('guru')->create(['role' => 'anggota']);
        Event::create(['slug' => 'agenda-guru', 'title' => 'Agenda Guru', 'category' => 'foundation', 'starts_at' => now()->addDay()]);

        $this->actingAs($guru)->get(route('home'))->assertOk()
            ->assertSee('Guru');
    }
}
