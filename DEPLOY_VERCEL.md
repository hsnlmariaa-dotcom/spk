# Panduan Lengkap Deploy CDSS-DBD ke Vercel

Sistem Pendukung Keputusan Klinis (CDSS) Demam Berdarah Dengue ini telah dikonfigurasi agar dapat dideploy ke **Vercel** menggunakan **Vercel Serverless PHP Runtime** (`vercel-php@0.8.0` - PHP 8.4).

---

## 🏗️ Cara Kerja di Vercel

1. **Serverless Entrypoint (`api/index.php`)**: Bertindak sebagai front-controller yang meneruskan request ke file PHP yang sesuai (`index.php`, `login.php`, `diagnosa.php`, `hasil.php`, `pasien.php`, dll).
2. **Aset Statis**: File CSS dan JavaScript di `/assets/` langsung disajikan oleh Vercel CDN tanpa melewati PHP, sehingga sangat cepat dan hemat resource.
3. **Session Persistence**: Menggunakan signed HMAC token via cookie (`includes/auth.php`) sehingga sesi login tetap aman dan tidak hilang meskipun fungsi Lambda di-spin up atau di-restart di container baru.
4. **Cloud MySQL Database**: Karena Vercel berjalan secara serverless (tanpa localhost MySQL), Anda perlu menghubungkan aplikasi ke MySQL berbasis cloud.

---

## 🚀 Langkah 1: Siapkan Cloud MySQL Database (Gratis)

Pilih salah satu penyedia cloud database gratis berikut:

### Pilihan Rekomendasi: **TiDB Cloud (Serverless)**
1. Daftar gratis di [https://tidbcloud.com](https://tidbcloud.com) (Tanpa kartu kredit).
2. Buat cluster baru: pilih **Serverless** (Free 5GB).
3. Pilih region terdekat: **AWS Singapore (`ap-southeast-1`)**.
4. Klik **Connect**, lalu catat informasi koneksinya:
   - **Host** (contoh: `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`)
   - **Port** (biasanya: `4000`)
   - **User** (contoh: `xxxxxx.root`)
   - **Password** (password yang Anda buat)
   - **Database** (bisa `test` atau buat database `cdss_dbd`)

*(Alternatif lain yang didukung: Aiven for MySQL, Railway MySQL, Clever Cloud, atau Supabase).*

---

## 🔑 Langkah 2: Konfigurasi Environment Variables di Vercel

Di dashboard proyek Vercel Anda:
1. Masuk ke **Settings** &rarr; **Environment Variables**.
2. Tambahkan variabel berikut (pilih target **Production**, **Preview**, dan **Development**):

| Nama Variabel | Contoh Nilai | Keterangan |
| :--- | :--- | :--- |
| `DB_HOST` | `gateway01.ap-southeast-1.prod.aws.tidbcloud.com` | Host MySQL Cloud |
| `DB_PORT` | `4000` *(atau `3306`)* | Port MySQL Cloud |
| `DB_USER` | `xxxxxx.root` | Username MySQL Cloud |
| `DB_PASS` | `password_anda` | Password database |
| `DB_NAME` | `cdss_dbd` *(atau `test`)* | Nama database |
| `DB_SSL` | `true` | Wajib `true` untuk TiDB / Aiven |
| `AUTH_SECRET` | `kunci_rahasia_acak_32_karakter_bebas` | Kunci enkripsi token login |

> **Catatan:** Jika provider Anda menyediakan satu baris URL koneksi, Anda juga bisa langsung menggunakan variabel tunggal `DATABASE_URL=mysql://user:pass@host:port/dbname`.

---

## 🗄️ Langkah 3: Inisialisasi Database & Seeder Data

Setelah proyek berhasil dideploy di Vercel dan Environment Variables telah diisi, Anda memiliki **2 cara** untuk mengisi database:

### Cara A: Melalui Browser (Paling Cepat & Otomatis)
Cukup buka URL proyek Anda yang sudah aktif dan akses endpoint setup:
```
https://nama-proyek-anda.vercel.app/database_setup.php
```
Halaman wizard akan otomatis membuat seluruh tabel, men-seed aturan Certainty Factor, 16 gejala & kriteria lab, 13 data simulasi pasien, dan akun default dokter & admin.

### Cara B: Import Manual via SQL Dump
Import file `cdss_dbd.sql` yang ada di root proyek ke MySQL Cloud Anda melalui:
- SQL Console di dashboard TiDB Cloud / Aiven, atau
- Software database management (DBeaver, Navicat, MySQL Workbench).

---

## 📤 Langkah 4: Deploy ke Vercel

### Metode 1: Melalui GitHub (Rekomendasi)
Karena proyek ini sudah terhubung ke GitHub (`origin/main`):
```bash
git add .
git commit -m "Configure Vercel deployment with community PHP runtime and cloud DB support"
git push origin main
```
1. Buka [https://vercel.com/new](https://vercel.com/new).
2. Hubungkan akun GitHub Anda dan pilih repositori `spk`.
3. Di bagian **Framework Preset**, biarkan **Other** (karena Vercel membaca konfigurasi dari `vercel.json`).
4. Buka accordion **Environment Variables** dan masukkan data database dari Langkah 2.
5. Klik **Deploy**!

### Metode 2: Menggunakan Vercel CLI
Jika Anda menginstal Vercel CLI di komputer:
```bash
# Login ke akun Vercel
npx vercel login

# Deploy ke production
npx vercel --prod
```

---

## 🔐 Akun Login Default Sistem

Setelah database di-setup, Anda dapat langsung login menggunakan akun berikut:

| Peran (Role) | Username | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` |
| **Dokter / Pakar** | `dokter1` | `dokter123` |

---

## 📂 Struktur File Konfigurasi Vercel

- `vercel.json` &mdash; Konfigurasi runtime PHP `vercel-php@0.8.0`, mapping static assets, dan catch-all rewrite ke `api/index.php`.
- `.vercelignore` &mdash; Daftar berkas lokal yang dikecualikan dari upload agar deployment ringan dan cepat.
- `api/index.php` &mdash; Front controller Vercel Serverless Function yang memproses setiap request routing dan query string.
- `config/database.php` &mdash; Koneksi database dinamis yang mendukung env vars, SSL cloud database, dan auto HTTPS URL detection.
- `includes/auth.php` &mdash; Sistem autentikasi dengan sinkronisasi cookie HMAC untuk mencegah session loss pada serverless function.

