<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\ReadingPosition;
use App\Models\User;
use App\Services\RecommendationEngine;
use Database\Seeders\YplGovernanceSeeder;
use Database\Seeders\YplHandbooksSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class YplRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rekomendasi_berdasarkan_kategori_dan_tag(): void
    {
        $this->seed(YplHandbooksSeeder::class);
        $this->seed(YplGovernanceSeeder::class);

        $staff = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($staff)->create([
            'slug' => 'sop-cuti', 'title' => 'SOP Cuti',
            'description' => 'Pengajuan cuti tahunan.',
            'visibility' => 'public', 'status' => 'active', 'doc_type' => 'sop',
        ]);
        $doc2 = Document::factory()->for($staff)->create([
            'slug' => 'form-cuti', 'title' => 'Formulir Cuti',
            'description' => 'Formulir pengajuan cuti.',
            'visibility' => 'public', 'status' => 'active', 'doc_type' => 'form',
        ]);

        $recs = (new RecommendationEngine(3))->forDocument($doc);
        $this->assertGreaterThanOrEqual(1, $recs->count());
        $titles = $recs->pluck('document.title')->toArray();
        $this->assertContains('Formulir Cuti', $titles);
    }

    public function test_rekomendasi_personal_berdasarkan_riwayat_baca(): void
    {
        $this->seed(YplHandbooksSeeder::class);

        $user = User::factory()->create(['role' => 'anggota']);
        $cat = Category::where('collection', 'handbook')->first();

        $doc1 = Document::factory()->for($user)->create([
            'slug' => 'handbook-b01-'.Str::random(6), 'title' => 'Handbook B01', 'visibility' => 'public', 'status' => 'active',
            'category_id' => $cat->id,
        ]);
        $doc2 = Document::factory()->for(User::factory()->create())->create([
            'slug' => 'handbook-b02-'.Str::random(6), 'title' => 'Handbook B02', 'visibility' => 'public', 'status' => 'active',
            'category_id' => $cat->id,
        ]);

        // User baca doc1
        ReadingPosition::create(['user_id' => $user->id, 'document_id' => $doc1->id, 'page' => 5, 'percent' => 20]);

        $recs = (new RecommendationEngine(3))->forUser($user);
        $this->assertGreaterThanOrEqual(1, $recs->count());
        // doc2 harus direkomendasikan karena mirip kategori dengan doc1
        $titles = $recs->pluck('document.title')->toArray();
        $this->assertContains('Handbook B02', $titles);
    }

    public function test_rekomendasi_tidak_bocor_dokumen_privat(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $publicDoc = Document::factory()->for($owner)->create([
            'slug' => 'public-doc', 'title' => 'Dokumen Publik', 'visibility' => 'public', 'status' => 'active',
        ]);
        $privateDoc = Document::factory()->for($owner)->create([
            'slug' => 'private-doc', 'title' => 'Dokumen Privat', 'visibility' => 'private', 'status' => 'active',
        ]);

        $anggota = User::factory()->create(['role' => 'anggota']);
        $recs = (new RecommendationEngine(3))->forDocument($publicDoc, $anggota);

        $titles = $recs->pluck('document.title')->toArray();
        $this->assertNotContains('Dokumen Privat', $titles);
    }
}
