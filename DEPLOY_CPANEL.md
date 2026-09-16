# Radius Sistem - Panduan Deploy ke cPanel (Subdomain)
========================================================

## 1. Persiapan Database (di cPanel)
1. Buka **cPanel -> MySQL Databases**.
2. Buat database baru, misal `username_radius`.
3. Buat user MySQL baru, misal `username_radius`, beri password kuat.
4. Di bagian "Add User To Database", centang **ALL PRIVILEGES**.
5. Buka **phpMyAdmin** -> pilih database kamu.
6. Klik tab **Import**, pilih file `database/radius_sistem.sql` (sudah
   termasuk semua tabel + data contoh: 3 cabang, wilayah cakupan,
   2 user), lalu **Go**.

   *File ini sudah dikonversi ke collation `utf8mb4_general_ci`
   (kompatibel MySQL 5.7/MariaDB), bukan `utf8mb4_0900_ai_ci`.*

### Jika tabel staff sudah ada

Jika database production sudah memiliki tabel `staff` dari versi sebelumnya,
jalankan `database/add_employee_code.sql` terlebih dahulu. File ini menambahkan
kolom `employee_code` dan unique index tanpa menghapus data staff existing.
Setelah itu baru jalankan `database/teachers_import.sql`.

### Tabel commercial_insights (insight area komersial)

Fitur "Insight Area Komersial" butuh tabel `commercial_insights`. Jalankan
`database/commercial_insights_module.sql` (idempotent — aman dijalankan
berulang, tidak menghapus data existing). Setelah tabel ada, data per cabang
diisi otomatis saat cabang disimpan/diedit, atau manual lewat tombol
**Refresh Insight** di halaman detail cabang.

## 2. Upload File
1. Buka **cPanel -> File Manager** (atau pakai FTP/SFTP).
2. Masuk ke folder root subdomain kamu (biasanya
   `public_html` atau `public_html/<nama-subdomain>`).
3. Upload **seluruh isi folder proyek**:
   ```
   index.php
   .htaccess
   .env
   app/
   config/
   public/
   views/
   database/
   composer.json
   ```
   > Jangan upload `preview-dashboard.html`, `*.docx`, `Design_System*.md`
   > kalau tidak perlu — tidak membahayakan tapi tidak diperlukan.
4. **Ganti `.htaccess`**: file `.htaccess` bawaan berisi
   `RewriteBase /radius-sistem/` yang khusus Laragon. Ganti dengan isi
   file **`.htaccess.cpanel`** yang sudah disiapkan (tanpa RewriteBase).

## 3. Konfigurasi .env
1. Di proyek, rename/copy `.env.example` menjadi `.env` **di server**
   (atau edit `.env.cpanel.example` dulu lalu upload sebagai `.env`).
2. Isi nilai yang benar:
   ```
   DB_NAME=<database yang tadi dibuat>
   DB_USER=<user yang tadi dibuat>
   DB_PASSWORD=<password user>
   APP_URL=https://radiusmarketing.rumahbiru.id
   NOMINATIM_USER_AGENT=<email kamu>
   CURL_CAINFO=            # biarkan kosong
   ```

## 4. Composer (opsional tapi disarankan)
Aplikasi punya fallback autoloader (index.php), jadi bisa jalan tanpa
`vendor/`. Kalau mau instal vendor:
```
cd <folder proyek di server>
composer install --no-dev
```
Jika hosting tidak punya composer via SSH, upload folder `vendor/`
dari lokal (setelah `composer install` di komputer kamu).

## 5. Testing
- Buka `https://<subdomain kamu>/`
- Harusnya redirect ke halaman login.
- Login admin bawaan:
  - Email: `admin@radius.local`
  - Password: `admin123`
- Ganti password segera via fitur (tambah/edit user) jika sudah ada,
  atau langsung di DB.

## Troubleshooting
- **500 error**: cek `error_log` di folder root; pastikan `.htaccess`
  sudah versi cPanel dan `.env` terisi benar.
- **Login gagal (unknown user)**: pastikan import SQL sukses penuh
  (tabel users berisi 2 baris).
- **Error SSL / geocoding gagal di server**: biarkan `CURL_CAINFO=`
  kosong. Kalau error tetap muncul, upload `config/cacert.pem` dan
  isi `CURL_CAINFO` dengan path absolut file tsb.
- **Setelah deploy, password admin `admin123`** berlaku (hash sudah
  diperbaiki di `radius_sistem.sql`).
