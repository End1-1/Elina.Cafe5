<?php
# © 2026 , Kudryashov Vasili
# Created: 2026-01-27 11:40:20
# Last Modified: 2026-03-29 11:40:25
require_once __DIR__ . "/index.php";

class Complect extends Auth {
    public function get($params) {
        $complectId = (int)($params->complect_id ?? 0);
        if ($complectId <= 0) {
            dieWithCode(Translator::t("Select complect"));
        }

        $base = $this->select(
            "select f_id, f_name, f_scancode, f_complectout, f_lastinputprice from c_goods where f_id=?",
            "i",
            [$complectId]
        )->fetch_assoc();
        if (empty($base)) {
            dieWithCode(Translator::t("Wrong barcode"));
        }

        $sql = <<<EOD
        select g.f_id, gr.f_name as f_group_name, g.f_name, g.f_scancode as f_barcode,
        u.f_name as f_unit_name, gc.f_qty, g.f_lastinputprice
        from c_goods_complectation gc
        left join c_goods g on g.f_id=gc.f_goods
        left join c_groups gr on gr.f_id=g.f_group
        left join c_units u on u.f_id=g.f_unit
        where gc.f_base=?
        order by g.f_name
        EOD;
        $items = $this->select($sql, "i", [$complectId])->fetch_all(MYSQLI_ASSOC);

        $this->result["complect"] = $base;
        $this->result["f_complectout"] = (float)($base["f_complectout"] ?: 1);
        $this->result["complect_items"] = $items ?: [];
        $this->echoResult();
    }
}
