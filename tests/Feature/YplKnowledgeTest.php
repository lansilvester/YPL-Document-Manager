<?php

namespace Tests\Feature;

use App\Models\Document;
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
}
