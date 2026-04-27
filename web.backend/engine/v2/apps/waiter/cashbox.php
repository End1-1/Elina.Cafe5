<?php
# © 2026 , Kudryashov Vasili
# Created: 2026-01-17 16:06:01
# Last Modified: 2026-01-17 16:06:04
require_once __DIR__ . "/index.php";

class Cashbox extends Auth
{
    public function GetOpenedCashboxSessionId($cashbox_id)
    {
        $cashbox_session = $this->select("select f_id from cash_session where f_state=1 and f_cashbox_id=?", "i", [$cashbox_id])->fetch_assoc();
        return $cashbox_session ? $cashbox_session["f_id"] : 0;
    }

    public function GetRawCashboxSession($cashbox_session_id)
    {
        return $this->select("select * from cash_session where f_id=?", "i", [$cashbox_session_id])->fetch_assoc();
    }
    public function GetCashboxSession($cashbox_session_id)
    {
        $sql = <<<EOD
        SELECT c.f_id, c.f_date_open, money_fmt(c.f_amount_expected) as f_amount_expected,
        concat(u1.f_last, ' ', LEFT(u1.f_first, 1), '.') AS f_user_open_name, 
        count(distinct case when o.f_operation_type = 1 then o.f_order_id end) as f_orders_count
        FROM cash_session c
        LEFT JOIN s_user u1 ON u1.f_id=c.f_user_open
        left join cash_operations o on o.f_session_id=c.f_id
        where c.f_id=?
        limit 1
        EOD;

        return $this->select($sql, "i", [$cashbox_session_id])->fetch_assoc();
    }

    public function GetOpenCashboxSession($cashbox_id)
    {
        $id = $this->GetOpenedCashboxSessionId($cashbox_id);
        if (!$id) {
            return null;
        }

        return $this->GetCashboxSession($id);
    }
    public function CheckStatus($params)
    {
        if (empty($params->cashbox_id)) {
            dieWithCode("cashbox_id not specified");
        }
        $cashbox = $this->GetOpenCashboxSession($params->cashbox_id);
        $this->result["cashbox_session_id"] = $cashbox ? $cashbox["f_id"]  : 0;
        $this->result["cashbox_session"] = $cashbox ?? [];
        $this->echoResult();
    }
    public function Open($params)
    {
        $cashbox = $this->GetOpenCashboxSession($params->cashbox_id);
        if (!$cashbox) {
            $this->insert("cash_session", [
                "f_state" => 1,
                "f_cashbox_id" => $params->cashbox_id,
                "f_user_open" => $this->userid,
                "f_date_open" => date("Y-m-d H:i:s"),
                "f_amount_open" => $params->amount_open,
                "f_amount_fact" => 0,
                "f_amount_expected" => 0,
                "f_amount_difference" => 0
            ]);

            $cashbox = $this->GetOpenCashboxSession($params->cashbox_id);
        }
        $this->result["cashbox_session"] = $cashbox;
        $this->echoResult();
    }

    public function Close($params)
    {
        $id = $this->GetOpenedCashboxSessionId($params->cashbox_id);
        if (!$id) {
            die(Translator::t("No active session"));
        }
        $cashbox = $this->GetRawCashboxSession($id);
        if (!$cashbox) {
            dieWithCode("Do you want to hack close function of cashbox?");
        }
        $cashbox["f_state"] = 2;
        $cashbox["f_date_close"] = date("Y-m-d H:i:s");
        $cashbox["f_user_close"] = $this->userid;
        $cashbox["f_amount_fact"] = $params->amount_fact ?? 0;
        $cashbox["f_amount_difference"] = ($params->amount_fact ?? 0) - $cashbox["f_amount_expected"];
        $this->update("cash_session", $cashbox, $cashbox["f_id"]);
        $this->result["cashbox"] = $this->GetCashboxSession($id);
        $this->echoResult();
    }
}
