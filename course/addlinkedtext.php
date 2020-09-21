<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");
require("../includes/parsedatetime.php");



ini_set("max_execution_time", "120");




/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$useeditor = "text,summary";

$cid = Sanitize::courseId($_GET['cid']);
$linkid = Sanitize::onlyInt($_GET['id']);
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
if (!empty($linkid)) {
	$curBreadcrumb .= "&gt; "._("Modify Link")."\n";
	$pagetitle = _("Modify Link");
} else {
	$curBreadcrumb .= "&gt; "._("Add Link")."\n";
	$pagetitle = _("Add Link");
}
if (isset($_GET['tb'])) {
	$totb = Sanitize::encodeStringForDisplay($_GET['tb']);
} else {
	$totb = 'b';
}

if (isset($_GET['id'])) {
	$stm = $DBH->prepare("SELECT courseid FROM imas_linkedtext WHERE id=?");
	$stm->execute(array($linkid));
	if ($stm->rowCount()==0 || $stm->fetchColumn(0) != $cid) {
		echo _("Invalid ID");
		exit;
	}
}

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = _("You need to log in as a teacher to access this page");
} elseif (empty($cid)) {
	$overwriteBody=1;
	$body = _("You need to access this page from the course page menu");
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$block = $_GET['block'];
	$page_formActionTag = "addlinkedtext.php?" . Sanitize::generateQueryStringFromMap(array('block' => $block,
            'cid' => $cid, 'folder' => $_GET['folder']));
	$page_formActionTag .= (!empty($linkid)) ? "&id=" . $linkid : "";
	$page_formActionTag .= "&tb=$totb";
	$uploaderror = false;
	$caltag = Sanitize::stripHtmlTags($_POST['caltag']);
	$points = 0;

	if ($_POST['title']!= null) { //if the form has been submitted
		$DBH->beginTransaction();
		if ($_POST['avail']==1) {
			if ($_POST['sdatetype']=='0') {
				$startdate = 0;
			} else if ($_POST['sdatetype']=='now') {
				$startdate = time();
			} else {
				$startdate = parsedatetime($_POST['sdate'],$_POST['stime'],0);
			}
			if ($_POST['edatetype']=='2000000000') {
				$enddate = 2000000000;
			} else {
				$enddate = parsedatetime($_POST['edate'],$_POST['etime'],2000000000);
			}
			$oncal = Sanitize::onlyInt($_POST['oncal']);
		} else if ($_POST['avail']==2) {
			if ($_POST['altoncal']==0) {
				$startdate = 0;
				$oncal = 0;
			} else {
				$startdate = parsedatetime($_POST['cdate'],"12:00 pm",0);
				$oncal = ($startdate>0)?1:0;
				$caltag = Sanitize::stripHtmlTags($_POST['altcaltag']);
			}
			$enddate =  2000000000;
		} else {
			$startdate = 0;
			$enddate =  2000000000;
			$oncal = 0;
		}

		$processingerror = false;
		$newfileid = 0;
		if ($_POST['linktype']=='text') {
			$_POST['text'] = Sanitize::incomingHtml($_POST['text']);
		} else if ($_POST['linktype']=='file') {
			require_once("../includes/filehandler.php");
			if ($_FILES['userfile']['name']!='') {
				//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
				//$uploadfile = $uploaddir . "$cid-" . basename($_FILES['userfile']['name']);
				$userfilename = Sanitize::sanitizeFilenameAndCheckBlacklist(basename(str_replace('\\','/',$_FILES['userfile']['name'])));
				$filename = $userfilename;
				$extension = strtolower(strrchr($userfilename,"."));
				$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p");
				if (in_array($extension,$badextensions)) {
					$overwriteBody = 1;
					$body = "<p>"._("File type is not allowed")."</p>";
				} else {
					if ($_FILES['userfile']['error']==1 || $_FILES['userfile']['error']==2) {
						$errormsg = _("File size too large");
						$_POST['text'] = _("File upload error")." - $errormsg";
						$uploaderror = true;
					} else {
						if (($filename=storeuploadedcoursefile('userfile',$cid.'/'.$filename))===false) {
							$errormsg = _("Try again");
							$_POST['text'] = _("File upload error")." - $errormsg";
							$uploaderror = true;
						} else {
							$_POST['text'] = "file:$filename";
							$stm = $DBH->prepare("INSERT INTO imas_linked_files (filename) VALUES (?) ");
							$stm->execute(array($filename));
							$newfileid = $DBH->lastInsertId();
						}

					}
					/*
					$uploadfile = $uploaddir . $filename;
					$t=0;
					while(file_exists($uploadfile)){ //make sure filename is unused
						$filename = substr($filename,0,strpos($userfilename,"."))."_$t".strstr($userfilename,".");
						$uploadfile=$uploaddir.$filename;
						$t++;
					}

					if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
						//echo "<p>File is valid, and was successfully uploaded</p>\n";
						$_POST['text'] = "file:$filename";
					} else {
						switch ($_FILES['userfile']['error']) {
							case 1:
							case 2:
								$errormsg = "File size too large";
								break;
							default:
								$errormsg = "Try again";
								break;
						}
						$_POST['text'] = "File upload error - $errormsg";
						$uploaderror = true;
					}
					//$_POST['text'] = "file:$cid-" . basename($_FILES['userfile']['name']);
					*/
				}

			} else if (!empty($_POST['curfile'])) {
				//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
				///if (!file_exists($uploaddir . $_POST['curfile'])) {
				$curfile = Sanitize::sanitizeFilePathAndCheckBlacklist($_POST['curfile']);
				if (!doesfileexist('cfile', $curfile)) {
					$processingerror = true;
				} else {
					$_POST['text'] = "file:".$curfile;
				}
			} else {
				$processingerror = true;
			}
		} else if ($_POST['linktype']=='web') {
			$_POST['text'] = Sanitize::url($_POST['web']);
			if (substr($_POST['text'],0,4)!='http') {
				$processingerror = true;
			}
		} else if ($_POST['linktype']=='tool') {
			if ($_POST['tool']==0) {
				$processingerror = true;
			} else {
				//tool~~custom~~customurl~~gbcategory~~cntingb~~tutoredit~~gradesecret
				$_POST['text'] = 'exttool:'.$_POST['tool'].'~~'.$_POST['toolcustom'].'~~'.$_POST['toolcustomurl'];
				if ($_POST['usegbscore']==0 || $_POST['points']==0) {
					$points = 0;
				} else {
					$_POST['text'] .= '~~'.$_POST['gbcat'].'~~'.$_POST['cntingb'].'~~'.$_POST['tutoredit'].'~~'.$_POST['gradesecret'];
					$points = intval($_POST['points']);
				}
			}
		}

		if ($points==0 && isset($_POST['hadpoints']) && !empty($linkid)) {
			$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetypeid=:gradetypeid AND gradetype='exttool'");
			$stm->execute(array(':gradetypeid'=>$linkid));
		}
		$_POST['title'] = Sanitize::stripHtmlTags($_POST['title']);

		if ($_POST['summary']=='<p>'._('Enter summary here (displays on course page)').'</p>' || $_POST['summary']=='<p></p>') {
			$_POST['summary'] = '';
		} else {
			$_POST['summary'] = Sanitize::incomingHtml($_POST['summary']);
		}
		$_POST['text'] = trim($_POST['text']);
		$outcomes = array();
		if (isset($_POST['outcomes'])) {
			foreach ($_POST['outcomes'] as $o) {
				if (is_numeric($o) && $o>0) {
					$outcomes[] = intval($o);
				}
			}
		}
		$outcomes = implode(',',$outcomes);
		if (!empty($linkid)) {  //already have id; update
			$stm = $DBH->prepare("SELECT text,fileid FROM imas_linkedtext WHERE id=:id");
			$stm->execute(array(':id'=>$linkid));
			list($text,$oldfileid) = $stm->fetch(PDO::FETCH_NUM);
			$text = trim($text);
			if (substr($text,0,5)=='file:') { //has file
				if ($_POST['text']!=$text) { //if not same file, delete old if not used
					if ($oldfileid > 0) {
						// had a file id - can use that approach
						$stm = $DBH->prepare("SELECT count(id) FROM imas_linkedtext WHERE fileid=?");
						$stm->execute(array($oldfileid));
						if ($stm->fetchColumn(0) == 1) { // only one use of this file
							$filename = substr($text,5);
							deletecoursefile($filename);
							$stm = $DBH->prepare("DELETE FROM imas_linked_files WHERE id=?");
							$stm->execute(array($oldfileid));
						}
					} else {
						// no file id - old file, so do it the old way
						$stm = $DBH->prepare("SELECT id FROM imas_linkedtext WHERE text=:text");
						$stm->execute(array(':text'=>$text));
						if ($stm->rowCount()==1) {
							//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
							$filename = substr($text,5);
							deletecoursefile($filename);
							//if (file_exists($uploaddir . $filename)) {
							//	unlink($uploaddir . $filename);
							//}
						}
					}
				} else {
					// same file, so keep fileid
					$newfileid = $oldfileid;
				}
			}
			if (!$processingerror) {
				$available = sanitize::onlyInt($_POST['avail']);
				$target = Sanitize::onlyInt($_POST['target']);
				$query = "UPDATE imas_linkedtext SET title=:title,summary=:summary,text=:text,startdate=:startdate,enddate=:enddate,avail=:avail,";
				$query .= "oncal=:oncal,caltag=:caltag,target=:target,outcomes=:outcomes,points=:points,fileid=:fileid WHERE id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':title'=>$_POST['title'], ':summary'=>$_POST['summary'], ':text'=>$_POST['text'], ':startdate'=>$startdate,
					':enddate'=>$enddate, ':avail'=>$available, ':oncal'=>$oncal, ':caltag'=>$caltag, ':target'=>$target,
					':outcomes'=>$outcomes, ':points'=>$points, ':fileid'=>$newfileid, ':id'=>$linkid));
			}
		} else if (!$processingerror) { //add new
			$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate,avail,oncal,caltag,target,outcomes,points,fileid) VALUES ";
			$query .= "(:courseid, :title, :summary, :text, :startdate, :enddate, :avail, :oncal, :caltag, :target, :outcomes, :points, :fileid);";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid, ':title'=>$_POST['title'], ':summary'=>$_POST['summary'], ':text'=>$_POST['text'],
				':startdate'=>$startdate, ':enddate'=>$enddate, ':avail'=>$_POST['avail'], ':oncal'=>$oncal, ':caltag'=>$caltag,
				':target'=>$_POST['target'], ':outcomes'=>$outcomes, ':points'=>$points, ':fileid'=>$newfileid));
			$newtextid = $DBH->lastInsertId();
			$query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
			$query .= "(:courseid, 'LinkedText', :typeid);";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid, ':typeid'=>$newtextid));
			$itemid = $DBH->lastInsertId();
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
			$itemorder = serialize($items);
			$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
			$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));

		}
		$DBH->commit();
		if ($uploaderror == true || $processingerror == true) {
			if ($uploaderror == true) {
				$body = "<p>"._("Error uploading file!")." $errormsg</p>\n";
			} else {
				$body = "<p>"._("Error with your submission")."</p>";
			}
			$body .= "<p><a href=\"addlinkedtext.php?cid=" . $cid;
			if (!empty($linkid)) {
				$body .= "&id=" . $linkid;
			} else {
				$body .= "&id=$newtextid";
			}
			$body .= "\">Try Again</a></p>\n";
			echo "<html><body>$body</body></html>";
		} else {
			$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".$cid.$btf ."&r=" .Sanitize::randomQueryStringParam());
		}
		exit;
	} else {
		$toolcustom = '';
		$selectedtool = 0;
		$filename = '';
		$webaddr = '';
		if (!empty($linkid)) {
			$stm = $DBH->prepare("SELECT * FROM imas_linkedtext WHERE id=:id");
			$stm->execute(array(':id'=>$linkid));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			$startdate = $line['startdate'];
			$enddate = $line['enddate'];
			$gbcat = 0;
			$cntingb = 1;
			$tutoredit = 0;
			$gradesecret = uniqid();
			if ($line['avail']==2 && $startdate>0) {
				$altoncal = 1;
			} else {
				$altoncal = 0;
			}
			if (substr($line['text'],0,4)=='http') {
				$type = 'web';
				$webaddr = $line['text'];
				$line['text'] = "";
			} else if (substr($line['text'],0,5)=='file:') {
				$type = 'file';
				$filename = substr($line['text'],5);
				$line['text'] = "";
			} else if (substr($line['text'],0,8)=='exttool:') {
				$type = 'tool';
				$points= $line['points'];
				$toolparts = explode('~~',substr($line['text'],8));
				$selectedtool = $toolparts[0];
				$toolcustom = $toolparts[1];
				if (isset($toolparts[2])) {
					$toolcustomurl = $toolparts[2];
				} else {
					$toolcustomurl = '';
				}
				if (isset($toolparts[3])) {
					$gbcat = $toolparts[3];
					$cntingb = $toolparts[4];
					$tutoredit = $toolparts[5];
					$gradesecret = $toolparts[6];
				}
				$line['text'] = "";
			} else {
				$type = 'text';
			}
			if ($line['outcomes']!='') {
				$gradeoutcomes = explode(',',$line['outcomes']);
			} else {
				$gradeoutcomes = array();
			}

			$savetitle = _("Save Changes");
		} else {
			//set defaults
			$line['title'] = "";
			$line['summary'] = "";
			$line['text'] = "";
			$line['avail'] = 1;
			$line['oncal'] = 0;
			$line['caltag'] = '!';
			$line['target'] = 0;
			$altoncal = 0;
			$startdate = time();
			$enddate = time() + 7*24*60*60;
			$type = 'text';
			$gradeoutcomes = array();
			$savetitle = _("Create Item");
			$selectedgbitem = 0;
			$points = 0;
			$cntingb = 1;
			$gbcat = 0;
			$tutoredit = 0;
			$gradesecret = uniqid();
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

		if (empty($linkid)) {
			$stime = $defstime;
			$etime = $deftime;
		}

		$toolvals = array(0);
		$toollabels = array(_('Select a tool...'));
		$query = "SELECT id,name FROM imas_external_tools WHERE courseid=:courseid ";
		$query .= "OR (courseid=0 AND (groupid=:groupid OR groupid=0)) ORDER BY name";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid, ':groupid'=>$groupid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$toolvals[] = $row[0];
			$toollabels[] = $row[1];
		}
		if ($selectedtool>0 && !in_array($selectedtool,$toolvals)) {
			$type = 'text';
			$line['text'] = "<p>"._("Invalid tool was selected")."</p>";
		}
		$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$page_gbcatSelect = array();
		$i=0;
		if ($stm->rowCount()>0) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$page_gbcatSelect['val'][$i] = $row[0];
				$page_gbcatSelect['label'][$i] = $row[1];
				$i++;
			}
		}
		$page_tutorSelect['label'] = array(_("No access to scores"),_("View Scores"),_("View and Edit Scores"));
		$page_tutorSelect['val'] = array(2,0,1);

		$outcomenames = array();
		$stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$outcomenames[$row[0]] = $row[1];
		}
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

	}
}

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";
$placeinhead .= '<script type="text/javascript">
 function linktypeupdate(el) {
 	var tochg = ["text","web","file","tool"];
	for (var i=0;i<4;i++) {
		if (tochg[i]==el.value) {
			disp = "";
		} else {
			disp = "none";
		}
		document.getElementById(tochg[i]+"input").style.display = disp;
	}
	if (el.value=="web" || el.value=="file") {
		$("input:radio[name=target][value=1]").prop("checked",true);
	} else {
		$("input:radio[name=target][value=0]").prop("checked",true);
	}
 }
 </script>';
 $placeinhead .= '<script type="text/javascript"> function toggleGBdetail(v) { document.getElementById("gbdetail").style.display = v?"block":"none";}</script>';

 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>

	<div class=breadcrumb><?php echo $curBreadcrumb  ?></div>
	<div id="headeraddlinkedtext" class="pagetitle"><h1><?php echo $pagetitle ?></h1></div>

	<form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
		<span class=form>Title: </span>
		<span class=formright><input type=text size=60 name=title value="<?php echo str_replace('"','&quot;',$line['title']);?>" required />
		</span><BR class=form>

		<?php echo _("Summary: (shows on course page)"); ?><BR>
		<div class=editor>
			<textarea cols=60 rows=10 id=summary name=summary style="width: 100%"><?php echo Sanitize::encodeStringForDisplay($line['summary'], true);?></textarea>
		</div>
		<br/>

		<span class=form><?php echo _("Link type:"); ?> </span>
		<span class="formright">
		<select id="linktype" name="linktype" onchange="linktypeupdate(this)">
			<option value="text" <?php writeHtmlSelected($type,'text');?>><?php echo _("Page of text"); ?></option>
			<option value="web" <?php writeHtmlSelected($type,'web');?>><?php echo _("Web link"); ?></option>
			<option value="file" <?php writeHtmlSelected($type,'file');?>><?php echo _("File"); ?></option>
			<option value="tool" <?php writeHtmlSelected($type,'tool');?>><?php echo _("External Tool"); ?></option>
		</select>
		</span><br class="form"/>

		<div id="textinput" <?php if ($type != 'text') {echo 'style="display:none;"';}?> >
			Text<BR>
			<div class=editor>
				<textarea cols=80 rows=20 id=text name=text style="width: 100%"><?php echo Sanitize::encodeStringForDisplay($line['text'], true);?></textarea>
			</div>
		</div>
		<div id="webinput" <?php if ($type != 'web') {echo 'style="display:none;"';}?> >
			<span class="form"><?php echo ("Weblink (start with http://)"); ?></span>
			<span class="formright">
				<input size="80" name="web" value="<?php echo Sanitize::encodeStringForDisplay($webaddr);?>" />
			</span><br class="form">

		</div>
		<div id="fileinput" <?php if ($type != 'file') {echo 'style="display:none;"';}?>>
			<span class="form">File</span>
			<input type="hidden" name="MAX_FILE_SIZE" value="15000000" />
			<span class="formright">
			<?php if ($filename != '') {
				require_once("../includes/filehandler.php");
				echo '<input type="hidden" name="curfile" value="'.Sanitize::encodeStringForDisplay($filename).'"/>';
				$alink = getcoursefileurl($filename);
				echo _('Current file:').' <a target="_blank" href="' . Sanitize::url($alink) . '">'.Sanitize::encodeStringForDisplay(basename($filename)).'</a><br/>'._('Replace').' ';
			} else {
				echo _('Attach ');
			}
			?>
			file (Max 15MB): <input name="userfile" type="file" />
			</span><br class="form">
		</div>
		<div id="toolinput" <?php if ($type != 'tool') {echo 'style="display:none;"';}?>>
			<span class="form"><?php echo _("External Tool"); ?></span>
			<span class="formright">
			<?php
			if (count($toolvals)>0) {
				writeHtmlSelect('tool',$toolvals,$toollabels,$selectedtool);
				echo '<br/>',_('Custom parameters:'),' <input type="text" name="toolcustom" size="40" value="'.Sanitize::encodeStringForDisplay($toolcustom).'" /><br/>';
				echo _('Custom launch URL:'),' <input type="text" name="toolcustomurl" size="40" value="'.Sanitize::encodeStringForDisplay($toolcustomurl).'" /><br/>';
			} else {
				echo _('No Tools defined yet').'<br/>';
			}
			if (!isset($CFG['GEN']['noInstrExternalTools'])) {
				echo '<a href="../admin/externaltools.php?' . Sanitize::generateQueryStringFromMap(array('cid' => $cid,
                        'ltfrom' => $linkid)) .'">'._('Add or edit an external tool').'</a>';
			}
			?>
			</span><br class="form"/>
			<span class="form"><?php echo _("If this tool returns scores, do you want to record them?"); ?></span>
			<span class="formright">
			<input type=radio name="usegbscore" value="0" <?php if ($points==0) { echo 'checked=1';}?> onclick="toggleGBdetail(false)"/>No<br/>
			<input type=radio name="usegbscore" value="1" <?php if ($points>0) { echo 'checked=1';}?> onclick="toggleGBdetail(true)"/>Yes
			</span><br class="form"/>
			<div id="gbdetail" <?php if ($points==0) { echo 'style="display:none;"';}?>>
			<span class="form"><?php echo _("Points:"); ?></span>
			<span class="formright">
				<input type=text size=4 name="points" value="<?php echo Sanitize::encodeStringForDisplay($points); ?>"/> points
			</span><br class="form"/>
			<span class=form><?php echo _("Gradebook Category:"); ?></span>
				<span class=formright>

	<?php
		writeHtmlSelect("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],$gbcat,"Default",0);
	?>
			</span><br class=form>
			<span class=form>Count: </span>
			<span class="formright">
				<input type=radio name="cntingb" value="1" <?php writeHtmlChecked($cntingb,1,0); ?> /> <?php echo _("Count in Gradebook"); ?><br/>
				<input type=radio name="cntingb" value="0" <?php writeHtmlChecked($cntingb,0,0); ?> /> <?php echo _("Don't count in grade total and hide from students"); ?><br/>
				<input type=radio name="cntingb" value="3" <?php writeHtmlChecked($cntingb,3,0); ?> /> <?php echo _("Don't count in grade total"); ?><br/>
				<input type=radio name="cntingb" value="2" <?php writeHtmlChecked($cntingb,2,0); ?> /> <?php echo _("Count as Extra Credit"); ?>
			</span><br class=form>
			<span class="form"><?php echo _("Tutor Access:"); ?></span>
				<span class="formright">
	<?php
		writeHtmlSelect("tutoredit",$page_tutorSelect['val'],$page_tutorSelect['label'],$tutoredit);
		echo '<input type="hidden" name="gradesecret" value="'.Sanitize::encodeStringForDisplay($gradesecret).'"/>';
	?>
			</span><br class="form" />
			</div>
		</div>

		<span class="form"><?php echo _("Open page in:"); ?></span>
		<span class="formright">
			<input type=radio name="target" value="0" <?php writeHtmlChecked($line['target'],0);?>/><?php echo _("Current window"); ?>/tab<br/>
			<input type=radio name="target" value="1" <?php writeHtmlChecked($line['target'],1);?>/><?php echo _("New window"); ?>/tab<br/>
		</span><br class="form"/>

		<span class=form><?php echo _("Show:"); ?></span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($line['avail'],0);?> onclick="$('#datediv').slideUp(100);$('#altcaldiv').slideUp(100);"/><?php echo _("Hide"); ?><br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($line['avail'],1);?> onclick="$('#datediv').slideDown(100);$('#altcaldiv').slideUp(100);"/><?php echo _("Show by Dates"); ?><br/>
			<input type=radio name="avail" value="2" <?php writeHtmlChecked($line['avail'],2);?> onclick="$('#datediv').slideUp(100);$('#altcaldiv').slideDown(100);"/><?php echo _("Show Always"); ?><br/>
		</span><br class="form"/>

		<div id="datediv" style="display:<?php echo ($line['avail']==1)?"block":"none"; ?>">
		<span class=form><?php echo _("Available After:"); ?></span>
		<span class=formright>
			<input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,'0',0) ?>/>
			<?php echo _("Always until end date"); ?><br/>
			<input type=radio name="sdatetype" value="sdate" <?php writeHtmlChecked($startdate,'0',1) ?>/>
			<input type=text size=10 name=sdate value="<?php echo $sdate;?>">
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=stime value="<?php echo $stime;?>">
		</span><BR class=form>

		<span class=form><?php echo _("Available Until:"); ?></span><span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/> <?php echo _("Always after start date"); ?><br/>
			<input type=radio name="edatetype" value="edate"  <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
			<input type=text size=10 name=edate value="<?php echo $edate;?>">
			<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
			<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=etime value="<?php echo $etime;?>">
		</span><BR class=form>

		<span class=form><?php echo _("Place on Calendar?"); ?></span>
		<span class=formright>
			<input type=radio name="oncal" value=0 <?php writeHtmlChecked($line['oncal'],0); ?> /> <?php echo _("No"); ?><br/>
			<input type=radio name="oncal" value=1 <?php writeHtmlChecked($line['oncal'],1); ?> /> <?php echo _("Yes, on Available after date (will only show after that date)"); ?><br/>
			<input type=radio name="oncal" value=2 <?php writeHtmlChecked($line['oncal'],2); ?> /> <?php echo _("Yes, on Available until date"); ?><br/>
			<?php echo _("With tag:"); ?> <input name="caltag" type=text size=8 value="<?php echo Sanitize::encodeStringForDisplay($line['caltag']);?>"/>
		</span><br class="form" />
		</div>
		<div id="altcaldiv" style="display:<?php echo ($line['avail']==2)?"block":"none"; ?>">
		<span class=form><?php echo _("Place on Calendar?"); ?></span>
		<span class=formright>
			<input type=radio name="altoncal" value="0" <?php writeHtmlChecked($altoncal,0); ?> /> No<br/>
			<input type=radio name="altoncal" value="1" <?php writeHtmlChecked($altoncal,1); ?> /> Yes, on
			<input type=text size=10 name="cdate" value="<?php echo $sdate;?>">
			<a href="#" onClick="displayDatePicker('cdate', this); return false">
			<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a> <br/>
			<?php echo _("With tag:"); ?> <input name="altcaltag" type=text size=8 value="<?php echo Sanitize::encodeStringForDisplay($line['caltag']);?>"/>
		</span><BR class=form>
		</div>
<?php
	if (count($outcomes)>0) {
			echo '<span class="form">',_('Associate Outcomes:'),'</span></span class="formright">';
			writeHtmlMultiSelect('outcomes',$outcomes,$outcomenames,$gradeoutcomes,_('Select an outcome...'));
			echo '</span><br class="form"/>';
	}
	if ($points>0) {
		echo '<input type="hidden" name="hadpoints" value="true"/>';
	}
?>
		<div class=submit><input type=submit value="<?php echo $savetitle;?>"></div>
	</form>
	<p>&nbsp;</p>
<?php
}
	require("../footer.php");
?>
