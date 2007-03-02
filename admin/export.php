<?php
//IMathAS:  Question library export
//(c) 2006 David Lippman
	require("../validate.php");
	if (!(isset($teacherid)) && $myrights<75) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$isadmin = false;
	$isgrpadmin = false;
	if (isset($_GET['cid']) && $_GET['cid']=="admin") {
		if ($myrights <75) {
			require("../header.php");
			echo "You need to log in as an admin to access this page";
			require("../footer.php");
			exit;
		} else if ($myrights < 100) {
			$isgrpadmin = true;
		} else if ($myrights == 100) {
			$isadmin = true;
		}
	} 
	
	$cid = $_GET['cid'];
	
	$checked = array_merge((array)$_POST['pchecked'],(array)$_POST['nchecked']);
	$clist = "'".implode("','",$checked)."'";
	$now = time();
	if (isset($_POST['export'])) {
		header('Content-type: text/imas');
		header("Content-Disposition: attachment; filename=\"imasexport.imas\"");
		echo "PACKAGE DESCRIPTION\n";
		echo $_POST['libdescription'];
		echo "\n";
		echo "\nSTART LIBRARY\nID\n1\nUID\n0\nLASTMODDATE\n$now\nNAME\n{$_POST['libname']}\nPARENT\n0\n";
		$qsetlist = implode(',',range(0,count($checked)-1));
		echo "\nSTART LIBRARY ITEMS\nLIBID\n1\nQSETIDS\n$qsetlist\n";
		$query = "SELECT * FROM imas_questionset WHERE id IN ($clist)";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$qcnt = 0;
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
		}
		exit;
	}
	
	require("../header.php");
?>
<script type="text/javascript">
function chkAll(frm, arr, mark) {
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if(frm.elements[i].name == arr) {
       frm.elements[i].checked = mark;
     }
   } catch(er) {}
  }
}
</script>
<?php
	if ($isadmin || $isgrpadmin) {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"admin.php\">Admin</a> &gt; Export Question Set</div>\n";
	} else {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Export Question Set</div>\n";
	}
	
	echo "<form method=post action=\"export.php?cid=$cid\">\n";
	
	echo "<h3>Questions Marked for Export</h3>\n";
	if (count($checked)==0) {
		echo "<p>No Questions currently marked for export</p>\n";
	} else {
		echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca\" value=\"1\" onClick=\"chkAll(this.form, 'pchecked[]', this.checked)\" checked=checked>\n";
		echo "<table cellpadding=5 class=gb>\n";
		echo "<thead><tr><th></th><th>Description</th><th>Type</th></tr></thead><tbody>\n";
		$query = "SELECT id,description,qtype FROM imas_questionset WHERE id IN ($clist)";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$alt=0;
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
			echo "<td>";
			echo "<input type=checkbox name='pchecked[]' value='{$line['id']}' checked=checked>";
			echo "</td><td>{$line['description']}</td><td>{$line['qtype']}</td>\n";
			echo "</tr>\n";
		}
		echo "</tbody></table>\n";
		
	}
	
	if (isset($_POST['finalize'])) {
		echo "<h3>Export Settings</h3>\n";
		echo "<span class=form>Library Description</span><span class=formright><textarea name=\"libdescription\" rows=4 cols=60></textarea></span><br class=form>\n";
		echo "<span class=form>Library Name</span><span class=formright><input type=text name=\"libname\" size=\"40\"/></span><br class=form>\n";
		
		echo "<div class=submit><input name=\"export\" type=submit value=\"Export\"></div>\n";
		echo "</form>\n";
		require("../footer.php");
		exit;
	}
	
	//remember search
	if (isset($_POST['search'])) {
		$safesearch = $_POST['search'];
		$search = stripslashes($safesearch);
		$search = str_replace('"','&quot;',$search);
		$sessiondata['lastsearch'] = str_replace(" ","+",$safesearch);
		writesessiondata();
	} else if (isset($sessiondata['lastsearch'])) {
		$safesearch = str_replace("+"," ",$sessiondata['lastsearch']);
		$search = stripslashes($safesearch);
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
	
	$llist = "'".implode("','",explode(',',$searchlibs))."'";
	
	//echo "<p><input type=button value=\"Done\" onClick=\"window.location='course.php?cid={$_GET['cid']}'\"></p>\n";
	
	echo "<h3>Potential Questions</h3>\n";
	echo <<<END
<script>
var curlibs = '$searchlibs';
function libselect() {
	window.open('../course/libtree.php?libtree=popup&cid=$cid&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	document.getElementById("libnames").innerHTML = libn;
}
</script>
END;
	if (substr($searchlibs,0,1)=="0") {
			$lnames[] = "Unassigned";
	}
	$query = "SELECT name FROM imas_libraries WHERE id IN ($llist)";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$lnames[] = $row[0];
	}
	$lnames = implode(", ",$lnames);
	echo "In Libraries: <span id=\"libnames\">$lnames</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"$searchlibs\">\n";
	echo " <input type=button value=\"Select Libraries\" onClick=\"libselect()\"> <br>"; 
	
	echo "Search: <input type=text size=15 name=search value=\"$search\"> <input type=submit value=\"Update and Search\">\n";
	echo "<input type=submit name=\"finalize\" value=\"Finalize\"><BR>\n";
	
	if (isset($search)) {
		if ($isadmin) {
			/*if (in_array('all',$searchlibs)) {
				$query = "SELECT id,description,qtype FROM imas_questionset WHERE description LIKE '%$safesearch%' ";
			} else {*/
				//$llist = implode(",",$searchlibs);
				
				$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
				$query .= "FROM imas_questionset,imas_library_items WHERE imas_questionset.description LIKE '%$safesearch%' ";
				$query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
			//}	
		} else if ($isgrpadmin) {
				$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
				$query .= "FROM imas_questionset,imas_library_items,imas_users WHERE imas_questionset.description LIKE '%$safesearch%' ";
				$query .= "AND imas_questionset.ownerid=imas_users.id ";
				$query .= "AND (imas_users.groupid='$groupid' OR imas_questionset.userights>0) "; 
				$query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
		} else {
			/*if (in_array('all',$searchlibs)) {
				$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
				$query .= "FROM imas_questionset,imas_library_items,imas_libraries WHERE imas_questionset.description LIKE '%$safesearch%' ";
				$query .= "AND (imas_questionset.ownerid=$userid OR imas_questionset.userights>0) "; 
				$query .= "AND imas_library_items.qsetid=imas_questionset.id AND ((imas_library_items.libid=imas_libraries.id ";
				$query .= "AND (imas_libraries.ownerid=$userid OR imas_libraries.userights>0)) OR imas_library_items.libid=0)";
			} else {*/
				//$llist = implode(",",$searchlibs);
			
				$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.qtype ";
				$query .= "FROM imas_questionset,imas_library_items WHERE imas_questionset.description LIKE '%$safesearch%' ";
				$query .= "AND (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0) "; 
				$query .= "AND imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($llist)";
			//}	
			
		}
		
		
		if (count($checked)>0) { $query .= "AND imas_questionset.id NOT IN ($clist);"; }
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		if (mysql_num_rows($result) == 0) {
			echo "<p>No Questions matched search</p>\n";
		} else {
			echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
			echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"chkAll(this.form, 'nchecked[]', this.checked)\">\n";
			echo "<table cellpadding=5 id=myTable class=gb>\n";
			echo "<thead><tr><th></th><th>Description</th><th>Type</th></tr></thead>\n";
			echo "<tbody>\n";
			$alt=0;
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
				echo "<td>";
				echo "<input type=checkbox name='nchecked[]' value='{$line['id']}'></td>";
				echo "<td>{$line['description']}</td><td>{$line['qtype']}</td>\n";
				echo "</tr>\n";
			} 
			echo "</tbody></table>\n";
			
			echo "<script type=\"text/javascript\">\n";
			echo "initSortTable('myTable',Array(false,'S','S'),true);\n";
			echo "</script>\n";
		}
	}
	echo "</form>";
	
	echo "<p>Note: Export of questions with static image files is not yet supported</p>";
	require("../footer.php");
?>
