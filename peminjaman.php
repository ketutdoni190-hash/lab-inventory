<?php
require __DIR__ . '/includes/init.php';
$me = require_login();
$pdo = db();

$data = [
    'nama_peminjam' => '', 'kontak_peminjam' => '', 'barang_id' => (string)($_GET['barang'] ?? ''), 'jumlah' => '1',
    'tanggal_pinjam' => date('Y-m-d'), 'tanggal_rencana_kembali' => date('Y-m-d', strtotime('+3 days')), 'keperluan' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($data as $k => $_) $data[$k] = post($k);

    if ($data['nama_peminjam'] === '' || mb_strlen($data['nama_peminjam']) > 100) $errors['nama_peminjam'] = 'Nama peminjam wajib diisi (maks. 100 karakter).';
    if (mb_strlen($data['kontak_peminjam']) > 100) $errors['kontak_peminjam'] = 'Maksimal 100 karakter.';
    if (!ctype_digit($data['jumlah']) || (int)$data['jumlah'] < 1) $errors['jumlah'] = 'Jumlah minimal 1.';
    if (!valid_date($data['tanggal_pinjam'])) $errors['tanggal_pinjam'] = 'Tanggal peminjaman tidak valid.';
    if ($data['tanggal_rencana_kembali'] !== '') {
        if (!valid_date($data['tanggal_rencana_kembali'])) $errors['tanggal_rencana_kembali'] = 'Tanggal tidak valid.';
        elseif (!isset($errors['tanggal_pinjam']) && $data['tanggal_rencana_kembali'] < $data['tanggal_pinjam'])
            $errors['tanggal_rencana_kembali'] = 'Tidak boleh sebelum tanggal peminjaman.';
    }

    $st = $pdo->prepare('SELECT * FROM barang WHERE id = ?');
    $st->execute([(int)$data['barang_id']]);
    $barang = $st->fetch();
    if (!$barang) $errors['barang_id'] = 'Pilih barang yang akan dipinjam.';
    elseif (!isset($errors['jumlah']) && (int)$data['jumlah'] > (int)$barang['stok_tersedia'])
        $errors['jumlah'] = "Stok tidak mencukupi. Tersedia: {$barang['stok_tersedia']} unit.";

    if (!$errors) {
        $jml = (int)$data['jumlah'];
        try {
            $pdo->beginTransaction();
            // Kurangi stok secara atomik — gagal jika stok sudah diambil transaksi lain
            $up = $pdo->prepare('UPDATE barang SET stok_tersedia = stok_tersedia - ?, updated_at = ? WHERE id = ? AND stok_tersedia >= ?');
            $up->execute([$jml, now(), $barang['id'], $jml]);
            if ($up->rowCount() !== 1) throw new RuntimeException('Stok tidak mencukupi (baru saja berubah). Silakan coba lagi.');

            $pdo->prepare('INSERT INTO peminjaman (nama_peminjam, kontak_peminjam, barang_id, kode_barang, nama_barang, jumlah,
                    tanggal_pinjam, tanggal_rencana_kembali, status, keperluan, dicatat_oleh, created_at)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$data['nama_peminjam'], $data['kontak_peminjam'] ?: null, $barang['id'], $barang['kode'], $barang['nama'], $jml,
                    $data['tanggal_pinjam'], $data['tanggal_rencana_kembali'] ?: null, 'dipinjam', $data['keperluan'] ?: null, $me['id'], now()]);
            $newId = (int)$pdo->lastInsertId();
            $kode = 'PJ' . date('Ymd', strtotime($data['tanggal_pinjam'])) . sprintf('%04d', $newId);
            $pdo->prepare('UPDATE peminjaman SET kode_transaksi = ? WHERE id = ?')->execute([$kode, $newId]);
            log_activity("Mencatat peminjaman $kode: $jml x {$barang['nama']} oleh {$data['nama_peminjam']}");
            $pdo->commit();
            flash('success', "Transaksi $kode berhasil disimpan. Status: Dipinjam.");
            redirect('peminjaman.php');
        } catch (RuntimeException $ex) {
            $pdo->rollBack();
            $errors['jumlah'] = $ex->getMessage();
        }
    }
}

$barangList = $pdo->query('SELECT id, kode, nama, kategori, stok_tersedia, stok_total FROM barang ORDER BY kategori, nama')->fetchAll();
$aktif = $pdo->query("SELECT p.*, u.nama AS petugas FROM peminjaman p LEFT JOIN users u ON u.id = p.dicatat_oleh
    WHERE p.status = 'dipinjam' ORDER BY p.created_at DESC, p.id DESC LIMIT 10")->fetchAll();

$title = 'Peminjaman Barang';
$active = 'peminjaman';
require __DIR__ . '/includes/header.php';

$inv = fn($k) => isset($errors[$k]) ? ' is-invalid' : '';
$err = fn($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
?>
<div class="row g-3">
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle"></i> Catat Peminjaman Baru</div>
            <div class="card-body">
                <?php if ($errors): ?><div class="alert alert-danger py-2 small">Periksa kembali isian formulir.</div><?php endif; ?>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nama Peminjam *</label>
                        <input name="nama_peminjam" class="form-control<?= $inv('nama_peminjam') ?>" value="<?= e($data['nama_peminjam']) ?>" placeholder="Nama lengkap" required>
                        <?= $err('nama_peminjam') ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NIM / NIP / No. HP</label>
                        <input name="kontak_peminjam" class="form-control<?= $inv('kontak_peminjam') ?>" value="<?= e($data['kontak_peminjam']) ?>" placeholder="opsional">
                        <?= $err('kontak_peminjam') ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Barang *</label>
                        <select name="barang_id" id="barang_id" class="form-select<?= $inv('barang_id') ?>" required>
                            <option value="">— Pilih barang —</option>
                            <?php $lastKat = null;
                            foreach ($barangList as $b):
                                if ($b['kategori'] !== $lastKat) { if ($lastKat !== null) echo '</optgroup>'; echo '<optgroup label="' . e($b['kategori']) . '">'; $lastKat = $b['kategori']; } ?>
                                <option value="<?= (int)$b['id'] ?>" data-stok="<?= (int)$b['stok_tersedia'] ?>"
                                    <?= (string)$b['id'] === $data['barang_id'] ? 'selected' : '' ?> <?= $b['stok_tersedia'] <= 0 ? 'disabled' : '' ?>>
                                    <?= e($b['kode'] . ' — ' . $b['nama']) ?> (tersedia <?= (int)$b['stok_tersedia'] ?>)
                                </option>
                            <?php endforeach; if ($lastKat !== null) echo '</optgroup>'; ?>
                        </select>
                        <?= $err('barang_id') ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah *</label>
                        <input name="jumlah" id="jumlah" type="number" min="1" class="form-control<?= $inv('jumlah') ?>" value="<?= e($data['jumlah']) ?>" required>
                        <div class="form-text" id="stok-info"></div>
                        <?= $err('jumlah') ?>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Tanggal Pinjam *</label>
                            <input name="tanggal_pinjam" type="date" class="form-control<?= $inv('tanggal_pinjam') ?>" value="<?= e($data['tanggal_pinjam']) ?>" required>
                            <?= $err('tanggal_pinjam') ?>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Rencana Kembali</label>
                            <input name="tanggal_rencana_kembali" type="date" class="form-control<?= $inv('tanggal_rencana_kembali') ?>" value="<?= e($data['tanggal_rencana_kembali']) ?>">
                            <?= $err('tanggal_rencana_kembali') ?>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Keperluan</label>
                        <input name="keperluan" class="form-control" value="<?= e($data['keperluan']) ?>" maxlength="255" placeholder="mis. Praktikum Jaringan">
                    </div>
                    <button class="btn btn-primary w-100"><i class="bi bi-save"></i> Simpan Transaksi</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-hourglass-split"></i> Sedang Dipinjam (terbaru)</span>
                <a href="pengembalian.php" class="small">Proses pengembalian</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Kode</th><th>Peminjam</th><th>Barang</th><th>Jml</th><th>Rencana Kembali</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($aktif as $r): ?>
                        <tr>
                            <td><span class="code"><?= e($r['kode_transaksi']) ?></span></td>
                            <td><?= e($r['nama_peminjam']) ?><div class="small text-muted">Dicatat: <?= e($r['petugas'] ?? '-') ?></div></td>
                            <td><?= e($r['nama_barang']) ?></td>
                            <td><?= (int)$r['jumlah'] ?></td>
                            <td class="text-nowrap"><?= tgl($r['tanggal_rencana_kembali']) ?></td>
                            <td><?= status_badge($r) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$aktif): ?><tr><td colspan="6" class="text-center text-muted py-4">Tidak ada barang yang sedang dipinjam.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const sel = document.getElementById('barang_id'), qty = document.getElementById('jumlah'), info = document.getElementById('stok-info');
    function upd() {
        const opt = sel.options[sel.selectedIndex];
        const stok = opt && opt.dataset.stok ? parseInt(opt.dataset.stok, 10) : null;
        if (stok === null) { info.textContent = ''; qty.removeAttribute('max'); return; }
        qty.max = stok;
        info.textContent = 'Stok tersedia: ' + stok + ' unit';
    }
    sel.addEventListener('change', upd); upd();
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
