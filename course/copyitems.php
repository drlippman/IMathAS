<?php
//IMathAS:  Copy Course Items
//(c) 2006 David Lippman

//boost operation time
@set_time_limit(0);
ini_set("max_execution_time", "600");

/*** master php includes *******/
require("../validate.php");
require("../includes/copyiteminc.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Copy Course Items";

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=" . $_GET['cid'] . "\">$coursename</a> &gt; Copy Course Items";

	// SECURITY CHECK DATA PROCESSING
if (!(isset($teacherid))) {
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {

	$cid = $_GET['cid'];
	$oktocopy = 1;
	
	if (isset($_GET['action'])) {
		$query = "SELECT imas_courses.id FROM imas_courses,imas_teachers WHERE imas_courses.id=imas_teachers.courseid";
		$query .= " AND imas_teachers.userid='$userid' AND imas_courses.id='{$_POST['ctc']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)==0) {
			$query = "SELECT enrollkey,copyrights FROM imas_courses WHERE id='{$_POST['ctc']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$copyrights = mysql_result($result,0,1)*1;
			if ($copyrights<2) {
				$oktocopy = 0;
				if ($copyrights==1) {
					$query = "SELECT imas_users.groupid FROM imas_courses,imas_users,imas_teachers WHERE imas_courses.id=imas_teachers.courseid ";
					$query .= "AND imas_teachers.userid=imas_users.id AND imas_courses.id='{$_POST['ctc']}'";
					$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($r2)) {
						if ($row[0]==$groupid) {
							$oktocopy=1;
							break;
						}
					}
				}
				if ($oktocopy==0) {
					$ekey = mysql_result($result,0,0);
					if (!isset($_POST['ekey']) || strtolower(trim($ekey)) != strtolower(trim($_POST['ekey']))) {
						$overwriteBody = 1;
						$body = "Invalid enrollment key entered.  <a href=\"copyitems.php?cid=$cid\">Try Again</a>";
					} else {
						$oktocopy = 1;
					}
				}
			}
		}
	} 
	if ($oktocopy == 1) {
		if (isset($_GET['action']) && $_GET['action']=="copycalitems") {
			if (isset($_POST['clearexisting'])) {
				$query = "DELETE FROM imas_calitems WHERE courseid='$cid'";
				mysql_query($query) or die("Query failed :$query " . mysql_error());
			}
			if (isset($_POST['checked']) && count($_POST['checked'])>0) {
				$checked = $_POST['checked'];
				$chklist = "'".implode("','",$checked)."'";
				$query = "SELECT date,tag,title FROM imas_calitems WHERE id IN ($chklist) AND courseid='{$_POST['ctc']}'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$insarr = array();
				while ($row = mysql_fetch_row($result)) {
					$insarr[] = "('$cid','".implode("','",addslashes_deep($row))."')";
				}
				$query = "INSERT INTO imas_calitems (courseid,date,tag,title) VALUES ";
				$query .= implode(',',$insarr);
				mysql_query($query) or die("Query failed :$query " . mysql_error());
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
			exit;	
		} else if (isset($_GET['action']) && $_GET['action']=="copy") {
			if ($_POST['whattocopy']=='all') {
				$_POST['copycourseopt'] = 1;
				$_POST['copygbsetup'] = 1;
				$_POST['removewithdrawn'] = 1;
				$_POST['usereplaceby'] = 1;
				$_POST['copyrubrics'] = 1;
				$_POST['copyoutcomes'] = 1;
				$_POST['copystickyposts'] = 1;
				$_POST['append'] = '';
				if (isset($_POST['copyofflinewhole'])) {
					$_POST['copyoffline'] = 1;
				}
				$_POST['addto'] = 'none';
			}
			mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
			if (isset($_POST['copycourseopt'])) {
				$tocopy = 'ancestors,hideicons,allowunenroll,copyrights,msgset,topbar,cploc,picicons,chatset,showlatepass,theme,latepasshrs';
				
				$query = "SELECT $tocopy FROM imas_courses WHERE id='{$_POST['ctc']}'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$row = mysql_fetch_row($result);
				$tocopyarr = explode(',',$tocopy);
				if ($row[0]=='') {
					$row[0] = intval($_POST['ctc']);
				} else {
					$row[0] = intval($_POST['ctc']).','.$row[0];
				}
				$sets = '';
				for ($i=0; $i<count($tocopyarr); $i++) {
					if ($i>0) {$sets .= ',';}
					$sets .= $tocopyarr[$i] . "='" . addslashes($row[$i])."'";
				}
				$query = "UPDATE imas_courses SET $sets WHERE id='$cid'";
				mysql_query($query) or die("Query failed :$query " . mysql_error());
			}
			if (isset($_POST['copygbsetup'])) {
				$query = "SELECT useweights,orderby,defaultcat,defgbmode,stugbmode,colorize FROM imas_gbscheme WHERE courseid='{$_POST['ctc']}'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$row = mysql_fetch_row($result);
				$query = "UPDATE imas_gbscheme SET useweights='{$row[0]}',orderby='{$row[1]}',defaultcat='{$row[2]}',defgbmode='{$row[3]}',stugbmode='{$row[4]}',colorize='{$row[5]}' WHERE courseid='$cid'";
				mysql_query($query) or die("Query failed :$query " . mysql_error());
				
				$query = "SELECT id,name,scale,scaletype,chop,dropn,weight,hidden,calctype FROM imas_gbcats WHERE courseid='{$_POST['ctc']}'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$query = "SELECT id FROM imas_gbcats WHERE courseid='$cid' AND name='{$row[1]}'";
					$r2 = mysql_query($query) or die("Query failed :$query " . mysql_error());
					if (mysql_num_rows($r2)==0) {
						$query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight,hidden,calctype) VALUES ";
						$frid = array_shift($row);
						$irow = "'".implode("','",addslashes_deep($row))."'";
						$query .= "('$cid',$irow)";
						mysql_query($query) or die("Query failed :$query " . mysql_error());
						$gbcats[$frid] = mysql_insert_id();
					} else {
						$rpid = mysql_result($r2,0,0);
						$query = "UPDATE imas_gbcats SET scale='{$row[2]}',scaletype='{$row[3]}',chop='{$row[4]}',dropn='{$row[5]}',weight='{$row[6]}',hidden='{$row[7]}',calctype='{$row[8]}' ";
						$query .= "WHERE id='$rpid'";
						$gbcats[$row[0]] = $rpid;
					}
				}
			} else {
				$gbcats = array();
				$query = "SELECT tc.id,toc.id FROM imas_gbcats AS tc JOIN imas_gbcats AS toc ON tc.name=toc.name WHERE tc.courseid='{$_POST['ctc']}' AND ";
				$query .= "toc.courseid='$cid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$gbcats[$row[0]] = $row[1];
				}
			}
			if (isset($_POST['copyoutcomes'])) {
				//load any existing outcomes
				$outcomes = array();
				$query = "SELECT tc.id,toc.id FROM imas_outcomes AS tc JOIN imas_outcomes AS toc ON tc.name=toc.name WHERE tc.courseid='{$_POST['ctc']}' AND ";
				$query .= "toc.courseid='$cid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$hasoutcomes = true;
				} else {
					$hasoutcomes = false;
				}
				while ($row = mysql_fetch_row($result)) {
					$outcomes[$row[0]] = $row[1];
				}
				$newoutcomes = array();
				$query = "SELECT id,name,ancestors FROM imas_outcomes WHERE courseid='{$_POST['ctc']}'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					if (isset($outcomes[$row[0]])) { continue;}
					if ($row[2]=='') {
						$row[2] = $row[0];
					} else {
						$row[2] = $row[0].','.$row[2];
					}
					$row[1] = addslashes($row[1]);
					$query = "INSERT INTO imas_outcomes (courseid,name,ancestors) VALUES ";
					$query .= "('$cid','{$row[1]}','{$row[2]}')";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
					$outcomes[$row[0]] = mysql_insert_id();
					$newoutcomes[] = $outcomes[$row[0]];
				}
				
				if ($hasoutcomes) {
					//already has outcomes, so we'll just add to the end of the existing list new outcomes
					$query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					$row = mysql_fetch_row($result);
					$outcomesarr = unserialize($row[0]);
					foreach ($newoutcomes as $o) {
						$outcomesarr[] = $o;
					}
				} else {
					//rewrite whole order
					$query = "SELECT outcomes FROM imas_courses WHERE id='{$_POST['ctc']}'";
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					$row = mysql_fetch_row($result);
					function updateoutcomes(&$arr) {
						global $outcomes;
						foreach ($arr as $k=>$v) {
							if (is_array($v)) {
								updateoutcomes($arr[$k]['outcomes']);
							} else {
								$arr[$k] = $outcomes[$v];
							}
						}
					}
					$outcomesarr = unserialize($row[0]);
					updateoutcomes($outcomesarr);
				}
				$newoutcomearr = addslashes(serialize($outcomesarr));
				$query = "UPDATE imas_courses SET outcomes='$newoutcomearr' WHERE id='$cid'";
				mysql_query($query) or die("Query failed :$query " . mysql_error());
				
			} else {
				$outcomes = array();
				$query = "SELECT tc.id,toc.id FROM imas_outcomes AS tc JOIN imas_outcomes AS toc ON tc.name=toc.name WHERE tc.courseid='{$_POST['ctc']}' AND ";
				$query .= "toc.courseid='$cid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$outcomes[$row[0]] = $row[1];
				}
			}
			
			if (isset($_POST['removewithdrawn'])) {
				$removewithdrawn = true;
			}
			if (isset($_POST['usereplaceby'])) {
				$usereplaceby = "all";
				$query = 'SELECT imas_questionset.id,imas_questionset.replaceby FROM imas_questionset JOIN ';
				$query .= 'imas_questions ON imas_questionset.id=imas_questions.questionsetid JOIN ';
				$query .= 'imas_assessments ON imas_assessments.id=imas_questions.assessmentid WHERE ';
				$query .= "imas_assessments.courseid='{$_POST['ctc']}' AND imas_questionset.replaceby>0";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$replacebyarr[$row[0]] = $row[1];  
				}
			}
			
			if (isset($_POST['checked']) || $_POST['whattocopy']=='all') {
				$checked = $_POST['checked'];
				$query = "SELECT blockcnt FROM imas_courses WHERE id='$cid'";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				$blockcnt = mysql_result($result,0,0);
				
				$query = "SELECT itemorder FROM imas_courses WHERE id='{$_POST['ctc']}'";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				$items = unserialize(mysql_result($result,0,0));
				$newitems = array();
				
				if (isset($_POST['copystickyposts'])) {
					$copystickyposts = true;
				} else {
					$copystickyposts = false;
				}
				
				if ($_POST['whattocopy']=='all') {
					copyallsub($items,'0',$newitems,$gbcats);
				} else {
					copysub($items,'0',$newitems,$gbcats,isset($_POST['copyhidden']));
				}
				doaftercopy($_POST['ctc']);
				
				$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				$items = unserialize(mysql_result($result,0,0));
				if ($_POST['addto']=="none") {
					array_splice($items,count($items),0,$newitems);
				} else {
					$blocktree = explode('-',$_POST['addto']);
					$sub =& $items;
					for ($i=1;$i<count($blocktree);$i++) {
						$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
					}
					array_splice($sub,count($sub),0,$newitems);
				}
				$itemorder = addslashes(serialize($items));
				if ($itemorder!='') {
					$query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt' WHERE id='$cid'";
					mysql_query($query) or die("Query failed : $query" . mysql_error());
				}
			}	
			$offlinerubrics = array();
			if (isset($_POST['copyoffline'])) {
				$query = "SELECT name,points,showdate,gbcategory,cntingb,tutoredit,rubric FROM imas_gbitems WHERE courseid='{$_POST['ctc']}'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$insarr = array();
				while ($row = mysql_fetch_row($result)) {
					$rubric = array_pop($row);
					if (isset($gbcats[$row[3]])) {
						$row[3] = $gbcats[$row[3]];
					} else {
						$row[3] = 0;
					}
					$ins = "('$cid','".implode("','",addslashes_deep($row))."')";
					$query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit) VALUES $ins";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
					if ($rubric>0) {
						$offlinerubrics[mysql_insert_id()] = $rubric;
					}
				}
			}
			if (isset($_POST['copyrubrics'])) {
				copyrubrics($offlinerubrics);
			}
			mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
			if (isset($_POST['selectcalitems'])) {
				$_GET['action']='selectcalitems';
				$calitems = array();
				$query = "SELECT id,date,tag,title FROM imas_calitems WHERE courseid='{$_POST['ctc']}' ORDER BY date";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$calitems[] = $row;
				}
			} else {
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
		
				exit;
			}
			
		} elseif (isset($_GET['action']) && $_GET['action']=="select") { //DATA MANIPULATION FOR second option
		
			$query = "SELECT itemorder,picicons FROM imas_courses WHERE id='{$_POST['ctc']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			list($itemorder,$picicons) = mysql_fetch_row($result);
			$items = unserialize($itemorder);
			$ids = array();
			$types = array();
			$names = array();
			$sums = array();
			$parents = array();
			getsubinfo($items,'0','',false,' ');
			
			$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$items = unserialize(mysql_result($result,0,0));
			$existblocks = array();
		
			buildexistblocks($items,'0');
			
			$i=0;
			$page_blockSelect = array();
			
			foreach ($existblocks as $k=>$name) {
				$page_blockSelect['val'][$i] = $k;
				$page_blockSelect['label'][$i] = $name;
				$i++;
			}
			
		} else if (isset($_GET['loadothers'])) {
			$query = "SELECT id,name FROM imas_groups";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$page_hasGroups=true;
				$grpnames = array();
				$grpnames[0] = "Default Group";
				while ($row = mysql_fetch_row($result)) {
					$grpnames[$row[0]] = $row[1];
				}
			}	
			
			$query = "SELECT ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid,iu.groupid FROM imas_courses AS ic,imas_teachers AS it,imas_users AS iu,imas_groups WHERE ";
			$query .= "it.courseid=ic.id AND it.userid=iu.id AND iu.groupid=imas_groups.id AND iu.groupid<>'$groupid' AND iu.id<>'$userid' AND ic.available<4 ORDER BY imas_groups.name,iu.LastName,iu.FirstName,ic.name";
			$courseGroupResults = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			
			
		} else { //DATA MANIPULATION FOR DEFAULT LOAD
		
			$query = "SELECT ic.id,ic.name FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$userid' and ic.id<>'$cid' AND ic.available<4 ORDER BY ic.name";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$i=0;
			$page_mineList = array();
			while ($row = mysql_fetch_row($result)) {
				$page_mineList['val'][$i] = $row[0];
				$page_mineList['label'][$i] = $row[1];
				$i++;
			}	
			
			$query = "SELECT ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid FROM imas_courses AS ic,imas_teachers AS it,imas_users AS iu WHERE ";
			$query .= "it.courseid=ic.id AND it.userid=iu.id AND iu.groupid='$groupid' AND iu.id<>'$userid' AND ic.available<4 ORDER BY iu.LastName,iu.FirstName,ic.name";
			$courseTreeResult = mysql_query($query) or die("Query failed : " . mysql_error());
			$lastteacher = 0;
			
			
			//$query = "SELECT ic.id,ic.name,ic.copyrights FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$templateuser' AND ic.available<4 ORDER BY ic.name";
			$query = "SELECT id,name,copyrights FROM imas_courses WHERE (istemplate&1)=1 AND copyrights=2 AND available<4 ORDER BY name";
			$courseTemplateResults = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "SELECT ic.id,ic.name,ic.copyrights FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ";
			$query .= "iu.groupid='$groupid' AND (ic.istemplate&2)=2 AND ic.copyrights>0 AND ic.available<4 ORDER BY ic.name";
			$groupTemplateResults = mysql_query($query) or die("Query failed : " . mysql_error());
		}
	}
}
/******* begin html output ********/

if (!isset($_GET['loadothers'])) {
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/libtree.js\"></script>\n";
$placeinhead .= "<style type=\"text/css\">\n<!--\n@import url(\"$imasroot/course/libtree.css\");\n-->\n</style>\n";
$placeinhead .= '<script type="text/javascript">
	function updatetocopy(el) {
		if (el.value=="all") {
			$("#selectitemstocopy").hide();$("#allitemsnote").show();
		} else {
			$("#selectitemstocopy").show();$("#allitemsnote").hide();
		} }
		
	$(function() {
		$("input:radio").change(function() {
			if ($(this).hasClass("copyr")) {
				$("#ekeybox").show();
			} else {
				$("#ekeybox").hide();
			}
		});
	});	
		</script>';
require("../header.php");
}
if ($overwriteBody==1) {
	echo $body;
} else {
	if (!isset($_GET['loadothers'])) {
?>

	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headercopyitems" class="pagetitle"><h2>Copy Course Items</h2></div>

<?php
	}
	if (isset($_GET['action']) && $_GET['action']=='selectcalitems') {
//DISPLAY BLOCK FOR selecting calendar items to copy
?>
	<form id="qform" method=post action="copyitems.php?cid=<?php echo $cid ?>&action=copycalitems">
	<input type=hidden name=ekey id=ekey value="<?php echo $_POST['ekey'] ?>">
	<input type=hidden name=ctc id=ctc value="<?php echo $_POST['ctc'] ?>">
	<h4>Select Calendar Items to Copy</h4>
	Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
	
	<table cellpadding=5 class=gb>
		<thead>
		<tr><th></th><th>Date</th><th>Tag</th><th>Text</th></tr>
		</thead>
		<tbody>
<?php	
		$alt=0;
		for ($i = 0 ; $i<(count($calitems)); $i++) {
			if ($alt==0) {echo "		<tr class=even>"; $alt=1;} else {echo "		<tr class=odd>"; $alt=0;}
?>			
			<td>
			<input type=checkbox name='checked[]' value='<?php echo $calitems[$i][0];?>' checked="checked"/>
			</td>
			<td class="nowrap"><?php echo tzdate("m/d/Y",$calitems[$i][1]); ?></td>
			<td><?php echo $calitems[$i][2]; ?></td>
			<td><?php echo $calitems[$i][3]; ?></td>
		</tr>
<?php
		}
?>		
		</tbody>
	</table>
	<p>Remove all existing calendar items? <input type="checkbox" name="clearexisting" value="1" /></p>
	<p><input type=submit value="Copy Calendar Items"></p>
	</form>

<?php
		
	} else if (isset($_GET['action']) && $_GET['action']=="select") {

//DISPLAY BLOCK FOR SECOND STEP - selecting course item
?>
	<script type="text/javascript">
	
	function chkgrp(frm, arr, mark) {
	  var els = frm.getElementsByTagName("input");
	  for (var i = 0; i < els.length; i++) {
		  var el = els[i];
		  if (el.type=='checkbox' && (el.id.indexOf(arr+'.')==0 || el.id.indexOf(arr+'-')==0 || el.id==arr)) {
	     	       el.checked = mark;
		  }
	  }
	}
	</script>
	
	<form id="qform" method=post action="copyitems.php?cid=<?php echo $cid ?>&action=copy">
	<input type=hidden name=ekey id=ekey value="<?php echo $_POST['ekey'] ?>">
	<input type=hidden name=ctc id=ctc value="<?php echo $_POST['ctc'] ?>">
	What to copy: <select name="whattocopy" onchange="updatetocopy(this)">
		<option value="all">Copy whole course</option>
		<option value="select">Select items to copy</option>
	</select>
	<?php
		if ($_POST['ekey']=='') { echo '&nbsp;<a class="small" target="_blank" href="course.php?cid='.$_POST['ctc'].'">Preview source course</a>';}
	?>
	<div id="allitemsnote">
	<p><input type=checkbox name="copyofflinewhole"  value="1"/> Copy offline grade items </p>
	<p>Copying the whole course will also copy (and overwrite) course settings, gradebook categories, outcomes, and rubrics.  
	   To change these options, choose "Select items to copy" instead.</p>
	  
	</div>
	<div id="selectitemstocopy" style="display:none;">
	<h4>Select Items to Copy</h4>
	
	Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
	
	<table cellpadding=5 class=gb>
		<thead>
		<?php
		if ($picicons) {
			echo '<tr><th></th><th>Title</th><th>Summary</th></tr>';
		} else {
			echo '<tr><th></th><th>Type</th><th>Title</th><th>Summary</th></tr>';
		}
		?>
		
		</thead>
		<tbody>
<?php	
		$alt=0;
	
		for ($i = 0 ; $i<(count($ids)); $i++) {
			if ($alt==0) {echo "		<tr class=even>"; $alt=1;} else {echo "		<tr class=odd>"; $alt=0;}
			echo '<td>';
			if (strpos($types[$i],'Block')!==false) {		
				echo "<input type=checkbox name='checked[]' value='{$ids[$i]}' id='{$parents[$i]}' ";
				echo "onClick=\"chkgrp(this.form, '{$ids[$i]}', this.checked);\" ";
				echo '/>';
			} else {
				echo "<input type=checkbox name='checked[]' value='{$ids[$i]}' id='{$parents[$i]}.{$ids[$i]}' ";
				echo '/>';
			}
?>
			</td>
			
		<?php
			$tdpad = 16*strlen($prespace[$i]);
			
			if ($picicons) {
				echo '<td style="padding-left:'.$tdpad.'px"><img alt="'.$types[$i].'" title="'.$types[$i].'" src="'.$imasroot.'/img/';
				switch ($types[$i]) {
					case 'Calendar': echo $CFG['CPS']['miniicons']['calendar']; break;
					case 'InlineText': echo $CFG['CPS']['miniicons']['inline']; break;
					case 'LinkedText': echo $CFG['CPS']['miniicons']['linked']; break;
					case 'Forum': echo $CFG['CPS']['miniicons']['forum']; break;
					case 'Wiki': echo $CFG['CPS']['miniicons']['wiki']; break;
					case 'Block': echo $CFG['CPS']['miniicons']['folder']; break;
					case 'Assessment': echo $CFG['CPS']['miniicons']['assess']; break;
					case 'Drill': echo $CFG['CPS']['miniicons']['drill']; break;
				}
				echo '" class="floatleft"/><div style="margin-left:21px">'.$names[$i].'</div></td>';
			} else {
				
				echo '<td>'.$prespace[$i].$names[$i].'</td>';
				echo '<td>'.$types[$i].'</td>';
			}
		?>
			<td><?php echo $sums[$i] ?></td>
		</tr>
<?php
		}
?>		
		
		</tbody>
	</table>
	<p> </p>
	<fieldset><legend>Options</legend>
	<table>
	<tbody>
	<tr><td class="r">Copy course settings?</td><td><input type=checkbox name="copycourseopt"  value="1"/></td></tr>
	<tr><td class="r">Copy gradebook scheme and categories<br/>(<i>will overwrite current scheme</i>)? </td><td>
		<input type=checkbox name="copygbsetup" value="1"/></td></tr>
	<tr><td class="r">Set all copied items as hidden to students?</td><td><input type="checkbox" name="copyhidden" value="1"/></td></tr>
	<tr><td class="r">Copy offline grade items?</td><td> <input type=checkbox name="copyoffline"  value="1"/></td></tr>
	<tr><td class="r">Remove any withdrawn questions from assessments?</td><td> <input type=checkbox name="removewithdrawn"  value="1" checked="checked"/></td></tr>
	<tr><td class="r">Use any suggested replacements for old questions?</td><td> <input type=checkbox name="usereplaceby"  value="1" checked="checked"/></td></tr>
	<tr><td class="r">Copy rubrics? </td><td><input type=checkbox name="copyrubrics"  value="1" checked="checked"/></td></tr>
	<tr><td class="r">Copy outcomes? </td><td><input type=checkbox name="copyoutcomes"  value="1" /></td></tr>
	<tr><td class="r">Select calendar items to copy?</td><td> <input type=checkbox name="selectcalitems"  value="1"/></td></tr>
	
	<tr><td class="r">Copy "display at top" instructor forum posts? </td><td><input type=checkbox name="copystickyposts"  value="1" checked="checked"/></td></tr>
	
	<tr><td class="r">Append text to titles?</td><td> <input type="text" name="append"></td></tr>
	<tr><td class="r">Add to block:</td><td>

<?php
writeHtmlSelect ("addto",$page_blockSelect['val'],$page_blockSelect['label'],$selectedVal=null,$defaultLabel="Main Course Page",$defaultVal="none",$actions=null);
?>
		
		
	</td></tr>
	</tbody>
	</table>
	</fieldset>
	</div>
	<p><input type=submit value="Copy Items"></p>
	</form>
<?php
	} else if (isset($_GET['loadothers'])) { //loading others subblock
	 if ($page_hasGroups) {
				$lastteacher = 0;
				$lastgroup = -1;
				while ($line = mysql_fetch_array($courseGroupResults, MYSQL_ASSOC)) {
					if ($line['groupid']!=$lastgroup) {
						if ($lastgroup!=-1) {
							echo "				</ul>\n			</li>\n";
							echo "			</ul>\n		</li>\n";
							$lastteacher = 0;
						}
	?>					
				<li class=lihdr>
					<span class=dd>-</span>
					<span class=hdr onClick="toggle('g<?php echo $line['groupid'] ?>')">
						<span class=btn id="bg<?php echo $line['groupid'] ?>">+</span>
					</span>
					<span class=hdr onClick="toggle('g<?php echo $line['groupid'] ?>')">
						<span id="ng<?php echo $line['groupid'] ?>" ><?php echo $grpnames[$line['groupid']] ?></span>
					</span>
					<ul class=hide id="g<?php echo $line['groupid'] ?>">
	
	<?php
						$lastgroup = $line['groupid'];
					}
					if ($line['userid']!=$lastteacher) {
						if ($lastteacher!=0) {
							echo "				</ul>\n			</li>\n";
						}
	?>					
				<li class=lihdr>
					<span class=dd>-</span>
					<span class=hdr onClick="toggle(<?php echo $line['userid'] ?>)">
						<span class=btn id="b<?php echo $line['userid'] ?>">+</span>
					</span>
					<span class=hdr onClick="toggle(<?php echo $line['userid'] ?>)">
						<span id="n<?php echo $line['userid'] ?>" ><?php echo $line['LastName'] . ", " . $line['FirstName'] . "\n" ?>
						</span>
					</span> 
					<a href="mailto:<?php echo $line['email'] ?>">Email</a>
					<ul class=hide id="<?php echo $line['userid'] ?>">
	<?php					
						$lastteacher = $line['userid'];
					}
	?>
						<li>
							<span class=dd>-</span>
							<?php
							echo '<input type="radio" name="ctc" value="'.$line['id'].'" '.(($line['copyrights']<2)?'class="copyr"':'').'>';
							echo $line['name'];
							if ($line['copyrights']<2) {
								echo "&copy;\n"; 
							} else {
								echo " <a href=\"course.php?cid={$line['id']}\" target=\"_blank\">Preview</a>";
							}
							?>  
						</li>
	<?php
				}
	?>			
				
						</ul>
					</li>
				</ul>
			</li> 
		</ul>
	</li>
	<?php
		 } else {
			 echo '<li>No other users</li>';
		 }	
		
	} else { //DEFAULT DISPLAY BLOCK
?>
	<script type="text/javascript">
	var othersloaded = false;
	var ahahurl = '<?php echo $imasroot?>/course/copyitems.php?cid=<?php echo $cid ?>&loadothers=true';
	function loadothers() {
		if (!othersloaded) {
			//basicahah(ahahurl, "other");
			$.ajax({url:ahahurl, dataType:"html"}).done(function(resp) {
				$('#other').html(resp);
				$("#other input:radio").change(function() {
					if ($(this).hasClass("copyr")) {
						$("#ekeybox").show();
					} else {
						$("#ekeybox").hide();
					}
				});
			});
			othersloaded = true;
		}
	}
	</script>
	<h4>Select a course to copy items from</h4>
		
	<form method=post action="copyitems.php?cid=<?php echo $cid ?>&action=select">
		Course List
		<ul class=base>
			<li><span class=dd>-</span>
				<input type=radio name=ctc value="<?php echo $cid ?>" checked=1>This Course</li>
			<li class=lihdr><span class=dd>-</span>
				<span class=hdr onClick="toggle('mine')">
					<span class=btn id="bmine">+</span>
				</span>
				<span class=hdr onClick="toggle('mine')">
					<span id="nmine" >My Courses</span>
				</span>
				<ul class=hide id="mine">
<?php
//my items
		for ($i=0;$i<count($page_mineList['val']);$i++) {
?>		

					<li><span class=dd>-</span>
						<input type=radio name=ctc value="<?php echo $page_mineList['val'][$i] ?>"><?php echo $page_mineList['label'][$i] . "\n" ?>
					</li>
<?php
		}
?>		
				</ul>
			</li>
			<li class=lihdr><span class=dd>-</span>
				<span class=hdr onClick="toggle('grp')">
					<span class=btn id="bgrp">+</span>
				</span>
				<span class=hdr onClick="toggle('grp')">
					<span id="ngrp" >My Group's Courses</span>
				</span>
				<ul class=hide id="grp">

<?php
//group's courses
		if (mysql_num_rows($courseTreeResult)>0) {
			while ($line = mysql_fetch_array($courseTreeResult, MYSQL_ASSOC)) {
				if ($line['userid']!=$lastteacher) {
					if ($lastteacher!=0) {
						echo "				</ul>\n			</li>\n";
					}
?>					
					<li class=lihdr>
						<span class=dd>-</span>
						<span class=hdr onClick="toggle(<?php echo $line['userid'] ?>)">
							<span class=btn id="b<?php echo $line['userid'] ?>">+</span>
						</span>
						<span class=hdr onClick="toggle(<?php echo $line['userid'] ?>)">
							<span id="n<?php echo $line['userid'] ?>"><?php echo $line['LastName'] . ", " . $line['FirstName'] . "\n" ?>
							</span> 
						</span> 
						<a href="mailto:<?php echo $line['email'] ?>">Email</a>
						<ul class=hide id="<?php echo $line['userid'] ?>">
<?php
					$lastteacher = $line['userid'];
				}
?>
							<li>
								<span class=dd>-</span>
								<?php
								echo '<input type="radio" name="ctc" value="'.$line['id'].'" '.(($line['copyrights']<2)?'class="copyr"':'').'>';
								echo $line['name'];
								if ($line['copyrights']<1) {
									echo "&copy;\n"; 
								} else {
									echo " <a href=\"course.php?cid={$line['id']}\" target=\"_blank\">Preview</a>";
								}
								?>  
							</li>
<?php
			}
			echo "						</ul>\n					</li>\n"; 
			echo "				</ul>			</li>\n";
		} else {
			echo "				</ul>\n			</li>\n";
		}
?>		
			<li class=lihdr>
				<span class=dd>-</span>
				<span class=hdr onClick="toggle('other');loadothers();">
					<span class=btn id="bother">+</span>
				</span>
				<span class=hdr onClick="toggle('other');loadothers();">
					<span id="nother" >Other's Courses</span>
				</span>
				<ul class=hide id="other">

<?php		
//Other's courses: loaded via AHAH when clicked  
		echo "<li>Loading...</li>			</ul>\n		</li>\n";
		
//template courses
		if (mysql_num_rows($courseTemplateResults)>0) {
?>
		<li class=lihdr>
			<span class=dd>-</span>
			<span class=hdr onClick="toggle('template')">
				<span class=btn id="btemplate">+</span>
			</span>
			<span class=hdr onClick="toggle('template')">
				<span id="ntemplate" >Template Courses</span>
			</span>
			<ul class=hide id="template">

<?php			
			while ($row = mysql_fetch_row($courseTemplateResults)) {
?>			
				<li>
					<span class=dd>-</span>
					<?php
					echo '<input type="radio" name="ctc" value="'.$row[0].'" '.(($row[2]<2)?'class="copyr"':'').'>';
					echo $row[1];
					if ($row[2]<2) {
						echo "&copy;\n"; 
					} else {
						echo " <a href=\"course.php?cid={$row[0]}\" target=\"_blank\">Preview</a>";
					}
					?>
				</li>

<?php
			}
			echo "			</ul>\n		</li>\n";
		}
		if (mysql_num_rows($groupTemplateResults)>0) {
?>
		<li class=lihdr>
			<span class=dd>-</span>
			<span class=hdr onClick="toggle('gtemplate')">
				<span class=btn id="bgtemplate">+</span>
			</span>
			<span class=hdr onClick="toggle('gtemplate')">
				<span id="ngtemplate" >Group Template Courses</span>
			</span>
			<ul class=hide id="gtemplate">

<?php			
			while ($row = mysql_fetch_row($groupTemplateResults)) {
?>			
				<li>
					<span class=dd>-</span>
					<input type=radio name=ctc value="<?php echo $row[0] ?>">
					<?php echo $row[1] ?>
					<?php 
						if ($row[2]<1) {
							echo "&copy;\n"; 
						} else {
							echo " <a href=\"course.php?cid={$row[0]}\" target=\"_blank\">Preview</a>";
						}
					?>
				</li>

<?php
			}
			echo "			</ul>\n		</li>\n";
		}
?>		
		</ul>
		
		<p id="ekeybox" style="display:none;">
		For courses marked with &copy;, you must supply the course enrollment key to show permission to copy the course.<br/>
		Enrollment key: <input type=text name=ekey id=ekey size=30></p>
		<input type=submit value="Select Course Items">
		<p>&nbsp;</p>
	</form>

<?php		
	}
}	
if (!isset($_GET['loadothers'])) {
 require ("../footer.php");
}
?>
