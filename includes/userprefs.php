<?php

//also make sure you have in the header:
// $placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/jstz_min.js\" ></script>";
// and 

function showUserPrefsForm() {
	global $CFG, $sessiondata, $tzname;
	
	require_once(dirname(__FILE__)."/htmlutil.php");
	
	$prefs = array();
	$prefs['mathdisp'] = array(
		'1'=>_('MathJax - best display and best for screenreaders'),
		'6'=>_('Katex - faster display'),
		'2'=>_('Image-based display'),
		'0'=>_('Calculator-style linear display, like x^2/3'));
	$prefs['graphdisp'] = array(
		'1'=>_('SVG in browser - highest quality'),
		'2'=>_('Imaged-based - visual alternative'),
		'0'=>_('Text alternatives - tables or charts in place of graphs'));
	$prefs['drawentry'] = array(
		'1'=>_('Mouse-based visual drawing entry'),
		'0'=>_('Keyboard and text-based drawing entry alternative'));
	$prefs['useed'] = array(
		'1'=>_('Rich text editor with formatting buttons'),
		'0'=>_('Plain text entry'));
	$prefs['tztype'] = array(
		'0'=>_('Use timezone as reported by the browser'),
		'1'=>_('Use a specific timezone for this session only'),
		'2'=>_('Always show times based on a specific timezone'));
	$prefs['usertheme'] = array(
		'0'=>_('Use instructor chosen course theme')); 
	if (isset($CFG['GEN']['stuthemes'])) {
		foreach ($CFG['GEN']['stuthemes'] as $k=>$v) {
			$prefs['usertheme'][$k] = $v;
		}
	} else {
		$prefs['usertheme']['highcontrast.css']=_('High contrast, dark on light');
		$prefs['usertheme']['highcontrast_dark.css']=_('High contrast, light on dark');
	}
	$prefs['livepreview'] = array(
		'1'=>_('Show live preview of answer entry as I type'),
		'0'=>_('Only show a preview when I click the Preview button'));
	$prefdefaults = array(
		'mathdisp'=>1,
		'graphdisp'=>1,
		'drawentry'=>1,
		'useed'=>1,
		'tztype'=>0,
		'usertheme'=>0,
		'livepreview'=>1);
	$prefdescript = array(
		'mathdisp'=>_('Math Display'),
		'graphdisp'=>_('Graph Display'),
		'drawentry'=>_('Drawing Entry'),
		'useed'=>_('Text Editor'),
		'usertheme'=>_('Course styling and contrast'),
		'livepreview'=>_('Live preview'),
		'tztype'=>_('Time Zone'));
	
	foreach($prefdefaults as $k=>$v) {
		if (isset($CFG['UP'][$k])) {
			$prefdefaults[$k] = $CFG['UP'][$k];
		}
		//mark default etnry with *
		$prefs[$k][$prefdefaults[$k]] = '* '.$prefs[$k][$prefdefaults[$k]];
	}
	$sessiondata['userprefs']['tztype'] = isset($sessiondata['userprefs']['tzname'])?2:0;
	
	$timezones = array('Etc/GMT+12', 'Pacific/Pago_Pago', 'America/Adak', 'Pacific/Honolulu', 'Pacific/Marquesas', 'Pacific/Gambier', 'America/Anchorage', 'America/Los_Angeles', 'Pacific/Pitcairn', 'America/Phoenix', 'America/Denver', 'America/Guatemala', 'America/Chicago', 'Pacific/Easter', 'America/Bogota', 'America/New_York', 'America/Caracas', 'America/Halifax', 'America/Santo_Domingo', 'America/Santiago', 'America/St_Johns', 'America/Godthab', 'America/Argentina/Buenos_Aires', 'America/Montevideo', 'Etc/GMT+2', 'Etc/GMT+2', 'Atlantic/Azores', 'Atlantic/Cape_Verde', 'Etc/UTC', 'Europe/London', 'Europe/Berlin', 'Africa/Lagos', 'Africa/Windhoek', 'Asia/Beirut', 'Africa/Johannesburg', 'Asia/Baghdad', 'Europe/Moscow', 'Asia/Tehran', 'Asia/Dubai', 'Asia/Baku', 'Asia/Kabul', 'Asia/Yekaterinburg', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Kathmandu', 'Asia/Dhaka', 'Asia/Omsk', 'Asia/Rangoon', 'Asia/Krasnoyarsk', 'Asia/Jakarta', 'Asia/Shanghai', 'Asia/Irkutsk', 'Australia/Eucla', 'Australia/Eucla', 'Asia/Yakutsk', 'Asia/Tokyo', 'Australia/Darwin', 'Australia/Adelaide', 'Australia/Brisbane', 'Asia/Vladivostok', 'Australia/Sydney', 'Australia/Lord_Howe', 'Asia/Kamchatka', 'Pacific/Noumea', 'Pacific/Norfolk', 'Pacific/Auckland', 'Pacific/Tarawa', 'Pacific/Chatham', 'Pacific/Tongatapu', 'Pacific/Apia', 'Pacific/Kiritimati');
			
	echo '<fieldset id="userinfoprefs"><legend>'._('Accessibility and Display Preferences').'</legend>';
	echo '<p>'._('Default settings are indicated with a *').'</p>';
	foreach ($prefdescript as $key=>$descrip) {
		echo '<span class=form><label for="'.$key.'">'.$descrip.'</label></span>';
		echo '<span class=formright>';
		writeHtmlSelect($key,array_keys($prefs[$key]), array_values($prefs[$key]), isset($sessiondata['userprefs'][$key])?$sessiondata['userprefs'][$key]:$prefdefaults[$key]);
		if ($key=='tztype') {
			echo '<span id="tzset" style="display:none;"><br/>';
			echo '<label for="settimezone">'._('Set timezone to:').'</label> <select name="settimezone" id="settimezone">';
			foreach ($timezones as $tz) {
				echo '<option value="'.$tz.'" '.($tz==$tzname?'selected':'').'>'.$tz.'</option>';
			}
			echo '</select></span>';
			echo '<script type="text/javascript"> $(function() {
				var oldval = $("#tztype option[value=0]").text();
				$("#tztype option[value=0]").text(oldval + ": "+jstz.determine().name());
				if ($("#tztype").val()==0 && $("#settimezone").val()!=jstz.determine().name()) {
					$("#tztype").val(1);
				}	
				$("#tztype").on("change", function() {
					if ($(this).val()==0) { 
						$("#tzset").hide();
						$("#settimezone").val(jstz.determine().name());
					} else {
						$("#tzset").show();
					}
					}).trigger("change");});</script>';
		}
		echo '</span><br class=form />';
	}
	echo '</fieldset>';

}

function storeUserPrefs() {
	global $CFG, $DBH, $sessiondata, $userid, $tzname, $sessionid;
	
	//save user prefs.  Get existing
	$currentuserprefs = array();
	$stm = $DBH->prepare("SELECT item,id,value FROM imas_user_prefs WHERE userid=:id");
	$stm->execute(array(':id'=>$userid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$currentuserprefs[$row[0]] = array($row[1],$row[2]);
	}
	$prefdefaults = array(
		'mathdisp'=>1,
		'graphdisp'=>1,
		'drawentry'=>1,
		'useed'=>1,
		'usertheme'=>0,
		'livepreview'=>1);
	foreach($prefdefaults as $k=>$v) {
		$sessiondata['userprefs'][$k] = $_POST[$k];
		if (isset($CFG['UP'][$k])) {
			$prefdefaults[$k] = $CFG['UP'][$k];
		}
		if (strcmp($_POST[$k],$prefdefaults[$k])==0) { //selected default
			if (isset($currentuserprefs[$k])) {
				$stm = $DBH->prepare("DELETE FROM imas_user_prefs WHERE id=:id");
				$stm->execute(array(':id'=>$currentuserprefs[$k][0]));
				if ($k=='usertheme') {
					unset($sessiondata['userprefs'][$k]);
					unset($sessiondata['coursetheme']);
				}
			}
		} else {
			if (isset($currentuserprefs[$k])) {
				if (strcmp($currentuserprefs[$k][1],$_POST[$k])!=0) {
					//new value - update
					$stm = $DBH->prepare("UPDATE imas_user_prefs SET value=:value WHERE id=:id");
					$stm->execute(array(':value'=>$_POST[$k], ':id'=>$currentuserprefs[$k][0]));
				}
			} else { //no current value - create new pref entry
				$stm = $DBH->prepare("INSERT INTO imas_user_prefs (item,value,userid) VALUES (:item,:value,:userid)");
				$stm->execute(array(':item'=>$k, ':value'=>$_POST[$k], ':userid'=>$userid));
			}
		}
	}
	//use timezone from form - either browser reported or set val
    $tzname = Sanitize::stripHtmlTags($_POST['settimezone']);
	if (date_default_timezone_set($_POST['settimezone'])) {
		//$tzname = $_POST['settimezone'];
		$stm = $DBH->prepare("UPDATE imas_sessions SET tzname=:tzname WHERE sessionid=:sessionid");
		$stm->execute(array(':tzname'=>$tzname, ':sessionid'=>$sessionid));
	}
	if ($_POST['tztype']==2) { //using a permanant fixed timezone - record it     
		$sessiondata['userprefs']['tzname'] = $tzname;
		if (isset($currentuserprefs['tzname'])) {
			$stm = $DBH->prepare("UPDATE imas_user_prefs SET value=:value WHERE id=:id");
			$stm->execute(array(':value'=>$tzname, ':id'=>$currentuserprefs['tzname'][0]));
		} else {
			$stm = $DBH->prepare("INSERT INTO imas_user_prefs (item,value,userid) VALUES ('tzname',:value,:userid)");
			$stm->execute(array(':value'=>$tzname, ':userid'=>$userid));
		}
	} else { //no permanant fixed timezone, delete tzname record if exists
		unset($sessiondata['userprefs']['tzname']);
		if (isset($currentuserprefs['tzname'])) {
			$stm = $DBH->prepare("DELETE FROM imas_user_prefs WHERE id=:id");
			$stm->execute(array(':id'=>$currentuserprefs['tzname'][0]));
		}
	}
	foreach(array('graphdisp','mathdisp','useed') as $key) {
		$sessiondata[$key] = $sessiondata['userprefs'][$key];
	}
	writesessiondata();
}

function generateuserprefs($writetosession=false) {
	global $DBH, $CFG, $sessiondata, $sessionid, $userid;
	
	$sessiondata['userprefs'] = array();
	$prefdefaults = array(
		'mathdisp'=>1,
		'graphdisp'=>1,
		'drawentry'=>1,
		'useed'=>1,
		'livepreview'=>1);
	
	if (strpos(basename($_SERVER['PHP_SELF']),'upgrade.php')===false) {
		$stm = $DBH->prepare("SELECT item,value FROM imas_user_prefs WHERE userid=:id");
		$stm->execute(array(':id'=>$userid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$sessiondata['userprefs'][$row[0]] = $row[1];
		}
		if (isset($sessiondata['userprefs']['tzname'])) {
			$_POST['tzname'] = $sessiondata['userprefs']['tzname'];
		}
		foreach($prefdefaults as $key=>$def) {
			if (isset($sessiondata['userprefs'][$key])) {
				//keep it
			} else if (isset($CFG['UP'][$key])) {
				$sessiondata['userprefs'][$key] = $CFG['UP'][$key];
			} else {
				$sessiondata['userprefs'][$key] = $prefdefaults[$key];
			}
		}
		foreach(array('graphdisp','mathdisp','useed') as $key) {
			if (isset($sessiondata['userprefs'][$key])) {
				$sessiondata[$key] = $sessiondata['userprefs'][$key];
			}
		}
		if ($writetosession) {
			$enc = base64_encode(serialize($sessiondata));
			$now = time();
			if (isset($_POST['tzname'])) {
				$stm = $DBH->prepare("UPDATE imas_sessions SET sessiondata=:sessiondata,time=:time,tzname=:tzname WHERE sessionid=:sessionid");
				$stm->execute(array(':sessiondata'=>$enc, ':time'=>$now, ':tzname'=>$_POST['tzname'], ':sessionid'=>$sessionid));
			} else {
				$stm = $DBH->prepare("UPDATE imas_sessions SET sessiondata=:sessiondata,time=:time WHERE sessionid=:sessionid");
				$stm->execute(array(':sessiondata'=>$enc, ':time'=>$now, ':sessionid'=>$sessionid));
			}
		}
	 }
}

?>
