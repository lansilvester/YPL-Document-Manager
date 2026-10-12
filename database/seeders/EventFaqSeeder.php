<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentRelation;
use App\Models\Event;
use App\Models\Faq;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventFaqSeeder extends Seeder
{
    /**
     * Agenda kalender + FAQ awal (BAB 7/9): seed-managed seperti systems,
     * tertaut ke dokumen governance agar rantai knowledge hidup.
     */
    public function run(): void
    {
        $events = [
            ['Rapat Tinjauan SOP Pengadaan', 'foundation', 'Tinjauan berkala SOP pengadaan barang dan jasa bersama Direktorat Keuangan.', '2026-11-05 09:00', 'Ruang Rapat Yayasan'],
            ['Asesmen Tengah Semester', 'academic', 'Jadwal asesmen tengah semester seluruh unit sekolah.', '2026-10-20 07:30', 'Masing-masing unit'],
            ['Pemeriksaan Asrama Bulanan', 'boarding', 'Pemeriksaan fasilitas dan kedisiplinan asrama putra dan putri.', '2026-10-25 08:00', 'Kompleks Asrama'],
            ['Batas Pengajuan Purchase Request', 'deadline', 'Batas akhir pengajuan purchase request triwulan IV.', '2026-10-31 16:00', 'Direktorat Keuangan'],
        ];
        foreach ($events as [$title, $category, $desc, $starts, $loc]) {
            Event::firstOrCreate(['slug' => Str::slug($title)], [
                'title' => $title, 'category' => $category, 'description' => $desc,
                'starts_at' => $starts, 'location' => $loc,
            ]);
        }

        $faqs = [
            ['Bagaimana cara mengajukan purchase request?', 'Ajukan melalui menu Layanan → Purchase Request, lampirkan penawaran vendor, lalu pantau status di Permintaan Saya. Berlaku efektif setelah disetujui sesuai SOP Pengadaan Barang dan Jasa.', 'Layanan', 10],
            ['Siapa PIC pengadaan barang?', 'PIC pengadaan tercatat di Document Passport setiap SOP — lihat bagian PIC pada halaman detail dokumen terkait.', 'Governance', 20],
            ['Kapan dokumen ditinjau ulang?', 'Setiap dokumen Active memiliki tanggal review. Dokumen yang melewati jadwal otomatis bertanda Review Due dan masuk daftar pantauan governance.', 'Governance', 30],
            ['Apakah tamu bisa mengunduh dokumen?', 'Tamu dapat membaca dokumen publik tanpa mengunduh. Unduhan memerlukan akun terdaftar sesuai otoritas visibilitas dokumen.', 'Akses', 40],
        ];
        foreach ($faqs as [$q, $a, $cat, $order]) {
            Faq::firstOrCreate(['slug' => Str::slug(mb_substr($q, 0, 100))], [
                'question' => $q, 'answer' => $a, 'category' => $cat, 'sort_order' => $order,
            ]);
        }

        // Tautkan ke dokumen governance bila ada (pola YplGovernanceSeeder).
        $sop = Document::where('slug', 'sop-pengadaan-barang-dan-jasa')->first();
        if ($sop) {
            $event = Event::where('slug', 'rapat-tinjauan-sop-pengadaan')->first();
            $faq = Faq::where('slug', Str::slug(mb_substr('Bagaimana cara mengajukan purchase request?', 0, 100)))->first();
            if ($event) {
                DocumentRelation::firstOrCreate(
                    ['document_id' => $sop->id, 'related_id' => null, 'system_id' => null, 'event_id' => $event->id, 'faq_id' => null],
                    ['relation_type' => 'calendar']
                );
            }
            if ($faq) {
                DocumentRelation::firstOrCreate(
                    ['document_id' => $sop->id, 'related_id' => null, 'system_id' => null, 'event_id' => null, 'faq_id' => $faq->id],
                    ['relation_type' => 'faq']
                );
            }
        }
    }
}
