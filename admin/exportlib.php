<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

//boost operation time

ini_set("max_execution_time", "900");




/*** master php includes *******/
require_once "../init.php";
require_once "../includes/filehandler.php";


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
		$rootlibs = array_map('intval', explode(',', $_POST['libs']));
		if (count($rootlibs)==0) {
			echo "No libraries selected";
			exit;
		}
		header('Content-type: text/imas');
		header("Content-Disposition: attachment; filename=\"imasexport.imas\"");
		echo "PACKAGE DESCRIPTION\n";
		echo Sanitize::simpleASCII($_POST['packdescription']);
		echo "\n";
		echo "INSTALLNAME\n";
		echo $installname;
		echo "\n";

		$rootlist = implode(',', $rootlibs);
		if (trim($rootlist)=='') {
			exit;
		}

		$libcnt = 1;
		$libs = Array();
		$parents = Array();
		$names = Array();
		$nonpriv = isset($_POST['nonpriv']);
		$noncopyright = isset($_POST['noncopyright']);
		//$libs is systemid=>newid
		//$parents is childnewid=>parentnewid

		//get root lib names
		$query = "SELECT id,name,parent,uniqueid,lastmoddate,ownerid,userights FROM imas_libraries WHERE id IN ($rootlist)";
		if ($nonpriv) {
			$query .= " AND userights>0";
		}
		$query .= " ORDER BY uniqueid";
		$stm = $DBH->query($query);
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if (!in_array($row[2],$rootlibs)) { //don't export children here
				$libs[$row[0]] = $libcnt;
				$parents[$libcnt] = 0;
				echo "\nSTART LIBRARY\n";
				echo "ID\n";
				echo Sanitize::forRawExport(rtrim($libcnt)) . "\n";
				echo "UID\n";
				echo Sanitize::forRawExport(Sanitize::forRawExport(rtrim($row[3]))) . "\n";
				echo "LASTMODDATE\n";
				echo Sanitize::forRawExport(rtrim($row[4])) . "\n";
				echo "OWNERID\n";
				echo Sanitize::forRawExport(rtrim($row[5])) . "\n";
				echo "USERIGHTS\n";
				echo Sanitize::forRawExport(rtrim($row[6])) . "\n";
				echo "NAME\n";
				echo Sanitize::forRawExport(rtrim($row[1])) . "\n";
				echo "PARENT\n";
				echo "0\n";
				$libcnt++;
			}
		}

		//lists child libraries
		function getchildlibs($lib) {
			global $DBH,$libs,$libcnt,$nonpriv;
			$query = "SELECT id,name,uniqueid,lastmoddate FROM imas_libraries WHERE parent=:parent AND deleted=0";
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
						echo Sanitize::forRawExport(rtrim($libcnt)) . "\n";
						echo "UID\n";
						echo Sanitize::forRawExport(rtrim($row[2])) . "\n";
						echo "LASTMODDATE\n";
						echo Sanitize::forRawExport(rtrim($row[3])) . "\n";
						echo "OWNERID\n";
						echo Sanitize::forRawExport(rtrim($row[5])) . "\n";
						echo "USERIGHTS\n";
						echo Sanitize::forRawExport(rtrim($row[6])) . "\n";
						echo "NAME\n";
						echo Sanitize::forRawExport(rtrim($row[1])) . "\n";
						echo "PARENT\n";
						echo Sanitize::forRawExport(rtrim($libs[$lib])) . "\n";
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

		if (count($libarray)==0) {
			exit;
		}
		$liblist = implode(',', array_map('intval', $libarray));
		//set question id array
		//$qassoc is systemqsetid=>newqsetid
		//$libitems is newlibid=>newqsetid
		$query = "SELECT imas_library_items.qsetid,imas_library_items.libid FROM imas_library_items ";
		$query .= "JOIN imas_questionset ON imas_library_items.qsetid=imas_questionset.id ";
		$query .= "WHERE imas_library_items.libid IN ($liblist) AND imas_library_items.junkflag=0  AND imas_library_items.deleted=0 AND imas_questionset.deleted=0 ";
		if ($nonpriv) {
			$query .= " AND imas_questionset.userights>0";
		}
		if ($noncopyright) {
			$query .= " AND imas_questionset.license>0";
		}
		$stm = $DBH->query($query);
		$qassoc = Array();
		$libitems = Array();
		$qcnt = 0;
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if (!isset($qassoc[$row[0]])) {$qassoc[$row[0]] = $qcnt; $qcnt++;}
			$libitems[$libs[$row[1]]][] = $qassoc[$row[0]];
		}

		foreach ($libs as $newid) {
			if (isset($libitems[$newid])) {
				echo "\nSTART LIBRARY ITEMS\n";
				echo "LIBID\n";
				echo Sanitize::forRawExport(rtrim($newid)) . "\n";
				echo "QSETIDS\n";
				echo Sanitize::forRawExport(rtrim(implode(',',$libitems[$newid]))) . "\n";
			}
		}

		$imgfiles = array();
		$qlist = implode(',', array_map('intval', array_unique(array_keys($qassoc))));
		if (trim($qlist) != '') {
			//first, lets pull any questions that have include__from so we can lookup backrefs
			$query = "SELECT * FROM imas_questionset WHERE id IN ($qlist)";
			//$query = "SELECT imas_questionset.* FROM imas_questionset,imas_library_items ";
			//$query .= "WHERE imas_library_items.qsetid=imas_questionset.id AND imas_library_items.libid IN ($liblist) AND imas_library_items.junkflag=0 ";
			if ($nonpriv) {
				$query .= " AND userights>0";
			}
			$query .= " AND (control LIKE '%includecodefrom%' OR qtext LIKE '%includeqtextfrom%')";
			$stm = $DBH->query($query);
			$includedqs = array();
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
			$stm = $DBH->query($query);
			while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
				$line['control'] = preg_replace_callback('/includecodefrom\((\d+)\)/', function($matches) use ($includedbackref) {
						return "includecodefrom(UID".$includedbackref[$matches[1]].")";
				}, $line['control']);
				$line['qtext'] = preg_replace_callback('/includeqtextfrom\((\d+)\)/', function($matches) use ($includedbackref) {
						return "includeqtextfrom(UID".$includedbackref[$matches[1]].")";
				}, $line['qtext']);
				echo "\nSTART QUESTION\n";
				echo "QID\n";
				echo Sanitize::forRawExport(rtrim($qassoc[$line['id']])) . "\n";
				echo "\nUQID\n";
				echo Sanitize::forRawExport(rtrim($line['uniqueid'])) . "\n";
				echo "\nLASTMOD\n";
				echo Sanitize::forRawExport(rtrim($line['lastmoddate'])) . "\n";
				echo "\nDESCRIPTION\n";
				echo Sanitize::forRawExport(rtrim($line['description'])) . "\n";
				echo "\nAUTHOR\n";
				echo Sanitize::forRawExport(rtrim($line['author'])) . "\n";
				echo "\nOWNERID\n";
				echo Sanitize::forRawExport(rtrim($line['ownerid'])) . "\n";
				echo "\nUSERIGHTS\n";
				echo Sanitize::forRawExport(rtrim($line['userights'])) . "\n";
				echo "\nCONTROL\n";
				echo Sanitize::forRawExport(rtrim($line['control'])) . "\n";
				echo "\nQCONTROL\n";
				echo Sanitize::forRawExport(rtrim($line['qcontrol'])) . "\n";
				echo "\nQTYPE\n";
				echo Sanitize::forRawExport(rtrim($line['qtype'])) . "\n";
				echo "\nQTEXT\n";
				echo Sanitize::forRawExport(rtrim($line['qtext'])) . "\n";
				echo "\nANSWER\n";
				echo Sanitize::forRawExport(rtrim($line['answer'])) . "\n";
				echo "\nSOLUTION\n";
				echo Sanitize::forRawExport(rtrim($line['solution'])) . "\n";
				echo "\nSOLUTIONOPTS\n";
				echo Sanitize::forRawExport(rtrim($line['solutionopts'])) . "\n";
				echo "\nEXTREF\n";
				echo Sanitize::forRawExport(rtrim($line['extref'])) . "\n";
				echo "\nLICENSE\n";
				echo Sanitize::forRawExport(rtrim($line['license'])) . "\n";
				echo "\nANCESTORAUTHORS\n";
				echo Sanitize::forRawExport(rtrim($line['ancestorauthors'])) . "\n";
				echo "\nOTHERATTRIBUTION\n";
				echo Sanitize::forRawExport(rtrim($line['otherattribution'])) . "\n";
				if ($line['hasimg']==1) {
					echo "\nQIMGS\n";
					$stm2 = $DBH->prepare("SELECT var,filename,alttext FROM imas_qimages WHERE qsetid=:qsetid");
					$stm2->execute(array(':qsetid'=>$line['id']));
					while ($row = $stm2->fetch(PDO::FETCH_NUM)) {
						echo Sanitize::forRawExport($row[0].','.getqimageurl($row[1],true).','.$row[2]). "\n";
					}
				}
			}
		}

		exit;
	} else {  //STEP 1 DATA MANIPULATION

		if ($isadmin || $isgrpadmin || $isadminpage) {
			$curBreadcrumb =  "<div class=breadcrumb>$breadcrumbbase <a href=\"admin2.php\">Admin</a> &gt; Export libraries</div>\n";
		} else {
			$curBreadcrumb =  "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Export Libraries</div>\n";
		}
	}
}

/******* begin html output ********/
$placeinhead = '<link rel="stylesheet" href="'.$staticroot.'/javascript/accessibletree.css" type="text/css" />';
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/accessibletree.js?v=031111"></script>';

require_once "../header.php";

if ($overwriteBody==1) {
	echo $body;
} else {

	echo $curBreadcrumb
?>
	<form method=post action="exportlib.php?cid=<?php echo $cid ?>">

		<div id="headerexportlib" class="pagetitle"><h1>Library Export</h1></div>
		<p>Note:  If a parent library is selected, it's children libraries are included in the export,
		and heirarchy will be maintained.  If libraries from different trees are selected, the topmost
		libraries in each branch selected will be exported at the same level.</p>

<?php
	$select = "all";
	$_GET['selectrights'] = 0;
	require_once "../course/libtree3.php";
?>
		<span class="form">Limit</span>
		<span class="formright">
			<label><input type="checkbox" name="nonpriv" checked="checked" />
			Limit to non-private questions and libs
			</label><br/>
			<label><input type="checkbox" name="noncopyright" checked="checked" />
			Limit to non-copyrighted questions
			</label>
		</span><br class="form" />
	
		</span><br class="form" />
		<label for=packdescription class=form>Package Description</label>
		<span class=formright>
			<textarea name="packdescription" id="packdescription" rows=4 cols=60></textarea>
		</span><br class=form>

		<input type=submit name="submit" value="Export"><br/>

	</form>


<?php
}
require_once "../footer.php";
?>
