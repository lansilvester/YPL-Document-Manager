<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Event;
use App\Models\RoleRequest;
use App\Models\ServiceRequest;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\YplHandbooksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YplDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(YplHandbooksSeeder::class);
    }

    private function docIn(string $collection, string $slug, array $overrides = []): Document
    {
        $category = Category::where('collection', $collection)->firstOrFail();

        return Document::factory()->create(array_merge([
            'slug' => $slug,
            'title' => ucwords(str_replace('-', ' ', $slug)),
            'category_id' => $category->id,
            'visibility' => 'public',
            'file_path' => 'documents/'.$slug.'.pdf',
            'status' => 'active',
        ], $overrides));
    }

    public function test_dashboard_wajib_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_siswa_melihat_seksi_audiens_siswa(): void
    {
        $this->docIn('handbook', 'hb-siswa', ['title' => 'Handbook Siswa Doc']);
        $this->docIn('academic', 'akademik-siswa', ['title' => 'Akademik Siswa Doc']);
        $siswa = User::factory()->audience('siswa')->create(['role' => 'anggota']);

        $this->actingAs($siswa)->get(route('dashboard'))->assertOk()
            ->assertSee('Dashboard Saya')
            ->assertSee('Handbook Siswa')
            ->assertSee('Akademik')
            ->assertSee('Handbook Siswa Doc')
            ->assertDontSee('Ringkasan Operasional');
    }

    public function test_pimpinan_melihat_ringkasan_operasional(): void
    {
        $pimpinan = User::factory()->audience('pimpinan')->create(['role' => 'admin']);
        Event::create(['slug' => 'agenda-pim', 'title' => 'Agenda Pimpinan', 'category' => 'foundation', 'starts_at' => now()->addDay()]);
        ServiceRequest::create(['user_id' => User::factory()->create()->id, 'type' => 'leave', 'title' => 'Cuti Menunggu', 'status' => 'pending']);

        $this->actingAs($pimpinan)->get(route('dashboard'))->assertOk()
            ->assertSee('Ringkasan Operasional')
            ->assertSee('Layanan Menunggu Tindakan')
            ->assertSee('Cuti Menunggu');
    }

    public function test_pimpinan_melihat_review_jatuh_tempo(): void
    {
        $this->docIn('governance', 'due-doc', [
            'title' => 'Dokumen Jatuh Tempo',
            'doc_type' => 'policy',
            'review_at' => now()->addDays(5)->toDateString(),
        ]);
        $pimpinan = User::factory()->audience('pimpinan')->create(['role' => 'admin']);

        $this->actingAs($pimpinan)->get(route('dashboard'))->assertOk()
            ->assertSee('Review Jatuh Tempo')
            ->assertSee('Dokumen Jatuh Tempo');
    }

    public function test_dashboard_tidak_membocorkan_dokumen_privat(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $this->docIn('academic', 'rahasia-dash', ['title' => 'Rahasia Dashboard', 'visibility' => 'private', 'user_id' => $owner->id]);
        $siswa = User::factory()->audience('siswa')->create(['role' => 'anggota']);

        $this->actingAs($siswa)->get(route('dashboard'))->assertOk()
            ->assertDontSee('Rahasia Dashboard');
    }

    public function test_audiens_kosong_fallback_ke_peran_sistem(): void
    {
        $anggota = User::factory()->create(['role' => 'anggota']);

        $this->actingAs($anggota)->get(route('dashboard'))->assertOk()
            ->assertSee('Dashboard Saya')
            ->assertSee('SAYA INGIN')
            ->assertSee('Dokumen Terbaru');
    }

    public function test_staff_melihat_approval_pending(): void
    {
        $staff = User::factory()->audience('pimpinan')->create(['role' => 'staff']);
        $pemohon = User::factory()->create(['role' => 'anggota']);
        RoleRequest::create(['user_id' => $pemohon->id, 'requested_role' => 'staff', 'reason' => 'Ikut mengelola', 'status' => 'pending']);

        $this->actingAs($staff)->get(route('dashboard'))->assertOk()
            ->assertSee('Persetujuan Peran Pending')
            ->assertSee($pemohon->name);
    }

    public function test_mitra_melihat_seksi_kemitraan(): void
    {
        $this->docIn('handbook', 'hb-mitra', ['title' => 'Panduan Mitra Doc']);
        Unit::create(['name' => 'Unit Kemitraan', 'slug' => 'unit-kemitraan']);
        $unit = Unit::where('slug', 'unit-kemitraan')->firstOrFail();
        Contact::create(['unit_id' => $unit->id, 'name' => 'Narahubung Mitra', 'position' => 'Liaison']);
        $mitra = User::factory()->audience('mitra')->create(['role' => 'anggota']);

        $this->actingAs($mitra)->get(route('dashboard'))->assertOk()
            ->assertSee('Panduan Institusi Mitra')
            ->assertSee('Kontak & Kolaborasi')
            ->assertSee('Narahubung Mitra')
            ->assertSee('Panduan Mitra Doc');
    }

    public function test_publik_melihat_seksi_institusi(): void
    {
        $this->docIn('public', 'profil-publik', ['title' => 'Profil Publik Doc']);
        $this->docIn('academic', 'program-publik', ['title' => 'Program Pendidikan Doc']);
        $publik = User::factory()->audience('publik')->create(['role' => 'anggota']);

        $this->actingAs($publik)->get(route('dashboard'))->assertOk()
            ->assertSee('Profil & Publikasi Institusi')
            ->assertSee('Pendidikan & Program')
            ->assertSee('Profil Publik Doc');
    }
}
