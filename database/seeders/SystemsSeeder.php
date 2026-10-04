<?php

namespace Database\Seeders;

use App\Models\System;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SystemsSeeder extends Seeder
{
    /**
     * Registri sistem operasional YPL (BAB 16): target integrasi
     * sekaligus simpul knowledge graph yang nyata.
     */
    public function run(): void
    {
        $items = [
            ['Odoo ERP', 'System-of-record: purchase, HR, finance, fleet, maintenance.', null],
            ['Sistem Akademik', 'Kurikulum, penilaian, dan data siswa.', null],
            ['Sistem Asrama', 'Boarding, disiplin, dan perizinan santri.', null],
            ['HR & Kepegawaian', 'Cuti, kehadiran, dan data karyawan.', null],
            ['Keuangan', 'Anggaran, pengadaan, dan pelaporan.', null],
            ['Fleet & Transportasi', 'Kendaraan operasional dan penjadwalan.', null],
            ['Maintenance & Fasilitas', 'Work order dan aset fasilitas.', null],
            ['ICT & Helpdesk', 'Perangkat, jaringan, dan tiket bantuan.', null],
        ];

        foreach ($items as [$name, $desc, $url]) {
            System::firstOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'description' => $desc, 'url' => $url,
            ]);
        }
    }
}
