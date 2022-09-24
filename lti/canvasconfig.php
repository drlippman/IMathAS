<?php
$init_skip_csrfp = true;
require("../init_without_validate.php");

$host = Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']);
if (substr($host,0,4)=='www.') { //strip www if not required - Canvas can match to higher domains.
	 $shorthost = substr($host,4);
} else {
	 $shorthost = $host;
}
header("Content-type: application/json;");
?>
{
   "title":"<?php echo Sanitize::encodeStringForDisplay($installname); ?>",
   "description":"<?php echo Sanitize::encodeStringForDisplay($installname); ?>",
   "privacy_level":"public",
   "oidc_initiation_url":"<?php echo $basesiteurl;?>/lti/login.php",
   "target_link_uri":"<?php echo $basesiteurl;?>/lti/launch.php",
   "scopes":[
       "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
       "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly",
       "https://purl.imsglobal.org/spec/lti-ags/scope/score",
       "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
    ],
   "extensions":[
      {
         "domain":"<?php echo $shorthost; ?>",
         "tool_id":"<?php echo Sanitize::encodeStringForDisplay($installname); ?>",
         "platform":"canvas.instructure.com",
         "privacy_level": "public",
         "settings":{
            "text":"<?php echo Sanitize::encodeStringForDisplay($installname); ?>",
            "placements":[
               {
                  "text":"<?php echo Sanitize::encodeStringForDisplay($installname); ?>",
                  "enabled":true,
                  "placement":"assignment_selection",
                  "message_type":"LtiDeepLinkingRequest",
                  "target_link_uri":"<?php echo $basesiteurl;?>/lti/launch.php",
                  "selection_height": 600,
                  "selection_width": 600
               }
            ],
            "privacy_level": "public"
         }
      }
   ],
   "public_jwk_url": "<?php echo $basesiteurl;?>/lti/jwks.php",
   "custom_fields":{
      "canvas_assignment_due_at":"$Canvas.assignment.dueAt.iso8601",
      "context_history":"$Context.id.history",
      "canvas_sections":"$com.instructure.User.sectionNames"
   }
}
<?php
