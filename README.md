# LOSNITO Document — YPL Smart Digital Library

> Platform **Digital Knowledge, Governance & Community** Yayasan Pendidikan Lokon (YPL).
> Prinsip: **DISCOVER → KNOW → UNDERSTAND → ACT**. Dua mode utama: **Library Mode** (discovery) dan **Reader Mode** (baca imersif).

![Home — desktop](presentasi/screenshots/desktop-home.png)

- **Status:** 215 tests lolos (1178 assertions) · 202 routes (114 web + 88 REST API) · branch `main`
- **Repo:** `lansilvester/YPL-Document-Manager`

---

## 📖 Panduan Membaca Repo Ini

Pilih jalur sesuai kebutuhan Anda:

| Saya ingin… | Buka… |
|---|---|
| Presentasi ke atasan / memahami aplikasi secara umum | [§1 Presentasi](#1-presentasi--dokumentasi-visual) → [`presentasi/README.md`](presentasi/README.md) |
| Melihat daftar fitur per halaman web | [§2 Katalog Fitur](#2-katalog-fitur) |
| Integrasi / konsumsi REST API (mobile, frontend lain) | [§3 REST API](#3-rest-api--apiv1) → [`docs/API.md`](docs/API.md) |
| Tahu rencana fitur berikutnya | [§4 Roadmap](#4-roadmap--fitur-ke-depan) |
| Develop / deploy / testing | [§5 Developer Essentials](#5-developer-essentials) |

---

## 1. Presentasi & Dokumentasi Visual

Panduan lengkap (teknis + non-teknis): [`presentasi/README.md`](presentasi/README.md).

### 1.1 Slide deck

| Deck | Isi | Kapan dipakai |
|---|---|---|
| [`YPL-SDL-Laporan-Lengkap.pptx`](presentasi/YPL-SDL-Laporan-Lengkap.pptx) | **Slide utama 22 slide** — angka kunci, kesesuaian briefing 21 BAB, fitur, keamanan, UML, roadmap | Presentasi ke atasan (pakai ini) |
| `LOSNITO-Progress-Report.pptx` | Laporan progress 18 slide (versi awal) | Arsip |
| `ERD-DFD-UseCase.pptx` | Slide diagram UML (versi awal) | Arsip |

### 1.2 Dokumen sumber & diagram

| File | Isi |
|---|---|
| [`YPL Smart Digital Library Website.docx`](presentasi/YPL%20Smart%20Digital%20Library%20Website.docx) | Briefing awal — sumber requirements (21 BAB) |
| [`Use Case Diagram`](presentasi/Use%20Case%20Diagram%20—%20YPL%20Smart%20Digital%20Library%20(LOSNITO).png) | Aktor vs fitur (Tamu → Super Admin) |
| [`ERD`](presentasi/ERD%20—%20YPL%20Smart%20Digital%20Library%20(LOSNITO).png) | 23 tabel + 29 FK, digambar dari skema MySQL aktual (`presentasi/generate_erd.py`) |

### 1.3 Screenshot (`presentasi/screenshots/`)

| Desktop | Mobile (390px) |
|---|---|
| Home, Library, Reader, Governance, Search, Ask, Community, Calendar, Insight, Systems | Home, Library, Reader, Search, Ask |

### 1.4 Urutan presentasi yang disarankan

1. Buka `YPL-SDL-Laporan-Lengkap.pptx` (sudah sistematis 22 slide).
2. Demo aplikasi hidup: Beranda → Perpustakaan → Baca → Tanya.
3. Tutup dengan roadmap (§4): Odoo terakhir, lalu go-live bertahap.

---

## 2. Katalog Fitur

| Area | Rute Web | Inti |
|---|---|---|
| Home | `/` | Hero + universal search, quick action role-aware, blok "Untuk Anda", YPL Today |
| Library | `/perpustakaan`, `/perpustakaan/{kategori}` | Bookshelf 10 Handbook B01–B10 (buku 3D), filter koleksi, book detail |
| Reader | `/baca/{slug}` | Two-page desktop / single mobile, flip + TOC + search-in-book, posisi & preferensi tersimpan, thumbnail lazy, catatan per halaman, Ask inline |
| Dokumen | `/dokumen`, `/dokumen/{slug}`, `/unggah`, `/tulis` | Unggah PDF (≤9 MB), editor HTML tersanitasi, unduh PDF/HTML, embed, suka, bagikan, Dokumen Saya; visibilitas public/restricted/private |
| Governance | `/governance`, `/governance/{slug}`, `/versi`, `/impact` | Document Passport, lifecycle `draft → review → approval → active → review-due → archived`, approval workflow, versioning + snapshot + "What changed?", relasi, impact map 2-hop, ekspor CSV |
| Search & Ask | `/cari`, `/tanya` | Universal search per tipe + panel Ask YPL bersitasi section-level (permission-aware, fallback ekstraktif) |
| Peta Pengetahuan | `/perpustakaan/peta-pengetahuan` | Graf node-link 2-hop (dokumen, sistem, agenda, FAQ, PIC), kontrol kedalaman, PIC klikabel |
| Services | `/layanan`, `/layanan/minta/{tipe}`, `/permintaan-saya` | Katalog 8 layanan ("Saya ingin…"), workflow `pending → approved/rejected → done` |
| Community | `/community` | Direktori unit/peran/orang (login), kontak PIC publik, FAQ komunitas |
| Calendar | `/calendar`, `/calendar/{slug}`, `/staff/agenda` | Agenda nyata + kelola agenda staff/admin/SA (slug stabil, activity log) |
| Insight | `/insight` | Etalase publik: angka + dokumen terbaru |
| Dashboard | `/dashboard` | Role-aware per 7 audiens (siswa, orang_tua, guru, karyawan, pimpinan, mitra, publik) |
| Akun & Peran | `/profil`, `/admin/*`, `/sa/*` | Registrasi/login, reset via kode email, avatar, pengajuan peran/audiens, panel staff/admin/superadmin |

Detail tiap fitur + peran: [`docs/API.md` §3](docs/API.md) (matriks hak akses) dan slide deck (§1.1).

---

## 3. REST API (`/api/v1`)

Dokumentasi lengkap + contoh request/response: [`docs/API.md`](docs/API.md).
Base URL: `{APP_URL}/api/v1`. Format respons: `{ message, data }` / koleksi paginasi `{ data, meta }`.

Legenda **Auth**: `—` publik · `A` anggota (token Sanctum) · `S` staff · `AD` admin · `SA` superadmin.
Otorisasi memakai middleware peran yang sama dengan web + `scopeVisibleTo()` — dokumen privat tidak bocor.

### 3.1 Mulai cepat (dapat token)

```bash
curl -s -X POST {APP_URL}/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"staf@lokon.or.id","password":"Rahasia1!","device_name":"mobile-app"}'
# → { "data": { "token": "3|ab12...", "token_type": "Bearer", "user": {...} } }

curl -s {APP_URL}/api/v1/documents \
  -H 'Authorization: Bearer 3|ab12...'
```

### 3.2 Autentikasi (4 endpoint)

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| POST | `/auth/register` | — | Daftar (`anggota`), mengembalikan token |
| POST | `/auth/login` | — | Login, mengembalikan token |
| GET | `/auth/me` | A | Profil + hitungan dokumen/bookmark |
| POST | `/auth/logout` | A | Cabut token aktif |

### 3.3 Katalog Publik (13 endpoint)

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| GET | `/categories` | — | Kategori + jumlah dokumen |
| GET | `/systems`, `/systems/{system:slug}` | — | Registri sistem operasional + detail |
| GET | `/faqs`, `/faqs/{faq:slug}` | — | FAQ (tamu: publik saja) |
| GET | `/units`, `/contacts` | — | Unit + kontak PIC publik |
| GET | `/events`, `/events/{event:slug}` | — | Agenda (tamu: publik; `?category=`) |
| GET | `/insight` | — | Statistik etalase publik |
| GET | `/community` | — | Direktori unit & kontak (`people` perlu login) |
| GET | `/help` | — | FAQ per kategori |
| GET | `/services` | — | Katalog 8 tipe layanan |

### 3.4 Dokumen (12 endpoint)

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| GET | `/documents` | — | Daftar permission-aware (`?q=&category=&type=`) |
| GET | `/documents/{slug}` | — | Detail dokumen |
| GET | `/documents/{slug}/content` | — | Isi editor (HTML sanitasi) / URL berkas |
| GET | `/documents/{slug}/related` | — | Rekomendasi berbobot |
| POST | `/documents` | A | Unggah PDF (`multipart`, ≤9 MB) |
| PUT | `/documents/{slug}` | A | Ubah metadata / ganti berkas (policy) |
| DELETE | `/documents/{slug}` | A | Hapus (policy) |
| POST | `/documents/{slug}/bookmark`, `/like` | A | Toggle bookmark / suka |
| GET/POST/DELETE | `/documents/{slug}/notes` (`/{note}`) | A | Catatan pembaca per halaman |

### 3.5 Governance (7 endpoint)

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| GET | `/governance/documents` | — | Listing terfilter (tipe/status/audiens/unit) |
| GET | `/governance/documents/{slug}` | — | Passport dokumen |
| GET | `…/versions`, `…/impact-map` | — | Riwayat versi + graf impact |
| POST | `/documents/{slug}/transition` | A | Transisi lifecycle (policy per target status) |
| POST/DELETE | `/documents/{slug}/relations` (`/{relation}`) | A | Kelola relasi (tepat satu target) |

### 3.6 Pencarian & Ask YPL (2 endpoint)

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| GET | `/search?q=` | — | Cari permission-aware per grup |
| POST | `/ask` | — | Tanya jawab bersitasi (`{question, doc?}`) |

### 3.7 Profil & Interaksi (12 endpoint)

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| PATCH/PUT/GET/POST | `/profile`, `/profile/password`, `/profile/stats`, `/profile/role-request` | A | Profil, ganti password, statistik, pengajuan peran |
| GET | `/bookmarks` | A | Bookmark saya |
| GET/POST | `/notifications`, `/notifications/{id}/read`, `/notifications/read-all` | A | Notifikasi in-app |

> API internal web (sesi, bukan token): `POST /api/dokumen/{slug}/posisi`, `POST /api/preferensi-baca`, `POST|DELETE /api/dokumen/{slug}/catatan` — dipakai Reader untuk autosave.

### 3.8 Layanan (5 endpoint)

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| POST | `/service-requests` | A | Ajukan layanan |
| GET | `/service-requests/my` | A | Permintaan saya |
| GET | `/service-requests` | S | Kelola semua pengajuan |
| POST | `/service-requests/{id}/decide` | S | Putusan (`approved/rejected/done`) |

### 3.9 Panel Admin (20 endpoint, `AD`)

Dashboard, analytics, logs · CRUD pengguna · CRUD kategori · CRUD unit · CRUD kontak · persetujuan peran (`/role-requests`, `/approve`, `/reject`).

| Method | Path |
|---|---|
| GET | `/admin/dashboard`, `/admin/analytics`, `/admin/logs` |
| GET/PUT/DELETE | `/admin/users`, `/admin/users/{user}` |
| GET/POST/PUT/DELETE | `/admin/categories`, `/admin/units`, `/admin/contacts` (+ `/{slug\|id}`) |
| GET/POST | `/admin/role-requests`, `/admin/role-requests/{id}/approve`, `/admin/role-requests/{id}/reject` |

### 3.10 Panel Super Admin (10 endpoint, `SA`)

| Method | Path |
|---|---|
| GET | `/sa/dashboard` |
| GET/PUT/DELETE | `/sa/users`, `/sa/users/{user}/role`, `/sa/users/{user}` |
| GET/PUT | `/sa/settings` |
| GET/POST/PUT/DELETE | `/sa/roles`, `/sa/roles/{role}` |

---

## 4. Roadmap & Fitur Ke Depan

### 4.1 Status phase (Blueprint BAB 18)

| Phase | Status |
|---|---|
| 1 Foundation · 2 Living Books · 3 Governance · 4 Services · 5 Intelligence | ✅ Selesai |
| 6 Ecosystem (role dashboard, analytics, community) | ✅ Selesai |
| Hardening keamanan (reset via email, CSP nonce, privasi staff, SMTP) | ✅ Selesai |

### 4.2 Rencana berikutnya

| # | Fitur | Status | Prioritas | Catatan |
|---|---|---|---|---|
| 1 | **Integrasi Odoo** (BAB 16) | 📋 Direncanakan — terakhir | Tinggi | Discovery → read-only sync `systems` via queue → tulis bila perlu; degradasi anggun bila Odoo offline. Butuh akses server/API dari pihak yayasan |
| 2 | Checklist go-live produksi | 🟡 Di tangan owner | Tinggi | `APP_DEBUG=false`, `APP_KEY` generate, kredensial MySQL server, `storage:link`, isi kredensial SMTP Zoho, user DB khusus, backup terjadwal |
| 3 | Hardening lanjutan | 📋 Direncanakan | Sedang | Hapus `unsafe-inline` tersisa di `style-src` (58 atribut `style=""` → kelas CSS); purge history git file dump ber-hash bila repo publik |
| 4 | Suite MySQL | 📋 Opsional | Rendah | 2 test Ask gagal di transaksi MySQL (artefak FULLTEXT, bukan bug); suite resmi tetap SQLite 215/215 |
| 5 | Housekeeping `presentasi/` | 💬 Menunggu keputusan | Rendah | 18 MB termasuk 2 pptx arsip; keluarkan bila repo terasa berat (laporan lengkap sudah mencakup semua) |

---

## 5. Developer Essentials

### 5.1 Tech stack

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 13, PHP ^8.3, Blade + Eloquent, queue `sync` |
| Frontend | Tailwind 4 + Vite, Blade components, design tokens `--ypl-*` |
| Database | SQLite (lokal/testing) / MySQL (produksi), FULLTEXT + FTS5 |
| Library | `barryvdh/laravel-dompdf`, `ezyang/htmlpurifier` |
| AI | Ask YPL ekstraktif bersitasi + driver Gemini opsional (testing) |
| Quality | PHPUnit 12, Laravel Pint, GitHub Actions |

### 5.2 Struktur

```text
app/Http/Controllers/        # 26 controller web (Home, Library, Reader, Governance, ...)
app/Http/Controllers/Api/    # 9 controller REST API v1
app/Http/Resources/          # 14 API Resource
app/Models/                  # 21 model (Document, Category, Event, Faq, ...)
app/Services/                # AskYpl, RecommendationEngine, ImpactMap, ...
routes/web.php | routes/api.php
docs/API.md                   # dokumentasi REST API lengkap
database/migrations/          # 33 migrations
tests/Feature/                # 18 file test
presentasi/                   # slide + diagram + screenshot (lihat §1)
```

### 5.3 Lokal & kualitas

```bash
composer install && cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
npm install && npm run build && php artisan serve   # http://localhost:8000
php artisan test --compact      # 215 tests, ~5 detik
vendor/bin/pint --dirty         # rapikan gaya kode
```

Akun bawaan: `admin@lokon.or.id` / `password` (ganti setelah masuk!) · Super Admin via `php artisan ypl:superadmin --email=...`.
Deploy: [`DEPLOY.md`](DEPLOY.md) (InfinityFree, tanpa SSH) — termasuk konfigurasi SMTP Zoho.

---

## 6. Lisensi

Kerangka Laravel berlisensi [MIT](https://opensource.org/licenses/MIT). Konten/dokumen YPL milik Yayasan Pendidikan Lokon.
