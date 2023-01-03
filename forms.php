<?php
//IMathAS:  Basic forms
//(c) 2006 David Lippman
require_once("includes/newusercommon.php");
if (!isset($_GET['action'])) { exit; }
if ($_GET['action']!="newuser" && $_GET['action']!="resetpw" && $_GET['action']!="lookupusername") {
	require("init.php");
} else {
	$init_session_start = true;
	require("init_without_validate.php");
	if (isset($CFG['CPS']['theme'])) {
		$defaultcoursetheme = $CFG['CPS']['theme'][0];
	} else if (!isset($defaultcoursetheme)) {
		$defaultcoursetheme = "default.css";
	}
	$coursetheme = $defaultcoursetheme;
}

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['forms'])) {
	require($CFG['hooks']['forms']);
}

require("includes/htmlutil.php");

if (isset($_GET['greybox'])) {
	$gb = '&greybox=true';
	$flexwidth = true;
	$nologo = true;
} else {
	$gb = '';
}
$placeinhead = '<script type="text/javascript" src="'.$staticroot.'/javascript/jquery.validate.min.js?v=122917"></script>';
if (isset($CFG['locale'])) {
	$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/jqvalidatei18n/messages_'.substr($CFG['locale'],0,2).'.min.js"></script>';
}
if ($_GET['action']=='chguserinfo') {
	$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/jstz_min.js\" ></script>";
}
require("header.php");
switch($_GET['action']) {
	case "newuser":
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; ",_('New Student Signup'),"</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h1>',_('New Student Signup'),'</h1></div>';
		echo "<form id=\"newuserform\" class=limitaftervalidate method=post action=\"actions.php?action=newuser$gb\">\n";
		echo "<span class=form><label for=\"SID\">$longloginprompt:</label></span> <input class=\"form pii-username\" type=\"text\" size=12 id=SID name=SID><BR class=\"form\">\n";
		echo "<span class=\"form\"><label for=\"pw1\">",_('Choose a password:'),"</label></span><input class=\"form\" type=\"password\" size=20 id=pw1 name=pw1><BR class=\"form\">\n";
		echo "<span class=\"form\"><label for=\"pw2\">",_('Confirm password:'),"</label></span> <input class=\"form\" type=\"password\" size=20 id=pw2 name=pw2><BR class=\"form\">\n";
		echo "<span class=\"form\"><label for=\"firstname\">",_('Enter First Name:'),"</label></span> <input class=\"form pii-first-name\" type=\"text\" size=20 id=firstname name=firstname autocomplete=\"given-name\"><BR class=\"form\">\n";
		echo "<span class=\"form\"><label for=\"lastname\">",_('Enter Last Name:'),"</label></span> <input class=\"form pii-last-name\" type=\"text\" size=20 id=lastname name=lastname autocomplete=\"family-name\"><BR class=\"form\">\n";
		echo "<span class=\"form\"><label for=\"email\">",_('Enter E-mail address:'),"</label></span>  <input class=\"form pii-email\" type=\"text\" size=60 id=email name=email autocomplete=\"email\"><BR class=\"form\">\n";
		echo "<span class=form><label for=\"msgnot\">",_('Notify me by email when I receive a new message:'),"</label></span><span class=formright><input type=checkbox id=msgnot name=msgnot checked=\"checked\" /></span><BR class=form>\n";
        if (isset($CFG['GEN']['COPPA'])) {
			echo "<span class=form><label for=\"over13\">",_('I am 13 years old or older'),"</label></span><span class=formright><input type=checkbox name=over13 id=over13 onchange=\"toggleOver13()\"></span><br class=form />\n";
        }
        if (isset($studentTOS)) {
			echo "<span class=form><label for=\"agree\">",_('I have read and agree to the Terms of Use (below)'),"</label></span><span class=formright><input type=checkbox name=agree id=agree></span><br class=form />\n";
		} else if (isset($CFG['GEN']['TOSpage'])) {
			$t1=_('Terms of Use');
			$ta=sprintf("<a href=\"#\" onclick=\"GB_show('%s','".$CFG['GEN']['TOSpage']."',700,500);return false;\">%s</a>",$t1,$t1);
			$tf=_('I have read and agree to the %s');
			$t2=sprintf($tf,$ta);
			echo "<span class=form><label for=\"agree\">".$t2."</label></span><span class=formright><input type=checkbox name=agree id=agree></span><br class=form />\n";
		}

        $extrarequired = [];
        $requiredrules = [];
        if (isset($studentTOS) || isset($CFG['GEN']['TOSpage'])) {
            $extrarequired[] = 'agree';
        }
        if (isset($CFG['GEN']['COPPA'])) {
            $extrarequired[] = 'courseid';
            $requiredrules['courseid'] = '{depends: function(element) {return !document.getElementById("over13").checked}}';
        }
		showNewUserValidation('newuserform', $extrarequired, $requiredrules);

		if (!$emailconfirmation) {
            $doselfenroll = false;
            if (isset($CFG['GEN']['COPPA'])) {
                $fullopt = 'style="display:none;';
            }
			$stm = $DBH->query("SELECT id,name FROM imas_courses WHERE istemplate > 0 AND (istemplate&4)=4 AND available<4 ORDER BY name");
			if ($stm->rowCount()>0) {
                $doselfenroll = true;
                if (isset($CFG['GEN']['COPPA'])) {
                    echo '<p class="fullopt" style="display:none">';
                } else {
                    echo '<p>';
                }
				echo '<label for="courseselect">',_('Select the course you\'d like to enroll in'),'</label><br/>';
				echo '<select id="courseselect" name="courseselect" onchange="courseselectupdate(this);">';
				echo '<option value="0" selected="selected">',_('My teacher gave me a course ID (enter below)'),'</option>';
				echo '<optgroup label="Self-study courses">';
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					echo '<option value="'.Sanitize::encodeStringForDisplay($row[0]).'">'.Sanitize::encodeStringForDisplay($row[1]).'</option>';
				}
				echo '</optgroup>';
                echo '</select></p>';
                if (isset($CFG['GEN']['COPPA'])) {
                    echo '<p class="limitedopt">'._('Enter the course ID and Key provided by your teacher below').'</p>';
                }
				echo '<div id="courseinfo">';
				echo '<script type="text/javascript"> function courseselectupdate(el) { var c = document.getElementById("courseinfo"); var c2 = document.getElementById("selfenrollwarn"); ';
				echo 'if (el.value==0) {c.style.display="";c2.style.display="none";} else {c.style.display="none";c2.style.display="";}}</script>';
			} else {
                if (isset($CFG['GEN']['COPPA'])) {
                    echo '<p class="fullopt" style="display:none">';
                } else {
                    echo '<p>';
                }
                echo _('If you already know your course ID, you can enter it now.  Otherwise, leave this blank and you can enroll later.'),'</p>';
                if (isset($CFG['GEN']['COPPA'])) {
                    echo '<p class="limitedopt">'._('Enter the course ID and Key provided by your teacher below').'</p>';
                }
            }
            if (isset($CFG['GEN']['COPPA'])) {
                echo '<script type="text/javascript">
                function toggleOver13() {
                    var chked = document.getElementById("over13").checked;
                    $(".fullopt").toggle(chked);
                    $(".limitedopt").toggle(!chked);
                }</script>';
            }

			echo '<span class="form"><label for="courseid">',_('Course ID'),':</label></span><input class="form" type="text" size="20" name="courseid" id="courseid"/><br class="form"/>';
			echo '<span class="form"><label for="ekey">',_('Enrollment Key'),':</label></span><input class="form" type="text" size="20" name="ekey" id="ekey"/><br class="form"/>';
			if ($doselfenroll) {
				echo '</div>';
				echo '<div id="selfenrollwarn" class=noticetext style="display:none;">',_('Warning: You have selected a non-credit self-study course. ');
				echo sprintf(_('If you are using %s with an instructor-led course, this is NOT what you want; nothing you do in the self-study course will be viewable by your instructor or count towards your course.  For an instructor-led course, you need to enter the course ID and key provided by your instructor.'),$installname);
				echo '</div>';
			}
		}
		$_SESSION['challenge'] = uniqid();
        $_SESSION['newuserstart'] = time();
		echo '<input type=hidden name=challenge value="'.Sanitize::encodeStringForDisplay($_SESSION['challenge']).'"/>';
		echo '<span class="sr-only"><label aria-hidden=true">Do not fill this out <input name=hval tabindex="-1"></label></span>';
		echo "<div class=submit><input type=submit value='",_('Sign Up'),"'></div>\n";
		echo "</form>\n";
		if (isset($studentTOS)) {
			include($studentTOS);
		}
		break;
	case "forcechgpwd":
	case "chgpwd":
		if ($gb == '' && $_GET['action']!='forcechgpwd') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; ",_('Change Password'),"</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h1>',_('Change Your Password'),'</h1></div>';
		if ($_GET['action']=='forcechgpwd') {
			echo '<p>'._('To ensure the security of your account, we are requiring a password change. Please select a new password.').'</p>';
			echo "<form id=\"pageform\" class=limitaftervalidate method=post action=\"actions.php?action=forcechgpwd$gb\">\n";
		} else {
			echo "<form id=\"pageform\" class=limitaftervalidate method=post action=\"actions.php?action=chgpwd$gb\">\n";
		}
		echo "<span class=form><label for=\"oldpw\">",_('Enter old password'),":</label></span> <input class=form type=password id=oldpw name=oldpw size=40 /> <BR class=form>\n";
		echo "<span class=form><label for=\"pw1\">",_('Enter new password'),":</label></span>  <input class=form type=password id=pw1 name=pw1 size=40> <BR class=form>\n";
		echo "<span class=form><label for=\"pw2\">",_('Verify new password'),":</label></span>  <input class=form type=password id=pw2 name=pw2 size=40> <BR class=form>\n";

		showNewUserValidation("pageform",array("oldpw"));

		echo "<div class=submit><input type=submit value=",_('Submit'),"></div>";
		echo "</form>\n";
		break;
	case "chguserinfo":
		$stm = $DBH->prepare("SELECT * FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$userid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		if ($myrights < 75 && substr($line['email'],0,7)==='BOUNCED') {
			$line['email'] = '';
        }
        $mfatype = 0;
        if ($line['mfa'] !== '') {
            $mfadata = json_decode($line['mfa'], true);
            if (!empty($mfadata['mfatype']) && $mfadata['mfatype'] == 'admin') {
                $mfatype = 1;
            } else {
                $mfatype = 2;
            }
        }
        echo '<script type="text/javascript">
            function togglechgpw(val) { 
                document.getElementById("pwinfo").style.display=val?"":"none"; 
            } 
            function togglechgmfa(val) { 
                $("#mfainfo").toggle(val>0);
            }
            var oldemail = "'.Sanitize::encodeStringForJavascript($line['email']).'";
            $(function () {
                $("#dochgpw,#email,#dochgmfa").on("input change keydown paste", function() {
                    var needchk = $("#dochgpw").prop("checked") ||
                        $("#email").val() != oldemail ||
                        ($("#dochgmfa").val() < '.$mfatype.');
                    $("#seccheck").toggle(needchk);
                    $("#oldpw,#oldmfa").prop("required", needchk);
                });
            });
            function cleartrustedmfa(el) {
                $.post("actions.php", {action:"cleartrustedmfa"})
                 .done(function(msg) { if (msg == "OK") { $(el).replaceWith("'._('Cleared').'");}});
            }
        </script>';
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; ",_('Modify User Profile'),"</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h1>',_('User Profile'),'</h1></div>';

		//call hook, if defined
		if (function_exists('chguserinfoExtras')) {
			chguserinfoExtras($userid, $myrights, $groupid);
		}

		echo "<form id=\"pageform\" class=limitaftervalidate enctype=\"multipart/form-data\" method=post action=\"actions.php?action=chguserinfo$gb\">\n";
		echo '<fieldset id="userinfoprofile"><legend>',_('Profile Settings'),'</legend>';
		echo "<span class=form><label for=\"firstname\">",_('Enter First Name'),":</label></span> <input class=\"form pii-first-name\" type=text size=20 id=firstname name=firstname autocomplete=\"given-name\" value=\"".Sanitize::encodeStringForDisplay($line['FirstName'])."\" /><br class=\"form\" />\n";
		echo "<span class=form><label for=\"lastname\">",_('Enter Last Name'),":</label></span> <input class=\"form pii-first-name\" type=text size=20 id=lastname name=lastname autocomplete=\"family-name\" value=\"".Sanitize::encodeStringForDisplay($line['LastName'])."\"><BR class=form>\n";
		if ($myrights>10 && $groupid>0) {
			$stm = $DBH->prepare("SELECT name FROM imas_groups WHERE id=:id");
			$stm->execute(array(':id'=>$groupid));
			$r = $stm->fetch(PDO::FETCH_NUM);
			echo '<span class="form">'._('Group').':</span><span class="formright">'.Sanitize::encodeStringForDisplay($r[0]).'</span><br class="form"/>';
		}
		echo '<span class="form"><label for="dochgpw">',_('Change Password?'),'</label></span> <span class="formright"><input type="checkbox" name="dochgpw" id="dochgpw" onclick="togglechgpw(this.checked)" /></span><br class="form" />';
		echo '<div style="display:none" id="pwinfo">';
		echo "<span class=form><label for=\"pw1\">",_('Enter new password:'),"</label></span>  <input class=form type=password id=pw1 name=pw1 size=40> <BR class=form>\n";
		echo "<span class=form><label for=\"pw2\">",_('Verify new password:'),"</label></span>  <input class=form type=password id=pw2 name=pw2 size=40> <BR class=form>\n";
        echo '</div>';
        echo '<span class=form><label for="dochgmfa">'._('2-factor Authentication').'</label></span>';
        echo '<span class="formright"><select name="dochgmfa" id="dochgmfa" onchange="togglechgmfa(this.value)" /> ';
        echo '<option value=0 '.($mfatype == 0 ? 'selected':'').'>'._('Disable').'</option>';
        if ($line['rights'] > 74) {
            echo '<option value=1 '.($mfatype == 1 ? 'selected':'').'>'._('Enable for admin actions').'</option>';
            echo '<option value=2 '.($mfatype == 2 ? 'selected':'').'>'._('Enable for login and admin actions').'</option>';
        } else {
            echo '<option value=2 '.($mfatype == 2 ? 'selected':'').'>'._('Enable').'</option>';
        }
        echo '</select></span><br class="form" />';
				
        if ($line['mfa']=='') {
            require('includes/GoogleAuthenticator.php');
            $MFA = new GoogleAuthenticator();
            $mfasecret = $MFA->createSecret();
            $mfaurl = $MFA->getOtpauthUrl($installname.':'.$line['SID'], $mfasecret, $installname);
            echo '<div style="display:none" id="mfainfo">';
            echo '<script type="text/javascript" src="javascript/jquery.qrcode.min.js"></script>';
            echo '<script type="text/javascript">$(function(){$("#mfaqrcode").qrcode({width:128,height:128,text:"'.Sanitize::encodeStringForJavascript($mfaurl).'"})});</script>';
            echo '<input type=hidden name=mfasecret value="'.Sanitize::encodeStringForDisplay($mfasecret).'" />';
            echo '<span class=form>Instructions:</span><span class=formright>To enable 2-factor authentication, you will need an app compatible with Google Authenticator. <a href="https://authy.com/download/">Authy</a> is recommended. ';
            echo 'Using the app, scan the QR code below, or manually enter the key code. Once it is set up, enter the token code provided in the box.</span><BR class=form>';
            echo '<span class=form>QR Code:</span><span class=formright><span id="mfaqrcode"></span></span><br class=form>';
            echo '<span class=form>Key Code:</span><span class=formright>'.Sanitize::encodeStringForDisplay($mfasecret).'</span><br class=form>';
            echo "<span class=form><label for=\"mfaverify\">Enter token code from app:</label></span> <input class=form id=mfaverify name=mfaverify size=8> <br class=form>\n";
            echo '</div>';
        } else {
            if (!empty($mfadata['trusted']) || !empty($mfadata['logintrusted'])) {
                echo '<span class=form>'._('2-factor authentication trusted devices').':</span><span class=formright>';
                echo '<a href="#" onclick="cleartrustedmfa(this)">'._('Clear trusted devices').'</a></span><br class=form>';
            }
        }

		echo "<span class=form><label for=\"email\">",_('Enter E-mail address:'),"</label></span>  <input class=\"form pii-email\" type=text size=60 id=email name=email autocomplete=\"email\" value=\"".Sanitize::emailAddress($line['email'])."\"><BR class=form>\n";
        
        echo '<div style="display:none" id="seccheck">';
        echo '<p class="noticetext">'._('The changes you are making require additional security verification.').'</p>';
        echo "<span class=form><label for=\"oldpw\">",_('Enter current password:'),"</label></span> <input class=form type=password id=oldpw name=oldpw size=40 /> <BR class=form>\n";
        if ($line['mfa'] != '') {
            echo "<span class=form><label for=\"oldmfa\">",_('Enter 2-factor Authentication code:'),"</label></span> <input class=form type=text id=oldmfa name=oldmfa size=10 /> <BR class=form>\n";
        }
        echo '</div>';

        echo "<span class=form><label for=\"msgnot\">",_('Notify me by email when I receive a new message:'),"</label></span><span class=formright><input type=checkbox id=msgnot name=msgnot ";
		if ($line['msgnotify']==1) {echo "checked=1";}
		echo " /></span><BR class=form>\n";
		if (isset($CFG['FCM']) && isset($CFG['FCM']['webApiKey']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false) {
			echo '<span class=form>'._('Push notifications:').'</span><span class=formright>';
			echo '<a href="'.$imasroot.'/admin/FCMsetup.php">'.('Setup push notifications on this device').'</a></span><br class=form>';
		}

		echo "<span class=form><label for=\"stupic\">",_('Picture:'),"</label></span>";
		echo "<span class=\"formright\">";
		if ($line['hasuserimg']==1) {
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				echo "<img class=\"pii-image\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_$userid.jpg\" alt=\"User picture\"/> <input type=\"checkbox\" name=\"removepic\" id=removepic value=\"1\" /> <label for=removepic>Remove</label> ";
			} else {
				$curdir = rtrim(dirname(__FILE__), '/\\');
				$galleryPath = "$curdir/course/files/";
				echo "<img class=\"pii-image\" src=\"$imasroot/course/files/userimg_$userid.jpg\" alt=\"User picture\"/> <input type=\"checkbox\" name=\"removepic\" id=removepic value=\"1\" /> <label for=removepic>Remove</label> ";
			}
		} else {
			echo _("No Pic ");
		}
		echo '<br/><input type="file" name="stupic" id="stupic"/></span><br class="form" />';
		echo '<span class="form"><label for="perpage">',_('Messages/Posts per page:'),'</label></span>';
		echo '<span class="formright"><select name="perpage" id="perpage">';
		for ($i=10;$i<=100;$i+=10) {
			echo '<option value="'.$i.'" ';
			if ($i==$line['listperpage']) {echo 'selected="selected"';}
			echo '>'.$i.'</option>';
		}
		echo '</select></span><br class="form" />';

		$pagelayout = explode('|',$line['homelayout']);
		foreach($pagelayout as $k=>$v) {
			if ($v=='') {
				$pagelayout[$k] = array();
			} else {
				$pagelayout[$k] = explode(',',$v);
			}
		}
		$hpsets = '';
		if (!isset($CFG['GEN']['fixedhomelayout']) || !in_array(2,$CFG['GEN']['fixedhomelayout'])) {
			$hpsets .= '<input type="checkbox" name="homelayout10" id="homelayout10" ';
			if (in_array(10,$pagelayout[2])) {$hpsets .= 'checked="checked"';}
			$hpsets .=  ' /> <label for="homelayout10">'._('New messages widget').'</label><br/>';

			$hpsets .= '<input type="checkbox" name="homelayout11" id="homelayout11" ';
			if (in_array(11,$pagelayout[2])) {$hpsets .= 'checked="checked"';}
			$hpsets .= ' /> <label for="homelayout11">'._('New forum posts widget').'</label><br/>';
		}
		if (!isset($CFG['GEN']['fixedhomelayout']) || !in_array(3,$CFG['GEN']['fixedhomelayout'])) {

			$hpsets .= '<input type="checkbox" name="homelayout3-0" id="homelayout3-0" ';
			if (in_array(0,$pagelayout[3])) {$hpsets .= 'checked="checked"';}
			$hpsets .= ' /> <label for="homelayout3-0">'._('New messages notes on course list').'</label><br/>';

			$hpsets .= '<input type="checkbox" name="homelayout3-1" id="homelayout3-1" ';
			if (in_array(1,$pagelayout[3])) {$hpsets .= 'checked="checked"';}
			$hpsets .= ' /> <label for="homelayout3-1">'._('New posts notes on course list').'</label><br/>';
		}
		if ($hpsets != '') {
			echo '<span class="form">',_('Show on home page:'),'</span><span class="formright">';
			echo $hpsets;
			echo '</span><br class="form" />';

		}
		/*  moved to user prefs
		echo '<span class="form"><label for="theme">'._('Overwrite default course theme on all pages:').'</label></span><span class="formright">';
		echo '<select name="theme" id="theme">';
		echo '<option value="" '.($line['theme']==''?'selected':'').'>'._('Use course default theme').'</option>';
		if (isset($CFG['GEN']['stuthemes'])) {
			foreach ($CFG['GEN']['stuthemes'] as $k=>$v) {
				echo '<option value="'.$k.'" '.($line['theme']==$k?'selected':'').'>'._($v).'</option>';
			}
		} else {
			echo '<option value="highcontrast.css" '.($line['theme']=='highcontrast.css'?'selected':'').'>'._('High contrast, dark on light').'</option>';
			echo '<option value="highcontrast_dark.css" '.($line['theme']=='highcontrast_dark.css'?'selected':'').'>'._('High contrast, light on dark').'</option>';
		}
		echo '</select><br class="form" />';
		*/

		if (isset($CFG['GEN']['translatewidgetID'])) {
			echo '<span class="form">Attempt to translate pages into another language:</span>';
			echo '<span class="formright">';
			echo '<div id="google_translate_element"></div><script type="text/javascript">';
			echo ' function googleTranslateElementInit() {';
			echo '  new google.translate.TranslateElement({pageLanguage: "en", layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL}, "google_translate_element");';
			echo ' }</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>';
			echo '<br class="form"/>';
			unset($CFG['GEN']['translatewidgetID']);
		}
		echo '</fieldset>';

		//show accessibilty and display prefs form
		require("includes/userprefs.php");
		showUserPrefsForm();


		if ($myrights>19) {
			echo '<fieldset id="userinfoinstructor"><legend>',_('Instructor Options'),'</legend>';
            // removed 6/7/21
            //echo "<span class=form><label for=\"qrd\">",_('Make new questions private by default?<br/>(recommended for new users):'),"</label></span><span class=formright><input type=checkbox id=qrd name=qrd ";
			//if ($line['qrightsdef']==0) {echo "checked=1";}
			//echo " /></span><BR class=form>\n";
			if ($line['deflib']==0) {
				$lname = "Unassigned";
			} else {
				$stm = $DBH->prepare("SELECT name FROM imas_libraries WHERE id=:id");
				$stm->execute(array(':id'=>$line['deflib']));
				$lname = $stm->fetchColumn(0);
			}

			echo "<script type=\"text/javascript\">";
			echo "var curlibs = '{$line['deflib']}';";
			echo "function libselect() {";
			echo "  window.open('$imasroot/course/libtree2.php?libtree=popup&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));";
			echo " }";
			echo "function setlib(libs) {";
			echo "  document.getElementById(\"libs\").value = libs;";
			echo "  curlibs = libs;";
			echo "}";
			echo "function setlibnames(libn) {";
			echo "  document.getElementById(\"libnames\").innerHTML = libn;";
			echo "}";
			echo "</script>";
			echo "<span class=form>"._('Default question library').":</span><span class=formright> <span id=\"libnames\">".Sanitize::encodeStringForDisplay($lname)."</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"".Sanitize::encodeStringForDisplay($line['deflib'])."\">\n";
			echo " <input type=button value=\"",_('Select Library'),"\" onClick=\"libselect()\"></span><br class=form> ";

			echo "<span class=form><label for=usedeflib>",_('Use default question library for all templated questions?'),"</label></span>";
			echo "<span class=formright><input type=checkbox name=\"usedeflib\" id=\"usedeflib\"";
			if ($line['usedeflib']==1) {echo "checked=1";}
			echo "> ";
			echo "</span><br class=form><p>",_("Default question library is used for all local (assessment-only) copies of questions created when you edit a question (that's not yours) in an assessment.  You can elect to have all templated questions be assigned to this library."),"</p>";
			echo '</fieldset>';

		}
		$requiredrules = array(
			'oldpw'=>'{depends: function(element) {return $("#dochgpw").is(":checked")}}',
			'pw1'=>'{depends: function(element) {return $("#dochgpw").is(":checked")}}',
			'pw2'=>'{depends: function(element) {return $("#dochgpw").is(":checked")}}'
		);
		showNewUserValidation("pageform", array('oldpw'), $requiredrules);

		echo "<div class=submit><input type=submit value='",_('Update Info'),"'></div>\n";
        echo '<script>function doSubmit() { document.getElementById("pageform").submit(); }</script>';
        
		//echo '<p><a href="forms.php?action=googlegadget">Get Google Gadget</a> to monitor your messages and forum posts</p>';
		echo "</form>\n";
		break;
	case "enroll":
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; ",_('Enroll in a Course'),"</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h1>',_('Enroll in a Course'),'</h1></div>';
		echo "<form id=\"pageform\" method=post action=\"actions.php?action=enroll$gb\">";
		$doselfenroll = false;
        $stm = $DBH->query("SELECT id,name FROM imas_courses WHERE istemplate > 0 AND (istemplate&4)=4 AND available<4 ORDER BY name");
        if ($stm->rowCount()>0) {
            $stm2 = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=?");
            $stm2->execute(array($userid));
            $jsondata = json_decode($stm2->fetchColumn(0), true);
            if (empty($jsondata['under13'])) {
                $doselfenroll = true;
            }
        }
		if ($doselfenroll) {
			echo '<p>',_('Select the course you\'d like to enroll in'),'</p>';
			echo '<p><select id="courseselect" name="courseselect" onchange="courseselectupdate(this);">';
			echo '<option value="0" selected="selected">',_('My teacher gave me a course ID (enter below)').'</option>';
			echo '<optgroup label="Self-study courses">';
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				echo '<option value="'.Sanitize::encodeStringForDisplay($row[0]).'">'.Sanitize::encodeStringForDisplay($row[1]).'</option>';
			}
			echo '</optgroup>';
			echo '</select></p>';
			echo '<div id="courseinfo">';
			echo '<script type="text/javascript"> function courseselectupdate(el) { var c = document.getElementById("courseinfo"); var c2 = document.getElementById("selfenrollwarn"); ';
			echo 'if (el.value==0) {c.style.display="";c2.style.display="none";} else {c.style.display="none";c2.style.display="";}}</script>';
		} else {
			echo '<p>',_('Enter the course ID provided by your teacher.'),'</p>';
			echo '<input type="hidden" name="courseselect" id="courseselect" value="0"/>';
		}
		echo '<span class="form"><label for="cid">',_('Course ID'),':</label></span><input class="form" type="text" size="20" name="cid" id="cid"/><br class="form"/>';
		echo '<span class="form"><label for="ekey">',_('Enrollment Key'),':</label></span><input class="form" type="text" size="20" name="ekey" id="ekey"/><br class="form"/>';
		if ($doselfenroll) {
			echo '</div>';
			echo '<div id="selfenrollwarn" class=noticetext style="display:none;">',_('Warning: You have selected a non-credit self-study course. ');
			echo sprintf(_('If you are using %s with an instructor-led course, this is NOT what you want; nothing you do in the self-study course will be viewable by your instructor or count towards your course. For an instructor-led course, you need to enter the course ID and key provided by your instructor.'),$installname);
			echo '</div>';
		}
		echo '<script type="text/javascript">
		$("#pageform").validate({
			rules: {
				cid: {
					required: {depends: function(element) {return $("#courseselect").val()==0}}
				}
			},
			invalidHandler: function() {setTimeout(function(){$("#pageform").removeClass("submitted").removeClass("submitted2");}, 100)}}
		);
		</script>';
		echo '<div class=submit><input type=submit value="',_('Sign Up'),'"></div>';
		echo '</form>';
		break;
	case "unenroll":
		if (!isset($_GET['cid'])) { echo _("Course ID not specified")."\n"; break;}
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; ",_('Unenroll'),"</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h1>',_('Unenroll'),'</h1></div>';

		echo _("Are you SURE you want to unenroll from this course?  All assessment attempts will be deleted."),"\n";
		echo '<form method="post" action="actions.php?cid='.Sanitize::courseId($_GET['cid']).'">';
		echo '<p><button name="action" value="unenroll">'._('Really Unenroll').'</button>';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='./course/course.php?cid=".Sanitize::courseId($_GET['cid'])."'\"></p>\n";
		echo '</form>';
		break;
	case "resetpw":
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; ",_('Password Reset'),"</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h1>',_('Reset Password'),'</h1></div>';
		echo "<form id=\"pageform\" class=limitaftervalidate method=post action=\"actions.php?action=resetpw$gb\">\n";
		if (isset($_GET['code'])) {
			$userId = Sanitize::onlyInt($_GET['id']);
			$stm = $DBH->prepare("SELECT remoteaccess FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$userId));
			$row = $stm->fetch(PDO::FETCH_ASSOC);
			if ($row !== false && $row['remoteaccess']!='' && $row['remoteaccess']===$_GET['code']) {
				echo '<input type="hidden" name="code" value="'.Sanitize::encodeStringForDisplay($_GET['code']).'"/>';
				echo '<input type="hidden" name="id" value="'.Sanitize::encodeStringForDisplay($_GET['id']).'"/>';
				echo '<p>',_('Please select a new password'),':</p>';
				echo '<p>',_('Enter new password'),':  <input type="password" size="25" id=pw1 name="pw1"/><br/>';
				echo '<p>',_('Verify new password'),':  <input type="password" size="25" id=pw2 name="pw2"/></p>';
				echo "<p><input type=submit value=\"",_('Submit'),"\" /></p></form>";
				showNewUserValidation("pageform");
			} else {
				echo '<p>',_('Invalid reset code.  If you have requested a password reset multiple times, you need the link from the most recent email.'),'</p>';
			}
		} else {
			echo "<p>",_('Enter your User Name below and click Submit.  An email will be sent to your email address on file.  A link in that email will reset your password.'),"</p>";
			echo "<p><label for=username>",_('User Name'),"</label>: <input type=text class=\"pii-username\" name=\"username\" id=username /></p>";
			echo '<script type="text/javascript">
			$("#pageform").validate({
				rules: {
					username: { required: true}
				},
				invalidHandler: function() {setTimeout(function(){$("#pageform").removeClass("submitted").removeClass("submitted2");}, 100)}}
			);
			</script>';
            echo '<p class="sr-only"><label aria-hidden=true>Do not fill this out <input name=terms tabindex="-1" autocomplete="off" /></label></p>';
            $_SESSION['challenge'] = uniqid();
            echo '<input type=hidden name=challenge value="'.Sanitize::encodeStringForDisplay($_SESSION['challenge']).'"/>';
            $_SESSION['resetpwstart'] = time();    
			echo "<p><input type=submit value=\"",_('Submit'),"\" /></p>";
			echo "</form>";
		}

		break;
	case "lookupusername":
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; ",_('Username Lookup'),"</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h1>',_('Lookup Username'),'</h1></div>';
		echo "<form id=\"pageform\" method=post action=\"actions.php?action=lookupusername$gb\">\n";
		echo _("If you can't remember your username, enter your email address below.  An email will be sent to your email address with your username. ");
		echo "<p><label for=email>",_('Email'),"</label>: <input type=text class=\"pii-email\" name=\"email\" id=email /></p>";
		echo '<script type="text/javascript">
		$("#pageform").validate({
			rules: {
				email: { required: true, email: true}
			},
			invalidHandler: function() {setTimeout(function(){$("#pageform").removeClass("submitted").removeClass("submitted2");}, 100)}}
		);
		</script>';
        echo '<p class="sr-only"><label aria-hidden=true>Do not fill this out <input name=terms tabindex="-1" autocomplete="off" /></label></p>';
        $_SESSION['challenge'] = uniqid();
        echo '<input type=hidden name=challenge value="'.Sanitize::encodeStringForDisplay($_SESSION['challenge']).'"/>';
        $_SESSION['lookupusernamestart'] = time();    

		echo "<p><input type=submit value=\"",_('Submit'),"\" /></p>";
		echo "</form>";
		break;
	case "forumwidgetsettings":
		$stm = $DBH->prepare("SELECT hideonpostswidget FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$userid));
		$hidelist = explode(',', $stm->fetchColumn(0));
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; ",_('Forum Widget Settings'),"</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h1>',_('Forum Widget Settings'),'</h1></div>';
		echo '<p>',_('The most recent 10 posts from each course show in the New Forum Posts widget.  Select the courses you want to show in the widget.'),'</p>';
		echo "<form method=post action=\"actions.php?action=forumwidgetsettings$gb\">\n";
		$allcourses = array();
		$stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers AS it ON ic.id=it.courseid WHERE it.userid=:userid ORDER BY ic.name");
		$stm->execute(array(':userid'=>$userid));
		if ($stm->rowCount()>0) {
			echo '<p><b>',_('Courses you\'re teaching'),':</b> ',_('Check'),': <a href="#" onclick="$(\'.teaching\').prop(\'checked\',true);return false;">',_('All'),'</a> <a href="#" onclick="$(\'.teaching\').prop(\'checked\',false);return false;">',_('None'),'</a>';
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$allcourses[] = $row[0];
				echo '<br/><input type="checkbox" name="checked[]" class="teaching" value="'.Sanitize::encodeStringForDisplay($row[0]).'" id="c'.Sanitize::encodeStringForDisplay($row[0]).'"';
				if (!in_array($row[0],$hidelist)) {echo 'checked="checked"';}
				echo '/> <label for="c'.Sanitize::encodeStringForDisplay($row[0]).'">'.Sanitize::encodeStringForDisplay($row[1]).'</label>';
			}
			echo '</p>';
		}
		$stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_tutors AS it ON ic.id=it.courseid WHERE it.userid=:userid ORDER BY ic.name");
		$stm->execute(array(':userid'=>$userid));
		if ($stm->rowCount()>0) {
			echo '<p><b>',_('Courses you\'re tutoring'),':</b> ',_('Check'),': <a href="#" onclick="$(\'.tutoring\').prop(\'checked\',true);return false;">',_('All'),'</a> <a href="#" onclick="$(\'.tutoring\').prop(\'checked\',false);return false;">',_('None'),'</a>';
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$allcourses[] = Sanitize::encodeStringForDisplay($row[0]);
				echo '<br/><input type="checkbox" name="checked[]" class="tutoring" value="'.Sanitize::encodeStringForDisplay($row[0]).'" id="c'.Sanitize::encodeStringForDisplay($row[0]).'"';
				if (!in_array($row[0],$hidelist)) {echo 'checked="checked"';}
				echo '/> <label for="c'.Sanitize::encodeStringForDisplay($row[0]).'">'.Sanitize::encodeStringForDisplay($row[1]).'</label>';
			}
			echo '</p>';
		}
		$stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_students AS it ON ic.id=it.courseid WHERE it.userid=:userid ORDER BY ic.name");
		$stm->execute(array(':userid'=>$userid));
		if ($stm->rowCount()>0) {
			echo '<p><b>',_('Courses you\'re taking'),':</b> ',_('Check'),': <a href="#" onclick="$(\'.taking\').prop(\'checked\',true);return false;">',_('All'),'</a> <a href="#" onclick="$(\'.taking\').prop(\'checked\',false);return false;">',_('None'),'</a>';
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$allcourses[] = $row[0];
				echo '<br/><input type="checkbox" name="checked[]" class="taking" value="'.Sanitize::encodeStringForDisplay($row[0]).'" id="c'.Sanitize::encodeStringForDisplay($row[0]).'"';
				if (!in_array($row[0],$hidelist)) {echo 'checked="checked"';}
				echo '/> <label for="c'.Sanitize::encodeStringForDisplay($row[0]).'">'.Sanitize::encodeStringForDisplay($row[1]).'</label>';
			}
			echo '</p>';
		}
		echo '<input type="hidden" name="allcourses" value="'.Sanitize::encodeStringForDisplay(implode(',',$allcourses)).'"/>';
		echo '<input type="submit" value="',_('Save Changes'),'"/>';
		echo '</form>';
        break;
    case "adminmfanotice":
        echo '<p>'._('For security, this site requires all admin-level accounts to enable Two-Factor Authentication. ');
        echo sprintf(_('Please visit the <a href="%s">user profile page</a> and enable 2-factor authentication.'),
            'forms.php?action=chguserinfo');
        echo '</p>';
        break;
	case "googlegadget":
		$stm = $DBH->prepare("SELECT remoteaccess FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$userid));
		$code = $stm->fetchColumn(0);
		if ($code=='' || isset($_GET['regen'])) {
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			do {
				$pass = '';
				for ($i=0;$i<10;$i++) {
					$pass .= substr($chars,rand(0,61),1);
				}
				$stm = $DBH->prepare("SELECT id FROM imas_users WHERE remoteaccess=:remoteaccess");
				$stm->execute(array(':remoteaccess'=>$pass));
			} while ($stm->rowCount()>0);
			$stm = $DBH->prepare("UPDATE imas_users SET remoteaccess=:remoteaccess WHERE id=:id");
			$stm->execute(array(':remoteaccess'=>$pass, ':id'=>$userid));
			$code = $pass;
		}
		echo '<div id="headerforms" class="pagetitle"><h1>Google Gadget Access Code</h1></div>';
		echo "<p>The $installname Google Gadget allow you to view a list of new forum posts ";
		echo "and messages from your iGoogle page.  To install, click the link below to add ";
		echo "the gadget to your iGoogle page, then use the Access key below in the settings ";
		echo "to gain access to your data</p>";

		echo "<p>Access Code: ".Sanitize::encodeStringForDisplay($code)."</p>";
		echo "<p><a href=\"forms.php?action=googlegadget&regen=true$gb\">Generate a new Access code<a/><br/>";
		echo "<p><a href=\"actions.php?action=googlegadget&clear=true$gb\">Clear Access code</a></p>";
		echo "<p>Note: This access code only allows Google to access a list of new posts and messages, and does not provide access to grades or any other data stored at $installname.  Be aware that this form of access is insecure and could be intercepted by a third party.</p>";
		echo "<p>You can also bookmark <a href=\"getpostlist.php?key=".Sanitize::encodeStringForDisplay($code)."\">this page</a> to be able to access your post list without needing to log in.</p>";
		break;
}
	require("footer.php");
?>
