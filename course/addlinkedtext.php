<?php
//IMathAS:  Add/modify linked text items.
//(c) 2006 David Lippman
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	
	$cid = $_GET['cid'];
	$block = $_GET['block'];
	
	if (isset($_GET['remove'])) {
		if ($_GET['remove']=="really") {
			$textid = $_GET['id'];
			
			$query = "SELECT id FROM imas_items WHERE typeid='$textid' AND itemtype='LinkedText'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemid = mysql_result($result,0,0);
			
			$query = "DELETE FROM imas_items WHERE id='$itemid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "SELECT text FROM imas_linkedtext WHERE id='$textid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$text = trim(mysql_result($result,0,0));
			if (substr($text,0,5)=='file:') { //delete file if not used
				$safetext = addslashes($text);
				$query = "SELECT id FROM imas_linkedtext WHERE text='$safetext'"; //any others using file?
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)==1) { 
					$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
					$filename = substr($text,5);
					unlink($uploaddir . $filename);
				}
			}
			
			$query = "DELETE FROM imas_linkedtext WHERE id='$textid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
						
			$query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$items = unserialize(mysql_result($result,0,0));
			
			$blocktree = explode('-',$block);
			$sub =& $items;
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
			$key = array_search($itemid,$sub);
			array_splice($sub,$key,1);
			$itemorder = addslashes(serialize($items));
			$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
		
			exit;
		} else {
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; Modify Linked Text</div>\n";
			echo "Are you SURE you want to delete this text item?";
			echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='addlinkedtext.php?cid={$_GET['cid']}&block=$block&id={$_GET['id']}&remove=really'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='course.php?cid={$_GET['cid']}'\"></p>\n";
			require("../footer.php");
			exit;
		}
	}
	
	
	if ($_POST['title']!= null) { //if the form has been submitted
		require_once("parsedatetime.php");
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
		if ($_FILES['userfile']['name']!='') {
			$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
			//$uploadfile = $uploaddir . "$cid-" . basename($_FILES['userfile']['name']);
			$userfilename = preg_replace('/[^\w\.]/','',basename($_FILES['userfile']['name']));
			$filename = $userfilename;
			$extension = strtolower(strrchr($userfilename,"."));
			$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p");
			if (in_array($extension,$badextensions)) {
				echo "<p>File type is not allowed</p>";
			} else {
				$uploadfile = $uploaddir . $filename;
				$t=0;
				while(file_exists($uploadfile)){ //make sure filename is unused
					$filename = substr($filename,0,strpos($userfilename,"."))."_$t".strstr($userfilename,".");
					$uploadfile=$uploaddir.$filename;
					$t++;
				}
				
				if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
					//echo "<p>File is valid, and was successfully uploaded</p>\n";
				} else {
					echo "<p>Error uploading file!</p>\n";
					echo "<p><a href=\"addlinkedtext.php?cid={$_GET['cid']}";
					if (isset($_GET['id'])) {
						echo "id={$_GET['id']}";
					}
					echo "\">Try Again</a></p>\n";
					exit;
				}
				//$_POST['text'] = "file:$cid-" . basename($_FILES['userfile']['name']);
				$_POST['text'] = "file:$filename";
			}
			
		} else if (substr(trim(strip_tags($_POST['text'])),0,4)=="http") {
			$_POST['text'] = trim(strip_tags($_POST['text']));	
		} 
		$_POST['text'] = trim($_POST['text']);
		if (isset($_GET['id'])) {  //already have id; update
			$query = "SELECT text FROM imas_linkedtext WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$text = trim(mysql_result($result,0,0));
			if (substr($text,0,5)=='file:') { //has file
				$safetext = addslashes($text);
				if ($_POST['text']!=$safetext) { //if not same file
					$query = "SELECT id FROM imas_linkedtext WHERE text='$safetext'"; //any others using file?
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($result)==1) { 
						$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
						$filename = substr($text,5);
						unlink($uploaddir . $filename);
					}
				}
			}
			
			$query = "UPDATE imas_linkedtext SET title='{$_POST['title']}',summary='{$_POST['summary']}',text='{$_POST['text']}',startdate=$startdate,enddate=$enddate ";
			$query .= "WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		} else { //add new
		$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate) VALUES ";
		$query .= "('$cid','{$_POST['title']}','{$_POST['summary']}','{$_POST['text']}',$startdate,$enddate);";
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
		$sub[] = $itemid;
		$itemorder = addslashes(serialize($items));
		
		$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		
		}
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
		
		exit;
	} else {
		if (isset($_GET['id'])) {
			$query = "SELECT * FROM imas_linkedtext WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			$startdate = $line['startdate'];
			$enddate = $line['enddate'];
		} else {
			//set defaults
			$line['title'] = "Enter title here";
			$line['summary'] = "<p>Enter summary here (displays on course page)</p>";
			$line['text'] = "<p>Enter text here</p>";
			$startdate = time();
			$enddate = time() + 7*24*60*60;
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
	}
	$useeditor = "text,summary";
	$pagetitle = "Linked Text Settings";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	if (isset($_GET['id'])) {
		echo "&gt; Modify Linked Text</div>\n";
		echo "<h2>Modify Linked Text <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=linkedtextitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
	} else {
		echo "&gt; Add Linked Text</div>\n";
		echo "<h2>Add Linked Text <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=linkedtextitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
	}
?>

<form enctype="multipart/form-data" method=post action="addlinkedtext.php?block=<?php echo $block;?>&cid=<?php echo $cid; if (isset($_GET['id'])) {echo "&id={$_GET['id']}";}?>&folder=<?php echo $_GET['folder'];?>">
<span class=form>Title: </span><span class=formright><input type=text size=60 name=title value="<?php echo $line['title'];?>"></span><BR class=form>

Summary<BR>
<div class=editor>
<textarea cols=60 rows=10 id=summary name=summary style="width: 100%"><?php echo $line['summary'];?></textarea>
</div>
<BR>
Text or weblink (start with http://)<BR>
<div class=editor>
<textarea cols=80 rows=20 id=text name=text style="width: 100%"><?php echo $line['text'];?></textarea>
</div>
<BR>
<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
<span class=form>Or attach file (Max 3MB)<sup>*</sup>: </span><span class=formright><input name="userfile" type="file" /></span><br class=form>

<script src="../javascript/CalendarPopup.js"></script>
<SCRIPT LANGUAGE="JavaScript" ID="js1">
var cal1 = new CalendarPopup();
</SCRIPT>

<span class=form>Available After:</span><span class=formright><input type=radio name="sdatetype" value="0" <?php if ($startdate=='0') {echo "checked=1";}?>/> Always until end date<br/>
<input type=radio name="sdatetype" value="sdate" <?php if ($startdate!='0') {echo "checked=1";}?>/><input type=text size=10 name=sdate value="<?php echo $sdate;?>"> 
<A HREF="#" onClick="cal1.select(document.forms[0].sdate,'anchor1','MM/dd/yyyy',document.forms[0].sdate.value); return false;" NAME="anchor1" ID="anchor1"><img src="../img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=stime value="<?php echo $stime;?>"></span><BR class=form>

<span class=form>Available Until:</span><span class=formright>
<input type=radio name="edatetype" value="2000000000" <?php if ($enddate=='2000000000') {echo "checked=1";}?>/> Always after start date<br/>
<input type=radio name="edatetype" value="edate"  <?php if ($enddate!='2000000000') {echo "checked=1";}?>/>
<input type=text size=10 name=edate value="<?php echo $edate;?>"> 
<A HREF="#" onClick="cal1.select(document.forms[0].edate,'anchor2','MM/dd/yyyy',(document.forms[0].sdate.value=='<?php echo $sdate;?>')?(document.forms[0].edate.value):(document.forms[0].sdate.value)); return false;" NAME="anchor2" ID="anchor2"><img src="../img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=etime value="<?php echo $etime;?>"></span><BR class=form>

<div class=submit><input type=submit value=Submit></div>
<p><sup>*</sup>Avoid quotes in the filename</p>
<?php
	require("../footer.php");
?>
