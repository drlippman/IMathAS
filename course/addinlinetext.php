<?php
//IMathAS:  Add/modify inline text items
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
			
			$query = "SELECT id FROM imas_items WHERE typeid='$textid' AND itemtype='InlineText'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$itemid = mysql_result($result,0,0);
			
			$query = "DELETE FROM imas_items WHERE id='$itemid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "DELETE FROM imas_inlinetext WHERE id='$textid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "SELECT filename FROM imas_instr_files WHERE itemid='$textid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
			while ($row = mysql_fetch_row($result)) {
				$safefn = addslashes($row[0]);
				$query = "SELECT id FROM imas_instr_files WHERE filename='$safefn'";
				$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($r2)==1) {
					unlink($uploaddir . $row[0]);
				}
			}
			$query = "DELETE FROM imas_instr_files WHERE itemid='$textid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
						
			$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
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
			
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
			
			exit;
		} else {
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";
			echo "&gt; Modify Inline Text</div>\n";
			echo "Are you SURE you want to delete this text item?";
			echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='addinlinetext.php?cid=$cid&block=$block&id={$_GET['id']}&remove=really'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='course.php?cid=$cid'\"></p>\n";
			require("../footer.php");
			exit;
		}
	}
	
	
	
	if ($_POST['title']!= null || $_POST['text']!=null || $_POST['sdate']!=null) { //if the form has been submitted
		require_once("parsedatetime.php");
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
		if (isset($_POST['hidetitle'])) {
			$_POST['title']='##hidden##';
		}
		
		$filestoremove = array();
		if (isset($_GET['id'])) {  //already have id; update
			$query = "UPDATE imas_inlinetext SET title='{$_POST['title']}',text='{$_POST['text']}',startdate=$startdate,enddate=$enddate ";
			$query .= "WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			//update attached files
			$query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				if (isset($_POST['delfile-'.$row[0]])) {
					$filestoremove[] = $row[0];
					$query = "DELETE FROM imas_instr_files WHERE id='{$row[0]}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
					$query = "SELECT id FROM imas_instr_files WHERE filename='{$row[2]}'";
					$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($r2)==0) {
						$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
						unlink($uploaddir . $row[2]);
					}
				} else if ($_POST['filedescr-'.$row[0]]!=$row[1]) {
					$query = "UPDATE imas_instr_files SET description='{$_POST['filedescr-'.$row[0]]}' WHERE id='{$row[0]}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			}	
			$newtextid = $_GET['id'];
		} else { //add new
			
			$query = "INSERT INTO imas_inlinetext (courseid,title,text,startdate,enddate) VALUES ";
			$query .= "('$cid','{$_POST['title']}','{$_POST['text']}',$startdate,$enddate);";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$newtextid = mysql_insert_id();
			
			$query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
			$query .= "('$cid','InlineText','$newtextid');";
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
		if ($_FILES['userfile']['name']!='') { 
			$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
			$userfilename = preg_replace('/[^\w\.]/','',basename($_FILES['userfile']['name']));
			$filename = $userfilename;
			$extension = strtolower(strrchr($userfilename,"."));
			$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p");
			if (in_array($extension,$badextensions)) {
				echo "<p>File type is not allowed</p>";
			} else {
				$uploadfile = $uploaddir . $filename;
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
				} else {
					echo "<p>Error uploading file!</p>\n";
					exit;
				}
			}
		}
		
		
	} 
	if (isset($addedfile) || count($filestoremove)>0 || isset($_GET['movefile'])) {
		$query = "SELECT fileorder FROM imas_inlinetext WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$fileorder = explode(',',mysql_result($result,0,0));
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
		$query = "UPDATE imas_inlinetext SET fileorder='$fileorder' WHERE id='{$_GET['id']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	if ($_POST['submitbtn']=='Submit') {
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
		
		exit;
	}
	if (isset($_GET['id'])) {
		$query = "SELECT * FROM imas_inlinetext WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		if ($line['title']=='##hidden##') {
			$hidetitle = true;
			$line['title']='';
		}
		$startdate = $line['startdate'];
		$enddate = $line['enddate'];
		$fileorder = explode(',',$line['fileorder']);
	} else {
		//set defaults
		$line['title'] = "Enter title here";
		$line['text'] = "<p>Enter text here</p>";
		$startdate = time();
		$enddate = time() + 7*24*60*60;
		$hidetitle = false;
		$fileorder = array();
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
	
	$useeditor = "text";
	$pagetitle = "Inline Text Settings";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	if (isset($_GET['id'])) {
		echo "&gt; Modify Inline Text</div>\n";
		echo "<h2>Modify Inline Text <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=inlinetextitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
	} else {
		echo "&gt; Add Inline Text</div>\n";
		echo "<h2>Add Inline Text <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=inlinetextitems','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
	}
?>

<form enctype="multipart/form-data" method=post action="addinlinetext.php?block=<?php echo $block;?>&cid=<?php echo $cid; if (isset($_GET['id'])) {echo "&id={$_GET['id']}";}?>&folder=<?php echo $_GET['folder'];?>">
<span class=form>Title: </span><span class=formright><input type=text size=60 name=title value="<?php echo $line['title'];?>">
<br/><input type="checkbox" name="hidetitle" value="1" <?php if ($hidetitle==true) {echo "checked=1";} ?>/> Hide title and icon

</span><BR class=form>

Text:<BR>
<div class=editor>
<textarea cols=60 rows=20 id=text name=text style="width: 100%"><?php echo $line['text'];?></textarea>
</div>

<span class=form>
Attached Files:</span><span class=wideformright>

<?php
echo "<script type=\"text/javascript\">\n";
echo "function movefile(from) { \n";
echo "  var to = document.getElementById('ms-'+from).value; \n";
$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/addinlinetext.php?cid=$cid&block=$block&id={$_GET['id']}";
echo "  if (to != from) {\n";
echo "  	var toopen = '$address&movefile=' + from + '&movefileto=' + to;\n";
echo "  	window.location = toopen; \n";
echo "  }\n";
echo "}\n";
echo "</script>\n";

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
   
if (isset($_GET['id'])) {
	$query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='{$_GET['id']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		while ($row = mysql_fetch_row($result)) {
			$filedescr[$row[0]] = $row[1];
			$filenames[$row[0]] = rawurlencode($row[2]);
		}
		foreach ($fileorder as $k=>$fid) {
			echo generatemoveselect(count($fileorder),$k);
			echo "<a href=\"$imasroot/course/files/{$filenames[$fid]}\" target=\"_blank\">View</a> ";
			echo "<input type=\"text\" name=\"filedescr-$fid\" value=\"{$filedescr[$fid]}\"/> Delete? <input type=checkbox name=\"delfile-$fid\"/><br/>";	
		}
	}
} 
?>

<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
New file<sup>*</sup>: <input type="file" name="userfile"/><br/>
Description: <input type="text" name="newfiledescr"/><br/>
<input type=submit name="submitbtn" value="Add / Update Files"/>
</span><br class=form>
<div>
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
</div>
<div class=submit><input type=submit name="submitbtn" value="Submit"></div>
<p><sup>*</sup>Avoid quotes in the filename</p>
<?php
	require("../footer.php");
?>
