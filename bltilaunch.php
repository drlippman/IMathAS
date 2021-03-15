<?php
//IMathAS:  BasicLTI Producer Code
//(c) David Lippman 2009
//
//
//launches with 5 types of key/secrets
//   username     : of a user with rights 11 or 76 (has to manually entered into database, along with a plaintext password as the secret)
//   aid_###      : launches assessment with given id.  secret is course's ltisecret
//   cid_###      : launches course with given id.  secret is course's ltisecret
//   placein_###  : launches a content linkage selector. In Canvas, uses resource selection return.
//		    in others, links to resource id (which is lost on LMS course copy)
//   sso_username : launches single signon to home page using given userid w/ rights 11 or 76.
//                  secret value stored in DB password field.  Currently must be manually editted in DB
//   username	  : like sso_username, but triggers course/item connection
//   aid_, cid_, and sso_ types accept additional _0 or _1  :  0 is default, and links LMS account with a local account
//                                      1 using LMS for validation, does not ask for local account info
//
//  for sso_ and username types, if associated user has rights 11, instructors must link accounts;
//     rights 76 allows TC to create instructor accounts
//
//  LMS MUST provide, in addition to key and secret:
//    user_id
//
//  LMS MAY provide:
//    lis_person_name_given
//    lis_person_name_family
//    lis_person_contact_email_primary

header('P3P: CP="ALL CUR ADM OUR"');
$init_skip_csrfp = true;
$init_session_start = true;
include("init_without_validate.php");
unset($init_skip_csrfp);

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['bltilaunch'])) {
	require($CFG['hooks']['bltilaunch']);
}

$curdir = rtrim(dirname(__FILE__), '/\\');


 if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
	 $urlmode = 'https://';
 } else if ($_SERVER['HTTP_HOST'] != 'localhost') {
 	 $urlmode = 'http://';
	 $errormsg = _('This launch was made insecurely (using http instead of https). ');
 	 $errormsg .= sprintf(_('%s requires secure launches to protect student data. '),$installname);
	 $ltirole = strtolower($_REQUEST['roles']);
	 if (strpos($ltirole,'instructor')!== false || strpos($ltirole,'administrator')!== false || strpos($ltirole,'contentdeveloper')!== false) {
	 	 $errormsg .= _('Please update the links or tool in the LMS to use https (aka SSL). ');
	 	 if ($_REQUEST['tool_consumer_info_product_family_code'] == 'moodle') {
	 	 	 $errormsg .= _('In Moodle, you can do this by clicking the "Edit preconfigured tool" icon, scrolling down to Privacy, and clicking the Force SSL checkbox. ');
	 	 	 $errormsg .= _('If you do not have access to do this, you might need to ask your LMS administrator to enable the Force SSL option. ');
	 	 }
	 }
 	 reporterror($errormsg);
 }
if ($enablebasiclti!=true) {
	echo _("BasicLTI not enabled");
	exit;
}

function reporterror($err) {
	global $imasroot;
	require("header.php");
	printf('<p>%s</p>', Sanitize::encodeStringForDisplay($err));
	require("footer.php");
	exit;
}

function generateToolState() {
	if (function_exists("random_bytes")) {
		$token = bin2hex(random_bytes(64));
	} elseif (function_exists("openssl_random_pseudo_bytes")) {
		$token = bin2hex(openssl_random_pseudo_bytes(64));
	} else {
		$token = '';
		for ($i = 0; $i < 64; ++$i) {
			$r = mt_rand (0, 35);
			if ($r < 26) {
				$c = chr(ord('a') + $r);
			} else {
				$c = chr(ord('0') + $r - 26);
			}
			$token .= $c;
		}
	}
	return substr($token, 0, 64);
}
function do112relaunch() {
	if (!isset($_REQUEST['platform_state'])) {
		reporterror(_("Missing platform_state"));
	}

	$_SESSION['lti_tool_state'] = generateToolState();
	echo '<html><body><form id="theform" method="POST" action="'.Sanitize::encodeStringForDisplay($_REQUEST['relaunch_url']).'">
		<input type=hidden name="tool_state" value="'.Sanitize::encodeUrlParam($_SESSION['lti_tool_state']).'">
		<input type=hidden name="platform_state" value="'.Sanitize::encodeUrlParam($_REQUEST['platform_state']).'">
		</form><script>document.getElementById("theform").submit();</script></body></html>';
	exit;
}

function verify112relaunch() {
	if ($_REQUEST['tool_state'] != $_SESSION['lti_tool_state']) {
		reporterror(_("Invalid tool_state"));
		exit;
	}
}

//start session - session_start already called in init
$sessionid = session_id();
$atstarthasltiuserid = isset($_SESSION['ltiuserid']);
$askforuserinfo = false;

$now = time();

//use new behavior for place_aid requests that don't come from a placein_###_# key
if (
    (isset($_SESSION['place_aid']) && isset($_SESSION['lti_keytype']) && $_SESSION['lti_keytype']=='cc-a' && !isset($_REQUEST['oauth_consumer_key']))
    ||
    (isset($_REQUEST['custom_place_aid']) && isset($_REQUEST['oauth_consumer_key']) && substr($_REQUEST['oauth_consumer_key'],0,7)!='placein')
    ) {

//if (isset($_SESSION['place_aid']) || isset($_REQUEST['custom_place_aid'])) {
/*use new behavior for place_aid requests */


//check to see if accessiblity page is posting back
if (isset($_GET['launch'])) {
	if (empty($_SESSION['userid'])) {
		reporterror(_("No authorized session exists. This is most likely caused by your browser blocking third-party cookies.  Please adjust your browser settings and try again. If you are using Safari, you may need to disable Prevent Cross-Site Tracking."));
	}
	$userid = $_SESSION['userid'];
	if (empty($_POST['tzname']) && $_POST['tzoffset']=='') {
		echo _('Uh oh, something went wrong.  Please go back and try again');
		exit;
	}
	if (isset($_POST['tzname'])) {
		$_SESSION['logintzname'] = $_POST['tzname'];
	}

	require_once("$curdir/includes/userprefs.php");
	generateuserprefs();

	$stm = $DBH->prepare('UPDATE imas_users SET lastaccess=:lastaccess WHERE id=:id');
	$stm->execute(array(':lastaccess'=>$now, ':id'=>$userid));

	if (isset($_POST['tzname'])) {
		$tzname = $_POST['tzname'];
	} else {
		$tzname = '';
	}
	$_SESSION['tzoffset'] = $_POST['tzoffset'];
    $_SESSION['tzname'] = $tzname;
    if (isset($CFG['static_server']) && !empty($_POST['static_check'])) {
        $_SESSION['static_ok'] = 1;
    }

	$keyparts = explode('_',$_SESSION['ltikey']);
	if ($_SESSION['ltiitemtype']==0) { //is aid
		$aid = $_SESSION['ltiitemid'];
		$stm = $DBH->prepare('SELECT courseid,ver FROM imas_assessments WHERE id=:aid');
		$stm->execute(array(':aid'=>$aid));
		list($cid,$aver) = $stm->fetch(PDO::FETCH_NUM);
		if ($cid===false) {
			$diaginfo = "(Debug info: 1-$aid)";
			reporterror(_("This assignment does not appear to exist anymore.")." $diaginfo");
		}
		if ($_SESSION['ltirole'] == 'learner') {
			$stm = $DBH->prepare('INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES (:userid,:courseid,\'assesslti\',:typeid,:viewtime,\'\')');
			$stm->execute(array(':userid'=>$userid,':courseid'=>$cid,':typeid'=>$aid,':viewtime'=>$now));
		}
		if ($aver > 1) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assess2/?cid=$cid&aid=$aid");
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assessment/showtest.php?cid=$cid&id=$aid");
		}
	} else if ($_SESSION['ltiitemtype']==1) { //is cid
		$cid = $_SESSION['ltiitemid'];
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid");
	} else if ($_SESSION['ltiitemtype']==2) {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/index.php");
	} else if ($_SESSION['ltiitemtype']==3) {
		$cid = $_SESSION['ltiitemid'][2];
		$folder = $_SESSION['ltiitemid'][1];
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid&folder=".$folder);
	} else { //will only be instructors hitting this option
		header('Location: ' . $GLOBALS['basesiteurl'] . "/ltihome.php");
	}
	exit;
} else if (isset($_GET['accessibility'])) {
	if (empty($_SESSION['userid'])) {
		reporterror(_("No authorized session exists. This is most likely caused by your browser blocking third-party cookies.  Please adjust your browser settings and try again. If you are using Safari, you may need to disable Prevent Cross-Site Tracking."));
	}
	$userid = $_SESSION['userid'];

	//time to output a postback to capture tzname
	$pref = 0;
	$flexwidth = true;
	$nologo = true;
	$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/jstz_min.js\" ></script>";
	require("header.php");
	echo "<h3>Connecting to $installname</h3>";
	echo "<form id=\"postbackform\" method=\"post\" action=\"" . $imasroot . "/bltilaunch.php?launch=true\" ";
	if ($_SESSION['ltiitemtype']==0 && $_SESSION['ltitlwrds'] != '') {
		echo "onsubmit='return confirm(\"This assessment has a time limit of "
            .Sanitize::encodeStringForJavascript($_SESSION['ltitlwrds'])
            .".  Click OK to start or continue working on the assessment.\")' >";
		echo "<p class=noticetext>This assessment has a time limit of ".Sanitize::encodeStringForDisplay($_SESSION['ltitlwrds']).".</p>";
		echo '<div class="textright"><input type="submit" value="Continue" /></div>';

		if ($_SESSION['lticanuselatepass']) {
			echo "<p><a href=\"$imasroot/course/redeemlatepass.php?from=ltitimelimit&cid=".Sanitize::encodeUrlParam($_SESSION['ltiitemcid'])."&aid=".Sanitize::encodeUrlParam($_SESSION['ltiitemid'])."\">", _('Use LatePass'), "</a></p>";
		}

	} else {
		echo ">";
	}
	?>
	<div id="settings"><noscript>JavaScript is not enabled.  JavaScript is required for <?php echo $installname; ?>.
	Please enable JavaScript and reload this page</noscript></div>
	<input type="hidden" id="tzoffset" name="tzoffset" value="" />
	<input type="hidden" id="tzname" name="tzname" value="">
	<script type="text/javascript">
		$(function() {
			var thedate = new Date();
			document.getElementById("tzoffset").value = thedate.getTimezoneOffset();
			var tz = jstz.determine();
			document.getElementById("tzname").value = tz.name();
			<?php
			if ($_SESSION['ltiitemtype']!=0 || $_SESSION['ltitlwrds'] == '') {
				//auto submit the form
				echo 'document.getElementById("postbackform").submit();';
			}
			?>
		});
	</script>
	</form>
	<?php
	require("footer.php");
	exit;

} else if (isset($_GET['userinfo']) && isset($_SESSION['ltiuserid'])) {
	//check to see if new LTI user is posting back user info
	$ltiuserid = $_SESSION['ltiuserid'];
	$ltiorg = $_SESSION['ltiorg'];
	$ltirole = $_SESSION['ltirole'];
	$keyparts = explode('_',$_SESSION['ltikey']);
	$name_only = false;
	if (count($keyparts)==1 && $ltirole=='learner') {
		$name_only = true;
	} else if (count($keyparts)>2 && $keyparts[2]==1 && $ltirole=='learner') {
		$name_only = true;
	}
	if ($ltirole=='learner' || $_SESSION['lti_keyrights']==76 || $_SESSION['lti_keyrights']==77) {
		$allow_acctcreation = true;
	} else {
		$allow_acctcreation = false;
	}
	if ($_GET['userinfo']=='set') {
		if (isset($CFG['GEN']['newpasswords'])) {
			require_once("includes/password.php");
		}
		//check input
		$infoerr = '';
		unset($userid);
		if ($name_only) {
			if (empty($_POST['firstname']) || empty($_POST['lastname'])) {
				$infoerr = 'Please provide your name';
			}
			$_POST['email'] = 'none@none.com';
			$msgnot = 0;
		} else {
			if (!empty($_POST['curSID']) && !empty($_POST['curPW'])) {
				//provided current SID/PW pair
				$stm = $DBH->prepare('SELECT password,id FROM imas_users WHERE SID=:sid');
				$stm->execute(array(':sid'=>$_POST['curSID']));
				//if (mysql_num_rows($result)==0) {
				if ($stm->rowCount()==0) {
					$infoerr = 'Username (key) is not valid';
				} else {
					list($realpw,$tmpuserid) = $stm->fetch(PDO::FETCH_NUM); //DB mysql_result($result,0,0);
					if (((!isset($CFG['GEN']['newpasswords']) || $CFG['GEN']['newpasswords']!='only') && ($realpw == md5($_POST['curPW'])))
					  || (isset($CFG['GEN']['newpasswords']) && password_verify($_POST['curPW'],$realpw)) ) {
						$userid= $tmpuserid; //DB mysql_result($result,0,1);
					} else {
						$infoerr = 'Existing username/password provided are not valid.';
						unset($tmpuserid);
					}
				}
			} else {
				if (!$allow_acctcreation) {
					$infoerr = 'Must link to an existing account';
				} else {
					require_once(__DIR__.'/includes/newusercommon.php');
					$infoerr = checkNewUserValidation();
					//new info
					if (isset($_POST['msgnot'])) {
						$msgnot = 1;
					} else {
						$msgnot = 0;
					}
					if (isset($CFG['GEN']['newpasswords'])) {
						$md5pw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
					} else {
						$md5pw = md5($_POST['pw1']);
					}
				}
			}
		}
		if ($infoerr=='') { // no error, so create!
			$stm = $DBH->prepare('INSERT INTO imas_ltiusers (org,ltiuserid) VALUES (:org,:ltiuserid)');
			$stm->execute(array(':org'=>$ltiorg,':ltiuserid'=>$ltiuserid));
			$localltiuser = $DBH->lastInsertId();
			if (!isset($userid) && $allow_acctcreation) {
				if ($name_only) {
					//make up a username/password for them
					$_POST['SID'] = 'lti-'.$localltiuser;
					$md5pw = 'pass'; //totally unusable since not md5'ed
				}
				if ($ltirole=='instructor') {
					if (isset($CFG['LTI']['instrrights'])) {
						$rights = $CFG['LTI']['instrrights'];
					} else {
						$rights = 40;
					}
					$newgroupid = intval($_SESSION['lti_keygroupid']);
					$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify,groupid) VALUES ";
					$query .= '(:SID,:password,:rights,:FirstName,:LastName,:email,:msgnotify,:groupid)';
					$stm = $DBH->prepare($query);
					$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw,':rights'=>$rights,
						':FirstName'=>Sanitize::stripHtmlTags($_POST['firstname']),
						':LastName'=>Sanitize::stripHtmlTags($_POST['lastname']),
						':email'=>Sanitize::emailAddress($_POST['email']),
						':msgnotify'=>$msgnot,':groupid'=>$newgroupid));
				} else {
					$rights = 10;
					$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify) VALUES ";
					$query .= '(:SID,:password,:rights,:FirstName,:LastName,:email,:msgnotify)';
					$stm = $DBH->prepare($query);
					$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw,':rights'=>$rights,
						':FirstName'=>Sanitize::stripHtmlTags($_POST['firstname']),
						':LastName'=>Sanitize::stripHtmlTags($_POST['lastname']),
						':email'=>Sanitize::emailAddress($_POST['email']),
						':msgnotify'=>$msgnot));
				}
				$userid = $DBH->lastInsertId(); //DB mysql_insert_id();

				if ($rights>=20) {
					//log new account
					$stm = $DBH->prepare("INSERT INTO imas_log (time, log) VALUES (:now, :log)");
					$stm->execute(array(':now'=>$now, ':log'=>"New Instructor Request: $userid:: Group: $newgroupid, added via LTI"));

					$reqdata = array('added'=>$now, 'actions'=>array(array('on'=>$now, 'status'=>11, 'via'=>'LTI')));
					$stm = $DBH->prepare("INSERT INTO imas_instr_acct_reqs (userid,status,reqdate,reqdata) VALUES (?,11,?,?)");
					$stm->execute(array($newuserid, $now, json_encode($reqdata)));
				}
			}
			$stm = $DBH->prepare('UPDATE imas_ltiusers SET userid=:userid WHERE id=:localltiuser');
			$stm->execute(array(':userid'=>$userid, ':localltiuser'=>$localltiuser));
		} else {
			//uh-oh, had an error.  Better ask for user info again
			$askforuserinfo = true;
		}
	} else {
		//ask for student info
		$flexwidth = true;
		$nologo = true;
		$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/jquery.validate.min.js?v=122917"></script>';
		require("header.php");
		if (isset($infoerr)) {
			echo '<p class=noticetext>'.Sanitize::encodeStringForDisplay($infoerr).'</p>';
		}

		echo "<form method=\"post\" id=\"pageform\" class=\"limitaftervalidate\" action=\"".$imasroot."/bltilaunch.php?userinfo=set\" ";
		if ($name_only) {
			//using LTI for authentication; don't need username/password
			//only request name
			echo "<p>Please provide a little information about yourself:</p>";
			echo "<span class=form><label for=\"firstname\">Enter First Name (given name):</label></span> <input class=form type=text size=20 id=firstname name=firstname autocomplete=\"given-name\"><BR class=form>\n";
			echo "<span class=form><label for=\"lastname\">Enter Last Name (surname):</label></span> <input class=form type=text size=20 id=lastname name=lastname autocomplete=\"family-name\"><BR class=form>\n";
			echo "<div class=submit><input type=submit value='Submit'></div>\n";
			echo '<script type="text/javascript"> $(function() {
				$("#pageform").validate({
					rules: {
						firstname: {required: true},
						lastname: {required: true}
					},
					submitHandler: function(el,evt) {return submitlimiter(evt);}
				});
			});</script>';
		} else {
			$deffirst = '';
			$deflast = '';
			$defemail = '';
			if (isset($_SESSION['LMSfirstname'])) {
				$deffirst = $_SESSION['LMSfirstname'];
			}
			if (isset($_SESSION['LMSlastname'])) {
				$deflast = $_SESSION['LMSlastname'];
			}
			if (isset($_SESSION['LMSemail'])) {
				$defemail = $_SESSION['LMSemail'];
			}

			if (isset($_SESSION['ltiorgname'])) {
				$ltiorgname = $_SESSION['ltiorgname'];
			} else {
				$ltiorgname = $ltiorg;
			}

			//strip off prepended org info before display
			$ltiorgparts = explode(':',$ltiorgname);
			if (count($ltiorgparts)>2) {
				array_shift($ltiorgparts);
				$ltiorgname = implode(':',$ltiorgparts);
			} else {
				$ltiorgname = $ltiorgparts[1];
			}

			//tying LTI to IMAthAS account
			//give option to provide existing account info, or provide full new student info
			if ($allow_acctcreation) {
				echo "<p>".sprintf(_("If you already have an account on %s, enter your username and password below to enable automated signon from %s"),$installname,Sanitize::encodeStringForDisplay($ltiorgname))."</p>";
			} else {
				echo "<p>".sprintf(_("Enter your username and password for %s below to enable automated signon from %s"),$installname,Sanitize::encodeStringForDisplay($ltiorgname))."</p>";
			}
			echo "<span class=form><label for=\"curSID\">".Sanitize::encodeStringForDisplay($loginprompt).":</label></span> <input class=form type=text size=12 id=\"curSID\" name=\"curSID\"><BR class=form>\n";
			echo "<span class=form><label for=\"curPW\">"._("Password:")."</label></span><input class=form type=password size=20 id=\"curPW\" name=\"curPW\"><BR class=form>\n";
			echo "<div class=submit><input type=submit value='"._("Sign In")."'></div>\n";
			if ($allow_acctcreation) {
				echo "<p>".sprintf(_("If you do not already have an account on %s, provide the information below to create an account and enable automated signon from %s"),$installname,Sanitize::encodeStringForDisplay($ltiorgname))."</p>";
				echo "<span class=form><label for=\"SID\">".Sanitize::encodeStringForDisplay($longloginprompt).":</label></span> <input class=form type=text size=12 id=SID name=SID><BR class=form>\n";
				echo "<span class=form><label for=\"pw1\">"._("Choose a password:")."</label></span><input class=form type=password size=20 id=pw1 name=pw1><BR class=form>\n";
				echo "<span class=form><label for=\"pw2\">"._("Confirm password:")."</label></span> <input class=form type=password size=20 id=pw2 name=pw2><BR class=form>\n";
				echo "<span class=form><label for=\"firstname\">"._("Enter First Name:")."</label></span> <input class=form type=text value=\"".Sanitize::encodeStringForDisplay($deffirst)."\" size=20 id=firstname name=firstname autocomplete=\"given-name\"><BR class=form>\n";
				echo "<span class=form><label for=\"lastname\">"._("Enter Last Name:")."</label></span> <input class=form type=text value=\"".Sanitize::encodeStringForDisplay($deflast)."\" size=20 id=lastname name=lastname autocomplete=\"family-name\"><BR class=form>\n";
				echo "<span class=form><label for=\"email\">"._("Enter E-mail address:")."</label></span>  <input class=form type=email value=\"".Sanitize::encodeStringForDisplay($defemail)."\" size=60 id=email name=email autocomplete=\"email\"><BR class=form>\n";
				echo "<span class=form><label for=\"msgnot\">"._("Notify me by email when I receive a new message:")."</label></span><input class=floatleft type=checkbox id=msgnot name=msgnot /><BR class=form>\n";
				echo "<div class=submit><input type=submit value='"._("Create Account")."'></div>\n";
				require_once(__DIR__.'/includes/newusercommon.php');
				$requiredrules = array(
					'curSID'=>'{depends: function(element) {return $("#SID").val()==""}}',
					'curPW'=>'{depends: function(element) {return $("#SID").val()==""}}',
					'SID'=>'{depends: function(element) {return $("#SID").val()!=""}}',
					'pw1'=>'{depends: function(element) {return $("#SID").val()!=""}}',
					'pw2'=>'{depends: function(element) {return $("#SID").val()!=""}}',
					'firstname'=>'{depends: function(element) {return $("#SID").val()!=""}}',
					'lastname'=>'{depends: function(element) {return $("#SID").val()!=""}}',
					'email'=>'{depends: function(element) {return $("#SID").val()!=""}}',
				);
				showNewUserValidation('pageform',array('curSID','curPW'), $requiredrules);

			} else {
				echo "<p>".sprintf(_("If you do not already have an account on %s, please visit the site to request an account."),$installname)."</p>";
				echo '<script type="text/javascript"> $(function() {
					$("#pageform").validate({
						rules: {
							curSID: {required: true},
							curPW: {required: true}
						},
						submitHandler: function(el,evt) {return submitlimiter(evt);}
					});
				});</script>';
			}
		}
		echo "</form>\n";
		require("footer.php");
		exit;

	}

} else if (isset($_SESSION['ltiuserid']) && !isset($_REQUEST['oauth_consumer_key'])) {
	//refreshed this page from accessibility options page so session already exists
	// (if user_id is set, then is new LTI request, so want to pass down to OAuth)
	//pull necessary info and continue
	if (isset($_SESSION['userid'])) {
		$userid = $_SESSION['userid'];
	} else {
			reporterror(_("No session recorded"));
	}

	$keyparts = explode('_',$_SESSION['ltikey']);
} else if(isset($_REQUEST['custom_view_folder'])) {
	//temporary branch for handling this deprecated feature, until it can be removed.
	$linkparts = explode("-",$_REQUEST['custom_view_folder']);
	$stm = $DBH->prepare('SELECT itemorder FROM imas_courses WHERE id=:cid');
	$stm->execute(array(':cid'=>$linkparts[0]));
	if ($stm->rowCount()==0) {
		reporterror(_("invalid course identifier in folder view launch"));
	} else {
		$cid = intval($linkparts[0]);
		$row = $stm->fetch(PDO::FETCH_ASSOC); //DB mysql_fetch_row($result2);
		$items = unserialize($row['itemorder']);
		function findfolder($items,$n,$loc) {
			foreach ($items as $k=>$b) {
				if (is_array($b)) {
					if ($b['id']==$n) {
						return $loc.'-'.($k+1);
					} else {
						$out = findfolder($b['items'],$n,$loc.'-'.($k+1));
						if ($out != '') {
							return $out;
						}
					}
				}
			}
			return '';
		}
		$loc = findfolder($items, $linkparts[1], '0');
		if ($loc=='') {
			reporterror(_("invalid folder identifier in folder view launch"));
		}
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/public.php?cid=".$linkparts[0]."&folder=".$loc);
	}
	exit;

} else {
	//not postback of new LTI user info, so must be fresh request

	//verify necessary POST values for LTI.  OAuth specific will be checked later
	if (empty($_REQUEST['lti_version'])) {
		reporterror(_("Insufficient launch information. This might indicate your browser is set to restrict third-party cookies. Check your browser settings and try again. If you are using Safari, you may need to disable Prevent Cross-Site Tracking."));
	}
	if (empty($_REQUEST['user_id'])) {
		if (isset($_REQUEST['relaunch_url'])) {
			do112relaunch();
		}
		reporterror(_("Unable to launch - User information not provided (user_id is required)"));
	} else {
		$ltiuserid = $_REQUEST['user_id'];
	}
	if (!empty($_REQUEST['tool_state'])) {
		verify112relaunch();
	}

	if (empty($_REQUEST['context_id'])) {
		reporterror(_("Unable to launch - Course information not provided (context_id is required)"));
	}

	if (isset($_SESSION['ltiuserid']) && $_SESSION['ltiuserid']!=$ltiuserid) {
		//new user - need to clear out session
		session_destroy();
		session_start();
		session_regenerate_id();
		$sessionid = session_id();
		$_SESSION = array();
		//setcookie(session_name(),session_id(),0,'','',false,true );
	}

	/*if (empty($_REQUEST['roles'])) {
		reporterror("roles is required");
	} else {
		$ltirole = $_REQUEST['roles'];
	}*/
	if (empty($_REQUEST['tool_consumer_instance_guid'])) {
		$ltiorg = 'Unknown';
	} else {
		$ltiorg = $_REQUEST['tool_consumer_instance_guid'];
	}
	if (empty($_REQUEST['oauth_consumer_key'])) {
		reporterror(_("Unable to launch - oauth_consumer_key (resource key) is required"));
	} else {
		$ltikey = $_REQUEST['oauth_consumer_key'];
	}

	//check OAuth Signature!
	require_once 'includes/OAuth.php';
	require_once 'includes/ltioauthstore.php';

	//set up OAuth
	$store = new IMathASLTIOAuthDataStore();
	$server = new OAuthServer($store);
	$method = new OAuthSignatureMethod_HMAC_SHA1();
	$server->add_signature_method($method);
	$method2 = new OAuthSignatureMethod_HMAC_SHA256();
	$server->add_signature_method($method2);
	$request = OAuthRequest::from_request();
	$base = $request->get_signature_base_string();
	try {
		$requestinfo = $server->verify_request($request);
	} catch (Exception $e) {
		reporterror($e->getMessage());
	}
	$store->mark_nonce_used($request);

	$keyparts = explode('_',$ltikey);
	$_SESSION['ltiorigkey'] = $ltikey;

	// prepend ltiorg with courseid or sso+userid to prevent cross-instructor hacking
	if ($keyparts[0]=='LTIkey') {  //cid:org
		$_SESSION['ltilookup'] = 'c';
		$ltiorg = $keyparts[1].':'.$ltiorg;
		$keytype = 'gc';
	} else {
		$_SESSION['ltilookup'] = 'u';
		$ltiorg = $ltikey.':'.$ltiorg;
		$keytype = 'g';
	}
	if (isset($_REQUEST['custom_place_aid'])) { //common catridge lti placement, or Canvas LTI selection
		$placeaid = intval($_REQUEST['custom_place_aid']);
		$keytype = 'cc-a';
		$_SESSION['place_aid'] = $_REQUEST['custom_place_aid'];
	} else if (isset($_REQUEST['custom_open_folder'])) {
		$keytype = 'cc-of';
		$parts = explode('-',$_REQUEST['custom_open_folder']);
		$sourcecid = $parts[0];
		$_SESSION['open_folder'] = array($sourcecid,$parts[1]);
	}


	//Store all LTI request data in session variable for reuse on submit
	//if we got this far, secret has already been verified
	$_SESSION['ltiuserid'] = $ltiuserid;
	$_SESSION['ltiorg'] = $ltiorg;
	$ltirole = strtolower($_REQUEST['roles']);
	if (strpos($ltirole,'instructor')!== false || strpos($ltirole,'administrator')!== false || strpos($ltirole,'contentdeveloper')!== false) {
		$ltirole = 'instructor';
	} else {
		$ltirole = 'learner';
	}

	$_SESSION['ltirole'] = $ltirole;
	$_SESSION['lti_context_id'] = $_REQUEST['context_id'];
    if (!empty($_REQUEST['context_label'])) {
        $_SESSION['lti_context_label'] = $_REQUEST['context_label'];
    } else if (!empty($_REQUEST['context_title'])) {
        $_SESSION['lti_context_label'] = $_REQUEST['context_title'];
    } else {
        $_SESSION['lti_context_label'] = $_REQUEST['context_id'];
    }
    $_SESSION['lti_resource_link_id'] = $_REQUEST['resource_link_id'];
	$_SESSION['lti_lis_result_sourcedid'] = $_REQUEST['lis_result_sourcedid'];
	$_SESSION['lti_outcomeurl'] = $_REQUEST['lis_outcome_service_url'];
	$_SESSION['lti_key'] = $ltikey;
	$_SESSION['lti_keytype'] = $keytype;
	$_SESSION['lti_keyrights'] = $requestinfo[0]->rights;
	$_SESSION['lti_keygroupid'] = intval($requestinfo[0]->groupid);
	if (isset($_REQUEST['selection_directive']) && $_REQUEST['selection_directive']=='select_link') {
		$_SESSION['selection_return'] = $_REQUEST['launch_presentation_return_url'];
        $_SESSION['selection_return_format'] = "Canvas";
        unset($_SESSION['place_aid']);
	}
	if (isset($_REQUEST['lti_message_type']) && $_REQUEST['lti_message_type']=='ContentItemSelectionRequest') {
		$_SESSION['selection_return'] = $_REQUEST['content_item_return_url'];
		$_SESSION['selection_targets'] = $_REQUEST['accept_presentation_document_targets'];
		$_SESSION['selection_return_format'] = "IMSdeeplink";
		if (isset($_REQUEST['ltiseltype']) && $_REQUEST['ltiseltype']=='assn') {
			$_SESSION['selection_type'] = 'assn';
		} else if (isset($_REQUEST['ltiseltype']) && $_REQUEST['ltiseltype']=='link') {
			$_SESSION['selection_type'] = 'link';
		} else {
			$_SESSION['selection_type'] = 'all';
		}
        $_SESSION['selection_data'] = @$_REQUEST['data'];
        unset($_SESSION['place_aid']);
	}
	unset($_SESSION['lti_duedate']);
	if (!isset($_REQUEST['custom_canvas_assignment_due_at'])) {
		if (isset($_REQUEST['custom_assignment_due_at'])) {
			$_REQUEST['custom_canvas_assignment_due_at'] = $_REQUEST['custom_assignment_due_at'];
		}
    }
	if (isset($_REQUEST['custom_canvas_assignment_due_at'])) {
		$duedate = strtotime($_REQUEST['custom_canvas_assignment_due_at']);
		if ($duedate !== false) {
			$_SESSION['lti_duedate'] = $duedate;
		} else {
			$_SESSION['lti_duedate'] = 2000000000;
		}
	}

	//look if we know this user
	$orgparts = explode(':',$ltiorg);  //THIS was added to avoid issues when LMS GUID change, while still storing it
	$shortorg = $orgparts[0];	   //we'll only use the part from the lti key
	$query = "SELECT lti.userid,iu.FirstName,iu.LastName,iu.email,lti.id FROM imas_ltiusers AS lti LEFT JOIN imas_users as iu ON lti.userid=iu.id ";
	$query .= "WHERE lti.org LIKE :org AND lti.ltiuserid=:ltiuserid ";
	if ($ltirole!='learner') {
		//if they're a teacher, make sure their imathas account is too. If not, we'll act like we don't know them
		//and require a new connection
		$query .= "AND iu.rights>19 ";
	}
	//if multiple accounts, use student one first (if not $ltirole of teacher) then higher rights.
	//if there was a mixup and multiple records were created, use the first one
	$query .= "ORDER BY iu.rights, lti.id";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':org'=>"$shortorg:%", ':ltiuserid'=>$ltiuserid));
	if ($stm->rowCount()>0) { //yup, we know them
		$userrow = $stm->fetch(PDO::FETCH_ASSOC);
		$userid = $userrow['userid'];
		if (((!empty($_REQUEST['lis_person_name_given']) && !empty($_REQUEST['lis_person_name_family'])) || !empty($_REQUEST['lis_person_name_full'])) &&
		   ((count($keyparts)==1 && $ltirole=='learner') || (count($keyparts)>2 && $keyparts[2]==1 && $ltirole=='learner') )) {
			if (!empty($_REQUEST['lis_person_name_given']) && !empty($_REQUEST['lis_person_name_family'])) {
				$firstname = $_REQUEST['lis_person_name_given'];
				$lastname = $_REQUEST['lis_person_name_family'];
			} else {
				$firstname = '';
				$lastname = $_REQUEST['lis_person_name_full'];
			}
			if (!empty($_REQUEST['lis_person_contact_email_primary'])) {
				$email = $_REQUEST['lis_person_contact_email_primary'];
			} else {
				$email = 'none@none.com';
			}
			if ($userrow['FirstName'] === null) { //accidentally deleted the imas_users record!  Restore it
				$query = "INSERT INTO imas_users (id,SID,password,rights,FirstName,LastName,email,msgnotify) VALUES ";
				$query .= '(:userid,:SID,:password,:rights,:FirstName,:LastName,:email,0)';
				$stm = $DBH->prepare($query);
				$stm->execute(array(':userid'=>$userid, ':SID'=>'lti-'.$userrow['id'], ':password'=>'pass' ,':rights'=>10,
					':FirstName'=>Sanitize::stripHtmlTags($firstname),
					':LastName'=>Sanitize::stripHtmlTags($lastname),
					':email'=>Sanitize::emailAddress($email)));
			} else if ($firstname != $userrow['FirstName'] || $lastname != $userrow['LastName'] || $email != $userrow['email']) {
				//update imas_users record
				$stm2 = $DBH->prepare("UPDATE imas_users SET FirstName=?,LastName=?,email=? WHERE id=?");
				$stm2->execute(array($firstname, $lastname, $email, $userid));
			}
		}
	} else {
		//student is not known.  Bummer.  Better figure out what to do with them :)

		//go ahead and create the account if:
		//has name information (should we skip?)
		//domain level placement and (student or instructor with acceptable key rights)
		//a _1 type placement and (student or instructor with acceptable key rights)

		if (((!empty($_REQUEST['lis_person_name_given']) && !empty($_REQUEST['lis_person_name_family'])) || !empty($_REQUEST['lis_person_name_full'])) &&
		   ((count($keyparts)==1 && $ltirole=='learner') || (count($keyparts)>2 && $keyparts[2]==1 && $ltirole=='learner') )) {
			if (!empty($_REQUEST['lis_person_name_given']) && !empty($_REQUEST['lis_person_name_family'])) {
				$firstname = $_REQUEST['lis_person_name_given'];
				$lastname = $_REQUEST['lis_person_name_family'];
			} else {
				$firstname = '';
				$lastname = $_REQUEST['lis_person_name_full'];
			}
			if (!empty($_REQUEST['lis_person_contact_email_primary'])) {
				$email = $_REQUEST['lis_person_contact_email_primary'];
			} else {
				$email = 'none@none.com';
			}
			$stm = $DBH->prepare('INSERT INTO imas_ltiusers (org,ltiuserid) VALUES (:org,:ltiuserid)');
			$stm->execute(array(':org'=>$ltiorg,':ltiuserid'=>$ltiuserid));
			$localltiuser = $DBH->lastInsertId();
			if (!isset($userid)) {
				//make up a username/password for them
				$_POST['SID'] = 'lti-'.$localltiuser;
				$md5pw = 'pass'; //totally unusable since not md5'ed
				if ($ltirole=='instructor') { //not currently used - no teachers without real usernames/passwords
					if (isset($CFG['LTI']['instrrights'])) {
						$rights = $CFG['LTI']['instrrights'];
					} else {
						$rights = 40;
					}
					$newgroupid = intval($_SESSION['lti_keygroupid']);
					$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify,groupid) VALUES ";
					$query .= '(:SID,:password,:rights,:FirstName,:LastName,:email,0,:groupid)';
					$stm = $DBH->prepare($query);
					$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw,':rights'=>$rights,
						':FirstName'=>Sanitize::stripHtmlTags($firstname),
						':LastName'=>Sanitize::stripHtmlTags($lastname),
						':email'=>Sanitize::emailAddress($email),
						':groupid'=>$newgroupid));

				} else {
					$rights = 10;
					$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify) VALUES ";
					$query .= '(:SID,:password,:rights,:FirstName,:LastName,:email,0)';
					$stm = $DBH->prepare($query);
					$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw,':rights'=>$rights,
						':FirstName'=>Sanitize::stripHtmlTags($firstname),
						':LastName'=>Sanitize::stripHtmlTags($lastname),
						':email'=>Sanitize::emailAddress($email)));
				}
				$userid = $DBH->lastInsertId(); //DB $userid = mysql_insert_id();
				if ($rights>=20) {
					//log new account
					$stm = $DBH->prepare("INSERT INTO imas_log (time, log) VALUES (:now, :log)");
					$stm->execute(array(':now'=>$now, ':log'=>"New Instructor Request: $userid:: Group: $newgroupid, added via LTI"));

					$reqdata = array('added'=>$now, 'actions'=>array(array('on'=>$now, 'status'=>11, 'via'=>'LTI')));
					$stm = $DBH->prepare("INSERT INTO imas_instr_acct_reqs (userid,status,reqdate,reqdata) VALUES (?,11,?,?)");
					$stm->execute(array($newuserid, $now, json_encode($reqdata)));
				}
			}
			$stm = $DBH->prepare('UPDATE imas_ltiusers SET userid=:userid WHERE id=:localltiuser');
			$stm->execute(array(':userid'=>$userid, ':localltiuser'=>$localltiuser));
		} else {
			////create form asking them for user info
			$askforuserinfo = true;
			$_SESSION['LMSfirstname'] = $_REQUEST['lis_person_name_given'];
			$_SESSION['LMSlastname'] = $_REQUEST['lis_person_name_family'];
			if (!empty($_REQUEST['lis_person_contact_email_primary'])) {
				$_SESSION['LMSemail'] = $_REQUEST['lis_person_contact_email_primary'];
			}
		}
	}
	$_SESSION['ltikey'] = $ltikey;
}



//Do we need to ask for student's info?
//either first connect or bad info on first submit
if ($askforuserinfo == true) {
	if (!empty($_REQUEST['tool_consumer_instance_description'])) {
		$_SESSION['ltiorgname'] = $_REQUEST['tool_consumer_instance_description'];
	}
	header('Location: ' . $GLOBALS['basesiteurl'] . "/bltilaunch.php?userinfo=ask");
	exit;

}

//if here, we know the local userid.

//call hook, if defined
if (function_exists('onHaveLocalUser')) {
	onHaveLocalUser($userid);
}

//if it's a common catridge placement and we're here, then either we're using domain credentials, or
//course credentials for a non-source course.

//see if lti_courses is created
//  if not, see if source cid is instructors course
// 	if so, give option to copy or set lti_course
//	if not, create a course copy
//
//see if courseid==source course cid
//  if not, copy assessment if needed into course, set placement
//  if so, set placement

//determine request type, and check availability
$now = time();

//general placement or common catridge placement - look for placement, or create if know info
$orgparts = explode(':',$_SESSION['ltiorg']);  //THIS was added to avoid issues when GUID change, while still storing it
$shortorg = $orgparts[0];
$query = "SELECT placementtype,typeid FROM imas_lti_placements WHERE ";
$query .= "contextid=:contextid AND linkid=:linkid AND typeid>0 AND org LIKE :org";
$stm = $DBH->prepare($query);
$stm->execute(array(':contextid'=>$_SESSION['lti_context_id'], ':linkid'=>$_SESSION['lti_resource_link_id'], ':org'=>"$shortorg:%"));
if ($stm->rowCount()==0) {
	if (isset($_SESSION['place_aid'])) {
		$stm = $DBH->prepare('SELECT courseid,name FROM imas_assessments WHERE id=:aid');
		$stm->execute(array(':aid'=>$_SESSION['place_aid']));
		list($aidsourcecid,$aidsourcename) = $stm->fetch(PDO::FETCH_NUM);
		if ($aidsourcecid===false) {
			$diaginfo = "(Debug info: 2-{$_SESSION['place_aid']})";
			reporterror(_("This assignment does not appear to exist anymore.")." $diaginfo");
		}

		//look to see if we've already linked this context_id with a course
		$stm = $DBH->prepare('SELECT courseid,copiedfrom FROM imas_lti_courses WHERE contextid=:contextid AND org LIKE :org');
		$stm->execute(array(':contextid'=>$_SESSION['lti_context_id'], ':org'=>"$shortorg:%"));
		if ($stm->rowCount()==0) {
			//if instructor, see if the source course is ours
			/***TODO:  check rights to see if they have course creation rights or not */
			if ($_SESSION['ltirole']=='instructor') {
				$copycourse = "notify";
				$stm = $DBH->prepare('SELECT id FROM imas_teachers WHERE courseid=:aidsourcecid AND userid=:userid');
				$stm->execute(array(':aidsourcecid'=>$aidsourcecid, ':userid'=>$userid));
				if ($stm->rowCount()>0) {
					$copycourse="ask";
					if (isset($_POST['docoursecopy']) && $_POST['docoursecopy']=="useexisting") {
						$destcid = $aidsourcecid;
						$copycourse = "no";
						$ltilog = ['copy'=>'useorig', 'cid'=>$destcid];
					}
				}
				$stm = $DBH->prepare('SELECT jsondata,UIver FROM imas_courses WHERE id=:aidsourcecid');
				$stm->execute(array(':aidsourcecid'=>$aidsourcecid));
				list($aidsourcejsondata,$sourceUIver) = $stm->fetch(PDO::FETCH_NUM);
				$aidsourcejsondata = json_decode($aidsourcejsondata, true);
				$blockLTICopyOfCopies = ($aidsourcejsondata!==null && !empty($aidsourcejsondata['blockLTICopyOfCopies']));

				if (isset($_POST['docoursecopy']) && $_POST['docoursecopy']=="useother" && !empty($_POST['useothercoursecid'])) {
					$destcid = $_POST['useothercoursecid'];
					$copycourse = "no";
					$ltilog = ['copy'=>'useother', 'cid'=>$destcid];
				} else if (isset($_POST['docoursecopy']) && $_POST['docoursecopy']=="makecopy") {
					$copycourse = "yes";
					$sourcecid = $aidsourcecid;
					$ltilog = ['copy'=>'copyorig', 'cid'=>$sourcecid];
				} else if (isset($_POST['docoursecopy']) && $_POST['docoursecopy']=="copyother" && $_POST['othercoursecid']>0) {
					$copycourse = "yes";
					$sourcecid = Sanitize::onlyInt($_POST['othercoursecid']);
					$ltilog = ['copy'=>'copyother', 'cid'=>$sourcecid];
				}
				if ($copycourse=="notify" || $copycourse=="ask") {
					$_SESSION['userid'] = $userid; //remember me
					$nologo = true;
					$flexwidth = true;
					$placeinhead = '<style type="text/css"> ul.nomark {margin-left: 20px;} ul.nomark li {text-indent: -20px;}</style>';
					require("header.php");

					$query = "SELECT DISTINCT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers AS imt ON ic.id=imt.courseid ";
					$query .= "AND imt.userid=:userid JOIN imas_assessments AS ia ON ic.id=ia.courseid ";
					$query .= "WHERE ic.available<4 AND ic.ancestors REGEXP :cregex AND ia.ancestors REGEXP :aregex ORDER BY ic.name";
					$stm = $DBH->prepare($query);
					$stm->execute(array(
						':userid'=>$userid,
						':cregex'=>'[[:<:]]'.$aidsourcecid.'[[:>:]]',
						':aregex'=>'[[:<:]]'.$_SESSION['place_aid'].'[[:>:]]'));
					$othercourses = array();
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$othercourses[$row[0]] = $row[1];
					}
					$advuseother = '';
					if (count($othercourses)>0) {
						$advuseother .= '<li><a class="small" style="margin-left:20px;" href="#" onclick="$(this).hide().next(\'span\').show();return false;">Show advanced options</a> ';
						$advuseother .= '<span style="display:none;"><input name="docoursecopy" type="radio" value="useother" />';
						$advuseother .= 'Associate this LMS course with an existing course: ';
						$advuseother .= '<select name="useothercoursecid">';
						foreach ($othercourses as $k=>$v) {
							if ($k==$aidsourcecid) {continue;}
							$advuseother .= '<option value="'.$k.'">'.Sanitize::encodeStringForDisplay($v.' (Course ID '.$k.')').'</option>';
						}
						$advuseother .= '</select>';
						$advuseother .= '<br/>Using this option means students in this LMS course will show up in the Roster and Gradebook of the '.$installname.' course you associate it with.</span></li>';
					}
					echo "<form method=\"post\" action=\"".$imasroot."/bltilaunch.php\">";
					if ($copycourse=="ask") {
						echo "<p>Your LMS course is not yet associated with a course on $installname.  The assignment associated with this
							link is located in a $installname course you are already a teacher of (course ID $aidsourcecid).
							Would you like to associate this LMS course with that $installname course?  This means
							that your students in your LMS course will show up in the Roster and Gradebook in that
							$installname course.  If you don't want to use your existing $installname course,
							a copy of the $installname assignments can be made for you automatically and associated with
							this LMS course.</p>
							<ul class=nomark>
							<li><input name=\"docoursecopy\" type=\"radio\" value=\"useexisting\" checked onclick=\"$('#usenew').hide()\" />Associate this LMS course with my existing course (ID $aidsourcecid) on $installname</li>
							<li><input name=\"docoursecopy\" type=\"radio\" value=\"makecopy\" onclick=\"$('#usenew').show()\"/>Create a copy of my existing course (ID $aidsourcecid) on $installname</li>";
						if (count($othercourses)>0 && !$blockLTICopyOfCopies) {
							echo '<li><input name="docoursecopy" type="radio" value="copyother" onclick="$(\'#usenew\').show()" />Create a copy of another course: <select name="othercoursecid">';
							foreach ($othercourses as $k=>$v) {
								echo '<option value="'.$k.'">'.Sanitize::encodeStringForDisplay($v.' (Course ID '.$k.')').'</option>';
							}
							echo '</select></li>';
							echo $advuseother;
						}
						echo "	</ul>";
						if ($sourceUIver == 1) {
                            if (!empty($CFG['assess_upgrade_optout'])) {
                                echo '<p id="usenew" style="display:none;"><input type="checkbox" name="usenewassess" value="1" checked /> Use new assessment interface (only applies if copying)</p>';
                            } else {
                                echo '<input type="hidden" name="usenewassess" value="1">';
                            }
						}
						echo "<p>The first option is best if this is your first time using this $installname course.  The second option
							may be preferrable if you have copied the course in your LMS and want your students records to
							show in a separate $installname course.</p>
							<p><input type=\"submit\" value=\"Continue\"/> (this may take a few moments - please be patient)</p>";
					} else {
						echo "<p>Your LMS course is not yet associated with a course on $installname.  The assignment associated with this
							link is located in a $installname course you are not a teacher of (course ID $aidsourcecid).
							To use this content, a copy of the assignments will be made for you automatically,
							and this LMS course will be associated with that copy in $installname.  This will allow you to make changes to the assignments
							without affecting the original course, and will ensure your student records are housed in your own
							$installname course.</p>";
						if (count($othercourses)>0 && !$blockLTICopyOfCopies) {
							echo "<ul class=nomark><li><input name=\"docoursecopy\" type=\"radio\" value=\"makecopy\" />Create a copy of the original course (ID $aidsourcecid) on $installname</li>";
							echo '<li><input name="docoursecopy" type="radio" value="copyother" />Create a copy of another course: <select name="othercoursecid">';
							foreach ($othercourses as $k=>$v) {
								echo '<option value="'.$k.'">'.Sanitize::encodeStringForDisplay($v).'</option>';
							}
							echo '</select></li>';
							echo $advuseother;
							echo '</ul>';
						} else {
							echo "<input name=\"docoursecopy\" type=\"hidden\" value=\"makecopy\" />";
						}
						if ($sourceUIver == 1) {
                            if (!empty($CFG['assess_upgrade_optout'])) {
                                echo '<p><input type="checkbox" name="usenewassess" checked /> Use new assessment interface (only applies if copying)</p>';
                            } else {
                                echo '<input type="hidden" name="usenewassess" value="1">';
                            }
						}
						echo "<p><input type=\"submit\" value=\"Create a copy on $installname\"/> (this may take a few moments - please be patient)</p>";
					}
					echo "</form>";
					require("footer.php");
					exit;
				}
			} else {
				reporterror(_("Course link not established yet.  Notify your instructor they need to click this assignment to set it up."));
			}
			if ($copycourse == "yes") {
				//create a course
				//creating a copy of a template course
				$blockcnt = 1;
				$itemorder = serialize(array());
				$randkey = uniqid();
				$allowunenroll = isset($CFG['CPS']['allowunenroll'])?$CFG['CPS']['allowunenroll'][0]:0;
				$copyrights = isset($CFG['CPS']['copyrights'])?$CFG['CPS']['copyrights'][0]:0;
				$msgset = isset($CFG['CPS']['msgset'])?$CFG['CPS']['msgset'][0]:0;
				$msgmonitor = (floor($msgset/5))&1;
				$msgset = $msgset%5;
				if (!isset($defaultcoursetheme)) {$defaultcoursetheme = "modern.css";}
				$theme = isset($CFG['CPS']['theme'])?$CFG['CPS']['theme'][0]:$defaultcoursetheme;
				$showlatepass = isset($CFG['CPS']['showlatepass'])?$CFG['CPS']['showlatepass'][0]:0;

				$avail = 0;
				$lockaid = 0;
				$DBH->beginTransaction();

				$query = "INSERT INTO imas_courses (name,ownerid,enrollkey,allowunenroll,copyrights,msgset,showlatepass,itemorder,available,theme,ltisecret,blockcnt) VALUES ";
				$query .= "(:name,:ownerid,:enrollkey,:allowunenroll,:copyrights,:msgset,:showlatepass,:itemorder,:available,:theme,:ltisecret,:blockcnt)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':name'=>$_SESSION['lti_context_label'], ':ownerid'=>$userid, ':enrollkey'=>$randkey,
					':allowunenroll'=>$allowunenroll, ':copyrights'=>$copyrights, ':msgset'=>$msgset, ':showlatepass'=>$showlatepass, ':itemorder'=>$itemorder,
					':available'=>$avail, ':theme'=>$theme, ':ltisecret'=>$randkey, ':blockcnt'=>$blockcnt));
				$destcid = $DBH->lastInsertId();

				//call hook, if defined
				if (function_exists('onAddCourse')) {
					onAddCourse($destcid, $userid, $myrights, $groupid);
				}

				$stm = $DBH->prepare('INSERT INTO imas_teachers (userid,courseid) VALUES (:userid,:destcid)');
				$stm->execute(array(':userid'=>$userid, ':destcid'=>$destcid));

				//DO full course copy
				$query = "SELECT useweights,orderby,defaultcat,defgbmode,stugbmode,usersort FROM imas_gbscheme WHERE courseid=:courseid";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$sourcecid));
				$row = $stm->fetch(PDO::FETCH_NUM);
				//$stm = $DBH->prepare("UPDATE imas_gbscheme SET useweights=:useweights,orderby=:orderby,defaultcat=:defaultcat,defgbmode=:defgbmode,stugbmode=:stugbmode WHERE courseid=:courseid");
				//$stm->execute(array(':useweights'=>$row[0], ':orderby'=>$row[1], ':defaultcat'=>$row[2], ':defgbmode'=>$row[3], ':stugbmode'=>$row[4], ':courseid'=>$destcid));
				//bug fix: need to insert, not update
				$stm = $DBH->prepare("INSERT INTO imas_gbscheme (courseid,useweights,orderby,defaultcat,defgbmode,stugbmode,usersort) VALUES (:courseid, :useweights, :orderby, :defaultcat, :defgbmode, :stugbmode, :usersort)");
				$stm->execute(array(':courseid'=>$destcid, ':useweights'=>$row[0], ':orderby'=>$row[1], ':defaultcat'=>$row[2], ':defgbmode'=>$row[3], ':stugbmode'=>$row[4], ':usersort'=>$row[5]));


				$gbcats = array();
				$stm = $DBH->prepare("SELECT id,name,scale,scaletype,chop,dropn,weight,hidden,calctype FROM imas_gbcats WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$sourcecid));

				$query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight,hidden,calctype) VALUES ";
				$query .= "(:courseid,:name,:scale,:scaletype,:chop,:dropn,:weight,:hidden,:calctype)";
				$cols = explode(',', ':courseid,:name,:scale,:scaletype,:chop,:dropn,:weight,:hidden,:calctype');
				$stm2 = $DBH->prepare($query);

				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$frid = $row[0];
					$row[0] = $destcid; //change course id

					$varmap = array();
					foreach ($cols as $i=>$col) {
						$varmap[$col] = $row[$i];
					}
					$stm2->execute($varmap);
					$gbcats[$frid] = $DBH->lastInsertId();
				}
				$copystickyposts = true;
				$stm = $DBH->prepare("SELECT itemorder,ancestors,outcomes,latepasshrs,dates_by_lti,deflatepass,UIver,level FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$sourcecid));
				$r = $stm->fetch(PDO::FETCH_NUM);

				$items = unserialize($r[0]);
				$ancestors = $r[1];
				$outcomesarr = $r[2];
				$latepasshrs = $r[3];
				$datesbylti = $r[4];
				$deflatepass = $r[5];
				$sourceUIver = $r[6];
				$courselevel = $r[7];
				if (isset($_POST['usenewassess'])) {
					$destUIver = 2;
					$convertAssessVer = 2;
				} else {
					$destUIver = $sourceUIver;
				}
				if ($ancestors=='') {
					$ancestors = intval($sourcecid);
				} else {
					$ancestors = intval($sourcecid).','.$ancestors;
				}
				$ancestors = $ancestors;
				$outcomes = array();
				$query = 'SELECT imas_questionset.id,imas_questionset.replaceby FROM imas_questionset JOIN ';
				$query .= 'imas_questions ON imas_questionset.id=imas_questions.questionsetid JOIN ';
				$query .= 'imas_assessments ON imas_assessments.id=imas_questions.assessmentid WHERE ';
				$query .= "imas_assessments.courseid=:courseid AND imas_questionset.replaceby>0";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$sourcecid));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$replacebyarr[$row[0]] = $row[1];
				}

				if ($outcomesarr!='') {
					$stm = $DBH->prepare("SELECT id,name,ancestors FROM imas_outcomes WHERE courseid=:courseid");
					$stm->execute(array(':courseid'=>$sourcecid));

					$stm2 = $DBH->prepare("INSERT INTO imas_outcomes (courseid,name,ancestors) VALUES (:destcid,:name,:ancestors)");

					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						if ($row[2]=='') {
							$row[2] = $row[0];
						} else {
							$row[2] = $row[0].','.$row[2];
						}
						$stm2->execute(array(':destcid'=>$destcid, ':name'=>$row[1], ':ancestors'=>$row[2]));
						$outcomes[$row[0]] = $DBH->lastInsertId();
					}
					function updateoutcomes(&$arr) {
						global $outcomes;
						foreach ($arr as $k=>$v) {
							if (is_array($v)) {
								updateoutcomes($arr[$k]['outcomes']);
							} else {
								$arr[$k] = $outcomes[$v];
							}
						}
					}
					$outcomesarr = unserialize($outcomesarr);
					updateoutcomes($outcomesarr);
					$newoutcomearr = serialize($outcomesarr);
				} else {
					$newoutcomearr = '';
				}
				$removewithdrawn = true;
				$usereplaceby = "all";
				$newitems = array();
				$cid = $destcid; //needed for copyiteminc
				require_once("includes/copyiteminc.php");
				copyallsub($items,'0',$newitems,$gbcats);
				doaftercopy($sourcecid, $newitems);

				$itemorder = serialize($newitems);
				$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt,ancestors=:ancestors,outcomes=:outcomes,latepasshrs=:latepasshrs,deflatepass=:deflatepass,dates_by_lti=:datesbylti,UIver=:UIver,level=:level WHERE id=:id");
				$stm->execute(array(':itemorder'=>$itemorder, ':blockcnt'=>$blockcnt, ':ancestors'=>$ancestors, ':outcomes'=>$newoutcomearr, ':latepasshrs'=>$latepasshrs, ':deflatepass'=>$deflatepass, ':datesbylti'=>$datesbylti, ':UIver'=>$destUIver, ':level'=>$courselevel, ':id'=>$destcid));

				$offlinerubrics = array();
				/*
				//copy offline
				$query = "SELECT name,points,showdate,gbcategory,cntingb,tutoredit,rubric FROM imas_gbitems WHERE courseid='$sourcecid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$insarr = array();
				while ($row = mysql_fetch_row($result)) {
					$rubric = array_pop($row);
					if (isset($gbcats[$row[3]])) {
						$row[3] = $gbcats[$row[3]];
					} else {
						$row[3] = 0;
					}
					$ins = "('$cid','".implode("','",addslashes_deep($row))."')";
					$query = "INSERT INTO imas_gbitems (courseid,name,points,showdate,gbcategory,cntingb,tutoredit) VALUES $ins";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
					if ($rubric>0) {
						$offlinerubrics[mysql_insert_id()] = $rubric;
					}
				}*/
				copyrubrics();
				$DBH->commit();
				$copiedfromcid = $sourcecid;
			}
			$query = "INSERT INTO imas_lti_courses (org,contextid,courseid,copiedfrom,contextlabel) VALUES ";
			$query .= "(:org, :contextid, :courseid, :copiedfrom, :contextlabel)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(
				':org'=>$_SESSION['ltiorg'],
				':contextid'=>$_SESSION['lti_context_id'],
				':courseid'=>$destcid,
				':copiedfrom'=>($copycourse == "yes")?$sourcecid:0,
				':contextlabel'=>$_SESSION['lti_context_label']));

			$ltilog['contextid'] = $_SESSION['lti_context_id'];
			$ltilog['action'] = 'Establish LTI course connection';
			require_once('includes/TeacherAuditLog.php');
			TeacherAuditLog::addTracking(
				$destcid,
				"Course Settings Change",
				$destcid,
				$ltilog
			);
		} else {
			list($destcid, $copiedfromcid) = $stm->fetch(PDO::FETCH_NUM);
		}
		if ($destcid==$aidsourcecid) {
			//aid is in destination course - just make placement
			$aid = $_SESSION['place_aid'];
			//echo "here 1: $aid";
		} else {
			$foundaid = false;
			$aidtolookfor = intval($_SESSION['place_aid']);
			//aid is in original source course.  Let's see if we already copied it.
			if ($copiedfromcid == $aidsourcecid) {
				$anregex = '^([0-9]+:)?'.$aidtolookfor.'[[:>:]]';
				$stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:destcid");
				$stm->execute(array(':ancestors'=>$anregex, ':destcid'=>$destcid));
				if ($stm->rowCount()>0) {
					$aid = $stm->fetchColumn(0);
					//echo "here 2: $aid";
					$foundaid = true;
					//echo "found 1";
					//exit;
				}
			}
			if (!$foundaid) { //do course ancestor walk-back
				//need to look up ancestor depth
				$stm = $DBH->prepare("SELECT ancestors FROM imas_courses WHERE id=?");
				$stm->execute(array($destcid));
				$ancestors = explode(',', $stm->fetchColumn(0));
				$ciddepth = array_search($aidsourcecid, $ancestors);  //so if we're looking for 23, "20,24,23,26" would give 2 here.
				if ($ciddepth !== false) {
					array_unshift($ancestors, $destcid);  //add current course to front
					$foundsubaid = true;
					for ($i=$ciddepth;$i>=0;$i--) {  //starts one course back from aidsourcecid because of the unshift
						$stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:cid");
						$stm->execute(array(':ancestors'=>'^([0-9]+:)?'.$aidtolookfor.'[[:>:]]', ':cid'=>$ancestors[$i]));
						if ($stm->rowCount()>0) {
							$aidtolookfor = $stm->fetchColumn(0);
						} else {
							$foundsubaid = false;
							break;
						}
					}
					if ($foundsubaid) {
						$aid = $aidtolookfor;
						$foundaid = true;
						//echo "here 3: $aid";
						//echo "found 2";
						//exit;
					}
				}
			}
			if (!$foundaid) { //look for the assessment id anywhere in the ancestors list
				$anregex = '[[:<:]]'.intval($_SESSION['place_aid']).'[[:>:]]';
				$stm = $DBH->prepare("SELECT id,name,ancestors FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:destcid");
				$stm->execute(array(':ancestors'=>$anregex, ':destcid'=>$destcid));
				$res = $stm->fetchAll(PDO::FETCH_ASSOC);
				if (count($res)==1) {  //only one result - we found it
					$aid = $res[0]['id'];
					$foundaid = true;
					//echo "here 4: $aid";
					//echo "found 3";
					//exit;
				}
				if (!$foundaid && count($res)>0) { //multiple results - look for the identical name
					foreach ($res as $k=>$row) {
						$res[$k]['loc'] = strpos($row['ancestors'], (string) $aidtolookfor);
						if ($row['name']==$aidsourcename) {
							$aid = $row['id'];
							$foundaid = true;
							//echo "here 5: $aid";
							//echo "found 4";
							//exit;
							break;
						}
					}
				}
				if (!$foundaid && count($res)>0) { //no name match. pick the one with the assessment closest to the start
					usort($res, function($a,$b) { return $a['loc'] - $b['loc'];});
					$aid = $res[0]['id'];
					//echo "here 6: $aid";
					$foundaid = true;
					//echo "found 5";
					//exit;
				}
			}
			if (!$foundaid) {
				//aid is in source course.  Let's look and see if there's an assessment in destination with the same title.
				//this handles cases where an assessment was linked in from elsewhere and manually copied

				$stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE name=:name AND courseid=:courseid");
				$stm->execute(array(':name'=>$aidsourcename, ':courseid'=>$destcid));
				if ($stm->rowCount()>0) {
					$aid = $stm->fetchColumn(0);
					//echo "here 7: $aid";
				} else {
					// no assessment with same title - need to copy assessment from destination to source course
					require_once("includes/copyiteminc.php");
					$stm = $DBH->prepare("SELECT id FROM imas_items WHERE itemtype='Assessment' AND typeid=:typeid");
					$stm->execute(array(':typeid'=>$_SESSION['place_aid']));
					if ($stm->rowCount()==0) {
						reporterror(sprintf("Error.  Assessment ID %s not found."),"'{$_SESSION['place_aid']}'");
					}
					$sourceitemid = $stm->fetchColumn(0);
					$cid = $destcid;

					$stm = $DBH->prepare("SELECT itemorder,dates_by_lti,UIver FROM imas_courses WHERE id=:id");
					$stm->execute(array(':id'=>$cid));
					list($items,$datesbylti,$convertAssessVer) = $stm->fetch(PDO::FETCH_NUM);
					$items = unserialize($items);
					$newitem = copyitem($sourceitemid,array());
					$stm = $DBH->prepare("SELECT typeid FROM imas_items WHERE id=:id");
					$stm->execute(array(':id'=>$newitem));
					$aid = $stm->fetchColumn(0);

					$items[] = $newitem;
					$items = serialize($items);
					$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
					$stm->execute(array(':itemorder'=>$items, ':id'=>$cid));
					//echo "here 8: $aid";
				}
			}
		}
		$query = "INSERT INTO imas_lti_placements (org,contextid,linkid,placementtype,typeid) VALUES ";
		$query .= "(:org, :contextid, :linkid, :placementtype, :typeid)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':org'=>$_SESSION['ltiorg'], ':contextid'=>$_SESSION['lti_context_id'], ':linkid'=>$_SESSION['lti_resource_link_id'], ':placementtype'=>'assess', ':typeid'=>$aid));

		$linkparts = array('aid',$aid);

	} else if ($_SESSION['ltirole']=='instructor') {
		//don't need to do anything - will prompt for linking
	} else if ($_SESSION['lti_keytype']=='cc-of') {
		//do later
	} else {
		reporterror(_("This placement is not yet set up"));
	}
} else {
	$row = $stm->fetch(PDO::FETCH_NUM);
	if ($row[0]=='course') {
		$linkparts = array('cid',$row[1]);
	} else if ($row[0]=='assess') {
		$linkparts = array('aid',$row[1]);
	} /*
	   //don't have a way to store yet
	   //don't have a way to track copies yet
	   else if ($row[0]=='folder') {
		$pts = explode('-', $row[1]);
		$linkparts = array('folder',$row[1]);
	}*/ else {
		reporterror(_("Invalid placement type"));
	}

}
//** move inside of no placement?
if ($_SESSION['lti_keytype']=='cc-of') {
	$linkparts = array('folder',$_SESSION['open_folder'][0],$_SESSION['open_folder'][1]);

	//do checks to make sure it's OK to link into this course.
	$linkcid = intval($_SESSION['open_folder'][0]);

	//look to see if we've already linked this context_id with a course
	$stm = $DBH->prepare("SELECT courseid FROM imas_lti_courses WHERE contextid=:contextid AND org LIKE :org");
	$stm->execute(array(':contextid'=>$_SESSION['lti_context_id'], ':org'=>"$shortorg:%"));
	if ($stm->rowCount()==0) {
		//if instructor, see if the source course is ours
		if ($_SESSION['ltirole']=='instructor') {
			$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
			$stm->execute(array(':courseid'=>$linkcid, ':userid'=>$userid));
			if ($stm->rowCount()>0) {
				$stm = $DBH->prepare("INSERT INTO imas_lti_courses (org,contextid,courseid,contextlabel) VALUES (:org, :contextid, :courseid, :contextlabel)");
				$stm->execute(array(
					':org'=>$_SESSION['ltiorg'],
					':contextid'=>$_SESSION['lti_context_id'],
					':courseid'=>$linkcid,
					':contextlabel'=>$_SESSION['lti_context_label']));
				require_once('includes/TeacherAuditLog.php');
				TeacherAuditLog::addTracking(
					$linkcid,
					"Course Settings Change",
					$linkcid,
					[
						'action'=>'Establish LTI course connection',
						'type'=>'cc-of',
						'contextid'=>$_SESSION['lti_context_id']
					]
				);
			} else {
				reporterror(_("You are not an instructor on the course and folder this link is pointing to. Auto-copying is not currently supported for folder-level links."));
			}
		} else {
			reporterror(_("Course connection not established yet.  Notify your instructor they need to click this link to set it up."));
		}
	} else {
		$courselinkcid = $stm->fetchColumn(0);
		if ($courselinkcid != $linkcid) {
			reporterror(_("This course in the LMS is not associated with the course this link is pointing to."));
		}
	}
}

//is course level placement
if ($linkparts[0]=='cid') {
	$cid = intval($linkparts[1]);
	$stm = $DBH->prepare("SELECT available,ltisecret FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	if ($_SESSION['ltirole']!='instructor') {
		if (!($line['avail']==0 || $line['avail']==2)) {
			reporterror(_("This course is not available"));
		}
	}
} else if ($linkparts[0]=='aid') {   //is assessment level placement
	$aid = intval($linkparts[1]);
	$stm = $DBH->prepare("SELECT id,courseid,startdate,enddate,reviewdate,LPcutoff,avail,ltisecret,allowlate,date_by_lti FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	if ($line===false) {
		$diaginfo = "(Debug info: 3-$aid)";
		reporterror(_("This assignment does not appear to exist anymore.")." $diaginfo");
	}
	$cid = $line['courseid'];
	if (isset($_SESSION['lti_duedate']) && ($line['date_by_lti']==1 || $line['date_by_lti']==2)) {
		if ($_SESSION['ltirole']=='instructor') {
			$newdatebylti = 2; //set/keep as instructor-set
		} else {
			$newdatebylti = 3; //mark as student-set
		}
		//no default due date set yet, or is the instructor:  set the default due date
		$stm = $DBH->prepare("UPDATE imas_assessments SET startdate=:startdate,enddate=:enddate,date_by_lti=:datebylti WHERE id=:id");
		$stm->execute(array(':startdate'=>min($now, $_SESSION['lti_duedate']),
			':enddate'=>$_SESSION['lti_duedate'], ':datebylti'=>$newdatebylti, ':id'=>$aid));
		$line['enddate'] = $_SESSION['lti_duedate'];
	}
	if (!isset($_SESSION['lti_duedate']) && $line['date_by_lti']==1) {
		//assessment is set to use dates sent by LTI, but none was sent.  Give error for instructor.
		if ($_SESSION['ltirole'] == 'instructor') {
			$err = 'Your '.$installname.' course is set to use dates sent by the LMS, but the LMS did not send a date. ';
			$err .= 'Your "App Config" may be old and not contain the necessary info. ';
			$err .= 'In Canvas, go to Settings -> Apps -> View App Configurations ';
			$err .= 'and edit the '.$installname.' app. (If you cannot edit it, you may have to ask your ';
			$err .= 'Canvas admin to edit the app). In the "Custom Fields" box, ';
			$err .= 'enter this: canvas_assignment_due_at=$Canvas.assignment.dueAt.iso8601';
			reporterror($err);
		} else {
			$err = 'Tell your teacher that Canvas is not sending due dates.';
			reporterror($err);
		}
	}

	if ($_SESSION['ltirole']!='instructor') {
		//if ($line['avail']==0 || $now>$line['enddate'] || $now<$line['startdate']) {
		//	reporterror("This assessment is closed");
		//}
		if ($line['avail']==0) {
			//reporterror("This assessment is closed");
		}
		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,is_lti FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		$exceptionrow = $stm->fetch(PDO::FETCH_NUM);
		$useexception = false;
		if ($exceptionrow!=null) {
			//have exception.  Update using lti_duedate if needed
			if (isset($_SESSION['lti_duedate']) && $line['date_by_lti']>0 && $_SESSION['lti_duedate']!=$exceptionrow[1]) {
				//if new due date is later, or no latepass used, then update
				if ($exceptionrow[2]==0 || $_SESSION['lti_duedate']>$exceptionrow[1]) {
					$stm = $DBH->prepare("UPDATE imas_exceptions SET startdate=:startdate,enddate=:enddate,is_lti=1,islatepass=0 WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
					$stm->execute(array(':startdate'=>min($now, $line['startdate'], $exceptionrow[0]),
						':enddate'=>$_SESSION['lti_duedate'], ':userid'=>$userid, ':assessmentid'=>$aid));
				}
			}
			require_once("./includes/exceptionfuncs.php");
			$exceptionfuncs = new ExceptionFuncs($userid, $cid, true);
			$useexception = $exceptionfuncs->getCanUseAssessException($exceptionrow, $line, true);
		} else if ($line['date_by_lti']==3 && ($line['enddate']!=$_SESSION['lti_duedate'] || $now<$line['startdate'])) {
			//default dates already set by LTI, and users's date doesn't match - create new exception
			//also create if it's before the default assessment startdate - since they could access via LMS, it should be available.
			$exceptionrow = array(min($now,$_SESSION['lti_duedate']), $_SESSION['lti_duedate'], 0, 1);
			$stm = $DBH->prepare("INSERT INTO imas_exceptions (startdate,enddate,islatepass,is_lti,userid,assessmentid,itemtype) VALUES (?,?,?,?,?,?,'A')");
			$stm->execute(array_merge($exceptionrow, array($userid, $aid)));
			$useexception = true;
		}
		if ($exceptionrow!=null && $useexception) {
			if ($now<$exceptionrow[0] || $exceptionrow[1]<$now) { //outside exception dates
				if ($now > $line['startdate'] && $now < $line['reviewdate']) {
					$isreview = true;
				} else {
					//reporterror("This assessment is closed");
				}
			} else { //inside exception dates exception
				if ($line['enddate']<$now && ($exceptionrow[3]==0 || $exceptionrow[2]>0)) { //exception is for past-due-date
					$inexception = true; //only trigger if past due date for penalty
				}
			}
			$exceptionduedate = $exceptionrow[1];
		} else { //has no exception
			if ($now < $line['startdate'] || $line['enddate'] < $now) { //outside normal dates
				if ($now > $line['startdate'] && $now < $line['reviewdate']) {
					$isreview = true;
				} else {
					//reporterror("This assessment is closed");
				}
			}
		}

	}
} else if ($linkparts[0]=='folder') {
	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$linkparts[1]));
	if ($stm->rowCount()==0) {
		reporterror(_("invalid course identifier in folder view launch"));
	} else {
		$cid = intval($linkparts[1]);
		$row = $stm->fetch(PDO::FETCH_NUM);
		$items = unserialize($row[0]);
		function findfolder($items,$n,$loc) {
			foreach ($items as $k=>$b) {
				if (is_array($b)) {
					if ($b['id']==$n) {
						return $loc.'-'.($k+1);
					} else {
						$out = findfolder($b['items'],$n,$loc.'-'.($k+1));
						if ($out != '') {
							return $out;
						}
					}
				}
			}
			return '';
		}
		$loc = findfolder($items, $linkparts[2], '0');
		if ($loc=='') {
			reporterror(_("invalid folder identifier in folder view launch"));
		}
		$linkparts[3] = $loc;
	}
} else if ($_SESSION['ltirole']!='instructor') {
	reporterror(_("invalid key. unknown action type"));
}

//see if student is enrolled, if appropriate to action type
if ($linkparts[0]=='cid' || $linkparts[0]=='aid' || $linkparts[0]=='placein' || $linkparts[0]=='folder') {
	$latepasses = 0;
	if ($_SESSION['ltirole']=='instructor') {
		$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		if ($stm->rowCount() == 0) {
			//see if they're a tutor - that's just as good.
			$stm = $DBH->prepare("SELECT id FROM imas_tutors WHERE userid=:userid AND courseid=:courseid");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
			if ($stm->rowCount() == 0) {
				//reporterror("error - you are not an instructor or tutor on the $installname course this link is associated with.  If you are team-teaching this course, have the other instructor add you as a teacher or tutor on $installname then try again.");
				$stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES (:userid, :courseid)");
				$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
			}
		}
		$timelimitmult = 1;
	} else {
		$stm = $DBH->prepare("SELECT timelimitmult,latepass FROM imas_students WHERE userid=:userid AND courseid=:courseid");
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		if ($stm->rowCount() == 0) {
			$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
			if ($stm->rowCount() == 0) {
				$stm = $DBH->prepare("SELECT id FROM imas_tutors WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
				if ($stm->rowCount() == 0) {
					$stm = $DBH->prepare("SELECT deflatepass FROM imas_courses WHERE id=:id");
					$stm->execute(array(':id'=>$cid));
					$deflatepass = $stm->fetchColumn(0);
					$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,section,latepass) VALUES (:userid, :courseid, :section, :latepass)");
                    $stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':section'=>$_SESSION['lti_context_label'], ':latepass'=>$deflatepass));
                    require_once('./includes/setSectionGroups.php');
                    setSectionGroups($userid, $cid, $_SESSION['lti_context_label']);
				}
			} else {
				$_SESSION['ltirole']='instructor';
				$setstuviewon = true;
			}
			$timelimitmult = 1;
		} else {
			list($timelimitmult,$latepasses) = $stm->fetch(PDO::FETCH_NUM);
		}
	}
}

//check if db session entry exists for session
$promptforsettings = false;
$SESS = $_SESSION;
if (!empty($_SESSION['userid'])) {	//check that same userid, and that we're not jumping on someone else's
	//existing session.  If so, then we need to create a new session.
	//also, if session did not have ltiuserid already, must be jumping non-LTI to LTI

	if ($_SESSION['userid'] != $userid || !$atstarthasltiuserid) {
		session_destroy();
		session_start();
		session_regenerate_id();
		$sessionid = session_id();
		//setcookie(session_name(),session_id(),0,'','',false,true );
		$_SESSION = array();
		$createnewsession = true;
	} else {
		//already have session.  Don't need to create one
		if (!isset($_SESSION['mathdisp'])) {
			//for some reason settings are not set, so reload from user prefs
			require_once("$curdir/includes/userprefs.php");
			generateuserprefs(true);
		}
		$createnewsession = false;
	}
} else {
	$_SESSION = array();
	$createnewsession = true;
}

//if assessment, going to check for timelimit
if ($linkparts[0]=='aid') {
	$stm = $DBH->prepare("SELECT timelimit,ver FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($timelimit, $aver) = $stm->fetch(PDO::FETCH_NUM);
	$timelimit = abs($timelimit * $timelimitmult);
	if ($timelimit>0 && $aver==1) {
		 if ($timelimit>3600) {
			$tlhrs = floor($timelimit/3600);
			$tlrem = $timelimit % 3600;
			$tlmin = floor($tlrem/60);
			$tlsec = $tlrem % 60;
			$tlwrds = "$tlhrs hour";
			if ($tlhrs > 1) { $tlwrds .= "s";}
			if ($tlmin > 0) { $tlwrds .= ", $tlmin minute";}
			if ($tlmin > 1) { $tlwrds .= "s";}
			if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
			if ($tlsec > 1) { $tlwrds .= "s";}
		} else if ($timelimit>60) {
			$tlmin = floor($timelimit/60);
			$tlsec = $timelimit % 60;
			$tlwrds = "$tlmin minute";
			if ($tlmin > 1) { $tlwrds .= "s";}
			if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
			if ($tlsec > 1) { $tlwrds .= "s";}
		} else {
			$tlwrds = $timelimit . " second(s)";
		}
	} else {
		$tlwrds = '';
	}
	//this sessiondata tells WAMAP to limit access to the specific resouce requested

	$_SESSION['ltitlwrds'] = $tlwrds;
	$_SESSION['ltiitemtype']=0;
	$_SESSION['ltiitemver']=$aver;
	$_SESSION['ltiitemid'] = $aid;

	$_SESSION['lticanuselatepass'] = false;
	if ($_SESSION['ltirole']!='instructor' && $line['allowlate']>0) {
		$stm = $DBH->prepare("SELECT latepasshrs FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$latepasshrs = $stm->fetchColumn(0);
		require_once("./includes/exceptionfuncs.php");
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $latepasses, $latepasshrs);
		list($useexception, $canundolatepass, $canuselatepass) = $exceptionfuncs->getCanUseAssessException($exceptionrow, $line);
		$_SESSION['lticanuselatepass'] = $canuselatepass;
	}

}  else if ($linkparts[0]=='cid') { //is cid
	$_SESSION['ltiitemtype']=1;
	$_SESSION['ltiitemid'] = $cid;
} else if ($linkparts[0]=='folder') { //is folder content view
	$_SESSION['ltiitemtype']=3;
	$_SESSION['ltiitemid'] = array($linkparts[2],$linkparts[3],$cid);
} else {
	$_SESSION['ltiitemtype']=-1;
}
$_SESSION['ltiorg'] = $SESS['ltiorg'];
$_SESSION['ltirole'] = $SESS['ltirole'];
$_SESSION['lti_context_id']  = $SESS['lti_context_id'];
$_SESSION['lti_resource_link_id']  = $SESS['lti_resource_link_id'];
if ($linkparts[0]=='aid') {
	$_SESSION['lti_lis_result_sourcedid'.$aid]  = $SESS['lti_lis_result_sourcedid'];
}
$_SESSION['lti_outcomeurl']  = $SESS['lti_outcomeurl'];
$_SESSION['lti_context_label'] = $SESS['lti_context_label'];
$_SESSION['lti_launch_get'] = $SESS['lti_launch_get'];
$_SESSION['lti_key'] = $SESS['lti_key'];
$_SESSION['lti_keytype'] = $SESS['lti_keytype'];
$_SESSION['lti_keylookup'] = $SESS['ltilookup'];
$_SESSION['lti_origkey'] = $SESS['ltiorigkey'];
if (isset($SESS['lti_duedate'])) {
	$_SESSION['lti_duedate'] = $SESS['lti_duedate'];
}
if (isset($SESS['selection_return'])) {
	$_SESSION['lti_selection_return'] = $SESS['selection_return'];
	$_SESSION['lti_selection_targets'] = $SESS['selection_targets'];
	$_SESSION['lti_selection_return_format'] = $SESS['selection_return_format'];
	$_SESSION['lti_selection_type'] = $SESS['selection_type'];
	$_SESSION['lti_selection_data'] = $SESS['selection_data'];
}

if (isset($setstuviewon) && $setstuviewon==true) {
	$_SESSION['stuview'] = 0;
}

if ($_SESSION['lti_keytype']=='gc') {
	$_SESSION['lti_launch_get']['cid'] = $linkparts[1];
}

$_SESSION['userid'] = $userid;
$_SESSION['time'] = $now;

if (!$promptforsettings && !$createnewsession && !($linkparts[0]=='aid' && $tlwrds != '')) {

	//redirect now if already have session and no timelimit
	$now = time();
	$stm = $DBH->prepare("UPDATE imas_users SET lastaccess=:lastaccess WHERE id=:id");
	$stm->execute(array(':lastaccess'=>$now, ':id'=>$userid));

	if ($linkparts[0]=='aid') { //is aid
		if ($_SESSION['ltirole'] == 'learner') {
			$stm = $DBH->prepare("INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES (:userid, :courseid, :type, :typeid, :viewtime, :info)");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':type'=>'assesslti', ':typeid'=>$aid, ':viewtime'=>$now, ':info'=>''));
		}
		if ($aver > 1) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assess2/?cid=$cid&aid=$aid");
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assessment/showtest.php?cid=$cid&id=$aid&ltilaunch=true");
		}
	} else if ($linkparts[0]=='cid') { //is cid
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid");
	} else if ($linkparts[0]=='folder') {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?checksess=true&cid=$cid&folder=".$linkparts[3]);
	} else { //will only be instructors hitting this option
		header('Location: ' . $GLOBALS['basesiteurl'] . "/ltihome.php");
	}
	exit;
} else {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/bltilaunch.php?accessibility=ask");
	exit;
}





/*end new behavior */
} else {
/*use old behavior for any other request*/



//check to see if accessiblity page is posting back
if (isset($_GET['launch'])) {
	if (empty($_SESSION['userid'])) {
		reporterror(_("No authorized session exists. This is most likely caused by your browser blocking third-party cookies.  Please adjust your browser settings and try again. If you are using Safari, you may need to disable Prevent Cross-Site Tracking."));
	}
	$userid = $_SESSION['userid'];
	if (empty($_POST['tzname']) && $_POST['tzoffset']=='') {
		echo _('Uh oh, something went wrong.  Please go back and try again');
		exit;
	}
	if (isset($_POST['tzname'])) {
		$_SESSION['logintzname'] = $_POST['tzname'];
	}

	require_once("$curdir/includes/userprefs.php");
	generateuserprefs();

	$now = time();
	$stm = $DBH->prepare("UPDATE imas_users SET lastaccess=:lastaccess WHERE id=:id");
	$stm->execute(array(':lastaccess'=>$now, ':id'=>$userid));

	if (isset($_POST['tzname'])) {
		$tzname = $_POST['tzname'];
	} else {
		$tzname = '';
	}
	$_SESSION['tzoffset'] = $_POST['tzoffset'];
    $_SESSION['tzname'] = $tzname;
    if (isset($CFG['static_server']) && !empty($_POST['static_check'])) {
        $_SESSION['static_ok'] = 1;
    }

	$keyparts = explode('_',$_SESSION['ltikey']);
	if ($_SESSION['ltiitemtype']==0) { //is aid
		$aid = $_SESSION['ltiitemid'];
		$stm = $DBH->prepare("SELECT courseid,ver FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$aid));
		list($cid, $aver) = $stm->fetch(PDO::FETCH_NUM);
		if ($cid===false) {
			$diaginfo = "(Debug info: 4-$aid)";
			reporterror(_("This assignment does not appear to exist anymore.")." $diaginfo");
		}
		if ($_SESSION['ltirole'] == 'learner') {
			$stm = $DBH->prepare("INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES (:userid, :courseid, :type, :typeid, :viewtime, :info)");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':type'=>'assesslti', ':typeid'=>$aid, ':viewtime'=>$now, ':info'=>''));
		}
		if ($aver > 1) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assess2/?cid=$cid&aid=$aid");
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assessment/showtest.php?cid=$cid&id=$aid");
		}
	} else if ($_SESSION['ltiitemtype']==1) { //is cid
		$cid = $_SESSION['ltiitemid'];
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid");
	} else if ($_SESSION['ltiitemtype']==2) {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/index.php");
	} else if ($_SESSION['ltiitemtype']==3) {
		$cid = $_SESSION['ltiitemid'][2];
		$folder = $_SESSION['ltiitemid'][1];
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid&folder=".$folder);
	} else { //will only be instructors hitting this option
		header('Location: ' . $GLOBALS['basesiteurl'] . "/ltihome.php");
	}
	exit;
} else if (isset($_GET['accessibility'])) {
	if (empty($_SESSION['userid'])) {
		reporterror(_("No authorized session exists. This is most likely caused by your browser blocking third-party cookies.  Please adjust your browser settings and try again. If you are using Safari, you may need to disable Prevent Cross-Site Tracking."));
	}
	$userid = $_SESSION['userid'];

	//time to output a postback to capture tzoffset and math/graph settings
	$pref = 0;
	/*if (isset($_COOKIE['mathgraphprefs'])) {
		 $prefparts = explode('-',$_COOKIE['mathgraphprefs']);
		 if ($prefparts[0]==2 && $prefparts[1]==2) { //img all
			$pref = 3;
		 } else if ($prefparts[0]==2) { //img math
			 $pref = 4;
		 } else if ($prefparts[1]==2) { //img graph
			 $pref = 2;
		 }
	}*/
	$flexwidth = true;
	$nologo = true;
	$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/jstz_min.js\" ></script>";
	require("header.php");
	echo "<h3>Connecting to $installname</h3>";
	echo "<form id=\"postbackform\" method=\"post\" action=\"".$imasroot."/bltilaunch.php?launch=true\" ";
	if ($_SESSION['ltiitemtype']==0 && $_SESSION['ltitlwrds'] != '') {
		echo "onsubmit='return confirm(\"This assessment has a time limit of ".Sanitize::encodeStringForDisplay($_SESSION['ltitlwrds']).".  Click OK to start or continue working on the assessment.\")' >";
		echo "<p class=noticetext>This assessment has a time limit of ".Sanitize::encodeStringForDisplay($_SESSION['ltitlwrds']).".</p>";
		echo '<div class="textright"><input type="submit" value="Continue" /></div>';

		if ($_SESSION['lticanuselatepass']) {
			echo "<p><a href=\"$imasroot/course/redeemlatepass.php?from=ltitimelimit&cid=".Sanitize::encodeUrlParam($_SESSION['ltiitemcid'])."&aid=".Sanitize::encodeUrlParam($_SESSION['ltiitemid'])."\">", _('Use LatePass'), "</a></p>";
		}
	} else {
		echo ">";
	}
	?>
	<div id="settings"><noscript>JavaScript is not enabled.  JavaScript is required for <?php echo $installname; ?>.
	Please enable JavaScript and reload this page</noscript></div>
	<input type="hidden" id="tzoffset" name="tzoffset" value="" />
	<input type="hidden" id="tzname" name="tzname" value="">
	<script type="text/javascript">
		 document.cookie = 'PHPSESSID=; path=; expires=' + new Date(0).toUTCString();
		 $(function() {
			var thedate = new Date();
			document.getElementById("tzoffset").value = thedate.getTimezoneOffset();
			var tz = jstz.determine();
			document.getElementById("tzname").value = tz.name();
			<?php
			if ($_SESSION['ltiitemtype']!=0 || $_SESSION['ltitlwrds'] == '') {
				//auto submit the form
				echo 'document.getElementById("postbackform").submit();';
			}
			?>
		});
	</script>
	</form>
	<?php
	require("footer.php");
	exit;

} else if (isset($_GET['userinfo']) && isset($_SESSION['ltiuserid'])) {
	//check to see if new LTI user is posting back user info
	$ltiuserid = $_SESSION['ltiuserid'];
	$ltiorg = $_SESSION['ltiorg'];
	$ltirole = $_SESSION['ltirole'];
	$keyparts = explode('_',$_SESSION['ltikey']);
	$name_only = false;
	if (count($keyparts)==1 && $ltirole=='learner') {
		$name_only = true;
	} else if (count($keyparts)>2 && $keyparts[2]==1 && $ltirole=='learner') {
		$name_only = true;
	}
	if ($ltirole=='learner' || $_SESSION['lti_keyrights']==76 || $_SESSION['lti_keyrights']==77) {
		$allow_acctcreation = true;
	} else {
		$allow_acctcreation = false;
	}
	if ($_GET['userinfo']=='set') {
		if (isset($CFG['GEN']['newpasswords'])) {
			require_once("includes/password.php");
		}
		//check input
		$infoerr = '';
		unset($userid);
		if ($name_only) {
			if (empty($_POST['firstname']) || empty($_POST['lastname'])) {
				$infoerr = 'Please provide your name';
			}
			$_POST['email'] = 'none@none.com';
			$msgnot = 0;
		} else {
			if (!empty($_POST['curSID']) && !empty($_POST['curPW'])) {
				//provided current SID/PW pair
				$stm = $DBH->prepare("SELECT password,id FROM imas_users WHERE SID=:SID");
				$stm->execute(array(':SID'=>$_POST['curSID']));
				if ($stm->rowCount()==0) {
					$infoerr = 'Username (key) is not valid';
				} else {
					list($realpw,$queryuserid) = $stm->fetch(PDO::FETCH_NUM);
					if (((!isset($CFG['GEN']['newpasswords']) || $CFG['GEN']['newpasswords']!='only') && ($realpw == md5($_POST['curPW'])))
					  || (isset($CFG['GEN']['newpasswords']) && password_verify($_POST['curPW'],$realpw)) ) {
						$userid = $queryuserid;
					} else {
						$infoerr = 'Existing username/password provided are not valid.';
					}
				}
			} else {
				if (!$allow_acctcreation) {
					$infoerr = 'Must link to an existing account';
				} else {
					if (!$allow_acctcreation) {
						$infoerr = 'Must link to an existing account';
					} else {
						require_once(__DIR__.'/includes/newusercommon.php');
						$infoerr = checkNewUserValidation();
						//new info
						if (isset($_POST['msgnot'])) {
							$msgnot = 1;
						} else {
							$msgnot = 0;
						}
						if (isset($CFG['GEN']['newpasswords'])) {
							$md5pw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
						} else {
							$md5pw = md5($_POST['pw1']);
						}
					}
				}
			}
		}
		if ($infoerr=='') { // no error, so create!
			$stm = $DBH->prepare("INSERT INTO imas_ltiusers (org,ltiuserid) VALUES (:org, :ltiuserid)");
			$stm->execute(array(':org'=>$ltiorg, ':ltiuserid'=>$ltiuserid));
			$localltiuser = $DBH->lastInsertId();
			if (!isset($userid) && $allow_acctcreation) {
				if ($name_only) {
					//make up a username/password for them
					$_POST['SID'] = 'lti-'.$localltiuser;
					$md5pw = 'pass'; //totally unusable since not md5'ed
				}
				if ($ltirole=='instructor') {
					if (isset($CFG['LTI']['instrrights'])) {
						$rights = $CFG['LTI']['instrrights'];
					} else {
						$rights = 40;
					}
					$newgroupid = intval($_SESSION['lti_keygroupid']);
					$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify,groupid) VALUES ";
					$query .= "(:SID, :password, :rights, :FirstName, :LastName, :email, :msgnotify, :groupid)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw, ':rights'=>$rights,
						':FirstName'=>Sanitize::stripHtmlTags($_POST['firstname']),
						':LastName'=>Sanitize::stripHtmlTags($_POST['lastname']),
						':email'=>Sanitize::emailAddress($_POST['email']),
						':msgnotify'=>$msgnot, ':groupid'=>$newgroupid));
				} else {
					$rights = 10;
					$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify) VALUES ";
					$query .= "(:SID, :password, :rights, :FirstName, :LastName, :email, :msgnotify)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw, ':rights'=>$rights,
						':FirstName'=>Sanitize::stripHtmlTags($_POST['firstname']),
						':LastName'=>Sanitize::stripHtmlTags($_POST['lastname']),
						':email'=>Sanitize::emailAddress($_POST['email']),
						':msgnotify'=>$msgnot));
				}
				$userid = $DBH->lastInsertId();
			}
			$stm = $DBH->prepare("UPDATE imas_ltiusers SET userid=:userid WHERE id=:id");
			$stm->execute(array(':userid'=>$userid, ':id'=>$localltiuser));
		} else {
			//uh-oh, had an error.  Better ask for user info again
			$askforuserinfo = true;
		}
	} else {
		//ask for student info
		$nologo = true;
		$flexwidth = true;
		$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/jquery.validate.min.js?v=122917"></script>';
		require("header.php");
		if (isset($infoerr)) {
			echo '<p class=noticetext>'.Sanitize::encodeStringForDisplay($infoerr).'</p>';
		}

		echo "<form method=\"post\" id=\"pageform\" class=\"limitaftervalidate\" action=\"".$imasroot."/bltilaunch.php?userinfo=set\" ";
		if ($name_only) {
			//using LTI for authentication; don't need username/password
			//only request name
			echo "<p>Please provide a little information about yourself:</p>";
			echo "<span class=form><label for=\"firstname\">Enter First Name:</label></span> <input class=form type=text size=20 id=firstname name=firstname autocomplete=\"given-name\"><BR class=form>\n";
			echo "<span class=form><label for=\"lastname\">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname autocomplete=\"family-name\"><BR class=form>\n";
			echo "<div class=submit><input type=submit value='Submit'></div>\n";
			echo '<script type="text/javascript"> $(function() {
				$("#pageform").validate({
					rules: {
						firstname: {required: true},
						lastname: {required: true}
					},
					submitHandler: function(el,evt) {return submitlimiter(evt);}
				});
			});</script>';
		} else {
			$deffirst = '';
			$deflast = '';
			$defemail = '';
			if (isset($_SESSION['LMSfirstname'])) {
				$deffirst = $_SESSION['LMSfirstname'];
			}
			if (isset($_SESSION['LMSlastname'])) {
				$deflast = $_SESSION['LMSlastname'];
			}
			if (isset($_SESSION['LMSemail'])) {
				$defemail = $_SESSION['LMSemail'];
			}

			if (isset($_SESSION['ltiorgname'])) {
				$ltiorgname = $_SESSION['ltiorgname'];
			} else {
				$ltiorgname = $ltiorg;
			}

			//strip off prepended org info before display
			$ltiorgparts = explode(':',$ltiorgname);
			if (count($ltiorgparts)>2) {
				array_shift($ltiorgparts);
				$ltiorgname = implode(':',$ltiorgparts);
			} else {
				$ltiorgname = $ltiorgparts[1];
			}

			//tying LTI to IMAthAS account
			//give option to provide existing account info, or provide full new student info
			if ($allow_acctcreation) {
				echo "<p>If you already have an account on $installname, enter your username and ";
				echo "password below to enable automated signon from ".Sanitize::encodeStringForDisplay($ltiorgname)."</p>";
			} else {
				echo "<p>Enter your username and ";
				echo "password for $installname below to enable automated signon from ".Sanitize::encodeStringForDisplay($ltiorgname)."</p>";
			}
			echo "<span class=form><label for=\"curSID\">".Sanitize::encodeStringForDisplay($loginprompt).":</label></span> <input class=form type=text size=12 id=\"curSID\" name=\"curSID\"><BR class=form>\n";
			echo "<span class=form><label for=\"curPW\">Password:</label></span><input class=form type=password size=20 id=\"curPW\" name=\"curPW\"><BR class=form>\n";
			echo "<div class=submit><input type=submit value='Sign In'></div>\n";
			if ($allow_acctcreation) {
				echo "<p>If you do not already have an account on $installname, provide the information below to create an account ";
				echo "and enable automated signon from ".Sanitize::encodeStringForDisplay($ltiorgname)."</p>";
				echo "<span class=form><label for=\"SID\">".Sanitize::encodeStringForDisplay($longloginprompt).":</label></span> <input class=form type=text size=12 id=SID name=SID><BR class=form>\n";
				echo "<span class=form><label for=\"pw1\">Choose a password:</label></span><input class=form type=password size=20 id=pw1 name=pw1><BR class=form>\n";
				echo "<span class=form><label for=\"pw2\">Confirm password:</label></span> <input class=form type=password size=20 id=pw2 name=pw2><BR class=form>\n";
				echo "<span class=form><label for=\"firstname\">Enter First Name:</label></span> <input class=form type=text autocomplete=\"given-name\" value=\"".Sanitize::encodeStringForDisplay($deffirst)."\" size=20 id=firstnam name=firstname><BR class=form>\n";
				echo "<span class=form><label for=\"lastname\">Enter Last Name:</label></span> <input class=form type=text autocomplete=\"family-name\" value=\"".Sanitize::encodeStringForDisplay($deflast)."\" size=20 id=lastname name=lastname><BR class=form>\n";
				echo "<span class=form><label for=\"email\">Enter E-mail address:</label></span>  <input class=form type=email autocomplete=\"email\" value=\"".Sanitize::encodeStringForDisplay($defemail)."\" size=60 id=email name=email><BR class=form>\n";
				echo "<span class=form><label for=\"msgnot\">Notify me by email when I receive a new message:</label></span><input class=floatleft type=checkbox id=msgnot name=msgnot /><BR class=form>\n";
				echo "<div class=submit><input type=submit value='Create Account'></div>\n";
				require_once(__DIR__.'/includes/newusercommon.php');
				$requiredrules = array(
					'curSID'=>'{depends: function(element) {return $("#SID").val()==""}}',
					'curPW'=>'{depends: function(element) {return $("#SID").val()==""}}',
					'SID'=>'{depends: function(element) {return $("#SID").val()!=""}}',
					'pw1'=>'{depends: function(element) {return $("#SID").val()!=""}}',
					'pw2'=>'{depends: function(element) {return $("#SID").val()!=""}}',
					'firstname'=>'{depends: function(element) {return $("#SID").val()!=""}}',
					'lastname'=>'{depends: function(element) {return $("#SID").val()!=""}}',
					'email'=>'{depends: function(element) {return $("#SID").val()!=""}}',
				);
				showNewUserValidation('pageform',array('curSID','curPW'), $requiredrules);
			} else {
				echo "<p>If you do not already have an account on $installname, please visit the site to request an account.</p>";
				echo '<script type="text/javascript"> $(function() {
					$("#pageform").validate({
						rules: {
							curSID: {required: true},
							curPW: {required: true}
						},
						submitHandler: function(el,evt) {return submitlimiter(evt);}
					});
				});</script>';
			}
		}
		echo "</form>\n";
		require("footer.php");
		exit;

	}

} else if (isset($_SESSION['ltiuserid']) && !isset($_REQUEST['oauth_consumer_key'])) {
	//refreshed this page from accessibility options page so session already exists
	// (if user_id is set, then is new LTI request, so want to pass down to OAuth)
	//pull necessary info and continue
	if (empty($_SESSION['userid'])) {
		reporterror(_("No session recorded"));
	} else {
		$userid = $_SESSION['userid'];
	}

	$keyparts = explode('_',$_SESSION['ltikey']);
} else {
	//not postback of new LTI user info, so must be fresh request

	//verify necessary POST values for LTI.  OAuth specific will be checked later
	if (empty($_REQUEST['lti_version'])) {
		reporterror(_("Insufficient launch information. This might indicate your browser is set to restrict third-party cookies. Check your browser settings and try again. If you are using Safari, you may need to disable Prevent Cross-Site Tracking."));
	}
	if (empty($_REQUEST['user_id'])) {
		if (isset($_REQUEST['relaunch_url'])) {
			do112relaunch();
		}
		reporterror(_("Unable to launch - User information not provided (user_id is required)"));
	} else {
		$ltiuserid = $_REQUEST['user_id'];
	}
	if (!empty($_REQUEST['tool_state'])) {
		verify112relaunch();
	}

	if (empty($_REQUEST['context_id'])) {
		reporterror(_("Unable to launch - Course information not provided (context_id is required)"));
	}

	if (isset($_SESSION['ltiuserid']) && $_SESSION['ltiuserid']!=$ltiuserid) {
		//new user - need to clear out session
		session_destroy();
		session_start();
		session_regenerate_id();
		$sessionid = session_id();
		$_SESSION = array();
		//setcookie(session_name(),session_id(),0,'','',false,true );
	}

	/*if (empty($_REQUEST['roles'])) {
		reporterror("roles is required");
	} else {
		$ltirole = $_REQUEST['roles'];
	}*/
	if (empty($_REQUEST['tool_consumer_instance_guid'])) {
		$ltiorg = 'Unknown';
	} else {
		$ltiorg = $_REQUEST['tool_consumer_instance_guid'];
	}
	if (empty($_REQUEST['oauth_consumer_key'])) {
		reporterror(_("Unable to launch - oauth_consumer_key (resource key) is required"));
	} else {
		$ltikey = $_REQUEST['oauth_consumer_key'];
	}

	//check OAuth Signature!
	require_once 'includes/OAuth.php';
	require_once 'includes/ltioauthstore.php';

	//set up OAuth
	$store = new IMathASLTIOAuthDataStore();
	$server = new OAuthServer($store);
	$method = new OAuthSignatureMethod_HMAC_SHA1();
	$server->add_signature_method($method);
	$request = OAuthRequest::from_request();
	$base = $request->get_signature_base_string();
	try {
		$requestinfo = $server->verify_request($request);
	} catch (Exception $e) {
		reporterror($e->getMessage());
	}
	$store->mark_nonce_used($request);

	$keyparts = explode('_',$ltikey);
	$_SESSION['ltiorigkey'] = $ltikey;

	// prepend ltiorg with courseid or sso+userid to prevent cross-instructor hacking
	if ($keyparts[0]=='cid' || $keyparts[0]=='placein' || $keyparts[0]=='LTIkey') {  //cid:org
		$_SESSION['ltilookup'] = 'c';
		$ltiorg = $keyparts[1].':'.$ltiorg;
		if ($keyparts[0]=='placein' || $keyparts[0]=='LTIkey') {
			$keytype = 'gc';
		} else {
			$keytype = 'c';
		}
		if (isset($_REQUEST['custom_place_aid'])) { //common catridge blti placement using cid_### or placein_### key type
			$placeaid = intval($_REQUEST['custom_place_aid']);
			$stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$placeaid));
			$sourcecid = $stm->fetchColumn(0);
			if ($sourcecid===false) {
				$diaginfo = "(Debug info: 5-$placeaid)";
				reporterror(_("This assignment does not appear to exist anymore.")." $diaginfo");
			}
			if ($keyparts[1]==$sourcecid) { //is key is for source course; treat like aid_### placement
				$keyparts[0] = 'aid';
				$keyparts[1] = $placeaid;
				$ltikey = implode('_',$keyparts);
				$keytype = 'a';
			} else {  //key is for a different course; mark as cc placement
				$keytype = 'cc-c';
				$_SESSION['place_aid'] = array($sourcecid,$_REQUEST['custom_place_aid']);
			}
		} else if (isset($_REQUEST['custom_open_folder'])) {
			$keytype = 'cc-of';
			$parts = explode('-',$_REQUEST['custom_open_folder']);
			$sourcecid = $parts[0];
			$_SESSION['view_folder'] = array($sourcecid,$parts[1]);
		}
	} else if ($keyparts[0]=='aid') {   //also cid:org
		$_SESSION['ltilookup'] = 'a';
		$aid = intval($keyparts[1]);
		$stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$aid));
		$ltiorg = $stm->fetchColumn(0) . ':' . $ltiorg;
		$keytype = 'a';
	} else if ($keyparts[0]=='sso') {  //ssouserid:org
		$_SESSION['ltilookup'] = 'u';
		$ltiorg = $keyparts[0].$keyparts[1]. ':' . $ltiorg;
		$keytype = 's';
	} else {
		$_SESSION['ltilookup'] = 'u';
		$ltiorg = $ltikey.':'.$ltiorg;
		$keytype = 'g';
		if (isset($_REQUEST['custom_place_aid'])) {
			$placeaid = intval($_REQUEST['custom_place_aid']);
			$keytype = 'cc-g';
			$stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$placeaid));
			$sourcecid = $stm->fetchColumn(0);
			if ($sourcecid===false) {
				$diaginfo = "(Debug info: 6-$placeaid)";
				reporterror(_("This assignment does not appear to exist anymore.")." $diaginfo");
			}
			$_SESSION['place_aid'] = array($sourcecid,$_REQUEST['custom_place_aid']);
		} else if (isset($_REQUEST['custom_view_folder'])) {
			$keytype = 'cc-vf';
			$parts = explode('-',$_REQUEST['custom_view_folder']);
			$sourcecid = $parts[0];
			$_SESSION['view_folder'] = array($sourcecid,$parts[1]);
		} else if (isset($_REQUEST['custom_open_folder'])) {
			$keytype = 'cc-of';
			$parts = explode('-',$_REQUEST['custom_open_folder']);
			$sourcecid = $parts[0];
			$_SESSION['view_folder'] = array($sourcecid,$parts[1]);
		}
    }
   

	//Store all LTI request data in session variable for reuse on submit
	//if we got this far, secret has already been verified
	$_SESSION['ltiuserid'] = $ltiuserid;
	$_SESSION['ltiorg'] = $ltiorg;
	$ltirole = strtolower($_REQUEST['roles']);
	if (strpos($ltirole,'instructor')!== false || strpos($ltirole,'administrator')!== false || strpos($ltirole,'contentdeveloper')!== false) {
		$ltirole = 'instructor';
	} else {
		$ltirole = 'learner';
	}

	$_SESSION['ltirole'] = $ltirole;
    $_SESSION['lti_context_id'] = $_REQUEST['context_id'];
    if (!empty($_REQUEST['context_label'])) {
        $_SESSION['lti_context_label'] = $_REQUEST['context_label'];
    } else if (!empty($_REQUEST['context_title'])) {
        $_SESSION['lti_context_label'] = $_REQUEST['context_title'];
    } else {
        $_SESSION['lti_context_label'] = $_REQUEST['context_id'];
    }
	$_SESSION['lti_resource_link_id'] = $_REQUEST['resource_link_id'];
	$_SESSION['lti_lis_result_sourcedid'] = $_REQUEST['lis_result_sourcedid'];
	$_SESSION['lti_outcomeurl'] = $_REQUEST['lis_outcome_service_url'];
	$_SESSION['lti_key'] = $ltikey;
	$_SESSION['lti_keytype'] = $keytype;
	$_SESSION['lti_keyrights'] = $requestinfo[0]->rights;
	$_SESSION['lti_keygroupid'] = intval($requestinfo[0]->groupid);
	if (isset($_REQUEST['selection_directive']) && $_REQUEST['selection_directive']=='select_link') {
		$_SESSION['selection_return'] = $_REQUEST['launch_presentation_return_url'];
        $_SESSION['selection_return_format'] = "Canvas";
        unset($_SESSION['place_aid']);
	}
	if (isset($_REQUEST['lti_message_type']) && $_REQUEST['lti_message_type']=='ContentItemSelectionRequest') {
		$_SESSION['selection_return'] = $_REQUEST['content_item_return_url'];
		$_SESSION['selection_targets'] = $_REQUEST['accept_presentation_document_targets'];
		$_SESSION['selection_return_format'] = "IMSdeeplink";
		if (isset($_REQUEST['ltiseltype']) && $_REQUEST['ltiseltype']=='assn') {
			$_SESSION['selection_type'] = 'assn';
		} else if (isset($_REQUEST['ltiseltype']) && $_REQUEST['ltiseltype']=='link') {
			$_SESSION['selection_type'] = 'link';
		} else {
			$_SESSION['selection_type'] = 'all';
		}
        $_SESSION['selection_data'] = @$_REQUEST['data'];
        unset($_SESSION['place_aid']);
    }
    unset($_SESSION['lti_duedate']);
	if (!isset($_REQUEST['custom_canvas_assignment_due_at'])) {
		if (isset($_REQUEST['custom_assignment_due_at'])) {
			$_REQUEST['custom_canvas_assignment_due_at'] = $_REQUEST['custom_assignment_due_at'];
		}
    }
	if (isset($_REQUEST['custom_canvas_assignment_due_at'])) {
		$duedate = strtotime($_REQUEST['custom_canvas_assignment_due_at']);
		if ($duedate !== false) {
			$_SESSION['lti_duedate'] = $duedate;
		} else {
			$_SESSION['lti_duedate'] = 2000000000;
		}
	}

	//look if we know this student
	$orgparts = explode(':',$ltiorg);  //THIS was added to avoid issues when GUID change, while still storing it
	$shortorg = $orgparts[0];
	$query = "SELECT lti.userid FROM imas_ltiusers AS lti LEFT JOIN imas_users as iu ON lti.userid=iu.id ";
	$query .= "WHERE lti.org LIKE :org AND lti.ltiuserid=:ltiuserid ";
	if ($ltirole!='learner') {
		//if they're a teacher, make sure their imathas account is too. If not, we'll act like we don't know them
		//and require a new connection
		$query .= "AND iu.rights>19 ";
	}
	//if multiple accounts, use student one first (if not $ltirole of teacher) then higher rights.
	//if there was a mixup and multiple records were created, use the first one
	$query .= "ORDER BY iu.rights, lti.id";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':org'=>"$shortorg:%", ':ltiuserid'=>$ltiuserid));
	if ($stm->rowCount() > 0) {
		$userid = $stm->fetchColumn(0);
	} else {
		//student is not known.  Bummer.  Better figure out what to do with them :)

		//go ahead and create the account if:
		//has name information (should we skip?)
		//domain level placement and (student or instructor with acceptable key rights)
		//a _1 type placement and (student or instructor with acceptable key rights)

		if (((!empty($_REQUEST['lis_person_name_given']) && !empty($_REQUEST['lis_person_name_family'])) || !empty($_REQUEST['lis_person_name_full'])) &&
		   ((count($keyparts)==1 && $ltirole=='learner') || (count($keyparts)==1 && $keytype=='cc-vf' && $_SESSION['lti_keyrights']>75) ||
		   (count($keyparts)>2 && $keyparts[2]==1 && $ltirole=='learner'))) {
			if (!empty($_REQUEST['lis_person_name_given']) && !empty($_REQUEST['lis_person_name_family'])) {
				$firstname = $_REQUEST['lis_person_name_given'];
				$lastname = $_REQUEST['lis_person_name_family'];
			} else {
				$firstname = '';
				$lastname = $_REQUEST['lis_person_name_full'];
			}
			if (!empty($_REQUEST['lis_person_contact_email_primary'])) {
				$email = $_REQUEST['lis_person_contact_email_primary'];
			} else {
				$email = 'none@none.com';
			}
			$stm = $DBH->prepare("INSERT INTO imas_ltiusers (org,ltiuserid) VALUES (:org, :ltiuserid)");
			$stm->execute(array(':org'=>$ltiorg, ':ltiuserid'=>$ltiuserid));
			$localltiuser = $DBH->lastInsertId();
			if (!isset($userid)) {
				//make up a username/password for them
				$_POST['SID'] = 'lti-'.$localltiuser;
				$md5pw = 'pass'; //totally unusable since not md5'ed
				if ($ltirole=='instructor') {
					if (isset($CFG['LTI']['instrrights'])) {
						$rights = $CFG['LTI']['instrrights'];
					} else {
						$rights = 40;
					}
					$newgroupid = intval($_SESSION['lti_keygroupid']);
					$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify,groupid) VALUES ";
					$query .= "(:SID, :password, :rights, :FirstName, :LastName, :email, :msgnotify, :groupid)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw, ':rights'=>$rights,
						':FirstName'=>Sanitize::stripHtmlTags($firstname),
						':LastName'=>Sanitize::stripHtmlTags($lastname),
						':email'=>Sanitize::emailAddress($email),
						':msgnotify'=>0, ':groupid'=>$newgroupid));
				} else {
					$rights = 10;
					$query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify) VALUES ";
					$query .= "(:SID, :password, :rights, :FirstName, :LastName, :email, :msgnotify)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw, ':rights'=>$rights,
						':FirstName'=>Sanitize::stripHtmlTags($firstname),
						':LastName'=>Sanitize::stripHtmlTags($lastname),
						':email'=>Sanitize::emailAddress($email),
						':msgnotify'=>0));
				}
				$userid = $DBH->lastInsertId();
			}
			$stm = $DBH->prepare("UPDATE imas_ltiusers SET userid=:userid WHERE id=:id");
			$stm->execute(array(':userid'=>$userid, ':id'=>$localltiuser));
		} else {
			////create form asking them for user info
			$askforuserinfo = true;
			$_SESSION['LMSfirstname'] = $_REQUEST['lis_person_name_given'];
			$_SESSION['LMSlastname'] = $_REQUEST['lis_person_name_family'];
			if (!empty($_REQUEST['lis_person_contact_email_primary'])) {
				$_SESSION['LMSemail'] = $_REQUEST['lis_person_contact_email_primary'];
			}
		}
	}
	$_SESSION['ltikey'] = $ltikey;
}



//Do we need to ask for student's info?
//either first connect or bad info on first submit
if ($askforuserinfo == true) {
	if (!empty($_REQUEST['tool_consumer_instance_description'])) {
		$_SESSION['ltiorgname'] = $_REQUEST['tool_consumer_instance_description'];
	}
	header('Location: ' . $GLOBALS['basesiteurl'] . "/bltilaunch.php?userinfo=ask");
	exit;

}

//if here, we know the local userid.

//if it's a common catridge placement and we're here, then either we're using domain credentials, or
//course credentials for a non-source course.

//see if lti_courses is created
//  if not, see if source cid is instructors course
// 	if so, set lti_course
//	if not, create a new blank course
//
//see if courseid==source course cid
//  if not, copy assessment into course, set placement
//  if so, set placement

//determine request type, and check availability
$now = time();

//general placement or common catridge placement - look for placement, or create if know info
$orgparts = explode(':',$_SESSION['ltiorg']);  //THIS was added to avoid issues when GUID change, while still storing it
$shortorg = $orgparts[0];

if (((count($keyparts)==1 || $_SESSION['lti_keytype']=='gc') && $_SESSION['lti_keytype']!='cc-vf' && $_SESSION['lti_keytype']!='cc-of') || $_SESSION['lti_keytype']=='cc-g' || $_SESSION['lti_keytype']=='cc-c') {
	$query = "SELECT placementtype,typeid FROM imas_lti_placements WHERE ";
    $query .= "contextid=:contextid AND linkid=:linkid AND typeid>0 AND org LIKE :org";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':contextid'=>$_SESSION['lti_context_id'], ':linkid'=>$_SESSION['lti_resource_link_id'], ':org'=>"$shortorg:%"));
	if ($stm->rowCount()==0) {
		if (isset($_SESSION['place_aid'])) {
			//look to see if we've already linked this context_id with a course
			$stm = $DBH->prepare("SELECT courseid FROM imas_lti_courses WHERE contextid=:contextid AND org LIKE :org");
			$stm->execute(array(':contextid'=>$_SESSION['lti_context_id'], ':org'=>"$shortorg:%"));
			if ($stm->rowCount()==0) {
				if ($_SESSION['lti_keytype']=='cc-g') {
					//if instructor, see if the source course is ours
					$copycourse = true;
					if ($_SESSION['ltirole']=='instructor') {
						$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
						$stm->execute(array(':courseid'=>$_SESSION['place_aid'][0], ':userid'=>$userid));
						if ($stm->rowCount()>0) {
							$copycourse=false;
							$destcid = intval($_SESSION['place_aid'][0]);
						}
					} else {
						reporterror(_("Course link not established yet"));
					}
					if ($copycourse) {
						//create a course
						//creating a copy of a template course
						$blockcnt = 1;
						$itemorder = serialize(array());
						$randkey = uniqid();
						$allowunenroll = isset($CFG['CPS']['allowunenroll'])?$CFG['CPS']['allowunenroll'][0]:0;
						$copyrights = isset($CFG['CPS']['copyrights'])?$CFG['CPS']['copyrights'][0]:0;
						$msgset = isset($CFG['CPS']['msgset'])?$CFG['CPS']['msgset'][0]:0;
						$msgmonitor = (floor($msgset/5))&1;
						$msgset = $msgset%5;
						$theme = isset($CFG['CPS']['theme'])?$CFG['CPS']['theme'][0]:$defaultcoursetheme;
						$showlatepass = isset($CFG['CPS']['showlatepass'])?$CFG['CPS']['showlatepass'][0]:0;

						$avail = 0;
						$lockaid = 0;
						$query = "INSERT INTO imas_courses (name,ownerid,enrollkey,allowunenroll,copyrights,msgset,showlatepass,itemorder,available,theme,ltisecret,blockcnt) VALUES ";
						$query .= "(:name, :ownerid, :enrollkey, :allowunenroll, :copyrights, :msgset, :showlatepass, :itemorder, :available, :theme, :ltisecret, :blockcnt);";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':name'=>$_SESSION['lti_context_label'], ':ownerid'=>$userid, ':enrollkey'=>$randkey, 
							':allowunenroll'=>$allowunenroll, ':copyrights'=>$copyrights, ':msgset'=>$msgset, ':showlatepass'=>$showlatepass, ':itemorder'=>$itemorder,
							':available'=>$avail, ':theme'=>$theme, ':ltisecret'=>$randkey, ':blockcnt'=>$blockcnt));
						$destcid  = $DBH->lastInsertId();

						//call hook, if defined
						if (function_exists('onAddCourse')) {
							onAddCourse($destcid, $userid);
						}

						$stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES (:userid, :courseid)");
						$stm->execute(array(':userid'=>$userid, ':courseid'=>$destcid));

					}
					$stm = $DBH->prepare("INSERT INTO imas_lti_courses (org,contextid,courseid,contextlabel) VALUES (:org, :contextid, :courseid, :contextlabel)");
					$stm->execute(array(
						':org'=>$_SESSION['ltiorg'],
						':contextid'=>$_SESSION['lti_context_id'],
						':courseid'=>$destcid,
						':contextlabel'=>$_SESSION['lti_context_label']));
					require_once('includes/TeacherAuditLog.php');
					TeacherAuditLog::addTracking(
						$destcid,
						"Course Settings Change",
						$destcid,
						[
							'action'=>'Establish LTI course connection',
							'type'=>'2',
							'contextid'=>$_SESSION['lti_context_id'],
							'copycourse'=>$copycourse,
							'orig'=>$_SESSION['place_aid'][0]
						]
					);

				} else if ($_SESSION['lti_keytype']=='cc-c') {
					$copyaid = true;
					//link up key/secret course
					$destcid = $keyparts[1];
					$stm = $DBH->prepare("INSERT INTO imas_lti_courses (org,contextid,courseid,contextlabel) VALUES (:org, :contextid, :courseid, :contextlabel)");
					$stm->execute(array(
						':org'=>$_SESSION['ltiorg'],
						':contextid'=>$_SESSION['lti_context_id'],
						':courseid'=>$destcid,
						':contextlabel'=>$_SESSION['lti_context_label']));
					require_once('includes/TeacherAuditLog.php');
					TeacherAuditLog::addTracking(
						$destcid,
						"Course Settings Change",
						$destcid,
						[
							'action'=>'Establish LTI course connection',
							'type'=>'3',
							'contextid'=>$_SESSION['lti_context_id'],
						]
					);
				}
			} else {
				$destcid = $stm->fetchColumn(0);
			}
			if ($destcid==$_SESSION['place_aid'][0]) {
				//aid is in destination course - just make placement
				$aid = $_SESSION['place_aid'][1];
			} else {
				//aid is in source course.  Let's see if we already copied it.
				$stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE ancestors REGEXP :ancregex AND courseid=:destcid");
				$stm->execute(array(':ancregex'=>'^([0-9]+:)?'.intval($_SESSION['place_aid'][1]).'[[:>:]]', ':destcid'=>$destcid));
				if ($stm->rowCount()>0) {
					$aid = $stm->fetchColumn(0);
				} else {
					//aid is in source course.  Let's look and see if there's an assessment in destination with the same title.
					//THIS SHOULD BE REMOVED - only included to accomodate people doing things the wrong way.
					$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
					$stm->execute(array(':id'=>$_SESSION['place_aid'][1]));
					$sourceassessname = $stm->fetchColumn(0);
					$stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE name=:name AND courseid=:courseid");
					$stm->execute(array(':name'=>$sourceassessname, ':courseid'=>$destcid));
					if ($stm->rowCount()>0) {
						$aid = $stm->fetchColumn(0);
					} else {
						// no assessment with same title - need to copy assessment from destination to source course
						require_once("includes/copyiteminc.php");
						$cid = $destcid;
						$stm = $DBH->prepare("SELECT itemorder,dates_by_lti,UIver FROM imas_courses WHERE id=:id");
						$stm->execute(array(':id'=>$cid));
						list($items,$datesbylti,$convertAssessVer) = $stm->fetch(PDO::FETCH_NUM);
						$items = unserialize($items);

						$stm = $DBH->prepare("SELECT id FROM imas_items WHERE itemtype='Assessment' AND typeid=:typeid");
						$stm->execute(array(':typeid'=>$_SESSION['place_aid'][1]));
						if ($stm->rowCount()==0) {
							reporterror(sprintf(_("Error.  Assessment ID %s not found."),"'{$_SESSION['place_aid'][1]}'"));
						}

						$newitem = copyitem($stm->fetchColumn(0),array());
						$stm = $DBH->prepare("SELECT typeid FROM imas_items WHERE id=:id");
						$stm->execute(array(':id'=>$newitem));
						$aid = $stm->fetchColumn(0);

						$items[] = $newitem;
						$items = serialize($items);
						$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
						$stm->execute(array(':itemorder'=>$items, ':id'=>$cid));
					}
				}
			}
			$query = "INSERT INTO imas_lti_placements (org,contextid,linkid,placementtype,typeid) VALUES ";
			$query .= "(:org, :contextid, :linkid, :placementtype, :typeid)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':org'=>$_SESSION['ltiorg'], ':contextid'=>$_SESSION['lti_context_id'], ':linkid'=>$_SESSION['lti_resource_link_id'], ':placementtype'=>'assess', ':typeid'=>$aid));
			$keyparts = array('aid',$aid);

		} else if ($_SESSION['ltirole']!='instructor') {
			reporterror(_("This placement is not yet set up"));
		}
	} else {
        $row = $stm->fetch(PDO::FETCH_NUM);
		if ($row[0]=='course') {
			$keyparts = array('cid',$row[1]);
		} else if ($row[0]=='assess') {
			$keyparts = array('aid',$row[1]);
		} else {
			reporterror(_("Invalid placement type"));
		}

	}
}

if ($_SESSION['lti_keytype']=='cc-vf' || $_SESSION['lti_keytype']=='cc-of') {
	$keyparts = array('folder',$_SESSION['view_folder'][0],$_SESSION['view_folder'][1]);
}
//is course level placement
if ($keyparts[0]=='cid' || $keyparts[0]=='placein' || $keyparts[0]=='LTIkey') {
	$cid = intval($keyparts[1]);
	$stm = $DBH->prepare("SELECT available,ltisecret FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	if ($_SESSION['ltirole']!='instructor') {
		if (!($line['avail']==0 || $line['avail']==2)) {
			reporterror(_("This course is not available"));
		}
	}
} else if ($keyparts[0]=='aid') {   //is assessment level placement
	$aid = intval($keyparts[1]);
	$stm = $DBH->prepare("SELECT courseid,startdate,enddate,reviewdate,LPcutoff,avail,ltisecret,allowlate,date_by_lti FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	if ($line===false) {
		$diaginfo = "(Debug info: 7-$aid)";
		reporterror(_("This assignment does not appear to exist anymore.")." $diaginfo");
	}
    $cid = $line['courseid'];
    if (isset($_SESSION['lti_duedate']) && ($line['date_by_lti']==1 || $line['date_by_lti']==2)) {
		if ($_SESSION['ltirole']=='instructor') {
			$newdatebylti = 2; //set/keep as instructor-set
		} else {
			$newdatebylti = 3; //mark as student-set
		}
		//no default due date set yet, or is the instructor:  set the default due date
		$stm = $DBH->prepare("UPDATE imas_assessments SET startdate=:startdate,enddate=:enddate,date_by_lti=:datebylti WHERE id=:id");
		$stm->execute(array(':startdate'=>min($now, $_SESSION['lti_duedate']),
			':enddate'=>$_SESSION['lti_duedate'], ':datebylti'=>$newdatebylti, ':id'=>$aid));
		$line['enddate'] = $_SESSION['lti_duedate'];
    }
    if (!isset($_SESSION['lti_duedate']) && $line['date_by_lti']==1) {
		//assessment is set to use dates sent by LTI, but none was sent.  Give error for instructor.
		if ($_SESSION['ltirole'] == 'instructor') {
			$err = 'Your '.$installname.' course is set to use dates sent by the LMS, but the LMS did not send a date. ';
			$err .= 'Your "App Config" may be old and not contain the necessary info. ';
			$err .= 'In Canvas, go to Settings -> Apps -> View App Configurations ';
			$err .= 'and edit the '.$installname.' app. (If you cannot edit it, you may have to ask your ';
			$err .= 'Canvas admin to edit the app). In the "Custom Fields" box, ';
			$err .= 'enter this: canvas_assignment_due_at=$Canvas.assignment.dueAt.iso8601';
			reporterror($err);
		} else {
			$err = 'Tell your teacher that Canvas is not sending due dates.';
			reporterror($err);
		}
	}
	if ($_SESSION['ltirole']!='instructor') {
		//if ($line['avail']==0 || $now>$line['enddate'] || $now<$line['startdate']) {
		//	reporterror("This assessment is closed");
		//}
		if ($line['avail']==0) {
			//reporterror("This assessment is closed");
		}
		$stm2 = $DBH->prepare("SELECT startdate,enddate,islatepass,is_lti FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm2->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		$row = $stm2->fetch(PDO::FETCH_NUM);
		$useexception = false;
		if ($row!=null) {
            if (isset($_SESSION['lti_duedate']) && $line['date_by_lti']>0 && $_SESSION['lti_duedate']!=$row[1]) {
				//if new due date is later, or no latepass used, then update
				if ($row[2]==0 || $_SESSION['lti_duedate']>$row[1]) {
					$stm = $DBH->prepare("UPDATE imas_exceptions SET startdate=:startdate,enddate=:enddate,is_lti=1,islatepass=0 WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
					$stm->execute(array(':startdate'=>min($now, $line['startdate'], $row[0]),
						':enddate'=>$_SESSION['lti_duedate'], ':userid'=>$userid, ':assessmentid'=>$aid));
				}
			}
			require_once("./includes/exceptionfuncs.php");
			$exceptionfuncs = new ExceptionFuncs($userid, $cid, true);
			$useexception = $exceptionfuncs->getCanUseAssessException($row, $line, true);
		} else if ($line['date_by_lti']==3 && ($line['enddate']!=$_SESSION['lti_duedate'] || $now<$line['startdate'])) {
			//default dates already set by LTI, and users's date doesn't match - create new exception
			//also create if it's before the default assessment startdate - since they could access via LMS, it should be available.
			$exceptionrow = array(min($now,$_SESSION['lti_duedate']), $_SESSION['lti_duedate'], 0, 1);
			$stm = $DBH->prepare("INSERT INTO imas_exceptions (startdate,enddate,islatepass,is_lti,userid,assessmentid,itemtype) VALUES (?,?,?,?,?,?,'A')");
			$stm->execute(array_merge($exceptionrow, array($userid, $aid)));
			$useexception = true;
		}
		if ($row!=null && $useexception) {
			if ($now<$row[0] || $row[1]<$now) { //outside exception dates
				if ($now > $line['startdate'] && $now < $line['reviewdate']) {
					$isreview = true;
				} else {
					//reporterror("This assessment is closed");
				}
			} else { //inside exception dates exception
				if ($line['enddate']<$now) { //exception is for past-due-date
					$inexception = true; //only trigger if past due date for penalty
				}
			}
			$exceptionduedate = $row[1];
		} else { //has no exception
			if ($now < $line['startdate'] || $line['enddate'] < $now) { //outside normal dates
				if ($now > $line['startdate'] && $now < $line['reviewdate']) {
					$isreview = true;
				} else {
					//reporterror("This assessment is closed");
				}
			}
		}

	}
} else if ($keyparts[0]=='folder') {
	$stm2 = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$stm2->execute(array(':id'=>$keyparts[1]));
	if ($stm2->rowCount()==0) {
		reporterror(_("invalid course identifier in folder view launch"));
	} else {
		$cid = intval($keyparts[1]);
		if ($_SESSION['lti_keytype']=='cc-vf') {
			$usid = explode('_',$_SESSION['ltiorigkey']);
			$query = "SELECT imas_tutors.id FROM imas_tutors JOIN imas_users ON imas_tutors.userid=imas_users.id WHERE ";
			$query .= "imas_tutors.courseid=:courseid AND imas_users.SID=:SID";
			$stm3 = $DBH->prepare($query);
			$stm3->execute(array(':courseid'=>$cid, ':SID'=>$usid[0]));
			if ($stm3->rowCount()==0) {
				reporterror(_("not authorized to view folders in this course"));
			}
		}
		$row = $stm2->fetch(PDO::FETCH_NUM);
		$items = unserialize($row[0]);
		function findfolder($items,$n,$loc) {
			foreach ($items as $k=>$b) {
				if (is_array($b)) {
					if ($b['id']==$n) {
						return $loc.'-'.($k+1);
					} else {
						$out = findfolder($b['items'],$n,$loc.'-'.($k+1));
						if ($out != '') {
							return $out;
						}
					}
				}
			}
			return '';
		}
		if ($keyparts[2]=='0') {
			$loc = '0';
		} else {
			$loc = findfolder($items, $keyparts[2], '0');
		}
		if ($loc=='') {
			reporterror(_("invalid folder identifier in folder view launch"));
		}
		$keyparts[3] = $loc;
	}
} else if ($keyparts[0]!='sso' && $_SESSION['ltirole']!='instructor') {
	reporterror(_("invalid key. unknown action type"));
}

//see if student is enrolled, if appropriate to action type
if ($keyparts[0]=='cid' || $keyparts[0]=='aid' || $keyparts[0]=='placein' || $keyparts[0]=='folder' || $keyparts[0]=='LTIkey') {
	if ($_SESSION['ltirole']=='instructor') {
		$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		if ($stm->rowCount() == 0) {
			$stm = $DBH->prepare("SELECT id FROM imas_tutors WHERE userid=:userid AND courseid=:courseid");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
			if ($stm->rowCount() == 0) {
				$stm = $DBH->prepare("INSERT INTO imas_tutors (userid,courseid,section) VALUES (:userid, :courseid, :section)");
				$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':section'=>$_SESSION['lti_context_label']));
			}
		}
		$timelimitmult = 1;
	} else {
		$stm = $DBH->prepare("SELECT id,timelimitmult FROM imas_students WHERE userid=:userid AND courseid=:courseid");
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		if ($stm->rowCount() == 0) {
			$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
			if ($stm->rowCount() == 0) {
				$stm = $DBH->prepare("SELECT id FROM imas_tutors WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
				if ($stm->rowCount() == 0) {
					$stm = $DBH->prepare("SELECT deflatepass FROM imas_courses WHERE id=:id");
					$stm->execute(array(':id'=>$cid));
					$deflatepass = $stm->fetchColumn(0);
					$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,section,latepass) VALUES (:userid, :courseid, :section, :latepass)");
                    $stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':section'=>$_SESSION['lti_context_label'], ':latepass'=>$deflatepass));
                    require_once('./includes/setSectionGroups.php');
                    setSectionGroups($userid, $cid, $_SESSION['lti_context_label']);
				}
			} else {
				$_SESSION['ltirole']='instructor';
				$setstuviewon = true;
			}
			$timelimitmult = 1;
		} else {
			$timelimitmult = $stm->fetchColumn(1);
		}
	}
}

//check if db session entry exists for session
$promptforsettings = false;
$SESS = $_SESSION; // store values in case we need to clear existing session
if (!empty($_SESSION['userid'])) {
	//check that same userid, and that we're not jumping on someone else's
	//existing session.  If so, then we need to create a new session.
	//also, if session did not have ltiuserid already, must be jumping non-LTI to LTI
	if ($_SESSION['userid'] != $userid || !$atstarthasltiuserid) {
		session_destroy();
		session_start();
		session_regenerate_id();
		$sessionid = session_id();
		//setcookie(session_name(),session_id(),0,'','',false,true );
		$_SESSION = array();
		$createnewsession = true;
	} else {
		//already have session.  Don't need to create one
		if (!isset($_SESSION['mathdisp'])) {
			//for some reason settings are not set, so reload from user prefs
			require_once("$curdir/includes/userprefs.php");
			generateuserprefs(true);
		}
		$createnewsession = false;
	}
} else {
	$_SESSION = array();
	$createnewsession = true;
}

//if assessment, going to check for timelimit
if ($keyparts[0]=='aid') {
	$stm = $DBH->prepare("SELECT timelimit,ver FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($timelimit, $aver) = $stm->fetch(PDO::FETCH_NUM);
	$timelimit = abs($timelimit * $timelimitmult);
	if ($timelimit>0 && $aver==1) {
		 if ($timelimit>3600) {
			$tlhrs = floor($timelimit/3600);
			$tlrem = $timelimit % 3600;
			$tlmin = floor($tlrem/60);
			$tlsec = $tlrem % 60;
			$tlwrds = "$tlhrs hour";
			if ($tlhrs > 1) { $tlwrds .= "s";}
			if ($tlmin > 0) { $tlwrds .= ", $tlmin minute";}
			if ($tlmin > 1) { $tlwrds .= "s";}
			if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
			if ($tlsec > 1) { $tlwrds .= "s";}
		} else if ($timelimit>60) {
			$tlmin = floor($timelimit/60);
			$tlsec = $timelimit % 60;
			$tlwrds = "$tlmin minute";
			if ($tlmin > 1) { $tlwrds .= "s";}
			if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
			if ($tlsec > 1) { $tlwrds .= "s";}
		} else {
			$tlwrds = $timelimit . " second(s)";
		}
	} else {
		$tlwrds = '';
	}
	//this sessiondata tells WAMAP to limit access to the specific resouce requested
	$_SESSION['ltitlwrds'] = $tlwrds;
	$_SESSION['ltiitemtype']=0;
	$_SESSION['ltiitemver']=$aver;
	$_SESSION['ltiitemid'] = $aid;

	$_SESSION['lticanuselatepass'] = false;
	if ($_SESSION['ltirole']!='instructor' && $line['allowlate']>0) {
		$stm = $DBH->prepare("SELECT latepasshrs FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$latepasshrs = $stm->fetchColumn(0);
		require_once("./includes/exceptionfuncs.php");
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $latepasses, $latepasshrs);
		list($useexception, $canundolatepass, $canuselatepass) = $exceptionfuncs->getCanUseAssessException($exceptionrow, $line);
		$_SESSION['lticanuselatepass'] = $canuselatepass;
	}
}  else if ($keyparts[0]=='cid') { //is cid
	$_SESSION['ltiitemtype']=1;
	$_SESSION['ltiitemid'] = $cid;
} else if ($keyparts[0]=='sso') { //is sso
	$_SESSION['ltiitemtype']=2;
} else if ($keyparts[0]=='folder') { //is folder content view
	$_SESSION['ltiitemtype']=3;
	$_SESSION['ltiitemid'] = array($keyparts[2],$keyparts[3],$cid);
} else {
	$_SESSION['ltiitemtype']=-1;
}
$_SESSION['ltiorg'] = $SESS['ltiorg'];
$_SESSION['ltirole'] = $SESS['ltirole'];
$_SESSION['lti_context_id']  = $SESS['lti_context_id'];
$_SESSION['lti_resource_link_id']  = $SESS['lti_resource_link_id'];
// record it with aid for safety
if ($keyparts[0]=='aid') {
	$_SESSION['lti_lis_result_sourcedid'.$aid]  = $SESS['lti_lis_result_sourcedid'];
}
$_SESSION['lti_outcomeurl']  = $SESS['lti_outcomeurl'];
$_SESSION['lti_context_label'] = $SESS['lti_context_label'];
$_SESSION['lti_launch_get'] = $SESS['lti_launch_get'];
$_SESSION['lti_key'] = $SESS['lti_key'];
$_SESSION['lti_keytype'] = $SESS['lti_keytype'];
$_SESSION['lti_keylookup'] = $SESS['ltilookup'];
$_SESSION['lti_origkey'] = $SESS['ltiorigkey'];
if (isset($SESS['lti_duedate'])) {
	$_SESSION['lti_duedate'] = $SESS['lti_duedate'];
}
if (isset($SESS['selection_return'])) {
	$_SESSION['lti_selection_return'] = $SESS['selection_return'];
	$_SESSION['lti_selection_targets'] = $SESS['selection_targets'];
	$_SESSION['lti_selection_return_format'] = $SESS['selection_return_format'];
	$_SESSION['lti_selection_type'] = $SESS['selection_type'];
	$_SESSION['lti_selection_data'] = $SESS['selection_data'];
}

if (isset($setstuviewon) && $setstuviewon==true) {
	$_SESSION['stuview'] = 0;
}

if ($_SESSION['lti_keytype']=='gc') {
	$_SESSION['lti_launch_get']['cid'] = $keyparts[1];
}
if ($_SESSION['lti_keytype']=='cc-vf') {
	$_SESSION['mathdisp'] = 0;
	$_SESSION['graphdisp'] = 2;
	$_SESSION['tzoffset'] = 0;
}

$_SESSION['userid'] = $userid;
$_SESSION['time'] = $now;

if ($_SESSION['lti_keytype']=='cc-vf' || (!$promptforsettings && !$createnewsession && !($keyparts[0]=='aid' && $tlwrds != ''))) {
	//redirect now if already have session and no timelimit
	$now = time();
	$stm = $DBH->prepare("UPDATE imas_users SET lastaccess=:lastaccess WHERE id=:id");
	$stm->execute(array(':lastaccess'=>$now, ':id'=>$userid));

	if ($keyparts[0]=='aid') { //is aid
		if ($_SESSION['ltirole'] == 'learner') {
			$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
			$query .= "(:userid, :courseid, :type, :typeid, :viewtime, :info)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':type'=>'assesslti', ':typeid'=>$aid, ':viewtime'=>$now, ':info'=>''));
		}
		if ($aver > 1) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assess2/?cid=$cid&aid=$aid");
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assessment/showtest.php?cid=$cid&id=$aid&ltilaunch=true");
		}
	} else if ($keyparts[0]=='cid') { //is cid
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid");
	} else if ($keyparts[0]=='sso') {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/index.php");
	} else if ($keyparts[0]=='folder') {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?checksess=true&cid=$cid&folder=".$keyparts[3]);
	} else { //will only be instructors hitting this option
		header('Location: ' . $GLOBALS['basesiteurl'] . "/ltihome.php");
	}
	exit;
} else {
	header('Location: ' . $GLOBALS['basesiteurl'] . "/bltilaunch.php?accessibility=ask");
	exit;
}

/*end using old behavior for other requests */
}

?>
