<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentRelation;
use App\Models\DocumentVersion;
use App\Models\System;
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
                'subtitle' => 'Landasan prinsip pengadaan YPL',
                'desc' => 'Prinsip dan kewenangan pengadaan barang dan jasa di lingkungan YPL.',
                'type' => 'policy', 'version' => '1.0', 'class' => 'internal',
                'effective' => '2026-01-05', 'review' => '2027-01-05', 'views' => 45,
                'unit' => 'Direktorat Keuangan', 'audience' => ['karyawan', 'pimpinan'],
                'body' => '<p>Pengadaan barang dan jasa wajib efisien, transparan, dan akuntabel.</p><p>Kebijakan ini menjadi acuan seluruh unit dalam merencanakan dan melaksanakan pengadaan.</p>',
            ],
            [
                'slug' => 'sop-pengadaan-barang-dan-jasa', 'code' => 'YPL-SOP-001',
                'title' => 'SOP Pengadaan Barang dan Jasa',
                'subtitle' => 'Alur operasional purchase request hingga serah terima',
                'desc' => 'Mengatur proses pengadaan barang dan jasa di lingkungan YPL.',
                'type' => 'sop', 'version' => '3.0', 'class' => 'internal',
                'effective' => '2026-09-01', 'review' => '2027-09-01', 'views' => 120,
                'unit' => 'Direktorat Keuangan', 'audience' => ['karyawan', 'guru', 'pimpinan'],
                'body' => '<p>Langkah: ajukan purchase request → verifikasi keuangan → persetujuan → pembelian → serah terima.</p><p>Setiap tahap tercatat dan dapat diaudit.</p>',
            ],
            [
                'slug' => 'wi-purchase-request', 'code' => 'YPL-WI-001',
                'title' => 'WI Purchase Request',
                'subtitle' => 'Cara mengisi formulir purchase request dengan benar',
                'desc' => 'Instruksi kerja pengisian dan pengajuan purchase request.',
                'type' => 'work_instruction', 'version' => '1.2', 'class' => 'internal',
                'effective' => '2026-09-01', 'review' => '2027-03-01', 'views' => 60,
                'unit' => 'Direktorat Keuangan', 'audience' => ['karyawan', 'guru'],
                'body' => '<p>Isi formulir dengan spesifikasi, estimasi biaya, dan justifikasi kebutuhan.</p><p>Lampirkan minimal satu pembanding harga untuk nilai di atas ambang batas.</p>',
            ],
            [
                'slug' => 'formulir-purchase-request', 'code' => 'YPL-FRM-001',
                'title' => 'Formulir Purchase Request',
                'subtitle' => 'Formulir resmi pengajuan pembelian',
                'desc' => 'Formulir resmi pengajuan pembelian barang dan jasa.',
                'type' => 'form', 'version' => '2.0', 'class' => 'internal',
                'effective' => '2026-09-01', 'review' => '2027-09-01', 'views' => 88,
                'unit' => 'Direktorat Keuangan', 'audience' => ['karyawan', 'guru'],
                'body' => '<p>Kolom: pemohon, unit, tanggal dibutuhkan, rincian barang/jasa, estimasi biaya, tanda tangan.</p><p>Serahkan ke Direktorat Keuangan setelah ditandatangani atasan langsung.</p>',
            ],
            [
                'slug' => 'struktur-organisasi-ypl', 'code' => 'YPL-ORG-001',
                'title' => 'Struktur Organisasi YPL',
                'subtitle' => 'Tata kelola dan kewenangan unit',
                'desc' => 'Bagan organisasi dan pembagian kewenangan unit di lingkungan YPL.',
                'type' => 'organization', 'version' => '1.0', 'class' => 'internal',
                'effective' => '2026-01-05', 'review' => '2027-01-05', 'views' => 52,
                'unit' => 'Direktorat Umum', 'audience' => ['karyawan', 'pimpinan'],
                'body' => '<p>Struktur organisasi menetapkan garis kewenangan, koordinasi antarunit, dan penanggung jawab layanan.</p>',
            ],
            [
                'slug' => 'register-risiko-ypl', 'code' => 'YPL-RSK-001',
                'title' => 'Register Risiko YPL',
                'subtitle' => 'Identifikasi dan mitigasi risiko institusi',
                'desc' => 'Daftar risiko utama YPL beserta tingkat dan rencana mitigasinya.',
                'type' => 'risk', 'version' => '1.0', 'class' => 'confidential',
                'effective' => '2026-02-01', 'review' => '2027-02-01', 'views' => 33,
                'unit' => 'Direktorat Umum', 'audience' => ['pimpinan'],
                'body' => '<p>Setiap risiko dinilai berdasarkan kemungkinan dan dampak, lalu dipetakan ke kendali yang berlaku.</p>',
            ],
            [
                'slug' => 'panduan-kepatuhan-ypl', 'code' => 'YPL-CMP-001',
                'title' => 'Panduan Kepatuhan YPL',
                'subtitle' => 'Kepatuhan regulasi dan standar mutu',
                'desc' => 'Ketentuan kepatuhan terhadap regulasi eksternal dan standar mutu internal YPL.',
                'type' => 'compliance', 'version' => '1.0', 'class' => 'internal',
                'effective' => '2026-03-01', 'review' => '2027-03-01', 'views' => 41,
                'unit' => 'Direktorat Umum', 'audience' => ['karyawan', 'pimpinan'],
                'body' => '<p>Panduan ini memetakan kewajiban kepatuhan, bukti audit, dan jadwal penilaian berkala.</p>',
            ],
            [
                'slug' => 'manual-mutu-ypl', 'code' => 'YPL-QLT-001',
                'title' => 'Manual Mutu YPL',
                'subtitle' => 'Sistem manajemen mutu layanan pendidikan',
                'desc' => 'Kerangka sistem manajemen mutu layanan pendidikan YPL.',
                'type' => 'quality', 'version' => '1.0', 'class' => 'internal',
                'effective' => '2026-04-01', 'review' => '2027-04-01', 'views' => 37,
                'unit' => 'Direktorat Akademik', 'audience' => ['guru', 'pimpinan'],
                'body' => '<p>Manual mutu menetapkan sasaran mutu, indikator kinerja, dan siklus perbaikan berkelanjutan.</p>',
            ],
            [
                'slug' => 'kebijakan-pengendalian-dokumen', 'code' => 'YPL-DOC-001',
                'title' => 'Kebijakan Pengendalian Dokumen',
                'subtitle' => 'Aturan penomoran, revisi, dan pengarsipan',
                'desc' => 'Aturan pengendalian dokumen: penomoran, versi, persetujuan, dan pengarsipan.',
                'type' => 'document_control', 'version' => '1.0', 'class' => 'internal',
                'effective' => '2026-05-01', 'review' => '2027-05-01', 'views' => 29,
                'unit' => 'Direktorat Umum', 'audience' => ['karyawan', 'pimpinan'],
                'body' => '<p>Semua dokumen resmi wajib bernomor unik, berversi, disetujui, dan diarsipkan sesuai kebijakan ini.</p>',
            ],
        ];

        $docs = [];
        foreach ($items as $it) {
            $content = '<p>'.e($it['desc']).'</p>'.$it['body'];
            $doc = Document::firstOrCreate(['slug' => $it['slug']], [
                'user_id' => $admin->id,
                'approver_id' => $admin->id,
                'pic_id' => $admin->id,
                'category_id' => $cat->id,
                'title' => $it['title'],
                'subtitle' => $it['subtitle'],
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
                'audience' => $it['audience'],
                'unit' => $it['unit'],
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

        // Rantai relasi: SOP → policy + wi + form + sistem; WI → form.
        $links = [
            ['sop-pengadaan-barang-dan-jasa', 'kebijakan-pengadaan-ypl', 'policy'],
            ['sop-pengadaan-barang-dan-jasa', 'wi-purchase-request', 'wi'],
            ['sop-pengadaan-barang-dan-jasa', 'formulir-purchase-request', 'form'],
            ['wi-purchase-request', 'formulir-purchase-request', 'form'],
            ['kebijakan-pengendalian-dokumen', 'kebijakan-pengadaan-ypl', 'policy'],
            ['register-risiko-ypl', 'panduan-kepatuhan-ypl', 'compliance'],
            ['manual-mutu-ypl', 'panduan-kepatuhan-ypl', 'compliance'],
        ];
        foreach ($links as [$from, $to, $type]) {
            DocumentRelation::firstOrCreate(
                ['document_id' => $docs[$from]->id, 'related_id' => $docs[$to]->id, 'system_id' => null],
                ['relation_type' => $type]
            );
        }

        // Kanonisasi field knowledge untuk 4 slug seed (aman di-seed ulang).
        foreach ($items as $it) {
            $docs[$it['slug']]->update([
                'subtitle' => $it['subtitle'],
                'audience' => $it['audience'],
                'unit' => $it['unit'],
                'pic_id' => $admin->id,
            ]);
        }

        // SOP Pengadaan berjalan di atas Odoo ERP.
        if ($odoo = System::where('slug', 'odoo-erp')->first()) {
            DocumentRelation::firstOrCreate(
                ['document_id' => $docs['sop-pengadaan-barang-dan-jasa']->id, 'related_id' => null, 'system_id' => $odoo->id],
                ['relation_type' => 'system']
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
