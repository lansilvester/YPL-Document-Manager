<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentRelation;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\HtmlSanitizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Rantai governance contoh pasca-briefing (idempotent):
 * Policy Pengadaan → SOP Pengadaan → WI Purchase Request → Formulir Purchase Request.
 */
class YplGovernanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@lokon.or.id')->first()
            ?? User::firstOrCreate(['email' => 'admin@lokon.or.id'], [
                'name' => 'Admin YPL', 'password' => 'password',
                'role' => 'admin', 'ypl_verified' => true,
            ]);

        $cat = Category::where('code', 'B06')->first()
            ?? Category::where('collection', 'governance')->first();
        if (! $cat) {
            $this->command->warn('Kategori governance belum ada — lewati.');

            return;
        }

        $items = [
            [
                'slug' => 'kebijakan-pengadaan-ypl', 'code' => 'YPL-POL-001',
                'title' => 'Kebijakan Pengadaan Barang dan Jasa',
                'desc' => 'Prinsip dan kewenangan pengadaan barang dan jasa di lingkungan YPL.',
                'type' => 'policy', 'version' => '1.0', 'class' => 'internal',
                'effective' => '2026-01-05', 'review' => '2027-01-05', 'views' => 45,
                'body' => '<p>Pengadaan barang dan jasa wajib efisien, transparan, dan akuntabel.</p><p>Kebijakan ini menjadi acuan seluruh unit dalam merencanakan dan melaksanakan pengadaan.</p>',
            ],
            [
                'slug' => 'sop-pengadaan-barang-dan-jasa', 'code' => 'YPL-SOP-001',
                'title' => 'SOP Pengadaan Barang dan Jasa',
                'desc' => 'Mengatur proses pengadaan barang dan jasa di lingkungan YPL.',
                'type' => 'sop', 'version' => '3.0', 'class' => 'internal',
                'effective' => '2026-09-01', 'review' => '2027-09-01', 'views' => 120,
                'body' => '<p>Langkah: ajukan purchase request → verifikasi keuangan → persetujuan → pembelian → serah terima.</p><p>Setiap tahap tercatat dan dapat diaudit.</p>',
            ],
            [
                'slug' => 'wi-purchase-request', 'code' => 'YPL-WI-001',
                'title' => 'WI Purchase Request',
                'desc' => 'Instruksi kerja pengisian dan pengajuan purchase request.',
                'type' => 'work_instruction', 'version' => '1.2', 'class' => 'internal',
                'effective' => '2026-09-01', 'review' => '2027-03-01', 'views' => 60,
                'body' => '<p>Isi formulir dengan spesifikasi, estimasi biaya, dan justifikasi kebutuhan.</p><p>Lampirkan minimal satu pembanding harga untuk nilai di atas ambang batas.</p>',
            ],
            [
                'slug' => 'formulir-purchase-request', 'code' => 'YPL-FRM-001',
                'title' => 'Formulir Purchase Request',
                'desc' => 'Formulir resmi pengajuan pembelian barang dan jasa.',
                'type' => 'form', 'version' => '2.0', 'class' => 'internal',
                'effective' => '2026-09-01', 'review' => '2027-09-01', 'views' => 88,
                'body' => '<p>Kolom: pemohon, unit, tanggal dibutuhkan, rincian barang/jasa, estimasi biaya, tanda tangan.</p><p>Serahkan ke Direktorat Keuangan setelah ditandatangani atasan langsung.</p>',
            ],
        ];

        $docs = [];
        foreach ($items as $it) {
            $content = '<p>'.e($it['desc']).'</p>'.$it['body'];
            $doc = Document::firstOrCreate(['slug' => $it['slug']], [
                'user_id' => $admin->id,
                'approver_id' => $admin->id,
                'category_id' => $cat->id,
                'title' => $it['title'],
                'description' => $it['desc'],
                'visibility' => 'public',
                'source_type' => 'editor',
                'content' => $content,
                'views_count' => $it['views'],
                'doc_code' => $it['code'],
                'doc_type' => $it['type'],
                'version' => $it['version'],
                'status' => 'active',
                'classification' => $it['class'],
                'effective_at' => $it['effective'],
                'review_at' => $it['review'],
            ]);
            if (! $doc->file_path) {
                $path = 'documents/gov-seed-'.Str::lower(Str::random(12)).'.pdf';
                $safe = HtmlSanitizer::clean($doc->content ?? $content);
                Storage::disk('private')->put($path, Pdf::loadHTML('<h1>'.e($doc->title).'</h1><hr>'.$safe)->setPaper('a4')->output());
                $doc->update([
                    'file_path' => $path,
                    'file_size' => Storage::disk('private')->size($path),
                    'mime' => 'application/pdf',
                ]);
            }
            $docs[$it['slug']] = $doc;
        }

        // Rantai relasi: SOP → policy + wi + form; WI → form.
        $links = [
            ['sop-pengadaan-barang-dan-jasa', 'kebijakan-pengadaan-ypl', 'policy'],
            ['sop-pengadaan-barang-dan-jasa', 'wi-purchase-request', 'wi'],
            ['sop-pengadaan-barang-dan-jasa', 'formulir-purchase-request', 'form'],
            ['wi-purchase-request', 'formulir-purchase-request', 'form'],
        ];
        foreach ($links as [$from, $to, $type]) {
            DocumentRelation::firstOrCreate(
                ['document_id' => $docs[$from]->id, 'related_id' => $docs[$to]->id],
                ['relation_type' => $type]
            );
        }

        // Satu entri riwayat publikasi per dokumen baru.
        foreach ($docs as $doc) {
            if ($doc->versions()->doesntExist()) {
                DocumentVersion::create([
                    'document_id' => $doc->id, 'user_id' => $admin->id,
                    'version' => $doc->version, 'from_status' => 'approval',
                    'to_status' => 'active', 'change_note' => 'Publikasi awal pasca-briefing.',
                ]);
            }
        }

        $this->command->info('Rantai governance YPL selesai dimuat.');
    }
}
