<?php
# (C) 2026 Kudryashov Vasili
# Named C5 report (migrated from reports table, f_id=2).

return [
    "legacy_id"      => 2,
    "name"           => "Սևագիր վաճառքմեր",
    "group_name"     => "Վաճառք",
    "menu_level"     => 2,
    // reports_permissions.f_group with f_access=1 for this report.
    "permissions"    => [1, 9],
    "icon"           => "documents.png",
    "sum_columns"    => null,
    "handler"        => "39617ca7-8fa4-11ed-8ad3-1078d2d2b808",
    "delete_handler" => "67edc699-93e5-11ed-bf0e-1078d2d2b808",
    "filter_handler" => null,
    "date_filter"    => true,
    "query"          => <<<SQL
SELECT f_id as `Կոդ`, f_date as `Ամսաթիվ`, f_time as `Ժամ`, f_amount as `Գումար`, f_comment as `Մեկնաբանություն` FROM o_draft_sale
WHERE f_state=1 AND f_date BETWEEN %date1 AND %date2
ORDER BY f_date DESC, f_time DESC
SQL,
];
