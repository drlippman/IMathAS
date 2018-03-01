<?php 
// Add/remove Teachers
// IMathAS (c) 2018 David Lippman

require("../init.php");

if ($myrights<40) {
	echo "Not authorized to view this page";
	exit;
}
$cid = Sanitize::onlyInt($_GET['id']);

if ($cid==0) {
	echo "Invalid course ID";
	exit;
}

$stm = $DBH->prepare("SELECT ic.name,ic.ownerid,iu.groupid FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ic.id=?");
$stm->execute(array($cid));
list($coursename, $courseownerid, $coursegroupid) = $stm->fetch(PDO::FETCH_NUM);

if (!($myrights==100 || ($myrights>=75 && $coursegroupid==$groupid) || $courseownerid==$userid)) {
	echo "Not authorized to transfer ownership of this course.";
	exit;
}

$from = 'admin2';
if (!empty($_GET['from'])) {
	if ($_GET['from']=='home') {
		$from = 'home';
		$backloc = '/index.php';
	} else if ($_GET['from']=='admin2') {
		$from = 'admin2';
		$backloc = '/admin/admin2.php';
	} else if (substr($_GET['from'],0,2)=='ud') {
		$userdetailsuid = Sanitize::onlyInt(substr($_GET['from'],2));
		$from = 'ud'.$userdetailsuid;
		$backloc = '/admin/userdetails.php?id='.Sanitize::encodeUrlParam($userdetailsuid);
	} else if (substr($_GET['from'],0,2)=='gd') {
		$groupdetailsgid = Sanitize::onlyInt(substr($_GET['from'],2));
		$from = 'gd'.$groupdetailsgid;
		$backloc = '/admin/admin2.php?groupdetails='.Sanitize::encodeUrlParam($groupdetailsgid);
	}
}

//process transfer
if (isset($_POST['newowner']) && intval($_POST['newowner'])>0) {
	$stm = $DBH->prepare("UPDATE imas_courses SET ownerid=:ownerid WHERE id=:id");
	$stm->execute(array(':ownerid'=>$_POST['newowner'], ':id'=>$cid));
	if ($stm->rowCount()>0) {
		$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
		$stm->execute(array(':courseid'=>$cid, ':userid'=>$_POST['newowner']));
		if ($stm->rowCount()==0) {
			$stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES (:userid, :courseid)");
			$stm->execute(array(':userid'=>$_POST['newowner'], ':courseid'=>$cid));
		}
		if (isset($_POST['removeasteacher'])) {
			$stm = $DBH->prepare("DELETE FROM imas_teachers WHERE courseid=:courseid AND userid=:userid");
			$stm->execute(array(':courseid'=>$cid, ':userid'=>$courseownerid));
		}
	}
	header('Location: ' . $GLOBALS['basesiteurl'] . $backloc);
	exit;
}

//process AJAX post-backs
if (isset($_POST['loadgroup'])) {
	$stm = $DBH->prepare("SELECT id,LastName,FirstName FROM imas_users WHERE id<>? AND groupid=? AND rights>11 ORDER BY LastName,FirstName");
	$stm->execute(array($courseownerid, $coursegroupid));
	$out = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if ($row['rights']==76 || $row['rights']==77) {continue;}
		$out[] = array("id"=>$row['id'], "name"=>$row['LastName'].', '.$row['FirstName']);
	}
	echo json_encode($out);
	exit;
} else if (isset($_POST['search'])) {
	require("../includes/userutils.php");
	$possible_teachers = searchForUser($_POST['search'], true, true);
	$out = array();
	foreach ($possible_teachers as $row) {
		if ($row['id']==$courseownerid) { continue; }
		$out[] = array("id"=>$row['id'], "name"=>$row['LastName'].', '.$row['FirstName'].' ('.$row['name'].')');
	}
	echo json_encode($out);
	exit;
}

$placeinhead = '<script src="https://cdn.jsdelivr.net/npm/vue@2.5.6/dist/vue.min.js"></script>';
$placeinhead .= '<style type="text/css">
 [v-cloak] { display: none;}
 .fade-enter-active {
  transition: all 0.2s ease-out;
}
.fade-leave-active {
  transition: all 0.1s ease-out;
}

.fade-enter, .fade-leave-to {
  opacity: 0;
}
.fade-leave-active {
	position: absolute;
	z-index: 0;
}
.fade-move {
	transition: transform .2s;
}
</style>';

$pagetitle = _('Transfer Course Ownership');

require("../header.php");

echo "<div class=breadcrumb>$breadcrumbbase ";
if ($from == 'admin') {
	echo "<a href=\"admin2.php\">Admin</a> &gt; ";
} else if ($from == 'admin2') {
	echo '<a href="admin2.php">'._('Admin').'</a> &gt; ';
} else if (substr($_GET['from'],0,2)=='ud') {
	echo '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$imasroot.$backloc.'">'._('User Details').'</a> &gt; ';
} else if (substr($_GET['from'],0,2)=='gd') {
	echo '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$imasroot.$backloc.'">'._('Group Details').'</a> &gt; ';
}
echo "$pagetitle</div>\n";
echo '<div class="pagetitle"><h2>'.$pagetitle.' - '.Sanitize::encodeStringForDisplay($coursename).'</h2></div>';
?>
<form method="post">
<div id="app" v-cloak>
<p>List your group member or search for a teacher to transfer your course ownership to.<br/>
<input type=checkbox name=removeasteacher checked> Remove me as a teacher after transferring the course.</p>
<p><button type=button @click="loadGroup()">List my group members</button>
	or lookup a teacher: <input v-model="toLookup" size=30>
	<button type=button @click="searchTeacher()" :disabled="toLookup.length==0">Search</button>
	<span v-if="processingSearch" class="noticetext">Looking up teachers... <img src="../img/updating.gif"></span>
</p>
<p>
	<button type=submit :disabled="selectedTeacher == 0">Transfer</button>
	<button type=button @click="leavePage()" class="secondarybtn">Nevermind</button>
</p>
<p v-if="searchResults !== null && searchResults.length==0">No teachers found</p>
<transition-group name="fade" tag="ul" class="nomark" v-if="searchResults !== null && searchResults.length>0">
	<li v-for="teacher in searchResults" :key="teacher.id">
		<input type=radio name=newowner :value="teacher.id" v-model="selectedTeacher"> {{teacher.name}}
	</li>
</transition-group>
</div>
</form>
<script type="text/javascript">
var app = new Vue({
	el: '#app',
	data: {
		processingSearch: false,
		courseOwner: <?php echo Sanitize::onlyInt($courseownerid);?>,
		toLookup: "",
		searchResults: null,
		lastSearchType: '',
		selectedTeacher: 0
	},
	methods: {
		loadGroup: function() {
			this.processingSearch = true;
			this.lastSearchType = 'group';
			var self = this;
			$.ajax({
				dataType: "json",
				type: "POST",
				url: window.location.href,
				data: {loadgroup: 1},
			}).done(function(msg) {
				self.searchResults = msg;
			}).always(function() {
				self.processingSearch = false;
			});
			//todo: add error handling
		},
		searchTeacher: function() {
			if (this.toLookup != '') {
				this.processingSearch = true;
				this.lastSearchType = 'search';
				var self = this;
				$.ajax({
					dataType: "json",
					type: "POST",
					url: window.location.href,
					data: {search: this.toLookup},
				}).done(function(msg) {
					self.searchResults = msg;
				}).always(function() {
					self.processingSearch = false;
				});
				//todo: add error handling
			}
		},
		leavePage: function() {
			window.location.href = '<?php echo $imasroot.$backloc;?>';
		}
	}
});
</script>
<?php
require("../footer.php");
