<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            // Fase 4: registrasi divalidasi sistem internal YPL (domain email yayasan)
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'regex:/@(.+\.)?(lokon|ypl)(\.or\.id|\.ac\.id|\.sch\.id)?$/i'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'academic_info' => ['nullable', 'string', 'max:255'],
        ], ['email.regex' => 'Gunakan email resmi YPL untuk mendaftar.']);

        // Default registrasi web = Anggota. Peningkatan peran wajib lewat persetujuan Admin/Super Admin.
        $user = User::create([
            ...$data,
            'ypl_verified' => true,
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
}
