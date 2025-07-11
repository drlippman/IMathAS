<?php
//IMathAS:  Drill Assess creator (rough version)
//(c) 2011 David Lippman

require_once "../init.php";
require_once "../includes/htmlutil.php";
require_once "../includes/parsedatetime.php";


if (!isset($teacherid)) {
	echo 'You are not authorized to view this page';
	exit;
}

$pagetitle = "Add/Modify Drill Assessment";
$cid = Sanitize::courseId($_GET['cid']);

if (isset($_GET['tb'])) {
	$totb = $_GET['tb'];
} else {
	$totb = 'b';
}
$block = $_GET['block'] ?? '0';
if (isset($_GET['daid'])) {
    $daid = Sanitize::onlyInt($_GET['daid']);
    $stm = $DBH->prepare("SELECT * FROM imas_drillassess WHERE id=:id AND courseid=:courseid");
    $stm->execute(array(':id'=>$daid, ':courseid'=>$cid));
}
if (!isset($_GET['daid']) || $stm->rowCount()==0) {
	//new to invalid
	$itemdescr = array();
	$itemids = array();
	$scoretype = 't';
	$showtype = '4';
	$n = 30;
	$showtostu = 7;
	$daid = 0;
	$drillname = "";
	$drillsummary = "";
	$startdate = time();
	$enddate = time() + 7*24*60*60;
	$avail = 1;
    $caltag = 'D';
} else {
	$dadata = $stm->fetch(PDO::FETCH_ASSOC);
	$n = $dadata['n'];
	$showtype = $dadata['showtype'];
	$scoretype = $dadata['scoretype'];
	$showtostu = $dadata['showtostu'];
	$startdate= $dadata['startdate'];
	$enddate= $dadata['enddate'];
	$avail= $dadata['avail'];
	$drillname= $dadata['name'];
	$drillsummary= $dadata['summary'];
	$caltag = $dadata['caltag'];

	if ($dadata['itemids']=='') {
		$itemids = array();
	} else {
		$itemids = explode(',',$dadata['itemids']);
	}
	if ($dadata['itemdescr']=='') {
		$itemdescr = array();
	} else {
		$itemdescr = explode(',',$dadata['itemdescr']);
	}
}

if (isset($_GET['clearatt'])) {
	$stm = $DBH->prepare("DELETE FROM imas_drillassess_sessions WHERE drillassessid=:drillassessid");
	$stm->execute(array(':drillassessid'=>$daid));
	header(sprintf('Location: %s/course/adddrillassess.php?cid=%s&daid=%d&r=%s', $GLOBALS['basesiteurl'], $cid, $daid, Sanitize::randomQueryStringParam()));
	exit;
}
if (isset($_GET['record'])) {
	$DBH->beginTransaction();
	if ($_POST['avail']==1) {
		if ($_POST['sdatetype']=='0') {
			$startdate = 0;
		} else {
			$startdate = parsedatetime($_POST['sdate'], $_POST['stime'],0);
		}
		if ($_POST['edatetype']=='2000000000') {
			$enddate = 2000000000;
		} else {
			$enddate = parsedatetime($_POST['edate'], $_POST['etime'],2000000000);
		}
	} else {
		$startdate = 0;
		$enddate =  2000000000;
	}
	$_POST['title'] = Sanitize::stripHtmlTags($_POST['title']);
    $_POST['summary'] = Sanitize::trimEmptyPara($_POST['summary']);
	if ($_POST['summary']=='<p>Enter summary here (displays on course page)</p>' || $_POST['summary']=='<p></p>') {
		$_POST['summary'] = '';
	} else {
		$_POST['summary'] = Sanitize::incomingHtml($_POST['summary']);
	}

	if (isset($_POST['descr'])) {
		foreach ($_POST['descr'] as $k=>$v) {
			$itemdescr[$k] = str_replace(',','',$v);
		}
	}

	$beentaken = isset($_POST['beentaken']);

	if (!$beentaken) {
		$newitemids = array();
		$newitemdescr = array();
		if (isset($_POST['order'])) {
			asort($_POST['order']);
			foreach ($_POST['order'] as $id=>$ord) {
				if (!isset($_POST['delitem'][$id])) {
					$newitemids[] = $itemids[$id];
					$newitemdescr[] = $itemdescr[$id];
				}
			}
		}

		$itemids = array_values($newitemids);
		$itemdescr = array_values($newitemdescr);
	}
	$classbests = array();
	$updatebests = false;
	//if (isset($_POST['idstoadd']) && trim($_POST['idstoadd'])!='') {
	if (isset($_POST['nchecked'])) {
		$toadd = $_POST['nchecked'];
		//$toadd = explode(',',$_POST['idstoadd']);
		foreach ($toadd as $k=>$v) {
			$toadd[$k] = Sanitize::onlyInt($v);
			if ($toadd[$k]==0) {
				unset($toadd[$k]);
			}
		}
		$toadd_query_placeholders = Sanitize::generateQueryPlaceholders($toadd);
		$query = "SELECT id,description FROM imas_questionset WHERE id IN ($toadd_query_placeholders)";
		$stm = $DBH->prepare($query); //pre-sanitized INTs
		$stm->execute(array_values($toadd));
		$descr = array();
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$descr[$row[0]] = str_replace(',','',$row[1]);
		}
		foreach ($toadd as $k=>$v) {
			$itemids[] = Sanitize::onlyInt($v);
			$itemdescr[] = $descr[$v];
		}
		$classbests = array_fill(0,count($itemids),-1);
		$updatebests = true;
	}

	$n = intval($_POST['n']);
	$scoretype = $_POST['scoretype'];
	$showtype = intval($_POST['showtype']);
	$showtostu = (isset($_POST['showlast'])?1:0) + (isset($_POST['showpbest'])?2:0) + (isset($_POST['showcbest'])?4:0);
	if (isset($_POST['clearbests'])) {
		$classbests = array_fill(0,count($itemids),-1);
		$updatebests = true;
	}
	$itemlist = implode(',',$itemids);
	$descrlist = implode(',',$itemdescr);
	$bestlist = implode(',',$classbests);
	if ($daid==0) {
		$query = "INSERT INTO imas_drillassess (courseid,name,summary,avail,startdate,enddate,itemdescr,itemids,scoretype,showtype,n,classbests,showtostu) VALUES ";
		$query .= "(:courseid, :name, :summary, :avail, :startdate, :enddate, :itemdescr, :itemids, :scoretype, :showtype, :n, :classbests, :showtostu)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid, ':name'=>$_POST['title'], ':summary'=>$_POST['summary'], ':avail'=>$_POST['avail'],
			':startdate'=>$startdate, ':enddate'=>$enddate, ':itemdescr'=>$descrlist, ':itemids'=>$itemlist, ':scoretype'=>$scoretype,
			':showtype'=>$showtype, ':n'=>$n, ':classbests'=>$bestlist, ':showtostu'=>$showtostu));
		$daid = $DBH->lastInsertId();
		$stm = $DBH->prepare("INSERT INTO imas_items (courseid,itemtype,typeid) VALUES (:courseid, 'Drill', :typeid)");
		$stm->execute(array(':courseid'=>$cid, ':typeid'=>$daid));
		$itemid = $DBH->lastInsertId();
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		$items = unserialize($line['itemorder']);

		$blocktree = explode('-',$block);
		$sub =& $items;
		for ($i=1;$i<count($blocktree);$i++) {
			$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
		}
		if ($totb=='b') {
			$sub[] = $itemid;
		} else if ($totb=='t') {
			array_unshift($sub,$itemid);
		}
		$itemorder = serialize($items);
		$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
		$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
	} else {
		if ($beentaken) {
			$query = "UPDATE imas_drillassess SET itemdescr=:itemdescr,showtostu=:showtostu,";
			$query .= "name=:name,summary=:summary,avail=:avail,caltag=:caltag,startdate=:startdate,enddate=:enddate";
			$qarr = array(':itemdescr'=>$descrlist, ':showtostu'=>$showtostu, ':name'=>$_POST['title'], ':summary'=>$_POST['summary'],
				':avail'=>$_POST['avail'], ':caltag'=>$_POST['caltag'], ':startdate'=>$startdate, ':enddate'=>$enddate);
		} else {
			$query = "UPDATE imas_drillassess SET itemdescr=:itemdescr,showtostu=:showtostu,";
			$query .= "name=:name,summary=:summary,avail=:avail,caltag=:caltag,startdate=:startdate,enddate=:enddate,";
			$query .= "itemids=:itemids,scoretype=:scoretype,showtype=:showtype,n=:n";
			$qarr = array(':itemdescr'=>$descrlist, ':showtostu'=>$showtostu, ':itemids'=>$itemlist, ':scoretype'=>$scoretype,
				':showtype'=>$showtype, ':n'=>$n, ':name'=>$_POST['title'], ':summary'=>$_POST['summary'], ':avail'=>$_POST['avail'],
				':caltag'=>$_POST['caltag'], ':startdate'=>$startdate, ':enddate'=>$enddate);

		}
		if ($updatebests) {
			$query .= ",classbests=:classbests";
			$qarr[':classbests'] = $bestlist;
		}
		$query .= " WHERE id=:id";
		$qarr[':id'] = $daid;
		$stm = $DBH->prepare($query);
		$stm->execute($qarr);
		if (!$beentaken) {
			//Delete any instructor attempts to account for possible changes
			$stm = $DBH->prepare("DELETE FROM imas_drillassess_sessions WHERE drillassessid=:drillassessid");
			$stm->execute(array(':drillassessid'=>$daid));
		}
	}

	$DBH->commit();
	if (isset($_POST['save']) && $_POST['save']=='Save') {
		$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
		header(sprintf('Location: %s/course/course.php?cid=%s&r=%s', $GLOBALS['basesiteurl'], $cid.$btf, Sanitize::randomQueryStringParam()));
	} else {
		header(sprintf('Location: %s/course/adddrillassess.php?cid=%s&daid=%d&r=%s', $GLOBALS['basesiteurl'], $cid, $daid, Sanitize::randomQueryStringParam()));
	}
	exit;
}
$query = "SELECT ias.id FROM imas_drillassess_sessions AS ias,imas_students WHERE ";
$query .= "ias.drillassessid=:drillassessid AND ias.userid=imas_students.userid AND imas_students.courseid=:courseid LIMIT 1";
$stm = $DBH->prepare($query);
$stm->execute(array(':drillassessid'=>$daid, ':courseid'=>$cid));
if ($stm->rowCount()>0) {
	$beentaken = true;
} else {
	$beentaken = false;
}

$useeditor = "summary";
$testqpage = ($courseUIver>1) ? 'testquestion2.php' : 'testquestion.php';
$placeinhead = "<script type=\"text/javascript\">
		var previewqaddr = '$imasroot/course/$testqpage?cid=$cid';
		var qsearchaddr = '$imasroot/course/qsearch.php?cid=$cid&did=$daid';
		</script>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/addquestions.js\"></script>";
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/tablesorter.js"></script>';
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/qsearch.js?071125\"></script>";
$placeinhead .= "<link rel=\"stylesheet\" href=\"$staticroot/course/addquestions2.css?v=060823\" type=\"text/css\" />";
require_once "../header.php";

/*  Get data for question searching */
//remember search

if (isset($_SESSION['lastsearchd'.$daid])) {
	$searchterms = trim($_SESSION['lastsearchd'.$daid]);
} else {
	$searchterms = '';
}
if (isset($_SESSION['searchtyped'.$daid])) {
	$searchtype = $_SESSION['searchtyped'.$daid];
} else {
	$searchtype = 'libs';
}
if (isset($_SESSION['searchind'.$daid])) {
	$searchin = $_SESSION['searchind'.$daid];
} else if ($searchtype == 'libs') {
	$searchin = [$userdeflib];
	$_SESSION['searchind'.$daid] = $searchin;
	$_SESSION['lastsearchlibsd'.$daid] = implode(',', $searchin);
} else {
	$searchin = [];
}
require_once '../includes/questionsearch.php';
$search_parsed = parseSearchString($searchterms);
$search_results = searchQuestions($search_parsed, $userid, $searchtype, $searchin, [
	'existing' => $itemids
]);

echo '<script type="text/javascript">';
echo "var curlibs = '".Sanitize::encodeStringForJavascript(implode(',', $searchin))."';";
echo "var cursearchtype = '".Sanitize::simpleString($searchtype)."';";
echo 'var curaid=0; var assessver=2;';
echo 'var curcid='.$cid.';';
echo '</script>';

$hr = floor($coursedeftime/60)%12;
$min = $coursedeftime%60;
$am = ($coursedeftime<12*60)?'am':'pm';
$deftime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
$hr = floor($coursedefstime/60)%12;
$min = $coursedefstime%60;
$am = ($coursedefstime<12*60)?'am':'pm';
$defstime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;

if ($startdate!=0) {
	$sdate = tzdate("m/d/Y",$startdate);
	$stime = tzdate("g:i a",$startdate);
} else {
	$sdate = tzdate("m/d/Y",time());
	$stime = $defstime; //tzdate("g:i a",time());
}
if ($enddate!=2000000000) {
	$edate = tzdate("m/d/Y",$enddate);
	$etime = tzdate("g:i a",$enddate);
} else {
	$edate = tzdate("m/d/Y",time()+7*24*60*60);
	$etime = $deftime; //tzdate("g:i a",time()+7*24*60*60);
}


?>
<script type="text/javascript">

function updateorder(el) {
	var tomove = el.parentNode.parentNode;
	var tbl = document.getElementById("usedqtable").getElementsByTagName("tbody")[0];
	var trs = tbl.getElementsByTagName("tr");
	var n = 0;
	for (var i=0;i<trs.length;i++) {
		if (trs[i]==tomove) {
			n = i;
			break;
		}
	}
	var cnt = trs.length;
	var moveto = el.value*1;
	if (moveto<=n) {
		var dest = trs[moveto];
	} else if (moveto+1 < cnt) {
		var dest = trs[moveto+1];
	}

	tbl.removeChild(tomove);
	if (cnt==moveto+1) {
		tbl.appendChild(tomove);
	} else {
		tbl.insertBefore(tomove,dest);
	}
	var sel = tbl.getElementsByTagName("select");
	for (var i=0;i<sel.length;i++) {
		sel[i].selectedIndex = i;
	}
}
</script>
<?php

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Add/Modify Drill Assessment</div>";
echo "<h1>Add/Modify Drill Assessment</h1>";

printf("<form id=\"selform\" method=\"post\" action=\"adddrillassess.php?cid=%s&daid=%d&block=%s&tb=%s&record=true\">",
    $cid, $daid, Sanitize::encodeUrlParam($block), Sanitize::encodeUrlParam($totb));
?>
		<label for=title class=form>Title: </label>
		<span class=formright><input type=text size=60 name="title" id=title value="<?php echo Sanitize::encodeStringForDisplay($drillname);?>" required />
		</span><BR class=form>

		<label for=summary>Summary: (shows on course page)</label><BR>
		<div class=editor>
			<textarea cols=60 rows=10 id=summary name=summary style="width: 100%"><?php echo Sanitize::encodeStringForDisplay($drillsummary, true);?></textarea>
		</div>
		<br/>
		<span class=form>Show:</span>
		<span class=formright>
			<label><input type=radio name="avail" value="0" <?php writeHtmlChecked($avail,0);?> onclick="$('#datediv').slideUp(100);$('#altcaldiv').slideUp(100);"/>Hide</label><br/>
			<label><input type=radio name="avail" value="1" <?php writeHtmlChecked($avail,1);?> onclick="$('#datediv').slideDown(100);$('#altcaldiv').slideUp(100);"/>Show by Dates</label><br/>
			<label><input type=radio name="avail" value="2" <?php writeHtmlChecked($avail,2);?> onclick="$('#datediv').slideUp(100);$('#altcaldiv').slideDown(100);"/>Show Always</label>
		</span><br class="form"/>

		<div id="datediv" style="display:<?php echo ($avail==1)?"block":"none"; ?>">
		<span class=form>Available After:</span>
		<span class=formright>
			<label><input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,'0',0) ?>/>
			Always until end date</label><br/>
			<input type=radio name="sdatetype" value="sdate" <?php writeHtmlChecked($startdate,'0',1) ?> aria-label="Available after a date"/>
			<input type=text size=10 name=sdate value="<?php echo $sdate;?>" aria-label="available after date">
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=stime value="<?php echo $stime;?>" aria-label="available after time">
		</span><BR class=form>

		<span class=form>Available Until:</span><span class=formright>
			<label><input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/> Always after start date</label><br/>
			<input type=radio name="edatetype" value="edate"  <?php writeHtmlChecked($enddate,'2000000000',1) ?> aria-label="Available until a date"/>
			<input type=text size=10 name=edate value="<?php echo $edate;?>" aria-label="available until date">
			<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
			<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=etime value="<?php echo $etime;?>" aria-label="available until time">
		</span><BR class=form>

		<label for=caltag class=form>Calendar Tag:</label>
		<span class=formright>
			<input name="caltag" id=caltag type=text size=8 value="<?php echo Sanitize::encodeStringForDisplay($caltag); ?>"/>
		</span><BR class=form>
		</div>
		<span class=form></span>
		<span class=formright>
			<input type=submit name="save" value="Save"> now or continue below for Drill Options
		</span><br class=form>

<?php

if ($beentaken) {
	echo '<p>This drill has already been taken!  You will not be able to modify most settings unless you clear out existing attempts. ';
	echo "<button type=button onclick=\"if (confirm('Are you SURE you want to clear out existing attempts?')) {window.location='adddrillassess.php?cid=$cid&daid=$daid&clearatt=true';}\">Clear Existing Attempts</button></p>\n";
	//echo " <a href=\"adddrillassess.php?cid=$cid&daid=$daid&clearatt=true\" onclick=\"return confirm('Are you SURE you want to clear out existing attempts?');\">Clear existing attempts</a></p>";
}

echo '<p><b>Drill type</b></p>';
echo '<p><label for=scoretype>Scoring type:</label>';
$vals = array('nat','nct','ncc','nst','nsc','t');
$lbls = array('Do N questions then stop.  Record time.','Do N questions correct.  Record time.','Do N questions correct.  Record total attempts.','Do N questions correct in a row.  Record time','Do N questions correct in a row.  Record total attempts','Do as many correct as possible in N seconds');
writeHtmlSelect('scoretype',$vals,$lbls,$scoretype,null,null,$beentaken?'disabled="disabled"':'');
echo ' <label>where N = <input type="text" size="4" name="n" value="' . Sanitize::encodeStringForDisplay($n) . '" ' . ($beentaken ? 'disabled="disabled"' : '') . '/></label></p>';
echo '<p><label for=showtype>Feedback on individual questions:</label>';
$vals = array(0,1,4,2,3);
$lbls = array('Show score, and display answer if wrong', 'Show score, don\'t show answers, give new question if wrong','Show score, don\'t show answers, give same question if wrong','Don\'t show score','Don\'t show score, but provide show answer buttons');
writeHtmlSelect('showtype',$vals,$lbls,$showtype,null,null,$beentaken?'disabled="disabled"':'');
echo '</p>';

echo '<p>Show drill results to student: ';
echo '<label><input type="checkbox" name="showlast" '.getHtmlChecked($showtostu&1,1).'/> Show last score.</label> ';
echo '<label><input type="checkbox" name="showpbest" '.getHtmlChecked($showtostu&2,2).'/> Show personal best score.</label> ';
echo '<label><input type="checkbox" name="showcbest" '.getHtmlChecked($showtostu&4,4).'/> Show class best score.</label></p>';

if ($beentaken) {
	echo '<p><label><input type="checkbox" name="clearbests" value="1" /> Reset class bests?</label></p>';
}
echo '<table id="usedqtable">';
echo '<tr>';
if (!$beentaken) {echo '<th></th>';}
echo '<th>Description</th><th>Preview</th>';
if (!$beentaken) {echo '<th>Delete?</th>';}
echo '</tr>';
function generateselect($cnt,$i) {
	echo "<select name=\"order[$i]\" onchange=\"updateorder(this)\" aria-label=\"select new position\">";
	for ($j=1;$j<$cnt+1;$j++) {
		echo "<option value=\"$j\" ";
		if ($j==$i+1) {echo 'selected="selected" ';}
		echo '>'.($j).'</option>';
	}
	echo '</select>';
}
foreach ($itemids as $k=>$id) {
	echo '<tr id="row'.$k.'">';
	if (!$beentaken) {
		echo '<td>';
		generateselect(count($itemids),$k);
		echo '</td>';
	}
	echo '<td><input type="text" size="60" name="descr['.$k.']" value="' . Sanitize::encodeStringForDisplay($itemdescr[$k]) . '" aria-label="Description"/></td>';
	echo "<td><input type=button value=\"Preview\" onClick=\"previewq(null,$k," . Sanitize::encodeStringForJavascript($itemids[$k]) . ")\"/></td>";
	if (!$beentaken) {
		echo '<td><input type="checkbox" name="delitem['.$k.']" value="1" aria-label="Delete"/></td>';
	}
	echo '</tr>';
}
echo '</table>';
 echo '<input type="submit" value="Update"/>';
if (!$beentaken) {
	echo '<h2>'._('Potential Questions').'</h2>';

	outputSearchUI($searchtype, $searchterms, $search_results, 'addquestions');

	echo '<div class=pdiv>Check: <a href="#" onclick="return chkAllNone(\'selform\',\'nchecked[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'selform\',\'nchecked[]\',false)">None</a> ';
	echo '<button type=submit>'._('Add Selected').'</button>';
	echo '<button type="button" onclick="window.location=\'moddataset.php?cid='.$cid.'&amp;daid='.$daid.'\'">'._('Add New Question').'</button>';
	echo '</div>';

	?>
	<table cellpadding="5" id="myTable" class="gb zebra potential-question-list" style="clear:both; position:relative;" tabindex="-1">
    </table>
    <p><span id="searchnums"><?php echo _('Showing');?> <span id="searchnumvals"></span></span>
      <a href="#" id="searchprev" style="display:none"><?php echo _('Previous Results');?></a>
      <a href="#" id="searchnext" style="display:none"><?php echo _('More Results');?></a>
    </p>
	<script type="text/javascript">
		$(function() {
			displayQuestionList(<?php echo json_encode($search_results, JSON_INVALID_UTF8_IGNORE); ?>);
			setlibhistory();
		});
	</script>
	<?php
} else {
	echo '<input type="hidden" name="beentaken" value="1" />';
}
/*if (!$beentaken) {
	echo '<p>Add more questions (list ids separated by commas): <input type="text" name="idstoadd" value="" /></p>';
}

echo '<input type="submit" value="Update"/>';
*/
echo '</form>';
/*if ($daid>0) {
	$url = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/drillassess.php?cid=$cid&amp;daid=$daid";
	echo "<p>Link to drill assessment: <a href=\"$url\">$url</a></p>" ;
	$url = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gb-viewdrill.php?cid=$cid&amp;daid=$daid";
	echo "<p>Link to view results: <a href=\"$url\">$url</a></p>" ;

}*/
require_once '../footer.php';

?>
