<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Event;
use App\Models\Role;
use App\Models\ServiceRequest;
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

    public function test_beranda_urutan_aksi_mengikuti_peran(): void
    {
        $this->seed(RoleSeeder::class);

        $pos = fn (string $html, string $needle) => mb_strpos($html, $needle);

        // Siswa: Handbook dulu; Pimpinan: Policy dulu; tamu: urutan baku.
        $siswa = $this->actingAs(User::factory()->create(['role' => 'siswa']))->get('/')->assertOk()->getContent();
        $this->assertLessThan($pos($siswa, '>Policy<'), $pos($siswa, '>Handbook<'));

        $pim = $this->actingAs(User::factory()->create(['role' => 'pimpinan']))->get('/')->assertOk()->getContent();
        $this->assertLessThan($pos($pim, '>Handbook<'), $pos($pim, '>Policy<'));

        $tamu = $this->get('/')->assertOk()->getContent();
        $this->assertLessThan($pos($tamu, '>Handbook<'), $pos($tamu, '>Policy<'));
    }

    public function test_explore_disaring_scope_peran(): void
    {
        $this->seed(RoleSeeder::class);

        // Siswa di luar scope governance → kartu disembunyikan.
        $this->actingAs(User::factory()->create(['role' => 'siswa']))->get('/')->assertOk()
            ->assertSee('Learning Library')
            ->assertDontSee('Governance Library');

        // Staff dalam scope → kartu tampil.
        $this->actingAs(User::factory()->create(['role' => 'staff']))->get('/')->assertOk()
            ->assertSee('Governance Library');
    }

    public function test_untuk_anda_tampil_sesuai_peran(): void
    {
        $this->seed(RoleSeeder::class);
        $siswa = User::factory()->create(['role' => 'siswa']);
        $staff = User::factory()->create(['role' => 'staff']);
        Event::create(['slug' => 'apel-anda', 'title' => 'Apel Pagi Anda', 'category' => 'academic', 'starts_at' => now()->addDay()]);
        ServiceRequest::create(['user_id' => $siswa->id, 'type' => 'leave', 'title' => 'Izin Anda', 'status' => 'pending']);
        $due = Document::factory()->for($staff)->create([
            'slug' => 'doc-due-anda', 'title' => 'Doc Due Anda', 'visibility' => 'public',
            'file_path' => 'documents/d.pdf', 'status' => 'active', 'review_at' => now()->addDays(5)->toDateString(),
        ]);

        // Tamu: tanpa blok personal.
        $this->get('/')->assertOk()->assertDontSee('UNTUK ANDA');

        // Siswa: agenda + pengajuan, tanpa kartu pantauan review operasional.
        $this->actingAs($siswa)->get('/')->assertOk()
            ->assertSee('UNTUK ANDA')
            ->assertSee('Apel Pagi Anda')
            ->assertSee('pengajuan menunggu')
            ->assertDontSee('TINJAU SEBELUM');

        // Staff operasional: kartu pantauan review tampil.
        $this->actingAs($staff)->get('/')->assertOk()
            ->assertSee('UNTUK ANDA')
            ->assertSee('TINJAU SEBELUM')
            ->assertSee('Doc Due Anda');
    }

    public function test_role_tak_dikenal_fallback_aman(): void
    {
        $this->seed(RoleSeeder::class);
        $aneh = User::factory()->create(['role' => 'bane']);

        // Diperlakukan sebagai anggota: beranda normal, tanpa error.
        $this->actingAs($aneh)->get('/')->assertOk()
            ->assertSee('SAYA INGIN')
            ->assertSee('Governance Library');

        // Library publik tetap terbuka untuk semua peran.
        $this->actingAs($aneh)->get(route('library.index'))->assertOk();
    }
}
