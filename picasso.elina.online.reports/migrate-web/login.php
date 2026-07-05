<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/api.php';
require_once __DIR__ . '/inc/auth.php';

$errorMessage = '';

if (is_logged_in()) {
    header('Location: /online-report.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errorMessage = t('login_failed');
    } else {
        $result = api_login($username, $password);
        if ($result['ok']) {
            $data = $result['data'];
            $_SESSION['sessionkey'] = $data['sessionkey'] ?? '';
            $_SESSION['userid'] = $data['user']['f_id'] ?? 0;
            $_SESSION['username'] = $data['user']['f_login'] ?? $username;
            $config = $data['config']['f_config'] ?? [];
            if (is_array($config)) {
                $_SESSION['config'] = json_encode($config, JSON_UNESCAPED_UNICODE);
            } else {
                $_SESSION['config'] = (string)$config;
            }
            header('Location: /online-report.php');
            exit;
        }
        $errorMessage = $result['error'] ?? t('login_failed');
    }
}

$pageTitle = t('login');
$activeMenu = '';
ob_start();
?>
<section class="card card-narrow">
    <h1><?= h(t('login')) ?></h1>
    <form method="post" class="form">
        <label class="form__label">
            <span><?= h(t('username')) ?></span>
            <input type="text" name="username" autocomplete="username" required
                   value="<?= h($_POST['username'] ?? '') ?>">
        </label>
        <label class="form__label">
            <span><?= h(t('password')) ?></span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button type="submit" class="btn btn-primary"><?= h(t('sign_in')) ?></button>
    </form>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
