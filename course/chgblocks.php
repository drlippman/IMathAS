<?php
//IMathAS:  Mass change blocks of items on course page
//(c) 2014 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

//buildExistBlocksArray constructs $existblocks for use in generating
//the existing block select list on the html form
function buildExistBlocksArray($items,$parent) {
	global $existblocks;
	global $existblockids;
	global $existBlocksVals;
	global $existBlocksLabels;

	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			$existblocks[$parent.'-'.($k+1)] = $item['name'];
			$existblockids[$parent.'-'.($k+1)] = $item['id'];
			if (count($item['items'])>0) {
				buildExistBlocksArray($item['items'],$parent.'-'.($k+1));
			}
		}
	}

	$i=0;
	foreach ($existblocks as $k=>$name) {
		$existBlocksVals[$i]=$k;
		//DB $existBlocksLabels[$i]=stripslashes($name);
		$existBlocksLabels[$i]=$name;
		$i++;
	}
}

function updateBlocksArray(&$items,$tochg,$sets) {
	foreach ($items as $n=>$item) {
		if (is_array($item)) {
			if (in_array($item['id'], $tochg)) {
				foreach ($sets as $k=>$v) {
					$items[$n][$k] = $v;
				}
			}
			if (count($item['items'])>0) {
				updateBlocksArray($items[$n]['items'], $tochg, $sets);
			}
		}
	}
}


$overwriteBody = 0;
$body = "";

$cid = Sanitize::courseId($_GET['cid']);
$pagetitle = "Mass Change Block Settings";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=".$cid.'">'.Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Mass Change Block Settings";
//DB $query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB $items = unserialize(mysql_result($result,0,0));
$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$items = unserialize($stm->fetchColumn(0));

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_POST['checked'])) { //form posted, update the blocks
	$checked = array();
	foreach ($_POST['checked'] as $id) {
		$id = intval($id);
		if ($id != 0) {
			$checked[] = $id;
		}
	}

	$sets = array();
	if (isset($_POST['chgavail'])) {
		$sets['avail'] = intval($_POST['avail']);
	}
	if (isset($_POST['chgavailbeh'])) {
		$sets['SH'] = $_POST['showhide'] . $_POST['availbeh'];
	}
	if (isset($_POST['chggrouplimit'])) {
		$grouplimit = array();
		if ($_POST['grouplimit']!='none') {
			$grouplimit[] = $_POST['grouplimit'];
		}
		$sets['grouplimit'] = $grouplimit;
	}
	if (isset($_POST['chgcolors'])) {
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
		$sets['colors'] = $colors;
	}

	updateBlocksArray($items,$checked,$sets);

	//DB $itemorder = addslashes(serialize($items));
	$itemorder = serialize($items);
	//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid';";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
	$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
	header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());

	exit;

} else { //it is a teacher but the form has not been posted
	$existblocks = array();
	$existblockids = array();
	$existBlocksVals = array();
	$existBlocksLabels = array();
	buildExistBlocksArray($items,'0');

	$page_sectionlistval = array("none");
	$page_sectionlistlabel = array(_("No restriction"));
	//DB $query = "SELECT DISTINCT section FROM imas_students WHERE courseid='$cid' ORDER BY section";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE courseid=:courseid ORDER BY section");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$page_sectionlistval[] = 's-'.$row[0];
		$page_sectionlistlabel[] = 'Section '.$row[0];
	}

	$titlebg = "#DDDDFF";
	$titletxt = "#000000";
	$bi = "#EEEEFF";
	$usedef = 1;
	$fixedheight = 0;
	$grouplimit = array();

}

//anything in the placeinhead variable is inserted in the html doc between the HEAD tags
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
$(document).ready(init);
$(function() {
	$('.chgbox').change(function() {
			$(this).parents('tr').toggleClass('odd');
	});
})
</script>";
$placeinhead .= "<style type=\"text/css\">img {	behavior:	 url(\"$imasroot/javascript/pngbehavior.htc\");} table td {border-bottom: 1px solid #ccf;}</style>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/colorpicker.js\"></script>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";

/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>

<div class=breadcrumb>
	<?php echo $curBreadcrumb; ?>
</div>
<form id="qform" method="post" action="chgblocks.php?cid=<?php echo $cid;?>">
<h2><?php echo _('Blocks to Change');?></h2>
Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)"><?php echo _('All');?></a>
<a href="#" onclick="return chkAllNone('qform','checked[]',false)"><?php echo _('None');?></a>
<ul class="nomark">
<?php
foreach ($existblocks as $pos=>$name) {
	echo '<li><input type="checkbox" name="checked[]" value="' . Sanitize::encodeStringForDisplay($existblockids[$pos]) . '"/>';
	$n = substr_count($pos,"-")-1;
	for ($i=0;$i<$n;$i++) {
		echo '&nbsp;&nbsp;';
	}
	echo Sanitize::encodeStringForDisplay($name) . '</li>';
}
?>
</ul>
<table class="gb" id="opttable">
<thead>
<tr><th>Change?</th><th>Option</th><th>Setting</th></tr>
</thead>
<tbody>
	<tr>
		<td><input type="checkbox" name="chgavail" class="chgbox"/></td>
		<td class="r">Show:</td>
		<td>
			<input type=radio name="avail" value="0"/>Hide<br/>
			<input type=radio name="avail" value="1"/>Show by Dates<br/>
			<input type=radio name="avail" value="2" checked="checked"/>Show Always
		</td>
	</tr>
	<tr>
		<td><input type="checkbox" name="chgavailbeh" class="chgbox"/></td>
		<td class="r">When available:<br/>
		When not available:</td>
		<td>
			<select name="availbeh">
			<option value="O" selected="selected">Show Expanded</option>
			<option value="C">Show Collapsed</option>
			<option value="F">Show as Folder</option>
			<option value="T">Show as TreeReader</option>
			</select><br/>
			<select name="showhide">
			<option value="H" selected="selected">Hide from Students</option>
			<option value="S">Show Collapsed/as folder</option>
			</select>
		</td>
	</tr>
	<tr>
		<td><input type="checkbox" name="chggrouplimit" class="chgbox"/></td>
		<td class="r">Restrict access to students in section:</td>
		<td>
			<?php writeHtmlSelect('grouplimit',$page_sectionlistval,$page_sectionlistlabel,0); ?>
		</td>
	</tr>
	<tr>
		<td><input type="checkbox" name="chgcolors" class="chgbox"/></td>
		<td class="r">Block colors:</td>
		<td>
			<input type=radio name="colors" value="def" checked="checked"/>Use defaults<br/>
			<input type=radio name="colors" value="copy"/>Copy colors from block:

			<?php
			writeHtmlSelect("copycolors",$existBlocksVals,$existBlocksLabels);
			?>

			<br />&nbsp;<br/>
			<input type=radio name="colors" id="colorcustom" value="custom"/>Use custom:
			<table style="display: inline; border-collapse: collapse; margin-left: 15px;">
				<tr>
					<td id="ex1" style="border: 1px solid #000;background-color:#DDDDFF;color:#000000;">Sample Title Cell</td>
				</tr>
				<tr>
					<td id="ex2" style="border: 1px solid #000;background-color:#EEEEFF">&nbsp;sample content cell</td>
				</tr>
			</table>
			<br/>
			<table style=" margin-left: 30px;">
				<tr>
					<td>Title Background: </td>
					<td><input type=text id="titlebg" name="titlebg" value="#DDDDFF" />
					</td>
				</tr>
				<tr>
					<td>Title Text: </td>
					<td><input type=text id="titletxt" name="titletxt" value="#000000" />
					</td>
				</tr>
				<tr>
					<td>Items Background: </td>
					<td><input type=text id="bi" name="bi" value="#EEEEFF" />
					</td>
				</tr>
			</table>

		</td>
	</tr>
</tbody>
</table>
<div class=submit><input type=submit value="<?php echo _('Apply Changes')?>"></div>
</form>
<?php
}
require("../footer.php");
?>
