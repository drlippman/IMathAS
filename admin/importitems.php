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

/*** pre-html data manipulation, including function code *******/
function getsubinfo($items,$parent,$pre) {
	global $ids,$types,$names,$item,$parents;
	foreach($items as $k=>$anitem) {
		if (is_array($anitem)) {
			$ids[] = $parent.'-'.($k+1);
			$types[] = $pre."Block";
			$names[] = stripslashes($anitem['name']);
			$parents[] = $parent;
			getsubinfo($anitem['items'],$parent.'-'.($k+1),$pre.'--');
		} else {
			$ids[] = $anitem;
			$parents[] = $parent;
			$types[] = $pre.$item[$anitem]['type'];
			if (isset($item[$anitem]['name'])) {
				$names[] = $item[$anitem]['name'];
			} else {
				$names[] = $item[$anitem]['title'];
			}
		}
	}
}

function additem($itemtoadd,$item,$questions,$qset) {
	
	global $newlibs;
	global $userid, $userights, $cid, $missingfiles;
	$mt = microtime();
	if ($item[$itemtoadd]['type'] == "Assessment") {
		//add assessment.  set $typeid
		$settings = explode("\n",$item[$itemtoadd]['settings']);
		foreach ($settings as $set) {
			$pair = explode('=',$set);
			$item[$itemtoadd][$pair[0]] = $pair[1];
		}
		$setstoadd = explode(',','name,summary,intro,avail,startdate,enddate,reviewdate,timelimit,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,password,cntingb,minscore,showcat,showhints,isgroup,allowlate,exceptionpenalty,noprint,groupmax,endmsg,eqnhelper,caltag,calrtag,showtips,deffeedbacktext,istutorial,viddata');
		$valsets = "'$cid'";
		$tosets = 'courseid';
		foreach ($setstoadd as $set) {
			if (isset($item[$itemtoadd][$set])) {
				$tosets .= ','.$set;
				$valsets .= ',\''.$item[$itemtoadd][$set].'\'';
			}
		}
		$query = "INSERT INTO imas_assessments ($tosets) VALUES ($valsets)";
		/*'{$item[$itemtoadd]['name']}','{$item[$itemtoadd]['summary']}','{$item[$itemtoadd]['intro']}',";
		$query .= "'{$item[$itemtoadd]['avail']}','{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}','{$item[$itemtoadd]['reviewdate']}','{$item[$itemtoadd]['timelimit']}',";
		$query .= "'{$item[$itemtoadd]['displaymethod']}','{$item[$itemtoadd]['defpoints']}','{$item[$itemtoadd]['defattempts']}',";
		$query .= "'{$item[$itemtoadd]['deffeedback']}','{$item[$itemtoadd]['defpenalty']}','{$item[$itemtoadd]['shuffle']}','{$item[$itemtoadd]['password']}','{$item[$itemtoadd]['cntingb']}',";
		*/
		mysql_query($query) or die("error on: $query: " . mysql_error());
		$typeid = mysql_insert_id();
					
		//determine question to be added
		//$qtoadd = explode(',',$item[$itemtoadd]['questions']);  //FIX!!! can be ~ separated as well
		//FIX!!: check on format issues for grouped assessments.
		if (trim($item[$itemtoadd]['questions'])=='') {
			$qtoadd = array();
		} else {
			$qtoadd = preg_split('/[,~]/',$item[$itemtoadd]['questions']);
		}
		$allqids = array();
		foreach ($qtoadd as $qid) {
			//add question or get system id. 
			$query = "SELECT id,adddate FROM imas_questionset WHERE uniqueid='{$questions[$qid]['uqid']}' AND deleted=0";
			$result = mysql_query($query) or die("error on: $query: " . mysql_error());
			$questionexists = (mysql_num_rows($result)>0);
			
			if ($questionexists && $_POST['merge']==1) {
				$questions[$qid]['qsetid'] = mysql_result($result,0,0);
				$n = array_search($questions[$qid]['uqid'],$qset['uniqueid']);
				if ($qset['lastmod'][$n]>mysql_result($result,0,1)) { //if old question
					$now = time();
					if (!empty($qset['qimgs'][$n])) {
						$hasimg = 1;
					} else {
						$hasimg = 0;
					}
					$query = "UPDATE imas_questionset SET description='{$qset['description'][$n]}',";
					$query .= "author='{$qset['author'][$n]}',qtype='{$qset['qtype'][$n]}',";
					$query .= "control='{$qset['control'][$n]}',qcontrol='{$qset['qcontrol'][$n]}',";
					$query .= "qtext='{$qset['qtext'][$n]}',answer='{$qset['answer'][$n]}',";
					$query .= "extref='{$qset['extref'][$n]}',lastmoddate=$now,adddate=$now,hasimg=$hasimg ";
					$query .= " WHERE id='{$questions[$qid]['qsetid']}' AND (ownerid='$userid' OR userights>3)";
					mysql_query($query) or die("error on: $query: " . mysql_error());
					if (mysql_affected_rows()>0 && $hasimg==1) {
						//not efficient, but sufficient :)
						$query = "DELETE FROM imas_qimages WHERE qsetid='{$questions[$qid]['qsetid']}'";
						mysql_query($query) or die("Import failed on $query: " . mysql_error());
						$qimgs = explode("\n",$qset['qimgs'][$n]);
						foreach($qimgs as $qimg) {
							$p = explode(',',$qimg);
							$query = "INSERT INTO imas_qimages (qsetid,var,filename) VALUES ('{$questions[$qid]['qsetid']}','{$p[0]}','{$p[1]}')";
							mysql_query($query) or die("Import failed on $query: " . mysql_error());
						}
					}
				}
			} else if ($questionexists && $_POST['merge']==-1) {
				$questions[$qid]['qsetid'] = mysql_result($result,0,0);
			} else { //add question, and assign to default library
				$n = array_search($questions[$qid]['uqid'],$qset['uniqueid']);
				if ($questionexists && $_POST['merge']==0) {
					$questions[$qid]['uqid'] = substr($mt,11).substr($mt,2,2).$qid;
					$qset['uniqueid'][$n] = $questions[$qid]['uqid'];
				}
				$now = time();
				if (!empty($qset['qimgs'][$n])) {
					$hasimg = 1;
				} else {
					$hasimg = 0;
				}
				$query = "INSERT INTO imas_questionset (adddate,lastmoddate,uniqueid,ownerid,";
				$query .= "author,userights,description,qtype,control,qcontrol,qtext,answer,extref,hasimg) ";
				$query .= "VALUES ($now,'{$qset['lastmod'][$n]}','{$qset['uniqueid'][$n]}',";
				$query .= "'$userid','{$qset['author'][$n]}','$userights',";
				$query .= "'{$qset['description'][$n]}','{$qset['qtype'][$n]}','{$qset['control'][$n]}',";
				$query .= "'{$qset['qcontrol'][$n]}','{$qset['qtext'][$n]}','{$qset['answer'][$n]}','{$qset['extref'][$n]}',$hasimg)";
				mysql_query($query) or die("error on: $query: " . mysql_error());
				$questions[$qid]['qsetid'] = mysql_insert_id();
				if ($hasimg==1) {
					$qimgs = explode("\n",$qset['qimgs'][$n]);
					foreach($qimgs as $qimg) {
						$p = explode(',',$qimg);
						$query = "INSERT INTO imas_qimages (qsetid,var,filename) VALUES ({$questions[$qid]['qsetid']},'{$p[0]}','{$p[1]}')";
							
						mysql_query($query) or die("Import failed on $query: " . mysql_error());
					}
				}
				foreach ($newlibs as $lib) {
					$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('$lib','{$questions[$qid]['qsetid']}','$userid')";
					mysql_query($query) or die("error on: $query: " . mysql_error());
				}
			}
			$allqids[] = $questions[$qid]['qsetid'];
			
			//add question $questions[$qid].  assessmentid is $typeid
			$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category,regen,showans)";
			$query .= "VALUES ($typeid,'{$questions[$qid]['qsetid']}','{$questions[$qid]['points']}',";
			$query .= "'{$questions[$qid]['attempts']}','{$questions[$qid]['penalty']}','{$questions[$qid]['category']}','{$questions[$qid]['regen']}','{$questions[$qid]['showans']}')";
			mysql_query($query) or die("error on: $query: " . mysql_error());
			$questions[$qid]['systemid'] = mysql_insert_id();
		}
		
		//resolve any includecodefrom links
		$qidstoupdate = array();
		$qidstocheck = implode(',',$allqids);
		//look up any refs to UIDs
		$query = "SELECT id,control,qtext FROM imas_questionset WHERE id IN ($qidstocheck) AND (control LIKE '%includecodefrom(UID%' OR qtext LIKE '%includeqtextfrom(UID%')";
		$result = mysql_query($query) or die("error on: $query: " . mysql_error());
		$includedqs = array();
		while ($row = mysql_fetch_row($result)) {
			$qidstoupdate[] = $row[0];
			if (preg_match_all('/includecodefrom\(UID(\d+)\)/',$row[1],$matches,PREG_PATTERN_ORDER) >0) {
				$includedqs = array_merge($includedqs,$matches[1]);
			}
			if (preg_match_all('/includeqtextfrom\(UID(\d+)\)/',$row[2],$matches,PREG_PATTERN_ORDER) >0) {
				$includedqs = array_merge($includedqs,$matches[1]);
			}
		}
		if (count($qidstoupdate)>0) {
			//lookup backrefs
			$includedbackref = array();
			if (count($includedqs)>0) {
				$includedlist = implode(',',$includedqs);
				$query = "SELECT id,uniqueid FROM imas_questionset WHERE uniqueid IN ($includedlist)";
				$result = mysql_query($query) or die("Query failed : $query"  . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$includedbackref[$row[1]] = $row[0];		
				}
			}
			$updatelist = implode(',',$qidstoupdate);
			$query = "SELECT id,control,qtext FROM imas_questionset WHERE id IN ($updatelist)";
			$result = mysql_query($query) or die("error on: $query: " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$control = addslashes(preg_replace('/includecodefrom\(UID(\d+)\)/e','"includecodefrom(".$includedbackref["\\1"].")"',$row[1]));
				$qtext = addslashes(preg_replace('/includeqtextfrom\(UID(\d+)\)/e','"includeqtextfrom(".$includedbackref["\\1"].")"',$row[2]));
				$query = "UPDATE imas_questionset SET control='$control',qtext='$qtext' WHERE id={$row[0]}";
				mysql_query($query) or die("error on: $query: " . mysql_error());
			}
		}
		
		//recreate itemorder 
		$item[$itemtoadd]['questions'] = preg_replace("/(\d+)/e",'$questions[\\1]["systemid"]',$item[$itemtoadd]['questions']);
		//write itemorder to db
		$query = "UPDATE imas_assessments SET itemorder='{$item[$itemtoadd]['questions']}' WHERE id=$typeid";
		mysql_query($query) or die("error on: $query: " . mysql_error());
	} else if ($item[$itemtoadd]['type'] == "Forum") {
		$settings = explode("\n",$item[$itemtoadd]['settings']);
		foreach ($settings as $set) {
			$pair = explode('=',$set);
			$item[$itemtoadd][$pair[0]] = $pair[1];
		}
		$query = "INSERT INTO imas_forums (name,description,courseid,avail,startdate,enddate,postby,replyby,defdisplay,points,cntingb,settings)";
		$query .= "VALUES ('{$item[$itemtoadd]['name']}','{$item[$itemtoadd]['summary']}','$cid',";
		$query .= "'{$item[$itemtoadd]['avail']}','{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}','{$item[$itemtoadd]['postby']}','{$item[$itemtoadd]['replyby']}',";
		$query .= "'{$item[$itemtoadd]['defdisplay']}','{$item[$itemtoadd]['points']}','{$item[$itemtoadd]['cntingb']}','{$item[$itemtoadd]['settings']}')";
		mysql_query($query) or die("error on: $query: " . mysql_error());
		$typeid = mysql_insert_id();
	} else if ($item[$itemtoadd]['type'] == "InlineText") {
		$query = "INSERT INTO imas_inlinetext (courseid,title,text,avail,startdate,enddate,oncal,caltag)";
		$query .= "VALUES ('$cid','{$item[$itemtoadd]['title']}','{$item[$itemtoadd]['text']}',";
		$query .= "'{$item[$itemtoadd]['avail']}','{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}','{$item[$itemtoadd]['oncal']}','{$item[$itemtoadd]['caltag']}')";
		mysql_query($query) or die("error on: $query: " . mysql_error());
		$typeid = mysql_insert_id();
		if (isset($item[$itemtoadd]['instrfiles'])) {
			$item[$itemtoadd]['instrfiles'] = explode("\n",$item[$itemtoadd]['instrfiles']);
			$fileorder = array();
			foreach ($item[$itemtoadd]['instrfiles'] as $fileinfo) {
				if (!file_exists("../course/files/$filename")) {
					$missingfiles[] = $filename;
				}
				list($filename,$filedescr) = explode(':::',addslashes($fileinfo));
				$query = "INSERT INTO imas_instr_files (description,filename,itemid) VALUES ";
				$query .= "('$filedescr','$filename',$typeid)";
				mysql_query($query) or die("error on: $query: " . mysql_error());
				$fileorder[] = mysql_insert_id();
			}
			$query = "UPDATE imas_inlinetext SET fileorder='".implode(',',$fileorder)."' WHERE id=$typeid";
			mysql_query($query) or die("error on: $query: " . mysql_error());
		}
	} else if ($item[$itemtoadd]['type'] == "LinkedText") {
		$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,avail,startdate,enddate,oncal,caltag,target)";
		$query .= "VALUES ('$cid','{$item[$itemtoadd]['title']}','{$item[$itemtoadd]['summary']}','{$item[$itemtoadd]['text']}',";
		$query .= "'{$item[$itemtoadd]['avail']}','{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}','{$item[$itemtoadd]['oncal']}','{$item[$itemtoadd]['caltag']}','{$item[$itemtoadd]['target']}')";
		mysql_query($query) or die("error on: $query: " . mysql_error());
		$typeid = mysql_insert_id();
	} else {
		return false;
	}
	
	//add item, set 
	$query = "INSERT INTO imas_items (courseid,itemtype,typeid) ";
	$query .= "VALUES ('$cid','{$item[$itemtoadd]['type']}',$typeid)";
	mysql_query($query) or die("error on: $query: " . mysql_error());
	$item[$itemtoadd]['systemid'] = mysql_insert_id();
	
	return ($item[$itemtoadd]['systemid']);		
}

function parsefile($file) {
	$handle = fopen($file,"r");
	if (!$handle) {
		echo "eek!  can't open file";
		exit;
	}
	$itemcnt = -1;
	$qcnt = -1;
	$qscnt = -1;
	$initem = false;
	$text = '';
	$line = '';
	$item = array();
	$questions = array();
	$qset = array();
	
	while (!feof($handle)) {
		$line = rtrim(fgets($handle, 4096));
		switch ((string)$line) {
			case  "EXPORT DESCRIPTION":
				$desc = rtrim(fgets($handle, 4096));
				break;
			case  "ITEM LIST":
				$itemlist = rtrim(fgets($handle, 44096));
				break;	
			case  "BEGIN ITEM":
				$itemcnt++;
				$initem = true;
				unset($part);
				break;
			case  "END ITEM":
				if (isset($part)) {
					$item[$curid][$part] = rtrim($text);
				}
				$initem = false;
				$text = '';
				unset($part);
				unset($curid);
				break;
			case  "ID":
				$curid = rtrim(fgets($handle, 4096));
				break;
			case  "TYPE":
			case  "TITLE":
			case  "NAME":
			case  "TEXT":
			case  "SUMMARY":  //note: use this for forum description
			case  "INTRO":
			case  "QUESTIONS":
			case  "STARTDATE":
			case  "ENDDATE":
			case  "POSTBY":
			case  "REPLYBY":
			case  "AVAIL":
			case  "REVIEWDATE":
			case  "INSTRFILES";
			case  "ONCAL":
			case  "CALTAG":
			case  "TARGET":
			case  "SETTINGS":
				if (isset($part)) {
					$item[$curid][$part] = rtrim($text);
				}
				$text = '';
				$part = strtolower($line);
				break;
			case  "BEGIN QUESTION":
				$qcnt++;
				$initem = true;
				unset($part);
				break;
			case  "END QUESTION":
				if (isset($part)) {
					$questions[$curqid][$part] = rtrim($text);
				}
				$initem = false;
				$text = '';
				unset($part);
				unset($curqid);
				break;
			case  "QID":
				$curqid = rtrim(fgets($handle, 4096));
				break;
			case  "UQID":
			case  "POINTS":
			case  "PENALTY":
			case  "ATTEMPTS":
			case  "REGEN":
			case  "SHOWANS":
			case  "CATEGORY":
				if (isset($part)) {
					$questions[$curqid][$part] = rtrim($text);
				}
				$text = '';
				$part = strtolower($line);
				break;
			case  "BEGIN QSET":
				$qscnt++;
				$initem = true;
				unset($part);
				break;
			case  "END QSET":
				if (isset($part)) {
					$qset[$part][$qscnt] = rtrim($text);
				}
				$initem = false;
				$text = '';
				unset($part);
				break;
			case  "DESCRIPTION":
			case  "UNIQUEID":
			case  "LASTMOD":
			case  "AUTHOR":
			case  "CONTROL":
			case  "QCONTROL":
			case  "QTEXT":
			case  "QTYPE":
			case  "EXTREF":
			case  "QIMGS":
			case  "ANSWER":
				if (isset($part)) {
					$qset[$part][$qscnt] = rtrim($text);
				}
				$text = '';
				$part = strtolower($line);
				break;
			default:
				if (isset($part) && $initem) {
					$text .= $line . "\n";
				}
				break;
		}
	}
	
	return array($desc,$itemlist,$item,$questions,$qset);
}

function copysub($items,$parent,&$addtoarr) {
	global $checked,$blockcnt,$item,$questions,$qset;
	foreach ($items as $k=>$anitem) {
		if (is_array($anitem)) {
			if (array_search($parent.'-'.($k+1),$checked)!==FALSE) { //copy block
				$newblock = array();
				$newblock['name'] = $anitem['name'];
				$newblock['id'] = $blockcnt;
				$blockcnt++;
				$newblock['startdate'] = $anitem['startdate'];
				$newblock['enddate'] = $anitem['enddate'];
				$newblock['avail'] = $anitem['avail'];
				$newblock['SH'] = $anitem['SH'];
				$newblock['colors'] = $anitem['colors'];
				$newblock['public'] = $anitem['public'];
				$newblock['fixedheight'] = $anitem['fixedheight'];
				$newblock['items'] = array();
				copysub($anitem['items'],$parent.'-'.($k+1),$newblock['items']);
				$addtoarr[] = $newblock;
			} else {
				copysub($anitem['items'],$parent.'-'.($k+1),$addtoarr);
			}
		} else {
			if (array_search($anitem,$checked)!==FALSE) {
				$addtoarr[] = additem($anitem,$item,$questions,$qset);
			}
		}
	}
}


 //set some page specific variables and counters
$cid = $_GET['cid'];
$overwriteBody = 0;
$body = "";
$pagetitle = $installname . " Import Course Items";
$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Import Course Items</div>\n";
 
//data manipulation here

	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";	
} elseif (!(isset($_GET['cid']))) {
 	$overwriteBody = 1;
	$body = "You need to access this page from a menu link";	
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
	
	

	
	//FORM HAS BEEN POSTED, STEP 3 DATA MANIPULATION
	if (isset($_POST['process'])) {
		$filename = rtrim(dirname(__FILE__), '/\\') .'/import/' . $_POST['filename'];
		list ($desc,$itemlist,$item,$questions,$qset) = parsefile($filename);
		
		$userights = $_POST['userights'];
		$newlibs = explode(",",$_POST['libs']);
		$item = array_map('addslashes_deep', $item);
		$questions = array_map('addslashes_deep', $questions);
		$qset = array_map('addslashes_deep', $qset);
		
		$checked = $_POST['checked'];
		$query = "SELECT blockcnt,itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		$blockcnt = mysql_result($result,0,0);
		$ciditemorder = unserialize(mysql_result($result,0,1));
		$items = unserialize($itemlist);
		$newitems = array();
		$missingfiles = array();
		
		mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
				
		copysub($items,'0',$newitems);
		
		array_splice($ciditemorder,count($ciditemorder),0,$newitems);
		$itemorder = addslashes(serialize($ciditemorder));
		$query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt' WHERE id='$cid'";
		mysql_query($query) or die("Query failed : $query" . mysql_error());
		
		mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
			
		if (count($missingfiles)>0) {
			echo "These files pointed to by inline text items were not found and will need to be reuploaded:<br/>";
			foreach ($missingfiles as $file) {
				echo "$file <br/>";
			}
			echo "<p><a href=\"$imasroot/course/course.php?cid=$cid\">Done</a></p>";
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");
		}
		exit;	
	} elseif ($_FILES['userfile']['name']!='') { //STEP 2 DATA MANIPULATION
		$page_fileErrorMsg = "";
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			$page_fileHiddenInput = "<input type=hidden name=\"filename\" value=\"".basename($uploadfile)."\" />\n";
		} else {
			$page_fileErrorMsg .= "<p>Error uploading file!</p>\n";
			echo $_FILES["userfile"]['error'];
			exit;
		}
		list ($desc,$itemlist,$item,$questions,$qset) = parsefile($uploadfile);
		if (!isset($desc)) {
			$page_fileErrorMsg .=  "This does not appear to be a course items file.  It may be ";
			$page_fileErrorMsg .=  "a question or library export.\n";
		}

		$items = unserialize($itemlist);
		$ids = array();
		$types = array();
		$names = array();
		$parents = array();
		getsubinfo($items,'0','');
		
	}
}	
	
/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>
<script type="text/javascript">

var curlibs = '0';
function libselect() {
	window.open('../course/libtree.php?libtree=popup&selectrights=1&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	if (libs.charAt(0)=='0' && libs.indexOf(',')>-1) {
		libs = libs.substring(2);
	}
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	if (libn.indexOf('Unassigned')>-1 && libn.indexOf(',')>-1) {
		libn = libn.substring(11);
	}
	document.getElementById("libnames").innerHTML = libn;
}
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

<?php echo $curBreadcrumb; ?>
	<div id="headerimportitems" class="pagetitle"><h2>Import Course Items</h2></div>
	<form id="qform" enctype="multipart/form-data" method=post action="importitems.php?cid=<?php echo $cid ?>">

<?php	
	if ($_FILES['userfile']['name']=='') {
?>	
		<p>This page will allow you to import course items previously exported from
		this site or another site running this software.</p>
		
		<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
		<span class=form>Import file: </span>
		<span class=formright><input name="userfile" type="file" /></span><br class=form>
		<div class=submit><input type=submit value="Submit"></div>

<?php
	} else {
	
		if (strlen($page_fileErrorMsg)>1) {
			echo $page_fileErrorMsg;
		} else { 
			echo $page_fileHiddenInput;
?>
		<h3>Package Description</h3>
		<?php echo $desc; ?>
			
			
		<p>Some questions (possibly older or different versions) may already exist on the system.  
		With these questions, do you want to:<br/>
			<input type=radio name=merge value="1" CHECKED>Update existing questions, 
			<input type=radio name=merge value="0">Add as new question, 
			<input type=radio name=merge value="-1">Keep existing questions
		</p>
		<p>
			For Added Questions, Set Question Use Rights to 
			<select name=userights>
				<option value="0">Private</option>
				<option value="2" SELECTED>Allow use, use as template, no modifications</option>
				<option value="3">Allow use by all and modifications by group</option>
				<option value="4">Allow use and modifications by all</option>
			</select>
		</p>
		<p>
			
		Assign Added Questions to library: 
		<span id="libnames">Unassigned</span>
		<input type=hidden name="libs" id="libs"  value="0">
		<input type=button value="Select Libraries" onClick="libselect()"><br> 
			
		Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
			

<?php		
			if (count($ids)>0) {
?>
		<table cellpadding=5 class=gb>
		<thead>
			<tr><th></th><th>Type</th><th>Title</th></tr>
		</thead>
		<tbody>
<?php			
				$alt=0;
				for ($i = 0 ; $i<(count($ids)); $i++) {
					if ($alt==0) {echo "		<tr class=even>"; $alt=1;} else {echo "		<tr class=odd>"; $alt=0;}
			echo '<td>';
			if (strpos($types[$i],'Block')!==false) {		
				echo "<input type=checkbox name='checked[]' value='{$ids[$i]}' id='{$parents[$i]}' checked=checked ";
				echo "onClick=\"chkgrp(this.form, '{$ids[$i]}', this.checked);\" ";
				echo '/>';
			} else {
				echo "<input type=checkbox name='checked[]' value='{$ids[$i]}' id='{$parents[$i]}.{$ids[$i]}' checked=checked ";
				echo '/>';
			}
?>
				</td>
				<td><?php echo $types[$i] ?></td>
				<td><?php echo $names[$i] ?></td>
			</tr>

<?php
				}
?>			
		</tbody>
		</table>
		<p><input type=submit name="process" value="Import Items"></p>
<?php
			}
		}
?>

<?php
	}
	echo "</form>\n";
}
require("../footer.php");
	
?>
