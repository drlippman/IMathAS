<?php

require("../init.php");
require_once('../includes/filehandler.php');

if ($myrights<100 && ($myspecialrights&64)!=64) {exit;}

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['admin/approvepending'])) {
	require($CFG['hooks']['admin/approvepending']);
}

$defGrouptype = isset($CFG['GEN']['defGroupType'])?$CFG['GEN']['defGroupType']:0;

//handle ajax postback
if (!empty($_POST['newstatus'])) {
    $newStatus = Sanitize::onlyInt($_POST['newstatus']);
    $instId = Sanitize::onlyInt($_POST['userid']);
	$stm = $DBH->prepare("SELECT status,reqdata FROM imas_instr_acct_reqs WHERE userid=?");
    $stm->execute(array($instId));
    list($oldstatus, $reqdata) = $stm->fetch(PDO::FETCH_NUM);
	$reqdata = json_decode($reqdata, true);

	if (!isset($reqdata['actions'])) {
		$reqdata['actions'] = array();
	}
	$reqdata['actions'][] = array(
		'by'=>$userid,
		'on'=>time(),
		'status'=>$newStatus);

	$stm = $DBH->prepare("UPDATE imas_instr_acct_reqs SET status=?,reqdata=? WHERE userid=?");
	$stm->execute(array($newStatus, json_encode($reqdata), $instId));

	if ($newStatus==4) { // request more info
		$stm = $DBH->prepare("SELECT FirstName,LastName,SID,email FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$instId));
		$row = $stm->fetch(PDO::FETCH_ASSOC);

		//call hook, if defined
		if (function_exists('getMoreInfoMessage')) {
			$message = getMoreInfoMessage($row['FirstName'], $row['LastName'], $row['SID'], $group);
		} else {
			$message = '<style type="text/css">p {margin:0 0 1em 0} </style><p>Hi '.Sanitize::encodeStringForDisplay($row['FirstName']).'</p>';
			$message .= '<p>You recently requested an instructor account on '.$installname.' with the username <b>'.Sanitize::encodeStringForDisplay($row['SID']).'</b>. ';
			$message .= 'Unfortunately, the information you provided was not sufficient for us to verify your instructor status. ';
			$message .= 'If you believe you should have an instructor account, ';
			$message .= 'you are welcome to reply to this email with additional verification information.</p>';
		}

		require_once("../includes/email.php");
		send_email(Sanitize::emailAddress($row['email']), !empty($accountapproval)?$accountapproval:$sendfrom,
			$installname._(' Account Status'), $message,
			!empty($CFG['email']['new_acct_replyto'])?$CFG['email']['new_acct_replyto']:array(),
			!empty($CFG['email']['new_acct_bcclist'])?$CFG['email']['new_acct_bcclist']:array(), 10);

	} else if ($newStatus==10) { //deny
		$stm = $DBH->prepare("UPDATE imas_users SET rights=10 WHERE id=:id");
		$stm->execute(array(':id'=>$instId));
		if (isset($CFG['GEN']['enrollonnewinstructor'])) {
			require("../includes/unenroll.php");
			foreach ($CFG['GEN']['enrollonnewinstructor'] as $rcid) {
				unenrollstu($rcid, array(intval($instId)));
			}
		}
        if ($oldstatus != 4) {
            $stm = $DBH->prepare("SELECT FirstName,LastName,SID,email FROM imas_users WHERE id=:id");
            $stm->execute(array(':id'=>$instId));
            $row = $stm->fetch(PDO::FETCH_ASSOC);

            //call hook, if defined
            if (function_exists('getDenyMessage')) {
                $message = getDenyMessage($row['FirstName'], $row['LastName'], $row['SID'], $group);
            } else {
                $message = '<style type="text/css">p {margin:0 0 1em 0} </style><p>Hi '.Sanitize::encodeStringForDisplay($row['FirstName']).'</p>';
                $message .= '<p>You recently requested an instructor account on '.$installname.' with the username <b>'.Sanitize::encodeStringForDisplay($row['SID']).'</b>. ';
                $message .= 'Unfortunately, the information you provided was not sufficient for us to verify your instructor status, ';
                $message .= 'so your account has been converted to a student account. If you believe you should have an instructor account, ';
                $message .= 'you are welcome to reply to this email with additional verification information.</p>';
            }

            //call hook, if defined
            if (function_exists('getDenyBcc')) {
                $CFG['email']['new_acct_bcclist'] = getDenyBcc();
            }

            require_once("../includes/email.php");
            send_email(Sanitize::emailAddress($row['email']), !empty($accountapproval)?$accountapproval:$sendfrom,
                $installname._(' Account Status'), $message,
                !empty($CFG['email']['new_acct_replyto'])?$CFG['email']['new_acct_replyto']:array(),
                !empty($CFG['email']['new_acct_bcclist'])?$CFG['email']['new_acct_bcclist']:array(), 10);
        }
	} else if ($newStatus==11) { //approve
		if ($_POST['group']>-1) {
			$group = Sanitize::onlyInt($_POST['group']);
		} else if (trim($_POST['newgroup'])!='') {
			$newGroupName = Sanitize::stripHtmlTags(trim($_POST['newgroup']));
			$stm = $DBH->prepare("SELECT id FROM imas_groups WHERE name REGEXP ?");
			$stm->execute(array('^[[:space:]]*'.str_replace('.','[.]',preg_replace('/\s+/', '[[:space:]]+', $newGroupName)).'[[:space:]]*$'));
			$group = $stm->fetchColumn(0);
			if ($group === false) {
				$stm = $DBH->prepare("INSERT INTO imas_groups (name,grouptype) VALUES (:name,:grouptype)");
				$stm->execute(array(':name'=>$newGroupName, ':grouptype'=>$defGrouptype));
				$group = $DBH->lastInsertId();
			}
		} else {
			$group = 0;
        }
        
        if ($group > 0 && !empty($reqdata['ipeds']) && strpos($reqdata['ipeds'],'-')!==false) {
            list($ipedtype, $ipedid) = explode('-', $reqdata['ipeds']);
            $stm = $DBH->prepare("INSERT IGNORE INTO imas_ipeds_group (type,ipedsid,groupid) VALUES (?,?,?)");
            $stm->execute(array($ipedtype, $ipedid, $group));
        }

		$stm = $DBH->prepare("UPDATE imas_users SET rights=40,groupid=:groupid WHERE id=:id");
		$stm->execute(array(':groupid'=>$group, ':id'=>$instId));

		$stm = $DBH->prepare("SELECT FirstName,LastName,SID,email FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$instId));
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        
        // enroll in courses, if not already
        if (isset($CFG['GEN']['enrollonnewinstructor']) || isset($CFG['GEN']['enrolloninstructorapproval'])) {
            $allInstrEnroll = array_unique(array_merge($CFG['GEN']['enrollonnewinstructor'] ?? [], $CFG['GEN']['enrolloninstructorapproval'] ?? [])); 
            $stm = $DBH->prepare("SELECT courseid FROM imas_students WHERE userid=?");
            $stm->execute([$instId]);
            $existingEnroll = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
            $toEnroll = array_diff($allInstrEnroll, $existingEnroll);
            if (count($toEnroll) > 0) {
                $valbits = array();
                $valvals = array();
                foreach ($toEnroll as $ncid) {
                    $valbits[] = "(?,?)";
                    array_push($valvals, $instId, $ncid);
                }
                $stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid) VALUES ".implode(',',$valbits));
                $stm->execute($valvals);
            }
        }

		//call hook, if defined
		if (function_exists('getApproveMessage')) {
			$message = getApproveMessage($row['FirstName'], $row['LastName'], $row['SID'], $group);
		} else {
			$message = '<style type="text/css">p {margin:0 0 1em 0} </style><p>Hi '.Sanitize::encodeStringForDisplay($row['FirstName']).'</p>';
			$message .= '<p>'.sprintf(_('Welcome to %s.  Your account has been activated, and you\'re all set to log in as an instructor using the username %s and the password you provided.'),$installname,'<b>'.Sanitize::encodeStringForDisplay($row['SID']).'</b>').'</p>';
		}

		//call hook, if defined
		if (function_exists('getApproveBcc')) {
			$CFG['email']['new_acct_bcclist'] = getApproveBcc();
		}

		require_once("../includes/email.php");
		send_email($row['email'], !empty($accountapproval)?$accountapproval:$sendfrom,
			$installname._(' Account Approval'), $message,
			!empty($CFG['email']['new_acct_replyto'])?$CFG['email']['new_acct_replyto']:array(),
			!empty($CFG['email']['new_acct_bcclist'])?$CFG['email']['new_acct_bcclist']:array(), 10);

	}
	echo "OK";
	exit;
}

$countries = ['AF'=>'Afghanistan', 'AL'=>'Albania', 'DZ'=>'Algeria', 'AD'=>'Andorra', 'AO'=>'Angola', 'AI'=>'Anguilla', 'AQ'=>'Antarctica', 'AG'=>'Antigua and Barbuda', 'AR'=>'Argentina', 'AM'=>'Armenia', 'AW'=>'Aruba', 'AU'=>'Australia', 'AT'=>'Austria', 'AZ'=>'Azerbaijan', 'BS'=>'Bahamas (the)', 'BH'=>'Bahrain', 'BD'=>'Bangladesh', 'BB'=>'Barbados', 'BY'=>'Belarus', 'BE'=>'Belgium', 'BZ'=>'Belize', 'BJ'=>'Benin', 'BM'=>'Bermuda', 'BT'=>'Bhutan', 'BO'=>'Bolivia (Plurinational State of)', 'BQ'=>'Bonaire, Sint Eustatius and Saba', 'BA'=>'Bosnia and Herzegovina', 'BW'=>'Botswana', 'BV'=>'Bouvet Island', 'BR'=>'Brazil', 'IO'=>'British Indian Ocean Territory (the)', 'BN'=>'Brunei Darussalam', 'BG'=>'Bulgaria', 'BF'=>'Burkina Faso', 'BI'=>'Burundi', 'CV'=>'Cabo Verde', 'KH'=>'Cambodia', 'CM'=>'Cameroon', 'CA'=>'Canada', 'KY'=>'Cayman Islands (the)', 'CF'=>'Central African Republic (the)', 'TD'=>'Chad', 'CL'=>'Chile', 'CN'=>'China', 'CX'=>'Christmas Island', 'CC'=>'Cocos (Keeling) Islands (the)', 'CO'=>'Colombia', 'KM'=>'Comoros (the)', 'CD'=>'Congo (the Democratic Republic of the)', 'CG'=>'Congo (the)', 'CK'=>'Cook Islands (the)', 'CR'=>'Costa Rica', 'HR'=>'Croatia', 'CU'=>'Cuba', 'CW'=>'Curaçao', 'CY'=>'Cyprus', 'CZ'=>'Czechia', 'CI'=>'Côte d\'Ivoire', 'DK'=>'Denmark', 'DJ'=>'Djibouti', 'DM'=>'Dominica', 'DO'=>'Dominican Republic (the)', 'EC'=>'Ecuador', 'EG'=>'Egypt', 'SV'=>'El Salvador', 'GQ'=>'Equatorial Guinea', 'ER'=>'Eritrea', 'EE'=>'Estonia', 'SZ'=>'Eswatini', 'ET'=>'Ethiopia', 'FK'=>'Falkland Islands (the) [Malvinas]', 'FO'=>'Faroe Islands (the)', 'FJ'=>'Fiji', 'FI'=>'Finland', 'FR'=>'France', 'GF'=>'French Guiana', 'PF'=>'French Polynesia', 'TF'=>'French Southern Territories (the)', 'GA'=>'Gabon', 'GM'=>'Gambia (the)', 'GE'=>'Georgia', 'DE'=>'Germany', 'GH'=>'Ghana', 'GI'=>'Gibraltar', 'GR'=>'Greece', 'GL'=>'Greenland', 'GD'=>'Grenada', 'GP'=>'Guadeloupe', 'GT'=>'Guatemala', 'GG'=>'Guernsey', 'GN'=>'Guinea', 'GW'=>'Guinea-Bissau', 'GY'=>'Guyana', 'HT'=>'Haiti', 'HM'=>'Heard Island and McDonald Islands', 'VA'=>'Holy See (the)', 'HN'=>'Honduras', 'HK'=>'Hong Kong', 'HU'=>'Hungary', 'IS'=>'Iceland', 'IN'=>'India', 'ID'=>'Indonesia', 'IR'=>'Iran (Islamic Republic of)', 'IQ'=>'Iraq', 'IE'=>'Ireland', 'IM'=>'Isle of Man', 'IL'=>'Israel', 'IT'=>'Italy', 'JM'=>'Jamaica', 'JP'=>'Japan', 'JE'=>'Jersey', 'JO'=>'Jordan', 'KZ'=>'Kazakhstan', 'KE'=>'Kenya', 'KI'=>'Kiribati', 'KP'=>'Korea (the Democratic People\'s Republic of)', 'KR'=>'Korea (the Republic of)', 'KW'=>'Kuwait', 'KG'=>'Kyrgyzstan', 'LA'=>'Lao People\'s Democratic Republic (the)', 'LV'=>'Latvia', 'LB'=>'Lebanon', 'LS'=>'Lesotho', 'LR'=>'Liberia', 'LY'=>'Libya', 'LI'=>'Liechtenstein', 'LT'=>'Lithuania', 'LU'=>'Luxembourg', 'MO'=>'Macao', 'MG'=>'Madagascar', 'MW'=>'Malawi', 'MY'=>'Malaysia', 'MV'=>'Maldives', 'ML'=>'Mali', 'MT'=>'Malta', 'MQ'=>'Martinique', 'MR'=>'Mauritania', 'MU'=>'Mauritius', 'YT'=>'Mayotte', 'MX'=>'Mexico', 'MD'=>'Moldova (the Republic of)', 'MC'=>'Monaco', 'MN'=>'Mongolia', 'ME'=>'Montenegro', 'MS'=>'Montserrat', 'MA'=>'Morocco', 'MZ'=>'Mozambique', 'MM'=>'Myanmar', 'NA'=>'Namibia', 'NR'=>'Nauru', 'NP'=>'Nepal', 'NL'=>'Netherlands (the)', 'NC'=>'New Caledonia', 'NZ'=>'New Zealand', 'NI'=>'Nicaragua', 'NE'=>'Niger (the)', 'NG'=>'Nigeria', 'NU'=>'Niue', 'NF'=>'Norfolk Island', 'NO'=>'Norway', 'OM'=>'Oman', 'PK'=>'Pakistan', 'PS'=>'Palestine, State of', 'PA'=>'Panama', 'PG'=>'Papua New Guinea', 'PY'=>'Paraguay', 'PE'=>'Peru', 'PH'=>'Philippines (the)', 'PN'=>'Pitcairn', 'PL'=>'Poland', 'PT'=>'Portugal', 'QA'=>'Qatar', 'MK'=>'Republic of North Macedonia', 'RO'=>'Romania', 'RU'=>'Russian Federation (the)', 'RW'=>'Rwanda', 'RE'=>'Réunion', 'BL'=>'Saint Barthélemy', 'SH'=>'Saint Helena, Ascension and Tristan da Cunha', 'KN'=>'Saint Kitts and Nevis', 'LC'=>'Saint Lucia', 'MF'=>'Saint Martin (French part)', 'PM'=>'Saint Pierre and Miquelon', 'VC'=>'Saint Vincent and the Grenadines', 'WS'=>'Samoa', 'SM'=>'San Marino', 'ST'=>'Sao Tome and Principe', 'SA'=>'Saudi Arabia', 'SN'=>'Senegal', 'RS'=>'Serbia', 'SC'=>'Seychelles', 'SL'=>'Sierra Leone', 'SG'=>'Singapore', 'SX'=>'Sint Maarten (Dutch part)', 'SK'=>'Slovakia', 'SI'=>'Slovenia', 'SB'=>'Solomon Islands', 'SO'=>'Somalia', 'ZA'=>'South Africa', 'GS'=>'South Georgia and the South Sandwich Islands', 'SS'=>'South Sudan', 'ES'=>'Spain', 'LK'=>'Sri Lanka', 'SD'=>'Sudan (the)', 'SR'=>'Suriname', 'SJ'=>'Svalbard and Jan Mayen', 'SE'=>'Sweden', 'CH'=>'Switzerland', 'SY'=>'Syrian Arab Republic', 'TW'=>'Taiwan', 'TJ'=>'Tajikistan', 'TZ'=>'Tanzania, United Republic of', 'TH'=>'Thailand', 'TL'=>'Timor-Leste', 'TG'=>'Togo', 'TK'=>'Tokelau', 'TO'=>'Tonga', 'TT'=>'Trinidad and Tobago', 'TN'=>'Tunisia', 'TR'=>'Turkey', 'TM'=>'Turkmenistan', 'TC'=>'Turks and Caicos Islands (the)', 'TV'=>'Tuvalu', 'UG'=>'Uganda', 'UA'=>'Ukraine', 'AE'=>'United Arab Emirates (the)', 'GB'=>'United Kingdom of Great Britain and Northern Ireland (the)', 'UM'=>'United States Minor Outlying Islands (the)', 'UY'=>'Uruguay', 'UZ'=>'Uzbekistan', 'VU'=>'Vanuatu', 'VE'=>'Venezuela (Bolivarian Republic of)', 'VN'=>'Viet Nam', 'VG'=>'Virgin Islands (British)', 'WF'=>'Wallis and Futuna', 'EH'=>'Western Sahara', 'YE'=>'Yemen', 'ZM'=>'Zambia', 'ZW'=>'Zimbabwe', 'AX'=>'Åland Islands'];

function getReqData() {
	global $DBH, $countries;

	$query = 'SELECT ir.status,ir.reqdata,ir.reqdate,iu.id,iu.email,iu.LastName,iu.FirstName,iu.SID ';
	$query .= 'FROM imas_instr_acct_reqs AS ir JOIN imas_users AS iu ';
	$query .= 'ON ir.userid=iu.id WHERE ir.status<10 ORDER BY ir.status,ir.reqdate';
	$stm = $DBH->query($query);

	$out = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($out[$row['status']])) {
			$out[$row['status']] = array();
		}
        $userdata = json_decode($row['reqdata'],true);
        if (isset($userdata['ipeds'])) {
            // handle requests with ipeds info 
            if (strpos($userdata['ipeds'],'-') !== false) {
                list($ipedstype,$ipedsval) = explode('-', $userdata['ipeds']);
                $query = "SELECT DISTINCT IF(ip.type='A',ip.agency,ip.school) AS schoolname,ip.country,ig.id,ig.name 
                    FROM imas_ipeds AS ip 
                    LEFT JOIN imas_ipeds_group AS ipg ON ip.type=ipg.type AND ip.ipedsid=ipg.ipedsid 
                    LEFT JOIN imas_groups AS ig ON ipg.groupid=ig.id 
                    WHERE ip.type=? and ip.ipedsid=?";
                $stm2 = $DBH->prepare($query);
                $stm2->execute(array($ipedstype, $ipedsval));
                $ipedsgroups = array();
                while ($r2 = $stm2->fetch(PDO::FETCH_ASSOC)) {
                    $ipedname = $r2['schoolname'];
                    if ($r2['id'] !== null) {
                        $ipedsgroups[] = ['id'=>$r2['id'], 'name'=>$r2['name']];
                    }
                }
                if (count($ipedsgroups)>0) {
                    $userdata['fixedgroups'] = $ipedsgroups;
                } 
                $userdata['school'] = $ipedname;
            } else if ($userdata['ipeds'] == '0') {
                $userdata['school'] = $userdata['otherschool'].' ('.$countries[$userdata['schoolloc']].')';
            }
        }
        $urlformatted = false;
        if (isset($userdata['vertype'])) {
            // these values are further handled and sanitized below.
            if ($userdata['vertype'] == 'url') {
                $userdata['url'] = $userdata['verdata']; 
            } else if ($userdata['vertype'] == 'email') {
                $userdata['url'] = _('Expect an email from: ').$userdata['verdata'];
            } else if ($userdata['vertype'] == 'upload') {
                $url = getprivatefileurl(substr($userdata['verdata'],5));
                $userdata['url'] = "<a href=\"$url\" target=\"_blank\">"._('Verification Image').'</a>';
                $urlformatted = true;
            }
        }
		if (isset($userdata['url'])) {
			if (substr($userdata['url'],0,4)=='http') {
				$userdata['url'] = Sanitize::url($userdata['url']);
				$urldisplay = Sanitize::encodeStringForDisplay($userdata['url']);
				$urlstring = "Verification URL: <a href='{$userdata['url']}' target='_blank'>{$urldisplay}</a>";
			} else if ($urlformatted) {
				$urlstring = 'Verification: '.$userdata['url'];
            } else {
				$urlstring = 'Verification: '.Sanitize::encodeStringForDisplay($userdata['url']);
			}
			$userdata['url'] = $urlstring;
		}
		$userdata['reqdate'] = tzdate("D n/j/y, g:i a", $row['reqdate']);
		$userdata['name'] = $row['LastName'].', '.$row['FirstName'];
        $userdata['email'] = $row['email'];
        $userdata['username'] = $row['SID'];
		$userdata['id'] = $row['id'];
		if (isset($userdata['school'])) {
			$userdata['search'] = '<a target="checkver" href="https://www.google.com/search?q='.Sanitize::encodeUrlParam($row['FirstName'].' '.$row['LastName'].' '.$userdata['school']).'">Search Google for Name/School</a>';
		}
		$out[$row['status']][] = $userdata;
	}
	array_walk_recursive($out, function(&$item) {
		$item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
	});
	return $out;
}

//add fields based on your new instructor request form
//and then add the "search" entry
if (empty($reqFields)) {
    if (!empty($CFG['use_ipeds'])) {
        $reqFields = array(
            'school' => 'School',
            'url' => 'Verification',
            'search' => 'Search'
        );
    } else {
        $reqFields = array(
            'school' => 'School',
            'phone' => 'Phone',
            'search' => 'Search'
        );
    }
}

$placeinhead = '<script src="https://cdn.jsdelivr.net/npm/vue@2.5.6/dist/vue.min.js"></script>';
//$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/testgroups.js\"></script>";
$placeinhead .= '<style type="text/css">
 [v-cloak] { display: none;}
 .userdata, .userlist {
 	list-style-type: none;
 }
 .userlist {
 	padding-left: 10px;
 }
 .userdata {
 	padding-left: 20px;
 }
 .userlist > li {
 	display:block;
 	border: 1px solid #ccc;
 	margin-bottom: 4px;
 }
 .userlist > li > span {
 	display: block;
 	padding: 5px;
 	background-color: #eee;
 	cursor: pointer;
 }
 .userdata li {
 	margin: 5px 0px;
 }

 </style>';

$pagetitle = _('Approve Instructor Accounts');
$curBreadcrumb = $breadcrumbbase;
if (!isset($_GET['from'])) {
	$curBreadcrumb .= "<a href=\"../admin/admin2.php\">Admin</a> ";
	$curBreadcrumb .= "&gt; <a href=\"../util/utils.php\">Utilities</a> &gt; ";
}

require("../header.php");
echo '<div class="breadcrumb">'. $curBreadcrumb . $pagetitle.'</div>';
echo '<div class="pagetitle"><h1>'.$pagetitle.'</h1></div>';

?>

<div id="app" v-cloak>
  <div style="float:right" class="noticetext">{{statusMsg}}</div>
  <div v-if="toApprove.length==0">No requests to process</div>
  <div v-for="(users,status) in toApprove" v-if="users.length>0">
    <h3>{{ statusTitle[status] }}</h3>
    <ul class="userlist">
      <li v-for="(user,userindex) in users">
        <span @click="toggleActiveUser(user.id, status, userindex)">
          <span v-if="activeUser==user.id">[-]</span>
          <span v-else>[+]</span>
          <span class="pii-full-name">{{user.name}}</span> ({{user.school}})
        </span>
      	<ul class="userdata" v-if="activeUser==user.id">
      	  <li>Request Made: {{user.reqdate}}</li>
          <li>Username: <span class="pii-username">{{user.username}}</span></li>
          <li>Email: <span class="pii-email">{{user.email}}</span></li>
      	  <li v-for="(title,fieldindex) in fieldTitles">
      	    <span v-if="fieldindex=='url' || fieldindex=='search'" v-html="user[fieldindex]"></span>
      	    <span v-else>{{title}}: {{user[fieldindex]}}</span>
      	  </li>
      	  <li>
      	    <span v-if="status!=1">
      	    	<button @click="chgStatus(status, userindex, 1)">Needs Investigation</button>
      	    </span>
      	    <span v-if="status!=2">
      	    	<button @click="chgStatus(status, userindex, 2)">Waiting for Confirmation</button>
      	    </span>
      	    <span v-if="status!=3">
      	    	<button @click="chgStatus(status, userindex, 3)">Probably should be Denied</button>
      	    </span>
      	  </li>
          <li v-if="!fixedgroups">Search for group: <input v-model="grpsearch" size=30 @keyup.enter="searchGroups">
            <button type=button @click="searchGroups">Search</button>
          </li>
      	  <li v-show="groups !== null">Group: <select v-model="group">
      	  	<optgroup label="groups">
      	  		<option value="-1" v-if="!fixedgroups">New group</option>
      	  		<option value=0 v-if="!fixedgroups">Default</option>
      	  		<option v-for="group in groups" :value="group.id">{{group.name}}</option>
      	  	</optgroup>
      	  	</select>
      	  	<span v-if="group==-1">New group name: <input size=30 v-model="newgroup" @blur="checkgroupname"></span>
      	  </li>
      	  <li>
      	    <button @click="chgStatus(status, userindex, 11)">Approve Request</button>
      	    <button @click="chgStatus(status, userindex, 10)">Deny Request</button>
						<span v-if="status!=4">
      	    	<button @click="chgStatus(status, userindex, 4)">Request More Info</button>
      	    </span>
      	    <br/>
      	    With an Approve, Deny, or Request More Info, an email is automatically sent to the requester.
      	  </li>
      	</ul>
      </li>
    </ul>
  </div>

</div>

<script type="text/javascript">

var app = new Vue({
	el: '#app',
	data: {
		groups: null,
		grpsearch: '',
		toApprove: <?php echo json_encode(getReqData(), JSON_HEX_TAG|JSON_INVALID_UTF8_IGNORE); ?>,
		fieldTitles: <?php echo json_encode($reqFields, JSON_HEX_TAG|JSON_INVALID_UTF8_IGNORE);?>,
		activeUser: -1,
		activeUserStatus: -1,
		activeUserIndex: -1,
		statusTitle: {
			0: 'New Account Request',
			1: 'Needs Investigation',
			2: 'Waiting for Confirmation',
			3: 'Probably should be denied',
			4: 'Waiting for more info'
		},
		statusMsg: "",
        group: 0,
        fixedgroups: false,
		newgroup: ""
	},
	computed: {

	},
	methods: {
		toggleActiveUser: function(userid, status, userindex) {
			if (userid==this.activeUser) {
				this.activeUser = -1;
				this.activeUserStatus = -1;
				this.activeUserIndex = -1;
			} else {
				this.activeUser = userid;
				this.activeUserStatus = status;
				this.activeUserIndex = userindex;
				this.$nextTick(function() {
                    if (this.toApprove[status][userindex].hasOwnProperty('fixedgroups')) {
                        this.groups = this.toApprove[status][userindex].fixedgroups;
                        this.group = this.toApprove[status][userindex].fixedgroups[0]['id'];
                        this.fixedgroups = true;
                    } else {
                        this.groups = null;
                        this.group = 0;
                        this.fixedgroups = false;
                        this.grpsearch = this.toApprove[status][userindex].school;
                        this.newgroup = this.toApprove[status][userindex].school;
                    }
				});
			}
		},
		searchGroups: function () {
			if (this.grpsearch.trim() == '') { return ;}
			var self = this;
			$.ajax({
				type: "POST",
				url: "groupsearch.php",
				dataType: "json",
				data: {
					"grpsearch": this.grpsearch
				}
			}).done(function(msg) {
				self.groups = msg;
				self.group = msg[0].id;
			});
		},
		chgStatus: function(status, userindex, newstatus) {
			if (newstatus==11 && this.group===-1 && !this.checkgroupname()) {
				return false;
			}
			this.statusMsg = _("Saving...");
			var self = this;
			$.ajax({
				type: "POST",
				url: "approvepending2.php",
				data: {
					"userid": this.toApprove[status][userindex].id,
					"group": this.group,
					"newgroup": this.newgroup,
					"newstatus": newstatus
				}
			}).done(function(msg) {
			  if (msg=="OK") {
			    self.activeUser = -1;
			    self.group = 0;
			    if (newstatus>9) {
			      self.toApprove[status].splice(userindex, 1);
			    } else {
			      var curuser = self.toApprove[status].splice(userindex, 1);
			      if (newstatus in self.toApprove) {
			      	      self.toApprove[newstatus].push(curuser[0]);
			      } else {
			      	      self.toApprove[newstatus] = curuser;
			      }
			    }
			    self.statusMsg = "";
			  } else {
			    self.statusMsg = msg;
			  }
			}).fail(function(msg) {
			  self.statusMsg = msg;
			});
		},
		clone: function(obj) {
			return JSON.parse(JSON.stringify(obj)); //crude
		},
		checkgroupname: function() {
			var proposedgroup = this.newgroup.replace(/\s+/g," ").trim();
			var self = this;
			$.ajax({
				type: "POST",
				url: "groupsearch.php",
				dataType: "json",
				async: false,
				data: {
					"grpsearch": proposedgroup,
					"exact": true
				}
			}).done(function(msg) {
				if (msg.length === 0) {
					return true;
				} else {
					alert("That group name already exists!");
					self.groups = msg;
					self.group = msg[0].id;
					return false;
				}
			});
			return true;
		}
	}
});

</script>

<?php
require("../footer.php");
