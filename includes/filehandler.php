<?php

function getfilehandlertype($filetype) {
	if ($filetype=='filehandlertype' || $filetype=='filehandlertypecfiles') {
		if (isset($GLOBALS[$filetype])) {
			return $GLOBALS[$filetype];
		} else {
			$GLOBALS['filehandlertype'] = 'local';
			$GLOBALS['filehandlertypecfiles'] = 'local';
			if (isset($GLOBALS['AWSkey'])) {
				$curdir = rtrim(dirname(__FILE__), '/\\');
				require_once("$curdir/S3.php");
				$GLOBALS['filehandlertype'] = 's3';
				if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
					$GLOBALS['filehandlertypecfiles'] = 's3';
				}
			}
			return $GLOBALS[$filetype];
		}
	} else {
		return false;
	}
}

function storecontenttofile($content,$key,$sec="private") {
	if (getfilehandlertype('filehandlertype') == 's3') {
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
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
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
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
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

function relocategraphfileifneeded($file, $key) {
	global $imasroot;
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		if ($s3->putObjectFile($file,$GLOBALS['AWSbucket'],'gimgs/'.$key,"public-read")) {
			return 'https://'.$GLOBALS['AWSbucket'].".s3.amazonaws.com/gimgs/$key";;
		} else {
			return false;
		}
	} else {
		return $imasroot.'/filter/graph/imgs/'.$key;
	}
}
function getgraphfileurl($key) {
	global $imasroot;
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		return 'https://'.$GLOBALS['AWSbucket'].".s3.amazonaws.com/gimgs/$key";;
	} else {
		return $imasroot.'/filter/graph/imgs/'.$key;
	}
}

//copies file at URL
function rehostfile($url, $keydir, $sec="public", $prependToFilename="") {
	if (substr($url,0,4)!=='http') {return false;}

	$tmpdir = __dir__.'/../admin/import/tmp';
	if (!is_dir($tmpdir)) {
		mkdir($tmpdir);
	}
	//TODO: $url = Sanitize::url($url);
	$parseurl = parse_url($url);
	$fn =  Sanitize::sanitizeFilenameAndCheckBlacklist($prependToFilename.basename($parseurl['path']));
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		copy($url, $tmpdir.'/'.$fn);
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		if ($s3->putObjectFile($tmpdir.'/'.$fn,$GLOBALS['AWSbucket'],$keydir.'/'.$fn,$sec)) {
			@unlink($tmpdir.'/'.$fn);
			return $fn;
		} else {
			@unlink($tmpdir.'/'.$fn);
			return false;
		}
	} else {
		if (substr($keydir,0,7)=='cfiles/') {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files/';
			$dir = $base.Sanitize::onlyInt(substr($keydir,7));
		} else if ($keydir=='qimages') {
			$dir = rtrim(dirname(dirname(__FILE__)), '/\\').'/assessment/qimages';
		} else {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore/';
			$dir = $base.$keydir;
		}

		if (!is_dir($dir)) {
			mkdir_recursive($dir);
		}
		copy($url, $dir.'/'.$fn);
		return $fn;
	}
}

function storeuploadedfile($id,$key,$sec="private") {
	if (getfilehandlertype('filehandlertype') == 's3') {
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
			downsizeimage($_FILES[$id]);
			$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			// $_FILES[]['tmp_name'] is not user provided. This is safe.
			if ($s3->putObjectFile(realpath($_FILES[$id]['tmp_name']),$GLOBALS['AWSbucket'],$key,$sec)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
			downsizeimage($_FILES[$id]);
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

function storeimportfile($id) {
	$key = uniqid();
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		$sec = "private";
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
			$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			// $_FILES[]['tmp_name'] is not user provided. This is safe.
			if ($s3->putObjectFile(realpath($_FILES[$id]['tmp_name']),$GLOBALS['AWSbucket'],'import/'.$key,$sec)) {
				return $key;
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/admin/import/';
			$key = Sanitize::sanitizeFilePathAndCheckBlacklist($key);
			if (move_uploaded_file($_FILES[$id]['tmp_name'], $base.$key)) {
				return $key;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}

function getimportfilepath($key) {
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		return $s3->queryStringGet($GLOBALS['AWSbucket'],'import/'.$key,7200);
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/admin/import/';
		return $base . Sanitize::sanitizeFilePathAndCheckBlacklist($key);
	}
}
function deleteimport($key) {
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3->deleteObject($GLOBALS['AWSbucket'],'import/'.$key);
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/admin/import/';
		@unlink(realpath($base.$key));
	}
}

function downsizeimage($fileinfo) {
	if (preg_match('/\.(jpg|jpeg)/', $fileinfo['name'])) {
		$imgdata = getimagesize($fileinfo['tmp_name']);
		if ($imgdata === false || $imgdata[2] !== IMAGETYPE_JPEG) {
			return;
		}
        $exif = false;
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($fileinfo['tmp_name']);
		}
		$changed = false;
		if ((min($imgdata[0],$imgdata[1])>1000 || !empty($exif['Orientation'])) &&
		   ($imgdata[0]*$imgdata[1]*3*2/1048576 < 80)) {  //make sure mem use will be under 80MB
			if (min($imgdata[0],$imgdata[1])>1000) {
				$r = $imgdata[0]/$imgdata[1]; // width/height
				$image = imagecreatefromjpeg($fileinfo['tmp_name']);
				if ($imgdata[0]>$imgdata[1]) { //width bigger than height
					$newh = 1000;
					$neww = round($r*$newh);
				} else {
					$neww = 1000;
					$newh = round($neww/$r);
				}
				$dst = imagecreatetruecolor($neww, $newh);
				imagecopyresampled($dst, $image, 0, 0, 0, 0, $neww, $newh, $imgdata[0], $imgdata[1]);
				imagedestroy($image);
                $changed = true;
			} else {
				$dst = imagecreatefromjpeg($fileinfo['tmp_name']);
			}
			if (isset($exif['Orientation']) && $exif['Orientation']>1) {
				switch($exif['Orientation']) {
					case 3:
					    $dst = imagerotate($dst, 180, 0);
					    $changed = true;
					    break;
					case 6:
					    $dst = imagerotate($dst, -90, 0);
					    $changed = true;
					    break;
					case 8:
					    $dst = imagerotate($dst, 90, 0);
					    $changed = true;
					    break;
				}
			}
            if ($changed) {
			    imagejpeg($dst, $fileinfo['tmp_name'], 90);
			    imagedestroy($dst);
            }
		}
	}
}

function storeuploadedcoursefile($id,$key,$sec="public-read") {
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
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
			if ($s3->putObjectFile(realpath($_FILES[$id]['tmp_name']),$GLOBALS['AWSbucket'],'cfiles/'.$key,$sec)) {
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
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
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
			if ($s3->putObjectFile(realpath($_FILES[$id]['tmp_name']),$GLOBALS['AWSbucket'],'qimages/'.$key,$sec)) {
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
	if (getfilehandlertype('filehandlertype') == 's3') {
		$s3object = "adata/$file";
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		return $s3->queryStringGet($GLOBALS['AWSbucket'],$s3object,7200);
	} else {
		return $imasroot.'/filestore/adata/'.$file;
	}
}

function getasidfilepath($file) {
	global $imasroot;
	if (getfilehandlertype('filehandlertype') == 's3') {
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
	if (getfilehandlertype('filehandlertype') =='s3') {
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
		$keylist = implode(',', array_map('intval', $val));
		$searchnot = "$tosearchby NOT IN ($keylist)";
	} else {
		$val = intval($val);
		$searchnot = "$tosearchby<>$val";
	}
	if ($aid != null) {
		if (is_array($aid)) {
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
		$lookforph[] = "lastanswers LIKE ? OR bestlastanswers LIKE ? OR reviewlastanswers LIKE ?";
		array_push($valarr, "%$file%", "%$file%", "%$file%");
	}
	$lookforstr = implode(' OR ',$lookforph);
	//$searchnot santized above
	if ($aid != null) {
		$stm = $DBH->prepare("SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchnot AND ($lookforstr)");
		$stm->execute($valarr);
		$skip = array();
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			preg_match_all('/@FILE:(.+?)@/',$row[0].$row[1].$row[2],$exmatch);
			//remove from todel list all files found in other sessions
			$todel = array_diff($todel,$exmatch[1]);
		}
	}
	$deled = array();
	if (getfilehandlertype('filehandlertype') == 's3') {
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
			if (@unlink($base.'/adata/'.$file)) {
				$deled[] = $file;
				recursiveRmdir(dirname($base.'/adata/'.$file));
			}
		}
	}

	return count($deled);
}

// Delete files from assess2 records on unenroll.
function deleteAssess2FilesOnUnenroll($tounenroll, $aids, $groupassess) {
	global $DBH;
	if (count($aids) == 0 || count($tounenroll) == 0) {
		return 0;
	}
	// look up any assessment records for these users/aids and pull out any
	// file uploads in them
	$aidlist = implode(',', array_map('intval', $aids));
	$userlist = implode(',', array_map('intval', $tounenroll));
	$query = "SELECT assessmentid,scoreddata,practicedata FROM imas_assessment_records ";
	$query .= "WHERE assessmentid IN ($aidlist) AND userid IN ($userlist)";
	$stm = $DBH->query($query);
	$todel = [];
	$tomaybedel = [];
	$tolookupaid = [];
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$scoreddata = gzdecode($row['scoreddata']);
		$practicedata = $row['practicedata']==''?'':gzdecode($row['practicedata']);
		preg_match_all('/@FILE:(.+?)@/', $scoreddata.$practicedata, $matches);
		foreach ($matches[1] as $file) {
			// if it's a group asssess, we'll look to see if anyone else is using
			// the same file later
			if (in_array($row['assessmentid'], $groupassess)) {
				if (!in_array($file, $tomaybedel)) {
					$tomaybedel[] = $file;
				}
				if (!in_array($row['assessmentid'], $tolookupaid)) {
					$tolookupaid[] = $row['assessmentid'];
				}
			} else {
				if (!in_array($file,$todel)) {
					$todel[] = $file;
				}
			}
		}
	}
	if (count($tolookupaid)>0) {
		// these are the group assessments that had files.
		// look up other assessment records for same assessment but other students
		// Remove any tomaybedel files that are used elsewhere
		$aidlist2 = implode(',', $tolookupaid);
		$query = "SELECT assessmentid,scoreddata,practicedata FROM imas_assessment_records ";
		$query .= "WHERE assessmentid IN ($aidlist2) AND userid NOT IN ($userlist)";
		$stm = $DBH->query($query);
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$scoreddata = gzdecode($row['scoreddata']);
			$practicedata = gzdecode($row['practicedata']);
			preg_match_all('/@FILE:(.+?)@/', $scoreddata.$practicedata, $exmatch);
			//remove from tolookup list all files found in other sessions
			$tomaybedel = array_diff($tomaybedel, $exmatch[1]);
			if (count($tomaybedel)===0) {
				break;
			}
		}
	}
	// combine the lists
	$todel = array_unique(array_merge($todel, $tomaybedel));

	$deled = array();

	if (getfilehandlertype('filehandlertype') == 's3') {
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
			if (@unlink(realpath($base.'/adata/'.$file))) {
				$deled[] = $file;
				recursiveRmdir(realpath(dirname($base.'/adata/'.$file)));
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
		$keylist = implode(',', array_map('intval', $val));
		$searchwhere = "$tosearchby IN ($keylist)";
		$searchnot = "$tosearchby NOT IN ($keylist)";
	} else {
		$val = intval($val);
		$searchwhere = "$tosearchby=$val";
		$searchnot = "$tosearchby<>$val";
	}
	if ($aid === null && $tosearchby!='userid') {
		$stm = $DBH->query("SELECT assessmentid FROM imas_assessment_sessions WHERE $searchwhere");
		$aid = $stm->fetchColumn(0);
	}
	if ($aid != null) {
		if (is_array($aid)) {
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
	//searchwhere is sanitized above
	$stm = $DBH->query("SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchwhere");
	if ($stm->rowCount()==0) {return 0;}
	$todel = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		preg_match_all('/@FILE:(.+?)@/',$row[0].$row[1].$row[2],$matches);
		foreach ($matches[1] as $file) {
			if (!in_array($file,$todel)) {
				$todel[] = $file;
			}
		}
	}
	if (count($todel)==0) {return 0;}
	$valarr = array();
	$lookforph = array();
	foreach ($todel as $file) {
		$lookforph[] = "lastanswers LIKE ? OR bestlastanswers LIKE ? OR reviewlastanswers LIKE ?";
		array_push($valarr, "%$file%", "%$file%", "%$file%");
	}
	$lookforstr = implode(' OR ',$lookforph);
	if ($aid != null) {
		$stm = $DBH->prepare("SELECT lastanswers,bestlastanswers,reviewlastanswers FROM imas_assessment_sessions WHERE $searchnot AND ($lookforstr)");
		$stm->execute($valarr);
		$skip = array();
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			preg_match_all('/@FILE:(.+?)@/',$row[0].$row[1].$row[2],$exmatch);
			//remove from todel list all files found in other sessions
			$todel = array_diff($todel,$exmatch[1]);
		}
	}
	$deled = array();

	if (getfilehandlertype('filehandlertype') == 's3') {
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
			if (@unlink(realpath($base.'/adata/'.$file))) {
				$deled[] = $file;
				recursiveRmdir(realpath(dirname($base.'/adata/'.$file)));
			}
		}
	}
	return count($deled);
}
/*
//wherearr array of imas_assessment_sessions id=>val for WHERE
function deleteasidfilesbyquery($wherearr,$lim=0) {
	if (getfilehandlertype('filehandlertype') == 's3') {
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
	if (getfilehandlertype('filehandlertype') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$arr = $s3->getBucket($GLOBALS['AWSbucket'],"adata/$aid/");
		if ($arr!=false) {
			$delcnt = deleteFilesFromGetBucketCall($s3,$arr);
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
	if (getfilehandlertype('filehandlertype') == 's3') {
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
	if (getfilehandlertype('filehandlertype') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "ufiles/$uid/$safeFilename";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (@unlink($base."/ufiles/$uid/$safeFilename")) {
			return true;
		} else {
			return false;
		}
	}
}

function deleteforumfile($postid,$file) {
	$postid = Sanitize::simpleString($postid);
	$safeFilename = Sanitize::sanitizeFilenameAndCheckBlacklist($file);
	if (getfilehandlertype('filehandlertype') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "ffiles/$postid/$safeFilename";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (@unlink($base."/ffiles/$postid/$safeFilename")) {
			return true;
		} else {
			return false;
		}
	}
}

function deletecoursefile($file) {
	$safeFilename = Sanitize::sanitizeFilePathAndCheckBlacklist($file);
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "cfiles/$safeFilename";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files';
		if (@unlink($base."/$safeFilename")) {
			return true;
		} else {
			return false;
		}
	}
}

function deletecoursefiles($files) {
    if (getfilehandlertype('filehandlertypecfiles') == 's3') {
        $s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
        $fileuris = [];
        foreach ($files as $file) {
            $safeFilename = Sanitize::sanitizeFilePathAndCheckBlacklist($file);
            $fileuris[] = "cfiles/$safeFilename";
        }
        if ($s3->deleteObjects($GLOBALS['AWSbucket'], $fileuris)) {
            return true;
        } else {
            return false;
        }
    } else {
        $base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files';
        $success = true;
        foreach ($files as $file) {
            $safeFilename = Sanitize::sanitizeFilePathAndCheckBlacklist($file);
            if (@unlink($base."/$safeFilename")) {
            } else {
                $success = false;
            }
        }
        return $success;
    }
}

function deleteqimage($file) {
	$safeFilename = Sanitize::sanitizeFilenameAndCheckBlacklist($file);
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "qimages/$safeFilename";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/assessment/qimages';
		if (@unlink($base."/$safeFilename")) {
			return true;
		} else {
			return false;
		}
	}
}

function deletefilebykey($key) {
	$safeFilename = Sanitize::sanitizeFilePathAndCheckBlacklist($file);
	if (getfilehandlertype('filehandlertype') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = $safeFilename;
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (@unlink($base."/$safeFilename")) {
			return true;
		} else {
			return false;
		}
	}
}

function deleteallpostfiles($postid) {
	$postid = Sanitize::onlyInt($postid);
	$delcnt = 0;
	if (getfilehandlertype('filehandlertype') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$arr = $s3->getBucket($GLOBALS['AWSbucket'],"ffiles/$postid/");
		if ($arr!=false) {
			$delcnt = deleteFilesFromGetBucketCall($s3,$arr);
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

// use internally only
function deleteFilesFromGetBucketCall($s3,$arr) {
    $delcnt = 0;
    $files = [];
    foreach ($arr as $s3object) {
        $files[] = $s3object['name'];
        if (count($files)>500) {
            if ($s3->deleteObjects($GLOBALS['AWSbucket'], $files)) {
                $delcnt += count($files);
            }
            $files = [];
        }
    }
    if (count($files)>0) {
        if ($s3->deleteObjects($GLOBALS['AWSbucket'], $files)) {
            $delcnt += count($files);
        }
    }
    return $delcnt;
}

function deletealluserfiles($uid) {
	$delcnt = 0;
	if (getfilehandlertype('filehandlertype') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$arr = $s3->getBucket($GLOBALS['AWSbucket'],"ufiles/$uid/");
		if ($arr!=false) {
            $delcnt = deleteFilesFromGetBucketCall($s3,$arr);
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

function getfilesize($type, $key) {
    if ($type=='cfile') {
        if (getfilehandlertype('filehandlertypecfiles') == 's3') {
			$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			return $s3->getObjectInfo($GLOBALS['AWSbucket'], 'cfiles/'.$key, true)['size'];
		} else {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files/';
			return filesize($base.$key);
		}
    } else {
        if (getfilehandlertype('filehandlertype') == 's3') {
			$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			return $s3->getObjectInfo($GLOBALS['AWSbucket'], $key, true)['size'];
		} else {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore/';
			return filesize($base.$key);
		}
    }
}

function doesfileexist($type,$key) {
	if ($type=='cfile') {
		if (getfilehandlertype('filehandlertypecfiles') == 's3') {
			$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			return $s3->getObjectInfo($GLOBALS['AWSbucket'], 'cfiles/'.$key, false);
		} else {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files/';
			return file_exists($base.$key);
		}
	} else if ($type=='graphimg') {
		if (getfilehandlertype('filehandlertypecfiles') == 's3') {
			$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			return $s3->getObjectInfo($GLOBALS['AWSbucket'], 'gimgs/'.$key, false);
		} else {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filter/graph/imgs/';
			return file_exists($base.$key);
		}
	} else {
		if (getfilehandlertype('filehandlertype') == 's3') {
			$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			return $s3->getObjectInfo($GLOBALS['AWSbucket'], $key, false);
		} else {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore/';
			return file_exists($base.$key);
		}
	}
}

function copycoursefile($key,$dest) {
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3->getObject($GLOBALS['AWSbucket'], 'cfiles/'.$key, $dest);
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files/';
		copy($base.$key, $dest);
	}
}
function copyqimage($key,$dest) {
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3->getObject($GLOBALS['AWSbucket'], 'qimages/'.$key, $dest);
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/assessment/qimages/';
		copy($base.$key, $dest);
	}
}

function getuserfileurl($key) {
	global $urlmode,$imasroot;
	$key = Sanitize::rawurlencodePath($key);
	if (getfilehandlertype('filehandlertype') == 's3') {
		//return $urlmode."s3.amazonaws.com/{$GLOBALS['AWSbucket']}/$key";
		return 'https://'.$GLOBALS['AWSbucket'].".s3.amazonaws.com/$key";
	} else {
		return "$imasroot/filestore/$key";
	}
}
function getprivatefileurl($key,$exp=7200) {
    global $imasroot;
    $key = Sanitize::rawurlencodePath($key);
	if (getfilehandlertype('filehandlertype') == 's3') {
		$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		return $s3->queryStringGet($GLOBALS['AWSbucket'],$key,$exp);
	} else {
		return $imasroot.'/filestore/'.$key;
	}
}
function getfopenloc($key) {
	global $urlmode,$imasroot;
	$key = Sanitize::rawurlencodePath($key);
	if (getfilehandlertype('filehandlertype') == 's3') {
		return 'https://'.$GLOBALS['AWSbucket'].".s3.amazonaws.com/$key";
	} else {
		return "../filestore/$key";
	}
}
function getcoursefileurl($key,$abs=false) {
	global $urlmode,$imasroot;
	$st = substr($key,0,6);
	if ($st == 'http:/' || $st=='https:') {
		return $key;
	} else if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		//return $urlmode."s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/$key";
		return 'https://'.$GLOBALS['AWSbucket'].".s3.amazonaws.com/cfiles/$key";
	} else {
		$key = Sanitize::rawurlencodePath($key);  //shouldn't be needed since filenames sanitized, but better to be safe
		if ($abs==true) {
			return $GLOBALS['basesiteurl'] . "/course/files/$key";
		} else {
			return "$imasroot/course/files/$key";
		}
	}
}
function getqimageurl($key,$abs=false) {
	global $urlmode,$imasroot;
	$key = Sanitize::rawurlencodePath($key);
	if (getfilehandlertype('filehandlertypecfiles') == 's3') {
		return 'https://'.$GLOBALS['AWSbucket'].".s3.amazonaws.com/qimages/$key";
	} else {
		if ($abs==true) {
			return $GLOBALS['basesiteurl'] . "/assessment/qimages/$key";
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
		if (@unlink($dir . '/' . $obj)) {
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
