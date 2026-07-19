<?php
# (C) 2026 Kudryashov Vasili
# Named C5 report (migrated from reports table, f_id=6).

return [
    "legacy_id"      => 6,
    "name"           => "Մենեջերների աշխատավարձ",
    "group_name"     => "Մենեջերների աշխատավարձ",
    "menu_level"     => 2,
    // reports_permissions.f_group with f_access=1 for this report.
    "permissions"    => [1],
    "icon"           => "documents.png",
    "sum_columns"    => "5",
    "handler"        => null,
    "delete_handler" => null,
    "filter_handler" => "f2051597-852d-11ee-8b5c-1078d2d2b808",
    "query"          => "call manager_salary(%date1, %date2)",
];
