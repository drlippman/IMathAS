<?php
//IMathAS:  Add end messages 
//(c) 2008 David Lippman

	require("../validate.php");
	if (!(isset($teacherid))) { // loaded by a NON-teacher
		echo "You must be a teacher to access this page";
		exit;
	}
	$aid = $_GET['aid'];
	$cid = $_GET['cid'];
	
	if (isset($_GET['record'])) {
		$endmsg = array();
		$endmsg['type'] = $_POST['type'];
		$endmsg['def'] = stripslashes($_POST['msg'][0]);
		$i=1;
		$msgarr = array();
		while (isset($_POST['sc'][$i]) && !empty($_POST['sc'][$i]) ) {
			$msgarr[(float)$_POST['sc'][$i]] = stripslashes($_POST['msg'][$i]);
			$i++;
		}
		krsort($msgarr);
		$endmsg['msgs'] = $msgarr;
		$msgstr = addslashes(serialize($endmsg));
		$query = "UPDATE imas_assessments SET endmsg='$msgstr' WHERE id='$aid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
			
		exit;	
	}
	
	$pagetitle = "End of Assessment Messages";
	
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
	echo "&gt; <a href=\"addquestions.php?cid=$cid&amp;aid=$aid\">Add/Remove Questions</a> &gt; End of Assessment Msg</div>\n";
	
	$query = "SELECT endmsg FROM imas_assessments WHERE id='$aid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$endmsg = mysql_result($result,0,0);
	if ($endmsg!='') {
		$endmsg = unserialize($endmsg);
	} else {
		$endmsg = array();
		$endmsg['def'] = '';
		$endmsg['type'] = 0;
		$endmsg['msgs'] = array();
	}
	echo "<form method=\"post\" action=\"assessendmsg.php?cid=$cid&amp;aid=$aid&amp;record=true\" />";
	
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
	
	echo '<input type="submit" value="Submit" />';
	echo '</form>';
?>
<p>Order of entries is not important; the message with highest applicable score will be reported.  
The "otherwise, show" message will display if no other score messages are defined.  Use this instead
of trying to create a 0 score entry</p>
<?php
	require("../footer.php");
?>
