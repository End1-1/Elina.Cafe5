<?php
# (C) 2026 Kudryashov Vasili
# Goods sales by store for marketing: stores 2,3,4,24
require_once __DIR__ . "/reports.php";

class SaleForMarketingReport extends Report
{
    private const SALE_STORES = [2, 3, 4, 24];

    protected function columns()
    {
        return [
            $this->tr("Store"),
            $this->tr("Group"),
            $this->tr("Goods"),
            $this->tr("Scancode"),
            $this->tr("Qty"),
        ];
    }

    protected function filter()
    {
        return [
            ["type" => "date", "title" => tr("Date of begin"), "field" => "date1"],
            ["type" => "date", "title" => tr("Date of end"), "field" => "date2"],
        ];
    }

    protected function where()
    {
        $date1 = $this->safeDate($this->params->date1 ?? null, date("Y-m-01"));
        $date2 = $this->safeDate($this->params->date2 ?? null, date("Y-m-d"));
        $stores = implode(",", array_map("intval", self::SALE_STORES));
        return "WHERE oh.f_state=2"
            . " AND og.f_store IN ($stores)"
            . " AND oh.f_datecash BETWEEN '$date1' AND '$date2'";
    }

    protected function rows()
    {
        $sql = <<<EOD
        SELECT
            st.f_name AS f_store,
            gr.f_name AS f_group,
            g.f_name AS f_goods,
            g.f_scancode,
            CAST(SUM(og.f_qty * COALESCE(og.f_sign, 1)) AS FLOAT) AS f_qty
        FROM o_goods og
        INNER JOIN o_header oh ON oh.f_id = og.f_header
        LEFT JOIN c_goods g ON g.f_id = og.f_goods
        LEFT JOIN c_groups gr ON gr.f_id = g.f_group
        LEFT JOIN c_storages st ON st.f_id = og.f_store
        {$this->where()}
        GROUP BY st.f_name, gr.f_name, g.f_name, g.f_scancode
        ORDER BY st.f_name, gr.f_name, g.f_name
        EOD;

        return $this->db->stmtall($sql)->fetch_all();
    }

    protected function sumColumns()
    {
        return [
            ["4" => 0],
        ];
    }

    protected function widget()
    {
        return [
            "icon" => "goods.png",
            "title" => $this->tr("Sale for marketing"),
        ];
    }

    private function safeDate($value, $fallback)
    {
        if (empty($value)) {
            return $fallback;
        }
        try {
            return (new DateTimeImmutable((string)$value))->format("Y-m-d");
        } catch (Exception $e) {
            return $fallback;
        }
    }
}

$r = new SaleForMarketingReport();
$r->echoResult();
