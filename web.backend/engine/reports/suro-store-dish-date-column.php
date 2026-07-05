<?php
#27/01/2025 (C) Kudryashov Vasili
require_once __DIR__ . "/reports.php";

$widget = ["icon" => "cash.png", "title" => "Վաճառք ըստ օրերի"];
$cols = ["Խանութ", "Խումբ"];
$hiddencols = [];
$handler = [];
$filter = [
    ["type" => "date", "title" => tr("Date of begin"), "field" => "date1"],
    ["type" => "date", "title" => tr("Date of end"), "field" => "date2"],
    ["type" => "keyvalue", "title" => tr("Store"), "field" => "store", "filter" => "storages"],
    ["type" => "checkbox", "title" => tr("Quantities"), "field" => "qnt"],
    ["type" => "checkbox", "title" => tr("Amounts"), "field" => "amount"],
];
$colsum = [];

if (empty($params->date1)) {
    $params->date1 = date("Y-m-d");
}

if (empty($params->date2)) {
    $params->date2 = date("Y-m-d");
}
if (empty($params->qnt) && empty($params->amount)) {
    $params->qnt = 1;
}
if (empty($params->qnt)) {
    $params->qnt = 0;
}
if (empty($params->amount)) {
    $params->amount = 0;
}
$i = 3;
if ($params->qnt > 0) {
    array_push($cols, "Քանակ");
    array_push($colsum, [2 => 0]);
    $i++;
}
if ($params->amount > 0) {
    array_push($cols, "Գումար");
    array_push($colsum, [3 => 0]);
    $i++;
}

$d1 = new DateTimeImmutable($params->date1);
$d2 = (new DateTimeImmutable($params->date2))->modify("+1 day");


$store_filter = "";
if (!empty($params->store)) {
    $store_filter = "and og.f_store in(" . $params->store . ") ";
} else {
    $store_filter = " and og.f_store in (2,3,5,11) ";
}

$sql = "select st.f_name, gr.f_name, sum(og.f_qty), sum(og.f_total)  "
    . "from o_goods og "
    . "left join o_header h on h.f_id=og.f_header "
    . "left join c_goods d on d.f_id=og.f_goods "
    . "left join c_groups gr on gr.f_id=d.f_group "
    . "left join c_storages st on st.f_id=og.f_store "
    . "where h.f_state=2 $store_filter "
    . "and h.f_datecash between '$params->date1' and '$params->date2' "
    . "group by 1,2 ";

$rows = stmtall($sql)->fetch_all(MYSQLI_NUM);


echo json_encode(
    [
        "widget" => $widget,
        "cols" => $cols,
        "rows" => $rows,
        "handler" => $handler,
        "sum" => $colsum,
        "filter" => $filter,
        "hiddencols" => $hiddencols
    ],
    JSON_UNESCAPED_UNICODE
);
