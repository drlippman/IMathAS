<?php
namespace app\components;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\AssessmentSession;
use Yii;
use yii\base\Component;

require("S3.php");

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


class filehandler extends Component
{
public  static function storecontenttofile($content,$key,$sec="private") {
	if ($GLOBALS['filehandertype'] == 's3') {
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
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
			filehandler::mkdir_recursive($dir);
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

public static function relocatecoursefileifneeded($file, $key, $sec="public")
{
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		if ($s3->putObjectFile($file,$GLOBALS['AWSbucket'],'cfiles/'.$key,$sec)) {
			return true;
		} else {
			return false;
		}
	} else {
		return true;
	}
}

public static function relocatefileifneeded($file, $key, $sec="public") {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		if ($s3->putObjectFile($file,$GLOBALS['AWSbucket'],$key,$sec)) {
			return filehandler::getuserfileurl($key);
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
			$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
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
				filehandler::mkdir_recursive($dir);
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

public static function storeuploadedcoursefile($id,$key,$sec="public-read") {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
			$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			$t=0;
			$fn = $key;
			while ($s3->getObjectInfo($GLOBALS['AWSbucket'], 'cfiles/'.$key, false) !==false) {
				$key = substr($fn,0,strpos($fn,"."))."_$t".strstr($fn,".");
				$t++;
			}
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
            $base = rtrim(dirname(dirname(__FILE__)), '/\\').'/web/Uploads/';
            $keydir = dirname($key);
			$dir = $base.dirname($key);
			$fn = basename($key);

			if (!is_dir($dir)) {
				filehandler::mkdir_recursive($dir);
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
public static function storeuploadedqimage($id,$key,$sec="public-read") {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		if ($sec=="public" || $sec=="public-read") {
			$sec = "public-read";
		} else {
			$sec = "private";
		}
		if (is_uploaded_file($_FILES[$id]['tmp_name'])) {
			$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			$t=0;
			$fn = $key;
			while ($s3->getObjectInfo($GLOBALS['AWSbucket'], 'qimages/'.$key, false) !==false) {
				$key = substr($fn,0,strpos($fn,"."))."_$t".strstr($fn,".");
				$t++;
			}
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
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/web/Uploads/qimages/';
			$dir = $base.dirname($key);
			$fn = basename($key);
			if (!is_dir($dir)) {
				filehandler::mkdir_recursive($dir);
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
public  static function getasidfileurl($file) {

	if ($GLOBALS['filehandertype'] == 's3') {
		$s3object = "adata/$file";
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		return $s3->queryStringGet($GLOBALS['AWSbucket'],$s3object,7200);
	} else {
		return AppUtility::getHomeURL().'/filestore/adata/'.$file;
	}
}

function getasidfilepath($file) {
	global $imasroot;
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3object = "adata/$file";
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
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
public static function deleteasidfilesfromstring2($str,$tosearchby,$val,$aid=null) {

    $n = preg_match_all('/@FILE:(.+?)@/',$str,$matches);
    if ($n==0 || $n===false) {return 0;}
	$todel = $matches[1];
     $lim = 0;
    $query = AssessmentSession::getSessionInfoForUnenroll($val,$tosearchby,$aid,$lim, $todel);
	$skip = array();
    foreach($query as $data)
    {
        preg_match_all('/@FILE:(.+?)@/',$data['lastanswers'].$data['bestlastanswers'].$data['reviewlastanswers'],$exmatch);
        $todel = array_diff($todel,$exmatch['bestlastanswers']);
    }
	$deled = array();
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
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

				filehandler::recursiveRmdir(dirname($base.'/adata/'.$file));
			}
		}
	}
	
	return count($deled);
}
	
	
public static function deleteasidfilesbyquery2($tosearchby,$val,$aid=null,$lim=0) {

    $query = AssessmentSession::getSessionDataForUnenroll($val,$tosearchby,$aid,$lim);

    if($query == "" || $query == null){
        return 0;
    }

    $todel = array();
	foreach($query as $session){
        preg_match_all('/@FILE:(.+?)@/',$session['lastanswers'].$session['bestlastanswers'].$session['reviewlastanswers'],$matches);
        foreach($matches[1] as $file){
            if (!in_array($file,$todel)) {
                $todel[] = $file;
            }
        }
    }

    if (count($todel)==0) {return 0;}
    $query = AssessmentSession::getSessionInfoForUnenroll($val,$tosearchby,$aid,$lim, $todel);
	$skip = array();
    foreach($query as $session){
        preg_match_all('/@FILE:(.+?)@/',$session['lastanswers'].$session['bestlastanswers'].$session['reviewlastanswers'],$exmatch);
        $todel = array_diff($todel,$exmatch[1]);
    }
	$deled = array();
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
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
				filehandler::recursiveRmdir(dirname($base.'/adata/'.$file));
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
public static function deleteallaidfiles($aid) {

	$delcnt = 0;
	if ($GLOBALS['filehandertype'] == 's3')
    {
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
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
		if (($delcnt = filehandler::unlinkRecursive($base."/adata/$aid",true))==0) {
			return false;
		}
	}
	return $delcnt;
}


function getuserfiles($uid,$img=false) {
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
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
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
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
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "/$postid/$file";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore';
		if (unlink($base."/$postid/$file")) {
			return true;
		} else {
			return false;
		}
	}
}

public static function deletecoursefile($file) {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "cfiles/$file";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {

        $filePath = AppConstant::UPLOAD_DIRECTORY.$file;
        if(file_exists($filePath)){
            if (unlink($filePath)) {
                return true;
            } else {
                return false;
            }
        }

	}
}
public static function deleteqimage($file) {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3object = "qimages/$file";
		if($s3->deleteObject($GLOBALS['AWSbucket'],$s3object)) {
			return true;
		}else {
			return false;
		}
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/web/Uploads/qimages';
		if (unlink($base."/$file")) {
			return true;
		} else {
			return false;
		}
	}
}

function deletefilebykey($key) {
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
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

public static function deleteallpostfiles($postid) {
	$delcnt = 0;
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
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
		if (($delcnt = filehandler::unlinkRecursive($base."/ffiles/$postid",true))==0) {
			return false;
		}
	}
	return $delcnt;
}

public static function deletealluserfiles($uid) {
	$delcnt = 0;
	if ($GLOBALS['filehandertype'] == 's3') {
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
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
		if (($delcnt =  filehandler::unlinkRecursive($base."/ufiles/$uid",true))==0) {
			return false;
		}
	}
	return $delcnt;
}

public  static function doesfileexist($type,$key) {
	if ($type=='cfile') {
		if ($GLOBALS['filehandertypecfiles'] == 's3') {
			$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			return $s3->getObjectInfo($GLOBALS['AWSbucket'], 'cfiles/'.$key, false);
		} else {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files/';
			return file_exists($base.$key);
		}
	} else {
		if ($GLOBALS['filehandertype'] == 's3') {
			$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
			return $s3->getObjectInfo($GLOBALS['AWSbucket'], $key, false);
		} else {
			$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/filestore/';
			return file_exists($base.$key);
		}
	}
}

function copycoursefile($key,$dest) {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3->getObject($GLOBALS['AWSbucket'], 'cfiles/'.$key, $dest);
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/course/files/';
		copy($base.$key, $dest);
	}
}
function copyqimage($key,$dest) {
	if ($GLOBALS['filehandertypecfiles'] == 's3') {
		$s3 = new \S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
		$s3->getObject($GLOBALS['AWSbucket'], 'qimages/'.$key, $dest);
	} else {
		$base = rtrim(dirname(dirname(__FILE__)), '/\\').'/assessment/qimages/';
		copy($base.$key, $dest);
	}
}

public static function getuserfileurl($key) {
    return AppUtility::getHomeURL().AppConstant::UPLOAD_DIRECTORY.$key;
}
public static function getcoursefileurl($key) {
    return AppUtility::getHomeURL().AppConstant::UPLOAD_DIRECTORY.$key;
	}
public static function mkdir_recursive($pathname, $mode=0777)
{
    is_dir(dirname($pathname)) || filehandler::mkdir_recursive(dirname($pathname), $mode);
    return is_dir($pathname) || @mkdir($pathname, $mode);
}
public static function recursiveRmdir($dir) {
	if (basename($dir)=='adata' || basename($dir)=='ufiles' || basename($dir)=='files') { return;}
	if (@rmdir($dir)) {
		filehandler::recursiveRmdir(dirname($dir));
	}
}
public static function unlinkRecursive($dir, $deleteRootToo) {
    $cnt = 0;
    if(!$dh = @opendir($dir))
    {
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
		$cnt += filehandler::unlinkRecursive($dir.'/'.$obj, true);
	}
    }
    closedir($dh);
   
    if ($deleteRootToo) {
        @rmdir($dir);
    }
    return $cnt;
}

}
?>