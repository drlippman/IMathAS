<?php

	require("init_without_validate.php");
	require_once(__DIR__.'/includes/newusercommon.php');
	$pagetitle = "New instructor account request";
	$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages.css\" type=\"text/css\">\n";
	$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/jquery.validate.min.js"></script>';
	if (isset($CFG['locale'])) {
	  $placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/jqvalidatei18n/messages_'.substr($CFG['locale'],0,2).'.min.js"></script>';
	}
	$nologo = true;
	require("header.php");
	$pagetitle = "Instructor Account Request";
	require("infoheader.php");
	$required = array('SID','firstname','lastname','email','pw1','pw2','school','phone','agree');

	if (isset($_POST['firstname'])) {
		$error = '';
		if (!isset($_POST['agree'])) {
			$error .= "<p>You must agree to the Terms and Conditions to set up an account</p>";
		}

		$error .= checkNewUserValidation($required);

		if ($error != '') {
			echo $error;
		} else {
			if (isset($CFG['GEN']['homelayout'])) {
				$homelayout = $CFG['GEN']['homelayout'];
			} else {
				$homelayout = '|0,1,2||0,1';
			}

			if (isset($CFG['GEN']['newpasswords'])) {
				require_once("./includes/password.php");
				$md5pw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
			} else {
				$md5pw = md5($_POST['pw1']);
			}
			//DB $query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, homelayout) ";
			//DB $query .= "VALUES ('{$_POST['username']}','$md5pw',0,'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['email']}','$homelayout');";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $newuserid = mysql_insert_id();
			$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, homelayout) ";
			$query .= "VALUES (:SID, :password, :rights, :FirstName, :LastName, :email, :homelayout);";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw, ':rights'=>12, ':FirstName'=>$_POST['firstname'], ':LastName'=>$_POST['lastname'], ':email'=>$_POST['email'], ':homelayout'=>$homelayout));
			$newuserid = $DBH->lastInsertId();
			if (isset($CFG['GEN']['enrollonnewinstructor'])) {
				$valbits = array();
				foreach ($CFG['GEN']['enrollonnewinstructor'] as $ncid) {
				  $ncid = intval($ncid);
					$valbits[] = "($newuserid,$ncid)";
				}
				//DB $query = "INSERT INTO imas_students (userid,courseid) VALUES ".implode(',',$valbits);
				//DB mysql_query($query) or die("Query failed : " . mysql_error());

				$stm = $DBH->query("INSERT INTO imas_students (userid,courseid) VALUES ".implode(',',$valbits)); //known INTs - safe
			}
			$subject = "New Instructor Account Request";
			$message = "Name: {$_POST['firstname']} {$_POST['lastname']} <br/>\n";
			$message .= "Email: {$_POST['email']} <br/>\n";
			$message .= "School: {$_POST['school']} <br/>\n";
			$message .= "Phone: {$_POST['phone']} <br/>\n";
			$message .= "Username: {$_POST['SID']} <br/>\n";
			
			require_once("./includes/email.php");
			
			send_email($sendfrom, $sendfrom, $subject, $message, array(), array(), 10); 

			$now = time();
			//DB $query = "INSERT INTO imas_log (time, log) VALUES ($now, 'New Instructor Request: $newuserid:: School: {$_POST['school']} <br/> Phone: {$_POST['phone']} <br/>')";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("INSERT INTO imas_log (time, log) VALUES (:now, :log)");
			$stm->execute(array(':now'=>$now, ':log'=>"New Instructor Request: $newuserid:: School: {$_POST['school']} <br/> Phone: {$_POST['phone']} <br/>"));

			$reqdata = array('reqmade'=>$now, 'school'=>$_POST['school'], 'phone'=>$_POST['phone']);
			$stm = $DBH->prepare("INSERT INTO imas_instr_acct_reqs (userid,status,reqdate,reqdata) VALUES (?,0,?,?)");
			$stm->execute(array($newuserid, $now, json_encode($reqdata)));

			$message = "<p>Your new account request has been sent.</p>  ";
			$message .= "<p>This request is processed by hand, so please be patient.</p>";
			
			send_email($_POST['email'], $sendfrom, $subject, $message, array(), array(), 10); 

			echo $message;
			require("footer.php");
			exit;

		}
	}
	if (isset($_POST['firstname'])) {$firstname=Sanitize::encodeStringForDisplay($_POST['firstname']);} else {$firstname='';}
	if (isset($_POST['lastname'])) {$lastname=Sanitize::encodeStringForDisplay($_POST['lastname']);} else {$lastname='';}
	if (isset($_POST['email'])) {$email=Sanitize::encodeStringForDisplay($_POST['email']);} else {$email='';}
	if (isset($_POST['phone'])) {$phone=Sanitize::encodeStringForDisplay($_POST['phone']);} else {$phone='';}
	if (isset($_POST['school'])) {$school=Sanitize::encodeStringForDisplay($_POST['school']);} else {$school='';}
	if (isset($_POST['SID'])) {$username=Sanitize::encodeStringForDisplay($_POST['SID']);} else {$username='';}

	echo "<h3>New Instructor Account Request</h3>\n";
	echo "<form method=post id=newinstrform class=limitaftervalidate action=\"newinstructor.php\">\n";
	echo "<span class=form>First Name</span><span class=formright><input type=text id=firstname name=firstname value=\"$firstname\" size=40></span><br class=form />\n";
	echo "<span class=form>Last Name</span><span class=formright><input type=text id=lastname name=lastname value=\"$lastname\" size=40></span><br class=form />\n";
	echo "<span class=form>Email Address</span><span class=formright><input type=text id=email name=email value=\"$email\" size=40></span><br class=form />\n";
	echo "<span class=form>Phone Number</span><span class=formright><input type=text id=phone name=phone value=\"$phone\" size=40></span><br class=form />\n";
	echo "<span class=form>School/College</span><span class=formright><input type=text id=school name=school value=\"$school\" size=40></span><br class=form />\n";
	echo "<span class=form>Requested Username</span><span class=formright><input type=text id=SID name=SID value=\"$username\" size=40></span><br class=form />\n";
	echo "<span class=form>Requested Password</span><span class=formright><input type=password id=pw1 name=pw1 size=40></span><br class=form />\n";
	echo "<span class=form>Retype Password</span><span class=formright><input type=password id=pw2 name=pw2 size=40></span><br class=form />\n";
	echo "<span class=form>I have read and agree to the Terms of Use (below)</span><span class=formright><input type=checkbox id=agree name=agree></span><br class=form />\n";
	echo "<div class=submit><input type=submit value=\"Request Account\"></div>\n";
	echo "</form>\n";
	echo "<h4>Terms of Use</h4>\n";
	echo "<p><em>This software is made available with <strong>no warranty</strong> and <strong>no guarantees</strong>.  The ";
	echo "server or software might crash or mysteriously lose all your data.  Your account or this service may be terminated without warning.  ";
	echo "No official support is provided. </em></p>\n";
	echo "<p><em>Copyrighted materials should not be posted or used in questions without the permission of the copyright owner.  You shall be solely ";
	echo "responsible for your own user created content and the consequences of posting or publishing them.  This site expressly disclaims any and all liability in ";
	echo "connection with user created content.</em></p>";
	showNewUserValidation('newinstrform',$required);
	require("footer.php");
?>
