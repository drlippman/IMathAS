<?php

$init_session_start = true;
$init_skip_csrfp = true;
require('../init_without_validate.php');
require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

use \IMSGlobal\LTI;
if (!isset($_POST['launchid'])) {
  echo 'Error - missing launch id';
  exit;
}
$launch = LTI\LTI_Message_Launch::from_cache($_POST['launchid'], new Imathas_LTI_Database($DBH));

echo "Launch id:".$launch->get_launch_id().'</br>';
print_r($_POST);
