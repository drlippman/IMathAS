<?php
//IMathAS:  JSON revision history for Wiki page
//(c) 2010 David Lippman


/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");
require("../includes/diff.php");
					

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";

$cid = intval($_GET['cid']);
$id = intval($_GET['id']);
$groupid = intval($_GET['grp']);

if ($cid==0) {
	$overwriteBody=1;
	$body = "Error - need course id";
} else if ($id==0) {
	$overwriteBody=1;
	$body = "Error - need wiki id";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$query = "SELECT name,startdate,enddate,editbydate,avail FROM imas_wikis WHERE id='$id'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$row = mysql_fetch_row($result);
	$wikiname = $row[0];
	$now = time();
	if (!isset($teacherid) && ($row[4]==0 || ($row[4]==1 && ($now<$row[1] || $now>$row[2])))) {
		$overwriteBody=1;
		$body = "Error - not available for viewing";
	} else {
		require_once("../filter/filter.php");

		if (isset($teacherid) || $now<$row[3]) {
			$canedit = true;
		} else {
			$canedit = false;
		}
		$query = "SELECT i_w_r.id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName,i_u.id FROM ";
		$query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
		$query .= "WHERE i_w_r.wikiid='$id' AND i_w_r.stugroupid='$groupid' ORDER BY i_w_r.id DESC";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$numrevisions = mysql_num_rows($result);
		
		$row = mysql_fetch_row($result);
		$text = $row[1];
		if (strlen($text)>6 && substr($text,0,6)=='**wver') {
			$wikiver = substr($text,6,strpos($text,'**',6)-6);
			$text = substr($text,strpos($text,'**',6)+2);
		} else {
			$wikiver = 1;
		}
		$lastedittime = tzdate("F j, Y, g:i a",$row[2]);
		$lasteditedby = $row[3].', '.$row[4];
		$revisionusers = array();
		$revisionusers[$row[5]] =  $row[3].', '.$row[4];
		$revisionhistory = array(array('u'=>$row[5], 't'=>$lastedittime,'id'=>$row[0]));
		//$revisionhistory = '[{u:'.$row[5].',t:"'.$lastedittime.'",id:'.$row[0].'}';
		
		if ($numrevisions>1) {
			$i = 0;
			while ($row = mysql_fetch_row($result)) {
				$revisionusers[$row[5]] =  $row[3].', '.$row[4];
				//$row[1] = filter(str_replace('"','@^@^@',$row[1]));
				//$row[1] = str_replace('"','\\"',$row[1]);
				//$row[1] = str_replace('@^@^@','"',$row[1]);
				//$revisionhistory .= ',{u:'.$row[5].',c:'.$row[1].',t:"'.tzdate("F j, Y, g:i a",$row[2]).'",id:'.$row[0].'}';
				if (function_exists('json_encode')) {
					$row[1]=  json_decode($row[1]);
				} else {
					require_once("../includes/JSON.php");
					$jsonser = new Services_JSON();
					$row[1] = $jsonser->decode($row[1]);
				}
				$revisionhistory[] = array('u'=>$row[5],'c'=>$row[1],'t'=>tzdate("F j, Y, g:i a",$row[2]),'id'=>$row[0]);
				$i++;
			}
			//$revisionhistory .= ']';
			$keys = array_keys($revisionusers);
			$i = 0;
			//$users = '{';
			$users = array();
			foreach ($keys as $uid) {
				//if ($i>0) { $users .= ',';}
				//$users .= $uid.':"'.str_replace(array('\\','"',), array('\\\\','\"'), $revisionusers[$uid]).'"';
				$users[$uid] = $revisionusers[$uid];
				$i++;
			}
			//$users .= '}';
		} else {
			$users = array(); //'{}';
			$revisionhistory = array(); //'[]';
		}
		 $text = diffstringsplit($text);
		 foreach ($text as $k=>$v) {
			 $text[$k] = filter($v);//str_replace(array("\n","\r",'"'),array('','','\\"'),filter($v));
		 }
		 //$original = '["'.implode('","',$text).'"]';
	}
}

if ($overwriteBody==1) {
	echo $body;
} else {  // general JSON 
	$out = array('o'=>$text,'h'=>$revisionhistory,'u'=>$users);
	if (function_exists('json_encode')) {
		echo json_encode($out);
	} else {
		require_once("../includes/JSON.php");
		$jsonser = new Services_JSON();
		echo $jsonser->encode($out);
	}
	//echo '{"o":'.$original.',"h":'.$revisionhistory.',"u":'.$users.'}';
}
?>
