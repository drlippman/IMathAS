<?php
//IMathAS:  Drill Assess creator (rough version)
//(c) 2011 David Lippman

require("../validate.php");
require("../includes/htmlutil.php");

if (!isset($teacherid)) {
	echo 'You are not authorized to view this page';
	exit;
}

$pagetitle = "Add/Modify Drill Assessment";
$cid = intval($_GET['cid']);
$daid = intval($_GET['daid']);

$query = "SELECT * FROM imas_drillassess WHERE id='$daid' AND courseid='$cid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)==0) {
	//new to invalid
	$itemdescr = array();
	$itemids = array();
	$scoretype = 't';
	$showtype = '4';
	$n = 30;
	$showtostu = 7;
	$itemids = array();
	$itemdescr = array();
	$daid = 0;
} else {
	$dadata = mysql_fetch_array($result, MYSQL_ASSOC);
	$n = $dadata['n'];
	$showtype = $dadata['showtype'];
	$scoretype = $dadata['scoretype'];
	$showtostu = $dadata['showtostu'];
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
	$query = "DELETE FROM imas_drillassess_sessions WHERE drillassessid=$daid";
	mysql_query($query) or die("Query failed : " . mysql_error());
	header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/adddrillassess.php?cid=$cid&daid=$daid");
	exit;
}
if (isset($_GET['record'])) {
	if (isset($_POST['descr'])) {
		foreach ($_POST['descr'] as $k=>$v) {
			$itemdescr[$k] = str_replace(',','',$v);
		}
	}
	for ($i=count($itemdescr)-1;$i>=0;$i--) {
		if (isset($_POST['delitem'][$i])) {
			unset($itemids[$i]);
			unset($itemdescr[$i]);
		}
	}
	$itemids = array_values($itemids);
	$itemdescr = array_values($itemdescr);
	$classbests = array();
	$updatebests = false;
	if (isset($_POST['idstoadd']) && trim($_POST['idstoadd'])!='') {
		$toadd = explode(',',$_POST['idstoadd']);
		foreach ($toadd as $k=>$v) {
			$toadd[$k] = intval($v);
			if ($toadd[$k]==0) {
				unset($toadd[$k]);
			}
		}
		$toaddlist = implode(',',$toadd);
		$query = "SELECT id,description FROM imas_questionset WHERE id IN ($toaddlist)";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$descr = array();
		while ($row = mysql_fetch_row($result)) {
			$descr[$row[0]] = str_replace(',','',$row[1]);
		}
		foreach ($toadd as $k=>$v) {
			$itemids[] = $v;
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
		$query = "INSERT INTO imas_drillassess (courseid,itemdescr,itemids,scoretype,showtype,n,classbests,showtostu) VALUES ";
		$query .= "($cid,'$descrlist','$itemlist','$scoretype',$showtype,$n,'$bestlist',$showtostu)";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$daid = mysql_insert_id();
	} else {
		$query = "UPDATE imas_drillassess SET itemdescr='$descrlist',itemids='$itemlist',scoretype='$scoretype',showtype=$showtype,";
		$query .= "n=$n,showtostu=$showtostu";
		if ($updatebests) {
			$query .= ",classbests='$bestlist'";
		} 
		$query .= " WHERE id=$daid";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/adddrillassess.php?cid=$cid&daid=$daid");
	exit;
}

$query = "SELECT id FROM imas_drillassess_sessions WHERE drillassessid=$daid";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
if (mysql_num_rows($result)>0) {
	$beentaken = true;
} else {
	$beentaken = false;
}

require("../header.php");
?>
<script type="text/javascript">
function previewq(formn,loc,qn) {
	var addr = '<?php echo $imasroot ?>/course/testquestion.php?cid=<?php echo $cid ?>&checked=0&qsetid='+qn;
	previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));
	previewpop.focus();
}
</script>
<?php
	
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> &gt; Add/Modify Drill Assessment</div>";
echo "<h2>Add/Modify Drill Assessment</h2>";

if ($beentaken) {
	echo '<p>This drill has already been taken!  You will not be able to modify most settings unless you clear out existing attempts.';
	echo "<a href=\"adddrillassess.php?cid=$cid&daid=$daid&clearatt=true\" onclick=\"return confirm('Are you SURE you want to clear out existing attempts?');\">Clear existing attempts</a></p>";
}

echo "<form id=\"selform\" method=\"post\" action=\"adddrillassess.php?cid=$cid&daid=$daid&record=true\">";
echo '<p><b>Drill type</b></p>';
echo '<p>Scoring type:';
$vals = array('nat','nct','ncc','nst','nsc','t');
$lbls = array('Do N questions then stop.  Record time.','Do N questions correct.  Record time.','Do N questions correct.  Record total attempts.','Do N questions correct in a row.  Record time','Do N questions correct in a row.  Record total attempts','Do as many correct as possible in N seconds');
writeHtmlSelect('scoretype',$vals,$lbls,$scoretype,null,null,$beentaken?'disabled="disabled"':'');
echo ' where N = <input type="text" size="4" name="n" value="'.$n.'" '. ($beentaken?'disabled="disabled"':''). '/></p>';
echo '<p>Feedback on individual questions:';
$vals = array(0,1,4,2,3);
$lbls = array('Show score, and display answer if wrong', 'Show score, don\'t show answers, give new question if wrong','Show score, don\'t show answers, give same question if wrong','Don\'t show score','Don\'t show score, but provide show answer buttons');
writeHtmlSelect('showtype',$vals,$lbls,$showtype,null,null,$beentaken?'disabled="disabled"':'');
echo '</p>';

echo '<p>Show drill results to student: ';
echo '<input type="checkbox" name="showlast" '.getHtmlChecked($showtostu&1,1).'/> Show last score. ';
echo '<input type="checkbox" name="showpbest" '.getHtmlChecked($showtostu&2,2).'/> Show personal best score. ';
echo '<input type="checkbox" name="showcbest" '.getHtmlChecked($showtostu&4,4).'/> Show class best score.</p>';

echo '<table>';
echo '<tr><th>Description</th><th>Preview</th>';
if (!$beentaken) {echo '<th>Delete?</th>';}
echo '</tr>';
foreach ($itemids as $k=>$id) {
	echo '<tr>';
	echo '<td><input type="text" size="60" name="descr['.$k.']" value="'.$itemdescr[$k].'"/></td>';
	echo "<td><input type=button value=\"Preview\" onClick=\"previewq('selform',$k,{$itemids[$k]})\"/></td>";
	if (!$beentaken) {
		echo '<td><input type="checkbox" name="delitem['.$k.']"/></td>';
	}
	echo '</tr>';
}
echo '<table>';
if (!$beentaken) {
	echo '<p>Add more questions (list ids separated by commas): <input type="text" name="idstoadd" value="" /></p>';
}

echo '<input type="submit" value="Update"/>';
echo '</form>';
if ($daid>0) {
	$url = 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/drillassess.php?cid=$cid&amp;daid=$daid";
	echo "<p>Link to drill assessment: <a href=\"$url\">$url</a></p>" ;
	$url = 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gb-viewdrill.php?cid=$cid&amp;daid=$daid";
	echo "<p>Link to view results: <a href=\"$url\">$url</a></p>" ;
	
}
require('../footer.php');

?>

