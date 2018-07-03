<?php
//IMathAS:  LTI Outcome service
//(c) David Lippman 2014
//
//
//expects a lis_results_sourcedid of the form
//   sig::cid-linkid::userid
//  where sig = sha1(gbitemid::cid-linkid::userid)

$init_skip_csrfp = true;
require("../init_without_validate.php");
unset($init_skip_csrfp);

if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
	 $urlmode = 'https://';
} else {
 	 $urlmode = 'http://';
}

//$fp = fopen("ltilog.log","a");
//fwrite($fp, "request from ".$_SERVER['REMOTE_HOST']."\n");

if ($enablebasiclti!=true) {
	echo "BasicLTI not enabled";
	exit;
}

//check OAuth Signature!
require_once '../includes/OAuth.php';
require_once '../includes/ltioauthstore.php';

//set up OAuth
$LTImode = "consumer";
$store = new IMathASLTIOAuthDataStore();
$server = new OAuthServer($store);
$method = new OAuthSignatureMethod_HMAC_SHA1();
$server->add_signature_method($method);
$request = OAuthRequest::from_request();
$base = $request->get_signature_base_string();
try {
	$requestinfo = $server->verify_request($request);
} catch (Exception $e) {
	echo 'Invalid credentials';
	//fwrite($fp, "Invalid credentials\n");
	exit;
}
$store->mark_nonce_used($request);

//signature checks out. Proceed

$xml = file_get_contents('php://input');

//fwrite($fp, "sig OK.  XML: ".$xml."\n");

preg_match('/<imsx_messageIdentifier>\s*(.*?)\s*<\/imsx_messageIdentifier>/is', $xml, $matches);
$msgid = $matches[1];

if (strpos($xml,'replaceResultRequest')!==false) {
	preg_match('/<sourcedId>\s*(.*?)\s*<\/sourcedId>.*?<textString>\s*(.*?)<\/textString>/is', $xml, $matches);
	list($sig,$rlid,$userid) = explode('::', $matches[1]);
	if (!is_numeric($matches[2])) {
		//fwrite($fp, "not isfloat $matches[2]\n");
		failmessage('replaceResult');
	}
	$score = floatval($matches[2]);
	if ($score<0 || $score>1) {
		//fwrite($fp, "out of range $matches[2]\n");
		failmessage('replaceResult');
	}

} else {
	preg_match('/<sourcedId>\s*(.*?)\s*<\/sourcedId>/is', $xml, $matches);
	list($sig,$rlid,$userid) = explode('::', $matches[0]);
}

list($cid,$linkid) = explode('-',$rlid);
$cid = intval($cid);
$linkid = intval($linkid);
$userid = intval($userid);

//check is a student
//DB $query = "SELECT id FROM imas_students WHERE courseid=$cid AND userid=$userid";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$stm = $DBH->prepare("SELECT id FROM imas_students WHERE courseid=:courseid AND userid=:userid");
$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid));
if ($stm->rowCount()==0) {
	//fwrite($fp, "not stu\n");
	failmessage('replaceResult');
}

//DB $query = "SELECT text,points FROM imas_linkedtext WHERE id='$linkid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB $row = mysql_fetch_row($result);
$stm = $DBH->prepare("SELECT text,points FROM imas_linkedtext WHERE id=:id");
$stm->execute(array(':id'=>$linkid));
$row = $stm->fetch(PDO::FETCH_NUM);

$toolparts = explode('~~',substr($row[0],8));
if (isset($toolparts[6])) {
	$gradesecret = $toolparts[6];
} else {
	//fwrite($fp, "no gradesecret\n");
	failmessage('replaceResult');
}
$possible = $row[1];

$sig2 = sha1($gradesecret.'::'.$rlid.'::'.$userid);


if (strpos($xml,'replaceResultRequest')!==false) {
	if ($possible==0 || $sig2!=$sig) {
		//fwrite($fp, "possible $possible=0 or bad sig\n");
		failmessage('replaceResult');
	}
	$points = round($score*$possible,1);
	//fwrite($fp, "Writing score $score,$possible,$points for $gbitem user $userid\n");
	//DB $query = "SELECT id,score FROM imas_grades WHERE gradetypeid=$linkid AND gradetype='exttool' AND userid=$userid";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)>0) {
		//DB $row = mysql_fetch_row($result);
	$stm = $DBH->prepare("SELECT id,score FROM imas_grades WHERE gradetypeid=:gradetypeid AND gradetype='exttool' AND userid=:userid");
	$stm->execute(array(':gradetypeid'=>$linkid, ':userid'=>$userid));
	if ($stm->rowCount()>0) {
		$row = $stm->fetch(PDO::FETCH_NUM);
		//DB $query = "UPDATE imas_grades SET score=$points WHERE id=".$row[0];
		$stm = $DBH->prepare("UPDATE imas_grades SET score=:score WHERE id=:id");
		$stm->execute(array(':score'=>$points, ':id'=>$row[0]));
	} else {
		//DB $query = "INSERT INTO imas_grades (gradetypeid,userid,score,gradetype) VALUES ";
		//DB $query .= "($linkid,$userid,$points,'exttool')";
		$query = "INSERT INTO imas_grades (gradetypeid,userid,score,gradetype) VALUES ";
		$query .= "(:gradetypeid, :userid, :score, :gradetype)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':gradetypeid'=>$linkid, ':userid'=>$userid, ':score'=>$points, ':gradetype'=>'exttool'));
	}
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	successmessage('replaceResult',$msgid,$score);
} else if (strpos($xml,'readResultRequest')!==false) {
	if ($possible==0 || $sig2!=$sig) {
		failmessage('readResult');
	}
	//DB $query = "SELECT id,score FROM imas_grades WHERE gradetypeid=$linkid AND gradetype='exttool' AND userid=$userid";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)>0) {
		//DB $row = mysql_fetch_row($result);
	$stm = $DBH->prepare("SELECT id,score FROM imas_grades WHERE gradetypeid=:gradetypeid AND gradetype='exttool' AND userid=:userid");
	$stm->execute(array(':gradetypeid'=>$linkid, ':userid'=>$userid));
	if ($stm->rowCount()>0) {
		$row = $stm->fetch(PDO::FETCH_NUM);
		successmessage('readResult',$msgid,round($row[1]/$possible,3));
	} else {
		successmessage('readResult',$msgid,'');
	}
} else if (strpos($xml,'deleteResultRequest')!==false) {
	if ($possible==0 || $sig2!=$sig) {
		failmessage('deleteResult');
	}
	//DB $query = "DELETE FROM imas_grades WHERE gradetypeid=$linkid AND gradetype='exttool' AND userid=$userid";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetypeid=:gradetypeid AND gradetype='exttool' AND userid=:userid");
	$stm->execute(array(':gradetypeid'=>$linkid, ':userid'=>$userid));
	successmessage('deleteResult',$msgid,'');
}

function failmessage($type) {
	header("Content-type: application/xml");
	echo '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
  <imsx_POXHeader>
    <imsx_POXResponseHeaderInfo>
      <imsx_version>V1.0</imsx_version>
      <imsx_messageIdentifier>'.uniqid().'</imsx_messageIdentifier>
      <imsx_statusInfo>
        <imsx_codeMajor>failure</imsx_codeMajor>
        <imsx_severity>status</imsx_severity>
        <imsx_operationRefIdentifier>'.$type.'</imsx_operationRefIdentifier>
      </imsx_statusInfo>
    </imsx_POXResponseHeaderInfo>
  </imsx_POXHeader>
  <imsx_POXBody>
    <'.$type.'Response/>
  </imsx_POXBody>
</imsx_POXEnvelopeResponse>';
exit;
}

function successmessage($type,$msgid,$score) {
	header("Content-type: application/xml");
	$out = '<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
  <imsx_POXHeader>
    <imsx_POXResponseHeaderInfo>
      <imsx_version>V1.0</imsx_version>
      <imsx_messageIdentifier>'.uniqid().'</imsx_messageIdentifier>
      <imsx_statusInfo>
        <imsx_codeMajor>success</imsx_codeMajor>
        <imsx_severity>status</imsx_severity>
        <imsx_operationRefIdentifier>'.$type.'</imsx_operationRefIdentifier>
        <imsx_messageRefIdentifier>'.Sanitize::stripHtmlTags($msgid).'</imsx_messageRefIdentifier>
      </imsx_statusInfo>
    </imsx_POXResponseHeaderInfo>
  </imsx_POXHeader>
  <imsx_POXBody>';
  if ($type=='readResult') {
  	$out .= '<readResultResponse>
      <result>
        <resultScore>
          <language>en</language>
          <textString>'.$score.'</textString>
        </resultScore>
      </result>
    </readResultResponse>';
  }
  $out .=  '
    <'.$type.'Response/>
  </imsx_POXBody>
</imsx_POXEnvelopeResponse>';
  //global $fp;
  //fwrite($fp, $out);
  echo $out;
  exit;
}

?>
