<?php
require("../validate.php");

if (!isset($teacherid)) {
	echo "Must be a teacher to access this page";
	exit;
}

if (isset($_POST['mergefrom'])) {
	$seta = array();
	foreach ($_POST['mergefrom'] as $aid=>$n) {
		if (trim($n)!='') {
			$seta[$n - 1] = $aid;
		}
	}
	$query = "SELECT name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,allowlate,exceptionpenalty,noprint,avail,groupmax,endmsg,deffeedbacktext,eqnhelper,caltag,calrtag,reqscore,reqscoreaid FROM imas_assessments WHERE id='{$seta[0]}'";
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	$row = mysql_fetch_row($result);
	$row[0] .= ' - merge result';
	$row = "'".implode("','",addslashes_deep($row))."'";
	$query = "INSERT INTO imas_assessments (courseid,name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,allowlate,exceptionpenalty,noprint,avail,groupmax,endmsg,deffeedbacktext,eqnhelper,caltag,calrtag,reqscore,reqscoreaid) ";

	$query .= "VALUES ('$cid',$row)";
	
	mysql_query($query) or die("Query failed : $query" . mysql_error());
	$newaid = mysql_insert_id();
	
	$intro = '';
	$newaitems = array();
	$qcnt = 0;
	
	function incrementqnum($m) {
		global $qcnt;
		return '[QUESTION '.($m[1]+$qcnt).']';
	}
	
	for ($i=0;$i<count($seta);$i++) {
		$query = "SELECT itemorder,intro,name FROM imas_assessments WHERE id='{$seta[$i]}'";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		$thisname = mysql_result($result,0,2);
		$thisintro = '';
		if (isset($_POST['addpages'])) {
			$thisintro .= "<p>[PAGE $thisname]</p>";
		}
		$thisintroraw = mysql_result($result,0,1);
		if (($introjson=json_decode($thisintroraw,true))!==null) { //is json intro
			$thisintro .=  = $introjson[0];		
		} else {
			$thisintro .= $thisintroraw;
		}
		$thisqcnt = 0;
		if (trim(mysql_result($result,0,0))!='') {
			$aitems = explode(',',mysql_result($result,0,0));
			foreach ($aitems as $k=>$aitem) {
				if (strpos($aitem,'~')===FALSE) {
					///$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
					///$query .= "SELECT '$newaid',questionsetid,points,attempts,penalty,category FROM imas_questions WHERE id='$aitem'";
					//mysql_query($query) or die("Query failed :$query " . mysql_error());
					$query = "SELECT questionsetid,points,attempts,penalty,category,rubric FROM imas_questions WHERE id='$aitem'";
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					$row = mysql_fetch_row($result);
					$rubric = array_pop($row);
					$row = "'".implode("','",addslashes_deep($row))."'";
					$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
					$query .= "VALUES ('$newaid',$row)";
					mysql_query($query) or die("Query failed : $query" . mysql_error());
					$newid = mysql_insert_id();
					$newaitems[] = $newid;
					$thisqcnt++;
				} else {
					$sub = explode('~',$aitem);
					$newsub = array();
					if (strpos($sub[0],'|')!==false) { //true except for bwards compat 
						$newsub[] = array_shift($sub);
						$pt = explode('|',$newsub[0]);
						$thisqcnt += $pt[0];
					}
					foreach ($sub as $subi) {
						//$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
						//$query .= "SELECT '$newaid',questionsetid,points,attempts,penalty,category FROM imas_questions WHERE id='$subi'";
						//mysql_query($query) or die("Query failed : $query" . mysql_error());
						$query = "SELECT questionsetid,points,attempts,penalty,category,rubric FROM imas_questions WHERE id='$subi'";
						$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
						$row = mysql_fetch_row($result);
						$rubric = array_pop($row);
						$row = "'".implode("','",addslashes_deep($row))."'";
						$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
						$query .= "VALUES ('$newaid',$row)";
						mysql_query($query) or die("Query failed : $query" . mysql_error());
						$newid = mysql_insert_id();
						$newsub[] = $newid;
					}
					$newaitems[] = implode('~',$newsub);
				}
			}
		}
		if (isset($_POST['nameasheader'])) {
			$thisintro = '<h3>'.$thisname.'</h3>'.$thisintro;
		}
		if ($_POST['mergetype']==0 || $_POST['mergetype']==3) {
			$thisintro = preg_replace_callback('/\[QUESTION\s*(\d+)\s*\]/','incrementqnum',$thisintro);
			$intro .= $thisintro;
		} else if ($_POST['mergetype']==1) {
			$intro .= $thisintro;
			for ($k=$qcnt+1;$k<=$qcnt+$thisqcnt;$k++) {
				$intro .= "<p>[QUESTION $k]</p>";
			}
		} else if ($_POST['mergetype']==2) {
			if ($thisqcnt==1) {
				$intro .= "<p>[Q ".($qcnt+1)."]</p>";
			} else {
				$intro .= "<p>[Q ".($qcnt+1)."-".($qcnt+$thisqcnt)."]</p>";
			}
			$intro .= $thisintro;
		}
		$qcnt += $thisqcnt;
	}
	if ($_POST['mergetype']==3) {
		$text = preg_replace('/<p[^>]*>(\s|&nbsp;)*(\[QUESTION.*?\])(\s|&nbsp;)*<\/p>/sm', ' $2 ', $intro);
		$text = preg_replace('/<p[^>]*>(\s|&nbsp;)*(\[PAGE.*?\])(\s|&nbsp;)*<\/p>/sm', '', $text);
		$text = preg_replace('/<p[^>]*>(\s|&nbsp;)*<span[^>]*>(\s|&nbsp;)*(\[QUESTION.*?\])(\s|&nbsp;)*<\/span>(\s|&nbsp;)*<\/p>/sm', ' $3 ', $text);
		$text = preg_replace('/<p[^>]*>(\s|&nbsp;)*<span[^>]*>(\s|&nbsp;)*(\[PAGE.*?\])(\s|&nbsp;)*<\/span>(\s|&nbsp;)*<\/p>/sm', '', $text);
		
		$sp = preg_split('/\[QUESTION\s*(\d+)\]/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		$n = 0;
		$out = '';
		
		while (isset($sp[$n])) {
			$text = trim($sp[$n]);
			$qn = array($sp[$n+1]);
			$n+=2;
			while (isset($sp[$n]) && trim($sp[$n])=='' && isset($sp[$n+1])) {
				$qn[1] = $sp[$n+1];
				$n+=2;
			}
			if (isset($sp[$n]) && !isset($sp[$n+1])) { //last item in the set
				$text .= trim($sp[$n]);
				$n++;
			}
			$out .= '<p>';
			if (count($qn)==1) {
				$out .= '[Q '.$qn[0].']';
			} else {
				$out .= '[Q '.implode('-',$qn).']';
			}
			$out .= '</p>';
			
			$out .= $text;
		}	
		$intro = $out;
	}
	$intro = addslashes($intro);
	$newitemorder = implode(',',$newaitems);
	$query = "UPDATE imas_assessments SET itemorder='$newitemorder',intro='$intro' WHERE id='$newaid'";
	mysql_query($query) or die("Query failed : $query" . mysql_error());
	
	$query = "INSERT INTO imas_items (courseid,itemtype,typeid) ";
	$query .= "VALUES ('$cid','Assessment',$newaid)";
	mysql_query($query) or die("Query failed :$query " . mysql_error());
	$newitemid = mysql_insert_id();
	
	$query = "SELECT blockcnt,itemorder FROM imas_courses WHERE id='$cid';";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$blockcnt = mysql_result($result,0,0);
	$items = unserialize(mysql_result($result,0,1));
	$items[] = $newitemid;
	$itemorder = addslashes(serialize($items));
	$query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt' WHERE id='$cid'";
	mysql_query($query) or die("Query failed : $query" . mysql_error());
	$pagetitle = "Merge Assessments";

	$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> &gt; Merge Assessments";

	require("../header.php");
	echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
	echo '<div class="pagetitle"><h2>Merge Assessments</h2></div>';
	echo '<p>Merge complete</p>';
	require("../footer.php");
	exit;
		
} else {	
	$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$itemorder = unserialize(mysql_result($result,0,0));
	$itemsimporder = array();
	function flattenitems($items,&$addto) {
		global $itemsimporder;
		foreach ($items as $item) {
			if (is_array($item)) {
				flattenitems($item['items'],$addto);
			} else {
				$addto[] = $item;
			}
		}
	}
	flattenitems($itemorder,$itemsimporder);
	
	$itemsassoc = array();
	$query = "SELECT id,typeid FROM imas_items WHERE courseid='$cid' AND itemtype='Assessment'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$itemsassoc[$row[0]] = $row[1];
	}
	
	
	$query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$assess = array();
	while ($row = mysql_fetch_row($result)) {
		$assess[$row[0]] = $row[1];
	}
	
	$pagetitle = "Merge Assessments";

	$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> &gt; Merge Assessments";

	require("../header.php");
	echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
	echo '<div class="pagetitle"><h2>Merge Assessments</h2></div>';
	
	echo '<form method="post" action="mergeassess.php?cid='.$cid.'">';
	echo '<p><b>Number the assessments you want to merge into a new assessment</b>.  Note that assessment settings and summary will be taken from the first assessment.</p>';
	echo '<ul>';
	foreach ($itemsimporder as $item) {
		if (!isset($itemsassoc[$item])) { continue; }
		$id = $itemsassoc[$item];
		echo "<li><input type=\"text\" size=\"2\" name=\"mergefrom[$id]\" />{$assess[$id]}</li>";
	}
	echo '</ul>';
	echo '<p>Include assessment name as headers in intro? <input type="checkbox" name="nameasheader" value="1"/></p>';
	echo '<p>Intro merge type:<br/><input type="radio" name="mergetype" value="0" checked="checked" />Just merge text (and adjust existing [QUESTION #] tags) ';
	echo '<br/><input type="radio" name="mergetype" value="1" /> Add Embed [QUESTION #] tags ';
	echo ' <br/><input type="radio" name="mergetype" value="2" /> Add Skip Around [Q #] tags ';
	echo ' <br/><input type="radio" name="mergetype" value="3" /> Merge text, convert [QUESTION #] tags to Skip Around [Q #] tags ';
	echo ' <br/><input type="checkbox" name="addpages" value="1" />Add Page [PAGE] tags </p> ';
	echo '<input type="submit" value="Go">';
	echo '</form>';
	require("../footer.php");
}
