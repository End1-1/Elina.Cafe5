<?php
# (C) 2026 Kudryashov Vasili
# Named C5 report (migrated from reports table, f_id=3).
# Note: hall=10 is intentionally hardcoded in the query (kept as-is).

return [
    "legacy_id"      => 3,
    "name"           => "Օնլայն վաճառքի տեղաշարժ",
    "group_name"     => "Վաճառք",
    "menu_level"     => 2,
    // reports_permissions.f_group with f_access=1 for this report.
    "permissions"    => [9, 1],
    "icon"           => "documents.png",
    "sum_columns"    => "6",
    "handler"        => null,
    "delete_handler" => null,
    "filter_handler" => null,
    "query"          => <<<SQL
SELECT ah.f_date AS `Պահեստի ամսաթիվ`, oh.f_datecash AS `Վաճառքի ամսաթիվ`,
 cONCAT(oh.f_prefix,oh.f_hallid) AS `Պատվեր`, st.f_name AS `Պահեստ`, g.f_scancode AS `Բարկոդ`, 
 g.f_name AS `Անվանում`, og.f_qty*og.f_sign AS `Քանակ`
FROM o_goods og
INNER JOIN c_goods g ON g.f_id=og.f_goods
INNER JOIN o_header oh ON oh.f_id=og.f_header
inner JOIN a_header_store hs ON hs.f_saleuuid=oh.f_id and hs.f_storeout=og.f_store
LEFT JOIN a_header ah ON ah.f_id=hs.f_id
inner JOIN c_storages st ON st.f_id=hs.f_storeout
WHERE oh.f_hall=10  and oh.f_datecash BETWEEN %date1 AND %date2

union 

SELECT  ah.f_date AS `Պահեստի ամսաթիվ`, oh.f_datecash AS `Վաճառքի ամսաթիվ`,
 cONCAT(oh.f_prefix,oh.f_hallid) AS `Պատվեր`, st.f_name AS `Պահեստ`, g.f_scancode AS `Բարկոդ`, 
 g.f_name AS `Անվանում`, og.f_qty*og.f_sign AS `Քանակ`
FROM o_goods og
INNER JOIN c_goods g ON g.f_id=og.f_goods
INNER JOIN o_header oh ON oh.f_id=og.f_header
inner JOIN a_header_store hs ON hs.f_saleuuid=oh.f_id and hs.f_storeout<>23
LEFT JOIN a_header ah ON ah.f_id=hs.f_id
inner JOIN c_storages st ON st.f_id=hs.f_storeout
WHERE oh.f_hall=10  and oh.f_datecash BETWEEN %date1 AND %date2
SQL,
];
