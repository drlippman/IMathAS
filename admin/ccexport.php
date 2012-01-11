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
	
require("../header.php");
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Common Cartridge Export</div>\n";
	
$path = realpath("../course/files");

if (isset($_GET['delete'])) {
	unlink($path.'/CCEXPORT'.$cid.'.imscc');
	echo "export file deleted";
} else if (isset($_GET['create'])) {

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
	function filtercapture($str,&$res) {
		global $newdir,$imgcnt,$imasroot,$addmathabs,$mathimgurl;
		$str = forcefiltermath($str);
		$str = forcefiltergraph($str);
		$graphfiles = getgraphfilenames($str);
		foreach ($graphfiles as $f) {
			copy("../filter/graph/imgs/$f",$newdir.'/'.$f);
			$resitem =  '<resource href="'.$f.'" identifier="RESwebcontentImage'.$imgcnt.'" type="webcontent">'."\n";
			$resitem .= '  <file href="'.$f.'" />'."\n";
			$resitem .= '</resource>';
			$res[] = $resitem;
			$imgcnt++;
		}
		$str = str_replace($imasroot.'/filter/graph/imgs/','',$str);
		if ($addmathabs) {
			$str = str_replace($mathimgurl,'http://'. $_SERVER['HTTP_HOST']. $mathimgurl, $str);
		}
		return $str;
	}
	
	function getorg($it,$parent,&$res,$ind) {
		global $iteminfo,$newdir,$installname,$urlmode;
		$out = '';
		foreach ($it as $k=>$item) {
			if (is_array($item)) {
				$out .= $ind.'<item identifier="BLOCK'.$item['id'].'">'."\n";
				$out .= $ind.'  <title>'.htmlentities($item['name']).'</title>'."\n";
				$out .= $ind.getorg($item['items'],$parent.'-'.($k+1),$res,$ind.'  ');
				$out .= $ind.'</item>'."\n";
			} else {
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
							copy("../course/files/{$r[2]}",$newdir.'/'.$r[2]);
							$resitem =  '<resource href="'.$r[2].'" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'file'.$r[0].'" type="webcontent">'."\n";
							$resitem .= '  <file href="'.$r[2].'" />'."\n";
							$resitem .= '</resource>';
							$res[] = $resitem;
							$filesout[$r[0]] = array($r[1],$r[2]);
						}
					}
					$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= $ind.'  <title>'.htmlentities($row[0]).'</title>'."\n";
					$out .= $ind.'</item>'."\n";
					$fp = fopen($newdir.'/inlinetext'.$iteminfo[$item][1].'.html','w');
					fwrite($fp,filtercapture($row[1],$res));
					if ($row[2]!='') {
						fwrite($fp,'<ul>');
						foreach ($files as $f) {
							fwrite($fp,'<li><a href="'.$filesout[$f][1].'">'.htmlentities($filesout[$f][0]).'</a></li>');
						}
						fwrite($fp,'</ul>');
					}
					fclose($fp);
					$resitem =  '<resource href="inlinetext'.$iteminfo[$item][1].'.html" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="webcontent">'."\n";
					$resitem .= '  <file href="inlinetext'.$iteminfo[$item][1].'.html" />'."\n";
					$resitem .= '</resource>';
					$res[] = $resitem;
				} else if ($iteminfo[$item][0]=='LinkedText') {
					$query = "SELECT title,text,summary FROM imas_linkedtext WHERE id='{$iteminfo[$item][1]}'";
					$r = mysql_query($query) or die("Query failed : " . mysql_error());
					$row = mysql_fetch_row($r);
					if ((substr($row[1],0,4)=="http") && (strpos(trim($row[1])," ")===false)) { //is a web link
						$alink = trim($row[1]);
						$fp = fopen($newdir.'/weblink'.$iteminfo[$item][1].'.xml','w');
						fwrite($fp,'<webLink xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imswl_v1p1">');
						fwrite($fp,' <title>'.htmlentities($row[0]).'</title>');
						fwrite($fp,' <url href="'.$alink.'" target="_blank"/>');
						fwrite($fp,'</webLink>');
						fclose($fp);
						$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
						$out .= $ind.'  <title>'.htmlentities($row[0]).'</title>'."\n";
						$out .= $ind.'</item>'."\n";
						$resitem =  '<resource identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="imswl_xmlv1p1">'."\n";
						$resitem .= '  <file href="weblink'.$iteminfo[$item][1].'.xml" />'."\n";
						$resitem .= '</resource>';
						$res[] = $resitem;
					} else if (substr(strip_tags($row[1]),0,5)=="file:") {  //is a file
						$filename = trim(substr(strip_tags($row[1]),5));
						copy("../course/files/$filename",$newdir.'/'.$filename);
						$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
						$out .= $ind.'  <title>'.htmlentities($row[0]).'</title>'."\n";
						$out .= $ind.'</item>'."\n";
						
						$resitem =  '<resource href="'.$filename.'" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="webcontent">'."\n";
						$resitem .= '  <file href="'.$filename.'" />'."\n";
						$resitem .= '</resource>';
						$res[] = $resitem;
					} else { //is text
						$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
						$out .= $ind.'  <title>'.htmlentities($row[0]).'</title>'."\n";
						$out .= $ind.'</item>'."\n";
						$fp = fopen($newdir.'/linkedtext'.$iteminfo[$item][1].'.html','w');
						fwrite($fp,filtercapture($row[1],$res));
						fclose($fp);
						$resitem =  '<resource href="linkedtext'.$iteminfo[$item][1].'.html" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="webcontent">'."\n";
						$resitem .= '  <file href="linkedtext'.$iteminfo[$item][1].'.html" />'."\n";
						$resitem .= '</resource>';
						$res[] = $resitem;
					}
				} else if ($iteminfo[$item][0]=='Forum') {
					$query = "SELECT name,description FROM imas_forums WHERE id='{$iteminfo[$item][1]}'";
					$r = mysql_query($query) or die("Query failed : " . mysql_error());
					$row = mysql_fetch_row($r);
					$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= $ind.'  <title>'.htmlentities($row[0]).'</title>'."\n";
					$out .= $ind.'</item>'."\n";
					$fp = fopen($newdir.'/forum'.$iteminfo[$item][1].'.xml','w');
					fwrite($fp,'<topic xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imsdt_v1p1">');
					fwrite($fp,' <title >'.htmlentities($row[0]).'</title>');
					fwrite($fp,' <text texttype="text/html">'.htmlentities(filtercapture($row[1],$res)).'</text>');
					fwrite($fp,'</topic>');
					fclose($fp);
					$resitem =  '<resource identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="imsdt_xmlv1p1">'."\n";
					$resitem .= '  <file href="forum'.$iteminfo[$item][1].'.xml" />'."\n";
					$resitem .= '</resource>';
					$res[] = $resitem;
					
				} else if ($iteminfo[$item][0]=='Assessment') {
					$query = "SELECT name,summary FROM imas_assessments WHERE id='{$iteminfo[$item][1]}'";
					$r = mysql_query($query) or die("Query failed : " . mysql_error());
					$row = mysql_fetch_row($r);
					$out .= $ind.'<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= $ind.'  <title>'.htmlentities($row[0]).'</title>'."\n";
					$out .= $ind.'</item>'."\n";
					$fp = fopen($newdir.'/blti'.$iteminfo[$item][1].'.xml','w');
					fwrite($fp,'<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm ="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp ="http://www.imsglobal.org/xsd/imslticp_v1p0">');
					fwrite($fp,'<blti:title>'.htmlentities($row[0]).'</blti:title>');
					fwrite($fp,'<blti:description>'.htmlentities($row[1]).'</blti:description>');
					fwrite($fp,'<blti:custom><lticm:property name="place_aid">'.$iteminfo[$item][1].'</lticm:property></blti:custom>');
					fwrite($fp,'<blti:launch_url>http://' . $_SERVER['HTTP_HOST'] . $imasroot . '/bltilaunch.php</blti:launch_url>');
					if ($urlmode == 'https://') {fwrite($fp,'<blti:secure_launch_url>https://' . $_SERVER['HTTP_HOST'] . $imasroot . '/bltilaunch.php</blti:secure_launch_url>');}
					fwrite($fp,'<blti:vendor><lticp:code>IMathAS</lticp:code><lticp:name>'.$installname.'</lticp:name></blti:vendor>');
					fwrite($fp,'</cartridge_basiclti_link>');
					fclose($fp);
					$resitem =  '<resource identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="imsbasiclti_xmlv1p0">'."\n";
					$resitem .= '  <file href="blti'.$iteminfo[$item][1].'.xml" />'."\n";
					$resitem .= '</resource>';
					$res[] = $resitem;
				}
				
			}
		}
		return $out;
	}
	
	$manifestorg = getorg($items,'0',$manifestres,'  ');
	
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
	
	// initialize an iterator
	// pass it the directory to be processed
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('../course/files/CCEXPORT'.$cid.'/'));
	
	// iterate over the directory
	// add each file found to the archive
	foreach ($iterator as $key=>$value) {
		if (basename($key)=='.' || basename($key)=='..') { continue;}
		$zip->addFile(realpath($key), basename($key)) or die ("ERROR: Could not add file: $key");        
	}
	
	// close and save archive
	$zip->close();
	rename($path.'/CCEXPORT'.$cid.'.zip',$path.'/CCEXPORT'.$cid.'.imscc');
	echo "Archive created successfully.";    
	
	function rrmdir($dir) {
	   if (is_dir($dir)) {
	     $objects = scandir($dir);
	     foreach ($objects as $object) {
	       if ($object != "." && $object != "..") {
		 if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
	       }
	     }
	     reset($objects);
	     rmdir($dir);
	   }
	} 
	rrmdir($newdir);
	
	echo "<br/><a href=\"$imasroot/course/files/CCEXPORT$cid.imscc\">Download</a><br/>";
	echo "Once downloaded, keep things clean and <a href=\"ccexport.php?cid=$cid&delete=true\">Delete</a> the export file off the server.";
} else {
	echo '<h2>Common Cartridge Export</h2>';
	echo '<p>This feature will allow you to export a v1.1 compliant IMS Common Cartridge export of your course, which can ';
	echo 'then be loaded into other Learning Management Systems that support this standard.  Inline text, web links, ';
	echo 'course files, and forums will all transfer reasonable well, but be aware that any math exported will call back to this server for display.</p>';
	echo '<p>Since LMSs cannot support the type of assessment that this system ';
	echo 'does, assessments are exported as LTI (learning tools interoperability) placements back to this system.  Not all LMSs ';
	echo 'support this standard yet, so your assessments may not transfer.  If they do, you will need to set up the LTI tool on your LMS ';
	echo 'to work with this system by supplying an LTI key and secret.  If this system and your LMS have domain credentials set up, you may not have to do ';
	echo 'anything.  Otherwise, you can use the LTI key you set in your course settings, along with the key cid_###_0 (if you want students ';
	echo 'to create an account on this system) or cid_###_1 (if you want students to only be able to log in through the LMS), where ### is ';
	echo 'replaced with your course key.  If you do not see the LTI key setting in your course settings, then your system administrator does ';
	echo 'not have LTI enabled on your system, and you cannot use this feature.</p>';
	echo "<p><a href=\"ccexport.php?cid=$cid&create=true\">Create CC Export</a></p>";
}
require("../footer.php");

?>
