<?php
#15/01/2025 (C) Kudryashov Vasili
require_once __DIR__ . "/reports.php";

$widget = ["icon" => "cash.png", "title" => "Վաճառք ըստ օրերի"];
$cols = ["Խումբ", "Ապրանք",];
$hiddencols = [];
$handler = [];
$filter = [
    ["type" => "date", "title" => tr("Date of begin"), "field" => "date1"],
    ["type" => "date", "title" => tr("Date of end"), "field" => "date2"],
    ["type"=> "checkbox", "title"=>tr("Quantities"), "field" => "qnt"],
    ["type"=> "checkbox", "title"=>tr("Amounts"), "field" => "amount"],
];
$colsum = [];

// Валидация входных параметров
if (empty($params->date1)) {
    $params->date1 = date("Y-m-d");
}
if (empty($params->date2)) {
    $params->date2 = date("Y-m-d");
}

if (empty($params->qnt) && empty($params->amount)) {
    $params->qnt = 1;
}
$params->qnt = !empty($params->qnt) ? 1 : 0;
$params->amount = !empty($params->amount) ? 1 : 0;

// Безопасные даты для SQL (защита от инъекций)
$safe_date1 = (new DateTimeImmutable($params->date1))->format('Y-m-d');
$safe_date2 = (new DateTimeImmutable($params->date2))->format('Y-m-d');

$i = 2;
if ($params->qnt > 0) {
    array_push($cols, "Քանակ");
    array_push($colsum, [$i => 0]); // Возвращено: исходный формат
    $i++;
}
if ($params->amount > 0) {
    array_push($cols, "Գումար");
    array_push($colsum, [$i => 0]); // Возвращено: исходный формат
    $i++;
}

$d1 = new DateTimeImmutable($safe_date1);
$d2 = (new DateTimeImmutable($safe_date2))->modify("+1 day");

$pivot_sql = ""; 
do {
    if ($params->qnt > 0) {
        if (!empty($pivot_sql)) {
            $pivot_sql .= ",";
        }
        $pivot_sql .= "cast(coalesce(sum(case when oh.f_datecash='" . $d1->format("Y-m-d") . "' then coalesce(ob.f_qty,0) end), 0) as float)";
    }
    if ($params->amount > 0) {
        if (!empty($pivot_sql)) {
            $pivot_sql .= ",";
        }
        $pivot_sql .= "cast(coalesce(sum(case when oh.f_datecash='" . $d1->format("Y-m-d") . "' then coalesce(ob.f_total,0) end), 0) as float)";
    }
    
    array_push($cols, $d1->format("d/m/Y"));
    if ($params->qnt > 0 && $params->amount > 0) {
        array_push($cols, "");
    }
    
    array_push($colsum, [$i => 0]); // Возвращено: исходный формат
    $i++;
    if ($params->qnt > 0 && $params->amount > 0) {
        array_push($colsum, [$i => 0]); // Возвращено: исходный формат
        $i++;
    }
    $d1 = $d1->modify("+1 day");
} while ($d1 < $d2);

$totalfields = "";
if ($params->qnt > 0) {
    $totalfields .= "cast(sum(ob.f_qty) as float),";
}
if ($params->amount > 0) {
    $totalfields .= "cast(sum(ob.f_total) as float),";
}

// Сборка финального SQL (используем безопасные переменные)
// Примечание: если ob.f_state=1 был нужен, можешь дописать его обратно в where после oh.f_state=2
$sql = "select gr.f_name, d.f_name, " . $totalfields . $pivot_sql . " "
    . "from o_goods ob "
    . "left join o_header oh on oh.f_id=ob.f_header "
    . "left join c_goods d on d.f_id=ob.f_goods "
    . "left join c_groups gr on gr.f_id=d.f_group "
    . "where oh.f_state=2  and ob.f_store in (2,3,5,24) "
    . "and oh.f_datecash between '$safe_date1' and '$safe_date2' "
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