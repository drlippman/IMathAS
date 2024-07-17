<?php
//Calendar export for import into Canvas
//(c) 2017 David Lippman for IMathAS

require_once "../init.php";
require_once "../includes/calendardata.php";

if (!isset($teacherid)) {
	echo "You are not authorized to view this page";
	exit;
}
list($coursename,$calevents) = getCalendarEventData($cid, $userid, isset($_GET['stuonly']));

$zip = new ZipArchive();
$file = tempnam(__DIR__.'/import', "zip");
$zip->open($file, ZipArchive::OVERWRITE);

$manifest = '<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="'.Sanitize::simpleString($installname.'-calevents-'.$cid).'" xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1" xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource" xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1 http://www.imsglobal.org/profile/cc/ccv1p1/ccv1p1_imscp_v1p2_v1p0.xsd http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource http://www.imsglobal.org/profile/cc/ccv1p1/LOM/ccv1p1_lomresource_v1p0.xsd http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest http://www.imsglobal.org/profile/cc/ccv1p1/LOM/ccv1p1_lommanifest_v1p0.xsd">
	<metadata>
    <schema>IMS Common Cartridge</schema>
    <schemaversion>1.1.0</schemaversion>
	</metadata>
	<resources>
		<resource identifier="'.Sanitize::simpleString($installname.'-caleventsres-'.$cid).'res" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" href="course_settings/canvas_export.txt">
			<file href="course_settings/events.xml"/>
		</resource>
	</resources>
</manifest>';
$zip->addFromString("imsmanifest.xml", $manifest);

$zip->addFromString("course_settings/canvas_export.txt",'Q: What did the panda say when he was forced out of his natural habitat? A: This is un-BEAR-able');

$events = '<?xml version="1.0" encoding="UTF-8"?>
<events xmlns="http://canvas.instructure.com/xsd/cccv1p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 http://canvas.instructure.com/xsd/cccv1p0.xsd">
';
foreach ($calevents as $k=>$event) {
	$events .= '<event identifier="'.Sanitize::simpleString($installname.'-calevent-'.$event[0]).'">'."\n";
	$events .= '<title>' . htmlentities($event[2],ENT_XML1,'UTF-8',false) . '</title>'."\n";
	if ($event[3]!='') {
		$event[3] = str_replace(_('Assignment Due: '), sprintf(_('Assignment Due in %s: '), $installname), $event[3]);
		$events .= '<description>' . htmlentities($event[3],ENT_XML1,'UTF-8',false) . '</description>'."\n";
	} else {
		$events .= '<description/>'."\n";
	}
	$events .= '<start_at>' . gmdate("Y-m-d\TH:i:s", $event[1]) . '</start_at>'."\n";
	$events .= '<end_at>' . gmdate("Y-m-d\TH:i:s", $event[1]) . '</end_at>'."\n";
	$events .= '</event>'."\n";
}
$events .= '</events>';
$zip->addFromString("course_settings/events.xml", $events);

$zip->close();

header("Content-Type: application/imscc");
header("Content-Length: " . filesize($file));
header("Content-Disposition: attachment; filename=\"calendarevents-$cid.imscc\"");
readfile($file);

unlink($file);
