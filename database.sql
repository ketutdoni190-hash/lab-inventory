-- Laboratory Inventory & Borrowing Management System
-- Database MySQL/MariaDB. Impor via phpMyAdmin (pilih database dulu, lalu tab Import).
-- Akun: admin/admin123, staff1/staff123, staff2/staff123

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS log_aktivitas, peminjaman, barang, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(10) NOT NULL DEFAULT 'staff',
  status VARCHAR(10) NOT NULL DEFAULT 'aktif',
  last_login DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS barang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  nama VARCHAR(150) NOT NULL,
  kategori VARCHAR(50) NOT NULL,
  stok_total INT NOT NULL DEFAULT 0,
  stok_tersedia INT NOT NULL DEFAULT 0,
  lokasi VARCHAR(100) NULL,
  keterangan TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS peminjaman (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_transaksi VARCHAR(30) NULL UNIQUE,
  nama_peminjam VARCHAR(100) NOT NULL,
  kontak_peminjam VARCHAR(100) NULL,
  barang_id INT NULL,
  kode_barang VARCHAR(20) NOT NULL,
  nama_barang VARCHAR(150) NOT NULL,
  jumlah INT NOT NULL,
  tanggal_pinjam DATE NOT NULL,
  tanggal_rencana_kembali DATE NULL,
  tanggal_kembali DATETIME NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'dipinjam',
  keperluan VARCHAR(255) NULL,
  kondisi_kembali VARCHAR(20) NULL,
  catatan_kembali VARCHAR(255) NULL,
  dicatat_oleh INT NULL,
  dikembalikan_oleh INT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE SET NULL,
  FOREIGN KEY (dicatat_oleh) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (dikembalikan_oleh) REFERENCES users(id) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS log_aktivitas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  aksi VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, nama, username, password, role, status, last_login, created_at, updated_at) VALUES
(1, 'Administrator Lab', 'admin', '$2y$10$442rHWmJl3xtlPZe/AXTpurnyFP0.LJbl8q53Lqcza1zf8Vi71Wx2', 'admin', 'aktif', NULL, '2026-11-12 13:04:11', NULL),
(2, 'Rina Kartika', 'staff1', '$2y$10$O9ONrjFjcSdpEwRjYglzp.I3RAr9kRNTD5REzy4HgoR6ULFvZ.gCi', 'staff', 'aktif', NULL, '2026-11-12 13:04:11', NULL),
(3, 'Dimas Saputra', 'staff2', '$2y$10$pKCwOxC./IXR3piwccoVlOJXXA9rccG33AqLX1pdCVk022V0kggeG', 'staff', 'aktif', NULL, '2026-11-12 13:04:11', NULL),
(4, 'Yoga Pratama', 'staff3', '$2y$10$6buUiy1YVwUijEDaP/lkWe7Plf2GBn.AzrGylx09s7rT1H/.X3d8S', 'staff', 'nonaktif', NULL, '2026-11-12 13:04:11', NULL);

INSERT INTO barang (id, kode, nama, kategori, stok_total, stok_tersedia, lokasi, keterangan, created_at, updated_at) VALUES
(1, 'BR001', 'Laptop Lenovo', 'Laptop', 5, 5, 'Lemari A1', NULL, '2026-11-12 13:04:11', NULL),
(2, 'BR002', 'Mouse Logitech', 'Periferal', 10, 10, 'Laci B1', NULL, '2026-11-12 13:04:11', NULL),
(3, 'BR003', 'Keyboard Mechanical', 'Periferal', 8, 8, 'Laci B2', NULL, '2026-11-12 13:04:11', NULL),
(4, 'BR004', 'Proyektor Epson', 'Presentasi', 3, 3, 'Lemari C1', NULL, '2026-11-12 13:04:11', NULL),
(5, 'BR005', 'Monitor LG', 'Monitor', 6, 6, 'Rak D1', NULL, '2026-11-12 13:04:11', NULL),
(6, 'BR006', 'Laptop ASUS VivoBook 14', 'Laptop', 4, 4, 'Lemari A1', NULL, '2026-11-12 13:04:11', NULL),
(7, 'BR007', 'Laptop HP ProBook 440', 'Laptop', 3, 3, 'Lemari A2', NULL, '2026-11-12 13:04:11', NULL),
(8, 'BR008', 'Monitor Samsung 24 inch', 'Monitor', 5, 5, 'Rak D1', NULL, '2026-11-12 13:04:11', NULL),
(9, 'BR009', 'Monitor Dell 27 inch', 'Monitor', 2, 2, 'Rak D2', NULL, '2026-11-12 13:04:11', NULL),
(10, 'BR010', 'Mouse Wireless Rexus', 'Periferal', 12, 12, 'Laci B1', NULL, '2026-11-12 13:04:11', NULL),
(11, 'BR011', 'Keyboard Logitech K120', 'Periferal', 15, 15, 'Laci B2', NULL, '2026-11-12 13:04:11', NULL),
(12, 'BR012', 'Headset Logitech H390', 'Audio', 10, 10, 'Laci B3', NULL, '2026-11-12 13:04:11', NULL),
(13, 'BR013', 'Webcam Logitech C270', 'Periferal', 6, 6, 'Laci B3', NULL, '2026-11-12 13:04:11', NULL),
(14, 'BR014', 'Speaker Aktif Simbadda', 'Audio', 4, 4, 'Rak D3', NULL, '2026-11-12 13:04:11', NULL),
(15, 'BR015', 'Proyektor BenQ MS560', 'Presentasi', 2, 2, 'Lemari C1', NULL, '2026-11-12 13:04:11', NULL),
(16, 'BR016', 'Layar Proyektor 70 inch', 'Presentasi', 3, 3, 'Gudang', NULL, '2026-11-12 13:04:11', NULL),
(17, 'BR017', 'Laser Pointer Logitech R400', 'Presentasi', 5, 5, 'Lemari C2', NULL, '2026-11-12 13:04:11', NULL),
(18, 'BR018', 'Router TP-Link Archer C6', 'Jaringan', 4, 4, 'Lemari E1', NULL, '2026-11-12 13:04:11', NULL),
(19, 'BR019', 'Switch Cisco 24 Port', 'Jaringan', 2, 2, 'Lemari E1', NULL, '2026-11-12 13:04:11', NULL),
(20, 'BR020', 'Switch TP-Link 8 Port', 'Jaringan', 5, 5, 'Lemari E1', NULL, '2026-11-12 13:04:11', NULL),
(21, 'BR021', 'Access Point Ubiquiti UniFi', 'Jaringan', 3, 3, 'Lemari E2', NULL, '2026-11-12 13:04:11', NULL),
(22, 'BR022', 'Kabel LAN Cat6 10 m', 'Kabel', 20, 20, 'Lemari E3', NULL, '2026-11-12 13:04:11', NULL),
(23, 'BR023', 'Crimping Tool RJ45', 'Peralatan Jaringan', 6, 6, 'Lemari E3', NULL, '2026-11-12 13:04:11', NULL),
(24, 'BR024', 'LAN Tester', 'Peralatan Jaringan', 5, 5, 'Lemari E3', NULL, '2026-11-12 13:04:11', NULL),
(25, 'BR025', 'Kabel HDMI 3 m', 'Kabel', 15, 15, 'Laci F1', NULL, '2026-11-12 13:04:11', NULL),
(26, 'BR026', 'Kabel VGA 3 m', 'Kabel', 10, 10, 'Laci F1', NULL, '2026-11-12 13:04:11', NULL),
(27, 'BR027', 'Adaptor HDMI to VGA', 'Kabel', 8, 8, 'Laci F2', NULL, '2026-11-12 13:04:11', NULL),
(28, 'BR028', 'Stop Kontak 6 Lubang', 'Kelistrikan', 10, 10, 'Laci F3', NULL, '2026-11-12 13:04:11', NULL),
(29, 'BR029', 'UPS APC 650VA', 'Kelistrikan', 3, 3, 'Gudang', NULL, '2026-11-12 13:04:11', NULL),
(30, 'BR030', 'Flashdisk SanDisk 32GB', 'Penyimpanan', 10, 10, 'Laci G1', NULL, '2026-11-12 13:04:11', NULL),
(31, 'BR031', 'Harddisk Eksternal WD 1TB', 'Penyimpanan', 4, 4, 'Laci G1', NULL, '2026-11-12 13:04:11', NULL),
(32, 'BR032', 'Printer Epson L3210', 'Printer', 2, 2, 'Meja Petugas', NULL, '2026-11-12 13:04:11', NULL),
(33, 'BR033', 'Raspberry Pi 4 Model B', 'Mikrokontroler', 6, 6, 'Lemari H1', NULL, '2026-11-12 13:04:11', NULL),
(34, 'BR034', 'Arduino Uno R3', 'Mikrokontroler', 10, 10, 'Lemari H1', NULL, '2026-11-12 13:04:11', NULL),
(35, 'BR035', 'Tablet Samsung Galaxy Tab A8', 'Tablet', 3, 3, 'Lemari A2', NULL, '2026-11-12 13:04:11', NULL);

INSERT INTO log_aktivitas (id, user_id, aksi, created_at) VALUES
(1, 1, 'Inisialisasi sistem: 35 data barang awal dimuat', '2026-11-12 13:04:11');

