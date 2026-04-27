<?php
# © 2025 , Kudryashov Vasili
# Created: 2025-07-06 13:35:02
# Last Modified: 2026-02-07 09:38:00

require_once __DIR__ . "/index.php";
require_once __DIR__ . "/../../worker/uuid.php";

const ORDER_STATE_OPEN = 1;
const ORDER_STATE_CLOSED = 2;
const ORDER_STATE_EMPTY = 3;
const ORDER_STATE_MOVED = 4;
const ORDER_STATE_PREORDER = 5;

const FORMAT_DATE_TO_STR = "d/m/Y";
const FORMAT_TIME_TO_STR = "H:i";

const CASHBOX_IN = 1;

class Order extends Auth
{
    public function OpenOrder($params)
    {
        $this->result["order"] = $this->GetOrder($params->id);
        $this->echoResult();
    }
    public function ReopenOrder($params)
    {
        $header = $this->select("select * from o_header where f_id=?", "s", [$params->id])->fetch_assoc();
        if (!$header) {
            dieWithCode("Do you want to try hack reopen order?");
        }
        if ($header["f_state"]  != ORDER_STATE_CLOSED) {
            dieWithCode("Why you want to reopen order with illegal state?");
        }
        $check = $this->select("select * from o_header where f_state=1 and f_table=?", "i", [$header["f_table"]])->fetch_row();
        if ($check) {
            dieWithCode(Translator::t("This table busy now"));
        }
        $odata = json_decode($header["f_data"] ?? "{}", true);
        $odata["log"][] = ["ts" => date("Y-m-d H:i:s"), "action" => "reopen", "user" => $this->fullName()];
        $this->update("o_header", [
            "f_state" => 1,
            "f_data" => json_encode($odata, JSON_UNESCAPED_UNICODE)
        ], $params->id);
        $this->result["order"] = $this->GetOrder($params->id);
        $this->echoResult();
    }

    public function AddDish($params)
    {
        //first , check stoplist
        $this->beginTransaction();
        $stoplist = $this->select("select * from c_stoplist where f_dish=? for update", "i", [$params->dish])->fetch_assoc();
        if (!empty($stoplist)) {
            if ($stoplist["f_qty"] - $params->qty < 0) {
                $this->rollback();
                dieWithCode(Translator::t("Stoplist limit reached"));
            }
            $this->select("update c_stoplist set f_qty=f_qty-? where f_dish=?", "ii", [$params->qty, $params->dish], true);
            $this->result["stoplist"] = $stoplist["f_qty"] - $params->qty;
        }
        $this->commit();
        $oheader = $this->GetOpenHeader($params->table);
        if (empty($oheader) || empty($oheader["f_id"])) {
            $oheader = $this->CreateOrder($params);
        }

        $data = [
            "f_count_service" => $params->count_service ?? 0,
            "f_count_discount" => $params->count_discount ?? 0,
            "f_service_factor" => $params->service_factor ?? 0,
            "f_discount_factor" => $params->discount_factor ?? 0,
            "f_append_time" => date("Y-m-d H:i:s"),
            "f_append_user" => $this->fullName(),
            "f_print1" => $params->print1,
            "f_print2" => $params->print2,
        ];
        $this->result["focused_dish"] = uuid_v4();
        $v["f_id"] = $this->result["focused_dish"];
        $v["f_header"] = $oheader["f_id"];
        $v["f_state"] = 1;
        $v["f_type"] = $params->type;
        $v["f_parent"] = $params->parent ?? null;
        $v["f_store"] = $params->store;
        $v["f_goods"] = $params->dish;
        $v["f_qty"] = $params->qty;
        $v["f_price"] = $params->price;
        $v["f_total"] = $params->price * $params->qty;
        $v["f_data"] = json_encode($data, JSON_UNESCAPED_UNICODE);
        $v["f_row"] = $params->row;
        $this->insert("o_goods", $v);
        if (!empty($params->shift_rows)) {
            $this->select("update o_goods set f_row=f_row+100 where f_row>=? and f_header=?", "is", [$params->row, $oheader["f_id"]], true);
        }
        $this->CountAmounts($oheader["f_id"],  $this->LogRecord("add dish", ["comment" => $params->dish_name . " (" . $params->qty . ") "]));
        $oheader["dishes"] = $this->GetDishes($oheader["f_id"]);
        $this->result["order"] = $oheader;
        $this->echoResult();
    }

    public function SetDishQty($params)
    {
        $dish_states = require_once __DIR__ . "/../worker/dict-dish-state.php";
        $sql = <<<EOD
        select g.f_name as f_dish_name, og.* 
        from o_goods og
        left join c_goods g on g.f_id=og.f_goods
        where og.f_id=?
        EOD;
        $row = $this->select($sql, "s", [$params->id])->fetch_assoc();
        if (!$row) {
            dieWithCode("Are you hacker of o_goods row?");
        }
        $data = json_decode($row["f_data"] ?? "{}", true);
        $data = array_merge((array)$params->data, $data);
        $stoplistqty = $params->restore_stoplist ?? 0;
        $log = [];
        if ($stoplistqty != 0) {
            $this->beginTransaction();
            $stoplist = $this->select("select * from c_stoplist where f_dish=? for update", "i", [$params->dish])->fetch_assoc();
            if (!empty($stoplist)) {
                $v["f_qty"] = $stoplist["f_qty"] + $params->restore_stoplist;
                if ($v["f_qty"] < 0) {
                    $this->rollback();
                    dieWithCode(Translator::t("Stoplist limit reached"));
                }
                $this->update("c_stoplist", $v, $params->dish, "f_dish");
                $this->result["restore_stoplist_qty"] = $v["f_qty"];
                $this->result["restore_stoplist_dish"] = $params->dish;
            }
            $this->commit();
        }
        if ($params->new_state == 2 || $params->new_state == 3) {
            $this->result["print1"] = $data["f_print1"] ?? "";
            $this->result["print2"] = $data["f_print2"] ?? "";
            $this->result["f_store_out"] = $params->new_state == DISH_STATE_VOID;
            $this->result["f_removed_qty"] = $params->new_qty;
            $this->result["f_removed_dish_name"] = $row["f_dish_name"];
            if (!empty($params->remove_reason)) {
                $data["f_remove_reason"] = $params->remove_reason;
                $data["f_removed_time"] = date("y-M-d H:i:s");
                $this->result["f_removed_comment"] = $params->remove_reason;
            }
            $log[] = $this->LogRecord("dishes removed",  ["comment" => $params->dish_name . " (" . $params->new_qty . ") " . $dish_states["names"][$params->new_state], "important" => true]);
        } else {
            $log[] = $this->LogRecord("dish quantity", ["comment" => $params->dish_name . " (" . $params->new_qty . ") " . $dish_states["names"][$params->new_state]]);
        }
        $v = [];
        $v["f_qty"] = $params->new_qty;
        $v["f_state"] = $params->new_state;
        $v["f_data"] = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($params->remove_emarks ?? false) {
            $v["f_emarks"] = null;
        }
        $this->update("o_goods", $v, $params->id);
        $this->CountAmounts($params->order_id, $log);
        $this->result["order"] = $this->GetOrder($params->order_id);
        $this->echoResult();
    }

    public function SetDishComment($params)
    {
        $row = $this->select("select * from o_goods where f_id=?", "s", [$params->id])->fetch_assoc();
        if (empty($row)) {
            dieWithCode("Are your hacker of row?");
        }
        $data = json_decode($row["f_data"] ?? "{}", true);
        $data["f_comment"] = $params->comment;
        $this->update("o_goods", ["f_data" => json_encode($data, JSON_UNESCAPED_UNICODE)], $params->id);
        $this->result["order"] = $this->GetOrder($row["f_header"]);
        $this->echoResult();
    }

    public function CreateOrder($params)
    {
        $this->beginTransaction();
        $sql = <<<EOD
        SELECT h.f_id, c.f_counter+1 as f_counter, h.f_counterhall,
        CONCAT(
            LEFT(h.f_prefix, LENGTH(h.f_prefix) - LENGTH(REGEXP_SUBSTR(h.f_prefix, '[0-9]+$'))),
            LPAD(
                c.f_counter + 1,
                LENGTH(REGEXP_SUBSTR(h.f_prefix, '[0-9]+$')),
                '0')
        ) AS f_prefix,
        if (t.f_special_config > 0, s2.f_config, s1.f_config) as f_config 
        FROM h_tables t
        LEFT JOIN h_halls h ON h.f_id = t.f_hall
        LEFT JOIN h_halls c ON c.f_id=h.f_counterhall
        left join sys_json_config s1 on s1.f_id=h.f_settings
        left join sys_json_config s2 on s2.f_id=t.f_special_config
        WHERE t.f_id=?
        for update
        EOD;
        $hall = $this->select($sql, "i", [$params->table])->fetch_assoc();
        $data = json_decode($hall["f_config"] ?? "{}", true);
        $service_factor = $service_factor = !empty($params->service_factor) ? $params->service_factor : ($data["f_service_factor"] ?? 0);

        $discount_factor = $params->discount_factor ?? 0;
        if (empty($hall)) {
            $this->rollback();
            dieWithCode("Oh, why tell me wrong id of table?");
        }
        $nv["f_counter"] = $hall["f_counter"];
        $this->update("h_halls", $nv, $hall["f_counterhall"]);
        $this->commit();

        $id = uuid_v4();
        $v["f_id"] = $id;
        $v["f_prefix"] = $hall["f_prefix"];
        $v["f_state"] = 1;
        $v["f_hall"] = $hall["f_id"];
        $v["f_table"] = $params->table;
        $v["f_datecash"] = date("Y-m-d");
        $v["f_timeopen"] = date("H:i:s");
        $v["f_staff"] = $this->userid;
        $v["f_cashier"] = $this->userid;
        $v["f_currentstaff"] = $this->userid;
        $v["f_data"] = json_encode([
            "f_service_factor" => $service_factor,
            "f_discount_factor" => $discount_factor,
            "f_date_open" => date("Y-m-d"),
            "f_time_open" => date("H:i:s"),
            "log" => [
                $this->LogRecord("created")
            ]
        ], JSON_UNESCAPED_UNICODE);
        $this->insert("o_header", $v);
        return $this->GetHeader($id);
    }

    public function CountAmounts($id, $extraLog = [])
    {
        $row = $this->select("select f_data from o_header where f_id=?", "s", [$id])->fetch_assoc();
        $odata = json_decode($row["f_data"] ?? "{}", true) ?: [];
        $odata["log"] ??= [];

        $dishes = $this->select("select * from o_goods where f_header=? and f_state=1", "s", [$id])->fetch_all(MYSQLI_ASSOC);

        $subtotal = $totaldue = $serviceCounted = $discountCounted = 0;

        foreach ($dishes as $d) {
            $ddata = json_decode($d["f_data"] ?? "{}", true) ?: [];

            if (($ddata["f_complimentary"] ?? 0) == 1) continue;
            if (!($ddata["f_printed"] ?? false)) continue;

            $priceModificator = 0;

            if (($ddata["f_count_service"] ?? false)) {
                $ddata["f_service_factor"] = $odata["f_service_factor"] ?? 0;
                $priceModificator += $ddata["f_service_factor"];
                $serviceCounted += $d["f_price"] * $ddata["f_service_factor"] * $d["f_qty"];
            }

            if (($ddata["f_count_discount"] ?? false)) {
                $ddata["f_discount_factor"] = $odata["f_discount_factor"] ?? 0;
                $priceModificator -= $ddata["f_discount_factor"];
                $discountCounted += $d["f_price"] * $ddata["f_discount_factor"] * $d["f_qty"];
            }

            $subtotal += $d["f_price"] * $d["f_qty"];
            $price = $d["f_price"] * (1 + $priceModificator);
            $totaldue += $d["f_qty"] * $price;

            $this->update("o_goods", [
                "f_total" => $d["f_qty"] * $price,
                "f_data"  => json_encode($ddata, JSON_UNESCAPED_UNICODE)
            ], $d["f_id"]);
        }

        $odata["f_sub_total"] = $subtotal;
        $odata["f_service_amount"] = $serviceCounted;
        $odata["f_discount_amount"] = $discountCounted;

        if (!empty($extraLog)) {
            if (array_keys($extraLog) !== range(0, count($extraLog) - 1)) {
                $extraLog = [$extraLog];
            }
            $odata["log"] = array_merge($odata["log"], $extraLog);
        }

        $this->update("o_header", [
            "f_amounttotal" => $totaldue,
            "f_data" => json_encode($odata, JSON_UNESCAPED_UNICODE)
        ], $id);
    }


    public function SetAmount($params)
    {
        $oheader = $this->GetHeader($params->id);
        $odata = json_decode($oheader["f_data"] ?? "{}", true);
        $odata[$params->payment_field] = $params->amount;
        $odata["f_amount_paid"] =
            ($odata["f_amount_cash"] ?? 0)
            + ($odata["f_amount_card"] ?? 0)
            + ($odata["f_amount_bank"] ?? 0)
            + ($odata["f_amount_idram"] ?? 0)
            + ($odata["f_amount_complimentary"] ?? 0);

        if ($odata["f_amount_paid"] > $oheader["f_amounttotal"]) {
            $odata["f_amount_change"] = $odata["f_amount_paid"] - $oheader["f_amounttotal"];
        }
        $this->update("o_header", ["f_data" => json_encode($odata, JSON_UNESCAPED_UNICODE)], $params->id);
        $this->result["order"] = $this->GetOrder($params->id);
        $this->echoResult();
    }

    public function GetOpenHeader($table)
    {
        $sql = <<<EOD
        select oh.*, coalesce(oh.f_table, t.f_id) as f_table,  t.f_name as f_table_name, oh.f_data
        from h_tables t
        left join o_header oh on t.f_id=oh.f_table and  oh.f_state=1 
        where t.f_id=?
        EOD;
        return  $this->select($sql, "i", [$table])->fetch_assoc();
    }

    public function GetHeader($id)
    {
        $sql = <<<EOD
        select oh.f_id, oh.f_state, oh.f_prefix, oh.f_table, oh.f_amounttotal, oh.f_data,
        h.f_name as f_hall_name, t.f_name as f_table_name, oh.f_cash_session_id,
        oh.f_staff, concat(u1.f_last, ' ', left(u1.f_first, 1) , '.') as f_staff_name,
        oh.f_cashier, concat(u2.f_last, ' ', left(u2.f_first, 1), '.') as f_cashier_name
        from o_header oh
        left join h_tables t on t.f_id=oh.f_table 
        left join h_halls h on h.f_id=t.f_hall
        left join s_user u1 on u1.f_id = oh.f_staff
        left join s_user u2 on u2.f_id = oh.f_cashier
        where oh.f_id=?
        EOD;
        $oheader = $this->select($sql, "s", [$id])->fetch_assoc();
        return $oheader;
    }

    public function GetOrder($id)
    {
        $oheader = $this->GetHeader($id);
        $oheader["dishes"] = $this->GetDishes($oheader["f_id"]);
        return $oheader;
    }

    public function GetDishes($header)
    {
        $sql = <<<EOD
        select og.f_id, og.f_state, og.f_type, og.f_parent, og.f_emarks, og.f_data,
        og.f_row, og.f_qty, og.f_price, og.f_store,
        og.f_goods as f_dish, g.f_name as f_dish_name,
        og.f_header
        from o_goods og 
        left join c_goods g on g.f_id=og.f_goods
        where og.f_header=?
        order by og.f_row
        EOD;
        return $this->select($sql, "s", [$header])->fetch_all(MYSQLI_ASSOC);
    }

    public function GetHeaderData($id)
    {
        $row = $this->select("select f_data from o_header where f_id=?", "s", [$id])->fetch_assoc();
        if (!$row) {
            dieWithCode("Do you want to hack get header data?");
        }
        $odata = json_decode($row["f_data"] ?? "{}", true);
        return $odata;
    }

    private function TryLock($table, $locksrc)
    {
        $this->beginTransaction();
        $lc = $this->select("select f_locksrc, f_name from h_tables where f_id=? for update", "i", [$table])->fetch_assoc();
        if (!empty($locksrc) && !empty($lc["f_locksrc"]) && $lc["f_locksrc"] !== $locksrc) {
            $this->rollback();
            dieWithCode(Translator::t("Table locked by ") . $lc["f_locksrc"]);
        }
        $v["f_locksrc"] = $locksrc;
        $this->update("h_tables", $v, $table);
        $this->commit();
        return $lc;
    }

    public function OpenTable($params)
    {
        $this->TryLock($params->table, $params->locksrc);
        $oheader = $this->GetOpenHeader($params->table);
        if (empty($oheader["f_id"])) {
            if (!empty($params->create_empty)) {
                $oheader = $this->CreateOrder($params);
            }
        } else {
            $oheader["dishes"] = $this->GetDishes($oheader["f_id"]);
            $data = json_decode($oheader["f_data"], true);
            $this->AppendLog("opened for edit", $data);
            $this->update("o_header", ["f_data" => json_encode($data, JSON_UNESCAPED_UNICODE)], $oheader["f_id"]);
        }
        $this->result["order"] = $oheader;
        $this->echoResult();
    }

    public function CloseOrder($params)
    {
        $payment = require_once __DIR__ . "/../worker/dict-payment.php";
        require_once __DIR__ . "/cashbox.php";
        $cc = new Cashbox();
        $cash_session = $cc->GetOpenedCashboxSessionId($params->cashbox_id ?? 0);
        if (!$cash_session) {
            dieWithCode("Cashbox session is empty");
        }
        $row = $this->select("select * from o_header where f_id=?", "s", [$params->id])->fetch_assoc();
        if (!$row) {
            dieWithCode("Are you hacker of close order row?");
        }
        $odata = json_decode($row["f_data"] ?? "{}", true);
        $total = 0;
        foreach ($payment["types"] as $pt) {
            $pn = $payment["fields"][$pt];
            if (($odata[$pn] ?? 0) > 0) {
                $total +=  $odata[$pn];
                $this->insert("cash_operations", [
                    "f_session_id" => $cash_session,
                    "f_order_id" => $params->id,
                    "f_user" => $this->userid,
                    "f_operation_type" => CASHBOX_IN,
                    "f_payment_type_id" => $pt,
                    "f_datetime" => date("Y-m-d H:i:s"),
                    "f_amount" => $odata[$pn]
                ]);
            }
        }

        $this->select("update cash_session set f_amount_expected=f_amount_expected+? where f_id=?", "di", [$total, $cash_session], true);

        $odata["f_date_close"] = date("Y-m-d");
        $odata["f_time_close"] = date("H:i:s");
        $odata["f_fiscal"] = $params->fiscal->out ?? [];
        $this->AppendLog("order closed", $odata);
        if (!empty($params->fiscal->in)) {
            require_once __DIR__ . "/../common/fiscal.php";
            $fiscallog = new Fiscal();
            $fiscallog->InsertLog((object) array_merge((array) $params->fiscal, ["f_id" => uuid_v4(), "f_order" => $params->id,  "err" => $params->fiscal->err ?? ""]));
        }
        $v["f_state"] = 2;
        $v["f_datecash"] = date("Y-m-d");
        $v["f_data"] = json_encode($odata, JSON_UNESCAPED_UNICODE);
        $v["f_cashier"]  = $this->userid;
        $v["f_staff"] = $this->userid;
        $v["f_cash_session_id"] = $cash_session;
        $this->update("o_header", $v, $params->id);
        $this->result["order"] = $this->GetOrder($params->id);
        $this->result["order"]["precheck_dishes"] = $this->GetPrecheckDishes($params->id);
        $this->echoResult();
    }

    public function UnlockTable($params)
    {
        if (!empty($params->id)) {
            if ($params->empty_order) {
                $v["f_state"] = ORDER_STATE_EMPTY;
                $this->update("o_header", $v, $params->id);
            }
        }
        $this->select("update h_tables set f_locksrc=null where f_locksrc=?", "s", [$params->locksrc], true);
        if (!empty($params->id)) {
            $header = $this->GetHeader($params->id);
            $data = json_decode($header["f_data"], true);
            $this->AppendLog("table closed", $data);
            $this->update("o_header", ["f_data" => json_encode($data, JSON_UNESCAPED_UNICODE)], $params->id);
        }
        $this->echoResult();
    }

    public function PrintServiceCheck($params)
    {
        $header = $this->GetHeader($params->header_id);
        if (empty($header)) {
            dieWithCode(Translator::t("Order not exists"));
        }
        $this->beginTransaction();
        $sql = <<<EOD
        select og.f_id, g.f_name as f_dish_name, og.f_qty, 
        coalesce(json_value(og.f_data, '$.f_printed'), false) as f_printed,
        json_value(og.f_data, '$.f_print1') as f_print1, 
        json_value(og.f_data ,'$.f_print2') as f_print2,
        json_value(og.f_data, '$.f_comment') as f_comment,
        og.f_data
        from o_goods og
        left join c_goods g on g.f_id=og.f_goods
        where og.f_header=? 
        and og.f_state=1 and og.f_type=1
        order by og.f_row
        for update
        EOD;
        $print_data = $this->select($sql, "s", [$params->header_id])->fetch_all(MYSQLI_ASSOC);
        $result = [];
        $printed = [];
        $log = [];
        foreach ($print_data as $pd) {
            if (!empty($pd["f_printed"]) && empty($params->reprint ?? false)) {
                continue;
            }
            $data = json_decode($pd["f_data"] ?? "{}", true);
            if (!empty($pd["f_print1"])) {
                if (empty($result[$pd["f_print1"]])) {
                    $result[$pd["f_print1"]] = [
                        "dishes" => [],
                        "printer" => $pd["f_print1"],
                        "side" => "[1]",
                    ];
                }
                $result[$pd["f_print1"]]["dishes"][] = [...$pd];
                $log[] = $this->LogRecord("printed service #1 on " . $pd["f_print1"], ["comment" => $pd["f_dish_name"] . " (" . $pd["f_qty"] . ") "]);
            }

            if (!empty($pd["f_print2"])) {
                if (empty($result[$pd["f_print2"]])) {
                    $result[$pd["f_print2"]] = [
                        "dishes" => [],
                        "printer" => $pd["f_print2"],
                        "side" => "[2]",
                    ];
                }
                $result[$pd["f_print2"]]["dishes"][] = [...$pd];
                $log[] = $this->LogRecord("printed service #2 on " . $pd["f_print2"], ["comment" => $pd["f_dish_name"] . " (" . $pd["f_qty"] . ") "]);
            }
            $printed[] = $pd["f_id"];
        }
        $this->result["printed"] = $printed;
        foreach ($printed as $p) {
            $data["f_printed"] = true;
            $data["f_print_time"] = date("Y-m-d H:i:s");
            $this->update("o_goods", ["f_data" => json_encode($data, JSON_UNESCAPED_UNICODE)], $p);
        }
        $this->commit();
        $this->CountAmounts($params->header_id, $log);
        $this->result["reprint"] = $params->reprint ?? false;
        $this->result["header"] = $header;
        $this->result["order"] = $this->GetOrder($params->header_id);
        $this->result["print_data"] = $result;
        $this->echoResult();
    }

    private function GetPrecheckDishes($id)
    {
        $sql = <<<EOD
        select og.f_state,og.f_parent, og.f_emarks, og.f_data,
        og.f_row, sum(og.f_qty)as f_qty, og.f_price, og.f_store,
        og.f_goods as f_dish, g.f_name as f_dish_name
        from o_goods og 
        left join c_goods g on g.f_id=og.f_goods
        where og.f_header=? AND og.f_state=1 AND og.f_parent IS null
        group by og.f_goods, og.f_state, og.f_price
        order by og.f_row
        EOD;
        return $this->select($sql, "s", [$id])->fetch_all(MYSQLI_ASSOC);
    }
    public function FiscalPrinted($params)
    {
        $header = $this->select("select * from o_header where f_id=?", "s", [$params->id])->fetch_assoc();
        if (!$header) {
            dieWithCode("Do you try to hack fiscal printed?");
        }
        $odata = json_decode($header["f_data"] ?? "{}", true);
        $odata["f_fiscal"] = $params->fiscal ?? [];
        $this->update("o_header", ["f_data" => json_encode($odata, JSON_UNESCAPED_UNICODE)], $params->id);
        if (!empty($params->fiscal)) {
            $this->FiscalLog((object)[
                "in" => $params->fiscal->in,
                "out" => $params->fiscal->out,
                "error" => $params->fiscal->error,
                "result" => $params->fiscal->result,
                "id" => $params->id
            ], true);
        }
        $this->result["order"] = $this->GetOrder($params->id);
        $this->echoResult();
    }

    public function PrintPrecheck($params)
    {
        /* THIS IS TEMPORARY UNTILL FIND BUG WITHY INCORECT COUNTING */
        $this->CountAmounts($params->id);

        $row = $this->select("select f_data from o_header where f_id=?", "s", [$params->id])->fetch_assoc();;
        if (!$row) {
            dieWithCode(Translator::t("Wrong order id"));
        }
        $this->CountAmounts($params->id, []);
        $data = json_decode($row["f_data"] ?? "{}", true);
        $precheck = abs(($data["f_precheck"] ?? 0)) + 1;
        $printcount = ($data["f_print_count"] ?? 0) + 1;
        $data["f_precheck"] = $precheck;
        $data["f_print_count"] = $printcount;
        $this->AppendLog("print precheck", $data);
        $this->update("o_header", ["f_data" => json_encode($data, JSON_UNESCAPED_UNICODE)], $params->id);
        $this->result["order"] = $this->GetOrder($params->id);

        $this->result["order"]["precheck_dishes"] = $this->GetPrecheckDishes($params->id);
        $this->echoResult();
    }

    public function CancelPrecheck($params)
    {
        $row = $this->select("select f_data from o_header where f_id=?", "s", [$params->id])->fetch_assoc();
        if (!$row) {
            dieWithCode(Translator::t("Wrong order id"));
        }
        $data = json_decode($row["f_data"] ?? "{}", true);

        $precheck = ($data["f_precheck"] ?? 0) * -1;
        $data["f_precheck"] = $precheck;
        $this->AppendLog("cancel precheck", $data);
        $this->update("o_header", ["f_data" => json_encode($data, JSON_UNESCAPED_UNICODE)], $params->id);
        $this->result["order"] = $this->GetOrder($params->id);
        $this->echoResult();
    }

    private function LogRecord($action, array $extra = [])
    {
        $record = array_merge(["ts" => date("Y-m-d H:i:s"), "host" => $this->remote_host, "action" => $action, "user" => $this->fullName()], $extra);
        return $record;
    }

    private function AppendLog($action, &$log, array $extra = [])
    {
        $log["log"][] = $this->LogRecord($action, $extra);
    }

    public function FiscalLog($params, $noecho = false)
    {
        $odata = json_decode($this->select("select f_data from o_header where f_id=?", "s", [$params->id])->fetch_assoc()["f_data"] ??  "{}", true);
        $this->AppendLog("fiscal fail", $odata, ["comment" => $params->error]);
        $this->update("o_header", ["f_data" => json_encode($odata, JSON_UNESCAPED_UNICODE)], $params->id);
        $v["f_id"] = uuid_v4();
        $v["f_order"] = $params->id;
        $v["f_date"] = date("Y-m-d");
        $v["f_time"] = date("H:i:s");
        $v["f_elapsed"] = $params->elapsed ?? 0;
        $v["f_in"] = $params->in;
        $v["f_out"] = $params->out;
        $v["f_err"] = $params->error;
        $v["f_result"] = $params->result;
        $v["f_state"] = $params->result === 0 ? 1 : 0;
        $this->insert("o_tax_log", $v);
        if (!$noecho) {
            $this->echoResult();
        }
    }

    public function GetServiceValues($params)
    {
        $this->result["values"] = $this->select("select f_value, CONCAT(
        TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM f_value * 100)),
        '%'
    ) as f_name from o_service_values order by f_value ")->fetch_all(MYSQLI_ASSOC);
        $this->echoResult();
    }

    public function ChangeServiceValue($params)
    {
        $oheader = $this->GetHeader($params->id);
        $odata = json_decode($oheader["f_data"], true);
        $odata["f_service_factor"] = $params->value;
        $this->update("o_header", ["f_data" => json_encode($odata, JSON_UNESCAPED_UNICODE)], $params->id);
        $dishes = $this->GetDishes($params->id);
        foreach ($dishes as $d) {
            $v = [];
            $ddata = json_decode($d["f_data"] ?? "{}");
            if (!$ddata) {
                $ddata = (object)[];
            }
            if ($ddata->f_complimentary ?? 0 == 1) {
                continue;
            }
            if (($ddata->f_count_service ?? false) === true) {
                $ddata->f_service_factor = $params->value;
            }

            $v["f_data"]  = json_encode($ddata, JSON_UNESCAPED_UNICODE);
            $this->update("o_goods", $v, $d["f_id"]);
        }
        $this->CountAmounts($params->id, $this->LogRecord("service value", ["comment" => $params->value * 100 . "%"]));
        $this->result["order"] = $this->GetOrder($params->id);
        $this->echoResult();
    }

    public function RemoveArrayOfDishes($params)
    {
        $dishes = [];
        foreach ($params->dishes as $d) {
            $data = (array)$d->f_data;
            if ($d->f_state == 2 || $d->f_state == 3) {
                $d->print1 = $data["f_print1"] ?? "";
                $d->print2 = $data["f_print2"] ?? "";
                $d->f_store_out = $d->f_state == 3;
                $d->f_removed_qty = $d->f_qty;
                $d->f_removed_dish_name = $d->dishName;
                if (!empty($d->remove_reason)) {
                    $data["f_remove_reason"] = $d->remove_reason;
                    $d->f_remove_reason = $d->remove_reason;
                    $data["f_removed_time"] = date("y-M-d H:i:s");
                }
            }
            $v = [];
            $v["f_qty"] = $d->f_qty;
            $v["f_state"] = $d->f_state;
            $v["f_data"] = json_encode($data, JSON_UNESCAPED_UNICODE);
            $v["f_emarks"] = null;
            $dishes[] = $v;
            $this->update("o_goods", $v, $d->f_id);
            $d->f_data = json_encode((array)$data, JSON_UNESCAPED_UNICODE);
            $dishes[] = $d;
        }
        $this->result["removed_dishes"] = $dishes;
        $this->CountAmounts($params->order_id);
        $this->result["order"] = $this->GetOrder($params->order_id);
        $this->echoResult();
    }

    public function TransferTable($params)
    {
        $dish_states = require_once __DIR__ . "/../worker/dict-dish-state.php";
        $table = $this->TryLock($params->destination, $params->locksrc);

        $odst = $this->select("select f_id, f_data from o_header where f_table=? and f_state=1", "i", [$params->destination])->fetch_assoc();
        $src_dishes = $this->GetDishes($params->id);
        if (!$src_dishes) {
            dieWithCode("Are you src dish hacker?");
        }
        if (empty($odst)) {
            /* just move */
            $odata = $this->select("select f_data from o_header where f_id=?", "s", [$params->id])->fetch_assoc();
            $odata = json_decode($odata["f_data"] ?? "{}", true);
            $this->AppendLog("transfer table", $odata, ["comment" => $params->source_table_name . " => " . $params->destination_table_name, "important" => true]);
            $v = ["f_table" => $params->destination, "f_data" => json_encode($odata, JSON_UNESCAPED_UNICODE)];
            $this->update("o_header", $v, $params->id);
        } else {
            /* merge */
            $source_data = $this->GetHeaderData($params->id);
            $this->beginTransaction();
            foreach ($src_dishes as $d) {
                if ($d["f_state"] != DISH_STATE_NORMAL) {
                    continue;
                }
                $v = [];
                $ddata = json_decode($d["f_data"] ?? "{}", true);
                $ddata["f_from_table"] = $table["f_name"];
                $this->AppendLog("transfer items", $source_data, ["comment" => $d["f_dish_name"] . " (" . $d["f_qty"] . ") " . $params->source_table_name . " => " . $params->destination_table_name, "important" => true]);
                $v["f_header"] = $odst["f_id"];
                $v["f_data"] = json_encode($ddata, JSON_UNESCAPED_UNICODE);
                $this->update("o_goods", $v, $d["f_id"]);
            }
            $this->update("o_header", ["f_data" => json_encode($source_data, JSON_UNESCAPED_UNICODE)], $params->id);
            $this->commit();
        }
        $this->result["order"] = $this->GetOrder($params->id);
        $this->echoResult();
    }

    public function TransferItems($params)
    {
        $odata = $this->GetHeaderData($params->id1);
        foreach ($params->data as $d) {
            $v = [];
            $v["f_header"] = $d->f_header;
            $dish = $this->select("select f_data from o_goods where f_id=?", "s", [$d->f_id])->fetch_assoc();
            $ddata = json_decode($dish["f_data"] ?? "{}", true);
            $ddata["f_from_table"] = $d->f_from_table;
            $this->AppendLog("transfer items", $odata, ["comment" => $d->f_dish_name . " (" . $d->f_qty . ") " . $params->source_table_name . " => " . $params->destination_table_name]);
            $v["f_data"] = json_encode($ddata, JSON_UNESCAPED_UNICODE);
            $this->update("o_goods", $v, $d->f_id);
        }
        $this->CountAmounts($params->id1);
        $this->CountAmounts($params->id2);
        $this->update("o_header", ["f_data" => json_encode($odata, JSON_UNESCAPED_UNICODE)], $params->id1);
        $this->echoResult();
    }

    public function SetHeaderComment($params)
    {
        $oheader = $this->select("select f_data from o_header where f_id=?", "s", [$params->id])->fetch_assoc();
        $odata = json_decode($oheader["f_data"], true);
        $odata["f_comment"] = $params->comment;
        $v["f_data"] = json_encode($odata, JSON_UNESCAPED_UNICODE);
        $this->update("o_header", $v, $params->id);
        $this->result["order"] = $this->GetOrder($params->id);
        $this->echoResult();
    }

    public function ComplimentaryItems($params)
    {
        foreach ($params->items ?? [] as $i) {
            $row = $this->select("select f_data from o_goods where f_id=?", "s", [$i->f_id])->fetch_assoc();
            $data = json_decode($row["f_data"] ?? "{}", true);
            $data["f_complimentary"] = true;
            $v = [];
            $v["f_data"] = json_encode($data, JSON_UNESCAPED_UNICODE);
            $this->update("o_goods", $v, $i->f_id);
        }

        $this->CountAmounts($params->id);
        $this->result["order"] = $this->GetOrder($params->id);
        $this->echoResult();
    }
}
