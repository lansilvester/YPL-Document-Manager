<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class YplHandbooksSeeder extends Seeder
{
    /**
     * Koleksi fondasi YPL SDL Phase 1: 10 Living Handbook B01-B10
     * + 6 koleksi Library. Idempotent via firstOrCreate.
     */
    public function run(): void
    {
        $handbooks = [
            ['B01', 'Ketentuan Umum', 'Foundation rules dan ketentuan umum.', 'navy'],
            ['B02', 'Akademik & Pengembangan Potensi', 'Academic knowledge dan student development.', 'blue'],
            ['B03', 'Pendidikan & Kehidupan Asrama', 'Boarding life, discipline, safety, permission dan development.', 'red'],
            ['B04', 'Administrasi, Keuangan & Layanan Siswa', 'Administrative and student services.', 'deep-navy'],
            ['B05', 'Panduan Orang Tua', 'Parent experience dan parent responsibilities.', 'purple'],
            ['B06', 'Tata Kelola, Kebijakan & Sistem Manajemen YPL', 'Governance spine / master governance layer.', 'slate'],
            ['B07', 'Panduan Siswa', 'Student experience dan expectations.', 'teal'],
            ['B08', 'Panduan Guru', 'Teacher operational and academic knowledge.', 'violet'],
            ['B09', 'Panduan Pembimbing Karakter & Pamong', 'Mentor/pamong operational knowledge.', 'charcoal'],
            ['B10', 'Panduan Institusi Mitra', 'Partner and external stakeholder guidance.', 'brown'],
        ];

        foreach ($handbooks as $i => [$code, $name, $desc, $color]) {
            Category::firstOrCreate(['code' => $code], [
                'name' => $code.' — '.$name,
                'slug' => Str::slug($code.'-'.$name),
                'description' => $desc,
                'collection' => 'handbook',
                'color' => $color,
                'sort_order' => $i + 1,
            ]);
        }

        $collections = [
            ['Governance Library', 'Policy • Regulation • SOP', 'governance'],
            ['Academic Library', 'Academic resources', 'academic'],
            ['Boarding Library', 'Student life & residential knowledge', 'boarding'],
            ['Staff Library', 'HR, services, policies & SOP', 'staff'],
            ['Parent Library', 'Academic, finance & boarding info', 'parent'],
            ['Public Collection', 'Profile, programs & publications', 'public'],
        ];

        foreach ($collections as $i => [$name, $desc, $key]) {
            Category::firstOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'description' => $desc,
                'collection' => $key,
                'color' => 'steel',
                'sort_order' => 20 + $i,
            ]);
        }
    }
}
