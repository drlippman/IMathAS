<?php

require("../init.php");

if ($myrights<100 && ($myspecialrights&64)!=64) {exit;}

//handle ajax postback
if (isset($_POST['newstatus'])) {
	$stm = $DBH->prepare("SELECT reqdata FROM imas_instr_acct_reqs WHERE userid=?");
	$stm->execute(array($_POST['userid']));
	$reqdata = json_decode($stm->fetchColumn(0), true);
	
	if (!isset($reqdata['actions'])) {
		$reqdata['actions'] = array();
	}
	$reqdata['actions'][] = array(
		'by'=>$userid,
		'on'=>time(),
		'status'=>$_POST['newstatus']);
	
	$stm = $DBH->prepare("UPDATE imas_instr_acct_reqs SET status=?,reqdata=? WHERE userid=?");
	$stm->execute(array($_POST['newstatus'], json_encode($reqdata), $_POST['userid']));
	
	if ($_POST['newstatus']==10) { //deny
		$stm = $DBH->prepare("UPDATE imas_users SET rights=10 WHERE id=:id");
		$stm->execute(array(':id'=>$_POST['userid']));
		if (isset($CFG['GEN']['enrollonnewinstructor'])) {
			require("../includes/unenroll.php");
			foreach ($CFG['GEN']['enrollonnewinstructor'] as $rcid) {
				unenrollstu($rcid, array(intval($_POST['userid'])));
			}
		}
	} else if ($_POST['newstatus']==11) { //approve
		if ($_POST['group']>-1) {
			$group = intval($_POST['group']);
		} else if (trim($_POST['newgroup'])!='') {
			$stm = $DBH->prepare("INSERT INTO imas_groups (name) VALUES (:name)");
			$stm->execute(array(':name'=>$_POST['newgroup']));
			$group = $DBH->lastInsertId();
		} else {
			$group = 0;
		}
		
		$stm = $DBH->prepare("UPDATE imas_users SET rights=40,groupid=:groupid WHERE id=:id");
		$stm->execute(array(':groupid'=>$group, ':id'=>$_POST['userid']));
		
		$stm = $DBH->prepare("SELECT FirstName,SID,email FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$_POST['userid']));
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
	$query = "SELECT s.groupid, ig.name, s.domain
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
	  JOIN imas_groups AS ig ON s.groupid=ig.id ORDER BY ig.name";
	$stm = $DBH->query($query);
	$out = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (preg_match('/(gmail|yahoo|hotmail)/', $row['domain'])) {
			$row['domain'] = '';
		}
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


$placeinhead .= '<script src="https://cdn.jsdelivr.net/npm/vue@2.5.6/dist/vue.js"></script>';
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
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
      	  		<option v-for="group in groups" v-if="!(group.id in suggestedGroupIds)" :value="group.id">{{group.name}}</option>
      	  	</optgroup>
      	  	</select>
      	  	<span v-if="group==-1">New group name: <input size=30 v-model="newgroup"></span>
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
var app = new Vue({
	el: '#app',
	data: {
		groups: <?php echo json_encode(getGroups()); ?>,
		toApprove: <?php echo json_encode(getReqData()); ?>,
		fieldTitles: <?php echo json_encode($reqFields);?>,
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
		normalizedGroups: function() {
			var out = {}; var grpname;
			for (var i in this.groups) {
				out[this.groups[i].id] = this.normalizeGroupName(this.groups[i].name);
			}
			return out;
		},
		suggestedGroups: function() {
			if (this.activeUser==-1) {
				return [];
			}
			var out = []; var matchedIDs = []; var i;
			var user = this.toApprove[this.activeUserStatus][this.activeUserIndex];
			if (user.email.indexOf("@")>-1) {
				var userEmailDomain = user.email.substr(user.email.indexOf("@")+1).toLowerCase();
				for (i in this.groups) {
					if (userEmailDomain == this.groups[i].domain) {
						out.push({"id": this.groups[i].id, "dist":0, "name": this.groups[i].name});
						matchedIDs.push(this.groups[i].id);
					}
				}
			}
			if (user.school && user.school != "") {
				var userGroup = this.normalizeGroupName(user.school);
				var breakdist = .5
				if (userGroup.length>4) {
					breakdist = Math.min(5, .5*userGroup.length);
				}
				var dist; var grpsuggestions = [];
				for (i in this.groups) {
					if (this.groups[i].id in matchedIDs) {continue;}
					dist = levenshtein(userGroup, this.normalizedGroups[this.groups[i].id]);
					if (dist<breakdist) {
						out.push({"id": this.groups[i].id, "dist":dist, "name": this.groups[i].name});
					}
				}
			}
			out.sort(function(a,b) {
				if (a.dist==b.dist) {
					var nameA = a.name.toLowerCase();
					var nameB = b.name.toLowerCase();
					if (nameA < nameB) {
						return -1;
					} else if (nameA>nameB) {
						return 1;
					} else {
						return 0;
					}
				} else {
					return (a.dist - b.dist);
				}
			});
			return out;
		},
		suggestedGroupIds: function() {
			var ids = [];
			for (i in this.suggestedGroups) {
				ids.push(this.suggestedGroups[i].groupid);
			}
			return ids;
		}
	},
	methods: {
		normalizeGroupName: function(grpname) {
			grpname = grpname.toLowerCase();
			grpname = grpname.replace(/\b(sd|cc|su|of|hs|hsd|usd|isd|school|unified|public|county|district|college|community|university|univ|state|\.edu|www\.)\b/, "");
			grpname = grpname.replace(/\bmt(\.|\b)/, "mount");
			grpname = grpname.replace(/\bst(\.|\b)/, "saint");
			return grpname;
		},
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
		}
	}
});

//from https://stackoverflow.com/questions/18516942/fastest-general-purpose-levenshtein-javascript-implementation
var levenshtein = (function() {
    var row2 = [];
    return function(s1, s2) {
        if (s1 === s2) {
            return 0;
        } else {
            var s1_len = s1.length, s2_len = s2.length;
            if (s1_len && s2_len) {
                var i1 = 0, i2 = 0, a, b, c, c2, row = row2;
                while (i1 < s1_len)
                    row[i1] = ++i1;
                while (i2 < s2_len) {
                    c2 = s2.charCodeAt(i2);
                    a = i2;
                    ++i2;
                    b = i2;
                    for (i1 = 0; i1 < s1_len; ++i1) {
                        c = a + (s1.charCodeAt(i1) === c2 ? 0 : 1);
                        a = row[i1];
                        b = b < a ? (b < c ? b + 1 : c) : (a < c ? a + 1 : c);
                        row[i1] = b;
                    }
                }
                return b;
            } else {
                return s1_len + s2_len;
            }
        }
    };
})();

</script>

<?php
require("../footer.php");

