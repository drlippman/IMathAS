<?php

	require("init_without_validate.php");
	require_once(__DIR__.'/includes/newusercommon.php');
	$pagetitle = "Antrag für eine Dozentenkennung";
	$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages.css\" type=\"text/css\">\n";
	$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/jquery.validate.min.js"></script>';
	$nologo = true;
	require("header.php");
	$pagetitle = "Antrag für eine Dozentenkennung";
	require("infoheader.php");
	$extrarequired = array('school','phone','agree');

	if (isset($_POST['firstname'])) {
		$error = '';
		if (!isset($_POST['agree'])) {
			$error .= "<p>Für die Erteilung einer Dozentenkennung müssen Sie den Nutzungsbedingungen und der Datenschutzerklärung zustimmen</p>";
		}

		$error .= checkNewUserValidation($extrarequired);

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
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: $installname <$sendfrom>\r\n";
			$subject = "New Instructor Account Request";
			$message = "Name: {$_POST['firstname']} {$_POST['lastname']} <br/>\n";
			$message .= "Email: {$_POST['email']} <br/>\n";
			$message .= "School: {$_POST['school']} <br/>\n";
			$message .= "Phone: {$_POST['phone']} <br/>\n";
			$message .= "Username: {$_POST['SID']} <br/>\n";
			mail($sendfrom,$subject,$message,$headers);

			$now = time();
			//DB $query = "INSERT INTO imas_log (time, log) VALUES ($now, 'New Instructor Request: $newuserid:: School: {$_POST['school']} <br/> Phone: {$_POST['phone']} <br/>')";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("INSERT INTO imas_log (time, log) VALUES (:now, :log)");
			$stm->execute(array(':now'=>$now, ':log'=>"New Instructor Request: $newuserid:: School: {$_POST['school']} <br/> Phone: {$_POST['phone']} <br/>"));

			$reqdata = array('reqmade'=>$now, 'school'=>$_POST['school'], 'phone'=>$_POST['phone']);
			$stm = $DBH->prepare("INSERT INTO imas_instr_acct_reqs (userid,status,reqdate,reqdata) VALUES (?,0,?,?)");
			$stm->execute(array($newuserid, $now, json_encode($reqdata)));

			$message = "<p>Ihr Antrag für eine Dozentenkennung wurde abgeschickt.</p>  ";
			$message .= "<p>Ihr Antrag wird geprüft; bitte haben Sie etwas Geduld.</p>";
			mail($_POST['email'],$subject,$message,$headers);

			echo $message;
			require("footer.php");
			exit;

		}
	}
	if (isset($_POST['firstname'])) {$firstname=Sanitize::encodeStringForDisplay($_POST['firstname']);} else {$firstname='';}
	if (isset($_POST['lastname'])) {$lasname=Sanitize::encodeStringForDisplay($_POST['lastname']);} else {$lastname='';}
	if (isset($_POST['email'])) {$email=Sanitize::encodeStringForDisplay($_POST['email']);} else {$email='';}
	if (isset($_POST['phone'])) {$phone=Sanitize::encodeStringForDisplay($_POST['phone']);} else {$phone='';}
	if (isset($_POST['school'])) {$school=Sanitize::encodeStringForDisplay($_POST['school']);} else {$school='';}
	if (isset($_POST['SID'])) {$username=Sanitize::encodeStringForDisplay($_POST['SID']);} else {$username='';}

	echo "<h3>Anforderung einer Dozentenkennung</h3>\n";
	echo "<form method=post id=newinstrform class=limitaftervalidate action=\"newinstructor.php\">\n";
	echo "<span class=form>Vorname</span><span class=formright><input type=text id=firstname name=firstname value=\"$firstname\" size=40></span><br class=form />\n";
	echo "<span class=form>Nachname</span><span class=formright><input type=text id=lastname name=lastname value=\"$lastname\" size=40></span><br class=form />\n";
	echo "<span class=form>Email-Addresse</span><span class=formright><input type=text id=email name=email value=\"$email\" size=40></span><br class=form />\n";
	echo "<span class=form>Telefonnr.</span><span class=formright><input type=text id=phone name=phone value=\"$phone\" size=40></span><br class=form />\n";
	echo "<span class=form>Hochschule</span><span class=formright><input type=text id=school name=school value=\"$school\" size=40></span><br class=form />\n";
	echo "<span class=form>Gewünschter Benutzername</span><span class=formright><input type=text id=SID name=SID value=\"$username\" size=40></span><br class=form />\n";
	echo "<span class=form>Gewünschtes Passwort</span><span class=formright><input type=password id=pw1 name=pw1 size=40></span><br class=form />\n";
	echo "<span class=form>Passwort wiederholen</span><span class=formright><input type=password id=pw2 name=pw2 size=40></span><br class=form />\n";
	echo "<span class=form>Die <a href='https://netmath.vcrp.de/downloads/Systeme/NutzungsbedingungenIMathAS.html' target='_blank'>Nutzungsbedingungen</a> und die <a href='https://netmath.vcrp.de/downloads/Systeme/privacyIMathAS.html' target='_blank'>Datenschutzerklärung</a> habe ich gelesen und stimme ihnen zu.</span><span class=formright><input type=checkbox id=agree name=agree></span><br class=form />\n";
	echo "<div class=submit><input type=submit value=\"Kennung beantragen\"></div>\n";
	echo "</form>\n";
	showNewUserValidation('newinstrform',$extrarequired);
	require("footer.php");
?>
