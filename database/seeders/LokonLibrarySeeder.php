<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Pustaka awal YPL: 5 Matematika + 5 Bahasa Inggris + 5 Ekonomi,
 * diampu oleh Staff Alan Lengkong.
 */
class LokonLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $alan = User::firstOrCreate(['email' => 'alan@lokon.or.id'], [
            'name' => 'Alan Lengkong',
            'password' => 'password',
            'role' => 'staff',
            'ypl_verified' => true,
            'academic_info' => 'Staff Perpustakaan Digital YPL',
        ]);

        $cats = [];
        foreach (['Matematika' => 'Materi, rumus, dan kalkulus', 'Bahasa Inggris' => 'Grammar, sastra, dan makalah berbahasa Inggris', 'Ekonomi' => 'Mikro, makro, dan klasik ekonomi'] as $name => $desc) {
            $cats[$name] = Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'description' => $desc]);
        }

        $tags = [];
        foreach (['Unggulan' => 'gold', 'Baru' => 'navy', 'Referensi' => 'steel', 'Soal Latihan' => 'charcoal', 'Klasik' => 'red', 'Buku Teks' => 'slate'] as $name => $color) {
            $tags[$name] = Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'color' => $color]);
        }

        $items = [
            // [file, judul, deskripsi, kategori, visibilitas, [tag]]
            ['matematika-rumus-kalkulus.pdf', 'Rumus Kalkulus Lengkap (Cheat Sheet)', 'Lembar contekan kalkulus: turunan, integral, limit, dan deret. Sumber: Paul\'s Online Notes, Lamar University.', 'Matematika', 'public', ['Referensi', 'Soal Latihan']],
            ['matematika-rumus-aljabar.pdf', 'Rumus Aljabar Lengkap (Cheat Sheet)', 'Sifat eksponen, logaritma, pemfaktoran, dan persamaan kuadrat. Sumber: Paul\'s Online Notes, Lamar University.', 'Matematika', 'public', ['Referensi']],
            ['matematika-rumus-trigonometri.pdf', 'Rumus Trigonometri Lengkap (Cheat Sheet)', 'Identitas trigonometri, nilai sudut istimewa, dan rumus jumlah-selisih sudut. Sumber: Paul\'s Online Notes, Lamar University.', 'Matematika', 'public', ['Referensi', 'Soal Latihan']],
            ['matematika-kalkulus-whitman.pdf', 'Kalkulus — Guichard & Keisler (Whitman College)', 'Buku teks kalkulus terbuka: limit, turunan, integral, hingga kalkulus multivariabel.', 'Matematika', 'public', ['Buku Teks', 'Unggulan']],
            ['matematika-kalkulus-openstax-v1.pdf', 'Kalkulus Volume 1 (OpenStax)', 'Buku teks kalkulus terbuka Volume 1: fungsi, limit, turunan, dan aplikasinya. Lisensi CC BY.', 'Matematika', 'public', ['Buku Teks', 'Baru']],
            ['inggris-attention-is-all-you-need.pdf', 'Attention Is All You Need (Makalah NLP)', 'Makalah riset berbahasa Inggris yang memperkenalkan arsitektur Transformer (Vaswani dkk., 2017). Bahan reading akademik.', 'Bahasa Inggris', 'public', ['Referensi', 'Unggulan']],
            ['inggris-hamlet-folger.pdf', 'Hamlet — William Shakespeare (Edisi Folger)', 'Naskah drama Hamlet edisi Folger Shakespeare Library. Bahan sastra Inggris klasik.', 'Bahasa Inggris', 'public', ['Klasik']],
            ['inggris-romeo-juliet-folger.pdf', 'Romeo and Juliet — William Shakespeare (Edisi Folger)', 'Naskah drama Romeo and Juliet edisi Folger Shakespeare Library.', 'Bahasa Inggris', 'public', ['Klasik']],
            ['inggris-othello-folger.pdf', 'Othello — William Shakespeare (Edisi Folger)', 'Naskah drama Othello edisi Folger Shakespeare Library. Bahan sastra dan analisis karakter.', 'Bahasa Inggris', 'public', ['Klasik', 'Baru']],
            ['inggris-tale-two-cities-dickens.pdf', 'A Tale of Two Cities — Charles Dickens', 'Novel klasik Inggris karya Charles Dickens (domain publik). Bacaan sastra Revolusi Prancis.', 'Bahasa Inggris', 'public', ['Klasik', 'Unggulan']],
            ['ekonomi-prinsip-3e-openstax.pdf', 'Prinsip-Prinsip Ekonomi 3e (OpenStax)', 'Buku teks pengantar ekonomi mikro & makro edisi 3. Lisensi CC BY.', 'Ekonomi', 'public', ['Buku Teks', 'Unggulan']],
            ['ekonomi-prinsip-2e-openstax.pdf', 'Prinsip-Prinsip Ekonomi 2e (OpenStax)', 'Buku teks pengantar ekonomi edisi 2 (Greenlaw & Shapiro). Lisensi CC BY.', 'Ekonomi', 'public', ['Buku Teks']],
            ['ekonomi-mikro-2e-openstax.pdf', 'Mikroekonomi 2e (OpenStax)', 'Buku teks mikroekonomi terbuka: perilaku konsumen, teori firma, dan struktur pasar.', 'Ekonomi', 'public', ['Buku Teks', 'Referensi']],
            ['ekonomi-makro-2e-openstax.pdf', 'Makroekonomi 2e (OpenStax)', 'Buku teks makroekonomi terbuka: PDB, inflasi, pengangguran, dan kebijakan.', 'Ekonomi', 'public', ['Buku Teks', 'Referensi']],
            ['ekonomi-wealth-of-nations-smith.pdf', 'The Wealth of Nations — Adam Smith', 'Karya klasik ekonomi politik Adam Smith (domain publik). Fondasi ilmu ekonomi modern.', 'Ekonomi', 'public', ['Klasik', 'Unggulan']],
        ];

        foreach ($items as [$file, $title, $desc, $cat, $vis, $tagNames]) {
            $path = 'documents/'.$file;
            if (! file_exists(storage_path('app/public/'.$path))) {
                $this->command->warn("Lewati (file belum diunduh): {$file}");
                continue;
            }
            $doc = Document::firstOrCreate(['slug' => Str::slug($title)], [
                'user_id' => $alan->id,
                'category_id' => $cats[$cat]->id,
                'title' => $title,
                'description' => $desc,
                'file_path' => $path,
                'file_size' => filesize(storage_path('app/public/'.$path)),
                'mime' => 'application/pdf',
                'visibility' => $vis,
                'source_type' => 'upload',
            ]);
            $doc->tags()->sync(collect($tagNames)->map(fn ($n) => $tags[$n]->id)->all());
            ActivityLog::record($alan->id, $doc->id, 'upload', ['via' => 'staff-seed']);
        }

        $this->command->info('Pustaka Alan Lengkong selesai dimuat.');
    }
}
