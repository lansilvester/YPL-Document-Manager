<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showRegister()
    {
        abort_unless(\App\Models\Setting::feature('reg'), 404, 'Pendaftaran ditutup sementara.');

        return view('auth.register');
    }

    public function register(Request $request)
    {
        abort_unless(\App\Models\Setting::feature('reg'), 404, 'Pendaftaran ditutup sementara.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Domain email dapat dibatasi via Pengaturan Web (SA); kosong = bebas.
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'academic_info' => ['nullable', 'string', 'max:255'],
            'wa_number' => ['nullable', 'string', 'max:25'],
        ]);

        $allowed = array_filter(array_map('trim', explode(',', (string) \App\Models\Setting::get('reg_domains', ''))));
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
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'email' => 'Pendaftaran hanya menerima email: '.implode(', ', $allowed).'.',
                ]);
            }
        }

        // Normalisasi nomor WA bila diisi (0896… → +62896…).
        $wa = null;
        if (! empty($data['wa_number'])) {
            $wa = \App\Services\WaNumber::normalize($data['wa_number']);
            if (! $wa) {
                throw \Illuminate\Validation\ValidationException::withMessages([
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
        $user->sendEmailVerificationNotification();
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

    public function verifyEmail(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
            $request->user()->update(['ypl_verified' => true]);
        }

        return redirect()->route('home')->with('ok', 'Email berhasil diverifikasi.');
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('ok', 'Tautan verifikasi telah dikirim ulang.');
    }
}
