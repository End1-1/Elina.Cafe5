<?php
require_once __DIR__ . "/miura.php";

$sql = <<<EOD
select g.f_id as `id`, gr.f_name as `groupname`, g.f_name as `goodsname`,
coalesce(gp.f_price1, 0) as `price1`, coalesce(gp.f_price2, 0) as `price2`, 0 as specialflag, 
g.f_lowlevel as qtystop, coalesce(g.f_nospecial_price, 0) as nospecialprice 
from c_goods g 
left join c_groups gr on gr.f_id=g.f_group 
left join c_goods_prices gp on gp.f_goods=g.f_id 
where g.f_enabled=1 AND gp.f_currency=1
order by g.f_queue, g.f_name
EOD;
$goods = stmtall($sql)->fetch_all(MYSQLI_ASSOC);


$sql = <<<EOD
select distinct(gr.f_id) as `id`, gr.f_name as `name` 
from c_goods g 
left join c_groups gr on gr.f_id=g.f_group 
where g.f_enabled=1 
EOD;
$goodsgroup = stmtall($sql)->fetch_all(MYSQLI_ASSOC);

$sql = <<<EOD
select coalesce(pc.f_name, '') as category, pg.f_name as `group`, ps.f_name as status, p.f_id as id, coalesce(p.f_name, '') as name, 
p.f_address as address, p.f_taxname as taxname, coalesce(p.f_taxcode, '') as taxcode, coalesce(p.f_contact, '') as contact, 
coalesce(p.f_phone, '') as phonenumber, cast(p.f_permanent_discount as float) as discount, p.f_price_politic as pricepolitic 
from c_partners p 
left join c_partners_state ps on ps.f_id=p.f_state 
left join c_partners_group pg on pg.f_id=p.f_group 
left join c_partners_category pc on pc.f_id=p.f_category 
EOD;
$partners = stmtall($sql)->fetch_all(MYSQLI_ASSOC);

$sql = "select f_partner as partner, f_goods as goods, f_price as price from c_goods_special_prices";
$specialprices = stmtall($sql)->fetch_all(MYSQLI_ASSOC);

$sql = "select f_id as id, f_name as name from c_storages where f_state=1 ";
$storages = stmtall($sql)->fetch_all(MYSQLI_ASSOC);

$sql = <<<EOD
select distinct(u.f_id) as id, concat_ws(' ', u.f_last, u.f_first) as name 
from o_route_drivers r 
left join s_user u on u.f_id=r.f_user 
EOD;
$drivers = stmtall($sql)->fetch_all(MYSQLI_ASSOC);

$sql = "select f_partner as partner, f_goods as goods from c_goods_partner";
$partnersgoods = stmtall($sql)->fetch_all(MYSQLI_ASSOC);

$sql = <<<EOD
SELECT jc.f_config 
FROM sys_json_config jc
LEFT JOIN s_user u ON u.f_config=jc.f_id
WHERE u.f_id=$userid
EOD;
$config = stmtall($sql)->fetch_assoc();
$config = json_decode($config["f_config"]);

printResult(1, [
    "goods" => $goods,
    "goodsgroup" => $goodsgroup,
    "partners" => $partners,
    "specialprices" => $specialprices,
    "drivers" => $drivers,
    "partnersgoods" => $partnersgoods,
    "storages" => $storages,
    "config" => $config
]);