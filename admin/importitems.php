<?php
//IMathAS:  Course/Courseitem import
//(c) 2006 David Lippman
	require("../validate.php");
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
		
	$cid = $_GET['cid'];
	
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
						$newblock['SH'] = $anitem['SH'];
						$newblock['colors'] = $anitem['colors'];
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
		copysub($items,'0',$newitems);
		
		array_splice($ciditemorder,count($ciditemorder),0,$newitems);
		$itemorder = addslashes(serialize($ciditemorder));
		$query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt' WHERE id='$cid'";
		mysql_query($query) or die("Query failed : $query" . mysql_error());
		
		header("Location: http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/course/course.php?cid=$cid");

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
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Import Course Items</div>\n";
	echo "<h3>Import Course Items</h3>\n";
	echo "<form enctype=\"multipart/form-data\" method=post action=\"importitems.php?cid=$cid\">\n";
	
	if ($_FILES['userfile']['name']=='') {
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"300000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
	} else {
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/import/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			echo "<input type=hidden name=\"filename\" value=\"".basename($uploadfile)."\" />\n";
		} else {
			echo "<p>Error uploading file!</p>\n";
			echo "</form>\n";
			require("../footer.php");
			exit;
		}
		list ($desc,$itemlist,$item,$questions,$qset) = parsefile($uploadfile);

		if (!isset($desc)) {
			echo "This does not appear to be a course items file.  It may be ";
			echo "a question or library export.\n";
			require("../footer.php");
			exit;
		}
		echo "<h3>Package Description</h3>";
		echo $desc;
		
		$items = unserialize($itemlist);
		$ids = array();
		$types = array();
		$names = array();
		function getsubinfo($items,$parent,$pre) {
			global $ids,$types,$names,$item;
			foreach($items as $k=>$anitem) {
				if (is_array($anitem)) {
					$ids[] = $parent.'-'.($k+1);
					$types[] = $pre."Block";
					$names[] = stripslashes($anitem['name']);
					getsubinfo($anitem['items'],$parent.'-'.($k+1),$pre.'--');
				} else {
					$ids[] = $anitem;
					$types[] = $pre.$item[$anitem]['type'];
					if (isset($item[$anitem]['name'])) {
						$names[] = $item[$anitem]['name'];
					} else {
						$names[] = $item[$anitem]['title'];
					}
				}
			}
		}
		getsubinfo($items,'0','');
		
		
		echo "<p>Some questions (possibly older or different versions) may already exist on the system.  With these questions, do you want to:<br/>\n";
		echo "<input type=radio name=merge value=\"1\" CHECKED>Update existing questions, <input type=radio name=merge value=\"0\">Add as new question, <input type=radio name=merge value=\"-1\">Keep existing questions</p>\n";
		echo "<p>\n";
		echo "For Added Questions, Set Question Use Rights to <select name=userights>\n";
		echo "<option value=\"0\">Private</option>\n";
		echo "<option value=\"2\" SELECTED>Allow use, use as template, no modifications</option>\n";
		echo "<option value=\"3\">Allow use and modifications</option>\n";
		echo "</select>\n";
		echo "</p><p>\n";
		
		echo <<<END
<script>
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
</script>
END;
		
		echo "Assign Added Questions to library: <span id=\"libnames\">Unassigned</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"0\">\n";
		echo "<input type=button value=\"Select Libraries\" onClick=\"libselect()\"><br> "; 
		
		echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca\" value=\"1\" onClick=\"chkAll(this.form, 'checked[]', this.checked)\" checked=checked>\n";
		
		echo "<table cellpadding=5 class=gb>\n";
		echo "<thead><tr><th></th><th>Type</th><th>Title</th></tr></thead><tbody>\n";
		$alt=0;
		for ($i = 0 ; $i<(count($ids)); $i++) {
			if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
			echo "<td>";
			echo "<input type=checkbox name='checked[]' value='{$ids[$i]}' checked=checked>";
			echo "</td><td>{$types[$i]}</td><td>{$names[$i]}</td>\n";
			echo "</tr>\n";
		}
		echo "</tbody></table>\n";
		
		echo "<p><input type=submit name=\"process\" value=\"Import Items\"></p>\n";
	}
	echo "</form>\n";
	require("../footer.php");
	
function additem($itemtoadd,$item,$questions,$qset) {
	
	global $newlibs;
	global $userid, $userights, $cid;
	$mt = microtime();

	if ($item[$itemtoadd]['type'] == "Assessment") {
		//add assessment.  set $typeid
		$settings = explode("\n",$item[$itemtoadd]['settings']);
		foreach ($settings as $set) {
			$pair = explode('=',$set);
			$item[$itemtoadd][$pair[0]] = $pair[1];
		}
		$query = "INSERT INTO imas_assessments (courseid,name,summary,intro,startdate,enddate,reviewdate,timelimit,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,password,cntingb)";
		$query .= "VALUES ('$cid','{$item[$itemtoadd]['name']}','{$item[$itemtoadd]['summary']}','{$item[$itemtoadd]['intro']}',";
		$query .= "'{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}','{$item[$itemtoadd]['reviewdate']}','{$item[$itemtoadd]['timelimit']}',";
		$query .= "'{$item[$itemtoadd]['displaymethod']}','{$item[$itemtoadd]['defpoints']}','{$item[$itemtoadd]['defattempts']}',";
		$query .= "'{$item[$itemtoadd]['deffeedback']}','{$item[$itemtoadd]['defpenalty']}','{$item[$itemtoadd]['shuffle']}','{$item[$itemtoadd]['password']}','{$item[$itemtoadd]['cntingb']}')";
		mysql_query($query) or die("error on: $query: " . mysql_error());
		$typeid = mysql_insert_id();
					
		//determine question to be added
		//$qtoadd = explode(',',$item[$itemtoadd]['questions']);  //FIX!!! can be ~ separated as well
		$qtoadd = preg_split('/[,~]/',$item[$itemtoadd]['questions']);
		foreach ($qtoadd as $qid) {
			//add question or get system id. 
			$query = "SELECT id,adddate FROM imas_questionset WHERE uniqueid='{$questions[$qid]['uqid']}'";
			$result = mysql_query($query) or die("error on: $query: " . mysql_error());
			$questionexists = (mysql_num_rows($result)>0);
			if ($questionexists && $_POST['merge']==1) {
				$questions[$qid]['qsetid'] = mysql_result($result,0,0);
				$n = array_search($questions[$qid]['uqid'],$qset['uniqueid']);
				if ($qset['lastmod'][$n]>mysql_result($result,0,1)) { //if old question
					$now = time();
					$query = "UPDATE imas_questionset SET description='{$qset['description'][$n]}',";
					$query .= "author='{$qset['author'][$n]}',qtype='{$qset['qtype'][$n]}',";
					$query .= "control='{$qset['control'][$n]}',qcontrol='{$qset['qcontrol'][$n]}',";
					$query .= "qtext='{$qset['qtext'][$n]}',answer='{$qset['answer'][$n]}',";
					$query .= "lastmoddate=$now,adddate=$now";
					$query .= " WHERE id='{$questions[$qid]['qsetid']}' AND (ownerid='$userid' OR userights>2)";
					mysql_query($query) or die("error on: $query: " . mysql_error());
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
				$query = "INSERT INTO imas_questionset (adddate,lastmoddate,uniqueid,ownerid,";
				$query .= "author,userights,description,qtype,control,qcontrol,qtext,answer) ";
				$query .= "VALUES ($now,'{$qset['lastmod'][$n]}','{$qset['uniqueid'][$n]}',";
				$query .= "'$userid','{$qset['author'][$n]}','$userights',";
				$query .= "'{$qset['description'][$n]}','{$qset['qtype'][$n]}','{$qset['control'][$n]}',";
				$query .= "'{$qset['qcontrol'][$n]}','{$qset['qtext'][$n]}','{$qset['answer'][$n]}')";
				mysql_query($query) or die("error on: $query: " . mysql_error());
				$questions[$qid]['qsetid'] = mysql_insert_id();
				foreach ($newlibs as $lib) {
					$query = "INSERT INTO imas_library_items (libid,qsetid) VALUES ('$lib','{$questions[$qid]['qsetid']}')";
					mysql_query($query) or die("error on: $query: " . mysql_error());
				}
			}
		
			
			//add question $questions[$qid].  assessmentid is $typeid
			$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category)";
			$query .= "VALUES ($typeid,'{$questions[$qid]['qsetid']}','{$questions[$qid]['points']}',";
			$query .= "'{$questions[$qid]['attempts']}','{$questions[$qid]['penalty']}','{$questions[$qid]['category']}')";
			mysql_query($query) or die("error on: $query: " . mysql_error());
			$questions[$qid]['systemid'] = mysql_insert_id();
		}
		//recreate itemorder 
		$item[$itemtoadd]['questions'] = preg_replace("/(\d+)/e",'$questions[\\1]["systemid"]',$item[$itemtoadd]['questions']);
		//write itemorder to db
		$query = "UPDATE imas_assessments SET itemorder='{$item[$itemtoadd]['questions']}' WHERE id=$typeid";
		mysql_query($query) or die("error on: $query: " . mysql_error());
	} else if ($item[$itemtoadd]['type'] == "Forum") {
		$query = "INSERT INTO imas_forums (name,description,courseid,startdate,enddate)";
		$query .= "VALUES ('{$item[$itemtoadd]['name']}','{$item[$itemtoadd]['summary']}','$cid',";
		$query .= "'{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}')";
		mysql_query($query) or die("error on: $query: " . mysql_error());
		$typeid = mysql_insert_id();
	} else if ($item[$itemtoadd]['type'] == "InlineText") {
		$query = "INSERT INTO imas_inlinetext (courseid,title,text,startdate,enddate)";
		$query .= "VALUES ('$cid','{$item[$itemtoadd]['title']}','{$item[$itemtoadd]['text']}',";
		$query .= "'{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}')";
		mysql_query($query) or die("error on: $query: " . mysql_error());
		$typeid = mysql_insert_id();
	} else if ($item[$itemtoadd]['type'] == "LinkedText") {
		$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate)";
		$query .= "VALUES ('$cid','{$item[$itemtoadd]['title']}','{$item[$itemtoadd]['summary']}','{$item[$itemtoadd]['text']}',";
		$query .= "'{$item[$itemtoadd]['startdate']}','{$item[$itemtoadd]['enddate']}')";
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
				$itemlist = rtrim(fgets($handle, 4096));
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
			case  "REVIEWDATE":
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

?>
