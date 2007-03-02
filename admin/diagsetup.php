<?php
	require("../validate.php");
	if ($myrights<75) {
		require("../header.php");
		echo "You don't have authority to access this page.";
		require("../footer.php");
		exit;
	}
	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/diag.js\"></script>\n";
	require("../header.php");
	
	
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"$imasroot/admin/admin.php\">Admin</a> &gt; Diagnostic Setup</div>\n";
	
	if (isset($_GET['step']) && $_GET['step']==2) {
		echo '<h2>Diagnostic Setup</h2>';
		echo '<h4>Second-level Selector - extra information</h4>';
		echo '<form method=post action="diagsetup.php?step=3">';
	
		$sel1 = array();
		$ips = array();
		$pws = array();
		foreach ($_POST as $k=>$v) {
			if (strpos($k,'selout')!==FALSE) {
				$sel1[] = $v;
			} else if (strpos($k,'ipout')!==FALSE) {
				$ips[] = $v;
			} else if (strpos($k,'pwout')!==FALSE) {
				$pws[] = $v;
			}
		}
		
		$sel1list = implode(',',$sel1);
		$iplist = implode(',',$ips);
		$pwlist = implode(',',$pws);
		$public = 1*$_POST['avail'] + 2*$_POST['public'] + 4*$_POST['reentry'];
		
		echo "<input type=hidden name=\"sel1list\" value=\"$sel1list\"/>\n";
		echo "<input type=hidden name=\"iplist\" value=\"$iplist\"/>\n";
		echo "<input type=hidden name=\"pwlist\" value=\"$pwlist\"/>\n";
		echo "<input type=hidden name=\"cid\" value=\"{$_POST['cid']}\"/>\n";
		echo "<input type=hidden name=\"term\" value=\"{$_POST['term']}\"/>\n";
		echo "<input type=hidden name=\"sel1name\" value=\"{$_POST['sel']}\"/>\n";
		echo "<input type=hidden name=\"diagname\" value=\"{$_POST['diagname']}\"/>\n";
		echo "<input type=hidden name=\"idprompt\" value=\"{$_POST['idprompt']}\"/>\n";
		echo "<input type=hidden name=\"public\" value=\"$public\"/>\n";
		
		$sel2 = array();
		if (isset($_POST['id'])) {
			echo "<input type=hidden name=\"id\" value=\"{$_POST['id']}\"/>\n";
			$query = "SELECT sel1list,sel2name,sel2list,aidlist FROM imas_diags WHERE id='{$_POST['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
			$s1l = explode(',',$row[0]);
			$s2l = explode(';',$row[2]);
			for ($i=0;$i<count($s1l);$i++) {
				$sel2[$s1l[$i]] = explode('~',$s2l[$i]);
			}
			$sel2name = $row[1];
			$aids = explode(',',$row[3]);
		} else {
			$sel2name = "instructor";
			$aids = array();
		}
	
	
		echo "<p>Second-level selector name:  <input type=text name=sel2name value=\"$sel2name\"/> 'Select your ______'</p>";
		echo '<p>For each of the first-level selectors, select which assessment should be delivered, and provide options for the second-level selector</p>';
	
		foreach($sel1 as $k=>$s1) {
			echo '<div>';
			echo "<p><b>$s1</b>.  Deliver assessment: <select name=\"aid$k\">";
			$query = "SELECT id,name FROM imas_assessments WHERE courseid='{$_POST['cid']}'";
			$result = mysql_query($query);
			while ($row = mysql_fetch_row($result)) {
				echo "<option value=\"{$row[0]}\" ";
				if (isset($aids[$k]) && $row[0]==$aids[$k]) {
					echo "selected=1";
				}
				echo ">{$row[1]}</option>\n";
			}
			echo "</select></p>\n";
			echo "<p>Add selector value: <input type=text id=\"in$k\"  onkeypress=\"return onenter(event,'in$k','out$k')\"/>";
			echo "<input type=button value=\"Add\" onclick=\"additem('in$k','out$k')\"/><br/>\n";
			echo "<table><tbody id=\"out$k\">";
			if (isset($sel2[$s1])) {
				for ($i=0;$i<count($sel2[$s1]);$i++) {
					echo "<tr id=\"trout$k-$i\"><td><input type=hidden id=\"out$k-$i\" name=\"out$k-$i\" value=\"{$sel2[$s1][$i]}\">{$sel2[$s1][$i]}</td>";
					echo "<td><a href='#' onclick=\"removeitem('out$k-$i','out$k')\">Remove</a> ";
					echo "<a href='#' onclick=\"moveitemup('out$k-$i','out$k')\">Move up</a> ";
					echo "<a href='#' onclick=\"moveitemdown('out$k-$i','out$k')\">Move down</a> ";
					echo "</td></tr>";
				}
			}
			echo "</tbody></table></p>";
			
			if (isset($sel2[$s1]) && count($sel2[$s1])>0) {
				echo "<script> cnt['out$k'] = ".count($sel2[$s1]).";</script>";
			} else {
				echo "<script> cnt['out$k'] = 0;</script>";
			}
	
			echo '</div>';
		}
	
		echo '<input type=submit value="Continue">';
		echo '<form>';
	
		require("../footer.php");
		exit;
	}
	if (isset($_GET['step']) && $_GET['step']==3) {
		$sel1 = explode(',',$_POST['sel1list']);
		$aids = array();
		for ($i=0;$i<count($sel1);$i++) {
			$aids[$i] = $_POST['aid'.$i];
		}
		$aidlist = implode(',',$aids);
		$sel2 = array();
		foreach ($_POST as $k=>$v) {
			if (strpos($k,'out')!==FALSE) {
				$n = substr($k,3,strpos($k,'-')-3);
				$sel2[$n][] = $v;
			}
		}
		for ($i=0;$i<count($sel2);$i++) {
			$sel2[$i] = implode('~',$sel2[$i]);
		}
		$sel2list = implode(';',$sel2);
		
		if (isset($_POST['id'])) {
			$query = "UPDATE imas_diags SET ";
			$query .= "ownerid='$groupid',name='{$_POST['diagname']}',cid='{$_POST['cid']}',term='{$_POST['term']}',public='{$_POST['public']}',";
			$query .= "ips='{$_POST['iplist']}',pws='{$_POST['pwlist']}',idprompt='{$_POST['idprompt']}',sel1name='{$_POST['sel1name']}',";
			$query .= "sel1list='{$_POST['sel1list']}',aidlist='$aidlist',sel2name='{$_POST['sel2name']}',sel2list='$sel2list'";
			$query .= " WHERE id='{$_POST['id']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$id = $_POST['id'];
			echo "<p>Diagnostic Updated</p>";
		} else {
			$query = "INSERT INTO imas_diags (ownerid,name,cid,term,public,ips,pws,idprompt,sel1name,sel1list,aidlist,sel2name,sel2list) VALUES ";
			$query .= "('$groupid','{$_POST['diagname']}','{$_POST['cid']}','{$_POST['term']}','{$_POST['public']}','{$_POST['iplist']}',";
			$query .= "'{$_POST['pwlist']}','{$_POST['idprompt']}','{$_POST['sel1name']}','{$_POST['sel1list']}','$aidlist','{$_POST['sel2name']}','$sel2list')";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$id = mysql_insert_id();
			echo "<p>Diagnostic Added</p>";
		}
		echo "<p>Direct link to diagnostic:  <b>http://{$_SERVER['HTTP_HOST']}$imasroot/diag/index.php?id=$id</b></p>";
		
		if ($_POST['public']&2) {
			echo "<p>Diagnostic is listed on the public listing at: <b>http://{$_SERVER['HTTP_HOST']}$imasroot/diag/</b></p>";
		}
		echo "<a href=\"$imasroot/admin/admin.php\">Return to Admin Page</a>\n";
		require("../footer.php");
		exit;
	}
	
	if (isset($_GET['id'])) {
		$query = "SELECT name,term,cid,public,idprompt,ips,pws,sel1name,sel1list FROM imas_diags WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		$diagname = $line['name'];
		$cid = $line['cid'];
		$public = $line['public'];
		$idprompt = $line['idprompt'];
		$ips = $line['ips'];
		$pws = $line['pws'];
		$sel = $line['sel1name'];
		$sel1list=  $line['sel1list'];
		$term = $line['term'];
	} else {
		$diagname = '';
		$cid = 0;
		$public = 7;
		$idprompt = "Enter your student ID number";
		$ips = '';
		$pws = '';
		$sel = 'course';
		$sel1list = '';
		$term = '';
	}
	
	
?>
<h2>Diagnostic Setup</h2>
<form method=post action=diagsetup.php?step=2>
<?php
if (isset($_GET['id'])) {
	echo "<input type=hidden name=id value=\"{$_GET['id']}\"/>";
}
?>
<p>Diagnostic Name: 
<input type=text size=50 name="diagname" value="<?php echo $diagname; ?>"/></p>

<p>Term designator (e.g. F06):  <input type=text size=7 name="term" value="<?php echo $term; ?>"/></p>

<p>Linked with course: <select name="cid">
<?php
	$query = "SELECT imas_courses.id,imas_courses.name FROM imas_courses,imas_users,imas_teachers WHERE imas_courses.id=imas_teachers.courseid ";
	$query .= "AND imas_users.id=imas_teachers.userid AND imas_users.id=$userid";
	$result = mysql_query($query); // add or die
	while ($row = mysql_fetch_row($result)) {
		echo "<option value=\"{$row[0]}\" ";
		if ($cid==$row[0]) {
			echo "selected=1";
		}
		echo ">{$row[1]}</option>\n";
	}
?>
</select></p>

<p>Available? (Can be taken)? 
<input type=radio name="avail" value="1" <?php if ($public&1) {echo "checked=1";} ?> /> Yes 
<input type=radio name="avail" value="0" <?php if (!($public&1)) {echo "checked=1";} ?> /> No 
</p>
<p>Include in public listing? 
<input type=radio name="public" value="1" <?php if ($public&2) {echo "checked=1";} ?> /> Yes 
<input type=radio name="public" value="0" <?php if (!($public&2)) {echo "checked=1";} ?> /> No 
</p>
<p>Allow reentry (continuation of test at later date)? 
<input type=radio name="reentry" value="1" <?php if ($public&4) {echo "checked=1";} ?> /> Yes 
<input type=radio name="reentry" value="0" <?php if (!($public&4)) {echo "checked=1";} ?> /> No 
</p>

<p>Unique ID prompt: <input type=text size=60 name="idprompt" value="<?php echo $idprompt; ?>" />

<p>
Allow access without password from computer with these IP addresses.  Use * for wildcard, e.g. 134.39.*<br/>  
Enter IP address: <input type=text id="ipin" onkeypress="return onenter(event,'ipin','ipout')"><input type=button value="Add" onclick="additem('ipin','ipout')"/>
<table><tbody id="ipout"><?php
if (trim($ips)!='') {
$ips= explode(',',$ips);
for ($i=0;$i<count($ips);$i++) {
	echo "<tr id=\"tripout-$i\"><td><input type=hidden id=\"ipout-$i\" name=\"ipout-$i\" value=\"{$ips[$i]}\">{$ips[$i]}</td>";
	echo "<td><a href='#' onclick=\"removeitem('ipout-$i','ipout')\">Remove</a> ";
	echo "<a href='#' onclick=\"moveitemup('ipout-$i','ipout')\">Move up</a> ";
	echo "<a href='#' onclick=\"moveitemdown('ipout-$i','ipout')\">Move down</a> ";
	echo "</td></tr>";
}
}
?></tbody></table>
<?php 
if (is_array($ips)) {
	echo "<script> cnt['ipout'] = ".count($ips).";</script>";
} else {
	echo "<script> cnt['ipout'] = 0;</script>";
}
?>
</p>


<p>From other computers, a password will be required to access the diagnostic.<br/>  
Enter Password: <input type=text id="pwin"  onkeypress="return onenter(event,'pwin','pwout')"><input type=button value="Add" onclick="additem('pwin','pwout')"/>
<table><tbody id="pwout"><?php
if (trim($pws)!='') {
$pws= explode(',',$pws);
for ($i=0;$i<count($pws);$i++) {
	echo "<tr id=\"trpwout-$i\"><td><input type=hidden id=\"pwout-$i\" name=\"pwout-$i\" value=\"{$pws[$i]}\">{$pws[$i]}</td>";
	echo "<td><a href='#' onclick=\"removeitem('pwout-$i','pwout')\">Remove</a> ";
	echo "<a href='#' onclick=\"moveitemup('pwout-$i','pwout')\">Move up</a> ";
	echo "<a href='#' onclick=\"moveitemdown('pwout-$i','pwout')\">Move down</a> ";
	echo "</td></tr>";
}
}
?></tbody></table>
<?php 
if (is_array($pws)) {
	echo "<script> cnt['pwout'] = ".count($ips).";</script>";
} else {
	echo "<script> cnt['pwout'] = 0;</script>";
}
?>
</p>

<h4>First-level selector - selects assessment to be delivered</h4>
<p>Selector name:  <input name="sel" type=text value="<?php echo $sel; ?>"/> "Please select your _______"</p>
<p>Enter new selector option: <input type=text id="sellist"  onkeypress="return onenter(event,'sellist','selout')"> <input type=button value="Add" onclick="additem('sellist','selout')"/>
<table><tbody id="selout"><?php
if (trim($sel1list)!='') {
$sl= explode(',',$sel1list);
for ($i=0;$i<count($sl);$i++) {
	echo "<tr id=\"trselout-$i\"><td><input type=hidden id=\"selout-$i\" name=\"selout-$i\" value=\"{$sl[$i]}\">{$sl[$i]}</td>";
	echo "<td><a href='#' onclick=\"removeitem('selout-$i','selout')\">Remove</a> ";
	echo "<a href='#' onclick=\"moveitemup('selout-$i','selout')\">Move up</a> ";
	echo "<a href='#' onclick=\"moveitemdown('selout-$i','selout')\">Move down</a> ";
	echo "</td></tr>";
}
}
?></tbody></table>
<?php 
if (is_array($sl)) {
	echo "<script> cnt['selout'] = ".count($sl).";</script>";
} else {
	echo "<script> cnt['selout'] = 0;</script>";
}
?>
</p>

<p><input type=submit value="Continue Setup"/></p>
</form>
<?php
	require("../footer.php");
?>
	

