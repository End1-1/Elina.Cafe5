<?php
# (C) 2025 Kudryashov Vasili 
# Created - 2025-03-30 11:41:21
# Last modified - 2025-03-30 11:41:24
class saledoc extends PClass
{
    public function createDraft()
    {
        require_once __DIR__ . "/../shop/shop.php";
        $saleid = $this->params->header->f_id ?? "";
        if (empty($saleid)) {
            $this->exitError("Order id is empty");
        }

        $goods = $this->params->goods ?? [];
        if (empty($goods)) {
            $this->exitError("Goods list is empty");
        }

        $h = $this->params->header;
        $this->db->begin_transaction();

        try {
            $sho = new ShopOrder($saleid);
            $sho->remove(false, true);

            $v = [];
            $v["f_id"] = $saleid;
            $v["f_state"] = 1;
            $v["f_saletype"] = (int)($h->f_saletype ?? 1);
            $v["f_date"] = date("Y-m-d");
            $v["f_time"] = date("H:i:s");
            $v["f_staff"] = (int)($h->f_staff ?? $this->userid);
            if ($v["f_staff"] < 1) {
                $v["f_staff"] = $this->userid;
            }
            $v["f_amount"] = (float)($h->f_amounttotal ?? 0);
            $v["f_comment"] = $h->f_comment ?? "";
            $v["f_payment"] = 1;
            $v["f_partner"] = (int)($h->f_partner ?? 0);
            $v["f_debt"] = 0;
            $v["f_discount"] = 0;
            $v["f_datefor"] = $h->f_deliverydate ?? date("Y-m-d");
            $v["f_cashier"] = $this->userid;
            if (!empty($h->f_hall)) {
                $v["f_hall"] = (int)$h->f_hall;
            }
            $this->sinsert("o_draft_sale", $v);

            $row = 0;
            foreach ($goods as $g) {
                $body = [];
                $body["f_id"] = $g->f_id;
                $body["f_header"] = $saleid;
                $body["f_state"] = 1;
                $body["f_store"] = (int)($g->f_store ?? 0);
                $body["f_dateappend"] = date("Y-m-d");
                $body["f_timeappend"] = date("H:i:s");
                $body["f_goods"] = (int)$g->f_goods;
                $body["f_qty"] = (float)$g->f_qty;
                $body["f_back"] = 0;
                $body["f_price"] = (float)$g->f_price;
                $body["f_discount"] = (float)($g->f_discountfactor ?? 0);
                $body["f_userappend"] = $this->userid;
                $body["f_row"] = (int)($g->f_row ?? $row);
                $this->sinsert("o_draft_sale_body", $body);
                $row++;
            }

            if (!$this->db->commit()) {
                throw new RuntimeException($this->db->error ?: "Commit failed");
            }
        } catch (Throwable $e) {
            $this->db->rollback();
            $this->exitError($e->getMessage());
        }

        $this->result["id"] = $saleid;
        $this->echoResult();
    }
}
