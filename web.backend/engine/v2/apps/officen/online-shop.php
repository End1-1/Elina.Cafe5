<?php
# © 2026 , Kudryashov Vasili
# Created: 2026-06-17 19:50:46

require_once __DIR__ . "/index.php";

class OnlineShop extends Auth
{
    public function stockMatrix($params)
    {
        if (empty($params->rows) || !is_array($params->rows)) {
            dieWithCode("Empty goods list");
        }

        $stores = $this->select("select f_id, f_name from c_storages order by f_name")->fetch_all(MYSQLI_ASSOC);

        $goodsIds = [];
        foreach ($params->rows as $row) {
            $goodsId = (int)($row->goods_id ?? 0);
            if ($goodsId > 0) {
                $goodsIds[$goodsId] = true;
            }
        }

        if (empty($goodsIds)) {
            dieWithCode("No goods specified");
        }

        $ids = array_keys($goodsIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $sql = <<<EOD
        select f_goods, f_store, sum(f_qty*f_type) as f_qty
        from a_store
        where f_goods in ($placeholders)
        group by f_goods, f_store
        EOD;

        $stockRows = $this->select($sql, $types, $ids)->fetch_all(MYSQLI_ASSOC);
        $stockMap = [];
        $storeHasQty = [];
        foreach ($stockRows as $stockRow) {
            $goodsId = (int)$stockRow["f_goods"];
            $storeId = (int)$stockRow["f_store"];
            $qty = (float)$stockRow["f_qty"];
            $stockMap[$goodsId][$storeId] = $qty;
            if (abs($qty) > 0.000001) {
                $storeHasQty[$storeId] = true;
            }
        }

        $draftSql = <<<EOD
        select sd.f_goods, sd.f_store, sum(sd.f_qty) as f_qty
        from a_store_draft sd
        inner join a_header h on h.f_id = sd.f_document
        where h.f_state = 2
          and sd.f_type = -1
          and sd.f_goods in ($placeholders)
        group by sd.f_goods, sd.f_store
        EOD;

        $draftRows = $this->select($draftSql, $types, $ids)->fetch_all(MYSQLI_ASSOC);
        $draftMap = [];
        foreach ($draftRows as $draftRow) {
            $goodsId = (int)$draftRow["f_goods"];
            $storeId = (int)$draftRow["f_store"];
            $draftMap[$goodsId][$storeId] = (float)$draftRow["f_qty"];
        }

        foreach ($draftMap as $goodsId => $stores) {
            foreach ($stores as $storeId => $draftQty) {
                if ($draftQty < 0.000001) {
                    continue;
                }

                $available = ($stockMap[$goodsId][$storeId] ?? 0.0) - $draftQty;
                if ($available < 0) {
                    $available = 0.0;
                }

                $stockMap[$goodsId][$storeId] = $available;

                if ($available > 0.000001) {
                    $storeHasQty[$storeId] = true;
                }
            }
        }

        $stores = array_values(array_filter(
            $stores,
            fn($store) => !empty($storeHasQty[(int)$store["f_id"]])
        ));
        $this->result["stores"] = $stores;

        $resultRows = [];
        foreach ($params->rows as $row) {
            $goodsId = (int)($row->goods_id ?? 0);
            if ($goodsId < 1) {
                continue;
            }

            $stocks = [];
            foreach ($stores as $store) {
                $storeId = (int)$store["f_id"];
                $stocks[] = [
                    "store_id" => $storeId,
                    "qty" => $stockMap[$goodsId][$storeId] ?? 0.0,
                ];
            }

            $resultRows[] = [
                "row" => (int)($row->row ?? 0),
                "goods_id" => $goodsId,
                "name" => $row->name ?? "",
                "barcode" => $row->barcode ?? "",
                "qty" => (float)($row->qty ?? 0),
                "unit" => $row->unit ?? "",
                "store_id" => (int)($row->store_id ?? 0),
                "stocks" => $stocks,
            ];
        }

        $this->result["rows"] = $resultRows;
        $this->echoResult();
    }

    public function notifyHold($params)
    {
        if (empty($params->items) || !is_array($params->items)) {
            dieWithCode("Empty goods list");
        }

        $time = date("Y-m-d H:i:s");
        $created = 0;
        foreach ($params->items as $item) {
            $sourceStoreId = (int)($item->source_store_id ?? $params->store_id ?? 0);
            if ($sourceStoreId < 1) {
                continue;
            }

            $qty = (float)($item->qty ?? 0);
            if ($qty < 0.000001) {
                continue;
            }

            $body = [
                "action" => 4,
                "label" => "ONLINE",
                "time" => $time,
                "goodsname" => $item->name ?? "",
                "qty" => $qty,
            ];
            if (!empty($item->ordernumber)) {
                $body["ordernumber"] = $item->ordernumber;
            }

            $this->insert("sys_chat", [
                "f_state" => 0,
                "f_created" => $time,
                "f_userfrom" => $this->userid,
                "f_userto" => $sourceStoreId,
                "f_body" => json_encode($body, JSON_UNESCAPED_UNICODE),
            ]);
            $created++;
        }

        $this->result["created"] = $created;
        $this->echoResult();
    }
}
