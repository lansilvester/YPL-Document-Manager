<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YplEventAdminTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Rapat Tahunan YPL',
            'description' => 'Rapat kerja tahunan seluruh unit.',
            'category' => 'foundation',
            'starts_at' => '2026-12-01 09:00',
            'ends_at' => '2026-12-01 12:00',
            'location' => 'Kampus YPL Lokon',
            'visibility' => 'internal',
        ], $overrides);
    }

    public function test_anggota_ditolak_mengelola_agenda(): void
    {
        $anggota = User::factory()->create(['role' => 'anggota']);

        $this->actingAs($anggota)->get(route('staff.events.index'))->assertForbidden();
        $this->actingAs($anggota)->post(route('staff.events.store'), $this->payload())->assertForbidden();
    }

    public function test_tamu_dialihkan_ke_halaman_masuk(): void
    {
        $this->get(route('staff.events.index'))->assertRedirect(route('login'));
    }

    public function test_staff_admin_dan_superadmin_dapat_mengelola_agenda(): void
    {
        foreach (['staff', 'admin', 'superadmin'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('staff.events.index'))->assertOk()->assertSee('Kelola Agenda');
        }
    }

    public function test_validasi_store_menolak_data_tidak_lengkap(): void
    {
        $staf = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staf)->post(route('staff.events.store'), $this->payload([
            'title' => '',
            'category' => 'bukan-kategori',
            'starts_at' => '',
            'visibility' => 'rahasia',
        ]))->assertSessionHasErrors(['title', 'category', 'starts_at', 'visibility']);

        $this->assertDatabaseCount('events', 0);
    }

    public function test_category_harus_dari_daftar_kategori(): void
    {
        $staf = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staf)->post(route('staff.events.store'), $this->payload(['category' => 'piknik']))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('events', 0);
    }

    public function test_ends_at_tidak_boleh_mendahului_starts_at(): void
    {
        $staf = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staf)->post(route('staff.events.store'), $this->payload([
            'starts_at' => '2026-12-02 09:00', 'ends_at' => '2026-12-01 12:00',
        ]))->assertSessionHasErrors('ends_at');

        $this->assertDatabaseCount('events', 0);
    }

    public function test_store_membuat_agenda_dengan_slug_aman(): void
    {
        $staf = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staf)->post(route('staff.events.store'), $this->payload())->assertRedirect();

        $this->assertDatabaseHas('events', [
            'title' => 'Rapat Tahunan YPL',
            'category' => 'foundation',
            'visibility' => 'internal',
            'starts_at' => '2026-12-01 09:00:00',
        ]);
        $this->assertSame(1, Event::where('title', 'Rapat Tahunan YPL')->count());
        $this->assertStringStartsWith('rapat-tahunan-ypl-', Event::where('title', 'Rapat Tahunan YPL')->first()->slug);
    }

    public function test_update_mengubah_agenda_tanpa_mengubah_slug(): void
    {
        $staf = User::factory()->create(['role' => 'staff']);
        $event = Event::create([
            'slug' => 'rapat-awal', 'title' => 'Rapat Awal', 'category' => 'foundation',
            'starts_at' => '2026-12-01 09:00', 'visibility' => 'public',
        ]);

        $this->actingAs($staf)->put(route('staff.events.update', $event), $this->payload([
            'title' => 'Rapat Akhir',
        ]))->assertRedirect();

        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Rapat Akhir', 'slug' => 'rapat-awal']);
    }

    public function test_destroy_menghapus_agenda(): void
    {
        $staf = User::factory()->create(['role' => 'staff']);
        $event = Event::create([
            'slug' => 'rapat-hapus', 'title' => 'Rapat Hapus', 'category' => 'event',
            'starts_at' => '2026-12-01 09:00', 'visibility' => 'public',
        ]);

        $this->actingAs($staf)->delete(route('staff.events.destroy', $event))->assertRedirect();

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_store_mencatat_ke_activity_log(): void
    {
        $staf = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staf)->post(route('staff.events.store'), $this->payload())->assertRedirect();

        $this->assertSame(1, ActivityLog::where('user_id', $staf->id)->where('action', 'event_create')->count());
    }

    public function test_update_mencatat_ke_activity_log(): void
    {
        $staf = User::factory()->create(['role' => 'staff']);
        $event = Event::create([
            'slug' => 'rapat-log', 'title' => 'Rapat Log', 'category' => 'event',
            'starts_at' => '2026-12-01 09:00', 'visibility' => 'public',
        ]);

        $this->actingAs($staf)->put(route('staff.events.update', $event), $this->payload())->assertRedirect();

        $this->assertSame(1, ActivityLog::where('user_id', $staf->id)->where('action', 'event_update')->count());
    }

    public function test_destroy_mencatat_ke_activity_log(): void
    {
        $staf = User::factory()->create(['role' => 'staff']);
        $event = Event::create([
            'slug' => 'rapat-dihapus', 'title' => 'Rapat Dihapus', 'category' => 'event',
            'starts_at' => '2026-12-01 09:00', 'visibility' => 'public',
        ]);

        $this->actingAs($staf)->delete(route('staff.events.destroy', $event))->assertRedirect();

        $this->assertSame(1, ActivityLog::where('user_id', $staf->id)
            ->where('action', 'event_delete')->count());
    }
}
