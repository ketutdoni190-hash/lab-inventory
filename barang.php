<?php
require __DIR__ . '/includes/init.php';
$me = require_login();
$admin = $me['role'] === 'admin';
$pdo = db();

$q = get('q');
$kat = get('kategori');
$ketersediaan = get('ketersediaan'); // tersedia | habis | dipinjam

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(kode LIKE ? OR nama LIKE ? OR kategori LIKE ? OR lokasi LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
if ($kat !== '') { $where[] = 'kategori = ?'; $params[] = $kat; }
if ($ketersediaan === 'tersedia') $where[] = 'stok_tersedia > 0';
if ($ketersediaan === 'habis') $where[] = 'stok_tersedia <= 0';
if ($ketersediaan === 'dipinjam') $where[] = 'stok_tersedia < stok_total';
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare('SELECT COUNT(*) FROM barang' . $sqlWhere);
$st->execute($params);
$total = (int)$st->fetchColumn();
[$offset, $pages, $page] = paginate($total, 15);

$st = $pdo->prepare("SELECT * FROM barang $sqlWhere ORDER BY kode ASC LIMIT 15 OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll();

$kategori = $pdo->query('SELECT DISTINCT kategori FROM barang ORDER BY kategori')->fetchAll(PDO::FETCH_COLUMN);

$title = 'Data Barang';
$active = 'barang';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <form class="row g-2 align-items-center" method="get">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="Cari kode, nama, kategori, lokasi…" value="<?= e($q) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="kategori" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua kategori</option>
                    <?php foreach ($kategori as $k): ?>
                        <option <?= $k === $kat ? 'selected' : '' ?>><?= e($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="ketersediaan" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="tersedia" <?= $ketersediaan === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="dipinjam" <?= $ketersediaan === 'dipinjam' ? 'selected' : '' ?>>Sedang dipinjam</option>
                    <option value="habis" <?= $ketersediaan === 'habis' ? 'selected' : '' ?>>Habis</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2 justify-content-md-end">
                <button class="btn btn-outline-secondary">Cari</button>
                <?php if ($q || $kat || $ketersediaan): ?><a href="barang.php" class="btn btn-link text-decoration-none">Reset</a><?php endif; ?>
                <?php if ($admin): ?>
                    <a href="barang_form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Lokasi</th>
                <th class="text-center">Stok</th><th class="text-center">Tersedia</th><th class="text-center">Dipinjam</th>
                <th>Status</th>
                <?php if ($admin): ?><th class="text-end">Aksi</th><?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $b): $dip = (int)$b['stok_total'] - (int)$b['stok_tersedia']; ?>
                <tr>
                    <td><span class="code"><?= e($b['kode']) ?></span></td>
                    <td class="fw-medium"><?= e($b['nama']) ?>
                        <?php if ($b['keterangan']): ?><div class="small text-muted"><?= e($b['keterangan']) ?></div><?php endif; ?></td>
                    <td><?= e($b['kategori']) ?></td>
                    <td class="text-muted small"><?= e($b['lokasi'] ?: '-') ?></td>
                    <td class="text-center"><?= (int)$b['stok_total'] ?></td>
                    <td class="text-center fw-semibold"><?= (int)$b['stok_tersedia'] ?></td>
                    <td class="text-center"><?= $dip ?></td>
                    <td><?= stok_badge((int)$b['stok_tersedia'], (int)$b['stok_total']) ?></td>
                    <?php if ($admin): ?>
                        <td class="text-end text-nowrap">
                            <a href="barang_form.php?id=<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="barang_hapus.php" class="d-inline"
                                  data-confirm="Hapus barang <?= e($b['kode'] . ' - ' . $b['nama']) ?>? Riwayat transaksi tetap tersimpan.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Hapus" <?= $dip > 0 ? 'disabled' : '' ?>><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block"></i>Barang tidak ditemukan.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body d-flex justify-content-between align-items-center border-top py-2">
        <span class="small text-muted">Menampilkan <?= count($rows) ?> dari <?= $total ?> barang<?= $admin ? ' · Barang yang sedang dipinjam tidak dapat dihapus' : '' ?></span>
        <?= pagination_links($pages, $page) ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
