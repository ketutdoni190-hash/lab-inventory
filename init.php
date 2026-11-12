<?php
/**
 * Inisialisasi: konfigurasi, session, koneksi DB, helper, autentikasi.
 * Setiap halaman cukup memanggil: require __DIR__ . '/includes/init.php';
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/schema.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('LABINVSESSID');
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    try {
        if (DB_DRIVER === 'sqlite') {
            $isNew = !file_exists(DB_SQLITE_PATH);
            $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $opts);
            $pdo->exec('PRAGMA foreign_keys = ON');
            if ($isNew) install_database($pdo, 'sqlite');
        } else {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
            $pdo->exec("SET time_zone = '+07:00'");
        }
    } catch (PDOException $ex) {
        http_response_code(500);
        exit('<h3 style="font-family:sans-serif">Koneksi database gagal.</h3><p style="font-family:sans-serif">Periksa pengaturan di <code>config.php</code>. Detail: '
            . htmlspecialchars($ex->getMessage()) . '</p>');
    }
    return $pdo;
}

/* ================= HELPER ================= */

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }
function now(): string { return date('Y-m-d H:i:s'); }
function post(string $k, string $default = ''): string { return trim((string)($_POST[$k] ?? $default)); }
function get(string $k, string $default = ''): string { return trim((string)($_GET[$k] ?? $default)); }

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function get_flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function verify_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Sesi formulir kedaluwarsa (token CSRF tidak valid). Silakan kembali dan muat ulang halaman.');
    }
}

function valid_date(string $s): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
}

function tgl(?string $d, bool $time = false): string
{
    if (!$d) return '-';
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($d);
    return date('j', $ts) . ' ' . $bln[(int)date('n', $ts)] . ' ' . date('Y', $ts) . ($time ? ', ' . date('H:i', $ts) : '');
}

function is_terlambat(array $r): bool
{
    return $r['status'] === 'dipinjam' && !empty($r['tanggal_rencana_kembali']) && $r['tanggal_rencana_kembali'] < date('Y-m-d');
}

function status_badge(array $r): string
{
    if ($r['status'] === 'selesai') return '<span class="badge rounded-pill text-bg-success">Selesai</span>';
    if (is_terlambat($r)) return '<span class="badge rounded-pill text-bg-danger">Terlambat</span>';
    return '<span class="badge rounded-pill text-bg-warning">Dipinjam</span>';
}

function stok_badge(int $tersedia, int $total): string
{
    if ($tersedia <= 0) return '<span class="badge rounded-pill text-bg-danger">Habis</span>';
    if ($tersedia < $total && $tersedia <= max(1, (int)floor($total * 0.3))) return '<span class="badge rounded-pill text-bg-warning">Terbatas</span>';
    return '<span class="badge rounded-pill text-bg-success">Tersedia</span>';
}

function log_activity(string $aksi, ?int $userId = null): void
{
    $userId = $userId ?? (current_user()['id'] ?? null);
    db()->prepare('INSERT INTO log_aktivitas (user_id, aksi, created_at) VALUES (?,?,?)')
        ->execute([$userId, mb_substr($aksi, 0, 255), now()]);
}

/** Pagination sederhana. Mengembalikan [offset, totalHalaman, halamanAktif]. */
function paginate(int $total, int $perPage): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
    return [($page - 1) * $perPage, $pages, $page];
}

function pagination_links(int $pages, int $page): string
{
    if ($pages <= 1) return '';
    $html = '<nav><ul class="pagination pagination-sm justify-content-end mb-0">';
    for ($i = 1; $i <= $pages; $i++) {
        if ($pages > 10 && abs($i - $page) > 2 && $i !== 1 && $i !== $pages) {
            if ($i === 2 || $i === $pages - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            continue;
        }
        $q = http_build_query(array_merge($_GET, ['page' => $i]));
        $html .= '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="?' . e($q) . '">' . $i . '</a></li>';
    }
    return $html . '</ul></nav>';
}

/* ================= AUTENTIKASI ================= */

function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) return $cache;
    $cache = null;
    if (!empty($_SESSION['uid'])) {
        $st = db()->prepare('SELECT id, nama, username, role, status FROM users WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch();
        if ($u && $u['status'] === 'aktif') {
            $cache = $u;
        } else {
            // akun dihapus/dinonaktifkan saat sedang login -> paksa keluar
            unset($_SESSION['uid']);
            flash('danger', 'Akun Anda telah dinonaktifkan. Hubungi Admin.');
        }
    }
    return $cache;
}

function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        if (empty($_SESSION['flash'])) flash('warning', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        $title = 'Akses Ditolak';
        $active = '';
        require __DIR__ . '/header.php';
        echo '<div class="card border-0 shadow-sm"><div class="card-body text-center py-5">
                <i class="bi bi-shield-lock display-4 text-danger"></i>
                <h4 class="mt-3">403 — Akses Ditolak</h4>
                <p class="text-muted">Halaman ini hanya dapat diakses oleh Admin.</p>
                <a href="index.php" class="btn btn-primary">Kembali ke Dashboard</a></div></div>';
        require __DIR__ . '/footer.php';
        exit;
    }
    return $u;
}
