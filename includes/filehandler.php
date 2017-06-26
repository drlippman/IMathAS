<?php

@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");

$GLOBALS['filehandertypecfiles'] = 'local';
if (isset($GLOBALS['AWSkey'])) {
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require("$curdir/S3.php");
	$GLOBALS['filehandertype'] = 's3';
	if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
		$GLOBALS['filehandertypecfiles'] = 's3';
	}
} else {
	$GLOBALS['filehandertype'] = 'local';
}

function storecontenttofile($content,$key,$sec="private") {
	if ($GLOBALS['filehandertype'] == 's3') {
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		if ($s3->putObject($content,$GLOBALS['AWSbucket'],$key,$sec)) {
			return true;
		} else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore/';
		$key = Sanitize::sanitizeFilePathAndCheckBlacklist($key);
		$dir = $base.dirname($key);
		$fn = basename($key);
		if (!is_dir($dir)) {
			mkdir_recursive($dir);
		}
		$fh = @fopen($dir.'/'.$fn,'wb');
		if ($fh) {
			fwrite($fh,$content);
			fclose($fh);
			return true;
		} else {
			return false;
		}
	}
}

function relocatecoursefileifneeded($file, $key, $sec="public") {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		if ($s3->putObjectFile($file,$GLOBALS['AWSbucket'],'cfiles/'.$key,$sec)) {
			return true;
		} else {
			return false;
		}
	} else {
		return true;
	}
}

function relocatefileifneeded($file, $key, $sec="public") {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		if ($s3->putObjectFile($file,$GLOBALS['AWSbucket'],$key,$sec)) {
			return getuserfileurl($key);
		} else {
			return false;
		}
	} else {
		return true;
	}
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
			// $_FILES[]['tmp_name'] is not user provided. This is safe.
			if ($s3->putObjectFile($_FILES[$id]['tmp_name'],$GLOBALS['AWSbucket'],$key,$sec)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore/';
			$key = Sanitize::sanitizeFilePathAndCheckBlacklist($key);
			$dir = $base.dirname($key);
			$fn = basename($key);
			if (!is_dir($dir)) {
				mkdir_recursive($dir);
			}
			if (move_uploaded_file($_FILES[$id]['tmp_name'],$dir.'/'.$fn)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}

function storeuploadedcoursefile($id,$key,$sec="public-read") {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
			$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			$t=0;
			$fn = $key;
			while ($s3->getObjectInfo($GLOBALS['AWSbucket'], 'cfiles/'.$key, false) !==false) {
				$key = substr($fn,0,strpos($fn,"."))."_$t".strstr($fn,".");
				$t++;
			}
			// $_FILES[]['tmp_name'] is not user provided. This is safe.
			if ($s3->putObjectFile($_FILES[$id]['tmp_name'],$GLOBALS['AWSbucket'],'cfiles/'.$key,$sec)) {
				return $key;
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files/';
			$key = Sanitize::sanitizeFilePathAndCheckBlacklist($key);
			$dir = $base.dirname($key);
			$fn = basename($key);
			$keydir = dirname($key);
			if (!is_dir($dir)) {
				mkdir_recursive($dir);
			}
			$t=0; $tfn = $fn;
			while(file_exists($dir.'/'.$fn)){
				$fn = substr($tfn,0,strpos($tfn,"."))."_$t".strstr($tfn,".");
				$t++;
			}
			if (move_uploaded_file($_FILES[$id]['tmp_name'],$dir.'/'.$fn)) {
				return $keydir.'/'.$fn;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}
function storeuploadedqimage($id,$key,$sec="public-read") {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
			$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			$t=0;
			$fn = $key;
			while ($s3->getObjectInfo($GLOBALS['AWSbucket'], 'qimages/'.$key, false) !==false) {
				$key = substr($fn,0,strpos($fn,"."))."_$t".strstr($fn,".");
				$t++;
			}
			// $_FILES[]['tmp_name'] is not user provided. This is safe.
			if ($s3->putObjectFile($_FILES[$id]['tmp_name'],$GLOBALS['AWSbucket'],'qimages/'.$key,$sec)) {
				return $key;
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/assessment/qimages/';
			$key = Sanitize::sanitizeFilePathAndCheckBlacklist($key);
			$dir = $base.dirname($key);
			$fn = basename($key);
			if (!is_dir($dir)) {
				mkdir_recursive($dir);
			}
			$t=0; $tfn = $fn;
			while(file_exists($dir.'/'.$fn)){
				$fn = substr($tfn,0,strpos($tfn,"."))."_$t".strstr($tfn,".");
				$t++;
			}
			if (move_uploaded_file($_FILES[$id]['tmp_name'],$dir.'/'.$fn)) {
				//fix if every to directory-based qimages
				return $fn;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}
function getasidfileurl($file) {
	global $imasroot;
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3object = "adata/$file";
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		return $s3->queryStringGet($GLOBALS['AWSbucket'],$s3object,7200);
	} else {
		return $imasroot.'/filestore/adata/'.$file;
	}
}

function getasidfilepath($file) {
	global $imasroot;
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3object = "adata/$file";
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		return $s3->queryStringGet($GLOBALS['AWSbucket'],$s3object,7200);
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\');
		return $base.'/filestore/adata/'.$file;
	}
}

/*
function deleteasidfilesfromstring($str) {
	if ($GLOBALS['filehandertype'] =='s3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$deled = array();
		$n = preg_match_all('/@FILE:(.+?)@/',$str,$matches);
		if ($n==0 || $n===false) {return 0;}
		foreach($matches[1] as $file) {
			if (in_array($file,$deled)) { continue;}
			$s3object = "adata/$file";
			if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
				$deled[] = $file;
			}
		}
		return count($deled);
	}
}
*/

//need to exclude asid or agroupid we're deleting from
function deleteasidfilesfromstring2($str,$tosearchby,$val,$aid=null) {
	global $DBH;
	if ($tosearchby!='id' && $tosearchby!='agroupid') {return 0;}
	if (is_array($val)) {
		//DB $keylist = "'".implode("','",$val)."'";
		$keylist = implode(',', array_map('intval', $val));
		$searchnot = "$tosearchby NOT IN ($keylist)";
	} else {
		//DB $val = addslashes($val);
		$val = intval($val);
		$searchnot = "$tosearchby<>$val";
	}
	if ($aid != null) {
		if (is_array($aid)) {
			//DB $keylist = "'".implode("','",$aid)."'";
			$keylist = implode(',', array_map('intval', $aid));
			$searchnot .= " AND assessmentid IN ($keylist)";
		} else {
			$aid = intval($aid);
			$searchnot .= " AND assessmentid=$aid";
		}
	}
	$n = preg_match_all('/@FILE:(.+?)@/',$str,$matches);
	if ($n==0 || $n===false) {return 0;}
	$todel = $matches[1];

	$valarr = array();
	$lookforph = array();
	foreach ($matches[1] as $file) {
		//DB $file = addslashes($file);
		//DB $lookfor[] = "lastanswers LIKE '%$file%' OR bestlastanswers LIKE '%$file%' OR reviewlastanswers LIKE '%$file%'";
		$lookforph[] = "lastanswers LIKE ? OR bestlastanswers LIKE ? OR reviewlastanswers LIKE ?";
		array_push($valarr, "%$file%", "%$file%", "%$file%");
	}
	$lookforstr = implode(' OR ',$lookforph);
	//DB $query = "SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchnot AND ($lookforstr)";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//$searchnot santized above
	$stm = $DBH->prepare("SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchnot AND ($lookforstr)");
	$stm->execute($valarr);
	$skip = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		preg_match_all('/@FILE:(.+?)@/',$row[0].$row[1].$row[2],$exmatch);
		//remove from todel list all files found in other sessions
		$todel = array_diff($todel,$exmatch[1]);
	}
	$deled = array();
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		foreach($todel as $file) {
			if (in_array($file,$deled)) { continue;}
			$s3object = "adata/$file";
			if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
				$deled[] = $file;
			}
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		foreach($todel as $file) {
			if (in_array($file,$deled)) { continue;}
			if (unlink($base.'/adata/'.$file)) {
				$deled[] = $file;
				recursiveRmdir(dirname($base.'/adata/'.$file));
			}
		}
	}

	return count($deled);
}


function deleteasidfilesbyquery2($tosearchby,$val,$aid=null,$lim=0) {
	global $DBH;
	if ($tosearchby!='id' && $tosearchby!='agroupid' && $tosearchby!='userid') {return 0;}
	$lim = intval($lim);

	if (is_array($val)) {
		//DB $keylist = "'".implode("','",$val)."'";
		$keylist = implode(',', array_map('intval', $val));
		$searchwhere = "$tosearchby IN ($keylist)";
		$searchnot = "$tosearchby NOT IN ($keylist)";
	} else {
		//DB $val = addslashes($val);
		$val = intval($val);
		$searchwhere = "$tosearchby=$val";
		$searchnot = "$tosearchby<>$val";
	}
	if ($aid != null) {
		if (is_array($aid)) {
			//DB $keylist = "'".implode("','",$aid)."'";
			$keylist = implode(',', array_map('intval', $aid));
			$searchwhere .= " AND assessmentid IN ($keylist)";
			$searchnot .= " AND assessmentid IN ($keylist)";
		} else {
			$aid = intval($aid);
			$searchwhere .= " AND assessmentid=$aid";
			$searchnot .= " AND assessmentid=$aid";
		}
	}
	if ($lim>0) {
		$searchwhere .= " LIMIT $lim";
	}
	//DB $query = "SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchwhere";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB if (mysql_num_rows($result)==0) {return 0;}
	//searchwhere is sanitized above
	$stm = $DBH->query("SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchwhere");
	if ($stm->rowCount()==0) {return 0;}
	$todel = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		preg_match_all('/@FILE:(.+?)@/',$row[0].$row[1].$row[2],$matches);
		foreach ($matches[1] as $file) {
			if (!in_array($file,$todel)) {
				$todel[] = $file;
			}
		}
	}
	if (count($todel)==0) {return 0;}
	//DB $lookfor = array();
	$valarr = array();
	$lookforph = array();
	foreach ($todel as $file) {
		//DB $file = addslashes($file);
		//DB $lookfor[] = "lastanswers LIKE '%$file%' OR bestlastanswers LIKE '%$file%' OR reviewlastanswers LIKE '%$file%'";
		$lookforph[] = "lastanswers LIKE ? OR bestlastanswers LIKE ? OR reviewlastanswers LIKE ?";
		array_push($valarr, "%$file%", "%$file%", "%$file%");
	}
	$lookforstr = implode(' OR ',$lookforph);
	//DB $query = "SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchnot AND ($lookforstr)";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$stm = $DBH->prepare("SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchnot AND ($lookforstr)");
	$stm->execute($valarr);
	$skip = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		preg_match_all('/@FILE:(.+?)@/',$row[0].$row[1].$row[2],$exmatch);
		//remove from todel list all files found in other sessions
		$todel = array_diff($todel,$exmatch[1]);
	}
	$deled = array();

	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		foreach($todel as $file) {
			if (in_array($file,$deled)) { continue;}
			$s3object = "adata/$file";
			if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
				$deled[] = $file;
			}
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		foreach($todel as $file) {
			if (in_array($file,$deled)) { continue;}
			if (unlink($base.'/adata/'.$file)) {
				$deled[] = $file;
				recursiveRmdir(dirname($base.'/adata/'.$file));
			}
		}
	}
	return count($deled);
}
/*
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
			$delcnt += deleteasidfilesfromstring($row[2].$row[3].$row[4]);
			$cnt++;
		}
		return $delcnt;
	}
}

*/
//delete all assessment files for an assessmentid
function deleteallaidfiles($aid) {
	$delcnt = 0;
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$arr = $s3->getBucket($GLOBALS['AWSbucket'],"adata/$aid/");
		if ($arr!=false) {
			foreach ($arr as $k=>$file) {
				if($s3->deleteObject($GLOBALS['AWSbucket'],$file['name'])) {
					$delcnt++;
				}
			}
		}

	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (($delcnt = unlinkRecursive($base."/adata/$aid",true))==0) {
			return false;
		}
	}
	return $delcnt;
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
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if ($handle = @opendir($base."/ufiles/$uid")) {
			$imgext = array(".gif",".jpg",".png",".jpeg");
			$arr = array();
			$k = 0;
			while (false !== ($file=readdir($handle))) {
				if ($file != "." && $file != ".." && !is_dir($file)) {
					if ($img) {
						if (in_array(strtolower(strrchr($file,".")),$imgext)) {
							$arr[$k]['name'] = "ufiles/$uid/$file";
							$k++;
						}
					} else {
						$arr[$k]['name'] = "ufiles/$uid/$file";
						$k++;
					}
				}
			}
			closedir($handle);
			return $arr;
		} else {
			return array();
		}
	}
}
function deleteuserfile($uid,$file) {
	$safeFilename = Sanitize::sanitizeFilenameAndCheckBlacklist($file);
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "ufiles/$uid/$safeFilename";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (unlink($base."/ufiles/$uid/$safeFilename")) {
			return true;
		} else {
			return false;
		}
	}
}

function deleteforumfile($postid,$file) {
	$safeFilename = Sanitize::sanitizeFilenameAndCheckBlacklist($file);
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "ffiles/$postid/$safeFilename";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (unlink($base."/ffiles/$postid/$safeFilename")) {
			return true;
		} else {
			return false;
		}
	}
}

function deletecoursefile($file) {
	$safeFilename = Sanitize::sanitizeFilePathAndCheckBlacklist($file);
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "cfiles/$safeFilename";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files';
		if (unlink($base."/$safeFilename")) {
			return true;
		} else {
			return false;
		}
	}
}
function deleteqimage($file) {
	$safeFilename = Sanitize::sanitizeFilenameAndCheckBlacklist($file);
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "qimages/$safeFilename";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/assessment/qimages';
		if (unlink($base."/$safeFilename")) {
			return true;
		} else {
			return false;
		}
	}
}

function deletefilebykey($key) {
	$safeFilename = Sanitize::sanitizeFilePathAndCheckBlacklist($file);
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = $safeFilename;
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (unlink($base."/$safeFilename")) {
			return true;
		} else {
			return false;
		}
	}
}

function deleteallpostfiles($postid) {
	$delcnt = 0;
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$arr = $s3->getBucket($GLOBALS['AWSbucket'],"ffiles/$postid/");
		if ($arr!=false) {
			foreach ($arr as $s3object) {
				if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object['name'])) {
					$delcnt++;
				}
			}
		} else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (($delcnt = unlinkRecursive($base."/ffiles/$postid",true))==0) {
			return false;
		}
	}
	return $delcnt;
}
function deletealluserfiles($uid) {
	$delcnt = 0;
	if ($GLOBALS['filehandertype'] == 's3') {

		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$arr = $s3->getBucket($GLOBALS['AWSbucket'],"ufiles/$uid/");
		if ($arr!=false) {
			foreach ($arr as $s3object) {
				if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object['name'])) {
					$delcnt++;
				}
			}
		} else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (($delcnt = unlinkRecursive($base."/ufiles/$uid",true))==0) {
			return false;
		}
	}
	return $delcnt;
}

function doesfileexist($type,$key) {
	if ($type=='cfile') {
		if ($GLOBALS['filehandertypecfiles'] == 's3') {
			$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			return $s3->getObjectInfo($GLOBALS['AWSbucket'], 'cfiles/'.$key, false);
		} else {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files/';
			return file_exists($base.$key);
		}
	} else {
		if ($GLOBALS['filehandertype'] == 's3') {
			$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			return $s3->getObjectInfo($GLOBALS['AWSbucket'], $key, false);
		} else {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore/';
			return file_exists($base.$key);
		}
	}
}

function copycoursefile($key,$dest) {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3->getObject($GLOBALS['AWSbucket'], 'cfiles/'.$key, $dest);
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files/';
		copy($base.$key, $dest);
	}
}
function copyqimage($key,$dest) {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3->getObject($GLOBALS['AWSbucket'], 'qimages/'.$key, $dest);
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/assessment/qimages/';
		copy($base.$key, $dest);
	}
}

function getuserfileurl($key) {
	global $urlmode,$imasroot;
	if ($GLOBALS['filehandertype'] == 's3') {
		//return $urlmode."s3.amazonaws.com/{$GLOBALS['AWSbucket']}/$key";
		return 'https://'.$GLOBALS['AWSbucket'].".s3.amazonaws.com/$key";
	} else {
		return "$imasroot/filestore/$key";
	}
}
function getfopenloc($key) {
	global $urlmode,$imasroot;
	if ($GLOBALS['filehandertype'] == 's3') {
		return $urlmode."s3.amazonaws.com/{$GLOBALS['AWSbucket']}/$key";
	} else {
		return "../filestore/$key";
	}
}
function getcoursefileurl($key,$abs=false) {
	global $urlmode,$imasroot;
	$st = substr($key,0,6);
	if ($st == 'http:/' || $st=='https:') {
		return $key;
	} else if ($GLOBALS['filehandertypecfiles'] == 's3') {
		//return $urlmode."s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/$key";
		return 'https://'.$GLOBALS['AWSbucket'].".s3.amazonaws.com/cfiles/$key";
	} else {
		$key = Sanitize::rawurlencodePath($key);  //shouldn't be needed since filenames sanitized, but better to be safe
		if ($abs==true) {
			return $urlmode.$_SERVER['HTTP_HOST']."$imasroot/course/files/$key";
		} else {
			return "$imasroot/course/files/$key";
		}
	}
}
function getqimageurl($key,$abs=false) {
	global $urlmode,$imasroot;
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		return $urlmode."s3.amazonaws.com/{$GLOBALS['AWSbucket']}/qimages/$key";
	} else {
		if ($abs==true) {
			return $urlmode.$_SERVER['HTTP_HOST']."$imasroot/assessment/qimages/$key";
		} else {
			return "$imasroot/assessment/qimages/$key";
		}
	}
}
function mkdir_recursive($pathname, $mode=0777)
{
    is_dir(dirname($pathname)) || mkdir_recursive(dirname($pathname), $mode);
    return is_dir($pathname) || @mkdir($pathname, $mode);
}
function recursiveRmdir($dir) {
	if (basename($dir)=='adata' || basename($dir)=='ufiles' || basename($dir)=='files') { return;}
	if (@rmdir($dir)) {
		recursiveRmdir(dirname($dir));
	}
}
function unlinkRecursive($dir, $deleteRootToo) {
    $cnt = 0;
    if(!$dh = @opendir($dir))  {
        return;
    }
    while (false !== ($obj = readdir($dh)))  {
        if($obj == '.' || $obj == '..')  {
            continue;
        }
	if (is_file($dir . '/' . $obj)) {
		if (unlink($dir . '/' . $obj)) {
			$cnt++;
		}
	} else if (is_dir($dir . '/' . $obj)) {
		$cnt += unlinkRecursive($dir.'/'.$obj, true);
	}
    }
    closedir($dh);

    if ($deleteRootToo) {
        @rmdir($dir);
    }
    return $cnt;
}


?>
