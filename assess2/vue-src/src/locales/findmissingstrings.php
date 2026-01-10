<?php

/**
 * findmissingstrings.php
 *
 * Usage (CLI):
 *   php findmissingstrings.php
 *
 * This script reads `en.ftl` in the target directory, collects the message
 * and term identifiers defined there, then compares other `.ftl` files in the
 * same directory and reports which identifiers from `en.ftl` are missing in
 * each file.
 */

// Detect mode and target directory
$is_cli = (php_sapi_name() == 'cli');
$targetDir = __DIR__;
$targetDir = rtrim($targetDir, "\\/");

if (!$is_cli) {
    exit;
}

// Helper: parse .ftl file and return array of ids
function parse_ftl_file($path) {
    if (!is_readable($path)) return [];
    $content = file($path, FILE_IGNORE_NEW_LINES);
    $ids = [];
    foreach ($content as $line) {
        $trim = ltrim($line);
        // skip comments
        if ($trim === '' || strpos($trim, '#') === 0 || strpos($trim, '//') === 0) continue;
        // Match message or term identifiers at start of a definition. We want
        // to capture identifiers that start with a letter and may have
        // letters, digits, dots, underscores or hyphens. Terms (starting with
        // a dash) are allowed too (e.g. -term).
        if (preg_match('/^\s*(-?[A-Za-z][A-Za-z0-9_.-]*)\s*=/', $line, $m)) {
            $ids[$m[1]] = true;
        }
    }
    $keys = array_keys($ids);
    sort($keys, SORT_STRING);
    return $keys;
}

// Read en.ftl
$enPath = $targetDir . DIRECTORY_SEPARATOR . 'en.ftl';
if (!file_exists($enPath)) {
    $msg = "en.ftl not found in: $targetDir";
    if ($is_cli) {
        fwrite(STDERR, $msg . PHP_EOL);
        exit(1);
    } else {
        echo '<pre>' . htmlspecialchars($msg) . '</pre>';
        exit;
    }
}
$enIds = parse_ftl_file($enPath);

// Collect other .ftl files
$files = glob($targetDir . DIRECTORY_SEPARATOR . '*.ftl');
$results = [];
foreach ($files as $file) {
    if (basename($file) === 'en.ftl') continue;
    $ids = parse_ftl_file($file);
    $missing = array_values(array_diff($enIds, $ids));
    sort($missing, SORT_STRING);
    $results[basename($file)] = $missing;
}

// Prepare output
$out = [];
$totalMissing = 0;
foreach ($results as $fname => $missing) {
    $count = count($missing);
    $totalMissing += $count;
    $out[] = "File: $fname  —  Missing: $count";
    if ($count) {
        foreach ($missing as $id) {
            $out[] = "  - $id";
        }
    }
    $out[] = "";
}
$out[] = "Summary: checked " . count($results) . " file(s). Total missing strings: $totalMissing";

// Output according to mode
if ($is_cli) {
    echo implode(PHP_EOL, $out) . PHP_EOL;
} else {
    echo '<pre>' . htmlspecialchars(implode("\n", $out)) . '</pre>';
}

return 0;
