<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Favorite;
use App\Models\RoleRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();

        $myDocs = Document::where('user_id', $user->id)->with('category')->latest()->limit(8)->get();

        $viewed = ActivityLog::with('document.category')
            ->where('user_id', $user->id)->where('action', 'view')
            ->latest()->limit(40)->get()
            ->unique('document_id')->take(8);

        $favDocs = Document::whereIn('id', Favorite::where('user_id', $user->id)->pluck('document_id'))
            ->with('category')->latest()->limit(4)->get();

        return view('profile.edit', [
            'user' => $user,
            'pendingRequest' => RoleRequest::where('user_id', $user->id)->where('status', 'pending')->first(),
            'stats' => [
                'unggahan' => Document::where('user_id', $user->id)->count(),
                'dilihat' => Document::where('user_id', $user->id)->sum('views_count'),
                'favorit' => Favorite::where('user_id', $user->id)->count(),
                'dibaca' => ActivityLog::where('user_id', $user->id)->where('action', 'view')->distinct('document_id')->count('document_id'),
            ],
            'myDocs' => $myDocs,
            'viewed' => $viewed,
            'favDocs' => $favDocs,
        ]);
    }

    public function update(Request $request)
    {
        // File yang melebihi batas server (upload_max_filesize) dibuang PHP secara diam-diam:
        // $request->hasFile() menjadi false sehingga validasi lolos tanpa foto. Deteksi manual.
        $attempted = $request->file('avatar');
        if ($attempted && ! $attempted->isValid()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'avatar' => 'Foto gagal diunggah — ukurannya melebihi batas server (maks 2 MB). Kecilkan dulu fotonya lalu coba lagi.',
            ]);
        }

        // Hanya data non-kritis yang boleh diubah (role & tanggal terdaftar dikunci).
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'academic_info' => ['nullable', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'wa_number' => ['nullable', 'string', 'max:25'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'birth_date.date' => 'Tanggal lahir tidak valid.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'avatar.image' => 'Foto profil wajib berupa file gambar.',
            'avatar.mimes' => 'Foto profil harus berformat JPG, PNG, GIF, atau WebP. Foto iPhone (HEIC) perlu dikonversi dulu.',
            'avatar.max' => 'Ukuran foto profil maksimal 2 MB.',
        ]);

        // Normalisasi nomor WA: 0896…, +62896…, 62896…, 896… → +62896…
        $wa = null;
        if (! empty($data['wa_number'])) {
            $wa = $this->normalizeWa($data['wa_number']);
            if (! $wa) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'wa_number' => 'Nomor WA tidak dikenali. Tulis nomor HP Indonesia, contoh: 089612345678 atau +6289612345678.',
                ]);
            }
        }

        $user = auth()->user();
        $user->fill([
            'name' => $data['name'],
            'academic_info' => $data['academic_info'] ?? null,
            'birth_place' => $data['birth_place'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'wa_number' => $wa,
        ]);
        if ($request->hasFile('avatar')) {
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }
        $user->save();

        return back()->with('ok', 'Profil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini salah.',
        ]);

        $request->user()->update(['password' => $data['password']]);
        \App\Models\ActivityLog::record(auth()->id(), null, 'password_change');

        return back()->with('ok', 'Kata sandi berhasil diganti. Gunakan kata sandi baru saat masuk berikutnya.');
    }

    /**
     * Normalisasi nomor HP Indonesia ke format +62….
     * Menerima: 0896…, +62896…, 62896…, 896…, (0896) 123-4567, dst.
     * Mengembalikan null bila tidak dikenali sebagai nomor HP Indonesia.
     */
    private function normalizeWa(string $input): ?string
    {
        return \App\Services\WaNumber::normalize($input);
    }
}
