<?php
# © 2026 , Kudryashov Vasili
# Created: 2026-02-05 09:04:22
# Last Modified: 2026-02-05 09:04:25

require_once __DIR__ . "/index.php";

class Stock extends Auth
{
    public function quickCheck($params)
    {
        if (!empty($params->barcode)) {
            $row = $this->select("select f_id from c_goods where f_scancode=?", "s", [$params->barcode])->fetch_assoc();
            if (!$row) {
                dieWithCode("Do you want to hack quickCheck?");
            }
            $goods_id = $row["f_id"];
        } else {
            $goods_id = $params->goods_id;
        }
        if (!$goods_id) {
            dieWithCode("Do you want to hack quickCheck?");
        }
        $sql = <<<EOD
        select sum(f_qty*f_type) as f_qty from a_store where f_goods=? and f_store=?
        EOD;
        $row = $this->select($sql, "ii", [$goods_id, $params->store_id])->fetch_assoc();
        $this->result["qty"] = $row["f_qty"];
        $this->result = array_merge((array)$params, (array)$this->result);
        $this->echoResult();
    }
}
