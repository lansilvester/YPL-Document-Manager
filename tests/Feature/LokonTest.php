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
}
