<?php
require __DIR__ . '/includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('barang.php');
verify_csrf();

$pdo = db();
$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM barang WHERE id = ?');
$st->execute([$id]);
$b = $st->fetch();

if (!$b) {
    flash('danger', 'Barang tidak ditemukan.');
} else {
    $st = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE barang_id = ? AND status = 'dipinjam'");
    $st->execute([$id]);
    if ($st->fetchColumn() > 0) {
        flash('danger', "Barang {$b['kode']} masih dipinjam. Proses pengembalian terlebih dahulu sebelum menghapus.");
    } else {
        // Riwayat transaksi tetap tersimpan (kode & nama barang disimpan di tabel peminjaman)
        $pdo->prepare('UPDATE peminjaman SET barang_id = NULL WHERE barang_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM barang WHERE id = ?')->execute([$id]);
        log_activity("Menghapus barang {$b['kode']} - {$b['nama']}");
        flash('success', "Barang {$b['kode']} - {$b['nama']} berhasil dihapus.");
    }
}
redirect('barang.php');
