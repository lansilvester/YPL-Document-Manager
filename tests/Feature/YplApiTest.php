<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\Event;
use App\Models\ServiceRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class YplApiTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'staff'], $attrs));
    }

    // ------------------------------------------------------------------
    // Autentikasi
    // ------------------------------------------------------------------

    public function test_login_mengembalikan_token_dan_data_pengguna(): void
    {
        $user = User::factory()->create(['email' => 'staf@lokon.or.id', 'password' => 'Rahasia1!']);

        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'staf@lokon.or.id',
            'password' => 'Rahasia1!',
            'device_name' => 'phpunit',
        ]);

        $res->assertOk()
            ->assertJsonStructure(['message', 'data' => ['token', 'token_type', 'user' => ['id', 'email', 'role']]])
            ->assertJsonPath('data.user.email', 'staf@lokon.or.id');
        $this->assertSame('Bearer', $res->json('data.token_type'));
    }

    public function test_login_gagal_dengan_kata_sandi_salah(): void
    {
        User::factory()->create(['email' => 'staf@lokon.or.id', 'password' => 'Rahasia1!']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'staf@lokon.or.id',
            'password' => 'SalahBanget1!',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_me_menolak_tanpa_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_mengembalikan_profil_dengan_token(): void
    {
        $user = $this->staff(['email' => 'staf@lokon.or.id']);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'staf@lokon.or.id')
            ->assertJsonPath('data.is_staff', true);
    }

    public function test_logout_mencabut_token(): void
    {
        $user = $this->staff();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ------------------------------------------------------------------
    // Katalog publik
    // ------------------------------------------------------------------

    public function test_katalog_publik_dapat_diakses_tanpa_login(): void
    {
        $this->getJson('/api/v1/categories')->assertOk();
        $this->getJson('/api/v1/events')->assertOk();
        $this->getJson('/api/v1/faqs')->assertOk();
        $this->getJson('/api/v1/units')->assertOk();
        $this->getJson('/api/v1/contacts')->assertOk();
        $this->getJson('/api/v1/systems')->assertOk();
        $this->getJson('/api/v1/insight')->assertOk()->assertJsonPath('data.stats.documents', 0);
    }

    public function test_insight_menghitung_dokumen_publik(): void
    {
        Document::factory()->create(['visibility' => 'public', 'status' => 'active']);
        Document::factory()->create(['visibility' => 'private', 'status' => 'active']);

        $this->getJson('/api/v1/insight')->assertOk()->assertJsonPath('data.stats.documents', 1);
    }

    // ------------------------------------------------------------------
    // Dokumen — permission-aware
    // ------------------------------------------------------------------

    public function test_daftar_dokumen_tamu_hanya_publik_aktif(): void
    {
        $public = Document::factory()->create(['visibility' => 'public', 'status' => 'active', 'title' => 'Terbuka']);
        Document::factory()->create(['visibility' => 'private', 'status' => 'active', 'title' => 'Rahasia']);
        Document::factory()->create(['visibility' => 'restricted', 'status' => 'active', 'title' => 'Terbatas']);
        Document::factory()->create(['visibility' => 'public', 'status' => 'draft', 'title' => 'Draf']);

        $res = $this->getJson('/api/v1/documents')->assertOk();
        $titles = collect($res->json('data'))->pluck('title');

        $this->assertTrue($titles->contains('Terbuka'));
        $this->assertFalse($titles->contains('Rahasia'));
        $this->assertFalse($titles->contains('Terbatas'));
        $this->assertFalse($titles->contains('Draf'));
    }

    public function test_detail_dokumen_privat_orang_lain_ditolak_untuk_anggota(): void
    {
        $owner = User::factory()->create();
        $doc = Document::factory()->create(['user_id' => $owner->id, 'visibility' => 'private', 'status' => 'active']);

        Sanctum::actingAs($this->staff(['role' => 'anggota']));

        $this->getJson("/api/v1/documents/{$doc->slug}")->assertForbidden();
    }

    public function test_detail_dokumen_publik_dapat_dilihat_tamu(): void
    {
        $doc = Document::factory()->create(['visibility' => 'public', 'status' => 'active', 'title' => 'Panduan']);

        $this->getJson("/api/v1/documents/{$doc->slug}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Panduan')
            ->assertJsonPath('data.has_file', false);
    }

    // ------------------------------------------------------------------
    // Event CRUD — otorisasi staff/admin/SA
    // ------------------------------------------------------------------

    public function test_anggota_tidak_dapat_membuat_event(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'anggota']));

        $this->postJson('/api/v1/events', [
            'title' => 'Acara', 'category' => 'event',
            'starts_at' => '2026-12-01 09:00', 'visibility' => 'public',
        ])->assertForbidden();
    }

    public function test_staff_dapat_crud_event_dengan_activity_log(): void
    {
        $staf = $this->staff();
        Sanctum::actingAs($staf);

        $create = $this->postJson('/api/v1/events', [
            'title' => 'Rapat Tahunan', 'category' => 'foundation',
            'starts_at' => '2026-12-01 09:00', 'ends_at' => '2026-12-01 12:00',
            'location' => 'Kampus', 'visibility' => 'internal',
        ])->assertCreated();
        $slug = $create->json('data.slug');
        $this->assertDatabaseHas('events', ['title' => 'Rapat Tahunan', 'visibility' => 'internal']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $staf->id, 'action' => 'event_create']);

        $this->putJson("/api/v1/events/{$slug}", [
            'title' => 'Rapat Tahunan YPL', 'category' => 'foundation',
            'starts_at' => '2026-12-01 09:00', 'visibility' => 'public',
        ])->assertOk()->assertJsonPath('data.title', 'Rapat Tahunan YPL');
        $this->assertDatabaseHas('events', ['slug' => $slug, 'title' => 'Rapat Tahunan YPL']);

        $this->deleteJson("/api/v1/events/{$slug}")->assertOk();
        $this->assertDatabaseMissing('events', ['slug' => $slug]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $staf->id, 'action' => 'event_delete']);
    }

    public function test_event_validasi_menolak_kategori_tidak_dikenal(): void
    {
        Sanctum::actingAs($this->staff());

        $this->postJson('/api/v1/events', [
            'title' => 'Acara', 'category' => 'piknik',
            'starts_at' => '2026-12-01 09:00', 'visibility' => 'public',
        ])->assertStatus(422)->assertJsonValidationErrors('category');
    }

    // ------------------------------------------------------------------
    // Governance
    // ------------------------------------------------------------------

    public function test_transisi_lifecycle_mencatat_versi(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $doc = Document::factory()->create(['visibility' => 'public', 'status' => 'review']);
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/documents/{$doc->slug}/transition", ['to' => 'approval'])
            ->assertOk();
        $this->assertDatabaseHas('documents', ['id' => $doc->id, 'status' => 'approval']);
        $this->assertDatabaseHas('document_versions', ['document_id' => $doc->id, 'to_status' => 'approval']);
    }

    public function test_transisi_tidak_valid_ke_status_berikutnya_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $doc = Document::factory()->create(['visibility' => 'public', 'status' => 'draft']);
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/documents/{$doc->slug}/transition", ['to' => 'active'])
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Search & Ask
    // ------------------------------------------------------------------

    public function test_search_mengembalikan_dokumen_relevan(): void
    {
        Document::factory()->create(['visibility' => 'public', 'status' => 'active', 'title' => 'SOP Pengadaan Barang']);
        Document::factory()->create(['visibility' => 'private', 'status' => 'active', 'title' => 'SOP Pengadaan Rahasia']);

        $res = $this->getJson('/api/v1/search?q=Pengadaan')->assertOk();
        $titles = collect($res->json('data.documents'))->pluck('title');

        $this->assertTrue($titles->contains('SOP Pengadaan Barang'));
        $this->assertFalse($titles->contains('SOP Pengadaan Rahasia'));
    }

    public function test_ask_tanpa_pertanyaan_menghasilkan_validasi(): void
    {
        $this->postJson('/api/v1/ask', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('question');
    }

    // ------------------------------------------------------------------
    // Layanan
    // ------------------------------------------------------------------

    public function test_katalog_layanan_publik(): void
    {
        $this->getJson('/api/v1/services')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'transportation');
    }

    public function test_anggota_dapat_mengirim_pengajuan_layanan(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/service-requests', [
            'type' => 'purchase', 'title' => 'Beli laptop', 'description' => 'Untuk tim IT.',
        ])->assertCreated()->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('service_requests', ['user_id' => $user->id, 'type' => 'purchase', 'status' => 'pending']);
    }

    public function test_staff_dapat_memutus_pengajuan(): void
    {
        $staf = $this->staff();
        $req = ServiceRequest::create([
            'user_id' => User::factory()->create()->id, 'type' => 'leave',
            'title' => 'Cuti', 'status' => 'pending',
        ]);
        Sanctum::actingAs($staf);

        $this->postJson("/api/v1/service-requests/{$req->id}/decide", ['to' => 'approved'])
            ->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertDatabaseHas('service_requests', ['id' => $req->id, 'status' => 'approved', 'handler_id' => $staf->id]);
    }

    // ------------------------------------------------------------------
    // Autorisasi panel admin/SA
    // ------------------------------------------------------------------

    public function test_anggota_ditolak_di_panel_admin(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'anggota']));

        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }

    public function test_staff_ditolak_di_panel_admin_tetapi_bisa_analytics(): void
    {
        Sanctum::actingAs($this->staff());

        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
        $this->getJson('/api/v1/admin/units')->assertForbidden();
    }

    public function test_admin_dapat_mengakses_dashboard_dan_crud_unit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/dashboard')->assertOk()->assertJsonPath('data.stats.users', 1);

        $res = $this->postJson('/api/v1/admin/units', ['name' => 'Direktorat Riset'])
            ->assertCreated();
        $unitId = $res->json('data.id');

        $this->putJson("/api/v1/admin/units/{$unitId}", ['name' => 'Direktorat Riset YPL'])->assertOk();
        $this->assertDatabaseHas('units', ['id' => $unitId, 'name' => 'Direktorat Riset YPL']);

        $this->deleteJson("/api/v1/admin/units/{$unitId}")->assertOk();
        $this->assertDatabaseMissing('units', ['id' => $unitId]);
    }

    public function test_admin_tidak_dapat_mengakses_panel_super_admin(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/v1/sa/users')->assertForbidden();
        $this->getJson('/api/v1/sa/settings')->assertForbidden();
    }

    public function test_superadmin_dapat_mengelola_pengguna_dan_peran(): void
    {
        $sa = User::factory()->create(['role' => 'superadmin']);
        $target = User::factory()->create(['role' => 'anggota']);
        Sanctum::actingAs($sa);

        $this->getJson('/api/v1/sa/users')->assertOk();
        $this->putJson("/api/v1/sa/users/{$target->id}/role", ['role' => 'staff'])
            ->assertOk()->assertJsonPath('data.role', 'staff');
        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'staff']);
    }

    public function test_superadmin_tidak_dapat_mengubah_akun_superadmin_lain(): void
    {
        $sa = User::factory()->create(['role' => 'superadmin']);
        $other = User::factory()->create(['role' => 'superadmin']);
        Sanctum::actingAs($sa);

        $this->putJson("/api/v1/sa/users/{$other->id}/role", ['role' => 'admin'])->assertForbidden();
    }

    public function test_admin_ditolak_mengubah_akun_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sa = User::factory()->create(['role' => 'superadmin']);
        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/admin/users/{$sa->id}", [
            'name' => 'Coba', 'email' => $sa->email,
        ])->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Akun & interaksi
    // ------------------------------------------------------------------

    public function test_toggle_bookmark_membuat_dan_menghapus(): void
    {
        $user = User::factory()->create();
        $doc = Document::factory()->create(['visibility' => 'public', 'status' => 'active']);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/documents/{$doc->slug}/bookmark")
            ->assertOk()->assertJsonPath('data.bookmarked', true);
        $this->assertDatabaseHas('bookmarks', ['user_id' => $user->id, 'document_id' => $doc->id]);

        $this->postJson("/api/v1/documents/{$doc->slug}/bookmark")
            ->assertOk()->assertJsonPath('data.bookmarked', false);
        $this->assertDatabaseMissing('bookmarks', ['user_id' => $user->id, 'document_id' => $doc->id]);
    }

    public function test_catatan_baca_disimpan_dan_dihapus(): void
    {
        $user = User::factory()->create();
        $doc = Document::factory()->create(['visibility' => 'public', 'status' => 'active']);
        Sanctum::actingAs($user);

        $res = $this->postJson("/api/v1/documents/{$doc->slug}/notes", ['page' => 3, 'body' => 'Penting']);
        $res->assertCreated();
        $noteId = $res->json('data.id');
        $this->assertDatabaseHas('reading_notes', ['id' => $noteId, 'page' => 3]);

        $this->deleteJson("/api/v1/documents/{$doc->slug}/notes/{$noteId}")->assertOk();
        $this->assertDatabaseMissing('reading_notes', ['id' => $noteId]);
    }

    public function test_notifikasi_read_all(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('data.unread_count', 0);
        $this->postJson('/api/v1/notifications/read-all')->assertOk();
    }

    // ------------------------------------------------------------------
    // Konten katalog CRUD (kategori/kontak) via admin
    // ------------------------------------------------------------------

    public function test_admin_dapat_membuat_kategori_dan_kontak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $cat = $this->postJson('/api/v1/admin/categories', ['name' => 'Riset', 'code' => 'R01'])
            ->assertCreated()->json('data');
        $this->assertDatabaseHas('categories', ['id' => $cat['id'], 'name' => 'Riset']);

        $unit = Unit::create(['name' => 'Unit Riset', 'slug' => 'unit-riset', 'sort_order' => 1]);
        $contact = $this->postJson('/api/v1/admin/contacts', [
            'unit_id' => $unit->id, 'name' => 'Rina', 'position' => 'Kepala', 'email' => 'rina@lokon.or.id',
        ])->assertCreated()->json('data');
        $this->assertDatabaseHas('contacts', ['id' => $contact['id'], 'name' => 'Rina', 'unit_id' => $unit->id]);
    }

    public function test_kategori_handbook_dilindungi_dari_penghapusan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Handbook B01', 'slug' => 'handbook-b01', 'collection' => 'handbook']);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/admin/categories/{$category->slug}")
            ->assertStatus(422);
    }
}
