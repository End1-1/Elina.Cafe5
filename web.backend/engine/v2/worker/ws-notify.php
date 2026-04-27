<?php
# (C) 2026 Kudryashov Vasili 
# Created - 2025-03-29 01:06:49
# Last modified - 2025-03-29 01:06:53
require_once __DIR__ . "/../../cnf.php";
require_once __DIR__ . "/../../functions/ws.php";

return function (string $dict, int $id, bool $isnew) {
    global $websocketaddress, $websocketport, $websocketuser, $websocketpass, $websocketkey;

    try {
        $ws = new WSClient($websocketaddress, $websocketport);
        if (!$ws->connect()) return false;

        $ws->handshake();

        // REGISTER
        $ws->sendJson([
            "command"  => "register_socket",
            "key"      => $websocketkey,
              "username" => $websocketuser,
            "password" => $websocketpass
        ]);

        // WAIT AUTH ACK
        $ack = $ws->receiveJson();
        if (!$ack || ($ack["status"] ?? 0) != 1) {
            error_log("Service5 auth failed: " . json_encode($ack));
            $ws->close();
            return false;
        }

        // SEND UPDATE EVENT
        $ws->sendJson([
            "command" => "dict_event",
            "entity"  => $dict,
            "op"      => $isnew ? "i" : "u",
            "id"      => $id,
            "ts"      => time()
        ]);

        $ws->close();
        return true;
    } catch (Throwable $e) {
        error_log("Service5 notify error: " . $e->getMessage());
        return false;
    }
};
