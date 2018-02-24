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
	echo "Not authorized to to change teachers on this course.";
	exit;
}

//utility function
function getTeachers($cid) {
	global $DBH;
	
	$query = "SELECT iu.id,iu.LastName,iu.FirstName,ig.name FROM imas_users AS iu JOIN imas_groups AS ig ON iu.groupid=ig.id ";
	$query .= "JOIN imas_teachers AS it ON it.userid=iu.id WHERE it.courseid=? ORDER BY LastName, FirstName";
	$stm = $DBH->prepare($query);
	$stm->execute(array($cid));
	$out = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$out[] = array("id"=>$row['id'], "name"=>$row['LastName'].', '.$row['FirstName'].' ('.$row['name'].')');
	}
	return $out;
}

//process AJAX post-backs
if (isset($_POST['remove'])) {
	$toremove = array_diff($_POST['remove'], array($courseownerid));
	$ph = Sanitize::generateQueryPlaceholders($toremove);
	$stm = $DBH->prepare("DELETE FROM imas_teachers WHERE userid IN ($ph) AND courseid=?");
	$toremove[] = $cid;
	$stm->execute($toremove);
	
	echo json_encode(getTeachers($cid));
	exit;
} else if (isset($_POST['add'])) {
	$stm = $DBH->prepare("SELECT userid FROM imas_teachers WHERE courseid=?");
	$stm->execute(array($cid));
	$existing = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
	$toadd = array_diff($_POST['add'], $existing);
	$exarr = array();
	foreach ($toadd as $uid) {
		$exarr[] = $uid;
		$exarr[] = $cid;
	}
	$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,2);
	$stm = $DBH->prepare("INSERT INTO imas_teachers (userid,courseid) VALUES $ph");
	$stm->execute($exarr);
	
	echo json_encode(getTeachers($cid));
	exit;
} else if (isset($_POST['loadgroup'])) {
	$stm = $DBH->prepare("SELECT userid FROM imas_teachers WHERE courseid=?");
	$stm->execute(array($cid));
	$existing = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
	
	$ph = Sanitize::generateQueryPlaceholders($existing);
	$stm = $DBH->prepare("SELECT id,LastName,FirstName FROM imas_users WHERE id NOT IN ($ph) AND groupid=? ORDER BY LastName,FirstName");
	$existing[] = $coursegroupid;
	$stm->execute($existing);
	$out = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$out[] = array("id"=>$row['id'], "name"=>$row['LastName'].', '.$row['FirstName']);
	}
	echo json_encode($out);
	exit;
} else if (isset($_POST['search'])) {
	$stm = $DBH->prepare("SELECT userid FROM imas_teachers WHERE courseid=?");
	$stm->execute(array($cid));
	$existing = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
	
	require("../includes/userutils.php");
	$possible_teachers = searchForUser($_POST['search'], true, true);
	$out = array();
	foreach ($possible_teachers as $row) {
		if (in_array($row['id'], $existing)) { continue; }
		$out[] = array("id"=>$row['id'], "name"=>$row['LastName'].', '.$row['FirstName'].' ('.$row['name'].')');
	}
	echo json_encode($out);
	exit;
}

$from = 'admin2';
if (!empty($_GET['from'])) {
	if ($_GET['from']=='home') {
		$from = 'home';
		$backloc = '../index.php';
	} else if ($_GET['from']=='admin2') {
		$from = 'admin2';
		$backloc = 'admin2.php';
	} else if (substr($_GET['from'],0,2)=='ud') {
		$userdetailsuid = Sanitize::onlyInt(substr($_GET['from'],2));
		$from = 'ud'.$userdetailsuid;
		$backloc = 'userdetails.php?id='.Sanitize::encodeUrlParam($userdetailsuid);
	} else if (substr($_GET['from'],0,2)=='gd') {
		$groupdetailsgid = Sanitize::onlyInt(substr($_GET['from'],2));
		$from = 'gd'.$groupdetailsgid;
		$backloc = 'admin2.php?groupdetails='.Sanitize::encodeUrlParam($groupdetailsgid);
	}
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

$pagetitle = _('Add/Remove Teachers');

require("../header.php");

echo "<div class=breadcrumb>$breadcrumbbase ";
if ($from == 'admin') {
	echo "<a href=\"admin2.php\">Admin</a> &gt; ";
} else if ($from == 'admin2') {
	echo '<a href="admin2.php">'._('Admin').'</a> &gt; ';
} else if (substr($_GET['from'],0,2)=='ud') {
	echo '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$backloc.'">'._('User Details').'</a> &gt; ';
} else if (substr($_GET['from'],0,2)=='gd') {
	echo '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$backloc.'">'._('Group Details').'</a> &gt; ';
}
echo "$pagetitle</div>\n";
echo '<div class="pagetitle"><h2>'.$pagetitle.' - '.Sanitize::encodeStringForDisplay($coursename).'</h2></div>';
?>

<div id="app" v-cloak>
<div id="currentteachers">
	<h3>Current Teachers</h3>
	<p>With selected: <button @click="removeTeachers()">Remove as teacher</button>
	   <span v-if="processingRemove" class="noticetext">Saving Changes... <img src="../img/updating.gif"></span>
	</p>
	<transition-group name="fade" tag="ul" class="nomark">
		<li v-for="teacher in existingTeachers" :key="teacher.id">
			<input type=checkbox :value="teacher.id" :disabled="teacher.id==courseOwner"> {{teacher.name}}
		</li>
	</transition-group>
</div>
<div id="potentialteachers">
	<h3>Potential Teachers</h3>
	<p><button @click="loadGroup()">List my group members</button>
		or lookup a teacher: <input v-model="toLookup" size=30>
		<button @click="searchTeacher()" :disabled="toLookup.length==0">Search</button>
		<span v-if="processingSearch" class="noticetext">Looking up teachers... <img src="../img/updating.gif"></span>
		<span v-if="processingAdd" class="noticetext">Adding teachers... <img src="../img/updating.gif"></span>
	</p>
	<p v-if="searchResults !== null && searchResults.length==0">No teachers found</p>
	<p v-if="searchResults !== null && searchResults.length>0">
		<button @click="addTeachers()">Add selected</button>
	</p>
	<transition-group name="fade" tag="ul" class="nomark" v-if="searchResults !== null && searchResults.length>0">
		<li v-for="teacher in searchResults" :key="teacher.id">
			<input type=checkbox :value="teacher.id"> {{teacher.name}}
		</li>
	</transition-group>
</div>
</div>

<script type="text/javascript">
var app = new Vue({
	el: '#app',
	data: {
		processingRemove: false,
		processingSearch: false,
		processingAdd: false,
		existingTeachers: <?php echo json_encode(getTeachers($cid));?>,
		courseOwner: <?php echo Sanitize::onlyInt($courseownerid);?>,
		toLookup: "",
		searchResults: null,
		lastSearchType: ''
	},
	methods: {
		removeTeachers: function() {
			var toremove = $("#currentteachers input:checked").map(function(){ return $(this).val();}).get();
			if (toremove.length>0 && confirm("Are you SURE you want to remove these teachers?")) {
				var self = this;
				this.processingRemove = true;
				$.ajax({
					dataType: "json",
					type: "POST",
					url: window.location.href,
					data: {remove: toremove},
				}).done(function(msg) {
					self.existingTeachers = msg;
				}).always(function() {
					self.processingRemove = false;
					$("#currentteachers input:checked").prop("checked", false);
					if (self.lastSearchType=='group') {
						self.loadGroup();
					} else if (self.lastSearchType=='search') {
						self.searchTeacher();
					}
				});
				//todo: add error handling
			}
		}, 
		addTeachers: function() {
			var toadd = $("#potentialteachers input:checked").map(function(){ return $(this).val();}).get();
			if (toadd.length>0) {
				var self = this;
				this.processingAdd = true;
				$.ajax({
					dataType: "json",
					type: "POST",
					url: window.location.href,
					data: {add: toadd},
				}).done(function(msg) {
					self.existingTeachers = msg;
					var i = self.searchResults.length;
					while (i--) {
						if (toadd.indexOf(self.searchResults[i].id)!==-1) {
							self.searchResults.splice(i, 1);
						}
					}
				}).always(function() {
					self.processingAdd = false;
					$("#potentialteachers input:checked").prop("checked", false);
				});
				//todo: add error handling
			}
		},
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
		}
	}
});
</script>
<?php
require("../footer.php");
