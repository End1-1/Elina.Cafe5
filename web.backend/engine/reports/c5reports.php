<?php
# (C) 2026 Kudryashov Vasili
# Legacy-compatible endpoint for named C5 reports (optional alias endpoint).
require_once __DIR__ . "/reports.php";
require_once __DIR__ . "/../v2/apps/reports/c5reports/index.php";

class C5Reports extends PClass
{
    public function reportList()
    {
        $group = (int)($this->user["f_group"] ?? 0);
        $reports = [];
        foreach (c5reports_for_group($group) as $def) {
            $reports[] = [
                "f_id"    => (int)$def["legacy_id"],
                "f_level" => (int)$def["menu_level"],
                "f_name"  => $def["name"],
                "image"   => $def["icon"] ?? "documents.png",
            ];
        }
        $this->result["reports"] = $reports;
        $this->echoResult();
    }

    public function report()
    {
        $id = (int)($this->params->id ?? 0);
        $def = c5reports_by_legacy_id($id);
        if ($def === null) {
            $this->exitError("Invalid report id: $id");
        }

        $filterFields = c5reports_handler($def["filter_handler"]);
        $deleteQuery  = c5reports_handler($def["delete_handler"]);

        $this->result["report"] = [
            "f_id"                => (int)$def["legacy_id"],
            "f_name"              => $def["name"],
            "f_query"             => $def["query"],
            "f_sumcolumn_indexes" => $def["sum_columns"],
            "f_handler"           => $def["handler"],
            "f_deletehandler"     => $deleteQuery,
            "f_filterhandler"     => $filterFields,
            "date_filter"         => !empty($def["date_filter"])
                || str_contains((string)$def["query"], "%date1")
                || str_contains((string)$def["query"], "%date2"),
        ];
        $this->echoResult();
    }
}

$c = new C5Reports();
$method = $params->call ?? "reportList";
if ($method === "report" || $method === "c5report") {
    $c->report();
} elseif ($method === "reportList" || $method === "c5reportlist") {
    $c->reportList();
} else {
    $c->reportList();
}
