<?php
# (C) 2014-2026 Kudryashov Vasili
# Created - 25/02/2025 
# Last modified - 2026-06-03
require_once __DIR__ . "/reports.php";

class Effectivness extends Report
{
    private function heartbeat(string $stepName): void
    {
        // Отключаем буферизацию Nginx/Apache, если она включена
        if (!headers_sent()) {
            header('X-Accel-Buffering: no');
        }

        // Выплевываем скрытый комментарий для Cloudflare
        echo "\n";

        // Принудительно выталкиваем данные в сеть
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
    // Выносим хардкод складов в константы для удобства поддержки
    private const DEFAULT_SHOPS = "5,24,2,3";
    private const BASE_STORES   = "28,8";
    private const INPUT_STORE   = "16";

    private function sqlLogEnabled(): bool
    {
        # return !empty($_GET['debug']);
        return true;
    }

    private function execSql(string $label, string $sql)
    {
        $started = microtime(true);
        $result = $this->db->stmtall($sql);
        if ($this->sqlLogEnabled()) {
            $ms = round((microtime(true) - $started) * 1000, 2);
            error_log(sprintf('[Effectivness] %s: %s ms', $label, $ms));
        }
        return $result;
    }

    protected function hiddenCols()
    {
        return empty($this->params->view) ? [] : [0];
    }

    protected function columns()
    {
        if (empty($this->params->view)) {
            return [
                $this->tr("Goods code"),
                $this->tr("Group"),
                $this->tr("Goods"),
                $this->tr("Barcode"),
                $this->tr("Begin store"),
                $this->tr("Begin shop"),
                $this->tr("Input"),
                $this->tr("Sale"),
                $this->tr("Output"),
                $this->tr("Final shop"),
                $this->tr("Final store"),
                $this->tr("Effectiveness"),
                $this->tr("Delta"),
                $this->tr("Whosale price"),
                $this->tr("Retail price"),
                $this->tr("Retail discounted")
            ];
        } else {
            return [
                $this->tr("Group code"), // Индекс 0 (будет скрыт согласно hiddenCols)
                $this->tr("Group"),
                $this->tr("Begin store"),
                $this->tr("Begin shop"),
                $this->tr("Input"),
                $this->tr("Sale"),
                $this->tr("Output"),
                $this->tr("Final shop"),
                $this->tr("Final store"),
                $this->tr("Effectiveness"),
                $this->tr("Delta"),
                $this->tr("Whosale price"),
                $this->tr("Retail price"),
                $this->tr("Retail discounted")
            ];
        }
    }

    protected function widget()
    {
        return ["icon" => "cash.png", "title" => $this->tr("Effectivness")];
    }

    protected function filter()
    {
        return [
            ["type" => "date", "title" => tr("Date of begin"), "field" => "d1"],
            ["type" => "date", "title" => tr("Date of end"), "field" => "d2"],
            ["type" => "keyvalue", "title" => $this->tr("Point of sale"), "field" => "pos", "filter" => "storages"],
            ["type" => "keyvalue", "title" => $this->tr("Group"), "field" => "gr", "filter" => "goodsgroup"],
            ["type" => "checkbox", "title" => tr("Groupping"), "field" => "view"]
        ];
    }

    protected function rows()
    {
        if (empty($this->params->d1)) {
            $this->params->d1 = date("Y-m-d");
        }
        if (empty($this->params->d2)) {
            $this->params->d2 = date("Y-m-d");
        }

        $this->params->d1 = preg_replace('/[^0-9\-]/', '', $this->params->d1);
        $this->params->d2 = preg_replace('/[^0-9\-]/', '', $this->params->d2);

        if (!empty($this->params->pos)) {
            $this->params->pos = preg_replace('/[^0-9,]/', '', $this->params->pos);
        }
        if (!empty($this->params->gr)) {
            $this->params->gr = preg_replace('/[^0-9,]/', '', $this->params->gr);
        }

        if ($this->sqlLogEnabled()) {
            error_log(sprintf(
                '[Effectivness] start %s mode d1=%s d2=%s pos=%s gr=%s',
                empty($this->params->view) ? 'goods' : 'group',
                $this->params->d1,
                $this->params->d2,
                $this->params->pos ?? '',
                $this->params->gr ?? ''
            ));
        }

        if (empty($this->params->view)) {
            return $this->getGoods();
        }
        return $this->getGroup();
    }

    protected function sumColumns()
    {
        if (empty($this->params->view)) {
            return [[4 => 0], [5 => 0], [6 => 0], [7 => 0], [8 => 0], [9 => 0], [10 => 0]];
        }
        return [[2 => 0], [3 => 0], [4 => 0], [5 => 0], [6 => 0], [7 => 0], [8 => 0]];
    }

    private function getGoods()
    {
        $this->fillTempReport();
        $s = <<<EOD
        select
            t.f_goods,
            max(t.f_groupname) as f_groupname,
            max(t.f_goodsname) as f_goodsname,
            max(t.f_scancode) as f_scancode,
            sum(t.f_start2) as f_start2,
            sum(t.f_startqty) as f_startqty,
            sum(t.f_input) as f_input,
            sum(t.f_sale) as f_sale,
            sum(t.f_output) as f_output,
            sum(t.f_final) as f_final,
            sum(t.f_final2) as f_final2,
            if(sum(t.f_startqty + t.f_input + t.f_start2) <> 0, (sum(t.f_sale) * 100) / sum(t.f_startqty + t.f_input + t.f_start2), 0) as f_effectiveness,
            if(sum(t.f_startqty + t.f_start2) > 0, ((sum(t.f_final) * 100) / sum(t.f_startqty + t.f_start2)) - 100, 0) as f_delta,
            (t.f_wholesale) as f_wholesale,
            (t.f_retail) as f_retail,
            (t.f_retaildisc) as f_retaildisc
        from t_rep t
        inner join c_goods g on g.f_id = t.f_goods and g.f_unit <> 10
        group by t.f_goods
        EOD;
        return $this->execSql('select_goods', $s)->fetch_all();
    }

    private function getGroup()
    {
        $this->fillTempReport();

        $s = <<<EOD
        select
            f_groupid,
            -- Если это акционная строка, можем визуально пометить имя группы, например добавить " (Скидка)"
            if(f_retail <> f_retaildisc and f_retaildisc > 0, concat(f_groupname, ' (Զեղչ)'), f_groupname) as f_groupname,
            sum(f_start2) as f_startstore,
            sum(f_startqty),
            sum(f_input),
            sum(f_sale),
            sum(f_output),
            sum(f_final),
            sum(f_final2) as f_finalstore,
            if(sum(f_startqty + f_input + f_start2) <> 0, (sum(f_sale) * 100) / sum(f_startqty + f_input + f_start2), 0) as f_effectiveness,
            if(sum(f_startqty + f_start2) > 0, ((sum(f_final) * 100) / sum(f_startqty + f_start2)) - 100, 0) as f_delta,
            max(f_wholesale),
            max(f_retail),
            max(f_retaildisc)
        from t_rep
        group by 
            f_groupid, 
            -- Если имя группы в интерфейсе должно остаться строго одинаковым, f_groupname оставляем здесь
            f_groupname,
            -- А вот и наш виновник торжества: группируем по логическому условию (выдаст 1 или 0)
            if(f_retail <> f_retaildisc and f_retaildisc > 0, 1, 0)
        EOD;
        return $this->execSql('select_group', $s)->fetch_all();
    }

    private function fillTempReport()
    {
        $reportStarted = microtime(true);
        $whereStore = !empty($this->params->pos) ? $this->params->pos : self::DEFAULT_SHOPS;
        $baseStores = self::BASE_STORES;
        $inputStore = self::INPUT_STORE;

        $whereGroup = "";
        if (!empty($this->params->gr)) {
            $whereGroup = " and g.f_group in({$this->params->gr}) ";
        }
        $join = " inner join c_goods g on g.f_id=s.f_goods ";

        $sql = <<<EOD
        create temporary table t_rep (f_goods int, f_groupid int, f_groupname tinytext, f_goodsname tinytext, f_scancode tinytext,
        f_start2 float, f_startqty float, f_input float, f_sale float, f_output float,
        f_final float, f_final2 float, f_effectiveness float, f_delta float,
        f_wholesale float, f_retail float, f_retaildisc float);
        EOD;
        $this->execSql('create_temp_table', $sql);

$sql = <<<EOD
insert into t_rep
select 
g.f_id, 
gr.f_id, 
gr.f_name, 
g.f_name, 
g.f_scancode,
0, 0, 0, 0, 0, 0, 0, 0, 0,
gp.f_price2, 
gp.f_price1, 
gp.f_price1disc
from c_goods g
-- Подзапрос гарантирует строго одну строку цен на один f_goods
left join (
SELECT 
	f_goods,
	MAX(f_price2) as f_price2,
	MAX(f_price1) as f_price1,
	MAX(coalesce(f_price1disc, 0)) as f_price1disc
FROM c_goods_prices
WHERE f_currency = 1
GROUP BY f_goods
) gp ON gp.f_goods = g.f_id
left join c_groups gr ON gr.f_id = g.f_group
EOD;

if (!empty($this->params->gr)) {
$sql .= " where g.f_group in ({$this->params->gr}) ";
}
$this->execSql('insert_goods', $sql);

        #begin qty store
        $sql = <<<EOD
        update t_rep t
        left join (
        select s.f_goods, sum(s.f_qty*s.f_type) as f_qty
        from a_store_draft s
        inner join a_header h on h.f_id=s.f_document
        $join
        where h.f_date < '{$this->params->d1}' and h.f_state=1 and g.f_unit<>10 and s.f_store in ($baseStores) $whereGroup
        group by s.f_goods
        ) s on s.f_goods = t.f_goods
        set t.f_start2=COALESCE(s.f_qty, 0)
        EOD;
        $this->execSql('begin_store', $sql);

        $sql = <<<EOD
        update t_rep t
        left join (
        SELECT gc.f_goods, sum(gc.f_qty*s.f_qty*s.f_type) as f_qty
        from a_store_draft s
        inner join a_header h on h.f_id=s.f_document
        inner join c_goods g on g.f_id=s.f_goods
        INNER JOIN c_goods_complectation gc ON gc.f_base=s.f_goods
        where h.f_date < '{$this->params->d1}' AND g.f_unit=10 and s.f_store in ($baseStores) $whereGroup
        group by gc.f_goods
        ) s on s.f_goods = t.f_goods
        set t.f_start2=t.f_start2+COALESCE(s.f_qty, 0)
        EOD;
        $this->execSql('begin_store_complect', $sql);
        $this->heartbeat('begin_store_complect');

#  begin qty shop (Обычные товары)
$goodsFilter = "g.f_unit <> 10";
if (!empty($this->params->gr)) {
    $goodsFilter .= " AND g.f_group IN ({$this->params->gr})";
}
$sql = <<<EOD
UPDATE t_rep t
LEFT JOIN (
    SELECT s.f_goods, SUM(s.f_qty * s.f_type) as f_qty
    FROM a_store_draft s
    INNER JOIN a_header h ON h.f_id = s.f_document
    INNER JOIN c_goods g ON g.f_id = s.f_goods
    WHERE h.f_date < '{$this->params->d1}'   and h.f_state=1
      AND s.f_store IN ($whereStore) 
      AND $goodsFilter
    GROUP BY s.f_goods
) s ON s.f_goods = t.f_goods
SET t.f_startqty = COALESCE(s.f_qty, 0)
EOD;
        $this->execSql('begin_shop', $sql);

# Подготавливаем фильтр для комплектов
$complectFilter = "g.f_unit = 10";
if (!empty($this->params->gr)) {
    $complectFilter .= " AND g.f_group IN ({$this->params->gr})";
}

# begin qty shop complect (Комплекты)

$sql = <<<EOD
UPDATE t_rep t
LEFT JOIN (
    SELECT gc.f_goods, SUM(gc.f_qty * s.f_qty * s.f_type) as f_qty
    FROM a_store_draft s
    INNER JOIN a_header h ON h.f_id = s.f_document
    INNER JOIN c_goods g ON g.f_id = s.f_goods
    INNER JOIN c_goods_complectation gc ON gc.f_goods = g.f_id
    WHERE h.f_date < '{$this->params->d1}'  and h.f_state=1
      AND s.f_store IN ($whereStore) 
      AND $complectFilter
    GROUP BY gc.f_goods
) s ON s.f_goods = t.f_goods
SET t.f_startqty = t.f_startqty + COALESCE(s.f_qty, 0)
EOD;
        $this->execSql('begin_shop_complect', $sql);

        #input qty
        $sql = <<<EOD
        update t_rep t
        left join (
        select s.f_goods, sum(s.f_qty*s.f_type) as f_qty
        from a_store_draft s
        inner join a_header h on h.f_id=s.f_document
        $join
        where h.f_date between '{$this->params->d1}' and '{$this->params->d2}'  and h.f_state=1 and s.f_type=1 and g.f_unit<>10 and s.f_store in ($inputStore) $whereGroup
        group by s.f_goods
        ) s on s.f_goods = t.f_goods
        set t.f_input=t.f_input+COALESCE(s.f_qty, 0)
        EOD;
        $this->execSql('input', $sql);

        #sale qty
        $sql = <<<EOD
        update t_rep t
        left join (
        select s.f_goods, sum(s.f_qty*s.f_sign) as f_qty
        from o_goods s
        inner join o_header h on h.f_id=s.f_header
        $join
        where h.f_datecash between '{$this->params->d1}' and '{$this->params->d2}'
        and g.f_unit<>10 and s.f_store in ($whereStore) $whereGroup
        group by s.f_goods
        ) s on s.f_goods = t.f_goods
        set t.f_sale=t.f_sale+COALESCE(s.f_qty, 0)
        EOD;
        $this->execSql('sale_ogoods', $sql);

        $sql = <<<EOD
        update t_rep t
        left join (
        SELECT gc.f_goods, sum(gc.f_qty*s.f_qty*s.f_type*-1) as f_qty
        from a_store_draft s
        inner join a_header h on h.f_id=s.f_document
        inner join c_goods g on g.f_id=s.f_goods
        INNER JOIN c_goods_complectation gc ON gc.f_base=g.f_id
        where h.f_date between '{$this->params->d1}' and '{$this->params->d2}'
        and s.f_reason=4 and g.f_unit=10 and s.f_store in ($whereStore) $whereGroup
        group by gc.f_goods
        ) s on s.f_goods = t.f_goods
        set t.f_sale=t.f_sale+COALESCE(s.f_qty, 0)
        EOD;
        $this->execSql('sale_complect', $sql);
		$this->heartbeat('sale_complect');

        #other output
        $sql = <<<EOD
        update t_rep t
        left join (
        select s.f_goods, sum(s.f_qty*s.f_type) as f_qty
        from a_store_draft s
        inner join a_header h on h.f_id=s.f_document
        $join
        where h.f_date between '{$this->params->d1}' and '{$this->params->d2}'  and h.f_state=1 and s.f_type=-1 and s.f_reason<>4 and g.f_unit<>10 and s.f_store in ($whereStore, $baseStores) $whereGroup
        group by s.f_goods
        ) s on s.f_goods = t.f_goods
        set t.f_output=t.f_output+COALESCE(s.f_qty, 0)
        EOD;
        $this->execSql('output', $sql);

        $sql = <<<EOD
        update t_rep t
        left join (
        SELECT gc.f_goods, sum(gc.f_qty*s.f_qty*s.f_type) as f_qty
        from a_store_draft s
        inner join a_header h on h.f_id=s.f_document
        inner join c_goods g on g.f_id=s.f_goods
        INNER JOIN c_goods_complectation gc ON gc.f_base=g.f_id
        where h.f_date between '{$this->params->d1}' and '{$this->params->d2}' and s.f_type=1 and s.f_reason<>4 and g.f_unit=10 and s.f_store in ($whereStore, $baseStores) $whereGroup
        group by gc.f_goods
        ) s on s.f_goods = t.f_goods
        set t.f_output=t.f_output+COALESCE(s.f_qty, 0)
        EOD;
        $this->execSql('output_complect', $sql);

        #final qty store
        $sql = <<<EOD
        update t_rep t
        left join (
        select s.f_goods, sum(s.f_qty*s.f_type) as f_qty
        from a_store s
        inner join a_header h on h.f_id=s.f_document
        $join
        where h.f_date <= '{$this->params->d2}' and h.f_state=1 and g.f_unit<>10 and s.f_store in ($baseStores) $whereGroup
        group by s.f_goods
        ) s on s.f_goods = t.f_goods
        set t.f_final2=t.f_final2+COALESCE(s.f_qty, 0)
        EOD;
        $this->execSql('final_store', $sql);

        $sql = <<<EOD
        update t_rep t
        left join (
        SELECT gc.f_goods, sum(gc.f_qty*s.f_qty*s.f_type) as f_qty
        from a_store_draft s
        inner join a_header h on h.f_id=s.f_document
        inner join c_goods g on g.f_id=s.f_goods
        INNER JOIN c_goods_complectation gc ON gc.f_base=s.f_goods
        where h.f_date <= '{$this->params->d2}'  AND g.f_unit=10 and s.f_store in ($baseStores) $whereGroup
        group by gc.f_goods
        ) s on s.f_goods = t.f_goods
        set t.f_final2=t.f_final2+COALESCE(s.f_qty, 0)
        EOD;
        $this->execSql('final_store_complect', $sql);
		$this->heartbeat('final_store_complect');

        #final qty shop
        $sql = <<<EOD
        update t_rep t
        left join (
        select s.f_goods, sum(s.f_qty*s.f_type) as f_qty
        from a_store s
        inner join a_header h on h.f_id=s.f_document
        $join
        where h.f_date <= '{$this->params->d2}' and h.f_state=1 and g.f_unit<>10 and s.f_store in ($whereStore) $whereGroup
        group by s.f_goods
        ) s on s.f_goods = t.f_goods
        set t.f_final=t.f_final+COALESCE(s.f_qty, 0)
        EOD;
        $this->execSql('final_shop', $sql);

        $sql = <<<EOD
        update t_rep t
        left join (
        SELECT gc.f_goods, sum(gc.f_qty*s.f_qty*s.f_type) as f_qty
        from a_store_draft s
        inner join a_header h on h.f_id=s.f_document
        inner join c_goods g on g.f_id=s.f_goods
        INNER JOIN c_goods_complectation gc ON gc.f_base=g.f_id
        where h.f_date <= '{$this->params->d2}' and h.f_state=1 AND g.f_unit=10 and s.f_store in ($whereStore) $whereGroup
        group by gc.f_goods
        ) s on s.f_goods = t.f_goods
        set t.f_final=t.f_final+COALESCE(s.f_qty, 0)
        EOD;
        $this->execSql('final_shop_complect', $sql);

        $this->execSql('delete_empty', "delete from t_rep where f_startqty<=0 and f_input<=0 and f_sale<=0 and f_final<=0 ");

        if ($this->sqlLogEnabled()) {
            $ms = round((microtime(true) - $reportStarted) * 1000, 2);
            error_log(sprintf('[Effectivness] fillTempReport total: %s ms', $ms));
        }
    }
}

$e = new Effectivness();
$e->echoResult();
