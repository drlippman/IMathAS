<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	
	if (isset($_GET['remove'])) {
		if ($_GET['remove']=="really") {
			$blocktree = explode('-',$_GET['id']);
			$blockid = array_pop($blocktree) - 1; //-1 adjust for 1-index
			
			$query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$items = unserialize(mysql_result($result,0,0));
			$sub =& $items;
			if (count($blocktree)>1) {
				for ($i=1;$i<count($blocktree);$i++) {
					$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
				}
			}
			if (is_array($sub[$blockid])) { //make sure it's really a block
				$blockitems = $sub[$blockid]['items'];
				$obid = $sub[$blockid]['id'];
				if (count($blockitems)>0) {
					if (isset($_POST['delcontents']) && $_POST['delcontents']==1) { //clear out contents of block
						require("delitembyid.php");
						delrecurse($blockitems);
						array_splice($sub,$blockid,1); //remove block and contained items from itemorder
					} else {
						array_splice($sub,$blockid,1,$blockitems); //remove block, replace with items in block
					}
				} else {
					array_splice($sub,$blockid,1); //empty block; just remove block
				}
			}
			$itemlist = addslashes(serialize($items));
			$query = "UPDATE imas_courses SET itemorder='$itemlist' WHERE id='{$_GET['cid']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$obarr = explode(',',$_COOKIE['openblocks-'.$_GET['cid']]);
			$obloc = array_search($obid,$obarr);
			array_splice($obarr,$obloc,1);
			setcookie('openblocks-'.$_GET['cid'],implode(',',$obarr));
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
			
			exit;
		} else {
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; Remove Block</div>\n";
			echo "<form method=post action=\"addblock.php?cid={$_GET['cid']}&id={$_GET['id']}&remove=really\">";
			echo "<p>Are you SURE you want to delete this Block?</p>";
			echo "<p><input type=radio name=\"delcontents\" value=\"0\" checked=1/>Move contents to main course page<br/>";
			echo "<input type=radio name=\"delcontents\" value=\"1\"/>Also Delete all items in block</p>";
			echo "<p><input type=submit value=\"Yes, Remove\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='course.php?cid={$_GET['cid']}'\"></p>\n";
			require("../footer.php");
			exit;
		}
	}
	
	$cid = $_GET['cid'];
		
	if ($_POST['title']!= null) { //if the form has been submitted
		require_once("parsedatetime.php");
		if ($_POST['sdatetype']=='0') {
			$startdate = 0;
		} else if ($_POST['sdatetype']=='now') {
			$startdate = time()-2;
		} else {
			$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		}
		if ($_POST['edatetype']=='2000000000') {
			$enddate = 2000000000;
		} else {
			$enddate = parsedatetime($_POST['edate'],$_POST['etime']);
		}
		
		//$_POST['title'] = str_replace(array(',','\\"','\\\'','~'),"",$_POST['title']);
		
		$query = "SELECT itemorder,blockcnt FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
		$blockcnt = mysql_result($result,0,1);
		
		if (isset($_GET['block'])) { //adding new
			$blocktree = explode('-',$_GET['block']);
		} else { //modifying existing
			$blocktree = explode('-',$_GET['id']);
			$existingid = array_pop($blocktree) - 1; //-1 adjust for 1-index
		}
		
		if ($_POST['colors']=="def") {
			$colors = '';
		} else if ($_POST['colors']=="copy") {
			$blocktreecol = explode('-',$_POST['copycolors']);
			$sub2 = $items;
			for ($i=1;$i<count($blocktreecol);$i++) {
				$colors = $sub2[$blocktreecol[$i]-1]['colors'];
				$sub2 = $sub2[$blocktreecol[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		} else {
			$colors = $_POST['titlebg'].','.$_POST['titletxt'].','.$_POST['bi'];
		}
		

		$sub =& $items;
		if (count($blocktree)>1) {
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		}
		if (isset($existingid)) {  //already have id; update
			$sub[$existingid]['name'] = $_POST['title'];
			$sub[$existingid]['startdate'] = $startdate;
			$sub[$existingid]['enddate'] = $enddate;
			$sub[$existingid]['SH'] = $_POST['showhide'] . $_POST['availbeh'];
			$sub[$existingid]['colors'] = $colors;
		} else { //add new
			$blockitems = array();
			$blockitems['name'] = $_POST['title'];
			$blockitems['id'] = $blockcnt;
			$blockitems['startdate'] = $startdate;
			$blockitems['enddate'] = $enddate;
			$blockitems['SH'] = $_POST['showhide'] . $_POST['availbeh'];
			$blockitems['colors'] = $colors;
			$blockitems['items'] = array();
			array_push($sub,$blockitems);
			$blockcnt++;
		}
		$itemorder = addslashes(serialize($items));
		$query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt=$blockcnt WHERE id='$cid';";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
			
		exit;
	} else {
		if (isset($_GET['id'])) {
			$query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$items = unserialize(mysql_result($result,0,0));
			
			$blocktree = explode('-',$_GET['id']);
			$existingid = array_pop($blocktree) - 1; //-1 adjust for 1-index
			$blockitems = $items;
			if (count($blocktree)>1) {
				for ($i=1;$i<count($blocktree);$i++) {
					$blockitems = $blockitems[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
				}
			}
			
			$title = stripslashes($blockitems[$existingid]['name']);
			$title = str_replace('"','&quot;',$title);
			$startdate = $blockitems[$existingid]['startdate'];
			$enddate = $blockitems[$existingid]['enddate'];
			$showhide = $blockitems[$existingid]['SH'][0];
			if (strlen($blockitems[$existingid]['SH'])==1) {
				$availbeh = 'O';
			} else {
				$availbeh = $blockitems[$existingid]['SH'][1];
			}
			if ($blockitems[$existingid]['colors']=='') {
				$titlebg = "#DDDDFF";
				$titletxt = "#000000";
				$bi = "#EEEEFF";
				$usedef = 1;
			} else {
				list($titlebg,$titletxt,$bi) = explode(',',$blockitems[$existingid]['colors']);
				$usedef = 0;
			}
			
						
		} else {
			//set defaults
			$title = 'Enter Block name here';
			$startdate = time() + 60*60;
			$enddate = time() + 7*24*60*60;
			$availbeh = 'O';
			$showhide = 'H';
			$titlebg = "#DDDDFF";
			$titletxt = "#000000";
			$bi = "#EEEEFF";
			$usedef = 1;
			$query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$items = unserialize(mysql_result($result,0,0));
		}
		$existblocks = array();
		function buildexistblocks($items,$parent) {
			global $existblocks;
			foreach ($items as $k=>$item) {
				if (is_array($item)) {
					$existblocks[$parent.'-'.($k+1)] = $item['name'];
					if (count($item['items'])>0) {
						buildexistblocks($item['items'],$parent.'-'.($k+1));
					}
				}
			}
		}
		buildexistblocks($items,'0');
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
	$pagetitle = "Block Settings";
	$placeinhead = "<script type=\"text/javascript\">
	function init() {
		var inp1 = document.getElementById(\"titlebg\");
		attachColorPicker(inp1);
		var inp2 = document.getElementById(\"titletxt\");
		attachColorPicker(inp2);
		var inp3 = document.getElementById(\"bi\");
		attachColorPicker(inp3);
	}
	var imgBase = '$imasroot/javascript/cpimages';
	window.onload = init;
	</script>";
	$placeinhead .= "<style type=\"text/css\">img {	behavior:	 url(\"$imasroot/javascript/pngbehavior.htc\");}</style>";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/colorpicker.js\"></script>";
	
	require("../header.php");
	
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	if (isset($_GET['id'])) {
		echo "&gt; Modify Block</div>\n";
		echo "<h2>Modify Block <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=blocks','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
	} else {
		echo "&gt; Add Block</div>\n";
		echo "<h2>Add Block <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=blocks','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
	}
?>

<form method=post action="addblock.php?cid=<?php echo $cid; if (isset($_GET['id'])) {echo "&id={$_GET['id']}";} if (isset($_GET['block'])) {echo "&block={$_GET['block']}";}?>&folder=<?php echo $_GET['folder'];?>">
<span class=form>Title: </span><span class=formright><input type=text size=60 name=title value="<?php echo str_replace('"','&quot;',$title);?>"></span><BR class=form>

<script src="../javascript/CalendarPopup.js"></script>
<SCRIPT LANGUAGE="JavaScript" ID="js1">
var cal1 = new CalendarPopup();
</SCRIPT>

<span class=form>Available After:</span><span class=formright><input type=radio name="sdatetype" value="0" <?php if ($startdate=='0') {echo "checked=1";}?>/> Always until end date<br/>
<input type=radio name="sdatetype" value="now"/> Now<br/>
<input type=radio name="sdatetype" value="sdate" <?php if ($startdate!='0') {echo "checked=1";}?>/><input type=text size=10 name=sdate value="<?php echo $sdate;?>"> 
<A HREF="#" onClick="cal1.select(document.forms[0].sdate,'anchor1','MM/dd/yyyy',document.forms[0].sdate.value); return false;" NAME="anchor1" ID="anchor1"><img src="../img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=stime value="<?php echo $stime;?>"></span><BR class=form>

<span class=form>Available Until:</span><span class=formright>
<input type=radio name="edatetype" value="2000000000" <?php if ($enddate=='2000000000') {echo "checked=1";}?>/> Always after start date<br/>
<input type=radio name="edatetype" value="edate"  <?php if ($enddate!='2000000000') {echo "checked=1";}?>/>
<input type=text size=10 name=edate value="<?php echo $edate;?>"> 
<A HREF="#" onClick="cal1.select(document.forms[0].edate,'anchor2','MM/dd/yyyy',(document.forms[0].sdate.value=='<?php echo $sdate;?>')?(document.forms[0].edate.value):(document.forms[0].sdate.value)); return false;" NAME="anchor2" ID="anchor2"><img src="../img/cal.gif" alt="Calendar"/></A>
at <input type=text size=10 name=etime value="<?php echo $etime;?>"></span><BR class=form>

<span class=form>When available:</span><span class=formright>
		<input type=radio name=availbeh value="O" <?php if ($availbeh=='O') {echo "CHECKED";}?> />Show Expanded<br/>
		<input type=radio name=availbeh value="C" <?php if ($availbeh=='C') {echo "CHECKED";}?> />Show Collapsed<br/>
		<input type=radio name=availbeh value="F" <?php if ($availbeh=='F') {echo "CHECKED";}?> />Show as Folder</span><br class=form />
<span class=form>When not available:</span><span class=formright>
		<input type=radio name=showhide value="H" <?php if ($showhide=='H') {echo "CHECKED";}?> />Hide from Students<br/>
		<input type=radio name=showhide value="S" <?php if ($showhide=='S') {echo "CHECKED";}?> />Show Collapsed/as folder</span><br class=form />


<span class=form>Block colors:</span><span class=formright>
		<input type=radio name=colors value="def" <?php if ($usedef==1) {echo "CHECKED";}?> />Use defaults<br/>
		<input type=radio name=colors value="custom" <?php if ($usedef==0) {echo "CHECKED";}?> />Use custom:
		<table style="display: inline; border-collapse: collapse;"><tr><td id="ex1" style="border: 1px solid #000;background-color:<?php echo $titlebg;?>;color:<?php echo $titletxt;?>;">
		   Title</td></tr><tr><td id="ex2" style="border: 1px solid #000;background-color:<?php echo $bi;?>;">&nbsp;</td></tr></table>
		    <br/><table style=" margin-left: 30px;">
		<tr><td>Title Background: </td><td><input type=text id="titlebg" name="titlebg" value="<?php echo $titlebg;?>" /></td></tr>
		<tr><td>Title Text: </td><td><input type=text id="titletxt" name="titletxt" value="<?php echo $titletxt;?>" /></td></tr>
		<tr><td>Items Background: </td><td><input type=text id="bi" name="bi" value="<?php echo $bi;?>" /></td></tr> </table>
		<br/>
		<input type=radio name=colors value="copy" <?php if ($usedef==2) {echo "CHECKED";}?> />Copy colors from block: 
		<select name="copycolors">
		<?php
		foreach ($existblocks as $k=>$name) {
			echo "<option value=\"$k\">".stripslashes($name)."</option>";
		}
		?>
		</select>
		</span><br class="form"/>
		
		
<div class=submit><input type=submit value=Submit></div>
<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
<?php
	require("../footer.php");
?>
