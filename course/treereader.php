<?php
//IMathAS:  Tree-style framed content reading based on block structure
//(c) 2011 David Lippman

require("../init.php");
require_once("../includes/exceptionfuncs.php");

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($instrPreviewId)) { // loaded by a NON-teacher
	echo "You are not enrolled in this course. Please return to the <a href=\"../index.php\">Home Page</a> and enroll";
	exit;
}
if (isset($teacherid) || isset($tutorid)) {
	$viewall = true;
} else {
	$viewall = false;
}
if ((!isset($_GET['folder']) || $_GET['folder']=='') && !isset($_SESSION['folder'.$cid])) {
	$_GET['folder'] = '0';
	$_SESSION['folder'.$cid] = '0';
} else if ((isset($_GET['folder']) && $_GET['folder']!='') && (!isset($_SESSION['folder'.$cid]) || $_SESSION['folder'.$cid]!=$_GET['folder'])) {
	$_SESSION['folder'.$cid] = $_GET['folder'];
} else if ((!isset($_GET['folder']) || $_GET['folder']=='') && isset($_SESSION['folder'.$cid])) {
	$_GET['folder'] = $_SESSION['folder'.$cid];
}

if (isset($_GET['recordbookmark'])) {  //for recording bookmarks into the student's record
	$stm = $DBH->prepare("UPDATE imas_bookmarks SET value=:value WHERE userid=:userid AND courseid=:courseid AND name=:name");
	$stm->execute(array(':value'=>$_GET['recordbookmark'], ':userid'=>$userid, ':courseid'=>$cid, ':name'=>'TR'.$_GET['folder']));
	if ($stm->rowCount()==0) {
		$stm = $DBH->prepare("INSERT INTO imas_bookmarks (userid,courseid,name,value) VALUES (:userid, :courseid, :name, :value)");
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':name'=>'TR'.$_GET['folder'], ':value'=>$_GET['recordbookmark']));
	}
	return "OK";
	exit;
}

$cid = intval($_GET['cid']);
$stm = $DBH->prepare("SELECT name,itemorder,allowunenroll,msgset FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$line = $stm->fetch(PDO::FETCH_ASSOC);
$items = unserialize($line['itemorder']);

if ($_GET['folder']!='0') {
	$now = time();
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
	$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder=0\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	for ($i=0;$i<count($backtrack);$i++) {
		$curBreadcrumb .= "&gt; ";
		if ($i!=count($backtrack)-1) {
			$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder=".Sanitize::encodeUrlParam($backtrack[$i][1])."\">";
		}
		$curBreadcrumb .= Sanitize::encodeStringForDisplay($backtrack[$i][0]);
		if ($i!=count($backtrack)-1) {
			$curBreadcrumb .= "</a>";
		}
	}
	$curname = $backtrack[count($backtrack)-1][0];
	if (count($backtrack)==1) {
		$backlink =  "<span class=right><a href=\"course.php?cid=$cid&folder=0\">Back</a></span><br class=\"form\" />";
	} else {
		$backlink = "<span class=right><a href=\"course.php?cid=$cid&folder=".Sanitize::encodeUrlParam($backtrack[count($backtrack)-2][1])."\">Back</a></span><br class=\"form\" />";
	}
} else {
	$curBreadcrumb .= Sanitize::encodeStringForDisplay($coursename);
	$curname = Sanitize::encodeStringForDisplay($coursename);
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
	var footer = $(".footerwrapper");
	var footerheight = 0;
	if (footer.length > 0) {
		footerheight = footer.height();
	}
	var height =  (windowheight - framepos[1] - 15 - footerheight);
	theframe.style.height =height + "px";
}

function recordlasttreeview(id) {
	var url = "' . $GLOBALS['basesiteurl'] . '/course/treereader.php?cid='.$cid.'&folder='.Sanitize::encodeUrlParam($_GET['folder']).'&recordbookmark=" + id;
	basicahah(url, "bmrecout");
}
var treereadernavstate = 1;
function toggletreereadernav() {
	var lc = document.getElementById("leftcontent");

	if (treereadernavstate==1) {
		$("#leftcontenttext").slideUp(200,function() {
			$(this).attr("aria-expanded",false).attr("aria-hidden",true);
			$("#leftcontent").addClass("narrow").attr("aria-expanded",false);
			//document.getElementById("centercontent").style.marginLeft = "30px";
			$("#centercontent").addClass("wider");
			resizeiframe();
		});;
		document.getElementById("navtoggle").src= document.getElementById("navtoggle").src.replace(/collapse/,"expand");
	} else {
		$("#leftcontent").removeClass("narrow").attr("aria-expanded",true);
		$("#leftcontenttext").slideDown(200).attr("aria-expanded",true).attr("aria-hidden",false);
		//document.getElementById("centercontent").style.marginLeft = "260px";
		$("#centercontent").removeClass("wider");
		document.getElementById("navtoggle").src= document.getElementById("navtoggle").src.replace(/expand/,"collapse");
		resizeiframe();
	}

	treereadernavstate = (treereadernavstate+1)%2;
}

function updateTRunans(aid, status) {
	console.log(aid + "," + status);
	var urlbase = "' . $GLOBALS['basesiteurl'] . '";
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
	width: 250px;
}
#leftcontent.narrow {
	width: 20px;
}
#centercontent {
	margin-left: 260px;
	position:relative;
}
#centercontent.wider {
	margin-left: 30px;
}
@media (max-width:480px) {
	#centercontent, #centercontent.wider {
		margin-left: 0px;
	}
	#leftcontent {
		position: relative;
		width: auto;
	}
}
ul[role="tree"]:focus {
    outline:1px dotted #0000ff;
}
ul[role="tree"] li[aria-selected="true"]  {
      outline: none;
}
ul[role="tree"] li[aria-selected="true"] > span.hdr .blocklbl {
	border:dotted 1px;
}
ul[role="tree"] li > span.hdr .blocklbl {
	border: 1px transparent;

}
ul[role="tree"] li[aria-selected="true"] > a {
	border:dotted 1px;
}
ul[role="tree"] li[aria-expanded="false"] > ul {
      display:none;
}
ul[role="tree"] li[aria-expanded="true"] > ul {
      display:block;
}

</style>';
$placeinhead .= "<style type=\"text/css\">\n<!--\n@import url(\"$staticroot/course/libtree.css\");\n-->\n</style>\n";
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/a11ytree.js"></script>';
$placeinhead .= '<script type="text/javascript">$(function() {
  $("#leftcontenttext").a11yTree({
	toggleSelector: "span.hdr",
	toggleIconSelector: "span.btn",
	treeItemLabelSelector: "span.blocklbl",
	onCollapse:function($item) {
		$item.children("span").children("span.btn").text("+");
	},
	onExpand:function($item) {
		$item.children("span").children("span.btn").text("-");
	}
  });
});</script>';
require("../header.php");
$stm = $DBH->prepare("SELECT value FROM imas_bookmarks WHERE userid=:userid AND courseid=:courseid AND name=:name");
$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':name'=>'TR'.$_GET['folder']));
if ($stm->rowCount()==0) {
	$openitem = '';
} else {
	$openitem = $stm->fetchColumn(0);
}

$foundfirstitem = '';
$foundopenitem = '';

$astatus = array();
if (!$viewall) {
	$query = "SELECT ia.id,ia.ver,ias.bestscores FROM imas_assessments AS ia ";
	$query .= "JOIN imas_assessment_sessions AS ias ON ia.id=ias.assessmentid ";
	$query .= "WHERE ia.courseid=:courseid AND ias.userid=:userid AND ia.ver=1";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
	$query = "SELECT ia.id,ia.ver,iar.status,iar.lastchange FROM imas_assessments AS ia ";
	$query .= "JOIN imas_assessment_records AS iar ON ia.id=iar.assessmentid ";
	$query .= "WHERE ia.courseid=:courseid AND iar.userid=:userid AND ia.ver>1";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (($row['status']&3)==0) {
			$astatus[$row['id']] = 2; //completed
		} else if ($row['lastchange']>0) {
			$astatus[$row['id']] = 1; //something done
		} else {
			$astatus[$row['id']] = 0; //unstarted
		}
	}
	$exceptions = array();
	if (!isset($teacherid) && !isset($tutorid)) {
		$query = "SELECT items.id,ex.startdate,ex.enddate,ex.islatepass,ex.waivereqscore,ex.itemtype FROM ";
		$query .= "imas_exceptions AS ex,imas_items as items,imas_assessments as i_a WHERE ex.userid=:userid AND ";
		$query .= "ex.assessmentid=i_a.id AND (items.typeid=i_a.id AND items.itemtype='Assessment' AND items.courseid=:courseid) ";
		$query .= "UNION SELECT items.id,ex.startdate,ex.enddate,ex.islatepass,ex.waivereqscore,ex.itemtype FROM ";
		$query .= "imas_exceptions AS ex,imas_items as items,imas_forums as i_f WHERE ex.userid=:userid2 AND ";
		$query .= "ex.assessmentid=i_f.id AND (items.typeid=i_f.id AND items.itemtype='Forum' AND items.courseid=:courseid2) ";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':userid2'=>$userid, ':courseid2'=>$cid));

		// $query .= "AND (($now<i_a.startdate AND ex.startdate<$now) OR ($now>i_a.enddate AND $now<ex.enddate))";
		//$query .= "AND (ex.startdate<$now AND $now<ex.enddate)";
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$exceptions[$line['id']] = array($line['startdate'],$line['enddate'],$line['islatepass'],$line['waivereqscore'],$line['itemtype']);
		}
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $studentinfo['latepasses'], $latepasshrs);
	} else {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, false);
	}
	//update block start/end dates to show blocks containing items with exceptions
	if (count($exceptions)>0) {
		upsendexceptions($items);
	}
}

function printlist($items) {
	global $DBH,$CFG,$cid,$imasroot,$foundfirstitem, $foundopenitem, $openitem, $astatus, $studentinfo, $now, $viewall, $exceptions, $exceptionfuncs,$staticroot;
	$out = '';
	$isopen = false;
	foreach ($items as $item) {
		$opentxt = '';
		if (is_array($item)) { //is block
			//TODO check that it's available
			if ($viewall || $item['avail']==2 || ($item['avail']==1 && $item['startdate']<$now && $item['enddate']>$now)) {
				list($subcontent,$bisopen) = printlist($item['items']);
				if ($bisopen) {
					$isopen = true;
				}
				if ($bisopen) {
					$out .=  "<li class=lihdr aria-expanded=true ><span class=hdr><span class=btn id=\"b".Sanitize::encodeStringForDisplay($item['id'])."\">-</span> <img src=\"$staticroot/img/folder_tiny.png\" alt=\"Folder\"> ";
					$out .=  "<span class=blocklbl>".Sanitize::encodeStringForDisplay($item['name'])."</span></span>\n";
					$out .=  '<ul class="nomark" id="'.Sanitize::encodeStringForDisplay($item['id']).'">';
				} else {
					$out .=  "<li class=lihdr aria-expanded=false><span class=hdr><span class=btn id=\"b".Sanitize::encodeStringForDisplay($item['id'])."\">+</span> <img src=\"$staticroot/img/folder_tiny.png\" alt=\"Folder\"> ";
					$out .=  "<span class=blocklbl>".Sanitize::encodeStringForDisplay($item['name'])."</span></span>\n";
					$out .=  '<ul class="nomark" id="'.Sanitize::encodeStringForDisplay($item['id']).'">';
				}
				$out .= $subcontent;
				$out .=  '</ul></li>';
			}
		} else {
			$stm = $DBH->prepare("SELECT itemtype,typeid FROM imas_items WHERE id=:id");
			$stm->execute(array(':id'=>$item));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			$typeid = Sanitize::onlyInt($line['typeid']);
			$itemtype = Sanitize::simpleString($line['itemtype']);
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
				 $stm = $DBH->prepare("SELECT name,summary,startdate,enddate,reviewdate,LPcutoff,deffeedback,reqscore,reqscoreaid,reqscoretype,avail,allowlate,timelimit,displaymethod,ver FROM imas_assessments WHERE id=:id");
				 $stm->execute(array(':id'=>$typeid));
				 $line = $stm->fetch(PDO::FETCH_ASSOC);
				 if (isset($exceptions[$item])) {
				 	 $useexception = $exceptionfuncs->getCanUseAssessException($exceptions[$item], $line, true);
				 	 if ($useexception) {
				 	 	 $line['startdate'] = $exceptions[$item][0];
				 	 	 $line['enddate'] = $exceptions[$item][1];
				 	 }
				 }
				 if ($viewall || ($line['avail']==1 && $line['startdate']<$now && ($line['enddate']>$now || $line['reviewdate']>$now))) {
					 if ($openitem=='' && $foundfirstitem=='') {
						 if ($line['ver'] > 1) {
							 $foundfirstitem = '/assess2/?cid='.$cid.'&amp;aid='.Sanitize::encodeUrlParam($typeid);
						 } else {
							 $foundfirstitem = '/assessment/showtest.php?cid='.$cid.'&amp;id='.Sanitize::encodeUrlParam($typeid);
						 }
						 $isopen = true;
					 }
					 if ($itemtype.$typeid===$openitem) {
						 if ($line['ver'] > 1) {
							 $foundopenitem = '/assess2/?cid='.$cid.'&amp;aid='.Sanitize::encodeUrlParam($typeid);
						 } else {
							 $foundopenitem = '/assessment/showtest.php?cid='.$cid.'&amp;id='.Sanitize::encodeUrlParam($typeid);
						 }
						 $isopen = true;
						 $opentxt = ' aria-selected="true" ';
					 }
					 $out .= '<li '.$opentxt.'>';
					 if ($line['displaymethod']!='Embed' && $line['displaymethod']!='skip' && $line['displaymethod']!='full') {
						 $out .=  '<img src="'.$staticroot.'/img/assess_tiny.png" alt="Assessment"> ';
					 } else {
						 if (!isset($astatus[$typeid]) || $astatus[$typeid]==0) {
							 $out .= '<img id="aimg'.$typeid.'" src="'.$staticroot.'/img/q_fullbox.gif" alt="'._('Unattempted').'"/> ';
						 } else if ($astatus[$typeid]==1) {
							 $out .= '<img id="aimg'.$typeid.'" src="'.$staticroot.'/img/q_halfbox.gif" alt="'._('Started').'"/> ';
						 } else {
							 $out .= '<img id="aimg'.$typeid.'" src="'.$staticroot.'/img/q_emptybox.gif" alt="'._('Attempted').'"/> ';
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
						 $onclick = 'onclick="return confirm(\''. sprintf(_('This assessment has a time limit of %s.  Click OK to start or continue working on the assessment.'), Sanitize::encodeStringForJavascript($tlwrds)). '\')"';
					 } else {
						 $onclick = 'onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')"';
					 }
					 if ($line['ver'] > 1) {
						 if (!empty($CFG['assess2-use-vue-dev'])) {
							 $out .= '<a tabindex="-1" href="'.$CFG['assess2-use-vue-dev-address'].'/?cid='.$cid.'&amp;aid='.$typeid.'" '.$onclick.' target="readerframe">'.Sanitize::encodeStringForDisplay($line['name']).'</a></li>';
						 } else {
						 	$out .= '<a tabindex="-1" href="'.$imasroot.'/assess2/?cid='.$cid.'&amp;aid='.$typeid.'" '.$onclick.' target="readerframe">'.Sanitize::encodeStringForDisplay($line['name']).'</a></li>';
						 }
					 } else {
						 $out .= '<a tabindex="-1" href="'.$imasroot.'/assessment/showtest.php?cid='.$cid.'&amp;id='.$typeid.'" '.$onclick.' target="readerframe">'.Sanitize::encodeStringForDisplay($line['name']).'</a></li>';
					 }
				 }
			} else if ($line['itemtype']=='LinkedText') {
				//TODO check availability, etc.
				 $stm = $DBH->prepare("SELECT title,summary,text,startdate,enddate,avail,target FROM imas_linkedtext WHERE id=:id");
				 $stm->execute(array(':id'=>$typeid));
				 $line = $stm->fetch(PDO::FETCH_ASSOC);
				 if ($viewall || $line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
					 if ($openitem=='' && $foundfirstitem=='') {
						 $foundfirstitem = '/course/showlinkedtext.php?cid='.$cid.'&amp;id='.Sanitize::encodeUrlParam($typeid); $isopen = true;
					 }
					 if ($itemtype.$typeid===$openitem) {
						 $foundopenitem = '/course/showlinkedtext.php?cid='.$cid.'&amp;id='.Sanitize::encodeUrlParam($typeid); $isopen = true;  $opentxt = ' aria-selected="true" ';
					 }
					 $out .=  '<li '.$opentxt.'><img src="'.$staticroot.'/img/html_tiny.png" alt="Link"> <a tabindex="-1" href="showlinkedtext.php?cid='.$cid.'&amp;id='.Sanitize::encodeUrlParam($typeid).'"  onclick="recordlasttreeview(\''.Sanitize::encodeStringForJavascript($itemtype).Sanitize::encodeStringForJavascript($typeid).'\')"  target="readerframe">'.Sanitize::encodeStringForDisplay($line['title']).'</a></li>';
				 }
			} else if ($line['itemtype']=='InlineText') {
				//TODO check availability, etc.
				 $stm = $DBH->prepare("SELECT title,text,startdate,enddate,avail FROM imas_inlinetext WHERE id=:id");
				 $stm->execute(array(':id'=>$typeid));
				 $line = $stm->fetch(PDO::FETCH_ASSOC);
				 if ($viewall || $line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
					 if ($openitem=='' && $foundfirstitem=='') {
						 $foundfirstitem = '/course/showinlinetext.php?cid='.$cid.'&amp;id='.Sanitize::encodeUrlParam($typeid); $isopen = true;
					 }
					 if ($itemtype.$typeid===$openitem) {
						 $foundopenitem = '/course/showinlinetext.php?cid='.$cid.'&amp;id='.Sanitize::encodeUrlParam($typeid); $isopen = true;  $opentxt = ' aria-selected="true" ';
					 }
					 $out .=  '<li '.$opentxt.'><img src="'.$staticroot.'/img/inline_tiny.png" alt="Text"> <a tabindex="-1" href="showinlinetext.php?cid='.$cid.'&amp;id='.Sanitize::encodeUrlParam($typeid).'"  onclick="recordlasttreeview(\''.Sanitize::encodeStringForJavascript($itemtype).Sanitize::encodeStringForJavascript($typeid).'\')"  target="readerframe">'.Sanitize::encodeStringForDisplay($line['title']).'</a></li>';
				 }
			}/*else if ($line['itemtype']=='Forum') {
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
				 $out .=  '<li><img src="'.$imasroot.'/img/forum_tiny.png" alt="Forum"> <a href="'.$imasroot.'/forums/thread.php?cid='.$cid.'&amp;forum='.$typeid.'" onclick="recordlasttreeview(\''.$itemtype.$typeid.'\')" target="readerframe">'.$line['name'].'</a></li>';
			} */else if ($line['itemtype']=='Wiki') {
				//TODO check availability, etc.
				 $stm = $DBH->prepare("SELECT id,name,description,startdate,enddate,editbydate,avail,settings,groupsetid FROM imas_wikis WHERE id=:id");
				 $stm->execute(array(':id'=>$typeid));
				 $line = $stm->fetch(PDO::FETCH_ASSOC);
				 if ($viewall || $line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
					 if ($openitem=='' && $foundfirstitem=='') {
						 $foundfirstitem = '/wikis/viewwiki.php?cid='.$cid.'&amp;id='.$typeid.'&framed=true'; $isopen = true;
					 }
					 if ($itemtype.$typeid===$openitem) {
						 $foundopenitem = '/wikis/viewwiki.php?cid='.$cid.'&amp;id='.$typeid.'&framed=true'; $isopen = true;  $opentxt = ' aria-selected="true" ';
					 }
					 $out .=  '<li '.$opentxt.'><img src="'.$staticroot.'/img/wiki_tiny.png" alt="Wiki"> <a tabindex="-1" href="'.$imasroot.'/wikis/viewwiki.php?cid='.$cid.'&amp;id='.Sanitize::encodeUrlParam($typeid).'&framed=true"  onclick="recordlasttreeview(\''.$itemtype.Sanitize::encodeStringForJavascript($typeid).'\')" target="readerframe">'.Sanitize::encodeStringForDisplay($line['name']).'</a></li>';
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
	<?php if (isset($instrPreviewId)) {
		echo '<span class="noticetext">Instructor Preview</span> ';
	}?>
	<?php echo $userfullname ?>
	</span>
	<?php echo $curBreadcrumb ?>
	<div class="clear"></div>
</div>

<div id="leftcontent" class="treeleftcontent" role="navigation" aria-label="<?php echo _('Content navigation');?>">
<img id="navtoggle" src="<?php echo $staticroot;?>/img/collapse.gif"  onclick="toggletreereadernav()" alt="Expand/Collapse" aria-expanded="true" aria-controls="leftcontenttext"/>
<ul id="leftcontenttext" class="nomark" style="margin-left:5px; font-size: 90%;">
<?php
$ul = printlist($items);
echo $ul[0];


?>
</ul>
<div id="bmrecout" style="display:none;"></div>
</div>
<div id="centercontent" role="main">
<iframe id="readerframe" name="readerframe" data-noresize="true" title="Selected Content" style="width:100%; border:1px solid #ccc;" src="<?php echo $imasroot . (($openitem=='')?$foundfirstitem:$foundopenitem); ?>"></iframe>
</div>
<?php
require("../footer.php");
?>
