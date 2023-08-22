<?php

//change 
$DBH->beginTransaction();

 $query = "UPDATE `imas_lti_platforms` SET 
    auth_login_url='https://sso.canvaslms.com/api/lti/authorize_redirect',
    auth_token_url='https://sso.canvaslms.com/login/oauth2/token',
    key_set_url='https://sso.canvaslms.com/api/lti/security/jwks' 
    WHERE auth_login_url='https://canvas.instructure.com/api/lti/authorize_redirect'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "UPDATE `imas_lti_platforms` SET 
    auth_login_url='https://sso.beta.canvaslms.com/api/lti/authorize_redirect',
    auth_token_url='https://sso.beta.canvaslms.com/login/oauth2/token',
    key_set_url='https://sso.beta.canvaslms.com/api/lti/security/jwks' 
    WHERE auth_login_url='https://canvas.beta.instructure.com/api/lti/authorize_redirect'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "UPDATE `imas_lti_platforms` SET 
    auth_login_url='https://sso.test.canvaslms.com/api/lti/authorize_redirect',
    auth_token_url='https://sso.test.canvaslms.com/login/oauth2/token',
    key_set_url='https://sso.test.canvaslms.com/api/lti/security/jwks' 
    WHERE auth_login_url='https://canvas.test.instructure.com/api/lti/authorize_redirect'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Updated Canvas LTI Endpoints</p>";

return true;
