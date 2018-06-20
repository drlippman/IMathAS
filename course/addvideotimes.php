<?php
// IMathAS: Add video cueing to assessments
// (c) 2012 David Lippman

require("../init.php");


if (!isset($teacherid)) {
	exit;
}
$aid = Sanitize::onlyInt($_GET['aid']);

//form handling

if (isset($_POST['vidid'])) {
	//DB $_POST = stripslashes_deep($_POST);
	$vidid = $_POST['vidid'];
	$vidar = $_POST['vidar'];
	$data = array();
	$i = 0;
	while (isset($_POST['segtitle'.$i])) {
		$n = array();
		$n[0] = trim(htmlentities($_POST['segtitle'.$i]));
		$thistime = timetosec($_POST['segend'.$i]);
		$n[1] = $thistime;
		if (isset($_POST['qn'.$i])) {
			$n[2] = $_POST['qn'.$i];
		}
		if (isset($_POST['hasfollowup'.$i])) {
			$n[3] = timetosec($_POST['followupend'.$i]);

			if (isset($_POST['showlink'.$i])) {
				$n[4] = true;
			} else {
				$n[4] = false;
			}
			$n[5] = trim(htmlentities($_POST['followuptitle'.$i]));
		}
		$data[$thistime] = $n;
		$i++;
	}
	ksort($data);
	$data = array_values($data);
	array_unshift($data, array($vidid, $vidar));
	if (trim($_POST['finalseg'])!='') {
		array_push($data, array(htmlentities($_POST['finalseg'])));
	}
	//DB 	$data = addslashes(serialize($data));
	$data = serialize($data);
	//DB $query = "UPDATE imas_assessments SET viddata='$data' WHERE id='$aid'";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_assessments SET viddata=:viddata WHERE id=:id");
	$stm->execute(array(':viddata'=>$data, ':id'=>$aid));

	header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions.php?cid=$cid&aid=$aid&r=" .Sanitize::randomQueryStringParam());
	exit;
}


//start display
require("../header.php");

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
echo "&gt; <a href=\"addquestions.php?cid=$cid&aid=$aid\">Add/Remove Questions</a> &gt; Video Navigation</div>\n";


//DB $query = "SELECT itemorder,viddata FROM imas_assessments WHERE id='$aid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB $row = mysql_fetch_row($result);
$stm = $DBH->prepare("SELECT itemorder,viddata FROM imas_assessments WHERE id=:id");
$stm->execute(array(':id'=>$aid));
$row = $stm->fetch(PDO::FETCH_NUM);
$qorder = explode(',',$row[0]);
$viddata = $row[1];
$qidbynum = array();
for ($i=0;$i<count($qorder);$i++) {
	if (strpos($qorder[$i],'~')!==false) {
		$qids = explode('~',$qorder[$i]);
		if (strpos($qids[0],'|')!==false) { //pop off nCr
			$qidbynum[$i] = $qids[1];
		} else {
			$qidbynum[$i] = $qids[0];
		}
	} else {
		$qidbynum[$i] = $qorder[$i];
	}
}

//Get question titles
$qtitlebyid = array();
//DB $query = "SELECT iq.id,iqs.description FROM imas_questions AS iq,imas_questionset as iqs";
//DB $query .= " WHERE iq.questionsetid=iqs.id AND iq.assessmentid='$aid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$query = "SELECT iq.id,iqs.description FROM imas_questions AS iq,imas_questionset as iqs";
$query .= " WHERE iq.questionsetid=iqs.id AND iq.assessmentid=:assessmentid";
$stm = $DBH->prepare($query);
$stm->execute(array(':assessmentid'=>$aid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	if (strlen($row[1])<30) {
		$qtitle[$row[0]] = $row[1];
	} else {
		$qtitle[$row[0]] = substr($row[1],0,30).'...';
	}
}

function sectotime($t) {
	if ($t<60) {
		return $t;
	} else {
		$o = floor($t/60).':';
		$t = $t%60;
		if ($t<10) {
			$o .= '0'.$t;
		} else {
			$o .= $t;
		}
	}
	return $o;
}
function timetosec($t) {
	if (strpos($t,':')===false) {
		$time = $t;
	} else {
		$x = explode(':',$t);
		$time = 60*$x[0] + $x[1];
	}
	return $time;
}

if ($viddata != '') {
	$data = unserialize($viddata);
	//load existing data
	$vidid = array_shift($data);
	if (is_array($vidid)) {
	  list($vidid,$vidar) = $vidid;
	} else {
	  $vidar = "16:9";
	}
	$vidarpts = explode(':',$vidar);
	$aspectRatio = $vidarpts[0]/$vidarpts[1];
	$n = count($data);
	$title = array(); $endtime = array();
	$qn = array();
	$followuptitle = array();
	$followupenddtime = array();
	$hasfollowup = array();
	$showlink = array();
	$finalsegtitle;
	for ($i=0;$i<$n;$i++) {
		$title[$i] = $data[$i][0];
		if (count($data[$i])==1) {
			$finalsegtitle = $data[$i][0];
			$n--;
		} else {
			$endtime[$i] = sectotime($data[$i][1]);
		}
		if (count($data[$i])>2) {  //is a question segment
			$qn[$i] = $data[$i][2];
			if (count($data[$i])>3) { //has followup
				$followuptitle[$i] = $data[$i][5];
				$followupendtime[$i] = sectotime($data[$i][3]);
				$showlink[$i] = $data[$i][4];
				$hasfollowup[$i] = true;
			} else {
				$hasfollowup[$i] = false;
				$followuptitle[$i] = '';
				$followupendtime[$i] = '';
				$showlink[$i] = true;
			}
		}
	}
} else {
	//new video stuff
	$n = count($qorder);
	$title = array_fill(0, $n, '');
	$endtime = array_fill(0,$n, '');
	$qn = range(0, $n-1);
	$followuptitle = array_fill(0, $n, '');
	$follwupendtime = array_fill(0,$n, '');
	$showlink = array_fill(0, $n, true);
	$finalsegtitle = '';
	$vidid = '';

}
?>
<script type="text/javascript">
var tag = document.createElement('script');
tag.src = "//www.youtube.com/player_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

var player;
var vidid = "<?php echo Sanitize::encodeStringForJavascript($vidid);?>";

function validatevidform(el) {
	var els = el.getElementsByTagName("input");
	var lastsegtime = 0;
	var hasfollowup = false;
	for (var i=0; i<els.length; i++) {
		if (els[i].name.match(/segtitle/)) {
			if (els[i].value=="") {
				alert("Please give all segments titles");
				els[i].focus();
				return false;
			}
		} else if (els[i].name.match(/vidid/)) {
			if (els[i].value=="") {
				alert("Please provide a video ID");
				els[i].focus();
				return false;
			}
		} else if (els[i].name.match(/segend/)) {
			if (els[i].value=="") {
				alert("Please supply end times for all segments");
				els[i].focus();
				return false;
			}
			if (els[i].value.match(/:/)) {
				var v = els[i].value.split(':');
				v = v[0]*60 + v[1]*1;
			} else {
				var v = els[i].value*1;
			}
			if (v<lastsegtime) {
				alert("Make sure each segment's end time is later than previous segments");
				els[i].focus();
				return false;
			}
			lastsegtime = v;
		} else if (els[i].name.match(/hasfollowup/)) {
			hasfollowup = els[i].checked;
		} else if (els[i].name.match(/followuptitle/) && hasfollowup) {
			if (els[i].value=="") {
				alert("Please give all segments titles");
				els[i].focus();
				return false;
			}
		} else if (els[i].name.match(/followupend/) && hasfollowup) {
			if (els[i].value=="") {
				alert("Please supply end times for all segments");
				els[i].focus();
				return false;
			}
			if (els[i].value.match(/:/)) {
				var v = els[i].value.split(':');
				v = v[0]*60 + v[1]*1;
			} else {
				var v = els[i].value*1;
			}
			if (v<lastsegtime) {
				alert("Make sure each segment's end time is later than previous segments");
				els[i].focus();
				return false;
			}
			lastsegtime = v;
		}

	}
	return true;
}

function onYouTubePlayerAPIReady() {
	if (vidid!="") {
		loadPlayer();
	}
}

function loadPlayer() {
	player = new YT.Player('player', {
		height: <?php echo ceil(453/$aspectRatio);?>,
		width: 453,
		videoId: vidid,
		playerVars: {'autoplay': 0, 'wmode': 'transparent', 'fs': 0, 'controls':1, 'rel':0, 'modestbranding':1, 'showinfo':0}
	});
}

function loadnewvideo() {
	if (vidid=="") {
		vidid = document.getElementById("vidid").value;
		loadPlayer();
	} else {
		vidid = document.getElementById("vidid").value;
		player.cueVideoById(vidid);
	}
}
function updateAR() {
	var arpts = $("#vidar").val().split(":");
	var newheight = Math.ceil(453*arpts[1]/arpts[0]);
	$("#player").css("height",newheight+"px").attr("height",newheight);
}
function grabcurvidtime(n,type) {
	//do youtube video logic here
	if (!player || player.getPlayerState() != 1) { return;}
	var t =  Math.floor(player.getCurrentTime());
	var o;
	if (t < 60) {
		o = t;
	} else {
		o = Math.floor(t/60) + ":" + ((t%60<10)?'0'+(t%60):(t%60));
	}
	if (type==0) {
		document.getElementById("segend"+n).value=o;
	} else {
		document.getElementById("followupend"+n).value=o;
	}
}
function updatefollowup(n,el) {
	if (el.checked) {
		document.getElementById("followupspan"+n).style.display = "inline";
	} else {
		document.getElementById("followupspan"+n).style.display = "none";
	}
}
function addsegat(n) {
	var insat = document.getElementById("insat"+n);

	var newins = document.createElement("div");
	newins.className = "insblock";
	newins.id = "insat"+(curnumseg+1);
	newins.innerHTML = '<a href="#" onclick="addsegat('+(curnumseg+1)+'); return false;">Add video segment break</a>';
	insat.parentNode.insertBefore(newins, insat);

	var html = 'Segment title: <input type="text" size="20" name="segtitle'+curnumseg+'" value=""/> ';
	html += 'Ends at: <input type="text" size="4" name="segend'+curnumseg+'" id="segend'+curnumseg+'"  value=""/> ';
	html += '<input type="button" value="grab" onclick="grabcurvidtime('+curnumseg+',0);"/>';
	html += ' <a href="#" onclick="return deleteseg(this);">[Delete]</a>';

	var newseg = document.createElement("div");
	newseg.className = "vidsegblock";
	newseg.innerHTML = html;
	insat.parentNode.insertBefore(newseg, insat);

	curnumseg++;
}

function get_previoussibling(n) {
	x=n.previousSibling;
	while (x.nodeType!=1) {
		x=x.previousSibling;
	}
	return x;
}

function deleteseg(el) {
	if (confirm('Are you sure you want to remove this video segment?')) {
		var divtodelete = el.parentNode;
		divtodelete.parentNode.removeChild(get_previoussibling(divtodelete));
		divtodelete.parentNode.removeChild(divtodelete);
	}
	return false;
}
</script>
<style type="text/css">
div.insblock {
	background: #ccf;
	margin-top: 5px;
	margin-bottom: 5px;
}
div.vidsegblock {
	background: #cfc;
}
</style>

<?php
echo '<script type="text/javascript">var curnumseg = '.$n.';</script>';
?>

<h1>Video Navigation and Question Cues</h1>
<div style="float:right;"><div id="player" style="width: 453px; height: <?php echo ceil(453/$aspectRatio);?>px;"></div></div>
<p>This page allows you to setup your assessment to be cued to a video.  For each
question, give a title to the video segment that leads up to that question, and select
the time when that segment ends and the question should show.  You can grab this
from the playing video, type the time in min:sec form.  Make sure all times are at least
one second before the end of the video.</p>

<p>If your video contains a followup segment to a question (such as a solution),
you can indicate this and specify when the followup ends.  The next segment will
then start from the end of this followup.</p>

<form method="post" style="clear:both;" onsubmit="return validatevidform(this);">

<p>YouTube video ID: <input type="text" name="vidid" id="vidid" value="<?php echo Sanitize::encodeStringForDisplay($vidid); ?>"/>
<input type="button" value="Load Video" onclick="loadnewvideo()"/></p>
<p>
	Video Aspect Ratio:
	<select name="vidar" id="vidar" onchange="updateAR()">
<?php
	$ratios = array("16:9", "4:3", "3:2");
	foreach ($ratios as $ratio) {
		echo "<option ".($ratio==$vidar ? "selected" : "").">$ratio</option>";
	}
?>
	</select>
</p>

<?php

for ($i=0;$i<$n;$i++) {
	echo '<div class="insblock" id="insat'.$i.'">';
	echo '<a href="#" onclick="addsegat('.$i.'); return false;">Add video segment break</a></div>';

	if (isset($qn[$i])) {
		echo '<div class="vidsegblock">';
		echo 'Segment title: <input type="text" size="20" name="segtitle'.$i.'" value="' . Sanitize::encodeStringForDisplay($title[$i]) . '"/> ';
		echo 'Ends at: <input type="text" size="4" name="segend'.$i.'" id="segend' . $i . '" value="' . Sanitize::encodeStringForDisplay($endtime[$i]) . '"/> ';
		echo '<input type="button" value="grab" onclick="grabcurvidtime('.$i.',0);"/> ';
		echo 'Question ' . (Sanitize::onlyInt($qn[$i]) + 1) . ': ' . Sanitize::encodeStringForDisplay($qtitle[$qidbynum[$qn[$i]]]);
		echo '<input type="hidden" name="qn'.$i.'" value="' . Sanitize::encodeStringForDisplay($qn[$i]) . '"/>';
		echo '<br/>';
		echo 'Has followup? <input type="checkbox" name="hasfollowup'.$i.'" value="1" ';
		if ($hasfollowup[$i]) {
			echo 'checked="checked" onclick="updatefollowup('.$i.',this);" /> <span id="followupspan'.$i.'">';
		} else {
			echo ' onclick="updatefollowup('.$i.',this);" /> <span id="followupspan'.$i.'" style="display:none;">';
		}
		echo 'Followup title: <input type="text" size="20" name="followuptitle'.$i.'" value="' . Sanitize::encodeStringForDisplay($followuptitle[$i]) . '"/> ';
		echo 'Ends at: <input type="text" size="4" name="followupend'.$i.'" id="followupend'.$i.'" value="' . Sanitize::encodeStringForDisplay($followupendtime[$i]) . '"/> ';
		echo '<input type="button" value="grab" onclick="grabcurvidtime('.$i.',1);"/> ';
		echo 'Show link in navigation? <input type="checkbox" name="showlink'.$i.'" value="1" ';
		if ($showlink[$i]) {
			echo 'checked="checked"';
		}
		echo '/></span>';
		echo '</div>';
	} else {
		echo '<div class="vidsegblock">';
		echo 'Segment title: <input type="text" size="20" name="segtitle'.$i.'" value="' . Sanitize::encodeStringForDisplay($title[$i]) . '"/> ';
		echo 'Ends at: <input type="text" size="4" name="segend'.$i.'" id="segend'.$i.'" value="' . Sanitize::encodeStringForDisplay($endtime[$i]) . '"/> ';
		echo '<input type="button" value="grab" onclick="grabcurvidtime('.$i.',0);"/> <a href="#" onclick="return deleteseg(this);">[Delete]</a></div>';
	}
}
echo '<div class="insblock" id="insat' . $n . '">';
echo '<a href="#" onclick="addsegat(' . $n . '); return false;">Add video segment break</a></div>';

echo '<div class="vidsegblock">';
echo 'Remainder of video segment title (if any): <input type="text" size="20" name="finalseg" value="' . Sanitize::encodeStringForDisplay($finalsegtitle) . '"/></div>';
echo '<p><input type="submit" value="Submit"/></p>';
echo '</form>';
require("../footer.php");

?>
