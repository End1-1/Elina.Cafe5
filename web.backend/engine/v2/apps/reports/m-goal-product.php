<?php
# © 2025 , Kudryashov Vasili
# Created: 2025-11-04 14:03:43
# Last Modified: 2025-12-12 15:00:00

require_once "report.php";

class MGoalProduct extends Report
{

    public function widget()
    {
        return [
            "title" => Translator::t("Product"),
            "icon" => "template.png",
            "version" => 1
        ];
    }

    public function  sumColumns()
    {
        return [["8" => 0], ["7" => 0]];
    }

    public function hiddenCols()
    {
        return [];
    }

    public function handler()
    {
        return [
            'cac01eeb-b965-11f0-a072-8a884be02f31',
            'cac01eeb-b965-11f0-a072-8a884be02f31'
        ];
    }

    public function columns()
    {
        return [
            Translator::t("ID"),
            Translator::t("Date"),
            Translator::t("Status"),
            Translator::t("Name"),
            Translator::t("Width"),
            Translator::t("Height"),
            Translator::t("34"),
            Translator::t("36"),
            Translator::t("38"),
            Translator::t("40"),
            Translator::t("42"),
            Translator::t("44"),
            Translator::t("46"),
        ];
    }

    public function filter()
    {
        return [
            ["type" => "date", "title" => Translator::t("Date of begin"), "field" => "date1"],
            ["type" => "date", "title" => Translator::t("Date of end"), "field" => "date2"]
        ];
    }

    public function rows()
    {
        if (empty($this->params->date1)) {
            $this->params->date1 = date('Y-m-d');
            $this->params->date2 = date('Y-m-d');
        }
        $where =   " where  g.f_date between '{$this->params->date1}' and '{$this->params->date2}' ";
        if (!empty($this->params->status)) {
            $where .= " and g.f_status={$this->params->status}";
        }
        if (!empty($this->params->product)) {
            $where .= " and g.f_product={$this->params->product}";
        }
        if (!empty($this->params->showincomplete)) {
            $where .= " or (g.f_status in (1,2,3,4))";
        }

        $sql = <<<EOD
        SELECT g.f_id, g.f_date, gn.f_name, mf.f_name,
        g.f_width, g.f_height, 
        g.f_34, g.f_36, g.f_38, g.f_40, g.f_42, g.f_44, g.f_46
        FROM m_goal_product g
        left join mf_actions_group mf on mf.f_id=g.f_product
        LEFT JOIN m_goal_product_status gn ON gn.f_id=g.f_status
        $where 
        EOD;
        $this->debugi = $sql;
        return $this->select($sql)->fetch_all();
    }

    public function StatusList($params)
    {
        $data = $this->select("select * from m_goal_product_status")->fetch_all(MYSQLI_ASSOC);
        $this->result["data"] = $data;
        $this->echoResult();
    }

    public function ReasonList($params)
    {
        $data = $this->select("select * from m_goal_product_reason where f_id>1")->fetch_all(MYSQLI_ASSOC);
        $this->result["data"] = $data;
        $this->echoResult();
    }

    public function Put($params)
    {
        try {
            $image = $_FILES['image'] ?? null;
            $this->result["image"] = ($image && !empty($image['tmp_name'])) ? "yes" : "null";
            if ($image && !empty($image['tmp_name']) && is_uploaded_file($image['tmp_name'])) {
                $dir = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\') . "/engine/media/production/";
                if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new RuntimeException("Cannot create media dir: " . $dir);
                }
                $ext = strtolower(pathinfo($image['name'] ?? 'img', PATHINFO_EXTENSION) ?: 'jpg');
                $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg';
                $filename = "prod_" . time() . "_" . rand(1000, 9999) . "." . $ext;
                $targetPath = $dir . $filename;
                if (!move_uploaded_file($image['tmp_name'], $targetPath)) {
                    throw new RuntimeException("Failed to save uploaded image");
                }
                $params->f_image_url = "/engine/media/production/" . $filename;
            }

            // Keep only DB columns; never pass nested materials/other into insert/update
            $allowed = [
                'f_date', 'f_status', 'f_product', 'f_width', 'f_height',
                'f_width_doublerin', 'f_height_doublerin',
                'f_width_doublerin_fabric', 'f_height_doublerin_fabric',
                'f_width_lining', 'f_height_lining',
                'f_34', 'f_36', 'f_38', 'f_40', 'f_42', 'f_44', 'f_46',
                'f_image_url',
            ];
            $row = [];
            foreach ($allowed as $col) {
                if (!property_exists($params, $col)) {
                    continue;
                }
                $val = $params->{$col};
                if ($col === 'f_image_url' && is_string($val) && $val !== '') {
                    // Edit returns absolute URL — store path only
                    $path = parse_url($val, PHP_URL_PATH);
                    $val = $path ?: $val;
                }
                if (in_array($col, ['f_34', 'f_36', 'f_38', 'f_40', 'f_42', 'f_44', 'f_46', 'f_status', 'f_product'], true)) {
                    $val = (int)$val;
                }
                if (in_array($col, [
                    'f_width', 'f_height',
                    'f_width_doublerin', 'f_height_doublerin',
                    'f_width_doublerin_fabric', 'f_height_doublerin_fabric',
                    'f_width_lining', 'f_height_lining',
                ], true)) {
                    $val = (float)$val;
                }
                $row[$col] = $val;
            }
            $rowObj = (object)$row;

            $id = (int)($params->f_id ?? 0);
            if ($id > 0) {
                $this->update("m_goal_product", $rowObj, $id);
            } else {
                $id = (int)$this->insert("m_goal_product", $rowObj);
                $params->f_id = $id;
            }
            if ($id <= 0) {
                throw new RuntimeException("Failed to save product header");
            }

            $this->delete("m_goal_product_material", $id, "f_product");

            $this->insertMaterialList($params->materials ?? [], $id, 1, 0);
            $this->insertMaterialList($params->doublerin ?? [], $id, 1, 10);
            $this->insertMaterialList($params->doublerin_fabric ?? [], $id, 1, 11);
            $this->insertMaterialList($params->lining ?? [], $id, 1, 12);

            $others = $params->other ?? [];
            if (is_object($others)) {
                $others = (array)$others;
            }
            if (!is_array($others)) {
                $others = [];
            }
            foreach ($others as $m) {
                $m = is_array($m) ? (object)$m : $m;
                $reason = (int)($m->f_reason ?? 0);
                if ($reason <= 1) {
                    $reason = 2;
                }
                $this->insert("m_goal_product_material", $this->materialRow($m, $id, $reason, 0));
            }

            $this->result["status"] = 1;
            $this->result["f_id"] = $id;
            $this->echoResult();
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 0,
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    private function insertMaterialList($list, int $productId, int $reason, int $parent): void
    {
        if (is_object($list)) {
            $list = (array)$list;
        }
        if (!is_array($list)) {
            $list = [];
        }
        foreach ($list as $m) {
            $m = is_array($m) ? (object)$m : $m;
            $this->insert("m_goal_product_material", $this->materialRow($m, $productId, $reason, $parent));
        }
    }

    private function materialRow(object $m, int $productId, int $reason, int $parent = 0): object
    {
        return (object)[
            'f_product' => $productId,
            'f_parent' => $parent,
            'f_material' => (int)($m->f_material ?? 0),
            'f_code' => (string)($m->f_code ?? ''),
            'f_color' => (string)($m->f_color ?? ''),
            'f_qty1' => (float)($m->f_qty1 ?? 0),
            'f_qty2' => (float)($m->f_qty2 ?? 0),
            'f_totalqty' => (float)($m->f_totalqty ?? 0),
            'f_qtyperone' => (float)($m->f_qtyperone ?? 0),
            'f_colorqty' => (float)($m->f_colorqty ?? 0),
            'f_row' => (int)($m->f_row ?? 0),
            'f_reason' => $reason,
        ];
    }

    private function loadSectionMaterials(int $productId, int $reason, int $parent): array
    {
        $sql = <<<EOD
        select mm.f_id, mm.f_material, ma.f_name as f_materialname, mm.f_code, mm.f_color, mm.f_qty1, mm.f_qty2, mm.f_totalqty, mm.f_qtyperone, mm.f_colorqty,
        mm.f_row, coalesce(mm.f_parent,0) as f_parent
        from m_goal_product_material mm
        left join c_goods ma on ma.f_id=mm.f_material
        where mm.f_product=? and mm.f_reason=? and coalesce(mm.f_parent,0)=?
        order by mm.f_row
        EOD;
        return $this->select($sql, "iii", [$productId, $reason, $parent])->fetch_all(MYSQLI_ASSOC);
    }

    public function Edit($params)
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = "$scheme://$host";

        $sql = <<<EOD
        SELECT g.f_id, g.f_date, gn.f_name, mf.f_name,
        g.f_width, g.f_height,
        g.f_width_doublerin, g.f_height_doublerin,
        g.f_width_doublerin_fabric, g.f_height_doublerin_fabric,
        g.f_width_lining, g.f_height_lining,
        g.f_34, g.f_36, g.f_38, g.f_40, g.f_42, g.f_44, g.f_46,
        concat('$baseUrl', f_image_url) as f_image_url
        FROM m_goal_product g
        left join mf_actions_group mf on mf.f_id=g.f_product
        LEFT JOIN m_goal_product_status gn ON gn.f_id=g.f_status
        where g.f_id=?
        EOD;
        $data = $this->select($sql, "i", [$params->id])->fetch_assoc();
        if (empty($data)) {
            dieWithCode(Translator::t("No data"));
        }
        $this->result["data"] = $data;
        $productId = (int)$params->id;
        $this->result["materials"] = $this->loadSectionMaterials($productId, 1, 0);
        $this->result["doublerin"] = $this->loadSectionMaterials($productId, 1, 10);
        $this->result["doublerin_fabric"] = $this->loadSectionMaterials($productId, 1, 11);
        $this->result["lining"] = $this->loadSectionMaterials($productId, 1, 12);
        $sql = <<<EOD
        select mm.f_id,mm.f_reason, mn.f_name as f_reasonname, mm.f_material, ma.f_name as f_materialname, mm.f_code, mm.f_color, mm.f_qty1, mm.f_qty2, mm.f_totalqty, mm.f_qtyperone, mm.f_colorqty,
        mm.f_row
        from m_goal_product_material mm
        left join c_goods ma on ma.f_id=mm.f_material 
        left join m_goal_product_reason mn on mn.f_id=mm.f_reason
        where mm.f_product=? and f_reason<>1 and coalesce(mm.f_parent,0)=0
        order by mm.f_row
        EOD;
        $this->result["other"] = $this->select($sql, "i", [$productId])->fetch_all(MYSQLI_ASSOC);
        $this->echoResult();
    }
}
