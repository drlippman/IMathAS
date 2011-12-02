<?php
//IMathAS: Common Catridge v1.1 Export
//(c) 2011 David Lippman

require("../validate.php");

$cid = intval($_GET['cid']);
if (!isset($teacherid)) {
	exit;
}

$path = realpath("../course/files");

if (isset($_GET['delete'])) {
	unlink($path.'/CCEXPORT'.$cid.'.imscc');
	echo "file deleted";
	exit;
}

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

function getorg($it,$parent,&$res) {
	global $iteminfo,$newdir,$installname;
	$out = '';
	foreach ($it as $k=>$item) {
		if (is_array($item)) {
			$out .= '<item identifier="BLOCK'.$item['id'].'">'."\n";
			$out .= '  <title>'.htmlentities($item['name']).'</title>'."\n";
			$out .= getorg($item['items'],$parent.'-'.($k+1),$res);
			$out .= '</item>'."\n";
		} else {
			if ($iteminfo[$item][0]=='InlineText') {
				$query = "SELECT title,text FROM imas_inlinetext WHERE id='{$iteminfo[$item][1]}'";
				$r = mysql_query($query) or die("Query failed : " . mysql_error());
				$row = mysql_fetch_row($r);
				$out .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
				$out .= '  <title>'.htmlentities($row[0]).'</title>'."\n";
				$out .= '</item>';
				$fp = fopen($newdir.'/inlinetext'.$iteminfo[$item][1].'.html','w');
				fwrite($fp,$row[1]);
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
				 	fwrite($fp,' <title xmlns="">'.htmlentities($row[0]).'</title>');
				 	fwrite($fp,' <url href="'.$alink.'" target="_blank" xmlns="" />');
				 	fwrite($fp,'</webLink>');
				 	fclose($fp);
				 	$out .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= '  <title>'.htmlentities($row[0]).'</title>'."\n";
					$out .= '</item>';
				 	$resitem =  '<resource identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="imswl_xmlv1p1">'."\n";
					$resitem .= '  <file href="weblink'.$iteminfo[$item][1].'.xml" />'."\n";
					$resitem .= '</resource>';
					$res[] = $resitem;
				} else if (substr(strip_tags($row[1]),0,5)=="file:") {  //is a file
					$filename = trim(substr(strip_tags($row[1]),5));
					copy("../course/files/$filename",$newdir.'/'.$filename);
					$out .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= '  <title>'.htmlentities($row[0]).'</title>'."\n";
					$out .= '</item>';
					
					$resitem =  '<resource href="'.$filename.'" identifier="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'" type="webcontent">'."\n";
					$resitem .= '  <file href="'.$filename.'" />'."\n";
					$resitem .= '</resource>';
					$res[] = $resitem;
				} else { //is text
					$out .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
					$out .= '  <title>'.htmlentities($row[0]).'</title>'."\n";
					$out .= '</item>';
					$fp = fopen($newdir.'/linkedtext'.$iteminfo[$item][1].'.html','w');
					fwrite($fp,$row[1]);
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
				$out .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
				$out .= '  <title>'.htmlentities($row[0]).'</title>'."\n";
				$out .= '</item>';
				$fp = fopen($newdir.'/forum'.$iteminfo[$item][1].'.xml','w');
				fwrite($fp,'<topic xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imsdt_v1p1">');
				fwrite($fp,' <title >'.htmlentities($row[0]).'</title>');
				fwrite($fp,' <text texttype="text/html">'.htmlentities($row[1]).'</text>');
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
				$out .= '<item identifier="'.$iteminfo[$item][0].$iteminfo[$item][1].'" identifierref="RES'.$iteminfo[$item][0].$iteminfo[$item][1].'">'."\n";
				$out .= '  <title>'.htmlentities($row[0]).'</title>'."\n";
				$out .= '</item>';
				$fp = fopen($newdir.'/blti'.$iteminfo[$item][1].'.xml','w');
				fwrite($fp,'<cartridge_basiclti_link xmlns="http://www.imsglobal.org/xsd/imslticc_v1p0" xmlns:blti="http://www.imsglobal.org/xsd/imsbasiclti_v1p0" xmlns:lticm ="http://www.imsglobal.org/xsd/imslticm_v1p0" xmlns:lticp ="http://www.imsglobal.org/xsd/imslticp_v1p0">');
				fwrite($fp,'<blti:title>'.htmlentities($row[0]).'</blti:title>');
				fwrite($fp,'<blti:description>'.htmlentities($row[1]).'</blti:description>');
				fwrite($fp,'<blti:custom><lticm:property name="place_aid">'.$iteminfo[$item][1].'</lticm:property></blti:custom>');
				fwrite($fp,'<blti:launch_url>http://' . $_SERVER['HTTP_HOST'] . $imasroot . '/bltilaunch.php</blti:launch_url>');
				fwrite($fp,'<blti:secure_launch_url>https://' . $_SERVER['HTTP_HOST'] . $imasroot . '/bltilaunch.php</blti:secure_launch_url>');
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

$manifestorg = getorg($items,'0',$manifestres);

$fp = fopen($newdir.'/imsmanifest.xml','w');
fwrite($fp,'<manifest identifier="imathas'.$cid.'" xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1" xmlns:lom="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/resource" xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest" >'."\n");
fwrite($fp,'<metadata>    <schema>IMS Common Cartridge</schema>    <schemaversion>1.1.0</schemaversion> ');
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
    </lomimscc:lom></metadata>'."\n");
fwrite($fp,'<organizations>'."\n".' <organization identifier="O_1" structure="rooted-hierarchy">'."\n".' <item identifier="I_1">'."\n");
fwrite($fp,$manifestorg);
fwrite($fp, '</item></organization>  </organizations>');
fwrite($fp,'<resources>');
foreach($manifestres as $r) {
	fwrite($fp,$r."\n");
}
fwrite($fp,'</resources>');
fwrite($fp,'</manifest>');
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
echo "<a href=\"ccexport.php?cid=$cid&delete=true\">Delete</a>";

?>
