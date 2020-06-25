<?php
require('../init_without_validate.php');
require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

use \IMSGlobal\LTI;
$launch = LTI\LTI_Message_Launch::new(new Imathas_LTI_Database($DBH))
    ->validate();

if ($launch->is_deep_link_launch()) {
    echo 'Is deep linking request - do something';
} else if ($launch->is_submission_review_launch()) {
    echo 'Is submission review launch';
} else if ($launch->is_resource_launch()) {
    echo 'Is resource link launch - do something';
    $role = standardize_role($launch->get_roles());

}
