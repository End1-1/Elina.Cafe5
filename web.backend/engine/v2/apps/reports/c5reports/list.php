<?php
# (C) 2026 Kudryashov Vasili
# Menu list for named C5 reports (duplicate of legacy list.php c5reports section).

require_once __DIR__ . "/index.php";

/**
 * Build menu entries for the current user group.
 *
 * @return array<int, array{f_id:int, f_level:int, f_name:string, image:string, route:string}>
 */
function c5reports_build_menu_list(int $group): array
{
    $reports = [];
    foreach (c5reports_for_group($group) as $def) {
        $reports[] = [
            "f_id"    => (int)$def["legacy_id"],
            "f_level" => (int)$def["menu_level"],
            "f_name"  => $def["name"],
            "image"   => $def["icon"] ?? "documents.png",
            "route"   => "/engine/v2/reports/c5reports/get",
        ];
    }
    return $reports;
}
