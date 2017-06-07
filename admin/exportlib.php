<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

//boost operation time
@set_time_limit(0);
ini_set("max_input_time", "900");
ini_set("max_execution_time", "900");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

/*** master php includes *******/
require("../init.php");
require("../includes/filehandler.php");


/*** pre-html data manipulation, including function code *******/

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = $installname . " Library Export";


//data manipulation here
$isadmin = false;
$isgrpadmin = false;

	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid)) && $myrights<20) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_GET['cid']) && $_GET['cid']=="admin" && $myrights <20) {
 	$overwriteBody = 1;
	$body = "You need to log in as an admin to access this page";
} elseif (!(isset($_GET['cid'])) && $myrights < 75) {
 	$overwriteBody = 1;
	$body = "Please access this page from the menu links only.";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION

	$cid = (isset($_GET['cid'])) ? Sanitize::courseId($_GET['cid']) : "admin" ;

	if ($myrights < 100) {
		$isgrpadmin = true;
	} else if ($myrights == 100) {
		$isadmin = true;
	} else if (!isset($teacherid)) {
		$isadminpage = true;
	}

	if (isset($_POST['submit']) && $_POST['submit']=='Export') { //STEP 2 DATA MANIPULATION
		if (count($_POST['libs'])==0) {
			echo "No libraries selected";
			exit;
		}
		header('Content-type: text/imas');
		header("Content-Disposition: attachment; filename=\"imasexport.imas\"");
		echo "PACKAGE DESCRIPTION\n";
		echo $_POST['packdescription'];
		echo "\n";

		$rootlibs = $_POST['libs'];  //root libs
		if (isset($_POST['rootlib'])) {
			array_unshift($rootlibs,$_POST['rootlib']);
		}
		//DB $rootlist = "'".implode("','",$rootlibs)."'";
		$rootlist = implode(',', array_map('intval', $rootlibs));

		$libcnt = 1;
		$libs = Array();
		$parents = Array();
		$names = Array();
		$nonpriv = isset($_POST['nonpriv']);
		$noncopyright = isset($_POST['noncopyright']);
		//$libs is systemid=>newid
		//$parents is childnewid=>parentnewid

		//get root lib names
		$query = "SELECT id,name,parent,uniqueid,lastmoddate FROM imas_libraries WHERE id IN ($rootlist)";
		if ($nonpriv) {
			$query .= " AND userights>0";
		}
		$query .= " ORDER BY uniqueid";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
    $stm = $DBH->query($query);
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
			global $DBH,$libs,$libcnt,$nonpriv;
			//DB $query = "SELECT id,name,uniqueid,lastmoddate FROM imas_libraries WHERE parent='$lib'";
			//DB if ($nonpriv) {
				//DB $query .= " AND userights>0";
			//DB }
			//DB $query .= " ORDER BY uniqueid";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
				//DB while ($row = mysql_fetch_row($result)) {
			$query = "SELECT id,name,uniqueid,lastmoddate FROM imas_libraries WHERE parent=:parent";
			if ($nonpriv) {
        $query .= " AND userights>0";
      }
			$query .= " ORDER BY uniqueid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':parent'=>$lib));

			if ($stm->rowCount()>0) {
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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

		$liblist = implode(',', array_map('intval', $libarray));
		//set question id array
		//$qassoc is systemqsetid=>newqsetid
		//$libitems is newlibid=>newqsetid
		$query = "SELECT imas_library_items.qsetid,imas_library_items.libid FROM imas_library_items ";
		$query .= "JOIN imas_questionset ON imas_library_items.qsetid=imas_questionset.id ";
		$query .= "WHERE imas_library_items.libid IN ($liblist) AND imas_library_items.junkflag=0 AND imas_questionset.deleted=0 ";
		if ($nonpriv) {
			$query .= " AND imas_questionset.userights>0";
		}
		if ($noncopyright) {
			$query .= " AND imas_questionset.license>0";
		}
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
    $stm = $DBH->query($query);
		$qassoc = Array();
		$libitems = Array();
		$qcnt = 0;
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if (!isset($qassoc[$row[0]])) {$qassoc[$row[0]] = $qcnt; $qcnt++;}
			$libitems[$libs[$row[1]]][] = $qassoc[$row[0]];
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

		$imgfiles = array();
		$qlist = implode(',', array_map('intval', array_unique(array_keys($qassoc))));
		//first, lets pull any questions that have include__from so we can lookup backrefs
		$query = "SELECT * FROM imas_questionset WHERE id IN ($qlist)";
		//$query = "SELECT imas_questionset.* FROM imas_questionset,imas_library_items ";
		//$query .= "WHERE imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($liblist) AND imas_library_items.junkflag=0 ";
		if ($nonpriv) {
			$query .= " AND userights>0";
		}
		$query .= " AND (control LIKE '%includecodefrom%' OR qtext LIKE '%includeqtextfrom%')";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
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

		//$query = "SELECT imas_questionset.* FROM imas_questionset,imas_library_items ";
		//$query .= "WHERE imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($liblist) AND imas_library_items.junkflag=0 AND imas_questionset.deleted=0 ";
		$query = "SELECT * FROM imas_questionset WHERE id IN ($qlist)";
		if ($nonpriv) {
			$query .= " AND userights>0";
		}
		$query .= " ORDER BY uniqueid";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
    $stm = $DBH->query($query);
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
					$row[1] = trim($row[1]);
					echo $row[0].','.$row[1]. "\n";
					if ($GLOBALS['filehandertypecfiles'] == 's3') {
						copyqimage($row[1],realpath("../assessment/qimages").DIRECTORY_SEPARATOR.$row[1]);
					}
					$imgfiles[] = realpath("../assessment/qimages").DIRECTORY_SEPARATOR.$row[1];
				}
			}
		}
		// need to work on
		include("../includes/tar.class.php");
		if (file_exists("../course/files/qimages.tar.gz")) {
			unlink("../course/files/qimages.tar.gz");
		}
		$tar = new tar();
		$tar->addFiles($imgfiles);
		$tar->toTar("../course/files/qimages.tar.gz",TRUE);

		exit;
	} else {  //STEP 1 DATA MANIPULATION

		if ($isadmin || $isgrpadmin || $isadminpage) {
			$curBreadcrumb =  "<div class=breadcrumb>$breadcrumbbase <a href=\"admin.php\">Admin</a> &gt; Export libraries</div>\n";
		} else {
			$curBreadcrumb =  "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Export Libraries</div>\n";
		}
	}
}

/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {

	echo $curBreadcrumb
?>
	<form method=post action="exportlib.php?cid=<?php echo $cid ?>">

		<div id="headerexportlib" class="pagetitle"><h2>Library Export</h2></div>
		<p>Note:  If a parent library is selected, it's children libraries are included in the export,
		and heirarchy will be maintained.  If libraries from different trees are selected, the topmost
		libraries in each branch selected will be exported at the same level.</p>

<?php
	$select = "all";
	include("../course/libtree.php");
?>
		<span class="form">Limit to non-private questions and libs?</span>
		<span class="formright">
			<input type="checkbox" name="nonpriv" checked="checked" />
		</span><br class="form" />
		<span class="form">Limit to non-copyrighted questions?</span>
		<span class="formright">
			<input type="checkbox" name="noncopyright" checked="checked" />
		</span><br class="form" />
		<span class=form>Package Description</span>
		<span class=formright>
			<textarea name="packdescription" rows=4 cols=60></textarea>
		</span><br class=form>

		<input type=submit name="submit" value="Export"><br/>
		Once exported, <a href="../course/files/qimages.tar.gz">download image files</a> to be put in assessment/qimages
	</form>


<?php
}
require("../footer.php");
?>
