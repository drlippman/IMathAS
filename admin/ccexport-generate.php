<?php
//Generate Common Cartridge export
//IMathAS (c) 2018 David Lippman

if (!isset($myrights)) {
	echo 'This file cannot be called directly';
	exit;
}

error_reporting(0);
$loadmathfilter = 1;
$loadgraphfilter = 1;
require_once("../filter/filter.php");
require_once("../includes/filehandler.php");
if (substr($mathimgurl,0,4) !== 'http') {
    // need to make an absolute url
    if (strlen($imasroot) > 0) { 
        $mathimgurl = substr($basesiteurl,0,-1*strlen($imasroot)) . $mathimgurl;
    } else {
        $mathimgurl = $basesiteurl . $mathimgurl;
    }
}

if (!defined('ENT_XML1')) {
	define('ENT_XML1',ENT_QUOTES);
}

function dir_is_empty($dirname) {
  if (!is_dir($dirname)) return false;
  foreach (scandir($dirname) as $file)
  {
    if (!in_array($file, array('.','..','.svn','.git'))) return false;
  }
  return true;
}

$usechecked = ($_POST['whichitems']=='select' || empty($_POST['whichitems']));
if ($usechecked) {
	$checked = $_POST['checked'];
} else {
	$checked = array();
}

if ($_POST['lms']=='canvas') {
	$linktype = 'canvas';
} else {
	$linktype = 'url';
}

$iteminfo = array();
$stm = $DBH->prepare("SELECT id,itemtype,typeid FROM imas_items WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$iteminfo[$row[0]] = array($row[1],$row[2]);
}
$stm = $DBH->prepare("SELECT itemorder,name,dates_by_lti FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
list($itemorder,$coursename,$datesbylti) = $stm->fetch(PDO::FETCH_NUM);
$items = unserialize($itemorder);

if ($linktype=='canvas') {
	$newdatesbylti = empty($_POST['datesbylti'])?0:1;
	if ($newdatesbylti != $datesbylti) {
		$stm = $DBH->prepare("UPDATE imas_courses SET dates_by_lti=:datesbylti WHERE id=:id");
		$stm->execute(array(':id'=>$cid, ':datesbylti'=>$newdatesbylti));
		if ($newdatesbylti==1) {
			$stm = $DBH->prepare("UPDATE imas_assessments SET date_by_lti=1 WHERE date_by_lti=0 AND courseid=:cid");
			$stm->execute(array(':cid'=>$cid));
		} else {
			//undo it - doesn't restore dates
			$stm = $DBH->prepare("UPDATE imas_assessments SET date_by_lti=0 WHERE date_by_lti>0 AND courseid=:cid");
			$stm->execute(array(':cid'=>$cid));
			//remove is_lti from exceptions with latepasses
			$query = "UPDATE imas_exceptions JOIN imas_assessments ";
			$query .= "ON imas_exceptions.assessmentid=imas_assessments.id ";
			$query .= "SET imas_exceptions.is_lti=0 ";
			$query .= "WHERE imas_exceptions.is_lti>0 AND imas_exceptions.islatepass>0 AND imas_assessments.courseid=:cid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':cid'=>$cid));
			//delete any other is_lti exceptions
			$query = "DELETE imas_exceptions FROM imas_exceptions JOIN imas_assessments ";
			$query .= "ON imas_exceptions.assessmentid=imas_assessments.id ";
			$query .= "WHERE imas_exceptions.is_lti>0 AND imas_exceptions.islatepass=0 AND imas_assessments.courseid=:cid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':cid'=>$cid));
		}
	}
}

$newdir = $path . '/CCEXPORT'.$cid;
mkdir($newdir);

$manifestorg = '';
$manifestres = array();

$imgcnt = 1;
if (substr($mathimgurl,0,4)!='http') {
	$addmathabs = true;
} else {
	$addmathabs = false;
}

$htmldir = '';
$filedir = '';
$gbcats = array();
$usedcats = array();
if ($linktype=='canvas') {
	mkdir($newdir.'/wiki_content');
	mkdir($newdir.'/web_resources');
	$htmldir = 'wiki_content/';
	$filedir = 'web_resources/';

	$stm = $DBH->prepare("SELECT useweights,defaultcat FROM imas_gbscheme WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	list($useweights,$defaultcat) = $stm->fetch(PDO::FETCH_NUM);
	$r = explode(',',$defaultcat);
	$row['name'] = 'Default';
	$row['dropn'] = $r[3];
	$row['weight'] = $r[4];
	$gbcats[0] = $row;
	$usedcats[0] = 0;
	$stm = $DBH->prepare("SELECT id,name,dropn,weight FROM imas_gbcats WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$gbcats[$row['id']] = $row;
		$usedcats[$row['id']] = 0;
	}
}

function filtercapture($str,&$res) {
	global $newdir,$imgcnt,$imasroot,$addmathabs,$mathimgurl,$filedir,$linktype;
	$str = forcefiltermath($str);
	$str = forcefiltergraph($str);
	$graphfiles = getgraphfilenames($str);
	foreach ($graphfiles as $f) {
		copy("../filter/graph/imgs/$f",$newdir.'/'.$filedir.$f);
		$resitem =  '<resource href="'.$filedir.$f.'" identifier="RESwebcontentImage'.$imgcnt.'" type="webcontent">'."\n";
		$resitem .= '  <file href="'.$filedir.$f.'" />'."\n";
		$resitem .= '</resource>';
		$res[] = $resitem;
		$imgcnt++;
	}
	if ($linktype=='canvas') {
		$str = str_replace($imasroot.'/filter/graph/imgs/','$IMS_CC_FILEBASE$/',$str);
	} else {
		$str = str_replace($imasroot.'/filter/graph/imgs/','',$str);
	}
	if ($addmathabs) {
		$str = str_replace($mathimgurl,'http://'. Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']). $mathimgurl, $str);
	}
	return $str;
}

$ccnt = 1;
$module_meta = '';

$toplevelitems = '';
$inmodule = false;

function getorg($it,$parent,&$res,$ind,$mod_depth) {
	global $DBH,$iteminfo,$newdir,$installname,$urlmode,$linktype,$urlmode,$imasroot,$ccnt,$module_meta,$htmldir,$filedir, $toplevelitems, $inmodule;
	global $usechecked,$checked,$usedcats;

	$out = '';

	foreach ($it as $k=>$item) {
		$canvout = '';
		if (is_array($item)) {
			if (!$usechecked || array_search($parent.'-'.($k+1),$checked)!==FALSE) {
				$mod_depth_change = 1;
				$blockid = uniqid();
				if ($mod_depth>0 || strlen($ind)>2) {
					$canvout .= '<item identifier="BLOCK'.$blockid.'">'."\n";
					$canvout .= '<content_type>ContextModuleSubHeader</content_type>';
					$canvout .= '<title>'.htmlentities($item['name'],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$canvout .= '  <workflow_state>'.($item['avail']==0?'unpublished':'active').'</workflow_state>'."\n";
					$canvout .= "<position>$ccnt</position> <indent>".max($mod_depth-1,0)."</indent> </item>";
					$ccnt++;
					if ($inmodule && $mod_depth>0) {
						$module_meta .= $canvout;
					} else {
						$toplevelitems .= $canvout;
						$mod_depth_change = 2;
						if ($inmodule) {
							$module_meta .= '</items></module>';
							$inmodule = false;
						}
					}
				} else {
					if ($inmodule) {
						$module_meta .= '</items></module>';
					}
					$inmodule = true;
					$module_meta .= '<module identifier="BLOCK'.$blockid.'">'."\n";
					$module_meta .= '  <title>'.htmlentities($item['name'],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$module_meta .= '  <workflow_state>'.($item['avail']==0?'unpublished':'active').'</workflow_state>'."\n";
					if ($item['avail'] == 1 && $item['SH']{0} == 'H' && $item['startdate'] > 0 && isset($_POST['includestartdates'])) {
						$module_meta .= '  <unlock_at>'.gmdate("Y-m-d\TH:i:s", $item['startdate']).'</unlock_at>'."\n";
					}
					$module_meta .= '  <items>';
				}
				$out .= $ind.'<item identifier="BLOCK'.$blockid.'">'."\n";
				$out .= $ind.'  <title>'.htmlentities($item['name'],ENT_XML1,'UTF-8',false).'</title>'."\n";
				$out .= $ind.getorg($item['items'],$parent.'-'.($k+1),$res,$ind.'  ', $mod_depth+$mod_depth_change);
				$out .= $ind.'</item>'."\n";
			} else {
				$out .= $ind.getorg($item['items'],$parent.'-'.($k+1),$res,$ind.'  ', $mod_depth);
			}

		} else {
			if ($usechecked && array_search($item,$checked)===FALSE) {
				continue;
			}
			if ($iteminfo[$item][0]=='InlineText') {
				$stm = $DBH->prepare("SELECT title,text,fileorder,avail FROM imas_inlinetext WHERE id=:id");
				$stm->execute(array(':id'=>$iteminfo[$item][1]));
				$row = $stm->fetch(PDO::FETCH_NUM);
				if ($row[2]!='') {
					$files = explode(',',$row[2]);
					$stm = $DBH->prepare("SELECT id,description,filename FROM imas_instr_files WHERE itemid=:itemid");
					$stm->execute(array(':itemid'=>$iteminfo[$item][1]));
					$filesout = array();
					while ($r = $stm->fetch(PDO::FETCH_NUM)) {
						//if s3 filehandler, do files as weblinks rather than including the file itself
						if (substr($r[2],0,4)=='http') {
							//do nothing
						} else if (getfilehandlertype('filehandlertypecfiles') == 's3') {
							$r[2] = getcoursefileurl($r[2]);
						} else {
							//copy("../course/files/{$r[2]}",$newdir.'/'.$r[2]);
							copycoursefile($r[2], $newdir.'/'.$filedir.basename($r[2]));
							$resitem =  '<resource href="'.$filedir.basename($r[2]).'" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'file'.$r[0].'" type="webcontent">'."\n";
							$resitem .= '  <file href="'.$filedir.basename($r[2]).'" />'."\n";
							$resitem .= '</resource>';
							$res[] = $resitem;
						}
						$filesout[$r[0]] = array($r[1],$r[2]);
					}
				}
				$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
				$out .= $ind.'  <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
				$out .= $ind.'</item>'."\n";
				$canvout .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
				$canvout .= '<content_type>WikiPage</content_type>';
				$canvout .= '<workflow_state>'.($row[3]==0?'unpublished':'active').'</workflow_state>'."\n";
				$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
				$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
				$canvout .= "<position>$ccnt</position> <indent>".max($mod_depth-1,0)."</indent> </item>";
				$ccnt++;

				$fp = fopen($newdir.'/'.$htmldir.'inlinetext'.$iteminfo[$item][1].'.html','w');
				fwrite($fp,'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">');
				fwrite($fp,'<title>'.htmlentities($row[0]).'</title>');
				fwrite($fp,'<meta name="identifier" content="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'"/>');
				if ($linktype=="canvas") {
					fwrite($fp,'<meta name="editing_roles" content="teachers"/>');
					fwrite($fp,'<meta name="workflow_state" content="'.($row[3]==0?'unpublished':'active').'"/>');
				}
				fwrite($fp,"</head><body>");
				fwrite($fp,filtercapture($row[1],$res));
				if ($row[2]!='') {
					fwrite($fp,'<ul>');
					foreach ($files as $f) {
						if (getfilehandlertype('filehandlertypecfiles') == 's3') {
							fwrite($fp,'<li><a href="'.$filesout[$f][1].'">'.htmlentities($filesout[$f][0]).'</a></li>');
						} else {
							fwrite($fp,'<li><a href="'.$filedir.basename($filesout[$f][1]).'">'.htmlentities($filesout[$f][0]).'</a></li>');
						}
					}
					fwrite($fp,'</ul>');
				}
				fwrite($fp,'</body></html>');
				fclose($fp);
				$resitem =  '<resource href="'.$htmldir.'inlinetext'.$iteminfo[$item][1].'.html" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="webcontent">'."\n";
				$resitem .= '  <file href="'.$htmldir.'inlinetext'.$iteminfo[$item][1].'.html" />'."\n";
				$resitem .= '</resource>';
				$res[] = $resitem;
			} else if ($iteminfo[$item][0]=='LinkedText') {
				$stm = $DBH->prepare("SELECT title,text,summary,avail FROM imas_linkedtext WHERE id=:id");
				$stm->execute(array(':id'=>$iteminfo[$item][1]));
				$row = $stm->fetch(PDO::FETCH_NUM);

				//if s3 filehandler, do files as weblinks rather than including the file itself
				if (getfilehandlertype('filehandlertypecfiles') == 's3' && substr(strip_tags($row[1]),0,5)=="file:") {
					$row[1] = getcoursefileurl(trim(substr(strip_tags($row[1]),5)));
				}

				if ((substr($row[1],0,4)=="http") && (strpos(trim($row[1])," ")===false)) { //is a web link
					$alink = trim($row[1]);
					$fp = fopen($newdir.'/weblink'.$iteminfo[$item][1].'.xml','w');
					fwrite($fp,'<webLink xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imswl_v1p1">');
					fwrite($fp,' <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>');
					fwrite($fp,' <url href="'.htmlentities($alink,ENT_XML1,'UTF-8',false).'" target="_blank"/>');
					fwrite($fp,'</webLink>');
					fclose($fp);
					$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= $ind.'  <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$out .= $ind.'</item>'."\n";
					$canvout .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$canvout .= '<content_type>ExternalUrl</content_type>';
					$canvout .= '<workflow_state>'.($row[3]==0?'unpublished':'active').'</workflow_state>'."\n";
					$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
					$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$canvout .= '<url>'.htmlentities($alink,ENT_XML1,'UTF-8',false).'</url>';
					$canvout .= "<position>$ccnt</position> <indent>".max($mod_depth-1,0)."</indent> </item>";
					$ccnt++;
					$resitem =  '<resource identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="imswl_xmlv1p1">'."\n";
					$resitem .= '  <file href="weblink'.$iteminfo[$item][1].'.xml" />'."\n";
					$resitem .= '</resource>';
					$res[] = $resitem;
				} else if (substr(strip_tags($row[1]),0,5)=="file:") {  //is a file
					$filename = trim(substr(strip_tags($row[1]),5));
					//copy("../course/files/$filename",$newdir.'/'.$filedir.$filename);
					copycoursefile($filename, $newdir.'/'.$filedir.basename($filename));

					$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= $ind.'  <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$out .= $ind.'</item>'."\n";
					$canvout .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$canvout .= '<content_type>Attachment</content_type>';
					$canvout .= '<workflow_state>'.($row[3]==0?'unpublished':'active').'</workflow_state>'."\n";
					$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
					$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$canvout .= "<position>$ccnt</position> <indent>".max($mod_depth-1,0)."</indent> </item>";
					$ccnt++;
					$resitem =  '<resource href="'.$filedir.basename($filename).'" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="webcontent">'."\n";
					$resitem .= '  <file href="'.$filedir.basename($filename).'" />'."\n";
					$resitem .= '</resource>';
					$res[] = $resitem;
				} else { //is text
					$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= $ind.'  <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$out .= $ind.'</item>'."\n";
					$canvout .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$canvout .= '<content_type>WikiPage</content_type>';
					$canvout .= '<workflow_state>'.($row[3]==0?'unpublished':'active').'</workflow_state>'."\n";
					$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
					$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$canvout .= "<position>$ccnt</position> <indent>".max($mod_depth-1,0)."</indent> </item>";
					$ccnt++;
					$fp = fopen($newdir.'/'.$htmldir.'linkedtext'.$iteminfo[$item][1].'.html','w');
					fwrite($fp,'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">');
					fwrite($fp,'<title>'.htmlentities($row[0]).'</title>');
					fwrite($fp,'<meta name="identifier" content="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'"/>');
					if ($linktype=="canvas") {
						fwrite($fp,'<meta name="editing_roles" content="teachers"/>');
						fwrite($fp,'<meta name="workflow_state" content="'.($row[3]==0?'unpublished':'active').'"/>');
					}
					fwrite($fp,"</head><body>");
					fwrite($fp,filtercapture($row[1],$res));
					fwrite($fp,'</body></html>');
					fclose($fp);
					$resitem =  '<resource href="'.$htmldir.'linkedtext'.$iteminfo[$item][1].'.html" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="webcontent">'."\n";
					$resitem .= '  <file href="'.$htmldir.'linkedtext'.$iteminfo[$item][1].'.html" />'."\n";
					$resitem .= '</resource>';
					$res[] = $resitem;
				}
			} else if ($iteminfo[$item][0]=='Forum') {
				$stm = $DBH->prepare("SELECT name,description,avail FROM imas_forums WHERE id=:id");
				$stm->execute(array(':id'=>$iteminfo[$item][1]));
				$row = $stm->fetch(PDO::FETCH_NUM);
				$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
				$out .= $ind.'  <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
				$out .= $ind.'</item>'."\n";
				$canvout .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
				$canvout .= '<content_type>DiscussionTopic</content_type>';
				$canvout .= '<workflow_state>'.($row[2]==0?'unpublished':'active').'</workflow_state>'."\n";
				$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
				$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
				$canvout .= "<position>$ccnt</position> <indent>".max($mod_depth-1,0)."</indent> </item>";
				$ccnt++;
				$fp = fopen($newdir.'/forum'.$iteminfo[$item][1].'.xml','w');
				fwrite($fp,'<topic xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imsdt_v1p1">');
				fwrite($fp,' <title >'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>');
				fwrite($fp,' <text texttype="text/html">'.htmlentities(filtercapture($row[1],$res)).'</text>');
				fwrite($fp,'</topic>');
				fclose($fp);

				if ($linktype=='canvas') {
					$fp = fopen($newdir.'/RES'.$iteminfo[$item][0].$iteminfo[$item][1].'meta.xml','w');
					fwrite($fp,'<?xml version="1.0" encoding="UTF-8"?>
						<topicMeta xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 http://canvas.instructure.com/xsd/cccv1p0.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'meta" xmlns="http://canvas.instructure.com/xsd/cccv1p0">
						  <topic_id>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</topic_id>
						  <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>
						  <type>topic</type>
						  <workflow_state>'.($row[2]==0?'unpublished':'active').'</workflow_state>
						</topicMeta>');
					fclose($fp);
					$resitem =  '<resource identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'meta" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" href="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'meta.xml">'."\n";
					$resitem .= '  <file href="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'meta.xml" />'."\n";
					$resitem .= '</resource>';
					$res[] = $resitem;
					$resitem =  '<resource identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="imsdt_xmlv1p1">'."\n";
					$resitem .= '  <file href="forum'.$iteminfo[$item][1].'.xml" />'."\n";
					$resitem .= '  <dependency identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'meta"/>';
					$resitem .= '</resource>';
				} else {
					$resitem =  '<resource identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="imsdt_xmlv1p1">'."\n";
					$resitem .= '  <file href="forum'.$iteminfo[$item][1].'.xml" />'."\n";
					$resitem .= '</resource>';
				}
				$res[] = $resitem;

			} else if ($iteminfo[$item][0]=='Assessment') {
				$stm = $DBH->prepare("SELECT name,summary,defpoints,itemorder,enddate,gbcategory,avail,startdate,ptsposs FROM imas_assessments WHERE id=:id");
				$stm->execute(array(':id'=>$iteminfo[$item][1]));
				$row = $stm->fetch(PDO::FETCH_NUM);
				if ($row[8]==-1) {
					require_once("../includes/updateptsposs.php");
					$row[8] = updatePointsPossible($iteminfo[$item][1], $row[3], $row[2]);
				}
				//echo "encoding {$row[0]} as ".htmlentities($row[0],ENT_XML1,'UTF-8',false).'<br/>';
				$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
				$out .= $ind.'  <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
				$out .= $ind.'</item>'."\n";
				if ($linktype=='canvas') {
					$canvout .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$canvout .= '<content_type>Assignment</content_type>'."\n";
					$canvout .= '<workflow_state>'.($row[6]==0?'unpublished':'active').'</workflow_state>'."\n";
					$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>'."\n";
					$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$canvout .= "<position>$ccnt</position> <indent>".max($mod_depth-1,0)."</indent> </item>";
					$ccnt++;
					$aitems = explode(',',$row[3]);
					foreach ($aitems as $k=>$v) {
						if (strpos($v,'~')!==FALSE) {
							$sub = explode('~',$v);
							if (strpos($sub[0],'|')===false) { //backwards compat
								$aitems[$k] = $sub[0];
								$aitemcnt[$k] = 1;

							} else {
								$grpparts = explode('|',$sub[0]);
								$aitems[$k] = $sub[1];
								$aitemcnt[$k] = $grpparts[0];
							}
						} else {
							$aitemcnt[$k] = 1;
						}
					}

					mkdir($newdir.'/assn'.$iteminfo[$item][1]);
					$fp = fopen($newdir.'/assn'.$iteminfo[$item][1].'/assignment_settings.xml','w');
					fwrite($fp,'<assignment xmlns="http://canvas.instructure.com/xsd/cccv1p0" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 http://canvas.instructure.com/xsd/cccv1p0.xsd">'."\n");
					fwrite($fp,'<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n");
					fwrite($fp,'<workflow_state>'.($row[6]==0?'unpublished':'published').'</workflow_state>'."\n");
					fwrite($fp,'<points_possible>'.$row[8].'</points_possible>'."\n");
					fwrite($fp,'<grading_type>points</grading_type>'."\n");
					if (isset($_POST['includeduedates']) && $row[4]<2000000000) {
						fwrite($fp,'<due_at>'.gmdate("Y-m-d\TH:i:s", $row[4]).'</due_at>'."\n");
					}
					if ($row[7] > 0 && isset($_POST['includestartdates'])) {
						fwrite($fp,'<unlock_at>'.gmdate("Y-m-d\TH:i:s", $row[7]).'</unlock_at>'."\n");
					}
					if (isset($_POST['includegbcats'])) {
						fwrite($fp,'<assignment_group_identifierref>GBCAT'.$row[5].'</assignment_group_identifierref>'."\n");
					}
					$usedcats[$row[5]]++;
					fwrite($fp,'<submission_types>external_tool</submission_types>'."\n");
					fwrite($fp,'<external_tool_url>'. $GLOBALS['basesiteurl'] . '/bltilaunch.php?custom_place_aid='.$iteminfo[$item][1].'</external_tool_url>'."\n");
					fwrite($fp,'</assignment>');
					fclose($fp);
					$fp = fopen($newdir.'/assn'.$iteminfo[$item][1].'/assignmenthtml'.$iteminfo[$item][1].'.html','w');
					fwrite($fp,'<html><body> </body></html>');
					fclose($fp);
					$resitem =  '<resource identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" href="assn'.$iteminfo[$item][1].'/assignmenthtml'.$iteminfo[$item][1].'.html">'."\n";
					$resitem .= '  <file href="assn'.$iteminfo[$item][1].'/assignmenthtml'.$iteminfo[$item][1].'.html" />'."\n";
					$resitem .= '  <file href="assn'.$iteminfo[$item][1].'/assignment_settings.xml" />'."\n";
					$resitem .= '</resource>';
					$res[] = $resitem;
				} else {
					$fp = fopen($newdir.'/blti'.$iteminfo[$item][1].'.xml','w');
					fwrite($fp,'<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm ="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp ="http://www.imsglobal.org/xsd/imslticp_v1p0">');
					fwrite($fp,'<blti:title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</blti:title>');
					fwrite($fp,'<blti:description>'.htmlentities(html_entity_decode($row[1]),ENT_XML1,'UTF-8',false).'</blti:description>');
					if ($linktype=='url') {
						$urladd = '?custom_place_aid='.$iteminfo[$item][1];
					} else {
						fwrite($fp,'<blti:custom><lticm:property name="place_aid">'.$iteminfo[$item][1].'</lticm:property></blti:custom>');
						$urladd = '';
					}
					if ($urlmode == 'https://') {
						fwrite($fp,'<blti:launch_url>https://' . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $imasroot . '/bltilaunch.php'.$urladd.'</blti:launch_url>');
						fwrite($fp,'<blti:secure_launch_url>https://' . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $imasroot . '/bltilaunch.php'.$urladd.'</blti:secure_launch_url>');
					} else {
						fwrite($fp,'<blti:launch_url>http://' . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $imasroot . '/bltilaunch.php'.$urladd.'</blti:launch_url>');
					}
					fwrite($fp,'<blti:vendor><lticp:code>IMathAS</lticp:code><lticp:name>'.$installname.'</lticp:name></blti:vendor>');
					fwrite($fp,'</cartridge_basiclti_link>');
					fclose($fp);
					$resitem =  '<resource identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="imsbasiclti_xmlv1p0">'."\n";
					$resitem .= '  <file href="blti'.$iteminfo[$item][1].'.xml" />'."\n";
					$resitem .= '</resource>';
					$res[] = $resitem;
				}
			} else if ($iteminfo[$item][0]=='Wiki') {
				$stm = $DBH->prepare("SELECT name,avail FROM imas_wikis WHERE id=:id");
				$stm->execute(array(':id'=>$iteminfo[$item][1]));
				$row = $stm->fetch(PDO::FETCH_NUM);
				$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
				$out .= $ind.'  <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
				$out .= $ind.'</item>'."\n";
				$canvout .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
				$canvout .= '<content_type>WikiPage</content_type>';
				$canvout .= '<workflow_state>'.($row[1]==0?'unpublished':'active').'</workflow_state>'."\n";
				$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
				$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
				$canvout .= "<position>$ccnt</position> <indent>".max($mod_depth-1,0)."</indent> </item>";
				$ccnt++;

				$fp = fopen($newdir.'/'.$htmldir.'wikitext'.$iteminfo[$item][1].'.html','w');
				fwrite($fp,'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">');
				fwrite($fp,'<title>'.htmlentities($row[0]).'</title>');
				fwrite($fp,'<meta name="identifier" content="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'"/>');
				if ($linktype=="canvas") {
					fwrite($fp,'<meta name="editing_roles" content="teachers"/>');
					fwrite($fp,'<meta name="workflow_state" content="'.($row[1]==0?'unpublished':'active').'"/>');
				}
				fwrite($fp,"</head><body>");
				$stm = $DBH->prepare("SELECT revision FROM imas_wiki_revisions WHERE wikiid=:wikiid AND stugroupid=0 ORDER BY id DESC LIMIT 1");
				$stm->execute(array(':wikiid'=>$iteminfo[$item][1]));
				if ($stm->rowCount()>0) {
					$row = $stm->fetch(PDO::FETCH_NUM);
					$text = $row[0];
					if (strlen($text)>6 && substr($text,0,6)=='**wver') {
						$wikiver = substr($text,6,strpos($text,'**',6)-6);
						$text = substr($text,strpos($text,'**',6)+2);
					}
					fwrite($fp,filtercapture($text,$res));
				}

				fwrite($fp,'</body></html>');
				fclose($fp);
				$resitem =  '<resource href="'.$htmldir.'wikitext'.$iteminfo[$item][1].'.html" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="webcontent">'."\n";
				$resitem .= '  <file href="'.$htmldir.'wikitext'.$iteminfo[$item][1].'.html" />'."\n";
				$resitem .= '</resource>';
				$res[] = $resitem;
			}
			if ($inmodule && $mod_depth>0) {
				$module_meta .= $canvout;
			} else {
				$toplevelitems .= $canvout;
			}
		}
	}
	return $out;
}
if ($linktype=='canvas') {
	$manifestres[] = '<resource identifier="coursesettings1" href="course_settings/canvas_export.txt" type="associatedcontent/imscc_xmlv1p1/learning-application-resource">
	      <file href="course_settings/canvas_export.txt"/>
	      <file href="course_settings/course_settings.xml"/>
	      <file href="course_settings/assignment_groups.xml"/>
	      <file href="course_settings/module_meta.xml"/>
	    </resource>';
}
$manifestorg = getorg($items,'0',$manifestres,'  ', 0);


if ($linktype=='canvas') {
	if ($toplevelitems != '') {
		$module_meta = '<module identifier="imported">
		<title>Imported Content</title>
		<workflow_state>active</workflow_state>
		<items>' . $toplevelitems . '</items></module>' . $module_meta;
	}
	if ($inmodule) {
		$module_meta .= '</items></module>';
	}
	$module_meta = '<?xml version="1.0" encoding="UTF-8"?>
	<modules xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 http://canvas.instructure.com/xsd/cccv1p0.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://canvas.instructure.com/xsd/cccv1p0">
	'.$module_meta . '</modules>';

	if (isset($_POST['includeappconfig']) && $_POST['includeappconfig']==1) {
		$fp = fopen($newdir.'/bltiimathas.xml','w');
		fwrite($fp,'<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm ="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp ="http://www.imsglobal.org/xsd/imslticp_v1p0">');
		fwrite($fp,'<blti:title>'.htmlentities($installname,ENT_XML1,'UTF-8',false).'</blti:title>');
		fwrite($fp,'<blti:description>Math Assessment</blti:description>');
		fwrite($fp,'<blti:vendor><lticp:code>IMathAS</lticp:code><lticp:name>'.$installname.'</lticp:name></blti:vendor>');
		fwrite($fp,'<blti:extensions platform="canvas.instructure.com">');
		fwrite($fp,' <lticm:property name="privacy_level">public</lticm:property>');
		fwrite($fp,' <lticm:property name="domain">'.Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']).'</lticm:property>');
		/*
		//Canvas-specific extension
		fwrite($fp,' <lticm:options name="resource_selection">
			<lticm:property name="url">' . $GLOBALS['basesiteurl'] . '/bltilaunch.php</lticm:property>
			<lticm:property name="text">Pick an Assessment</lticm:property>
			<lticm:property name="selection_width">500</lticm:property>
			<lticm:property name="selection_height">300</lticm:property>
		      </lticm:options>');
		*/
		//Deep Linking extension
		fwrite($fp,' <lticm:options name="assignment_selection">
			 <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
			 <lticm:property name="url">' . $GLOBALS['basesiteurl'] . '/bltilaunch.php?ltiseltype=assn</lticm:property>
		     <lticm:property name="selection_width">500</lticm:property>
		     <lticm:property name="selection_height">300</lticm:property>
		    </lticm:options>
		    <lticm:options name="link_selection">
			 <lticm:property name="message_type">ContentItemSelectionRequest</lticm:property>
			 <lticm:property name="url">' . $GLOBALS['basesiteurl'] . '/bltilaunch.php?ltiseltype=link</lticm:property>
			 <lticm:property name="selection_width">500</lticm:property>
		     <lticm:property name="selection_height">300</lticm:property>
		    </lticm:options>
		    <lticm:property name="session_setup_url">' . $GLOBALS['basesiteurl'] . '/ltisessionsetup.php</lticm:property>');
		fwrite($fp,'</blti:extensions>');
		fwrite($fp,'<blti:custom>');
		fwrite($fp,'  <lticm:property name="canvas_assignment_due_at">$Canvas.assignment.dueAt.iso8601</lticm:property>');
		fwrite($fp,'</blti:custom>');
		fwrite($fp,'</cartridge_basiclti_link>');
		fclose($fp);
		$resitem =  '<resource identifier="RESbltiimathas" type="imsbasiclti_xmlv1p0">'."\n";
		$resitem .= '  <file href="bltiimathas.xml" />'."\n";
		$resitem .= '</resource>';
		$manifestres[] = $resitem;
	}
	mkdir($newdir.'/non_cc_assessments');
	mkdir($newdir.'/course_settings');
	file_put_contents($newdir.'/course_settings/canvas_export.txt', "Q: Why do pandas prefer Cartesian coordinates? A: Because they're not polar bears");

	$fp = fopen($newdir.'/course_settings/assignment_groups.xml','w');
	fwrite($fp,'<?xml version="1.0" encoding="UTF-8"?>'."\n");
	fwrite($fp, '<assignmentGroups xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 http://canvas.instructure.com/xsd/cccv1p0.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://canvas.instructure.com/xsd/cccv1p0">'."\n");
	$pcnt = 1;
	if (isset($_POST['includegbcats'])) {
		foreach ($gbcats as $i=>$cat) {
			if ($usedcats[$i]==0) {continue;}
			fwrite($fp, ' <assignmentGroup identifier="GBCAT'.$i.'">'."\n");
			fwrite($fp, '  <title>'.htmlentities($cat['name'],ENT_XML1,'UTF-8',false).'</title>'."\n");
			fwrite($fp, '  <position>'.$pcnt.'</position>'."\n");
			$pcnt++;
			if ($useweights && $cat['weight']>-1) {
				fwrite($fp, '  <group_weight>'.number_format($cat['weight'],1).'</group_weight>'."\n");
			}
			if ($cat['dropn']>0) {
				fwrite($fp, '  <rules><rule><drop_type>drop_lowest</drop_type><drop_count>'.$cat['dropn'].'</drop_count></rule></rules>'."\n");
			}
			fwrite($fp, ' </assignmentGroup>'."\n");
		}
	}
	fwrite($fp,'</assignmentGroups>');
	fclose($fp);
	$fp = fopen($newdir.'/course_settings/module_meta.xml','w');
	fwrite($fp,$module_meta);
	fclose($fp);
	$fp = fopen($newdir.'/course_settings/course_settings.xml','w');
	fwrite($fp,'<?xml version="1.0" encoding="UTF-8"?>
<course xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 http://canvas.instructure.com/xsd/cccv1p0.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" identifier="coursesettings1" xmlns="http://canvas.instructure.com/xsd/cccv1p0">
<title>'.htmlentities($coursename,ENT_XML1,'UTF-8',false).'</title>'."\n");
	if ($useweights) {
		fwrite($fp, '<group_weighting_scheme>percent</group_weighting_scheme>'."\n");
	}
	fwrite($fp, '</course>');
	fclose($fp);

	if (dir_is_empty($newdir.'/web_resources')) {
		rmdir($newdir.'/web_resources');
	}
}

$fp = fopen($newdir.'/imsmanifest.xml','w');
fwrite($fp,'<?xml version="1.0" encoding="UTF-8" ?>'."\n");
fwrite($fp,'<manifest identifier="imathas'.$cid.'" xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1" xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource" xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest" >'."\n");
fwrite($fp,'<metadata>'."\n".'<schema>IMS Common Cartridge</schema>'."\n".'<schemaversion>1.1.0</schemaversion> '."\n");
fwrite($fp, '<lomimscc:lom>
      <lomimscc:general>
	<lomimscc:title>
	  <lomimscc:string language="en-US">'.htmlentities($coursename,ENT_XML1,'UTF-8',false).'</lomimscc:string>
	</lomimscc:title>
	<lomimscc:description>
	  <lomimscc:string language="en-US">'.htmlentities($coursename,ENT_XML1,'UTF-8',false).'</lomimscc:string>
	</lomimscc:description>
	<lomimscc:keyword>
	  <lomimscc:string language="en-US">IMathAS</lomimscc:string>
	</lomimscc:keyword>
      </lomimscc:general>
    </lomimscc:lom>'."\n".'</metadata>'."\n");
fwrite($fp,'<organizations>'."\n".' <organization identifier="O_1" structure="rooted-hierarchy">'."\n".' <item identifier="I_1">'."\n");
fwrite($fp,$manifestorg);
fwrite($fp, ' </item>'."\n".' </organization>'."\n".'</organizations>'."\n");
fwrite($fp,'<resources>'."\n");
foreach($manifestres as $r) {
	fwrite($fp,$r."\n");
}
fwrite($fp,'</resources>'."\n");
fwrite($fp,'</manifest>'."\n");
fclose($fp);

// increase script timeout value
ini_set('max_execution_time', 300);

// create object
$zip = new ZipArchive();

// open archive
if ($zip->open($path.'/CCEXPORT'.$cid.'.zip', ZIPARCHIVE::CREATE) !== TRUE) {
    die ("Could not open archive");
}

/*// initialize an iterator
// pass it the directory to be processed
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('../course/files/CCEXPORT'.$cid.'/'));

// iterate over the directory
// add each file found to the archive
foreach ($iterator as $key=>$value) {
	if (basename($key)=='.' || basename($key)=='..') { continue;}
	$zip->addFile(realpath($key), basename($key)) or die ("ERROR: Could not add file: $key");
}
*/
function addFolderToZip($dir, $zipArchive, $zipdir = ''){
    if (is_dir($dir)) {
	if ($dh = opendir($dir)) {

	    //Add the directory
	    if(!empty($zipdir)) $zipArchive->addEmptyDir($zipdir);

	    // Loop through all the files
	    while (($file = readdir($dh)) !== false) {

		//If it's a folder, run the function again!
		if(!is_file($dir . $file)){
		    // Skip parent and root directories
		    if( ($file !== ".") && ($file !== "..")){
			addFolderToZip($dir . $file . "/", $zipArchive, $zipdir . $file . "/");
		    }

		}else{
		    // Add the files
		    $zipArchive->addFile($dir . $file, $zipdir . $file);

		}
	    }
	}
    }
}
addFolderToZip($newdir.'/',$zip);

// close and save archive
$zip->close();
rename($path.'/CCEXPORT'.$cid.'.zip',$path.'/CCEXPORT'.$cid.'.imscc');
//echo "Archive created successfully.";

function rrmdir($path) {
  if (is_file($path) || is_link($path)) {
    unlink($path);
  }
  elseif (is_dir($path)) {
    if ($d = opendir($path)) {
      while (($entry = readdir($d)) !== false) {
	if ($entry == '.' || $entry == '..') continue;
	$entry_path = $path .DIRECTORY_SEPARATOR. $entry;
	rrmdir($entry_path);
      }
      closedir($d);
    }
    rmdir($path);
  }
 }

rrmdir($newdir);
$archive_file_name = 'CCEXPORT'.$cid.'.imscc';
//echo "<br/><a href=\"$imasroot/course/files/CCEXPORT$cid.imscc\">Download</a><br/>";
//echo "Once downloaded, keep things clean and <a href=\"ccexport.php?cid=$cid&delete=true\">Delete</a> the export file off the server.";
header("Content-type: application/vnd.ims.imsccv1p1");
header("Content-Disposition: attachment; filename=$archive_file_name");
header("Content-length: " . filesize($path.'/'.$archive_file_name));
header("Pragma: no-cache");
header("Expires: 0");
readfile($path.'/'.$archive_file_name);
unlink($path.'/'.$archive_file_name);
