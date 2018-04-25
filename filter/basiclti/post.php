<?php
require("../../init.php");

if (empty($_GET['linkid'])) {
	echo "no link id provided";
	exit;
}
$linkid = Sanitize::onlyInt($_GET['linkid']);
//DB $query = "SELECT text,title,points FROM imas_linkedtext WHERE id='{$_GET['linkid']}'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB list($text,$title,$points) = mysql_fetch_row($result);
$stm = $DBH->prepare("SELECT text,title,points FROM imas_linkedtext WHERE id=:id");
$stm->execute(array(':id'=>$linkid));
list($text,$title,$points) = $stm->fetch(PDO::FETCH_NUM);
$toolparts = explode('~~',substr($text,8));
$tool = $toolparts[0];
$linkcustom = $toolparts[1];
if (isset($toolparts[2]) && $toolparts[2]!="") {
	$toolcustomurl = $toolparts[2];
} else {
	$toolcustomurl = '';
}
if (isset($toolparts[3])) {
	$gbcat = $toolparts[3];
	$cntingb = $toolparts[4];
	$tutoredit = $toolparts[5];
	$gradesecret = $toolparts[6];
}
$tool = intval($tool);

//DB $query = "SELECT * from imas_external_tools WHERE id=$tool AND (courseid='$cid' OR (courseid=0 AND (groupid='$groupid' OR groupid=0)))";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$stm = $DBH->prepare("SELECT * from imas_external_tools WHERE id=:id AND (courseid=:courseid OR (courseid=0 AND (groupid=:groupid OR groupid=0)))");
$stm->execute(array(':id'=>$tool, ':courseid'=>$cid, ':groupid'=>$groupid));
if ($stm->rowCount()==0) {
	echo '<html><body>Invalid tool</body></html>';
	exit;
}
//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
$line = $stm->fetch(PDO::FETCH_ASSOC);

require_once("blti_util.php");
$parms = array();

if (trim($line['custom'])!='') {
	$toolcustarr = explode('&',$line['custom']);
	foreach ($toolcustarr as $custbit) {
		$pt = explode('=',$custbit);
		if (count($pt)==2 && trim($pt[0])!='' && trim($pt[1])!='') {
			$pt[0] = map_keyname($pt[0]);
			$parms['custom_'.$pt[0]] = str_replace(array('$cid','$userid','$linkid'),array($cid,$userid,$linkid),$pt[1]);
		}
	}
}
if (trim($linkcustom)!='') {
	$toolcustarr = explode('&',$linkcustom);
	foreach ($toolcustarr as $custbit) {
		$pt = explode('=',$custbit);
		if (count($pt)==2 && trim($pt[0])!='' && trim($pt[1])!='') {
			$pt[0] = map_keyname($pt[0]);
			$parms['custom_'.$pt[0]] = str_replace(array('$cid','$userid','$linkid'),array($cid,$userid,$linkid),$pt[1]);
		}
	}
}

//DB $query = "SELECT FirstName,LastName,email FROM imas_users WHERE id='$userid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB list($firstname,$lastname,$email) = mysql_fetch_row($result);
$stm = $DBH->prepare("SELECT FirstName,LastName,email FROM imas_users WHERE id=:id");
$stm->execute(array(':id'=>$userid));
list($firstname,$lastname,$email) = $stm->fetch(PDO::FETCH_NUM);
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
$parms['resource_link_id'] = $cid.'-'.$linkid;

if ($points>0 && isset($studentid) && !isset($sessiondata['stuview'])) {
	$sig = sha1($gradesecret.'::'.$parms['resource_link_id'].'::'.$userid);
	$parms['lis_result_sourcedid'] = $sig.'::'.$parms['resource_link_id'].'::'.$userid;
	$parms['lis_outcome_service_url'] = $GLOBALS['basesiteurl'] . '/admin/ltioutcomeservice.php';
}

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
$parms['launch_presentation_return_url'] = $GLOBALS['basesiteurl'] . '/course/course.php?cid=' . $cid;

if (isset($CFG['GEN']['LTIorgid'])) {
	$org_id = $CFG['GEN']['LTIorgid'];
} else {
	$org_id = Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']);
}

$org_desc = $installname;

if ($toolcustomurl!='') {
	$line['url'] = $toolcustomurl;
}

if ($line['url']=='') {
	echo '<html><body>This tool does not have a default launch URL.  Custom launch URL is required.</body></html>';
	exit;
}

try {
	$parms = signParameters($parms, $line['url'], "POST", $line['ltikey'], $line['secret'], null, $org_id, $org_desc);
	$content = postLaunchHTML($parms, $line['url'], isset($parms['custom_debug']));
	print($content);
} catch (Exception $e) {
	echo $e->getMessage();
}
?>
