<?php
require __DIR__ . '/includes/init.php';
$me = require_login();
$admin = $me['role'] === 'admin';
$pdo = db();
$today = date('Y-m-d');

// Statistik utama
$s = $pdo->query('SELECT COUNT(*) AS jenis, COALESCE(SUM(stok_total),0) AS total, COALESCE(SUM(stok_tersedia),0) AS tersedia FROM barang')->fetch();
$totalUnit = (int)$s['total'];
$tersedia = (int)$s['tersedia'];
$dipinjam = $totalUnit - $tersedia;
$t = $pdo->query("SELECT COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN status='dipinjam' THEN 1 ELSE 0 END),0) AS aktif,
        COALESCE(SUM(CASE WHEN status='selesai' THEN 1 ELSE 0 END),0) AS selesai
    FROM peminjaman")->fetch();
$st = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam' AND tanggal_rencana_kembali < ?");
$st->execute([$today]);
$terlambat = (int)$st->fetchColumn();
$st = $pdo->prepare('SELECT COUNT(*) FROM peminjaman WHERE tanggal_pinjam = ?');
$st->execute([$today]);
$hariIni = (int)$st->fetchColumn();

// Transaksi terbaru
// (Staff: hanya transaksi relevan — yang ia tangani atau yang masih dipinjam)
$st = $pdo->prepare('SELECT p.*, u.nama AS petugas FROM peminjaman p LEFT JOIN users u ON u.id = p.dicatat_oleh'
    . ($admin ? '' : " WHERE p.dicatat_oleh = ? OR p.dikembalikan_oleh = ? OR p.status = 'dipinjam'")
    . ' ORDER BY p.created_at DESC, p.id DESC LIMIT 6');
$st->execute($admin ? [] : [$me['id'], $me['id']]);
$recent = $st->fetchAll();

// Ringkasan aktivitas terbaru (Admin: semua pengguna, Staff: aktivitas sendiri)
if ($admin) {
    $acts = $pdo->query('SELECT l.*, u.nama, u.role FROM log_aktivitas l LEFT JOIN users u ON u.id = l.user_id
        ORDER BY l.created_at DESC, l.id DESC LIMIT 8')->fetchAll();
} else {
    $st = $pdo->prepare('SELECT l.*, u.nama, u.role FROM log_aktivitas l LEFT JOIN users u ON u.id = l.user_id
        WHERE l.user_id = ? ORDER BY l.created_at DESC, l.id DESC LIMIT 8');
    $st->execute([$me['id']]);
    $acts = $st->fetchAll();
}

// Stok menipis
$low = $pdo->query('SELECT kode, nama, stok_tersedia, stok_total FROM barang
    WHERE stok_tersedia <= 1 OR stok_tersedia * 10 <= stok_total * 3 ORDER BY stok_tersedia ASC, nama LIMIT 6')->fetchAll();

if ($admin) {
    $staff = $pdo->query("SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN status='aktif' THEN 1 ELSE 0 END),0) AS aktif FROM users WHERE role='staff'")->fetch();
}

$title = 'Dashboard';
$active = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h2 class="h5 mb-1">Halo, <?= e($me['nama']) ?> 👋</h2>
        <div class="text-muted small"><?= $admin ? 'Ringkasan seluruh aktivitas laboratorium' : 'Ringkasan operasional laboratorium hari ini' ?> · <?= tgl($today) ?></div>
    </div>
    <div class="d-flex gap-2">
        <a href="peminjaman.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Peminjaman</a>
        <a href="pengembalian.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-return-left"></i> Pengembalian</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total Barang', number_format($totalUnit), (int)$s['jenis'] . ' jenis barang', 'bi-box-seam', 'bg-soft-primary'],
        ['Barang Tersedia', number_format($tersedia), $totalUnit ? round($tersedia / $totalUnit * 100) . '% dari total unit' : '-', 'bi-check2-circle', 'bg-soft-success'],
        ['Barang Dipinjam', number_format($dipinjam), (int)$t['aktif'] . ' transaksi aktif' . ($terlambat ? " · $terlambat terlambat" : ''), 'bi-box-arrow-up-right', 'bg-soft-warning'],
        ['Total Transaksi', number_format((int)$t['total']), (int)$t['selesai'] . ' selesai · ' . $hariIni . ' hari ini', 'bi-receipt', 'bg-soft-info'],
    ];
    foreach ($cards as [$label, $val, $sub, $icon, $bg]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex gap-3 align-items-center">
                    <div class="stat-icon <?= $bg ?>"><i class="bi <?= $icon ?>"></i></div>
                    <div>
                        <div class="stat-label"><?= $label ?></div>
                        <div class="stat-value"><?= $val ?></div>
                        <div class="small text-muted"><?= e($sub) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($terlambat): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>Ada <strong><?= $terlambat ?></strong> transaksi yang melewati tanggal rencana pengembalian.
            <a href="riwayat.php?status=terlambat" class="alert-link">Lihat daftar</a></div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt"></i> Transaksi Terbaru</span>
                <a href="riwayat.php" class="small">Lihat semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Kode</th><th>Peminjam</th><th>Barang</th><th>Jml</th><th>Tgl Pinjam</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><span class="code"><?= e($r['kode_transaksi']) ?></span></td>
                            <td><?= e($r['nama_peminjam']) ?></td>
                            <td><?= e($r['nama_barang']) ?></td>
                            <td><?= (int)$r['jumlah'] ?></td>
                            <td class="text-nowrap"><?= tgl($r['tanggal_pinjam']) ?></td>
                            <td><?= status_badge($r) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recent): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-activity"></i> <?= $admin ? 'Aktivitas Terbaru' : 'Aktivitas Saya' ?></span>
                <?php if ($admin): ?><a href="aktivitas.php" class="small">Semua log</a><?php endif; ?>
            </div>
            <div class="card-body py-2">
                <?php foreach ($acts as $a): ?>
                    <div class="activity-item">
                        <span class="activity-dot"></span>
                        <div class="small">
                            <div><?php if ($admin): ?><strong><?= e($a['nama'] ?? 'Sistem') ?></strong> · <?php endif; ?><?= e($a['aksi']) ?></div>
                            <div class="text-muted"><?= tgl($a['created_at'], true) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$acts): ?><p class="text-muted small my-3">Belum ada aktivitas.</p><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-exclamation-diamond"></i> Stok Menipis / Habis</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($low as $b): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><span class="code"><?= e($b['kode']) ?></span> <?= e($b['nama']) ?></span>
                        <span class="small"><?= (int)$b['stok_tersedia'] ?>/<?= (int)$b['stok_total'] ?> <?= stok_badge((int)$b['stok_tersedia'], (int)$b['stok_total']) ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$low): ?><li class="list-group-item text-muted small">Semua stok aman.</li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-info-circle"></i> Ringkasan</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>Transaksi aktif (sedang dipinjam)</span><strong><?= (int)$t['aktif'] ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Transaksi selesai</span><strong><?= (int)$t['selesai'] ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Transaksi terlambat</span><strong class="<?= $terlambat ? 'text-danger' : '' ?>"><?= $terlambat ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Peminjaman hari ini</span><strong><?= $hariIni ?></strong></li>
                <?php if ($admin): ?>
                    <li class="list-group-item d-flex justify-content-between"><span>Akun Staff (aktif / total)</span><strong><?= (int)$staff['aktif'] ?> / <?= (int)$staff['total'] ?></strong></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
