<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\ReadingPref;
use App\Models\User;
use Database\Seeders\YplHandbooksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YplReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_detail_adalah_digital_book(): void
    {
        $this->seed(YplHandbooksSeeder::class);
        $book = Category::where('code', 'B01')->firstOrFail();

        $this->get(route('library.show', $book))->assertOk()
            ->assertSee('LIVING BOOK')
            ->assertSee('TABLE OF CONTENTS')
            ->assertSee('Active')
            ->assertSee('v1.0');
    }

    public function test_open_book_masuk_reader_tanpa_hilang_konteks(): void
    {
        $user = User::factory()->create();
        $doc = Document::factory()->for($user)->create([
            'slug' => 'reader-book-a', 'title' => 'Buku Reader A', 'visibility' => 'public', 'status' => 'active',
            'file_path' => 'documents/uji-reader-a.pdf',
        ]);

        // Detail punya tombol Baca Imersif ke reader.
        $this->get(route('documents.show', $doc))->assertOk()
            ->assertSee(route('reader.show', $doc));

        // Reader menjaga konteks: judul + koleksi + TOC + jump + search.
        $this->get(route('reader.show', $doc))->assertOk()
            ->assertSee($doc->title)
            ->assertSee('TABLE OF CONTENTS')
            ->assertSee('DIRECT PAGE JUMP')
            ->assertSee('Search in book');
    }

    public function test_reader_dua_halaman_flip_dan_mobile(): void
    {
        $user = User::factory()->create();
        $doc = Document::factory()->for($user)->create([
            'slug' => 'reader-flip', 'title' => 'Flip Book', 'visibility' => 'public',
            'file_path' => 'documents/uji-reader-flip.pdf',
        ]);

        $html = $this->actingAs($user)->get(route('reader.show', $doc))->getContent();

        // Page-turn signature 450-700ms + reduced-motion fallback.
        $this->assertStringContainsString('pageturn', $html);
        $this->assertStringContainsString('.55s', $html);
        $this->assertStringContainsString('prefers-reduced-motion', $html);
        // Two-page spread + single-page mobile + swipe + keyboard.
        $this->assertStringContainsString("layout === 'double'", $html);
        $this->assertStringContainsString('max-width: 767px', $html);
        $this->assertStringContainsString('touchstart', $html);
        $this->assertStringContainsString('ArrowRight', $html);
        // Bookmark + posisi tersimpan + Ask YPL inline tersedia.
        $this->assertStringContainsString('posisi', $html);
        $this->assertStringContainsString('id="ask-panel"', $html);
        $this->assertStringContainsString('id="form-ask"', $html);
        $this->assertStringContainsString('id="btn-ask"', $html);
        // Fidelity BAB 4: kontrol Aa + indikator Reading %.
        $this->assertStringContainsString('Aa', $html);
        $this->assertStringContainsString('reading-pct', $html);
        $this->assertStringContainsString('CONTENTS', $html);
    }

    public function test_ask_inline_reader_menjawab_ter_scoped(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $scope = Document::factory()->for($owner)->create([
            'slug' => 'reader-ask-scope', 'title' => 'SOP Ask Inline',
            'description' => 'Prosedur ask inline tujuh langkah unik.',
            'visibility' => 'public', 'status' => 'active',
            'file_path' => 'documents/uji-reader-ask.pdf',
        ]);
        Document::factory()->for($owner)->create([
            'slug' => 'reader-ask-global', 'title' => 'SOP Global Lain',
            'description' => 'Prosedur ask inline tujuh langkah unik.',
            'visibility' => 'public', 'status' => 'active', 'file_path' => 'documents/g.pdf',
        ]);

        // Tamu menjawab lewat endpoint JSON, dibatasi ke dokumen scope.
        $res = $this->postJson(route('ask.query'), ['question' => 'ask inline tujuh langkah', 'doc' => 'reader-ask-scope'])
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('scope', 'reader-ask-scope');

        $this->assertSame('SOP Ask Inline', $res->json('sources.0.title'));
        $this->assertStringContainsString(route('documents.show', $scope), $res->json('sources.0.url'));
        $this->assertArrayHasKey('section', $res->json('sources.0'));
    }

    public function test_reader_privat_ditolak(): void
    {
        $owner = User::factory()->create();
        $doc = Document::factory()->for($owner)->create([
            'slug' => 'reader-privat', 'title' => 'Privat', 'visibility' => 'private',
            'file_path' => 'documents/uji-reader-privat.pdf',
        ]);

        $this->get(route('reader.show', $doc))->assertForbidden();
        $this->actingAs($owner)->get(route('reader.show', $doc))->assertOk();
    }

    public function test_reader_thumbnails_dan_panel_catatan(): void
    {
        $user = User::factory()->create();
        $doc = Document::factory()->for($user)->create([
            'slug' => 'reader-thumb', 'title' => 'Thumb Book', 'visibility' => 'public',
            'file_path' => 'documents/uji-reader-thumb.pdf',
        ]);

        $html = $this->actingAs($user)->get(route('reader.show', $doc))->getContent();

        // Thumbnail lazy: IntersectionObserver + render per halaman terlihat.
        $this->assertStringContainsString('IntersectionObserver', $html);
        $this->assertStringContainsString('pg-grid', $html);
        // Panel catatan + endpoint simpan tersedia untuk login.
        $this->assertStringContainsString('id="notes-panel"', $html);
        $this->assertStringContainsString('id="form-note"', $html);
        $this->assertStringContainsString('catatan', $html);
    }

    public function test_reader_tamu_tanpa_panel_catatan(): void
    {
        $user = User::factory()->create();
        $doc = Document::factory()->for($user)->create([
            'slug' => 'reader-thumb-tamu', 'title' => 'Thumb Tamu', 'visibility' => 'public',
            'file_path' => 'documents/uji-reader-thumb-tamu.pdf',
        ]);

        // Tamu: tombol catatan mengarah ke login, bukan panel.
        $guest = $this->get(route('reader.show', $doc))->getContent();
        $this->assertStringNotContainsString('id="notes-panel"', $guest);
        $this->assertStringNotContainsString('id="form-note"', $guest);
    }

    public function test_catatan_crud_terisolasi_per_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $doc = Document::factory()->for($owner)->create([
            'slug' => 'reader-note', 'title' => 'Note Book', 'visibility' => 'public', 'status' => 'active',
            'file_path' => 'documents/uji-reader-note.pdf',
        ]);

        // Tamu diarahkan login (rute di balik middleware auth).
        $this->postJson(route('api.notes.store', $doc), ['page' => 1, 'body' => 'x'])
            ->assertUnauthorized();

        // Simpan via JSON seperti pelanggan reader.
        $res = $this->actingAs($owner)->postJson(route('api.notes.store', $doc), [
            'page' => 3, 'body' => 'Ingat definisi di halaman ini',
        ])->assertOk()->assertJsonPath('ok', true);
        $noteId = $res->json('note.id');
        $this->assertDatabaseHas('reading_notes', [
            'id' => $noteId, 'user_id' => $owner->id, 'page' => 3,
        ]);

        // Validasi: body wajib, maksimal 1000.
        $this->actingAs($owner)->postJson(route('api.notes.store', $doc), ['page' => 1])
            ->assertStatus(422);
        $this->actingAs($owner)->postJson(route('api.notes.store', $doc), [
            'page' => 1, 'body' => str_repeat('x', 1001),
        ])->assertStatus(422);

        // Terlihat di reader pemilik, tak terlihat di reader orang lain.
        $this->actingAs($owner)->get(route('reader.show', $doc))->assertOk()
            ->assertSee('Ingat definisi di halaman ini');
        $this->actingAs($other)->get(route('reader.show', $doc))->assertOk()
            ->assertDontSee('Ingat definisi di halaman ini');

        // Orang lain tak bisa menghapus; pemilik bisa.
        $this->actingAs($other)->deleteJson(route('api.notes.destroy', [$doc, $noteId]))
            ->assertNotFound();
        $this->actingAs($owner)->deleteJson(route('api.notes.destroy', [$doc, $noteId]))
            ->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseMissing('reading_notes', ['id' => $noteId]);
    }

    public function test_reader_default_two_page_di_desktop(): void
    {
        $user = User::factory()->create();
        $doc = Document::factory()->for($user)->create([
            'slug' => 'reader-default-two', 'title' => 'Default Two', 'visibility' => 'public',
            'file_path' => 'documents/uji-reader-default-two.pdf',
        ]);

        // Tanpa preferensi tersimpan, layout default two-page (BAB 20).
        ReadingPref::where('user_id', $user->id)->delete();

        $html = $this->actingAs($user)->get(route('reader.show', $doc))->getContent();

        $this->assertStringContainsString('layout = "double"', $html);
        $this->assertStringContainsString('value="double" selected', preg_replace('/\s+/', ' ', $html));
        // Mobile tetap diturunkan ke single via breakpoint adaptif.
        $this->assertStringContainsString('max-width: 767px', $html);
    }

    public function test_tema_paper_tersimpan_dan_terrender(): void
    {
        $user = User::factory()->create();
        $doc = Document::factory()->for($user)->create([
            'slug' => 'reader-paper', 'title' => 'Paper Book', 'visibility' => 'public',
            'file_path' => 'documents/uji-reader-paper.pdf',
        ]);

        // Preferensi menerima paper, menolak nilai asing.
        $this->actingAs($user)->postJson(route('api.prefs'), ['theme' => 'paper', 'zoom' => 100, 'layout' => 'single'])->assertOk();
        $this->assertEquals('paper', $user->refresh()->readingPref->theme);
        $this->actingAs($user)->postJson(route('api.prefs'), ['theme' => 'neon', 'zoom' => 100, 'layout' => 'single'])->assertStatus(422);

        // Render memakai kelas paper + penanda tema + label tombol.
        $this->actingAs($user)->get(route('reader.show', $doc))->assertOk()
            ->assertSee('bg-[#faf6ec]', false)
            ->assertSee('data-theme="paper"', false)
            ->assertSee('Tema: Paper');
    }

    public function test_tema_lawas_fallback_aman(): void
    {
        $user = User::factory()->create();
        $doc = Document::factory()->for($user)->create([
            'slug' => 'reader-legacy', 'title' => 'Legacy Book', 'visibility' => 'public',
            'file_path' => 'documents/uji-reader-legacy.pdf',
        ]);
        ReadingPref::updateOrCreate(['user_id' => $user->id], ['theme' => 'sepia', 'zoom' => 100, 'layout' => 'single']);

        $this->actingAs($user)->get(route('reader.show', $doc))->assertOk()
            ->assertSee('bg-[#10182e]', false)
            ->assertSee('data-theme="light"', false);
    }
}
