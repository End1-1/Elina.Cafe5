<?php
# © 2026 , Kudryashov Vasili
# Created: 2026-02-06 17:03:39
# Last Modified: 2026-02-09 11:35:01

require_once __DIR__ . "/index.php";

class Partners extends Auth
{

    private $validator;
    public function __construct()
    {
        $this->validator = require_once __DIR__ . "/dict-validators.php";
        return parent::__construct();
    }

    public function GetPartnerData($id)
    {
        $sql = $this->validator["select"]["c_partners"]["sql"];
        $sql = str_replace("1=1", "p.f_id=?", $sql);
        return $this->select($sql, "ssi", [Translator::$locale, Translator::$locale, $id])->fetch_assoc();
    }

    public function Get($params)
    {
        $this->result["partner"] = $this->GetPartnerData($params);
        $this->echoResult();
    }

    public function Save($params)
    {
        if (property_exists($params, "f_id")) {
            if (is_string($params->f_id)) {
                $params->f_id = trim($params->f_id);
            }
            if ($params->f_id === "" || $params->f_id === "null" || $params->f_id === null
                || $params->f_id === 0 || $params->f_id === "0") {
                $params->f_id = null;
            } elseif (filter_var($params->f_id, FILTER_VALIDATE_INT) !== false) {
                $params->f_id = (int)$params->f_id;
            } else {
                $params->f_id = null;
            }
        }

        $data = $this->ValidateParams($params, $this->validator["save"]["c_partners"]);
        if (empty($data->f_id)) {
            unset($data->f_id);
            $data->f_id = $this->insert("c_partners", $data);
        } else {
            $this->update("c_partners", $data, $data->f_id);
        }
        $partner = $this->GetPartnerData($data->f_id);
        $this->result["partner"] = $partner;
        $this->echoResult();
    }
}
