<?php

/**
 * This is a HACKY start just to do testing.  Eventually flush
 * this out with ability for teachers or group admins to edit their
 * own registrations, and customize for each LMS.
 *
 */

require("../../init.php");

if ($myrights < 100) {
  exit;
}

if (isset($_POST['delete'])) {
  $stm = $DBH->prepare("DELETE FROM imas_lti_issuers WHERE id=?");
  $stm->execute(array($_POST['delete']));
  header('Location: ' . $basesiteurl . "/lti/admin/platforms.php");
  exit;
}
if (!empty(trim($_POST['issuer'])) &&
  !empty(trim($_POST['clientid'])) &&
  !empty(trim($_POST['keyseturl'])) &&
  !empty(trim($_POST['tokenurl'])) &&
  !empty(trim($_POST['authurl']))
) {
  $stm = $DBH->prepare("INSERT INTO imas_lti_issuers (issuer,client_id,auth_login_url,auth_token_url,key_set_url) VALUES (?,?,?,?,?)");
  $stm->execute(array(
    trim($_POST['issuer']),
    trim($_POST['clientid']),
    trim($_POST['authurl']),
    trim($_POST['tokenurl']),
    trim($_POST['keyseturl'])
  ));
  header('Location: ' . $basesiteurl . "/lti/admin/platforms.php");
  exit;
}

require("../../header.php");

echo '<h1>'._('LTI 1.3 Platforms').'</h1>';
echo '<h2>'._('Existing Platforms').'</h2>';
$stm = $DBH->query('SELECT id,issuer,client_id,created_at FROM imas_lti_issuers WHERE 1');
echo '<form method="post" action="platforms.php">';
echo '<ul class=nomark>';
if ($stm->rowCount()===0) {
  echo '<li>'._('No platforms').'</li>';
}
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
  echo '<li>'._('Issuer: ').Sanitize::encodeStringForDisplay($row['issuer']);
  echo ', '._('ClientID: ').Sanitize::encodeStringForDisplay($row['client_id']);
  echo ', '._('Created: ') . date("M j Y ", strtotime($row['created_at']));
  echo ' <button type=submit name="delete" value="'.Sanitize::encodeStringForDisplay($row['id']).'" ';
  echo 'onclick="return confirm(\''._('Are you SURE you want to delete this platform?').'\');">';
  echo _('Delete').'</button>';
  echo '</li>';
}
echo '</ul>';

echo '<h2>'._('New Platform').'</h2>';
echo '<p>'._('Info to put in the LSM').'</p>';
echo '<ul class=nomark>';
echo '<li>'._('Target Link URI/Tool URL/Redirect URI:').' '.$basesiteurl.'/lti/launch.php</li>';
echo '<li>'._('OpenID Connect / Initiate Login URL:').' '.$basesiteurl.'/lti/login.php</li>';
echo '<li>'._('Keyset URL:').' '.$basesiteurl.'/lti/jwks.php</li>';
echo '</ul>';
echo '<p>'._('Info from LSM').'</p>';
echo '<ul class=nomark>';
echo '<li>'._('Issuer/Platform ID:').' <input name=issuer size=50/></li>';
echo '<li>'._('Client ID:').' <input name=clientid size=50/></li>';
echo '<li>'._('Keyset URL:').' <input name=keyseturl size=50/></li>';
echo '<li>'._('Token URL:').' <input name=tokenurl size=50/></li>';
echo '<li>'._('Authentication URL:').' <input name=authurl size=50/></li>';
echo '</ul>';
echo '<button type=submit>'._('Add Platform').'</button></p>';
echo '</form>';
require('../../footer.php');
