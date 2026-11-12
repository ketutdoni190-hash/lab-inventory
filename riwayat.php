<?php
require __DIR__ . '/includes/init.php';
$me = require_login();
$admin = $me['role'] === 'admin';
$pdo = db();
$today = date('Y-m-d');

$q = get('q');
$status = get('status');      // dipinjam | selesai | terlambat
$dari = get('dari');
$sampai = get('sampai');

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.kode_transaksi LIKE ? OR p.nama_peminjam LIKE ? OR p.nama_barang LIKE ? OR p.kode_barang LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
if ($status === 'dipinjam' || $status === 'selesai') { $where[] = 'p.status = ?'; $params[] = $status; }
if ($status === 'terlambat') { $where[] = "p.status = 'dipinjam' AND p.tanggal_rencana_kembali < ?"; $params[] = $today; }
if ($dari !== '' && valid_date($dari)) { $where[] = 'p.tanggal_pinjam >= ?'; $params[] = $dari; }
if ($sampai !== '' && valid_date($sampai)) { $where[] = 'p.tanggal_pinjam <= ?'; $params[] = $sampai; }
// Staff hanya melihat transaksi yang relevan: yang ia catat/terima, ditambah semua yang masih dipinjam
if (!$admin) { $where[] = "(p.dicatat_oleh = ? OR p.dikembalikan_oleh = ? OR p.status = 'dipinjam')"; array_push($params, $me['id'], $me['id']); }
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare('SELECT COUNT(*) FROM peminjaman p' . $sqlWhere);
$st->execute($params);
$total = (int)$st->fetchColumn();
[$offset, $pages, $page] = paginate($total, 20);

$st = $pdo->prepare("SELECT p.*, u1.nama AS pencatat, u2.nama AS penerima FROM peminjaman p
    LEFT JOIN users u1 ON u1.id = p.dicatat_oleh LEFT JOIN users u2 ON u2.id = p.dikembalikan_oleh
    $sqlWhere ORDER BY p.created_at DESC, p.id DESC LIMIT 20 OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll();

$title = 'Riwayat Transaksi';
$active = 'riwayat';
require __DIR__ . '/includes/header.php';
?>
<?php if (!$admin): ?>
<div class="alert alert-info small py-2"><i class="bi bi-info-circle"></i> Menampilkan transaksi yang relevan bagi Anda: transaksi yang Anda catat/terima pengembaliannya, serta semua barang yang masih dipinjam.</div>
<?php endif; ?>
<div class="card">
    <div class="card-header">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-lg-4">
                <label class="form-label small mb-1">Pencarian</label>
                <input type="search" name="q" class="form-control" placeholder="Kode transaksi, peminjam, barang…" value="<?= e($q) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <?php foreach (['dipinjam' => 'Dipinjam', 'terlambat' => 'Terlambat', 'selesai' => 'Selesai'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= $status === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small mb-1">Pinjam dari</label>
                <input type="date" name="dari" class="form-control" value="<?= e($dari) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small mb-1">Sampai</label>
                <input type="date" name="sampai" class="form-control" value="<?= e($sampai) ?>">
            </div>
            <div class="col-6 col-lg-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                <a href="riwayat.php" class="btn btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead>
            <tr><th>Kode</th><th>Peminjam</th><th>Barang</th><th class="text-center">Jml</th><th>Tgl Pinjam</th><th>Rencana</th><th>Tgl Kembali</th><th>Kondisi</th><th>Petugas</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><span class="code"><?= e($r['kode_transaksi']) ?></span></td>
                    <td><?= e($r['nama_peminjam']) ?><?php if ($r['kontak_peminjam']): ?><div class="text-muted"><?= e($r['kontak_peminjam']) ?></div><?php endif; ?></td>
                    <td><?= e($r['nama_barang']) ?><div class="text-muted"><?= e($r['kode_barang']) ?><?= $r['keperluan'] ? ' · ' . e($r['keperluan']) : '' ?></div></td>
                    <td class="text-center"><?= (int)$r['jumlah'] ?></td>
                    <td class="text-nowrap"><?= tgl($r['tanggal_pinjam']) ?></td>
                    <td class="text-nowrap"><?= tgl($r['tanggal_rencana_kembali']) ?></td>
                    <td class="text-nowrap"><?= tgl($r['tanggal_kembali'], true) ?></td>
                    <td><?= e($r['kondisi_kembali'] ?? '-') ?><?php if ($r['catatan_kembali']): ?><div class="text-muted"><?= e($r['catatan_kembali']) ?></div><?php endif; ?></td>
                    <td class="text-nowrap">
                        <div title="Dicatat oleh"><i class="bi bi-box-arrow-up-right text-muted"></i> <?= e($r['pencatat'] ?? '-') ?></div>
                        <?php if ($r['penerima']): ?><div title="Pengembalian diterima oleh"><i class="bi bi-box-arrow-in-down-left text-muted"></i> <?= e($r['penerima']) ?></div><?php endif; ?>
                    </td>
                    <td><?= status_badge($r) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="10" class="text-center text-muted py-5">Tidak ada transaksi yang cocok.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body d-flex justify-content-between align-items-center border-top py-2">
        <span class="small text-muted"><?= $total ?> transaksi ditemukan</span>
        <?= pagination_links($pages, $page) ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
