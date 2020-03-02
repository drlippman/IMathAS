<?php
//IMathAS Math Config File.  Adjust settings here!

//GROUP: DATABASE ACCESS SETTINGS
$dbserver = "localhost";
$dbname = "IMathAS";
$dbusername = "IMathAS";
$dbpassword = "mzpS8u7Cbvrdj26L";

//GROUP ERROR REPORTING
//error reporting level.  Set to 0 for production servers.
// ID: In Produktivserver nicht gesetzt, hier in init.php auf 0 gesetzt
error_reporting(E_ALL & ~E_NOTICE);

//GROUP: PATH SETTINGS
//web path to install
$imasroot = "/IMathAS";

//base site url - use when generating full URLs to site pages.
$httpmode = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
	? 'https://' : 'http://';
$GLOBALS['basesiteurl'] = $httpmode . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $imasroot;

//mimetex path
$mathimgurl = "http://www.imathas.com/cgi-bin/mimetex.cgi";

//This is used to change the session file path different than the default.
//This is usually not necessary unless your site is on a server farm, or
//you're on a shared server and want more security of session data.
//This may also be needed to allow setting the garbage collection time limit
//so that session data isn't removed after 24 minutes.
//Make sure this directory has write access by the server process.
//$sessionpath = '/tmp/persistent/imathas/sessions';

//math live chat server - comment out to not use
//Chat uses its own database tables, and draws user info from the 
//query string rather than from the IMathAS user tables, so you
//can use the chat server on a different IMathAS install
//to reduce the server load on the main install. 
//use this URL to use the local server:
$mathchaturl = "$imasroot/mathchat/index.php";


// GROUP: USER RIGHTS
// Anmelden als Gast guest ohne Passwort ermöglichen (guestaccount) !!! ID !!!
$CFG['GEN']['guesttempaccts']= true;

//should non-admins be allowed to create new non-group libraries?
//on a single-school install, set to true; for larger installs that plan to
//use the instructor-groups features, set to false
$allownongrouplibs = false;

//should anyone be allowed to import/export questions and libraries from the 
//course page?  Intended for easy sharing between systems, but the course page
//is cleaner if turned off.
$allowcourseimport = false;

//allow installation of macro files by admins?  macro files contain a large
//security risk.  If you are going to have many admins, and don't trust the
//security of their passwords, you should set this to false.  Installing
//macros is equivalent in security risk to having FTP access to the IMathAS
//server.
//For single-admin systems, it is recommended you leave this as false, and
//change it when you need to install a macro file.  Do install macro files
//using the web system; a help file is automatically generated when you install
//through the system
$allowmacroinstall = true;

//template user id
//Generally not needed.  Use if you want a list of Template courses in the
//copy course items page.  Set = to a user's ID who will serve as the 
//template holder instructor.  Add that user to all courses to list as a 
//template
//$templateuser = 10;

// GROUP: ORGANIZATION SPECIFIC SETTINGS
$CFG['locale'] = "de_DE.UTF-8";

//The name for this installation.  For personalization
$installname = "IMathAS";
//email to send notices from
$sendfrom = "dahn@vcrp.de";

// GROUP: LTI
//enable lti?
$enablebasiclti = true;
$CFG['LTI']['showURLinSettings'] = true;

// GROUP: GENERAL LOOK
// !!! ID !!!
//A small logo to display on the upper right of course pages
//set = '<img src="/path/to/img.gif">' or = 'Some Text'
//Image should be about 120 x 80px
$smallheaderlogo = "";

// Themes-Liste !!! ID !!!
$CFG['CPS']['themelist'] = "default_nm.css,default.css,angelish.css,angelishmore.css,facebookish.css,highcontrast.css,highcontrast_dark.css,modern.css,netmath.css,oea.css,wamap.css";
$CFG['CPS']['themenames'] = "Standard (neu),Standard (alt),Engelhaft,Engelhafter,Facebook-Art,Hoher Kontrast,Hoher Kontrast (dunkel),Modern,NetMath,OEA,Wamap";

// GROUP: LOGIN AND HOME PAGE
//To change loginpage based on domain/url/etc, define $loginpage here

//Uncomment to change the default course theme, also used on the home & admin page:
// !!! ID !!!
$defaultcoursetheme = "wamap.css";
// Default theme for new courses !!! ID !!!
$CFG['CPS']['theme'] = array("default_nm.css",1);

//login prompts
$loginprompt = "Username";

$studentTOS="info/studentTOS.php";
//$CFG['GEN']['TOSpage']="https://netmath.vcrp.de/downloads/Systeme/NutzungsbedingungenIMathAS.html";

// Kopf der Home-Seite !!! ID !!!
$CFG['GEN']['headerinclude'] = "headercontentvcrp.php";
$CFG['GEN']['hidedefindexmenu'] = true;

// GROUP: USER REGISTRATION
// !!! ID !!!
$longloginprompt = "Geben Sie einen Benutzernamen ein. Verwenden Sie nur Buchstaben, Zahlen und das Zeichen _.";
$loginformat = '/^[\w+\-]+$/';

//require email confirmation of new users?
$emailconfirmation = true;

//use more secure password hashes? requires PHP 5.3.7+
// !!! ID !!!
$CFG['GEN']['newpasswords'] = 'only';

// !!! ID !!!
$CFG['acct']['SIDformaterror'] = "Ungültiges Format für einen Benutzernamen.";
$CFG['acct']['passwordFormaterror'] = "Ungültiges Passwortformat";
$CFG['acct']['emailFormaterror'] = "Ungültige eMail-Adresse";

// Automatisches Einschreiben in Dozenten-Cafe und Spielwiese !!! ID !!! - Einkommentieren für Produktivversion
// $CFG['GEN']['enrollonnewinstructor'] = array(15,87);


// GROUP: TESTS

//color shift icons as deadline approaches?
$colorshift = true;


// GROUP: DON'T CHANGE
















//Amazon S3 access for file upload

//$AWSkey = "";

//$AWSsecret = "";

//$AWSbucket = "";





//no need to change anything from here on
  /* Connecting, selecting database */
	try {
	 $DBH = new PDO("mysql:host=$dbserver;dbname=$dbname", $dbusername, $dbpassword);
	 $DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	 $GLOBALS["DBH"] = $DBH;
	} catch(PDOException $e) {
	 die("<p>Could not connect to database: <b>" . $e->getMessage() . "</b></p></div></body></html>");
	}
	$DBH->query("set session sql_mode=''");

	  unset($dbserver);
	  unset($dbusername);
	  unset($dbpassword);

?>