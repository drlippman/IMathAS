<?php
//IMathAS:  JSON revision history for Wiki page
//(c) 2010 David Lippman


/*** master php includes *******/
require_once "../init.php";
require_once "../includes/htmlutil.php";
require_once "../includes/diff.php";
require_once '../includes/stugroups.php';


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";

$cid = intval($_GET['cid']);
$id = intval($_GET['id']);
$groupid = intval($_GET['grp'] ?? 0);

if ($cid==0) {
	$overwriteBody=1;
	$body = "Error - need course id";
} else if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	$overwriteBody=1;
	$body = "You must be enrolled in the course";
} else if ($id==0) {
	$overwriteBody=1;
	$body = "Error - need wiki id";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$stm = $DBH->prepare("SELECT name,startdate,enddate,editbydate,avail,groupsetid FROM imas_wikis WHERE id=:id AND courseid=:cid");
	$stm->execute(array(':id'=>$id, ':cid'=>$cid));
	$row = $stm->fetch(PDO::FETCH_ASSOC);
	if ($row === false) {
		echo 'Invalid wiki id';
		exit;
	}
	$wikiname = $row['name'];
	$now = time();
	if (!isset($teacherid) && ($row['avail']==0 || ($row['avail']==1 && ($now<$row['startdate'] || $now>$row['enddate'])))) {
		$overwriteBody=1;
		$body = "Error - not available for viewing";
	} else {
		require_once "../filter/filter.php";

		if (isset($teacherid) || $now<$row['editbydate']) {
			$canedit = true;
		} else {
			$canedit = false;
		}
		if ($row['groupsetid']>0) {
			if (isset($teacherid)) {
				checkGroupIDinCourse($groupid,$cid);
			} else {
				$stm = $DBH->prepare("SELECT id FROM imas_stugroupmembers WHERE stugroupid=? AND userid=?");
				$stm->execute([$groupid, $userid]);
				if ($stm->fetchColumn(0)===false) {
					echo 'Invalid groupid';
					exit;
				}
			}
		} else {
			$groupid = 0;
		}
		$query = "SELECT i_w_r.id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName,i_u.id AS userid FROM ";
		$query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
		$query .= "WHERE i_w_r.wikiid=:wikiid AND i_w_r.stugroupid=:stugroupid ORDER BY i_w_r.id DESC";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':wikiid'=>$id, ':stugroupid'=>$groupid));
		$numrevisions = $stm->rowCount();
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		$text = $row['revision'];
		if (strlen($text)>6 && substr($text,0,6)=='**wver') {
			$wikiver = substr($text,6,strpos($text,'**',6)-6);
			$text = substr($text,strpos($text,'**',6)+2);
		} else {
			$wikiver = 1;
		}
		$lastedittime = tzdate("F j, Y, g:i a",$row['time']);
		$lasteditedby = $row['LastName'].', '.$row['FirstName'];
		$revisionusers = array();
		$revisionusers[$row['userid']] =  $lasteditedby;
		$revisionhistory = array(array('u'=>$row['userid'], 't'=>$lastedittime,'id'=>$row['id']));
		//$revisionhistory = '[{u:'.$row[5].',t:"'.$lastedittime.'",id:'.$row[0].'}';

		if ($numrevisions>1) {
			$i = 0;
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				$revisionusers[$row['userid']] =  $row['LastName'].', '.$row['FirstName'];
				$row['revision']=  json_decode($row['revision']);
				$revisionhistory[] = array('u'=>$row['userid'],'c'=>$row['revision'],'t'=>tzdate("F j, Y, g:i a",$row['time']),'id'=>$row['id']);
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
	echo json_encode($out, JSON_INVALID_UTF8_IGNORE);
	//echo '{"o":'.$original.',"h":'.$revisionhistory.',"u":'.$users.'}';
}
?>
