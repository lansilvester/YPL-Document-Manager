<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCode;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Regresi hardening hasil uji keamanan:
 * - #1 reset password: kode dikirim ke email, tak pernah tampil di respons
 * - #2 privasi ruang staff
 * - hardening: ekspor governance di balik auth + CSP nonce
 */
class YplSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function resetRowFor(string $email): ?object
    {
        return DB::table('password_resets')->where('email', $email)->first();
    }

    /** #1 — kode reset dikirim ke email, tidak pernah bocor di respons. */
    public function test_kode_reset_dikirim_ke_email_dan_tidak_tampil_di_respons(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'anggota@ypl.test']);

        $resp = $this->post('/lupa-password', ['email' => $user->email]);

        $kode = null;
        Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use (&$kode) {
            $kode = $mail->code;

            return true;
        });
        $this->assertNotNull($kode);
        $this->assertStringNotContainsString($kode, $resp->getContent(), 'Kode reset tidak boleh tampil di HTML respons');
        $resp->assertOk()->assertDontSee('name="token"', false);

        $this->assertNotNull($this->resetRowFor($user->email), 'Baris reset harus tersimpan untuk verifikasi kode');
        $this->assertTrue(Hash::check($kode, $this->resetRowFor($user->email)->token_hash));
    }

    /** #1 — respons seragam untuk email terdaftar maupun tidak (anti-enumerasi). */
    public function test_lupa_password_tidak_mengungkap_status_email(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'ada@ypl.test']);

        $this->post('/lupa-password', ['email' => 'ada@ypl.test'])->assertOk();
        $this->post('/lupa-password', ['email' => 'tidak-ada@ypl.test'])->assertOk();

        Mail::assertSent(PasswordResetCode::class, 1);
        $this->assertNull($this->resetRowFor('tidak-ada@ypl.test'));
    }

    /** #1 — reset hanya dengan kode benar; sesi lama dicabut; kode sekali pakai. */
    public function test_reset_butuh_kode_benar_dan_mencabut_sesi_lama(): void
    {
        $user = User::factory()->create(['email' => 'reset@ypl.test', 'password' => 'Lama#1234']);
        DB::table('sessions')->insert([
            'id' => 'sess-lama', 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'uji', 'payload' => 'x', 'last_activity' => time(),
        ]);
        DB::table('password_resets')->insert([
            'email' => $user->email, 'token_hash' => Hash::make('ABCD1234'),
            'expires_at' => now()->addMinutes(15), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post('/reset-password', [
            'email' => $user->email, 'token' => 'WRONG000',
            'password' => 'Baru#5678', 'password_confirmation' => 'Baru#5678',
        ])->assertSessionHasErrors('token');
        $this->assertTrue(Hash::check('Lama#1234', $user->fresh()->password));

        $this->post('/reset-password', [
            'email' => $user->email, 'token' => 'abcd1234',
            'password' => 'Baru#5678', 'password_confirmation' => 'Baru#5678',
        ])->assertRedirect('/masuk');

        $this->assertTrue(Hash::check('Baru#5678', $user->fresh()->password));
        $this->assertNull($this->resetRowFor($user->email), 'Kode sekali pakai harus dihapus');
        $this->assertDatabaseMissing('sessions', ['id' => 'sess-lama']);
    }

    /** #1 — halaman reset cukup butuh email (kode diisi manual dari email). */
    public function test_halaman_reset_aksesibel_dengan_email_saja(): void
    {
        $this->get('/reset-password?email='.urlencode('x@ypl.test'))->assertOk()->assertSee('x@ypl.test');
        $this->get('/reset-password')->assertNotFound();
    }

    /** #2 — ruang staff menyembunyikan dokumen privat milik orang lain. */
    public function test_ruang_staff_menyembunyikan_dokumen_privat_orang_lain(): void
    {
        $pemilik = User::factory()->create(['role' => 'anggota']);
        $privat = Document::factory()->for($pemilik)->create([
            'title' => 'RAHASIA-PRIBADI-XYZ', 'slug' => 'rahasia-pribadi-xyz', 'visibility' => 'private',
        ]);
        $staff = User::factory()->create(['role' => 'staff']);
        $milikStaff = Document::factory()->for($staff)->create([
            'title' => 'PRIBADI-STAFF-SENDIRI', 'slug' => 'pribadi-staff-sendiri', 'visibility' => 'private',
        ]);

        $this->actingAs($staff)->get('/staff/dokumen')
            ->assertOk()
            ->assertDontSee($privat->title)
            ->assertSee($milikStaff->title);
    }

    /** Hardening — ekspor governance di balik auth. */
    public function test_ekspor_governance_butuh_login(): void
    {
        $this->post('/governance/ekspor')->assertRedirect('/masuk');

        $this->actingAs(User::factory()->create(['role' => 'anggota']))
            ->post('/governance/ekspor')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /** Hardening — CSP nonce: header dan atribut markup cocok, tanpa unsafe-inline script. */
    public function test_csp_memakai_nonce_dan_bukan_unsafe_inline_script(): void
    {
        $resp = $this->get('/');
        $resp->assertOk();

        $csp = $resp->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);

        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-([^']+)'/", $csp);
        preg_match("/'nonce-([^']+)'/", $csp, $m);
        $nonce = $m[1];

        $this->assertStringContainsString('nonce="'.$nonce.'"', $resp->getContent(), 'Script inline harus memakai nonce sama dengan header CSP');
    }
}
