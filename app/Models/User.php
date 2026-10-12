<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'avatar_path', 'academic_info', 'birth_place', 'birth_date', 'wa_number', 'role', 'audience', 'ypl_verified'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Audiens BAB 11 yang dapat dipetakan ke pengalaman role-aware. */
    public const AUDIENCES = ['siswa', 'orang_tua', 'guru', 'karyawan', 'pimpinan', 'mitra', 'publik'];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function readingPref()
    {
        return $this->hasOne(ReadingPref::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'admin', 'superadmin'], true);
    }

    /**
     * Peran audiens BAB 11 (siswa/orang_tua/guru/…) bila diset,
     * jika tidak jatuh ke peran sistem berbasis kolom role.
     */
    public function audienceRole(): Role
    {
        if ($this->audience && in_array($this->audience, self::AUDIENCES, true)) {
            $role = Role::query()->where('slug', $this->audience)->first();
            if ($role) {
                return $role;
            }
        }

        return Role::for($this);
    }

    protected $attributes = ['role' => 'anggota'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
        ];
    }
}
