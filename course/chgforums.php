<?php
//IMathAS:  Mass change forum settings
//(c) 2010 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");

if (!isset($teacherid)) {
	echo "You need to log in as a teacher to access this page";
	exit;
}
$cid = $_GET['cid'];

if (isset($_POST['checked'])) { //form submitted
	$checked = $_POST['checked'];
	require_once("parsedatetime.php");
	$checkedlist = "'".implode("','",$checked)."'";
	$sets = array();
	if (isset($_POST['chgavail'])) {
		$sets[] = 'avail='.intval($_POST['avail']);
	}
	if (isset($_POST['chgreplyby'])) {
		if ($_POST['replyby']=="Always") {
			$replyby = 2000000000;
		} else if ($_POST['replyby']=="Never") {
			$replyby = 0;
		} else {
			$replyby = parsedatetime($_POST['replybydate'],$_POST['replybytime']);
		}
		$sets[] = "replyby='$replyby'";
	}
	if (isset($_POST['chgreplyby'])) {
		if ($_POST['postby']=="Always") {
			$postby = 2000000000;
		} else if ($_POST['postby']=="Never") {
			$postby = 0;
		} else {
			$postby = parsedatetime($_POST['postbydate'],$_POST['postbytime']);
		}
		$sets[] = "postby='$postby'";
	}	
	if (isset($_POST['chgcaltag'])) {
		$sets[] = "caltag='".$_POST['caltagpost'].'--'.$_POST['caltagreply']."'";
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
	if (count($sops)>0) {
		$out = "settings";
		foreach ($sops as $op) {
			$out = "($out $op)";
		}
		$sets[] = "settings=$out";
	}
	if (isset($_POST['chgdefdisplay'])) {
		$sets[] = 'defdisplay='.intval($_POST['defdisplay']);
	}
	if (isset($_POST['chgsortby'])) {
		$sets[] = 'sortby='.intval($_POST['sortby']);
	}
	if (isset($_POST['chgcntingb'])) {
		$sets[] = 'cntingb='.intval($_POST['cntingb']);
		$sets[] = 'points='.intval($_POST['points']);
	}
	if (isset($_POST['chggbcat'])) {
		$sets[] = "gbcategory='{$_POST['gbcat']}'";
	}
	if (isset($_POST['chgforumtype'])) {
		$sets[] = "forumtype='{$_POST['forumtype']}'";
	}
	if (isset($_POST['chgtaglist'])) {
		if (isset($_POST['usetags'])) {
			$taglist = trim($_POST['taglist']);
		} else {
			$taglist = '';
		}	
		$sets[] = "taglist='$taglist'";
	}
	if (count($sets)>0 & count($checked)>0) {
		$setslist = implode(',',$sets);
		$query = "UPDATE imas_forums SET $setslist WHERE id IN ($checkedlist);";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
	}
	if (isset($_POST['chgsubscribe'])) {
		
		if (isset($_POST['subscribe'])) {
			//add any subscriptions we don't already have
			$query = "SELECT forumid FROM imas_forum_subscriptions WHERE forumid IN ($checkedlist) AND userid='$userid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$hassubscribe = array();
			if (mysql_num_rows($result)>0) {
				while ($row = mysql_fetch_row($result)) {
					$hassubscribe[] = $row[0];
				}
			}
			$toadd = array_diff($_POST['checked'],$hassubscribe);
			foreach ($toadd as $fid) {
				$fid = intval($fid);
				if ($fid>0) {
					$query = "INSERT INTO imas_forum_subscriptions (forumid,userid) VALUES ('$fid','$userid')";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			}
		} else {
			//remove any existing subscriptions
			$query = "DELETE FROM imas_forum_subscriptions WHERE forumid IN ($checkedlist) AND userid='$userid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
		}
			
	}
	header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
	exit;
} 

//prep for output
$forumitems = array();
$query = "SELECT id,name FROM imas_forums WHERE courseid='$cid' ORDER BY name";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$forumitems[$row[0]] = $row[1];
}

$query = "SELECT id,name FROM imas_stugroupset WHERE courseid='$cid' ORDER BY name";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$i=0;
$page_groupSelect = array();
while ($row = mysql_fetch_row($result)) {
	$page_groupSelect['val'][$i] = $row[0];
	$page_groupSelect['label'][$i] = "Use group set: {$row[1]}";
	$i++;
}

$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$page_gbcatSelect = array();
$i=0;
if (mysql_num_rows($result)>0) {
	while ($row = mysql_fetch_row($result)) {
		$page_gbcatSelect['val'][$i] = $row[0];
		$page_gbcatSelect['label'][$i] = $row[1];
		$i++;
	}
}

$hr = floor($coursedeftime/60)%12;
$min = $coursedeftime%60;
$am = ($coursedeftime<12*60)?'am':'pm';
$deftime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
	

$replybydate = tzdate("m/d/Y",time()+7*24*60*60);
$replybytime = $deftime; //tzdate("g:i a",time()+7*24*60*60);
$postbydate = tzdate("m/d/Y",time()+7*24*60*60);
$postbytime = $deftime; //tzdate("g:i a",time()+7*24*60*60);


//HTML output
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
$placeinhead .= '<style type="text/css">
table td {
	border-bottom: 1px solid #ccf;	
}
</style>';

require("../header.php");

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
echo "&gt; Mass Change Forums</div>";
echo '<div id="headerchgforums" class="pagetitle"><h2>Mass Change Forums</h2></div>';

echo "<form id=\"mainform\" method=post action=\"chgforums.php?cid=$cid\">";


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
	echo '<li><input type="checkbox" name="checked[]" value="'.$id.'" /> '.$name.'</li>';		
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
	<td><input type="checkbox" name="chgavail"/></td>
	<td class="r">Show:</td>
	<td>
	<input type=radio name="avail" value="0" />Hide<br/>
	<input type=radio name="avail" value="1" checked="checked"/>Show by Dates<br/>
	<input type=radio name="avail" value="2"/>Show Always
	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgpostby" /></td>
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
	<td><input type="checkbox" name="chgreplyby" /></td>
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
	<td><input type="checkbox" name="chgcaltag" /></td>
	<td class="r">Calendar icon:</td>
	<td> 
	New Threads: <input name="caltagpost" type=text size=1 value="FP"/>, 
	Replies: <input name="caltagreply" type=text size=1 value="FR"/>
	</td>
</tr>
		

<tr class="coptr">
	<td><input type="checkbox" name="chgallowanon"/></td>
	<td class="r">Allow anonymous posts: </td>
	<td>
		<input type=checkbox name="allowanon" value="1"/>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgallowmod"/></td>
	<td class="r">Allow students to modify posts: </td>
	<td>
		<input type=checkbox name="allowmod" value="1" checked="checked"/>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgallowdel"/></td>
	<td class="r">Allow students to delete own posts (if no replies): </td>
	<td>
		<input type=checkbox name="allowdel" value="1"/>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgallowlikes"/></td>
	<td class="r">Turn on "liking" posts: </td>
	<td>
		<input type=checkbox name="allowlikes" value="1"/>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgsubscribe"/></td>
	<td class="r">Get email notify of new posts: </td>
	<td>
		<input type=checkbox name="subscribe" value="1"/>
	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgdefdisplay"/></td>
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
	<td><input type="checkbox" name="chgsortby"/></td>
	<td class="r">Sort threads by: </td>
	<td>
	<input type="radio" name="sortby" value="0" checked="checked"/> Thread start date<br/>
	<input type="radio" name="sortby" value="1" /> Most recent reply date	
	</td>
</tr>

<tr class="coptr">
	<td><input type="checkbox" name="chgcntingb"/></td>
	<td class="r">Count: </td>
	<td><input name="cntingb" value="0" checked="checked" type="radio"> No<br/>
	<input name="cntingb" value="1" type="radio"> Yes<br/>
	<input name="cntingb" value="2" type="radio"> Yes, as extra credit<br/>
	If yes, for: <input type=text size=4 name="points" value="0"/> points
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chggbcat"/></td>
	<td class="r">Gradebook category: </td>
	<td>
<?php 
writeHtmlSelect ("gbcat",$page_gbcatSelect['val'],$page_gbcatSelect['label'],null,"Default",0," id=gbcat");
?>
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgforumtype"/></td>
	<td class="r">Gradebook category: </td>
	<td>
		<input type=radio name="forumtype" value="0" checked="checked"/>Regular forum<br/>
		<input type=radio name="forumtype" value="1"/>File sharing forum
	</td>
</tr>
<tr class="coptr">
	<td><input type="checkbox" name="chgtaglist"/></td>
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
<input type="submit" name="submit" value="Submit Changes" />
</form>
<?php
require("../footer.php");
?>
	
	
	

