# Report — YPL Smart Digital Library (Losnito)

Tanggal: 13 Oktober 2026 · Baseline terverifikasi: 155 test lolos, Pint bersih.

## 1. Ringkasan Website

Platform knowledge digital Yayasan Pendidikan Lokon: bukan sekadar repository PDF, melainkan **Digital Knowledge, Governance & Community Platform** dengan prinsip **DISCOVER → KNOW → UNDERSTAND → ACT**. Dua mode utama: **Library Mode** (discovery) dan **Reader Mode** (baca imersif). Blueprint acuan: dokumen briefing 21 bab (BAB 1–21).

**Teknologi:** Laravel 13 (PHP ^8.3), Tailwind 4 + Vite 8, SQLite (lokal/testing) / MySQL (production InfinityFree), `barryvdh/laravel-dompdf` (ekspor PDF), `ezyang/htmlpurifier` (sanitasi HTML), PHPUnit 12, Laravel Pint, Laravel Boost MCP.

## 2. Progress per Phase (Blueprint BAB 18)

| Phase | Status |
|---|---|
| 1 Foundation (IA, taxonomy, Library Mode) | ✅ Selesai |
| 2 Living Books (bookshelf B01–B10, book detail, Reader Mode) | ✅ Selesai + fidelity visual BAB 4 |
| 3 Governance (passport, lifecycle, versioning, approval, relasi, impact map) | ✅ Selesai (**version control nyata**: bump otomatis + revisi saat edit + "What changed?"; snapshot isi per versi; otomasi review-due + archive policy retensi otomatis) |
| 4 Services (forms, workflow, My Requests) | ✅ Selesai (kurang: integrasi Odoo) |
| 5 Intelligence (universal search, Ask YPL, rekomendasi, graph) | ✅ Fungsional — Ask YPL v1 + driver Gemini testing, Recommendations selesai, universal search selesai, **Peta Pengetahuan `/peta-pengetahuan`** + blok terkait selesai |
| 6 Ecosystem (Odoo, role dashboard, analitik) | 🔄 Berjalan — **dashboard per peran `/dashboard` (audience-aware) selesai**; Community/Calendar/Insight nyata; **analytics `activity_logs` selesai**; sisa: Odoo |

## 3. Daftar Fitur

- **Home (BAB 5 ketat):** hero + universal search, 6 quick actions, Explore 4 koleksi, What's New, YPL Today (deadline review nyata).
- **Library `/perpustakaan`:** bookshelf 10 Handbook B01–B10 berbentuk buku 3D, filter koleksi (handbook/governance/academic/boarding/staff/parent/public), book detail (cover besar, TOC, Open Book, Bookmark).
- **Reader Mode `/baca/{slug}`:** 2-halaman desktop / 1-halaman mobile, page-flip 550ms + reduced-motion, TOC drawer terang, lompat halaman + grid, search-in-book, toolbar bawah (Contents/Search/Bookmark/Aa/Ask YPL/counter/Reading %), keyboard + swipe, persist posisi & preferensi. **Panel Rekomendasi di sidebar kanan.** **Ask YPL inline** (§8/§14) — tanya & ringkas dokumen ter-scoped tanpa meninggalkan Reader; sumber dibuka tab baru.
- **Dokumen:** unggah PDF (maks 9 MB), tulis via editor (sanitasi HTML), unduh PDF/HTML, semat (embed), suka (like), bagikan, Dokumen Saya, visibilitas public/restricted/private.
- **Governance:** Document Passport (DOC ID, tipe, owner, approver, PIC, effective/review, klasifikasi, status, audience, unit), lifecycle draft→review→approval→active→review-due→archived + approval workflow + View History + **"What changed?"** (diff bidang antar revisi), relasi Handbook→Policy→SOP→Form→PIC→System, listing filter tipe/status/audience/unit, CRUD Handbook khusus admin. **Version control nyata:** versi naik otomatis saat edit (bila tidak diisi), tiap perubahan metadata/isi mencatat revisi + catatan perubahan. Tipe mencakup governance penuh (organization/risk/compliance/quality/document_control) dengan label rapi via `Document::typeLabel()`.
- **Services `/layanan`:** katalog 8 layanan ("Saya ingin…"), pengajuan + Permintaan Saya, kelola + putusan staff (pending→approved/rejected→done).
- **Systems `/sistem`:** registri 8 sistem operasional (Odoo, Akademik, Asrama, HR, Keuangan, Fleet, Maintenance, ICT) + dokumen tertaut.
- **Community `/community`:** direktori unit, peran, dan orang (login untuk direktori orang) + **kontak PIC publik** (email/telepon) + pengetahuan komunitas (FAQ). Unit & kontak dikelola admin (`/admin/unit`, `/admin/kontak`).
- **Ask YPL `/tanya`:** jawaban ekstraktif **bersitasi section-level** (§ heading) dari knowledge sesuai otoritas user; driver Gemini (`gemini-3.8-flash`) untuk testing — gagal/timeout otomatis fallback ekstraktif. Tamu→publik; anggota→+restricted+milik sendiri; admin→semua.
- **Akun & peran:** registrasi/login, reset password, profil + avatar + ganti password, pengajuan kenaikan peran, panel staff/admin/superadmin, CRUD definisi peran role-aware (superadmin).
- **Admin:** dashboard, kelola kategori/handbook, pengguna, riwayat aktivitas, persetujuan peran. **Superadmin:** kelola pengguna, pengaturan web + sakelar fitur, definisi peran.
- **Recommendations:** rekomendasi dokumen terkait (detail & Reader) + personal (riwayat baca/bookmark) — skor berbobot 6 sinyal, **N+1 eliminated, query O(1)**.
- **Universal Search `/cari`:** dokumen (kelompok per tipe) + layanan + orang + **PIC/kontak publik** + Ask YPL panel.
- **Peta Pengetahuan `/peta-pengetahuan`:** halaman eksplorasi graf knowledge lintas koleksi (BAB 12) — pilih dokumen pusat, render graf node-link 2-hop (dokumen/sistem/agenda/FAQ/PIC) + legenda warna + fallback daftar aksesibel; permission-aware. Blok **"Terkait dengan topik ini"** di detail dokumen & governance.
- **Dashboard Saya `/dashboard`:** satu templat role-aware; seksi per audiens (`users.audience`) — ringkasan, agenda, koleksi, formulir, review jatuh tempo, persetujuan (staff/admin), layanan menunggu, permintaan saya, rekomendasi.

## 4. Tabel `users` — Deskripsi Kolom

Sumber: `0001_01_01_000000_create_users_table.php`, `2026_09_25_000001_create_lokon_tables.php`, `2026_09_25_000004_profile_fields.php`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | ID unik pengguna |
| `name` | string | Nama lengkap |
| `email` | string, unik | Login + kontak |
| `email_verified_at` | timestamp, null | Verifikasi email (Laravel bawaan) |
| `password` | string (hash) | Kata sandi ter-hash, hidden di serialisasi |
| `remember_token` | string, null | Ingat sesi login |
| `avatar_path` | string, null | Foto profil (`storage/`) |
| `academic_info` | string, null | Info akademik/jabatan bebas |
| `birth_place` | string(100), null | Tempat lahir |
| `birth_date` | date, null | Tanggal lahir |
| `wa_number` | string(25), null | Nomor WhatsApp (normalisasi +62) |
| `role` | string(20), default `user` | Otoritas: `anggota`, `staff`, `admin`, `superadmin` (registrasi → `anggota`) |
| `audience` | string(20), null | Audiens UX (dashboard): `siswa`, `orang_tua`, `guru`, `karyawan`, `pimpinan`, `mitra`, `publik`; kosong → fallback ke `role` sistem. Ditetapkan langsung oleh Admin/SA atau lewat pengajuan audiens yang disetujui |
| `ypl_verified` | boolean, default false | Verifikasi keanggotaan YPL |
| `created_at` / `updated_at` | timestamps | Audit standar |

**Peran & otoritas:** `anggota` (baca publik/restricted, unggah, ajukan), `staff` (+kelola dokumen, proses layanan), `admin` (+kelola kategori/pengguna, publish), `superadmin` (1 akun: `superadmin@lokon.or.id` — kendali penuh + pengaturan + definisi peran). Definisi role-aware audiens (siswa, guru, dsb.) tersimpan terpisah di tabel `roles` (slug, prioritas, scope koleksi, urutan homepage).

**Relasi user:** `documents` (unggahan), `bookmarks`, `readingPref`, `serviceRequests` (implisit via user_id).

## 5. Tabel Pendukung (ringkas)

`documents` (metadata knowledge BAB 7 + FTS), `categories` (koleksi + B01–B10), `document_relations` (dokumen↔dokumen/sistem), `document_versions` (audit lifecycle + snapshot isi per revisi + `change_note`; dibaca "What changed?" sebagai diff antar snapshot), `systems`, `roles`, `units` + `contacts` (direktori unit & PIC publik, CRUD admin), `service_requests`, `role_requests` (peran sistem **atau** audiens: `requested_role`/`requested_audience`), `bookmarks`, `likes`, `reading_positions`, `reading_prefs`, `tags` (+pivot), `activity_logs`, `settings`, bawaan Laravel (`sessions`, `cache`, `jobs`, `password_reset_tokens`).

**Archive policy (BAB 13):** `Document::retentionDays()` (setting `retention_days`, default 1095) + scope `Document::archiveDue()`; perintah `governance:archive-stale` (`--days`, `--dry-run`) mengarsipkan dokumen `review_due` yang melewati masa retensi, terjadwal harian 02:00. Masa retensi diatur Super Admin di Pengaturan Web.

**Version control dokumen (BAB 13):** `Document::nextVersion()` menaikkan segmen terakhir nomor versi (`1.0 → 1.1`); `Document::recordVersion()` menyimpan revisi (snapshot + catatan + status); `Document::snapshotDiff()` menghitung bidang yang berubah antar revisi. Revisi tercatat saat: pembuatan dokumen (v1.0), setiap edit metadata/berkas (Document/Staff controller), dan perubahan isi (editor). Halaman `/governance/{slug}/riwayat` menampilkan **"What changed?"** per revisi.

## 6. Ask YPL — Arsitektur

`AskController` → `AskYpl::answer()` (retrieval `visibleTo()` + `searchAny()` OR + skor overlap + fallback jujur) → opsional `GeminiLlm::compose()` (prompt grounding + sitasi [n], `config/ask.php`: driver/key/model/timeout). Kontrak: `['answer','sources','found','ai']`. **Sitasi section-level:** `bestPassage()`/`contentSentences()` melacak heading terdekat (h1–h3), sumber membawa `section` (mis. `§ Sanksi`); tanpa heading → `section=null` (jujur, tanpa nomor halaman palsu). **Inline Reader:** endpoint yang sama menyajikan JSON (`sources` terstruktur: title/url/section/passage/doc_type/status/version) saat diminta klien, ter-scoped ke dokumen yang dibaca. Audit tiap tanya ke `activity_logs`.

## 7. Universal Search (`/cari`)

`SearchController` + `search/index.blade.php`:
- Panel Ask YPL di atas hasil (jawab + sitasi `[n]`, reuse engine izin yang sama)
- Dokumen dikelompokkan per tipe (SOP/FORM/…) dengan badge versi-status, hormati `visibleTo()` — private orang lain tak bocor (teruji)
- Layanan & Aksi — pencocokan kata kunci ke 8 tipe `ServiceRequest`, tombol `Start →` (acceptance *"search mengembalikan dokumen dan action/service"* kini terpenuhi)
- Orang (hanya login, tanpa email) — tamu tak melihat seksi ini
- **PIC / Kontak publik** — nama/jabatan/unit/email/telepon tampil tanpa login (direktori PIC unit)
- Kosong yang jujur + validasi panjang kueri; hero beranda kini mengarah ke `/cari` (daftar dokumen tetap utuh)

## 8. Recommendation Engine — Arsitektur & Optimasi

`RecommendationEngine` service dengan **query O(1)** — N+1 eliminated:

**Sinyal (bobot total 100):**
1. Relasi eksplisit (`document_relations`) — 30
2. Kategori sama — 20
3. Tag overlap — 20
4. Penulis sama — 10
5. Co-reading (`reading_positions`) — 10
6. Sistem operasi sama — 10

**Dua mode:**
- `forDocument($doc, $user)` → rekomendasi kontekstual di detail dokumen & Reader
- `forUser($user)` → rekomendasi personal berdasarkan riwayat baca + bookmark

**Optimasi Query (N+1 eliminated → O(1)):**
- Pre-compute maps: `buildRelationMaps()`, `buildCoreaderMap()`, `buildCoreaderMapMultiple()` — **1 query per tipe** untuk semua kandidat sekaligus
- Eager load semua relasi di query awal (`with(['category','tags','user','outgoingRelations.related','outgoingRelations.system'])`)
- Scoring di memori, zero query tambahan per kandidat
- **Query count:** query relasi dibatch dan tidak tumbuh per seed/kandidat. `forUser()` membatasi seed pada 10 bacaan + 10 bookmark terbaru dan kandidat pada 200 dokumen aktif; daftar kartu hanya mengambil kolom/relasi yang dirender.

**UI Integration:**
- Detail dokumen: panel **REKOMENDASI UNTUK ANDA** (ganti "Dokumen Sejenis" dengan badge alasan + views)
- Reader Mode: panel **REKOMENDASI** di TOC drawer (kanan) — link langsung ke Reader dokumen terkait
- Privacy: `visibleTo($user)` diterapkan — dokumen private orang lain tidak pernah bocor ke rekomendasi (teruji)

## 9. Sisa Pekerjaan

**Phase 5 Intelligence:** ✅ Selesai — Knowledge Graph visual (halaman Peta Pengetahuan + graf impact map + blok terkait).

**Phase 6 Ecosystem:**
- Penetapan audiens pengguna oleh Admin/SA + alur **pengajuan audiens** dari profil (disetujui Admin/SA) — **selesai**.
- Seeder demo audiens opsional (`php artisan db:seed --class=AudienceDemoSeeder`).
- Analytics dashboard dari `activity_logs` — **selesai** (`/staff/analytics`).
- Odoo Integration (terakhir; API akan disediakan).

**Hardening Production:**
- ✅ Cabut key testing, hapus rute `/deploy/{secret}` (commit `caa423f`), rate limiter sensitif aktif.
- Sisa opsional: hapus key Gemini testing (`GEMINI_API_KEY`) dari `.env` produksi bila tidak dipakai.