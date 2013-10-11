<?php
//IMathAS:  Add end messages 
//(c) 2008 David Lippman

if (!isset($imasroot)) {
	require("../validate.php");
	if (!(isset($teacherid))) { // loaded by a NON-teacher
		echo "You must be a teacher to access this page";
		exit;
	}
}
	$cid = $_GET['cid'];
	
	if (isset($_GET['record'])) {
		$endmsg = array();
		$endmsg['type'] = $_POST['type'];
		$endmsg['def'] = stripslashes($_POST['msg'][0]);
		$i=1;
		$msgarr = array();
		while (isset($_POST['sc'][$i]) && !empty($_POST['sc'][$i]) ) {
			$key = (int)$_POST['sc'][$i];
			if ($key>0) {
				$msgarr[$key] = stripslashes($_POST['msg'][$i]);
			}
			$i++;
		}
		krsort($msgarr);
		$endmsg['msgs'] = $msgarr;
		require_once("../includes/htmLawed.php");
		$htmlawedconfig = array('elements'=>'*-script');
		$endmsg['commonmsg'] = htmLawed(stripslashes($_POST['commonmsg']),$htmlawedconfig);
		$msgstr = addslashes(serialize($endmsg));
		if (isset($_POST['aid'])) {
			$query = "UPDATE imas_assessments SET endmsg='$msgstr' WHERE id='{$_POST['aid']}'";
		} else if (isset($_POST['aidlist'])) {
			$aidlist = "'".implode("','",explode(',',$_POST['aidlist']))."'";
			$query = "UPDATE imas_assessments SET endmsg='$msgstr' WHERE id IN ($aidlist)";
		}
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
			
		exit;	
	}
	
	$pagetitle = "End of Assessment Messages";
	$useeditor = "commonmsg";
	
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
	if (!isset($_POST['checked'])) {
		echo "&gt; <a href=\"addquestions.php?cid=$cid&amp;aid={$_GET['aid']}\">Add/Remove Questions</a> &gt; End of Assessment Msg</div>\n";
	} else {
		echo "&gt; <a href=\"chgassessments.php?cid=$cid\">Mass Change Assessments</a> &gt; End of Assessment Msg</div>\n";
	}
	if (!isset($_POST['checked'])) {
		$query = "SELECT endmsg FROM imas_assessments WHERE id='{$_GET['aid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$endmsg = mysql_result($result,0,0);
	} else {
		$endmsg = '';
		if (count($_POST['checked'])==0) {
			echo "No assessments selected";
			require("../footer.php");
			exit;
		}
	}
	if ($endmsg!='') {
		$endmsg = unserialize($endmsg);
	} else {
		$endmsg = array();
		$endmsg['def'] = '';
		$endmsg['type'] = 0;
		$endmsg['msgs'] = array();
		$endmsg['commonmsg'] = '';
	}
	echo '<div id="headerassessendmsg" class="pagetitle"><h2>End of Assessment Messages</h2></div>';
	echo "<form method=\"post\" action=\"assessendmsg.php?cid=$cid&amp;record=true\" />";
	if (isset($_POST['checked'])) {
		echo '<input type="hidden" name="aidlist" value="'.implode(',',$_POST['checked']).'" />';
	} else {
		echo '<input type="hidden" name="aid" value="'.$_GET['aid'].'" />';
	}
	echo '<p>Base messages on: ';
	echo '<input type="radio" name="type" value="0" ';
	if ($endmsg['type']==0) { echo 'checked="checked"';}
	echo ' />Points <input type="radio" name="type" value="1" ';
	if ($endmsg['type']==1) { echo 'checked="checked"';}
	echo ' />Percents</p>';
	
	echo '<table class="gb"><thead><tr><th>If score is at least</th><th>Display this message</th></tr></thead><tbody>';
	$i=1;
	foreach($endmsg['msgs'] as $sc=>$msg) {
		$msg = str_replace('"','&quot;',$msg);
		echo "<tr><td><input type=\"text\" size=\"4\" name=\"sc[$i]\" value=\"$sc\"/></td>";
		echo "<td><input type=\"text\" size=\"80\" name=\"msg[$i]\" value=\"$msg\" /></td></tr>";
		$i++;
	}
	for ($j=0;$j<10;$j++) {
		echo "<tr><td><input type=\"text\" size=\"4\" name=\"sc[$i]\" value=\"\"/></td>";
		echo "<td><input type=\"text\" size=\"80\" name=\"msg[$i]\" value=\"\" /></td></tr>";
		$i++;
	}
	echo "<tr><td>Otherwise, show:</td>";
	echo "<td><input type=\"text\" size=\"80\" name=\"msg[0]\" value=\"{$endmsg['def']}\" /></td></tr>";
	echo '</tbody></table>';
	echo '<p>After the score-specific message, display this text to everyone:</p>';
	echo '<div class=editor><textarea cols="50" rows="10" name="commonmsg" style="width: 100%">';
	echo htmlentities($endmsg['commonmsg']);
	echo '</textarea></div>';
	echo '<p><input type="submit" value="Submit" /></p>';
	echo '</form>';
?>
<p>Order of entries is not important; the message with highest applicable score will be reported.  
The "otherwise, show" message will display if no other score messages are defined.  Use this instead
of trying to create a 0 score entry</p>
<?php
	require("../footer.php");
?>
