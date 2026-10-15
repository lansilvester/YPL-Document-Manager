# Dokumentasi & Presentasi — YPL Smart Digital Library (LOSNITO)

> Folder ini adalah **pusat dokumen presentasi** aplikasi. Semua materi —
> briefing awal, diagram, slide, dan penjelasan — dikumpulkan di satu tempat
> agar bisa dibaca **atasan/non-teknis** maupun **developer/teknis**.

## 1. Daftar Isi Folder

| File | Isi | Untuk siapa |
|---|---|---|
| `YPL Smart Digital Library Website.docx` | **Briefing awal** (sumber requirements, 21 BAB) | Semua — acuan kebenaran fitur |
| `Use Case Diagram — YPL Smart Digital Library (LOSNITO).png` | Diagram aktor vs fitur | Atasan + developer |
| `ERD — YPL Smart Digital Library (LOSNITO).png` | Diagram relasi 23 tabel database | Developer (+ atasan, lihat §5) |
| `YPL-SDL-Laporan-Lengkap.pptx` | **Slide utama 22 slide** — progress, fitur, UML, roadmap | Presentasi ke atasan |
| `LOSNITO-Progress-Report.pptx` | Laporan progress (versi awal) | Arsip |
| `ERD-DFD-UseCase.pptx` | Slide diagram UML (versi awal) | Arsip |
| `screenshots/` | Tangkapan layar aplikasi (desktop + mobile) | Pendukung slide |
| `use-case-diagram.{mmd,png,svg}` | Sumber diagram use case (Mermaid) | Developer |
| `generate_erd.py` | Generator ERD dari skema MySQL aktual | Developer |
| `generate_diagrams.py` | Generator slide UML via python-pptx | Developer |

## 2. Penjelasan Non-Teknis (untuk atasan)

**LOSNITO = perpustakaan digital Yayasan Pendidikan Lokon.** Semua dokumen
yayasan (kebijakan, SOP, handbook, formulir) tersimpan rapi, bisa dicari,
dibaca nyaman di HP/laptop, dan setiap dokumen resmi punya **masa berlaku +
penanggung jawab** yang terpantau otomatis.

Alur kerja aplikasi diringkas dalam 4 tahap:

1. **DISCOVER (Temukan)** — Cari dokumen, jelajahi koleksi, dapat rekomendasi.
2. **KNOW (Ketahui)** — Baca imersif (mode baca nyaman, 3 tema, catatan pribadi).
3. **UNDERSTAND (Pahami)** — Tanya jawab otomatis bersumber resmi + peta relasi antar-dokumen.
4. **ACT (Bertindak)** — Ajukan layanan (cuti, transport, dsb.), pantau kalender agenda.

**Keamanan untuk atasan:** dokumen bertanda *privat* hanya terlihat pemilik +
pimpinan; tamu hanya melihat dokumen resmi yang masih berlaku; 1 Super Admin.

## 3. Penjelasan Teknis (untuk developer)

- **Stack:** Laravel 13 + Blade + Tailwind, PDF.js reader, MySQL produksi /
  SQLite `:memory:` untuk test. Suite: **183 test hijau**.
- **Otorisasi terpusat:** `Document::isVisibleTo()` + `scopeVisibleTo()`
  (paritas baris-vs-query) + `DocumentPolicy` + middleware
  `staff`/`admin`/`superadmin`. Kriteria-12 Opsi A live.
- **Database:** 32 tabel (`ypl_smart_digital_library`, utf8mb4, FULLTEXT);
  23 tabel domain divisualkan di ERD (9 tabel infra: cache/jobs/sessions/
  password_resets/migrations disembunyikan agar diagram fokus).
- **Domain data (warna di ERD):**
  - 🔵 Dokumen — `documents` (pusat), `document_versions`, `document_relations`,
    `document_tag`, `categories`, `tags`
  - 🔴 Orang & akses — `users` (pusat), `roles`, `role_requests`, `contacts`, `units`
  - 🟢 Interaksi pembaca — `bookmarks`, `likes`, `reading_notes`,
    `reading_positions`, `reading_prefs`
  - 🟡 Layanan & agenda — `service_requests`, `events`, `faqs`, `systems`
  - ⚫ Sistem — `activity_logs`, `notifications`, `settings`
- **Status vs briefing 21 BAB:** 19 selesai penuh; tersisa **BAB 16 integrasi
  Odoo** (sistem tercatat sebagai simpul `systems`, API menyusul terakhir).

## 4. Cara Membaca Use Case Diagram (non-teknis)

Gambar orang = **siapa** yang memakai (Tamu, Anggota, Siswa/Guru/Pimpinan,
Staff, Admin, Super Admin). Oval = **apa** yang bisa dilakukan (baca, cari,
tanya, ajukan layanan, kelola dokumen, pantau). Garis = siapa boleh apa.
Aturan emas: **semakin ke kanan peran, semakin banyak yang boleh dilakukan**;
Tamu paling sedikit, Super Admin paling banyak (1 akun).

## 5. Cara Membaca ERD (non-teknis)

Setiap kotak = satu **lemari arsip** (mis. `documents` = lemari dokumen,
`users` = daftar orang). Garis antar-kotak = **kaitan** (mis. dokumen
"dimiliki oleh" orang, catatan "menempel pada" dokumen). Kotak `documents`
dan `users` di tengah karena paling banyak dikaitkan — itu normal dan sehat.

## 6. Urutan Presentasi yang Disarankan (ke atasan)

1. Buka `YPL-SDL-Laporan-Lengkap.pptx` — 22 slide sudah sistematis
   (angka kunci → kesesuaian briefing → fitur → keamanan → UML → roadmap).
2. Tunjukkan aplikasi hidup (menu: Beranda → Perpustakaan → Baca → Tanya).
3. Tutup dengan roadmap: **Odoo terakhir**, lalu go-live bertahap.

## 7. Regenerasi Diagram (teknis)

```bash
# 1. Dump skema + FK dari MySQL aktual:
mysql -uroot -proot ypl_smart_digital_library -N -e "
  SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA='ypl_smart_digital_library'
  AND TABLE_NAME NOT IN ('cache','cache_locks','failed_jobs','job_batches',
    'jobs','migrations','password_reset_tokens','password_resets','sessions')
  ORDER BY TABLE_NAME, ORDINAL_POSITION;" > /tmp/schema.txt
mysql -uroot -proot ypl_smart_digital_library -N -e "
  SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA='ypl_smart_digital_library'
  AND REFERENCED_TABLE_NAME IS NOT NULL
  ORDER BY TABLE_NAME;" > /tmp/fks.txt

# 2. Render ulang:
python3 presentasi/generate_erd.py   # membaca /tmp/schema.txt & /tmp/fks.txt di atas

# Slide UML:
python3.11 presentasi/generate_diagrams.py
```
