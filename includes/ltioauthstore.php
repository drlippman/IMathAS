<?php
// code is adapted from IMS-DEV sample code
// on code.google.com/p/ims-dev
require_once  'OAuth.php';

/**
 * A Trivial store for testing - no support for tokens
 */
class IMathASLTIOAuthDataStore extends OAuthDataStore {
   
    function lookup_consumer($consumer_key) {
        /*if ( strpos($consumer_key, "http://" ) === 0 ) {
            $consumer = new OAuthConsumer($consumer_key,"secret", NULL);
            return $consumer;
        }
        if ( $this->consumers[$consumer_key] ) {
            $consumer = new OAuthConsumer($consumer_key,$this->consumers[$consumer_key], NULL);
            return $consumer;
        }
        return NULL;
	*/
	
	$keyparts = explode('_',$consumer_key);
	
	if ($keyparts[0]=='cid') {
		$keyparts[1] = intval($keyparts[1]);
		$query = "SELECT ltisecret FROM imas_courses WHERE id='{$keyparts[1]}'";
	} else if ($keyparts[0]=='aid') {
		$keyparts[1] = intval($keyparts[1]);
		$query = "SELECT ltisecret FROM imas_assessments WHERE id='{$keyparts[1]}'";
	} else if ($keyparts[0]=='sso') {
		$query = "SELECT password FROM imas_users WHERE SID='{$keyparts[1]}' AND rights=11";
	} else {
		$query = "SELECT password FROM imas_users WHERE SID='{$keyparts[0]}' AND rights=11";
	}
	
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		$secret = mysql_result($result,0,0);
		if ($secret=='') {
			//if secret isn't set, don't use blank as secret
			return NULL;
		}
		 $consumer = new OAuthConsumer($consumer_key,$secret, NULL);
		 return $consumer;
        }
        return NULL;
    }

    function lookup_token($consumer, $token_type, $token) {
        return new OAuthToken($consumer, "");
    }

    // Return NULL if the nonce has not been used
    // Return $nonce if the nonce was previously used
    function lookup_nonce($consumer, $token, $nonce, $timestamp) {
        $query = "SELECT id FROM imas_ltinonces WHERE nonce='$nonce'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		return mysql_result($result,0,0);
	} else {
		return NULL;
	}
    }
    
    //records nonce and deletes out old ones
    function record_nonce($nonce) {
	    $now = time();
	    $query = "INSERT INTO imas_ltinonces (nonce,time) VALUES ('$nonce','$now')";
	    mysql_query($query) or die("Query failed : " . mysql_error());
	    
	    $old = $now - 900; //old stuff - 15 min
	    $query = "DELETE FROM imas_ltinonces WHERE time<$old";
	    mysql_query($query) or die("Query failed : " . mysql_error());
    }

    function mark_nonce_used($request) {
	    $nonce = @$request->get_parameter('oauth_nonce');    
	    $this->record_nonce($nonce);
    }
    
    function new_request_token($consumer) {
        return NULL;
    }

    function new_access_token($token, $consumer) {
        return NULL;
    }
}

?>
