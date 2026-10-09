<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentRelation;
use App\Models\DocumentVersion;
use App\Models\System;
use App\Models\User;
use Database\Seeders\SystemsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YplGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_lifecycle_penuh_draft_sampai_archived(): void
    {
        $owner = User::factory()->create(['role' => 'anggota']);
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);
        $doc = Document::factory()->for($owner)->create([
            'slug' => 'gov-lifecycle', 'title' => 'SOP Lifecycle', 'visibility' => 'public',
            'file_path' => 'documents/uji-gov.pdf',
        ]);
        $this->assertEquals('draft', $doc->fresh()->status);

        // Owner/staff boleh submit; tamu dialihkan ke login.
        $this->post(route('documents.transition', $doc), ['to' => 'review'])->assertRedirect('/masuk');
        $this->actingAs($owner)->post(route('documents.transition', $doc), ['to' => 'review'])->assertRedirect();
        $this->assertEquals('review', $doc->fresh()->status);

        // Langsung publish dari review ditolak (harus via approval).
        $this->actingAs($admin)->post(route('documents.transition', $doc), ['to' => 'active'])->assertForbidden();

        $this->actingAs($staff)->post(route('documents.transition', $doc), ['to' => 'approval'])->assertRedirect();
        $this->actingAs($admin)->post(route('documents.transition', $doc), ['to' => 'active'])->assertRedirect();
        $doc->refresh();
        $this->assertEquals('active', $doc->status);
        $this->assertEquals($admin->id, $doc->approver_id);

        $this->actingAs($admin)->post(route('documents.transition', $doc), ['to' => 'review_due'])->assertRedirect();
        $this->actingAs($owner)->post(route('documents.transition', $doc), ['to' => 'review'])->assertRedirect();
        $this->actingAs($staff)->post(route('documents.transition', $doc), ['to' => 'approval'])->assertRedirect();
        $this->actingAs($admin)->post(route('documents.transition', $doc), ['to' => 'active'])->assertRedirect();
        $this->actingAs($admin)->post(route('documents.transition', $doc), ['to' => 'archived'])->assertRedirect();
        $this->assertEquals('archived', $doc->fresh()->status);

        // Audit trail versi tercatat.
        $this->assertGreaterThanOrEqual(7, $doc->versions()->count());
        $this->assertDatabaseHas('activity_logs', ['document_id' => $doc->id, 'action' => 'transition']);
    }

    public function test_passport_tampil_di_detail(): void
    {
        $this->seed();
        $doc = Document::firstOrFail();

        $this->get(route('documents.show', $doc))->assertOk()
            ->assertSee('DOCUMENT PASSPORT')
            ->assertSee('RELATED KNOWLEDGE')
            ->assertSee(strtoupper(str_replace('_', ' ', $doc->status)));
    }

    public function test_relasi_pengetahuan_terhubung(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $a = Document::factory()->for($staff)->create(['slug' => 'gov-a', 'title' => 'Policy A', 'visibility' => 'public', 'file_path' => 'documents/a.pdf']);
        $b = Document::factory()->for($staff)->create(['slug' => 'gov-b', 'title' => 'SOP B', 'visibility' => 'public', 'file_path' => 'documents/b.pdf']);

        $this->actingAs($staff)->post(route('documents.relations.store', $a), [
            'related_slug' => $b->slug, 'relation_type' => 'sop',
        ])->assertRedirect();
        $this->assertDatabaseHas('document_relations', ['document_id' => $a->id, 'related_id' => $b->id]);

        // Relasi ke diri sendiri ditolak.
        $this->actingAs($staff)->post(route('documents.relations.store', $a), [
            'related_slug' => $a->slug, 'relation_type' => 'related',
        ])->assertStatus(422);

        $this->get(route('documents.show', $a))->assertOk()->assertSee('SOP B');

        $rel = $a->outgoingRelations()->firstOrFail();
        $this->actingAs($staff)->delete(route('documents.relations.destroy', [$a, $rel]))->assertRedirect();
        $this->assertDatabaseMissing('document_relations', ['id' => $rel->id]);
    }

    public function test_governance_listing_filter(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Document::factory()->for($staff)->create([
            'slug' => 'gov-policy-1', 'title' => 'Policy X', 'visibility' => 'public',
            'file_path' => 'documents/px.pdf', 'doc_type' => 'policy', 'status' => 'active',
        ]);

        $this->get(route('governance.index'))->assertOk()->assertSee('siklus hidup yang transparan');
        $this->get(route('governance.index', ['tipe' => 'policy']))->assertOk()->assertSee('Policy X');
        $this->get(route('governance.index', ['status' => 'draft']))->assertOk();
    }

    public function test_dashboard_tanpa_bocor_private(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $priv = Document::factory()->for($owner)->create([
            'slug' => 'priv-dashboard', 'title' => 'Rahasia Dashboard XyZ', 'visibility' => 'private',
            'file_path' => 'documents/priv.pdf',
        ]);
        DocumentVersion::create([
            'document_id' => $priv->id, 'user_id' => $owner->id,
            'version' => '1.0', 'from_status' => 'draft', 'to_status' => 'review',
        ]);

        $this->get(route('governance.dashboard'))->assertOk()
            ->assertDontSee('Rahasia Dashboard XyZ');
    }

    public function test_versi_private_ditolak_untuk_tamu(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $priv = Document::factory()->for($owner)->create([
            'slug' => 'priv-versi', 'title' => 'Rahasia Versi', 'visibility' => 'private',
            'file_path' => 'documents/priv2.pdf',
        ]);

        $this->get(route('governance.versions', $priv))->assertForbidden();
        $this->actingAs($owner)->get(route('governance.versions', $priv))->assertOk();
    }

    public function test_show_tampil_dengan_relasi_masuk_keluar_dan_sistem(): void
    {
        $this->seed([SystemsSeeder::class]);
        $owner = User::factory()->create(['role' => 'staff']);
        $a = Document::factory()->for($owner)->create([
            'slug' => 'gov-show-a', 'title' => 'Dok A', 'visibility' => 'public',
            'file_path' => 'documents/a.pdf', 'status' => 'active',
        ]);
        $b = Document::factory()->for($owner)->create([
            'slug' => 'gov-show-b', 'title' => 'Dok B', 'visibility' => 'public',
            'file_path' => 'documents/b.pdf', 'status' => 'active',
        ]);
        DocumentRelation::create([
            'document_id' => $a->id, 'related_id' => $b->id, 'relation_type' => 'related',
        ]);
        $odoo = System::where('slug', 'odoo-erp')->firstOrFail();
        DocumentRelation::create([
            'document_id' => $a->id, 'related_id' => null, 'system_id' => $odoo->id,
            'relation_type' => 'system',
        ]);

        $this->get(route('governance.show', $a))->assertOk()
            ->assertSee('DOCUMENT PASSPORT')
            ->assertSee('RELATED KNOWLEDGE')
            ->assertSee('READ DOCUMENT')
            ->assertSee('View History')
            ->assertSee('Dok B')
            ->assertSee('Odoo ERP');
        $this->get(route('governance.impact', $a))->assertOk();
    }

    public function test_ekspor_csv_hormati_visibilitas(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        Document::factory()->for($owner)->create([
            'slug' => 'ekspor-publik', 'title' => 'Dokumen Ekspor Publik', 'visibility' => 'public',
            'file_path' => 'documents/pub.pdf', 'doc_type' => 'sop', 'status' => 'active',
        ]);
        Document::factory()->for($owner)->create([
            'slug' => 'ekspor-privat', 'title' => 'Dokumen Ekspor Rahasia', 'visibility' => 'private',
            'file_path' => 'documents/priv3.pdf',
        ]);

        $res = $this->post(route('governance.export'), [])->assertOk();
        $res->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $body = $res->streamedContent() ?? $res->getContent();
        $this->assertStringContainsString('Dokumen Ekspor Publik', $body);
        $this->assertStringNotContainsString('Dokumen Ekspor Rahasia', $body);

        // Pemilik melihat dokumennya sendiri.
        $res = $this->actingAs($owner)->post(route('governance.export'), []);
        $body = $res->streamedContent() ?? $res->getContent();
        $this->assertStringContainsString('Dokumen Ekspor Rahasia', $body);
    }

    public function test_transisi_menyimpan_snapshot_revisi(): void
    {
        $owner = User::factory()->create(['role' => 'anggota']);
        $doc = Document::factory()->for($owner)->create([
            'slug' => 'snap-doc', 'title' => 'Judul Snapshot', 'visibility' => 'public',
            'file_path' => 'documents/snap.pdf', 'status' => 'draft',
            'subtitle' => 'Sub kinn', 'unit' => 'Unit A',
        ]);

        $this->actingAs($owner)->post(route('documents.transition', $doc), ['to' => 'review'])
            ->assertRedirect();

        $version = $doc->versions()->latest()->firstOrFail();
        $this->assertEquals('Judul Snapshot', $version->snapshot['title']);
        $this->assertEquals('Unit A', $version->snapshot['unit']);
        $this->get(route('governance.versions', $doc))->assertOk()
            ->assertSee('Snapshot isi revisi');
    }

    public function test_command_tandai_review_due_otomatis(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $lewat = Document::factory()->for($owner)->create([
            'slug' => 'lewat-review', 'title' => 'Lewat Review', 'visibility' => 'public',
            'file_path' => 'documents/lewat.pdf', 'status' => 'active',
            'review_at' => now()->subDay()->format('Y-m-d'),
        ]);
        $nanti = Document::factory()->for($owner)->create([
            'slug' => 'nanti-review', 'title' => 'Nanti Review', 'visibility' => 'public',
            'file_path' => 'documents/nanti.pdf', 'status' => 'active',
            'review_at' => now()->addMonth()->format('Y-m-d'),
        ]);
        $draf = Document::factory()->for($owner)->create([
            'slug' => 'draf-lama', 'title' => 'Draf Lama', 'visibility' => 'public',
            'file_path' => 'documents/draf.pdf', 'status' => 'draft',
            'review_at' => now()->subDay()->format('Y-m-d'),
        ]);

        $this->artisan('governance:mark-review-due')->assertSuccessful();

        $this->assertEquals('review_due', $lewat->fresh()->status);
        $this->assertEquals('active', $nanti->fresh()->status);
        $this->assertEquals('draft', $draf->fresh()->status);
        $this->assertDatabaseHas('document_versions', [
            'document_id' => $lewat->id, 'from_status' => 'active', 'to_status' => 'review_due',
        ]);
        $version = $lewat->versions()->latest()->firstOrFail();
        $this->assertEquals('Lewat Review', $version->snapshot['title']);
    }

    public function test_dokumen_show_ikut_mockup_untuk_tipe_governance(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $gov = Document::factory()->for($owner)->create([
            'slug' => 'gov-mockup', 'title' => 'SOP Mockup', 'visibility' => 'public',
            'file_path' => 'documents/gov.pdf', 'doc_type' => 'sop', 'status' => 'active',
        ]);
        $biasa = Document::factory()->for($owner)->create([
            'slug' => 'biasa', 'title' => 'Dok Biasa', 'visibility' => 'public',
            'file_path' => 'documents/biasa.pdf',
        ]);

        // Jalur user (/dokumen) untuk tipe governance = layout mockup.
        $this->get(route('documents.show', $gov))->assertOk()
            ->assertSee('DOCUMENT PASSPORT')
            ->assertSee('RELATED KNOWLEDGE')
            ->assertSee('READ DOCUMENT')
            ->assertSee('View History');

        // Tipe non-governance tetap layout lama (hero gelap).
        $this->get(route('documents.show', $biasa))->assertOk()
            ->assertSee('linear-gradient(120deg', false);
    }
}
