<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

/*** pre-html data manipulation, including function code *******/

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$page_hasSearchResults = 0;
$pagetitle = $installname . " Question Export";


//data manipulation here
$isadmin = false;
$isgrpadmin = false;

	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid)) && $myrights<75) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_GET['cid']) && $_GET['cid']=="admin" && $myrights <75) {
 	$overwriteBody = 1;
	$body = "You need to log in as an admin to access this page";
} elseif (!(isset($_GET['cid'])) && $myrights < 75) {
 	$overwriteBody = 1;
	$body = "Please access this page from the menu links only.";
} else {

	$cid = (isset($_GET['cid'])) ? $_GET['cid'] : "admin" ;

	if ($myrights < 100) {
		$isgrpadmin = true;
	} else if ($myrights == 100) {
		$isadmin = true;
	}

	if ($isadmin || $isgrpadmin) {
		$curBreadcrumb =  "<div class=breadcrumb>$breadcrumbbase <a href=\"admin.php\">Admin</a> &gt; Export Question Set</div>\n";
	} else {
		$curBreadcrumb =  "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Export Question Set</div>\n";
	}



	//remember search, USED FOR ALL STEPS
	if (isset($_POST['search'])) {
		$safesearch = $_POST['search'];
		//DB $search = stripslashes($safesearch);
		$search = $safesearch;
		$search = str_replace('"','&quot;',$search);
		$sessiondata['lastsearch'] = str_replace(" ","+",$safesearch);
		writesessiondata();
	} else if (isset($sessiondata['lastsearch'])) {
		$safesearch = str_replace("+"," ",$sessiondata['lastsearch']);
		//DB $search = stripslashes($safesearch);
		$search = $safesearch;
		$search = str_replace('"','&quot;',$search);
	} else {
		$search = '';
	}
	if (isset($_POST['libs'])) {
		if ($_POST['libs']=='') {
			$_POST['libs'] = '0';
		}
		$searchlibs = $_POST['libs'];
		//$sessiondata['lastsearchlibs'] = implode(",",$searchlibs);
		$sessiondata['lastsearchlibs'] = $searchlibs;
		writesessiondata();
	} else if (isset($sessiondata['lastsearchlibs'])) {
		//$searchlibs = explode(",",$sessiondata['lastsearchlibs']);
		$searchlibs = $sessiondata['lastsearchlibs'];
	} else {
		$searchlibs = '0';
	}

	//get list of items already checked for export
	//USED FOR STEP 2
	$checked = array_merge((array)$_POST['pchecked'],(array)$_POST['nchecked']);
	//DB $clist = "'".implode("','",$checked)."'";
  $clist = implode(',', array_map('intval', $checked));
	$now = time();

	//DB $query = "SELECT id,description,qtype FROM imas_questionset WHERE id IN ($clist)";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$i=0;
	$page_pChecked = array();

	//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	if (count($checked)>0) {
		$stm = $DBH->query("SELECT id,description,qtype FROM imas_questionset WHERE id IN ($clist)");
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$page_pChecked[$i]['id'] = $line['id'];
			$page_pChecked[$i]['description'] = $line['description'];
			$page_pChecked[$i]['qtype'] = $line['qtype'];
			$i++;
		}
	}

	//GRAB LIST OF LIBS/QUESTIONS, USED IN STEP 1 AND 2
	//DB $llist = "'".implode("','",explode(',',$searchlibs))."'";
  $llist = implode(',', array_map('intval', explode(',',$searchlibs)));

	if (substr($searchlibs,0,1)=="0")
		$lnames[] = "Unassigned";

	//DB $query = "SELECT name FROM imas_libraries WHERE id IN ($llist)";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->query("SELECT name FROM imas_libraries WHERE id IN ($llist)");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$lnames[] = $row[0];
	}
	$lnames = implode(", ",$lnames);

	if (isset($search)) {
    $qarr = array();
		if ($isadmin) {
			//DB $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
			//DB $query .= "FROM imas_questionset,imas_library_items WHERE imas_questionset.description LIKE '%$safesearch%' ";
			//DB $query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
			$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
			$query .= "FROM imas_questionset,imas_library_items WHERE imas_questionset.description LIKE :safesearch ";
			$query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
			$qarr = array(':safesearch'=>"%$safesearch%");
		} else if ($isgrpadmin) {
			//DB $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
			//DB $query .= "FROM imas_questionset,imas_library_items,imas_users WHERE imas_questionset.description LIKE '%$safesearch%' ";
			//DB $query .= "AND imas_questionset.ownerid=imas_users.id ";
			//DB $query .= "AND (imas_users.groupid='$groupid' OR imas_questionset.userights>0) ";
			//DB $query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
			$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
			$query .= "FROM imas_questionset,imas_library_items,imas_users WHERE imas_questionset.description LIKE :safesearch ";
			$query .= "AND imas_questionset.ownerid=imas_users.id ";
			$query .= "AND (imas_users.groupid=:groupid OR imas_questionset.userights>0) ";
			$query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
			$qarr = array(':safesearch'=>"%$safesearch%", ':groupid'=>$groupid);
		} else {
			//DB $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
			//DB $query .= "FROM imas_questionset,imas_library_items WHERE imas_questionset.description LIKE '%$safesearch%' ";
			//DB $query .= "AND (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0) ";
			//DB $query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
			$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
			$query .= "FROM imas_questionset,imas_library_items WHERE imas_questionset.description LIKE :safesearch ";
			$query .= "AND (imas_questionset.ownerid=:ownerid OR imas_questionset.userights>0) ";
			$query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
			$qarr = array(':safesearch'=>"%$safesearch%", ':ownerid'=>$userid);
		}

		if (count($checked)>0) { $query .= "AND imas_questionset.id NOT IN ($clist);"; }
    $stm = $DBH->prepare($query);
    $stm->execute($qarr);
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());

		//DB if (mysql_num_rows($result) != 0) {
		if ($stm->rowCount() != 0) {
			$page_hasSearchResults = 1;
			$i=0;
			$page_nChecked = array();

			//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
				$page_nChecked[$i]['id'] = $line['id'];
				$page_nChecked[$i]['description'] = $line['description'];
				$page_nChecked[$i]['qtype'] = $line['qtype'];
				$i++;
			}
		}
	}



	//output export file here
	if (isset($_POST['export'])) {
		header('Content-type: text/imas');
		header("Content-Disposition: attachment; filename=\"imasexport.imas\"");
		echo "PACKAGE DESCRIPTION\n";
		echo $_POST['libdescription'];
		echo "\n";
		echo "\nSTART LIBRARY\nID\n1\nUID\n0\nLASTMODDATE\n$now\nNAME\n{$_POST['libname']}\nPARENT\n0\n";
		$qsetlist = implode(',',range(0,count($checked)-1));
		echo "\nSTART LIBRARY ITEMS\nLIBID\n1\nQSETIDS\n$qsetlist\n";
		//first, lets pull any questions that have include__from so we can lookup backrefs
		//DB $query = "SELECT * FROM imas_questionset WHERE id IN ($clist)";
		//DB $query .= " AND (control LIKE '%includecodefrom%' OR qtext LIKE '%includeqtextfrom%')";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		$query = "SELECT * FROM imas_questionset WHERE id IN ($clist)";
		$query .= " AND (control LIKE '%includecodefrom%' OR qtext LIKE '%includeqtextfrom%')";
		$stm = $DBH->query($query);
		$includedqs = array();
		//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			if (preg_match_all('/includecodefrom\((\d+)\)/',$line['control'],$matches,PREG_PATTERN_ORDER) >0) {
				$includedqs = array_merge($includedqs,$matches[1]);
			}
			if (preg_match_all('/includeqtextfrom\((\d+)\)/',$line['qtext'],$matches,PREG_PATTERN_ORDER) >0) {
				$includedqs = array_merge($includedqs,$matches[1]);
			}
		}
		$includedbackref = array();
		if (count($includedqs)>0) {
			$includedlist = implode(',', array_map('intval', $includedqs));
			//DB $query = "SELECT id,uniqueid FROM imas_questionset WHERE id IN ($includedlist)";
			//DB $result = mysql_query($query) or die("Query failed : $query"  . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->query("SELECT id,uniqueid FROM imas_questionset WHERE id IN ($includedlist)");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$includedbackref[$row[0]] = $row[1];
			}
		}

		//DB $query = "SELECT * FROM imas_questionset WHERE id IN ($clist)";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->query("SELECT * FROM imas_questionset WHERE id IN ($clist)");
		$qcnt = 0;
		//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			//DB $line['control'] = preg_replace('/includecodefrom\((\d+)\)/e','"includecodefrom(UID".$includedbackref["\\1"].")"',$line['control']);
			//DB $line['qtext'] = preg_replace('/includeqtextfrom\((\d+)\)/e','"includeqtextfrom(UID".$includedbackref["\\1"].")"',$line['qtext']);
      $line['control'] = preg_replace_callback('/includecodefrom\((\d+)\)/', function($matches) use ($includedbackref) {
          return "includecodefrom(UID".$includedbackref[$matches[1]].")";
        }, $line['control']);
      $line['qtext'] = preg_replace_callback('/includeqtextfrom\((\d+)\)/', function($matches) use ($includedbackref) {
          return "includeqtextfrom(UID".$includedbackref[$matches[1]].")";
        }, $line['qtext']);
			echo "\nSTART QUESTION\n";
			echo "QID\n";
			echo "$qcnt\n";
			$qcnt++;
			echo "\nUQID\n";
			echo rtrim($line['uniqueid']) . "\n";
			echo "\nLASTMOD\n";
			echo rtrim($line['lastmoddate']) . "\n";
			echo "\nDESCRIPTION\n";
			echo rtrim($line['description']) . "\n";
			echo "\nAUTHOR\n";
			echo rtrim($line['author']) . "\n";
			echo "\nCONTROL\n";
			echo rtrim($line['control']) . "\n";
			echo "\nQCONTROL\n";
			echo rtrim($line['qcontrol']) . "\n";
			echo "\nQTYPE\n";
			echo rtrim($line['qtype']) . "\n";
			echo "\nQTEXT\n";
			echo rtrim($line['qtext']) . "\n";
			echo "\nANSWER\n";
			echo rtrim($line['answer']) . "\n";
			echo "\nSOLUTION\n";
			echo rtrim($line['solution']) . "\n";
			echo "\nSOLUTIONOPTS\n";
			echo rtrim($line['solutionopts']) . "\n";
			echo "\nEXTREF\n";
			echo rtrim($line['extref']) . "\n";
			echo "\nLICENSE\n";
			echo rtrim($line['license']) . "\n";
			echo "\nANCESTORAUTHORS\n";
			echo rtrim($line['ancestorauthors']) . "\n";
			echo "\nOTHERATTRIBUTION\n";
			echo rtrim($line['otherattribution']) . "\n";
			if ($line['hasimg']==1) {
				echo "\nQIMGS\n";
				//DB $query = "SELECT var,filename FROM imas_qimages WHERE qsetid='{$line['id']}'";
				//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB while ($row = mysql_fetch_row($r2)) {
				$stm2 = $DBH->prepare("SELECT var,filename FROM imas_qimages WHERE qsetid=:qsetid");
				$stm2->execute(array(':qsetid'=>$line['id']));
				while ($row = $stm2->fetch(PDO::FETCH_NUM)) {
					echo $row[0].','.$row[1]. "\n";
				}
			}
		}
		exit;
	}
}

/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>

<script type="text/javascript">

var curlibs = '<?php echo $searchlibs ?>';
function libselect() {
	window.open('../course/libtree.php?libtree=popup&cid=<?php echo $cid ?>&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	document.getElementById("libnames").innerHTML = libn;
}
</script>

<?php echo $curBreadcrumb; ?>
	<div id="headerexport" class="pagetitle"><h2>Question Export</h2></div>
	<form id="pform" method=post action="export.php?cid=<?php echo $cid ?>">
	<h3>Questions Marked for Export</h3>

<?php
	if (count($checked)==0) {
		echo "<p>No Questions currently marked for export</p>\n";
	} else {
?>
		Check: <a href="#" onclick="return chkAllNone('pform','pchecked[]',true)">All</a> <a href="#" onclick="return chkAllNone('pform','pchecked[]',false)">None</a>

		<table cellpadding=5 class=gb>
		<thead>
			<tr>
				<th></th><th>Description</th><th>Type</th>
			</tr>
		</thead>
		<tbody>

<?php
		$alt = 0;
		for ($i=0;$i<count($page_pChecked);$i++) {
			if ($alt==0) {echo "			<tr class=even>"; $alt=1;} else {echo "			<tr class=odd>"; $alt=0;}
?>

				<td>
				<input type=checkbox name='pchecked[]' value='<?php echo $page_pChecked[$i]['id'] ?>' checked=checked>
				</td>
				<td><?php echo Sanitize::encodeStringForDisplay($page_pChecked[$i]['description']) ?></td>
				<td><?php echo $page_pChecked[$i]['qtype'] ?></td>
			</tr>

<?php
		}
?>

		</tbody>
		</table>

<?php
	}

	if (isset($_POST['finalize'])) { // step 2, initial list to export has already been posted
?>
			<h3>Export Settings</h3>
			<span class=form>Library Description</span>
			<span class=formright><textarea name="libdescription" rows=4 cols=60>
				</textarea></span><br class=form>
			<span class=form>Library Name</span>
			<span class=formright><input type=text name="libname" size="40"/></span><br class=form>

			<div class=submit><input name="export" type=submit value="Export"></div>
			</form>
<?php
	} else { //step 1, initial load
?>

		<h3>Potential Questions</h3>
		In Libraries: <span id="libnames"><?php echo $lnames ?></span>
		<input type=hidden name="libs" id="libs"  value="<?php echo $searchlibs ?>">
		<input type=button value="Select Libraries" onClick="libselect()"> <br>
		Search: <input type=text size=15 name=search value="<?php echo $search ?>">
		<input type=submit value="Update and Search">
		<input type=submit name="finalize" value="Finalize"><BR>

<?php
		if ($page_hasSearchResults==0) {
			echo "<p>No Questions matched search</p>\n";
		} else {
?>
		<script type="text/javascript" src="<?php echo $imasroot ?>/javascript/tablesorter.js"></script>
		Check: <a href="#" onclick="return chkAllNone('pform','nchecked[]',true)">All</a> <a href="#" onclick="return chkAllNone('pform','nchecked[]',false)">None</a>
		<table cellpadding=5 id=myTable class=gb>
		<thead>
			<tr><th></th><th>Description</th><th>Type</th></tr>
		</thead>
		<tbody>


<?php
			$alt = 0;
			for ($i=0;$i<count($page_nChecked);$i++) {
				if ($alt==0) {echo "			<tr class=even>"; $alt=1;} else {echo "			<tr class=odd>"; $alt=0;}
?>

				<td>
				<input type=checkbox name='nchecked[]' value='<?php echo $page_nChecked[$i]['id'] ?>'>
				</td>
				<td><?php echo $page_nChecked[$i]['description'] ?></td>
				<td><?php echo $page_nChecked[$i]['qtype'] ?></td>
			</tr>

<?php
			}
?>

		</tbody></table>

		<script type="text/javascript">
			initSortTable('myTable',Array(false,'S','S'),true);
		</script>

<?php
		}
		echo "<p>Note: Export of questions with static image files is not yet supported</p>";
	}
	echo "</form>";
}
	require("../footer.php");
?>
