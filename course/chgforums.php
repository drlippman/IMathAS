<?php
//IMathAS:  Mass change forum settings
//(c) 2010 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");

if (!isset($teacherid)) {
	echo "You need to log in as a teacher to access this page";
	exit;
}
$cid = Sanitize::courseId($_GET['cid']);

if (isset($_POST['checked'])) { //form submitted
	$checked = $_POST['checked'];
	require_once("../includes/parsedatetime.php");
	//DB $checkedlist = "'".implode("','",$checked)."'";
	$checkedlist = implode(',', array_map('intval', $checked));
	$sets = array();
	$qarr = array();
	if (isset($_POST['chgavail'])) {
		//DB $sets[] = 'avail='.intval($_POST['avail']);
		$sets[] = "avail=:avail";
		$qarr[':avail'] = $_POST['avail'];
	}
	if (isset($_POST['chgreplyby'])) {
		if ($_POST['replyby']=="Always") {
			$replyby = 2000000000;
		} else if ($_POST['replyby']=="Never") {
			$replyby = 0;
		} else {
			$replyby = parsedatetime($_POST['replybydate'],$_POST['replybytime']);
		}
		//DB $sets[] = "replyby='$replyby'";
		$sets[] = "replyby=:replyby";
		$qarr[':replyby'] = $replyby;
	}
	if (isset($_POST['chgreplyby'])) {
		if ($_POST['postby']=="Always") {
			$postby = 2000000000;
		} else if ($_POST['postby']=="Never") {
			$postby = 0;
		} else {
			$postby = parsedatetime($_POST['postbydate'],$_POST['postbytime']);
		}
		//DB $sets[] = "postby='$postby'";
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
		//DB $sets[] = "allowlate=$allowlate";
		$sets[] = "allowlate=:allowlate";
		$qarr[':allowlate'] = $allowlate;
	}
	if (isset($_POST['chgcaltag'])) {
		//DB $sets[] = "caltag='".$_POST['caltagpost'].'--'.$_POST['caltagreply']."'";
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
		//DB $sets[] = 'defdisplay='.intval($_POST['defdisplay']);
		$sets[] = "defdisplay=:defdisplay";
		$qarr[':defdisplay'] = $_POST['defdisplay'];
	}
	if (isset($_POST['chgsortby'])) {
		//DB $sets[] = 'sortby='.intval($_POST['sortby']);
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
		//DB $sets[] = 'cntingb='.intval($_POST['cntingb']);
		$sets[] = "cntingb=:cntingb";
		$qarr[':cntingb'] = $_POST['cntingb'];
		if (is_numeric($_POST['points'])) {
			//DB $sets[] = 'points='.intval($_POST['points']);
			$sets[] = "points=:points";
			$qarr[':points'] = $_POST['points'];
		}
	}
	if (isset($_POST['chggbcat'])) {
		//DB $sets[] = "gbcategory='{$_POST['gbcat']}'";
		$sets[] = "gbcategory=:gbcategory";
		$qarr[':gbcategory'] = $_POST['gbcat'];
	}
	if (isset($_POST['chgforumtype'])) {
		//DB $sets[] = "forumtype='{$_POST['forumtype']}'";
		$sets[] = "forumtype=:forumtype";
		$qarr[':forumtype'] = $_POST['forumtype'];
	}
	if (isset($_POST['chgtaglist'])) {
		if (isset($_POST['usetags'])) {
			$taglist = trim($_POST['taglist']);
		} else {
			$taglist = '';
		}
		//DB $sets[] = "taglist='$taglist'";
		$sets[] = "taglist=:taglist";
		$qarr[':taglist'] = $taglist;
	}
	if (count($sets)>0 & count($checked)>0) {
		$setslist = implode(',',$sets);
		//DB $query = "UPDATE imas_forums SET $setslist WHERE id IN ($checkedlist);";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_forums SET $setslist WHERE id IN ($checkedlist);");
		$stm->execute($qarr);
	}
	if (isset($_POST['chgsubscribe'])) {

		if (isset($_POST['subscribe'])) {
			//add any subscriptions we don't already have
			//DB $query = "SELECT forumid FROM imas_forum_subscriptions WHERE forumid IN ($checkedlist) AND userid='$userid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("SELECT forumid FROM imas_forum_subscriptions WHERE forumid IN ($checkedlist) AND userid=:userid");
			$stm->execute(array(':userid'=>$userid));
			$hassubscribe = array();
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$hassubscribe[] = $row[0];
			}

			$toadd = array_diff($_POST['checked'],$hassubscribe);
			foreach ($toadd as $fid) {
				$fid = intval($fid);
				if ($fid>0) {
					//DB $query = "INSERT INTO imas_forum_subscriptions (forumid,userid) VALUES ('$fid','$userid')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("INSERT INTO imas_forum_subscriptions (forumid,userid) VALUES (:forumid, :userid)");
					$stm->execute(array(':forumid'=>$fid, ':userid'=>$userid));
				}
			}
		} else {
			//remove any existing subscriptions
			//DB $query = "DELETE FROM imas_forum_subscriptions WHERE forumid IN ($checkedlist) AND userid='$userid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_forum_subscriptions WHERE forumid IN ($checkedlist) AND userid=:userid");
			$stm->execute(array(':userid'=>$userid));

		}

	}
	header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
	exit;
}

//prep for output
$forumitems = array();
//DB $query = "SELECT id,name FROM imas_forums WHERE courseid='$cid' ORDER BY name";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT id,name FROM imas_forums WHERE courseid=:courseid ORDER BY name");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$forumitems[$row[0]] = $row[1];
}

//DB $query = "SELECT id,name FROM imas_stugroupset WHERE courseid='$cid' ORDER BY name";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
$stm = $DBH->prepare("SELECT id,name FROM imas_stugroupset WHERE courseid=:courseid ORDER BY name");
$stm->execute(array(':courseid'=>$cid));
$i=0;
$page_groupSelect = array();
//DB while ($row = mysql_fetch_row($result)) {
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$page_groupSelect['val'][$i] = $row[0];
	$page_groupSelect['label'][$i] = "Use group set: {$row[1]}";
	$i++;
}

//DB $query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
$page_gbcatSelect = array();
$i=0;
//DB while ($row = mysql_fetch_row($result)) {
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
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
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

require("../header.php");

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
echo "&gt; Mass Change Forums</div>";
echo '<div id="headerchgforums" class="pagetitle"><h2>Mass Change Forums</h2></div>';

echo "<form id=\"mainform\" method=post action=\"chgforums.php?cid=$cid\" onsubmit=\"return valform();\">";


if (count($forumitems)==0) {
	echo '<p>No forums to change.</p>';
	require("../footer.php");
	exit;
}

?>
Check: <a href="#" onclick="return chkAllNone('mainform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('mainform','checked[]',false)">None</a>

<ul class=nomark>

<?php

foreach($forumitems as $id=>$name) {
	echo '<li><input type="checkbox" name="checked[]" value="'.$id.'" /> '.Sanitize::encodeStringForDisplay($name).'</li>';
}
?>
</ul>
<p>With selected, make changes below
<fieldset>
<legend>Forum Options</legend>
<table class=gb>
<thead>
<tr><th>Change?</th><th>Option</th><th>Setting</th></tr>
</thead>
<tbody>
<tr class="coptr">
	<td><input type="checkbox" name="chgavail" class="chgbox"/></td>
	<td class="r">Show:</td>
	<td>
	<input type=radio name="avail" value="0" />Hide<br/>
	<input type=radio name="avail" value="1" checked="checked"/>Show by Dates<br/>
	<input type=radio name="avail" value="2"/>Show Always
	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgpostby" class="chgbox" /></td>
	<td class="r">Students can create new threads:</td>
	<td>
	<input type=radio name="postby" value="Always" checked="checked"/>Always<br/>
	<input type=radio name="postby" value="Never" />Never<br/>
	<input type=radio name="postby" value="Date" />Before:
	<input type=text size=10 name="postbydate" value="<?php echo $postbydate;?>">
	<a href="#" onClick="displayDatePicker('postbydate', this); return false">
	<img src="../img/cal.gif" alt="Calendar"/></A>
	at <input type=text size=10 name=postbytime value="<?php echo $postbytime;?>">

	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgreplyby" class="chgbox" /></td>
	<td class="r">Students can reply to posts:</td>
	<td>
	<input type=radio name="replyby" value="Always" checked="checked"/>Always<br/>
	<input type=radio name="replyby" value="Never" />Never<br/>
	<input type=radio name="replyby" value="Date" />Before:
	<input type=text size=10 name="replybydate" value="<?php echo $replybydate;?>">
	<a href="#" onClick="displayDatePicker('replybydate', this); return false">
	<img src="../img/cal.gif" alt="Calendar"/></A>
	at <input type=text size=10 name=replybytime value="<?php echo $replybytime;?>">

	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgallowlate" class="chgbox" /></td>
	<td class="r">Allow use of LatePasses?:</td>
	<td>
		<?php
		writeHtmlSelect("allowlate",$page_allowlateSelect['val'],$page_allowlateSelect['label'],0);
		echo ' on ';
		writeHtmlSelect("allowlateon",$page_allowlateonSelect['val'],$page_allowlateonSelect['label'],0);
		?>
		<br/><label><input type="checkbox" name="latepassafterdue"> Allow LatePasses after due date, within 1 LatePass period</label>
	</td>

</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgcaltag" class="chgbox" /></td>
	<td class="r">Calendar icon:</td>
	<td>
	New Threads: <input name="caltagpost" type=text size=8 value="FP"/>,
	Replies: <input name="caltagreply" type=text size=8 value="FR"/>
	</td>
</tr>


<tr class="coptr">
	<td><input type="checkbox" name="chgallowanon" class="chgbox"/></td>
	<td class="r">Allow anonymous posts: </td>
	<td>
		<input type=checkbox name="allowanon" value="1"/>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgallowmod" class="chgbox"/></td>
	<td class="r">Allow students to modify posts: </td>
	<td>
		<input type=checkbox name="allowmod" value="1" checked="checked"/>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgallowdel" class="chgbox"/></td>
	<td class="r">Allow students to delete own posts (if no replies): </td>
	<td>
		<input type=checkbox name="allowdel" value="1"/>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgallowlikes" class="chgbox"/></td>
	<td class="r">Turn on "liking" posts: </td>
	<td>
		<input type=checkbox name="allowlikes" value="1"/>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgviewafterpost" class="chgbox"/></td>
	<td class="r">Viewing before posting:</td>
	<td>
		<input type=checkbox name="viewafterpost" value="1"/> Prevent students from viewing posts until they have created a thread.<br/><i>You will likely also want to disable modifying posts</i>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgsubscribe" class="chgbox"/></td>
	<td class="r">Get email notify of new posts: </td>
	<td>
		<input type=checkbox name="subscribe" value="1"/>
	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgdefdisplay" class="chgbox"/></td>
	<td class="r">Default display: </td>
	<td>
	<select name="defdisplay">
		<option value="0" selected="selected">Expanded</option>
		<option value="1">Collapsed</option>
		<option value="2">Condensed</option>
	</select>
	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgsortby" class="chgbox"/></td>
	<td class="r">Sort threads by: </td>
	<td>
	<input type="radio" name="sortby" value="0" checked="checked"/> Thread start date<br/>
	<input type="radio" name="sortby" value="1" /> Most recent reply date
	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgcntingb" class="chgbox"/></td>
	<td class="r">Count: </td>
	<td><input name="cntingb" value="0" checked="checked" type="radio"> No<br/>
	<input name="cntingb" value="1" type="radio"> Yes<br/>
	<input name="cntingb" value="4" type="radio"> Yes, but hide from students for now<br/>
	<input name="cntingb" value="2" type="radio"> Yes, as extra credit<br/>
	If yes, for: <input type=text size=4 name="points" value=""/> points (leave blank to not change)
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chggbcat" class="chgbox"/></td>
	<td class="r">Gradebook category: </td>
	<td>
<?php
writeHtmlSelect ("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],null,"Default",0," id=gbcat");
?>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgforumtype" class="chgbox"/></td>
	<td class="r">Forum Type: </td>
	<td>
		<input type=radio name="forumtype" value="0" checked="checked"/>Regular forum<br/>
		<input type=radio name="forumtype" value="1"/>File sharing forum
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgtaglist" class="chgbox"/></td>
	<td class="r">Categorize posts?: </td>
	<td>
		<input type=checkbox name="usetags" value="1" <?php if ($line['taglist']!='') { echo "checked=1";}?>
		  onclick="document.getElementById('tagholder').style.display=this.checked?'':'none';" />
		 <span id="tagholder" style="display:<?php echo ($line['taglist']=='')?"none":"inline"; ?>">
		   Enter in format CategoryDescription:category,category,category<br/>
		   <textarea rows="2" cols="60" name="taglist"><?php echo $line['taglist'];?></textarea>
		 </span>
	</td>
</tr>

</tbody>
</table>
</fieldset>
<div class="submit"><input type="submit" name="submit" value="<?php echo _('Apply Changes')?>" /></div>
</form>
<?php
require("../footer.php");
?>
