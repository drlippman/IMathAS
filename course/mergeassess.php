<?php
require("../init.php");


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
	//DB $query = "SELECT name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,allowlate,exceptionpenalty,noprint,avail,groupmax,endmsg,deffeedbacktext,eqnhelper,caltag,calrtag,reqscore,reqscoreaid FROM imas_assessments WHERE id='{$seta[0]}'";
	//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	//DB $row = mysql_fetch_row($result);
	$fieldstocopy = 'name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,allowlate,exceptionpenalty,noprint,avail,groupmax,endmsg,deffeedbacktext,eqnhelper,caltag,calrtag,reqscore,reqscoreaid';
	$stm = $DBH->prepare("SELECT $fieldstocopy FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$seta[0]));
	$row = $stm->fetch(PDO::FETCH_ASSOC);
	$defpoints = $row['defpoints'];
	//DB $row[0] .= ' - merge result';
	$row['name'] .= ' - merge result';
	$row['courseid'] = $cid;
	//DB $row = "'".implode("','",addslashes_deep($row))."'";
	//DB $query = "INSERT INTO imas_assessments (courseid,name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,allowlate,exceptionpenalty,noprint,avail,groupmax,endmsg,deffeedbacktext,eqnhelper,caltag,calrtag,reqscore,reqscoreaid) ";
	//DB $query .= "VALUES ('$cid',$row)";
	$fieldlist = implode(',', array_keys($row));
	$placeholders = Sanitize::generateQueryPlaceholders($row);
	$stm = $DBH->prepare("INSERT INTO imas_assessments ($fieldlist) VALUES ($placeholders)");
	$stm->execute(array_values($row));

	//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
	//DB $newaid = mysql_insert_id();
	$newaid = $DBH->lastInsertId();

	$intro = '';
	$newaitems = array();
	$qcnt = 0;

	function incrementqnum($m) {
		global $qcnt;
		return '[QUESTION '.($m[1]+$qcnt).']';
	}

	for ($i=0;$i<count($seta);$i++) {
		//DB $query = "SELECT itemorder,intro,name FROM imas_assessments WHERE id='{$seta[$i]}'";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		$stm = $DBH->prepare("SELECT itemorder,intro,name FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$seta[$i]));
		list($itemorder, $curintro, $thisname) = $stm->fetch(PDO::FETCH_NUM);
		//DB $thisname = mysql_result($result,0,2);
		$thisintro = '';
		if (isset($_POST['addpages'])) {
			$thisintro .= "<p>[PAGE $thisname]</p>";
		}
		$thisintroraw = $curintro;
		if (($introjson=json_decode($thisintroraw,true))!==null) { //is json intro
			$thisintro .=  $introjson[0];
		} else {
			$thisintro .= $thisintroraw;
		}
		$thisqcnt = 0;
		//DB if (trim(mysql_result($result,0,0))!='') {
		if (trim($itemorder)!='') {
			//DB $aitems = explode(',',mysql_result($result,0,0));
			$aitems = explode(',', $itemorder);
			foreach ($aitems as $k=>$aitem) {
				if (strpos($aitem,'~')===FALSE) {
					///$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
					///$query .= "SELECT '$newaid',questionsetid,points,attempts,penalty,category FROM imas_questions WHERE id='$aitem'";
					//mysql_query($query) or die("Query failed :$query " . mysql_error());
					//DB $query = "SELECT questionsetid,points,attempts,penalty,category,rubric FROM imas_questions WHERE id='$aitem'";
					//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					//DB $row = mysql_fetch_row($result);
					$stm = $DBH->prepare("SELECT questionsetid,points,attempts,penalty,category,rubric FROM imas_questions WHERE id=:id");
					$stm->execute(array(':id'=>$aitem));
					$row = $stm->fetch(PDO::FETCH_ASSOC);
					//DB $rubric = array_pop($row);
					$rubric = $row['rubric'];
					//DB $row = "'".implode("','",addslashes_deep($row))."'";
					//DB $query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
					//DB $query .= "VALUES ('$newaid',$row)";
					//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
					//DB $newid = mysql_insert_id();
					$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
					$query .= "VALUES (:assessmentid,:questionsetid,:points,:attempts,:penalty,:category)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':assessmentid'=>$newaid, ':questionsetid'=>$row['questionsetid'], ':points'=>$row['points'],
						':attempts'=>$row['attempts'], ':penalty'=>$row['penalty'], ':category'=>$row['category']));
					$newid = $DBH->lastInsertId();
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
						//DB $query = "SELECT questionsetid,points,attempts,penalty,category,rubric FROM imas_questions WHERE id='$subi'";
						//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
						//DB $row = mysql_fetch_row($result);
						$stm = $DBH->prepare("SELECT questionsetid,points,attempts,penalty,category,rubric FROM imas_questions WHERE id=:id");
						$stm->execute(array(':id'=>$subi));
						$row = $stm->fetch(PDO::FETCH_ASSOC);
						//DB $rubric = array_pop($row);
						$rubric = $row['rubric'];
						//DB $row = "'".implode("','",addslashes_deep($row))."'";
						//DB $query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
						//DB $query .= "VALUES ('$newaid',$row)";
						//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
						$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
						$query .= "VALUES (:assessmentid,:questionsetid,:points,:attempts,:penalty,:category)";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':assessmentid'=>$newaid, ':questionsetid'=>$row['questionsetid'], ':points'=>$row['points'],
							':attempts'=>$row['attempts'], ':penalty'=>$row['penalty'], ':category'=>$row['category']));

						//DB $newid = mysql_insert_id();
						$newid = $DBH->lastInsertId();
						$newsub[] = $newid;
					}
					$newaitems[] = implode('~',$newsub);
				}
			}
		}
		if (isset($_POST['nameasheader'])) {
			$thisintro = '<h2>'.$thisname.'</h2>'.$thisintro;
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
	//DB $intro = addslashes($intro);
	$newitemorder = implode(',',$newaitems);
	//DB $query = "UPDATE imas_assessments SET itemorder='$newitemorder',intro='$intro' WHERE id='$newaid'";
	//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder,intro=:intro WHERE id=:id");
	$stm->execute(array(':itemorder'=>$newitemorder, ':intro'=>$intro, ':id'=>$newaid));

	//update points poss
	require_once("../includes/updateptsposs.php");
	updatePointsPossible($newaid, $newitemorder, $defpoints);
	
	//DB $query = "INSERT INTO imas_items (courseid,itemtype,typeid) ";
	//DB $query .= "VALUES ('$cid','Assessment',$newaid)";
	//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
	$query = "INSERT INTO imas_items (courseid,itemtype,typeid) ";
	$query .= "VALUES (:courseid, 'Assessment', :typeid)";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':typeid'=>$newaid));
	//DB $newitemid = mysql_insert_id();
	$newitemid = $DBH->lastInsertId();

	//DB $query = "SELECT blockcnt,itemorder FROM imas_courses WHERE id='$cid';";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $blockcnt = mysql_result($result,0,0);
	//DB $items = unserialize(mysql_result($result,0,1));
	$stm = $DBH->prepare("SELECT blockcnt,itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	list($blockcnt, $itemorder) = $stm->fetch(PDO::FETCH_NUM);
	$items = unserialize($itemorder);
	$items[] = $newitemid;
	//DB $itemorder = addslashes(serialize($items));
	$itemorder = serialize($items);
	//DB $query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt' WHERE id='$cid'";
	//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt WHERE id=:id");
	$stm->execute(array(':itemorder'=>$itemorder, ':blockcnt'=>$blockcnt, ':id'=>$cid));
	$pagetitle = "Merge Assessments";

	$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Merge Assessments";

	require("../header.php");
	echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
	echo '<div class="pagetitle"><h1>Merge Assessments</h1></div>';
	echo '<p>Merge complete</p>';
	require("../footer.php");
	exit;

} else {
	//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	//DB $itemorder = unserialize(mysql_result($result,0,0));
	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$itemorder = unserialize($stm->fetchColumn(0));
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
	//DB $query = "SELECT id,typeid FROM imas_items WHERE courseid='$cid' AND itemtype='Assessment'";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT id,typeid FROM imas_items WHERE courseid=:courseid AND itemtype='Assessment'");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$itemsassoc[$row[0]] = $row[1];
	}


	//DB $query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
	$stm->execute(array(':courseid'=>$cid));
	$assess = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$assess[$row[0]] = $row[1];
	}

	$pagetitle = "Merge Assessments";
	$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Merge Assessments";

	require("../header.php");
	echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
	echo '<div class="pagetitle"><h1>Merge Assessments</h1></div>';

	echo '<form method="post" action="mergeassess.php?cid='.$cid.'">';
	echo '<p><b>Number the assessments you want to merge into a new assessment</b>.  Note that assessment settings and summary will be taken from the first assessment.</p>';
	echo '<ul>';
	foreach ($itemsimporder as $item) {
		if (!isset($itemsassoc[$item])) { continue; }
		$id = $itemsassoc[$item];
		echo "<li><input type=\"text\" size=\"2\" name=\"mergefrom[" . Sanitize::onlyInt($id) . "]\" />" . Sanitize::encodeStringForDisplay($assess[$id]) . "</li>";
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
