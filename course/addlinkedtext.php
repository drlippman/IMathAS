<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");
require("../includes/parsedatetime.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$useeditor = "text,summary";


$curBreadcrumb = "<a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
if (isset($_GET['id'])) {
	$curBreadcrumb .= "&gt; Modify Linked Text\n";
	$pagetitle = "Modify Linked Text";
} else {
	$curBreadcrumb .= "&gt; Add Linked Text\n";
	$pagetitle = "Add Linked Text";
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
	
	if ($_POST['title']!= null) { //if the form has been submitted
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
				$overwriteBody = 1;
				$body = "<p>File type is not allowed</p>";
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
					$overwriteBody = 1;
					$body = "<p>Error uploading file!</p>\n";
					$body .= "<p><a href=\"addlinkedtext.php?cid={$_GET['cid']}";
					if (isset($_GET['id'])) {
						$body .= "id={$_GET['id']}";
					}
					$body .= "\">Try Again</a></p>\n";
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
			
			$query = "UPDATE imas_linkedtext SET title='{$_POST['title']}',summary='{$_POST['summary']}',text='{$_POST['text']}',startdate=$startdate,enddate=$enddate,avail='{$_POST['avail']}',oncal='{$_POST['oncal']}',caltag='{$_POST['caltag']}' ";
			$query .= "WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
		} else { //add new
		$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate,avail,oncal,caltag) VALUES ";
		$query .= "('$cid','{$_POST['title']}','{$_POST['summary']}','{$_POST['text']}',$startdate,$enddate,'{$_POST['avail']}','{$_POST['oncal']}','{$_POST['caltag']}');";
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
			$line['avail'] = 1;
			$line['oncal'] = 0;
			$line['caltag'] = '!';
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
}
	
/******* begin html output ********/
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?> 	
	<script src="../javascript/CalendarPopup.js"></script>
	<SCRIPT LANGUAGE="JavaScript" ID="js1">
	var cal1 = new CalendarPopup();
	</SCRIPT>
	
	<div class=breadcrumb><?php echo $curBreadcrumb  ?></div>
	<h2><?php echo $pagetitle ?></h2>


	<form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
		<span class=form>Title: </span>
		<span class=formright><input type=text size=60 name=title value="<?php echo str_replace('"','&quot;',$line['title']);?>">
		</span><BR class=form>
		
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
		<span class=form>Or attach file (Max 3MB)<sup>*</sup>: </span>
		<span class=formright><input name="userfile" type="file" /></span><br class=form>
		

		<span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($line['avail'],0);?>/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($line['avail'],1);?>/>Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php writeHtmlChecked($line['avail'],2);?>/>Show Always<br/>
		</span><br class="form"/>
		<span class=form>Available After:</span>
		<span class=formright>
			<input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,'0',0) ?>/> 
			Always until end date<br/>
			<input type=radio name="sdatetype" value="sdate" <?php writeHtmlChecked($startdate,'0',1) ?>/>
			<input type=text size=10 name=sdate value="<?php echo $sdate;?>"> 
			<A HREF="#" onClick="cal1.select(document.forms[0].sdate,'anchor1','MM/dd/yyyy',document.forms[0].sdate.value); return false;" NAME="anchor1" ID="anchor1">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=stime value="<?php echo $stime;?>">
		</span><BR class=form>
		
		<span class=form>Available Until:</span><span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/> Always after start date<br/>
			<input type=radio name="edatetype" value="edate"  <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
			<input type=text size=10 name=edate value="<?php echo $edate;?>"> 
			<A HREF="#" onClick="cal1.select(document.forms[0].edate,'anchor2','MM/dd/yyyy',(document.forms[0].sdate.value=='<?php echo $sdate;?>')?(document.forms[0].edate.value):(document.forms[0].sdate.value)); return false;" NAME="anchor2" ID="anchor2">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=etime value="<?php echo $etime;?>">
		</span><BR class=form>
		<span class=form>Place on Calendar?</span>
		<span class=formright>
			<input type=radio name="oncal" value=0 <?php writeHtmlChecked($line['oncal'],0); ?> /> No<br/>
			<input type=radio name="oncal" value=1 <?php writeHtmlChecked($line['oncal'],1); ?> /> Yes, on Available after date (will only show after that date)<br/>
			<input type=radio name="oncal" value=2 <?php writeHtmlChecked($line['oncal'],2); ?> /> Yes, on Available until date<br/>
			With tag: <input name="caltag" type=text size=1 maxlength=1 value="<?php echo $line['caltag'];?>"/>
		</span><br class="form" />
		
		<div class=submit><input type=submit value=Submit></div>	
	</form>
	
	<p><sup>*</sup>Avoid quotes in the filename</p>
<?php
}
	require("../footer.php");
?>
