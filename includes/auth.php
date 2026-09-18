<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/functions.php';

function require_auth(array $roles = []): void
{
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
    if ($roles && !in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function require_api_auth(array $roles = []): void
{
    if (empty($_SESSION['user'])) json_response(false, 'Authentication required', null, 401);
    if ($roles && !in_array($_SESSION['user']['role'], $roles, true)) json_response(false, 'Insufficient permission', null, 403);
}

