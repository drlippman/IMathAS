<?php

require_once '../../includes/OAuth.php';

  // Replace this with some real function that pulls from the LMS.

  function validateDescriptor($descriptor)
  {
    $xml = new SimpleXMLElement($xmldata);
    if ( ! $xml ) {
       echo("Error parsing Descriptor XML\n");
       return;
    }
    $launch_url = $xml->secure_launch_url[0];
    if ( ! $launch_url ) $launch_url = $xml->launch_url[0];
    if ( $launch_url ) $launch_url = (string) $launch_url;
    return $launch_url;
  }

  function signParameters($oldparms, $endpoint, $method, $key, $secret, $org_secret, $org_id, $org_desc)
  {
    global $last_base_string;
    $parms = $oldparms;
    $parms["lti_version"] = "LTI-1p0";
    $parms["lti_message_type"] = "basic-lti-launch-request";
    if ( $org_id ) $parms["tool_consumer_instance_guid"] = $org_id;
    if ( $org_desc ) {
	    $parms["tool_consumer_instance_description"] = $org_desc;
	    $parms["tool_consumer_instance_name"] = $org_desc;
    }
    $parms["basiclti_submit"] = "Launch Tool";
    $parms["oauth_callback"] = "about:blank";

    if ( $org_secret ) {
      $oauth_consumer_secret = $org_secret;
      $oauth_consumer_key = $org_id;
    } else {
      $oauth_consumer_secret = $secret;
      $oauth_consumer_key = $key;
    }

    $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
    $test_consumer = new OAuthConsumer($oauth_consumer_key, $oauth_consumer_secret, NULL);

    $acc_req = OAuthRequest::from_consumer_and_token($test_consumer, $test_token, $method, $endpoint, $parms);
    $acc_req->sign_request($hmac_method, $test_consumer, $test_token);

    // Pass this back up "out of band" for debugging
    $last_base_string = $acc_req->get_signature_base_string();

    $newparms = $acc_req->get_parameters();

    return $newparms;
  }

  function postLaunchHTML($newparms, $endpoint, $debug) {
    global $last_base_string;
    $r = "<div id=\"ltiLaunchFormSubmitArea\">\n";
    $r .= "<form action=\"".Sanitize::encodeUrlForHref($endpoint)."\" name=\"ltiLaunchForm\" id=\"ltiLaunchForm\" method=\"post\" encType=\"application/x-www-form-urlencoded\">\n" ;
    foreach($newparms as $key => $value ) {
      $key = Sanitize::encodeStringForDisplay($key);
      $value = Sanitize::encodeStringForDisplay($value);
      if ( $key == "basiclti_submit" ) {
        $r .= "<input type=\"submit\" name=\"";
      } else {
        $r .= "<input type=\"hidden\" name=\"";
      }
      $r .= $key;
      $r .= "\" value=\"";
      $r .= $value;
      $r .= "\"/>\n";
    }
    $r .= "</form>\n";
    if ( $debug ) {
      $r .= "<pre>\n";
      $r .=  "<b>BasicLTI Endpoint</b>\n" . Sanitize::encodeStringForDisplay($endpoint) . "\n\n";
      $r .=  "<b>BasicLTI Parameters:</b>\n";
      foreach($newparms as $key => $value ) {
        $key = Sanitize::encodeStringForDisplay($key);
        $value = Sanitize::encodeStringForDisplay($value);
        $r .= "$key = $value\n";
      }
      $r .= "</pre>\n";
      $r .= "<p><b>OAuth Base String (most recent)</b><br/>\n".$last_base_string."</p>\n";
    } else {
      $basiclti_submit = "basiclti_submit";
      $basiclti_submit_text = "Launch Tool";
      $r .= " <script language=\"javascript\"> \n" .
          "    document.getElementById(\"ltiLaunchFormSubmitArea\").style.display = \"none\";\n" .
          "    nei = document.createElement('input');\n" .
          "    nei.setAttribute('type', 'hidden');\n" .
          "    nei.setAttribute('name', '".$basiclti_submit."');\n" .
          "    nei.setAttribute('value', '".$basiclti_submit_text."');\n" .
          "    document.getElementById(\"ltiLaunchForm\").appendChild(nei);\n" .
          "    document.ltiLaunchForm.submit(); \n" .
          " </script> \n";
    }
    $r .= "</div>\n";
    return $r;
  }

  // Parse a descriptor
  function launchInfo($xmldata) {
    $xml = new SimpleXMLElement($xmldata);
    if ( ! $xml ) {
       echo("Error parsing Descriptor XML\n");
       return;
    }
    $launch_url = $xml->secure_launch_url[0];
    if ( ! $launch_url ) $launch_url = $xml->launch_url[0];
    if ( $launch_url ) $launch_url = (string) $launch_url;
    $custom = array();
    if ( $xml->custom[0]->parameter )
    foreach ( $xml->custom[0]->parameter as $resource) {
      $key = (string) $resource['key'];
      $key = strtolower($key);
      $nk = "";
      for($i=0; $i < strlen($key); $i++) {
        $ch = substr($key,$i,1);
        if ( $ch >= "a" && $ch <= "z" ) $nk .= $ch;
        else if ( $ch >= "0" && $ch <= "9" ) $nk .= $ch;
        else $nk .= "_";
      }
      $value = (string) $resource;
      $custom["custom_".$nk] = $value;
    }
    return array("launch_url" => $launch_url, "custom" => $custom ) ;
  }

  function map_keyname($key) {
    $newkey = "";
    $key = strtolower(trim($key));
    foreach (str_split($key) as $ch) {
        if ( ($ch >= 'a' && $ch <= 'z') || ($ch >= '0' && $ch <= '9') ) {
            $newkey .= $ch;
        } else {
            $newkey .= '_';
        }
    }
    return $newkey;
}


?>
