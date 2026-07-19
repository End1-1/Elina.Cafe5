<?php
# (C) 2026 Kudryashov Vasili
# NTable report controller for named C5 reports (FrontDesk NTableWidget).

require_once __DIR__ . "/report.php";
require_once __DIR__ . "/c5reports/index.php";

class C5reports extends Report
{
    private ?array $def = null;

    /** cacheId => [filter endpoint, title] for keyvalue filters (reports_handler descriptors). */
    private const CACHE_FILTERS = [
        9  => ["filter" => "partners", "title" => "Partner"],
        28 => ["filter" => "halls", "title" => "Hall"],
        6  => ["filter" => "goodsgroup", "title" => "Goods group"],
        8  => ["filter" => "goods", "title" => "Store"],
    ];

    public function get($params)
    {
        $this->params = $params;
        $this->loadDef();
        parent::get($params);
    }

    public function list($params)
    {
        $this->params = $params;
        require_once __DIR__ . "/c5reports/list.php";
        $group = (int)($this->user["f_group"] ?? 0);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "status" => 1,
            "c5reports" => c5reports_build_menu_list($group),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function loadDef(): void
    {
        if ($this->def !== null) {
            return;
        }

        $id = (int)($this->params->id ?? 0);
        $this->def = c5reports_by_legacy_id($id);
        if ($this->def === null) {
            dieWithCode("Invalid report id: $id", 404);
        }

        $group = (int)($this->user["f_group"] ?? 0);
        if ($group !== 1 && !in_array($group, $this->def["permissions"] ?? [], true)) {
            dieWithCode("Access denied", 403);
        }
    }

    protected function widget()
    {
        $this->loadDef();
        return [
            "title" => $this->def["name"],
            "icon" => $this->def["icon"] ?? "documents.png",
            "version" => 2,
        ];
    }

    protected function columns()
    {
        $this->loadDef();
        if (!empty($this->def["columns"]) && is_array($this->def["columns"])) {
            return $this->def["columns"];
        }
        return $this->parseColumnsFromSql($this->def["query"]);
    }

    protected function filter()
    {
        $this->loadDef();
        $fields = [];

        if ($this->usesDateFilter()) {
            $fields[] = ["type" => "date", "title" => Translator::t("Date of begin"), "field" => "date1"];
            $fields[] = ["type" => "date", "title" => Translator::t("Date of end"), "field" => "date2"];
        }

        foreach ($this->filterDescriptors() as $desc) {
            $meta = self::CACHE_FILTERS[$desc["cacheId"]] ?? null;
            if ($meta === null) {
                continue;
            }
            $fields[] = [
                "type" => "keyvalue",
                "title" => Translator::t($meta["title"]),
                "field" => $desc["param"],
                "filter" => $meta["filter"],
            ];
        }

        return $fields;
    }

    protected function sumColumns()
    {
        $this->loadDef();
        $out = [];
        $raw = $this->def["sum_columns"] ?? null;
        if (empty($raw)) {
            return $out;
        }
        foreach (explode(",", (string)$raw) as $col) {
            $col = trim($col);
            if ($col !== "") {
                $out[] = [(string)(int)$col => 0];
            }
        }
        return $out;
    }

    protected function handler()
    {
        $this->loadDef();
        $uuid = $this->def["handler"] ?? null;
        if (empty($uuid)) {
            return [];
        }
        return [$uuid, $uuid];
    }

    protected function hiddenCols()
    {
        $this->loadDef();
        return $this->def["hidden_cols"] ?? [];
    }

    protected function rows()
    {
        $this->loadDef();

        if ($this->usesDateFilter() && empty($this->params->date1)) {
            $this->params->date1 = date("Y-m-d");
            $this->params->date2 = date("Y-m-d");
        }

        $sql = $this->def["query"];
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $sql = str_replace("\n", " ", $sql);

        if (str_contains($sql, "%date1")) {
            $sql = str_replace("%date1", "'" . $this->sqlDate((string)$this->params->date1) . "'", $sql);
        }
        if (str_contains($sql, "%date2")) {
            $sql = str_replace("%date2", "'" . $this->sqlDate((string)$this->params->date2) . "'", $sql);
        }
        if (str_contains($sql, "%filter")) {
            $sql = str_replace("%filter", $this->buildFilterSql(), $sql);
        }

        $result = $this->select($sql);
        if ($result === false) {
            return [];
        }
        return $result->fetch_all(MYSQLI_NUM);
    }

    private function buildFilterSql(): string
    {
        $cond = "";
        foreach ($this->filterDescriptors() as $desc) {
            $value = $this->params->{$desc["param"]} ?? null;
            if (empty($value)) {
                continue;
            }
            $ids = array_filter(array_map("trim", explode(",", (string)$value)), "strlen");
            $ids = array_map(fn($v) => (int)$v, $ids);
            $ids = array_filter($ids, fn($v) => $v > 0);
            if (empty($ids)) {
                continue;
            }
            $cond .= " and {$desc["sqlField"]} IN (" . implode(",", $ids) . ") ";
        }
        return $cond;
    }

    private function usesDateFilter(): bool
    {
        return !empty($this->def["date_filter"])
            || str_contains((string)$this->def["query"], "%date1")
            || str_contains((string)$this->def["query"], "%date2");
    }

    /** @return array<int, array{cacheId:int, sqlField:string, param:string}> */
    private function filterDescriptors(): array
    {
        $this->loadDef();
        $out = [];
        $filterHandler = $this->def["filter_handler"] ?? null;
        if (empty($filterHandler)) {
            return $out;
        }
        $descriptor = c5reports_handler($filterHandler);
        if (empty($descriptor)) {
            return $out;
        }
        foreach (explode(",", $descriptor) as $part) {
            $part = trim($part);
            if ($part === "") {
                continue;
            }
            $kv = explode("-", $part, 2);
            if (count($kv) !== 2) {
                continue;
            }
            $sqlField = $kv[1];
            $out[] = [
                "cacheId" => (int)$kv[0],
                "sqlField" => $sqlField,
                "param" => str_replace(".", "_", $sqlField),
            ];
        }
        return $out;
    }

    private function sqlDate(string $value): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $value = date("Y-m-d");
        }
        return $value;
    }

    private function parseColumnsFromSql(string $sql): array
    {
        $cols = [];
        if (preg_match_all("/\s+as\s+['\"`]([^'\"`]+)['\"`]/iu", $sql, $m)) {
            $cols = $m[1];
        } elseif (preg_match_all("/\s+AS\s+'([^']+)'/u", $sql, $m)) {
            $cols = $m[1];
        }
        return $cols;
    }
}
