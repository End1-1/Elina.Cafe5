<?php
/** @var string $pageTitle */
/** @var string $activeMenu online|dayend| */
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="hy">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> — <?= h(t('app_title')) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="topbar">
    <div class="topbar__brand"><?= h(t('app_title')) ?></div>
    <?php if (is_logged_in()): ?>
    <nav class="topbar__nav">
        <a href="/online-report.php" class="<?= $activeMenu === 'online' ? 'is-active' : '' ?>"><?= h(t('online_report')) ?></a>
        <a href="/day-end.php" class="<?= $activeMenu === 'dayend' ? 'is-active' : '' ?>"><?= h(t('day_end')) ?></a>
        <a href="/logout.php"><?= h(t('logout')) ?></a>
    </nav>
    <?php endif; ?>
</header>
<main class="page">
    <?php if (!empty($errorMessage ?? '')): ?>
        <div class="alert alert-error"><?= h($errorMessage) ?></div>
    <?php endif; ?>
    <?= $content ?>
</main>
</body>
</html>
