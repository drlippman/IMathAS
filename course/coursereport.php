<?php 
//IMathAS:  Main course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("courseshowitems.php");
require("../includes/htmlutil.php");
require("../includes/calendardisp.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($guestid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = _("You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n");
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$cid = $_GET['cid'];
	
   
		
	$query = "SELECT name,itemorder,hideicons,picicons,allowunenroll,msgset,toolset,chatset,topbar,cploc,latepasshrs FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	if ($line == null) {
		$overwriteBody = 1;
		$body = _("Course does not exist.  <a hre=\"../index.php\">Return to main page</a>") . "</body></html>\n";
	}	
	


	//DEFAULT DISPLAY PROCESSING
	$jsAddress1 = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}";
	$jsAddress2 = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	
	$openblocks = Array(0);
	$prevloadedblocks = array(0);
	if (isset($_COOKIE['openblocks-'.$cid]) && $_COOKIE['openblocks-'.$cid]!='') {$openblocks = explode(',',$_COOKIE['openblocks-'.$cid]); $firstload=false;} else {$firstload=true;}
	if (isset($_COOKIE['prevloadedblocks-'.$cid]) && $_COOKIE['prevloadedblocks-'.$cid]!='') {$prevloadedblocks = explode(',',$_COOKIE['prevloadedblocks-'.$cid]);}
	$plblist = implode(',',$prevloadedblocks);
	$oblist = implode(',',$openblocks);
	
	$curBreadcrumb = $breadcrumbbase;
	if (isset($backtrack) && count($backtrack)>0) {
		if (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==3) {
			$curBreadcrumb = '';
			$sendcrumb = '';
			$depth = substr_count($sessiondata['ltiitemid'][1],'-');
			for ($i=$depth-1;$i<count($backtrack);$i++) {
				if ($i>$depth-1) {
					$curBreadcrumb .= " &gt; ";
					$sendcrumb .= " &gt; ";
				}
				if ($i!=count($backtrack)-1) {
					$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder={$backtrack[$i][1]}\">";
				}
				$sendcrumb .= "<a href=\"course.php?cid=$cid&folder={$backtrack[$i][1]}\">".stripslashes($backtrack[$i][0]).'</a>';
				$curBreadcrumb .= stripslashes($backtrack[$i][0]);
				if ($i!=count($backtrack)-1) {
					$curBreadcrumb .= "</a>";
				}
			}
			$curname = $backtrack[count($backtrack)-1][0];
			if (count($backtrack)>$depth) {
				$backlink = "<span class=right><a href=\"course.php?cid=$cid&folder=".$backtrack[count($backtrack)-2][1]."\">" . _('Back') . "</a></span><br class=\"form\" />";
			}
			$_SESSION['backtrack'] = array($sendcrumb,$backtrack[count($backtrack)-1][1]);
			
		} else {
			$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder=0\">$coursename</a> ";
			for ($i=0;$i<count($backtrack);$i++) {
				$curBreadcrumb .= " &gt; ";
				if ($i!=count($backtrack)-1) {
					$curBreadcrumb .= "<a href=\"course.php?cid=$cid&folder={$backtrack[$i][1]}\">";
				}
				$curBreadcrumb .= stripslashes($backtrack[$i][0]);
				if ($i!=count($backtrack)-1) {
					$curBreadcrumb .= "</a>";
				}
			}
			$curname = $backtrack[count($backtrack)-1][0];
			if (count($backtrack)==1) {
				$backlink =  "<span class=right><a href=\"course.php?cid=$cid&folder=0\">" . _('Back') . "</a></span><br class=\"form\" />";
			} else {
				$backlink = "<span class=right><a href=\"course.php?cid=$cid&folder=".$backtrack[count($backtrack)-2][1]."\">" . _('Back') . "</a></span><br class=\"form\" />";
			}
		}
	} else {
		$curBreadcrumb .= $coursename;
		$curname = $coursename;
	}
	
	





}

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {

	if (isset($teacherid)) {
 ?>  
	<script type="text/javascript">
		function moveitem(from,blk) { 
			var to = document.getElementById(blk+'-'+from).value;
			
			if (to != from) {
				var toopen = '<?php echo $jsAddress1 ?>&block=' + blk + '&from=' + from + '&to=' + to;
				window.location = toopen;
			}
		}
		
		function additem(blk,tb) {
			var type = document.getElementById('addtype'+blk+'-'+tb).value;
			if (tb=='BB' || tb=='LB') { tb = 'b';}
			if (type!='') {
				var toopen = '<?php echo $jsAddress2 ?>/add' + type + '.php?block='+blk+'&tb='+tb+'&cid=<?php echo $_GET['cid'] ?>';
				window.location = toopen;
			}
		}
	</script>

<?php
	}	
?>
	<script type="text/javascript">
		var getbiaddr = 'getblockitems.php?cid=<?php echo $cid ?>&folder=';
		var oblist = '<?php echo $oblist ?>';
		var plblist = '<?php echo $plblist ?>';
		var cid = '<?php echo $cid ?>';
	</script> 
	
<?php
	//check for course layout
	if (isset($CFG['GEN']['courseinclude'])) {
		require($CFG['GEN']['courseinclude']);
		if ($firstload) {
			echo "<script>document.cookie = 'openblocks-$cid=' + oblist;\n";
			echo "document.cookie = 'loadedblocks-$cid=0';</script>\n";
		}
		require("../footer.php");
		exit;
	}
?>
	<div class=breadcrumb>
		<?php 
		if (isset($CFG['GEN']['logopad'])) {
			echo '<span class="padright hideinmobile" style="padding-right:'.$CFG['GEN']['logopad'].'">';
		} else {
			echo '<span class="padright hideinmobile">';
		}
		if (isset($guestid)) {
			echo '<span class="red">', _('Instructor Preview'), '</span> ';
		}
		if (!isset($usernameinheader)) {
			echo $userfullname;
		} else { echo '&nbsp;';}
		?>
		</span>
		<?php echo $curBreadcrumb ?>
		<div class=clear></div>
	</div>
<?
   }
   makeTopMenu();	

require("../footer.php");

function makeTopMenu() {
	global $teacherid;
	global $topbar;
	global $msgset;
	global $previewshift;
	global $imasroot;
	global $cid;
	global $newmsgs;
	global $quickview;
	global $newpostscnt;
	global $coursenewflag;
	global $CFG;
	global $useviewbuttons;
	
	if ($useviewbuttons && (isset($teacherid) || $previewshift>-1)) {
		echo '<div id="viewbuttoncont">View: ';
		echo "<a href=\"course.php?cid=$cid&quickview=off&teachview=1\" ";
		if ($previewshift==-1 && $quickview != 'on') {
			echo 'class="buttonactive buttoncurveleft"';
		} else {
			echo 'class="buttoninactive buttoncurveleft"';
		}
		echo '>', _('Instructor'), '</a>';
		echo "<a href=\"course.php?cid=$cid&quickview=off&stuview=0\" ";
		if ($previewshift>-1 && $quickview != 'on') {
			echo 'class="buttonactive"';
		} else {
			echo 'class="buttoninactive"';
		}
		echo '>', _('Student'), '</a>';
		echo "<a href=\"course.php?cid=$cid&quickview=on&teachview=1\" ";
		if ($previewshift==-1 && $quickview == 'on') {
			echo 'class="buttonactive buttoncurveright"';
		} else {
			echo 'class="buttoninactive buttoncurveright"';
		}
		echo '>', _('Quick Rearrange'), '</a>';
		echo '</div>';
		//echo '<br class="clear"/>';
			
		
	} else {
		$useviewbuttons = false;
	}
	
	if (isset($teacherid) && $quickview=='on') {
		if ($useviewbuttons) {
			echo '<br class="clear"/>';
		}
		echo '<div class="cpmid">';
		if (!$useviewbuttons) {
			echo _('Quick View.'), " <a href=\"course.php?cid=$cid&quickview=off\">", _('Back to regular view'), "</a>. ";
		} 
		if (isset($CFG['CPS']['miniicons'])) {
			echo _('Use icons to drag-and-drop order.'),' ',_('Click the icon next to a block to expand or collapse it. Click an item title to edit it in place.'), '  <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges()"/>';
		
		} else {
			echo _('Use colored boxes to drag-and-drop order.'),' ',_('Click the B next to a block to expand or collapse it. Click an item title to edit it in place.'), '  <input type="button" id="recchg" disabled="disabled" value="', _('Save Changes'), '" onclick="submitChanges()"/>';
		}
		 echo '<span id="submitnotice" style="color:red;"></span>';
		 echo '<div class="clear"></div>';
		 echo '</div>';
		
	}
	if (($coursenewflag&1)==1) {
		$gbnewflag = ' <span class="red">' . _('New') . '</span>';
	} else {
		$gbnewflag = '';
	}
	if (isset($teacherid) && count($topbar[1])>0 && $topbar[2]==0) {
		echo '<div class=breadcrumb>';
		if (in_array(0,$topbar[1]) && $msgset<4) { //messages
			echo "<a href=\"$imasroot/msgs/msglist.php?cid=$cid\">", _('Messages'), "</a>$newmsgs &nbsp; ";
		}
		if (in_array(6,$topbar[1])) { //Calendar
			echo "<a href=\"$imasroot/forums/forums.php?cid=$cid\">", _('Forums'), "</a>$newpostscnt &nbsp; ";
		}
		if (in_array(1,$topbar[1])) { //Stu view
			echo "<a href=\"course.php?cid=$cid&stuview=0\">", _('Student View'), "</a> &nbsp; ";
		}
		if (in_array(3,$topbar[1])) { //List stu
			echo "<a href=\"listusers.php?cid=$cid\">", _('Roster'), "</a> &nbsp; \n";
		}
		if (in_array(2,$topbar[1])) { //Gradebook
			echo "<a href=\"gradebook.php?cid=$cid\">", _('Gradebook'), "</a>$gbnewflag &nbsp; ";
		}
		if (in_array(7,$topbar[1])) { //List stu
			echo "<a href=\"managestugrps.php?cid=$cid\">", _('Groups'), "</a> &nbsp; \n";
		}
		if (in_array(4,$topbar[1])) { //Calendar
			echo "<a href=\"showcalendar.php?cid=$cid\">", _('Calendar'), "</a> &nbsp; \n";
		}
		if (in_array(5,$topbar[1])) { //Calendar
			echo "<a href=\"course.php?cid=$cid&quickview=on\">", _('Quick View'), "</a> &nbsp; \n";
		}
		
		if (in_array(9,$topbar[1])) { //Log out
			echo "<a href=\"../actions.php?action=logout\">", _('Log Out'), "</a>";
		}
		echo '<div class=clear></div></div>';
	} else if (!isset($teacherid) && ((count($topbar[0])>0 && $topbar[2]==0) || ($previewshift>-1 && !$useviewbuttons))) {
		echo '<div class=breadcrumb>';
		if ($topbar[2]==0) {
			if (in_array(0,$topbar[0]) && $msgset<4) { //messages
				echo "<a href=\"$imasroot/msgs/msglist.php?cid=$cid\">", _('Messages'), "</a>$newmsgs &nbsp; ";
			}
			if (in_array(3,$topbar[0])) { //forums
				echo "<a href=\"$imasroot/forums/forums.php?cid=$cid\">", _('Forums'), "</a>$newpostscnt &nbsp; ";
			}
			if (in_array(1,$topbar[0])) { //Gradebook
				echo "<a href=\"gradebook.php?cid=$cid\">", _('Show Gradebook'), "</a>$gbnewflag &nbsp; ";
			}
			if (in_array(2,$topbar[0])) { //Calendar
				echo "<a href=\"showcalendar.php?cid=$cid\">", _('Calendar'), "</a> &nbsp; \n";
			}
			if (in_array(9,$topbar[0])) { //Log out
				echo "<a href=\"../actions.php?action=logout\">", _('Log Out'), "</a>";
			}
			if ($previewshift>-1 && count($topbar[0])>0) { echo '<br />';}
		}
		if ($previewshift>-1 && !$useviewbuttons) {
			echo _('Showing student view. Show view:'), ' <select id="pshift" onchange="changeshift()">';
			echo '<option value="0" ';
			if ($previewshift==0) {echo "selected=1";}
			echo '>', _('Now'), '</option>';
			echo '<option value="3600" ';
			if ($previewshift==3600) {echo "selected=1";}
			echo '>', _('1 hour from now'), '</option>';
			echo '<option value="14400" ';
			if ($previewshift==14400) {echo "selected=1";}
			echo '>', _('4 hours from now'), '</option>';
			echo '<option value="86400" ';
			if ($previewshift==86400) {echo "selected=1";}
			echo '>', _('1 day from now'), '</option>';
			echo '<option value="604800" ';
			if ($previewshift==604800) {echo "selected=1";}
			echo '>', _('1 week from now'), '</option>';
			echo '</select>';
			echo " <a href=\"course.php?cid=$cid&teachview=1\">", _('Back to instructor view'), "</a>";
		}
		echo '<div class=clear></div></div>';
	}
}




?>

