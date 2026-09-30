# Deploy ke InfinityFree (tanpa SSH)

## 1. Siapkan file
- Salin file `.env.production` menjadi `.env`, lalu sesuaikan:
  - `APP_URL` → `https://domain-anda` (atau subdomain `.infinityfreeapp.com`)
  - `APP_KEY` → sudah terisi unik, jangan samakan antar instalasi
  - `DEPLOY_SECRET` → isi string acak panjang (mis. 40 karakter)
  - `DEPLOY_SA_EMAIL` → email Super Admin (opsional)
  - Kredensial MySQL sudah terisi (host `sql312.infinityfree.com`).
- Pastikan `APP_DEBUG=false`.

## 2. Upload file
- Upload **isi folder `public/`** ke `htdocs/` di hosting.
- Upload **seluruh folder aplikasi** (app, bootstrap, config, database, resources, routes, storage, vendor, artisan, composer.json) ke **satu level di atas** `htdocs` (sejajar, bukan di dalam).
- Edit `htdocs/index.php`: ubah path `__DIR__.'/../...'` menjadi `__DIR__.'/../../...'`? **Tidak** — bila struktur di atas benar (`htdocs` sejajar `app`), path bawaan `__DIR__.'/../vendor/autoload.php'` dan `__DIR__.'/../bootstrap/app.php'` sudah tepat. Jangan ubah.
- Pastikan folder `storage/` writable (chmod 775). Storage file PDF memakai disk lokal `private` di `storage/app/private` — aman di InfinityFree.

## 3. Siapkan database (sekali saja)
- Buat database MySQL di control panel (nama/user/host sesuai `.env`).
- Buka di browser: `https://domain-anda/deploy/SECRET` (ganti SECRET dengan `DEPLOY_SECRET`).
- Respons JSON `{"ok": true}` = migrasi + seed admin + (opsional) Super Admin berhasil. **Catat password SA yang tampil sekali itu.**
- **WAJIB: hapus rute `/deploy/{secret}` dari `routes/web.php`**, upload ulang file tersebut.

## 4. Batasan yang berlaku di InfinityFree
- Upload PDF maksimal **9 MB** (limit server ±10 MB). File 50 MB tidak bisa di-hosting di sini.
- Pustaka 15 PDF besar dari dev **tidak ikut** (terlalu besar untuk diunggah). Koleksi dimulai dari seed contoh; tambah dokumen ≤9 MB via menu Staff.
- Tanpa worker antrean (sudah `sync`), tanpa cron — semua fitur tetap jalan.
- Bila gambar avatar/logo rusak (symlink `public/storage` gagal), hubungi developer untuk patch serving via route.

## 5. Akun bawaan (ganti password setelah masuk!)
- Admin: `admin@lokon.or.id` / `password`
- Super Admin: sesuai output deploy di atas
