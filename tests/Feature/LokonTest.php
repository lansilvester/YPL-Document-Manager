<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LokonTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_galeri_publik(): void
    {
        $this->seed();
        $this->get('/')->assertStatus(200)->assertSee('Galeri Dokumen Publik');
    }

    public function test_pencarian_dan_filter_kategori(): void
    {
        $this->seed();
        $this->get('/?q=Matematika')->assertStatus(200)->assertSee('Matematika');
        $cat = Category::first()->slug;
        $this->get('/?kategori='.$cat)->assertStatus(200);
    }

    public function test_dokumen_populer_dan_show(): void
    {
        $this->seed();
        $doc = Document::first();
        $before = $doc->views_count;
        $this->get(route('documents.show', $doc))
            ->assertStatus(200)->assertSee($doc->title);
        $this->assertEquals($before + 1, $doc->fresh()->views_count);
    }

    public function test_registrasi_login_terproteksi(): void
    {
        $this->get('/unggah')->assertRedirect('/masuk');
        $this->post('/daftar', [
            'name' => 'Guru YPL',
            'email' => 'guru@lokon.or.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/unggah'); // redirect ke halaman yang awalnya dituju (intended)
        $this->assertAuthenticated();
    }

    public function test_editor_membuat_pdf(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/tulis', [
            'title' => 'Materi Contoh',
            'description' => 'Deskripsi',
            'visibility' => 'public',
            'content' => '<p>Halo YPL</p>',
        ])->assertRedirect();
        $doc = Document::where('title', 'Materi Contoh')->first();
        $this->assertNotNull($doc);
        $this->assertEquals('editor', $doc->source_type);
        $this->assertNotNull($doc->file_path);
    }

    public function test_favorit_dan_posisi_baca(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $doc = Document::first();
        $this->actingAs($user)->post(route('documents.favorite', $doc))->assertRedirect();
        $this->actingAs($user)->postJson(route('api.position', $doc), ['page' => 3])
            ->assertOk()->assertJson(['ok' => true]);
        $this->actingAs($user)->postJson(route('api.prefs'), [
            'theme' => 'dark', 'zoom' => 125, 'layout' => 'double',
        ])->assertOk();
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'document_id' => $doc->id]);
        $this->assertDatabaseHas('reading_positions', ['user_id' => $user->id, 'page' => 3]);
    }

    public function test_privasi_dokumen(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $doc = Document::factory()->for($owner)->create([
            'slug' => 'rahasia-123', 'visibility' => 'private', 'title' => 'Rahasia',
        ]);
        $this->get(route('documents.show', $doc))->assertForbidden();
        $this->actingAs($other)->get(route('documents.show', $doc))->assertForbidden();
        $this->actingAs($owner)->get(route('documents.show', $doc))->assertOk();
    }

    public function test_admin_kelola_kategori_dan_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->post('/admin/kategori', ['name' => 'Olahraga'])
            ->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Olahraga']);
        $this->actingAs($admin)->get('/admin/riwayat')->assertOk();
    }

    public function test_staff_crud_terproteksi_peran(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        $staff = User::factory()->create(['role' => 'staff', 'name' => 'Alan Lengkong']);
        $this->actingAs($user)->get('/staff/dokumen')->assertForbidden();
        $this->actingAs($staff)->get('/staff/dokumen')->assertOk()->assertSee('Ruang Kerja Staff');
    }

    public function test_staff_upload_beri_tag_ganti_file_hapus(): void
    {
        $this->seed();
        $staff = User::factory()->create(['role' => 'staff']);
        $tag = \App\Models\Tag::create(['name' => 'Unggulan', 'slug' => 'unggulan', 'color' => 'amber']);
        $pdf = \Illuminate\Http\UploadedFile::fake()->create('materi.pdf', 120, 'application/pdf');

        // tambah / upload + badge
        $this->actingAs($staff)->post('/staff/dokumen', [
            'title' => 'Materi Staff', 'visibility' => 'public',
            'tags' => [$tag->id], 'file' => $pdf,
        ])->assertRedirect();
        $doc = Document::where('title', 'Materi Staff')->first();
        $this->assertNotNull($doc);
        $this->assertTrue($doc->tags->contains($tag->id));

        // ubah + ganti file + ubah badge
        $tag2 = \App\Models\Tag::create(['name' => 'Baru', 'slug' => 'baru', 'color' => 'emerald']);
        $pdf2 = \Illuminate\Http\UploadedFile::fake()->create('materi-v2.pdf', 200, 'application/pdf');
        $oldPath = $doc->file_path;
        $this->actingAs($staff)->put("/staff/dokumen/{$doc->id}", [
            'title' => 'Materi Staff Revisi', 'visibility' => 'restricted',
            'tags' => [$tag2->id], 'file' => $pdf2,
        ])->assertRedirect();
        $doc->refresh();
        $this->assertEquals('Materi Staff Revisi', $doc->title);
        $this->assertFalse($doc->tags->contains($tag->id));
        $this->assertTrue($doc->tags->contains($tag2->id));
        $this->assertNotEquals($oldPath, $doc->file_path);

        // hapus
        $this->actingAs($staff)->delete("/staff/dokumen/{$doc->id}")->assertRedirect();
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
    }

    public function test_staff_kelola_badge(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->post('/staff/badge', ['name' => 'Klasik', 'color' => 'red'])->assertRedirect();
        $this->assertDatabaseHas('tags', ['slug' => 'klasik']);
        $tag = \App\Models\Tag::where('slug', 'klasik')->first();
        $this->actingAs($staff)->delete("/staff/badge/{$tag->id}")->assertRedirect();
        $this->assertDatabaseMissing('tags', ['slug' => 'klasik']);
    }

    public function test_tamu_baca_tanpa_unduh(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $pdf = \Illuminate\Http\UploadedFile::fake()->create('baca.pdf', 100, 'application/pdf');
        $this->actingAs($staff)->post('/staff/dokumen', [
            'title' => 'Bacaan Publik', 'visibility' => 'public', 'file' => $pdf,
        ])->assertRedirect();
        $doc = Document::where('title', 'Bacaan Publik')->first();

        // keluar dulu agar simulasi sebagai tamu
        $this->post('/keluar');

        // tamu boleh membaca di web
        $this->get(route('documents.show', $doc))->assertOk()->assertSee('Masuk untuk mengunduh');
        // tamu tidak boleh mengunduh (dialihkan ke login)
        $this->get(route('documents.download', $doc))->assertRedirect('/masuk');
        // anggota boleh mengunduh
        $anggota = User::factory()->create(['role' => 'anggota']);
        $this->actingAs($anggota)->get(route('documents.download', $doc))->assertOk();
    }

    public function test_registrasi_default_anggota(): void
    {
        $this->post('/daftar', [
            'name' => 'Siswa YPL', 'email' => 'siswa@lokon.or.id',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect('/');
        $this->assertEquals('anggota', User::where('email', 'siswa@lokon.or.id')->value('role'));
    }

    public function test_alur_persetujuan_peran(): void
    {
        $anggota = User::factory()->create(['role' => 'anggota']);
        $admin = User::factory()->create(['role' => 'admin']);
        $sa = User::factory()->create(['role' => 'superadmin']);

        // anggota mengajukan staff
        $this->actingAs($anggota)->post('/profil/permintaan-peran', ['requested_role' => 'staff', 'reason' => 'Pustakawan'])
            ->assertRedirect();
        // tidak boleh ada 2 pengajuan pending
        $this->actingAs($anggota)->post('/profil/permintaan-peran', ['requested_role' => 'staff'])
            ->assertSessionHasErrors('requested_role');
        // peran superadmin tidak bisa diminta
        $this->actingAs($anggota)->post('/profil/permintaan-peran', ['requested_role' => 'superadmin'])
            ->assertSessionHasErrors('requested_role');

        // admin menyetujui staff
        $req = \App\Models\RoleRequest::pending()->first();
        $this->actingAs($admin)->post("/admin/persetujuan/{$req->id}/setujui")->assertRedirect();
        $this->assertEquals('staff', $anggota->fresh()->role);

        // anggota lain meminta admin; admin biasa tidak boleh menyetujui
        $anggota2 = User::factory()->create(['role' => 'anggota']);
        $this->actingAs($anggota2)->post('/profil/permintaan-peran', ['requested_role' => 'admin'])->assertRedirect();
        $req2 = \App\Models\RoleRequest::pending()->first();
        $this->actingAs($admin)->post("/admin/persetujuan/{$req2->id}/setujui")->assertForbidden();
        // super admin boleh
        $this->actingAs($sa)->post("/admin/persetujuan/{$req2->id}/setujui")->assertRedirect();
        $this->assertEquals('admin', $anggota2->fresh()->role);
    }

    public function test_superadmin_tunggal_dan_terproteksi(): void
    {
        $sa = User::factory()->create(['role' => 'superadmin']);
        $admin = User::factory()->create(['role' => 'admin']);

        // admin tidak boleh masuk wilayah SA
        $this->actingAs($admin)->get('/sa/pengguna')->assertForbidden();
        $this->actingAs($admin)->get('/sa')->assertForbidden();
        $this->actingAs($sa)->get('/sa/pengguna')->assertOk();
        $this->actingAs($sa)->get('/sa')->assertOk()->assertSee('Pusat Kendali');

        // SA tidak bisa diubah/dihapus; hanya 1 SA
        $this->actingAs($sa)->put("/sa/pengguna/{$sa->id}/peran", ['role' => 'admin'])->assertForbidden();
        $this->actingAs($sa)->delete("/sa/pengguna/{$sa->id}")->assertForbidden();

        // command menolak SA kedua tanpa --force
        $this->artisan('ypl:superadmin', ['--email' => 'lain@lokon.or.id'])->assertFailed();
        $this->assertEquals(1, User::where('role', 'superadmin')->count());

        // SA bisa mengubah peran pengguna lain
        $this->actingAs($sa)->put("/sa/pengguna/{$admin->id}/peran", ['role' => 'staff'])->assertRedirect();
        $this->assertEquals('staff', $admin->fresh()->role);
    }

    public function test_profil_dashboard_dan_update(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        $staff = User::factory()->create(['role' => 'staff']);
        $pdf = \Illuminate\Http\UploadedFile::fake()->create('profil.pdf', 100, 'application/pdf');
        $this->actingAs($staff)->post('/staff/dokumen', [
            'title' => 'Untuk Riwayat', 'visibility' => 'public', 'file' => $pdf,
        ]);
        $doc = Document::where('title', 'Untuk Riwayat')->first();

        // dasbor profil memuat info, unggahan, dan riwayat
        $this->actingAs($user)->get(route('documents.show', $doc))->assertOk();
        $this->actingAs($user)->get('/profil')->assertOk()
            ->assertSee($user->name)
            ->assertSee('Terdaftar sejak')
            ->assertSee('Riwayat Dibaca')
            ->assertSee('Untuk Riwayat')
            ->assertSee('Favorit Saya')
            ->assertSee('Lihat semua favorit')
            ->assertSee('Profil Saya') // item dropdown profil
            ->assertSee('Keluar'); // tombol logout dalam dropdown

        // update data non-kritis
        $this->actingAs($user)->put('/profil', [
            'name' => 'Nama Baru',
            'birth_place' => 'Tomohon',
            'birth_date' => '2005-03-14',
            'wa_number' => '+6281234567890',
            'role' => 'superadmin', // percobaan mengubah data penting: harus diabaikan
        ])->assertRedirect();
        $user->refresh();
        $this->assertEquals('Nama Baru', $user->name);
        $this->assertEquals('Tomohon', $user->birth_place);
        $this->assertEquals('2005-03-14', $user->birth_date->format('Y-m-d'));
        $this->assertEquals('+6281234567890', $user->wa_number);
        $this->assertEquals('anggota', $user->role); // peran terkunci
    }

    public function test_aturn_foto_profil(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);

        // wajib file gambar: pdf ditolak
        $pdf = \Illuminate\Http\UploadedFile::fake()->create('dok.pdf', 100, 'application/pdf');
        $this->actingAs($user)->put('/profil', ['name' => $user->name, 'avatar' => $pdf])
            ->assertSessionHasErrors('avatar');

        // maksimal 2 MB: file 3 MB ditolak
        $besar = \Illuminate\Http\UploadedFile::fake()->image('besar.jpg')->size(3072);
        $this->actingAs($user)->put('/profil', ['name' => $user->name, 'avatar' => $besar])
            ->assertSessionHasErrors('avatar');

        // gambar valid 500 KB diterima
        $foto = \Illuminate\Http\UploadedFile::fake()->image('foto.jpg', 400, 400)->size(500);
        $this->actingAs($user)->put('/profil', ['name' => $user->name, 'avatar' => $foto])
            ->assertRedirect();
        $this->assertNotNull($user->fresh()->avatar_path);
    }

    public function test_foto_gagal_upload_server_ada_pesan(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);

        // simulasi file yang dibuang PHP karena melebihi batas server (UPLOAD_ERR_INI_SIZE)
        $tmp = tempnam(sys_get_temp_dir(), 'up').'.jpg';
        copy(__DIR__.'/../../public/images/logo-ypl.png', $tmp);
        $rusak = new \Illuminate\Http\UploadedFile($tmp, 'foto.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);
        $this->actingAs($user)->put('/profil', ['name' => $user->name, 'avatar' => $rusak])
            ->assertSessionHasErrors(['avatar' => 'Foto gagal diunggah — ukurannya melebihi batas server (maks 2 MB). Kecilkan dulu fotonya lalu coba lagi.']);
        $this->assertNull($user->fresh()->avatar_path);
        @unlink($tmp);
    }

    public function test_nomor_wa_dinormalisasi(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        $base = ['name' => $user->name];

        $this->actingAs($user)->put('/profil', $base + ['wa_number' => '089612345678'])->assertRedirect();
        $this->assertEquals('+6289612345678', $user->fresh()->wa_number);

        $this->actingAs($user)->put('/profil', $base + ['wa_number' => '+6289612345678'])->assertRedirect();
        $this->assertEquals('+6289612345678', $user->fresh()->wa_number);

        $this->actingAs($user)->put('/profil', $base + ['wa_number' => '6289612345678'])->assertRedirect();
        $this->assertEquals('+6289612345678', $user->fresh()->wa_number);

        $this->actingAs($user)->put('/profil', $base + ['wa_number' => '89612345678'])->assertRedirect();
        $this->assertEquals('+6289612345678', $user->fresh()->wa_number);

        $this->actingAs($user)->put('/profil', $base + ['wa_number' => '(0896) 123-4567'])->assertRedirect();
        $this->assertEquals('+628961234567', $user->fresh()->wa_number);

        // salah input: ditolak dengan pesan jelas
        $this->actingAs($user)->put('/profil', $base + ['wa_number' => 'halo-dunia'])
            ->assertSessionHasErrors(['wa_number' => 'Nomor WA tidak dikenali. Tulis nomor HP Indonesia, contoh: 089612345678 atau +6289612345678.']);
    }

    public function test_fitur_preview_suka_unduhhtml_semat(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $pdf = \Illuminate\Http\UploadedFile::fake()->create('fitur.pdf', 100, 'application/pdf');
        $this->actingAs($staff)->post('/staff/dokumen', [
            'title' => 'Dokumen Fitur', 'visibility' => 'public', 'file' => $pdf,
        ]);
        $doc = Document::where('title', 'Dokumen Fitur')->first();
        $this->post('/keluar');

        // tamu: tombol suka mengajak login, semat publik bisa dibuka
        $this->get(route('documents.show', $doc))->assertOk()->assertSee('Masuk untuk menyukai');
        $this->post(route('documents.like', $doc))->assertRedirect('/masuk');
        $this->get(route('documents.downloadHtml', $doc))->assertRedirect('/masuk');
        $this->get(route('documents.embed', $doc))->assertOk()->assertSee('Buka penuh');

        // anggota: suka, unduh html
        $anggota = User::factory()->create(['role' => 'anggota']);
        $this->actingAs($anggota)->post(route('documents.like', $doc))->assertRedirect();
        $this->assertDatabaseHas('likes', ['user_id' => $anggota->id, 'document_id' => $doc->id]);
        $this->actingAs($anggota)->post(route('documents.like', $doc))->assertRedirect(); // toggle batal
        $this->assertDatabaseMissing('likes', ['user_id' => $anggota->id, 'document_id' => $doc->id]);
        $res = $this->actingAs($anggota)->get(route('documents.downloadHtml', $doc))->assertOk();
        $this->assertStringContainsString('text/html', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('Dokumen Fitur', $res->streamedContent() ?? $res->getContent());

        // dokumen privat: semat ditolak untuk tamu & anggota lain
        $owner = User::factory()->create(['role' => 'anggota']);
        $priv = Document::factory()->for($owner)->create(['slug' => 'privat-semat', 'visibility' => 'private', 'title' => 'Privat']);
        $this->get(route('documents.embed', $priv))->assertForbidden();
        $this->actingAs($anggota)->get(route('documents.embed', $priv))->assertForbidden();
        $this->actingAs($owner)->get(route('documents.embed', $priv))->assertOk();
    }

    public function test_sa_pengaturan_dan_sakelar_fitur(): void
    {
        $sa = User::factory()->create(['role' => 'superadmin']);
        $admin = User::factory()->create(['role' => 'admin']);

        // hanya SA yang boleh membuka pengaturan
        $this->actingAs($admin)->get('/sa/pengaturan')->assertForbidden();
        $this->actingAs($sa)->get('/sa/pengaturan')->assertOk()->assertSee('Pengaturan Web');

        // ubah identitas + matikan registrasi & suka
        $logo = \Illuminate\Http\UploadedFile::fake()->image('logo.png', 200, 200)->size(100);
        $this->actingAs($sa)->put('/sa/pengaturan', [
            'site_name' => 'Perpus YPL', 'foundation_name' => 'Yayasan Contoh', 'logo' => $logo,
            'feat_reg' => '0', 'feat_upload' => '1', 'feat_editor' => '1', 'feat_download' => '1',
            'feat_like' => '0', 'feat_favorite' => '1', 'feat_share' => '1', 'feat_embed' => '1',
        ])->assertRedirect();
        $this->assertEquals('Perpus YPL', \App\Models\Setting::get('site_name'));
        $this->assertFalse(\App\Models\Setting::feature('reg'));
        $this->assertFalse(\App\Models\Setting::feature('like'));
        $this->assertNotNull(\App\Models\Setting::get('logo_path'));
        $this->actingAs($sa)->get('/')->assertSee('Perpus YPL');

        // fitur mati: URL diblokir
        $this->get('/daftar')->assertNotFound();
        $doc = Document::factory()->create(['slug' => 'suka-mati', 'visibility' => 'public', 'title' => 'Suka Mati']);
        $this->actingAs($admin)->post(route('documents.like', $doc))->assertNotFound();

        // logo kembali ke bawaan
        $this->actingAs($sa)->put('/sa/pengaturan', [
            'site_name' => 'Perpus YPL', 'foundation_name' => 'Yayasan Contoh', 'logo_reset' => '1',
            'feat_reg' => '1', 'feat_upload' => '1', 'feat_editor' => '1', 'feat_download' => '1',
            'feat_like' => '1', 'feat_favorite' => '1', 'feat_share' => '1', 'feat_embed' => '1',
        ])->assertRedirect();
        $this->assertNull(\App\Models\Setting::get('logo_path'));
        $this->assertTrue(\App\Models\Setting::feature('reg'));
    }
}
