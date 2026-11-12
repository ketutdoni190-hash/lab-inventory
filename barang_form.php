<?php
require __DIR__ . '/includes/init.php';
require_admin();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$item = null;
if ($id) {
    $st = $pdo->prepare('SELECT * FROM barang WHERE id = ?');
    $st->execute([$id]);
    $item = $st->fetch();
    if (!$item) { flash('danger', 'Barang tidak ditemukan.'); redirect('barang.php'); }
}

// Saran kode berikutnya (BR001, BR002, ...)
function next_kode(PDO $pdo): string
{
    $max = 0;
    foreach ($pdo->query("SELECT kode FROM barang WHERE kode LIKE 'BR%'")->fetchAll(PDO::FETCH_COLUMN) as $k) {
        if (preg_match('/^BR(\d+)$/', $k, $m)) $max = max($max, (int)$m[1]);
    }
    return sprintf('BR%03d', $max + 1);
}

$dipinjam = $item ? (int)$item['stok_total'] - (int)$item['stok_tersedia'] : 0;
$data = [
    'kode' => $item['kode'] ?? next_kode($pdo),
    'nama' => $item['nama'] ?? '',
    'kategori' => $item['kategori'] ?? '',
    'stok_total' => $item['stok_total'] ?? '',
    'lokasi' => $item['lokasi'] ?? '',
    'keterangan' => $item['keterangan'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($data as $k => $_) $data[$k] = post($k);
    $data['kode'] = strtoupper($data['kode']);

    if (!preg_match('/^[A-Z0-9\-]{2,20}$/', $data['kode'])) $errors['kode'] = 'Kode 2–20 karakter (huruf/angka/tanda -).';
    if ($data['nama'] === '' || mb_strlen($data['nama']) > 150) $errors['nama'] = 'Nama barang wajib diisi (maks. 150 karakter).';
    if ($data['kategori'] === '' || mb_strlen($data['kategori']) > 50) $errors['kategori'] = 'Kategori wajib diisi (maks. 50 karakter).';
    if (!ctype_digit($data['stok_total'])) $errors['stok_total'] = 'Stok harus berupa angka ≥ 0.';
    elseif ((int)$data['stok_total'] < $dipinjam) $errors['stok_total'] = "Stok tidak boleh kurang dari jumlah yang sedang dipinjam ($dipinjam unit).";

    if (!isset($errors['kode'])) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM barang WHERE kode = ? AND id <> ?');
        $st->execute([$data['kode'], $id]);
        if ($st->fetchColumn()) $errors['kode'] = 'Kode barang sudah digunakan.';
    }

    if (!$errors) {
        $stok = (int)$data['stok_total'];
        if ($item) {
            // stok tersedia = stok total baru - jumlah yang sedang dipinjam
            // (stok_tersedia ditulis lebih dulu agar MySQL memakai nilai stok_total lama)
            $pdo->prepare('UPDATE barang SET kode=?, nama=?, kategori=?, stok_tersedia = ? - (stok_total - stok_tersedia), stok_total=?,
                lokasi=?, keterangan=?, updated_at=? WHERE id=?')
                ->execute([$data['kode'], $data['nama'], $data['kategori'], $stok, $stok, $data['lokasi'] ?: null, $data['keterangan'] ?: null, now(), $id]);
            log_activity("Mengubah data barang {$data['kode']} - {$data['nama']}");
            flash('success', 'Data barang berhasil diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO barang (kode, nama, kategori, stok_total, stok_tersedia, lokasi, keterangan, created_at) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$data['kode'], $data['nama'], $data['kategori'], $stok, $stok, $data['lokasi'] ?: null, $data['keterangan'] ?: null, now()]);
            log_activity("Menambah barang {$data['kode']} - {$data['nama']} (stok $stok)");
            flash('success', 'Barang baru berhasil ditambahkan.');
        }
        redirect('barang.php');
    }
}

$kategori = $pdo->query('SELECT DISTINCT kategori FROM barang ORDER BY kategori')->fetchAll(PDO::FETCH_COLUMN);
$title = $item ? 'Edit Barang' : 'Tambah Barang';
$active = 'barang';
require __DIR__ . '/includes/header.php';

function field_err(array $errors, string $k): string
{
    return isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
}
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-box-seam"></i> <?= $item ? 'Edit data barang' : 'Form barang baru' ?></div>
            <div class="card-body">
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Kode Barang *</label>
                            <input name="kode" class="form-control <?= isset($errors['kode']) ? 'is-invalid' : '' ?>" value="<?= e($data['kode']) ?>" required>
                            <?= field_err($errors, 'kode') ?>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Nama Barang *</label>
                            <input name="nama" class="form-control <?= isset($errors['nama']) ? 'is-invalid' : '' ?>" value="<?= e($data['nama']) ?>" required>
                            <?= field_err($errors, 'nama') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori *</label>
                            <input name="kategori" list="kategori-list" class="form-control <?= isset($errors['kategori']) ? 'is-invalid' : '' ?>" value="<?= e($data['kategori']) ?>" placeholder="Pilih atau ketik kategori baru" required>
                            <datalist id="kategori-list"><?php foreach ($kategori as $k): ?><option value="<?= e($k) ?>"><?php endforeach; ?></datalist>
                            <?= field_err($errors, 'kategori') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stok Total *</label>
                            <input name="stok_total" type="number" min="<?= $dipinjam ?>" class="form-control <?= isset($errors['stok_total']) ? 'is-invalid' : '' ?>" value="<?= e($data['stok_total']) ?>" required>
                            <?php if ($item): ?><div class="form-text">Sedang dipinjam: <?= $dipinjam ?> unit. Stok tersedia akan disesuaikan otomatis.</div><?php endif; ?>
                            <?= field_err($errors, 'stok_total') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lokasi Penyimpanan</label>
                            <input name="lokasi" class="form-control" value="<?= e($data['lokasi']) ?>" placeholder="mis. Lemari A1" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Keterangan</label>
                            <input name="keterangan" class="form-control" value="<?= e($data['keterangan']) ?>" placeholder="opsional">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                        <a href="barang.php" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
