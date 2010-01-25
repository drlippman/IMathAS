<?php
if (isset($GLOBALS['AWSkey'])) {
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require("$curdir/S3.php");
	$GLOBALS['filehandertype'] = 's3';
} else {
	//TODO:  build non-AWS file handers.  Eventually :)	
}

function storeuploadedfile($id,$key,$sec="private") {
	if ($GLOBALS['filehandertype'] == 's3') {
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
}
function getasidfileurl($asid,$file) {
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3object = "adata/$asid/$file";
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		return $s3->queryStringGet($GLOBALS['AWSbucket'],$s3object,7200);
	}
				
}

function moveasidfilesfromstring($str,$s3oldasid,$s3newasid) {
	if ($GLOBALS['filehandertype'] =='s3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$copycnt = 0;
		preg_match_all('/@FILE:(.+?)@/',$str,$matches);
		foreach($matches[1] as $file) {
			$s3oldobject = "adata/$s3oldasid/$file";
			$s3newobject = "adata/$s3newasid/$file";
			if($s3->copyObject($GLOBALS['AWSbucket'],$s3oldobject,$GLOBALS['AWSbucket'],$s3newobject)) {
				if($s3->deleteObject($GLOBALS['AWSbucket'],$s3oldobject)) {
					$copycnt++;
				}
			}
			
		}
		return $copycnt;
	}
}

function copyasidfilesfromstring($str,$s3oldasid,$s3newasid) {
	if ($GLOBALS['filehandertype'] =='s3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$copycnt = 0;
		preg_match_all('/@FILE:(.+?)@/',$str,$matches);
		foreach($matches[1] as $file) {
			$s3oldobject = "adata/$s3oldasid/$file";
			$s3newobject = "adata/$s3newasid/$file";
			if($s3->copyObject($GLOBALS['AWSbucket'],$s3oldobject,$GLOBALS['AWSbucket'],$s3newobject)) {
				$copycnt++;
			}
		}
		return $copycnt;
	}
}

function deleteasidfilesfromstring($str,$s3asid) {
	if ($GLOBALS['filehandertype'] =='s3') {
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
}

//wherearr array of imas_assessment_sessions id=>val for WHERE
function deleteasidfilesbyquery($wherearr,$lim=0) {
	if ($GLOBALS['filehandertype'] == 's3') {
		//$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$delcnt = 0;
		if (count($wherearr)==0) {
			return false;
		}
		$conds = array();
		foreach($wherearr as $id=>$key) {
			if (is_array($key)) {
				$keylist = "'".implode("','",$key)."'";
				$conds[] = "$id IN ($keylist)";
			} else {
				$conds[] = "$id='$key'";
			}
		}
		$cond = implode(' AND ',$conds);
		$query = "SELECT id,agroupid,lastanswers,bestlastanswers,reviewlastanswers,assessmentid FROM imas_assessment_sessions WHERE $cond";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$cnt = 0;
		while (($row = mysql_fetch_row($result)) && ($lim==0 || $cnt<$lim)) {
			if (strpos($row[2].$row[3].$row[4],'FILE:')===false) { continue;}
			if ($row[1]>0) {
				$delcnt += deleteasidfilesfromstring($row[2].$row[3],'grp'.$row[1].'/'.$row[5]);
				$delcnt += deleteasidfilesfromstring($row[4],$row[0]);
			} else {
				$delcnt += deleteasidfilesfromstring($row[2].$row[3].$row[4],$row[0]);
			}
			$cnt++;
		}
		return $delcnt;
	}
}

function getuserfiles($uid,$img=false) {
	if ($GLOBALS['filehandertype'] == 's3') {
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
	} else {
		return array();
	}
}
function deleteuserfile($uid,$file) {
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "ufiles/$uid/$file";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	}
}
function getuserfileurl($key) {
	if ($GLOBALS['filehandertype'] == 's3') {
		return "http://s3.amazonaws.com/{$GLOBALS['AWSbucket']}/$key";
	}
}




?>
