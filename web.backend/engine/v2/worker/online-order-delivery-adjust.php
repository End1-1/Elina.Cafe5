<?php
# © 2026 , Kudryashov Vasili

require_once __DIR__ . "/die-with-code.php";

class OnlineOrderDeliveryAdjust
{
    public const DELIVERY_GOODS_ID = 9490;
    public const FREE_DELIVERY_THRESHOLD = 20000;
    public const DELIVERY_PRICE = 1000;

    public static function apply(Db $db, string $headerId, string $receiptNumber = ""): array
    {
        $receiptNumber = trim($receiptNumber);
        if ($receiptNumber !== "" && strtoupper($receiptNumber[0]) !== "O") {
            return ["adjusted" => false, "reason" => "not_online_order"];
        }

        $header = $db->select("select * from o_header where f_id=?", "s", [$headerId])->fetch_assoc();
        if (!$header) {
            dieWithCode("Order not found");
        }

        $docNumber = $header["f_prefix"] . $header["f_hallid"];
        if (strtoupper(substr($docNumber, 0, 1)) !== "O") {
            return ["adjusted" => false, "reason" => "not_online_order"];
        }

        $remainingRow = $db->select(
            "select coalesce(sum((f_qty - coalesce(f_returnedqty, 0)) * f_price), 0) as f_remaining
            from o_goods
            where f_header=? and f_goods!=? and (f_qty - coalesce(f_returnedqty, 0)) > 0.000001",
            "si",
            [$headerId, self::DELIVERY_GOODS_ID]
        )->fetch_assoc();
        $remaining = (float)$remainingRow["f_remaining"];

        if ($remaining >= self::FREE_DELIVERY_THRESHOLD) {
            return [
                "adjusted" => false,
                "reason" => "above_threshold",
                "remaining" => $remaining,
            ];
        }

        $delivery = $db->select(
            "select f_id, f_price, f_qty, f_total
            from o_goods
            where f_header=? and f_goods=?
              and (f_qty - coalesce(f_returnedqty, 0)) > 0.000001
            limit 1",
            "si",
            [$headerId, self::DELIVERY_GOODS_ID]
        )->fetch_assoc();

        if (!$delivery) {
            return ["adjusted" => false, "reason" => "no_delivery_line"];
        }

        if ((float)$delivery["f_price"] >= 0.001) {
            return ["adjusted" => false, "reason" => "delivery_already_paid"];
        }

        $delta = self::DELIVERY_PRICE;
        $newGoodsTotal = (float)$delivery["f_qty"] * self::DELIVERY_PRICE;
        $paymentAmount = (float)$header["f_amountcash"] > 0.001
            ? (float)$header["f_amountcash"]
            : (float)$header["f_amountcard"];

        $db->beginTransaction();

        try {
            $db->update("o_goods", [
                "f_price" => self::DELIVERY_PRICE,
                "f_total" => $newGoodsTotal,
            ], $delivery["f_id"]);

            $headerUpdate = [
                "f_amounttotal" => (float)$header["f_amounttotal"] + $delta,
            ];
            if ((float)$header["f_amountcash"] > 0.001) {
                $headerUpdate["f_amountcash"] = (float)$header["f_amountcash"] + $delta;
            } else {
                $headerUpdate["f_amountcard"] = (float)$header["f_amountcard"] + $delta;
            }
            $db->update("o_header", $headerUpdate, $headerId);

            if ($paymentAmount > 0.001) {
                $cashRow = $db->select(
                    "select e.f_id as ecash_id, e.f_amount, ah.f_id as a_header_id, ah.f_amount as a_header_amount
                    from a_header_cash ac
                    inner join a_header ah on ah.f_id = ac.f_id
                    inner join e_cash e on e.f_header = ac.f_id and e.f_sign = 1
                    where ac.f_oheader = ?
                    order by abs(e.f_amount - ?) asc
                    limit 1",
                    "sd",
                    [$headerId, $paymentAmount]
                )->fetch_assoc();

                if ($cashRow) {
                    $db->update("e_cash", [
                        "f_amount" => (float)$cashRow["f_amount"] + $delta,
                    ], $cashRow["ecash_id"]);
                    $db->update("a_header", [
                        "f_amount" => (float)$cashRow["a_header_amount"] + $delta,
                    ], $cashRow["a_header_id"]);
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            dieWithCode($e->getMessage());
        }

        return [
            "adjusted" => true,
            "remaining" => $remaining,
            "delta" => $delta,
            "doc_number" => $docNumber,
        ];
    }
}
