<?php

declare(strict_types=1);

function parse_json_response(string $response): ?array
{
    $response = trim($response);
    if ($response === '') {
        return null;
    }

    $data = json_decode($response, true);
    if (is_array($data)) {
        return $data;
    }

    $start = strpos($response, '{');
    if ($start === false) {
        return null;
    }

    $data = json_decode(substr($response, $start), true);
    return is_array($data) ? $data : null;
}

/**
 * @return array{ok:bool, data?:array, error?:string, http_code:int}
 */
function api_post(string $route, array $payload = [], bool $withSession = true): array
{
    $body = array_merge([
        'app' => 'mobileworker',
        'appversion' => APP_VERSION,
        'config' => $_SESSION['config'] ?? '{}',
        'workingday' => working_day(),
        'language' => 'am',
        'debug' => false,
    ], $payload);

    if ($withSession && !empty($_SESSION['sessionkey'])) {
        $body['sessionkey'] = $_SESSION['sessionkey'];
    }

    $json = json_encode($body, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return ['ok' => false, 'error' => 'JSON encode error', 'http_code' => 0];
    }

    $url = engine_url($route);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => $curlError ?: t('api_error'), 'http_code' => $httpCode];
    }

    $data = parse_json_response($response);
    if ($data === null) {
        return ['ok' => false, 'error' => trim(strip_tags($response)) ?: t('api_error'), 'http_code' => $httpCode];
    }

    if ($httpCode === 401) {
        return ['ok' => false, 'error' => $data['error'] ?? t('unauthorized'), 'http_code' => 401, 'auth' => false];
    }

    if (($data['status'] ?? 0) != 1) {
        $error = $data['error'] ?? $data['data'] ?? $data['message'] ?? t('api_error');
        if (is_array($error)) {
            $error = json_encode($error, JSON_UNESCAPED_UNICODE);
        }
        return ['ok' => false, 'error' => (string)$error, 'http_code' => $httpCode, 'data' => $data];
    }

    if (isset($data['data']) && is_array($data['data'])) {
        return ['ok' => true, 'data' => $data['data'], 'http_code' => $httpCode];
    }

    return ['ok' => true, 'data' => $data, 'http_code' => $httpCode];
}

function api_login(string $username, string $password): array
{
    return api_post('login.php', [
        'method' => 1,
        'username' => $username,
        'password' => $password,
    ], false);
}

function api_logout(): void
{
    api_post('logout.php', []);
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
