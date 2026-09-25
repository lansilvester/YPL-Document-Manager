<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\HtmlSanitizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@lokon.or.id'], [
            'name' => 'Admin YPL',
            'password' => 'password',
            'role' => 'admin',
            'ypl_verified' => true,
            'academic_info' => 'Administrator Yayasan',
        ]);

        $cats = collect([
            ['Matematika', 'Materi dan soal matematika'],
            ['Bahasa Indonesia', 'Materi bahasa dan sastra'],
            ['IPA', 'Ilmu pengetahuan alam'],
            ['Administrasi Yayasan', 'Dokumen departemen yayasan'],
        ])->map(fn ($c) => Category::firstOrCreate(['slug' => Str::slug($c[0])], [
            'name' => $c[0], 'description' => $c[1],
        ]));

        $samples = [
            ['Panduan Belajar Matematika Kelas 8', 'Kumpulan rumus dan contoh soal semester ganjil.', 128],
            ['Tata Bahasa Indonesia Praktis', 'Panduan menulis dan ejaan untuk tugas sekolah.', 86],
            ['Eksperimen IPA Sederhana', 'Panduan praktikum yang bisa dilakukan di kelas.', 54],
        ];
        foreach ($samples as $i => [$title, $desc, $views]) {
            $content = '<p>'.e($desc).'</p><p>Dokumen contoh untuk Yayasan Pendidikan Lokon.</p>';
            $doc = Document::firstOrCreate(['slug' => Str::slug($title)], [
                'user_id' => $admin->id,
                'category_id' => $cats[$i % $cats->count()]->id,
                'title' => $title,
                'description' => $desc,
                'visibility' => 'public',
                'source_type' => 'editor',
                'content' => $content,
                'views_count' => $views,
            ]);
            if (! $doc->file_path) {
                $path = 'documents/editor-seed-'.Str::lower(Str::random(12)).'.pdf';
                $safeContent = HtmlSanitizer::clean($doc->content ?? $content);
                Storage::disk('private')->put($path, Pdf::loadHTML('<h1>'.e($doc->title).'</h1><hr>'.$safeContent)->setPaper('a4')->output());
                $doc->update([
                    'file_path' => $path,
                    'file_size' => Storage::disk('private')->size($path),
                    'mime' => 'application/pdf',
                ]);
            }
        }
    }
}
