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
        $this->actingAs($sa)->get('/sa/pengguna')->assertOk();

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
}
