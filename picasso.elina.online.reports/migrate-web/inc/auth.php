<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/api.php';

function is_logged_in(): bool
{
    return !empty($_SESSION['sessionkey']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function handle_api_auth_failure(array $result): void
{
    if (!empty($result['auth']) || ($result['http_code'] ?? 0) === 401) {
        $_SESSION = [];
        header('Location: /login.php');
        exit;
    }
}
