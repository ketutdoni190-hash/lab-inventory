<?php
require __DIR__ . '/includes/init.php';
$me = require_admin();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$user = null;
if ($id) {
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    $user = $st->fetch();
    if (!$user) { flash('danger', 'Pengguna tidak ditemukan.'); redirect('users.php'); }
}
$self = $user && (int)$user['id'] === (int)$me['id'];

$data = [
    'nama' => $user['nama'] ?? '',
    'username' => $user['username'] ?? '',
    'role' => $user['role'] ?? 'staff',
    'status' => $user['status'] ?? 'aktif',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($data as $k => $_) $data[$k] = post($k);
    $data['username'] = strtolower($data['username']);
    $pass = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');
    if ($self) { $data['role'] = 'admin'; $data['status'] = 'aktif'; } // admin tidak bisa menurunkan/menonaktifkan diri sendiri

    if ($data['nama'] === '' || mb_strlen($data['nama']) > 100) $errors['nama'] = 'Nama wajib diisi (maks. 100 karakter).';
    if (!preg_match('/^[a-z0-9_.]{3,50}$/', $data['username'])) $errors['username'] = 'Username 3–50 karakter: huruf kecil, angka, titik, atau garis bawah.';
    if (!in_array($data['role'], ['admin', 'staff'], true)) $errors['role'] = 'Role tidak valid.';
    if (!in_array($data['status'], ['aktif', 'nonaktif'], true)) $errors['status'] = 'Status tidak valid.';
    if (!$user || $pass !== '') {
        if (strlen($pass) < 6) $errors['password'] = 'Password minimal 6 karakter.';
        elseif ($pass !== $pass2) $errors['password2'] = 'Konfirmasi password tidak sama.';
    }
    if (!isset($errors['username'])) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
        $st->execute([$data['username'], $id]);
        if ($st->fetchColumn()) $errors['username'] = 'Username sudah digunakan.';
    }

    if (!$errors) {
        if ($user) {
            $sql = 'UPDATE users SET nama=?, username=?, role=?, status=?, updated_at=?' . ($pass !== '' ? ', password=?' : '') . ' WHERE id=?';
            $args = [$data['nama'], $data['username'], $data['role'], $data['status'], now()];
            if ($pass !== '') $args[] = password_hash($pass, PASSWORD_DEFAULT);
            $args[] = $id;
            $pdo->prepare($sql)->execute($args);
            log_activity("Mengubah data pengguna {$data['username']}" . ($pass !== '' ? ' (termasuk reset password)' : ''));
            flash('success', 'Data pengguna berhasil diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO users (nama, username, password, role, status, created_at) VALUES (?,?,?,?,?,?)')
                ->execute([$data['nama'], $data['username'], password_hash($pass, PASSWORD_DEFAULT), $data['role'], $data['status'], now()]);
            log_activity("Menambah akun {$data['role']} baru: {$data['username']} ({$data['nama']})");
            flash('success', "Akun {$data['username']} berhasil dibuat.");
        }
        redirect('users.php');
    }
}

$title = $user ? 'Edit Pengguna' : 'Tambah Akun Staff';
$active = 'users';
require __DIR__ . '/includes/header.php';
$inv = fn($k) => isset($errors[$k]) ? ' is-invalid' : '';
$err = fn($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-gear"></i> <?= $user ? 'Edit data pengguna' : 'Form akun baru' ?></div>
            <div class="card-body">
                <form method="post" novalidate autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap *</label>
                            <input name="nama" class="form-control<?= $inv('nama') ?>" value="<?= e($data['nama']) ?>" required>
                            <?= $err('nama') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username *</label>
                            <input name="username" class="form-control<?= $inv('username') ?>" value="<?= e($data['username']) ?>" required>
                            <?= $err('username') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select<?= $inv('role') ?>" <?= $self ? 'disabled' : '' ?>>
                                <option value="staff" <?= $data['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="admin" <?= $data['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <?= $err('role') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Akun</label>
                            <select name="status" class="form-select<?= $inv('status') ?>" <?= $self ? 'disabled' : '' ?>>
                                <option value="aktif" <?= $data['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= $data['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                            <?php if ($self): ?><div class="form-text">Role & status akun sendiri tidak dapat diubah.</div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <?= $user ? '' : '*' ?></label>
                            <input name="password" type="password" class="form-control<?= $inv('password') ?>" autocomplete="new-password">
                            <?php if ($user): ?><div class="form-text">Kosongkan jika tidak ingin mengubah password.</div><?php endif; ?>
                            <?= $err('password') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Konfirmasi Password</label>
                            <input name="password2" type="password" class="form-control<?= $inv('password2') ?>" autocomplete="new-password">
                            <?= $err('password2') ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                        <a href="users.php" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
