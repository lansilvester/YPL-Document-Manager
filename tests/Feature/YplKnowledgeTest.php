<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Event;
use App\Models\Faq;
use App\Models\System;
use App\Models\User;
use Database\Seeders\SystemsSeeder;
use Database\Seeders\YplGovernanceSeeder;
use Database\Seeders\YplHandbooksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class YplKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');
    }

    public function test_knowledge_fields_tersimpan_saat_upload(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $pic = User::factory()->create(['role' => 'admin']);

        $this->actingAs($staff)->post('/staff/dokumen', [
            'title' => 'SOP Asrama', 'visibility' => 'public',
            'subtitle' => 'Tata tertib malam hari',
            'doc_type' => 'sop', 'unit' => 'Asrama Putra',
            'audience' => ['siswa', 'guru'], 'pic_id' => $pic->id,
            'file' => UploadedFile::fake()->create('sop.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $doc = Document::where('title', 'SOP Asrama')->firstOrFail();
        $this->assertEquals('Tata tertib malam hari', $doc->subtitle);
        $this->assertEquals(['siswa', 'guru'], $doc->audience);
        $this->assertEquals('Asrama Putra', $doc->unit);
        $this->assertEquals($pic->id, $doc->pic_id);

        $this->get(route('documents.show', $doc))->assertOk()
            ->assertSee('Tata tertib malam hari')
            ->assertSee('Asrama Putra')
            ->assertSee($pic->name)
            ->assertSee('SISWA');
    }

    public function test_sistem_nyata_dan_tertaut(): void
    {
        $this->seed(YplHandbooksSeeder::class);
        $this->seed(SystemsSeeder::class);
        $this->seed(YplGovernanceSeeder::class);

        $this->assertGreaterThanOrEqual(8, System::count());
        $this->get(route('systems.index'))->assertOk()->assertSee('Odoo ERP');

        $odoo = System::where('slug', 'odoo-erp')->firstOrFail();
        $this->get(route('systems.show', $odoo))->assertOk()->assertSee('DOKUMEN TERKAIT');

        // SOP Pengadaan tertaut ke Odoo + tampil di passport.
        $sop = Document::where('slug', 'sop-pengadaan-barang-dan-jasa')->firstOrFail();
        $this->assertTrue($sop->outgoingRelations()->whereNotNull('system_id')->exists());
        $this->get(route('documents.show', $sop))->assertOk()
            ->assertSee('Odoo ERP')
            ->assertSee('Direktorat Keuangan');
    }

    public function test_taut_sistem_dari_detail(): void
    {
        $this->seed(SystemsSeeder::class);
        $staff = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($staff)->create([
            'slug' => 'doc-sys', 'title' => 'Doc Sys', 'visibility' => 'public',
            'file_path' => 'documents/x.pdf',
        ]);

        $this->actingAs($staff)->post(route('documents.relations.store', $doc), [
            'system_slug' => 'odoo-erp',
        ])->assertRedirect();
        $this->assertDatabaseHas('document_relations', [
            'document_id' => $doc->id,
            'system_id' => System::where('slug', 'odoo-erp')->first()->id,
        ]);
    }

    public function test_filter_audience_dan_unit(): void
    {
        $this->seed(YplHandbooksSeeder::class);
        $this->seed(YplGovernanceSeeder::class);

        $this->get(route('governance.index', ['audiens' => 'guru']))->assertOk()
            ->assertSee('SOP Pengadaan Barang dan Jasa');
        $this->get(route('governance.index', ['audiens' => 'publik']))->assertOk()
            ->assertDontSee('SOP Pengadaan Barang dan Jasa');
        $this->get(route('governance.index', ['unit' => 'Keuangan']))->assertOk()
            ->assertSee('SOP Pengadaan Barang dan Jasa');
    }

    public function test_taut_agenda_dan_faq_dari_detail(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($staff)->create([
            'slug' => 'doc-cal-faq', 'title' => 'Doc Calendar Faq', 'visibility' => 'public',
            'file_path' => 'documents/x.pdf', 'doc_type' => 'sop', 'status' => 'active',
        ]);
        $event = Event::create(['slug' => 'rapat-x', 'title' => 'Rapat Tinjauan X', 'category' => 'foundation']);
        $faq = Faq::create(['slug' => 'faq-x', 'question' => 'Bagaimana cara X?', 'answer' => 'Lakukan X dengan benar.']);

        $this->actingAs($staff)->post(route('documents.relations.store', $doc), [
            'event_slug' => 'rapat-x',
        ])->assertRedirect();
        $this->actingAs($staff)->post(route('documents.relations.store', $doc), [
            'faq_slug' => 'faq-x',
        ])->assertRedirect();

        $this->assertDatabaseHas('document_relations', ['document_id' => $doc->id, 'event_id' => $event->id]);
        $this->assertDatabaseHas('document_relations', ['document_id' => $doc->id, 'faq_id' => $faq->id]);

        // Tampil di passport + impact map + graf.
        $this->get(route('governance.show', $doc))->assertOk()
            ->assertSee('Rapat Tinjauan X')
            ->assertSee('Bagaimana cara X?');
        $this->get(route('governance.impact', $doc))->assertOk()
            ->assertSee('Rapat Tinjauan X')
            ->assertSee('Bagaimana cara X?');
        $this->get(route('events.show', $event))->assertOk()->assertSee('Rapat Tinjauan X');
        $this->get(route('faqs.show', $faq))->assertOk()->assertSee('Bagaimana cara X?');
    }

    public function test_relasi_menolak_lebih_dari_satu_target(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($staff)->create([
            'slug' => 'doc-xor', 'title' => 'Doc Xor', 'visibility' => 'public',
            'file_path' => 'documents/x.pdf',
        ]);
        $other = Document::factory()->for($staff)->create([
            'slug' => 'doc-xor-2', 'title' => 'Doc Xor 2', 'visibility' => 'public',
            'file_path' => 'documents/y.pdf',
        ]);
        $this->seed(SystemsSeeder::class);

        $this->actingAs($staff)->post(route('documents.relations.store', $doc), [
            'related_slug' => 'doc-xor-2', 'system_slug' => 'odoo-erp',
        ])->assertStatus(422);
        $this->assertDatabaseMissing('document_relations', [
            'document_id' => $doc->id, 'related_id' => $other->id,
        ]);
    }

    public function test_pencarian_menemukan_faq_dan_agenda(): void
    {
        Event::create(['slug' => 'apel-pagi', 'title' => 'Apel Pagi Madrasah', 'category' => 'academic', 'location' => 'Lapangan']);
        Faq::create(['slug' => 'faq-cuti', 'question' => 'Bagaimana cara mengajukan cuti?', 'answer' => 'Isi formulir cuti di layanan HR.']);

        $this->get(route('search.index', ['q' => 'cuti']))->assertOk()
            ->assertSee('Bagaimana cara mengajukan cuti?');
        $this->get(route('search.index', ['q' => 'apel pagi']))->assertOk()
            ->assertSee('Apel Pagi Madrasah');
    }

    public function test_agenda_faq_internal_tersembunyi_dari_tamu(): void
    {
        $event = Event::create(['slug' => 'rapat-internal', 'title' => 'Rapat Internal Rahasia', 'visibility' => 'internal']);
        $faq = Faq::create(['slug' => 'faq-internal', 'question' => 'Prosedur internal rahasia?', 'answer' => 'Hanya staf.', 'visibility' => 'internal']);

        $this->get(route('events.show', $event))->assertNotFound();
        $this->get(route('faqs.show', $faq))->assertNotFound();
        $this->get(route('search.index', ['q' => 'rahasia']))->assertOk()
            ->assertDontSee('Rapat Internal Rahasia')
            ->assertDontSee('Prosedur internal rahasia?');

        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get(route('events.show', $event))->assertOk()
            ->assertSee('Rapat Internal Rahasia');
        $this->actingAs($staff)->get(route('faqs.show', $faq))->assertOk()
            ->assertSee('Prosedur internal rahasia?');
    }
}
