<?php
require("../../validate.php");

if (empty($_GET['linkid'])) {
	echo "no link id provided";
	exit;
}
$query = "SELECT text,title FROM imas_linkedtext WHERE id='{$_GET['linkid']}'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$text = mysql_result($result, 0,0);
$title = mysql_result($result,0,1);
list($tool,$linkcustom) = explode('~~',substr($text,8));
$tool = intval($tool);

$query = "SELECT * from imas_external_tools WHERE id=$tool AND (courseid='$cid' OR (courseid=0 AND (groupid='$groupid' OR groupid=0)))";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)==0) {
	echo '<html><body>Invalid tool</body></html>';
	exit;
}
$line = mysql_fetch_array($result, MYSQL_ASSOC);	

require_once("blti_util.php");
$parms = array();

if (trim($line['custom'])!='') {
	$toolcustarr = explode('&',$line['custom']);
	foreach ($toolcustarr as $custbit) {
		$pt = explode('=',$custbit);
		if (count($pt)==2 && trim($pt[0])!='' && trim($pt[1])!='') {
			$pt[0] = map_keyname($pt[0]);
			$parms['custom_'.$pt[0]] = str_replace(array('$cid','$userid','$linkid'),array($cid,$userid,intval($_GET['linkid'])),$pt[1]);
		}
	}
}
if (trim($linkcustom)!='') {
	$toolcustarr = explode('&',$linkcustom);
	foreach ($toolcustarr as $custbit) {
		$pt = explode('=',$custbit);
		if (count($pt)==2 && trim($pt[0])!='' && trim($pt[1])!='') {
			$pt[0] = map_keyname($pt[0]);
			$parms['custom_'.$pt[0]] = str_replace(array('$cid','$userid','$linkid'),array($cid,$userid,intval($_GET['linkid'])),$pt[1]);
		}
	}
}

$query = "SELECT FirstName,LastName,email FROM imas_users WHERE id='$userid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
list($firstname,$lastname,$email) = mysql_fetch_row($result);
$parms['user_id'] = $userid;
if (($line['privacy']&1)==1) {
	$parms['lis_person_name_full'] = "$firstname $lastname";
	$parms['lis_person_name_family'] = $lastname;
	$parms['lis_person_name_given'] = $firstname;
}
if (($line['privacy']&2)==2) {
	$parms['lis_person_contact_email_primary'] = $email;
}
if (isset($teacherid)) {
	$parms['roles'] = 'Instructor';
} else {
	$parms['roles'] = 'Learner';
}

$parms['context_id'] = $cid;
$parms['context_title'] = trim($coursename);
$parms['context_label'] = trim($coursename);
$parms['context_type'] = 'CourseSection';
$parms['resource_link_id'] = $cid.'-'.$_GET['linkid'];
$parms['resource_link_title'] = $title;
$parms['tool_consumer_info_product_family_code'] = 'IMathAS';
$parms['tool_consumer_info_version'] = 'LTI 1.0';
if (isset($CFG['GEN']['locale'])) {
	$parms['launch_presentation_locale'] = $CFG['GEN']['locale'];
} else {
	$parms['launch_presentation_locale'] = 'en-US';
}
if ($_GET['target']=='new') {
	$parms['launch_presentation_document_target'] = 'window';
} else {
	$parms['launch_presentation_document_target'] = 'iframe';
	$parms['launch_presentation_height'] = '500';
	$parms['launch_presentation_width'] = '600';
}
$parms['launch_presentation_return_url'] = $urlmode . $_SERVER['HTTP_HOST'] . $imasroot . '/course/course.php?cid=' . $cid;

if (isset($CFG['GEN']['LTIorgid'])) {
	$org_id = $CFG['GEN']['LTIorgid'];
} else {
	$org_id = $_SERVER['HTTP_HOST'];
}

$org_desc = $installname;

try {
	$parms = signParameters($parms, $line['url'], "POST", $line['ltikey'], $line['secret'], null, $org_id, $org_desc);
	$content = postLaunchHTML($parms, $line['url'],isset($parms['custom_debug']));
	print($content);
} catch (Exception $e) {
	echo $e->getMessage();
}
?>