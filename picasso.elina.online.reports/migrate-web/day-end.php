<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/api.php';
require_once __DIR__ . '/inc/auth.php';

require_login();

$date = request_date('date');
$errorMessage = '';
$rows = [];

$result = api_post('shop/reports/day-end.php', [
    'action' => 'get',
    'date' => $date,
]);

if (!$result['ok']) {
    handle_api_auth_failure($result);
    $errorMessage = $result['error'] ?? t('api_error');
} else {
    $rows = $result['data']['report'] ?? [];
}

$pageTitle = t('day_end');
$activeMenu = 'dayend';
ob_start();
?>
<section class="card">
    <form method="get" class="filters">
        <label>
            <span><?= h(t('date')) ?></span>
            <input type="date" name="date" value="<?= h($date) ?>" required>
        </label>
        <button type="submit" class="btn btn-primary"><?= h(t('refresh')) ?></button>
    </form>
</section>

<section class="card">
    <h2><?= h(t('day_end')) ?></h2>
    <div class="table-wrap">
        <table class="report">
            <thead>
            <tr>
                <th><?= h(t('shop')) ?></th>
                <th class="num"><?= h(t('previous')) ?></th>
                <th class="num"><?= h(t('income')) ?></th>
                <th class="num"><?= h(t('other_income')) ?></th>
                <th class="num"><?= h(t('sale')) ?></th>
                <th class="num"><?= h(t('output')) ?></th>
                <th class="num"><?= h(t('discount')) ?></th>
                <th class="num"><?= h(t('final')) ?></th>
                <th class="num"><?= h(t('check')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="9" class="muted"><?= h(t('no_data')) ?></td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= h($row['f_name'] ?? '') ?></td>
                    <td class="num"><?= h(fmt_number($row['f_prevday'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_income'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_inputother'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_sale'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_output'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_discount'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_final'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_check'] ?? 0, 0)) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
