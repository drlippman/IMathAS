<?php
$curdir = rtrim(dirname(__FILE__), '/\\');
require("$curdir/S3.php");

function storeuploadedfile($id,$key,$sec="private") {
	if ($sec=="public" || $sec=="public-read") {
		$sec = "public-read";
	} else {
		$sec = "private";
	}
	if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		if ($s3->putObjectFile($_FILES[$id]['tmp_name'],$GLOBALS['AWSbucket'],$key,$sec)) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}
function getasidfileurl($asid,$file) {
	$s3object = "adata/$asid/$file";
	$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
	return $s3->queryStringGet($GLOBALS['AWSbucket'],$s3object,7200);
				
}
function deleteasidfilesfromstring($str,$s3asid) {
	$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
	$delcnt = 0;
	preg_match_all('/@FILE:(.+?)@/',$str,$matches);
	foreach($matches[1] as $file) {
		$s3object = "adata/$s3asid/$file";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			$delcnt++;
		}
	}
	return $delcnt;
}

//wherearr array of imas_assessment_sessions id=>val for WHERE
function deleteasidfilesbyquery($wherearr) {
	$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
	$delcnt = 0;
	if (count($wherearr)==0) {
		return false;
	}
	$conds = array();
	foreach($wherearr as $id=>$key) {
		$conds[] = "$id='$key'";	
	}
	$cond = implode(' AND ',$conds);
	$query = "SELECT id,lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $cond";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$delcnt += deleteasidfilesfromstring($row[1].$row[2].$row[3],$row[0]);
	}
	return $delcnt;
}

function getuserfiles($uid,$img=false) {
	$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
	$arr = $s3->getBucket($GLOBALS['AWSbucket'],"ufiles/$uid/");
	if ($arr!=false) {
		if ($img) {
			$imgext = array(".gif",".jpg",".png",".jpeg");
			foreach ($arr as $k=>$v) {
				if (!in_array(strtolower(strrchr($arr[$k]['name'],".")),$imgext)) {
					unset($arr[$k]);
				}
			}
		}
		return $arr;
	} else {
		return array();
	}
}
function deleteuserfile($uid,$file) {
	$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
	$s3object = "ufiles/$uid/$file";
	if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
		return true;
	}else {
		return false;
	}
}
function getuserfileurl($key) {
	return "http://s3.amazonaws.com/{$GLOBALS['AWSbucket']}/$key";	
}




?>
