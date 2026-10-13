<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetCode;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\WaNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showRegister()
    {
        abort_unless(Setting::feature('reg'), 404, 'Pendaftaran ditutup sementara.');

        return view('auth.register');
    }

    public function register(Request $request)
    {
        abort_unless(Setting::feature('reg'), 404, 'Pendaftaran ditutup sementara.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Domain email dapat dibatasi via Pengaturan Web (SA); kosong = bebas.
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'academic_info' => ['nullable', 'string', 'max:255'],
            'wa_number' => ['nullable', 'string', 'max:25'],
        ]);

        $allowed = array_filter(array_map('trim', explode(',', (string) Setting::get('reg_domains', ''))));
        if ($allowed) {
            $domain = strtolower(substr(strrchr($data['email'], '@'), 1));
            $ok = false;
            foreach ($allowed as $a) {
                $a = strtolower(ltrim($a, '@'));
                if ($domain === $a || str_ends_with($domain, '.'.$a)) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                throw ValidationException::withMessages([
                    'email' => 'Pendaftaran hanya menerima email: '.implode(', ', $allowed).'.',
                ]);
            }
        }

        // Normalisasi nomor WA bila diisi (0896… → +62896…).
        $wa = null;
        if (! empty($data['wa_number'])) {
            $wa = WaNumber::normalize($data['wa_number']);
            if (! $wa) {
                throw ValidationException::withMessages([
                    'wa_number' => 'Nomor WA tidak dikenali. Tulis nomor HP Indonesia, contoh: 089612345678 atau +6289612345678.',
                ]);
            }
        }

        // Default registrasi web = Anggota. Peningkatan peran wajib lewat persetujuan Admin/Super Admin.
        $user = User::create([
            ...$data,
            'wa_number' => $wa,
            'ypl_verified' => false,
            'role' => 'anggota',
        ]);

        Auth::login($user);
        ActivityLog::record($user->id, null, 'register');
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('ok', 'Akun dibuat. Selamat datang di Lokon Dokumen Digital.');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($cred, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        ActivityLog::record(Auth::id(), null, 'login');

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showForgot()
    {
        return view('auth.forgot');
    }

    /**
     * Reset kata sandi tanpa layanan email dedicated: kode 8 karakter dibuat
     * dan dikirim ke EMAIL TERDAFTAR. Kode tidak pernah dikembalikan dalam
     * respons HTTP (mencegah penyadapan/take-over). Respons selalu sama
     * (issued) untuk menghindari enumerasi akun.
     */
    public function sendResetToken(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $email = strtolower(trim($data['email']));

        $user = User::where('email', $email)->first();
        $ttl = (int) env('PASSWORD_RESET_TTL', 15);

        if ($user) {
            $token = strtoupper(Str::random(8));
            DB::table('password_resets')->where('email', $email)->delete();
            DB::table('password_resets')->insert([
                'email' => $email,
                'token_hash' => Hash::make($token),
                'expires_at' => now()->addMinutes($ttl),
                'ip' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            ActivityLog::record($user->id, null, 'password_reset_request');

            try {
                Mail::to($email)->send(new PasswordResetCode($token, $ttl));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('auth.forgot-result', [
            'reset_email' => $email,
            'ttl' => $ttl,
        ]);
    }

    public function showReset(Request $request)
    {
        abort_if($request->missing('email'), 404);

        return view('auth.reset', [
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        // Normalisasi kode (huruf besar, tanpa spasi) sebelum validasi format.
        $request->merge(['token' => strtoupper(trim((string) $request->input('token')))]);

        $data = $request->validate([
            'token' => ['required', 'string', 'size:8', 'regex:/^[A-Z0-9]+$/'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);
        $email = strtolower(trim($data['email']));

        $row = DB::table('password_resets')->where('email', $email)->whereNull('used_at')->first();
        abort_unless($row, 403, 'Tidak ada permintaan reset untuk email tersebut.');
        abort_if(now()->greaterThan(Carbon::parse($row->expires_at)), 403, 'Kode reset sudah kedaluwarsa.');

        if (! Hash::check($data['token'], $row->token_hash)) {
            throw ValidationException::withMessages(['token' => 'Kode reset tidak sesuai.']);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->update(['password' => $data['password']]);

        DB::table('password_resets')->where('email', $email)->delete();
        // Cabut semua sesi lama akun ini (kode reset membatalkan sesi aktif).
        DB::table('sessions')->where('user_id', $user->id)->delete();
        ActivityLog::record($user->id, null, 'password_reset');

        return redirect()->route('login')->with('ok', 'Kata sandi berhasil diatur ulang. Silahkan masuk dengan kata sandi baru.');
    }
}
