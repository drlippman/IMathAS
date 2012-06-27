<?php
require("config.php");
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') {
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
      <lticm:property name="domain"><?php echo $_SERVER['HTTP_HOST']; ?></lticm:property>
      <lticm:property name="tool_id">resource_selection</lticm:property>
      <lticm:property name="privacy_level">public</lticm:property>
      <lticm:options name="resource_selection">
        <lticm:property name="url"><?php echo $urlmode.$_SERVER['HTTP_HOST'] . $imasroot . '/bltilaunch.php';?></lticm:property>
        <lticm:property name="text">Pick an Assessment</lticm:property>
        <lticm:property name="selection_width">500</lticm:property>
        <lticm:property name="selection_height">300</lticm:property>
      </lticm:options>
    </blti:extensions>
  </cartridge_basiclti_link>
