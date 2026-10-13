# YPL Smart Digital Library — REST API

Dokumentasi REST API **YPL Smart Digital Library (Lokon Dokumen Digital)**.

- **Base URL:** `/api/v1`
- **Format:** JSON (`Accept: application/json`)
- **Autentikasi:** Laravel Sanctum — personal access token (`Authorization: Bearer <token>`)
- **Otorisasi:** peran (`anggota` → `staff` → `admin` → `superadmin`); resource publik tetap
  mematuhi aturan visibilitas dokumen (public / restricted / private).
- **Versi aplikasi:** Laravel 13 · PHP 8.3+ · Sanctum 4.3

---

## 1. Mulai Cepat

```bash
# 1) Login dan ambil token
curl -X POST https://<host>/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@lokon.or.id","password":"password","device_name":"cli"}'

# Respons: { "message": "...", "data": { "token": "1|xxxx", "token_type": "Bearer", "user": { ... } } }

# 2) Pakai token
curl https://<host>/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|xxxx"
```

> Semua contoh memakai header `Accept: application/json`. Endpoint tulis juga memerlukan
> `Content-Type: application/json` (atau `multipart/form-data` untuk unggah berkas).

---

## 2. Konvensi Respons

### Sukses — objek tunggal
```json
{ "message": "Berhasil masuk.", "data": { "id": 1, "name": "..." } }
```

### Sukses — koleksi (paginated)
Resource koleksi mengikuti format Laravel `ResourceCollection`:
```json
{
  "data": [ { "id": 1 }, { "id": 2 } ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": {
    "current_page": 1, "last_page": 5, "per_page": 15, "total": 72,
    "path": "https://<host>/api/v1/documents",
    "links": [ { "url": null, "label": "&laquo; Previous", "active": false } ]
  }
}
```

### Sukses tulis
- `200 OK` — operasi umum (update/hapus/aksi).
- `201 Created` — pembuatan sumber daya baru (`data` berisi objek yang dibuat; beberapa
  endpoint menyertakan header `Location`).

### Error
| Status | Arti | Bentuk |
|---|---|---|
| `401 Unauthorized` | Token tidak ada / tidak valid / kedaluwarsa | `{"message":"Unauthenticated."}` |
| `403 Forbidden` | Peran/visibilitas tidak mencukupi | `{"message":"..."}` |
| `404 Not Found` | Sumber daya tak ada / tak terlihat (menyembunyikan keberadaan) | `{"message":"..."}` |
| `422 Unprocessable Entity` | Validasi gagal | `{"message":"...","errors":{"field":["..."]}}` |
| `429 Too Many Requests` | Melebihi throttle | `{"message":"Too Many Attempts."}` |
| `500 Server Error` | Kesalahan tak terduga | `{"message":"Server Error"}` |

Contoh `422`:
```json
{
  "message": "The given data was invalid.",
  "errors": { "title": ["The title field is required."] }
}
```

### Throttling
Beberapa endpoint dibatasi (berbasis email/IP atau user/IP):
`login` (5/menit), `register` (5 / 15 menit), `ask` & aksi sensitif (10/menit),
unggah dokumen (12/menit). Respons `429` menyertakan header `Retry-After`.

---

## 3. Peran & Hak Akses

| Peran | Deskripsi |
|---|---|
| **Tamu** | Tanpa token. Hanya resource publik & dokumen publik aktif. |
| **anggota** | Semua user terdaftar. Melihat publik + restricted, mengelola miliknya. |
| **staff** | `anggota` + kelola dokumen lintas, agenda, putusan layanan, analytics. |
| **admin** | `staff` + panel admin (pengguna, kategori, unit, kontak, persetujuan). |
| **superadmin** | Semua + panel Super Admin (peran, pengaturan web). |

> `isStaff()` = `staff | admin | superadmin`. `isAdmin()` = `admin | superadmin`.

---

## 4. Referensi Endpoint

Legenda kolom **Auth**: `—` publik · `A` anggota (token) · `S` staff · `AD` admin · `SA` superadmin.

### 4.1 Autentikasi

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| POST | `/auth/register` | — | Daftar akun baru (peran `anggota`), mengembalikan token. |
| POST | `/auth/login` | — | Login, mengembalikan token. |
| GET | `/auth/me` | A | Profil + hitungan dokumen/bookmark. |
| POST | `/auth/logout` | A | Cabut token yang sedang dipakai. |

**POST `/auth/login`**
```json
// Request
{ "email": "staf@lokon.or.id", "password": "Rahasia1!", "device_name": "mobile-app" }
// 200
{ "message": "Berhasil masuk.",
  "data": { "token": "3|ab12...", "token_type": "Bearer",
            "user": { "id": 5, "name": "Staf", "email": "staf@lokon.or.id", "role": "staff", "is_staff": true } } }
```
Error: `422` bila email/kata sandi salah (`errors.email`).

**POST `/auth/register`** — bisa ditutup SA (`reg_enabled`). Bila `reg_domains` diatur,
email wajib cocok domainnya. Body: `name`, `email`, `password`, `password_confirmation`,
`academic_info?`, `wa_number?`, `device_name?`. Mengembalikan `201` + token.

**POST `/auth/logout`** — menghapus token aktif; `200`.

---

### 4.2 Katalog Publik

Semua endpoint di bawah dapat diakses tanpa login (kecuali tercatat lain).

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| GET | `/categories` | — | Daftar kategori + jumlah dokumen. |
| GET | `/systems` | — | Registri sistem operasional + jumlah dokumen. |
| GET | `/systems/{system:slug}` | — | Detail satu sistem. |
| GET | `/faqs` | — | FAQ (tamu: publik saja). |
| GET | `/faqs/{faq:slug}` | — | Detail FAQ (`404` bila internal untuk tamu). |
| GET | `/units` | — | Unit organisasi + jumlah kontak. |
| GET | `/contacts` | — | Kontak PIC publik (dengan unit). |
| GET | `/events` | — | Agenda (tamu: publik). Query: `category`, `page`. |
| GET | `/events/{event:slug}` | — | Detail agenda. |
| GET | `/insight` | — | Statistik etalase publik. |
| GET | `/community` | — | Direktori unit & kontak; `people` hanya untuk login. |
| GET | `/help` | — | FAQ dikelompokkan per kategori. |
| GET | `/services` | — | Katalog 8 tipe layanan. |

**Contoh — GET `/events?category=academic`**
```json
{ "data": [
    { "id": 4, "slug": "asesmen-tengah-semester", "title": "Asesmen Tengah Semester",
      "category": "academic", "starts_at": "2026-10-20T07:30:00+00:00",
      "ends_at": null, "location": "Masing-masing unit", "visibility": "public" } ],
  "meta": { "current_page": 1, "total": 12 } }
```

**Contoh — GET `/insight`**
```json
{ "message": "Berhasil.", "data": { "stats": { "documents": 128, "views": 5400, "events": 12, "faqs": 24 } } }
```

---

### 4.3 Dokumen

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| GET | `/documents` | — | Daftar dokumen (permission-aware). Query: `q`, `category`, `type`, `per_page`, `page`. |
| GET | `/documents/{document:slug}` | — | Detail dokumen. |
| GET | `/documents/{document:slug}/content` | — | Isi dokumen editor (HTML ter-sanitasi) / URL berkas. |
| GET | `/documents/{document:slug}/related` | — | Rekomendasi dokumen terkait (berbobot). |
| POST | `/documents` | A | Unggah PDF baru (`multipart/form-data`). |
| PUT | `/documents/{document:slug}` | A | Perbarui dokumen (metadata / ganti berkas). |
| DELETE | `/documents/{document:slug}` | A | Hapus dokumen. |

**GET `/documents` — perilaku visibilitas**
- **Tamu:** hanya `visibility=public` + `status ∈ {active, review_due}`.
- **Anggota:** publik + restricted (aktif) + dokumen miliknya sendiri.
- **Staff/admin:** semua kecuali `private` milik orang lain (admin: semua).

**Field `data` (DocumentResource):**
`id`, `doc_code`, `title`, `slug`, `subtitle`, `description`, `doc_type`,
`doc_type_label`, `version`, `status`, `classification`, `visibility`, `source_type`,
`audience`, `unit`, `effective_at`, `review_at`, `views_count`, `downloads_count`,
`file_url` (bila terlihat & ada berkas), `has_file`, `uses_structured_reader`,
`is_governance`, `category`, `author`, `approver`, `pic`, `pic_name`, `tags`,
`created_at`, `updated_at`.

**POST `/documents` (multipart)** — field:
| Field | Wajib | Aturan |
|---|---|---|
| `title` | ✅ | string ≤255 |
| `file` | ✅ | PDF ≤ 9 MB (9216 KB) |
| `visibility` | ✅ | `public`\|`private`\|`restricted` |
| `description` | — | string |
| `category_id` | — | exists:categories |
| `doc_type` | — | salah satu `Document::TYPES` |
| `classification` | — | `internal`\|`confidential`\|`public` |
| `version` | — | ≤20 (default `1.0`) |
| `effective_at`, `review_at` | — | tanggal (`review_at ≥ effective_at`) |
| `subtitle`, `unit`, `pic_name` | — | string |
| `audience[]` | — | ⊆ `Document::AUDIENCES` |
| `pic_id` | — | exists:users |

Membuat `document_version` awal dan mencatat `activity_log` (`upload`). Mengembalikan `201`.

**PUT `/documents/{slug}`** — field sama kecuali `file` opsional. Tambahan `change_note?`.
Nomor versi naik otomatis bila `version` tidak diisi / tidak berubah.

**DELETE `/documents/{slug}`** — menghapus record + berkas, mencatat `activity_log`.

**Contoh — GET `/documents/{slug}`**
```json
{ "message": "Berhasil.",
  "data": {
    "id": 12, "doc_code": "YPL-SOP-12", "title": "SOP Pengadaan Barang", "slug": "sop-pengadaan-barang-ab12cd",
    "doc_type": "sop", "doc_type_label": "SOP", "version": "1.2", "status": "active",
    "visibility": "public", "classification": "internal", "has_file": true,
    "file_url": "https://<host>/dokumen/sop-pengadaan-barang-ab12cd/lihat",
    "category": { "id": 3, "name": "Keuangan", "slug": "keuangan" },
    "author": { "id": 2, "name": "Yohanis Manopo" },
    "tags": [ { "id": 1, "name": "Unggulan", "slug": "unggulan", "color": "navy" } ] } }
```

---

### 4.4 Governance

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| GET | `/governance/documents` | — | Daftar governance. Query: `tipe`, `status`, `audiens`, `unit`, `q`, `sort`, `per_page`. |
| GET | `/governance/documents/{document:slug}` | — | Passport + relasi keluar/masuk. |
| GET | `/governance/documents/{document:slug}/versions` | — | Riwayat versi (paginated). |
| GET | `/governance/documents/{document:slug}/impact-map` | — | Impact map + graf. Query: `kedalaman` (1–2). |
| POST | `/documents/{document:slug}/transition` | A | Transisi lifecycle. |
| POST | `/documents/{document:slug}/relations` | A | Tambah relasi pengetahuan. |
| DELETE | `/documents/{document:slug}/relations/{relation}` | A | Hapus relasi. |

**Sort** (`sort`): `latest` (default), `oldest`, `title`, `review_date`, `effective_date`.

**POST `transition`** — body `{ "to": "<status>", "change_note": "..." }`.
Status valid: `draft, review, approval, active, review_due, archived`. Transisi harus
sah menurut `Document::TRANSITIONS` **dan** diizinkan policy (mis. ke `active` hanya admin).
Membuat `document_version` + `activity_log`. `403` bila tidak berwenang / transisi tak sah.

**POST `relations`** — tepat satu target:
```json
{ "related_slug": "sop-lain", "relation_type": "related" }   // atau
{ "system_slug": "odoo" }        // { "event_slug": "..." }   // { "faq_slug": "..." }
```
`relation_type` ∈ `DocumentRelation::TYPES`. `422` bila bukan tepat satu target.

**Contoh — GET `/governance/documents/{slug}`**
```json
{ "message": "Berhasil.",
  "data": {
    "document": { "id": 12, "doc_code": "YPL-SOP-12", "status": "active", "...": "..." },
    "relations": {
      "outgoing": [ { "id": 7, "type": "policy",
                      "related_document": { "title": "Kebijakan Pengadaan", "slug": "kebijakan-pengadaan", "doc_type": "policy" },
                      "system": null, "event": null, "faq": null } ],
      "incoming": [ { "id": 9, "type": "related",
                      "document": { "title": "Formulir PR", "slug": "formulir-pr", "doc_type": "form" } } ] } } }
```

---

### 4.5 Pencarian & Ask YPL

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| GET | `/search` | — | Pencarian universal. Query: `q`. |
| POST | `/ask` | — | Ask YPL (jawaban bersitasi). Throttle 10/menit. |

**GET `/search?q=pengadaan`** mengembalikan grup: `documents`, `services`, `people`
(hanya login), `contacts`, `faqs`, `events`.

**POST `/ask`**
```json
// Request — pertanyaan biasa
{ "question": "Bagaimana cara mengajukan purchase request?" }
// Request — ringkas dokumen (scope)
{ "explain": true, "doc": "sop-pengadaan-barang-ab12cd" }
// 200
{ "message": "Jawaban tersedia.",
  "data": {
    "answer": "Ajukan melalui menu Layanan → Purchase Request...",
    "found": true, "ai": false, "scope": null,
    "sources": [ { "title": "SOP Pengadaan Barang", "slug": "sop-pengadaan-barang-ab12cd",
                   "url": "https://<host>/dokumen/sop-pengadaan-barang-ab12cd",
                   "section": "Prosedur", "passage": "...", "score": 12,
                   "doc_type": "sop", "status": "active", "version": "1.2" } ] } }
```
- `doc` yang tak terlihat → `404` (tanpa fallback).
- `explain` tanpa `doc` → `422`.
- `question` wajib bila bukan `explain`.

---

### 4.6 Profil & Interaksi (anggota)

Semua memerlukan token.

| Method | Path | Keterangan |
|---|---|---|
| PATCH | `/profile` | Perbarui profil (`name` wajib). |
| PUT | `/profile/password` | Ganti kata sandi (`current_password`, `password`, `password_confirmation`). |
| GET | `/profile/stats` | Statistik pribadi (unggahan, views, bookmark, dibaca). |
| POST | `/profile/role-request` | Ajukan kenaikan peran/audiens. |
| GET | `/bookmarks` | Daftar dokumen yang di-bookmark. |
| POST | `/documents/{slug}/bookmark` | Toggle bookmark. |
| POST | `/documents/{slug}/like` | Toggle suka. |
| GET | `/documents/{slug}/notes` | Catatan baca milik sendiri. |
| POST | `/documents/{slug}/notes` | Simpan catatan (`page`, `body`). |
| DELETE | `/documents/{slug}/notes/{note}` | Hapus catatan milik sendiri. |
| GET | `/notifications` | Notifikasi + `unread_count`. |
| POST | `/notifications/{id}/read` | Tandai satu notifikasi terbaca (mengembalikan `url`). |
| POST | `/notifications/read-all` | Tandai semua terbaca. |

**PATCH `/profile`** — `name` (wajib), `academic_info?`, `birth_place?`, `birth_date?`,
`wa_number?` (dinormalisasi `0896…` → `+62896…`).

**POST `bookmark`** → `{ "data": { "bookmarked": true } }`.
**POST `like`** → `{ "data": { "liked": true, "likes_count": 4 } }`.
**POST `notes`** → `201` dengan objek catatan.
**POST `role-request`** — salah satu dari `requested_role` (`staff`|`admin`) atau
`requested_audience` (⊆ `User::AUDIENCES`), plus `reason?`.

---

### 4.7 Layanan (Services)

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| POST | `/service-requests` | A | Kirim pengajuan. |
| GET | `/service-requests/my` | A | Pengajuan milik sendiri. |
| GET | `/service-requests` | S | Kelola semua. Query: `status`, `type`, `per_page`. |
| POST | `/service-requests/{serviceRequest}/decide` | S | Putusan (`to`, `handled_note?`). Throttle 10/menit. |

**POST `/service-requests`**
```json
{ "type": "purchase", "title": "Beli laptop", "description": "Untuk tim IT.", "needed_at": "2026-11-01" }
```
`type` ∈ `{transportation, leave, maintenance, student_permission, purchase, event, incident, submission}`.
`needed_at ≥ hari ini`. Status awal `pending`.

**POST `decide`** — `to` ∈ `{pending, approved, rejected, done}`; transisi harus sah
(`ServiceRequest::TRANSITIONS`). `422` bila transisi tidak valid.

---

### 4.8 Panel Admin

Auth: **AD** (admin/superadmin). Prefix `/admin`.

| Method | Path | Keterangan |
|---|---|---|
| GET | `/admin/dashboard` | Statistik + 30 log terbaru + pending approvals. |
| GET | `/admin/analytics` | Agregat 30 hari, tren 14 hari, top dokumen, layanan. |
| GET | `/admin/logs` | Riwayat aktivitas. Query: `action`, `per_page`. |
| GET | `/admin/users` | Daftar pengguna (+jumlah dokumen/bookmark). Query: `q`, `role`, `per_page`. |
| PUT | `/admin/users/{user}` | Perbarui pengguna (`name`, `email`, `audience`; SA juga `role`). |
| DELETE | `/admin/users/{user}` | Hapus pengguna. |
| GET/POST | `/admin/categories` | Daftar / tambah kategori. |
| PUT/DELETE | `/admin/categories/{category:slug}` | Ubah / hapus kategori. |
| GET/POST | `/admin/units` | Daftar / tambah unit. |
| PUT/DELETE | `/admin/units/{unit}` | Ubah / hapus unit. |
| GET/POST | `/admin/contacts` | Daftar / tambah kontak. |
| PUT/DELETE | `/admin/contacts/{contact}` | Ubah / hapus kontak. |
| GET | `/admin/role-requests` | Daftar pengajuan peran/audiens. |
| POST | `/admin/role-requests/{roleRequest}/approve` | Setujui. |
| POST | `/admin/role-requests/{roleRequest}/reject` | Tolak. |

**Aturan pengguna:**
- Admin tidak dapat melihat/mengubah akun `superadmin`.
- Admin hanya dapat menaikkan peran ke `admin`; hanya SA yang menyetel `role` bebas (`anggota`|`staff`|`admin`).
- Tidak dapat menghapus akun sendiri / akun superadmin.

**Kategori — field:** `name` (wajib), `description?`, `code?` (unik), `collection?`
(`handbook|governance|academic|boarding|staff|parent|public`), `color?`, `sort_order?`.
Kategori `collection=handbook` **dilindungi** dari penghapusan (`422`), begitu pula yang masih berisi dokumen.

**Unit — field:** `name` (wajib ≤120), `description?` ≤255, `sort_order?` 0–999.
**Kontak — field:** `unit_id?`, `name` wajib ≤120, `position?`, `email?`, `phone?`, `sort_order?`.

**role-request approve/reject** — peningkatan ke `admin` hanya boleh disetujui SA (`403`).
Pengajuan yang sudah `!= pending` → `422`.

---

### 4.9 Panel Super Admin

Auth: **SA**. Prefix `/sa`.

| Method | Path | Keterangan |
|---|---|---|
| GET | `/sa/dashboard` | Statistik global + daftar fitur. |
| GET | `/sa/users` | Semua pengguna. Query: `per_page`. |
| PUT | `/sa/users/{user}/role` | Ubah peran + audiens. |
| DELETE | `/sa/users/{user}` | Hapus pengguna. |
| GET | `/sa/settings` | Pengaturan web + sakelar fitur. |
| PUT | `/sa/settings` | Perbarui pengaturan. |
| GET/POST | `/sa/roles` | Daftar / buat definisi peran. |
| PUT/DELETE | `/sa/roles/{role}` | Ubah / hapus definisi peran. |

**PUT `/sa/users/{user}/role`** — `{ "role": "staff", "audience": "guru" }`.
`role` ∈ `{anggota, staff, admin}`. Mengubah akun `superadmin` → `403`.

**PUT `/sa/settings`**
```json
{ "site_name": "Lokon Dokumen Digital", "foundation_name": "Yayasan Pendidikan Lokon",
  "reg_domains": "lokon.or.id", "retention_days": 1095,
  "features": { "upload": true, "download": false } }
```
`features.*` boolean untuk kunci di `Setting::FEATURES` (`reg, upload, editor, download, like, bookmark, share, embed`).

**POST `/sa/roles`** — field: `slug` (`^[a-z0-9_]+$`, unik), `name`, `description?`,
`priority` (0–999), `collections[]?`, `quick[]?`, `explore[]?`. Slug peran sistem dikunci
saat update. Peran sistem / yang masih dipakai tidak dapat dihapus (`422`).

---

## 5. Contoh Alur Lengkap

### 5.1 Unggah dokumen (anggota)
```bash
TOKEN="3|xxxx"
curl -X POST https://<host>/api/v1/documents \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -F "title=SOP Pengadaan Barang" \
  -F "visibility=public" \
  -F "doc_type=sop" \
  -F "category_id=3" \
  -F "file=@sop-pengadaan.pdf"
```

### 5.2 Ajukan & proses layanan (staff)
```bash
# Anggota mengajukan
curl -X POST https://<host>/api/v1/service-requests \
  -H "Authorization: Bearer $USER_TOKEN" -H "Accept: application/json" \
  -d '{"type":"leave","title":"Cuti tahunan","needed_at":"2026-11-05"}'

# Staff menyetujui
curl -X POST https://<host>/api/v1/service-requests/12/decide \
  -H "Authorization: Bearer $STAFF_TOKEN" -H "Accept: application/json" \
  -d '{"to":"approved","handled_note":"Disetujui HR."}'
```

### 5.3 Kelola agenda (staff)
```bash
curl -X POST https://<host>/api/v1/events \
  -H "Authorization: Bearer $STAFF_TOKEN" -H "Accept: application/json" \
  -d '{"title":"Rapat Tahunan","category":"foundation","starts_at":"2026-12-01 09:00","visibility":"internal"}'
```

### 5.4 Transisi governance (admin)
```bash
curl -X POST https://<host>/api/v1/documents/sop-pengadaan-ab12cd/transition \
  -H "Authorization: Bearer $ADMIN_TOKEN" -H "Accept: application/json" \
  -d '{"to":"active","change_note":"Disetujui pada rapat."}'
```

---

## 6. Kode Postman / Klien

Impor cepat ke Postman/Insomnia:
1. Buat Environment dengan variabel `baseUrl` = `https://<host>/api/v1` dan `token`.
2. Request login → simpan `data.token` ke variabel `token`.
3. Set header default `Accept: application/json` dan `Authorization: Bearer {{token}}`.

---

## 7. Catatan Implementasi

- **Lokasi kode:** `routes/api.php`, `app/Http/Controllers/Api/*`, `app/Http/Resources/*`.
- **Tabel token:** `personal_access_tokens` (migrasi Sanctum).
- **Middleware peran** identik dengan web: `staff`, `admin`, `superadmin` (alias di
  `bootstrap/app.php`) — dijalankan setelah `auth:sanctum`.
- **Visibilitas dokumen** ditegakkan lewat `Document::scopeVisibleTo()` sehingga paritas
  dengan web (tidak ada kebocoran dokumen privat).
- **Aktivitas** tulis API dicatat ke `activity_logs` dengan penanda `via: api`.
- **Unggah berkas** memakai transaksi aman (`App\Services\DocumentFiles`); berkas
  disimpan di disk `private`.

---

## 8. Uji Cakupan

Suite API: `tests/Feature/YplApiTest.php` (32 test) mencakup autentikasi, izin per peran,
visibilitas dokumen, CRUD event/dokumen/kategori/unit/kontak, transisi governance,
layanan, pencarian, dan interaksi akun.

```bash
php artisan test --filter=YplApiTest
```
