<?php
require_once __DIR__ . "/../app.php";

class CreateGroupDiscount extends PClass
{
    public function __construct()
    {
        parent::__construct();
    }

    public function createGroupDiscount()
    {
        $v["f_name"] = $this->params->name;
        $v["f_body"] = "{}";
        $v["f_state"] = 1;
        $this->result["id"] = $this->sinsert("c_goods_price_order", $v);
        $this->result["name"] = $this->params->name;
        $this->echoResult();
    }

    public function getAll()
    {
        $all = $this->stmtall("select  * from c_goods_price_order")->fetch_all(MYSQLI_ASSOC);
        #check current prices, update new items, etc...
        foreach ($all as &$a) {
            $body = json_decode($a["f_body"]);
            if (property_exists($body, "groups")) {
                foreach ($body->groups as $group) {
                    $group->qty = $this->getQtyOfGroup($group->f_id);
                    $sql = <<<EOD
                SELECT g.f_id, g.f_name, gp.f_price1, gp.f_price2, gp.f_price1disc, gp.f_price2disc, g.f_scancode
                FROM c_goods g 
                INNER JOIN c_goods_prices gp ON gp.f_goods=g.f_id AND gp.f_currency=1
                WHERE g.f_group=?
                EOD;
                    $check = $this->stmtall($sql, "i", [$group->f_id])->fetch_all(MYSQLI_ASSOC);
                    foreach ($check as $c) {
                        for ($i = 0; $i < count($group->items); $i++) {
                            $found = false;
                            if ($group->items[$i]->f_id == $c["f_id"]) {
                                $found = true;
                                $group->items[$i]->f_price1 = $c["f_price1"];
                                $group->items[$i]->f_price2 = $c["f_price2"];
                                break;
                            }
                            if ($found) {

                            }
                        }
                    }
                }
                $a["f_body"] = json_encode($body, JSON_UNESCAPED_UNICODE);
            }
        }
        $this->result["all"] = $all;
        $this->echoResult();
    }

    public function getGroupItems()
    {
        $this->result["itemid"] = $this->params->item;
        $this->result["group"] = $this->params->group;
        $this->result["groupname"] = $this->params->groupname;
        $sql = <<<EOD
        select c.f_id, c.f_name, c.f_scancode, 
        cp.f_price1, cp.f_price2, 
        coalesce(cp.f_price1disc, 0) as f_price1disc,
        coalesce(cp.f_price2disc, 0) f_price2disc
        from c_goods c 
        left join c_goods_prices cp on cp.f_goods=c.f_id and cp.f_currency=1 
        where c.f_group=?
        EOD;
        $this->result["items"] = $this->stmtall($sql, "i", [$this->params->group])->fetch_all(MYSQLI_ASSOC);
        $this->result["qty"] = $this->getQtyOfGroup($this->params->group);
        if (!empty($this->result["items"])) {
            $this->result["retail"] = $this->result["items"][0]["f_price1"];
            $this->result["whosale"] = $this->result["items"][0]["f_price2"];
        }
        $this->echoResult();
    }

    public function save()
    {
        $body = $this->decodeBody($this->params->body);
        $sc = $this->buildStockChange($body, false);
        $body->stock_change = json_decode(json_encode($sc));
        $v["f_name"] = $this->params->name;
        $v["f_body"] = json_encode($body, JSON_UNESCAPED_UNICODE);
        $v["f_state"] = 1;
        $this->supdate("c_goods_price_order", $v, $this->params->itemid);
        $this->result["stock_change"] = $sc;
        $this->echoResult();
    }

    public function discount()
    {
        $body = $this->decodeBody($this->params->body);
        $sc = $this->buildStockChange($body, false);
        $body->stock_change = json_decode(json_encode($sc));
        $v["f_name"] = $this->params->name;
        $v["f_body"] = json_encode($body, JSON_UNESCAPED_UNICODE);
        $v["f_state"] = 1;
        $this->supdate("c_goods_price_order", $v, $this->params->itemid);
        foreach ($body->groups as $b) {
            foreach ($b->items as $i) {
                $this->stmtall("update c_goods_prices set f_price1disc=?, f_price2disc=? where f_goods=? and f_currency=1", "ddi", [
                    $i->f_price1disc,
                    $i->f_price2disc,
                    $i->f_id
                ]);
            }
        }
        $this->result["stock_change"] = $sc;
        $this->echoResult();
    }

    public function rollback()
    {
        $body = $this->decodeBody($this->params->body);
        $sc = $this->buildStockChange($body, true);
        $body->stock_change = json_decode(json_encode($sc));
        $v["f_name"] = $this->params->name;
        $v["f_body"] = json_encode($body, JSON_UNESCAPED_UNICODE);
        $v["f_state"] = 1;
        $this->supdate("c_goods_price_order", $v, $this->params->itemid);
        foreach ($body->groups as $b) {
            foreach ($b->items as $i) {
                $this->stmtall("update c_goods_prices set f_price1disc=0, f_price2disc=0 where f_goods=? and f_currency=1", "i", [
                    $i->f_id
                ]);
            }
        }
        $this->result["stock_change"] = $sc;
        $this->echoResult();
    }

    public function calcStockChange()
    {
        $body = $this->decodeBody($this->params->body);
        $forRollback = !empty($this->params->rollback);
        $this->result["stock_change"] = $this->buildStockChange($body, $forRollback);
        $this->echoResult();
    }

    public function refreshSaleStore()
    {
        $sql = <<<EOD
        SELECT g.f_group, SUM(s.f_qty*s.f_type) + s2.f_qty as f_qty
        FROM a_store s
        LEFT JOIN c_goods g ON g.f_id=s.f_goods
        left JOIN (SELECT g2.f_group, coalesce(sum(s.f_qty*s.f_type*c.f_qty),0) AS f_qty
        FROM a_store s
        LEFT JOIN c_goods g1 ON g1.f_id=s.f_goods
        LEFT JOIN c_goods_complectation c ON c.f_base=g1.f_id
        LEFT JOIN c_goods g2 ON g2.f_id=c.f_goods
        WHERE g2.f_group in (?)
        ) s2 ON s2.f_group=g.f_group
        WHERE g.f_group in (?)
        EOD;
        $this->result["store"] = $this->stmtall($sql, "ss", [$this->params->groups, $this->params->groups])->fetch_all();
        $this->result["sale"] = $this->stmtall("select f_group, sum(s.f_sign*f_qty) "
            . "from o_goods s "
            . "left join o_header h on h.f_id=s.f_header "
            . "left join c_goods g on g.f_id=s.f_goods "
            . "where h.f_datecash between ? and ? and g.f_group in ({$this->params->groups}) "
            . "group by 1 ", "ss", [$this->params->date1, $this->params->date2])->fetch_all();
        $this->echoResult();
    }

    public function editLwName()
    {
        $this->result["id"] = $this->params->id;
        $this->result["name"] = $this->params->name;
        $this->stmtall("update c_goods_price_order set f_name=? where f_id=?", "si", [$this->params->name, $this->params->id]);
        $this->echoResult();
    }

    public function removeLwName()
    {
        $this->result["id"] = $this->params->id;
        $this->stmtall("delete from c_goods_price_order where f_id=?", "i", [$this->params->id]);
        $this->echoResult();
    }

    public function getQtyOfGroup($group)
    {
        $sql = <<<EOD
        SELECT g.f_group, SUM(s.f_qty*s.f_type) + s2.f_qty as f_qty
        FROM a_store s
        LEFT JOIN c_goods g ON g.f_id=s.f_goods
        left JOIN (SELECT g2.f_group, coalesce(sum(s.f_qty*s.f_type*c.f_qty),0) AS f_qty
        FROM a_store s
        LEFT JOIN c_goods g1 ON g1.f_id=s.f_goods
        LEFT JOIN c_goods_complectation c ON c.f_base=g1.f_id
        LEFT JOIN c_goods g2 ON g2.f_id=c.f_goods
        WHERE g2.f_group=?
        ) s2 ON s2.f_group=g.f_group
        WHERE g.f_group=?
        EOD;
        $row = $this->stmtall($sql, "ii", [$group, $group])->fetch_assoc();
        return $row["f_qty"];
    }

    private function decodeBody($body)
    {
        if (is_string($body)) {
            $decoded = json_decode($body);
            return $decoded ? $decoded : (object)["groups" => []];
        }
        if (is_object($body)) {
            return $body;
        }
        return (object)["groups" => []];
    }

    private function buildStockChange($body, $forRollback = false)
    {
        $empty = [
            "total_delta" => 0,
            "items_count" => 0,
            "calculated_at" => date("Y-m-d H:i:s"),
            "by_store" => []
        ];
        if (!is_object($body) || !property_exists($body, "groups") || empty($body->groups)) {
            return $empty;
        }

        $priceAfterByGoods = [];
        $goodsIds = [];
        foreach ($body->groups as $g) {
            if (empty($g->items)) {
                continue;
            }
            $groupDisc = isset($g->f_price1disc) ? floatval($g->f_price1disc) : 0.0;
            foreach ($g->items as $i) {
                $id = (int)$i->f_id;
                $goodsIds[] = $id;
                $afterDisc = isset($i->f_price1disc) ? floatval($i->f_price1disc) : 0.0;
                if ($afterDisc <= 0 && $groupDisc > 0) {
                    $afterDisc = $groupDisc;
                }
                $priceAfterByGoods[$id] = $afterDisc;
            }
        }
        $goodsIds = array_values(array_unique($goodsIds));
        if (empty($goodsIds)) {
            return $empty;
        }

        $qtysByStore = $this->getQtyOfGoodsByStore($goodsIds);
        $prices = $this->getRetailPrices($goodsIds);
        $storeNames = $this->getStoreNames(array_keys($qtysByStore));

        $totalDelta = 0.0;
        $itemsCount = count($goodsIds);
        $byStore = [];

        foreach ($qtysByStore as $storeId => $goodsQty) {
            $delta = 0.0;
            foreach ($goodsQty as $goodsId => $qty) {
                if (abs($qty) < 0.000001) {
                    continue;
                }
                $p1 = isset($prices[$goodsId]) ? floatval($prices[$goodsId]["f_price1"]) : 0.0;
                $p1d = isset($prices[$goodsId]) ? floatval($prices[$goodsId]["f_price1disc"]) : 0.0;
                $before = $p1d > 0 ? $p1d : $p1;
                if ($forRollback) {
                    $after = $p1;
                } else {
                    $afterDisc = isset($priceAfterByGoods[$goodsId]) ? floatval($priceAfterByGoods[$goodsId]) : 0.0;
                    $after = $afterDisc > 0 ? $afterDisc : $p1;
                }
                $delta += $qty * ($after - $before);
            }
            $delta = round($delta, 2);
            if (abs($delta) < 0.01) {
                continue;
            }
            $totalDelta += $delta;
            $byStore[] = [
                "f_id" => (int)$storeId,
                "f_name" => isset($storeNames[$storeId]) ? $storeNames[$storeId] : ("#" . $storeId),
                "delta" => $delta
            ];
        }

        usort($byStore, function ($a, $b) {
            return strcmp($a["f_name"], $b["f_name"]);
        });

        return [
            "total_delta" => round($totalDelta, 2),
            "items_count" => $itemsCount,
            "calculated_at" => date("Y-m-d H:i:s"),
            "by_store" => $byStore
        ];
    }

    private function getQtyOfGoodsByStore($ids)
    {
        $result = [];
        if (empty($ids)) {
            return $result;
        }
        $in = implode(",", array_map("intval", $ids));

        $rows = $this->stmtall(
            "SELECT s.f_store, s.f_goods, SUM(s.f_qty*s.f_type) as f_qty
            FROM a_store s
            WHERE s.f_goods IN ($in)
            GROUP BY s.f_store, s.f_goods"
        )->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $r) {
            $storeId = (int)$r["f_store"];
            $goodsId = (int)$r["f_goods"];
            if (!isset($result[$storeId])) {
                $result[$storeId] = [];
            }
            if (!isset($result[$storeId][$goodsId])) {
                $result[$storeId][$goodsId] = 0.0;
            }
            $result[$storeId][$goodsId] += floatval($r["f_qty"]);
        }

        $rows2 = $this->stmtall(
            "SELECT s.f_store, c.f_goods, SUM(s.f_qty*s.f_type*c.f_qty) as f_qty
            FROM a_store s
            INNER JOIN c_goods_complectation c ON c.f_base=s.f_goods
            WHERE c.f_goods IN ($in)
            GROUP BY s.f_store, c.f_goods"
        )->fetch_all(MYSQLI_ASSOC);
        foreach ($rows2 as $r) {
            $storeId = (int)$r["f_store"];
            $goodsId = (int)$r["f_goods"];
            if (!isset($result[$storeId])) {
                $result[$storeId] = [];
            }
            if (!isset($result[$storeId][$goodsId])) {
                $result[$storeId][$goodsId] = 0.0;
            }
            $result[$storeId][$goodsId] += floatval($r["f_qty"]);
        }

        return $result;
    }

    private function getStoreNames($storeIds)
    {
        $result = [];
        if (empty($storeIds)) {
            return $result;
        }
        $in = implode(",", array_map("intval", $storeIds));
        $rows = $this->stmtall(
            "SELECT f_id, f_name FROM c_storages WHERE f_id IN ($in)"
        )->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $r) {
            $result[(int)$r["f_id"]] = $r["f_name"];
        }
        return $result;
    }

    private function getRetailPrices($ids)
    {
        $result = [];
        if (empty($ids)) {
            return $result;
        }
        $in = implode(",", array_map("intval", $ids));
        $rows = $this->stmtall(
            "SELECT f_goods, f_price1, coalesce(f_price1disc, 0) as f_price1disc
            FROM c_goods_prices WHERE f_currency=1 AND f_goods IN ($in)"
        )->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $r) {
            $result[(int)$r["f_goods"]] = [
                "f_price1" => floatval($r["f_price1"]),
                "f_price1disc" => floatval($r["f_price1disc"])
            ];
        }
        return $result;
    }
}

if (!empty($params->action)) {
    $c = new CreateGroupDiscount();
    $c->{$params->action}();
}
