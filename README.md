# LOSNITO Document — YPL Smart Digital Library

> Platform **Digital Knowledge, Governance & Community** Yayasan Pendidikan Lokon (YPL).
> Prinsip: **DISCOVER → KNOW → UNDERSTAND → ACT**. Dua mode utama: **Library Mode** (discovery) dan **Reader Mode** (baca imersif).

![Home — desktop](presentasi/screenshots/desktop-home.png)

- **Status (13 Okt 2026):** 215 tests lolos (1178 assertions) · 117 routes web + 84 endpoint REST API · branch `main`
- **Repo:** `lansilvester/YPL-Document-Manager`
- **REST API:** [`docs/API.md`](docs/API.md) — 84 endpoint `/api/v1` (Sanctum token), dokumentasi lengkap.
- **Presentasi progress:** [`presentasi/LOSNITO-Progress-Report.pptx`](presentasi/LOSNITO-Progress-Report.pptx) (18 slide, desktop + mobile)

---

## 1. Tentang

Bukan sekadar repository PDF, melainkan satu rumah digital untuk:

1. **Knowledge** — bookshelf 10 Handbook (B01–B10), dokumen Policy/SOP/Form/WI, sistem operasional, FAQ, agenda.
2. **Governance** — Document Passport, lifecycle, versioning + snapshot, approval workflow, relasi antar-dokumen, impact map graf 2-hop.
3. **Community** — direktori orang/unit/peran, kalender agenda, etalase publik (Insight), layanan pengajuan.

Alur pakai tipikal: `Home → /perpustakaan → /baca/{slug} → /governance/{slug} → /tanya`.

---

## 2. Progress per Phase (Blueprint BAB 18)

| Phase | Status (12 Okt 2026) |
|---|---|
| 1 Foundation (IA, taxonomy, Library Mode) | ✅ Selesai |
| 2 Living Books (bookshelf B01–B10, book detail, Reader Mode) | ✅ Selesai |
| 3 Governance (passport, lifecycle, versioning, approval, relasi, impact map) | ✅ Selesai (version control nyata: bump otomatis + revisi saat edit + "What changed?", snapshot per versi, otomasi review-due + **archive policy** retensi otomatis terjadwal) |
| 4 Services (forms, workflow, My Requests) | ✅ Selesai (kurang: integrasi Odoo) |
| 5 Intelligence (universal search, Ask YPL, rekomendasi, graph) | ✅ Fungsional — search + Ask YPL v1 + rekomendasi + graf 2-hop permission-aware + halaman **Peta Pengetahuan `/peta-pengetahuan`** |
| 6 Ecosystem (role dashboards, analytics, Odoo) | 🔄 Berjalan — Community, Calendar, Insight sudah nyata (prioritas-1); dashboard per peran (`/dashboard`, audience-aware) selesai |

Phase 1–5 tuntas; Phase 6 berjalan: integrasi Odoo menunggu API (placeholder di `/layanan`) · feature-frozen 30 Okt. **Update terbaru (13 Okt):** Event/agenda jadi first-class knowledge (**CRUD staff/admin/superadmin** di `/staff/agenda` — kelola dari Kalender, dash admin, dan nav staf; slug stabil, activity log, badge aksen baru).

Update terbaru (8–12 Okt): Event + FAQ jadi first-class knowledge · graf node-link 2-hop permission-aware di impact map ·
halaman **Peta Pengetahuan** `/peta-pengetahuan` + blok **"Terkait dengan topik ini"** di detail dokumen · dashboard per peran
**`/dashboard`** audience-aware (`users.audience`) · penetapan audiens oleh Admin/SA + **pengajuan audiens** dari profil (disetujui Admin/SA, kolom `role_requests.requested_audience`) · seeder demo audiens opsional (`--class=AudienceDemoSeeder`) · Community/Calendar/Insight nyata · **tipe governance penuh** (organization/risk/compliance/quality/document_control) · **sitasi Ask YPL section-level** (§ heading) · **Unit & Kontak PIC** dengan CRUD admin + **PIC publik di pencarian** · thumbnails lazy + catatan pembaca per halaman di Reader · eliminasi N+1 doc-card.

---

## 3. Fitur Utama

### Library & Reader
- **Home `/`** — hero + universal search, quick actions **role-aware** (urutan + scope per peran, `Role::orderedQuick()`), kartu Explore tersaring scope koleksi (`scopedExplore()`), blok **"Untuk Anda"**, What's New, YPL Today (deadline review nyata; pantauan review & layanan hanya untuk peran operasional).
- **Library `/perpustakaan`** — bookshelf 10 Handbook B01–B10 (buku 3D), filter koleksi, book detail (cover besar, TOC, Open Book, Bookmark).

![Library — desktop](presentasi/screenshots/desktop-library.png)

- **Reader `/baca/{slug}`** — **default two-page spread di desktop**, single-page adaptif di mobile (BAB 20), page-flip 550ms + reduced-motion, TOC drawer, lompat halaman + grid, search-in-book, toolbar (Contents/Search/Bookmark/Aa/Ask YPL/counter/Reading %), keyboard + swipe, persist posisi & preferensi, thumbnails lazy, catatan per halaman, panel rekomendasi. **Dokumen bersumber editor** memakai **Structured HTML Reader** (BAB 8/16): konten HTML ter-sanitasi + daftar **Bagian Dokumen** (heading `h2` ber-anchor) + progres baca berbasis scroll. **Ask YPL inline** (konteks dokumen, §8/§14) — tanya & ringkas dokumen tanpa meninggalkan Reader; sumber dibuka di tab baru.

![Reader — desktop](presentasi/screenshots/desktop-reader.png)

### Dokumen & Governance
- **Design system** — token warna institusi (navy/red/blue + varian), radius, bayangan, ring fokus, dan gerak didefinisikan sekali sebagai CSS variables (`--ypl-*`) di partial bersama `resources/views/components/tokens.blade.php` (dipakai layout utama, Reader, dan embed). Seluruh tampilan mengonsumsinya lewat utility `var(--ypl-*)` dan komponen kanonis (`.btn-primary`, `.btn-accent`, `.field`, `.page-title`, `.grad-navy`, `.brand-line`, dst.) sehingga perubahan tema cukup di satu tempat (BAB 16).
- **Dokumen** — unggah PDF (maks 9 MB), tulis via editor (sanitasi HTML), unduh PDF/HTML, semat (embed), suka, bagikan, Dokumen Saya, visibilitas public/restricted/private.
- **Governance `/governance`** — Document Passport (DOC ID, tipe, owner, approver, PIC, effective/review, klasifikasi, status, audience, unit), lifecycle `draft → review → approval → active → review-due → archived`, approval workflow, View History + snapshot isi per versi + **"What changed?"** (diff bidang antar revisi), relasi Handbook→Policy→SOP→Form→PIC→System, impact map graf 2-hop, listing filter tipe/status/audience/unit, ekspor CSV (throttled). **Version control nyata:** nomor versi naik otomatis saat edit (bila tidak diisi eksplisit), setiap perubahan metadata/isi mencatat revisi + catatan perubahan. **Archive policy (BAB 13):** dokumen `review_due` yang melewati masa retensi (default 1095 hari, diatur SA) diarsipkan otomatis via `governance:archive-stale` (dry-run tersedia). **Notifikasi governance:** review-due & arsip otomatis mengirim notifikasi in-app (lonceng + badge di header, pusat notifikasi `/notifikasi` dengan tandai-baca per item/semua; email otomatis bila mailer produksi aktif). Tipe dokumen mencakup governance penuh: `policy, regulation, standard, sop, work_instruction, decision, organization, risk, compliance, quality, document_control, form, evidence, handbook, other`.

![Governance — desktop](presentasi/screenshots/desktop-governance.png)

### Services, Systems, Community
- **Services `/layanan`** — katalog 8 layanan ("Saya ingin…"), pengajuan + Permintaan Saya, kelola + putusan staff (`pending → approved/rejected → done`).
- **Systems `/sistem`** — registri 8 sistem operasional (Odoo, Akademik, Asrama, HR, Keuangan, Fleet, Maintenance, ICT) + dokumen tertaut.
- **Community `/community`** — direktori unit/peran/orang (login untuk direktori orang) + **kontak PIC publik** (email/telepon) + pengetahuan komunitas (FAQ).

![Community — desktop](presentasi/screenshots/desktop-community.png)

- **Calendar `/calendar`** — agenda/event nyata + detail `/calendar/{slug}`. **Kelola Agenda `/staff/agenda`** untuk staff/admin/super admin: tambah/ubah/hapus (kategori, jadwal, lokasi, visibilitas publik/internal) dengan slug URL stabil + activity log.
- **Insight `/insight`** — etalase publik: angka (dokumen publik, kali dibaca, agenda, FAQ) + terbaru untuk publik.

![Insight — desktop](presentasi/screenshots/desktop-insight.png)

### Intelligence
- **Universal Search `/cari`** — dokumen dikelompokkan per tipe + badge versi-status, layanan/aksi (`Start →`), orang (login, tanpa email), **PIC/kontak publik**, FAQ & agenda, panel Ask YPL di atas hasil. Selalu hormati `visibleTo()`.

![Search — desktop](presentasi/screenshots/desktop-search-query.png)

- **Peta Pengetahuan `/peta-pengetahuan`** — halaman eksplorasi graf knowledge lintas koleksi (BAB 12): pilih dokumen pusat, render graf node-link 2-hop (dokumen, sistem, agenda, FAQ, PIC) dengan **kontrol kedalaman 1/2 dan simpul PIC yang bisa diklik** menuju kontak publik (`/community#kontak-pic`), legenda warna, fallback daftar teks untuk aksesibilitas. Permission-aware: simpul privat tidak pernah tampil untuk tamu. Blok **"Terkait dengan topik ini"** muncul di detail dokumen biasa & governance dengan tautan langsung ke peta.

- **Ask YPL `/tanya`** — jawaban ekstraktif **bersitasi section-level** (§ heading) dari knowledge sesuai otoritas user; driver Gemini untuk testing — gagal/timeout otomatis fallback ekstraktif. Tamu → publik; anggota → +restricted + milik sendiri; admin → semua. Tiap pertanyaan diaudit ke `activity_logs`. Endpoint sama menyajikan **JSON** untuk panel inline Reader (ter-scoped ke dokumen yang dibaca).

![Ask YPL — desktop](presentasi/screenshots/desktop-ask.png)

- **Recommendations** — skor berbobot 6 sinyal (relasi 30, kategori 20, tag 20, penulis/co-read/sistem @10), query batch tanpa query per kandidat atau seed, maksimal 10 dokumen baca + 10 bookmark terbaru sebagai seed dan 200 kandidat. Daftar dokumen memakai scope `forCard()` agar hanya kolom/relasi yang dirender yang diambil; rekomendasi tampil di detail dokumen + drawer Reader.

### Akun & Peran
- Registrasi/login, reset password (kode 8 karakter dikirim ke email terdaftar — tidak pernah ditampilkan di layar), profil + avatar + ganti password, pengajuan kenaikan peran, panel staff/admin/superadmin.
- Peran: `anggota` (baca publik/restricted, unggah, ajukan) · `staff` (+kelola dokumen, proses layanan) · `admin` (+kategori/pengguna, publish) · `superadmin` (pengaturan web + definisi peran). Registrasi → `anggota`.
- **Dashboard Saya `/dashboard`** — satu templat role-aware dengan seksi berbeda per audiens (`siswa`, `orang_tua`, `guru`, `karyawan`, `pimpinan`, `mitra`, `publik`; kolom `users.audience`, fallback ke peran sistem). Seksi: ringkasan, agenda, koleksi, formulir, review jatuh tempo, persetujuan (staff/admin), layanan menunggu, permintaan saya, plus rekomendasi personal. **Mitra** mendapat seksi panduan mitra + kontak/kolaborasi + resource publik; **publik** mendapat profil & publikasi institusi + pendidikan/program. Audiens ditetapkan langsung oleh Admin/SA, atau diajukan pengguna dari profil lalu disetujui Admin/SA.

### REST API `/api/v1`
- **84 endpoint** JSON dengan autentikasi **Sanctum personal access token** (`Authorization: Bearer <token>`) — dokumentasi lengkap di [`docs/API.md`](docs/API.md).
- Cakupan: autentikasi (`login`/`register`/`me`/`logout`), katalog publik (kategori, sistem, FAQ, unit, kontak, agenda, insight, community, help, layanan), dokumen (baca/unggah/ubah/hapus + konten + terkait), governance (passport, versi, impact map, transisi, relasi), pencarian & Ask YPL, profil & interaksi (bookmark, like, catatan, notifikasi), layanan, panel admin, dan panel Super Admin (pengguna, peran, pengaturan).
- Otorisasi memakai middleware peran yang sama dengan web (`staff`/`admin`/`superadmin`) **dan** aturan visibilitas dokumen `scopeVisibleTo()` — tidak ada kebocoran dokumen privat.

---

## 4. Tech Stack

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 13.33, PHP ^8.3 (berjalan 8.5.8), Blade + Eloquent, queue `sync` |
| Frontend | Tailwind CSS 4 + Vite 8, Blade components, **design tokens** (CSS variables `--ypl-*` di `components/tokens.blade.php`: warna, radius, shadow, ring fokus, motion; diadopsi menyeluruh via `var(--ypl-*)` di semua view) |
| Database | SQLite (lokal/testing) / MySQL (production), FTS triggers |
| Library | `barryvdh/laravel-dompdf` (ekspor PDF), `ezyang/htmlpurifier` (sanitasi HTML) |
| AI | `GeminiLlm` (`gemini-3.8-flash`, testing) + fallback ekstraktif bersitasi |
| Quality | PHPUnit 12, Laravel Pint, Laravel Boost MCP, GitHub Actions (`laravel.yml`) |
| Hosting | InfinityFree shared (tanpa SSH/cron/worker) — lihat [`DEPLOY.md`](DEPLOY.md) |

---

## 5. Struktur Proyek

```text
app/
  Http/Controllers/   # 26 controller: Home, Library (termasuk Peta Pengetahuan),
                      #   Reader, Governance, Notification, Search, Ask, Document, Event, Faq, Staff,
                      #   Admin, SuperAdmin, RoleDashboard, Unit, Contact, ...
  Http/Controllers/Api/  # 9 controller REST API v1 (Auth, Catalog, Document, Governance,
                      #   Search, Service, Account, Admin, SuperAdmin)
  Http/Resources/     # 14 API Resource (Document, Category, Event, Faq, User, ...)
  Models/             # 21 model: Document, Category, DocumentRelation,
                      #   DocumentVersion, Event, Faq, System, Role, Unit, Contact, ...
  Services/           # AskYpl, RecommendationEngine, ImpactMap, GeminiLlm,
                      #   HtmlSanitizer, DocumentFiles, WaNumber
routes/web.php        # 117 routes: public • auth • staff/ • admin/ • sa/
routes/api.php        # 84 endpoint REST API v1 (Sanctum token)
docs/API.md           # dokumentasi REST API lengkap
database/migrations/  # 33 migrations (users+audience, documents+FTS, categories B01–B10,
                      #   relations, versions+snapshot, events, faqs, systems, units, contacts,
                      #   personal_access_tokens, ...)
resources/views/      # home, library (termasuk peta-pengetahuan), reader, governance,
                      #   search, ask, portal, dashboard, systems, admin, sa, ...
tests/Feature/        # 18 file test (±215 tests)
presentasi/           # slide progress + screenshot desktop & mobile
```

---

## 6. Cara Menjalankan Lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install && npm run build   # atau npm run dev saat develop
php artisan serve               # http://localhost:8000
```

```bash
php artisan test --compact      # 176 tests, ~4 detik
vendor/bin/pint --dirty         # rapikan gaya kode PHP yang diubah
```

---

## 7. Akun Bawaan (ganti password setelah masuk!)

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@lokon.or.id` | `password` |
| Super Admin | dibuat saat deploy via `php artisan ypl:superadmin --email=...` | diatur saat pembuatan |

Akun demo per audiens (opsional): `php artisan db:seed --class=AudienceDemoSeeder` membuat `demo-{audiens}@lokon.or.id` untuk siswa, orang_tua, guru, karyawan, pimpinan, mitra, publik (password `password`).

---

## 8. Testing & Kualitas

- `php artisan test --compact` → **215 passed, 1178 assertions** (13 Okt 2026).
- Cakupan: Foundation, Reader, Governance (lifecycle penuh, passport, relasi, impact map, version control + archive policy, **notifikasi review/arsip + pusat notifikasi**), Knowledge (sistem tertaut, audience/unit), KnowledgeGraph (permission-aware, halaman Peta Pengetahuan + kontrol kedalaman + PIC klikabel, blok terkait), Ask (privasi dokumen), Search (privasi + pengelompokan), Recommendation (personal + kontekstual), Services, Roles (termasuk beranda role-aware), Dashboard (audience-aware + privasi), doc-card N+1 regression, **Event admin (otorisasi 4 peran, validasi, CRUD + slug stabil + activity log)**, **REST API (autentikasi token, izin per peran, visibilitas dokumen, CRUD, transisi governance, layanan, pencarian)**.
- Prinsip keamanan yang diuji: dokumen private orang lain **tidak pernah bocor** ke tamu — di search, ask, rekomendasi, graf, maupun **REST API**.

---

## 9. Deploy Production

Panduan lengkap: [`DEPLOY.md`](DEPLOY.md). Ringkasnya:

1. Salin `.env.production` → `.env`, sesuaikan `APP_URL`, kredensial MySQL, `DEPLOY_SA_EMAIL`. `APP_DEBUG=false`.
2. **Konfigurasi SMTP (Zoho Mail)** — kode reset & notifikasi email butuh mailer nyata. Di `.env` produksi:
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.zoho.com
   MAIL_PORT=587
   MAIL_SCHEME=smtp          # 587 = STARTTLS; pakai smtps bila port 465
   MAIL_USERNAME=noreply@lokon.or.id
   MAIL_PASSWORD=<app-password Zoho>
   MAIL_FROM_ADDRESS="noreply@lokon.or.id"
   MAIL_FROM_NAME="${APP_NAME}"
   ```
   Uji sebelum go-live: `php artisan ypl:mail-test email-anda@lokon.or.id` (email percobaan; cek juga folder spam).
3. Upload isi `public/` → `htdocs/`; upload app (sejajar `htdocs`); `storage/` writable.
4. Jalankan migrasi dari lokal (tanpa SSH): arahkan `DB_*` ke MySQL produksi → `php artisan migrate --force && php artisan db:seed --force && php artisan ypl:superadmin --email=superadmin@lokon.or.id`. (Rute `/deploy/{secret}` sudah dihapus demi keamanan — lihat [`DEPLOY.md`](DEPLOY.md).)

Batasan InfinityFree: PDF ≤ 9 MB · tanpa worker/cron (semua `sync`) · seed tanpa 15 PDF besar dev.

---

## 10. Tampilan Mobile

Aplikasi responsif penuh — contoh Home dan Library di viewport 390px:

| Home (mobile) | Library (mobile) |
|---|---|
| ![Home — mobile](presentasi/screenshots/mobile-home.png) | ![Library — mobile](presentasi/screenshots/mobile-library.png) |

| Reader (mobile) | Search & Ask (mobile) |
|---|---|
| ![Reader — mobile](presentasi/screenshots/mobile-reader.png) | ![Search — mobile](presentasi/screenshots/mobile-search.png) ![Ask — mobile](presentasi/screenshots/mobile-ask.png) |

---

## 11. Sisa Pekerjaan / Roadmap

- **Phase 5:** ✅ Selesai — visualisasi Knowledge Graph penuh (halaman Peta Pengetahuan + blok terkait & graf impact map).
- **Phase 6:** Role Dashboards ✅ selesai (`/dashboard` audience-aware penuh 7 audiens termasuk mitra & publik) · Analytics dashboard dari `activity_logs` ✅ selesai · Integrasi Odoo (terakhir; API akan disediakan).
- **Hardening (uji keamanan):**
  - **Reset password aman** — kode 8 karakter dikirim ke email terdaftar via Mailable (mailer produksi), tak pernah tampil di respons; respons seragam anti-enumerasi; semua sesi lama akun dicabut saat reset.
  - **Privasi ruang staff** — dokumen `private` milik orang lain tak tampil di `/staff/dokumen`.
  - **CSP ketat + nonce** — script hanya dari self/CDN dengan `nonce` per-request; tanpa `unsafe-inline` untuk script; `object-src 'none'`, `base-uri 'self'`, `form-action 'self'`; HSTS otomatis saat HTTPS.
  - **Ekspor governance** dipindah ke dalam `auth` + `throttle:sensitive`.
  - **Cookie sesi produksi** — `.env.example`: `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, `SESSION_HTTP_ONLY=true`.
  - **Sembunyikan versi PHP** — set `expose_php=Off` di `php.ini` server (header `X-Powered-By` juga dihapus middleware).
  - Rate limiter sensitif (`login`/`register`/`uploads`/`interactions`); rute `/deploy/{secret}` telah dihapus.

---

## 12. Lisensi

Kerangka Laravel berlisensi [MIT](https://opensource.org/licenses/MIT). Konten/dokumen YPL milik Yayasan Pendidikan Lokon.
