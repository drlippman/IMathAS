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
require("../validate.php");
include("../includes/filehandler.php");


/*** pre-html data manipulation, including function code *******/
function copysub($items,$parent,&$addtoarr) {
	global $itemcnt,$toexport;
	global $checked;
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			if (array_search($parent.'-'.($k+1),$checked)!==FALSE) { //copy block
				$newblock = array();
				$newblock['name'] = $item['name'];
				$newblock['avail'] = $item['avail'];
				$newblock['startdate'] = $item['startdate'];
				$newblock['enddate'] = $item['enddate'];
				$newblock['SH'] = $item['SH'];
				$newblock['colors'] = $item['colors'];
				$newblock['public'] = $item['public'];
				$newblock['fixedheight'] = $item['fixedheight'];
				$newblock['items'] = array();
				copysub($item['items'],$parent.'-'.($k+1),$newblock['items']);
				$addtoarr[] = $newblock;
			} else {
				copysub($item['items'],$parent.'-'.($k+1),$addtoarr);
			}
		} else {
			if (array_search($item,$checked)!==FALSE) {
				$toexport[$itemcnt] = $item;
				$addtoarr[] = $itemcnt;
				$itemcnt++;
			}
		}
	}
}

function getsubinfo($items,$parent,$pre) {
	global $ids,$types,$names;
	foreach($items as $k=>$item) {
		if (is_array($item)) {
			$ids[] = $parent.'-'.($k+1);
			$types[] = $pre."Block";
			//DB $names[] = stripslashes($item['name']);
			$names[] = $item['name'];
			getsubinfo($item['items'],$parent.'-'.($k+1),$pre.'--');
		} else {
			$ids[] = $item;
			$arr = getiteminfo($item);
			$types[] = $pre.$arr[0];
			$names[] = $arr[1];
		}
	}
}

function getiteminfo($itemid) {
  global $DBH;
	//DB $query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error() . " queryString: " . $query);
	$stm = $DBH->prepare("SELECT itemtype,typeid FROM imas_items WHERE id=:id");
	$stm->execute(array(':id'=>$itemid));
  //DB $itemtype = mysql_result($result,0,0);
  //DB $typeid = mysql_result($result,0,1);
  list($itemtype, $typeid) = $stm->fetch(PDO::FETCH_NUM);
  switch($itemtype) {
    case ($itemtype==="InlineText"):
      //DB $query = "SELECT title FROM imas_inlinetext WHERE id=$typeid";
      $stm = $DBH->prepare("SELECT title FROM imas_inlinetext WHERE id=:id");
      break;
    case ($itemtype==="LinkedText"):
      //DB $query = "SELECT title FROM imas_linkedtext WHERE id=$typeid";
      $stm = $DBH->prepare("SELECT title FROM imas_linkedtext WHERE id=:id");
      break;
    case ($itemtype==="Forum"):
      //DB $query = "SELECT name FROM imas_forums WHERE id=$typeid";
      $stm = $DBH->prepare("SELECT name FROM imas_forums WHERE id=:id");
      break;
    case ($itemtype==="Assessment"):
      //DB $query = "SELECT name FROM imas_assessments WHERE id=$typeid";
      $stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
      break;
  }
  $stm->execute(array(':id'=>$typeid));
  //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
  //DB $name = mysql_result($result,0,0);
  $name = $stm->fetchColumn(0);
	return array($itemtype,$name);
}

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = $installname . " Item Export";
$cid = Sanitize::courseId($_GET['cid']);
$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">"
	. Sanitize::encodeStringForDisplay($coursename) . "</a> &gt; Export Course Items</div>\n";


if (!(isset($teacherid))) {   //NO PERMISSIONS
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_POST['export'])) { //STEP 2 DATA PROCESSING, OUTPUT FILE HERE
	header('Content-type: text/imas');
	header("Content-Disposition: attachment; filename=\"imasitemexport.imas\"");

	$checked = $_POST['checked'];

	//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));

	$itemcnt = 0;
	$toexport = array();
	$qcnt = 0;
	//DB $items = unserialize(mysql_result($result,0,0));
	$items = unserialize($stm->fetchColumn(0));
	$newitems = array();
	$qtoexport = array();
	$qsettoexport = array();

	copysub($items,'0',$newitems);
	//print_r($newitems);
	echo "EXPORT DESCRIPTION\n";
	echo $_POST['description']."\n";
	echo "ITEM LIST\n";
	echo serialize($newitems)."\n";
	$coursefiles = array();
	foreach ($toexport as $exportid=>$itemid) {
		echo "BEGIN ITEM\n";
		echo "ID\n";
		echo $exportid."\n";
		//DB $query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT itemtype,typeid FROM imas_items WHERE id=:id");
		$stm->execute(array(':id'=>$itemid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		echo "TYPE\n";
		echo $row[0] . "\n";
		switch ($row[0]) {
			case ($row[0]==="InlineText"):
				//DB $query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='{$row[1]}'";
				//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				$stm2 = $DBH->prepare("SELECT id,description,filename FROM imas_instr_files WHERE itemid=:itemid");
				$stm2->execute(array(':itemid'=>$row[1]));
				$filenames = array();
				$filedescr = array();
				//DB if (mysql_num_rows($r2)>0) {
				if ($stm2->rowCount()>0) {
					   //DB while ($frow = mysql_fetch_row($r2)) {
					   while ($frow = $stm2->fetch(PDO::FETCH_NUM)) {
						   $filedescr[$frow[0]] = $frow[1];
						   if ($GLOBALS['filehandertypecfiles'] == 's3') {
						   	   $filenames[$frow[0]] = getcoursefileurl($frow[2]);
						   } else {
						   	   $filenames[$frow[0]] = basename($frow[2]);
						   	   $coursefiles[] = $frow[2];
						   }
					   }
				}
				//DB $query = "SELECT * FROM imas_inlinetext WHERE id='{$row[1]}'";
				//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $line = mysql_fetch_array($r2, MYSQL_ASSOC);
				$stm2 = $DBH->prepare("SELECT * FROM imas_inlinetext WHERE id=:id");
				$stm2->execute(array(':id'=>$row[1]));
				$line = $stm2->fetch(PDO::FETCH_ASSOC);
				echo "TITLE\n";
				echo $line['title'] . "\n";
				echo "TEXT\n";
				if ($GLOBALS['filehandertypecfiles'] == 's3' && count($filenames)>0) {
					$line['text'] .= '<ul>';
					foreach (explode(',',$line['fileorder']) as $fid) {
						$line['text'] .= '<li><a href="'.$filenames[$fid].'">'.$filedescr[$fid].'</a></li>';
					}
					$line['text'] .= '</ul>';
				}
				echo $line['text'] . "\n";
				echo "AVAIL\n";
				echo $line['avail'] . "\n";
				echo "STARTDATE\n";
				echo $line['startdate'] . "\n";
				echo "ENDDATE\n";
				echo $line['enddate'] . "\n";
				echo "ONCAL\n";
				echo $line['oncal'] . "\n";
				echo "CALTAG\n";
				echo $line['caltag'] . "\n";
				if ((!isset($GLOBALS['filehandertypecfiles']) || $GLOBALS['filehandertypecfiles'] != 's3') && count($filenames)>0) {
					   echo "INSTRFILES\n";
					   foreach (explode(',',$line['fileorder']) as $fid) {
						  echo $filenames[$fid]. ':::'.$filedescr[$fid]."\n";
					   }
				}

				echo "END ITEM\n";
				break;
			case ($row[0]==="LinkedText"):
				//DB $query = "SELECT * FROM imas_linkedtext WHERE id='{$row[1]}'";
				//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $line = mysql_fetch_array($r2, MYSQL_ASSOC);
				$stm2 = $DBH->prepare("SELECT * FROM imas_linkedtext WHERE id=:id");
				$stm2->execute(array(':id'=>$row[1]));
				$line = $stm2->fetch(PDO::FETCH_ASSOC);
				if (substr($line['text'],0,5)=='file:') {
					if ($GLOBALS['filehandertypecfiles'] == 's3' && substr(strip_tags($line['text']),0,5)=="file:") {
						$line['text'] = getcoursefileurl(trim(substr(strip_tags($line['text']),5)));
					} else {
						$coursefiles[] = substr($line['text'],5);
						$line['text'] = 'file:'.basename(substr($line['text'],5));
					}
				}
				echo "TITLE\n";
				echo $line['title'] . "\n";
				echo "SUMMARY\n";
				echo $line['summary'] . "\n";
				echo "TEXT\n";
				echo $line['text'] . "\n";
				echo "AVAIL\n";
				echo $line['avail'] . "\n";
				echo "STARTDATE\n";
				echo $line['startdate'] . "\n";
				echo "ENDDATE\n";
				echo $line['enddate'] . "\n";
				echo "ONCAL\n";
				echo $line['oncal'] . "\n";
				echo "CALTAG\n";
				echo $line['caltag'] . "\n";
				echo "TARGET\n";
				echo $line['target'] . "\n";
				echo "END ITEM\n";

				break;
			case ($row[0]==="Forum"):
				//DB $query = "SELECT * FROM imas_forums WHERE id='{$row[1]}'";
				//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $line = mysql_fetch_array($r2, MYSQL_ASSOC);
				$stm2 = $DBH->prepare("SELECT * FROM imas_forums WHERE id=:id");
				$stm2->execute(array(':id'=>$row[1]));
				$line = $stm2->fetch(PDO::FETCH_ASSOC);
				echo "NAME\n";
				echo $line['name'] . "\n";
				echo "SUMMARY\n";
				echo $line['description'] . "\n";
				echo "AVAIL\n";
				echo $line['avail'] . "\n";
				echo "STARTDATE\n";
				echo $line['startdate'] . "\n";
				echo "ENDDATE\n";
				echo $line['enddate'] . "\n";
				echo "REPLYBY\n";
				echo $line['replyby'] . "\n";
				echo "POSTBY\n";
				echo $line['postby'] . "\n";
				echo "SETTINGS\n";
				foreach (array("defdisplay","points","cntingb","settings") as $setting) {
					echo "$setting=".$line[$setting]."\n";
				}
				echo "END ITEM\n";
				break;
			case ($row[0]==="Assessment"):
				//DB $query = "SELECT * FROM imas_assessments WHERE id='{$row[1]}'";
				//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $line = mysql_fetch_array($r2, MYSQL_ASSOC);
				$stm2 = $DBH->prepare("SELECT * FROM imas_assessments WHERE id=:id");
				$stm2->execute(array(':id'=>$row[1]));
				$line = $stm2->fetch(PDO::FETCH_ASSOC);
				echo "NAME\n";
				echo $line['name'] . "\n";
				echo "SUMMARY\n";
				echo $line['summary'] . "\n";
				echo "INTRO\n";
				echo $line['intro'] . "\n";
				echo "AVAIL\n";
				echo $line['avail'] . "\n";
				echo "STARTDATE\n";
				echo $line['startdate'] . "\n";
				echo "ENDDATE\n";
				echo $line['enddate'] . "\n";
				echo "REVIEWDATE\n";
				echo $line['reviewdate'] . "\n";
				echo "SETTINGS\n";
				foreach (array("timelimit","displaymethod","defpoints","defattempts","deffeedback","defpenalty","shuffle","password","cntingb","minscore","showcat","showhints","isgroup","allowlate","exceptionpenalty","noprint","groupmax","endmsg","eqnhelper","caltag","calrtag","showtips","deffeedbacktext","msgtoinstr","istutorial","viddata") as $setting) {
					echo "$setting=".$line[$setting]."\n";
				}
				echo "QUESTIONS\n";
				unset($newqorder);
				$qs = explode(',',$line['itemorder']);
				foreach ($qs as $q) {
					if (strpos($q,'~')===FALSE) {
						$qtoexport[$qcnt] = $q;
						$newqorder[] = $qcnt;
						$qcnt++;
					} else {
						unset($newsub);
						$subs = explode('~',$q);
						if (strpos($subs[0],'|')!==false) {
							$newsub[] = $subs[0];
							array_shift($subs);
						}
						foreach($subs as $subq) {
							$qtoexport[$qcnt] = $subq;
							$newsub[] = $qcnt;
							$qcnt++;
						}
						$newqorder[] = implode('~',$newsub);
					}
				}
				echo implode(',',$newqorder) . "\n";
				echo "END ITEM\n";
				break;
		} //end item switch
	} // end item export

	foreach ($qtoexport as $exportid=>$qid) { //export questions
		echo "BEGIN QUESTION\n";
		echo "QID\n";
		echo $exportid . "\n";

		//DB $query = "SELECT imas_questions.*,imas_questionset.uniqueid from imas_questions,imas_questionset ";
		//DB $query .= "WHERE imas_questions.questionsetid=imas_questionset.id AND imas_questions.id='$qid'";
		//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $line = mysql_fetch_array($r2, MYSQL_ASSOC);
		$query = "SELECT imas_questions.*,imas_questionset.uniqueid from imas_questions,imas_questionset ";
		$query .= "WHERE imas_questions.questionsetid=imas_questionset.id AND imas_questions.id=:id";
		$stm2 = $DBH->prepare($query);
		$stm2->execute(array(':id'=>$qid));
		$line = $stm2->fetch(PDO::FETCH_ASSOC);

		if (!empty($line['uniqueid'])) {
			echo "UQID\n";
			echo $line['uniqueid'] . "\n";
			echo "POINTS\n";
			echo $line['points'] . "\n";
			echo "PENALTY\n";
			echo $line['penalty'] . "\n";
			echo "ATTEMPTS\n";
			echo $line['attempts'] . "\n";
			echo "CATEGORY\n";
			echo $line['category'] . "\n";
			echo "REGEN\n";
			echo $line['regen'] . "\n";
			echo "SHOWANS\n";
			echo $line['showans'] . "\n";
			echo "END QUESTION\n";

			$qsettoexport[] = $line['questionsetid'];
		}
	}
	/*
	foreach ($qsettoexport as $qsetid) { //export questionset
		echo "BEGIN QSET\n";

		$query = "SELECT * from imas_questionset WHERE id='$qsetid'";
		$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($r2, MYSQL_ASSOC);
		echo "UNIQUEID\n";
		echo $line['uniqueid'] . "\n";
		echo "LASTMOD\n";
		echo $line['lastmoddate'] . "\n";
		echo "DESCRIPTION\n";
		echo $line['description'] . "\n";
		echo "AUTHOR\n";
		echo $line['author'] . "\n";
		echo "CONTROL\n";
		echo $line['control'] . "\n";
		echo "QCONTROL\n";
		echo $line['qcontrol'] . "\n";
		echo "QTEXT\n";
		echo $line['qtext'] . "\n";
		echo "QTYPE\n";
		echo $line['qtype'] . "\n";
		echo "ANSWER\n";
		echo $line['answer'] . "\n";
		echo "END QSET\n";
	}
	*/
	if (count($qsettoexport)>0) {
		$qstoexportlist = array_map('Sanitize::onlyInt', $qsettoexport);
		$qstoexportlist_query_placeholders = Sanitize::generateQueryPlaceholders($qstoexportlist);

		//first, lets pull any questions that have include__from so we can lookup backrefs
		//DB $query = "SELECT * FROM imas_questionset WHERE id IN ($qstoexportlist)";
		//DB $query .= " AND (control LIKE '%includecodefrom%' OR qtext LIKE '%includeqtextfrom%')";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		$query = "SELECT * FROM imas_questionset WHERE id IN ($qstoexportlist_query_placeholders)";
		$query .= " AND (control LIKE '%includecodefrom%' OR qtext LIKE '%includeqtextfrom%')";
		$stm = $DBH->prepare($query);
		$stm->execute($qstoexportlist);
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
		$imgfiles = array();
		//DB $query = "SELECT * FROM imas_questionset WHERE id IN ($qstoexportlist)";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT * FROM imas_questionset WHERE id IN ($qstoexportlist_query_placeholders)");
		$stm->execute($qstoexportlist);
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
			echo "BEGIN QSET\n";
			echo "\nUNIQUEID\n";
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
			//no static file handling happening here yet just export the info
			if ($line['hasimg']==1) {
				echo "\nQIMGS\n";
				//DB $query = "SELECT var,filename FROM imas_qimages WHERE qsetid='{$line['id']}'";
				//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB while ($row = mysql_fetch_row($r2)) {
				$stm2 = $DBH->prepare("SELECT var,filename FROM imas_qimages WHERE qsetid=:qsetid");
				$stm2->execute(array(':qsetid'=>$line['id']));
				while ($row = $stm2->fetch(PDO::FETCH_NUM)) {
					echo Sanitize::encodeStringForDisplay($row[0]).','.Sanitize::encodeStringForDisplay($row[1]). "\n";

				}
			}
			echo "END QSET\n";
		}



		//DB $query = "SELECT DISTINCT filename FROM imas_qimages WHERE qsetid IN ($qstoexportlist)";
		//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($r2)) {
		$stm2 = $DBH->query("SELECT DISTINCT filename FROM imas_qimages WHERE qsetid IN ($qstoexportlist_query_placeholders)");
		$stm2->execute($qstoexportlist);
		while ($row = $stm2->fetch(PDO::FETCH_NUM)) {
			if ($GLOBALS['filehandertypecfiles'] == 's3') {
				if (!file_exists("../assessment/qimages".DIRECTORY_SEPARATOR.trim($row[0]))) {
					copyqimage($row[0], realpath("../assessment/qimages").DIRECTORY_SEPARATOR. trim($row[0]));
				}
			}
			$imgfiles[] = realpath("../assessment/qimages").DIRECTORY_SEPARATOR. trim($row[0]);
		}
	}
	// need to work on
	/*include("../includes/tar.class.php");
	if (file_exists("../course/files/qimages.tar.gz")) {
		unlink("../course/files/qimages.tar.gz");
	}
	$tar = new tar();
	$tar->addFiles($imgfiles);
	$tar->toTar("../course/files/qimages.tar.gz",TRUE);
	*/
	if (class_exists('ZipArchive')) {
		$zip = new ZipArchive();
		if ($zip->open("../course/files/qimages.zip", ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE )===TRUE) {
			foreach ($imgfiles as $file) {
				$zip->addFile($file,basename($file));
			}
		}
		$zip->close();
	}

	if (class_exists('ZipArchive')) {
		$zip = new ZipArchive();
		if ($zip->open("../course/files/coursefilepack$cid.zip", ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE )===TRUE) {
			foreach ($coursefiles as $file) {
				if ($GLOBALS['filehandertypecfiles'] == 's3') {
					copycoursefile($file, realpath("../course/files").DIRECTORY_SEPARATOR.basename($file));
					$zip->addFile("../course/files/".basename($file),basename($file));
				} else {
					$zip->addFile("../course/files/$file",basename($file));
				}
			}
		}
		$zip->close();
	}


	exit;

} else { //STEP 1 DATA PROCESSING, INITIAL LOAD
	//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $items = unserialize(mysql_result($result,0,0));
	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$items = unserialize($stm->fetchColumn(0));
	$ids = array();
	$types = array();
	$names = array();

	getsubinfo($items,'0','');
}

require("../header.php");

if ($overwriteBody==1) {
 echo $body;
} else {
?>

	<?php echo $curBreadcrumb; ?>
	<div class="cpmid"><a href="ccexport.php?cid=<?php echo $cid ?>">Export for another Learning Management System</a></div>

	<h2>Export Course Items</h2>

	<p>This page will let you export your course items for backup or transfer to
	another server running this software.</p>

	<form id="qform" method=post action="exportitems.php?cid=<?php echo $cid ?>">
		<p>Export description<br/>
		<textarea rows=5 cols=50 name=description>Course Item Export</textarea></p>
		<p>Select items to export</p>

		Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>

		<table cellpadding=5 class=gb>
		<thead>
			<tr><th></th><th>Type</th><th>Title</th></tr>
		</thead>
		<tbody>
<?php
	$alt=0;
	for ($i = 0 ; $i<(count($ids)); $i++) {
		if ($alt==0) {echo "			<tr class=even>"; $alt=1;} else {echo "			<tr class=odd>"; $alt=0;}
?>
				<td>
				<input type=checkbox name='checked[]' value='<?php echo $ids[$i] ?>' checked=checked>
				</td>
				<td><?php echo $types[$i] ?></td>
				<td><?php echo $names[$i] ?></td>
			</tr>
<?php
	}
?>
		</tbody>
		</table>
		<p><input type=submit name="export" value="Export Items"></p>
	</form>
	<p>Once exported, <a href="../course/files/qimages.zip">download image files</a> to be put in assessment/qimages</p>
	<?php
	if (class_exists('ZipArchive')) {
		echo '<p>Once exported, <a href="../course/files/coursefilepack'.$cid.'.zip">download course files</a> to be put in course/files/</p>';
	}
	?>
	<p>If you were wanting to export this course to a different Learning Management System, you can try the <a href="ccexport.php?cid=<?php echo $cid;?>">
	Common Cartridge export</a></p>
<?php
}

require("../footer.php");
?>
