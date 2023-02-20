<?php
//IMathAS:  Categorize questions used in an assessment
//(c) 2006 David Lippman

	require("../init.php");


	$aid = Sanitize::onlyInt($_GET['aid']);
	$cid = Sanitize::courseId($_GET['cid']);
    if (!empty($_GET['from']) && $_GET['from'] == 'addq2') {
        $addq = 'addquestions2';
        $from = 'addq2';
    } else {
        $addq = 'addquestions';
        $from = 'from=addq';
    }

	if (isset($_GET['record'])) {

		$upd_stm = $DBH->prepare("UPDATE imas_questions SET category=:category WHERE id=:id");
		$stm = $DBH->prepare("SELECT id,category FROM imas_questions WHERE assessmentid=:assessmentid");
		$stm->execute(array(':assessmentid'=>$aid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$upd_category = Sanitize::stripHtmlTags($_POST[$row[0]]);
			if ($row[1] != $_POST[$row[0]]) {
				$upd_stm->execute(array(':category'=>$upd_category, ':id'=>$row[0]));
			}
		}
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/$addq.php?cid=$cid&aid=$aid&r=" . Sanitize::randomQueryStringParam());

		exit;
	}

	$pagetitle = _("Categorize Questions");
	$testqpage = ($courseUIver>1) ? 'testquestion2.php' : 'testquestion.php';
	require("../header.php");
	$warn_cat=_("Are you SURE you want to reset all categories to Uncategorized/Default?");
	$custom=_("Custom");
	echo <<<END
<script>
function addcategory() {
	var name = document.getElementById("newcat").value;
	$('select optgroup[label=$custom]').append('<option value="'+name+'">'+name+'</option>');
	document.getElementById("newcat").value='';
}
function quickpick() {
	$('select.qsel').each(function() {
		if ($(this).val()==0) {
			$(this).find('optgroup[label='+document.getElementById("label").value+'] option:first').prop('selected',true);
		}
	});
}

function massassign() {
	var val = $('#masssel').val();
	$('input:checked').each(function() {
		var n = $(this).attr('id').substr(1);
		$('#'+n).val(val);
	});
}

function resetcat() {
	if (confirm('$warn_cat')) {
		$('select.qsel').val(0);
	}
}
function previewq(formn,loc,qn) {
	var addr = '$imasroot/course/$testqpage?cid=$cid&checked=1&qsetid='+qn+'&loc=c'+loc+'&formn='+formn;
	previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));
	previewpop.focus();
}

function getnextprev(formn,loc) {
	var form = document.getElementById(formn);
	var prevq = 0; var nextq = 0; var found=false;
	var prevl = 0; var nextl = 0;
	for (var e = 0; e < form.elements.length; e++) {
		var el = form.elements[e];
		if (typeof el.type == "undefined") {
			continue;
		}
		if (el.type == 'checkbox') {
			if (found) {
				nextq = el.value;
				nextl = el.id;
				break;
			} else if (el.id==loc) {
				found = true;
			} else {
				prevq = el.value;
				prevl = el.id;
			}
		}
	}
	return ([[prevl,prevq],[nextl,nextq]]);
}
</script>
END;
    echo "<div class=breadcrumb>$breadcrumbbase ";
    if (empty($_COOKIE['fromltimenu'])) {
        echo " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
    }
	echo "<a href=\"$addq.php?cid=$cid&aid=$aid\">"._("Add/Remove Questions")."</a> &gt; "._("Categorize Questions")."</div>\n";
	$stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$outcomenames = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$outcomenames[$row[0]] = $row[1];
	}
	$stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$row = $stm->fetch(PDO::FETCH_NUM);
	if ($row[0]=='') {
		$outcomearr = array();
	} else {
		$outcomearr = unserialize($row[0]);
		if (!is_array($outcomearr)) {
			$outcomearr = array();
		}
	}

	$outcomes = array();
	function flattenarr($ar) {
		global $outcomes;
		foreach ($ar as $v) {
			if (is_array($v)) { //outcome group
				$outcomes[] = array($v['name'], 1);
				flattenarr($v['outcomes']);
			} else {
				$outcomes[] = array($v, 0);
			}
		}
	}
	flattenarr($outcomearr);
	$query = "SELECT imas_questions.id,imas_libraries.id,imas_libraries.name FROM imas_questions,imas_library_items,imas_libraries ";
	$query .= "WHERE imas_questions.assessmentid=:assessmentid AND imas_questions.questionsetid=imas_library_items.qsetid AND ";
	$query .= "imas_library_items.libid=imas_libraries.id AND imas_library_items.deleted=0 AND imas_libraries.deleted=0 ORDER BY imas_questions.id";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':assessmentid'=>$aid));
	$libnames = array();
	$questionlibs = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$questionlibs[$row[0]][] = $row[1];
		$libnames[$row[1]] = $row[2];
	}

	//add assessment names as options
	//find the names of assessments these questionsetids appear in
	$query = "SELECT DISTINCT imas_questions.questionsetid AS qsetid,imas_assessments.id AS aid,imas_assessments.name ";
	$query .= "FROM imas_questions INNER JOIN imas_assessments ";
	$query .= "ON imas_questions.assessmentid=imas_assessments.id ";
	$query .= "AND imas_questions.questionsetid = ANY (SELECT imas_questions.questionsetid FROM imas_questions WHERE imas_questions.assessmentid=:assessmentid) ";
	$query .= "AND imas_assessments.courseid=:courseid ";
	$query .= "ORDER BY aid";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':assessmentid'=>$aid, ':courseid'=>$cid));
	$assessmentnames = array();
	$qsetidassessment = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		//store the relevent assessment names
		$assessmentnames[$row['aid']] = $row['name'];
		if ($row['aid']!=$aid) {
			//remember this other assignment which uses this same questionsetid
				$qsetidassessment[$row['qsetid']][] = $row['aid'];
		}
	}
	$query = "SELECT iq.id,iqs.id AS qsetid,iq.category,iqs.description FROM imas_questions AS iq,imas_questionset as iqs";
	$query .= " WHERE iq.questionsetid=iqs.id AND iq.assessmentid=:assessmentid";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':assessmentid'=>$aid));
	$descriptions = array();
	$category = array();
	$extracats = array();
	$qsetids = array();
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$descriptions[$line['id']] = $line['description'];
		$category[$line['id']] = $line['category'];
		//remember which questionsetid corresponds to this question
		$qsetids[$line['id']]=$line['qsetid'];
		if (!is_numeric($line['category']) && 0!=strncmp($line['category'],"AID-",4) && trim($line['category'])!='' && !in_array($line['category'],$extracats)) {
			$extracats[] = $line['category'];
		}
	}
	$stm = $DBH->prepare("SELECT itemorder FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	$row = $stm->fetch(PDO::FETCH_NUM);
	$itemarrinit = explode(',',$row[0]);
	$itemarr = array();
	$itemnum = array();
	foreach ($itemarrinit as $k=>$v) {
		if (($p=strpos($v,'~'))!==false) {
			$parts = explode('~', $v);
			for ($i=1;$i < count($parts);$i++) {
				$itemarr[] = $parts[$i];
				$itemnum[$parts[$i]] = ($k+1).'-'.$i;
			}
		} else {
			$itemarr[] = $v;
			$itemnum[$v] = ($k+1);
		}
	}

	echo '<div id="headercategorize" class="pagetitle"><h1>'._('Categorize Questions').'</h1></div>';
	echo "<form id=\"selform\" method=post action=\"categorize.php?aid=$aid&cid=$cid&from=$from&record=true\">";
	echo _('Check').': <a href="#" onclick="$(\'input[type=checkbox]\').prop(\'checked\',true);return false;">'._('All').'</a> ';
	echo '<a href="#" onclick="$(\'input[type=checkbox]\').prop(\'checked\',false);return false;">'._('None').'</a>';
	echo '<table class="gb"><thead><tr><th></th><th>Q#</th><th>'._('Description').'</th><th class="sr-only">'._('Preview').'</th><th>'._('Category').'</th></tr></thead><tbody>';

	foreach($itemarr as $qid) {
        if (!isset($qsetids[$qid])) { continue; }
		echo "<tr><td><input type=\"checkbox\" id=\"c".Sanitize::onlyInt($qid)."\" value=\"" . Sanitize::encodeStringForDisplay($qsetids[$qid]) . "\"/></td>";
		echo "<td>Q" . Sanitize::encodeStringForDisplay($itemnum[$qid]) . '</td><td>';
		echo Sanitize::encodeStringForDisplay($descriptions[$qid]) . "</td>";
		printf("<td><input type=button value=\""._("Preview")."\" onClick=\"previewq('selform', %d, %d);\"/></td>", Sanitize::onlyInt($qid), Sanitize::onlyInt($qsetids[$qid]));
		echo "<td><select id=\"".Sanitize::onlyInt($qid)."\" name=\"" . Sanitize::onlyInt($qid) . "\" class=\"qsel\">";
		echo "<option value=\"0\" ";
		if ($category[$qid] == 0) { echo "selected=1";}
		echo ">"._("Uncategorized or Default")."</option>\n";
		if (count($outcomes)>0) {
			echo '<optgroup label="'._('Outcomes').'"></optgroup>';
		}
		$ingrp = false;
		$issel = false;
		foreach ($outcomes as $oc) {
			if ($oc[1]==1) {//is group
				if ($ingrp) { echo '</optgroup>';}
				echo '<optgroup label="'.Sanitize::encodeStringForDisplay($oc[0]).'">';
				$ingrp = true;
			} else {
				echo '<option value="' . Sanitize::encodeStringForDisplay($oc[0]) . '" ';
				if ($category[$qid] == $oc[0]) { echo "selected=1"; $issel = true;}
				echo '>' . Sanitize::encodeStringForDisplay($outcomenames[$oc[0]]) . '</option>';
			}
		}
		if ($ingrp) { echo '</optgroup>';}
		if (isset($questionlibs[$qid])) {
			echo '<optgroup label="'._('Libraries').'">';
			foreach ($questionlibs[$qid] as $qlibid) {
				echo "<option value=\"" . Sanitize::encodeStringForDisplay($libnames[$qlibid]) . "\" ";
				if ($category[$qid] == $libnames[$qlibid] && !$issel) { echo "selected=1"; $issel= true;}
				echo ">" . Sanitize::encodeStringForDisplay($libnames[$qlibid]) . "</option>\n";
			}
			echo '</optgroup>\n';
		}

		if (isset($qsetidassessment[$qsetids[$qid]])) {
			echo '<optgroup label="'._('Assessments').'">';
			//add assessment names as options
			foreach ($qsetidassessment[$qsetids[$qid]] as $qaid) {
				echo '<option value="AID-'.$qaid.'" ';
				if ($category[$qid] == "AID-$qaid" && !$issel) { echo "selected=1"; $issel= true;}
				echo ">{$assessmentnames[$qaid]}</option>\n";
			}
			echo '</optgroup>\n';
		}

		echo '<optgroup label="'._('Custom').'">';
		foreach ($extracats as $cat) {
			echo "<option value=\"" . Sanitize::encodeStringForDisplay($cat) . "\" ";
			if ($category[$qid] == $cat && !$issel) { echo "selected=1";$issel = true;}
			echo ">" . Sanitize::encodeStringForDisplay($cat) . "</option>\n";
		}
		echo '</optgroup>';
		echo "</select></td></tr>\n";
	}
	echo "</tbody></table>\n";
	if (count($outcomes)>0) {
		echo '<p>'._('Apply outcome to selected').': <select id="masssel">';
		$ingrp = false;
		$issel = false;
		foreach ($outcomes as $oc) {
			if ($oc[1]==1) {//is group
				if ($ingrp) { echo '</optgroup>';}
				echo '<optgroup label="'.Sanitize::encodeStringForDisplay($oc[0]).'">';
				$ingrp = true;
			} else {
				echo '<option value="' . Sanitize::encodeStringForDisplay($oc[0]) . '">' . Sanitize::encodeStringForDisplay($outcomenames[$oc[0]]) . '</option>';
			}
		}
		if ($ingrp) { echo '</optgroup>';}
		echo '</select> <input type="button" value="Assign" onclick="massassign()"/></p>';

	}
echo "<p>"._("Select first listed")." <select id=\"label\">\n";
echo "<option value=\""._("Libraries")."\">"._("Libraries")."</option>";
echo "<option value=\""._("Assessments")."\">"._("Assessments")."</option>";
echo "</select>\n";
echo _("for all uncategorized questions").": <input type=button value=\""._("Quick Pick")."\" label=\"XXX\" onclick=\"quickpick()\"></p>\n";

	echo "<p>"._("Add new category to lists").": <input type=text id=\"newcat\" size=40> ";
	echo "<input type=button value=\""._("Add Category")."\" onclick=\"addcategory()\"></p>\n";
	echo '<p><input type=submit value="'._('Record Categorizations').'"> '._('and return to the Add/Remove Questions page').'.  <input type="button" class="secondarybtn" value="'._('Reset').'" onclick="resetcat()"/></p>';
	echo "</form>\n";

	require("../footer.php");

?>
