<?php
// code is adapted from IMS-DEV sample code
// on code.google.com/p/ims-dev
require_once  'OAuth.php';

/**
 * A Trivial store for testing - no support for tokens
 */
class IMathASLTIOAuthDataStore extends OAuthDataStore {

    function lookup_consumer($consumer_key) {
        global $DBH;
        if (isset($GLOBALS['LTImode']) && $GLOBALS['LTImode']=="consumer") {
        	//DB $query = "SELECT secret FROM imas_external_tools WHERE ltikey='".addslashes($consumer_key)."'";
        	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
        	//DB if (mysql_num_rows($result)==0) {
        	$stm = $DBH->prepare("SELECT secret FROM imas_external_tools WHERE ltikey=:ltikey");
        	$stm->execute(array(':ltikey'=>$consumer_key));
        	if ($stm->rowCount()==0) {
        		return NULL;
        	}
        	//DB $row = mysql_fetch_row($result);
        	//DB $secret = $row[0];
          $secret = $stm->fetchColumn(0);
        	$consumer = new OAuthConsumer($consumer_key,$secret);
		      return $consumer;
        }

      	$keyparts = explode('_',$consumer_key);

      	if ($keyparts[0]=='cid' || $keyparts[0]=='placein' || $keyparts[0]=='LTIkey') {
      		//DB $keyparts[1] = intval($keyparts[1]);
      		//DB $query = "SELECT ltisecret FROM imas_courses WHERE id='{$keyparts[1]}'";
          //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
      		$stm = $DBH->prepare("SELECT ltisecret FROM imas_courses WHERE id=:id");
      		$stm->execute(array(':id'=>$keyparts[1]));
      	} else if ($keyparts[0]=='aid') {
      		$keyparts[1] = intval($keyparts[1]);
      		//DB $query = "SELECT ic.ltisecret FROM imas_courses AS ic JOIN imas_assessments AS ia ON ";
      		//DB $query .= "ic.id=ia.courseid WHERE ia.id='{$keyparts[1]}'";
          //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
      		$query = "SELECT ic.ltisecret FROM imas_courses AS ic JOIN imas_assessments AS ia ON ";
      		$query .= "ic.id=ia.courseid WHERE ia.id=:id";
      		$stm = $DBH->prepare($query);
      		$stm->execute(array(':id'=>$keyparts[1]));
      	} else if ($keyparts[0]=='sso') {
      		//DB $query = "SELECT password,rights,groupid FROM imas_users WHERE SID='{$keyparts[1]}' AND (rights=11 OR rights=76 OR rights=77)";
          //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
      		$stm = $DBH->prepare("SELECT password,rights,groupid FROM imas_users WHERE SID=:SID AND (rights=11 OR rights=76 OR rights=77)");
      		$stm->execute(array(':SID'=>$keyparts[1]));
      	} else {
      		//DB $query = "SELECT password,rights,groupid FROM imas_users WHERE SID='{$keyparts[0]}' AND (rights=11 OR rights=76 OR rights=77)";
          //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
      		$stm = $DBH->prepare("SELECT password,rights,groupid FROM imas_users WHERE SID=:SID AND (rights=11 OR rights=76 OR rights=77)");
      		$stm->execute(array(':SID'=>$keyparts[0]));
      	}

      	//DB if (mysql_num_rows($result)>0) {
      	if ($stm->rowCount()>0) {
      		//DB $secret = mysql_result($result,0,0);
          $row = $stm->fetch(PDO::FETCH_NUM);
          $secret = $row[0];
      		if ($keyparts[0]=='cid' || $keyparts[0]=='aid' || $keyparts[0]=='placein' || $keyparts[0]=='LTIkey') {
      			$rights = 11;
      			$groupid = 0;
      		} else {
      			//DB $rights = mysql_result($result,0,1);
      			//DB $groupid = mysql_result($result,0,2);
            $rights = $row[1];
            $groupid = $row[2];
      		}
      		if ($secret=='') {
      			//if secret isn't set, don't use blank as secret
      			return NULL;
      		}
      		 $consumer = new OAuthConsumer($consumer_key,$secret, NULL,$rights,$groupid);
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
        global $DBH;
        //DB $query = "SELECT id FROM imas_ltinonces WHERE nonce='$nonce'";
      	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
      	//DB if (mysql_num_rows($result)>0) {
      		//DB return mysql_result($result,0,0);
        $stm = $DBH->prepare("SELECT id FROM imas_ltinonces WHERE nonce=:nonce");
        $stm->execute(array(':nonce'=>$nonce));
      	if ($stm->rowCount()>0) {
      		return $stm->fetchColumn(0);
      	} else {
      		return NULL;
      	}
    }

    //records nonce and deletes out old ones
    function record_nonce($nonce) {
      global $DBH;
	    $now = time();
	    //DB $query = "INSERT INTO imas_ltinonces (nonce,time) VALUES ('$nonce','$now')";
	    //DB mysql_query($query) or die("Query failed : " . mysql_error());
	    $stm = $DBH->prepare("INSERT INTO imas_ltinonces (nonce,time) VALUES (:nonce, :time)");
	    $stm->execute(array(':nonce'=>$nonce, ':time'=>$now));

	    $old = $now - 5400; //old stuff - 90 minutes
	    //DB $query = "DELETE FROM imas_ltinonces WHERE time<$old";
	    //DB mysql_query($query) or die("Query failed : " . mysql_error());
	    $stm = $DBH->query("DELETE FROM imas_ltinonces WHERE time<$old"); //known INT - safe
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
