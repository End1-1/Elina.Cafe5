<?php

# © 2025 , Kudryashov Vasili

# Created: 2025-11-27 09:51:33

# Last Modified: 2025-11-27 09:51:35

require_once __DIR__ . "/index.php";



$otp_url = "https://sendsms.nikita.am/broker-api/send";

$otp_login = "homefood";

$otp_pass = "Gu5K%NL2";

$otp_ordinator = "HomeFood.am";

$online_order_notify_phone = "+37433111777";

$online_order_hall = 1;



class Api extends Auth

{

    private ?int $onlineLogId = null;

    private array $onlineLog = ["status" => "pending", "steps" => []];



    public function stock($params)

    {

        $where = "where st.f_store in (2,3,5,24) ";

        if (!empty($params->sku)) {

            $skuList = $params->sku;

            $skuList = array_filter($skuList, fn($x) => is_string($x) && preg_match('/^\d+$/', $x));

            $quoted = array_map(fn($x) => "'$x'", $skuList);

            $final = implode(',', $quoted);

            $where .= "AND g.f_scancode IN ($final)";

            # $this->result["sku"] = $final;

        }

        $sql = <<<EOD

        select g.f_scancode as sku, st.f_store as store, sum(st.f_qty*st.f_type) as qty,

        cp.f_price1 as price1, cp.f_price1disc as price1disc,

        g.f_name as name, gr.f_name as groupname

        from a_store st

        left join c_goods g on  g.f_id=st.f_goods

        left join c_goods_prices cp on cp.f_goods=st.f_goods and cp.f_currency=1

        left join c_groups gr on gr.f_id=g.f_group

        $where

        group by 1,2

        having sum(st.f_qty*st.f_type)>0

        EOD;

        #$this->result["sql"] = $sql;

        $this->result["data"] = $this->select($sql)->fetch_all(MYSQLI_ASSOC);



        $this->echoResult();

    }



    public function ReserveCreate($params)

    {

        $this->echoResult();

    }



    public function ReserveCancel($params)

    {

        $this->echoResult();

    }



    public function PurchaseCreate($params)

    {

        $this->beginOnlineLog($params);

        $this->onlineLogStep("request_received");



        if (empty($params->items)) {

            $this->onlineLogFail("Empty list of goods");

        }



        $order_id = $params->{"query-id"} ?? "";

        if ($order_id == "auto" || empty($order_id)) {

            $order_id = uuid_v4();

        }

        $this->onlineLogStep("order_id_assigned", ["order_id" => $order_id]);



        $total = 0;

        $goodsRows = [];



        global $online_order_hall;

        $hallId = (int)$online_order_hall;



        foreach ($params->items as $item) {

            $res = $this->select(

                "select f_id from c_goods where f_scancode=? limit 1",

                "s",

                [$item->sku]

            );



            $row = $res->fetch_assoc();

            if (!$row) {

                $this->onlineLogFail("Goods not found: " . $item->sku, ["sku" => $item->sku]);

            }



            $rowTotal = $item->qty * $item->price;

            $total += $rowTotal;



            $goodsRows[] = [
                "goods_id" => $row['f_id'],
                "qty" => $item->qty,
                "price" => $item->price
            ];

        }

        $this->onlineLogStep("goods_resolved", [

            "items_count" => count($goodsRows),

            "total" => $total,

        ]);



        $phone = trim((string)($params->phone ?? ""));

        $name = trim((string)($params->name ?? ""));

        $address = trim((string)($params->address ?? ""));

        if ($phone === "") {

            $this->onlineLogFail("Phone is required");

        }



        $this->onlineLogStep("client_data_extracted", [

            "phone" => $phone,

            "name" => $name,

            "address" => $address,

        ]);



        $this->beginTransaction();

        $this->onlineLogStep("transaction_started", ["hall_id" => $hallId]);



        $partnerId = $this->resolveOnlineOrderPartner($phone, $name, $address);

        $draftData = (array)$params;

        $draftData["f_partner"] = $partnerId;

        if ($hallId > 0) {

            $draftData["f_hall"] = $hallId;

        }

        $this->onlineLogStep("partner_resolved", ["partner_id" => $partnerId]);



        $comment = "Օնլայն պատվեր";

        if ($phone !== "") {

            $comment .= " " . $phone;

        }

        if ($address !== "") {

            $comment .= " " . $address;

        }



        $draftHeader = [

            "f_id" => $order_id,

            "f_state" => 1,

            "f_saletype" => 1,

            "f_date" => date("Y-m-d"),

            "f_time" => date("H:i:s"),

            "f_amount" => $total,

            "f_payment" => (int)($params->{"payment-method"} ?? 1),

            "f_cashier" => 1,

            "f_staff" => 1,

            "f_partner" => $partnerId,

            "f_comment" => $comment,

            "f_data" => json_encode($draftData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

        ];

        if ($hallId > 0) {

            $draftHeader["f_hall"] = $hallId;

        }

        $this->insert("o_draft_sale", $draftHeader);

        $this->onlineLogStep("draft_header_inserted", ["order_id" => $order_id]);



        foreach ($goodsRows as $i => $row) {

            $this->insert("o_draft_sale_body", [

                "f_id" => uuid_v4(),

                "f_header" => $order_id,

                "f_state" => 1,

                "f_dateappend" => date("Y-m-d"),

                "f_timeappend" => date("H:i:s"),

                "f_goods" => $row['goods_id'],

                "f_qty" => (float)$row['qty'],

                "f_price" => (float)$row['price'],

                "f_row" => $i

            ]);

        }

        $this->onlineLogStep("draft_body_inserted", ["rows" => count($goodsRows)]);



        $this->sendOnlineOrderSms();

        $this->onlineLogStep("sms_sent", [

            "sms_response_code" => $this->result["sms_response_code"] ?? null,

            "sms_error" => $this->result["sms_error"] ?? null,

        ]);



        $this->commit();

        $this->onlineLogStep("transaction_committed");



        $this->result["result"] = 1;

        $this->result["order_id"] = $order_id;

        $this->result["partner_id"] = $partnerId;



        $this->onlineLogSuccess([

            "order_id" => $order_id,

            "partner_id" => $partnerId,

        ]);

        $this->echoResult();

    }



    private function beginOnlineLog($params): void

    {

        $request = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($request === false) {

            $request = json_encode(["error" => "Failed to encode request"], JSON_UNESCAPED_UNICODE);

        }



        $this->onlineLogId = (int)$this->insert("s_online_log", [

            "f_datetime" => date("Y-m-d H:i:s"),

            "f_request" => $request,

            "f_result" => json_encode($this->onlineLog, JSON_UNESCAPED_UNICODE),

        ]);

    }



    private function onlineLogStep(string $step, array $data = []): void

    {

        $entry = array_merge(["step" => $step, "time" => date("H:i:s")], $data);

        $this->onlineLog["steps"][] = $entry;

        $this->flushOnlineLog();

    }



    private function onlineLogFail(string $reason, array $data = []): void

    {

        $this->onlineLog["status"] = "fail";

        $this->onlineLog["error"] = $reason;

        if (!empty($data)) {

            $this->onlineLog["fail_data"] = $data;

        }

        $this->flushOnlineLog();

        dieWithCode($reason);

    }



    private function onlineLogSuccess(array $data = []): void

    {

        $this->onlineLog["status"] = "ok";

        if (!empty($data)) {

            $this->onlineLog["data"] = $data;

        }

        $this->flushOnlineLog();

    }



    private function flushOnlineLog(): void

    {

        if (!$this->onlineLogId) {

            return;

        }



        $this->update("s_online_log", [

            "f_result" => json_encode($this->onlineLog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

        ], $this->onlineLogId);

    }



    private function resolveOnlineOrderPartner(string $phone, string $name, string $address): int

    {

        $partner = $this->select(

            "select f_id from c_partners where f_phone=? limit 1",

            "s",

            [$phone]

        )->fetch_assoc();



        if ($partner) {

            return (int)$partner["f_id"];

        }



        $displayName = $name !== "" ? $name : $phone;



        return (int)$this->insert("c_partners", [

            "f_state" => 1,

            "f_category" => 2,

            "f_name" => $displayName,

            "f_taxname" => $displayName,

            "f_contact" => $name,

            "f_phone" => $phone,

            "f_address" => $address,

        ]);

    }



    private function sendOnlineOrderSms()

    {

        global $otp_login;

        global $otp_pass;

        global $otp_url;

        global $otp_ordinator;

        global $online_order_notify_phone;



        $phone = $online_order_notify_phone;

        if (empty($phone)) {

            return;

        }



        if (substr($phone, 0, 4) != "+374") {

            $this->result["sms_error"] = "Invalid notify phone";

            return;

        }



        $data = [

            "messages" => [

                [

                    "recipient" => $phone,

                    "priority" => "2",

                    "sms" => [

                        "originator" => $otp_ordinator,

                        "content" => [

                            "text" => "New online order, check please"

                        ]

                    ],

                    "message-id" => date("YmdHis") . random_int(100, 999)

                ]

            ]

        ];



        $ch = curl_init($otp_url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [

            "Content-Type: application/json; charset=utf-8"

        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_USERPWD, "$otp_login:$otp_pass");

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));



        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);



        $this->result["sms_response_code"] = $httpCode;

        $this->result["sms_response"] = $response;

    }



    public function printFiscal($params)

    {

        $this->echoResult();

    }



    public function get($params)

    {

        $this->result["data"] = $this->select("select * from c_goods where f_id=?", "i", [$params->f_id])->fetch_assoc();

        $this->echoResult();

    }

}


