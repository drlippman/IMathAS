<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");
require("../includes/parsedatetime.php");
require_once("../includes/filehandler.php");

@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

/*** pre-html data manipulation, including function code *******/

function generatemoveselect($count,$num) {
	$num = $num+1;  //adjust indexing
	$html = "<select id=\"ms-$num\" onchange=\"movefile($num)\">\n";
	for ($i = 1; $i <= $count; $i++) {
		$html .= "<option value=\"$i\" ";
		if ($i==$num) { $html .= "selected=1";}
		$html .= ">$i</option>\n";
	}
	$html .= "</select>\n";
	return $html;
}

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$useeditor = "text";
$cid = Sanitize::courseId($_GET['cid']);


$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
if (isset($_GET['id'])) {
	$curBreadcrumb .= "&gt; Modify Inline Text\n";
	$pagetitle = "Modify Inline Text";
} else {
	$curBreadcrumb .= "&gt; Add Inline Text\n";
	$pagetitle = "Add Inline Text";
}
if (isset($_GET['tb'])) {
	$totb = Sanitize::encodeStringForDisplay($_GET['tb']);
} else {
	$totb = 'b';
}

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$cid = Sanitize::courseId($_GET['cid']);
	$block = Sanitize::encodeStringForDisplay($_GET['block']);
	$page_formActionTag = "addinlinetext.php?" . Sanitize::generateQueryStringFromMap(array('block' => $block,
            'cid' => $cid, 'folder' => $_GET['folder']));
	$page_formActionTag .= "&tb=$totb";
	$caltag = Sanitize::stripHtmlTags($_POST['caltag']);
	if ($_POST['title']!= null || $_POST['text']!=null || $_POST['sdate']!=null) { //if the form has been submitted
		if ($_POST['avail']==1) {
			if ($_POST['sdatetype']=='0') {
				$startdate = 0;
			} else {
				$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
			}
			if ($_POST['edatetype']=='2000000000') {
				$enddate = 2000000000;
			} else {
				$enddate = parsedatetime($_POST['edate'],$_POST['etime']);
			}
			$oncal = Sanitize::onlyInt($_POST['oncal']);
		} else if ($_POST['avail']==2) {
			if ($_POST['altoncal']==0) {
				$startdate = 0;
				$oncal = 0;
			} else {
				$startdate = parsedatetime($_POST['cdate'],"12:00 pm");
				$oncal = 1;
				$caltag = Sanitize::stripHtmlTags($_POST['altcaltag']);
			}
			$enddate =  2000000000;
		}else {
			$startdate = 0;
			$enddate = 2000000000;
			$oncal = 0;
		}
		if (isset($_POST['hidetitle'])) {
			$_POST['title']='##hidden##';
		}
		if (isset($_POST['isplaylist'])) {
			$isplaylist = 1;
		} else {
			$isplaylist = 0;
		}

		require_once("../includes/htmLawed.php");

		//DB $_POST['title'] = addslashes(htmlentities(stripslashes($_POST['title'])));
		$_POST['title'] = htmlentities($_POST['title']);

		//DB $_POST['text'] = addslashes(myhtmLawed(stripslashes($_POST['text'])));
		$_POST['text'] = myhtmLawed($_POST['text']);

		$outcomes = array();
		if (isset($_POST['outcomes'])) {
			foreach ($_POST['outcomes'] as $o) {
				if (is_numeric($o) && $o>0) {
					$outcomes[] = intval($o);
				}
			}
		}
		$outcomes = implode(',',$outcomes);

		$filestoremove = array();
		if (isset($_GET['id'])) {  //already have id; update
			$available = Sanitize::onlyInt($_POST['avail']);
			$gid = Sanitize::onlyInt($_GET['id']);
			//DB $query = "UPDATE imas_inlinetext SET title='{$_POST['title']}',text='{$_POST['text']}',startdate=$startdate,enddate=$enddate,avail='{$_POST['avail']}',";
			//DB $query .= "oncal='$oncal',caltag='$caltag',outcomes='$outcomes',isplaylist=$isplaylist ";
			//DB $query .= "WHERE id='{$_GET['id']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "UPDATE imas_inlinetext SET title=:title,text=:text,startdate=:startdate,enddate=:enddate,avail=:avail,";
			$query .= "oncal=:oncal,caltag=:caltag,outcomes=:outcomes,isplaylist=:isplaylist ";
			$query .= "WHERE id=:id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':title'=>$_POST['title'], ':text'=>$_POST['text'], ':startdate'=>$startdate, ':enddate'=>$enddate,
				':avail'=>$available, ':oncal'=>$oncal, ':caltag'=>$caltag, ':outcomes'=>$outcomes, ':isplaylist'=>$isplaylist, ':id'=>$gid));

			//update attached files
			$del_file_stm = $DBH->prepare("DELETE FROM imas_instr_files WHERE id=:id");
			$src_file_stm = $DBH->prepare("SELECT id FROM imas_instr_files WHERE filename=:filename");
			$upd_descr_stm = $DBH->prepare("UPDATE imas_instr_files SET description=:description WHERE id=:id");

			//DB $query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='{$_GET['id']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT id,description,filename FROM imas_instr_files WHERE itemid=:itemid");
			$stm->execute(array(':itemid'=>$gid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if (isset($_POST['delfile-'.$row[0]])) {
					//DB $query = "DELETE FROM imas_instr_files WHERE id='{$row[0]}'";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$del_file_stm->execute(array(':id'=>$row[0]));
					//DB $query = "SELECT id FROM imas_instr_files WHERE filename='{$row[2]}'";
					//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					//DB if (mysql_num_rows($r2)==0) {
					if (substr($row[2],0,4)!='http') {
						$filestoremove[] = $row[0];
						$src_file_stm->execute(array(':filename'=>$row[2]));
						if ($src_file_stm->rowCount()==0) {
							//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
							//unlink($uploaddir . $row[2]);
							deletecoursefile($row[2]);
						}
					}
				} else if ($_POST['filedescr-'.$row[0]]!=$row[1]) {
					//DB $query = "UPDATE imas_instr_files SET description='{$_POST['filedescr-'.$row[0]]}' WHERE id='{$row[0]}'";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$upd_descr_stm->execute(array(':description'=>$_POST['filedescr-'.$row[0]], ':id'=>$row[0]));
				}
			}
			$newtextid = $gid;
		} else { //add new

			//DB $query = "INSERT INTO imas_inlinetext (courseid,title,text,startdate,enddate,avail,oncal,caltag,outcomes,isplaylist) VALUES ";
			//DB $query .= "('$cid','{$_POST['title']}','{$_POST['text']}',$startdate,$enddate,'{$_POST['avail']}','{$_POST['oncal']}','$caltag','$outcomes',$isplaylist);";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "INSERT INTO imas_inlinetext (courseid,title,text,startdate,enddate,avail,oncal,caltag,outcomes,isplaylist) VALUES ";
			$query .= "(:courseid, :title, :text, :startdate, :enddate, :avail, :oncal, :caltag, :outcomes, :isplaylist);";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid, ':title'=>$_POST['title'], ':text'=>$_POST['text'], ':startdate'=>$startdate, ':enddate'=>$enddate,
				':avail'=>$_POST['avail'], ':oncal'=>$_POST['oncal'], ':caltag'=>$caltag, ':outcomes'=>$outcomes, ':isplaylist'=>$isplaylist));

			//DB $newtextid = mysql_insert_id();
			$newtextid = $DBH->lastInsertId();

			//DB $query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
			//DB $query .= "('$cid','InlineText','$newtextid');";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
			$query .= "(:courseid, 'InlineText', :typeid);";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid, ':typeid'=>$newtextid));

			//DB $itemid = mysql_insert_id();
			$itemid = $DBH->lastInsertId();

			//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
			$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$cid));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			$items = unserialize($line['itemorder']);

			$blocktree = explode('-',$block);
			$sub =& $items;
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
			if ($totb=='b') {
				$sub[] = $itemid;
			} else if ($totb=='t') {
				array_unshift($sub,$itemid);
			}
			//DB $itemorder = addslashes(serialize($items));
			$itemorder = serialize($items);
			//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));

		}
		if ($_FILES['userfile']['name']!='') {
			$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
			$userfilename = Sanitize::sanitizeFilenameAndCheckBlacklist(basename(str_replace('\\','/',$_FILES['userfile']['name'])));
			$filename = $userfilename;
			$extension = strtolower(strrchr($userfilename,"."));
			$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".exe",".pl",".p");
			if (in_array($extension,$badextensions)) {
				$overwriteBody = 1;
				$body = "<p>File type is not allowed</p>";
			} else {
				/*$uploadfile = $uploaddir . $filename;
				$t=0;
				while(file_exists($uploadfile)){
					$filename = substr($filename,0,strpos($userfilename,"."))."_$t".strstr($userfilename,".");
					$uploadfile=$uploaddir.$filename;
					$t++;
				}

				if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
					//echo "<p>File is valid, and was successfully uploaded</p>\n";
					if (trim($_POST['newfiledescr'])=='') {
						$_POST['newfiledescr'] = $filename;
					}
					$query = "INSERT INTO imas_instr_files (description,filename,itemid) VALUES ('{$_POST['newfiledescr']}','$filename','$newtextid')";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
					$addedfile = mysql_insert_id();
					$_GET['id'] = $newtextid;

					*/
				if (($filename=storeuploadedcoursefile('userfile',$cid.'/'.$filename))!==false) {
					if (trim($_POST['newfiledescr'])=='') {
						$_POST['newfiledescr'] = basename($filename);
					}
					//DB $query = "INSERT INTO imas_instr_files (description,filename,itemid) VALUES ('{$_POST['newfiledescr']}','$filename','$newtextid')";
					//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
					//DB $addedfile = mysql_insert_id();
					$stm = $DBH->prepare("INSERT INTO imas_instr_files (description,filename,itemid) VALUES (:description, :filename, :itemid)");
					$stm->execute(array(':description'=>$_POST['newfiledescr'], ':filename'=>$filename, ':itemid'=>$newtextid));
					$addedfile = $DBH->lastInsertId();
				} else {
					$overwriteBody = 1;
					$body = "<p>Error uploading file!</p>\n";
				}
			}
		}
	}

	if (isset($addedfile) || count($filestoremove)>0 || isset($_GET['movefile'])) {
		//DB $query = "SELECT fileorder FROM imas_inlinetext WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $fileorder = explode(',',mysql_result($result,0,0));
		$stm = $DBH->prepare("SELECT fileorder FROM imas_inlinetext WHERE id=:id");
		$stm->execute(array(':id'=>Sanitize::onlyInt($newtextid)));
		$fileorder = explode(',',$stm->fetchColumn(0));
		if ($fileorder[0]=='') {
			$fileorder = array();
		}
		if (isset($addedfile)) {
			$fileorder[] = $addedfile;
		}
		if (count($filestoremove)>0) {
			for ($i=0; $i<count($filestoremove); $i++) {
				$k = array_search($filestoremove[$i],$fileorder);
				if ($k!==FALSE) {
					array_splice($fileorder,$k,1);
				}
			}
		}
		if (isset($_GET['movefile'])) {
			$from = $_GET['movefile'];
			$to = $_GET['movefileto'];
			$itemtomove = $fileorder[$from-1];  //-1 to adjust for 0 indexing vs 1 indexing
			array_splice($fileorder,$from-1,1);
			array_splice($fileorder,$to-1,0,$itemtomove);
		}
		$fileorder = implode(',',$fileorder);
		//DB $query = "UPDATE imas_inlinetext SET fileorder='$fileorder' WHERE id='{$_GET['id']}'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_inlinetext SET fileorder=:fileorder WHERE id=:id");
		$stm->execute(array(':fileorder'=>$fileorder, ':id'=>$newtextid));
	}
	if ($_POST['submitbtn']=='Submit') {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($_GET['cid']) ."&r=" .Sanitize::randomQueryStringParam());
		exit;
	}

	if (isset($_GET['id'])) {
		$gid = Sanitize::onlyInt($_GET['id']);
		//DB $query = "SELECT * FROM imas_inlinetext WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
		$stm = $DBH->prepare("SELECT * FROM imas_inlinetext WHERE id=:id");
		$stm->execute(array(':id'=>$gid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		if ($line['title']=='##hidden##') {
			$hidetitle = true;
			$line['title']='';
		}
		$startdate = $line['startdate'];
		$enddate = $line['enddate'];
		$fileorder = explode(',',$line['fileorder']);
		if ($line['avail']==2 && $startdate>0) {
			$altoncal = 1;
		} else {
			$altoncal = 0;
		}
		if ($line['outcomes']!='') {
			$gradeoutcomes = explode(',',$line['outcomes']);
		} else {
			$gradeoutcomes = array();
		}
		$savetitle = _("Save Changes");
	} else {
		//set defaults
		$line['title'] = "Enter title here";
		$line['text'] = "<p>Enter text here</p>";
		$line['avail'] = 1;
		$line['oncal'] = 0;
		$line['caltag'] = '!';
		$altoncal = 0;
		$startdate = time();
		$enddate = time() + 7*24*60*60;
		$hidetitle = false;
		$fileorder = array();
		$gradeoutcomes = array();
		$savetitle = _("Create Item");
	}

	$hr = floor($coursedeftime/60)%12;
	$min = $coursedeftime%60;
	$am = ($coursedeftime<12*60)?'am':'pm';
	$deftime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
	$hr = floor($coursedefstime/60)%12;
	$min = $coursedefstime%60;
	$am = ($coursedefstime<12*60)?'am':'pm';
	$defstime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;

	if ($startdate!=0) {
		$sdate = tzdate("m/d/Y",$startdate);
		$stime = tzdate("g:i a",$startdate);
	} else {
		$sdate = tzdate("m/d/Y",time());
		$stime = $defstime; //tzdate("g:i a",time());
	}
	if ($enddate!=2000000000) {
		$edate = tzdate("m/d/Y",$enddate);
		$etime = tzdate("g:i a",$enddate);
	} else {
		$edate = tzdate("m/d/Y",time()+7*24*60*60);
		$etime = $deftime; //tzdate("g:i a",time()+7*24*60*60);
	}

	if (isset($_GET['id'])) {
		//DB $query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT id,description,filename FROM imas_instr_files WHERE itemid=:itemid");
		$stm->execute(array(':itemid'=>$gid));
		$page_fileorderCount = count($fileorder);
		$i = 0;
		$page_FileLinks = array();
		//DB if (mysql_num_rows($result)>0) {
			//DB while ($row = mysql_fetch_row($result)) {
		if ($stm->rowCount()>0) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$filedescr[$row[0]] = $row[1];
				$filenames[$row[0]] = $row[2];
			}
			foreach ($fileorder as $k=>$fid) {
				$page_FileLinks[$k]['link'] = $filenames[$fid];
				$page_FileLinks[$k]['desc'] = $filedescr[$fid];
				$page_FileLinks[$k]['fid'] = $fid;

				//echo generatemoveselect(count($fileorder),$k);
				//echo "<a href=\"$imasroot/course/files/{$filenames[$fid]}\" target=\"_blank\">View</a> ";
				//echo "<input type=\"text\" name=\"filedescr-$fid\" value=\"{$filedescr[$fid]}\"/> Delete? <input type=checkbox name=\"delfile-$fid\"/><br/>";
			}
		}

	} else {
		$stime = $defstime;
		$etime = $deftime;
	}

	$outcomenames = array();
	//DB $query = "SELECT id,name FROM imas_outcomes WHERE courseid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$outcomenames[$row[0]] = $row[1];
	}
	//DB $query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $row = mysql_fetch_row($result);
	$stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$row = $stm->fetch(PDO::FETCH_NUM);
	if ($row[0]=='') {
		$outcomearr = array();
	} else {
		$outcomearr = unserialize($row[0]);
		if ($outcomearr===false) {
			$outcomearr = array();
		}
	}
	$outcomes = array();
	function flattenarr($ar) {
		global $outcomes;
		foreach ($ar as $v) {
			if (is_array($v)) { //outcome group
				$outcomes[] = array($v['name'], 1);
				flattenarr($v['outcomes']);
			} else {
				$outcomes[] = array($v, 0);
			}
		}
	}
	flattenarr($outcomearr);

	$page_formActionTag .= (isset($_GET['id'])) ? "&id=" . $gid : "";
}


 /******* begin html output ********/
 $placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>
<script type="text/javascript">
function movefile(from) {
	var to = document.getElementById('ms-'+from).value;
	var address = "<?php echo $GLOBALS['basesiteurl'] . "/course/addinlinetext.php?cid=$cid&block=$block&id=" . $gid ?>";

	if (to != from) {
 	var toopen = address + '&movefile=' + from + '&movefileto=' + to;
  	window.location = toopen;
  }
}
function chghidetitle() {
	var titleinput = $("input[name=title]");
	var hidetitle = $("input[name=hidetitle]").prop("checked");
	if (hidetitle) {
		$("#titlewrap").hide();
		console.log(titleinput.prop("required"));
		if (!!titleinput.prop("required")) {
			titleinput.data("hasrequired",true);
			titleinput.prop("required", false);
		}
	} else {
		$("#titlewrap").show();
		if (!!titleinput.data("hasrequired")) {
			titleinput.prop("required", true);
		}
	}	
}
$(function() { chghidetitle(); });


</script>


	<div class=breadcrumb><?php echo $curBreadcrumb  ?></div>
	<div id="headeraddinlinetext" class="pagetitle"><h2><?php echo $pagetitle ?><img src="<?php echo $imasroot ?>/img/help.gif" alt="Help" onClick="window.open('<?php echo $imasroot ?>/help.php?section=inlinetextitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/></h2></div>

	<form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
	<span class=form>Title: </span>
	<span class=formright>
		<span id="titlewrap" <?php if ($hidetitle==true) {echo 'style="display:none;"';} ?>>
		<input type=text size=60 name=title value="<?php echo str_replace('"','&quot;',$line['title']);?>"><br/>
		</span>
		<input type="checkbox" name="hidetitle" value="1" onclick="chghidetitle()" <?php writeHtmlChecked($hidetitle,true) ?>/>
		Hide title and icon
	</span><BR class=form>

	Text:<BR>
	<div class=editor>
		<textarea cols=60 rows=20 id=text name=text style="width: 100%"><?php echo Sanitize::encodeStringForDisplay($line['text']);?></textarea>
	</div>

	<span class=form>
	Attached Files:</span>
	<span class=wideformright>

<?php
	if (isset($gid)) {
		foreach ($page_FileLinks as $k=>$arr) {
			echo generatemoveselect($page_fileorderCount,$k);
?>
		<a href="<?php echo getcoursefileurl($arr['link']); ?>" target="_blank">
		View</a>
		<input type="text" name="filedescr-<?php echo $arr['fid'] ?>" value="<?php echo $arr['desc'] ?>"/>
		Delete? <input type=checkbox name="delfile-<?php echo $arr['fid'] ?>"/><br/>
<?php
		}
	}
?>

		<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
		New file<sup>*</sup>: <input type="file" name="userfile"/><br/>
		Description: <input type="text" name="newfiledescr"/><br/>
		<input type=submit name="submitbtn" value="Add / Update Files"/>
	</span><br class=form>

	<span class="form">List of YouTube videos</span>
	<span class="formright">
		<input type="checkbox" name="isplaylist" value="1" <?php writeHtmlChecked($line['isplaylist'],1);?>/> Show as embedded playlist
	</span>
	<br class="form"/>

	<div>
		<span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($line['avail'],0);?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='none';"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($line['avail'],1);?> onclick="document.getElementById('datediv').style.display='block';document.getElementById('altcaldiv').style.display='none';"/>Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php writeHtmlChecked($line['avail'],2);?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='block';"/>Show Always<br/>
		</span><br class="form"/>

		<div id="datediv" style="display:<?php echo ($line['avail']==1)?"block":"none"; ?>">

		<span class=form>Available After:</span>
		<span class=formright>
			<input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,'0',0) ?>/>
			 Always until end date<br/>
			<input type=radio name="sdatetype" value="sdate" <?php writeHtmlChecked($startdate,'0',1) ?>/>
			<input type=text size=10 name=sdate value="<?php echo $sdate;?>">
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=stime value="<?php echo $stime;?>">
		</span><BR class=form>

		<span class=form>Available Until:</span>
		<span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/>
			Always after start date<br/>
			<input type=radio name="edatetype" value="edate" <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
			<input type=text size=10 name=edate value="<?php echo $edate;?>">
			<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
			<img src="../img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=etime value="<?php echo $etime;?>">
		</span><BR class=form>

		<span class=form>Place on Calendar?</span>
		<span class=formright>
			<input type=radio name="oncal" value=0 <?php writeHtmlChecked($line['oncal'],0); ?> /> No<br/>
			<input type=radio name="oncal" value=1 <?php writeHtmlChecked($line['oncal'],1); ?> /> Yes, on Available after date (will only show after that date)<br/>
			<input type=radio name="oncal" value=2 <?php writeHtmlChecked($line['oncal'],2); ?> /> Yes, on Available until date<br/>
			With tag: <input name="caltag" type=text size=8 value="<?php echo Sanitize::encodeStringForDisplay($line['caltag']); ?>"/>
		</span><br class="form" />
		</div>
		<div id="altcaldiv" style="display:<?php echo ($line['avail']==2)?"block":"none"; ?>">
		<span class=form>Place on Calendar?</span>
		<span class=formright>
			<input type=radio name="altoncal" value="0" <?php writeHtmlChecked($altoncal,0); ?> /> No<br/>
			<input type=radio name="altoncal" value="1" <?php writeHtmlChecked($altoncal,1); ?> /> Yes, on
			<input type=text size=10 name="cdate" value="<?php echo $sdate;?>">
			<a href="#" onClick="displayDatePicker('cdate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></a> <br/>
			With tag: <input name="altcaltag" type=text size=8 value="<?php echo Sanitize::encodeStringForDisplay($line['caltag']); ?>"/>
		</span><BR class=form>
		</div>
<?php
	if (count($outcomes)>0) {
			echo '<span class="form">Associate Outcomes:</span></span class="formright">';
			writeHtmlMultiSelect('outcomes',$outcomes,$outcomenames,$gradeoutcomes,'Select an outcome...');
			echo '</span><br class="form"/>';
	}
?>

	</div>
	<div class=submit><button type=submit name="submitbtn" value="Submit"><?php echo $savetitle; ?></button></div>
	</form>
	<p><sup>*</sup>Avoid quotes in the filename</p>
<?php
}
	require("../footer.php");
?>
