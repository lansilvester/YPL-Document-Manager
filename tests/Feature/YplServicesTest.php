<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YplServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_katalog_layanan_task_oriented(): void
    {
        $this->seed();
        $this->get(route('services.index'))->assertOk()
            ->assertSee('Saya ingin')
            ->assertSee('Transportasi')
            ->assertSee('Pengadaan')
            ->assertSee('FORMS')
            ->assertSee('MY REQUESTS');
    }

    public function test_tamu_diarahkan_login_saat_mengajukan(): void
    {
        $this->get(route('services.request', 'purchase'))->assertRedirect('/masuk');
        $this->post(route('services.store'), ['type' => 'purchase', 'title' => 'X'])->assertRedirect('/masuk');
        $this->get(route('services.my'))->assertRedirect('/masuk');
    }

    public function test_anggota_mengajukan_dan_memantau(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);

        $this->actingAs($user)->get(route('services.request', 'purchase'))->assertOk();
        $this->actingAs($user)->get(route('services.request', 'tidak-ada'))->assertNotFound();

        $this->actingAs($user)->post(route('services.store'), [
            'type' => 'purchase', 'title' => 'Beli proyektor', 'description' => 'Untuk kelas 8',
            'needed_at' => now()->addWeek()->format('Y-m-d'),
        ])->assertRedirect(route('services.my'));

        $this->assertDatabaseHas('service_requests', ['title' => 'Beli proyektor', 'status' => 'pending']);
        $this->actingAs($user)->get(route('services.my'))->assertOk()->assertSee('Beli proyektor');
    }

    public function test_staff_memproses_hingga_done(): void
    {
        $user = User::factory()->create(['role' => 'anggota']);
        $staff = User::factory()->create(['role' => 'staff']);
        $req = ServiceRequest::create([
            'user_id' => $user->id, 'type' => 'maintenance',
            'title' => 'AC bocor', 'status' => 'pending',
        ]);

        // Anggota biasa tidak boleh masuk kelola staff.
        $this->actingAs($user)->get(route('staff.requests'))->assertForbidden();
        $this->actingAs($staff)->get(route('staff.requests'))->assertOk()->assertSee('AC bocor');

        // Transisi invalid ditolak.
        $this->actingAs($staff)->post(route('staff.requests.decide', $req), ['to' => 'done'])->assertStatus(422);

        $this->actingAs($staff)->post(route('staff.requests.decide', $req), [
            'to' => 'approved', 'handled_note' => 'Dijadwalkan teknisi.',
        ])->assertRedirect();
        $this->actingAs($staff)->post(route('staff.requests.decide', $req), ['to' => 'done'])->assertRedirect();

        $this->assertEquals('done', $req->fresh()->status);
        $this->assertEquals($staff->id, $req->fresh()->handler_id);
    }
}
