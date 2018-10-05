<?php
//IMathAS:  Course item import
//JSON edition
//(c) 2017 David Lippman

//boost operation time
@set_time_limit(0);
ini_set("max_input_time", "900");
ini_set("max_execution_time", "900");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

/*** master php includes *******/
require("../init.php");
require_once("../includes/filehandler.php");
require("itemexportfields.php");
require("importitemsfuncs.php");
/*** pre-html data manipulation, including function code *******/


//set some page specific variables and counters
$cid = Sanitize::courseId($_GET['cid']);
$overwriteBody = 0;
$body = "";
$pagetitle = $installname . ' '._('Import Course Items');
$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; "._('Import Course Items')."</div>\n";

//data manipulation here

//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
 	$overwriteBody = 1;
	$body = "You need to access this page from a menu link";
} else if (isset($_POST['process'])) {
	//FORM HAS BEEN POSTED, STEP 3 DATA MANIPULATION - do import

	$uploaddir = __DIR__.'/import/';
	$uploadfile = $uploaddir . Sanitize::sanitizeFilenameAndCheckBlacklist($_POST['filename']);
	$data = json_decode(file_get_contents($uploadfile), true);

	$options = array();
	foreach (array('courseopt','gbsetup','offline','calitems','stickyposts') as $n) {
		if (isset($_POST['import'.$n])) {
			$options['import'.$n] = 1;
		}
	}
	if ($_POST['merge']!=1 && $_POST['merge']!=-1) {
		if ($myrights==100 && $_POST['merge']==2) {
			if (isset($_POST['importasteacher'])) {
				$_POST['merge'] = 1;
			}
		} else {
			$_POST['merge'] = -1;
		}

	}
	$options['update'] = $_POST['merge'];
	$options['userights'] = $_POST['userights'];
	if (isset($_POST['reuseqrights'])) {
		$options['userights'] = -1;
	}
	if ($_POST['libs']=='') {
		$options['importlib'] = 0;
	} else {
		$libs = explode(',', $_POST['libs']);
		$options['importlib'] = $libs[0];
	}
	if (isset($adminasteacher) && $adminasteacher && isset($_POST['importasteacher'])) {
		$options['usecourseowner'] = true;
	}


	$importer = new ImportItemClass();
	$res = $importer->importdata($data, $cid, $_POST['checked'], $options);

	$overwriteBody = 1;
	$body = '<h1>Import Results</h1><p>';
	foreach ($res as $k=>$v) {
		$body .= Sanitize::encodeStringForDisplay($k.': '.$v).'<br/>';
	}
	$body .= '</p><p><a href="../course/course.php?cid='.$cid.'">Done</a><p>';

} elseif ($_FILES['userfile']['name']!='') {
	//STEP 2 DATA MANIPULATION - parse input file
	$page_fileErrorMsg = "";
	$uploaddir = __DIR__.'/import/';
	$uploadfile = $uploaddir . Sanitize::sanitizeFilenameAndCheckBlacklist($_FILES['userfile']['name']);
	if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
		$page_fileHiddenInput = '<input type=hidden name="filename" value="'.Sanitize::encodeStringForDisplay(basename($uploadfile)).'" />';
	} else {
		echo "<p>Error uploading file!</p>\n";
		echo Sanitize::encodeStringForDisplay($_FILES["userfile"]['error']);
		exit;
	}
	$data = json_decode(file_get_contents($uploadfile), true);
	if ($data===null || !isset($data['course'])) {
		$page_fileErrorMsg .=  "This does not appear to be a course items file.  It may be ";
		$page_fileErrorMsg .=  "a question or library export, or an older format course export.\n";
	} else {
		$ids = array();
		$types = array();
		$names = array();
		$parents = array();
		getsubinfo($data['course']['itemorder'],'0','');
		$hascourseopts = isset($data['course']['enrollkey']);
		$hasgbsetup = isset($data['gbscheme']);
		$hasoffline = isset($data['offline']);
		$hascalitems = isset($data['calitems']);
		$hasstickyposts = isset($data['stickyposts']);
	}
}
/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>

<script type="text/javascript">

var curlibs = '0';
function libselect() {
	//window.open('../course/libtree.php?libtree=popup&selectrights=1&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
	window.open('../course/libtree2.php?libtree=popup&selectrights=1&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	if (libs.charAt(0)=='0' && libs.indexOf(',')>-1) {
		libs = libs.substring(2);
	}
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	if (libn.indexOf('Unassigned')>-1 && libn.indexOf(',')>-1) {
		libn = libn.substring(11);
	}
	document.getElementById("libnames").innerHTML = libn;
}
function chkgrp(frm, arr, mark) {
	  var els = frm.getElementsByTagName("input");
	  for (var i = 0; i < els.length; i++) {
		  var el = els[i];
		  if (el.type=='checkbox' && (el.id.indexOf(arr+'.')==0 || el.id.indexOf(arr+'-')==0 || el.id==arr)) {
	     	       el.checked = mark;
		  }
	  }
	}
$(function() {
	$("#importasteacher").on("change", function() {
		$("#allowforceupdate").toggle(!$(this).is(":checked"));
	})
});
</script>
<?php
echo $curBreadcrumb;
echo '<div id="headerimportitems" class="pagetitle"><h1>'._('Import Course Items').'</h1></div>';
echo '<form id="qform" enctype="multipart/form-data" method=post action="importitems2.php?cid='.$cid.'">';
if ($_FILES['userfile']['name']=='' || strlen($page_fileErrorMsg)>1) {
	if (strlen($page_fileErrorMsg)>1) {
		echo '<p class="noticetext">'.$page_fileErrorMsg.'</p>';;
	}
?>
	<p>This page will allow you to import course items previously exported from
	this site or another site running this software.</p>

	<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
	<span class=form>Import file: </span>
	<span class=formright><input name="userfile" type="file" /></span><br class=form>
	<div class=submit><input type=submit value="Submit"></div>

<?php
} else {
	echo $page_fileHiddenInput;
	echo '<h2>'._('Course').': '.$data['course']['name'].'</h2>';

	if ($myrights==100) {
		echo '<p><input type="checkbox" name="importasteacher" id="importasteacher" checked /> Import as course owner (for ownership when updating or adding questions).</p>';
	}
?>

	<p>Some questions (possibly older or different versions) may already exist on the system.
	With these questions, do you want to:<br/>
		<label><input type=radio name=merge value="1" CHECKED>Update existing questions (if allowed)</label>
		<label><input type=radio name=merge value="-1">Keep existing questions</label>
		<?php if ($myrights==100) {
			echo '<span style="display:none" id="allowforceupdate"><label><input type=radio name=merge value="2">Force update</label></span>';
		}?>
	</p>
	<p>
		For Added Questions, Set Question Use Rights to
		<select name=userights>
			<option value="0">Private</option>
			<option value="2" SELECTED>Allow use, use as template, no modifications</option>
			<option value="3">Allow use by all and modifications by group</option>
			<option value="4">Allow use and modifications by all</option>
		</select>
		<br/><input type="checkbox" name="reuseqrights" checked /> Use rights in import, if available.
	</p>
	<p>
	Assign Added Questions to library:
	<span id="libnames">Unassigned</span>
	<input type=hidden name="libs" id="libs"  value="0">
	<input type=button value="Select Libraries" onClick="libselect()"><br>

	Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
<?php
	if (count($ids)>0) {
?>
		<table cellpadding=5 class=gb>
		<thead>
			<tr><th></th><th>Type</th><th>Title</th></tr>
		</thead>
		<tbody>
<?php
		$alt=0;
		for ($i = 0 ; $i<(count($ids)); $i++) {
			if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
			echo '<td>';
			if (strpos($types[$i],'Block')!==false) {
				echo "<input type=checkbox name='checked[]' value='".Sanitize::encodeStringForDisplay($ids[$i])."' id='{$parents[$i]}' checked=checked ";
				echo "onClick=\"chkgrp(this.form, '".Sanitize::encodeStringForJavascript($ids[$i])."', this.checked);\" ";
				echo '/>';
			} else {
				echo "<input type=checkbox name='checked[]' value='".Sanitize::encodeStringForDisplay($ids[$i])."' id='{$parents[$i]}.{$ids[$i]}' checked=checked ";
				echo '/>';
			}
?>
				</td>
				<td><?php echo Sanitize::encodeStringForDisplay($types[$i]); ?></td>
				<td><?php echo Sanitize::encodeStringForDisplay($names[$i]); ?></td>
			</tr>

<?php
		}
?>
		</tbody>
		</table>
<?php
		if ($hascourseopts || $hasgbsetup || $hasoffline || $hascalitems || $hasstickyposts) {
			echo '<fieldset><legend>Options</legend>';
			echo '<table><tbody>';
			if ($hascourseopts) {
				echo '<tr><td class="r">Import course settings? (will overwrite existing)</td>';
				echo '<td><input type=checkbox name="importcourseopt"  value="1" checked/></td></tr>';
			}
			if ($hasgbsetup) {
				echo '<tr><td class="r">Import gradebook scheme and categories? (will overwrite existing)</td>';
				echo '<td><input type=checkbox name="importgbsetup"  value="1" checked/></td></tr>';
			}
			if ($hasoffline) {
				echo '<tr><td class="r">Import offline grade items?</td>';
				echo '<td><input type=checkbox name="importoffline"  value="1" checked/></td></tr>';
			}
			if ($hascalitems) {
				echo '<tr><td class="r">Import calendar items?</td>';
				echo '<td><input type=checkbox name="importcalitems"  value="1" checked/></td></tr>';
			}
			if ($hasstickyposts) {
				echo '<tr><td class="r">Import "display at top" instructor forum posts?</td>';
				echo '<td><input type=checkbox name="importstickyposts"  value="1" checked/></td></tr>';
			}
			echo '</tbody></table></fieldset>';
		}
		echo '<p><input type=submit name="process" value="Import Items"></p>';

	}
	echo "</form>\n";
}

}
require("../footer.php");
