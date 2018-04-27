<?php
//IMathAS:  Function used to show category breakdown of scores
//Called from showtest and gradebook
//(c) 2006 David Lippman
function catscores($quests,$scores,$defptsposs,$defoutcome=0,$cid) {
	global $DBH;
	foreach ($quests as $i=>$q) {
		$quests[$i] = intval($q);  //sanitize
	}
	$qlist = implode(',',$quests);
	//DB $query = "SELECT id,category,points FROM imas_questions WHERE id IN ($qlist)";
 	//DB $result = mysql_query($query) or die("Query failed : $query; " . mysql_error());
	$stm = $DBH->query("SELECT id,category,points FROM imas_questions WHERE id IN ($qlist)"); //sanitized above - safe
	$cat = array();
	$pospts = array();
	$tolookup = array(intval($defoutcome));
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if (is_numeric($row[1]) && $row[1]==0 && $defoutcome!=0) {
			$cat[$row[0]] = $defoutcome;
		} else {
			$cat[$row[0]] = $row[1];
		}

		if (is_numeric($row[1]) && $row[1]>0) {
			$tolookup[] = intval($row[1]);
		}
		if ($row[2] == 9999) {
			$pospts[$row[0]] = $defptsposs;
		} else {
			$pospts[$row[0]] = $row[2];
		}
	}

	$outcomenames = array();
	$outcomenames[0] = "Uncategorized";
	if (count($tolookup)>0) {
		$lulist = implode(',',$tolookup);
		//DB $query = "SELECT id,name FROM imas_outcomes WHERE id IN ($lulist)";
		//DB $result = mysql_query($query) or die("Query failed : $query; " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT id,name FROM imas_outcomes WHERE id IN ($lulist)");  //santitized above - safe
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$outcomenames[$row[0]] = $row[1];
		}

		//DB $query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		if ($row[0]=='') {
			$outcomes = array();
		} else {
			$outcomes = unserialize($row[0]);
			if ($outcomes===false) {
				$outcomes = array();
			}
		}
	}

	$catscore = array();
	$catposs = array();
	for ($i=0; $i<count($quests); $i++) {
		$pts = getpts($scores[$i]);
		if ($pts<0) { $pts = 0;}
		$catscore[$cat[$quests[$i]]] += $pts;
		$catposs[$cat[$quests[$i]]] += $pospts[$quests[$i]];
	}
	echo "<h4>", _('Categorized Score Breakdown'), "</h4>\n";
	echo "<table cellpadding=5 class=gb><thead><tr><th>", _('Category'), "</th><th>", _('Points Earned / Possible (Percent)'), "</th></tr></thead><tbody>\n";
	$alt = 0;
	function printoutcomes($arr,$ind, $outcomenames, $catscore, $catposs) {
		$out = '';
		$totpts = 0;  $totposs = 0;
		foreach ($arr as $oi) {
			if (is_array($oi)) {
				list($outc, $subpts, $subposs) = printoutcomes($oi['outcomes'],$ind+1,$outcomenames,$catscore, $catposs);
				if ($outc!='') {
				  $out .= '<tr><td><span class="ind'.Sanitize::onlyInt($ind).'"><b>'.Sanitize::encodeStringForDisplay($oi['name']).'</b></span></td>';
					if ($subposs>0) {
					  $out .= '<td><div>'.Sanitize::onlyFloat($subpts).' / '.Sanitize::onlyFloat($subposs).' ('.round(100*$subpts/$subposs,1).'%)</div></td>';
					} else {
						$out .= '<td><div>-</div></td>';
					}
					$out .= '</tr>';
					$out .= $outc;
				}
				$totpts += $subpts;
				$totposs += $subposs;
			} else {
				if (isset($catscore[$oi])) {
				  $out .= '<tr><td><span class="ind'.Sanitize::onlyInt($ind).'">'.Sanitize::encodeStringForDisplay($outcomenames[$oi]).'</span></td>';
					$pc = round(100*$catscore[$oi]/$catposs[$oi],1);
					$out .= "<td>" . Sanitize::onlyFloat($catscore[$oi]) . " / " . Sanitize::onlyFloat($catposs[$oi]) . " ($pc%)</td></tr>\n";
					$totpts += $catscore[$oi];
					$totposs += $catposs[$oi];
				}
			}
		}
		return array($out, $totpts, $totposs);
	}
	if (count($tolookup)>0) {
		list($outc, $totpts, $totposs) = printoutcomes($outcomes, 0, $outcomenames, $catscore, $catposs);
		$outc = preg_split('/<tr/',$outc);
		for ($i=1;$i<count($outc);$i++) {
			if ($alt==0) {echo '<tr class="even"'; $alt=1;} else {echo '<tr class="odd"'; $alt=0;}
			echo $outc[$i];
		}
	}
	$assess_name_stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id AND courseid=:courseid LIMIT 1");
	foreach (array_keys($catscore) as $category) {
		if (is_numeric($category)) {
			continue;
		} elseif (0==strncmp($category,"AID-",4)) { //category is another assessment
			$categoryaid=intval(substr($category,4));
			//DB $query = "SELECT name FROM imas_assessments WHERE id='$categoryaid' AND courseid='$cid' LIMIT 1";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $assessmentname = mysql_result($result, 0, 0);
			$assess_name_stm->execute(array(':id'=>$categoryaid, ':courseid'=>$cid));
			$assessmentname = $assess_name_stm->fetchColumn(0);
			//link to the other assessment
			$catname="<a href='../assessment/showtest.php?cid=$cid&id=$categoryaid' >".Sanitize::encodeStringForDisplay($assessmentname)."</a>";
		} else {
			$catname = Sanitize::encodeStringForDisplay($category);
		}
		if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
		$pc = round(100*$catscore[$category]/$catposs[$category],1);
		echo "<td>$catname</td><td>" . Sanitize::encodeStringForDisplay($catscore[$category]) . " / " . Sanitize::encodeStringForDisplay($catposs[$category]) . " (" . Sanitize::encodeStringForDisplay($pc) . " %)</td></tr>\n";
	}
	echo "</tbody></table>\n";

}

?>
