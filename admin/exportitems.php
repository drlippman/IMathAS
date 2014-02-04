<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

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
			$names[] = stripslashes($item['name']);
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
	$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error() . " queryString: " . $query);
	$itemtype = mysql_result($result,0,0);
	$typeid = mysql_result($result,0,1);
	switch($itemtype) {
		case ($itemtype==="InlineText"):
			$query = "SELECT title FROM imas_inlinetext WHERE id=$typeid";
			break;
		case ($itemtype==="LinkedText"):
			$query = "SELECT title FROM imas_linkedtext WHERE id=$typeid";
			break;
		case ($itemtype==="Forum"):
			$query = "SELECT name FROM imas_forums WHERE id=$typeid";
			break;
		case ($itemtype==="Assessment"):
			$query = "SELECT name FROM imas_assessments WHERE id=$typeid";
			break;
	}
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$name = mysql_result($result,0,0);
	return array($itemtype,$name);
}
		
 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = $installname . " Item Export";
$cid = $_GET['cid'];
$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Export Course Items</div>\n";


if (!(isset($teacherid))) {   //NO PERMISSIONS
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_POST['export'])) { //STEP 2 DATA PROCESSING, OUTPUT FILE HERE
	header('Content-type: text/imas');
	header("Content-Disposition: attachment; filename=\"imasitemexport.imas\"");
	
	$checked = $_POST['checked'];
	
	$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());

	$itemcnt = 0;
	$toexport = array();
	$qcnt = 0;
	$items = unserialize(mysql_result($result,0,0));
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
		$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		echo "TYPE\n";
		echo $row[0] . "\n";
		switch ($row[0]) {
			case ($row[0]==="InlineText"):
				$query = "SELECT * FROM imas_inlinetext WHERE id='{$row[1]}'";
				$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				$line = mysql_fetch_array($r2, MYSQL_ASSOC);
				echo "TITLE\n";
				echo $line['title'] . "\n";
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
				$query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='{$row[1]}'";
				$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($r2)>0) {
					   $filenames = array();
					   $filedescr = array();
					   while ($row = mysql_fetch_row($r2)) {
						   $filenames[$row[0]] = basename($row[2]);
						   $filedescr[$row[0]] = $row[1];
						   $coursefiles[] = $row[2];
					   }
					   echo "INSTRFILES\n";
					   foreach (explode(',',$line['fileorder']) as $fid) {
						  echo $filenames[$fid]. ':::'.$filedescr[$fid]."\n";
					   }
				}
				
				echo "END ITEM\n";
				break;
			case ($row[0]==="LinkedText"):
				$query = "SELECT * FROM imas_linkedtext WHERE id='{$row[1]}'";
				$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				$line = mysql_fetch_array($r2, MYSQL_ASSOC);
				if (substr($line['text'],0,5)=='file:') {
					$coursefiles[] = substr($line['text'],5);
					$line['text'] = 'file:'.basename(substr($line['text'],5));
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
				$query = "SELECT * FROM imas_forums WHERE id='{$row[1]}'";
				$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				$line = mysql_fetch_array($r2, MYSQL_ASSOC);
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
				$query = "SELECT * FROM imas_assessments WHERE id='{$row[1]}'";
				$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				$line = mysql_fetch_array($r2, MYSQL_ASSOC);
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
		
		$query = "SELECT imas_questions.*,imas_questionset.uniqueid from imas_questions,imas_questionset ";
		$query .= "WHERE imas_questions.questionsetid=imas_questionset.id AND imas_questions.id='$qid'";
		$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($r2, MYSQL_ASSOC);
		
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
		$qstoexportlist = implode(',',$qsettoexport);
		//first, lets pull any questions that have include__from so we can lookup backrefs
		$query = "SELECT * FROM imas_questionset WHERE id IN ($qstoexportlist)";
		$query .= " AND (control LIKE '%includecodefrom%' OR qtext LIKE '%includeqtextfrom%')";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		$includedqs = array();
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if (preg_match_all('/includecodefrom\((\d+)\)/',$line['control'],$matches,PREG_PATTERN_ORDER) >0) {
				$includedqs = array_merge($includedqs,$matches[1]);
			}
			if (preg_match_all('/includeqtextfrom\((\d+)\)/',$line['qtext'],$matches,PREG_PATTERN_ORDER) >0) {
				$includedqs = array_merge($includedqs,$matches[1]);
			}
		}
		$includedbackref = array();
		if (count($includedqs)>0) {
			$includedlist = implode(',',$includedqs);
			$query = "SELECT id,uniqueid FROM imas_questionset WHERE id IN ($includedlist)";
			$result = mysql_query($query) or die("Query failed : $query"  . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$includedbackref[$row[0]] = $row[1];		
			}
		}
		$imgfiles = array();
		$query = "SELECT * FROM imas_questionset WHERE id IN ($qstoexportlist)";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$qcnt = 0;
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$line['control'] = preg_replace('/includecodefrom\((\d+)\)/e','"includecodefrom(UID".$includedbackref["\\1"].")"',$line['control']);
			$line['qtext'] = preg_replace('/includeqtextfrom\((\d+)\)/e','"includeqtextfrom(UID".$includedbackref["\\1"].")"',$line['qtext']);
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
			echo "\nEXTREF\n";
			echo rtrim($line['extref']) . "\n";
			//no static file handling happening here yet just export the info
			if ($line['hasimg']==1) {
				echo "\nQIMGS\n";
				$query = "SELECT var,filename FROM imas_qimages WHERE qsetid='{$line['id']}'";
				$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				while ($row = mysql_fetch_row($r2)) {
					echo $row[0].','.$row[1]. "\n";
					
				}
			}
			echo "END QSET\n";
		}
		
		include("../includes/filehandler.php");
		
		$query = "SELECT DISTINCT filename FROM imas_qimages WHERE qsetid IN ($qstoexportlist)";
		$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($r2)) {
			if ($GLOBALS['filehandertypecfiles'] == 's3') {
				copyqimage($row[0], realpath("../assessment/qimages").DIRECTORY_SEPARATOR. trim($row[0]));
			}
			$imgfiles[] = realpath("../assessment/qimages").DIRECTORY_SEPARATOR. trim($row[0]);
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
	$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());

	$items = unserialize(mysql_result($result,0,0));
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
	<p>Once exported, <a href="../course/files/qimages.tar.gz">download image files</a> to be put in assessment/qimages</p>
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
