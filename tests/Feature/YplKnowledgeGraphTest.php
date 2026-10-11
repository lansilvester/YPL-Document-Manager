<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentRelation;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YplKnowledgeGraphTest extends TestCase
{
    use RefreshDatabase;

    public function test_graf_hanya_memuat_dokumen_terlihat_tamu(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $center = Document::factory()->for($staff)->create([
            'slug' => 'graph-center', 'title' => 'Graph Center Doc', 'visibility' => 'public',
            'file_path' => 'documents/c.pdf', 'doc_type' => 'policy', 'status' => 'active',
        ]);
        $pub = Document::factory()->for($staff)->create([
            'slug' => 'graph-pub', 'title' => 'Graph Public Node', 'visibility' => 'public',
            'file_path' => 'documents/p.pdf', 'doc_type' => 'sop', 'status' => 'active',
        ]);
        $priv = Document::factory()->for($staff)->create([
            'slug' => 'graph-priv', 'title' => 'Graph Private Node', 'visibility' => 'private',
            'file_path' => 'documents/x.pdf', 'doc_type' => 'sop', 'status' => 'active',
        ]);
        DocumentRelation::create(['document_id' => $center->id, 'related_id' => $pub->id, 'relation_type' => 'related']);
        DocumentRelation::create(['document_id' => $center->id, 'related_id' => $priv->id, 'relation_type' => 'related']);

        // Tamu: simpul privat tidak bocor ke graf.
        $this->get(route('governance.impact', $center))->assertOk()
            ->assertSee('Graph Public Node')
            ->assertDontSee('Graph Private Node');

        // Pemilik: simpul privat terlihat.
        $this->actingAs($staff)->get(route('governance.impact', $center))->assertOk()
            ->assertSee('Graph Private Node');
    }

    public function test_kedalaman_dua_menampilkan_derajat_kedua(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $docs = [];
        foreach (['A' => 'Graph Depth A', 'B' => 'Graph Depth B', 'C' => 'Graph Depth C'] as $slug => $title) {
            $docs[$slug] = Document::factory()->for($staff)->create([
                'slug' => 'graph-depth-'.$slug, 'title' => $title, 'visibility' => 'public',
                'file_path' => 'documents/'.$slug.'.pdf', 'doc_type' => 'policy', 'status' => 'active',
            ]);
        }
        DocumentRelation::create(['document_id' => $docs['A']->id, 'related_id' => $docs['B']->id, 'relation_type' => 'related']);
        DocumentRelation::create(['document_id' => $docs['B']->id, 'related_id' => $docs['C']->id, 'relation_type' => 'related']);

        $this->get(route('governance.impact', ['document' => $docs['A']->slug, 'kedalaman' => 1]))->assertOk()
            ->assertSee('Graph Depth B')
            ->assertDontSee('Graph Depth C');

        $this->get(route('governance.impact', $docs['A']))->assertOk()
            ->assertSee('Graph Depth C');
    }

    public function test_simpul_pic_tampil_beserta_sistem(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $pic = User::factory()->create(['role' => 'admin', 'name' => 'Graph Pic Person']);
        $system = System::create(['slug' => 'graph-sys', 'name' => 'Graph System']);
        $doc = Document::factory()->for($staff)->create([
            'slug' => 'graph-pic', 'title' => 'Graph Pic Doc', 'visibility' => 'public',
            'file_path' => 'documents/g.pdf', 'doc_type' => 'sop', 'status' => 'active',
            'pic_id' => $pic->id,
        ]);
        DocumentRelation::create(['document_id' => $doc->id, 'system_id' => $system->id, 'relation_type' => 'system']);

        $this->get(route('governance.impact', $doc))->assertOk()
            ->assertSee('Graph Pic Person')
            ->assertSee('Graph System');
    }

    public function test_relasi_yatim_tidak_merusak_render(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($staff)->create([
            'slug' => 'graph-orphan', 'title' => 'Graph Orphan Doc', 'visibility' => 'public',
            'file_path' => 'documents/o.pdf', 'doc_type' => 'policy', 'status' => 'active',
        ]);
        $system = System::create(['slug' => 'graph-gone', 'name' => 'Graph Gone System']);
        DocumentRelation::create(['document_id' => $doc->id, 'system_id' => $system->id, 'relation_type' => 'system']);
        $system->delete(); // system_id jadi null → relasi yatim dua sisi.

        $this->get(route('governance.impact', $doc))->assertOk()
            ->assertSee('Graph Orphan Doc');
    }
}
