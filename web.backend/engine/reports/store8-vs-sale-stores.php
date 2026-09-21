<?php
# (C) 2026 Kudryashov Vasili
# Store 8 stock (incl. block contents) vs sale stores 2,3,5,24
require_once __DIR__ . "/reports.php";

class Store8VsSaleStoresReport extends Report
{
    private const SOURCE_STORE = 8;
    private const SALE_STORES = [2, 3, 5, 24];
    private const BLOCK_UNIT = 10;

    private function storeNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $in = implode(",", array_map("intval", $ids));
        $rows = $this->db->stmtall(
            "SELECT f_id, f_name FROM c_storages WHERE f_id IN ($in)"
        )->fetch_all(MYSQLI_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r["f_id"]] = $r["f_name"];
        }
        return $map;
    }

    protected function columns()
    {
        $names = $this->storeNames(array_merge([self::SOURCE_STORE], self::SALE_STORES));
        $cols = [
            $this->tr("Group"),
            $this->tr("Goods"),
            $this->tr("Scancode"),
            ($names[self::SOURCE_STORE] ?? ($this->tr("Store") . " " . self::SOURCE_STORE)),
        ];
        foreach (self::SALE_STORES as $id) {
            $cols[] = $names[$id] ?? ($this->tr("Store") . " " . $id);
        }
        return $cols;
    }

    protected function filter()
    {
        return [];
    }

    protected function rows()
    {
        $src = self::SOURCE_STORE;
        $unit = self::BLOCK_UNIT;

        $selectSale = "";
        $joinSale = "";
        foreach (self::SALE_STORES as $idx => $storeId) {
            $alias = "s" . $storeId;
            $selectSale .= ", CAST(COALESCE({$alias}.f_qty, 0) AS FLOAT) AS qty_{$storeId}";
            $joinSale .= " LEFT JOIN (
                SELECT f_goods, SUM(f_qty * f_type) AS f_qty
                FROM a_store
                WHERE f_store = {$storeId}
                GROUP BY f_goods
            ) {$alias} ON {$alias}.f_goods = g.f_id ";
        }

        $sql = <<<EOD
        SELECT
            gr.f_name AS f_group,
            g.f_name AS f_goods,
            g.f_scancode,
            CAST(COALESCE(s8.f_qty, 0) + COALESCE(s8b.f_qty, 0) AS FLOAT) AS qty8
            {$selectSale}
        FROM (
            SELECT f_goods FROM (
                SELECT f_goods
                FROM a_store
                WHERE f_store = {$src}
                GROUP BY f_goods
                HAVING SUM(f_qty * f_type) > 0
                UNION
                SELECT gc.f_goods
                FROM a_store a
                INNER JOIN c_goods gb ON gb.f_id = a.f_goods AND gb.f_unit = {$unit}
                INNER JOIN c_goods_complectation gc ON gc.f_base = a.f_goods
                WHERE a.f_store = {$src}
                GROUP BY gc.f_goods
                HAVING SUM(a.f_qty * a.f_type) > 0
            ) u
        ) base
        INNER JOIN c_goods g ON g.f_id = base.f_goods AND g.f_enabled = 1
            AND COALESCE(g.f_unit, 0) <> {$unit}
        LEFT JOIN c_groups gr ON gr.f_id = g.f_group
        LEFT JOIN (
            SELECT f_goods, SUM(f_qty * f_type) AS f_qty
            FROM a_store
            WHERE f_store = {$src}
            GROUP BY f_goods
        ) s8 ON s8.f_goods = g.f_id
        LEFT JOIN (
            SELECT gc.f_goods, SUM(a.f_qty * a.f_type * gc.f_qty) AS f_qty
            FROM a_store a
            INNER JOIN c_goods gb ON gb.f_id = a.f_goods AND gb.f_unit = {$unit}
            INNER JOIN c_goods_complectation gc ON gc.f_base = a.f_goods
            WHERE a.f_store = {$src}
            GROUP BY gc.f_goods
        ) s8b ON s8b.f_goods = g.f_id
        {$joinSale}
        WHERE COALESCE(s8.f_qty, 0) + COALESCE(s8b.f_qty, 0) > 0
        ORDER BY gr.f_name, g.f_name
        EOD;

        return $this->db->stmtall($sql)->fetch_all();
    }

    protected function sumColumns()
    {
        return [
            ["3" => 0],
            ["4" => 0],
            ["5" => 0],
            ["6" => 0],
            ["7" => 0],
        ];
    }

    protected function widget()
    {
        return [
            "icon" => "goods.png",
            "title" => $this->tr("Store 8 vs sale stores"),
        ];
    }
}

$r = new Store8VsSaleStoresReport();
$r->echoResult();
