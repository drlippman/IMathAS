<?php
//Generate Blackboard native cartridge
//IMathAS (c) 2018 David Lippman

if (!isset($myrights)) {
	echo 'This file cannot be called directly';
	exit;
}

error_reporting(0);
$loadmathfilter = 1;
$loadgraphfilter = 1;
require_once("../includes/filehandler.php");
require_once("../filter/filter.php");
require("bbexport-templates.php");
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

function leftpad($str) {
	return str_pad($str, 5, '0', STR_PAD_LEFT);
}
function xmlstr($str) {
	//remove newlines
	$str = str_replace(array(">\r\n<", ">\n<", "\r\n", "\n"), array('><','><',' ',' '), $str);
	return htmlentities($str,ENT_QUOTES|ENT_XML1,'UTF-8',false);
}
function bbdate($time) {
	return date("Y-m-d H:i:s T", $time);
}
function createbbitem($resid, $parentid, $template, $title, $rep, $handler, &$res) {
	global $newdir, $bbhandlers, $bbtemplates;

	$item = $bbtemplates[$template];
	if (!isset($rep['{{avail}}'])) {
		$rep['{{avail}}'] = 'true';
	}
	foreach ($rep as $k=>$v) {
		$item = str_replace($k,$v,$item);
	}
	if (isset($bbhandlers[$handler]) && !empty($bbhandlers[$handler][0])) {
		$item = str_replace('{{handler}}', $bbhandlers[$handler][0], $item);
		$item = str_replace('{{rendertype}}', $bbhandlers[$handler][1], $item);
		$item = str_replace('{{bodytype}}', isset($bbhandlers[$handler][3])?$bbhandlers[$handler][3]:'H', $item);
	}
	$item = str_replace('{{parentid}}', $parentid, $item);

	//empty-string out any remaining fields
	$item = preg_replace('/{{\w+}}/', '', $item);

	file_put_contents($newdir.'/'.$resid.'.dat',$item);
	$res[] = '<resource bb:file="'.$resid.'.dat" bb:title="'.xmlstr($title).'" identifier="'.$resid.'" type="'.$bbhandlers[$handler][2].'" xml:base="'.$resid.'"/>';
}

function replaceintemplate($template, $rep) {
	foreach ($rep as $k=>$v) {
		$template = str_replace($k,$v,$template);
	}
	return $template;
}
$filecnt = 0;
function addbbfile($courseid, $url, $fileaction, &$files, &$crs) {
	global $newdir,$bbtemplates,$filecnt;
	$filename = basename(parse_url($url, PHP_URL_PATH));
	$pathinfo = pathinfo($filename);
	$basename = Sanitize::simpleString($pathinfo['filename']);
	$extension = Sanitize::simpleString($pathinfo['extension']);
	$bbnow = bbdate(time());
	if ($basename != '') {
		$fileitemid = '_10'.leftpad($filecnt).'_1';
		$fileid = '_2'.leftpad($filecnt).'_1';
		//copy file
		if (!is_dir($newdir.'/csfiles')) {
			mkdir($newdir.'/csfiles');
		}
		if (!is_dir($newdir.'/csfiles/home_dir')) {
			mkdir($newdir.'/csfiles/home_dir');
		}
		copy($url, $newdir.'/csfiles/home_dir/'.$basename.'__xid-'.$fileid.'.'.$extension);
		$filesize = filesize($newdir.'/csfiles/home_dir/'.$basename.'__xid-'.$fileid.'.'.$extension);

		//add to files array for inclusion in basicitem
		$files[] = replaceintemplate($bbtemplates['file'], array(
			'{{fileitemid}}' => $fileitemid,
			'{{fileid}}' => $fileid,
			'{{size}}' => $filesize,
			'{{fileaction}}' => $fileaction,
			'{{filename}}' => xmlstr($filename),
			'{{created}}' => $bbnow,
			'{{updated}}' => $bbnow));

		//create file .xml
		$thisfilexml = replaceintemplate($bbtemplates['filexml'], array(
			'{{fileid}}' => $fileid,
			'{{filename}}' => xmlstr($filename)));
		file_put_contents($newdir.'/csfiles/home_dir/'.$basename.'__xid-'.$fileid.'.'.$extension.'.xml', $thisfilexml);

		//create crslink item
		$crs[] = replaceintemplate($bbtemplates['crslink'], array(
			'{{courseid}}' => $courseid,
			'{{fileitemid}}' => $fileitemid,
			'{{fileid}}' => $fileid,
			'{{filename}}' => xmlstr($filename),
			'{{id}}' => '_3'.leftpad($filecnt).'_1' ));

		$filecnt++;
	}
}

//make $mathimgurl absolute if not already
if (substr($mathimgurl,0,4)!='http' && isset($GLOBALS['basesiteurl'])) {
	$mathimgurl = substr($GLOBALS['basesiteurl'],0,-1*strlen($imasroot)). $mathimgurl;
}
function filtercapture($str) {
	$str = forcefiltermath($str);
	$str = forcefiltergraphnofile($str);
	return $str;
}
function forcefiltergraphnofile($str) {
	if (strpos($str,'embed')!==FALSE) {
		$str = preg_replace_callback('/<\s*embed.*?sscr=(.)(.+?)\1.*?>/','svgfiltersscrcallbacknofile',$str);
		$str = preg_replace_callback('/<\s*embed.*?script=(.)(.+?)\1.*?>/','svgfilterscriptcallbacknofile',$str);
	}
	return $str;
}
function svgfiltersscrcallbacknofile($arr) {
	if (trim($arr[2])=='') {return $arr[0];}

	if (strpos($arr[0],'style')!==FALSE) {
		$sty = preg_replace('/.*style\s*=\s*(.)(.+?)\1.*/',"$2",$arr[0]);
	} else {
		$sty = "vertical-align: middle;";
	}
	return ('<img src="'.$GLOBALS['basesiteurl'].'/filter/graph/svgimg.php?sscr='.Sanitize::encodeUrlParam($arr[2]).'" style="'.$sty.'" alt="Graphs"/>');
}
function svgfilterscriptcallbacknofile($arr) {
	if (trim($arr[2])=='') {return $arr[0];}

	$w = preg_replace('/.*\bwidth\s*=\s*.?(\d+).*/',"$1",$arr[0]);
	$h = preg_replace('/.*\bheight\s*=\s*.?(\d+).*/',"$1",$arr[0]);

	if (strpos($arr[0],'style')!==FALSE) {
		$sty = preg_replace('/.*style\s*=\s*(.)(.+?)\1.*/',"$2",$arr[0]);
	} else {
		$sty = "vertical-align: middle;";
	}
	return ('<img src="'.$GLOBALS['basesiteurl'].'/filter/graph/svgimg.php?script='.Sanitize::encodeUrlParam($arr[2]).'" style="'.$sty.'" alt="Graphs"/>');
}

$usechecked = ($_POST['whichitems']=='select');
if ($usechecked) {
	$checked = $_POST['checked'];
} else {
	$checked = array();
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

$newdir = $path . '/BBEXPORT'.$cid;
mkdir($newdir);

$manifestorg = '';
$manifestres = array();
$gbitems = array();
$junk = array();
$crslinks = array();

$imgcnt = 1;
$htmldir = '';
$filedir = '';

file_put_contents($newdir.'/.bb-package-info',$bbtemplates['bbinfo']);

$bbnow = bbdate(time());
$courseexportid = '_4'.$cid.'_1';

//write initial folder stuff
createbbitem('res00001', '', 'toc', $coursename, array(
	'{{id}}' => '_5'.$cid.'_1',
	'{{label}}' => xmlstr($coursename)
	), 'toc', $manifestres);

$initialblockid = '_600000_1';
createbbitem('res00002', '', 'toctop', '--TOP--', array(
	'{{id}}' => $initialblockid,
	'{{title}}' => '--TOP--',
	'{{created}}' => $bbnow
	), 'folder', $manifestres);

createbbitem('res00003', '', 'conf', 'Conferences', array(
	'{{id}}' => 'conf1',
	'{{coursename}}' => xmlstr($coursename),
	'{{created}}' => $bbnow,
	), 'conf', $manifestres);

$datcnt = 4;

$ccnt = 1;
$module_meta = '';

$toplevelitems = '';
$inmodule = false;

function getorg($it,$parent,&$res,$ind, $parentid) {
	global $DBH,$iteminfo,$newdir,$installname,$urlmode,$urlmode,$imasroot,$ccnt,$module_meta,$htmldir,$filedir, $toplevelitems, $inmodule;
	global $usechecked,$checked,$usedcats;
	global $datcnt, $newdir, $datcnt, $bbnow;
	global $gbitems, $crslinks, $courseexportid, $bbtemplates;

	$out = '';

	foreach ($it as $k=>$item) {
		if (is_array($item)) {
			if (!$usechecked || array_search($parent.'-'.($k+1),$checked)!==FALSE) {
				$resid = 'res'.leftpad($datcnt);
				$datcnt++;
				$blockid = '_6'.leftpad($item['id']).'_1';
				createbbitem($resid, $parentid, 'toctop', $item['name'], array(
					'{{id}}' => $blockid,
					'{{title}}' =>  xmlstr($item['name']),
					'{{parentid}}' => $parentid,
					'{{avail}}' => $item['avail']==0?'false':'true',
					'{{created}}' => $bbnow
					), 'folder', $res);

				$out .= $ind.'<item identifier="BLOCK'.$item['id'].'" identifierref="'.$resid.'">'."\n";
				$out .= $ind.'  <title>'.xmlstr($item['name']).'</title>'."\n";
				$out .= $ind.getorg($item['items'],$parent.'-'.($k+1),$res,$ind.'  ', $blockid);
				$out .= $ind.'</item>'."\n";


			} else {
				$out .= $ind.getorg($item['items'],$parent.'-'.($k+1),$res,$ind.'  ', $parentid);
			}

		} else {
			if ($usechecked && array_search($item,$checked)===FALSE) {
				continue;
			}
			$resid = 'res'.leftpad($datcnt);
			$datcnt++;
			if ($iteminfo[$item][0]=='InlineText') {
				$filesout = array();
				$stm = $DBH->prepare("SELECT title,text,fileorder,avail FROM imas_inlinetext WHERE id=:id");
				$stm->execute(array(':id'=>$iteminfo[$item][1]));
				$row = $stm->fetch(PDO::FETCH_NUM);
				if ($row[2]!='') {
					$files = explode(',',$row[2]);
					$stm = $DBH->prepare("SELECT id,description,filename FROM imas_instr_files WHERE itemid=:itemid");
					$stm->execute(array(':itemid'=>$iteminfo[$item][1]));
					while ($r = $stm->fetch(PDO::FETCH_NUM)) {
						//do files as weblinks rather than including the file itself
						if (substr($r[2],0,4)=='http') {
							//do nothing
						} else {
							$r[2] = getcoursefileurl($r[2], true);
						}
						addbbfile($courseexportid, $r[2], 'LINK', $filesout, $crslinks);
						//$filesout[$r[0]] = array($r[1],$r[2]);
					}
				}
				$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="'.$resid.'">'."\n";
				$out .= $ind.'  <title>'.xmlstr($row[0]).'</title>'."\n";
				$out .= $ind.'</item>'."\n";

				$text = $row[1];
				/*if ($row[2]!='') {
					$text .= '<ul>';
					foreach ($files as $f) {
						$text .= '<li><a href="'.$filesout[$f][1].'">'.htmlentities($filesout[$f][0]).'</a></li>';
					}
					$text .= '</ul>';
				}*/

				createbbitem($resid, $parentid, 'basicitem', $row[0], array(
					'{{id}}' => '_7'.$item.'_1',
					'{{title}}' => xmlstr($row[0]),
					'{{summary}}' => xmlstr(filtercapture($text)),
					'{{created}}' => $bbnow,
					'{{avail}}' => $row[3]==0?'false':'true',
					'{{newwindow}}' => "false",
					'{{files}}' => implode("\n", $filesout)
					), 'text', $res);

			} else if ($iteminfo[$item][0]=='LinkedText') {
				$stm = $DBH->prepare("SELECT title,text,summary,avail FROM imas_linkedtext WHERE id=:id");
				$stm->execute(array(':id'=>$iteminfo[$item][1]));
				$row = $stm->fetch(PDO::FETCH_NUM);

				$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="'.$resid.'">'."\n";
				$out .= $ind.'  <title>'.xmlstr($row[0]).'</title>'."\n";
				$out .= $ind.'</item>'."\n";

				//do files as weblinks rather than including the file itself
				if (substr(strip_tags($row[1]),0,5)=="file:") {
					if ($row[2]=='<p></p>') {
						$row[2] = '';
					}
					$filesout = array();
					$coursefileurl = getcoursefileurl(trim(substr(strip_tags($row[1]),5)), true);
					addbbfile($courseexportid, $coursefileurl, 'EMBED', $filesout, $crslinks);
					//$row[1] = getcoursefileurl(trim(substr(strip_tags($row[1]),5)), true);
					createbbitem($resid, $parentid, 'basicitem', $row[0], array(
						'{{id}}' => '_7'.$item.'_1',
						'{{title}}' => xmlstr($row[0]),
						'{{summary}}' => xmlstr(filtercapture($row[2])),
						'{{created}}' => $bbnow,
						'{{avail}}' => $row[3]==0?'false':'true',
						'{{newwindow}}' => "true",
						'{{files}}' => $filesout[0]
						), 'file', $res);
				} else if ((substr($row[1],0,4)=="http") && (strpos(trim($row[1])," ")===false)) { //is a web link
					$alink = trim($row[1]);
					createbbitem($resid, $parentid, 'basicitem', $row[0], array(
						'{{id}}' => '_7'.$item.'_1',
						'{{title}}' => xmlstr($row[0]),
						'{{summary}}' => xmlstr(filtercapture($row[2])),
						'{{created}}' => $bbnow,
						'{{avail}}' => $row[3]==0?'false':'true',
						'{{launchurl}}' => $alink,
						'{{newwindow}}' => "true",
						), 'link', $res);
				} else { //is text
					createbbitem($resid, $parentid, 'basicitem', $row[0], array(
						'{{id}}' => '_7'.$item.'_1',
						'{{title}}' => xmlstr($row[0]),
						'{{summary}}' => xmlstr(filtercapture($row[1])),
						'{{created}}' => $bbnow,
						'{{avail}}' => $row[3]==0?'false':'true',
						'{{newwindow}}' => "false"
						), 'page', $res);
				}
			} else if ($iteminfo[$item][0]=='Forum') {
				$stm = $DBH->prepare("SELECT name,description,avail FROM imas_forums WHERE id=:id");
				$stm->execute(array(':id'=>$iteminfo[$item][1]));
				$row = $stm->fetch(PDO::FETCH_NUM);

				$forumresid = $resid;
				createbbitem($forumresid, $parentid, 'forum', $row[0], array(
						'{{id}}' => '_8'.$item.'_1',
						'{{conferenceid}}' => 'conf1',
						'{{title}}' => xmlstr($row[0]),
						'{{summary}}' => xmlstr(filtercapture($row[1])),
						'{{avail}}' => $row[2]==0?'false':'true',
						'{{created}}' => $bbnow
						), 'forum', $res);

				$resid = 'res'.leftpad($datcnt);
				$datcnt++;

				$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="'.$resid.'">'."\n";
				$out .= $ind.'  <title>'.xmlstr($row[0]).'</title>'."\n";
				$out .= $ind.'</item>'."\n";

				createbbitem($resid, $parentid, 'basicitem', $row[0], array(
						'{{id}}' => '_7'.$item.'_1',
						'{{title}}' => xmlstr($row[0]),
						'{{summary}}' => xmlstr(filtercapture($row[1])),
						'{{created}}' => $bbnow,
						'{{avail}}' => $row[2]==0?'false':'true',
						'{{newwindow}}' => "false"
						), 'forumitem', $res);

				$forumlinkresid = 'res'.leftpad($datcnt);
				$datcnt++;

				createbbitem($forumlinkresid, $parentid, 'forumlink', '', array(
						'{{id}}' => '_9'.$item.'_1',
						'{{itemdat}}' => $resid,
						'{{avail}}' => $row[2]==0?'false':'true',
						'{{forumdat}}' => $forumresid
						), 'forumlink', $res);

			} else if ($iteminfo[$item][0]=='Assessment') {
				$stm = $DBH->prepare("SELECT name,summary,defpoints,itemorder,enddate,gbcategory,avail,startdate,ptsposs FROM imas_assessments WHERE id=:id");
				$stm->execute(array(':id'=>$iteminfo[$item][1]));
				$row = $stm->fetch(PDO::FETCH_NUM);
				if ($row[8]==-1) {
					require_once("../includes/updateptsposs.php");
					$row[8] = updatePointsPossible($iteminfo[$item][1], $row[3], $row[2]);
				}

				$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="'.$resid.'">'."\n";
				$out .= $ind.'  <title>'.xmlstr($row[0]).'</title>'."\n";
				$out .= $ind.'</item>'."\n";

				$extended = '<ENTRY key="customParameters"/>';
				$extended .= '<ENTRY key="alternateUrl">http://'.Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $imasroot . '/bltilaunch.php?custom_place_aid='.$iteminfo[$item][1].'</ENTRY>';
				$extended .= '<ENTRY key="vendorInfo">name='.$installname.'&amp;code=IMathAS</ENTRY>';

				createbbitem($resid, $parentid, 'basicitem', $row[0], array(
						'{{id}}' => '_7'.$item.'_1',
						'{{title}}' => xmlstr($row[0]),
						'{{summary}}' => xmlstr(filtercapture($row[1])),
						'{{created}}' => $bbnow,
						'{{avail}}' => $row[6]==0?'false':'true',
						'{{newwindow}}' => "false",
						'{{launchurl}}' => $urlmode.Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $imasroot . '/bltilaunch.php?custom_place_aid='.$iteminfo[$item][1],
						'{{extendeddata}}' => $extended
						), 'lti', $res);

				$gbitem = $bbtemplates['outcomedef'];
				$gbitem = str_replace('{{defid}}', '_11'.$item.'_1', $gbitem);
				$gbitem = str_replace('{{resid}}', $resid, $gbitem);
				$includeduedate = ($_POST['includeduedates']==1 && $row[4]<2000000000);
				$gbitem = str_replace('{{duedate}}', $includeduedate?bbdate($row[4]):'', $gbitem);
				$gbitem = str_replace('{{title}}', xmlstr($row[0]), $gbitem);
				$gbitem = str_replace('{{ptsposs}}', $row[8], $gbitem);
				$gbitems[] = $gbitem;

			} else if ($iteminfo[$item][0]=='Wiki') {
				$stm = $DBH->prepare("SELECT name,avail FROM imas_wikis WHERE id=:id");
				$stm->execute(array(':id'=>$iteminfo[$item][1]));
				$row = $stm->fetch(PDO::FETCH_NUM);

				$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="'.$resid.'">'."\n";
				$out .= $ind.'  <title>'.xmlstr($row[0]).'</title>'."\n";
				$out .= $ind.'</item>'."\n";

				$stm = $DBH->prepare("SELECT revision FROM imas_wiki_revisions WHERE wikiid=:wikiid AND stugroupid=0 ORDER BY id DESC LIMIT 1");
				$stm->execute(array(':wikiid'=>$iteminfo[$item][1]));
				if ($stm->rowCount()>0) {
					$text = $stm->fetchColumn(0);
					if (strlen($text)>6 && substr($text,0,6)=='**wver') {
						$wikiver = substr($text,6,strpos($text,'**',6)-6);
						$text = substr($text,strpos($text,'**',6)+2);
					}
				}

				createbbitem($resid, $parentid, 'basicitem', $row[0], array(
						'{{id}}' => '_7'.$item.'_1',
						'{{title}}' => xmlstr($row[0]),
						'{{summary}}' => xmlstr(filtercapture($text)),
						'{{created}}' => $bbnow,
						'{{avail}}' => $row[1]==0?'false':'true',
						'{{newwindow}}' => "false"
						), 'page', $res);

			}
		}
	}
	return $out;
}

$manifestorg = getorg($items,'0',$manifestres,'  ', $initialblockid);

if (count($gbitems)>0) {
	$resid = 'res'.leftpad($datcnt);
	$datcnt++;
	createbbitem($resid, $parentid, 'gb', 'Gradebook', array(
		'{{outcomedefs}}' => implode("\n", $gbitems)
		), 'gb', $manifestres);
}

if (count($crslinks)>0) {
	$resid = 'res'.leftpad($datcnt);
	$datcnt++;
	file_put_contents($newdir.'/'.$resid.'.dat', str_replace('{{linkset}}',implode("\n", $crslinks), $bbtemplates['crs']));
	$manifestres[] = '<resource bb:file="'.$resid.'.dat" bb:title="CSResourceLinks" identifier="'.$resid.'" type="course/x-bb-csresourcelinks" xml:base="'.$resid.'"/>';
}

$manifest = str_replace('{{items}}', $manifestorg, $bbtemplates['imsmanifest']);
$manifest = str_replace('{{coursename}}', xmlstr($coursename), $manifest);
$manifest = str_replace('{{resources}}', implode("\n",$manifestres), $manifest);
file_put_contents($newdir.'/imsmanifest.xml', $manifest);


// increase script timeout value
ini_set('max_execution_time', 300);

if (file_exists($path.'/BBEXPORT'.$cid.'.zip')) {
	unlink($path.'/BBEXPORT'.$cid.'.zip');
}
// create object
$zip = new ZipArchive();

// open archive
if ($zip->open($path.'/BBEXPORT'.$cid.'.zip', ZIPARCHIVE::CREATE) !== TRUE) {
    die ("Could not open archive");
}


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

$archive_file_name = 'BBEXPORT'.$cid.'.zip';
header("Content-type: application/zip");
header("Content-Disposition: attachment; filename=$archive_file_name");
header("Content-length: " . filesize($path.'/'.$archive_file_name));
header("Pragma: no-cache");
header("Expires: 0");
readfile($path.'/'.$archive_file_name);
unlink($path.'/'.$archive_file_name);
