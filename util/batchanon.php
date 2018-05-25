<?php
//Batch anonymize users
//IMathAS (c) 2018 David Lippman

require("../init.php");

if ($myrights < 100) { echo "You don't have the authority for this action"; break;}

require("../header.php");

$curBreadcrumb = "$breadcrumbbase <a href=\"$imasroot/admin/admin2.php\">Admin</a>\n";
$curBreadcrumb .= ' &gt; <a href="utils.php">Utils</a>';

echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Batch Anonymize Users</div>';
if (isset($_POST['anontype']) && is_numeric($_POST['months'])) {
	$months = Sanitize::onlyFloat($_POST['months']);
	if ($months>0) {
		$newemail = Sanitize::emailAddress($_POST['anonemail']);
		
		if ($_POST['anontype']=='full') {
			
			$query = "UPDATE imas_users SET SID=CONCAT('anon_',MD5(CONCAT(SID, UUID()))),FirstName=SID,LastName=SID,email=?,password=SID,msgnotify=0 "; 
		} else {
			$query = "UPDATE imas_users SET email=?,SID=CONCAT('anon_',MD5(CONCAT(SID, UUID()))),password=SID,msgnotify=0 "; 
		}
		$query .= "WHERE lastaccess<? ";
		if ($_POST['usertype']=='stu') {
			$query .= "AND rights<11 ";
		}
		$stm = $DBH->prepare($query);
		$oldtime = time() - $months*31*24*60*60;
		$stm->execute(array($newemail, $oldtime));
		$n = $stm->rowCount();
		echo '<p>'.Sanitize::onlyInt($n).' accounts anonymized (or re-anonymized)</p>';
		echo '<p><a href="utils.php">Done</a></p>';
		require("../footer.php");
		exit;
	}
} 

?>
<h1>Batch Anonymize Users</h1>

<form method="post" action="batchanon.php">

<p>Who do you want to anonymize?</p>
<p><select name="usertype">
	<option value="stu" selected>Students</option>
	<option value="all">All users</option>
	</select>
	who have not logged in for <input type="text" name="months" value="24" size="3" /> months.
</p>
<p>What type of anonymization would you like to do?</p>
<p><input type=radio id="partial" name=anontype value="partial" checked> 
   <label for="partial">Replace the user's email, username, and password with random values.
   This will make their account appear to be deleted and un-recoverable using the 
   "forgot username" and "forgot password", but the users's name will remain unchanged.</label><br/>
   <input type=radio id="full" name=anontype value="full"> 
   <label for="full">Replace the user's email, username, password, <em>and name</em> 
   with random values.</label>
</p>
<p>Replace email with: <input type=text name=anonemail value="none@none.com" /></p>	
<p>Anonymization does NOT delete the user's courses or course work</p>

<button type="submit" onclick="return confirm('Are you SURE you want to anonymize all these users?')">Anonymize</button>
<button type="button" onclick="window.location='utils.php'">Nevermind</button>

</form>

<?php
require("../footer.php");
	