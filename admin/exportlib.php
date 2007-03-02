<?php
//IMathAS:  Library tree export
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
	
		
	if (isset($_POST['packdescription'])) {
		header('Content-type: text/imas');
		header("Content-Disposition: attachment; filename=\"imasexport.imas\"");
		echo "PACKAGE DESCRIPTION\n";
		echo $_POST['packdescription'];
		echo "\n";
		
		$rootlibs = $_POST['libs'];  //root libs
		if (isset($_POST['rootlib'])) {
			array_unshift($rootlibs,$_POST['rootlib']);
		}
		$rootlist = "'".implode("','",$rootlibs)."'";
		
		$libcnt = 1;
		$libs = Array();
		$parents = Array();
		$names = Array();
		//$libs is systemid=>newid
		//$parents is childnewid=>parentnewid
		
		//get root lib names
		$query = "SELECT id,name,parent,uniqueid,lastmoddate FROM imas_libraries WHERE id IN ($rootlist)";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			if (!in_array($row[2],$rootlibs)) { //don't export children here
				$libs[$row[0]] = $libcnt;
				$parents[$libcnt] = 0;
				echo "\nSTART LIBRARY\n";
				echo "ID\n";
				echo rtrim($libcnt) . "\n";
				echo "UID\n";
				echo rtrim($row[3]) . "\n";
				echo "LASTMODDATE\n";
				echo rtrim($row[4]) . "\n";
				echo "NAME\n";
				echo rtrim($row[1]) . "\n";
				echo "PARENT\n";
				echo "0\n";
				$libcnt++;
			}
		}
		
		//lists child libraries
		function getchildlibs($lib) {
			global $libs,$libcnt;
			$query = "SELECT id,name,uniqueid,lastmoddate FROM imas_libraries WHERE parent='$lib'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				while ($row = mysql_fetch_row($result)) {
					if (!isset($libs[$row[0]])) { //in case someone clicked a parent and it's child
						$libs[$row[0]] = $libcnt;
						$parents[$libcnt] = $libs[$lib];
						echo "\nSTART LIBRARY\n";
						echo "ID\n";
						echo rtrim($libcnt) . "\n";
						echo "UID\n";
						echo rtrim($row[2]) . "\n";
						echo "LASTMODDATE\n";
						echo rtrim($row[3]) . "\n";
						echo "NAME\n";
						echo rtrim($row[1]) . "\n";
						echo "PARENT\n";
						echo rtrim($libs[$lib]) . "\n";
						$libcnt++;
						getchildlibs($row[0]);
					}
				}
			}
		}
		
		foreach ($rootlibs as $k=>$rootlib) {
			getchildlibs($rootlib);
		}
		
		$libarray = array_keys($libs);
		foreach($libarray as $k=>$v) {
			$libarray[$k] = "'".$v."'";
		}
		$liblist = implode(',',$libarray);
		//set question id array
		//$qassoc is systemqsetid=>newqsetid
		//$libitems is newlibid=>newqsetid
		$query = "SELECT qsetid,libid FROM imas_library_items WHERE libid IN ($liblist)";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$qassoc = Array();
		$libitems = Array();
		$qcnt = 0;
		while ($row = mysql_fetch_row($result)) {
			if (!isset($qassoc[$row[0]])) {$qassoc[$row[0]] = $qcnt;}
			$libitems[$libs[$row[1]]][] = $qcnt;
			$qcnt++;
		}
		
		foreach ($libs as $newid) {
			if (isset($libitems[$newid])) {
				echo "\nSTART LIBRARY ITEMS\n";
				echo "LIBID\n";
				echo rtrim($newid) . "\n";
				echo "QSETIDS\n";
				echo rtrim(implode(',',$libitems[$newid])) . "\n";
			}
		}
		
		$query = "SELECT imas_questionset.* FROM imas_questionset,imas_library_items ";
		$query .= "WHERE imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($liblist)";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo "\nSTART QUESTION\n";
			echo "QID\n";
			echo rtrim($qassoc[$line['id']]) . "\n";
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
	if ($isadmin || $isgrpadmin) {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"admin.php\">Admin</a> &gt; Export Question Set</div>\n";
	} else {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Export Libraries</div>\n";
	}
	
	echo "<form method=post action=\"exportlib.php?cid=$cid\">\n";
	
	echo "<h3>Library Export</h3>\n";
	echo "<p>Note:  If a parent library is selected, it's children libraries are included in the export, and heirarchy will ";
	echo "be maintained.  If libraries from different trees are selected, the topmost libraries in each branch selected will ";
	echo "be exported at the same level.</p>\n";
	
	$select = "all";
	include("../course/libtree.php");
	
	echo "<span class=form>Package Description</span><span class=formright><textarea name=\"packdescription\" rows=4 cols=60></textarea></span><br class=form>\n";
		
	echo "<input type=submit value=\"Export\">\n";
	echo "</form>";
	echo "<p>Note: Export of questions with static image files is not yet supported</p>";
	require("../footer.php");
?>
