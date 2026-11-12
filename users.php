<?php
require __DIR__ . '/includes/init.php';
$me = require_admin();
$pdo = db();

// Aktifkan / nonaktifkan akun
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    $u = $st->fetch();
    if (!$u) {
        flash('danger', 'Pengguna tidak ditemukan.');
    } elseif ((int)$u['id'] === (int)$me['id']) {
        flash('warning', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
    } else {
        $new = $u['status'] === 'aktif' ? 'nonaktif' : 'aktif';
        $pdo->prepare('UPDATE users SET status = ?, updated_at = ? WHERE id = ?')->execute([$new, now(), $id]);
        log_activity(($new === 'aktif' ? 'Mengaktifkan' : 'Menonaktifkan') . " akun {$u['username']} ({$u['nama']})");
        flash('success', "Akun {$u['username']} sekarang " . ($new === 'aktif' ? 'aktif' : 'nonaktif') . '.');
    }
    redirect('users.php' . (isset($_GET['role']) ? '?role=' . urlencode($_GET['role']) : ''));
}

$q = get('q');
$role = get('role');
$where = [];
$params = [];
if ($q !== '') { $where[] = '(u.nama LIKE ? OR u.username LIKE ?)'; array_push($params, "%$q%", "%$q%"); }
if ($role === 'admin' || $role === 'staff') { $where[] = 'u.role = ?'; $params[] = $role; }
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare("SELECT u.*,
        (SELECT COUNT(*) FROM peminjaman p WHERE p.dicatat_oleh = u.id) AS jml_pinjam,
        (SELECT COUNT(*) FROM peminjaman p WHERE p.dikembalikan_oleh = u.id) AS jml_kembali
    FROM users u $sqlWhere ORDER BY u.role ASC, u.nama ASC");
$st->execute($params);
$rows = $st->fetchAll();

$title = 'Manajemen Pengguna';
$active = 'users';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <form class="row g-2 align-items-center" method="get">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="Cari nama atau username…" value="<?= e($q) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua role</option>
                    <option value="staff" <?= $role === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2 justify-content-md-end">
                <button class="btn btn-outline-secondary">Cari</button>
                <a href="user_form.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Tambah Staff</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th class="text-center">Catat Pinjam</th><th class="text-center">Terima Kembali</th><th>Login Terakhir</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $u): $self = (int)$u['id'] === (int)$me['id']; ?>
                <tr>
                    <td class="fw-medium"><?= e($u['nama']) ?><?= $self ? ' <span class="badge text-bg-light border">Anda</span>' : '' ?></td>
                    <td><span class="code"><?= e($u['username']) ?></span></td>
                    <td><span class="badge role-badge <?= $u['role'] === 'admin' ? 'role-admin' : 'role-staff' ?>"><?= strtoupper(e($u['role'])) ?></span></td>
                    <td><?= $u['status'] === 'aktif' ? '<span class="badge rounded-pill text-bg-success">Aktif</span>' : '<span class="badge rounded-pill text-bg-secondary">Nonaktif</span>' ?></td>
                    <td class="text-center"><?= (int)$u['jml_pinjam'] ?></td>
                    <td class="text-center"><?= (int)$u['jml_kembali'] ?></td>
                    <td class="small text-muted text-nowrap"><?= tgl($u['last_login'], true) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="user_form.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        <?php if (!$self): ?>
                            <form method="post" class="d-inline" data-confirm="<?= $u['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?> akun <?= e($u['username']) ?>?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <?php if ($u['status'] === 'aktif'): ?>
                                    <button class="btn btn-sm btn-outline-danger" title="Nonaktifkan"><i class="bi bi-person-x"></i> Nonaktifkan</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-success" title="Aktifkan"><i class="bi bi-person-check"></i> Aktifkan</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Pengguna tidak ditemukan.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
