<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YplAskTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_dapat_bertanya_dari_dokumen_publik(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        Document::factory()->for($owner)->create([
            'slug' => 'sop-cuti-tahunan', 'title' => 'SOP Cuti Tahunan',
            'description' => 'Pengajuan cuti tahunan maksimal dua belas hari kerja.',
            'visibility' => 'public', 'status' => 'active',
        ]);

        $this->get(route('ask.index'))->assertOk()->assertSee('Tanya knowledge YPL');

        $this->post(route('ask.query'), ['question' => 'Berapa hari cuti tahunan?'])
            ->assertOk()
            ->assertSee('Berdasarkan 1 sumber resmi')
            ->assertSee('SOP Cuti Tahunan')
            ->assertSee('dua belas hari kerja');
    }

    public function test_dokumen_private_milik_orang_lain_tidak_bocor(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $anggota = User::factory()->create(['role' => 'anggota']);
        Document::factory()->for($owner)->create([
            'slug' => 'rahasia-gaji', 'title' => 'Rahasia Skala Gaji',
            'description' => 'Skala gaji pokok karyawan tahun ini.',
            'visibility' => 'private', 'status' => 'active',
        ]);
        Document::factory()->for($owner)->create([
            'slug' => 'info-gaji-umum', 'title' => 'Info Gaji Umum',
            'description' => 'Informasi umum tunjangan karyawan.',
            'visibility' => 'public', 'status' => 'active',
        ]);

        $this->actingAs($anggota)->post(route('ask.query'), ['question' => 'Berapa skala gaji pokok?'])
            ->assertOk()
            ->assertSee('Info Gaji Umum')
            ->assertDontSee('Rahasia Skala Gaji');
    }

    public function test_pemilik_melihat_dokumen_private_miliknya(): void
    {
        $owner = User::factory()->create(['role' => 'anggota']);
        Document::factory()->for($owner)->create([
            'slug' => 'catatan-riset', 'title' => 'Catatan Riset Pribadi',
            'description' => 'Hipotesis fotosintesis pada daun singkong.',
            'visibility' => 'private', 'status' => 'active',
        ]);

        $this->actingAs($owner)->post(route('ask.query'), ['question' => 'Apa hipotesis fotosintesis?'])
            ->assertOk()
            ->assertSee('Catatan Riset Pribadi');
    }

    public function test_anggota_melihat_dokumen_restricted_admin_melihat_semua(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        Document::factory()->for($owner)->create([
            'slug' => 'panduan-internal', 'title' => 'Panduan Internal Komunitas',
            'description' => 'Tata cara kegiatan komunitas internal.',
            'visibility' => 'restricted', 'status' => 'active',
        ]);
        Document::factory()->for($owner)->create([
            'slug' => 'arsip-rahasia', 'title' => 'Arsip Rahasia Pimpinan',
            'description' => 'Arsip rahasia pimpinan yayasan.',
            'visibility' => 'private', 'status' => 'active',
        ]);

        $anggota = User::factory()->create(['role' => 'anggota']);
        $this->actingAs($anggota)->post(route('ask.query'), ['question' => 'Tata cara kegiatan komunitas?'])
            ->assertOk()->assertSee('Panduan Internal Komunitas');
        $this->actingAs($anggota)->post(route('ask.query'), ['question' => 'Arsip rahasia pimpinan?'])
            ->assertOk()->assertDontSee('Arsip Rahasia Pimpinan');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('ask.query'), ['question' => 'Arsip rahasia pimpinan?'])
            ->assertOk()->assertSee('Arsip Rahasia Pimpinan');
    }

    public function test_tanpa_bukti_jujur_menyatakan_tidak_ditemukan(): void
    {
        $this->post(route('ask.query'), ['question' => 'kebijakan perjalanan dinas ke bulan'])
            ->assertOk()
            ->assertSee('SUMBER TIDAK DITEMUKAN')
            ->assertSee('tidak menemukan sumber resmi');
    }

    public function test_validasi_pertanyaan(): void
    {
        $this->post(route('ask.query'), ['question' => ''])->assertSessionHasErrors('question');
        $this->post(route('ask.query'), ['question' => 'ab'])->assertSessionHasErrors('question');
        $this->post(route('ask.query'), ['question' => str_repeat('a', 501)])->assertSessionHasErrors('question');
    }

    public function test_llm_menyusun_jawaban_dari_kutipan_berizin(): void
    {
        config()->set('ask.driver', 'gemini');
        config()->set('ask.key', 'test-key');
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Cuti tahunan maksimal 12 hari [1].']]]]],
            ], 200),
        ]);

        $owner = User::factory()->create(['role' => 'staff']);
        Document::factory()->for($owner)->create([
            'slug' => 'sop-cuti', 'title' => 'SOP Cuti',
            'description' => 'Pengajuan cuti tahunan maksimal dua belas hari.',
            'visibility' => 'public', 'status' => 'active',
        ]);

        $this->post(route('ask.query'), ['question' => 'Berapa hari cuti?'])
            ->assertOk()
            ->assertSee('JAWABAN AI BERBASIS SUMBER')
            ->assertSee('Cuti tahunan maksimal 12 hari [1]')
            ->assertSee('SOP Cuti');
    }

    public function test_llm_gagal_kembali_ke_ekstraktif(): void
    {
        config()->set('ask.driver', 'gemini');
        config()->set('ask.key', 'test-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'overload'], 503)]);

        $owner = User::factory()->create(['role' => 'staff']);
        Document::factory()->for($owner)->create([
            'slug' => 'sop-cuti-dua', 'title' => 'SOP Cuti',
            'description' => 'Pengajuan cuti tahunan maksimal dua belas hari.',
            'visibility' => 'public', 'status' => 'active',
        ]);

        $this->post(route('ask.query'), ['question' => 'Berapa hari cuti?'])
            ->assertOk()
            ->assertSee('Berdasarkan 1 sumber resmi')
            ->assertDontSee('JAWABAN AI');
        Http::assertSentCount(2);
    }

    public function test_tanpa_key_tidak_ada_panggilan_http(): void
    {
        config()->set('ask.driver', 'gemini');
        config()->set('ask.key', '');
        Http::fake();

        $owner = User::factory()->create(['role' => 'staff']);
        Document::factory()->for($owner)->create([
            'slug' => 'sop-cuti-tiga', 'title' => 'SOP Cuti',
            'description' => 'Pengajuan cuti tahunan maksimal dua belas hari.',
            'visibility' => 'public', 'status' => 'active',
        ]);

        $this->post(route('ask.query'), ['question' => 'Berapa hari cuti?'])->assertOk();
        Http::assertNothingSent();
    }

    public function test_tanya_dengan_konteks_dokumen(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $scope = Document::factory()->for($owner)->create([
            'slug' => 'sop-konteks', 'title' => 'SOP Konteks Unik',
            'description' => 'Prosedur konteks unik tujuh langkah.',
            'visibility' => 'public', 'status' => 'active',
            'file_path' => 'documents/konteks.pdf',
        ]);
        Document::factory()->for($owner)->create([
            'slug' => 'sop-lain', 'title' => 'SOP Lain',
            'description' => 'Prosedur konteks unik tujuh langkah.',
            'visibility' => 'public', 'status' => 'active',
        ]);

        // Banner konteks + jawaban hanya dari dokumen scope.
        $this->get(route('ask.index', ['doc' => 'sop-konteks']))->assertOk()
            ->assertSee('KONTEKS')
            ->assertSee('SOP Konteks Unik');
        $this->post(route('ask.query'), ['question' => 'konteks unik', 'doc' => 'sop-konteks'])->assertOk()
            ->assertSee('SOP Konteks Unik')
            ->assertDontSee('SOP Lain');

        // Link Reader membawa konteks.
        $this->get(route('reader.show', $scope))->assertOk()
            ->assertSee(route('ask.index', ['doc' => 'sop-konteks']));
    }

    public function test_konteks_asing_atau_tak_terlihat_ditolak(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $other = User::factory()->create(['role' => 'staff']);
        $priv = Document::factory()->for($owner)->create([
            'slug' => 'sop-priv-konteks', 'title' => 'SOP Privat Konteks',
            'description' => 'Rahasia konteks.',
            'visibility' => 'private', 'status' => 'active',
        ]);

        // Slug ngawur → 404.
        $this->actingAs($other)->post(route('ask.query'), ['question' => 'apa isinya?', 'doc' => 'tak-ada'])->assertNotFound();

        // Privat milik orang lain → 404, tanpa bocor isi.
        $this->actingAs($other)->post(route('ask.query'), ['question' => 'rahasia konteks', 'doc' => 'sop-priv-konteks'])
            ->assertNotFound()
            ->assertDontSee('Rahasia konteks.');

        // Pemilik boleh bertanya dalam konteks privatnya.
        $this->actingAs($owner)->post(route('ask.query'), ['question' => 'rahasia konteks', 'doc' => 'sop-priv-konteks'])->assertOk()
            ->assertSee('SOP Privat Konteks');

        // GET dengan slug asing: anggun, tanpa banner (fallback global).
        $this->get(route('ask.index', ['doc' => 'tak-ada']))->assertOk()
            ->assertDontSee('KONTEKS');
    }
}
