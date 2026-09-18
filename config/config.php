<?php
declare(strict_types=1);

define('APP_NAME', 'ArenaFlow');
define('BASE_URL', '/tournament-system');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/participants/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);
date_default_timezone_set('Asia/Jakarta');

if (ob_get_level() === 0) ob_start();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}
