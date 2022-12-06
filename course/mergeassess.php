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
	//$fieldstocopy = 'name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,allowlate,exceptionpenalty,noprint,avail,groupmax,endmsg,deffeedbacktext,eqnhelper,caltag,calrtag,reqscore,reqscoreaid';
	$fieldstocopy = 'name,summary,intro,startdate,enddate,reviewdate,LPcutoff,';
	$fieldstocopy .= 'timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,';
	$fieldstocopy .= 'defpenalty,itemorder,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,';
	$fieldstocopy .= 'allowlate,exceptionpenalty,noprint,avail,groupmax,isgroup,groupsetid,endmsg,';
	$fieldstocopy .= 'deffeedbacktext,eqnhelper,caltag,calrtag,tutoredit,posttoforum,msgtoinstr,';
	$fieldstocopy .= 'istutorial,viddata,reqscore,reqscoreaid,reqscoretype,ancestors,defoutcome,';
	$fieldstocopy .= 'posttoforum,ptsposs,extrefs,submitby,showscores,showans,viewingb,scoresingb,';
	$fieldstocopy .= 'ansingb,defregens,defregenpenalty,ver,keepscore,overtime_grace,overtime_penalty';
	$stm = $DBH->prepare("SELECT $fieldstocopy FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$seta[0]));
	$row = $stm->fetch(PDO::FETCH_ASSOC);
	$defpoints = $row['defpoints'];
	$row['name'] .= ' - merge result';
	$row['courseid'] = $cid;
	$fieldlist = implode(',', array_keys($row));
	$placeholders = Sanitize::generateQueryPlaceholders($row);
	$stm = $DBH->prepare("INSERT INTO imas_assessments ($fieldlist) VALUES ($placeholders)");
	$stm->execute(array_values($row));
	$newaid = $DBH->lastInsertId();

	$intro = '';
	$newaitems = array();
	$qcnt = 0;

	function incrementqnum($m) {
		global $qcnt;
		return '[QUESTION '.($m[1]+$qcnt).']';
	}

	for ($i=0;$i<count($seta);$i++) {
		$stm = $DBH->prepare("SELECT itemorder,intro,name FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$seta[$i]));
		list($itemorder, $curintro, $thisname) = $stm->fetch(PDO::FETCH_NUM);
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
		if (trim($itemorder)!='') {
			$aitems = explode(',', $itemorder);
			foreach ($aitems as $k=>$aitem) {
				if (strpos($aitem,'~')===FALSE) {
					///$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
					///$query .= "SELECT '$newaid',questionsetid,points,attempts,penalty,category FROM imas_questions WHERE id='$aitem'";
					//mysql_query($query) or die("Query failed :$query " . mysql_error());
					$stm = $DBH->prepare("SELECT questionsetid,points,attempts,penalty,category,rubric FROM imas_questions WHERE id=:id");
					$stm->execute(array(':id'=>$aitem));
					$row = $stm->fetch(PDO::FETCH_ASSOC);
					$rubric = $row['rubric'];
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
						$stm = $DBH->prepare("SELECT questionsetid,points,attempts,penalty,category,rubric FROM imas_questions WHERE id=:id");
						$stm->execute(array(':id'=>$subi));
						$row = $stm->fetch(PDO::FETCH_ASSOC);
						$rubric = $row['rubric'];
						$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
						$query .= "VALUES (:assessmentid,:questionsetid,:points,:attempts,:penalty,:category)";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':assessmentid'=>$newaid, ':questionsetid'=>$row['questionsetid'], ':points'=>$row['points'],
							':attempts'=>$row['attempts'], ':penalty'=>$row['penalty'], ':category'=>$row['category']));
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
	$newitemorder = implode(',',$newaitems);
	$stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder,intro=:intro WHERE id=:id");
	$stm->execute(array(':itemorder'=>$newitemorder, ':intro'=>$intro, ':id'=>$newaid));

	//update points poss
	require_once("../includes/updateptsposs.php");
	updatePointsPossible($newaid, $newitemorder, $defpoints);
	$query = "INSERT INTO imas_items (courseid,itemtype,typeid) ";
	$query .= "VALUES (:courseid, 'Assessment', :typeid)";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':typeid'=>$newaid));
	$newitemid = $DBH->lastInsertId();
	$stm = $DBH->prepare("SELECT blockcnt,itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	list($blockcnt, $itemorder) = $stm->fetch(PDO::FETCH_NUM);
	$items = unserialize($itemorder);
	$items[] = $newitemid;
	$itemorder = serialize($items);
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
	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$itemorder = unserialize($stm->fetchColumn(0));
	$itemsimporder = array();
	function flattenitems($items,&$addto) {
		global $itemsimporder;
		foreach ($items as $item) {
			if (is_array($item)) {
                if (!empty($item['items'])) {
				    flattenitems($item['items'],$addto);
                }
			} else {
				$addto[] = $item;
			}
		}
	}
	flattenitems($itemorder,$itemsimporder);

	$itemsassoc = array();
	$stm = $DBH->prepare("SELECT id,typeid FROM imas_items WHERE courseid=:courseid AND itemtype='Assessment'");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$itemsassoc[$row[0]] = $row[1];
	}
	$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
	$stm->execute(array(':courseid'=>$cid));
	$assess = array();
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
	echo '<input type="hidden" name="mergetype" value="0"/>';

	echo '<input type="submit" value="Go">';
	echo '</form>';
	require("../footer.php");
}
