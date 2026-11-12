<?php
/**
 * KONFIGURASI APLIKASI
 * Data rahasia (host, user, password database) ditulis di config.local.php,
 * bukan di file ini. Salin config.local.example.php menjadi config.local.php lalu isi.
 * config.local.php tidak ikut ke GitHub (lihat .gitignore), jadi password tetap aman.
 */

// Lokasi folder aplikasi
$lokasiConfig = dirname(realpath('config.php') ?: 'config.php');

// Baca konfigurasi lokal/hosting jika ada
if (file_exists($lokasiConfig . '/config.local.php')) {
    require $lokasiConfig . '/config.local.php';
}

// Driver database: 'mysql' (disarankan untuk hosting) atau 'sqlite' (tanpa server DB)
defined('DB_DRIVER') || define('DB_DRIVER', 'mysql');

// --- Pengaturan MySQL / MariaDB (default untuk XAMPP lokal) ---
defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_PORT') || define('DB_PORT', '3306');
defined('DB_NAME') || define('DB_NAME', 'lab_inventory');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');

// --- Pengaturan SQLite (dipakai jika DB_DRIVER = 'sqlite') ---
define('DB_SQLITE_PATH', $lokasiConfig . '/data/lab_inventory.sqlite');

// --- Aplikasi ---
define('APP_NAME', 'Lab Inventory');
define('APP_FULLNAME', 'Laboratory Inventory & Borrowing Management System');
defined('SHOW_DEMO_ACCOUNTS') || define('SHOW_DEMO_ACCOUNTS', true); // set false setelah dinilai

date_default_timezone_set('Asia/Jakarta');