<?php
//functions for common stuff across new user login pages

function showNewUserValidation($formname, $extrarequired=array(), $requiredrules=array(), $options=array()) {
  global $loginformat, $CFG;

  $extrarequired = array_diff($extrarequired,array('SID','firstname','lastname','email','pw1','pw2'));

  if (is_array($loginformat)) {
    $loginformat = '['.implode(',', $loginformat).']';
  }
  if (isset($CFG['acct']['passwordFormat']) && is_array($CFG['acct']['passwordFormat'])) {
    $CFG['acct']['passwordFormat'] = '['.implode(',', $CFG['acct']['passwordFormat']).']';
  }
  if (isset($CFG['acct']['emailFormat']) && is_array($CFG['acct']['emailFormat'])) {
    $CFG['acct']['emailFormat'] = '['.implode(',', $CFG['acct']['emailFormat']).']';
  }

  echo '<script type="text/javascript">
  $(function() {
	$("#'.Sanitize::simpleString($formname).'").validate({
    onkeyup: function(element, event) {
      if (element.name === "SID") {
        // no live (remote) validation on SID; just clear a stale error
        if ([16,17,18,20,35,36,37,38,39,40,45,144,225].indexOf(event.keyCode) !== -1) { return; }
        if ($(element).hasClass(this.settings.errorClass)) {
          this.settings.unhighlight.call(this, element, this.settings.errorClass, this.settings.validClass);
          this.hideThese(this.errorsFor(element));
          $("#errorlive").text("");
        }
        return;
      }
      $.validator.defaults.onkeyup.call(this, element, event);
    },
    showErrors: function(m, l) {
        let txt = "";
        for (const id in m) {
            txt += m[id] + " ";
        }
        $("#errorlive").text(txt);
        this.defaultShowErrors();
    },
    rules: {
      SID: {
        required: '.(isset($requiredrules['SID'])?$requiredrules['SID']:'true').',
        pattern: '.$loginformat.',
        remote: imasroot+"/actions.php?action=checkusername';
        if (isset($options['originalSID'])) {
          echo '&originalSID='.Sanitize::encodeUrlParam($options['originalSID']);
        }
        echo '"
      },
      pw1: {
      	required: '.(isset($requiredrules['pw1'])?$requiredrules['pw1']:'true').',';
        if (isset($CFG['acct']['passwordFormat'])) {
          echo 'pattern: '.$CFG['acct']['passwordFormat'].',';
        }
        if (in_array('oldpw', $extrarequired)) {
        	echo 'notEqual: "#oldpw",';
        }
        echo 'minlength: '.(isset($CFG['acct']['passwordMinlength'])?$CFG['acct']['passwordMinlength']:6).'
      },
      pw2: {
        required: '.(isset($requiredrules['pw2'])?$requiredrules['pw2']:'true').',
        equalTo: "#pw1"
      },
      firstname: { required: '.(isset($requiredrules['firstname'])?$requiredrules['firstname']:'true').'},
      lastname: {required: '.(isset($requiredrules['lastname'])?$requiredrules['lastname']:'true').'},
      email: {
        required: '.(isset($requiredrules['email'])?$requiredrules['email']:'true').',';
        if (isset($CFG['acct']['emailFormat'])) {
          echo 'pattern: '.$CFG['acct']['emailFormat'].',';
        }
        echo 'email: true
      }, ';
      foreach ($extrarequired as $field) {
        echo Sanitize::simpleString($field).': {required: '.(isset($requiredrules[$field])?$requiredrules[$field]:'true').'},';
      }
  echo '
    },
    messages: {
      SID: {
        remote: "',_("That username is already taken. Try another."),'",';
      if (isset($CFG['acct']['SIDformaterror'])) {
        echo 'pattern: "'.Sanitize::encodeStringForJavascript($CFG['acct']['SIDformaterror']).'",';
      }
echo '
      },';
      if (isset($CFG['acct']['passwordFormaterror'])) {
        echo 'pw1: {pattern: "'.Sanitize::encodeStringForJavascript($CFG['acct']['passwordFormaterror']).'"},';
      }
      if (isset($CFG['acct']['emailFormaterror'])) {
        echo 'email: {pattern: "'.Sanitize::encodeStringForJavascript($CFG['acct']['emailFormaterror']).'"},';
      }
echo '},
    invalidHandler: function() {
      setTimeout(function(){$("#'.Sanitize::simpleString($formname).'").removeClass("submitted").removeClass("submitted2");}, 100);
		},
		submitHandler: function(el,evt) {return submitlimiter(evt);}
  });
	});
  </script>';
}

/**
 * Whether a terms-of-use agreement is configured for new student accounts
 */
function studentTOSRequired() {
  global $CFG, $studentTOS;
  return (isset($studentTOS) || isset($CFG['GEN']['TOSpage']));
}

/**
 * Returns the HTML for the "I agree to the terms of use" form row
 */
function getStudentTOSAgreeRow() {
  global $CFG, $studentTOS;
  if (isset($studentTOS)) {
    $label = _('I have read and agree to the Terms of Use (below)');
  } else if (isset($CFG['GEN']['TOSpage'])) {
    $t1 = _('Terms of Use');
    $ta = sprintf("<a href=\"#\" onclick=\"GB_show('%s','".$CFG['GEN']['TOSpage']."',700,500);return false;\">%s</a>", $t1, $t1);
    $label = sprintf(_('I have read and agree to the %s'), $ta);
  } else {
    return '';
  }
  return "<span class=form><label for=\"agree\">".$label."</label></span><span class=formright><input type=checkbox name=agree id=agree></span><br class=form />\n";
}

/**
 * Outputs the full TOS text below the form, if a $studentTOS file is configured
 */
function showStudentTOSText() {
  global $studentTOS;
  if (isset($studentTOS)) {
    require_once $studentTOS;
  }
}

function checkFormatAgainstRegex($val, $regexs) {
  if (!is_array($regexs)) {
    $regexs = array($regexs);
  }
  $isok = true;
  foreach ($regexs as $regex) {
    $isok = $isok && preg_match($regex, $val);
  }
  return $isok;
}

/**
 * Checks a new password against the configured policy (format and minimum length).
 * Returns an array of error messages; empty if the password is acceptable.
 */
function getPasswordPolicyErrors($pw) {
  global $CFG;

  $errors = array();
  $pw = (string) $pw;
  if (isset($CFG['acct']['passwordFormat']) && !checkFormatAgainstRegex($pw, $CFG['acct']['passwordFormat'])) {
    $errors[] = "Invalid password format.";
  }
  if (isset($CFG['acct']['passwordMinlength'])) {
    $pwminlen = Sanitize::onlyInt($CFG['acct']['passwordMinlength']);
  } else {
    $pwminlen =  6;
  }
  if (strlen($pw) < $pwminlen) {
    $errors[] = "Password must be at least $pwminlen characters.";
  }
  return $errors;
}

function checkNewUserValidation($required = array('SID','firstname','lastname','email','pw1','pw2')) {
  global $loginformat, $loginprompt, $CFG, $DBH;

  $errors = array();
  foreach ($required as $v) {
    if (empty($_POST[$v])) {
      //JS validation should prevent us from ever showing this, hence the
      //cruddy unhelpful format here
      $errors[] = "Field ".Sanitize::encodeStringForDisplay($v)." is required";
    }
  }
	if (in_array('SID',$required)) {
	  if ($loginformat != '' && !checkFormatAgainstRegex($_POST['SID'], $loginformat)) {
	    $errors[] = "$loginprompt has invalid format";
	  }
	  $sid_value = Sanitize::stripHtmlTags($_POST['SID']);
	  $stm = $DBH->prepare('SELECT id FROM imas_users WHERE SID=:sid');
	  $stm->execute(array(':sid'=>$sid_value));
	  if ($stm->fetch(PDO::FETCH_NUM) !== false) {
	    $errors[] =  "$loginprompt '" . Sanitize::encodeStringForDisplay($sid_value) . "' is already used. ";
	  }
	}
	if (in_array('email',$required)) {
		//if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/',$_POST['email']) ||
		if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ||
	    (isset($CFG['acct']['emailFormat']) && !checkFormatAgainstRegex($_POST['email'], $CFG['acct']['emailFormat']))) {
	    $errors[] = "Invalid email address.";
	  }
	}
	if (in_array('pw1',$required)) {
	  $errors = array_merge($errors, getPasswordPolicyErrors($_POST['pw1']));
	  if (in_array('pw2',$required) && $_POST['pw1'] != $_POST['pw2']) {
	    $errors[] = "Passwords don't match.";
	  }
	}

  if (count($errors)==0) {
    return '';
  } else {
    return '<p>'.implode('</p><p>', $errors).'</p>';
  }
}
