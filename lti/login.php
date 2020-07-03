<?php

/**
 * Handle the OIDC login request
 */

require('../init_without_validate.php');
require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';

use \IMSGlobal\LTI;

LTI\LTI_OIDC_Login::new(new Imathas_LTI_Database($DBH))
    ->do_oidc_login_redirect($GLOBALS['basesiteurl']. "/lti/launch.php")
    ->do_hybrid_redirect();
?>
