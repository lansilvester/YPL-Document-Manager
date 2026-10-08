<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
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

        $this->get(route('governance.index'))->assertOk()->assertSee('Lifecycle');
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
}
