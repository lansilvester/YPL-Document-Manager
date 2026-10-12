<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class YplAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_hanya_staff(): void
    {
        $this->get(route('staff.analytics'))->assertRedirect('/masuk');

        $anggota = User::factory()->create(['role' => 'anggota']);
        $this->actingAs($anggota)->get(route('staff.analytics'))->assertForbidden();

        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get(route('staff.analytics'))->assertOk()
            ->assertSee('Analytics Operasional');
    }

    public function test_metrik_mencerminkan_aktivitas(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($staff)->create([
            'slug' => 'doc-analitik', 'title' => 'Doc Analitik Unik', 'visibility' => 'public',
            'file_path' => 'documents/a.pdf', 'views_count' => 42,
        ]);
        ActivityLog::record($staff->id, $doc->id, 'view');
        ActivityLog::record($staff->id, $doc->id, 'ask', ['q' => 'apa?']);

        $this->actingAs($staff)->get(route('staff.analytics'))->assertOk()
            ->assertSee('TREN 14 HARI')
            ->assertSee('LAYANAN PER STATUS')
            ->assertSee('DOKUMEN TERATAS')
            ->assertSee('AKTIVITAS TERAKHIR')
            ->assertSee('Doc Analitik Unik');
    }

    public function test_analytics_tanpa_n_plus_satu(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        for ($i = 1; $i <= 3; $i++) {
            $doc = Document::factory()->for($staff)->create([
                'slug' => 'doc-an-'.$i, 'title' => 'Doc An '.$i, 'visibility' => 'public',
                'file_path' => 'documents/a'.$i.'.pdf',
            ]);
            ActivityLog::record($staff->id, $doc->id, 'view');
        }

        DB::enableQueryLog();
        $this->actingAs($staff)->get(route('staff.analytics'))->assertOk();
        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Agregat + eager; tak tumbuh per baris aktivitas.
        $this->assertLessThanOrEqual(14, $total);
    }
}
