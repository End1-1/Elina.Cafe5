<?php
# (C) 2026 Kudryashov Vasili
# Registry of named C5 reports (replaces reports / reports_group / reports_permissions tables).
# Each report is a separate file in this folder returning an associative array.

/**
 * Load every report definition from this folder.
 *
 * @return array<string, array> keyed by file base name (report key)
 */
function c5reports_all(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = [];
    $skip = ["index.php", "handlers.php", "list.php"];
    foreach (glob(__DIR__ . "/*.php") as $file) {
        $base = basename($file);
        if (in_array($base, $skip, true)) {
            continue;
        }
        $def = require $file;
        if (!is_array($def) || empty($def["legacy_id"])) {
            continue;
        }
        $def["key"] = pathinfo($base, PATHINFO_FILENAME);
        $cache[$def["key"]] = $def;
    }

    return $cache;
}

/**
 * Report definitions available for a user group.
 *
 * @return array list of report definitions
 */
function c5reports_for_group(int $group): array
{
    $out = [];
    foreach (c5reports_all() as $def) {
        // f_group == 1 is admin and sees everything (mirrors old behaviour).
        if ($group === 1 || in_array($group, $def["permissions"] ?? [], true)) {
            $out[] = $def;
        }
    }
    return $out;
}

/**
 * Find one report by its legacy numeric id.
 */
function c5reports_by_legacy_id(int $legacyId): ?array
{
    foreach (c5reports_all() as $def) {
        if ((int)$def["legacy_id"] === $legacyId) {
            return $def;
        }
    }
    return null;
}

/**
 * Resolve a handler UUID to its SQL/filter definition.
 */
function c5reports_handler(?string $uuid): ?string
{
    if (empty($uuid)) {
        return null;
    }
    static $handlers = null;
    if ($handlers === null) {
        $handlers = require __DIR__ . "/handlers.php";
    }
    return $handlers[$uuid] ?? null;
}
