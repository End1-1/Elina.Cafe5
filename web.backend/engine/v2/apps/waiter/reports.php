<?php
# © 2025 , Kudryashov Vasili
# Created: 2026-01-08 10:47:25
# Last Modified: 2026-01-18 16:32:36

require_once __DIR__ . "/index.php";

class Reports extends Auth
{
    public function GetOrders($params)
    {
        $sql = <<<EOD
        select oh.f_id, oh.f_prefix, h.f_name as f_hall_name, t.f_name as f_table_name,
        date_fmt(json_value(oh.f_data, '$.f_date_close')) as f_date_open,
        json_value(oh.f_data, '$.f_time_open') as f_time_open,
        json_value(oh.f_data, '$.f_date_close') as f_date_close,
        json_value(oh.f_data, '$.f_time_close') as f_time_close,
        concat(u.f_last, ' ', left(u.f_first, 1), '.') as f_staff_name,
        money_fmt(oh.f_amounttotal) as f_amount_total, json_value(oh.f_data, '$.rseq') as f_fiscal
        from o_header oh 
        left join h_tables t on t.f_id=oh.f_table
        left join h_halls h on h.f_id=t.f_hall
        left join s_user u on u.f_id=oh.f_staff
        where oh.f_state=2 and oh.f_datecash between ? and ?
        order by  json_value(oh.f_data, '$.f_date_close'), json_value(oh.f_data, '$.f_time_close') 
        EOD;
        $this->result["orders"] = $this->select($sql, "ss", [$params->date1, $params->date2])->fetch_all(MYSQLI_ASSOC);
        $sql = <<<EOD
        select money_fmt(sum(oh.f_amounttotal)) as f_amount_total
        from o_header oh
        where oh.f_state=2 and oh.f_datecash between ? and ?
        EOD;
        $this->result["total"] = $this->select($sql, "ss", [$params->date1, $params->date2])->fetch_row()[0];
        $this->echoResult();
    }

    public function GetDaily($params)
    {
        $sql = <<<EOD
        SELECT COUNT(f_id) AS f_count_id, SUM(f_amounttotal) AS f_amount_total,
        sum(JSON_VALUE(f_data, '$.f_amount_cash')) AS f_amount_cash,
        SUM(JSON_VALUE(f_data, '$.f_amount_card')) AS f_amount_card,
        SUM(JSON_VALUE(f_data, '$.f_amount_bank')) AS f_amount_bank,
        SUM(JSON_VALUE(f_data, '$.f_amount_idram')) AS f_amount_idram,
        SUM(coalesce(JSON_VALUE(f_data, '$.f_service_amount'), 0)) AS f_service_amount
        FROM o_header oh
        WHERE oh.f_datecash BETWEEN ? AND ?
        AND oh.f_state=2
        EOD;
        $this->result  = array_merge($this->select($sql, "ss", [$params->date1, $params->date2])->fetch_all(MYSQLI_ASSOC)[0], $this->result);
        $this->echoResult();
    }
    public function GetList($params)
    {
        $last_session_id = $this->select("select coalesce(max(f_id), 0) from cash_session")->fetch_row()[0];
        $list = [
            ["title" => "01. " . Translator::t("Daily revenue by shift"), "report_id" => 1, "params" => "last-30-sessions", "default_values" => [
                "session_id" => $last_session_id
            ]],
            ["title" => "02. " . Translator::t("Daily revenue by shift, by waiter"), "report_id" => 2, "params" => "last-30-sessions", "default_values" => [
                "session_id" => $last_session_id
            ]],
            ["title" => "03. " . Translator::t("Dish sales report by shift"), "report_id" => 3, "params" => "last-30-sessions", "default_values" => [
                "session_id" => $last_session_id
            ]],
        ];
        $this->result["list"] = $list;
        $this->echoResult();
    }

    public function GetReport($params)
    {
        $output = [];
        match ($params->report_id ?? 0) {
            1 => $output = $this->PrintDailyRevenue($params),
            2 => $output =  $this->PrintDailyRevenueByWaiters($params),
            3 => $output = $this->PrintDishesByShift($params),
            default => dieWithCode("Report id not exits " . $params->report_id ?? 0)
        };
        $this->result["printing"] = $output;
        $this->echoResult();
    }

    private function PrintDailyRevenue($params)
    {
        $cashbox = $this->select("select cb.f_name from cash_box cb left join cash_session cs on cs.f_cashbox_id=cb.f_id where cs.f_id=?", "i", [$params->params->session_id])->fetch_assoc();
        $dict = require_once __DIR__ . "/../worker/dict-payment.php";

        $paymentParts = [];

        foreach ($dict["fields"] as $type => $field) {
            $paymentParts[] =
                "money_fmt(SUM(CAST(json_value(oh.f_data, '$.$field') AS DECIMAL(12,2)))) AS $field";
        }

        $paymentSql = implode(",\n        ", $paymentParts);

        $sql = <<<EOD
        SELECT 
            COUNT(DISTINCT(oh.f_id)) AS f_orders_count,
            money_fmt(SUM(oh.f_amounttotal)) AS f_amount_total,
            $paymentSql
        FROM o_header oh
        LEFT JOIN cash_session cs ON cs.f_id = oh.f_cash_session_id
        WHERE oh.f_state = 2 and oh.f_cash_session_id=?
    EOD;
        $this->result["sql"] = $sql;
        $totals = $this->select($sql, "i", [$params->params->session_id])->fetch_assoc();

        $sql = <<<EOD
    select money_fmt(SUM(coalesce(JSON_VALUE(f_data, '$.f_service_amount'), 0))) AS f_service_amount,
    datetime_fmt(cs.f_date_open, 0) as f_date_open,
    datetime_fmt(cs.f_date_close, 0) as f_date_close
    from o_header oh 
    left join cash_session cs  on cs.f_id=oh.f_cash_session_id
    where oh.f_state=2 and oh.f_cash_session_id=?
    EOD;
        $oh = $this->select($sql, "i", [$params->params->session_id])->fetch_assoc();

        $output = [
            ["cmd" => "fontsize", "size" => 11],
            ["cmd" => "ctext", "text" => $params->report_name ?? "Undefined"],
            ["cmd" => "br"],
            ["cmd" => "br"],
            ["cmd" => "lrtext", "left" => Translator::t("Cashbox"), "right" => $cashbox["f_name"]],
            ["cmd" => "br"],
            ["cmd" => "lrtext", "left" => Translator::t("Shift"), "right" => "#" . $params->params->session_id],
            ["cmd" => "br"],
            ["cmd" => "lrtext", "left" => Translator::t(key: "Date open"), "right" =>  $oh["f_date_open"]],
            ["cmd" => "br"],
            ["cmd" => "lrtext", "left" => Translator::t("Date close"), "right" =>  $oh["f_date_close"]],
            ["cmd" => "br"],
            ["cmd" => "line2", "width" => 1],
            ["cmd" => "lrtext", "left" => "Orders:", "right" => "{$totals["f_orders_count"]}"],
            ["cmd" => "br"],
            ["cmd" => "lrtext", "left" => "Total:",  "right" => $totals["f_amount_total"]],
            ["cmd" => "line"],
            ["cmd" => "br"],
        ];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "line2", "width" => 1];

        $output[] = ["cmd" => "br"];
        foreach ($dict["fields"] as $type => $field) {
            $amount = $totals[$field] ?? "0";
            if ((float)$amount == 0) {
                continue;
            }
            $output[] = ["cmd" => "lrtext", "left" => Translator::t($dict["names"][$type]), "right" => $amount];
            $output[] = ["cmd" => "br"];
        }
        $output[] = ["cmd" => "lrtext", "left" => Translator::t("Amount of service"), "right" => $oh["f_service_amount"]];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "line2", "width" => 1];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "fontsize", "size" => 8];
        $output[] = ["cmd" => "rtext", "text" => date("Y-m-d H:i:s")];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "ltext", "text" => "."];

        return $output;
    }

    private function PrintDailyRevenueByWaiters($params)
    {
        $cashbox = $this->select("select cb.f_name from cash_box cb left join cash_session cs on cs.f_cashbox_id=cb.f_id where cs.f_id=?", "i", [$params->params->session_id])->fetch_assoc();
        $dict = require_once __DIR__ . "/../worker/dict-payment.php";

        $paymentParts = [];

        foreach ($dict["fields"] as $type => $field) {
            $paymentParts[] =
                "money_fmt(SUM(CAST(json_value(oh.f_data, '$.$field') AS DECIMAL(12,2)))) AS $field";
        }

        $paymentSql = implode(",\n        ", $paymentParts);

        $sql = <<<EOD
        SELECT 
            concat(u.f_last, ' ', left(u.f_first, 1), '.') as f_waiter,
            datetime_fmt(cs.f_date_open, 0) as f_date_open,
            datetime_fmt(cs.f_date_close, 0) as f_date_close,
            COUNT(DISTINCT(oh.f_id)) AS f_orders_count,
            money_fmt(SUM(oh.f_amounttotal)) AS f_amount_total,
            money_fmt(SUM(coalesce(JSON_VALUE(f_data, '$.f_service_amount'), 0))) AS f_service_amount,
            $paymentSql
        FROM o_header oh
        LEFT JOIN cash_session cs ON cs.f_id = oh.f_cash_session_id
        left join s_user u on u.f_id=oh.f_staff
        WHERE oh.f_state = 2 and oh.f_cash_session_id=?
        group by 1
        EOD;
        $totals = $this->select($sql, "i", [$params->params->session_id])->fetch_all(MYSQLI_ASSOC);


        $sql = <<<EOD
         SELECT 
            
            COUNT(DISTINCT(oh.f_id)) AS f_orders_count,
            money_fmt(SUM(oh.f_amounttotal)) AS f_amount_total,
            money_fmt(SUM(coalesce(JSON_VALUE(f_data, '$.f_service_amount'), 0))) AS f_service_amount,
            $paymentSql
        FROM o_header oh
        LEFT JOIN cash_session cs ON cs.f_id = oh.f_cash_session_id
        WHERE oh.f_state = 2 and oh.f_cash_session_id=?
        EOD;
        $finaly = $this->select($sql, "i", [$params->params->session_id])->fetch_assoc();

        $first = true;
        foreach ($totals as $h) {
            if ($first) {
                $first = false;
                $output = [
                    ["cmd" => "fontsize", "size" => 11],
                    ["cmd" => "ctext", "text" => $params->report_name ?? "Undefined"],
                    ["cmd" => "br"],
                    ["cmd" => "br"],
                    ["cmd" => "lrtext", "left" => Translator::t("Cashbox"), "right" => $cashbox["f_name"]],
                    ["cmd" => "br"],
                    ["cmd" => "lrtext", "left" => Translator::t("Shift"), "right" => "#" . $params->params->session_id],
                    ["cmd" => "br"],
                    ["cmd" => "lrtext", "left" => Translator::t(key: "Date open"), "right" =>  $h["f_date_open"]],
                    ["cmd" => "br"],
                    ["cmd" => "lrtext", "left" => Translator::t("Date close"), "right" =>  $h["f_date_close"]],
                    ["cmd" => "br"],
                    ["cmd" => "line2", "width" => 1],


                ];
                $output[] = ["cmd" => "br"];
            }
            $output[] = ["cmd" => "br"];
            $output[] = ["cmd" => "ltext", "text" => $h["f_waiter"]];
            $output[] = ["cmd" => "br"];
            $output[] = ["cmd" => "lrtext", "left" => Translator::t("Orders"), "right" => "{$h["f_orders_count"]}"];
            $output[] = ["cmd" => "br"];
            foreach ($dict["fields"] as $type => $field) {
                $amount = $h[$field] ?? "0";
                if ((float)$amount == 0) {
                    continue;
                }
                $output[] = ["cmd" => "lrtext", "left" => Translator::t($dict["names"][$type]), "right" => $amount];
                $output[] = ["cmd" => "br"];
            }
            $output[] = ["cmd" => "lrtext", "left" => Translator::t("Amount of service"), "right" => $h["f_service_amount"]];
            $output[] = ["cmd" => "br"];
            $output[] = ["cmd" => "line2", "width" => 1];
        }

        //TOTOAL
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "ltext", "text" => Translator::t("Total")];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "lrtext", "left" =>  Translator::t("Orders"), "right" => "{$finaly["f_orders_count"]}"];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "lrtext", "left" => Translator::t("Amount"),  "right" => $finaly["f_amount_total"]];
        $output[] = ["cmd" => "br"];
        foreach ($dict["fields"] as $type => $field) {
            $amount = $finaly[$field] ?? "0";
            if ((float)$amount == 0) {
                continue;
            }
            $output[] = ["cmd" => "lrtext", "left" => Translator::t($dict["names"][$type]), "right" => $amount];
            $output[] = ["cmd" => "br"];
        }
        $output[] = ["cmd" => "line"];
        $output[] = ["cmd" => "br"];


        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "fontsize", "size" => 8];
        $output[] = ["cmd" => "rtext", "text" => date("Y-m-d H:i:s")];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "br"];
        $output[] = ["cmd" => "ltext", "text" => "."];

        return $output;
    }

    public function PrintDishesByShift($params)
    {
        $sql = <<<EOD
        select cb.f_name, 
        datetime_fmt(cs.f_date_open, 0) as f_date_open,
        datetime_fmt(cs.f_date_close, 0) as f_date_close
        from cash_box cb 
        left join cash_session cs on cs.f_cashbox_id=cb.f_id 
        where cs.f_id=?
        EOD;
        $cashbox = $this->select($sql, "i", [$params->params->session_id])->fetch_assoc();
        $sql = <<<EOD
        select gr.f_name as f_group_name, g.f_name as f_goods_name,
        money_fmt(sum(og.f_qty)) as f_qty, 
        money_fmt(sum(og.f_total)) as f_amount_total
        from o_goods og
        left join o_header oh on oh.f_id=og.f_header
        left join c_goods g on g.f_id=og.f_goods
        left join c_groups gr on gr.f_id=g.f_group
        where oh.f_state=2 and og.f_state=1
        and oh.f_cash_session_id=?
        group by g.f_name
        order by gr.f_name
        EOD;
        $data = $this->select($sql, "i", [$params->params->session_id])->fetch_all(MYSQLI_ASSOC);
        $output = [
            ["cmd" => "fontsize", "size" => 11],
            ["cmd" => "ctext", "text" => $params->report_name ?? "Undefined"],
            ["cmd" => "br"],
            ["cmd" => "br"],
            ["cmd" => "lrtext", "left" => Translator::t("Cashbox"), "right" => $cashbox["f_name"]],
            ["cmd" => "br"],
            ["cmd" => "lrtext", "left" => Translator::t("Shift"), "right" => "#" . $params->params->session_id],
            ["cmd" => "br"],
            ["cmd" => "lrtext", "left" => Translator::t(key: "Date open"), "right" =>  $cashbox["f_date_open"]],
            ["cmd" => "br"],
            ["cmd" => "lrtext", "left" => Translator::t("Date close"), "right" =>  $cashbox["f_date_close"]],
            ["cmd" => "br"],
            ["cmd" => "line2", "width" => 1],
        ];
        $group_name = "";
        foreach ($data as $d) {
            if ($group_name != $d["f_group_name"]) {
                $group_name = $d["f_group_name"];
                $output[] = ["cmd" => "ctext", "text" => $group_name];
                $output[] =   ["cmd" => "br"];
            }
            $output[] =  ["cmd" => "lrtext", "left" => $d["f_goods_name"], "right" => $d["f_qty"]];
            $output[] = ["cmd" => "br"];
        }
        return $output;
    }

    public function Last30Sessions($params)
    {
        $sql = <<<EOD
        SELECT 
            c.f_id, rpad(cb.f_name, 20, ' ') as f_name,
            datetime_fmt(c.f_date_open, 0)  AS f_date_open,
            datetime_fmt(c.f_date_close, 0) AS f_date_close,
            money_fmt(c.f_amount_expected)  AS f_amount_expected,
            CONCAT(u1.f_last, ' ', LEFT(u1.f_first, 1), '.') AS f_user_open_name,
            COUNT(DISTINCT CASE WHEN o.f_operation_type = 1 THEN o.f_order_id END) AS f_orders_count
        FROM cash_session c
        LEFT JOIN s_user u1 ON u1.f_id = c.f_user_open
        LEFT JOIN cash_operations o ON o.f_session_id = c.f_id
        left join cash_box cb on cb.f_id=c.f_cashbox_id
        GROUP BY c.f_id
        ORDER BY c.f_id DESC
        LIMIT 30;
        EOD;
        $data = $this->select($sql)->fetch_all(MYSQLI_ASSOC);
        $list = [];
        foreach ($data as $d) {
            $list[] = [
                "text" => "#" . str_pad($d["f_id"], 6) . "  " . $d["f_name"]
                    . Translator::t("Date open") . " " . $d["f_date_open"] . "   "
                    . Translator::t("Date close") . " " . $d["f_date_close"] . "   "
                    . Translator::t("Amount")  . " " . $d["f_amount_expected"],
                "value" => $d["f_id"]
            ];
        }
        $this->result["values"] = $list;
        $this->echoResult();
    }
}
