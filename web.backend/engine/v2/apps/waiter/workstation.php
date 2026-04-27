<?php
# © 2025 , Kudryashov Vasili
# Created: 2026-01-15 09:44:39
# Last Modified: 2026-01-15 09:44:46
require_once __DIR__ . "/index.php";

class Workstation extends Auth
{
    public function GetConfig($params)
    {
        $config = $this->select(
            "select * from workstations where f_name=? and f_station_account=? and f_type=?",
            "ssi",
            [$params->workstation, $params->station_account, $params->type]
        )->fetch_assoc();

        if (!$config) {

            $this->select(
                "insert ignore into workstations (f_type, f_station_account, f_name, f_config) values (?, ?, ?, '{}')",
                "iss",
                [$params->type, $params->station_account, $params->workstation],
                true
            );


            $config = $this->select(
            "select * from workstations where f_name=? and f_station_account=? and f_type=?",
            "ssi",
            [$params->workstation, $params->station_account, $params->type]
        )->fetch_assoc();
        }

        $this->result = array_merge($this->result, $config);
        $this->echoResult();
    }
}
