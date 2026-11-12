<?php
require __DIR__ . '/includes/init.php';
$me = require_login();
$pdo = db();

// Proses pengembalian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $kondisi = in_array($_POST['kondisi'] ?? '', ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Hilang'], true) ? $_POST['kondisi'] : 'Baik';
    $catatan = mb_substr(post('catatan'), 0, 255);

    $st = $pdo->prepare('SELECT * FROM peminjaman WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch();

    if (!$p) {
        flash('danger', 'Transaksi tidak ditemukan.');
    } else {
        $pdo->beginTransaction();
        // Ubah status hanya jika masih 'dipinjam' — mencegah stok bertambah dua kali
        $up = $pdo->prepare("UPDATE peminjaman SET status = 'selesai', tanggal_kembali = ?, kondisi_kembali = ?, catatan_kembali = ?, dikembalikan_oleh = ?
            WHERE id = ? AND status = 'dipinjam'");
        $up->execute([now(), $kondisi, $catatan ?: null, $me['id'], $id]);
        if ($up->rowCount() !== 1) {
            $pdo->rollBack();
            flash('warning', "Transaksi {$p['kode_transaksi']} sudah diproses sebelumnya.");
        } else {
            if ($p['barang_id']) {
                // Kembalikan stok tersedia (dibatasi agar tidak melebihi stok total)
                $pdo->prepare('UPDATE barang SET stok_tersedia = CASE WHEN stok_tersedia + ? > stok_total THEN stok_total ELSE stok_tersedia + ? END,
                    updated_at = ? WHERE id = ?')->execute([$p['jumlah'], $p['jumlah'], now(), $p['barang_id']]);
            }
            log_activity("Memproses pengembalian {$p['kode_transaksi']}: {$p['jumlah']} x {$p['nama_barang']} (kondisi: $kondisi)");
            $pdo->commit();
            flash('success', "Pengembalian {$p['kode_transaksi']} berhasil. Stok diperbarui dan status transaksi menjadi Selesai.");
        }
    }
    redirect('pengembalian.php');
}

$q = get('q');
$params = [];
$where = "p.status = 'dipinjam'";
if ($q !== '') {
    $where .= ' AND (p.kode_transaksi LIKE ? OR p.nama_peminjam LIKE ? OR p.nama_barang LIKE ? OR p.kode_barang LIKE ?)';
    $params = ["%$q%", "%$q%", "%$q%", "%$q%"];
}
$st = $pdo->prepare("SELECT p.*, u.nama AS petugas FROM peminjaman p LEFT JOIN users u ON u.id = p.dicatat_oleh
    WHERE $where ORDER BY p.tanggal_rencana_kembali IS NULL, p.tanggal_rencana_kembali ASC, p.id ASC");
$st->execute($params);
$rows = $st->fetchAll();

$title = 'Pengembalian Barang';
$active = 'pengembalian';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <form class="row g-2 align-items-center" method="get">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="Cari kode transaksi, peminjam, atau barang…" value="<?= e($q) ?>">
                </div>
            </div>
            <div class="col-md-6 d-flex gap-2 justify-content-md-end align-items-center">
                <button class="btn btn-outline-secondary">Cari</button>
                <?php if ($q): ?><a href="pengembalian.php" class="btn btn-link text-decoration-none">Reset</a><?php endif; ?>
                <span class="badge text-bg-light border"><?= count($rows) ?> barang sedang dipinjam</span>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Kode</th><th>Peminjam</th><th>Barang</th><th class="text-center">Jml</th><th>Tgl Pinjam</th><th>Rencana Kembali</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr class="<?= is_terlambat($r) ? 'table-danger' : '' ?>">
                    <td><span class="code"><?= e($r['kode_transaksi']) ?></span></td>
                    <td><?= e($r['nama_peminjam']) ?><?php if ($r['kontak_peminjam']): ?><div class="small text-muted"><?= e($r['kontak_peminjam']) ?></div><?php endif; ?></td>
                    <td><span class="code"><?= e($r['kode_barang']) ?></span> <?= e($r['nama_barang']) ?>
                        <?php if ($r['keperluan']): ?><div class="small text-muted"><?= e($r['keperluan']) ?></div><?php endif; ?></td>
                    <td class="text-center"><?= (int)$r['jumlah'] ?></td>
                    <td class="text-nowrap"><?= tgl($r['tanggal_pinjam']) ?></td>
                    <td class="text-nowrap"><?= tgl($r['tanggal_rencana_kembali']) ?></td>
                    <td><?= status_badge($r) ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#modalKembali"
                                data-id="<?= (int)$r['id'] ?>" data-info="<?= e($r['kode_transaksi'] . ' · ' . $r['jumlah'] . ' x ' . $r['nama_barang'] . ' · ' . $r['nama_peminjam']) ?>">
                            <i class="bi bi-arrow-return-left"></i> Kembalikan
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-check2-all fs-3 d-block"></i>Tidak ada barang yang sedang dipinjam.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalKembali" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="kembali-id">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-return-left"></i> Proses Pengembalian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small" id="kembali-info"></div>
                <div class="mb-3">
                    <label class="form-label">Kondisi Barang</label>
                    <select name="kondisi" class="form-select">
                        <option>Baik</option><option>Rusak Ringan</option><option>Rusak Berat</option><option>Hilang</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Catatan</label>
                    <input name="catatan" class="form-control" maxlength="255" placeholder="opsional">
                </div>
                <div class="form-text mt-2">Stok tersedia akan bertambah dan status transaksi berubah menjadi <strong>Selesai</strong>.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Konfirmasi Pengembalian</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('modalKembali').addEventListener('show.bs.modal', ev => {
    const btn = ev.relatedTarget;
    document.getElementById('kembali-id').value = btn.dataset.id;
    document.getElementById('kembali-info').textContent = btn.dataset.info;
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
