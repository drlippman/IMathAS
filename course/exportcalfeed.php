<?php

require("../init.php");
require("../includes/JWT.php");

//grab user's hashed password to sign request with
$stm = $DBH->prepare("SELECT password FROM imas_users WHERE id=:uid");
$stm->execute(array(':uid'=>$userid));
$key = $stm->fetchColumn(0);
if (isset($_POST['textitemstype'])) {
	//this is the AJAX callback to update the feed link
	$payload = array('uid'=>$userid, 'cid'=>$cid);
	if ($_POST['textitemstype']!='no') {
		$payload['T'] = strtoupper($_POST['textitemstype']{0}).intval($_POST['textitems']);
	}
	if ($_POST['assesstype']!='no') {
		$payload['A'] = strtoupper($_POST['assesstype']{0}).intval($_POST['assess']);
	}
	if ($_POST['forumtype']!='no') {
		$payload['F'] = strtoupper($_POST['forumtype']{0}).intval($_POST['forum']);
	}
	if ($_POST['caltype']!='no') {
		$payload['C'] = strtoupper($_POST['caltype']{0}).intval($_POST['cal']);
	}
	$token = JWT::encode($payload, $key); //token is URL safe from JWT
	$url = $GLOBALS['basesiteurl'] . '/admin/calendarfeed.php?t='.$token;
	echo $url;
	exit;
} else {
	//generate simple link on initial load
	$token = JWT::encode(array('uid'=>$userid, 'cid'=>$cid), $key); //token is URL safe from JWT
	$url = $GLOBALS['basesiteurl'] . '/admin/calendarfeed.php?t='.$token;
}
unset($key);

$freqs = array(_('no notification'), _('minutes'), _('hours'), _('days'));
$fvals = array('no','mins','hrs','days');

$placeinhead = '<script type="text/javascript">
function updatecallink() {
	$("#updatenotice").text("'._('Updating...').'");
	$.ajax({
		type: "POST",
		url: "exportcalfeed.php?cid='.$cid.'",
		data: $("#calfeedform").serialize(),
		datatype: "text"
	}).done(function(data) {
		$("#calfeedurl").text(data).attr("href",data);
		$("#updatenotice").text("'._('Updated').'");
	}).fail(function() {
		$("#updatenotice").text("'._('Error - try again').'");
	});
}
</script>';
require("../header.php");
require("../includes/htmlutil.php");

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
echo '&gt; <a href="showcalendar.php?cid='.$cid.'">'._('Calendar').'</a> ';
echo '&gt; '._('Generate Calendar Feed').'</div>';

echo '<div id="headercalfeed" class="pagetitle"><h1>'._('Generate Calendar Feed').'</h1></div>';

echo '<p>';
echo _('The link below can be used to subscribe to a calendar feed for the due dates and events in this course. ');
//echo _('To add notifications (reminder alarms), change the settings below to update the link.');
echo '</p><p>';
echo _('Calendar feed link').': <a href="'.$url.'" id="calfeedurl">'.$url.'</a>';
echo '</p>';
/*
echo '<form id="calfeedform" method="post"><fieldset><legend>'._('Feed Options').'</legend>';
echo '<p>'._('Indicate how long before the due date you want to receive notifications (reminder alarms).').'</p>';

echo '<span class="form">'._('Text and links').'</span><span class="formright">';
echo '<input type="text" size="3" id="textitems" name="textitems" value="" /> ';
writeHtmlSelect('textitemstype', $fvals, $freqs, 'no');
echo '</span><br class="form" />';

echo '<span class="form">'._('Online assignments').'</span><span class="formright">';
echo '<input type="text" size="3" id="assess" name="assess" value="" /> ';
writeHtmlSelect('assesstype', $fvals, $freqs, 'no');
echo '</span><br class="form" />';

echo '<span class="form">'._('Forum posts and replies').'</span><span class="formright">';
echo '<input type="text" size="3" id="forum" name="forum" value="" /> ';
writeHtmlSelect('forumtype', $fvals, $freqs, 'no');
echo '</span><br class="form" />';

echo '<span class="form">'._('Other calendar items').'</span><span class="formright">';
echo '<input type="text" size="3" id="cal" name="cal" value="" /> ';
writeHtmlSelect('caltype', $fvals, $freqs, 'no');
echo '</span><br class="form" />';

echo '<div class="form"><button type="button" onclick="updatecallink()">'._('Update feed link').'</button> ';
echo '<span id="updatenotice" class="noticetext"></span></div>';
echo '</fieldset></form>';
*/
echo '<h2>'._('To load in the calendar feed').'</h2>';
echo '<p>'._('Start by copying the link above. Usually you can do that by right-clicking on it and Copy Link Address').'</p>';
echo '<h3>'._('Google Calendar').'</h3>';
echo '<p>';
echo _('In the left column, click the triangle after "Other calendars" and select "Add by URL". Paste in the feed link and Add Calendar.');
echo '</p><p>';
echo _('If you wish to set up notifications/alarms, after adding the calendar click the arrow that shows when you hover over the calendar. Click Edit Notifications, then under Event Notifications click Add a notification.');
echo '</p>';

echo '<h3>'._('iOS').'</h3>';
echo '<p>';
echo _('Go to Settings, then "Mail, Contacts, and Calendars". Tap "Add Account", then "Other". Tap "Add Subscribed Calendar" and paste in the feed link.');
echo '</p>';


require("../footer.php");
?>
