<?php
//functions for common stuff across new user login pages

function showNewUserValidation($formname, $extrarequired=array(), $requiredrules=array(), $options=array()) {
  global $loginformat, $CFG;

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
        remote: _("That username is already taken. Try another."),';
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

function checkNewUserValidation($required = array('SID','firstname','lastname','email','pw1','pw2')) {
  global $loginformat, $CFG, $DBH;

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
	  if ($stm->rowCount()>0) {
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
	  if (isset($CFG['acct']['passwordFormat']) && !checkFormatAgainstRegex($_POST['pw1'], $CFG['acct']['passwordFormat'])) {
	    $errors[] = "Invalid password format.";
	  }
	  if (isset($CFG['acct']['passwordMinlength'])) {
	    $pwminlen = Sanitize::onlyInt($CFG['acct']['passwordMinlength']);
	  } else {
	    $pwminlen =  6;
	  }
	  if (strlen($_POST['pw1']) <$pwminlen ) {
	    $errors[] = "Password must be at least $pwminlen characters.";
	  }
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
