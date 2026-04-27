<?php
require_once ("../app.php");

if (
	!$stmt = $db->prepare("insert into a_store_reserve (f_date, f_time, f_enddate, f_state, f_source, f_store, f_goods, f_qty, f_message) "
		. "values (current_date(), current_time(), ?, 1, ?, ?, ?, ?, ?)")
) {
	exitError("D" . $db->error);
}
if (!$stmt->bind_param("siiids", $params->enddate, $params->source, $params->store, $params->goods, $params->qty, $params->message)) {
	exitError($stmt->error);
}
if (!$stmt->execute()) {
	exitError($stmt->error);
}
$stmt->close();

$server5 = json_encode([
	"action" => 1,
	"goods" => $params->goods,
	"qty" => $params->qty,
	"goodsname" => $params->goodsname,
	"scancode" => $params->barcode,
	"unit" => "",
	"usermessage" => $params->message,
	"enddate" => $params->enddate
]);
$sender = 0;
$receiver = 0;
if (!$res = $db->query("select fuser from server5.users_store where fid=" . $params->source)) {
	exitError($db->error);
}
if ($row = $res->fetch_assoc()) {
	$sender = $row["fuser"];
}
if (!$res = $db->query("select fuser from server5.users_store where fid=" . $params->store)) {
	exitError($db->error);
}
if ($row = $res->fetch_assoc()) {
	$receiver = $row["fuser"];
}

if (!$stmt = $db->prepare("insert into server5.users_chat (fdateserver, fstate,fsender,freceiver,fmessage) values (current_timestamp(), 1,?,?,?)")) {
	exitError($db->error);
}
$stmt->bind_param("iis", $sender, $receiver, $server5);
if (!$stmt->execute()) {
	exitError($stmt->error);
}
$stmt->close();

echo json_encode(["status" => 1, "data" => []]);