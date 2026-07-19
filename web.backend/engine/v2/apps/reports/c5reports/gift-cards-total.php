<?php
# (C) 2026 Kudryashov Vasili
# Named C5 report (migrated from reports table, f_id=1).

return [
    "legacy_id"      => 1,
    "name"           => "Նվեր քարտերի ընդհանուր",
    "group_name"     => "Նվեր քարտեր",
    "menu_level"     => 2,
    // reports_permissions.f_group with f_access=1 for this report.
    "permissions"    => [1],
    "icon"           => "documents.png",
    "sum_columns"    => "5,6,7",
    "handler"        => "ff64e987-a0b2-11ef-b479-022165c6dab1",
    "delete_handler" => null,
    "filter_handler" => null,
    "query"          => <<<SQL
SELECT b.f_id AS 'NN', b.f_code AS 'Կոդ', b.f_datesaled AS 'Վաճառված է', GROUP_CONCAT('', oh.f_datecash) AS 'Օգտագործման ամսաթիվ',
CONCAT_WS(' ', p.f_phone, p.f_taxname, p.f_contact) AS 'Հաճախորդ',
bs.f_amount AS 'Առժեք', COALESCE(bs.f_amount,0)-COALESCE(bh.f_amount, 0) AS 'Օգտագործում', 
bh.f_amount AS 'Մնացորդ' 
FROM  b_gift_card b
LEFT JOIN (SELECT f_card, f_amount FROM b_gift_card_history WHERE f_amount>0) AS bs ON bs.f_card=b.f_id
LEFT JOIN (SELECT f_card, SUM(f_amount) AS f_amount FROM b_gift_card_history GROUP BY 1) bh ON bh.f_card=b.f_id
LEFT JOIN b_gift_card_history bhh ON bhh.f_card=b.f_id
LEFT JOIN o_header oh ON oh.f_id=bhh.f_trsale AND bhh.f_amount<0
LEFT JOIN c_partners p ON b.f_costumer=p.f_id
GROUP BY 1
SQL,
];
