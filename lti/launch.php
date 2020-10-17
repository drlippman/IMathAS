<?php

/**
 * Handle a launch request from the LMS, after OIDC login
 */

// need session to hold launch cache
$init_session_start = true;
$init_skip_csrfp = true;
require('../init_without_validate.php');
require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

use \IMSGlobal\LTI;
$launch = LTI\LTI_Message_Launch::new(new Imathas_LTI_Database($DBH))
    ->validate();

// Get LMS user ID
$lti_user_id = $launch->get_platform_user_id();

// If a user is logged in, and isn't a lti user or different lti user, clear it
if (isset($_SESSION['userid']) &&
  (!isset($_SESSION['lti_user_id']) || $_SESSION['lti_user_id'] !== $lti_user_id)
) {
  session_destroy();
  session_start();
  session_regenerate_id();
  $_SESSION = array();
  // need to recache launch data since we've cleared the session
  $launch->cache_launch_data();
}

// TODO: Look for lti1p1 claim for remapping userid a/o contextid

require(__DIR__ .'/show_postback_form.php');
show_postback_form($launch, new Imathas_LTI_Database($DBH));
