<?php
# © 2026 , Kudryashov Vasili
# Created: 2026-01-27 12:29:24
# Last Modified: 2026-03-29 12:29:27
require_once __DIR__ . "/index.php";
require_once __DIR__ . "/../../worker/uuid.php";

class Documents extends Auth
{
    public function Save($params)
    {
        $this->SaveStoreDoc($params);
    }

    public function SaveStoreDoc($params)
    {
        $session = $params->session ?? uuid_v4();
        $params->session = $session;
        $json = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            dieWithCode("Invalid document payload");
        }

        $this->callJsonProcedure("sf_create_store_document", $json, true);

        $res = $this->select(
            "SELECT f_result FROM a_result WHERE f_session=?",
            "s",
            [$session]
        )->fetch_assoc();

        if (empty($res)) {
            dieWithCode("No result returned");
        }

        $result = json_decode($res["f_result"], true);
        if (!is_array($result)) {
            dieWithCode("Invalid procedure result");
        }
        if (($result["status"] ?? 0) == 0) {
            dieWithCode($result["message"] ?? "Unknown error");
        }

        $docId = $params->header->f_id ?? "";
        if ($docId !== "") {
            $header = $this->select(
                "select f_userid from a_header where f_id=?",
                "s",
                [$docId]
            )->fetch_assoc();
            if (!empty($header)) {
                $this->result["f_userid"] = $header["f_userid"];
            }
        }

        $this->result["status"] = 1;
        $this->result["session"] = $session;
        $this->result["message"] = $result["message"] ?? "";
        $this->echoResult();
    }

    public function SaveStoreDocument($params)
    {
        $this->SaveStoreDoc($params);
    }

    public function Remove($params)
    {
        $this->RemoveStoreDoc($params);
    }

    public function RemoveStoreDoc($params)
    {
        $id = $params->id ?? $params->removeid ?? "";
        if ($id === "") {
            dieWithCode("Document id is empty");
        }
        $json = json_encode(["removeid" => $id], JSON_UNESCAPED_UNICODE);
        $row = $this->select("select sf_remove_store_doc(?) as result", "s", [$json])->fetch_assoc();
        if (empty($row) || !isset($row["result"])) {
            dieWithCode("Database function returned nothing");
        }
        $result = json_decode($row["result"], true);
        if (!is_array($result) || ($result["status"] ?? 0) == 0) {
            $reason = $result["reason"] ?? ($result["message"] ?? "Cannot remove document");
            if (is_array($reason)) {
                $reason = json_encode($reason, JSON_UNESCAPED_UNICODE);
            }
            dieWithCode((string)$reason);
        }
        $this->result["status"] = 1;
        $this->echoResult();
    }
}
