<?php 
//IMathAS:  Course Recent Report
//(c) 2016 David Cooper, David Lippman

/*** master php includes *******/
require("../validate.php");



/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($guestid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = _("You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n");
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING


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
		$curBreadcrumb = $breadcrumbbase;
		$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">$coursename</a>   ";
		$curname = $coursename;

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
?>
<a href="coursereport.php?cid=<?php echo $cid ?>">Weekly Report</a>
   
<?




require("../footer.php");




?>

