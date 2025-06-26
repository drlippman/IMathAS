<?php
//IMathAS:  Mass change forum settings
//(c) 2010 David Lippman

/*** master php includes *******/
require_once "../init.php";
require_once "../includes/htmlutil.php";

if (!isset($teacherid)) {
	echo "You need to log in as a teacher to access this page";
	exit;
}
$cid = Sanitize::courseId($_GET['cid']);

if (isset($_POST['checked'])) { //form submitted
	$checked = $_POST['checked'];
	require_once "../includes/parsedatetime.php";
	$checkedlist = implode(',', array_map('intval', $checked));
	$sets = array();
	$qarr = array();
	if (isset($_POST['chgavail'])) {
		$sets[] = "avail=:avail";
		$qarr[':avail'] = $_POST['avail'];
	}
	if (isset($_POST['chgreplyby'])) {
		if ($_POST['replyby']=="Always") {
			$replyby = 2000000000;
		} else if ($_POST['replyby']=="Never") {
			$replyby = 0;
		} else {
			$replyby = parsedatetime($_POST['replybydate'],$_POST['replybytime'],2000000000);
		}
		$sets[] = "replyby=:replyby";
		$qarr[':replyby'] = $replyby;
	}
	if (isset($_POST['chgreplyby'])) {
		if ($_POST['postby']=="Always") {
			$postby = 2000000000;
		} else if ($_POST['postby']=="Never") {
			$postby = 0;
		} else {
			$postby = parsedatetime($_POST['postbydate'],$_POST['postbytime'],2000000000);
		}
		$sets[] = "postby=:postby";
		$qarr[':postby'] = $postby;
	}
	if (isset($_POST['chgallowlate'])) {
		$allowlate = 0;
		if ($_POST['allowlate']>0) {
			$allowlate = $_POST['allowlate'] + 10*$_POST['allowlateon'];
			if (isset($_POST['latepassafterdue'])) {
				$allowlate += 100;
			}
		}
		$sets[] = "allowlate=:allowlate";
		$qarr[':allowlate'] = $allowlate;
	}
	if (isset($_POST['chgcaltag'])) {
		$sets[] = "caltag=:caltag";
		$qarr[':caltag'] = $_POST['caltagpost'].'--'.$_POST['caltagreply'];
	}
	$sops = array();
	if (isset($_POST['chgallowanon'])) {
		if ( isset($_POST['allowanon']) && $_POST['allowanon']==1) {
			//turn on 1's bit
			$sops[] = " | 1";
		} else {
			//turn off 1's bit
			$sops[] = " & ~1";
		}
	}
	if (isset($_POST['chgallowmod'])) {
		if (isset($_POST['allowmod']) && $_POST['allowmod']==1) {
			//turn on 2's bit
			$sops[] = " | 2";
		} else {
			//turn off 2's bit
			$sops[] = " & ~2";
		}
	}
	if (isset($_POST['chgallowdel'])) {
		if (isset($_POST['allowdel']) && $_POST['allowdel']==1) {
			//turn on 4's bit
			$sops[] = " | 4";
		} else {
			//turn off 4's bit
			$sops[] = " & ~4";
		}
	}
	if (isset($_POST['chgallowlikes'])) {
		if (isset($_POST['allowlikes']) && $_POST['allowlikes']==1) {
			//turn on 8's bit
			$sops[] = " | 8";
		} else {
			//turn off 8's bit
			$sops[] = " & ~8";
		}
	}
	if (isset($_POST['chgviewafterpost'])) {
		if (isset($_POST['viewafterpost']) && $_POST['viewafterpost']==1) {
			//turn on 8's bit
			$sops[] = " | 16";
		} else {
			//turn off 8's bit
			$sops[] = " & ~16";
		}
	}
	if (count($sops)>0) {
		$out = "settings";
		foreach ($sops as $op) {
			$out = "($out $op)";
		}
		$sets[] = "settings=$out";  //safe, calculation
	}
	if (isset($_POST['chgdefdisplay'])) {
		$sets[] = "defdisplay=:defdisplay";
		$qarr[':defdisplay'] = $_POST['defdisplay'];
	}
	if (isset($_POST['chgsortby'])) {
		$sets[] = "sortby=:sortby";
		$qarr[':sortby'] = $_POST['sortby'];
	}
	if (isset($_POST['chgcntingb'])) {
		if (is_numeric($_POST['points']) && $_POST['points'] == 0) {
			$_POST['cntingb'] = 0;
		} else if ($_POST['cntingb'] == 0) {
			$_POST['points'] = 0;
		} else if ($_POST['cntingb'] == 4) {
			$_POST['cntingb'] = 0;
		}
		$sets[] = "cntingb=:cntingb";
		$qarr[':cntingb'] = $_POST['cntingb'];
		if (is_numeric($_POST['points'])) {
			$sets[] = "points=:points";
			$qarr[':points'] = $_POST['points'];
		}
	}
	if (isset($_POST['chgautoscore'])) {
		$autopostpts = Sanitize::onlyInt($_POST['autopostpts']);
		$autopostn = Sanitize::onlyInt($_POST['autopostn']);
		$autoreplypts = Sanitize::onlyInt($_POST['autoreplypts']);
		$autoreplyn = Sanitize::onlyInt($_POST['autoreplyn']);
		if (($autopostpts>0 && $autopostn>0) ||
			($autoreplypts>0 && $autoreplyn>0)) {
			$autoscore = "$autopostpts,$autopostn,$autoreplypts,$autoreplyn";
		} else {
			$autoscore = '';
		}
		$sets[] = "autoscore=:autoscore";
		$qarr[':autoscore'] = $autoscore;
	}
	if (isset($_POST['chggbcat'])) {
		$sets[] = "gbcategory=:gbcategory";
		$qarr[':gbcategory'] = $_POST['gbcat'];
	}
	if (isset($_POST['chgforumtype'])) {
		$sets[] = "forumtype=:forumtype";
		$qarr[':forumtype'] = $_POST['forumtype'];
	}
	if (isset($_POST['chgtaglist'])) {
		if (isset($_POST['usetags'])) {
			$taglist = trim($_POST['taglist']);
		} else {
			$taglist = '';
		}
		$sets[] = "taglist=:taglist";
		$qarr[':taglist'] = $taglist;
	}
	if (isset($_POST['chgpostinstr'])) {
		$sets[] = "postinstr=:postinstr";
		$qarr[':postinstr'] = Sanitize::incomingHtml(Sanitize::trimEmptyPara($_POST['postinstr']));
	}
	if (isset($_POST['chgreplyinstr'])) {
		$sets[] = "replyinstr=:replyinstr";
		$qarr[':replyinstr'] = Sanitize::incomingHtml(Sanitize::trimEmptyPara($_POST['replyinstr']));
	}
	if (count($sets)>0 & count($checked)>0) {
		$setslist = implode(',',$sets);
		$stm = $DBH->prepare("UPDATE imas_forums SET $setslist WHERE id IN ($checkedlist);");
		$stm->execute($qarr);
	}
	if (isset($_POST['chgsubscribe'])) {

		if (isset($_POST['subscribe'])) {
			//add any subscriptions we don't already have
			$stm = $DBH->prepare("SELECT forumid FROM imas_forum_subscriptions WHERE forumid IN ($checkedlist) AND userid=:userid");
			$stm->execute(array(':userid'=>$userid));
			$hassubscribe = array();
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$hassubscribe[] = $row[0];
			}

			$toadd = array_diff($_POST['checked'],$hassubscribe);
			foreach ($toadd as $fid) {
				$fid = intval($fid);
				if ($fid>0) {
					$stm = $DBH->prepare("INSERT INTO imas_forum_subscriptions (forumid,userid) VALUES (:forumid, :userid)");
					$stm->execute(array(':forumid'=>$fid, ':userid'=>$userid));
				}
			}
		} else {
			//remove any existing subscriptions
			$stm = $DBH->prepare("DELETE FROM imas_forum_subscriptions WHERE forumid IN ($checkedlist) AND userid=:userid");
			$stm->execute(array(':userid'=>$userid));

		}

	}
	$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
	header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid$btf&r=" . Sanitize::randomQueryStringParam());
	exit;
}

//prep for output
$forumitems = array();
$stm = $DBH->prepare("SELECT id,name FROM imas_forums WHERE courseid=:courseid ORDER BY name");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$forumitems[$row[0]] = $row[1];
}

$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
$page_gbcatSelect = array('val'=>[], 'label'=>[]);
$i=0;
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$page_gbcatSelect['val'][$i] = $row[0];
	$page_gbcatSelect['label'][$i] = $row[1];
	$i++;
}


$hr = floor($coursedeftime/60)%12;
$min = $coursedeftime%60;
$am = ($coursedeftime<12*60)?'am':'pm';
$deftime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;


$replybydate = tzdate("m/d/Y",time()+7*24*60*60);
$replybytime = $deftime; //tzdate("g:i a",time()+7*24*60*60);
$postbydate = tzdate("m/d/Y",time()+7*24*60*60);
$postbytime = $deftime; //tzdate("g:i a",time()+7*24*60*60);

$page_allowlateSelect = array();
$page_allowlateSelect['val'][0] = 0;
$page_allowlateSelect['label'][0] = "None";
$page_allowlateSelect['val'][1] = 1;
$page_allowlateSelect['label'][1] = "Unlimited";
for ($k=1;$k<9;$k++) {
	$page_allowlateSelect['val'][] = $k+1;
	$page_allowlateSelect['label'][] = "Up to $k";
}
$page_allowlateonSelect = array();
$page_allowlateonSelect['val'][0] = 0;
$page_allowlateonSelect['label'][0] = "Posts and Replies (1 LatePass for both)";
//doesn't work yet
//$page_allowlateonSelect['val'][1] = 1;
//$page_allowlateonSelect['label'][1] = "Posts or Replies (1 LatePass each)";
$page_allowlateonSelect['val'][1] = 2;
$page_allowlateonSelect['label'][1] = "Posts only";
$page_allowlateonSelect['val'][2] = 3;
$page_allowlateonSelect['label'][2] = "Replies only";

//HTML output
$useeditor = "postinstr,replyinstr";
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";
$placeinhead .= '<style type="text/css">
table td {
	border-bottom: 1px solid #ccf;
}
</style>
<script type="text/javascript">
function valform() {
	if ($("#mainform input:checkbox[name=\'checked[]\']:checked").length == 0) {
		if (!confirm("No forums are selected to be changed. Cancel to go back and select some forums, or click OK to make no changes")) {
			return false;
		}
	}
	if ($(".chgbox:checked").length == 0) {
		if (!confirm("No settings have been selected to be changed. Use the checkboxes along the left to indicate that you want to change that setting. Click Cancel to go back and select some settings to change, or click OK to make no changes")) {
			return false;
		}
	}
	return true;
}
$(function() {
	$(".chgbox").change(function() {
			$(this).parents("tr").toggleClass("odd");
	});
})
</script>';

require_once "../header.php";

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
echo "&gt; Mass Change Forums</div>";
echo '<div id="headerchgforums" class="pagetitle"><h1>Mass Change Forums</h1></div>';

echo "<form id=\"mainform\" method=post action=\"chgforums.php?cid=$cid\" onsubmit=\"return valform();\">";


if (count($forumitems)==0) {
	echo '<p>No forums to change.</p>';
	require_once "../footer.php";
	exit;
}

?>
Check: <a href="#" onclick="return chkAllNone('mainform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('mainform','checked[]',false)">None</a>

<ul class=nomark>

<?php

foreach($forumitems as $id=>$name) {
	echo '<li><label><input type="checkbox" name="checked[]" value="'.$id.'" /> '.Sanitize::encodeStringForDisplay($name).'</label></li>';
}
?>
</ul>
<p>With selected, make changes below
<fieldset>
<legend>Forum Options</legend>
<table class=gb>
<caption class="sr-only">Settings</caption>
<thead>
<tr><th>Change?</th><th>Option</th><th>Setting</th></tr>
</thead>
<tbody>
<tr class="coptr">
	<td><input type="checkbox" name="chgavail" class="chgbox" aria-labelledby="lshow"/></td>
	<td class="r" id="lshow">Show:</td>
	<td role=radiogroup aria-labelledby="lshow">
	<label><input type=radio name="avail" value="0" />Hide</label><br/>
	<label><input type=radio name="avail" value="1" checked="checked"/>Show by Dates</label><br/>
	<label><input type=radio name="avail" value="2"/>Show Always</label>
	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgpostby" class="chgbox" aria-labelledby="lpostby"/></td>
	<td class="r" id="lpostby">Students can create new threads:</td>
	<td role=radiogroup aria-labelledby="lpostby">
	<label><input type=radio name="postby" value="Always" checked="checked"/>Always</label><br/>
	<label><input type=radio name="postby" value="Never" />Never</label><br/>
	<label><input type=radio name="postby" value="Date" />Before</label>:
	<input type=text size=10 name="postbydate" value="<?php echo $postbydate;?>" aria-label="create threads by date">
	<a href="#" onClick="displayDatePicker('postbydate', this); return false">
	<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></A>
	at <input type=text size=10 name=postbytime value="<?php echo $postbytime;?>" aria-label="create threads by time">

	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgreplyby" class="chgbox" aria-labelledby="lreplyby"/></td>
	<td class="r" id="lreplyby">Students can reply to posts:</td>
	<td role=radiogroup aria-labelledby="lreplyby">
	<label><input type=radio name="replyby" value="Always" checked="checked"/>Always</label><br/>
	<label><input type=radio name="replyby" value="Never" />Never</label><br/>
	<label><input type=radio name="replyby" value="Date" />Before</label>:
	<input type=text size=10 name="replybydate" value="<?php echo $replybydate;?>" aria-label="reply by date">
	<a href="#" onClick="displayDatePicker('replybydate', this); return false">
	<img src="<?php echo $staticroot;?>/img/cal.gif" alt="Calendar"/></A>
	at <input type=text size=10 name=replybytime value="<?php echo $replybytime;?>" aria-label="reply by time">

	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgallowlate" class="chgbox" aria-labelledby="lallowlate"/></td>
	<td class="r" id="lallowlate">Allow use of LatePasses?:</td>
	<td>
		<?php
		writeHtmlSelect("allowlate",$page_allowlateSelect['val'],$page_allowlateSelect['label'],0,null,null,'aria-label="'._('number of latepasses').'"');
		echo ' on ';
		writeHtmlSelect("allowlateon",$page_allowlateonSelect['val'],$page_allowlateonSelect['label'],0,null,null,'aria-label="'._('latepasses apply to').'"');
		?>
		<br/><label><input type="checkbox" name="latepassafterdue"> Allow LatePasses after due date, within 1 LatePass period</label>
	</td>

</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgcaltag" class="chgbox" aria-labelledby="lcaltag"/></td>
	<td class="r" id="lcaltag">Calendar icon:</td>
	<td>
	<label>New Threads: <input name="caltagpost" type=text size=8 value="FP"/></label>,
	<label>Replies: <input name="caltagreply" type=text size=8 value="FR"/></label>
	</td>
</tr>


<tr class="coptr">
	<td><input type="checkbox" name="chgallowanon" class="chgbox" aria-labelledby="lallowanon"/></td>
	<td class="r" id="lallowanon">Anonymous posts: </td>
	<td>
		<label><input type=checkbox name="allowanon" value="1"/> Allow anonymous posts</label>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgallowmod" class="chgbox" aria-labelledby="lallowmod"/></td>
	<td class="r" id="lallowmod">Modify posts: </td>
	<td>
		<label><input type=checkbox name="allowmod" value="1" checked="checked"/>Allow students to modify posts</label>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgallowdel" class="chgbox" aria-labelledby="lallowdel"/></td>
	<td class="r" id="lallowdel">Delete posts: </td>
	<td>
		<label><input type=checkbox name="allowdel" value="1"/> Allow students to delete own posts (if no replies)</label>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgallowlikes" class="chgbox" aria-labelledby="llikes"/></td>
	<td class="r" id="llikes">"Liking" posts: </td>
	<td>
		<label><input type=checkbox name="allowlikes" value="1"/>Turn on "liking" posts</label>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgviewafterpost" class="chgbox" aria-labelledby="lviewafterpost"/></td>
	<td class="r" id="lviewafterpost">Viewing before posting:</td>
	<td>
		<label><input type=checkbox name="viewafterpost" value="1"/> Prevent students from viewing posts until they have created a thread.</label><br/><i>You will likely also want to disable modifying posts</i>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgsubscribe" class="chgbox" aria-labelledby="lsubscribe"/></td>
	<td class="r" id="lsubscribe">Email notification: </td>
	<td>
		<label><input type=checkbox name="subscribe" value="1"/>Get email notification of new posts</label>
	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgdefdisplay" class="chgbox" aria-labelledby="ldefdisp"/></td>
	<td class="r" id="ldefdisp">Default display: </td>
	<td>
	<select name="defdisplay" aria-labelledby="ldefdisp">
		<option value="0" selected="selected">Expanded</option>
		<option value="1">Collapsed</option>
		<option value="2">Condensed</option>
	</select>
	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgsortby" class="chgbox" aria-labelledby="lsortby"/></td>
	<td class="r" id="lsortby">Sort threads by: </td>
	<td role=radiogroup aria-labelledby="lsortby">
	<label><input type="radio" name="sortby" value="0" checked="checked"/> Thread start date</label><br/>
	<label><input type="radio" name="sortby" value="1" /> Most recent reply date</label>
	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgcntingb" class="chgbox" aria-labelledby="lcnt"/></td>
	<td class="r" id="lcnt">Count: </td>
	<td>
		<span role=radiogroup aria-labelledby="lcnt">
		<label><input name="cntingb" value="0" checked="checked" type="radio"> No</label><br/>
		<label><input name="cntingb" value="1" type="radio"> Yes</label><br/>
		<label><input name="cntingb" value="4" type="radio"> Yes, but hide from students for now</label><br/>
		<label><input name="cntingb" value="2" type="radio"> Yes, as extra credit</label><br/>
		</span>
		<label>If yes, for: <input type=text size=4 name="points" value=""/> points (leave blank to not change)</label>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgautoscore" class="chgbox" aria-labelledby="lauto"/></td>
	<td class="r" id="lauto">Autoscoring:</td>
	<td>
	<label>Auto-award <input type="text" size="2" name="autopostpts" value="0"> points</label>
	<label>for the first <input type="text" size="2" name="autopostn" value="0"> posts<br/>
	<label>Auto-award <input type="text" size="2" name="autoreplypts" value="0"> points</label>
	<label>for the first <input type="text" size="2" name="autoreplyn" value="0"> replies</label>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chggbcat" class="chgbox" aria-labelledby="lgbcat"/></td>
	<td class="r" id="lgbcat">Gradebook category: </td>
	<td>
<?php
writeHtmlSelect ("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],null,"Default",0,'aria-labelledby="lgbcat"');
?>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgforumtype" class="chgbox" aria-labelledby="lftype"/></td>
	<td class="r" id="lftype">Forum Type: </td>
	<td role=radiogroup aria-labelledby="lftype">
		<label><input type=radio name="forumtype" value="0" checked="checked"/>Regular forum</label><br/>
		<label><input type=radio name="forumtype" value="1"/>File sharing forum</label>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgtaglist" class="chgbox" aria-labelledby="ltaglist"/></td>
	<td class="r" id="ltaglist">Categorize posts?: </td>
	<td>
		<label><input type=checkbox name="usetags" value="1"
		  onclick="document.getElementById('tagholder').style.display=this.checked?'':'none';" /> Enable Categorizing</label>
		 <span id="tagholder" style="display:none">
		   <br><label for=taglist>Enter in format: CategoryDescription:category,category,category</label><br/>
		   <textarea rows="2" cols="60" name="taglist" id="taglist"></textarea>
		 </span>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgpostinstr" class="chgbox" aria-labelledby="lpostinstr"/></td>
	<td class="r" id="lpostinstr">Posting Instructions: <em>Displays on Add New Thread</em></td>
	<td>
		<div class=editor>
		<textarea cols=60 rows=10 id="postinstr" name="postinstr" style="width: 100%" aria-labelledby="lpostinstr"></textarea>
		</div>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgreplyinstr" class="chgbox" aria-labelledby="lreplyinstr"/></td>
	<td class="r" id="lreplyinstr">Reply Instructions: <em>Displays on Add Reply</em></td>
	<td>
		<div class=editor>
		<textarea cols=60 rows=10 id="replyinstr" name="replyinstr" style="width: 100%" aria-labelledby="lreplyinstr"></textarea>
		</div>
	</td>
</tr>

</tbody>
</table>
</fieldset>
<div class="submit"><input type="submit" name="submit" value="<?php echo _('Apply Changes')?>" /></div>
</form>
<?php
require_once "../footer.php";
?>
