<?php
/*
 * IMathAS: Release this session's claim on an assessment attempt
 * (c) 2026 David Lippman
 *
 * Sent with navigator.sendBeacon when the student leaves the assessment page.
 * Only deletes the claim held by the calling session, so it is harmless to
 * skip CSRF protection (sendBeacon can't send the CSRF header).
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 */

$no_session_handler = 'json_error';
$init_skip_csrfp = true;
require_once "../init.php";
require_once "./AssessClaim.php";

if (!empty($_GET['aid']) && !empty($userid)) {
  AssessClaim::release(intval($_GET['aid']));
}
echo '{"ok": 1}';
