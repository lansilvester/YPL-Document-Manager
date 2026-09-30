<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Definisi peran role-aware BAB 11: peran sistem + audiens,
 * beserta scope koleksi dan prioritas homepage. Idempotent.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $all = Role::COLLECTIONS;
        $gov = ['handbook', 'governance', 'academic', 'boarding', 'staff', 'parent', 'public'];

        $defs = [
            // Peran sistem (terkunci, tak dapat dihapus).
            ['anggota', 'Anggota', 'Pengguna terdaftar standar.', 50, true, $all, null],
            ['staff', 'Staff', 'Pengelola dokumen dan layanan.', 40, true, $all, null],
            ['admin', 'Admin', 'Administrator operasional.', 20, true, $all, null],
            ['superadmin', 'Super Admin', 'Developer / pemilik platform.', 0, true, $all, null],
            // Audiens BAB 11 (prioritas kecil = diprioritaskan).
            ['siswa', 'Siswa', 'Student Handbook, akademik, asrama, kalender, pengajuan.', 10, false,
                ['handbook', 'academic', 'boarding', 'public'],
                ['quick' => ['handbook', 'sop', 'form', 'calendar', 'ask', 'policy'], 'explore' => ['handbooks', 'learning', 'boarding', 'governance']]],
            ['orang_tua', 'Orang Tua', 'Akademik, asrama, keuangan, kalender, formulir.', 11, false,
                ['handbook', 'academic', 'boarding', 'parent', 'public'],
                ['quick' => ['form', 'calendar', 'handbook', 'policy', 'ask', 'sop'], 'explore' => ['learning', 'boarding', 'handbooks', 'governance']]],
            ['guru', 'Guru', 'Akademik, kurikulum, asesmen, policy, SOP.', 12, false,
                ['handbook', 'governance', 'academic', 'staff', 'public'],
                ['quick' => ['handbook', 'policy', 'sop', 'form', 'calendar', 'ask'], 'explore' => ['learning', 'handbooks', 'governance', 'boarding']]],
            ['karyawan', 'Karyawan', 'HR, layanan, policy, SOP, pengajuan.', 13, false,
                ['handbook', 'governance', 'staff', 'public'],
                ['quick' => ['form', 'sop', 'policy', 'calendar', 'handbook', 'ask'], 'explore' => ['governance', 'handbooks', 'learning', 'boarding']]],
            ['pimpinan', 'Pimpinan', 'Governance, compliance, approvals, dashboard.', 5, false,
                $gov,
                ['quick' => ['policy', 'sop', 'form', 'calendar', 'handbook', 'ask'], 'explore' => ['governance', 'handbooks', 'learning', 'boarding']]],
            ['mitra', 'Mitra', 'Handbook mitra, kontak, kolaborasi.', 60, false,
                ['handbook', 'public'],
                ['quick' => ['handbook', 'calendar', 'form', 'ask', 'policy', 'sop'], 'explore' => ['handbooks', 'learning', 'boarding', 'governance']]],
            ['publik', 'Publik', 'Profil, program, prestasi, publikasi.', 70, false,
                ['public'],
                ['quick' => ['handbook', 'calendar', 'ask', 'policy', 'sop', 'form'], 'explore' => ['handbooks', 'learning', 'boarding', 'governance']]],
        ];

        foreach ($defs as [$slug, $name, $desc, $priority, $system, $collections, $homepage]) {
            Role::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $desc,
                'priority' => $priority,
                'is_system' => $system,
                'scopes' => ['collections' => $collections],
                'homepage' => $homepage,
            ]);
        }
    }
}
