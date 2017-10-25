<?php
//IMathAS Math Config File.  Adjust settings here!

//database access settings
$dbserver = "localhost";
$dbname = "imasdb";
$dbusername = "root";
$dbpassword = "";

//error reporting level.  Set to 0 for production servers.
error_reporting(E_ALL & ~E_NOTICE);

//install name
$installname = "IMathAS";

//login prompts
$loginprompt = "Username";
$longloginprompt = "Enter a username.  Use only numbers, letters, or the _ character.";
$loginformat = '/^\w+$/';

//require email confirmation of new users?
$emailconfirmation = false;

//email to send notices from
$sendfrom = "imathas@yoursite.edu";

//color shift icons as deadline approaches?
$colorshift = true;

//path settings
//web path to install
$imasroot = "";

//mimetex path
$mathimgurl = "http://www.imathas.com/cgi-bin/mimetex.cgi";

//enable lti?
$enablebasiclti = true;

//allow nongroup libs?
$allownongrouplibs = false;

//allow course import of questions?
$allowcourseimport = false;

//allow macro install?
$allowmacroinstall = true;

//use more secure password hashes? requires PHP 5.3.7+
$CFG['GEN']['newpasswords'] = 'only';

//session path 
//$sessionpath = "";

//Amazon S3 access for file upload 

//$AWSkey = "";

//$AWSsecret = "";

//$AWSbucket = "";


//Uncomment to change the default course theme, also used on the home & admin page:
//$defaultcoursetheme = "default.css"

//To change loginpage based on domain/url/etc, define $loginpage here

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