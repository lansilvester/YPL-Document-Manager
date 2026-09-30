<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
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
            'slug' => 'reader-book-a', 'title' => 'Buku Reader A', 'visibility' => 'public',
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
        // Bookmark + posisi tersimpan + Ask YPL tersedia.
        $this->assertStringContainsString('posisi', $html);
        $this->assertStringContainsString('Ask YPL', $html);
        // Fidelity BAB 4: kontrol Aa + indikator Reading %.
        $this->assertStringContainsString('Aa', $html);
        $this->assertStringContainsString('reading-pct', $html);
        $this->assertStringContainsString('CONTENTS', $html);
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
}
