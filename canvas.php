<?php
$init_skip_csrfp = true;
require_once "init_without_validate.php";
if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https') || isset($CFG['GEN']['forcecanvashttps']))  {
	 $urlmode = 'https://';
 } else {
 	 $urlmode = 'http://';
 }

 $host = Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']);
 if (isset($CFG['GEN']['addwww']) && substr($host,0,4)!='www.') {
 	$host = 'www.'.$host;
 }
 if (substr($host,0,4)=='www.') { //strip www if not required - Canvas can match to higher domains.
 	 $shorthost = substr($host,4);
 } else {
 	 $shorthost = $host;
 }
header("Content-type: text/xml;");
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
  <cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0"
      xmlns:blti = "http://www.imsglobal.org/xsd/imsbasiclti_v1p0"
      xmlns:lticm ="http://www.imsglobal.org/xsd/imslticm_v1p0"
      xmlns:lticp ="http://www.imsglobal.org/xsd/imslticp_v1p0"
      xmlns:xsi = "http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation = "http://www.imsglobal.org/xsd/imslticc_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticc_v1p0.xsd
      http://www.imsglobal.org/xsd/imsbasiclti_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imsbasiclti_v1p0.xsd
      http://www.imsglobal.org/xsd/imslticm_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticm_v1p0.xsd
      http://www.imsglobal.org/xsd/imslticp_v1p0 http://www.imsglobal.org/xsd/lti/ltiv1p0/imslticp_v1p0.xsd">
    <blti:title><?php echo htmlentities($installname) ?></blti:title>
    <blti:description>Math Assessment</blti:description>
    <blti:extensions platform="canvas.instructure.com">
      <lticm:property name="domain"><?php echo $shorthost; ?></lticm:property>
      <lticm:property name="tool_id">resource_selection</lticm:property>
      <lticm:property name="privacy_level">public</lticm:property>
      <lticm:options name="assignment_selection">
        <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
        <lticm:property name="url"><?php echo $urlmode.$host . $imasroot . '/bltilaunch.php?ltiseltype=assn';?></lticm:property>
        <lticm:property name="selection_width">500</lticm:property>
        <lticm:property name="selection_height">300</lticm:property>
      </lticm:options>
      <lticm:options name="link_selection">
        <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
        <lticm:property name="url"><?php echo $urlmode.$host . $imasroot . '/bltilaunch.php?ltiseltype=link';?></lticm:property>
        <lticm:property name="selection_width">500</lticm:property>
        <lticm:property name="selection_height">300</lticm:property>
      </lticm:options>
      <lticm:property name="session_setup_url"><?php echo $urlmode.$host . $imasroot . '/ltisessionsetup.php';?></lticm:property>
    </blti:extensions>
    <blti:custom>
    	<lticm:property name="canvas_assignment_due_at">$Canvas.assignment.dueAt.iso8601</lticm:property>
    </blti:custom>
  </cartridge_basiclti_link>
