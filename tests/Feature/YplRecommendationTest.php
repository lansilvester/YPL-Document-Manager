<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentRelation;
use App\Models\ReadingPosition;
use App\Models\User;
use App\Services\RecommendationEngine;
use Database\Seeders\YplGovernanceSeeder;
use Database\Seeders\YplHandbooksSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_personal_recommendation_query_count_stays_bounded_with_many_seeds(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        $owner = User::factory()->create(['role' => 'staff']);
        $category = Category::create(['name' => 'Seed Category', 'slug' => 'seed-category']);

        $seeds = collect(range(1, 10))->map(fn (int $number) => Document::factory()
            ->for($owner)
            ->create([
                'slug' => 'seed-'.$number,
                'category_id' => $category->id,
                'visibility' => 'public',
                'status' => 'active',
            ]));
        $candidate = Document::factory()->for($owner)->create([
            'slug' => 'candidate-from-seed',
            'category_id' => $category->id,
            'visibility' => 'public',
            'status' => 'active',
        ]);

        foreach ($seeds as $seed) {
            ReadingPosition::create([
                'user_id' => $user->id,
                'document_id' => $seed->id,
                'page' => 1,
                'percent' => 20,
            ]);
        }

        Bookmark::create(['user_id' => $user->id, 'document_id' => $seeds->first()->id]);
        DocumentRelation::create([
            'document_id' => $seeds->first()->id,
            'related_id' => $candidate->id,
            'relation_type' => 'related',
        ]);

        $queryCount = 0;
        DB::listen(function (QueryExecuted $query) use (&$queryCount): void {
            $queryCount++;
        });

        $recommendations = (new RecommendationEngine(5))->forUser($user);

        $this->assertLessThanOrEqual(14, $queryCount);
        $candidateRecommendation = $recommendations->first(
            fn (array $recommendation) => $recommendation['document']->is($candidate)
        );
        $this->assertNotNull($candidateRecommendation);
        $this->assertContains('Relasi eksplisit', $candidateRecommendation['reasons']);
        $this->assertArrayNotHasKey('content', $candidateRecommendation['document']->getAttributes());
    }

    public function test_document_card_scope_loads_required_relations_without_document_content(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $document = Document::factory()->for($owner)->create([
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $cardDocument = Document::query()->forCard()->findOrFail($document->id);

        $this->assertArrayNotHasKey('content', $cardDocument->getAttributes());
        $this->assertTrue($cardDocument->relationLoaded('category'));
        $this->assertTrue($cardDocument->relationLoaded('user'));
        $this->assertTrue($cardDocument->relationLoaded('tags'));
    }
}
