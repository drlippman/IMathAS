<?php
$init_skip_csrfp = true;
require_once "init_without_validate.php";
if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
 	 $urlmode = 'https://';
 } else {
 	 $urlmode = 'http://';
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
      <lticm:property name="domain"><?php echo Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']); ?></lticm:property>
      <lticm:property name="tool_id">resource_selection</lticm:property>
      <lticm:property name="privacy_level">public</lticm:property>
      <lticm:options name="course_navigation">
        <lticm:property name="url"><?php echo $GLOBALS['basesiteurl'] . '/bltilaunch.php';?></lticm:property>
        <lticm:property name="text"><?php echo htmlentities($installname) ?></lticm:property>
        <lticm:property name="enabled">true</lticm:property>
      </lticm:options>
    </blti:extensions>
  </cartridge_basiclti_link>
