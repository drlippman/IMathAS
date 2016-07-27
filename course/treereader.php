<?php
//IMathAS:  Tree-style framed content reading based on block structure
//(c) 2011 David Lippman

require("../validate.php");
if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($guestid)) { // loaded by a NON-teacher
	echo "You are not enrolled in this course. Please return to the <a href=\"../index.php\">Home Page</a> and enroll";
	exit;
}
if (isset($teacherid) || isset($tutorid)) {
	$viewall = true;
} else {
	$viewall = false;
}
if ((!isset($_GET['folder']) || $_GET['folder']=='') && !isset($sessiondata['folder'.$cid])) {
	$_GET['folder'] = '0';  
	$sessiondata['folder'.$cid] = '0';
	writesessiondata();
} else if ((isset($_GET['folder']) && $_GET['folder']!='') && (!isset($sessiondata['folder'.$cid]) || $sessiondata['folder'.$cid]!=$_GET['folder'])) {
	$sessiondata['folder'.$cid] = $_GET['folder'];
	writesessiondata();
} else if ((!isset($_GET['folder']) || $_GET['folder']=='') && isset($sessiondata['folder'.$cid])) {
	$_GET['folder'] = $sessiondata['folder'.$cid];
}

if (isset($_GET['recordbookmark'])) {  //for recording bookmarks into the student's record
	$query = "UPDATE imas_bookmarks SET value='{$_GET['recordbookmark']}' WHERE userid='$userid' AND courseid='$cid' AND name='TR{$_GET['folder']}'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_affected_rows()==0) {
		$query = "INSERT INTO imas_bookmarks (userid,courseid,name,value) VALUES ('$userid','$cid','TR{$_GET['folder']}','{$_GET['recordbookmark']}')";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	return "OK";
	exit;
}

$cid = intval($_GET['cid']);
$query = "SELECT name,itemorder,hideicons,picicons,allowunenroll,msgset,chatset,topbar,cploc FROM imas_courses WHERE id=$cid";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$line = mysql_fetch_array($result, MYSQL_ASSOC);
$items = unserialize($line['itemorder']);		

if ($_GET['folder']!='0') {
	$now = time() + $previewshift;
	$blocktree = explode('-',$_GET['folder']);
	$backtrack = array();
	for ($i=1;$i<count($blocktree);$i++) {
		$backtrack[] = array($items[$blocktree[$i]-1]['name'],implode('-',array_slice($blocktree,0,$i+1)));
		if (!isset($teacherid) && !isset($tutorid) && $items[$blocktree[$i]-1]['avail']<2 && $items[$blocktree[$i]-1]['SH'][0]!='S' &&($now<$items[$blocktree[$i]-1]['startdate'] || $now>$items[$blocktree[$i]-1]['enddate'] || $items[$blocktree[$i]-1]['avail']=='0')) {
			$_GET['folder'] = 0;
			$items = unserialize($line['itemorder']);
			unset($backtrack);
			unset($blocktree);
			break;
		}
		if (isset($items[$blocktree[$i]-1]['grouplimit']) && count($items[$blocktree[$i]-1]['grouplimit'])>0 && !isset($teacherid) && !isset($tutorid)) {
			if (!in_array('s-'.$studentinfo['section'],$items[$blocktree[$i]-1]['grouplimit'])) {
				echo 'Not authorized';
				exit;
			}
		}  
		$items = $items[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	}
}
$curBreadcrumb = $breadcrumbbase;
if (isset($backtrack) && count($backtrack)>0) {
	$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder=0\">$coursename</a> ";
	for ($i=0;$i<count($backtrack);$i++) {
		$curBreadcrumb .= "&gt; ";
		if ($i!=count($backtrack)-1) {
			$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder={$backtrack[$i][1]}\">";
		}
		$curBreadcrumb .= stripslashes($backtrack[$i][0]);
		if ($i!=count($backtrack)-1) {
			$curBreadcrumb .= "</a>";
		}
	}
	$curname = $backtrack[count($backtrack)-1][0];
	if (count($backtrack)==1) {
		$backlink =  "<span class=right><a href=\"course.php?cid=$cid&folder=0\">Back</a></span><br class=\"form\" />";
	} else {
		$backlink = "<span class=right><a href=\"course.php?cid=$cid&folder=".$backtrack[count($backtrack)-2][1]."\">Back</a></span><br class=\"form\" />";
	}
} else {
	$curBreadcrumb .= $coursename;
	$curname = $coursename;
}


//Start Output
$pagetitle = "Content Browser";
$placeinhead = '<script type="text/javascript">function toggle(id) {
	node = document.getElementById(id);
	button = document.getElementById("b"+id);
	if (node.className.match("show")) {
		node.className = node.className.replace(/show/,"hide");
		button.innerHTML = "+";
	} else {
		node.className = node.className.replace(/hide/,"show");
		button.innerHTML = "-";
	}
}
function resizeiframe() {
	var windowheight = document.documentElement.clientHeight;
	var theframe = document.getElementById("readerframe");
	var framepos = findPos(theframe);
	var height =  (windowheight - framepos[1] - 15);
	theframe.style.height =height + "px";
}

function recordlasttreeview(id) {
	var url = "'.$urlmode . $_SERVER['HTTP_HOST'] . $imasroot . '/course/treereader.php?cid='.$cid.'&folder='.$_GET['folder'].'&recordbookmark=" + id;
	basicahah(url, "bmrecout");
}
var treereadernavstate = 1;
function toggletreereadernav() {
	if (treereadernavstate==1) {
		document.getElementById("leftcontent").style.width = "20px";
		document.getElementById("leftcontenttext").style.display = "none";
		document.getElementById("centercontent").style.marginLeft = "30px";
		document.getElementById("navtoggle").src= document.getElementById("navtoggle").src.replace(/collapse/,"expand");
	} else {
		document.getElementById("leftcontent").style.width = "250px";
		document.getElementById("leftcontenttext").style.display = "";
		document.getElementById("centercontent").style.marginLeft = "260px";
		document.getElementById("navtoggle").src= document.getElementById("navtoggle").src.replace(/expand/,"collapse");
	}
	resizeiframe();
	treereadernavstate = (treereadernavstate+1)%2;
}
function updateTRunans(aid, status) {
	var urlbase = "'.$urlmode.$_SERVER['HTTP_HOST'] . $imasroot.'";
	if (status==0) {
		document.getElementById("aimg"+aid).src = urlbase+"/img/q_fullbox.gif";
	} else if (status==1) {
		document.getElementById("aimg"+aid).src = urlbase+"/img/q_halfbox.gif";
	} else {
		document.getElementById("aimg"+aid).src = urlbase+"/img/q_emptybox.gif";
	}
}
addLoadEvent(resizeiframe);
</script>
<style type="text/css">
img {
vertical-align: middle;
}
html, body {
height: auto;
}
#leftcontent {
	margin-top: 0px;
}
</style>';
$placeinhead .= "<style type=\"text/css\">\n<!--\n@import url(\"$imasroot/course/libtree.css\");\n-->\n</style>\n";
require("../header.php");

$query = "SELECT value FROM imas_bookmarks WHERE userid='$userid' AND courseid='$cid' AND name='TR{$_GET['folder']}'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)==0) {
	$openitem = '';
} else {
	$openitem = mysql_result($result,0,0);
}

$foundfirstitem = '';
$foundopenitem = '';

$astatus = array();
if (!$viewall) {
	$query = "SELECT ia.id,ias.bestscores FROM imas_assessments AS ia JOIN imas_assessment_sessions AS ias ON ia.id=ias.assessmentid ";
	$query .= "WHERE ia.courseid='$cid' AND ias.userid='$userid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		if (strpos($row[1],'-1')===false) {
			$astatus[$row[0]] = 2; //completed
		} else { //at least some undone
			$p = explode(',',$row[1]);
			foreach ($p as $v) {
				if (strpos($v,'-1')===false) {
					$astatus[$row[0]] = 1; //at least some is done	
					continue 2;
				}
			}
			$astatus[$row[0]] = 0; //unstarted
		}
	}
	$exceptions = array();
	if (!isset($teacherid) && !isset($tutorid)) {
		$query = "SELECT items.id,ex.startdate,ex.enddate,ex.islatepass,ex.waivereqscore,ex.itemtype FROM ";
		$query .= "imas_exceptions AS ex,imas_items as items,imas_assessments as i_a WHERE ex.userid='$userid' AND ";
		$query .= "ex.assessmentid=i_a.id AND (items.typeid=i_a.id AND items.itemtype='Assessment' AND items.courseid='$cid') ";
		$query .= "UNION SELECT items.id,ex.startdate,ex.enddate,ex.islatepass,ex.waivereqscore,ex.itemtype FROM ";
		$query .= "imas_exceptions AS ex,imas_items as items,imas_forums as i_f WHERE ex.userid='$userid' AND ";
		$query .= "ex.assessmentid=i_f.id AND (items.typeid=i_f.id AND items.itemtype='Forum' AND items.courseid='$cid') ";
		
		// $query .= "AND (($now<i_a.startdate AND ex.startdate<$now) OR ($now>i_a.enddate AND $now<ex.enddate))";
		//$query .= "AND (ex.startdate<$now AND $now<ex.enddate)";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$exceptions[$line['id']] = array($line['startdate'],$line['enddate'],$line['islatepass'],$line['waivereqscore'],$line['itemtype']);
		}
	}
	//update block start/end dates to show blocks containing items with exceptions
	if (count($exceptions)>0) {
		upsendexceptions($items);
	}
}
		
function printlist($items) {
	global $cid,$imasroot,$foundfirstitem, $foundopenitem, $openitem, $astatus, $studentinfo, $now, $viewall, $exceptions;
	$out = '';
	$isopen = false;
	foreach ($items as $item) {
		if (is_array($item)) { //is block
			//TODO check that it's available
			if ($viewall || $item['avail']==2 || ($item['avail']==1 && $item['startdate']<$now && $item['enddate']>$now)) {
				list($subcontent,$bisopen) = printlist($item['items']);
				if ($bisopen) {
					$isopen = true;
				}
				if ($bisopen) {
					$out .=  "<li class=lihdr><span class=hdr onClick=\"toggle({$item['id']})\"><span class=btn id=\"b{$item['id']}\">-</span> <img src=\"$imasroot/img/folder_tiny.png\"> ";
					$out .=  "{$item['name']}</span>\n";
					$out .=  '<ul class="show nomark" id="'.$item['id'].'">';
				} else {
					$out .=  "<li class=lihdr><span class=hdr onClick=\"toggle({$item['id']})\"><span class=btn id=\"b{$item['id']}\">+</span> <img src=\"$imasroot/img/folder_tiny.png\"> ";
					$out .=  "{$item['name']}</span>\n";
					$out .=  '<ul class="hide nomark" id="'.$item['id'].'">';
				}
				$out .= $subcontent;
				$out .=  '</ul></li>';
			}
		} else {
			$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$item'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			$typeid = $line['typeid'];
			$itemtype = $line['itemtype'];
			/*if ($line['itemtype']=="Calendar") {
				$out .=  '<li><img src="'.$imasroot.'/img/calendar_tiny.png"> <a href="showcalendar.php?cid='.$cid.'" target="readerframe">Calendar</a></li>';
				if ($openitem=='' && $foundfirstitem=='') {
				 	 $foundfirstitem = '/course/showcalendar.php?cid='.$cid;
				 	 $isopen = true;
				}
			} else*/
			if ($line['itemtype']=='Assessment') {
				//TODO check availability, timelimit, etc.
				//TODO: reqscoreaid, latepasses
				 $query = "SELECT name,summary,startdate,enddate,reviewdate,deffeedback,reqscore,reqscoreaid,avail,allowlate,timelimit,displaymethod FROM imas_assessments WHERE id='$typeid'";
				 $result = mysql_query($query) or die("Query failed : " . mysql_error());
				 $line = mysql_fetch_array($result, MYSQL_ASSOC);
				 if (isset($exceptions[$item])) {
					$line['startdate'] = $exceptions[$item][0];
					$line['enddate'] = $exceptions[$item][1];
				 }
				 if ($viewall || ($line['avail']==1 && $line['startdate']<$now && ($line['enddate']>$now || $line['reviewdate']>$now))) {
					 if ($openitem=='' && $foundfirstitem=='') {
						 $foundfirstitem = '/assessment/showtest.php?cid='.$cid.'&amp;id='.$typeid; $isopen = true;
					 }
					 if ($itemtype.$typeid===$openitem) {
						 $foundopenitem = '/assessment/showtest.php?cid='.$cid.'&amp;id='.$typeid; $isopen = true;
					 }
					 $out .= '<li>';
					 if ($line['displaymethod']!='Embed') {
						 $out .=  '<img src="'.$imasroot.'/img/assess_tiny.png"> ';
					 } else {
						 if (!isset($astatus[$typeid]) || $astatus[$typeid]==0) {
							 $out .= '<img id="aimg'.$typeid.'" src="'.$imasroot.'/img/q_fullbox.gif" /> ';
						 } else if ($astatus[$typeid]==1) {
							 $out .= '<img id="aimg'.$typeid.'" src="'.$imasroot.'/img/q_halfbox.gif" /> ';
						 } else {
							 $out .= '<img id="aimg'.$typeid.'" src="'.$imasroot.'/img/q_emptybox.gif" /> ';
						 }
					 }
					 if (isset($studentinfo['timelimitmult'])) {
						 $line['timelimit'] *= $studentinfo['timelimitmult'];
					 }
					 $line['timelimit'] = abs($line['timelimit']);
					 if ($line['timelimit']>0) {
						   if ($line['timelimit']>3600) {
							$tlhrs = floor($line['timelimit']/3600);
							$tlrem = $line['timelimit'] % 3600;
							$tlmin = floor($tlrem/60);
							$tlsec = $tlrem % 60;
							$tlwrds = "$tlhrs " . _('hour');
							if ($tlhrs > 1) { $tlwrds .= "s";}
							if ($tlmin > 0) { $tlwrds .= ", $tlmin " . _('minute');}
							if ($tlmin > 1) { $tlwrds .= "s";}
							if ($tlsec > 0) { $tlwrds .= ", $tlsec " . _('second');}
							if ($tlsec > 1) { $tlwrds .= "s";}
						} else if ($line['timelimit']>60) {
							$tlmin = floor($line['timelimit']/60);
							$tlsec = $line['timelimit'] % 60;
							$tlwrds = "$tlmin " . _('minute');
							if ($tlmin > 1) { $tlwrds .= "s";}
							if ($tlsec > 0) { $tlwrds .= ", $tlsec " . _('second');}
							if ($tlsec > 1) { $tlwrds .= "s";}
						} else {
							$tlwrds = $line['timelimit'] . _(' second(s)');
						}
					 } else {
						   $tlwrds = '';
					 }
					 if ($tlwrds != '') {
						 $onclick = 'onclick="return confirm(\''. sprintf(_('This assessment has a time limit of %s.  Click OK to start or continue working on the assessment.'), $tlwrds). '\')"';
					 } else {
						 $onclick = 'onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')"';
					 }
					 $out .= '<a href="'.$imasroot.'/assessment/showtest.php?cid='.$cid.'&amp;id='.$typeid.'" '.$onclick.' target="readerframe">'.$line['name'].'</a></li>';
				 }
			} else if ($line['itemtype']=='LinkedText') {
				//TODO check availability, etc.
				 $query = "SELECT title,summary,text,startdate,enddate,avail,target FROM imas_linkedtext WHERE id='$typeid'";
				 $result = mysql_query($query) or die("Query failed : " . mysql_error());
				 $line = mysql_fetch_array($result, MYSQL_ASSOC);
				 if ($viewall || $line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
					 if ($openitem=='' && $foundfirstitem=='') {
						 $foundfirstitem = '/course/showlinkedtext.php?cid='.$cid.'&amp;id='.$typeid; $isopen = true;
					 }
					 if ($itemtype.$typeid===$openitem) {
						 $foundopenitem = '/course/showlinkedtext.php?cid='.$cid.'&amp;id='.$typeid; $isopen = true;
					 }
					 $out .=  '<li><img src="'.$imasroot.'/img/html_tiny.png"> <a href="showlinkedtext.php?cid='.$cid.'&amp;id='.$typeid.'"  onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')"  target="readerframe">'.$line['title'].'</a></li>';
				 }
			} /*else if ($line['itemtype']=='Forum') {
				//TODO check availability, etc.
				 $query = "SELECT id,name,description,startdate,enddate,groupsetid,avail,postby,replyby FROM imas_forums WHERE id='$typeid'";
				 $result = mysql_query($query) or die("Query failed : " . mysql_error());
				 $line = mysql_fetch_array($result, MYSQL_ASSOC);
				 if ($openitem=='' && $foundfirstitem=='') {
				 	 $foundfirstitem = '/forums/thread.php?cid='.$cid.'&amp;forum='.$typeid; $isopen = true;
				 }
				 if ($itemtype.$typeid===$openitem) {
				 	 $foundopenitem = '/forums/thread.php?cid='.$cid.'&amp;forum='.$typeid; $isopen = true;
				 }
				 $out .=  '<li><img src="'.$imasroot.'/img/forum_tiny.png"> <a href="'.$imasroot.'/forums/thread.php?cid='.$cid.'&amp;forum='.$typeid.'" onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')" target="readerframe">'.$line['name'].'</a></li>';
			} */else if ($line['itemtype']=='Wiki') {
				//TODO check availability, etc.
				 $query = "SELECT id,name,description,startdate,enddate,editbydate,avail,settings,groupsetid FROM imas_wikis WHERE id='$typeid'";
				 $result = mysql_query($query) or die("Query failed : " . mysql_error());
				 $line = mysql_fetch_array($result, MYSQL_ASSOC);
				 if ($viewall || $line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
					 if ($openitem=='' && $foundfirstitem=='') {
						 $foundfirstitem = '/wikis/viewwiki.php?cid='.$cid.'&amp;id='.$typeid.'&framed=true'; $isopen = true;
					 }
					 if ($itemtype.$typeid===$openitem) {
						 $foundopenitem = '/wikis/viewwiki.php?cid='.$cid.'&amp;id='.$typeid.'&framed=true'; $isopen = true;
					 }
					 $out .=  '<li><img src="'.$imasroot.'/img/wiki_tiny.png"> <a href="'.$imasroot.'/wikis/viewwiki.php?cid='.$cid.'&amp;id='.$typeid.'&framed=true"  onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')" target="readerframe">'.$line['name'].'</a></li>';
				 }
			} 
			
		}
	}
	return array($out,$isopen);
}
function upsendexceptions(&$items) {
	   global $exceptions;
	   $minsdate = 9999999999;
	   $maxedate = 0;
	   foreach ($items as $k=>$item) {
		   if (is_array($item)) {
			  $hasexc = upsendexceptions($items[$k]['items']);
			  if ($hasexc!=FALSE) {
				  if ($hasexc[0]<$items[$k]['startdate']) {
					  $items[$k]['startdate'] = $hasexc[0];
				  }
				  if ($hasexc[1]>$items[$k]['enddate']) {
					  $items[$k]['enddate'] = $hasexc[1];
				  }
				//return ($hasexc);
				if ($hasexc[0]<$minsdate) { $minsdate = $hasexc[0];}
				if ($hasexc[1]>$maxedate) { $maxedate = $hasexc[1];}
			  }
		   } else {
			   if (isset($exceptions[$item]) && $exceptions[$item][4]=='A') {
				  // return ($exceptions[$item]);
				   if ($exceptions[$item][0]<$minsdate) { $minsdate = $exceptions[$item][0];}
				   if ($exceptions[$item][1]>$maxedate) { $maxedate = $exceptions[$item][1];}
			   }
		   }
	   }
	   if ($minsdate<9999999999 || $maxedate>0) {
		   return (array($minsdate,$maxedate));
	   } else {
		   return false;
	   }
   }
?>
<div class="breadcrumb">
	<span class="padright">
	<?php if (isset($guestid)) {
		echo '<span class="red">Instructor Preview</span> ';
	}?>
	<?php echo $userfullname ?>
	</span>
	<?php echo $curBreadcrumb ?>
	<div class="clear"></div>
</div>

<div id="leftcontent" style="width: 250px;">
<img id="navtoggle" src="<?php echo $imasroot;?>/img/collapse.gif"  onclick="toggletreereadernav()"/>
<ul id="leftcontenttext" class="nomark" style="margin-left:5px; font-size: 90%;">
<?php
$ul = printlist($items);
echo $ul[0];


?>
</ul>
<div id="bmrecout" style="display:none;"></div>
</div>
<div id="centercontent" style="margin-left: 260px;">

<iframe id="readerframe" name="readerframe" style="width:100%; border:1px solid #ccc;" src="<?php echo $imasroot . (($openitem=='')?$foundfirstitem:$foundopenitem); ?>"></iframe>
</div>
<?php
require("../footer.php");
?>
