<?php
//IMathAS: Common Catridge v1.1 Export
//(c) 2011 David Lippman

require("../validate.php");

$cid = intval($_GET['cid']);
if (!isset($teacherid)) {
	echo 'You must be a teacher to access this page';
	exit;
}

$pagetitle = "CC Export";
$loadmathfilter = 1;
$loadgraphfilter = 1;
if (!defined('ENT_XML1')) {
	define('ENT_XML1',ENT_QUOTES);
}
$placeinhead = '<script type="text/javascript">
 function updatewhichsel(el) {
   if (el.value=="select") { $("#itemselectwrap").show();}
   else {$("#itemselectwrap").hide()};
 }
 </script>';

require("../header.php");
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Common Cartridge Export</div>\n";

echo '<div class="cpmid">';
if (!isset($CFG['GEN']['noimathasexportfornonadmins']) || $myrights>=75) {
	echo '<a href="exportitems.php?cid='.$cid.'">Export for another IMathAS system or as a backup for this system</a> | ';
}
echo '<a href="jsonexport.php?cid='. $cid.'" name="button">Export OEA JSON</a>';
echo '</div>';

$path = realpath("../course/files");

if (isset($_GET['delete'])) {
	unlink($path.'/CCEXPORT'.$cid.'.imscc');
	echo "export file deleted";
} else if (isset($_GET['create'])) {
	require("../includes/filehandler.php");
	$usechecked = ($_POST['whichitems']=='select');
	if ($usechecked) {
		$checked = $_POST['checked'];
	} else {
		$checked = array();
	}


	$linktype = $_POST['type'];
	$iteminfo = array();
	$query = "SELECT id,itemtype,typeid FROM imas_items WHERE courseid=$cid";
	$r = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($r)) {
		$iteminfo[$row[0]] = array($row[1],$row[2]);
	}

	$query = "SELECT itemorder FROM imas_courses WHERE id=$cid";
	$r = mysql_query($query) or die("Query failed : " . mysql_error());
	$items = unserialize(mysql_result($r,0,0));

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
	if ($linktype=='canvas') {
		mkdir($newdir.'/wiki_content');
		mkdir($newdir.'/web_resources');
		$htmldir = 'wiki_content/';
		$filedir = 'web_resources/';
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
			$str = str_replace($mathimgurl,'http://'. $_SERVER['HTTP_HOST']. $mathimgurl, $str);
		}
		return $str;
	}

	$ccnt = 1;
	$module_meta = '';

	$toplevelitems = '';
	$inmodule = false;

	function getorg($it,$parent,&$res,$ind) {
		global $iteminfo,$newdir,$installname,$urlmode,$linktype,$urlmode,$imasroot,$ccnt,$module_meta,$htmldir,$filedir, $toplevelitems, $inmodule;
		global $usechecked,$checked;

		$out = '';

		foreach ($it as $k=>$item) {
			$canvout = '';
			if (is_array($item)) {
				if (!$usechecked || array_search($parent.'-'.($k+1),$checked)!==FALSE) {
					if (strlen($ind)>2) {
						$canvout .= '<item identifier="BLOCK'.$item['id'].'">'."\n";
						$canvout .= '<content_type>ContextModuleSubHeader</content_type>';
						$canvout .= '<title>'.htmlentities($item['name'],ENT_XML1,'UTF-8',false).'</title>'."\n";
						$canvout .= "<position>$ccnt</position> <indent>".max(strlen($ind)/2 - 2, 0)."</indent> </item>";
						$ccnt++;
						$module_meta .= $canvout;
					} else {
						if ($inmodule) {
							$module_meta .= '</items></module>';
						}
						$inmodule = true;
						$module_meta .= '<module identifier="BLOCK'.$item['id'].'">
							<title>'.htmlentities($item['name'],ENT_XML1,'UTF-8',false).'</title>
							<items>';
						}
					$out .= $ind.'<item identifier="BLOCK'.$item['id'].'">'."\n";
					$out .= $ind.'  <title>'.htmlentities($item['name'],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$out .= $ind.getorg($item['items'],$parent.'-'.($k+1),$res,$ind.'  ');
					$out .= $ind.'</item>'."\n";
				} else {
					$out .= $ind.getorg($item['items'],$parent.'-'.($k+1),$res,$ind.'  ');
				}

			} else {
				if ($usechecked && array_search($item,$checked)===FALSE) {
					continue;
				}
				if ($iteminfo[$item][0]=='InlineText') {
					$query = "SELECT title,text,fileorder FROM imas_inlinetext WHERE id='{$iteminfo[$item][1]}'";
					$r = mysql_query($query) or die("Query failed : " . mysql_error());
					$row = mysql_fetch_row($r);
					if ($row[2]!='') {
						$files = explode(',',$row[2]);
						$query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='{$iteminfo[$item][1]}'";
						$result = mysql_query($query) or die("Query failed : " . mysql_error());
						$filesout = array();
						while ($r = mysql_fetch_row($result)) {
							//if s3 filehandler, do files as weblinks rather than including the file itself
							if ($GLOBALS['filehandertypecfiles'] == 's3') {
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
					$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
					$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$canvout .= "<position>$ccnt</position> <indent>".max(strlen($ind)/2 - 2, 0)."</indent> </item>";
					$ccnt++;

					$fp = fopen($newdir.'/'.$htmldir.'inlinetext'.$iteminfo[$item][1].'.html','w');
					fwrite($fp,'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">');
					fwrite($fp,'<title>'.htmlentities($row[0]).'</title>');
					fwrite($fp,'<meta name="identifier" content="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'"/>');
					if ($linktype=="canvas") {
						fwrite($fp,'<meta name="editing_roles" content="teachers"/>');
					}
					fwrite($fp,"</head><body>");
					fwrite($fp,filtercapture($row[1],$res));
					if ($row[2]!='') {
						fwrite($fp,'<ul>');
						foreach ($files as $f) {
							if ($GLOBALS['filehandertypecfiles'] == 's3') {
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
					$query = "SELECT title,text,summary FROM imas_linkedtext WHERE id='{$iteminfo[$item][1]}'";
					$r = mysql_query($query) or die("Query failed : " . mysql_error());
					$row = mysql_fetch_row($r);

					//if s3 filehandler, do files as weblinks rather than including the file itself
					if ($GLOBALS['filehandertypecfiles'] == 's3' && substr(strip_tags($row[1]),0,5)=="file:") {
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
						$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
						$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
						$canvout .= '<url>'.htmlentities($alink,ENT_XML1,'UTF-8',false).'</url>';
						$canvout .= "<position>$ccnt</position> <indent>".max(strlen($ind)/2 - 2, 0)."</indent> </item>";
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
						$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
						$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
						$canvout .= "<position>$ccnt</position> <indent>".max(strlen($ind)/2 - 2, 0)."</indent> </item>";
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
						$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
						$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
						$canvout .= "<position>$ccnt</position> <indent>".max(strlen($ind)/2 - 2, 0)."</indent> </item>";
						$ccnt++;
						$fp = fopen($newdir.'/'.$htmldir.'linkedtext'.$iteminfo[$item][1].'.html','w');
						fwrite($fp,'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">');
						fwrite($fp,'<title>'.htmlentities($row[0]).'</title>');
						fwrite($fp,'<meta name="identifier" content="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'"/>');
						if ($linktype=="canvas") {
							fwrite($fp,'<meta name="editing_roles" content="teachers"/>');
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
					$query = "SELECT name,description FROM imas_forums WHERE id='{$iteminfo[$item][1]}'";
					$r = mysql_query($query) or die("Query failed : " . mysql_error());
					$row = mysql_fetch_row($r);
					$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= $ind.'  <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$out .= $ind.'</item>'."\n";
					$canvout .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$canvout .= '<content_type>DiscussionTopic</content_type>';
					$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
					$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$canvout .= "<position>$ccnt</position> <indent>".max(strlen($ind)/2 - 2, 0)."</indent> </item>";
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
					$query = "SELECT name,summary,defpoints,itemorder FROM imas_assessments WHERE id='{$iteminfo[$item][1]}'";
					$r = mysql_query($query) or die("Query failed : " . mysql_error());
					$row = mysql_fetch_row($r);
					//echo "encoding {$row[0]} as ".htmlentities($row[0],ENT_XML1,'UTF-8',false).'<br/>';
					$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= $ind.'  <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$out .= $ind.'</item>'."\n";
					if ($linktype=='canvas') {
						$canvout .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
						$canvout .= '<content_type>Assignment</content_type>';
						$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
						$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
						$canvout .= "<position>$ccnt</position> <indent>".max(strlen($ind)/2 - 2, 0)."</indent> </item>";
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
						$query = "SELECT points,id FROM imas_questions WHERE assessmentid='{$iteminfo[$item][1]}'";
						$result2 = mysql_query($query) or die("Query failed : $query: " . mysql_error());
						$totalpossible = 0;
						while ($r = mysql_fetch_row($result2)) {
							if (($k = array_search($r[1],$aitems))!==false) { //only use first item from grouped questions for total pts
								if ($r[0]==9999) {
									$totalpossible += $aitemcnt[$k]*$row[2]; //use defpoints
								} else {
									$totalpossible += $aitemcnt[$k]*$r[0]; //use points from question
								}
							}
						}
						mkdir($newdir.'/assn'.$iteminfo[$item][1]);
						$fp = fopen($newdir.'/assn'.$iteminfo[$item][1].'/assignment_settings.xml','w');
						fwrite($fp,'<assignment xmlns="http://canvas.instructure.com/xsd/cccv1p0" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 http://canvas.instructure.com/xsd/cccv1p0.xsd">');
						fwrite($fp,'<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>');
						fwrite($fp,'<points_possible>'.$totalpossible.'</points_possible>');
						fwrite($fp,'<grading_type>points</grading_type>');
						fwrite($fp,'<assignment_group_identifierref>assngroup</assignment_group_identifierref>');
						fwrite($fp,'<submission_types>external_tool</submission_types>');
						fwrite($fp,'<external_tool_url>'. $urlmode . $_SERVER['HTTP_HOST'] . $imasroot . '/bltilaunch.php?custom_place_aid='.$iteminfo[$item][1].'</external_tool_url>');
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
						fwrite($fp,'<blti:launch_url>http://' . $_SERVER['HTTP_HOST'] . $imasroot . '/bltilaunch.php'.$urladd.'</blti:launch_url>');
						if ($urlmode == 'https://') {fwrite($fp,'<blti:secure_launch_url>https://' . $_SERVER['HTTP_HOST'] . $imasroot . '/bltilaunch.php'.$urladd.'</blti:secure_launch_url>');}
						fwrite($fp,'<blti:vendor><lticp:code>IMathAS</lticp:code><lticp:name>'.$installname.'</lticp:name></blti:vendor>');
						fwrite($fp,'</cartridge_basiclti_link>');
						fclose($fp);
						$resitem =  '<resource identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="imsbasiclti_xmlv1p0">'."\n";
						$resitem .= '  <file href="blti'.$iteminfo[$item][1].'.xml" />'."\n";
						$resitem .= '</resource>';
						$res[] = $resitem;
					}
				} else if ($iteminfo[$item][0]=='Wiki') {
					$query = "SELECT name FROM imas_wikis WHERE id='{$iteminfo[$item][1]}'";
					$r = mysql_query($query) or die("Query failed : " . mysql_error());
					$row = mysql_fetch_row($r);
					$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= $ind.'  <title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$out .= $ind.'</item>'."\n";
					$canvout .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$canvout .= '<content_type>WikiPage</content_type>';
					$canvout .= '<identifierref>RES'.$iteminfo[$item][0].$iteminfo[$item][1].'</identifierref>';
					$canvout .= '<title>'.htmlentities($row[0],ENT_XML1,'UTF-8',false).'</title>'."\n";
					$canvout .= "<position>$ccnt</position> <indent>".max(strlen($ind)/2 - 2, 0)."</indent> </item>";
					$ccnt++;

					$fp = fopen($newdir.'/'.$htmldir.'wikitext'.$iteminfo[$item][1].'.html','w');
					fwrite($fp,'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">');
					fwrite($fp,'<title>'.htmlentities($row[0]).'</title>');
					fwrite($fp,'<meta name="identifier" content="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'"/>');
					if ($linktype=="canvas") {
						fwrite($fp,'<meta name="editing_roles" content="teachers"/>');
					}
					fwrite($fp,"</head><body>");

					$query = "SELECT revision FROM imas_wiki_revisions WHERE wikiid='{$iteminfo[$item][1]}' AND stugroupid=0 ORDER BY id DESC LIMIT 1";
					$r = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($r)>0) {
						$row = mysql_fetch_row($r);
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
				if (strlen($ind)>2) {
					$module_meta .= $canvout;
				} else {
					$toplevelitems .= $canvout;
				}
			}
		}
		return $out;
	}
	if ($linktype=='canvas') {
		$manifestres[] = '<resource identifier="coursesettings1" href="course_settings/syllabus.html" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" intendeduse="syllabus">
		      <file href="course_settings/syllabus.html"/>
		      <file href="course_settings/course_settings.xml"/>
		      <file href="course_settings/assignment_groups.xml"/>
		      <file href="course_settings/module_meta.xml"/>
		    </resource>';
    	}
	$manifestorg = getorg($items,'0',$manifestres,'  ');


	if ($linktype=='canvas') {
		if ($toplevelitems != '') {
			$module_meta = '<module identifier="imported">
			<title>Imported Content</title>
			<items>' . $toplevelitems . '</items></module>' . $module_meta;
		}
		$module_meta = '<?xml version="1.0" encoding="UTF-8"?>
		<modules xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 http://canvas.instructure.com/xsd/cccv1p0.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://canvas.instructure.com/xsd/cccv1p0">
		'.$module_meta . '</items>  </module> </modules>';

		$fp = fopen($newdir.'/bltiimathas.xml','w');
		fwrite($fp,'<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm ="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp ="http://www.imsglobal.org/xsd/imslticp_v1p0">');
		fwrite($fp,'<blti:title>'.htmlentities($installname,ENT_XML1,'UTF-8',false).'</blti:title>');
		fwrite($fp,'<blti:description>Math Assessment</blti:description>');
		fwrite($fp,'<blti:vendor><lticp:code>IMathAS</lticp:code><lticp:name>'.$installname.'</lticp:name></blti:vendor>');
		fwrite($fp,'<blti:extensions platform="canvas.instructure.com">');
		fwrite($fp,' <lticm:property name="privacy_level">public</lticm:property>');
		fwrite($fp,' <lticm:property name="domain">'.$_SERVER['HTTP_HOST'].'</lticm:property>');
		fwrite($fp,' <lticm:options name="resource_selection">
			<lticm:property name="url">'.$urlmode.$_SERVER['HTTP_HOST'] . $imasroot . '/bltilaunch.php</lticm:property>
			<lticm:property name="text">Pick an Assessment</lticm:property>
			<lticm:property name="selection_width">500</lticm:property>
			<lticm:property name="selection_height">300</lticm:property>
		      </lticm:options>');
		fwrite($fp,'</blti:extensions>');
		fwrite($fp,'</cartridge_basiclti_link>');
		fclose($fp);
		$resitem =  '<resource identifier="RESbltiimathas" type="imsbasiclti_xmlv1p0">'."\n";
		$resitem .= '  <file href="bltiimathas.xml" />'."\n";
		$resitem .= '</resource>';
		$manifestres[] = $resitem;
		mkdir($newdir.'/non_cc_assessments');
    		mkdir($newdir.'/course_settings');
    		$fp = fopen($newdir.'/course_settings/syllabus.html','w');
    		fwrite($fp,'<html><body> </body></html>');
    		fclose($fp);
    		$fp = fopen($newdir.'/course_settings/assignment_groups.xml','w');
    		fwrite($fp,'<?xml version="1.0" encoding="UTF-8"?>
			<assignmentGroups xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 http://canvas.instructure.com/xsd/cccv1p0.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://canvas.instructure.com/xsd/cccv1p0">
			  <assignmentGroup identifier="assngroup">
			    <title>Assignments</title>
			  </assignmentGroup>
			</assignmentGroups>');
		fclose($fp);
		$fp = fopen($newdir.'/course_settings/module_meta.xml','w');
		fwrite($fp,$module_meta);
		fclose($fp);
		$fp = fopen($newdir.'/course_settings/course_settings.xml','w');
		fwrite($fp,'<?xml version="1.0" encoding="UTF-8"?>
<course xsi:schemaLocation="http://canvas.instructure.com/xsd/cccv1p0 http://canvas.instructure.com/xsd/cccv1p0.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" identifier="coursesettings1" xmlns="http://canvas.instructure.com/xsd/cccv1p0">
  <title>imp test</title>
</course>
');
		fclose($fp);
	}

	$fp = fopen($newdir.'/imsmanifest.xml','w');
	fwrite($fp,'<?xml version="1.0" encoding="UTF-8" ?>'."\n");
	fwrite($fp,'<manifest identifier="imathas'.$cid.'" xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1" xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource" xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest" >'."\n");
	fwrite($fp,'<metadata>'."\n".'<schema>IMS Common Cartridge</schema>'."\n".'<schemaversion>1.1.0</schemaversion> '."\n");
	fwrite($fp, '<lomimscc:lom>
	      <lomimscc:general>
		<lomimscc:title>
		  <lomimscc:string language="en-US">Common Cartridge export of '.$cid.' from '.$installname.'</lomimscc:string>
		</lomimscc:title>
		<lomimscc:description>
		  <lomimscc:string language="en-US">Common Cartridge export of '.$cid.' from '.$installname.'</lomimscc:string>
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
	echo "Archive created successfully.";

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

	echo "<br/><a href=\"$imasroot/course/files/CCEXPORT$cid.imscc\">Download</a><br/>";
	echo "Once downloaded, keep things clean and <a href=\"ccexport.php?cid=$cid&delete=true\">Delete</a> the export file off the server.";
} else {

	function getsubinfo($items,$parent,$pre) {
		global $ids,$types,$names;
		foreach($items as $k=>$item) {
			if (is_array($item)) {
				$ids[] = $parent.'-'.($k+1);
				$types[] = $pre."Block";
				$names[] = stripslashes($item['name']);
				getsubinfo($item['items'],$parent.'-'.($k+1),$pre.'--');
			} else {
				$ids[] = $item;
				$arr = getiteminfo($item);
				$types[] = $pre.$arr[0];
				$names[] = $arr[1];
			}
		}
	}
	function getiteminfo($itemid) {
		$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error() . " queryString: " . $query);
		$itemtype = mysql_result($result,0,0);
		$typeid = mysql_result($result,0,1);
		switch($itemtype) {
			case ($itemtype==="InlineText"):
				$query = "SELECT title FROM imas_inlinetext WHERE id=$typeid";
				break;
			case ($itemtype==="LinkedText"):
				$query = "SELECT title FROM imas_linkedtext WHERE id=$typeid";
				break;
			case ($itemtype==="Forum"):
				$query = "SELECT name FROM imas_forums WHERE id=$typeid";
				break;
			case ($itemtype==="Assessment"):
				$query = "SELECT name FROM imas_assessments WHERE id=$typeid";
				break;
		}
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$name = mysql_result($result,0,0);
		return array($itemtype,$name);
	}

	$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());

	$items = unserialize(mysql_result($result,0,0));
	$ids = array();
	$types = array();
	$names = array();

	getsubinfo($items,'0','');


	echo '<h2>Common Cartridge Export</h2>';
	echo '<p>This feature will allow you to export a v1.1 compliant IMS Common Cartridge export of your course, which can ';
	echo 'then be loaded into other Learning Management Systems that support this standard.  Inline text, web links, ';
	echo 'course files, and forums will all transfer reasonably well, but be aware that any math exported will call back to this server for display.</p>';
	echo '<p>Since LMSs cannot support the type of assessment that this system ';
	echo 'does, assessments are exported as LTI (learning tools interoperability) placements back to this system.  Not all LMSs ';
	echo 'support this standard yet, so your assessments may not transfer.  If they do, you will need to set up the LTI tool on your LMS ';
	echo 'to work with this system by supplying an LTI key and secret.  If this system and your LMS have domain credentials set up, you may not have to do ';
	echo 'anything.  Otherwise, you can use the LTI secret you set in your course settings, along with the key placein_###_0 (if you want students ';
	echo 'to create an account on this system) or placein_###_1 (if you want students to only be able to log in through the LMS), where ### is ';
	echo 'replaced with your course key.  <b>Important:</b> The key form placein_###_1 is necessary if you want grades from '.$installname.' to be ';
	echo 'reported back to the LMS automatically.  ';
	echo 'If you do not see the LTI key setting in your course settings, then your system administrator does ';
	echo 'not have LTI enabled on your system, and you cannot use this feature.</p>';
	if ($enablebasiclti==false) {
		echo '<p style="color:red">Note: Your system does not currenltly have LTI enabled.  Contact your system administrator</p>';
	}
	echo '<form id="qform" method="post" action="ccexport.php?cid='.$cid.'&create=true">';
	?>
	<input type="hidden" name="whichitems" value="select"/>
	<p>Items to export: <select name="whichitems" onchange="updatewhichsel(this)">
		<option value="all">Export entire course</option>
		<option value="select">Select individual items to export</option>
		</select>
		<div id="itemselectwrap" style="display:none;">

		Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>

		<table cellpadding=5 class=gb>
		<thead>
			<tr><th></th><th>Type</th><th>Title</th></tr>
		</thead>
		<tbody>
<?php
	$alt=0;
	for ($i = 0 ; $i<(count($ids)); $i++) {
		if ($alt==0) {echo "			<tr class=even>"; $alt=1;} else {echo "			<tr class=odd>"; $alt=0;}
?>
				<td>
				<input type=checkbox name='checked[]' value='<?php echo $ids[$i] ?>'>
				</td>
				<td><?php echo $types[$i] ?></td>
				<td><?php echo $names[$i] ?></td>
			</tr>
<?php
	}
?>
		</tbody>
		</table>
	</div>
	<?php
	//echo "<p><button type=\"submit\" name=\"type\" value=\"custom\">Create CC Export with LTI placements as custom fields (works in BlackBoard)</button></p>";
	echo "<p><button type=\"submit\" name=\"type\" value=\"url\">Create CC Export with LTI placements in URLs (works in BlackBoard and Moodle)</button></p>";
	echo "<p><button type=\"submit\" name=\"type\" value=\"canvas\">Create CC+custom Export (works in Canvas)</button></p>";
	echo '</form>';

}
require("../footer.php");

?>
