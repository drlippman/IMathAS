<?php
if (!isset($imasroot)) { //don't allow direct access to loginpage.php
	header("Location: index.php");
	exit;
}
//any extra CSS, javascript, etc needed for login page
	$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages.css\" type=\"text/css\" />\n";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/jstz_min.js\" ></script>";
	$nologo = true;
	$loadinginfoheader = true; // ID: Kein header über infoheader.php
	require("header.php");
	if (!empty($_SERVER['QUERY_STRING'])) {
		 $querys = '?'.$_SERVER['QUERY_STRING'];
	 } else {
		 $querys = '';
	 }
	 $loginFormAction = $GLOBALS['basesiteurl'] . substr($_SERVER['SCRIPT_NAME'],strlen($imasroot)) . Sanitize::encodeStringForDisplay($querys);
	 if (!empty($_SESSION['challenge'])) {
		 $challenge = $_SESSION['challenge'];
	 } else {
		 //use of microtime guarantees no challenge used twice
		 $challenge = base64_encode(microtime() . rand(0,9999));
		 $_SESSION['challenge'] = $challenge;
	 }
	 $pagetitle = "Die Mathe-Plattform des <a style='color: white' href='https://www.vcrp.de' target='_blank'>VCRP</a>";
	 include("infoheader.php");

?>



<div id="loginbox">
<form method="post" action="<?php echo $loginFormAction;?>">
<?php
	if ($haslogin) {
		if ($badsession) {
			if (isset($_COOKIE[session_name()])) {
				echo _('Problems with session storage');
			}  else {
				echo '<p>', _('Unable to establish a session.  Check that your browser is set to allow session cookies'), '</p>';
			}
		} else {
			echo "<p>", _("Login Error.  Try Again"), "</p>\n";
		}
	}
?>
<b>Login</b>

<div><noscript>JavaScript is not enabled.  JavaScript is required for <?php echo $installname; ?>.  Please enable JavaScript and reload this page</noscript></div>

<table>
<tr><td><label for="username"><?php echo $loginprompt;?></label>:</td><td><input type="text" size="15" id="username" name="username" /></td></tr>
<tr><td><label for="password"><?php echo _('Password'); ?></label>:</td><td><input type="password" size="15" id="password" name="password" /></td></tr>
</table>
<div class=textright><input type="submit" value="Login"></div>

<div class="textright"><a href="<?php echo $imasroot; ?>/forms.php?action=newuser"><?php echo 'Als neuer Student registrieren';?></a></br>
<a href="<?php echo $imasroot; ?>/newinstructor.php"><?php echo 'Als neuer Dozent registrieren';?></a>
</div>
<div class="textright"><a href="<?php echo $imasroot; ?>/forms.php?action=resetpw"><?php echo _('Forgot Password');?></a><br/>
<a href="<?php echo $imasroot; ?>/forms.php?action=lookupusername"><?php echo _('Forgot Username');?></a></div>

<input type="hidden" id="tzoffset" name="tzoffset" value="">
<input type="hidden" id="tzname" name="tzname" value="">
<input type="hidden" id="challenge" name="challenge" value="<?php echo $challenge; ?>" />
<script type="text/javascript">
$(function() {
        var thedate = new Date();
        document.getElementById("tzoffset").value = thedate.getTimezoneOffset();
        var tz = jstz.determine();
        document.getElementById("tzname").value = tz.name();
        $("#username").focus();
});
</script>

</form>
</div>
<div class="text">
<p><?php printf('%s ist ein webbasiertes System für Mathematikaufgaben und Kursverwaltung das hier vom <a href="https://www.vcrp.de" target="_blank">Virtuellen Campus Rheinland-Pfalz (VCRP)</a> betrieben wird.', $installname); ?></p>
 <img style="float: left; margin-right: 20px;" src="<?php echo $imasroot; ?>/img/screens.jpg" alt="Computer screens"/>

<p>Das System wurde für verschiedene Formen von umfangreichen Mathematikaufgaben entworfen. Studenten können durch generierte Tests und automatische Auswertung direkt Rückmeldung bekommen.</p>

<p>Wenn Sie bereits ein Benutzerkonto haben, können Sie sich rechts anmelden.</p>
<p><?php printf('Wenn Sie sich noch nicht registriert haben, können Sie sich <a href="%s/forms.php?action=newuser">als neuer Student registrieren</a>', $imasroot);?>. Für den Kurszugang erhalten Sie von Ihrem Dozenten eine Kurs-ID und ggf. ein Passwort. Viele Kurse sind nur über das Lernmanagementsystem der Hochschulen zugänglich. Für diese Kurse brauchen Sie sich hier nicht zu registrieren.</p>

<p>In öffentliche Kurse können Sie sich auch mit <em>guest</em> ohne Passwort einschreiben. Gast-Kennungen werden regelmäßig gelöscht.</p>

<p><?php printf('Wenn Sie Dozent sind, so können Sie <a href="%s/newinstructor.php"><b>hier eine Dozentenkennung beantragen</b></a>. Mit einer Dozentenkennung erhalten Sie Zugang zur hochschulübergreifenden Aufgabenbibliothek und können eigene Aufgaben und Tests erstellen und in das Lernmanagementsystem Ihrer Hochschule einbinden.', $imasroot)?></p>

<p><?php echo 'Hilfe:'; ?>
<ul>
<li><a href="<?php echo $imasroot;?>/info/enteringanswers.php"><?php echo 'Hilfe für Studierende für die Eingabe von Antworten (in Englisch)'; ?></a></li>
<li><a href="/IMathAS/embedq.php?theme=netmath&id=7573">Übung zur Eingabe von Antworten</a></li>
<li><a href="<?php echo $imasroot;?>/docs/docs.php"><?php echo 'Dokumentation für Dozenten (in Englisch)'; ?></a></li>
</ul>

<br class=clear>
<p class="textright"><?php printf('%s wird entwickelt von %s', $installname, ' <a href="http://www.imathas.com">IMathAS</a> &copy; 2006-2013 David Lippman');  ?><br>
<a href="https://netmath.vcrp.de/downloads/Systeme/Impressum.html" target="_blank">Impressum</a> |

<a href="https://netmath.vcrp.de/downloads/Systeme/NutzungsbedingungenIMathAS.html" target="_blank">Nutzungsbedingungen</a> |

<a href="https://netmath.vcrp.de/downloads/Systeme/privacyIMathAS.html" target="_blank">Datenschutzerklärung</a>

 </p>
</div>
<?php
	require("footer.php");
?>
