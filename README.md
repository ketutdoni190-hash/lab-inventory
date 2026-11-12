# Laboratory Inventory & Borrowing Management System

Aplikasi web untuk mengelola inventaris dan transaksi peminjaman barang laboratorium komputer.
Dibangun dengan **PHP 8 (native, PDO) + MySQL/MariaDB + Bootstrap 5**. Bisa di-hosting di
hosting gratis maupun berbayar (InfinityFree, AwardSpace, Niagahoster, Hostinger, dll.).

## Akun Awal

| Role  | Username | Password  |
|-------|----------|-----------|
| Admin | `admin`  | `admin123`|
| Staff | `staff1` | `staff123`|
| Staff | `staff2` | `staff123`|
| Staff (nonaktif, untuk uji) | `staff3` | `staff123` |

> Segera ganti password setelah aplikasi online (menu **Manajemen Pengguna → Edit**).

## Fitur

| Fitur | Admin | Staff |
|---|:-:|:-:|
| Login / logout, dashboard sesuai role | ✅ | ✅ |
| Dashboard: total barang, tersedia, dipinjam, total transaksi, aktivitas terbaru | ✅ (semua pengguna) | ✅ (aktivitas sendiri) |
| Lihat daftar barang + status ketersediaan, pencarian & filter | ✅ | ✅ |
| Tambah / edit / hapus barang | ✅ | ❌ |
| Catat peminjaman (nama peminjam, barang, jumlah, tanggal, status) | ✅ | ✅ |
| Proses pengembalian (stok otomatis bertambah, status → Selesai) | ✅ | ✅ |
| Riwayat transaksi | ✅ semua | ✅ yang relevan* |
| Manajemen pengguna (tambah, edit, aktif/nonaktif akun) | ✅ | ❌ |
| Log aktivitas seluruh pengguna | ✅ | ❌ |

\*Transaksi yang relevan bagi Staff = transaksi yang ia catat atau terima pengembaliannya, ditambah semua barang yang masih dipinjam.

Pengamanan: password di-hash (`password_hash`), semua query memakai *prepared statement*,
token CSRF di setiap form, proteksi XSS (`htmlspecialchars`), pembatasan akses per role di sisi server
(bukan hanya menyembunyikan menu), akun nonaktif langsung ter-logout, pengurangan stok atomik sehingga
stok tidak bisa minus, dan pengembalian tidak bisa diproses dua kali.

Data awal berisi **35 barang** (BR001–BR005 sesuai soal + 30 barang tambahan) dan 4 akun. Belum ada transaksi, sehingga stok tersedia = stok awal sesuai soal.

## Struktur Folder

```
lab-inventory/
├── index.php            Dashboard
├── login.php, logout.php
├── barang.php           Daftar & pencarian barang
├── barang_form.php      Tambah/edit barang (Admin)
├── barang_hapus.php     Hapus barang (Admin)
├── peminjaman.php       Catat peminjaman
├── pengembalian.php     Proses pengembalian
├── riwayat.php          Riwayat transaksi
├── users.php            Daftar & aktif/nonaktif pengguna (Admin)
├── user_form.php        Tambah/edit pengguna (Admin)
├── aktivitas.php        Log aktivitas (Admin)
├── install.php          Installer otomatis (hapus setelah dipakai)
├── config.php           Konfigurasi umum (aman di-commit)
├── config.local.example.php  Contoh → salin jadi config.local.php (berisi password DB, tidak di-commit)
├── database.sql         Struktur + data awal untuk phpMyAdmin
├── includes/            init (koneksi, helper, auth), schema, header, footer
├── assets/style.css
└── data/                (untuk SQLite opsional & file lock installer)
```

## Menjalankan di Komputer Sendiri (XAMPP/Laragon)

1. Salin folder `lab-inventory` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka phpMyAdmin → buat database `lab_inventory` → tab **Import** → pilih `database.sql` → **Go**.
3. Default sudah cocok untuk XAMPP (`root` tanpa password). Jika berbeda, buat `config.local.php` dari `config.local.example.php`.
4. Buka `http://localhost/lab-inventory`.

Alternatif tanpa MySQL: buat `config.local.php` berisi `<?php define('DB_DRIVER', 'sqlite');` — database dibuat otomatis
di folder `data/` saat aplikasi pertama kali dibuka.

## Deploy ke Hosting Publik (contoh: InfinityFree — gratis)

1. Daftar di <https://www.infinityfree.com>, buat akun hosting baru dan pilih subdomain
   (misal `labinventory.infinityfreeapp.com`).
2. Masuk **Control Panel → MySQL Databases** → buat database. Catat **MySQL Hostname**
   (mis. `sql123.infinityfree.com`), **Database Name** (mis. `if0_12345678_lab`), **Username**, dan **Password**
   (password ada di halaman akun hosting).
3. Salin `config.local.example.php` menjadi **`config.local.php`**, lalu isi data dari langkah 2.
   File ini tidak ikut ter-upload ke GitHub (sudah ada di `.gitignore`), jadi password database aman.
4. Buka **phpMyAdmin** dari control panel → pilih database → **Import** → `database.sql` → **Go**.
   (Atau lewati langkah ini dan buka `https://domain-anda/install.php` sekali.)
5. Upload **isi** folder `lab-inventory` (bukan foldernya) ke folder `htdocs` lewat **Online File Manager**
   atau FTP (FileZilla). Pastikan file `.htaccess` ikut terunggah.
6. Hapus `install.php` dari server, lalu buka domain Anda dan login.
7. Setelah dinilai, set `SHOW_DEMO_ACCOUNTS` di `config.local.php` ke `false` dan ganti password default.

Hosting lain (cPanel: Niagahoster, Hostinger, dll.) caranya sama: buat database di **MySQL Databases**,
impor `database.sql` lewat phpMyAdmin, unggah file ke `public_html`, lalu buat `config.local.php`.

**Kebutuhan server:** PHP ≥ 8.0 dengan ekstensi PDO MySQL (standar di hampir semua hosting), MySQL 5.7+/MariaDB 10.3+.

## Teknologi

- **Backend:** PHP 8 (native, PDO), session-based authentication, role-based access control
- **Database:** MySQL / MariaDB (opsional SQLite)
- **Frontend:** HTML5, Bootstrap 5.3, Bootstrap Icons, JavaScript (vanilla)
- **Hosting:** InfinityFree (Apache + PHP + MySQL)

## Alur Penggunaan Singkat

1. **Admin** login → tambah barang di *Data Barang* dan akun petugas di *Manajemen Pengguna*.
2. **Staff/Admin** mencatat peminjaman di *Peminjaman* → stok tersedia berkurang, status **Dipinjam**.
3. Saat barang kembali, buka *Pengembalian* → **Kembalikan** → pilih kondisi → stok bertambah, status **Selesai**.
4. Transaksi yang melewati tanggal rencana kembali otomatis ditandai **Terlambat** dan muncul di dashboard.
