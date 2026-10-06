<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YplSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_cari_kosong_dan_hasil_dokumen_berkelompok(): void
    {
        $this->get(route('search.index'))->assertOk()->assertSee('Cari knowledge YPL');

        $owner = User::factory()->create(['role' => 'staff']);
        Document::factory()->for($owner)->create([
            'slug' => 'sop-cuti', 'title' => 'SOP Cuti Tahunan', 'doc_type' => 'sop',
            'description' => 'Pengajuan cuti tahunan maksimal dua belas hari.',
            'visibility' => 'public', 'status' => 'active',
        ]);
        Document::factory()->for($owner)->create([
            'slug' => 'form-cuti', 'title' => 'Formulir Cuti',
            'description' => 'Formulir pengajuan cuti untuk karyawan.',
            'visibility' => 'public', 'status' => 'active',
        ]);

        $this->get(route('search.index', ['q' => 'cuti']))
            ->assertOk()
            ->assertSee('SOP') // label grup tipe
            ->assertSee('SOP Cuti Tahunan')
            ->assertSee('Formulir Cuti')
            ->assertSee('LAYANAN & AKSI', false) // 'cuti' cocok ke layanan leave
            ->assertSee('Cuti & HR');
    }

    public function test_hasil_menghormati_otoritas_tanpa_bocor(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        Document::factory()->for($owner)->create([
            'slug' => 'gaji-rahasia', 'title' => 'Skala Gaji Rahasia',
            'description' => 'Skala gaji pokok karyawan.',
            'visibility' => 'private', 'status' => 'active',
        ]);

        $this->get(route('search.index', ['q' => 'gaji']))
            ->assertOk()->assertDontSee('Skala Gaji Rahasia');

        $this->actingAs($owner)->get(route('search.index', ['q' => 'gaji']))
            ->assertOk()->assertSee('Skala Gaji Rahasia');
    }

    public function test_orang_hanya_untuk_pengguna_login_tanpa_email(): void
    {
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'role' => 'staff', 'academic_info' => 'Pamong asrama']);

        $this->get(route('search.index', ['q' => 'santoso']))
            ->assertOk()->assertDontSee('ORANG');

        $anggota = User::factory()->create(['role' => 'anggota']);
        $this->actingAs($anggota)->get(route('search.index', ['q' => 'santoso']))
            ->assertOk()
            ->assertSee('ORANG')
            ->assertSee('Budi Santoso')
            ->assertDontSee($budi->email); // email tak pernah diekspos
    }

    public function test_tanpa_hasil_tampilkan_kosong_yang_jujur(): void
    {
        $this->get(route('search.index', ['q' => 'perjalanan dinas ke bulan']))
            ->assertOk()->assertSee('Tidak ada hasil');
    }

    public function test_validasi_panjang_kueri(): void
    {
        $this->get(route('search.index', ['q' => str_repeat('a', 201)]))->assertSessionHasErrors('q');
    }
}
