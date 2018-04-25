<?php

require("../init.php");

if ($myrights<100 && ($myspecialrights&64)!=64) {exit;}

$newStatus = Sanitize::onlyInt($_POST['newstatus']);
$instId = Sanitize::onlyInt($_POST['userid']);
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
	
	if ($newStatus==10) { //deny
		$stm = $DBH->prepare("UPDATE imas_users SET rights=10 WHERE id=:id");
		$stm->execute(array(':id'=>$instId));
		if (isset($CFG['GEN']['enrollonnewinstructor'])) {
			require("../includes/unenroll.php");
			foreach ($CFG['GEN']['enrollonnewinstructor'] as $rcid) {
				unenrollstu($rcid, array(intval($instId)));
			}
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
				$stm = $DBH->prepare("INSERT INTO imas_groups (name) VALUES (:name)");
				$stm->execute(array(':name'=>$newGroupName));
				$group = $DBH->lastInsertId();
			}
		} else {
			$group = 0;
		}
		
		$stm = $DBH->prepare("UPDATE imas_users SET rights=40,groupid=:groupid WHERE id=:id");
		$stm->execute(array(':groupid'=>$group, ':id'=>$instId));
		
		$stm = $DBH->prepare("SELECT FirstName,SID,email FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$instId));
		$row = $stm->fetch(PDO::FETCH_NUM);
		
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= "From: $accountapproval\r\n";
		$message = '<style type="text/css">p {margin:0 0 1em 0} </style><p>Hi '.Sanitize::encodeStringForDisplay($row[0]).'</p>';
		$message .= '<p>Welcome to '.$installname.'.  Your account has been activated, and you\'re all set to log in as an instructor using the username <b>'.Sanitize::encodeStringForDisplay($row[1]).'</b> and the password you provided.</p>';

		if (isset($CFG['GEN']['useSESmail'])) {
			SESmail($row[2], $accountapproval, $installname . ' Account Approval', $message);
		} else {
			mail($row[2],$installname . ' Account Approval',$message,$headers);
		}
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
			$userdata['search'] = '<a target="checkver" href="https://www.google.com/search?q='.Sanitize::encodeUrlParam($row['FirstName'].' '.$row['LastName'].' '.$userdata['school']).'">Search for Name/School</a>';
		}
		$out[$row['status']][] = $userdata;
	}
	return $out;
}

function getGroups() {
	global $DBH;
	$query = "SELECT s.groupid, ig.name, MAX(s.domain)
	  FROM (SELECT groupid, SUBSTRING_INDEX(email, '@', -1) AS domain, COUNT(*) AS domainCount
		  FROM imas_users WHERE rights>10 AND groupid>0
		  GROUP BY groupid, domain
	       ) AS s
	  JOIN (SELECT s.groupid, MAX(s.domainCount) AS MaxdomainCount
		  FROM (SELECT groupid, SUBSTRING_INDEX(email, '@', -1) AS domain, COUNT(*) AS domainCount
			FROM imas_users WHERE rights>10 AND groupid>0
			GROUP BY groupid, domain
		  ) AS s
		 GROUP BY s.groupid
	       ) AS m
	    ON s.groupid = m.groupid AND s.domainCount = m.MaxdomainCount
	  JOIN imas_groups AS ig ON s.groupid=ig.id GROUP BY s.groupid ORDER BY ig.name";
	$stm = $DBH->query($query);
	$out = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (preg_match('/(gmail|yahoo|hotmail|me\.com)/', $row['domain'])) {
			$row['domain'] = '';
		}
		$row['name'] = preg_replace('/\s+/', ' ', trim($row['name']));
		$out[] = array('id'=>$row['groupid'], 'name'=>$row['name'], 'domain'=>strtolower($row['domain']));
	}
	return $out;
}

//add fields based on your new instructor request form
//and then add the "search" entry
$reqFields = array(
	'school' => 'School',
	'phone' => 'Phone',
	'search' => 'Search'
);


$placeinhead .= '<script src="https://cdn.jsdelivr.net/npm/vue@2.5.6/dist/vue.min.js"></script>';
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/fuse.min.js\"></script>";
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
echo '<div class="pagetitle"><h2>'.$pagetitle.'</h2></div>';

?>

<div id="app" v-cloak>
  <div style="float:right" class="noticetext">{{statusMsg}}</div>
  <div v-if="toApprove.length==0">No requests to process</div>
  <div v-for="(users,status) in toApprove" v-if="users.length>0">
    <h4>{{ statusTitle[status] }}</h4>
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
      	  <li>Group: <select v-model="group">
      	  	<optgroup v-if="suggestedGroups.length>0" label="Suggested Groups">
      	  		<option v-for="group in suggestedGroups" :value="group.id">{{group.name}}</option>
      	  	</optgroup>
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
      	  </li>
      	</ul>
      </li>
    </ul>
  </div>
  
</div>

<script type="text/javascript">
var groups = <?php echo json_encode(getGroups(), JSON_HEX_TAG); ?>;
function normalizeGroupName(grpname) {
	grpname = grpname.toLowerCase();
	grpname = grpname.replace(/\b(sd|cc|su|of|hs|hsd|usd|isd|school|unified|public|county|district|college|community|university|univ|state|\.edu|www\.|a|the)\b/g, "");
	grpname = grpname.replace(/\bmt(\.|\b)/, "mount");
	grpname = grpname.replace(/\bst(\.|\b)/, "saint");
	return grpname;
}
var fuseoptions = {
  shouldSort: true,
  tokenize: false,
  includeScore: true,
  threshold: 0.3,
  location: 0,
  distance: 100,
  maxPatternLength: 32,
  minMatchCharLength: 1,
  keys: ["normname"]
};
var normgroups = [];
for (i in groups) {
	normgroups.push({"id": groups[i].id, "name": groups[i].name, "normname":normalizeGroupName(groups[i].name)});
}
var fuse = new Fuse(normgroups, fuseoptions);

var app = new Vue({
	el: '#app',
	data: {
		groups: groups,
		toApprove: <?php echo json_encode(getReqData(), JSON_HEX_TAG); ?>,
		fieldTitles: <?php echo json_encode($reqFields, JSON_HEX_TAG);?>,
		activeUser: -1,
		activeUserStatus: -1,
		activeUserIndex: -1,
		statusTitle: {
			0: 'New Account Request',
			1: 'Needs Investigation',
			2: 'Waiting for Confirmation',
			3: 'Probably should be denied'
		},
		statusMsg: "",
		group: 0,
		newgroup: ""
	}, 
	computed: {
		suggestedGroups: function() {
			if (this.activeUser==-1) {
				return [];
			}
			var out = []; var matchedIDs = []; var i;
			var user = this.toApprove[this.activeUserStatus][this.activeUserIndex];
			if (user.email.indexOf("@")>-1) {
				var userEmailDomain = user.email.substr(user.email.indexOf("@")+1).toLowerCase();
				for (i in groups) {
					if (userEmailDomain == groups[i].domain) {
						out.push({"id": groups[i].id, "name": groups[i].name});
						matchedIDs.push(groups[i].id);
					}
				}
			}
			if (user.school && user.school != "") {
				var userGroup = normalizeGroupName(user.school);
				var results = fuse.search(userGroup);
				for (i in results) {
					if (results[i].score>.8) {break;}
					if (matchedIDs.indexOf(results[i].item.id)!=-1) { continue; }
					out.push({"id":results[i].item.id, "name": results[i].item.name});
					if (out.length>12) { break;}
				}
			}
			return out;
		},
		suggestedGroupIds: function() {
			var ids = [];
			for (i in this.suggestedGroups) {
				ids.push(this.suggestedGroups[i].id);
			}
			return ids;
		}
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
					if (this.suggestedGroups.length>0) {
						this.group = this.suggestedGroups[0].id;
					} else {
						this.group = 0;
					}
				});
			}
		},
		chgStatus: function(status, userindex, newstatus) {
			if (newstatus==11 && !this.checkgroupname()) {
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
			var proposedgroup = this.newgroup.replace(/^\s+/,"").replace(/\s+$/,"").replace(/\s+/g," ").toLowerCase();
			for (i in groups) {
				if (groups[i].name.toLowerCase()==proposedgroup) {
					alert("That group name already exists!");
					this.group = groups[i].id;
					return false;
				}
			}
			return true;
		}
	}
});

</script>

<?php
require("../footer.php");

