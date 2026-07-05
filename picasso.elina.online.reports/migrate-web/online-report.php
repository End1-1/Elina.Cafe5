<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/api.php';
require_once __DIR__ . '/inc/auth.php';

require_login();

$date1 = request_date('date1');
$date2 = request_date('date2', $date1);
$hall = (int)($_REQUEST['hall'] ?? 0);
$errorMessage = '';
$report = null;

$result = api_post('shop/reports/online-main.php', [
    'report' => 'report',
    'date1' => $date1,
    'date2' => $date2,
    'hall' => $hall,
    'version' => 0,
]);

if (!$result['ok']) {
    handle_api_auth_failure($result);
    $errorMessage = $result['error'] ?? t('api_error');
} else {
    $report = $result['data'];
}

$hallList = $report['halllist'] ?? [];
$items = $report['items'] ?? [];
$totals = $report['totals'] ?? [];
$cashbox = $report['cashbox'] ?? [];

$itemsQty = 0.0;
$itemsAmount = 0.0;
foreach ($items as $row) {
    $itemsQty += (float)($row['f_qty'] ?? 0);
    $itemsAmount += (float)($row['f_total'] ?? 0);
}

$sumTotal = $sumCash = $sumCard = $sumBank = $sumFiscal = $sumPrepaid = $sumDisc = 0.0;
foreach ($totals as $row) {
    $sumTotal += (float)($row['f_amounttotal'] ?? 0);
    $sumCash += (float)($row['f_amountcash'] ?? 0);
    $sumCard += (float)($row['f_amountcard'] ?? 0);
    $sumBank += (float)($row['f_amountbank'] ?? 0);
    $sumFiscal += (float)($row['f_fiscal'] ?? 0);
    $sumPrepaid += (float)($row['f_amountprepaid'] ?? 0);
    $sumDisc += (float)($row['f_disc'] ?? 0);
}
$fiscalPct = $sumTotal < 0.1 ? 0 : ($sumFiscal / $sumTotal * 100);

$cbColl = $cbCoin = $cbSpent = $cbRemain = 0.0;
foreach ($cashbox as $row) {
    $cbColl += (float)($row['f_handznum'] ?? 0);
    $cbCoin += (float)($row['f_kopek'] ?? 0);
    $cbSpent += (float)($row['f_spent'] ?? 0);
    $cbRemain += (float)($row['f_remainkopek'] ?? 0);
}

$pageTitle = t('online_report');
$activeMenu = 'online';
ob_start();
?>
<section class="card">
    <form method="get" class="filters">
        <label>
            <span><?= h(t('date_from')) ?></span>
            <input type="date" name="date1" value="<?= h($date1) ?>" required>
        </label>
        <label>
            <span><?= h(t('date_to')) ?></span>
            <input type="date" name="date2" value="<?= h($date2) ?>" required>
        </label>
        <label>
            <span><?= h(t('branch')) ?></span>
            <select name="hall">
                <option value="0"<?= $hall === 0 ? ' selected' : '' ?>><?= h(t('all_branches')) ?></option>
                <?php foreach ($hallList as $h): ?>
                    <option value="<?= (int)$h['f_id'] ?>"<?= $hall === (int)$h['f_id'] ? ' selected' : '' ?>>
                        <?= h($h['f_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary"><?= h(t('refresh')) ?></button>
    </form>
</section>

<?php if ($report !== null): ?>

<section class="card">
    <h2><?= h(t('goods')) ?></h2>
    <div class="table-wrap">
        <table class="report">
            <thead>
            <tr>
                <th>#</th>
                <th><?= h(t('group')) ?></th>
                <th class="num"><?= h(t('qty')) ?></th>
                <th class="num"><?= h(t('amount')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="4" class="muted"><?= h(t('no_data')) ?></td></tr>
            <?php else: ?>
                <?php $n = 1; foreach ($items as $row): ?>
                <tr>
                    <td><?= $n++ ?></td>
                    <td><?= h($row['f_groupname'] ?? '') ?></td>
                    <td class="num"><?= h(fmt_qty($row['f_qty'] ?? 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_total'] ?? 0, 0)) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="row-total">
                    <td colspan="2"><?= h(t('total')) ?></td>
                    <td class="num"><?= h(fmt_qty($itemsQty)) ?></td>
                    <td class="num"><?= h(fmt_number($itemsAmount, 0)) ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2><?= h(t('amount_total')) ?></h2>
    <div class="table-wrap">
        <table class="report report--sticky-first">
            <thead>
            <tr>
                <th><?= h(t('branch')) ?></th>
                <th class="num"><?= h(t('total')) ?></th>
                <th class="num"><?= h(t('cash')) ?></th>
                <th class="num"><?= h(t('card')) ?></th>
                <th class="num"><?= h(t('bank')) ?></th>
                <th class="num"><?= h(t('fiscal')) ?></th>
                <th class="num"><?= h(t('fiscal_pct')) ?></th>
                <th class="num"><?= h(t('prepaid')) ?></th>
                <th class="num"><?= h(t('discount')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($totals)): ?>
                <tr><td colspan="9" class="muted"><?= h(t('no_data')) ?></td></tr>
            <?php else: ?>
                <?php foreach ($totals as $row):
                    $rowTotal = (float)($row['f_amounttotal'] ?? 0);
                    $rowFiscal = (float)($row['f_fiscal'] ?? 0);
                    $rowPct = $rowTotal < 0.1 ? 0 : ($rowFiscal / $rowTotal * 100);
                ?>
                <tr>
                    <td><?= h($row['f_name'] ?? '') ?></td>
                    <td class="num"><?= h(fmt_number($rowTotal, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_amountcash'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_amountcard'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_amountbank'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($rowFiscal, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($rowPct, 1)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_amountprepaid'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_disc'] ?? 0, 0)) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="row-total">
                    <td><?= h(t('total')) ?></td>
                    <td class="num"><?= h(fmt_number($sumTotal, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($sumCash, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($sumCard, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($sumBank, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($sumFiscal, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($fiscalPct, 1)) ?></td>
                    <td class="num"><?= h(fmt_number($sumPrepaid, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($sumDisc, 0)) ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h2><?= h(t('cashbox')) ?></h2>
    <div class="table-wrap">
        <table class="report report--sticky-first">
            <thead>
            <tr>
                <th><?= h(t('branch')) ?></th>
                <th class="num"><?= h(t('collection')) ?></th>
                <th class="num"><?= h(t('coin')) ?></th>
                <th class="num"><?= h(t('spent')) ?></th>
                <th class="num"><?= h(t('coin_remain')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($cashbox)): ?>
                <tr><td colspan="5" class="muted"><?= h(t('no_data')) ?></td></tr>
            <?php else: ?>
                <?php foreach ($cashbox as $row): ?>
                <tr>
                    <td><?= h($row['f_hallname'] ?? '') ?></td>
                    <td class="num"><?= h(fmt_number($row['f_handznum'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_kopek'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_spent'] ?? 0, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($row['f_remainkopek'] ?? 0, 0)) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="row-total">
                    <td><?= h(t('total')) ?></td>
                    <td class="num"><?= h(fmt_number($cbColl, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($cbCoin, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($cbSpent, 0)) ?></td>
                    <td class="num"><?= h(fmt_number($cbRemain, 0)) ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php endif; ?>
<?php
$content = ob_get_clean();
require __DIR__ . '/templates/layout.php';
