<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun demo per audiens BAB 11 (opsional, tidak dipanggil DatabaseSeeder).
 * Jalankan manual: php artisan db:seed --class=AudienceDemoSeeder
 */
class AudienceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $demo = [
            'siswa' => ['Siswa Demo', 'anggota'],
            'orang_tua' => ['Orang Tua Demo', 'anggota'],
            'guru' => ['Guru Demo', 'anggota'],
            'karyawan' => ['Karyawan Demo', 'anggota'],
            'pimpinan' => ['Pimpinan Demo', 'admin'],
            'mitra' => ['Mitra Demo', 'anggota'],
            'publik' => ['Publik Demo', 'anggota'],
        ];

        foreach ($demo as $audience => [$name, $role]) {
            User::updateOrCreate(
                ['email' => "demo-{$audience}@lokon.or.id"],
                [
                    'name' => $name,
                    'password' => 'password',
                    'role' => $role,
                    'audience' => $audience,
                    'ypl_verified' => true,
                    'academic_info' => 'Akun demo audiens '.$audience,
                ]
            );
        }
    }
}
