<?php
/*
 * IMathAS: Assessment heartbeat endpoint
 * (c) 2026 David Lippman
 *
 * Keeps this session's claim on a timed/passworded assessment attempt fresh.
 * Deliberately lightweight: no validate.php, just the session's userid.
 *
 * Method: GET
 * Query string parameters:
 *  aid   Assessment ID
 *
 * Returns: {"ok": 1} or {"error": ...}
 */

// Skip full validate: the claim check only needs the session's userid and aid.
$init_skip_validate = true;
$init_session_start = true;
require_once "../init.php";

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

if (empty($_SESSION['userid'])) {
  echo '{"error": "no_session"}';
  exit;
}
if (empty($_GET['aid'])) {
  echo '{"error": "missing_param", "error_details": "Missing parameter aid"}';
  exit;
}

require_once __DIR__ . '/AssessClaim.php';
AssessClaim::verify(intval($_SESSION['userid']), intval($_GET['aid']));
echo '{"ok": 1}';
