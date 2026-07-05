<?php

function t(string $key): string
{
    static $lang = null;
    if ($lang === null) {
        $lang = require __DIR__ . '/lang.php';
    }
    return $lang[$key] ?? $key;
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fmt_number($value, int $decimals = 0): string
{
    if ($value === null || $value === '') {
        return '0';
    }
    return number_format((float)$value, $decimals, '.', ' ');
}

function fmt_qty($value): string
{
    $n = (float)$value;
    $decimals = (abs($n - round($n)) < 0.0001) ? 0 : 3;
    return fmt_number($n, $decimals);
}

function today_sql(): string
{
    return date('Y-m-d');
}

function request_date(string $name, ?string $default = null): string
{
    $value = trim((string)($_REQUEST[$name] ?? ''));
    if ($value !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }
    return $default ?? today_sql();
}
