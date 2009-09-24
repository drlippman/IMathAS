<?php
require("../../validate.php");
if (empty($_GET['key']) || empty($_GET['url']) || empty($_GET['linkback'])) {
	echo 'no key or url or linkback';
	exit;
}
$key = $_GET['key'];
$endpoint = $_GET['url'];
if (empty($sessiondata['lti-secrets'][$key])) {
	echo 'no secret';
	exit;
}
$secret = $sessiondata['lti-secrets'][$key];
unset($sessiondata['lti-secrets'][$key]);
writesessiondata();

require_once("blti_util.php");
$parms = array();

$query = "SELECT FirstName,LastName,email FROM imas_users WHERE id='$userid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
list($firstname,$lastname,$email) = mysql_fetch_row($result);
$parms['user_id'] = $userid;
$parms['lis_person_name_full'] = "$firstname $lastname";
$parms['lis_person_name_last'] = $lastname;
$parms['lis_person_name_first'] = $firstname;
$parms['lis_person_contact_email_primary'] = $email;

if (isset($teacherid)) {
	$parms['roles'] = 'Instructor';
} else {
	$parms['roles'] = 'Student';
}

if (isset($_GET['cid'])) {
	$query = "SELECT name FROM imas_courses WHERE id='{$_GET['cid']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$parms['context_id'] = $_GET['cid'];
	$parms['context_title'] = mysql_result($result,0,0);
	$parms['resource_link_id'] = $_GET['cid'].'-'.$_GET['linkback'];
} else {
	$parms['resource_link_id'] = '0-'.$_GET['linkback'];
}
$org_id = $_SERVER['HTTP_HOST'];
$org_desc = $installname;

try {
	$parms = signParameters($parms, $endpoint, "POST", $key, $secret, null, $org_id, $org_desc);
	$content = postLaunchHTML($parms, $endpoint, false);
	print($content);
} catch (Exception $e) {
	echo $e->getMessage();
}
?>