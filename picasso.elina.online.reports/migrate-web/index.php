<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/api.php';
require_once __DIR__ . '/inc/auth.php';

if (is_logged_in()) {
    header('Location: /online-report.php');
    exit;
}

header('Location: /login.php');
exit;
