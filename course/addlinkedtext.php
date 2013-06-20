<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");
require("../includes/parsedatetime.php");
@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$useeditor = "text,summary";


$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
if (isset($_GET['id'])) {
	$curBreadcrumb .= "&gt; Modify Link\n";
	$pagetitle = "Modify Link";
} else {
	$curBreadcrumb .= "&gt; Add Link\n";
	$pagetitle = "Add Link";
}	
if (isset($_GET['tb'])) {
	$totb = $_GET['tb'];
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
	$cid = $_GET['cid'];
	$block = $_GET['block'];	
	$page_formActionTag = "addlinkedtext.php?block=$block&cid=$cid&folder=" . $_GET['folder'];
	$page_formActionTag .= (isset($_GET['id'])) ? "&id=" . $_GET['id'] : "";
	$page_formActionTag .= "&tb=$totb";
	$uploaderror = false;
	$caltag = $_POST['caltag'];
	
	if ($_POST['title']!= null) { //if the form has been submitted
		if ($_POST['avail']==1) {
			if ($_POST['sdatetype']=='0') {
				$startdate = 0;
			} else if ($_POST['sdatetype']=='now') {
				$startdate = time();
			} else {
				$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
			}
			if ($_POST['edatetype']=='2000000000') {
				$enddate = 2000000000;
			} else {
				$enddate = parsedatetime($_POST['edate'],$_POST['etime']);
			}
			$oncal = $_POST['oncal'];
		} else if ($_POST['avail']==2) {
			if ($_POST['altoncal']==0) {
				$startdate = 0;
				$oncal = 0;
			} else {
				$startdate = parsedatetime($_POST['cdate'],"12:00 pm");
				$oncal = 1;
				$caltag = $_POST['altcaltag'];
			}
			$enddate =  2000000000;
		} else {
			$startdate = 0;
			$enddate =  2000000000;
			$oncal = 0;
		}
		
		$processingerror = false;
		if ($_POST['linktype']=='text') {
			require_once("../includes/htmLawed.php");
			$htmlawedconfig = array('elements'=>'*-script');
			$_POST['text'] = addslashes(htmLawed(stripslashes($_POST['text']),$htmlawedconfig));
		} else if ($_POST['linktype']=='file') {
			require_once("../includes/filehandler.php");
			if ($_FILES['userfile']['name']!='') {
				//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
				//$uploadfile = $uploaddir . "$cid-" . basename($_FILES['userfile']['name']);
				$userfilename = preg_replace('/[^\w\.]/','',basename($_FILES['userfile']['name']));
				$filename = $userfilename;
				$extension = strtolower(strrchr($userfilename,"."));
				$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p");
				if (in_array($extension,$badextensions)) {
					$overwriteBody = 1;
					$body = "<p>File type is not allowed</p>";
				} else {
					if ($_FILES['userfile']['error']==1 || $_FILES['userfile']['error']==2) {
						$errormsg = "File size too large";
						$_POST['text'] = "File upload error - $errormsg";
						$uploaderror = true;
					} else {
						if (($filename=storeuploadedcoursefile('userfile',$cid.'/'.$filename))===false) {
							$errormsg = "Try again";
							$_POST['text'] = "File upload error - $errormsg";
							$uploaderror = true;
						} else {
							$_POST['text'] = "file:$filename";
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
				if (!doesfileexist('cfile',$_POST['curfile'])) {
					$processingerror = true;
				} else {
					$_POST['text'] = "file:".$_POST['curfile'];
				}
			} else {
				$processingerror = true;
			}
		} else if ($_POST['linktype']=='web') {
			$_POST['text'] = trim(strip_tags($_POST['web']));
			if (substr($_POST['text'],0,4)!='http') {
				$processingerror = true;
			}
		} else if ($_POST['linktype']=='tool') {
			if ($_POST['tool']==0) {
				$processingerror = true;
			} else {
				$_POST['text'] = 'exttool:'.$_POST['tool'].'~~'.$_POST['toolcustom'];
			}
		}
		
		require_once("../includes/htmLawed.php");
		$htmlawedconfig = array('elements'=>'*-script' );
		if ($_POST['summary']=='<p>Enter summary here (displays on course page)</p>') {
			$_POST['summary'] = '';
		} else {
			$_POST['summary'] = addslashes(htmLawed(stripslashes($_POST['summary']),$htmlawedconfig));
		}
		$_POST['text'] = trim($_POST['text']);
		if (isset($_GET['id'])) {  //already have id; update
			$query = "SELECT text FROM imas_linkedtext WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$text = trim(mysql_result($result,0,0));
			if (substr($text,0,5)=='file:') { //has file
				$safetext = addslashes($text);
				if ($_POST['text']!=$safetext) { //if not same file, delete old if not used
					$query = "SELECT id FROM imas_linkedtext WHERE text='$safetext'"; //any others using file?
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($result)==1) { 
						//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
						$filename = substr($text,5);
						deletecoursefile($filename);
						//if (file_exists($uploaddir . $filename)) {
						//	unlink($uploaddir . $filename);
						//}
					}
				}
			}
			if (!$processingerror) {
				$query = "UPDATE imas_linkedtext SET title='{$_POST['title']}',summary='{$_POST['summary']}',text='{$_POST['text']}',startdate=$startdate,enddate=$enddate,avail='{$_POST['avail']}',oncal='$oncal',caltag='$caltag',target='{$_POST['target']}' ";
				$query .= "WHERE id='{$_GET['id']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
			}
		} else if (!$processingerror) { //add new
			$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate,avail,oncal,caltag,target) VALUES ";
			$query .= "('$cid','{$_POST['title']}','{$_POST['summary']}','{$_POST['text']}',$startdate,$enddate,'{$_POST['avail']}','$oncal','$caltag','{$_POST['target']}');";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$newtextid = mysql_insert_id();
			
			$query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
			$query .= "('$cid','LinkedText','$newtextid');";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$itemid = mysql_insert_id();
						
			$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
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
			$itemorder = addslashes(serialize($items));
			
			$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		
		}
		if ($uploaderror == true || $processingerror == true) {
			if ($uploaderror == true) {
				$body = "<p>Error uploading file! $errormsg</p>\n";
			} else {
				$body = "<p>Error with your submission</p>";
			}
			$body .= "<p><a href=\"addlinkedtext.php?cid={$_GET['cid']}";
			if (isset($_GET['id'])) {
				$body .= "&id={$_GET['id']}";
			} else {
				$body .= "&id=$newtextid";
			}
			$body .= "\">Try Again</a></p>\n";
			echo "<html><body>$body</body></html>";
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
		}
		exit;
	} else {
		$toolcustom = '';
		$selectedtool = 0;
		$filename = '';
		$webaddr = '';
		if (isset($_GET['id'])) {
			$query = "SELECT * FROM imas_linkedtext WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			$startdate = $line['startdate'];
			$enddate = $line['enddate'];
			if ($line['avail']==2 && $startdate>0) {
				$altoncal = 1;
			} else {
				$altoncal = 0;
			}
			if (substr($line['text'],0,4)=='http') {
				$type = 'web';
				$webaddr = $line['text'];
				$line['text'] = "<p>Enter text here</p>";
			} else if (substr($line['text'],0,5)=='file:') {
				$type = 'file';
				$filename = substr($line['text'],5);
				$line['text'] = "<p>Enter text here</p>";
			} else if (substr($line['text'],0,8)=='exttool:') {
				$type = 'tool';
				list($selectedtool,$toolcustom) = explode('~~',substr($line['text'],8));
				$line['text'] = "<p>Enter text here</p>";
			} else {
				$type = 'text';
			}
			if ($line['summary']=='') {
				$line['summary'] = "<p>Enter summary here (displays on course page)</p>";
			}
		} else {
			//set defaults
			$line['title'] = "Enter title here";
			$line['summary'] = "<p>Enter summary here (displays on course page)</p>";
			$line['text'] = "<p>Enter text here</p>";
			$line['avail'] = 1;
			$line['oncal'] = 0;
			$line['caltag'] = '!';
			$line['target'] = 0;
			$altoncal = 0;
			$startdate = time();
			$enddate = time() + 7*24*60*60;
			$type = 'text';
		}   
		if ($startdate!=0) {
			$sdate = tzdate("m/d/Y",$startdate);
			$stime = tzdate("g:i a",$startdate);
		} else {
			$sdate = tzdate("m/d/Y",time());
			$stime = tzdate("g:i a",time());
		}
		if ($enddate!=2000000000) {
			$edate = tzdate("m/d/Y",$enddate);
			$etime = tzdate("g:i a",$enddate);	
		} else {
			$edate = tzdate("m/d/Y",time()+7*24*60*60);
			$etime = tzdate("g:i a",time()+7*24*60*60);
		}     
		$toolvals = array(0);
		$toollabels = array('Select a tool...');
		$query = "SELECT id,name FROM imas_external_tools WHERE courseid='$cid' ";
		$query .= "OR (courseid=0 AND (groupid='$groupid' OR groupid=0)) ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$toolvals[] = $row[0];
			$toollabels[] = $row[1];
		}
		if ($selectedtool>0 && !in_array($selectedtool,$toolvals)) {
			$type = 'text';
			$line['text'] = "<p>Invalid tool was selected</p>";
		}
			
	}
}
	
/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
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
 }
 </script>';
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?> 	
	
	<div class=breadcrumb><?php echo $curBreadcrumb  ?></div>
	<div id="headeraddlinkedtext" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>


	<form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
		<span class=form>Title: </span>
		<span class=formright><input type=text size=60 name=title value="<?php echo str_replace('"','&quot;',$line['title']);?>">
		</span><BR class=form>
		
		Summary<BR>
		<div class=editor>
			<textarea cols=60 rows=10 id=summary name=summary style="width: 100%"><?php echo htmlentities($line['summary']);?></textarea>
		</div>
		<br/>
		
		<span class=form>Link type: </span>
		<span class="formright">
		<select id="linktype" name="linktype" onchange="linktypeupdate(this)">
			<option value="text" <?php writeHtmlSelected($type,'text');?>>Page of text</option>
			<option value="web" <?php writeHtmlSelected($type,'web');?>>Web link</option>
			<option value="file" <?php writeHtmlSelected($type,'file');?>>File</option>
			<option value="tool" <?php writeHtmlSelected($type,'tool');?>>External Tool</option>
		</select>
		</span><br class="form"/>
		
		<div id="textinput" <?php if ($type != 'text') {echo 'style="display:none;"';}?> >
			Text<BR>
			<div class=editor>
				<textarea cols=80 rows=20 id=text name=text style="width: 100%"><?php echo htmlentities($line['text']);?></textarea>
			</div>
		</div>
		<div id="webinput" <?php if ($type != 'web') {echo 'style="display:none;"';}?> >
			<span class="form">Weblink (start with http://)</span>
			<span class="formright">
				<input size="80" name="web" value="<?php echo htmlentities($webaddr);?>" />
			</span><br class="form">
			
		</div>
		<div id="fileinput" <?php if ($type != 'file') {echo 'style="display:none;"';}?>>
			<span class="form">File</span>
			<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
			<span class="formright">
			<?php if ($filename != '') {
				require_once("../includes/filehandler.php");
				echo '<input type="hidden" name="curfile" value="'.$filename.'"/>';
				$alink = getcoursefileurl($filename);
				echo 'Current file: <a href="'.$alink.'">'.basename($filename).'</a><br/>Replace ';
			} else {
				echo 'Attach ';
			}
			?>
			file (Max 10MB)<sup>*</sup>: <input name="userfile" type="file" />
			</span><br class="form">
		</div>
		<div id="toolinput" <?php if ($type != 'tool') {echo 'style="display:none;"';}?>>
			<span class="form">External Tool</span>
			<span class="formright">
			<?php 
			if (count($toolvals)>0) {
				writeHtmlSelect('tool',$toolvals,$toollabels,$selectedtool);
				echo '<br/>Custom parameters: <input type="text" name="toolcustom" size="40" value="'.htmlentities($toolcustom).'" /><br/>';
			} else {
				echo 'No Tools defined yet<br/>';
			}
			if (!isset($CFG['GEN']['noInstrExternalTools'])) {
				echo '<a href="../admin/externaltools.php?cid='.$cid.'&amp;ltfrom='.$_GET['id'].'">Add or edit an external tool</a>';
			}
			?>
			</span><br class="form">
		</div>
		
		<span class="form">Open page in:</span>
		<span class="formright">
			<input type=radio name="target" value="0" <?php writeHtmlChecked($line['target'],0);?>/>Current window/tab<br/>
			<input type=radio name="target" value="1" <?php writeHtmlChecked($line['target'],1);?>/>New window/tab<br/>
		</span><br class="form"/>
		
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
		
		<span class=form>Available Until:</span><span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/> Always after start date<br/>
			<input type=radio name="edatetype" value="edate"  <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
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
			With tag: <input name="caltag" type=text size=1 value="<?php echo $line['caltag'];?>"/>
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
			With tag: <input name="altcaltag" type=text size=1 value="<?php echo $line['caltag'];?>"/>
		</span><BR class=form>
		</div>
		
		<div class=submit><input type=submit value=Submit></div>	
	</form>
	
	<p><sup>*</sup>Avoid quotes in the filename</p>
<?php
}
	require("../footer.php");
?>
