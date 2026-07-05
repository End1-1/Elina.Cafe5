<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/helpers.php';

const APP_VERSION = '1.0.0-web';
const ENGINE_PREFIX = '/engine/';

function engine_url(string $route): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $route = ltrim($route, '/');
    if (strncmp($route, 'engine/', 7) === 0) {
        return $scheme . '://' . $host . '/' . $route;
    }
    return $scheme . '://' . $host . ENGINE_PREFIX . $route;
}

function working_day(): string
{
    return today_sql();
}
