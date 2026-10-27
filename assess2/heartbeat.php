<?php
/*
 * IMathAS: Assessment heartbeat endpoint
 * (c) 2026 David Lippman
 *
 * Keeps this session's claim on a timed/passworded assessment attempt fresh.
 * The claim check itself happens in common_start.php.
 *
 * Method: GET
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *
 * Returns: {"ok": 1} or {"error": ...}
 */

$no_session_handler = 'json_error';
$init_csrfp_scope = 'question';
require_once "../init.php";
require_once "./common_start.php";

check_for_required('GET', array('aid', 'cid'));
echo '{"ok": 1}';
