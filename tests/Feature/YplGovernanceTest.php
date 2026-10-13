<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentRelation;
use App\Models\Role;
use App\Models\Setting;
use App\Models\System;
use App\Models\User;
use App\Notifications\GovernanceNotification;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SystemsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
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

        $this->get(route('governance.index'))->assertOk()->assertSee('Kelola Dokumen Governance');
        $this->get(route('governance.index', ['tipe' => 'policy']))->assertOk()->assertSee('Policy X');
        $this->get(route('governance.index', ['status' => 'draft']))->assertOk();
    }

    public function test_kriteria_dua_belas_opsi_a(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $anggota = User::factory()->create(['role' => 'anggota']);
        $mk = fn ($slug, $title, $status, $vis = 'public') => Document::factory()->for($staff)->create([
            'slug' => $slug, 'title' => $title, 'visibility' => $vis,
            'file_path' => 'documents/'.$slug.'.pdf', 'doc_type' => 'policy', 'status' => $status,
        ]);
        $draft = $mk('k12-draft', 'K12 Draft Tersembunyi', 'draft');
        $due = $mk('k12-due', 'K12 Due Terlihat', 'review_due');
        $mk('k12-active', 'K12 Active Terlihat', 'active');

        // Tamu: draf tak terlihat di mana pun; review_due tetap terlihat (opsi A).
        $this->get(route('governance.index'))->assertOk()
            ->assertDontSee('K12 Draft Tersembunyi')
            ->assertSee('K12 Due Terlihat')
            ->assertSee('K12 Active Terlihat')
            ->assertDontSee('>DRAFT<', false)
            ->assertDontSee('>REVIEW<', false)
            ->assertDontSee('>APPROVAL<', false)
            ->assertSee('>ACTIVE<', false);
        $this->get(route('governance.index', ['status' => 'draft']))->assertOk()
            ->assertDontSee('K12 Draft Tersembunyi');
        $this->get(route('governance.show', $draft))->assertForbidden();
        $this->get(route('governance.show', $due))->assertOk();
        $this->get(route('search.index', ['q' => 'K12 Draft']))->assertOk()
            ->assertDontSee('K12 Draft Tersembunyi');

        // Anggota: draf restricted orang lain tetap tersembunyi; milik sendiri terlihat.
        $mk('k12-restr-draft', 'K12 Restr Draft', 'draft', 'restricted');
        $mine = Document::factory()->for($anggota)->create([
            'slug' => 'k12-mine', 'title' => 'K12 Milik Saya', 'visibility' => 'private',
            'file_path' => 'documents/mine.pdf', 'doc_type' => 'policy', 'status' => 'draft',
        ]);
        $this->actingAs($anggota)->get(route('governance.show', $draft))->assertForbidden();
        $this->actingAs($anggota)->get(route('governance.show', $mine))->assertOk();

        // Staff lifecycle utuh: draf internal tetap bisa ditelaah.
        $this->actingAs($staff)->get(route('governance.show', $draft))->assertOk()
            ->assertSee('K12 Draft Tersembunyi');
        $this->actingAs($staff)->get(route('governance.index', ['status' => 'draft']))->assertOk()
            ->assertSee('K12 Draft Tersembunyi');
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

        // Tamu tidak boleh mengekspor (hardening: rute di balik auth).
        $this->post(route('governance.export'), [])->assertRedirect('/masuk');

        // Anggota lain: hanya dokumen publik yang terlihat, privat bukan miliknya disembunyikan.
        $anggota = User::factory()->create(['role' => 'anggota']);
        $res = $this->actingAs($anggota)->post(route('governance.export'), [])->assertOk();
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
            'slug' => 'biasa', 'title' => 'Dok Biasa', 'visibility' => 'public', 'status' => 'active',
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

    public function test_tipe_governance_baru_dikenali_dan_difilter(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $baru = ['organization', 'risk', 'compliance', 'quality', 'document_control'];

        // Semua tipe BAB 17 terdaftar dan punya label tampilan.
        foreach ($baru as $tipe) {
            $this->assertContains($tipe, Document::TYPES);
            $this->assertContains($tipe, Document::GOVERNANCE_TYPES);
            $this->assertContains($tipe, Role::DOC_TYPES);
        }
        $this->assertSame('Document Control', Document::typeLabel('document_control'));
        $this->assertSame('Decisions', Document::typeLabel('decision'));
        $this->assertSame('Risk', Document::typeLabel('risk'));

        // Doc baru: risk + quality, keduanya dapat difilter lewat governance.
        Document::factory()->for($staff)->create([
            'slug' => 'gov-risk-1', 'title' => 'Register Risiko', 'visibility' => 'public',
            'file_path' => 'documents/risk.pdf', 'doc_type' => 'risk', 'status' => 'active',
        ]);
        Document::factory()->for($staff)->create([
            'slug' => 'gov-qlt-1', 'title' => 'Manual Mutu', 'visibility' => 'public',
            'file_path' => 'documents/qlt.pdf', 'doc_type' => 'quality', 'status' => 'active',
        ]);

        $this->get(route('governance.index', ['tipe' => 'risk']))->assertOk()
            ->assertSee('Register Risiko')->assertDontSee('Manual Mutu');
        $this->get(route('governance.index', ['tipe' => 'quality']))->assertOk()
            ->assertSee('Manual Mutu')->assertDontSee('Register Risiko');
    }

    public function test_seeder_governance_ypl_mengisi_tipe_baru(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['organization', 'risk', 'compliance', 'quality', 'document_control'] as $tipe) {
            $this->assertDatabaseHas('documents', ['doc_type' => $tipe]);
        }
    }

    public function test_naik_versi_dan_revisi_saat_edit_dokumen(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($owner)->create([
            'slug' => 'versi-edit', 'title' => 'Judul Awal', 'visibility' => 'public',
            'file_path' => 'documents/versi-edit.pdf', 'status' => 'active', 'version' => '1.0',
        ]);
        $this->assertEquals('1.1', $doc->nextVersion());

        // Edit tanpa mengubah nomor versi: otomatis naik + revisi tercatat.
        $this->actingAs($owner)->put(route('staff.documents.update', $doc), [
            'title' => 'Judul Revisi', 'visibility' => 'public', 'version' => '1.0',
            'change_note' => 'Perbaikan judul.',
        ])->assertRedirect();

        $doc->refresh();
        $this->assertEquals('1.1', $doc->version);
        $this->assertEquals('Judul Revisi', $doc->title);

        $version = $doc->versions()->latest()->firstOrFail();
        $this->assertEquals('1.1', $version->version);
        $this->assertEquals('Perbaikan judul.', $version->change_note);
        $this->assertEquals('Judul Revisi', $version->snapshot['title']);
    }

    public function test_naik_versi_eksplisit_tidak_ditimpa(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($owner)->create([
            'slug' => 'versi-eksplisit', 'title' => 'Dok', 'visibility' => 'public',
            'file_path' => 'documents/versi-eksplisit.pdf', 'status' => 'active', 'version' => '1.0',
        ]);

        $this->actingAs($owner)->put(route('staff.documents.update', $doc), [
            'title' => 'Dok', 'visibility' => 'public', 'version' => '2.0',
        ])->assertRedirect();

        $this->assertEquals('2.0', $doc->fresh()->version);
    }

    public function test_what_changed_menampilkan_diff_snapshot(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($owner)->create([
            'slug' => 'what-changed', 'title' => 'Judul Lama', 'visibility' => 'public',
            'file_path' => 'documents/what-changed.pdf', 'status' => 'active', 'version' => '1.0',
        ]);
        $doc->recordVersion(null, 'Revisi awal.', ['title' => 'Judul Lama', 'unit' => null]);

        $this->actingAs($owner)->put(route('staff.documents.update', $doc), [
            'title' => 'Judul Baru', 'visibility' => 'public', 'version' => '1.0',
        ])->assertRedirect();

        $this->get(route('governance.versions', $doc))->assertOk()
            ->assertSee('What changed?')
            ->assertSee('Judul Lama')
            ->assertSee('Judul Baru');
    }

    public function test_snapshot_diff_helper(): void
    {
        $diff = Document::snapshotDiff(
            ['title' => 'A', 'unit' => 'X', 'audience' => ['siswa']],
            ['title' => 'B', 'unit' => 'X', 'audience' => ['siswa', 'guru']],
        );

        $this->assertCount(2, $diff);
        $this->assertEquals('title', $diff[0]['field']);
        $this->assertEquals('audience', $diff[1]['field']);
        $this->assertEquals(['siswa'], $diff[1]['from']);
        $this->assertEquals(['siswa', 'guru'], $diff[1]['to']);
        $this->assertSame([], Document::snapshotDiff(['a' => 1], ['a' => 1]));
    }

    public function test_unggah_baru_mencatat_revisi_awal(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $pdf = UploadedFile::fake()->create('awal.pdf', 80, 'application/pdf');

        $this->actingAs($staff)->post(route('staff.documents.store'), [
            'title' => 'Dok Baru', 'visibility' => 'public', 'file' => $pdf,
        ])->assertRedirect();

        $doc = Document::where('title', 'Dok Baru')->firstOrFail();
        $this->assertEquals('1.0', $doc->version);
        $version = $doc->versions()->latest()->firstOrFail();
        $this->assertEquals('Dokumen dibuat.', $version->change_note);
        $this->assertEquals('Dok Baru', $version->snapshot['title']);
    }

    public function test_archive_policy_mengarsipkan_dokumen_kedaluwarsa(): void
    {
        $fresh = Document::factory()->create([
            'title' => 'Baru Review', 'status' => 'review_due', 'version' => '1.0',
            'review_at' => now()->subDays(10),
        ]);
        $stale = Document::factory()->create([
            'title' => 'Lama Review', 'status' => 'review_due', 'version' => '1.0',
            'review_at' => now()->subYears(4),
        ]);
        $activeStale = Document::factory()->create([
            'title' => 'Masih Aktif', 'status' => 'active', 'version' => '1.0',
            'review_at' => now()->subYears(4),
        ]);

        $this->artisan('governance:archive-stale')
            ->expectsOutputToContain('Diarsipkan otomatis: 1')
            ->assertSuccessful();

        $this->assertEquals('archived', $stale->fresh()->status);
        $this->assertEquals('review_due', $fresh->fresh()->status);
        $this->assertEquals('active', $activeStale->fresh()->status);

        $version = $stale->versions()->latest()->firstOrFail();
        $this->assertEquals('review_due', $version->from_status);
        $this->assertEquals('archived', $version->to_status);
        $this->assertDatabaseHas('activity_logs', ['document_id' => $stale->id, 'action' => 'transition']);
    }

    public function test_archive_policy_dry_run_tidak_mengubah(): void
    {
        $stale = Document::factory()->create([
            'status' => 'review_due', 'version' => '1.0', 'review_at' => now()->subYears(4),
        ]);

        $this->artisan('governance:archive-stale', ['--dry-run' => true])
            ->expectsOutputToContain('Kandidat arsip: 1')
            ->assertSuccessful();

        $this->assertEquals('review_due', $stale->fresh()->status);
    }

    public function test_retention_days_dapat_diatur_dan_dipakai_scope(): void
    {
        Setting::set('retention_days', '30');

        $doc = Document::factory()->create([
            'status' => 'review_due', 'version' => '1.0', 'review_at' => now()->subDays(40),
        ]);
        $recent = Document::factory()->create([
            'status' => 'review_due', 'version' => '1.0', 'review_at' => now()->subDays(20),
        ]);

        $this->assertEquals(30, Document::retentionDays());
        $this->assertTrue(Document::archiveDue()->pluck('id')->contains($doc->id));
        $this->assertFalse(Document::archiveDue()->pluck('id')->contains($recent->id));
    }

    public function test_mark_review_due_mengirim_notifikasi_ke_pihak_terkait(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'staff']);
        $pic = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($owner)->create([
            'slug' => 'notif-review', 'title' => 'Notif Review', 'visibility' => 'public',
            'file_path' => 'documents/notif.pdf', 'status' => 'active',
            'review_at' => now()->subDay()->format('Y-m-d'), 'pic_id' => $pic->id,
        ]);

        $this->artisan('governance:mark-review-due')
            ->expectsOutputToContain('notifikasi terkirim: 2')
            ->assertSuccessful();

        Notification::assertSentTo([$owner, $pic], GovernanceNotification::class, function ($n) use ($doc) {
            return $n->kind === 'review_due' && $n->document->id === $doc->id;
        });
        Notification::assertCount(2);
    }

    public function test_archive_stale_mengirim_notifikasi_arsip(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'staff']);
        $stale = Document::factory()->for($owner)->create([
            'title' => 'Arsip Notif', 'status' => 'review_due', 'version' => '1.0',
            'review_at' => now()->subYears(4),
        ]);

        $this->artisan('governance:archive-stale')
            ->expectsOutputToContain('notifikasi terkirim: 1')
            ->assertSuccessful();

        Notification::assertSentTo($owner, GovernanceNotification::class, function ($n) use ($stale) {
            return $n->kind === 'archived' && $n->document->id === $stale->id;
        });
    }

    public function test_pusat_notifikasi_menampilkan_dan_menandai_terbaca(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($owner)->create([
            'slug' => 'notif-ui', 'title' => 'Notif UI', 'visibility' => 'public',
            'file_path' => 'documents/notif-ui.pdf', 'status' => 'active',
        ]);

        $owner->notify(new GovernanceNotification(
            $doc, 'review_due', 'Dokumen perlu ditinjau: '.$doc->title,
            'Dokumen ini telah melewati tanggal review.',
        ));

        $this->actingAs($owner)->get(route('notifications.index'))
            ->assertOk()->assertSee('Dokumen perlu ditinjau: '.$doc->title);

        $id = $owner->notifications()->firstOrFail()->id;
        $this->actingAs($owner)->post(route('notifications.read', $id))
            ->assertRedirect(route('governance.show', $doc));
        $this->assertNotNull($owner->notifications()->firstOrFail()->read_at);

        $owner->notify(new GovernanceNotification($doc, 'review_due', 'Kedua', 'pesan'));
        $this->actingAs($owner)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertEquals(0, $owner->fresh()->unreadNotifications->count());
    }

    public function test_pusat_notifikasi_menolak_baca_notifikasi_orang_lain(): void
    {
        $owner = User::factory()->create(['role' => 'staff']);
        $lain = User::factory()->create(['role' => 'staff']);
        $doc = Document::factory()->for($owner)->create([
            'slug' => 'notif-privacy', 'title' => 'Notif Privasi', 'visibility' => 'public',
            'file_path' => 'documents/notif-privacy.pdf', 'status' => 'active',
        ]);
        $owner->notify(new GovernanceNotification($doc, 'review_due', 'Judul', 'pesan'));
        $id = $owner->notifications()->firstOrFail()->id;

        $this->actingAs($lain)->post(route('notifications.read', $id))->assertNotFound();
        $this->assertNull($owner->notifications()->firstOrFail()->read_at);
    }
}
