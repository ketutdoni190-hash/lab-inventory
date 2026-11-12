<?php
/** @var string $title  @var string $active */
$me = current_user();
$menu = [
    ['index.php', 'dashboard', 'bi-speedometer2', 'Dashboard', false],
    ['barang.php', 'barang', 'bi-box-seam', 'Data Barang', false],
    ['peminjaman.php', 'peminjaman', 'bi-box-arrow-up-right', 'Peminjaman', false],
    ['pengembalian.php', 'pengembalian', 'bi-box-arrow-in-down-left', 'Pengembalian', false],
    ['riwayat.php', 'riwayat', 'bi-clock-history', 'Riwayat Transaksi', false],
    ['users.php', 'users', 'bi-people', 'Manajemen Pengguna', true],
    ['aktivitas.php', 'aktivitas', 'bi-activity', 'Log Aktivitas', true],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') . ' · ' . APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<div class="app">
    <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar">
        <div class="brand">
            <span class="brand-icon"><i class="bi bi-pc-display-horizontal"></i></span>
            <div>
                <div class="brand-name"><?= e(APP_NAME) ?></div>
                <div class="brand-sub">Lab Komputer</div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none ms-auto" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
        </div>
        <nav class="nav flex-column">
            <?php foreach ($menu as [$href, $key, $icon, $label, $adminOnly]):
                if ($adminOnly && $me['role'] !== 'admin') continue;
                if ($key === 'users'): ?><div class="nav-section">Admin</div><?php endif; ?>
                <a class="nav-link<?= ($active ?? '') === $key ? ' active' : '' ?>" href="<?= $href ?>">
                    <i class="bi <?= $icon ?>"></i><span><?= $label ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-foot">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar"><?= e(mb_strtoupper(mb_substr($me['nama'], 0, 1))) ?></span>
                <div class="small lh-sm overflow-hidden">
                    <div class="fw-semibold text-white text-truncate"><?= e($me['nama']) ?></div>
                    <div class="text-white-50"><?= $me['role'] === 'admin' ? 'Administrator' : 'Staff Lab' ?></div>
                </div>
            </div>
            <a href="logout.php" class="btn btn-sm btn-outline-light w-100 mt-3"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </aside>

    <main class="content">
        <header class="topbar">
            <button class="btn btn-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list"></i></button>
            <h1 class="page-title"><?= e($title ?? '') ?></h1>
            <span class="ms-auto badge role-badge <?= $me['role'] === 'admin' ? 'role-admin' : 'role-staff' ?>">
                <i class="bi <?= $me['role'] === 'admin' ? 'bi-shield-check' : 'bi-person-badge' ?>"></i> <?= strtoupper(e($me['role'])) ?>
            </span>
        </header>
        <div class="page">
        <?php foreach (get_flashes() as [$type, $msg]): ?>
            <div class="alert alert-<?= e($type) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= e($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
