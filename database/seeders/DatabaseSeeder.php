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
    /**
     * Seed awal pasca-briefing YPL SDL: hanya taxonomy handbook B01-B10
     * + koleksi library + akun admin. Tanpa kategori/format lama.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@lokon.or.id'], [
            'name' => 'Admin YPL',
            'password' => 'password',
            'role' => 'admin',
            'ypl_verified' => true,
            'academic_info' => 'Administrator Yayasan',
        ]);

        $this->call(YplHandbooksSeeder::class);
        $this->call(YplGovernanceSeeder::class);
        $this->call(RoleSeeder::class);

        $samples = [
            ['B01', 'Contoh Dokumen B01 — Ketentuan Umum', 'Dokumen contoh awal untuk koleksi B01 pasca-briefing.', 12],
            ['B02', 'Contoh Dokumen B02 — Akademik', 'Dokumen contoh awal untuk koleksi B02 pasca-briefing.', 8],
        ];

        foreach ($samples as [$code, $title, $desc, $views]) {
            $cat = Category::where('code', $code)->first();
            if (! $cat) {
                continue;
            }
            $content = '<p>'.e($desc).'</p><p>Dokumen contoh YPL Smart Digital Library.</p>';
            $doc = Document::firstOrCreate(['slug' => Str::slug($title)], [
                'user_id' => $admin->id,
                'category_id' => $cat->id,
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
