<?php
require __DIR__ . '/includes/init.php';

if (current_user()) log_activity('Logout dari sistem');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_start();
flash('success', 'Anda telah logout.');
redirect('login.php');
