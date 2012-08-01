<?php

@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");

if (isset($GLOBALS['AWSkey'])) {
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require("$curdir/S3.php");
	$GLOBALS['filehandertype'] = 's3';
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
	} else {
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {	
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore/';
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
	if (is_array($val)) {
		$keylist = "'".implode("','",$val)."'";
		$searchnot = "$tosearchby NOT IN ($keylist)";
	} else {
		$val = addslashes($val);
		$searchnot = "$tosearchby<>$val";
	}
	if ($aid != null) {
		if (is_array($aid)) {
			$keylist = "'".implode("','",$aid)."'";
			$searchnot .= " AND assessmentid IN ($keylist)";
		} else {
			$aid = intval($aid);
			$searchnot .= " AND assessmentid=$aid";
		}
	}
	$n = preg_match_all('/@FILE:(.+?)@/',$str,$matches);
	if ($n==0 || $n===false) {return 0;}
	$todel = $matches[1];
	$lookfor = array();
	foreach ($matches[1] as $file) {
		$file = addslashes($file);
		$lookfor[] = "lastanswers LIKE '%$file%' OR bestlastanswers LIKE '%$file%' OR reviewlastanswers LIKE '%$file%'";
	}
	$lookforstr = implode(' OR ',$lookfor);
	$query = "SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchnot AND ($lookforstr)";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$skip = array();
	while ($row = mysql_fetch_row($result)) {
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
	if (is_array($val)) {
		$keylist = "'".implode("','",$val)."'";
		$searchwhere = "$tosearchby IN ($keylist)";
		$searchnot = "$tosearchby NOT IN ($keylist)";
	} else {
		$val = addslashes($val);
		$searchwhere = "$tosearchby=$val";
		$searchnot = "$tosearchby<>$val";
	}
	if ($aid != null) {
		if (is_array($aid)) {
			$keylist = "'".implode("','",$aid)."'";
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
	$query = "SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchwhere";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)==0) {return 0;}
	$todel = array();
	while ($row = mysql_fetch_row($result)) {
		preg_match_all('/@FILE:(.+?)@/',$row[0].$row[1].$row[2],$matches);
		foreach ($matches[1] as $file) {
			if (!in_array($file,$todel)) {
				$todel[] = $file;
			}
		}
	}
	if (count($todel)==0) {return 0;}
	$lookfor = array();
	foreach ($todel as $file) {
		$file = addslashes($file);
		$lookfor[] = "lastanswers LIKE '%$file%' OR bestlastanswers LIKE '%$file%' OR reviewlastanswers LIKE '%$file%'";
	}
	$lookforstr = implode(' OR ',$lookfor);
	$query = "SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchnot AND ($lookforstr)";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$skip = array();
	while ($row = mysql_fetch_row($result)) {
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
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "ufiles/$uid/$file";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (unlink($base."/ufiles/$uid/$file")) {
			return true;
		} else {
			return false;
		}
	}
}

function deleteforumfile($postid,$file) {
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "ffiles/$postid/$file";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (unlink($base."/ffiles/$postid/$file")) {
			return true;
		} else {
			return false;
		}
	}
}

function deletefilebykey($key) {
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "$key";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (unlink($base."/$key")) {
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

function getuserfileurl($key) {
	global $urlmode,$imasroot;
	if ($GLOBALS['filehandertype'] == 's3') {
		return $urlmode."s3.amazonaws.com/{$GLOBALS['AWSbucket']}/$key";
	} else {
		return "$imasroot/filestore/$key";
	}
}
function mkdir_recursive($pathname, $mode=0777)
{
    is_dir(dirname($pathname)) || mkdir_recursive(dirname($pathname), $mode);
    return is_dir($pathname) || @mkdir($pathname, $mode);
}
function recursiveRmdir($dir) {
	if (basename($dir)=='adata' || basename($dir)=='ufiles') { return;}
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
