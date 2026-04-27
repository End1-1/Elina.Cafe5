<?php
require_once (__DIR__ . "/shop.php");

$havechanges = stmtall("select f_have_changes from c_storages where f_id=? and f_have_changes=1", "i", [$params->store])->fetch_assoc();
if (!empty($havechanges) || !empty($params->forceupdate)) {
    stmtall("delete from a_store_sale where f_store=?", "i", [$params->store]);
    stmtall("insert into a_store_sale (f_store, f_goods, f_qty, f_qtyreserve, f_qtyprogram) "
        . "select ? as f_store, f_goods, sum(f_qty*f_type) as f_qty, 0, 0 "
        . "from a_store where f_store=? group by 1, 2 having sum(f_qty*f_type)>0", "ii", [$params->store, $params->store]);
    stmtall("update c_storages set f_have_changes=0 where f_id=?", "i", [$params->store]);
}


stmtall("update a_store_reserve set f_state=3, f_canceleddate=current_date, f_canceledtime=current_time, "
    . "f_message=concat(f_message, ' Expired') where (f_enddate<current_date or f_enddate is null) and f_state=1 ");

if (!defined("noecho")) {
    printResult(1, "ok");
}