<?php

require("../init.php");

if ($myrights<100 && ($myspecialrights&64)!=64) {exit;}

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['admin/approvepending'])) {
	require($CFG['hooks']['admin/approvepending']);
}

$newStatus = Sanitize::onlyInt($_POST['newstatus']);
$instId = Sanitize::onlyInt($_POST['userid']);
$defGrouptype = isset($CFG['GEN']['defGroupType'])?$CFG['GEN']['defGroupType']:0;

//handle ajax postback
if (!empty($newStatus)) {
	$stm = $DBH->prepare("SELECT reqdata FROM imas_instr_acct_reqs WHERE userid=?");
	$stm->execute(array($instId));
	$reqdata = json_decode($stm->fetchColumn(0), true);

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

		$stm = $DBH->prepare("UPDATE imas_users SET rights=40,groupid=:groupid WHERE id=:id");
		$stm->execute(array(':groupid'=>$group, ':id'=>$instId));

		$stm = $DBH->prepare("SELECT FirstName,LastName,SID,email FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$instId));
		$row = $stm->fetch(PDO::FETCH_ASSOC);

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

function getReqData() {
	global $DBH;

	$query = 'SELECT ir.status,ir.reqdata,ir.reqdate,iu.id,iu.email,iu.LastName,iu.FirstName ';
	$query .= 'FROM imas_instr_acct_reqs AS ir JOIN imas_users AS iu ';
	$query .= 'ON ir.userid=iu.id WHERE ir.status<10 ORDER BY ir.status,ir.reqdate';
	$stm = $DBH->query($query);

	$out = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($out[$row['status']])) {
			$out[$row['status']] = array();
		}
		$userdata = json_decode($row['reqdata'],true);
		if (isset($userdata['url'])) {
			if (substr($userdata['url'],0,4)=='http') {
				$userdata['url'] = Sanitize::url($userdata['url']);
				$urldisplay = Sanitize::encodeStringForDisplay($userdata['url']);
				$urlstring = "Verification URL: <a href='{$userdata['url']}' target='_blank'>{$urldisplay}</a>";
			} else {
				$urlstring = 'Verification: '.Sanitize::encodeStringForDisplay($userdata['url']);
			}
			$userdata['url'] = $urlstring;
		}
		$userdata['reqdate'] = tzdate("D n/j/y, g:i a", $row['reqdate']);
		$userdata['name'] = $row['LastName'].', '.$row['FirstName'];
		$userdata['email'] = $row['email'];
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
    $reqFields = array(
        'school' => 'School',
        'phone' => 'Phone',
        'search' => 'Search'
    );
}

$placeinhead .= '<script src="https://cdn.jsdelivr.net/npm/vue@2.5.6/dist/vue.min.js"></script>';
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
          {{user.name}} ({{user.school}})
        </span>
      	<ul class="userdata" v-if="activeUser==user.id">
      	  <li>Request Made: {{user.reqdate}}</li>
      	  <li>Email: {{user.email}}</li>
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
					<li>Search for group: <input v-model="grpsearch" size=30 @keyup.enter="searchGroups">
						<button type=button @click="searchGroups">Search</button>
					</li>
      	  <li v-show="groups !== null">Group: <select v-model="group">
      	  	<optgroup label="groups">
      	  		<option value="-1">New group</option>
      	  		<option value=0>Default</option>
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
					this.groups = null;
					this.group = 0;
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
