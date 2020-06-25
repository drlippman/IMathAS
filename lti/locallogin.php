<?php

/**
 * Generate postback form for getting tzname, and login info if needed.
 * @param  LTI_Message_Launch $launch
 * @param  Imathas_LTI_Database $db
 */
function show_postback_form($launch, $db) {
  global $imasroot,$installname;
  $promptForName = false;
  $promptForAcctCreation = false;
  $promptForLogin = false;

  $role = standardize_role($launch->get_roles());
  $contextid = $launch->get_platform_context_id();
  $ltiuserid = $launch->get_platform_user_id();
  $platform_id = $launch->get_platform_id();

  // see if we already know who this person is
  $localuserid = $db->get_local_userid($ltiuserid, $platform_id);

  if ($role == 'Learner') {
    $localcourse = $db->get_local_course($contextid, $platform_id);
    if ($localcourse === false) {
      // no course link established yet - abort
      echo _("Course link not established yet.  Notify your instructor they need to click this assignment to set it up.");
      exit;
    }
    if ($localuserid === false) {
      // no local user yet - see if we have enough info
      $name = parse_name_from_launch($launch->get_launch_data());
      if ($name === false) {
        $promptForName = true;
      }
      if (!empty($localcourse['allow_direct_login'])) {
        $promptForLogin = true;
        $promptForAcctCreation = true;
      }
    }
  } else if ($role == 'Instructor') {
    if ($localuserid === false) {
      $name = parse_name_from_launch($launch->get_launch_data());
      $promptForLogin = true;
      // if we allow instructor creation via LTI, see if we know a groupid
      // associated with this deployment; we'll only trust LTI to create instructors
      // if we recognize the deployment and can assign them to a group
      if (!empty($GLOBALS['lti']['allow_instr_create'])) {
        $groups = $this->db->get_groups($launch->get_issuer(), $launch->get_deployment_id());
        if (count($groups)>0) {
          $promptForAcctCreation = true;
        }
      }
    }
  }

  if (!empty($name)) {
    $deffirst = $name['first'];
    $deflast = $name['last'];
    $defemail = '';
    if (!empty($launch->get_launch_data()['email'])) {
      $defemail = $launch->get_launch_data()['email'];
    }
  }

  $GLOBALS['flexwidth'] = true;
	$GLOBALS['nologo'] = true;
	$GLOBALS['placeinhead'] = "<script type=\"text/javascript\" src=\"$imasroot/javascript/jstz_min.js\" ></script>";
  $GLOBALS['placeinhead'] .= '<script type="text/javascript" src="'.$imasroot.'/javascript/jquery.validate.min.js?v=122917"></script>';

	require("../header.php");
	echo '<h1>'.sprintf(_('Connecting to %s'),$installname).'</h1>';
  echo '<form id=postbackform method=post action="finishlogin.php">';
  echo '<input type=hidden name=launchid value="'.$launch->get_launch_id().'"/>';
  ?>
  <input type="hidden" id="tzoffset" name="tzoffset" value="" />
	<input type="hidden" id="tzname" name="tzname" value="">
	<script type="text/javascript">
		 $(function() {
			var thedate = new Date();
			document.getElementById("tzoffset").value = thedate.getTimezoneOffset();
			var tz = jstz.determine();
			document.getElementById("tzname").value = tz.name();
		});
	</script>
  <?php
  if ($promptForLogin) {
    echo '<p>'.sprintf(_('If you already have an account on %s, please enter your username and password below to enable automated signin.'), $installname).'</p>';
    echo '<span class=form><label for="curSID">' .
      Sanitize::encodeStringForDisplay($GLOBALS['loginprompt']). ':</label></span>';
    echo ' <input class=form type=text size=12 id="curSID" name="curSID"><BR class=form>';
    echo '<span class=form><label for="curPW">'._('Password').':</label></span>';
    echo ' <input class=form type=password size=20 id="curPW" name="curPW"><BR class=form>';
    echo '<div class=submit><button type=submit>'._('Sign In').'</button></div>';
    if ($promptForAcctCreation) {
      echo '<p>'.sprintf(_('If you do not already have an account on %s, provide the information below to create an account and enable automated signon'),$installname).'</p>';
      echo '<span class=form><label for="SID">'.Sanitize::encodeStringForDisplay($GLOBALS['longloginprompt']).':</label></span> <input class=form type=text size=12 id=SID name=SID><BR class=form>';
      echo '<span class=form><label for="pw1">'._('Choose a password').':</label></span><input class=form type=password size=20 id=pw1 name=pw1><BR class=form>';
      echo '<span class=form><label for="pw2">'._('Confirm password').':</label></span> <input class=form type=password size=20 id=pw2 name=pw2><BR class=form>';
      echo '<span class=form><label for="firstname">'._('Enter First Name').':</label></span> <input class=form type=text autocomplete="given-name" value="'.Sanitize::encodeStringForDisplay($deffirst).'" size=20 id=firstnam name=firstname><BR class=form>';
      echo '<span class=form><label for="lastname">'._('Enter Last Name').':</label></span> <input class=form type=text autocomplete="family-name" value="'.Sanitize::encodeStringForDisplay($deflast).'" size=20 id=lastname name=lastname><BR class=form>';
      echo '<span class=form><label for="email">'._('Enter E-mail address').':</label></span>  <input class=form type=email autocomplete="email" value="'.Sanitize::encodeStringForDisplay($defemail).'" size=60 id=email name=email><BR class=form>';
      echo '<span class=form><label for="msgnot">'._('Notify me by email when I receive a new message').':</label></span><input class=floatleft type=checkbox id=msgnot name=msgnot /><BR class=form>';
      echo '<div class=submit><button type=submit>'._('Create Account').'</button></div>';
      require_once(__DIR__.'/../includes/newusercommon.php');
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
      showNewUserValidation('postbackform',array('curSID','curPW'), $requiredrules);
    } else {
      echo '<script type="text/javascript"> $(function() {
        $("#postbackform").validate({
          rules: {
            curSID: {required: true},
            curPW: {required: true}
          },
          submitHandler: function(el,evt) {return submitlimiter(evt);}
        });
      });</script>';
      if ($role === 'Instructor') {
        echo '<p>'.sprintf(_('If you need an account, please visit the %s website to request an account'), $installname).'</p>';
      }
    }
  } else if ($promptForName) {
    echo '<p>'._('Please provide a little information about yourself').'</p>';
    echo '<span class=form><label for="firstname">'._('Enter First Name').':</label></span> <input class=form type=text size=20 id=firstname name=firstname autocomplete="given-name"><BR class=form>';
    echo '<span class=form><label for="lastname">'._('Enter Last Name').':</label></span> <input class=form type=text size=20 id=lastname name=lastname autocomplete="family-name"><BR class=form>';
    echo '<div class=submit><button type=submit>'._('Continue').'</button></div>';
    echo '<script type="text/javascript"> $(function() {
      $("#pageform").validate({
        rules: {
          firstname: {required: true},
          lastname: {required: true}
        },
        submitHandler: function(el,evt) {return submitlimiter(evt);}
      });
    });</script>';
  }
  echo '</form>';
  require('../footer.php');
}
