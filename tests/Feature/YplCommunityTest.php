<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\CommunitySeeder;
use Database\Seeders\EventFaqSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YplCommunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_mengisi_unit_dan_kontak(): void
    {
        $this->seed(CommunitySeeder::class);

        $this->assertGreaterThanOrEqual(4, Unit::count());
        $this->assertGreaterThanOrEqual(4, Contact::count());
        $this->assertDatabaseHas('units', ['name' => 'Direktorat Keuangan']);
        $this->assertDatabaseHas('contacts', ['name' => 'Yohanis Manopo']);
    }

    public function test_unit_dan_kontak_crud_hanya_admin(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Staff ditolak.
        $this->actingAs($staff)->get(route('admin.units'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.contacts'))->assertForbidden();

        // Admin kelola unit.
        $this->actingAs($admin)->get(route('admin.units'))->assertOk()->assertSee('Kelola Unit');
        $this->actingAs($admin)->post(route('admin.units.store'), [
            'name' => 'Direktorat Riset', 'description' => 'Riset dan pengembangan.', 'sort_order' => 5,
        ])->assertRedirect();
        $unit = Unit::where('name', 'Direktorat Riset')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.units.update', $unit), [
            'name' => 'Direktorat Riset YPL', 'description' => 'Riset YPL.', 'sort_order' => 2,
        ])->assertRedirect();
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'name' => 'Direktorat Riset YPL']);

        // Admin kelola kontak (publik).
        $this->actingAs($admin)->post(route('admin.contacts.store'), [
            'unit_id' => $unit->id, 'name' => 'Rina Lumenta', 'position' => 'Kepala Riset',
            'email' => 'riset@lokon.or.id', 'phone' => '0431-359999',
        ])->assertRedirect();
        $contact = Contact::where('name', 'Rina Lumenta')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.contacts.update', $contact), [
            'unit_id' => $unit->id, 'name' => 'Rina Lumenta', 'position' => 'Direktur Riset',
        ])->assertRedirect();
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'position' => 'Direktur Riset']);

        $this->actingAs($admin)->delete(route('admin.contacts.destroy', $contact))->assertRedirect();
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);

        $this->actingAs($admin)->delete(route('admin.units.destroy', $unit))->assertRedirect();
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_validasi_unit_dan_kontak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.units.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->actingAs($admin)->post(route('admin.contacts.store'), [
            'name' => '', 'email' => 'bukan-email', 'unit_id' => 9999,
        ])->assertSessionHasErrors(['name', 'email', 'unit_id']);
    }

    public function test_kontak_publik_tampil_dan_dicari_tanpa_login(): void
    {
        $unit = Unit::create(['name' => 'Direktorat Keuangan', 'slug' => 'direktorat-keuangan']);
        Contact::create([
            'unit_id' => $unit->id, 'name' => 'Yohanis Manopo',
            'position' => 'Kepala Pengadaan', 'email' => 'pengadaan@lokon.or.id',
        ]);

        // Halaman komunitas publik menampilkan kontak + email.
        $this->get(route('community.index'))->assertOk()
            ->assertSee('KONTAK PIC')
            ->assertSee('Yohanis Manopo')
            ->assertSee('pengadaan@lokon.or.id');

        // Pencarian publik menemukan kontak lewat nama/jabatan/unit.
        $this->get(route('search.index', ['q' => 'Yohanis']))->assertOk()
            ->assertSee('PIC / KONTAK')->assertSee('Yohanis Manopo');
        $this->get(route('search.index', ['q' => 'Kepala Pengadaan']))->assertOk()
            ->assertSee('Yohanis Manopo');
        $this->get(route('search.index', ['q' => 'Keuangan']))->assertOk()
            ->assertSee('Yohanis Manopo');
    }

    public function test_seeder_event_mengisi_kategori_event(): void
    {
        $this->seed(EventFaqSeeder::class);

        $this->assertDatabaseHas('events', ['category' => 'event']);
        $this->get(route('calendar.index', ['category' => 'event']))->assertOk()
            ->assertSee('Open House YPL');
    }
}
