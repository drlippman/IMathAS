<?php
//(c) 2013 David Lippman.  Part of IMathAS
//Define course outcomes
require("../validate.php");
if (!isset($teacherid)) { exit;}

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n";

$placeinhead = '<style type="text/css">.drag {color:red; background-color:#fcc;} .icon {cursor: pointer;} ul.qview li {padding: 3px}</style>';
$placeinhead .=  "<script>var AHAHsaveurl = '$imasroot/course/addoutcomes.php?cid=$cid&save=save'; var noblockcookie=true; var j=jQuery.noConflict();</script>";
$placeinhead .= "<script src=\"$imasroot/javascript/mootools.js\"></script>";
$placeinhead .= "<script src=\"$imasroot/javascript/nested1.js?v=0122102\"></script>";
$placeinhead .= '<script type="text/javascript">
	var ocnt = 0;
	var unsavedmsg = "'._("You have unrecorded changes.  Are you sure you want to abandon your changes?").'";
	function addoutcome() {
		var html = \'<li><span class=icon style="background-color:#0f0">O</span> \';
		html += \'<input type="text" size="60" id="new\'+ocnt+\'"> \';
		html += \'<a href="#" onclick="removeoutcome(this);return false\">'._("Delete").'</a></li>\';
		j("#qviewtree").append(j(html));
		ocnt++;
		if (!sortIt.haschanged) {
			sortIt.haschanged = true;
			sortIt.fireEvent(\'onFirstChange\', null);
			window.onbeforeunload = function() {return unsavedmsg;}
		}
	}
	function addoutcomegrp() {
		var html = \'<li class="blockli"><span class=icon style="background-color:#00f">G</span> \';
		html += \'<input type="text" size="60" id="newg\'+ocnt+\'"> \';
		html += \'<a href="#" onclick="removeoutcomegrp(this);return false\">'._("Delete").'</a></li>\';
		j("#qviewtree").append(j(html));
		ocnt++;
		if (!sortIt.haschanged) {
			sortIt.haschanged = true;
			sortIt.fireEvent(\'onFirstChange\', null);
			window.onbeforeunload = function() {return unsavedmsg;}
		}
	}
	function removeoutcome(el) {
		if (confirm("'._("Are you sure you want to delete this outcome?").'")) {
			j(el).parent().remove();
			if (!sortIt.haschanged) {
				sortIt.haschanged = true;
				sortIt.fireEvent(\'onFirstChange\', null);
				window.onbeforeunload = function() {return unsavedmsg;}
			}
		}
	}
	function removeoutcomegrp(el) {
		if (confirm("'._("Are you sure you want to delete this outcome group?  This will not delete the included outcomes.").'")) {
			var curloc = j(el).parent();
			curloc.find("li").each(function() {
				curloc.before(j(this));
			});
			curloc.remove();
			if (!sortIt.haschanged) {
				sortIt.haschanged = true;
				sortIt.fireEvent(\'onFirstChange\', null);
				window.onbeforeunload = function() {return unsavedmsg;}
			}
		}
	}
	
	</script>';
require("../header.php");

echo '<div class=breadcrumb>'.$curBreadcrumb.' &gt; '._("Define Course Outcomes").'</div>';

echo "<div id=\"headercourse\" class=\"pagetitle\"><h2>"._("Define Course Outcomes")."</h2></div>\n";

echo '<div class="breadcrumb">'._('Use colored boxes to drag-and-drop order.').' <input type="button" id="recchg" disabled="disabled" value="', _('Record Changes'), '" onclick="submitChanges()"/><span id="submitnotice" style="color:red;"></span></div>';

echo '<ul id="qviewtree" class="qview">';

echo '</ul>';
echo '<input type="button" onclick="addoutcomegrp()" value="'._('Add Outcome Group').'"/> ';
echo '<input type="button" onclick="addoutcome()" value="'._('Add Outcome').'"/> ';
require("../footer.php");

?>
