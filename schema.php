<?php
/**
 * Skema database + data awal (seed).
 * Dipakai oleh install.php (MySQL) dan inisialisasi otomatis SQLite.
 */

function schema_statements(string $driver): array
{
    $pk  = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $int = $driver === 'sqlite' ? 'INTEGER' : 'INT';
    $eng = $driver === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    return [
        "CREATE TABLE IF NOT EXISTS users (
            id $pk,
            nama VARCHAR(100) NOT NULL,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(10) NOT NULL DEFAULT 'staff',
            status VARCHAR(10) NOT NULL DEFAULT 'aktif',
            last_login DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL
        )$eng",
        "CREATE TABLE IF NOT EXISTS barang (
            id $pk,
            kode VARCHAR(20) NOT NULL UNIQUE,
            nama VARCHAR(150) NOT NULL,
            kategori VARCHAR(50) NOT NULL,
            stok_total $int NOT NULL DEFAULT 0,
            stok_tersedia $int NOT NULL DEFAULT 0,
            lokasi VARCHAR(100) NULL,
            keterangan TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL
        )$eng",
        "CREATE TABLE IF NOT EXISTS peminjaman (
            id $pk,
            kode_transaksi VARCHAR(30) NULL UNIQUE,
            nama_peminjam VARCHAR(100) NOT NULL,
            kontak_peminjam VARCHAR(100) NULL,
            barang_id $int NULL,
            kode_barang VARCHAR(20) NOT NULL,
            nama_barang VARCHAR(150) NOT NULL,
            jumlah $int NOT NULL,
            tanggal_pinjam DATE NOT NULL,
            tanggal_rencana_kembali DATE NULL,
            tanggal_kembali DATETIME NULL,
            status VARCHAR(10) NOT NULL DEFAULT 'dipinjam',
            keperluan VARCHAR(255) NULL,
            kondisi_kembali VARCHAR(20) NULL,
            catatan_kembali VARCHAR(255) NULL,
            dicatat_oleh $int NULL,
            dikembalikan_oleh $int NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE SET NULL,
            FOREIGN KEY (dicatat_oleh) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (dikembalikan_oleh) REFERENCES users(id) ON DELETE SET NULL
        )$eng",
        "CREATE TABLE IF NOT EXISTS log_aktivitas (
            id $pk,
            user_id $int NULL,
            aksi VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )$eng",
    ];
}

function seed_barang(): array
{
    // kode, nama, kategori, stok, lokasi
    return [
        ['BR001', 'Laptop Lenovo', 'Laptop', 5, 'Lemari A1'],
        ['BR002', 'Mouse Logitech', 'Periferal', 10, 'Laci B1'],
        ['BR003', 'Keyboard Mechanical', 'Periferal', 8, 'Laci B2'],
        ['BR004', 'Proyektor Epson', 'Presentasi', 3, 'Lemari C1'],
        ['BR005', 'Monitor LG', 'Monitor', 6, 'Rak D1'],
        ['BR006', 'Laptop ASUS VivoBook 14', 'Laptop', 4, 'Lemari A1'],
        ['BR007', 'Laptop HP ProBook 440', 'Laptop', 3, 'Lemari A2'],
        ['BR008', 'Monitor Samsung 24 inch', 'Monitor', 5, 'Rak D1'],
        ['BR009', 'Monitor Dell 27 inch', 'Monitor', 2, 'Rak D2'],
        ['BR010', 'Mouse Wireless Rexus', 'Periferal', 12, 'Laci B1'],
        ['BR011', 'Keyboard Logitech K120', 'Periferal', 15, 'Laci B2'],
        ['BR012', 'Headset Logitech H390', 'Audio', 10, 'Laci B3'],
        ['BR013', 'Webcam Logitech C270', 'Periferal', 6, 'Laci B3'],
        ['BR014', 'Speaker Aktif Simbadda', 'Audio', 4, 'Rak D3'],
        ['BR015', 'Proyektor BenQ MS560', 'Presentasi', 2, 'Lemari C1'],
        ['BR016', 'Layar Proyektor 70 inch', 'Presentasi', 3, 'Gudang'],
        ['BR017', 'Laser Pointer Logitech R400', 'Presentasi', 5, 'Lemari C2'],
        ['BR018', 'Router TP-Link Archer C6', 'Jaringan', 4, 'Lemari E1'],
        ['BR019', 'Switch Cisco 24 Port', 'Jaringan', 2, 'Lemari E1'],
        ['BR020', 'Switch TP-Link 8 Port', 'Jaringan', 5, 'Lemari E1'],
        ['BR021', 'Access Point Ubiquiti UniFi', 'Jaringan', 3, 'Lemari E2'],
        ['BR022', 'Kabel LAN Cat6 10 m', 'Kabel', 20, 'Lemari E3'],
        ['BR023', 'Crimping Tool RJ45', 'Peralatan Jaringan', 6, 'Lemari E3'],
        ['BR024', 'LAN Tester', 'Peralatan Jaringan', 5, 'Lemari E3'],
        ['BR025', 'Kabel HDMI 3 m', 'Kabel', 15, 'Laci F1'],
        ['BR026', 'Kabel VGA 3 m', 'Kabel', 10, 'Laci F1'],
        ['BR027', 'Adaptor HDMI to VGA', 'Kabel', 8, 'Laci F2'],
        ['BR028', 'Stop Kontak 6 Lubang', 'Kelistrikan', 10, 'Laci F3'],
        ['BR029', 'UPS APC 650VA', 'Kelistrikan', 3, 'Gudang'],
        ['BR030', 'Flashdisk SanDisk 32GB', 'Penyimpanan', 10, 'Laci G1'],
        ['BR031', 'Harddisk Eksternal WD 1TB', 'Penyimpanan', 4, 'Laci G1'],
        ['BR032', 'Printer Epson L3210', 'Printer', 2, 'Meja Petugas'],
        ['BR033', 'Raspberry Pi 4 Model B', 'Mikrokontroler', 6, 'Lemari H1'],
        ['BR034', 'Arduino Uno R3', 'Mikrokontroler', 10, 'Lemari H1'],
        ['BR035', 'Tablet Samsung Galaxy Tab A8', 'Tablet', 3, 'Lemari A2'],
    ];
}

function seed_data(PDO $pdo): void
{
    $now = date('Y-m-d H:i:s');

    // Pengguna awal
    $u = $pdo->prepare('INSERT INTO users (nama, username, password, role, status, created_at) VALUES (?,?,?,?,?,?)');
    $u->execute(['Administrator Lab', 'admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin', 'aktif', $now]);
    $u->execute(['Rina Kartika', 'staff1', password_hash('staff123', PASSWORD_DEFAULT), 'staff', 'aktif', $now]);
    $u->execute(['Dimas Saputra', 'staff2', password_hash('staff123', PASSWORD_DEFAULT), 'staff', 'aktif', $now]);
    $u->execute(['Yoga Pratama', 'staff3', password_hash('staff123', PASSWORD_DEFAULT), 'staff', 'nonaktif', $now]);

    // Barang
    $b = $pdo->prepare('INSERT INTO barang (kode, nama, kategori, stok_total, stok_tersedia, lokasi, created_at) VALUES (?,?,?,?,?,?,?)');
    $ids = [];
    foreach (seed_barang() as [$kode, $nama, $kat, $stok, $lok]) {
        $b->execute([$kode, $nama, $kat, $stok, $stok, $lok, $now]);
        $ids[$kode] = ['id' => (int)$pdo->lastInsertId(), 'nama' => $nama];
    }

    // Tanpa contoh transaksi: stok tersedia = stok awal sesuai soal
    $pdo->prepare('INSERT INTO log_aktivitas (user_id, aksi, created_at) VALUES (?,?,?)')
        ->execute([1, 'Inisialisasi sistem: ' . count($ids) . ' data barang awal dimuat', $now]);
}

function install_database(PDO $pdo, string $driver): void
{
    foreach (schema_statements($driver) as $sql) {
        $pdo->exec($sql);
    }
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $pdo->beginTransaction();
        seed_data($pdo);
        $pdo->commit();
    }
}
