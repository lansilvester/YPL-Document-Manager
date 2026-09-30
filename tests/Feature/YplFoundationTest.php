<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\YplHandbooksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YplFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_library_mode_sections(): void
    {
        $this->seed();
        $this->seed(YplHandbooksSeeder::class);

        $this->get('/')->assertOk()
            ->assertSee('Everything YPL.')
            ->assertSee('SAYA INGIN')
            ->assertSee('EXPLORE THE KNOWLEDGE')
            ->assertSee("WHAT'S NEW", false)
            ->assertSee('YPL TODAY');
    }

    public function test_bookshelf_b01_b10_tampil(): void
    {
        $this->seed(YplHandbooksSeeder::class);

        $this->assertEquals(10, Category::where('collection', 'handbook')->count());

        $this->get(route('library.index'))->assertOk()
            ->assertSee('YPL DIGITAL BOOKSHELF')
            ->assertSee('B01')
            ->assertSee('B10');

        $first = Category::where('code', 'B01')->firstOrFail();
        $this->get(route('library.show', $first))->assertOk()
            ->assertSee($first->name);
    }

    public function test_portal_ia_placeholders(): void
    {
        foreach (['governance.index', 'services.index', 'community.index', 'calendar.index', 'ask.index', 'insight.index'] as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_handbook_crud_hanya_admin(): void
    {
        $this->seed(YplHandbooksSeeder::class);
        $book = Category::where('code', 'B01')->firstOrFail();
        $slug = $book->slug;

        $staff = User::factory()->create(['role' => 'staff']);
        $anggota = User::factory()->create(['role' => 'anggota']);
        $admin = User::factory()->create(['role' => 'admin']);
        $sa = User::factory()->create(['role' => 'superadmin']);

        // Staff & anggota ditolak.
        $this->actingAs($staff)->get('/admin/kategori')->assertForbidden();
        $this->actingAs($anggota)->put(route('admin.categories.update', $book), ['name' => 'X'])->assertForbidden();

        // Admin mengubah handbook; slug stabil.
        $this->actingAs($admin)->put(route('admin.categories.update', $book), [
            'code' => 'B01', 'name' => 'B01 — Ketentuan Umum YPL', 'description' => 'Foundation rules.',
            'collection' => 'handbook', 'color' => 'red', 'sort_order' => 1,
        ])->assertRedirect();
        $book->refresh();
        $this->assertEquals($slug, $book->slug);
        $this->assertEquals('red', $book->color);
        $this->get(route('library.show', $book))->assertOk()->assertSee('Ketentuan Umum YPL');

        // Super Admin juga boleh.
        $this->actingAs($sa)->put(route('admin.categories.update', $book), [
            'code' => 'B01', 'name' => 'B01 — Ketentuan Umum', 'collection' => 'handbook',
        ])->assertRedirect();

        // Handbook dilindungi dari hapus; koleksi berisi dokumen juga ditolak.
        $this->actingAs($admin)->delete(route('admin.categories.destroy', $book))->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['code' => 'B01']);
    }

    public function test_navbar_sesuai_ia_dokumen(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Home')
            ->assertSee('Library')
            ->assertSee('Governance')
            ->assertSee('Services')
            ->assertSee('Community')
            ->assertSee('Calendar')
            ->assertSee('Ask YPL')
            ->assertSee('Insight')
            ->assertDontSee('Jelajahi Dokumen')
            ->assertDontSee('Milik Saya');
    }
}
