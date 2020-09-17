<?php
//IMathAS:  Main gradebook views (instructor & student)
//(c) 2007 David Lippman
// DONE:
//   Instructor Main view
//   Student Main view
//   Export/Email GB (gb-export.php)
//   Item Analysis (gb-itemanalysis.php)
//   New asid
//   View/Edit Assessment
//   Question/Category breakdown
//   email, message, unenroll, etc
//   stu view links
//   exceptions





require("../init.php");
$cid = Sanitize::courseId($_GET['cid']);
if (isset($teacherid)) {
	$isteacher = true;

}
if (isset($tutorid)) {
	$istutor = true;
}
if (!$isteacher && !$istutor && !isset($studentid)) {
	echo _('Error - you are not a student, teacher, or tutor for this course');
	exit;
}
if ($isteacher || $istutor) {
	$canviewall = true;
} else {
	$canviewall = false;
}

if ($canviewall) {
	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
		$_SESSION[$cid.'gbmode'] = $gbmode;
		if (isset($_GET['setgbmodeonly'])) {
			echo "DONE";
			exit;
		}
	} else if (isset($_SESSION[$cid.'gbmode']) && !isset($_GET['refreshdef'])) {
		$gbmode =  $_SESSION[$cid.'gbmode'];
	} else {
		$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbmode = $stm->fetchColumn(0);
		$_SESSION[$cid.'gbmode'] = $gbmode;
	}
	if (isset($_COOKIE["colorize-$cid"]) && !isset($_GET['refreshdef'])) {
		$colorize = $_COOKIE["colorize-$cid"];
	} else {
		$stm = $DBH->prepare("SELECT colorize FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$colorize = $stm->fetchColumn(0);
		setcookie("colorize-$cid",$colorize);
	}
	if (isset($_GET['catfilter'])) {
		$catfilter = $_GET['catfilter'];
		$_SESSION[$cid.'catfilter'] = $catfilter;
	} else if (isset($_SESSION[$cid.'catfilter'])) {
		$catfilter = $_SESSION[$cid.'catfilter'];
	} else {
		$catfilter = -1;
	}
	if (isset($tutorsection) && $tutorsection!='') {
		$secfilter = $tutorsection;
	} else {
		if (isset($_GET['secfilter'])) {
			$secfilter = $_GET['secfilter'];
			$_SESSION[$cid.'secfilter'] = $secfilter;
		} else if (isset($_SESSION[$cid.'secfilter'])) {
			$secfilter = $_SESSION[$cid.'secfilter'];
		} else {
			$secfilter = -1;
		}
	}
	if (isset($_GET['refreshdef']) && isset($_SESSION[$cid.'catcollapse'])) {
		unset($_SESSION[$cid.'catcollapse']);
	}
	if (isset($_SESSION[$cid.'catcollapse'])) {
		$overridecollapse = $_SESSION[$cid.'catcollapse'];
	} else {
		$overridecollapse = array();
	}
	if (isset($_GET['catcollapse'])) {
		$overridecollapse[$_GET['cat']] = $_GET['catcollapse'];
		$_SESSION[$cid.'catcollapse'] = $overridecollapse;
	}

	//Gbmode : Links NC Dates
	$hidesection = (((floor($gbmode/100000)%10)&1)==1);
	$hidecode = (((floor($gbmode/100000)%10)&2)==2);
	$showpercents = (((floor($gbmode/100000)%10)&4)==4)?1:0; //show percents instead of points
	$showpics = floor($gbmode/10000)%10 ; //0 none, 1 small, 2 big
	$totonleft = ((floor($gbmode/1000)%10)&1) ; //0 right, 1 left
	$avgontop = ((floor($gbmode/1000)%10)&2) ; //0 bottom, 2 top
	$lastlogin = (((floor($gbmode/1000)%10)&4)==4) ; //0 hide, 2 show last login column
	$links = ((floor($gbmode/100)%10)&1); //0: view/edit, 1 q breakdown
	$hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked
	$includeduedate = (((floor($gbmode/100)%10)&4)==4); //0: hide due date, 4: show due date
	$hidenc = (floor($gbmode/10)%10)%4; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
	$includelastchange = (((floor($gbmode/10)%10)&4)==4);  //: hide last change, 4: show last change
	$availshow = $gbmode%10; //0: past, 1 past&cur, 2 all, 3 past and attempted, 4=current only

} else {
	$secfilter = -1;
	$catfilter = -1;
	$links = 0;
	$hidenc = 1;
	$availshow = 1;
	$showpics = 0;
	$totonleft = 0;
	$avgontop = 0;
	$hidelocked = 0;
	$showpercents = 0;
	$lastlogin = false;
	$includeduedate = false;
	$includelastchange = false;
}

if ($canviewall && !empty($_GET['stu'])) {
	$stu = Sanitize::onlyInt($_GET['stu']);
} else {
	$stu = 0;
}

if (!empty($CFG['assess2-use-vue-dev'])) {
	$assessGbUrl = sprintf("%s/gbviewassess.html", $CFG['assess2-use-vue-dev-address']);
	$assessUrl = $CFG['assess2-use-vue-dev-address'] . '/';
} else {
	$assessGbUrl = "../assess2/gbviewassess.php";
	$assessUrl = "../assess2/";
}

//HANDLE ANY POSTS
if ($isteacher) {
	if (isset($_GET['togglenewflag'])) {
		//recording a toggle.  Called via AHAH
		$stm = $DBH->prepare("SELECT newflag FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$newflag = $stm->fetchColumn(0);
		$newflag = $newflag ^ 1;  //XOR
		$stm = $DBH->prepare("UPDATE imas_courses SET newflag=:newflag WHERE id=:id");
		$stm->execute(array(':newflag'=>$newflag, ':id'=>$cid));
		if (($newflag&1)==1) {
			echo 'New';
		}
		exit;
	}
	if (isset($_POST['lockinstead'])) {
		$_GET['action'] = "lock";
		$_POST['tolock'] = $_POST['tounenroll'];
	}
	if ((isset($_POST['posted']) && ($_POST['posted']=="E-mail" || $_POST['posted']=="Message"))|| isset($_GET['masssend']))  {
		$calledfrom='gb';
		include("masssend.php");
	}
	if ((isset($_POST['posted']) && $_POST['posted']=="Make Exception") || isset($_GET['massexception'])) {
		$calledfrom='gb';
		include("massexception.php");
	}
	if (isset($_POST['posted']) && $_POST['posted']==_("Excuse Grade")) {
		$calledfrom='gb';
		include("gb-excuse.php");
	}
	if (isset($_POST['posted']) && $_POST['posted']==_("Un-excuse Grade")) {
		$calledfrom='gb';
		include("gb-excuse.php");
	}
	if ((isset($_POST['posted']) && $_POST['posted']=="Unenroll") || (isset($_GET['action']) && $_GET['action']=="unenroll" )) {
		$calledfrom='gb';
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		$curBreadcrumb .= "&gt; <a href=\"gradebook.php?cid=$cid\">Gradebook</a> &gt; Confirm Change";
		$pagetitle = _('Unenroll Students');
		include("unenroll.php");
		include("../footer.php");
		exit;
	}
	if ((isset($_POST['posted']) && $_POST['posted']=="Lock") || (isset($_GET['action']) && $_GET['action']=="lock" )) {
		$calledfrom='gb';
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		$curBreadcrumb .= "&gt; <a href=\"gradebook.php?cid=$cid\">Gradebook</a> &gt; Confirm Change";
		$pagetitle = _('Lock Students');
		include("lockstu.php");
		include("../footer.php");
		exit;
	}
	if (isset($_POST['posted']) && $_POST['posted']=='Print Report') {
		//based on a contribution by Cam Joyce
		require_once("gbtable2.php");

		$placeinhead = '<style type="text/css" >@media print { .noPrint  { display:none; } }</style>';
		$placeinhead .= '<script type="text/javascript">addLoadEvent(print);</script>';
		$flexwidth = true;
		require("../header.php");

		echo '<div class="noPrint"><a href="#" onclick="window.print(); return false;">Print Reports</a> ';
		echo '<a href="gradebook.php?'.Sanitize::encodeStringForDisplay($_SERVER['QUERY_STRING']).'">', _('Back to Gradebook'), '</a></div>';
		if( isset($_POST['checked']) ) {
			echo "<div id=\"tbl-container\">";
			echo '<div id="bigcontmyTable"><div id="tblcontmyTable">';
			$value = $_POST['checked'];
			$last = count($value)-1;
			for($i = 0; $i < $last; $i++){
				gbstudisp(Sanitize::onlyInt($value[$i]));
				echo "<div style=\"page-break-after:always\"></div>";
			}
			gbstudisp(Sanitize::onlyInt($value[$last]));//no page break after last report

			echo "</div></div></div>";
		}
		require("../footer.php");
		exit;

	}
	if (isset($_POST['usrcomments']) && $stu>0) {
			$stm = $DBH->prepare("UPDATE imas_students SET gbcomment=:gbcomment WHERE userid=:userid AND courseid=:courseid");
			$stm->execute(array(':gbcomment'=>$_POST['usrcomments'], ':userid'=>$stu, ':courseid'=>$cid));
			//echo "<p>Comment Updated</p>";
	}
	if (isset($_POST['score']) && $stu>0) {
		foreach ($_POST['score'] as $id=>$val) {
			if (trim($val)=='') {
				$stm = $DBH->prepare("DELETE FROM imas_grades WHERE userid=:userid AND gradetypeid=:gradetypeid AND gradetype='offline'");
				$stm->execute(array(':userid'=>$stu, ':gradetypeid'=>$id));
			} else {
				$stm = $DBH->prepare("UPDATE imas_grades SET score=:score WHERE userid=:userid AND gradetypeid=:gradetypeid AND gradetype='offline'");
				$stm->execute(array(':score'=>$val, ':userid'=>$stu, ':gradetypeid'=>$id));
			}

		}
	}
	if (isset($_POST['newscore']) && $stu>0) {
		$toins = array();
		$qarr = array();
		foreach ($_POST['newscore'] as $id=>$val) {
			if (trim($val)=="") {continue;}
			$toins[] = "(?,?,?,?,?)";
			array_push($qarr, $id, 'offline', $stu, $val, $_POST['feedback'][$id]);
		}
		if (count($toins)>0) {
			$query = "INSERT INTO imas_grades (gradetypeid,gradetype,userid,score,feedback) VALUES ".implode(',',$toins);
			$stm = $DBH->prepare($query);
			$stm->execute($qarr);
		}
	}
	if (isset($_POST['usrcomments']) || isset($_POST['score']) || isset($_POST['newscore'])) {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?".Sanitize::fullQueryString($_SERVER['QUERY_STRING']) . "&r=" . Sanitize::randomQueryStringParam());
		exit;
	}
}



//DISPLAY
require_once("gbtable2.php");
require("../includes/htmlutil.php");

$placeinhead = '<script type="text/javascript">
var cid = '.Sanitize::onlyInt($cid).';
var stu = '.Sanitize::onlyInt($stu).';
var basesite = "'.$GLOBALS['basesiteurl'] . '/course/gradebook.php";
var gbmodebase = '.Sanitize::onlyInt($gbmode).';
var gbmod = {
	"hidenc": '.Sanitize::onlyInt($hidenc).',
	"availshow": '.Sanitize::onlyInt($availshow).',
	"hidelocked": '.Sanitize::onlyInt($hidelocked).',
	"links": '.Sanitize::onlyInt($links).',
	"pts": '.Sanitize::onlyInt($showpercents).',
	"showpics": '.Sanitize::onlyInt($showpics).'};
</script>';
$placeinhead .= '<style>
 dl.inlinedl dt,dl.inlinedl dd {
	 display: inline; margin: 0;
 }
 dl.inlinedl dt {
	font-weight: bold;
 }
 ul.inlineul {
	 display: inline; list-style: none; padding: 0px;
 }
 ul.inlineul li {
	 display: inline;
 }
 ul.inlineul li::after { content: ", "; }
 ul.inlineul li:last-child::after { content: ""; }
 </style>';
if ($canviewall) {
	$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/gradebook.js?v=052320"></script>';
}

if (isset($studentid) || $stu!=0) { //show student view
	if (isset($studentid)) {
		$stu = $userid;
		$includetimelimit = true;
		$includeduedate = true;
	}
	$pagetitle = _('Gradebook');
	$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js?v=051820\"></script>\n";
	$placeinhead .= '<script type="text/javascript">
		function showfb(id,type,uid) {
			if (type=="all") {
				GB_show(_("Feedback"), "showfeedbackall.php?cid="+cid+"&stu="+id, 600, 600);
			} else if (type=="F") {
				GB_show(_("Feedback"), "viewforumgrade.php?embed=true&cid="+cid+"&uid="+uid+"&fid="+id, 600, 600);
			} else if (type=="A2") {
				GB_show(_("Feedback"), "showfeedback.php?cid="+cid+"&type="+type+"&id="+id+"&uid="+uid, 600, 600);
			} else {
				GB_show(_("Feedback"), "showfeedback.php?cid="+cid+"&type="+type+"&id="+id, 600, 600);
			}
			return false;
		}
	</script>';

	require("../header.php");
	if (isset($_GET['from']) && $_GET['from']=="listusers") {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt ", _('Student Grade Detail'), "</div>\n";
	} else if ($isteacher || $istutor) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; ", _('Student Detail'), "</div>";
	} else {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; ", _('Gradebook'), "</div>";
	}
	if ($stu==-1) {
		echo '<div id="headergradebook" class="pagetitle"><h1>', _('Grade Book Averages'), ' </h1></div>';
	} else {
		echo '<div id="headergradebook" class="pagetitle"><h1>', _('Grade Book Student Detail'), '</h1></div>';
	}
	if ($canviewall) {
		echo "<div class=cpmid>";
		echo _('Category'), ': <select id="filtersel" onchange="chgfilter()">';
		echo '<option value="-1" ';
		if ($catfilter==-1) {echo "selected=1";}
		echo '>', _('All'), '</option>';
		echo '<option value="0" ';
		if ($catfilter==0) { echo "selected=1";}
		echo '>', _('Default'), '</option>';
		$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid ORDER BY name");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo '<option value="'.Sanitize::encodeStringForDisplay($row[0]).'"';
			if ($catfilter==$row[0]) {echo "selected=1";}
			echo '>'.Sanitize::encodeStringForDisplay($row[1]).'</option>';
		}
		echo '<option value="-2" ';
		if ($catfilter==-2) {echo "selected=1";}
		echo '>', _('Category Totals'), '</option>';
		echo '</select> | ';
		echo _('Not Counted:'), " <select id=\"hidenc\" onchange=\"chggbfilters()\">";
		echo "<option value=0 "; writeHtmlSelected($hidenc,0); echo ">", _('Show all'), "</option>";
		echo "<option value=1 "; writeHtmlSelected($hidenc,1); echo ">", _('Show stu view'), "</option>";
		echo "<option value=2 "; writeHtmlSelected($hidenc,2); echo ">", _('Hide all'), "</option>";
		echo "</select>";
		echo " | ", _('Show:'), " <select id=\"availshow\" onchange=\"chggbfilters()\">";
		echo "<option value=0 "; writeHtmlSelected($availshow,0); echo ">", _('Past due'), "</option>";
		echo "<option value=3 "; writeHtmlSelected($availshow,3); echo ">", _('Past &amp; Attempted'), "</option>";
		echo "<option value=4 "; writeHtmlSelected($availshow,4); echo ">", _('Available Only'), "</option>";
		echo "<option value=1 "; writeHtmlSelected($availshow,1); echo ">", _('Past &amp; Available'), "</option>";
		echo "<option value=2 "; writeHtmlSelected($availshow,2); echo ">", _('All'), "</option></select>";
		echo " | ", _('Links:'), " <select id=\"linktoggle\" onchange=\"chglinktoggle()\">";
		echo "<option value=0 "; writeHtmlSelected($links,0); echo ">", _('View/Edit'), "</option>";
		echo "<option value=1 "; writeHtmlSelected($links,1); echo ">", _('Scores'), "</option></select>";
		echo '<input type="hidden" id="toggle4" value="'.$showpics.'" />';
		echo '<input type="hidden" id="toggle5" value="'.$hidelocked.'" />';
		echo "</div>";
	}
	gbstudisp($stu);
	echo "<div>", _('Meanings:'), ' <ul class="inlineul">';
	echo '<li>'._('IP-In Progress (some unattempted questions)').'</li>';
	echo '<li>'._('UA-Unsubmitted attempt').'</li>';
	echo '<li>'._('OT-overtime').'</li>';
	echo '<li>'._('PT-practice test').'</li>';
	echo '<li>'._('EC-extra credit').'</li>';
	echo '<li>'._('NC-no credit').'</li>';
	echo '<li>'._('<sub>d</sub> Dropped score').'</li>';
	echo '<li>'._('<sup>x</sup> Excused score').'</li>';
	echo '<li>'._('<sup>e</sup> Has exception').'</li>';
	echo '<li>'._('<sup>LP</sup> Used latepass').'</li>';
	echo '</ul></div>';

	require("../footer.php");

} else { //show instructor view
	$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js?v=012811\"></script>\n";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablescroller2.js?v=052320\"></script>\n";
	$placeinhead .= "<script type=\"text/javascript\">\n";
	$placeinhead .= 'var ts = new tablescroller("myTable",';
	if (isset($_COOKIE["gblhdr-$cid"]) && $_COOKIE["gblhdr-$cid"]==1) {
		$placeinhead .= 'true,'.$showpics.');';
		$headerslocked = true;
	} else {
		if (!isset($_COOKIE["gblhdr-$cid"]) && isset($CFG['GBS']['lockheader']) && $CFG['GBS']['lockheader']==true) {
			$placeinhead .= 'true,'.$showpics.');';
			$headerslocked = true;
		} else {
			$placeinhead .= 'false,'.$showpics.');';
			$headerslocked = false;
			$usefullwidth = true;
		}
	}
	if (isset($_COOKIE["gbfullw-$cid"]) && $_COOKIE["gbfullw-$cid"]==1) {
		$usefullwidth = true;
	}
	$showwidthtoggle = (strpos($coursetheme, '_fw')!==false);

	$placeinhead .= "</script>\n";
	$placeinhead .= "<style type=\"text/css\"> table.gb { margin: 0px; } div.trld {display:table-cell;vertical-align:middle;white-space: nowrap;} </style>";
	$placeinhead .= '<style type="text/css"> .dropdown-header {  font-size: inherit;  padding: 3px 10px;} </style>';

	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; ", _('Gradebook'), "</div>";
	echo "<form id=\"qform\" method=post action=\"gradebook.php?cid=$cid\">";

	echo '<div id="headergradebook" class="pagetitle"><h1>', _('Gradebook'), ' <span class="noticetext" id="newflag" style="font-size: 70%" >';
	if (($coursenewflag&1)==1) {
		echo _('New');
	}
	echo '</span></h1></div>';
	if ($isdiag) {
		echo "<a href=\"gb-testing.php?cid=$cid\">", _('View diagnostic gradebook'), "</a>";
	}
	echo "<div class=cpmid>";
	$i = 0;
	$togglehtml = '<span class="dropdown">';
	$togglehtml .= ' <a tabindex=0 class="dropdown-toggle arrow-down" id="dropdownMenu'.$i.'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
	$togglehtml .= _('Toggles').'</a>';
	$togglehtml .= '<ul class="dropdown-menu gbtoggle" role="menu" aria-labelledby="dropdownMenu'.$i.'">';
	$togglehtml .= '<li class="dropdown-header">'._('Headers').'</li>';
	$togglehtml .= '<li><a data-hdrs="1">'._('Locked').'</a></li>';
	$togglehtml .= '<li><a data-hdrs="0">'._('Unlocked').'</a></li>';

	if ($showwidthtoggle && $headerslocked) {
		$togglehtml .= '<li class="dropdown-header" data-pgw="hdr">'._('Width').'</li>';
		$togglehtml .= '<li><a data-pgw="0">'._('Fixed').'</a></li>';
		$togglehtml .= '<li><a data-pgw="1">'._('Full').'</a></li>';
	}
	$togglehtml .= '<li class="dropdown-header">'._('Scores').'</li>';
	$togglehtml .= '<li><a data-pts="0">'._('Points').'</a></li>';
	$togglehtml .= '<li><a data-pts="1">'._('Percents').'</a></li>';

	$togglehtml .= '<li class="dropdown-header">'. _('Links'). '</li>';
    $togglehtml .= '<li><a data-links="0">'. _('View/Edit'). '</a></li>';
    if ($courseUIver>1) {
        $togglehtml .= '<li><a data-links="1">'. _('Summary'). '</a></li>';
    } else {
        $togglehtml .= '<li><a data-links="1">'. _('Scores'). '</a></li>';
    }

	$togglehtml .= '<li class="dropdown-header">'. _('Pics'). '</li>';
	$togglehtml .= '<li><a data-pics="0">'. _('None'). '</a></li>';
	$togglehtml .= '<li><a data-pics="1">'. _('Small').'</a></li>';
	$togglehtml .= '<li><a data-pics="2">'. _('Big'). '</a></li>';

	if ($isteacher) {
		$togglehtml .= '<li class="dropdown-header">'. _('NewFlag'). '</li>';
		$togglehtml .= '<li><a data-newflag="0">'. _('Off'). '</a></li>';
		$togglehtml .= '<li><a data-newflag="1">'. _('On').'</a></li>';
	}
	$togglehtml .= '</ul></span>';
	$i++;

	if ($isteacher) {
		echo '<span class="dropdown">';
		echo ' <a tabindex=0 class="dropdown-toggle arrow-down" id="dropdownMenu'.$i.'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
		echo _('Offline Grades').'</a>';
		echo '<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu'.$i.'">';
		echo " <li><a href=\"addgrades.php?cid=$cid&gbitem=new&grades=all\">", _('Add'), "</a></li>";
		echo " <li><a href=\"chgoffline.php?cid=$cid\">", _('Manage'), "</a></li>";
		echo '</ul></span> &nbsp; ';
		$i++;

		echo '<a href="gb-export.php?cid='.$cid.'&export=true">'._('Export').'</a> &nbsp; ';
		echo "<a href=\"gbsettings.php?cid=$cid\">", _('Settings'), "</a> &nbsp; ";
		echo "<a href=\"gbcomments.php?cid=$cid&stu=0\">", _('Comments'), "</a> &nbsp; ";
		echo _('Color:'), ' <select id="colorsel" onchange="updateColors(this)">';
		echo '<option value="0">', _('None'), '</option>';
		for ($j=50;$j<90;$j+=($j<70?10:5)) {
			for ($k=$j+($j<70?10:5);$k<100;$k+=($k<70?10:5)) {
				echo "<option value=\"$j:$k\" ";
				if ("$j:$k"==$colorize) {
					echo 'selected="selected" ';
				}
				echo ">$j/$k</option>";
			}
		}
		echo '<option value="-1:-1" ';
		if ($colorize == "-1:-1") { echo 'selected="selected" ';}
		echo '>', _('Active'), '</option>';
		echo '</select> &nbsp; ';
		//echo ' | <a href="#" onclick="chgnewflag(); return false;">', _('NewFlag'), '</a>';
		//echo '<input type="button" value="Pics" onclick="rotatepics()" />';
		echo $togglehtml;
		echo "<br/>\n";

	}

	echo _('Category:'), ' <select id="filtersel" onchange="chgfilter()">';
	echo '<option value="-1" ';
	if ($catfilter==-1) {echo "selected=1";}
	echo '>', _('All'), '</option>';
	echo '<option value="0" ';
	if ($catfilter==0) { echo "selected=1";}
	echo '>', _('Default'), '</option>';
	$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid ORDER BY name");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		echo '<option value="'.Sanitize::encodeStringForDisplay($row[0]).'"';
		if ($catfilter==$row[0]) {echo "selected=1";}
		echo '>'.Sanitize::encodeStringForDisplay($row[1]).'</option>';
	}
	echo '<option value="-2" ';
	if ($catfilter==-2) {echo "selected=1";}
	echo '>', ('Category Totals'), '</option>';
	echo '</select> &nbsp; ';
	echo _('Not Counted:'), " <select id=\"hidenc\" onchange=\"chggbfilters()\">";
	echo "<option value=0 "; writeHtmlSelected($hidenc,0); echo ">", _('Show all'), "</option>";
	echo "<option value=1 "; writeHtmlSelected($hidenc,1); echo ">", _('Show stu view'), "</option>";
	echo "<option value=2 "; writeHtmlSelected($hidenc,2); echo ">", _('Hide all'), "</option>";
	echo "</select> &nbsp; ";
	echo _('Show:'), " <select id=\"availshow\" onchange=\"chggbfilters()\">";
	echo "<option value=0 "; writeHtmlSelected($availshow,0); echo ">", _('Past due'), "</option>";
	echo "<option value=3 "; writeHtmlSelected($availshow,3); echo ">", _('Past &amp; Attempted'), "</option>";
	echo "<option value=4 "; writeHtmlSelected($availshow,4); echo ">", _('Available Only'), "</option>";
	echo "<option value=1 "; writeHtmlSelected($availshow,1); echo ">", _('Past &amp; Available'), "</option>";
	echo "<option value=2 "; writeHtmlSelected($availshow,2); echo ">", _('All'), "</option></select> &nbsp; ";

	if (!$isteacher) {
		echo $togglehtml;
	}

	echo "</div>";
	echo '<script type="text/javascript">
	$(function() {
		$("a[data-hdrs='.($headerslocked?1:0).']").parent().addClass("active");
		$("a[data-pts='.($showpercents?1:0).']").parent().addClass("active");
		$("a[data-links='.Sanitize::onlyInt($links).']").parent().addClass("active");
		$("a[data-pics='.Sanitize::onlyInt($showpics).']").parent().addClass("active");
		$("a[data-newflag='.(($coursenewflag&1)==1?1:0).']").parent().addClass("active");
		$("a[data-pgw='.(empty($_COOKIE["gbfullw-$cid"])?0:1).']").parent().addClass("active");
		setupGBpercents();';
		if ($isteacher && $colorize != '0' && $colorize != null) {
			echo '$("#myTable").hide();';
			echo 'updateColors(document.getElementById("colorsel"));';
			echo '$("#myTable").show();';
		}
		echo 'ts.init();
	});
	</script>';

	if ($isteacher) {
		echo _('Check:'), ' <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',true)">', _('All'), '</a> <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',false)">', _('None'), '</a> ';
		echo '<span class="dropdown">';
		echo ' <a tabindex=0 class="dropdown-toggle arrow-down" id="dropdownMenuWithsel" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
		echo _('With Selected').'</a>';
		echo '<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenuWithsel">';
		echo ' <li><a href="#" onclick="postGBform(\'Message\');return false;" title="',_("Send a message to the selected students"),'">', _('Message'), "</a></li>";
		if (!isset($CFG['GEN']['noEmailButton'])) {
			echo ' <li><a href="#" onclick="postGBform(\'E-mail\');return false;" title="',_("Send e-mail to the selected students"),'">', _('E-mail'), "</a></li>";
		}
		echo ' <li><a href="#" onclick="copyemails();return false;" title="',_("Copy e-mail addresses of the selected students"),'">', _('Copy E-mails'), "</a></li>";
		echo ' <li><a href="#" onclick="postGBform(\'Make Exception\');return false;" title="',_("Make due date exceptions for selected students"),'">',_('Make Exception'), "</a></li>";
		echo ' <li><a href="#" onclick="postGBform(\'Print Report\');return false;" title="',_("Generate printable grade reports"),'">', _('Print Report'), "</a></li>";
		echo ' <li><a href="#" onclick="postGBform(\'Lock\');return false;" title="',_("Lock selected students out of the course"),'">', _('Lock'), "</a></li>";
		if (!isset($CFG['GEN']['noInstrUnenroll'])) {
			echo ' <li><a href="#" onclick="postGBform(\'Unenroll\');return false;" title="',_("Unenroll the selected students"),'">', _('Unenroll'), "</a></li>";
		}

		echo '</ul></span>';

		/*echo _('With Selected:'), '  <button type="submit" name="posted" value="Print Report" title="',_("Generate printable grade reports"),'">',_('Print Report'),'</button> ';
		if (!isset($CFG['GEN']['noEmailButton'])) {
			echo '<button type="submit" name="posted" value="E-mail" title="',_("Send e-mail to the selected students"),'">',_('E-mail'),'</button> ';
		}
		echo '<button type="button" name="posted" value="Copy E-mails" title="',_("Copy e-mail addresses of the selected students"),'" onclick="copyemails()">',_('Copy E-mails'),'</button> ';
		echo '<button type="submit" name="posted" value="Message" title="',_("Send a message to the selected students"),'">',_('Message'),'</button> ';

		if (!isset($CFG['GEN']['noInstrUnenroll'])) {
			echo '<button type="submit" name="posted" value="Unenroll" title="',_("Unenroll the selected students"),'">',_('Unenroll'),'</button> ';
		}
		echo '<button type="submit" name="posted" value="Lock" title="',_("Lock selected students out of the course"),'">',_('Lock'),'</button> ';
		echo '<button type="submit" name="posted" value="Make Exception" title="',_("Make due date exceptions for selected students"),'">',_('Make Exception'),'</button> ';
		*/
	}
	$includelastchange = false;  //don't need it for instructor view
	$gbt = gbinstrdisp();
	echo "</form>";
	echo "</div>";
	echo _('Meanings:  IP-In Progress (some unattempted questions), UA-Unsubmitted attempt, OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sup>*</sup> Has feedback, <sub>d</sub> Dropped score, <sup>x</sup> Excused score, <sup>e</sup> Has exception <sup>LP</sup> Used latepass'), "\n";
	require("../footer.php");

	/*if ($isteacher) {
		echo "<div class=cp>";
		echo "<a href=\"addgrades.php?cid=$cid&gbitem=new&grades=all\">Add Offline Grade</a><br/>";
		echo "<a href=\"gradebook.php?stu=$stu&cid=$cid&export=true\">Export Gradebook</a><br/>";
		echo "Email gradebook to <a href=\"gradebook.php?stu=$stu&cid=$cid&emailgb=me\">Me</a> or <a href=\"gradebook.php?stu=$stu&cid=$cid&emailgb=ask\">to another address</a><br/>";
		echo "<a href=\"gbsettings.php?cid=$cid\">Gradebook Settings</a>";
		echo "<div class=clear></div></div>";
	}
	*/
}

function gbstudisp($stu) {
	global $DBH,$CFG,$hidenc,$cid,$gbmode,$availshow,$isteacher,$istutor,$catfilter,$imasroot,$canviewall,$urlmode;
	global $includeduedate, $includelastchange,$latepasshrs,$latepasses,$hidelocked,$exceptionfuncs;
	global $assessGbUrl, $assessUrl,$staticroot;

	if ($availshow==4) {
		$availshow=1;
		$hidepast = true;
	}
	$equivavailshow = $availshow;
	if ($availshow == 3) {
		$equivavailshow = 1; // treat past & attempted like past & available
	}

	$now = time();
	$hasoutcomes = false;
	if ($stu>0) {
		$stm = $DBH->prepare("SELECT showlatepass,latepasshrs,outcomes FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		list($showlatepass,$latepasshrs,$outcomedata) = $stm->fetch(PDO::FETCH_NUM);
		if ($outcomedata!='') {
			$outcomes = unserialize($outcomedata);
			if (is_array($outcomes) && count($outcomes)>0) {
				$hasoutcomes = true;
			}
		}
		$query = "SELECT imas_students.gbcomment,imas_users.email,imas_students.latepass,imas_students.section,imas_students.lastaccess FROM imas_students,imas_users WHERE ";
		$query .= "imas_students.userid=imas_users.id AND imas_users.id=:id AND imas_students.courseid=:courseid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>$stu, ':courseid'=>$_GET['cid']));
		if ($stm->rowCount()==0) { //shouldn't happen
			echo 'Invalid student id';
			require("../footer.php");
			exit;
		}
		list($gbcomment,$stuemail,$latepasses,$stusection,$lastaccess) = $stm->fetch(PDO::FETCH_NUM);
	}
	$curdir = rtrim(dirname(__FILE__), '/\\');

	$gbt = gbtable($stu);

	if ($stu>0) {
		echo '<div style="font-size:1.1em;font-weight:bold">';
		if ($isteacher || $istutor) {
			if ($gbt[1][0][1] != '') {
				$stm = $DBH->prepare("SELECT usersort FROM imas_gbscheme WHERE courseid=:courseid");
				$stm->execute(array(':courseid'=>$cid));
				$usersort = $stm->fetchColumn(0);
			} else {
				$usersort = 1;
			}

			if ($gbt[1][4][2]==1) {
				if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
					echo "<img src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm{$gbt[1][4][0]}.jpg\" onclick=\"togglepic(this)\" class=\"mida\" alt=\"User picture\"/> ";
				} else {
					echo "<img src=\"$imasroot/course/files/userimg_sm{$gbt[1][4][0]}.jpg\" style=\"float: left; padding-right:5px;\" onclick=\"togglepic(this)\" class=\"mida\" alt=\"User picture\"/>";
				}
			}
			$query = "SELECT iu.id,iu.FirstName,iu.LastName,istu.section FROM imas_users AS iu JOIN imas_students as istu ON iu.id=istu.userid WHERE istu.courseid=:courseid ";
			if ($hidelocked) {
				$query .= "AND istu.locked=0 ";
			}
			if ($usersort==0) {
				$query .= "ORDER BY istu.section,iu.LastName,iu.FirstName";
			} else {
				$query .= "ORDER BY iu.LastName,iu.FirstName";
			}
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid));

			echo '<select id="userselect" style="border:0;font-size:1.1em;font-weight:bold" onchange="chgstu(this)">';
			$lastsec = '';
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if ($row[3]!='' && $row[3]!=$lastsec && $usersort==0) {
					if ($lastsec=='') {echo '</optgroup>';}
					echo '<optgroup label="Section '.Sanitize::encodeStringForDisplay($row[3]).'">';
					$lastsec = $row[3];
				}
				echo '<option value="'.Sanitize::encodeStringForDisplay($row[0]).'"';
				if ($row[0]==$stu) {
					echo ' selected="selected"';
				}
				echo '>'.Sanitize::encodeStringForDisplay($row[2]).', '.Sanitize::encodeStringForDisplay($row[1]).'</option>';
			}
			if ($lastsec!='') {echo '</optgroup>';}
			echo '</select>';
			echo '<img id="updatingicon" style="display:none" src="'.$staticroot.'/img/updating.gif" alt="Updating..."/>';
			echo ' <span class="small">('.Sanitize::encodeStringForDisplay($gbt[1][0][1]).')</span>';
		} else {
			echo Sanitize::encodeStringForDisplay($gbt[1][0][0]) . ' <span class="small">('.Sanitize::encodeStringForDisplay($gbt[1][0][1]).')</span>';

			$now = time();
		}

		if ($stusection!='') {
			echo ' <span class="small">Section: '.Sanitize::encodeStringForDisplay($stusection).'.</span>';
		}
		$logindate = ($lastaccess>0)?tzdate('D n/j/y g:ia', $lastaccess):_('Never');
		echo ' <span class="small">'._('Last Login: ').$logindate.'.</span>';
		echo '</div>';
		if ($isteacher) {
			echo '<div style="clear:both;display:inline-block" class="cpmid secondary">';
			//echo '<a href="mailto:'.$stuemail.'">', _('Email'), '</a> | ';
			if (!isset($CFG['GEN']['noEmailButton'])) {
				echo "<a href=\"#\" onclick=\"GB_show('Send Email','$imasroot/course/sendmsgmodal.php?to=" . Sanitize::onlyInt($stu) . "&sendtype=email&cid=" . Sanitize::courseId($cid) . "',800,'auto')\" title=\"Send Email\">", _('Email'), "</a> | ";
			} else if ($stuemail != '' && $stuemail != 'none@none.com' &&
				substr($stuemail,0,7) !== 'BOUNCED' && filter_var($stuemail, FILTER_VALIDATE_EMAIL)
			) {
				echo '<a href="mailto:'.Sanitize::emailAddress($stuemail).'">', _('Email'), '</a> | ';
			}

			//echo "<a href=\"$imasroot/msgs/msglist.php?cid={$_GET['cid']}&add=new&to=$stu\">", _('Message'), "</a> | ";
			echo "<a href=\"#\" onclick=\"GB_show('Send Message','$imasroot/course/sendmsgmodal.php?to=" . Sanitize::onlyInt($stu) . "&sendtype=msg&cid=" . Sanitize::courseId($cid) . "',800,'auto')\" title=\"Send Message\">", _('Message'), "</a> | ";
			//remove since redundant with Make Exception button "with selected"
			//echo "<a href=\"gradebook.php?cid={$_GET['cid']}&uid=$stu&massexception=1\">", _('Make Exception'), "</a> | ";
			echo "<a href=\"listusers.php?cid=" . Sanitize::courseId($cid) . "&chgstuinfo=true&uid=" . Sanitize::onlyInt($stu) . "\">", _('Change Info'), "</a> | ";
			echo "<a href=\"viewloginlog.php?cid=" . Sanitize::courseId($cid) . "&uid=" . Sanitize::onlyInt($stu) . "&from=gb\">", _('Login Log'), "</a> | ";
			echo "<a href=\"viewactionlog.php?cid=" . Sanitize::courseId($cid) . "&uid=" . Sanitize::onlyInt($stu) . "&from=gb\">", _('Activity Log'), "</a> | ";
			echo "<a href=\"#\" onclick=\"makeofflineeditable(this); return false;\">", _('Edit Offline Scores'), "</a>";
			echo '</div>';
		} else if ($istutor) {
			echo '<div style="clear:both;display:inline-block" class="cpmid">';
			echo "<a href=\"viewloginlog.php?cid=" . Sanitize::courseId($cid) . "&uid=" . Sanitize::onlyInt($stu) . "&from=gb\">", _('Login Log'), "</a> | ";
			echo "<a href=\"viewactionlog.php?cid=" . Sanitize::courseId($cid) . "&uid=" . Sanitize::onlyInt($stu) . "&from=gb\">", _('Activity Log'), "</a>";
			echo '</div>';
		}

		if (trim($gbcomment)!='' || $isteacher) {
			if ($isteacher) {
				echo "<form method=post action=\"gradebook.php?".Sanitize::encodeStringForDisplay($_SERVER['QUERY_STRING'])."\">";
				echo _('Gradebook Comment').': '.  "<input type=submit value=\"", _('Update Comment'), "\"><br/>";
				echo "<textarea name=\"usrcomments\" rows=3 cols=60>" . Sanitize::encodeStringForDisplay($gbcomment, true) . "</textarea>";
				echo '</form>';
			} else {
				echo "<div style=\"clear:both;display:inline-block\" class=\"cpmid\">" . Sanitize::encodeStringForDisplay($gbcomment) . "</div><br/>";
			}
		}
		if ($showlatepass==1) {
			if ($latepasses==0) {
				$lpmsg = _('No LatePasses available');
			} else if ($latepasses>1) {
				$lpmsg = sprintf(_('%d LatePasses available'), $latepasses);
			} else {
				$lpmsg = _('One LatePass available');
			}
			if ($isteacher || $istutor) {echo '<br/>';}
		}
		if (!$isteacher && !$istutor) {
			echo Sanitize::encodeStringForDisplay($lpmsg);
		}

	}
	echo "<form method=\"post\" id=\"qform\" action=\"gradebook.php?".Sanitize::fullQueryString($_SERVER['QUERY_STRING'])."&uid=" . Sanitize::onlyInt($stu) . "\">";
	//echo "<input type='button' onclick='conditionalColor(\"myTable\",1,50,80);' value='Color'/>";
	if ($isteacher && $stu>0) {
		echo '<button type="submit" value="Save Changes" style="display:none"; id="savechgbtn">', _('Save Changes'), '</button> ';
		echo _('Check:'), ' <a href="#" onclick="return chkAllNone(\'qform\',\'assesschk[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'assesschk[]\',false)">', _('None'), '</a> ';
		echo _('With selected:');
		echo ' <button type="submit" value="Make Exception" name="posted">',_('Make Exception'),'</button> ';
		echo ' <button type="submit" value="Excuse Grade" name="posted" onclick="return confirm(\'Are you sure you want to excuse these grades?\')">',_('Excuse Grade'),'</button> ';
		echo ' <button type="submit" value="Un-excuse Grade" name="posted" onclick="return confirm(\'Are you sure you want to un-excuse these grades?\')">',_('Un-excuse Grade'),'</button> '.Sanitize::encodeStringForDisplay($lpmsg).'';
	}
	echo '<table id="myTable" class="gb" style="position:relative;">';
	echo '<thead><tr>';
	$sarr = array();
	if ($stu>0 && $isteacher) {
		echo '<th></th>';
	}
	echo '<th>', _('Item'), '</th><th>', _('Percent'), '</th><th>', _('Grade'), '</th><th>', _('Possible'), '</th>';
	if ($stu>0 && $isteacher) {
		echo '<th>', _('Time Spent (In Questions)'), '</th>';
		$sarr = "false,'S','N','N','N','N'";
		if ($includelastchange) {
			echo '<th>'._('Last Changed').'</th>';
			$sarr .= ",'D'";
		}
	} else if ($stu==-1) {
		echo '<th>', _('Time Spent (In Questions)'), '</th>';
		$sarr = "'S','N','N','N','N'";
	} else {
		$sarr = "'S','N','N','N'";
	}
	if ($stu>0) {
		if ($includeduedate) {
			echo '<th>'._('Due Date').'</th>';
			$sarr .= ",'D'";
		}
		echo '<th>', _('Feedback');
		echo '<br/>';
		echo '<a href="#" class="small feedbacksh pointer" onclick="return showfb('.Sanitize::onlyInt($stu).',\'all\')">', _('View All'), '</a>';
		echo '</th>';
		$sarr .= ",'N'";
	}
	echo '</tr></thead><tbody>';
	if ($catfilter>-2) {
		for ($i=0;$i<count($gbt[0][1]);$i++) { //assessment headers
			if (!$isteacher && !$istutor && $gbt[0][1][$i][4]==0) { //skip if hidden
				continue;
			}
			if ($hidenc==1 && $gbt[0][1][$i][4]==0) { //skip NC
				continue;
			} else if ($hidenc==2 && ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3)) {//skip all NC
				continue;
			}
			if ($gbt[0][1][$i][3]>$equivavailshow) {
				continue;
			}
			if ($hidepast && $gbt[0][1][$i][3]==0) {
				continue;
			}

			echo '<tr class="grid">';
			if ($stu>0 && $isteacher) {
				if ($gbt[0][1][$i][6]==0) {
					echo '<td><input type="checkbox" name="assesschk[]" value="'.$gbt[0][1][$i][7] .'" /></td>';
				} else if ($gbt[0][1][$i][6]==1) {
					echo '<td><input type="checkbox" name="offlinechk[]" value="'.$gbt[0][1][$i][7] .'" /></td>';
				} else if ($gbt[0][1][$i][6]==2) {
					echo '<td><input type="checkbox" name="discusschk[]" value="'.$gbt[0][1][$i][7] .'" /></td>';
				} else if ($gbt[0][1][$i][6]==3) {
					echo '<td><input type="checkbox" name="exttoolchk[]" value="'.$gbt[0][1][$i][7] .'" /></td>';
				} else {
					echo '<td></td>';
				}
			}
			echo '<td class="cat'.Sanitize::onlyInt(($gbt[0][1][$i][1]%10)).'" scope="row">';

			$showlink = false;
			if ($gbt[0][1][$i][6]==0 && $gbt[0][1][$i][3]==1 && $gbt[1][1][$i][13]==1 && !$isteacher && !$istutor) {
				$showlink = true;
				if ($gbt[0][1][$i][15] > 1) {
					echo '<a href="'.$assessUrl.'?cid='.$cid.'&aid='.$gbt[0][1][$i][7].'"';
				} else {
					echo '<a href="../assessment/showtest.php?cid='.$cid.'&id='.$gbt[0][1][$i][7].'"';
				}
				if (abs($gbt[0][1][$i][13])>0 && $gbt[0][1][$i][15]==1) {
					$tlwrds = '';
					$timelimit = abs($gbt[0][1][$i][13])*$gbt[1][4][4];
					if ($timelimit>3600) {
						$tlhrs = floor($timelimit/3600);
						$tlrem = $timelimit % 3600;
						$tlmin = floor($tlrem/60);
						$tlsec = $tlrem % 60;
						$tlwrds = "$tlhrs " . _('hour');
						if ($tlhrs > 1) { $tlwrds .= "s";}
						if ($tlmin > 0) { $tlwrds .= ", $tlmin " . _('minute');}
						if ($tlmin > 1) { $tlwrds .= "s";}
						if ($tlsec > 0) { $tlwrds .= ", $tlsec " . _('second');}
						if ($tlsec > 1) { $tlwrds .= "s";}
					} else if ($timelimit>60) {
						$tlmin = floor($timelimit/60);
						$tlsec = $timelimit % 60;
						$tlwrds = "$tlmin " . _('minute');
						if ($tlmin > 1) { $tlwrds .= "s";}
						if ($tlsec > 0) { $tlwrds .= ", $tlsec " . _('second');}
						if ($tlsec > 1) { $tlwrds .= "s";}
					} else {
						$tlwrds = $timelimit . _(' second(s)');
					}
					if ($timelimit > $gbt[0][1][$i][11] - $now) {
						echo " onclick='return confirm(\"", sprintf(_('This assessment has a time limit of %s, but that will be restricted by the upcoming due date. Click OK to start or continue working on the assessment.'), $tlwrds), "\")' ";
					} else {
						echo " onclick='return confirm(\"", sprintf(_('This assessment has a time limit of %s.  Click OK to start or continue working on the assessment.'), $tlwrds), "\")' ";
					}
				}
				echo '>';
			}
			echo Sanitize::encodeStringForDisplay($gbt[0][1][$i][0]);
			if ($showlink) {
				echo '</a>';
			}
			$afterduelatepass = false;
			if (!$isteacher && !$istutor && $latepasses>0 && !isset($gbt[1][1][$i][10])) {
				//not started, so no canuselatepass record
				$gbt[1][1][$i][10] = $exceptionfuncs->getCanUseAssessLatePass(array('enddate'=>$gbt[0][1][$i][11], 'allowlate'=>$gbt[0][1][$i][12], 'LPcutoff'=>$gbt[0][1][$i][14]));
			}
			/*if (!$isteacher && !$istutor && $latepasses>0  &&	(
				(isset($gbt[1][1][$i][10]) && $gbt[1][1][$i][10]>0 && !in_array($gbt[0][1][$i][7],$viewedassess)) ||  //started, and already figured it's ok
				(!isset($gbt[1][1][$i][10]) && $now<$gbt[0][1][$i][11]) || //not started, before due date
				(!isset($gbt[1][1][$i][10]) && $gbt[0][1][$i][12]>10 && $now-$gbt[0][1][$i][11]<$latepasshrs*3600 && !in_array($gbt[0][1][$i][7],$viewedassess)) //not started, within one latepass
			    )) {*/
			if (!$isteacher && !$istutor && $latepasses>0 && $gbt[1][1][$i][10]==true) { //if canuselatepass
				echo ' <span class="small"><a href="redeemlatepass.php?cid='.$cid.'&aid='.$gbt[0][1][$i][7].'&from=gb">[';
				echo _('Use LatePass').']</a></span>';
				if ($now>$gbt[0][1][$i][11]) {
					$afterduelatepass = true;
				}
			}
			if (!$isteacher && !$istutor && $gbt[1][1][$i][16] == 1) {
				echo ' <span class="small"><a href="'.$assessUrl.'?cid='.$cid.'&aid='.$gbt[0][1][$i][7].'#/showwork">[';
				echo _('Attach Work') .']</a></span>';
			}

			echo '</td>';

			echo '<td>';
			if (isset($gbt[1][1][$i][0]) && is_numeric($gbt[1][1][$i][0])) {
				if ($gbt[0][1][$i][2]>0) {
					echo round(100*$gbt[1][1][$i][0]/$gbt[0][1][$i][2],1).'%';
				}
			} else if (isset($gbt[1][1][$i][0]) && $gbt[1][1][$i][0] === 'N/A') {
				echo 'N/A';
			} else {
				echo '0%';
			}
			echo '</td>';

			echo '<td>';

			$haslink = false;

			if ($isteacher || $istutor || $gbt[1][1][$i][2]==1) { //show link
				if ($gbt[0][1][$i][6]==0) {//online
					if ($stu==-1) { //in averages
						if (isset($gbt[1][1][$i][0])) { //has score
							if ($gbt[0][1][$i][15] > 1) {
								echo "<a href=\"gb-itemanalysis2.php?stu=$stu&cid=$cid&aid={$gbt[0][1][$i][7]}\">";
							} else {
								echo "<a href=\"gb-itemanalysis.php?stu=$stu&cid=$cid&aid={$gbt[0][1][$i][7]}\">";
							}
							$haslink = true;
						}
					} else {
						if (isset($gbt[1][1][$i][0])) { //has score
							if ($gbt[0][1][$i][15] > 1) { // assess2
								$querymap = array(
	                                'stu' => $stu,
	                                'cid' => $cid,
	                                'aid' => $gbt[0][1][$i][7],
	                                'uid' => $gbt[1][4][0]
	                            );

								echo '<a href="'.$assessGbUrl.'?' . Sanitize::generateQueryStringFromMap($querymap) . "\"";
							} else {
								$querymap = array(
	                                'stu' => $stu,
	                                'cid' => $cid,
	                                'asid' => $gbt[1][1][$i][4],
	                                'uid' => $gbt[1][4][0]
	                            );

								echo '<a href="gb-viewasid.php?' . Sanitize::generateQueryStringFromMap($querymap) . "\"";
							}
							if ($afterduelatepass) {
								echo ' onclick="return confirm(\''._('If you view this assignment, you will not be able to use a LatePass on it').'\');"';
							}
							echo ">";
							$haslink = true;
						} else if ($isteacher) {
							if ($gbt[0][1][$i][15] > 1) { // assess2
								$querymap = array(
                    'stu' => $stu,
                    'cid' => $cid,
                    'aid' => $gbt[0][1][$i][7],
                    'uid' => $gbt[1][4][0]
                );

								echo '<a href="'.$assessGbUrl.'?' . Sanitize::generateQueryStringFromMap($querymap) . "\">";
							} else {
                $querymap = array(
                    'stu' => $stu,
                    'cid' => $cid,
                    'aid' => $gbt[0][1][$i][7],
                    'uid' => $gbt[1][4][0],
                    'asid' => 'new'
                );

                echo '<a href="gb-viewasid.php?' . Sanitize::generateQueryStringFromMap($querymap) . "\">";
							}
							$haslink = true;
						}
					}
				} else if ($gbt[0][1][$i][6]==1) {//offline
					if ($isteacher || ($istutor && $gbt[0][1][$i][8]==1)) {
						if ($stu==-1) {
							if (isset($gbt[1][1][$i][0])) { //has score
                                $querymap = array(
                                    'stu' => $stu,
                                    'cid' => $cid,
                                    'grades' => 'all',
                                    'gbitem' => $gbt[0][1][$i][7]
                                );

                                echo '<a href="addgrades.php?' . Sanitize::generateQueryStringFromMap($querymap) . "\">";
								$haslink = true;
							}
						} else {
                            $querymap = array(
                                'stu' => $stu,
                                'cid' => $cid,
                                'grades' => $gbt[1][4][0],
                                'gbitem' => $gbt[0][1][$i][7]
                            );

                            echo '<a href="addgrades.php?' . Sanitize::generateQueryStringFromMap($querymap) . "\">";
							$haslink = true;
						}
					}
				} else if ($gbt[0][1][$i][6]==2) {//discuss
					if ($stu != -1) {
                        $querymap = array(
                            'cid' => $cid,
                            'stu' => $stu,
                            'uid' => $gbt[1][4][0],
                            'fid' => $gbt[0][1][$i][7]
                        );

                        echo '<a href="viewforumgrade.php?' . Sanitize::generateQueryStringFromMap($querymap) . "\">";
						$haslink = true;
					}
				} else if ($gbt[0][1][$i][6]==3) {//exttool
					if ($isteacher || ($istutor && $gbt[0][1][$i][8]==1)) {
					    $querymap = array(
					        'cid' => $cid,
                            'stu' => $stu,
                            'grades' => $gbt[1][4][0],
                            'lid' => $gbt[0][1][$i][7]
                        );

					    echo '<a href="edittoolscores.php?' . Sanitize::generateQueryStringFromMap($querymap) . "\">";
						$haslink = true;
					}
				}
			}
			if (isset($gbt[1][1][$i][0])) {
				if ($gbt[1][1][$i][3]>9) {
					$gbt[1][1][$i][3] -= 10;
				}
				echo $gbt[1][1][$i][0];
				if ($gbt[1][1][$i][3]==1) {
					echo ' (NC)';
				} else if ($gbt[1][1][$i][3]==2) {
					echo ' (IP)';
				} else if ($gbt[1][1][$i][3]==5) {
					echo ' (UA)';
				} else if ($gbt[1][1][$i][3]==3) {
					echo ' (OT)';
				} else if ($gbt[1][1][$i][3]==4) {
					echo ' (PT)';
				}
			} else {
				echo '-';
			}
			if ($haslink) { //show link
				echo '</a>';
			}
			$exceptionnote = '';
			if (isset($gbt[1][1][$i][6]) ) {  //($isteacher || $istutor) &&
				if ($gbt[1][1][$i][6]>1) {
					if ($gbt[1][1][$i][6]>2) {
						$exceptionnote = '<sup>LP ('.($gbt[1][1][$i][6]-1).')</sup>';
					} else {
						$exceptionnote = '<sup>LP</sup>';
					}
				} else {
					$exceptionnote = '<sup>e</sup>';
				}
				echo $exceptionnote;
			}
			if (!empty($gbt[1][1][$i][14])) { //excused
				echo '<sup>x</sup>';
			}
			if (isset($gbt[1][1][$i][5]) && ($gbt[1][1][$i][5]&(1<<$availshow)) && !$hidepast) {
				echo '<sub>d</sub>';
			}
			echo '</td>';

			echo '<td>';
			if ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3) {
				echo $gbt[0][1][$i][2].'&nbsp;', _('pts'), ' ', _('(Not Counted)');
			} else {
				echo $gbt[0][1][$i][2].'&nbsp;', _('pts');
				if ($gbt[0][1][$i][4]==2) {
					echo ' (EC)';
				}
			}
			if ($gbt[0][1][$i][5]==1 && $gbt[0][1][$i][6]==0) {
				echo ' (PT)';
			}
			echo '</td>';

			if ($stu>0 && $isteacher) {
				if ($gbt[1][1][$i][7] > -1) {
					echo '<td>'.$gbt[1][1][$i][7].' min ('.$gbt[1][1][$i][8].' min)</td>';
				} else {
					echo '<td></td>';
				}
				if ($includelastchange) {
					if ($gbt[1][1][$i][9]>0) {
						echo '<td>'.tzdate('n/j/y g:ia', $gbt[1][1][$i][9]);
					} else {
						echo '<td></td>';
					}
				}
			} else if ($stu==-1) {
				if (isset($gbt[1][1][$i][7]) && $gbt[1][1][$i][7]>-1) {
					echo '<td>'.$gbt[1][1][$i][7].' min ('.$gbt[1][1][$i][8].' min)</td>';
				} else {
					echo '<td></td>';
				}
			}
			if ($stu>0) {
				if ($includeduedate) {
					if ($gbt[0][1][$i][6]!=1 &&  //skip offline
						$gbt[0][1][$i][11]<2000000000 && $gbt[0][1][$i][11]>0) {
						echo '<td>'.tzdate('n/j/y g:ia',$gbt[0][1][$i][11]);
					} else {
						echo '<td>-';
					}
					echo $exceptionnote;
					echo '</td>';
				}
				if ($gbt[1][1][$i][1]==0 || (isset($gbt[1][1][$i][0]) && $gbt[1][1][$i][0]==='N/A')) { //no feedback
					echo '<td></td>';
				} else if ($gbt[0][1][$i][6]==0) { //online
					if ($gbt[0][1][$i][15]>1) { //assess2
						echo '<td><a href="#" onclick="return showfb('.Sanitize::onlyInt($gbt[0][1][$i][7]).',\'A2\','.Sanitize::onlyInt($gbt[1][4][0]).')">', _('View Feedback'), '</a></td>';
					} else {
						echo '<td><a href="#" onclick="return showfb('.Sanitize::onlyInt($gbt[1][1][$i][4]).',\'A\')">', _('View Feedback'), '</a></td>';
					}
				} else if ($gbt[0][1][$i][6]==1) { //offline
					echo '<td><a href="#" onclick="return showfb('.Sanitize::onlyInt($gbt[1][1][$i][2]).',\'O\')">', _('View Feedback'), '</a></td>';
				} else if ($gbt[0][1][$i][6]==3) { //exttool
					echo '<td><a href="#" onclick="return showfb('.Sanitize::onlyInt($gbt[1][1][$i][2]).',\'E\')">', _('View Feedback'), '</a></td>';
				} else if ($gbt[0][1][$i][6]==2) { //forum
					echo '<td><a href="#" onclick="return showfb('.Sanitize::onlyInt($gbt[0][1][$i][7]).',\'F\','.Sanitize::onlyInt($gbt[1][4][0]).')">', _('View Feedback'), '</a></td>';
				}
			}
			echo '</tr>';
		}
	}
	echo '</tbody></table><br/>';
	if (!$hidepast) {
		$stm = $DBH->prepare("SELECT stugbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>Sanitize::courseId($cid)));
		$show = $stm->fetchColumn(0);
		//echo '</tbody></table><br/>';

		echo '<table class="gb"><thead>';
		echo '<tr>';
		echo '<th >', _('Totals'), '</th>';
		if (($show&1)==1) {
			echo '<th>', _('Past Due'), '</th>';
		}
		if (($show&2)==2) {
			echo '<th>', _('Past Due and Attempted'), '</th>';
		}
		if (($show&4)==4) {
			echo '<th>', _('Past Due and Available'), '</th>';
		}
		if (($show&8)==8) {
			echo '<th>', _('All'), '</th>';
		}
		echo '</tr>';
		echo '</thead><tbody>';
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			//$donedbltop = false;
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers
				if ($equivavailshow<2 && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				//if (!$donedbltop) {
				//	echo '<tr class="grid dbltop">';
				//	$donedbltop = true;
				//} else {
					echo '<tr class="grid">';
				//}
				echo '<td class="cat'.Sanitize::onlyFloat($gbt[0][2][$i][1]%10).'"><span class="cattothdr">'.Sanitize::encodeStringForDisplay($gbt[0][2][$i][0]).'</span>';
				if (isset($gbt[0][2][$i][11])) {  //category weight
					echo ' ('.Sanitize::onlyFloat($gbt[0][2][$i][11]).'%)';
				}
				echo '</td>';
				if (($show&1)==1) { //past
					echo '<td>';
					//show points in points-based mode
					if ($gbt[1][2][$i][4] == 0) {
						echo 'N/A';
					} else {
						if ($gbt[0][4][0]==0) {
							echo Sanitize::onlyFloat($gbt[1][2][$i][0]).'/'.Sanitize::onlyFloat($gbt[1][2][$i][4]).' (';
						}
						echo round(100*$gbt[1][2][$i][0]/$gbt[1][2][$i][4],1).'%';

						if ($gbt[0][4][0]==0) {
							echo ')';
						} else if ($gbt[0][2][$i][13]==0) { //if not points-based and not averaged percents
							echo ' ('.Sanitize::onlyFloat($gbt[1][2][$i][0]).'/'.Sanitize::onlyFloat($gbt[1][2][$i][4]).')';
						}
					}
					echo '</td>';
				}
				if (($show&2)==2) { //past and attempted
					echo '<td>';
					//show points in points-based mode
					if ($gbt[1][2][$i][7] == 0) {
						echo 'N/A';
					} else {
						if ($gbt[0][4][0]==0) {
							echo Sanitize::onlyFloat($gbt[1][2][$i][3]).'/'.Sanitize::onlyFloat($gbt[1][2][$i][7]).' (';
						}
						echo round(100*$gbt[1][2][$i][3]/$gbt[1][2][$i][7],1).'%';

						if ($gbt[0][4][0]==0) {
							echo ')';
						} else if ($gbt[0][2][$i][13]==0) { //if not points-based and not averaged percents
							echo ' ('.Sanitize::onlyFloat($gbt[1][2][$i][3]).'/'.Sanitize::onlyFloat($gbt[1][2][$i][7]).')';
						}
					}
					echo '</td>';
				}
				if (($show&4)==4) { //past and avail
					echo '<td>';
					if ($gbt[1][2][$i][5] == 0) {
						echo 'N/A';
					} else {
					//show points in points-based mode
						if ($gbt[0][4][0]==0) {
							echo Sanitize::onlyFloat($gbt[1][2][$i][1]).'/'.Sanitize::onlyFloat($gbt[1][2][$i][5]).' (';
						}
						echo round(100*$gbt[1][2][$i][1]/$gbt[1][2][$i][5],1).'%';

						if ($gbt[0][4][0]==0) {
							echo ')';
						} else if ($gbt[0][2][$i][13]==0) { //if not points-based and not averaged percents
							echo ' ('.Sanitize::onlyFloat($gbt[1][2][$i][1]).'/'.Sanitize::onlyFloat($gbt[1][2][$i][5]).')';
						}
					}
					echo '</td>';
				}
				if (($show&8)==8) { //all
					echo '<td>';
					if ($gbt[1][2][$i][6] == 0) {
						echo 'N/A';
					} else {
						//show points in points-based mode
						if ($gbt[0][4][0]==0) {
							echo Sanitize::onlyFloat($gbt[1][2][$i][2]).'/'.Sanitize::onlyFloat($gbt[1][2][$i][6]).' (';
						}
						echo round(100*$gbt[1][2][$i][2]/$gbt[1][2][$i][6],1).'%';

						if ($gbt[0][4][0]==0) {
							echo ')';
						} else if ($gbt[0][2][$i][13]==0) { //if not points-based and not averaged percents
							echo ' ('.Sanitize::onlyFloat($gbt[1][2][$i][2]).'/'.Sanitize::onlyFloat($gbt[1][2][$i][6]).')';
						}
					}
					echo '</td>';
				}

				echo '</tr>';
			}
		}
		//Totals
		if ($catfilter<0) {
			echo '<tr class="grid">';
			if ($gbt[0][4][0]==0) { //using points based
				echo '<td>', _('Total'), '</td>';
				if (($show&1)==1) {
					if ($gbt[1][3][4] > 0) {}
					$pct = ($gbt[1][3][4] > 0) ? round(100*$gbt[1][3][0]/$gbt[1][3][4], 1) : 0;
					echo '<td>'.Sanitize::onlyFloat($gbt[1][3][0]).'/'.Sanitize::onlyFloat($gbt[1][3][4]).' ('.$pct.'%)</td>';
				}
				if (($show&2)==2) {
					$pct = ($gbt[1][3][7] > 0) ? round(100*$gbt[1][3][3]/$gbt[1][3][7], 1) : 0;
					echo '<td>'.Sanitize::onlyFloat($gbt[1][3][3]).'/'.Sanitize::onlyFloat($gbt[1][3][7]).' ('.$pct.'%)</td>';
				}
				if (($show&4)==4) {
					$pct = ($gbt[1][3][5] > 0) ? round(100*$gbt[1][3][1]/$gbt[1][3][5], 1) : 0;
					echo '<td>'.Sanitize::onlyFloat($gbt[1][3][1]).'/'.Sanitize::onlyFloat($gbt[1][3][5]).' ('.$pct.'%)</td>';
				}
				if (($show&8)==8) {
					$pct = ($gbt[1][3][6] > 0) ? round(100*$gbt[1][3][2]/$gbt[1][3][6], 1) : 0;
					echo '<td>'.Sanitize::onlyFloat($gbt[1][3][2]).'/'.Sanitize::onlyFloat($gbt[1][3][6]).' ('.$pct.'%)</td>';
				}

			} else {
				echo '<td>', _('Weighted Total'), '</td>';
				if (($show&1)==1) {
					echo '<td>'.(($gbt[1][3][4] > 0) ? round(100*$gbt[1][3][0]/$gbt[1][3][4], 1) : 0) .'%</td>';
				}
				if (($show&2)==2) {
					echo '<td>'.(($gbt[1][3][7] > 0) ? round(100*$gbt[1][3][3]/$gbt[1][3][7], 1) : 0) .'%</td>';
				}
				if (($show&4)==4) {
					echo '<td>'.(($gbt[1][3][5] > 0) ? round(100*$gbt[1][3][1]/$gbt[1][3][5], 1) : 0) .'%</td>';
				}
				if (($show&8)==8) {
					echo '<td>'.(($gbt[1][3][6] > 0) ? round(100*$gbt[1][3][2]/$gbt[1][3][6], 1) : 0) .'%</td>';
				}
			}
			echo '</tr>';

		}
		echo '</tbody></table><br/>';
		echo '<dl class="inlinedl">';
		$outcometype = 0;
		if (($show&1)==1) {
			echo _('<dt>Past Due:</dt> <dd>total only includes items whose due date has passed.  Current assignments are not counted in this total.'), '</dd><br/>';
		}
		if (($show&2)==2) {
			echo _('<dt>Past Due and Attempted:</dt> <dd> total includes items whose due date has passed, as well as currently available items you have started working on.'), '</dd><br/>';
			$outcometype = 1;
		}
		if (($show&4)==4) {
			echo _('<dt>Past Due and Available:</dt> <dd> total includes items whose due date has passed as well as currently available items, even if you haven\'t starting working on them yet.'), '</dd><br/>';
		}
		if (($show&8)==8) {
			echo _('<dt>All:</dt> <dd> total includes all items: past, current, and future to-be-done items.'), '</dd><br/>';
		}
		echo '</dl>';
		if ($hasoutcomes) {
			echo '<p>';
			echo '<a href="outcomereport.php?' . Sanitize::generateQueryStringFromMap(array('stu' => $stu,
					'report' => 'outstu', 'cid' => $cid, 'type' => $outcometype)) . '">';
			echo _('View Outcome Report');
			echo '</a>';
			echo '</p>';
		}
	}

	if ($hidepast && $isteacher && $stu>0) {
		echo '<p><button type="submit" value="Save Changes" style="display:none"; id="savechgbtn">', _('Save Changes'), '</button>';
		echo '<button type="submit" value="Make Exception" name="massexception" >', _('Make Exception'), '</button> ', _('for selected assessments'), '</p>';
	}

	echo "</form>";

	echo "<script>initSortTable('myTable',Array($sarr),false);</script>\n";

}

function gbInstrCatHdrs(&$gbt, &$collapsegbcat) {
	global $catfilter, $availshow, $totonleft, $cid;

	$n = 0;
	$tots = '';
	if ($catfilter<0) {
		if ($gbt[0][4][0]==0) { //using points based
			if ($availshow<3) {
				$tots .= '<th><div><span class="cattothdr">'. _('Total'). '<br/>'.$gbt[0][3][$availshow].'&nbsp;'. _('pts'). '</span></div></th>';
			} else {
				$tots .= '<th><div><span class="cattothdr">'. _('Total').'</span></div></th>';
			}
			$tots .= '<th><div>%</div></th>';
			$n+=2;
		} else {
			$tots .= '<th><div><span class="cattothdr">'. _('Weighted Total %'). '</span></div></th>';
			$n++;
		}
	}
	if ($totonleft) {
		echo $tots;
	}
	if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
		for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers
			if (($availshow<2 || $availshow==3) && $gbt[0][2][$i][2]>1) {
				continue;
			} else if ($availshow==2 && $gbt[0][2][$i][2]==3) { //don't show future in cur view
				continue;
			} else if ($availshow==0 && $gbt[0][2][$i][2]==1) { //don't show cur in past view
				continue;
			}
			echo '<th class="cat'.($gbt[0][2][$i][1]%10).'"';
			if ($gbt[0][4][0]==0) { //using points based
				echo ' data-pts="'.$gbt[0][2][$i][3+$availshow].'"';
			}
			echo '><div><span class="cattothdr">';
			if ($availshow<3) {
				echo $gbt[0][2][$i][0].'<br/>';
				if ($gbt[0][4][0]==0) { //using points based
					echo $gbt[0][2][$i][3+$availshow].'&nbsp;', _('pts');
				} else {
					echo $gbt[0][2][$i][11].'%';
				}
			} else if ($availshow==3) { //past and attempted
				echo $gbt[0][2][$i][0];
				if (isset($gbt[0][2][$i][11])) {
					echo '<br/>'.$gbt[0][2][$i][11].'%';
				}
			}
			if ($collapsegbcat[$gbt[0][2][$i][1]]==0) {
				echo "<br/><a class=small href=\"gradebook.php?cid=$cid&amp;cat={$gbt[0][2][$i][10]}&amp;catcollapse=2\">", _('[Collapse]'), "</a>";
			} else {
				echo "<br/><a class=small href=\"gradebook.php?cid=$cid&amp;cat={$gbt[0][2][$i][10]}&amp;catcollapse=0\">", _('[Expand]'), "</a>";
			}
			echo '</span></div></th>';
			$n++;
		}
	}
	if (!$totonleft) {
		echo $tots;
	}
	return $n;
}
function gbInstrCatCols(&$gbt, $i, $insdiv, $enddiv) {
	global $catfilter, $availshow, $totonleft, $cid;

	//total totals
	$tot = '';
	if ($catfilter<0) {
		$fivenum = "<span onmouseover=\"tipshow(this,'". _('5-number summary:'). " {$gbt[0][3][3+$availshow]}')\" onmouseout=\"tipout()\" >";
		if ($gbt[$i][3][4+$availshow]>0) {
			$pct = round(100*$gbt[$i][3][$availshow]/$gbt[$i][3][4+$availshow],1);
		} else {
			$pct = 0;
		}
		if ($availshow==3 || $gbt[0][4][0]==0) { //attempted or using points based
			if ($gbt[$i][0][0]=='Averages') {
				if ($gbt[0][4][0]==0) { //using points based
					$tot .= '<td class="c">'.$insdiv.$pct.'%'.$enddiv .'</td>';
				}
				$tot .= '<td class="c">'.$insdiv.$fivenum.$pct.'%</span>'.$enddiv .'</td>';
			} else {
				if ($gbt[0][4][0]==0) { //using points based
					$tot .= '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].'/'.$gbt[$i][3][4+$availshow].$enddiv.'</td>';
					$tot .= '<td class="c">'.$insdiv.$pct .'%'.$enddiv .'</td>';

				} else {
					$tot .= '<td class="c">'.$insdiv.$pct.'%'.$enddiv .'</td>';
				}
			}
		} else {
			if ($gbt[0][4][0]==0) { //using points based
				$tot .= '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].$enddiv .'</td>';
				if ($gbt[$i][0][0]=='Averages') {
					$tot .= '<td class="c">'.$insdiv.$fivenum.$pct .'%</span>'.$enddiv .'</td>';
				} else {
					$tot .= '<td class="c">'.$insdiv.$pct .'%'.$enddiv .'</td>';
				}
			} else {
				if ($gbt[$i][0][0]=='Averages') {
					$tot .= '<td class="c">'.$insdiv.$fivenum.$pct.'%</span>'.$enddiv .'</td>';
				} else {
					$tot .= '<td class="c">'.$insdiv.$pct.'%'.$enddiv .'</td>';
				}
			}
		}
	}
	if ($totonleft) {
		echo $tot;
	}
	//category totals
	if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
		for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers
			if (($availshow<2 || $availshow==3) && $gbt[0][2][$j][2]>1) {
				continue;
			} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
				continue;
			} else if ($availshow==0 && $gbt[0][2][$j][2]==1) { //don't show cur in past view
				continue;
			}
			if ($gbt[$i][2][$j][4+$availshow]>0) {
				$pct = round(100*$gbt[$i][2][$j][$availshow]/$gbt[$i][2][$j][4+$availshow],1);
			} else {
				$pct = $gbt[$i][2][$j][$availshow];
			}
			echo '<td class="c">'.$insdiv;
			if ($gbt[$i][0][0]=='Averages' && $gbt[0][2][$j][6+$availshow]!='') {
				echo "<span onmouseover=\"tipshow(this,'", _('5-number summary:'), " {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
			}
			if ($catfilter!=-1) { //single category view

				if ($gbt[$i][0][0]=='Averages') {
					if ($gbt[$i][2][$j][4+$availshow] == 0) {
						echo $gbt[$i][2][$j][$availshow].'%';
					} else {
						echo $gbt[$i][2][$j][$availshow];
					}
				} else if ($gbt[$i][2][$j][4+$availshow]>0) { //category total has points poss listed
					echo $gbt[$i][2][$j][$availshow].'/'.$gbt[$i][2][$j][4+$availshow].' ('.$pct.'%)';
				} else {
					echo $pct.'%';
				}

			} else {
				if ($availshow==3 || ($gbt[0][4][0]==0 && $gbt[0][2][$j][13]==0)) {  //attempted or points based w/o percent scaling
					if ($gbt[$i][0][0]=='Averages') {
						if ($gbt[$i][2][$j][4+$availshow] == 0) {
							echo $gbt[$i][2][$j][$availshow].'%';
						} else {
							echo $gbt[$i][2][$j][$availshow];
						}
					} else if ($gbt[0][2][$j][14]==true || $availshow==3) { //if has drops or attempted
						echo $gbt[$i][2][$j][$availshow].'/'.$gbt[$i][2][$j][4+$availshow];
					} else {
						echo $gbt[$i][2][$j][$availshow];
					}
				} else {
					echo $pct.'%';
				}
			}
			if ($gbt[$i][0][0]=='Averages' && $availshow!=3 && $gbt[0][2][$j][6+$availshow]!='') {
				echo '</span>';
			}
			echo $enddiv .'</td>';
		}
	}
	if (!$totonleft) {
		echo $tot;
	}
}

function gbinstrdisp() {
	global $DBH,$hidenc,$showpics,$isteacher,$istutor,$cid,$gbmode,$stu,$availshow,$catfilter,$secfilter,$totonleft,$imasroot,$isdiag,$tutorsection;
	global $avgontop,$hidelocked,$colorize,$urlmode,$overridecollapse,$includeduedate,$lastlogin,$hidesection,$hidecode,$showpercents;
	global $assessGbUrl;

	$curdir = rtrim(dirname(__FILE__), '/\\');
	if ($availshow==4) {
		$availshow=1;
		$hidepast = true;
	}
	$equivavailshow = $availshow;
	if ($availshow == 3) {
		$equivavailshow = 1; // treat past & attempted like past & available
	}
	$gbt = gbtable();

	if ($avgontop) {
		$avgrow = array_pop($gbt);
		array_splice($gbt,1,0,array($avgrow));
	}
	//print_r($gbt);
	//echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n"; in placeinhead
	echo "<div id=\"tbl-container\">";
	echo '<div id="bigcontmyTable"><div id="tblcontmyTable">';

	//echo '<div id="gbloading">'._('Loading...').'</div>';
	echo '<table class="gb" id="myTable"><thead><tr>';

	$sortarr = array();
	for ($i=0;$i<count($gbt[0][0]);$i++) { //biographical headers
		if ($i==1) {echo '<th><div>&nbsp;</div></th>'; $sortarr[] = 'false';} //for pics
		if ($i==1 && $gbt[0][0][1]!='ID') { continue;}
		if ($gbt[0][0][$i]=='Section' || $gbt[0][0][$i]=='Code' || $gbt[0][0][$i]=='Last Login') {
			echo '<th class="nocolorize"><div>';
		} else {
			echo '<th><div>';
		}
		echo $gbt[0][0][$i];
		if (($gbt[0][0][$i]=='Section' || ($isdiag && $i==4)) && (!$istutor || $tutorsection=='')) {
			echo "<br/><select id=\"secfiltersel\" onchange=\"chgsecfilter()\"><option value=\"-1\" ";
			if ($secfilter==-1) {echo  'selected=1';}
			echo  '>', _('All'), '</option>';
			$stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE courseid=:courseid ORDER BY section");
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if ($row[0]=='') { continue;}
				echo  "<option value=\"" . Sanitize::encodeStringForDisplay($row[0]) . "\" ";
				if ($row[0]==$secfilter) {
					echo  'selected=1';
				}
				echo  ">" . Sanitize::encodeStringForDisplay($row[0]) . "</option>";
			}
			echo  "</select>";

		} else if ($gbt[0][0][$i]=='Name') {
			echo '<br/><span class="small">N='.(count($gbt)-2).'</span><br/>';
			echo "<select id=\"lockedtoggle\" onchange=\"chglockedtoggle()\">";
			echo "<option value=0 "; writeHtmlSelected($hidelocked,0); echo ">", _('Show Locked'), "</option>";
			echo "<option value=2 "; writeHtmlSelected($hidelocked,2); echo ">", _('Hide Locked'), "</option>";
			echo "</select>";
		}
		echo '</div></th>';
		if ($gbt[0][0][$i]=='Last Login') {
			$sortarr[] = "'D'";
		} else if ($i != 1) {
			$sortarr[] = "'S'";
		}
	}
	$n=0;

	//get collapsed gb cat info
	if (count($gbt[0][2])>1) {

		$collapsegbcat = array();
		for ($i=0;$i<count($gbt[0][2]);$i++) {

			if (isset($overridecollapse[$gbt[0][2][$i][10]])) {
				$collapsegbcat[$gbt[0][2][$i][1]] = $overridecollapse[$gbt[0][2][$i][10]];
			} else {
				$collapsegbcat[$gbt[0][2][$i][1]] = $gbt[0][2][$i][12];
			}
		}
	}

	if ($totonleft && !$hidepast) {
		$n += gbInstrCatHdrs($gbt, $collapsegbcat);
	}
	if ($catfilter>-2) {
		for ($i=0;$i<count($gbt[0][1]);$i++) { //assessment headers
			if (!$isteacher && !$istutor && $gbt[0][1][$i][4]==0) { //skip if hidden
				continue;
			}
			if ($hidenc==1 && $gbt[0][1][$i][4]==0) { //skip NC
				continue;
			} else if ($hidenc==2 && ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3)) {//skip all NC
				continue;
			}
			if ($gbt[0][1][$i][3]>$equivavailshow) {
				continue;
			}
			if ($hidepast && $gbt[0][1][$i][3]==0) {
				continue;
			}
			if ($collapsegbcat[$gbt[0][1][$i][1]]==2) {
				continue;
			}
			//name and points
			echo '<th class="cat'.($gbt[0][1][$i][1]%10).'" data-pts="'.$gbt[0][1][$i][2].'">';
			echo '<div>'.$gbt[0][1][$i][0].'<br/>';
			if ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3) {
				echo $gbt[0][1][$i][2].'&nbsp;', _('pts'), ' ', _('(Not Counted)');
			} else {
				echo $gbt[0][1][$i][2].'&nbsp;', _('pts');
				if ($gbt[0][1][$i][4]==2) {
					echo ' (EC)';
				}
			}
			if ($gbt[0][1][$i][5]==1 && $gbt[0][1][$i][6]==0) {
				echo ' (PT)';
			}
			if ($includeduedate && $gbt[0][1][$i][11]<2000000000 && $gbt[0][1][$i][11]>0) {
				echo '<br/><span class="small">'.tzdate('n/j/y&\n\b\s\p;g:ia', $gbt[0][1][$i][11]).'</span>';
			}
			//links
			if ($gbt[0][1][$i][6]==0 ) { //online
				if ($isteacher) {
					if (!empty($gbt[0][1][$i][15]) && $gbt[0][1][$i][15]>1) {
						$addassess = 'addassessment2.php';
					} else {
						$addassess = 'addassessment.php';
					}
					echo "<br/><a class=small href=\"$addassess?id={$gbt[0][1][$i][7]}&amp;cid=$cid&amp;from=gb\">", _('[Settings]'), "</a>";
					echo "<br/><a class=small href=\"isolateassessgrade.php?cid=$cid&amp;aid={$gbt[0][1][$i][7]}\">", _('[Isolate]'), "</a>";
					if ($gbt[0][1][$i][10]==true) {
						echo "<br/><a class=small href=\"isolateassessbygroup.php?cid=$cid&amp;aid={$gbt[0][1][$i][7]}\">", _('[By Group]'), "</a>";
					}
				} else {
					echo "<br/><a class=small href=\"isolateassessgrade.php?cid=$cid&amp;aid={$gbt[0][1][$i][7]}\">", _('[Isolate]'), "</a>";
				}
			} else if ($gbt[0][1][$i][6]==1  && ($isteacher || ($istutor && $gbt[0][1][$i][8]==1))) { //offline
				if ($isteacher) {
					echo "<br/><a class=small href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades=all&amp;gbitem={$gbt[0][1][$i][7]}\">", _('[Settings]'), "</a>";
					echo "<br/><a class=small href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades=all&amp;gbitem={$gbt[0][1][$i][7]}&amp;isolate=true\">", _('[Isolate]'), "</a>";
				} else {
					echo "<br/><a class=small href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades=all&amp;gbitem={$gbt[0][1][$i][7]}&amp;isolate=true\">", _('[Scores]'), "</a>";
				}
			} else if ($gbt[0][1][$i][6]==2  && $isteacher) { //discussion
				echo "<br/><a class=small href=\"addforum.php?id={$gbt[0][1][$i][7]}&amp;cid=$cid&amp;from=gb\">", _('[Settings]'), "</a>";
			} else if ($gbt[0][1][$i][6]==3  && $isteacher) { //exttool
				echo "<br/><a class=small href=\"addlinkedtext.php?id={$gbt[0][1][$i][7]}&amp;cid=$cid&amp;from=gb\">", _('[Settings]'), "</a>";
				echo "<br/><a class=small href=\"edittoolscores.php?stu=$stu&amp;cid=$cid&amp;uid=all&amp;lid={$gbt[0][1][$i][7]}&amp;isolate=true\">", _('[Isolate]'), "</a>";
			}

			echo '</div></th>';
			$n++;
		}
	}
	if (!$totonleft && !$hidepast) {
		$n += gbInstrCatHdrs($gbt, $collapsegbcat);
	}
	echo '</tr></thead><tbody>';
	//create student rows
	if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
		$userimgbase = $urlmode."{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles";
	} else {
		$userimgbase = "$imasroot/course/files";
	}
	for ($i=1;$i<count($gbt);$i++) {
		if ($i==1) {$insdiv = "<div>";  $enddiv = "</div>";} else {$insdiv = ''; $enddiv = '';}
		if ($i%2!=0) {
			echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">";
		} else {
			echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">";
		}
		echo '<td class="locked" scope="row"><div class="trld">';
		if ($gbt[$i][0][0]!="Averages" && $isteacher) {
			echo "<input type=\"checkbox\" name='checked[]' value='{$gbt[$i][4][0]}' />&nbsp;";
		}
		echo "<a href=\"gradebook.php?cid=$cid&amp;stu={$gbt[$i][4][0]}\">";
		if ($gbt[$i][4][1]>0) {
			echo '<span class="greystrike">'.$gbt[$i][0][0].'</span>';
		} else {
			echo Sanitize::encodeStringForDisplay($gbt[$i][0][0]);
		}
		echo '</a>';
		if ($gbt[$i][4][3]==1) {
			echo '<sup>*</sup>';
		}
		echo '</div></td>';
		if ($showpics==1 && $gbt[$i][4][2]==1) { //file_exists("$curdir//files/userimg_sm{$gbt[$i][4][0]}.jpg")) {
			echo "<td>{$insdiv}<div class=\"trld\"><img src=\"$userimgbase/userimg_sm{$gbt[$i][4][0]}.jpg\" alt=\"User picture\"/></div></td>";
		} else if ($showpics==2 && $gbt[$i][4][2]==1) {
			echo "<td>{$insdiv}<div class=\"trld\"><img src=\"$userimgbase/userimg_{$gbt[$i][4][0]}.jpg\" alt=\"User picture\"/></div></td>";
		} else {
			echo '<td>'.$insdiv.'<div class="trld">&nbsp;</div></td>';
		}
		for ($j=($gbt[0][0][1]=='ID'?1:2);$j<count($gbt[0][0]);$j++) {
			echo '<td class="c">'.$insdiv.$gbt[$i][0][$j].$enddiv .'</td>';
		}

		if ($totonleft && !$hidepast) {
			gbInstrCatCols($gbt, $i, $insdiv, $enddiv);
		}
		//assessment values
		if ($catfilter>-2) {
			for ($j=0;$j<count($gbt[0][1]);$j++) {
				if (!$isteacher && !$istutor && $gbt[0][1][$j][4]==0) { //skip if hidden
					continue;
				}
				if ($hidenc==1 && $gbt[0][1][$j][4]==0) { //skip NC
					continue;
				} else if ($hidenc==2 && ($gbt[0][1][$j][4]==0 || $gbt[0][1][$j][4]==3)) {//skip all NC
					continue;
				}
				if ($gbt[0][1][$j][3]>$equivavailshow) {
					continue;
				}
				if ($hidepast && $gbt[0][1][$j][3]==0) {
					continue;
				}
				if ($collapsegbcat[$gbt[0][1][$j][1]]==2) {
					continue;
				}
				//if online, not average, and either score exists and active, or score doesn't exist and assess is current,
				if ($gbt[0][1][$j][6]==0 && $gbt[$i][1][$j][4]!='average' && ((isset($gbt[$i][1][$j][3]) && $gbt[$i][1][$j][3]>9) || (!isset($gbt[$i][1][$j][3]) && $gbt[0][1][$j][3]==1))) {
					echo '<td class="c isact">'.$insdiv;
				} else {
					echo '<td class="c">'.$insdiv;
				}
				if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5]&(1<<$availshow)) && !$hidepast) {
					echo '<span style="font-style:italic">';
				}
				if ($gbt[0][1][$j][6]==0) {//online
					if (isset($gbt[$i][1][$j][0])) {
						if ($gbt[$i][1][$j][4]=='average') {
							if ($gbt[0][1][$j][2]>0) {
								$avgtip = _('Mean:').' '.round(100*$gbt[$i][1][$j][0]/$gbt[0][1][$j][2],1).'%<br/>';
							} else {
								$avgtip = '';
							}
							$avgtip .= _('5-number summary:').' '.$gbt[0][1][$j][9];
							if ($gbt[0][1][$j][15] > 1) {
								echo "<a href=\"gb-itemanalysis2.php?stu=$stu&amp;cid=$cid&amp;asid={$gbt[$i][1][$j][4]}&amp;aid={$gbt[0][1][$j][7]}\" ";
							} else {
								echo "<a href=\"gb-itemanalysis.php?stu=$stu&amp;cid=$cid&amp;asid={$gbt[$i][1][$j][4]}&amp;aid={$gbt[0][1][$j][7]}\" ";
							}
							echo "onmouseover=\"tipshow(this,'$avgtip')\" onmouseout=\"tipout()\" ";
							echo ">";
						} else {
							if ($gbt[0][1][$j][15] > 1) { // assess2
								echo "<a href=\"$assessGbUrl?stu=$stu&amp;cid=$cid&amp;aid={$gbt[0][1][$j][7]}&amp;uid={$gbt[$i][4][0]}\">";
							} else {
								echo "<a href=\"gb-viewasid.php?stu=$stu&amp;cid=$cid&amp;asid={$gbt[$i][1][$j][4]}&amp;uid={$gbt[$i][4][0]}\">";
							}
						}

						echo $gbt[$i][1][$j][0];

						echo '</a>';

						if ($gbt[$i][1][$j][3]>9) {
							$gbt[$i][1][$j][3] -= 10;
						}
						if ($gbt[$i][1][$j][3]==1) {
							echo ' (NC)';
						} else if ($gbt[$i][1][$j][3]==2) {
							echo ' (IP)';
						} else if ($gbt[$i][1][$j][3]==5) {
							echo ' (UA)';
						} else if ($gbt[$i][1][$j][3]==3) {
							echo ' (OT)';
						} else if ($gbt[$i][1][$j][3]==4) {
							echo ' (PT)';
						}

						if ($gbt[$i][1][$j][1]==1) {
							echo '<sup>*</sup>';
						}

					} else { //no score
						if ($gbt[$i][0][0]=='Averages') {
							echo '-';
						} else if ($isteacher) {
							if ($gbt[0][1][$j][15] > 1) { // assess2
								echo "<a href=\"$assessGbUrl?stu=$stu&amp;cid=$cid&amp;aid={$gbt[0][1][$j][7]}&amp;uid={$gbt[$i][4][0]}\">-</a>";
							} else {
								echo "<a href=\"gb-viewasid.php?stu=$stu&amp;cid=$cid&amp;asid=new&amp;aid={$gbt[0][1][$j][7]}&amp;uid={$gbt[$i][4][0]}\">-</a>";
							}
						} else {
							echo '-';
						}
					}
					if (isset($gbt[$i][1][$j][6]) ) {
						if ($gbt[$i][1][$j][6]>1) {
							if ($gbt[$i][1][$j][6]>2) {
								echo '<sup>LP ('.($gbt[$i][1][$j][6]-1).')</sup>';
							} else {
								echo '<sup>LP</sup>';
							}
						} else {
							echo '<sup>e</sup>';
						}
					}
				} else if ($gbt[0][1][$j][6]==1) { //offline
					if ($isteacher) {
						if ($gbt[$i][0][0]=='Averages') {
							if ($gbt[0][1][$j][2]>0) {
								$avgtip = _('Mean:').' '.round(100*$gbt[$i][1][$j][0]/$gbt[0][1][$j][2],1).'%<br/>';
							} else {
								$avgtip = '';
							}
							$avgtip .= _('5-number summary:').' '.$gbt[0][1][$j][9];

							echo "<a href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades=all&amp;gbitem={$gbt[0][1][$j][7]}\" ";
							echo "onmouseover=\"tipshow(this,'$avgtip')\" onmouseout=\"tipout()\" ";
							echo ">";
						} else {
							echo "<a href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades={$gbt[$i][4][0]}&amp;gbitem={$gbt[0][1][$j][7]}\">";
						}
					} else if ($istutor && $gbt[0][1][$j][8]==1) {
						if ($gbt[$i][0][0]=='Averages') {
							echo "<a href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades=all&amp;gbitem={$gbt[0][1][$j][7]}\">";
						} else {
							echo "<a href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades={$gbt[$i][4][0]}&amp;gbitem={$gbt[0][1][$j][7]}\">";
						}
					}
					if (isset($gbt[$i][1][$j][0])) {
						echo $gbt[$i][1][$j][0];

						if ($gbt[$i][1][$j][3]==1) {
							echo ' (NC)';
						}
					} else {
						echo '-';
					}
					if ($isteacher || ($istutor && $gbt[0][1][$j][8]==1)) {
						echo '</a>';
					}
					if ($gbt[$i][1][$j][1]==1) {
						echo '<sup>*</sup>';
					}
				} else if ($gbt[0][1][$j][6]==2) { //discuss
					if (isset($gbt[$i][1][$j][0])) {
						if ( $gbt[$i][0][0]!='Averages') {
							echo "<a href=\"viewforumgrade.php?cid=$cid&amp;stu=$stu&amp;uid={$gbt[$i][4][0]}&amp;fid={$gbt[0][1][$j][7]}\">";
							echo $gbt[$i][1][$j][0];
							echo '</a>';
						} else {
							if ($gbt[0][1][$j][2]>0) {
								$avgtip = _('Mean:').' '.round(100*$gbt[$i][1][$j][0]/$gbt[0][1][$j][2],1).'%<br/>';
							} else {
								$avgtip = '';
							}
							$avgtip .= _('5-number summary:').' '.$gbt[0][1][$j][9];
							echo "<span onmouseover=\"tipshow(this,'$avgtip')\" onmouseout=\"tipout()\"> ";
							echo $gbt[$i][1][$j][0];

							echo '</span>';
						}
						if ($gbt[$i][1][$j][1]==1) {
							echo '<sup>*</sup>';
						}
					} else {
						if ($isteacher && $gbt[$i][0][0]!='Averages') {
							echo "<a href=\"viewforumgrade.php?cid=$cid&amp;stu=$stu&amp;uid={$gbt[$i][4][0]}&amp;fid={$gbt[0][1][$j][7]}\">-</a>";
						} else {
							echo '-';
						}
					}

				} else if ($gbt[0][1][$j][6]==3) { //exttool
					if ($isteacher) {
						if ($gbt[$i][0][0]=='Averages') {
							if ($gbt[0][1][$j][2]>0) {
								$avgtip = _('Mean:').' '.round(100*$gbt[$i][1][$j][0]/$gbt[0][1][$j][2],1).'%<br/>';
							} else {
								$avgtip = '';
							}
							$avgtip .= _('5-number summary:').' '.$gbt[0][1][$j][9];
							echo "<a href=\"edittoolscores.php?stu=$stu&amp;cid=$cid&amp;uid=all&amp;lid={$gbt[0][1][$j][7]}\" ";
							echo "onmouseover=\"tipshow(this,'$avgtip')\" onmouseout=\"tipout()\" ";
							echo ">";
						} else {
							echo "<a href=\"edittoolscores.php?stu=$stu&amp;cid=$cid&amp;uid={$gbt[$i][4][0]}&amp;lid={$gbt[0][1][$j][7]}\">";
						}
					} else if ($istutor && $gbt[0][1][$j][8]==1) {
						if ($gbt[$i][0][0]=='Averages') {
							echo "<a href=\"edittoolscores.php?stu=$stu&amp;cid=$cid&amp;uid=all&amp;lid={$gbt[0][1][$j][7]}\">";
						} else {
							echo "<a href=\"edittoolscores.php?stu=$stu&amp;cid=$cid&amp;uid={$gbt[$i][4][0]}&amp;lid={$gbt[0][1][$j][7]}\">";
						}
					}
					if (isset($gbt[$i][1][$j][0])) {
						echo $gbt[$i][1][$j][0];

						if ($gbt[$i][1][$j][3]==1) {
							echo ' (NC)';
						}
					} else {
						echo '-';
					}
					if ($isteacher || ($istutor && $gbt[0][1][$j][8]==1)) {
						echo '</a>';
					}
					if ($gbt[$i][1][$j][1]==1) {
						echo '<sup>*</sup>';
					}
				}
				if (!empty($gbt[$i][1][$j][14])) { //excused
					echo '<sup>x</sup>';
				}
				if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5]&(1<<$availshow)) && !$hidepast) {
					echo '<sub>d</sub></span>';
				}
				echo $enddiv .'</td>';
			}
		}
		if (!$totonleft && !$hidepast) {
			gbInstrCatCols($gbt, $i, $insdiv, $enddiv);
		}
		echo '</tr>';
	}
	echo "</tbody></table></div></div>";
	if ($n>0) {
		$sarr = array_merge($sortarr, array_fill(0,$n,"'N'"));
	} else {
		$sarr = $sortarr;
	}

	$sarr = implode(",",$sarr);
	if (count($gbt)<500) {
		if ($avgontop) {
			echo "<script>initSortTable('myTable',Array($sarr),true,true,false);</script>\n";
		} else {
			echo "<script>initSortTable('myTable',Array($sarr),true,false);</script>\n";
		}
	}

}

?>
