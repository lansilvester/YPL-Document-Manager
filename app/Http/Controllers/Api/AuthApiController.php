<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\WaNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthApiController extends ApiController
{
    /**
     * Login dan terbitkan token API (Sanctum).
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi salah.'],
            ]);
        }

        $token = $user->createToken($data['device_name'] ?? 'api');
        ActivityLog::record($user->id, null, 'login', ['via' => 'api']);

        return $this->ok([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Berhasil masuk.');
    }

    /**
     * Daftar akun baru (default peran anggota).
     */
    public function register(Request $request): JsonResponse
    {
        abort_unless(Setting::feature('reg'), 404, 'Pendaftaran ditutup sementara.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'academic_info' => ['nullable', 'string', 'max:255'],
            'wa_number' => ['nullable', 'string', 'max:25'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $allowed = array_filter(array_map('trim', explode(',', (string) Setting::get('reg_domains', ''))));
        if ($allowed) {
            $domain = strtolower(substr(strrchr($data['email'], '@'), 1));
            $ok = collect($allowed)->contains(function ($a) use ($domain) {
                $a = strtolower(ltrim($a, '@'));

                return $domain === $a || str_ends_with($domain, '.'.$a);
            });
            if (! $ok) {
                throw ValidationException::withMessages([
                    'email' => ['Pendaftaran hanya menerima email: '.implode(', ', $allowed).'.'],
                ]);
            }
        }

        $wa = null;
        if (! empty($data['wa_number'])) {
            $wa = WaNumber::normalize($data['wa_number']);
            if (! $wa) {
                throw ValidationException::withMessages([
                    'wa_number' => ['Nomor WA tidak dikenali. Contoh: 089612345678 atau +6289612345678.'],
                ]);
            }
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'academic_info' => $data['academic_info'] ?? null,
            'wa_number' => $wa,
            'ypl_verified' => false,
            'role' => 'anggota',
        ]);
        ActivityLog::record($user->id, null, 'register', ['via' => 'api']);

        $token = $user->createToken($data['device_name'] ?? 'api');

        return $this->created([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Akun dibuat.', null);
    }

    /**
     * Profil pengguna yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadCount(['documents', 'bookmarks']);

        return $this->ok(new UserResource($user), 'Profil pengguna.');
    }

    /**
     * Cabut token yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return $this->ok(null, 'Berhasil keluar.');
    }
}
