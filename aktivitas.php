<?php
require __DIR__ . '/includes/init.php';
require_admin();
$pdo = db();

$q = get('q');
$uid = (int)get('user');
$where = [];
$params = [];
if ($q !== '') { $where[] = 'l.aksi LIKE ?'; $params[] = "%$q%"; }
if ($uid) { $where[] = 'l.user_id = ?'; $params[] = $uid; }
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare('SELECT COUNT(*) FROM log_aktivitas l' . $sqlWhere);
$st->execute($params);
$total = (int)$st->fetchColumn();
[$offset, $pages, $page] = paginate($total, 25);

$st = $pdo->prepare("SELECT l.*, u.nama, u.username, u.role FROM log_aktivitas l LEFT JOIN users u ON u.id = l.user_id
    $sqlWhere ORDER BY l.created_at DESC, l.id DESC LIMIT 25 OFFSET $offset");
$st->execute($params);
$rows = $st->fetchAll();
$users = $pdo->query('SELECT id, nama, role FROM users ORDER BY nama')->fetchAll();

$title = 'Log Aktivitas';
$active = 'aktivitas';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <form class="row g-2" method="get">
            <div class="col-md-5"><input type="search" name="q" class="form-control" placeholder="Cari aktivitas…" value="<?= e($q) ?>"></div>
            <div class="col-md-4">
                <select name="user" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua pengguna</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= $uid === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['nama']) ?> (<?= e($u['role']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-outline-secondary">Cari</button><a href="aktivitas.php" class="btn btn-light">Reset</a></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th style="width:180px">Waktu</th><th style="width:220px">Pengguna</th><th>Aktivitas</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="small text-muted text-nowrap"><?= tgl($r['created_at'], true) ?></td>
                    <td><?= e($r['nama'] ?? 'Sistem') ?> <?php if ($r['role']): ?><span class="badge role-badge <?= $r['role'] === 'admin' ? 'role-admin' : 'role-staff' ?>"><?= e($r['role']) ?></span><?php endif; ?></td>
                    <td><?= e($r['aksi']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="3" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body d-flex justify-content-between align-items-center border-top py-2">
        <span class="small text-muted"><?= $total ?> aktivitas</span>
        <?= pagination_links($pages, $page) ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
