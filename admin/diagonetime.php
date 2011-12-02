<?php
//IMathAS:  Diagnostic one-time passwords
//(c) 2009 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Diagnostic One-time Passwords";
$diag = $_GET['id'];

$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"$imasroot/admin/admin.php\">Admin</a> &gt; Diagnostic One-time Passwords</div>\n";

	// SECURITY CHECK DATA PROCESSING
if ($myrights<60) {
	$overwriteBody = 1;
	$body = "You don't have authority to access this page.";
} else if (isset($_GET['generate'])) {
	if (isset($_POST['n'])) {
		$lets = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
		$code_list = array();
		$now = time();
		$n = intval($_POST['n']);
		$goodfor = intval($_POST['multi']);
		$query = 'INSERT IGNORE INTO imas_diag_onetime (diag,time,code,goodfor) VALUES';
		for ($i=0; $i<$n; $i++) {
			$code = '';
			for ($j=0;$j<3;$j++) {
				$code .= substr($lets,rand(0,23),1);
			}
			for ($j=0;$j<3;$j++) {
				$code .= rand(1,9);
			}
			//for ($j=0;$j<3;$j++) {
			//	$code .= substr($lets,rand(0,23),1);
			//}
			if ($i>0) { $query .= ','; }
			$query .= "('$diag',$now,'$code',$goodfor)";
			$code_list[] = $code;	
		}
		mysql_query($query) or die("Query failed : " . mysql_error());
		$code_list = array();
		$query = "SELECT code,goodfor FROM imas_diag_onetime WHERE time=$now";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			if ($row[1]==0) {
				$row[1] = "One-time";
			} else if ($row[1]>1000000000) {
				if ($row[1]<time()) {
					$row[1] = "Used - Expired";
				} else {
					$row[1] = "Used - set to expire";
				}
			} else {
				$row[1] = intval($row[1]) . " minutes";
			}
			$code_list[] = $row;	
		}
	}
} else if (isset($_GET['delete'])) {
	if ($_GET['delete']=='true') {
		$query = "DELETE FROM imas_diag_onetime WHERE diag='$diag'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/admin.php");
		exit;
	}
} else {
	$old = time() - 365*24*60*60; //one year ago
	$now = time();
	$query = "DELETE FROM imas_diag_onetime WHERE time<$old OR (goodfor>1000000000 AND goodfor<$now)";
	mysql_query($query) or die("Query failed : " . mysql_error());
	$code_list = array();
	$query = "SELECT time,code,goodfor FROM imas_diag_onetime WHERE diag='$diag' ORDER BY time";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$row[0] = tzdate("F j, Y",$row[0]);
		if ($row[2]==0) {
			$row[2] = "One-time";
		} else if ($row[2]>1000000000) {
			$row[2] = "Used - set to expire";
		} else {
			$row[2] = intval($row[2]) . " minutes";
		}
		$code_list[] = $row;	
	}
}

/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) { //NO AUTHORITY
	echo $body;
} else {
	echo $curBreadcrumb;
	echo '<div id="headerdiagonetime" class="pagetitle"><h2>Diagnostic One-time Passwords</h2></div>';
	$query = "SELECT name FROM imas_diags WHERE id='$diag'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	echo '<h4>'.mysql_result($result,0,0).'</h4>';
	if (isset($_GET['generate'])) {
		if (isset($_POST['n'])) {
			echo "<b>Newly generated passwords</b> <a href=\"diagonetime.php?id=$diag&view=true\">View all</a>";
			echo '<table><thead><tr><th>Codes</th><th>Good For</th></tr></thead><tbody>';
			foreach ($code_list as $code) {
				echo "<tr><td>{$code[0]}</td><td>{$code[1]}</td></tr>";
			}
			echo '</tbody></table>';
		} else {
			echo "<form method=\"post\" action=\"diagonetime.php?id=$diag&generate=true\">";
			echo '<p>Generate <input type="text" size="1" value="1" name="n" /> passwords <br/>';
			echo 'Allow multi-use within <input type="text" size="1" value="0" name="multi" /> minutes (0 for one-time-only use)</p>';
			echo '<input type="submit" value="Go" />';
			echo '</form>';
		}
	} else if (isset($_GET['delete'])) {
		echo "<p>Are you sure you want to delete all one-time passwords for this diagnostic?</p>\n";
		echo "<p><input type=button value=\"Delete\" onclick=\"window.location='diagonetime.php?id=$diag&delete=true'\">\n";
		echo "<input type=button value=\"Nevermind\" onclick=\"window.location='admin.php'\"></p>\n";
	} else {
		echo "<b>All one-time passwords</b> <a href=\"diagonetime.php?id=$diag&generate=true\">Generate</a> <a href=\"diagonetime.php?id=$diag&delete=check\">Delete all</a>";
		echo '<table><thead><tr><th>Codes</th><th>Good For</th><th>Created</th></tr></thead><tbody>';
		foreach ($code_list as $row) {
			echo "<tr><td>{$row[1]}</td><td>{$row[2]}</td><td>{$row[0]}</td></tr>";
		}
		echo '</tbody></table>';
	}
}
require("../footer.php");

?>
