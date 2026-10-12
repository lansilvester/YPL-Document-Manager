<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\YplHandbooksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_menu_community_calendar_insight_berbahasa_inggris(): void
    {
        // Sesuai briefing: COMMUNITY, CALENDAR, INSIGHT — URL Inggris end-to-end.
        $this->assertSame(url('/community'), route('community.index'));
        $this->assertSame(url('/calendar'), route('calendar.index'));
        $this->assertSame(url('/insight'), route('insight.index'));

        $event = Event::create(['slug' => 'rapat-en', 'title' => 'Rapat En', 'category' => 'event']);
        $this->assertSame(url('/calendar/rapat-en'), route('events.show', $event));

        $faq = Faq::create(['slug' => 'faq-en', 'question' => 'Q En?', 'answer' => 'A.']);
        $this->assertSame(url('/faq/faq-en'), route('faqs.show', $faq));
    }

    public function test_tidak_ada_gradient_inline_duplikat(): void
    {
        // BAB 15: literal gradien kanonis wajib lewat class (grad-navy/grad-red/brand-line).
        // Varian rotasi cover (stop berbeda) dikecualikan — itu sistem variasi, bukan duplikat.
        $kanonis = [
            'linear-gradient(135deg,#002f80,#0046c0)',
            'linear-gradient(135deg,#a30014,#c90018)',
            'linear-gradient(90deg,#002f80 60%,#c90018 60%)',
        ];
        $pelanggar = [];
        foreach (glob(resource_path('views/**/*.blade.php')) as $file) {
            $isi = file_get_contents($file);
            foreach ($kanonis as $pola) {
                if (str_contains($isi, $pola)) {
                    $pelanggar[] = basename($file).' ('.$pola.')';
                }
            }
        }
        $this->assertSame([], $pelanggar);
    }

    public function test_community_nyata_unit_peran_dan_direktori(): void
    {
        $this->seed(RoleSeeder::class);
        $staff = User::factory()->create(['role' => 'staff', 'name' => 'Komunitas Staff X', 'academic_info' => 'Keuangan']);
        $unit = Unit::create(['name' => 'Direktorat Keuangan', 'slug' => 'direktorat-keuangan', 'description' => 'Keuangan YPL.']);
        Contact::create(['unit_id' => $unit->id, 'name' => 'PIC Keuangan', 'position' => 'Kepala Keuangan', 'email' => 'keuangan@lokon.or.id']);
        Faq::create(['slug' => 'faq-kom', 'question' => 'Tanya komunitas?', 'answer' => 'Jawab komunitas.']);

        // Tamu: unit + kontak + peran + FAQ terlihat, direktori dikunci.
        $this->get(route('community.index'))->assertOk()
            ->assertSee('Direktorat Keuangan')
            ->assertSee('PIC Keuangan')
            ->assertSee('Siswa')
            ->assertSee('Tanya komunitas?')
            ->assertDontSee('Komunitas Staff X')
            ->assertSee('Masuk');

        // Login: direktori terlihat; kartu direktori tanpa email (tanpa mailto dari orang).
        $this->actingAs($staff)->get(route('community.index'))->assertOk()
            ->assertSee('Komunitas Staff X')
            ->assertSee('Staff · Keuangan');
    }

    public function test_calendar_nyata_filter_dan_ypl_today(): void
    {
        Event::create(['slug' => 'rapat-cal', 'title' => 'Rapat Kalender A', 'category' => 'foundation', 'starts_at' => '2026-11-01 09:00']);
        Event::create(['slug' => 'apel-cal', 'title' => 'Apel Kalender B', 'category' => 'academic', 'starts_at' => '2026-10-20 07:30']);
        Event::create(['slug' => 'rapat-internal-cal', 'title' => 'Rapat Internal C', 'category' => 'foundation', 'visibility' => 'internal']);

        $this->get(route('calendar.index'))->assertOk()
            ->assertSee('Rapat Kalender A')
            ->assertDontSee('Rapat Internal C');

        $this->get(route('calendar.index', ['category' => 'academic']))->assertOk()
            ->assertSee('Apel Kalender B')
            ->assertDontSee('Rapat Kalender A');

        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get(route('calendar.index'))->assertOk()
            ->assertSee('Rapat Internal C');
    }

    public function test_insight_etalase_publik(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Document::factory()->for($staff)->create([
            'slug' => 'doc-insight', 'title' => 'Doc Insight Publik', 'visibility' => 'public',
            'file_path' => 'documents/i.pdf', 'status' => 'active',
        ]);
        Document::factory()->for($staff)->create([
            'slug' => 'doc-insight-priv', 'title' => 'Doc Insight Rahasia', 'visibility' => 'private',
            'file_path' => 'documents/j.pdf', 'status' => 'active',
        ]);

        $this->get(route('insight.index'))->assertOk()
            ->assertSee('Doc Insight Publik')
            ->assertDontSee('Doc Insight Rahasia')
            ->assertSee('TERBARU UNTUK PUBLIK');
    }

    public function test_insight_tanpa_n_plus_satu(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        for ($i = 1; $i <= 3; $i++) {
            Document::factory()->for($staff)->create([
                'slug' => 'doc-n1-'.$i, 'title' => 'Doc N1 '.$i, 'visibility' => 'public',
                'file_path' => 'documents/n'.$i.'.pdf', 'status' => 'active',
            ]);
        }

        DB::enableQueryLog();
        $this->get(route('insight.index'))->assertOk();
        $userQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], '"users"'))
            ->count();
        DB::disableQueryLog();

        // Eager load: maksimal 1 query users untuk semua kartu.
        $this->assertLessThanOrEqual(1, $userQueries);
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
