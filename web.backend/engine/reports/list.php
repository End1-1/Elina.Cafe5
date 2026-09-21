<?php
# (C) 2024-2025 Kudryashov Vasili
# Last modified - 2025-03-18 09:00:30
require_once __DIR__ . "/reports.php";

class ReportList extends PClass
{
    public function get()
    {
        $this->result = [
            "reports" => [
                ["title" => "Համառոտ հասույթ", "route" => "/engine/reports/sale1.php", "image" => "goods.png"],
                ["title" => $this->tr("Effectivness"), "route" => "/engine/reports/effectivness.php", "image" => "effectiveness.png"],
                ["title" => "Պահեստի մնացորդ", "route" => "/engine/reports/out-of-stock.php", "image" => "goods.png"],
                ["title" => "Պահեստ 8 vs վաճառքի պահեստներ", "route" => "/engine/reports/store8-vs-sale-stores.php", "image" => "goods.png"],
                ["title" => "Ապրանքների առկայություն", "route" => "/engine/goods/store.php", "image" => "goods.png"],
                ["title" => "Ապրանքի շարժ վաճառք/պահեստ", "route" => "/engine/reports/sale-store-relation.php", "image" => "goods.png"],
                ["title" => $this->tr("Draft sales"), "route" => "/engine/reports/draft-sales.php", "image" => "goods.png"],
                ["title" => $this->tr("Daily shop report"), "route" => "/engine/reports/elina_daily_rep.php", "image" => "goods.png"],
                ["title" => $this->tr("Complectation additions"), "route" => "/engine/reports/complectation_additions.php", "image" => "goods.png"],
            ]
        ];
        $this->echoResult();
    }
}

$rl = new ReportList();
$rl->get();
