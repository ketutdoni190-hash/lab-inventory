<?php
require __DIR__ . '/includes/init.php';

if (current_user()) redirect('index.php');

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = post('username');
    $password = (string)($_POST['password'] ?? '');

    // Pembatasan percobaan login sederhana (per sesi)
    $_SESSION['login_fail'] = $_SESSION['login_fail'] ?? ['n' => 0, 't' => 0];
    if ($_SESSION['login_fail']['n'] >= 5 && time() - $_SESSION['login_fail']['t'] < 60) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam 1 menit.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
        if (!$u || !password_verify($password, $u['password'])) {
            $error = 'Username atau password salah.';
            $_SESSION['login_fail'] = ['n' => $_SESSION['login_fail']['n'] + 1, 't' => time()];
        } elseif ($u['status'] !== 'aktif') {
            $error = 'Akun Anda nonaktif. Silakan hubungi Admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            unset($_SESSION['login_fail']);
            db()->prepare('UPDATE users SET last_login = ? WHERE id = ?')->execute([now(), $u['id']]);
            log_activity('Login ke sistem', (int)$u['id']);
            flash('success', 'Selamat datang, ' . $u['nama'] . '!');
            redirect('index.php');
        }
    }
}
$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<div class="login-wrap">
    <div class="card login-card shadow-lg">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
                <h1 class="h4 fw-bold mt-3 mb-1"><?= e(APP_NAME) ?></h1>
                <p class="text-muted small mb-0"><?= e(APP_FULLNAME) ?></p>
            </div>

            <?php foreach ($flashes as [$type, $msg]): ?>
                <div class="alert alert-<?= e($type) ?> py-2 small"><?= e($msg) ?></div>
            <?php endforeach; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" value="<?= e($username) ?>" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <button class="btn btn-primary w-100 py-2 fw-semibold"><i class="bi bi-box-arrow-in-right"></i> Masuk</button>
            </form>

            <?php if (SHOW_DEMO_ACCOUNTS): ?>
            <div class="mt-4 p-3 bg-light rounded small">
                <div class="fw-semibold mb-1">Akun demo:</div>
                <div>Admin: <span class="code">admin</span> / <span class="code">admin123</span></div>
                <div>Staff: <span class="code">staff1</span> / <span class="code">staff123</span></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
