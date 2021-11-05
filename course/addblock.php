<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");


/*** pre-html data manipulation, including function code *******/

//buildExistBlocksArray constructs $existblocks for use in generating
//the existing block select list on the html form
function buildExistBlocksArray($items,$parent) {
	global $existblocks;
	global $existBlocksVals;
	global $existBlocksLabels;

	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			$existblocks[$parent.'-'.($k+1)] = $item['name'];
			if (count($item['items'])>0) {
				buildExistBlocksArray($item['items'],$parent.'-'.($k+1));
			}
		}
	}

	$i=0;
	foreach ($existblocks as $k=>$name) {
		$existBlocksVals[$i]=$k;
		$existBlocksLabels[$i]=$name;
		$i++;
	}
}

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Block Settings";
$cid = Sanitize::courseId($_GET['cid']);
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a>";
$curBreadcrumb .= (isset($_GET['id'])) ? "&gt; Modify Block\n" : "&gt; Add Block\n";

if (isset($_GET['id'])) {
	$formTitle = "<div id=\"headeraddblock\" class=\"pagetitle\"><h1>Modify Block <img src=\"$staticroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=blocks','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h1></div>\n";
} else {
	$formTitle = "<div id=\"headeraddblock\" class=\"pagetitle\"><h1>Add Block <img src=\"$staticroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=blocks','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h1></div>\n";
}
if (isset($_GET['tb'])) {
	$totb = $_GET['tb'];
} else {
	$totb = 'b';
}


/* page load loop, runs only one set of code based on how the page was loaded,
current options are (in order of code blocks below):
  - loaded by a NON-teacher
  - form posted to itself with new/modified data
  - teacher modifying existing block
  - teacher adding new block
***************/
if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!empty($_POST['title'])) { //form posted to itself with new/modified data, update the block
	$DBH->beginTransaction();
	require_once("../includes/parsedatetime.php");
	if ($_POST['avail']==1) {
		if ($_POST['sdatetype']=='0') {
			$startdate = 0;
		} else if ($_POST['sdatetype']=='now') {
			$startdate = time()-2;
		} else {
			$startdate = parsedatetime($_POST['sdate'],$_POST['stime'],0);
		}
		if ($_POST['edatetype']=='2000000000') {
			$enddate = 2000000000;
		} else {
			$enddate = parsedatetime($_POST['edate'],$_POST['etime'],2000000000);
		}
	} else {
		$startdate = 0;
		$enddate = 2000000000;
	}

	if (is_numeric($_POST['fixedheight'])) {
		$fixedheight = intval($_POST['fixedheight']);
	} else {
		$fixedheight = 0;
	}

	$grouplimit = array();
	if ($_POST['grouplimit']!='none') {
		$grouplimit[] = $_POST['grouplimit'];
	}
	//$_POST['title'] = str_replace(array(',','\\"','\\\'','~'),"",$_POST['title']);
	$stm = $DBH->prepare("SELECT itemorder,blockcnt FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	list ($itemlist, $blockcnt) = $stm->fetch(PDO::FETCH_NUM);
	$items = unserialize($itemlist);

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
	if (isset($_POST['public'])) {
		$public = 1;
	} else {
		$public = 0;
	}
	if (isset($_POST['innav'])) {
		$innav = 1;
	} else {
		$innav = 0;
	}

	$sub =& $items;
	if (count($blocktree)>1) {
		for ($i=1;$i<count($blocktree);$i++) {
			$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
		}
	}
	if (isset($existingid)) {  //already have id; update
		$sub[$existingid]['name'] = htmlentities($_POST['title']);
		$sub[$existingid]['startdate'] = $startdate;
		$sub[$existingid]['enddate'] = $enddate;
		$sub[$existingid]['avail'] = $_POST['avail'];
		$sub[$existingid]['SH'] = ($_POST['showhide'] ?? 'H') . 
			($_POST['availbeh'] ?? 'O') . 
			($_POST['contentbehavior'] ?? '0');
		$sub[$existingid]['colors'] = $colors;
		$sub[$existingid]['public'] = $public;
		$sub[$existingid]['innav'] = $innav;
		$sub[$existingid]['fixedheight'] = $fixedheight;
		$sub[$existingid]['grouplimit'] = $grouplimit;
	} else { //add new
		$blockitems = array();
		$blockitems['name'] = htmlentities($_POST['title']);
		$blockitems['id'] = $blockcnt;
		$blockitems['startdate'] = $startdate;
		$blockitems['enddate'] = $enddate;
		$blockitems['avail'] = $_POST['avail'];
		$blockitems['SH'] = ($_POST['showhide'] ?? 'H') . 
			($_POST['availbeh'] ?? 'O') . 
			($_POST['contentbehavior'] ?? '0');
		$blockitems['colors'] = $colors;
		$blockitems['public'] = $public;
		$blockitems['innav'] = $innav;
		$blockitems['fixedheight'] = $fixedheight;
		$blockitems['grouplimit'] = $grouplimit;
		$blockitems['items'] = array();
		if ($totb=='b') {
			array_push($sub,$blockitems);
		} else if ($totb=='t') {
			array_unshift($sub,$blockitems);
		}

		$blockcnt++;
	}
	$itemorder = serialize($items);

	$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt WHERE id=:id");
	$stm->execute(array(':itemorder'=>$itemorder, ':blockcnt'=>$blockcnt, ':id'=>$cid));
	$DBH->commit();
	$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
	header(sprintf('Location: %s/course/course.php?cid=%s&r=' .Sanitize::randomQueryStringParam() , $GLOBALS['basesiteurl'], $cid.$btf));

	exit;
} else { //it is a teacher but the form has not been posted

	if (isset($_GET['id'])) { //teacher modifying existing block, load form with block data
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));

		$blocktree = explode('-',$_GET['id']);
		$existingid = array_pop($blocktree) - 1; //-1 adjust for 1-index
		$blockitems = $items;
		if (count($blocktree)>1) {
			for ($i=1;$i<count($blocktree);$i++) {
				$blockitems = $blockitems[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		}
		$title = $blockitems[$existingid]['name'];
		$title = str_replace('"','&quot;',$title);
		$startdate = $blockitems[$existingid]['startdate'];
		$enddate = $blockitems[$existingid]['enddate'];
		if (isset($blockitems[$existingid]['avail'])) { //backwards compat
			$avail = $blockitems[$existingid]['avail'];
		} else {
			$avail = 1;
		}
		if (isset($blockitems[$existingid]['public'])) { //backwards compat
			$public = $blockitems[$existingid]['public'];
		} else {
			$public = 0;
		}
		if (isset($blockitems[$existingid]['innav'])) { //backwards compat
			$innav = $blockitems[$existingid]['innav'];
		} else {
			$innav = 0;
		}
		$showhide = $blockitems[$existingid]['SH'][0];
		if (strlen($blockitems[$existingid]['SH'])>1) {
			$availbeh = $blockitems[$existingid]['SH'][1];
		} else {
			$availbeh = 'O';
		}
		if (strlen($blockitems[$existingid]['SH'])>2) {
			$contentbehavior = $blockitems[$existingid]['SH'][2];
		} else {
			$contentbehavior = 0;
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
		$fixedheight = $blockitems[$existingid]['fixedheight'];
		$grouplimit = $blockitems[$existingid]['grouplimit'];
		$savetitle = _("Save Changes");


	} else { //teacher adding new block, load form with default data
		//set defaults
		$title = '';
		$startdate = time() + 60*60;
		$enddate = time() + 7*24*60*60;
		$availbeh = 'O';
		$showhide = 'H';
		$contentbehavior = 0;
		$avail = 1;
		$public = 0;
		$titlebg = "#DDDDFF";
		$titletxt = "#000000";
		$bi = "#EEEEFF";
		$usedef = 1;
        $fixedheight = 0;
        $innav = 0;
		$grouplimit = array();
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));
		$savetitle = _("Create Block");
	}

	//set some default data for use with either the add or modify form
	$existblocks = array();
	$existBlocksVals = array();
	$existBlocksLabels = array();
	buildExistBlocksArray($items,'0');

	$page_sectionlistval = array("none");
	$page_sectionlistlabel = array("No restriction");
	$stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE courseid=:courseid ORDER BY section");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$page_sectionlistval[] = 's-'.$row[0];
		$page_sectionlistlabel[] = 'Section '.$row[0];
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
		$stime = $defstime; // tzdate("g:i a",time());
	}
	if ($enddate!=2000000000) {
		$edate = tzdate("m/d/Y",$enddate);
		$etime = tzdate("g:i a",$enddate);
	} else {
		$edate = tzdate("m/d/Y",time()+7*24*60*60);
		$etime = $deftime; //tzdate("g:i a",time()+7*24*60*60);
	}

	if (!isset($_GET['id'])) {
		$stime = $defstime;
		$etime = $deftime;
	}
}

//anything in the placeinhead variable is inserted in the html doc between the HEAD tags
$placeinhead = "<script type=\"text/javascript\">
function updateSlides(init) {
	if (typeof init != 'boolean') {
		init = false;
	}
	var availval = $('input[name=avail]:checked').val();
	/*if (availval==0) {
		$('.availbh').slideUp(init?0:100);
	} else {
		$('.availbh').slideDown(init?0:100);
	}*/
	if (availval==1) {
		$('.navail,#datediv').slideDown(init?0:100);
	} else {
		$('.navail,#datediv').slideUp(init?0:100);
	}
	var colorval = $('input[name=colors]:checked').val();
	if (colorval=='custom') {
		$('.coloropts').slideDown(init?0:100);
	} else {
		$('.coloropts').slideUp(init?0:100);
	}
	var showstyle = $('input[name=availbeh]:checked').val();
	if (showstyle=='O' || showstyle=='C') {
		$('.expando').slideDown(init?0:100);
	} else {
		$('.expando').slideUp(init?0:100);
	}
}
$(function() {
	var inp1 = document.getElementById(\"titlebg\");
	attachColorPicker(inp1);
	var inp2 = document.getElementById(\"titletxt\");
	attachColorPicker(inp2);
	var inp3 = document.getElementById(\"bi\");
	attachColorPicker(inp3);
	updateSlides(true);
	$('input[name=colors]').on('click', updateSlides);
	$('input[name=avail]').on('click', updateSlides);
	$('input[name=availbeh]').on('click', updateSlides);
});
var imgBase = '$staticroot/javascript/cpimages';
</script>";
$placeinhead .= "<style type=\"text/css\">img {	behavior:	 url(\"$imasroot/javascript/pngbehavior.htc\");}</style>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/colorpicker.js\"></script>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";

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

<div class=breadcrumb>
	<?php echo $curBreadcrumb; ?>
</div>

<?php echo $formTitle; ?>

<form method=post action="addblock.php?cid=<?php echo $cid; if (isset($_GET['id'])) {echo "&id=".Sanitize::encodeUrlParam($_GET['id']);} if (isset($_GET['block'])) {echo "&block=".Sanitize::encodeUrlParam($_GET['block']);} ?>&folder=<?php echo Sanitize::encodeUrlParam($_GET['folder'] ?? 0); ?>&tb=<?php echo Sanitize::encodeUrlParam($totb); ?>">
	<span class=form>Title: </span>
	<span class=formright><input type=text size=60 name=title value="<?php echo str_replace('"','&quot;',$title);?>" required></span>
	<BR class=form>
	<span class=form>Show:</span>
	<span class=formright>
		<input type=radio name="avail" value="0" <?php writeHtmlChecked($avail,0);?>/>Hide <span class=small>(this will hide all items in the block from the gradebook)</span><br/>
		<input type=radio name="avail" value="1" <?php writeHtmlChecked($avail,1);?>/>Show by Dates<br/>
		<input type=radio name="avail" value="2" <?php writeHtmlChecked($avail,2);?>/>Show Always<br/>
	</span><br class="form"/>

	<div id="datediv">
	<span class=form>Available After:</span>
	<span class=formright>
	<input type=radio name="sdatetype" value="0" <?php  writeHtmlChecked($startdate,0) ?>/>
	 Always until end date<br/>
	<input type=radio name="sdatetype" value="now"/> Now<br/>
	<input type=radio name="sdatetype" value="sdate" <?php  writeHtmlChecked($startdate,0,1) ?>/>
	<input type=text size=10 name="sdate" value="<?php echo $sdate;?>">
	<a href="#" onClick="displayDatePicker('sdate', this); return false">
	<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
	at <input type=text size=10 name=stime value="<?php echo $stime;?>"></span>
	<BR class=form>

	<span class=form>Available Until:</span><span class=formright>
	<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000') ?>/>
	 Always after start date<br/>
	<input type=radio name="edatetype" value="edate"  <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
	<input type=text size=10 name=edate value="<?php echo $edate;?>">
	<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
	<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></a>
	at <input type=text size=10 name=etime value="<?php echo $etime;?>"></span>
	<BR class=form>
	</div>
	<div class="availbh">
	<span class=form>When available:</span>
	<span class=formright>
	<input type=radio name=availbeh value="O" <?php writeHtmlChecked($availbeh,'O')?> />Show Expanded<br/>
	<input type=radio name=availbeh value="C" <?php writeHtmlChecked($availbeh,'C')?> />Show Collapsed<br/>
	<input type=radio name=availbeh value="F" <?php writeHtmlChecked($availbeh,'F')?> />Show as Folder<br/>
	<input type=radio name=availbeh value="T" <?php writeHtmlChecked($availbeh,'T')?> />Show as TreeReader
	</span><br class=form />
	</div>
	<div class="navail">
	<span class=form>When not available:</span>
	<span class=formright>
	<input type=radio name=showhide value="H" <?php writeHtmlChecked($showhide,'H') ?> />Hide from Students<br/>
	<input type=radio name=showhide value="S" <?php writeHtmlChecked($showhide,'S') ?> />Show Collapsed/as folder
	</span><br class=form />
	</div>
	<div class="availbh">
	<span class=form>For assignments within this block, when they are not available:</span>
	<span class=formright>
	<?php
		writeHtmlSelect('contentbehavior',array(0,1,2,3),array(
			_('Hide'),
			_('Show greyed out before start date, hide after end date'),
			_('Hide before start date, show greyed out after end date'),
			_('Show greyed out before and after'),
		), $contentbehavior);
	?>
	</span><br class=form />
	<div class="expando">
	<span class="form">If expanded, limit height to:</span>
	<span class="formright">
	<input type="text" name="fixedheight" size="4" value="<?php if ($fixedheight>0) {echo Sanitize::onlyInt($fixedheight);};?>" />pixels (blank for no limit)
	</span><br class="form" />
	</div>
	<span class="form">Restrict access to students in section:</span>
	<span class="formright">
	<?php writeHtmlSelect('grouplimit',$page_sectionlistval,$page_sectionlistlabel,$grouplimit[0] ?? 'none'); ?>
	</span><br class="form" />

	<span class=form>Quick Links:</span>
	<span class=formright>
	<input type=checkbox name=innav value="1" <?php writeHtmlChecked($innav,'1') ?> /> List block in student left navigation
	</span><br class=form />

	<span class=form>Public:</span>
	<span class=formright>
	<input type=checkbox name=public value="1" <?php writeHtmlChecked($public,'1') ?> /> Make items publicly accessible<sup>*</sup>
	</span><br class=form />


	<span class=form>Block colors:</span>
	<span class=formright>
	<input type=radio name="colors" value="def" <?php  writeHtmlChecked($usedef,1) ?> />Use defaults<br/>
	<input type=radio name="colors" value="copy" <?php writeHtmlChecked($usedef,2) ?> />Copy colors from block:

	<?php
	writeHtmlSelect("copycolors",$existBlocksVals,$existBlocksLabels);
	?>

	<br />&nbsp;<br/>
	<input type=radio name="colors" id="colorcustom" value="custom" <?php if ($usedef==0) {echo "CHECKED";}?> />Use custom
	<table class="coloropts" style="display: inline; border-collapse: collapse; margin-left: 15px;">
		<tr>
			<td id="ex1" style="border: 1px solid #000;background-color:
			<?php echo $titlebg;?>;color:<?php echo $titletxt;?>;">
			Sample Title Cell</td>
		</tr>
		<tr class="expando">
			<td id="ex2" style="border: 1px solid #000;background-color:
			<?php echo $bi;?>;">&nbsp;sample content cell</td>
		</tr>
	</table>
	<br class="coloropts"/>
	<table class="coloropts" style=" margin-left: 30px;">
		<tr>
			<td>Title Background: </td>
			<td><input type=text id="titlebg" name="titlebg" value="<?php echo $titlebg;?>" />
			</td>
		</tr>
		<tr>
			<td>Title Text: </td>
			<td><input type=text id="titletxt" name="titletxt" value="<?php echo $titletxt;?>" />
			</td>
		</tr>
		<tr class="expando">
			<td>Items Background: </td>
			<td><input type=text id="bi" name="bi" value="<?php echo $bi;?>" />
			</td>
		</tr>
	</table>
	</span>
	<br class="form"/>
	</div>
	<div class=submit><input type=submit value="<?php echo $savetitle?>"></div>
</form>
<p class="small"><sup>*</sup>If a parent block is set to be publicly accessible, this block will automatically be publicly accessible, regardless of your selection here.<br/>
Items from publicly accessible blocks can viewed without logging in at <?php echo $GLOBALS['basesiteurl'] ?>/course/public.php?cid=<?php echo $cid; ?>.<br/>
Note that assessments are never publicly accessible.</p>



<?php
if (isset($blockitems)) {
	echo '<input type="hidden" name="blockid" value="'.Sanitize::encodeStringForDisplay($blockitems[$existingid]['id']).'"/>';
	echo '<p class="small">Block ID: '.Sanitize::encodeStringForDisplay($blockitems[$existingid]['id']).'</p>';
}
echo '<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>';
}

	require("../footer.php");

/**** end html code ******/
//nothing after the end of html for this page
/***** cleanup code ******/
//no cleanup code for this page
?>
