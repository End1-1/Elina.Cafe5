<?php
# (C) 2026 Kudryashov Vasili
# Named C5 report (migrated from reports table, f_id=4).

return [
    "legacy_id"      => 4,
    "name"           => "Վաճառք/Եկամուտ",
    "group_name"     => "Վաճառք/Եկամուտ",
    "menu_level"     => 2,
    // reports_permissions.f_group with f_access=1 for this report.
    "permissions"    => [1],
    "icon"           => "documents.png",
    "sum_columns"    => "4,5,7",
    "handler"        => null,
    "delete_handler" => null,
    "filter_handler" => "0baed59d-cc9c-11ed-8a65-1078d2d2b808",
    "query"          => <<<SQL
SELECT concat(oh.f_prefix, oh.f_hallid) AS  `Պատվեր`,cp.f_taxname AS `Մատակարար`,
gr.f_name as  `Խումբ`,gg.f_name  AS `Ապրանք`,
sum(sss.f_qty*og.f_sign)  AS `Քանակ`,
sum(sss.f_qty*(og.f_total/og.f_qty)*og.f_sign)  AS `Գումար`,
sum(ad.f_price*sss.f_qty*og.f_sign*cr.f_rate)  AS `Ինքնարժեք`,
sum(sss.f_qty*og.f_price*og.f_sign) - sum(ad.f_price*sss.f_qty*og.f_sign*cr.f_rate)  AS `Եկամուտ`,
100-((sum(ad.f_price*sss.f_qty*og.f_sign*cr.f_rate)/sum(sss.f_qty*(og.f_total/og.f_qty)*og.f_sign))*100) AS `Տոկոս`
from o_goods og 
left join o_header oh on oh.f_id=og.f_header
left join c_goods gg on gg.f_id=og.f_goods 
left join c_groups gr on gr.f_id=gg.f_group
left join a_store_draft ad on ad.f_id=og.f_storerec
LEFT JOIN a_store sss ON sss.f_draft=ad.f_id
LEFT JOIN a_store sss2 ON sss2.f_id=sss.f_base 
left join a_header ah on ah.f_id=sss2.f_document 
left join c_partners cp on cp.f_id=ah.f_partner 
left join e_currency_cross_rate cr on cr.f_currency1=ah.f_currency and cr.f_currency2=1  
WHERE  oh.f_datecash between %date1 and %date2 %filter  
group BY 1,2,concat(oh.f_prefix, oh.f_hallid),cp.f_taxname,gr.f_name,gg.f_name
SQL,
];
