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

// TODO:




require("../validate.php");
$cid = $_GET['cid'];
if (isset($teacherid)) {
	$isteacher = true;
} 
if (isset($tutorid)) {
	$istutor = true;
}
if ($isteacher || $istutor) {
	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
		$sessiondata[$cid.'gbmode'] = $gbmode;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'gbmode'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		$query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$gbmode = mysql_result($result,0,0);
	}
	if (isset($_GET['catfilter'])) {
		$catfilter = $_GET['catfilter'];
		$sessiondata[$cid.'catfilter'] = $catfilter;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'catfilter'])) {
		$catfilter = $sessiondata[$cid.'catfilter'];
	} else {
		$catfilter = -1;
	}
	if (isset($tutorsection) && $tutorsection!='') {
		$secfilter = $tutorsection;
	} else {
		if (isset($_GET['secfilter'])) {
			$secfilter = $_GET['secfilter'];
			$sessiondata[$cid.'secfilter'] = $secfilter;
			writesessiondata();
		} else if (isset($sessiondata[$cid.'secfilter'])) {
			$secfilter = $sessiondata[$cid.'secfilter'];
		} else {
			$secfilter = -1;
		}
	}
	//Gbmode : Links NC Dates
	$showpics = floor($gbmode/10000)%10 ; //0 none, 1 small, 2 big
	$totonleft = floor($gbmode/1000)%10 ; //0 right, 1 left
	$links = floor($gbmode/100)%10; //0: view/edit, 1 q breakdown
	$hidenc = floor($gbmode/10)%10; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
	$availshow = $gbmode%10; //0: past, 1 past&cur, 2 all
	
	//

} else {
	$secfilter = -1;
	$catfilter = -1;
	$links = 0;
	$hidenc = 1;
	$availshow = 1;
	$showpics = 0;
	$totonleft = 0;
}

if (($isteacher || $istutor) && isset($_GET['stu'])) {
	$stu = $_GET['stu'];
} else {
	$stu = 0;
}

//HANDLE ANY POSTS
if ($isteacher) {
	if (isset($_GET['togglenewflag'])) {
		//recording a toggle.  Called via AHAH
		$query = "SELECT newflag FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$newflag = mysql_result($result,0,0);
		$newflag = $newflag ^ 1;  //XOR
		$query = "UPDATE imas_courses SET newflag = $newflag WHERE id='$cid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		if (($newflag&1)==1) {
			echo 'New';
		} 
		exit;
	}
	if ((isset($_POST['submit']) && ($_POST['submit']=="E-mail" || $_POST['submit']=="Message"))|| isset($_GET['masssend']))  {
		$calledfrom='gb';
		include("masssend.php");
	}
	if ((isset($_POST['submit']) && $_POST['submit']=="Make Exception") || isset($_GET['massexception'])) {
		$calledfrom='gb';
		include("massexception.php");
	}
	if ((isset($_POST['submit']) && $_POST['submit']=="Unenroll") || (isset($_GET['action']) && $_GET['action']=="unenroll" )) {
		$calledfrom='gb';
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		$curBreadcrumb .= "&gt; <a href=\"gradebook.php?cid=$cid\">Gradebook</a> &gt; Confirm Change";
		$pagetitle = "Unenroll Students";
		include("unenroll.php");
		include("../footer.php");
		exit;
	}	
}



//DISPLAY
require("gbtable2.php");
require("../includes/htmlutil.php");

$placeinhead = '';
if ($isteacher || $istutor) {
	$placeinhead .= "<script type=\"text/javascript\">";
	$placeinhead .= 'function chgfilter() { ';
	$placeinhead .= '       var cat = document.getElementById("filtersel").value; ';
	$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid";
	
	$placeinhead .= "       var toopen = '$address&catfilter=' + cat;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	if ($isteacher) { 
		$placeinhead .= 'function chgsecfilter() { ';
		$placeinhead .= '       var sec = document.getElementById("secfiltersel").value; ';
		$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid";
		
		$placeinhead .= "       var toopen = '$address&secfilter=' + sec;\n";
		$placeinhead .= "  	window.location = toopen; \n";
		$placeinhead .= "}\n";
		$placeinhead .= 'function chgnewflag() { ';
		$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid&togglenewflag=true";
		
		$placeinhead .= "       basicahah('$address','newflag','Recording...');\n";
		$placeinhead .= "}\n";
	}
	$placeinhead .= 'function chgtoggle() { ';
	$placeinhead .= "	var altgbmode = 10000*document.getElementById(\"toggle4\").value + 1000*$totonleft + 100*document.getElementById(\"toggle1\").value + 10*document.getElementById(\"toggle2\").value + 1*document.getElementById(\"toggle3\").value; ";
	$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid&gbmode=";
	$placeinhead .= "	var toopen = '$address' + altgbmode;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	if ($isteacher) {
		$placeinhead .= 'function chgexport() { ';
		$placeinhead .= "	var type = document.getElementById(\"exportsel\").value; ";
		$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gb-export.php?stu=$stu&cid=$cid&";
		$placeinhead .= "	var toopen = '$address';";
		$placeinhead .= "	if (type==1) { toopen = toopen+'export=true';}\n";
		$placeinhead .= "	if (type==2) { toopen = toopen+'emailgb=me';}\n";
		$placeinhead .= "	if (type==3) { toopen = toopen+'emailgb=ask';}\n";
		$placeinhead .= "	if (type==0) { return false;}\n";
		$placeinhead .= "  	window.location = toopen; \n";
		$placeinhead .= "}\n";
	}
	
	
	$placeinhead .= "</script>";
}

if (isset($studentid) || $stu!=0) { //show student view
	if (isset($studentid)) {
		$stu = $userid;
	}
	$pagetitle = "Gradebook";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
	
	require("../header.php");
	
	if (isset($_GET['from']) && $_GET['from']=="listusers") {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt Student Grade Detail</div>\n";
	} else if ($isteacher || $istutor) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; Student Detail</div>";
	} else {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; Gradebook</div>";
	}
	if ($stu==-1) {
		echo '<div id="headergradebook" class="pagetitle"><h2>Grade Book Averages </h2></div>';
	} else {
		echo '<div id="headergradebook" class="pagetitle"><h2>Grade Book Student Detail</h2></div>';
	}
	if ($isteacher || $istutor) {
		echo "<div class=cpmid>";
		echo 'Category: <select id="filtersel" onchange="chgfilter()">';
		echo '<option value="-1" ';
		if ($catfilter==-1) {echo "selected=1";}
		echo '>All</option>';
		echo '<option value="0" ';
		if ($catfilter==0) { echo "selected=1";}
		echo '>Default</option>';
		$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo '<option value="'.$row[0].'"';
			if ($catfilter==$row[0]) {echo "selected=1";}
			echo '>'.$row[1].'</option>';
		}
		echo '<option value="-2" ';
		if ($catfilter==-2) {echo "selected=1";}
		echo '>Category Totals</option>';
		echo '</select> | ';
		echo "Not Counted: <select id=\"toggle2\" onchange=\"chgtoggle()\">";
		echo "<option value=0 "; writeHtmlSelected($hidenc,0); echo ">Show all</option>";
		echo "<option value=1 "; writeHtmlSelected($hidenc,1); echo ">Show stu view</option>";
		echo "<option value=2 "; writeHtmlSelected($hidenc,2); echo ">Hide all</option>";
		echo "</select>";
		echo " | Show: <select id=\"toggle3\" onchange=\"chgtoggle()\">";
		echo "<option value=0 "; writeHtmlSelected($availshow,0); echo ">Past due</option>";
		echo "<option value=3 "; writeHtmlSelected($availshow,3); echo ">Current</option>";
		echo "<option value=1 "; writeHtmlSelected($availshow,1); echo ">Past & Current</option>";
		echo "<option value=2 "; writeHtmlSelected($availshow,2); echo ">All</option></select>";
		echo " | Links: <select id=\"toggle1\" onchange=\"chgtoggle()\">";
		echo "<option value=0 "; writeHtmlSelected($links,0); echo ">View/Edit</option>";
		echo "<option value=1 "; writeHtmlSelected($links,1); echo ">Scores</option></select>";
		echo "</div>";
	}
	
	gbstudisp($stu);
	echo "<p>Meanings:  IP-In Progress, OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sub>d</sub> Dropped score.  <sup>e</sup> Has exception/latepass  </p>\n";
	
	require("../footer.php");
	
} else { //show instructor view
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablescroller.js\"></script>\n";
	$placeinhead .= "<script type=\"text/javascript\">\n";
	$placeinhead .= 'var ts = new tablescroller("myTable",';
	if (isset($_COOKIE["gblhdr-$cid"]) && $_COOKIE["gblhdr-$cid"]==1) {
		$placeinhead .= 'true);';
	} else {
		$placeinhead .= 'false);';
	}
	$placeinhead .= "\nfunction lockcol() { \n";
	$placeinhead .= "var tog = ts.toggle(); ";
	$placeinhead .= "if (tog==1) { "; //going to locked
	$placeinhead .= "document.cookie = 'gblhdr-$cid=1';\n document.getElementById(\"lockbtn\").value = \"Unlock headers\"; ";
	$placeinhead .= "} else {";
	$placeinhead .= "document.cookie = 'gblhdr-$cid=0';\n document.getElementById(\"lockbtn\").value = \"Lock headers\"; ";
	//$placeinhead .= " var cont = document.getElementById(\"tbl-container\");\n";
	//$placeinhead .= " if (cont.style.overflow == \"auto\") {\n";
	//$placeinhead .= "   cont.style.height = \"auto\"; cont.style.overflow = \"visible\"; cont.style.border = \"0px\";";
	//$placeinhead .= "document.getElementById(\"myTable\").className = \"gb\"; document.cookie = 'gblhdr-$cid=0';";
	//$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Lock headers\"; } else {";
	//$placeinhead .= " cont.style.height = \"75%\"; cont.style.overflow = \"auto\"; cont.style.border = \"1px solid #000\";\n";
	//$placeinhead .= "document.getElementById(\"myTable\").className = \"gbl\"; document.cookie = 'gblhdr-$cid=1'; ";
	//$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Unlock headers\"; }";
	$placeinhead .= "}}\n ";
	$placeinhead .= 'function highlightrow(el) { el.setAttribute("lastclass",el.className); el.className = "highlight";}';
	$placeinhead .= 'function unhighlightrow(el) { el.className = el.getAttribute("lastclass");}';
	$placeinhead .= "function chkAll(frm, arr, mark) {  for (i = 0; i <= frm.elements.length; i++) {   try{     if(frm.elements[i].name == arr) {  frm.elements[i].checked = mark;     }   } catch(er) {}  }}";
	/*$placeinhead .= 'var picsize = 0;
		function rotatepics() {
			picsize = (picsize+1)%3;
			picshow(picsize);
		}
		function picshow(size) {
			if (size==0) {
				els = document.getElementById("myTable").getElementsByTagName("img");
				for (var i=0; i<els.length; i++) {
					els[i].style.display = "none";
				}
			} else {
				els = document.getElementById("myTable").getElementsByTagName("img");
				for (var i=0; i<els.length; i++) {
					els[i].style.display = "inline";
					if (els[i].getAttribute("src").match("userimg_sm")) {
						if (size==2) {
							els[i].setAttribute("src",els[i].getAttribute("src").replace("_sm","_"));
						}
					} else if (size==1) {
						els[i].setAttribute("src",els[i].getAttribute("src").replace("_","_sm"));
					}
				}
			}
		}';*/
	$placeinhead .= "</script>\n";
	$placeinhead .= "<style type=\"text/css\"> table.gb { margin: 0px; } </style>";
	
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; Gradebook</div>";
	echo "<form method=post action=\"gradebook.php?cid=$cid\">";
	
	echo '<div id="headergradebook" class="pagetitle"><h2>Gradebook <span class="red" id="newflag" style="font-size: 70%" >';
	if (($coursenewflag&1)==1) {
		echo 'New';
	}
	echo '</span></h2></div>';
	if ($isdiag) {
		echo "<a href=\"gb-testing.php?cid=$cid\">View diagnostic gradebook</a>";
	}
	echo "<div class=cpmid>";
	if ($isteacher) {
		echo "Offline Grades: <a href=\"addgrades.php?cid=$cid&gbitem=new&grades=all\">Add</a>, ";
		echo "<a href=\"chgoffline.php?cid=$cid\">Manage</a> | ";
		echo '<select id="exportsel" onchange="chgexport()">';
		echo '<option value="0">Export to...</option>';
		echo '<option value="1">... file</option>';
		echo '<option value="2">... my email</option>';
		echo '<option value="3">... other email</option></select> | ';
		//echo "Export to <a href=\"gb-export.php?stu=$stu&cid=$cid&export=true\">File</a>, ";
		//echo "<a href=\"gb-export.php?stu=$stu&cid=$cid&emailgb=me\">My Email</a>, or <a href=\"gb-export.php?stu=$stu&cid=$cid&emailgb=ask\">Other Email</a> | ";
		echo "<a href=\"gbsettings.php?cid=$cid\">GB Settings</a> | ";
		echo "<a href=\"gradebook.php?cid=$cid&stu=-1\">Averages</a> | ";
		echo "<a href=\"gbcomments.php?cid=$cid&stu=0\">Comments</a> | ";
		echo "<input type=\"button\" id=\"lockbtn\" onclick=\"lockcol()\" value=\"";
		if (isset($_COOKIE["gblhdr-$cid"]) && $_COOKIE["gblhdr-$cid"]==1) {
			echo "Unlock headers";
		} else {
			echo "Lock headers";
		}
		echo "\"/>";
		echo ' | <a href="#" onclick="chgnewflag(); return false;">NewFlag</a>';
		//echo '<input type="button" value="Pics" onclick="rotatepics()" />';
		echo "<br/>\n";
	}
	echo 'Category: <select id="filtersel" onchange="chgfilter()">';
	echo '<option value="-1" ';
	if ($catfilter==-1) {echo "selected=1";}
	echo '>All</option>';
	echo '<option value="0" ';
	if ($catfilter==0) { echo "selected=1";}
	echo '>Default</option>';
	$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo '<option value="'.$row[0].'"';
		if ($catfilter==$row[0]) {echo "selected=1";}
		echo '>'.$row[1].'</option>';
	}
	echo '<option value="-2" ';
	if ($catfilter==-2) {echo "selected=1";}
	echo '>Category Totals</option>';
	echo '</select> | ';
	echo "Not Counted: <select id=\"toggle2\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($hidenc,0); echo ">Show all</option>";
	echo "<option value=1 "; writeHtmlSelected($hidenc,1); echo ">Show stu view</option>";
	echo "<option value=2 "; writeHtmlSelected($hidenc,2); echo ">Hide all</option>";
	echo "</select>";
	echo " | Show: <select id=\"toggle3\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($availshow,0); echo ">Past due</option>";
	echo "<option value=3 "; writeHtmlSelected($availshow,3); echo ">Current</option>";
	echo "<option value=1 "; writeHtmlSelected($availshow,1); echo ">Past & Current</option>";
	echo "<option value=2 "; writeHtmlSelected($availshow,2); echo ">All</option></select>";
	echo " | Links: <select id=\"toggle1\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($links,0); echo ">View/Edit</option>";
	echo "<option value=1 "; writeHtmlSelected($links,1); echo ">Scores</option></select>";
	echo " | Pics: <select id=\"toggle4\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($showpics,0); echo ">None</option>";
	echo "<option value=1 "; writeHtmlSelected($showpics,1); echo ">Small</option>";
	echo "<option value=2 "; writeHtmlSelected($showpics,2); echo ">Big</option></select>";
	if (!$isteacher) {
	
		echo " | <input type=\"button\" id=\"lockbtn\" onclick=\"lockcol()\" value=\"";
		if (isset($_COOKIE["gblhdr-$cid"]) && $_COOKIE["gblhdr-$cid"]==1) {
			echo "Unlock headers";
		} else {
			echo "Lock headers";
		}
		echo "\"/>\n";	
	}
	
	echo "</div>";
	
	if ($isteacher) {
		echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca\" value=\"1\" onClick=\"chkAll(this.form, 'checked[]', this.checked)\"> \n";
		echo "With Selected:  <input type=submit name=submit value=\"E-mail\"> <input type=submit name=submit value=\"Message\"> <input type=submit name=submit value=\"Unenroll\"> <input type=submit name=submit value=\"Make Exception\"> ";
	}
	
	$gbt = gbinstrdisp();
	echo "</form>";
	echo "</div>";
	echo "Meanings:  IP-In Progress, OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sup>*</sup> Has feedback, <sub>d</sub> Dropped score, <sup>e</sup> Has exception/latepass\n";
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
	global $hidenc,$cid,$gbmode,$availshow,$isteacher,$istutor,$catfilter,$imasroot;
	if ($availshow==3) {
		$availshow=1;
		$hidepast = true;
	}
	$curdir = rtrim(dirname(__FILE__), '/\\');
	$gbt = gbtable($stu);
	
	if ($stu>0) {
		$query = "SELECT showlatepass FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$showlatepass = mysql_result($result,0,0);
		
		if ($isteacher && isset($_POST['usrcomments'])) {
			$query = "UPDATE imas_students SET gbcomment='{$_POST['usrcomments']}' WHERE userid='$stu'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			echo "<p>Comment Updated</p>";
		}
		if ($isteacher) {
			if (file_exists("$curdir//files/userimg_sm{$gbt[1][4][0]}.jpg")) {
				echo "<img src=\"$imasroot/course/files/userimg_sm{$gbt[1][4][0]}.jpg\" style=\"float: left; padding-right:5px;\" onclick=\"togglepic(this)\"/>";
			} 
		}
		echo '<h3>' . strip_tags($gbt[1][0][0]) . '</h3>';
		$query = "SELECT imas_students.gbcomment,imas_users.email,imas_students.latepass FROM imas_students,imas_users WHERE ";
		$query .= "imas_students.userid=imas_users.id AND imas_users.id='$stu' AND imas_students.courseid='{$_GET['cid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo '<div style="clear:both">';
		if (mysql_num_rows($result)>0) {
			if ($isteacher) {
				echo '<a href="mailto:'.mysql_result($result,0,1).'">Email</a> | ';
				echo "<a href=\"$imasroot/msgs/msglist.php?cid={$_GET['cid']}&add=new&to=$stu\">Message</a> | ";
				echo "<a href=\"gradebook.php?cid={$_GET['cid']}&uid=$stu&massexception=1\">Make Exception</a> | ";
				echo "<a href=\"listusers.php?cid={$_GET['cid']}&chgstuinfo=true&uid=$stu\">Change Info</a>";
			}
			$gbcomment = mysql_result($result,0,0);
			$latepasses = mysql_result($result,0,2);
			if ($showlatepass==1) {
				if ($latepasses==0) { $latepasses = 'No';}
				if ($isteacher) {echo '<br/>';}
				echo "$latepasses LatePass".($latepasses!=1?"es":"").' available';
			}
		} else {
			$gbcomment = '';
		}
		if (trim($gbcomment)!='' || $isteacher) {
			if ($isteacher) {
				echo "<form method=post action=\"gradebook.php?{$_SERVER['QUERY_STRING']}\">";
				echo "<textarea name=\"usrcomments\" rows=3 cols=60>$gbcomment</textarea><br/>";
				echo "<input type=submit value=\"Update Comment\">";
				echo "</form>";
			} else {
				echo "<div class=\"item\">$gbcomment</div>";
			}
		}
		echo '</div>';
	}
	
	echo '<table id="myTable" class=gb>';
	echo '<thead><tr><th>Item</th><th>Possible</th><th>Grade</th><th>Percent</th>';
	if ($stu>0) {
		echo '<th>Feedback</th>';
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
			if ($gbt[0][1][$i][3]>$availshow) {
				continue;
			}
			if ($hidepast && $gbt[0][1][$i][3]==0) {
				continue;
			}
			
			echo '<tr class="grid">';
			echo '<td class="cat'.$gbt[0][1][$i][1].'">'.$gbt[0][1][$i][0].'</td>';
			echo '<td>';
			
			if ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3) {
				echo $gbt[0][1][$i][2].' (Not Counted)';
			} else {
				echo $gbt[0][1][$i][2].'&nbsp;pts';
				if ($gbt[0][1][$i][4]==2) {
					echo ' (EC)';
				}
			}
			if ($gbt[0][1][$i][5]==1 && $gbt[0][1][$i][6]==0) {
				echo ' (PT)';
			}
			
			echo '</td><td>';
			$haslink = false;
			
			if ($isteacher || $istutor || $gbt[1][1][$i][2]==1) { //show link
				if ($gbt[0][1][$i][6]==0) {//online
					if ($stu==-1) { //in averages
						if (isset($gbt[1][1][$i][0])) { //has score
							echo "<a href=\"gb-itemanalysis.php?stu=$stu&cid=$cid&aid={$gbt[0][1][$i][7]}\">";
							$haslink = true;
						}
					} else {
						if (isset($gbt[1][1][$i][0])) { //has score
							echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$gbt[1][1][$i][4]}&uid={$gbt[1][4][0]}\">";
							$haslink = true;
						} else if ($isteacher) {
							echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid=new&aid={$gbt[0][1][$i][7]}&uid={$gbt[1][4][0]}\">";
							$haslink = true;
						}
					}
				} else if ($gbt[0][1][$i][6]==1) {//offline
					if ($isteacher || ($istutor && $gbt[0][1][$i][8]==1)) {
						if ($stu==-1) {
							if (isset($gbt[1][1][$i][0])) { //has score
								echo "<a href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$i][7]}\">";
								$haslink = true;
							}
						} else {
							echo "<a href=\"addgrades.php?stu=$stu&cid=$cid&grades={$gbt[1][4][0]}&gbitem={$gbt[0][1][$i][7]}\">";
							$haslink = true;
						}
					} 
				}
			}
			if (isset($gbt[1][1][$i][0])) {
				echo $gbt[1][1][$i][0];
				if ($gbt[1][1][$i][3]==1) {
					echo ' (NC)';
				} else if ($gbt[1][1][$i][3]==2) {
					echo ' (IP)';
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
			if (isset($gbt[1][1][$i][6]) ) {  //($isteacher || $istutor) && 
				echo '<sup>e</sup>';
			}
			if (isset($gbt[1][1][$i][5]) && ($gbt[1][1][$i][5]&(1<<$availshow)) && !$hidepast) {
				echo '<sub>d</sub>';
			}
			echo '</td><td>';
			if (isset($gbt[1][1][$i][0])) {
				if ($gbt[0][1][$i][2]>0) {
					echo round(100*$gbt[1][1][$i][0]/$gbt[0][1][$i][2],1).'%';
				}
			} else {
				echo '0%';
			}
			echo '</td>';
			if ($stu>0) {
				echo '<td>'.$gbt[1][1][$i][1].'</td>';
			}
			echo '</tr>';
		}
	}
	if (!$hidepast) {
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			$donedbltop = false;
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers	
				if ($availshow<2 && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				if (!$donedbltop) {
					echo '<tr class="grid dbltop">';
					$donedbltop = true;
				} else {
					echo '<tr class="grid">';
				}
				echo '<td class="cat'.$gbt[0][2][$i][1].'"><span class="cattothdr">'.$gbt[0][2][$i][0].'</span></td>';
				echo '<td>'.$gbt[0][2][$i][3+$availshow].'&nbsp;pts</td>';
				echo '<td>'.$gbt[1][2][$i][$availshow].'</td>';
				if ($gbt[0][2][$i][3+$availshow]>0) {
					echo '<td>'.round(100*$gbt[1][2][$i][$availshow]/$gbt[0][2][$i][3+$availshow],1).'%</td>';
				} else {
					echo '<td>0%</td>';
				}
				if ($stu>0) {
					echo '<td></td>';
				}
				echo '</tr>';
			}
		}
		//Totals
		if ($catfilter<0) {
			if ($availshow==2) {
				echo '<tr class="grid">';
				if (isset($gbt[0][3][0])) { //using points based
					echo '<td>Total All</td>';
					echo '<td>'.$gbt[0][3][2].'&nbsp;pts</td>';
					echo '<td>'.$gbt[1][3][2].'</td>';
					echo '<td>'.$gbt[1][3][5] .'%</td>';
				} else {
					echo '<td>Weighted Total All %</td>'; 
					echo '<td></td>';
					echo '<td>'.$gbt[1][3][2].'%</td>';
					echo '<td></td>';
				}
				if ($stu>0) {
					echo '<td></td>';
				}
				echo '</tr>';
			}
			echo '<tr class="grid">';
			if (isset($gbt[0][3][0])) { //using points based
				echo '<td>Total Past & Current</td>';
				echo '<td>'.$gbt[0][3][1].'&nbsp;pts</td>';
				echo '<td>'.$gbt[1][3][1].'</td>';
				echo '<td>'.$gbt[1][3][4] .'%</td>';
			} else {
				echo '<td>Weighted Total Past & Current %</td>'; 
				echo '<td></td>';
				echo '<td>'.$gbt[1][3][1].'%</td>';
				echo '<td></td>';
			}
			if ($stu>0) {
				echo '<td></td>';
			}
			echo '</tr>';
			echo '<tr class="grid">';
			if (isset($gbt[0][3][0])) { //using points based
				echo '<td>Total Past Due</td>';
				echo '<td>'.$gbt[0][3][0].'&nbsp;pts</td>';
				echo '<td>'.$gbt[1][3][0].'</td>';
				echo '<td>'.$gbt[1][3][3] .'%</td>';
			} else {
				echo '<td>Weighted Total Past Due %</td>'; 
				echo '<td></td>';
				echo '<td>'.$gbt[1][3][0].'%</td>';
				echo '<td></td>';
			}
			if ($stu>0) {
				echo '<td></td>';
			}
			echo '</tr>';
		}
	}
	echo '</tbody></table>';	
	$sarr = "'S','N','N','N'";
	if ($stu>0) {
		$sarr .= ",'S'";
	}
	if ($hidepast) {
		echo "<script>initSortTable('myTable',Array($sarr),false);</script>\n";
	} else if ($availshow==2) {
		echo "<script>initSortTable('myTable',Array($sarr),false,-3);</script>\n";
	} else {
		echo "<script>initSortTable('myTable',Array($sarr),false,-2);</script>\n";
	}
}

function gbinstrdisp() {
	global $hidenc,$showpics,$isteacher,$istutor,$cid,$gbmode,$stu,$availshow,$catfilter,$secfilter,$totonleft,$imasroot,$isdiag,$tutorsection;
	$curdir = rtrim(dirname(__FILE__), '/\\');
	if ($availshow==3) {
		$availshow=1;
		$hidepast = true;
	}
	$gbt = gbtable();
	//print_r($gbt);
	//echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n"; in placeinhead
	echo "<div id=\"tbl-container\">";
	echo '<table class="gb" id="myTable"><thead><tr>';
	$n=0;
	for ($i=0;$i<count($gbt[0][0]);$i++) { //biographical headers
		if ($showpics>0 && $i==1) {echo '<th></th>';} //for pics
		if ($i==1 && $gbt[0][0][1]!='ID') { continue;}
		echo '<th>'.$gbt[0][0][$i];
		if (($gbt[0][0][$i]=='Section' || ($isdiag && $i==4)) && (!$istutor || $tutorsection=='')) {
			echo "<br/><select id=\"secfiltersel\" onchange=\"chgsecfilter()\"><option value=\"-1\" ";
			if ($secfilter==-1) {echo  'selected=1';}
			echo  '>All</option>';
			$query = "SELECT DISTINCT section FROM imas_students WHERE courseid='$cid' ORDER BY section";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				if ($row[0]=='') { continue;}
				echo  "<option value=\"{$row[0]}\" ";
				if ($row[0]==$secfilter) {
					echo  'selected=1';
				}
				echo  ">{$row[0]}</option>";
			}
			echo  "</select>";	
			
		} else if ($gbt[0][0][$i]=='Name') {
			echo '<br/><span class="small">N='.(count($gbt)-2).'</span>';
		}
		echo '</th>';
		
		$n++;
	}
	if ($totonleft && !$hidepast) {
		//total totals
		if ($catfilter<0) {
			if (isset($gbt[0][3][0])) { //using points based
				echo '<th><span class="cattothdr">Total<br/>'.$gbt[0][3][$availshow].'&nbsp;pts</span></th>';
				echo '<th>%</th>';
				$n+=2;
			} else {
				echo '<th><span class="cattothdr">Weighted Total %</span></th>';
				$n++;
			}
		}
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers	
				if ($availshow<2 && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				echo '<th class="cat'.$gbt[0][2][$i][1].'"><span class="cattothdr">';
				echo $gbt[0][2][$i][0].'<br/>';
				echo $gbt[0][2][$i][3+$availshow].'&nbsp;pts';
				echo '</span></th>';
				$n++;
			}
		}
		
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
			if ($gbt[0][1][$i][3]>$availshow) {
				continue;
			}
			if ($hidepast && $gbt[0][1][$i][3]==0) {
				continue;
			}
			//name and points
			echo '<th class="cat'.$gbt[0][1][$i][1].'">'.$gbt[0][1][$i][0].'<br/>';
			if ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3) {
				echo $gbt[0][1][$i][2].' (Not Counted)';
			} else {
				echo $gbt[0][1][$i][2].'&nbsp;pts';
				if ($gbt[0][1][$i][4]==2) {
					echo ' (EC)';
				}
			}
			if ($gbt[0][1][$i][5]==1 && $gbt[0][1][$i][6]==0) {
				echo ' (PT)';
			}
			//links
			if ($gbt[0][1][$i][6]==0 ) { //online
				if ($isteacher) {
					echo "<br/><a class=small href=\"addassessment.php?id={$gbt[0][1][$i][7]}&cid=$cid&from=gb\">[Settings]</a>";
					echo "<br/><a class=small href=\"isolateassessgrade.php?cid=$cid&aid={$gbt[0][1][$i][7]}\">[Isolate]</a>";
				} else {
					echo "<br/><a class=small href=\"isolateassessgrade.php?cid=$cid&aid={$gbt[0][1][$i][7]}\">[Isolate]</a>";
				}
			} else if ($gbt[0][1][$i][6]==1  && ($isteacher || ($istutor && $gbt[0][1][$i][8]==1))) { //offline
				if ($isteacher) {
					echo "<br/><a class=small href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$i][7]}\">[Settings]</a>";
					echo "<br/><a class=small href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$i][7]}&isolate=true\">[Isolate]</a>";
				} else {
					echo "<br/><a class=small href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$i][7]}&isolate=true\">[Scores]</a>";
				}
			} else if ($gbt[0][1][$i][6]==2  && $isteacher) { //discussion
				echo "<br/><a class=small href=\"addforum.php?id={$gbt[0][1][$i][7]}&cid=$cid&from=gb\">[Settings]</a>";
			}
			
			echo '</th>';
			$n++;
		}
	}
	if (!$totonleft && !$hidepast) {
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers	
				if ($availshow<2 && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				echo '<th class="cat'.$gbt[0][2][$i][1].'"><span class="cattothdr">';
				echo $gbt[0][2][$i][0].'<br/>';
				echo $gbt[0][2][$i][3+$availshow].'&nbsp;pts';
				echo '</span></th>';
				$n++;
			}
		}
		//total totals
		if ($catfilter<0) {
			if (isset($gbt[0][3][0])) { //using points based
				echo '<th><span class="cattothdr">Total<br/>'.$gbt[0][3][$availshow].'&nbsp;pts</span></th>';
				echo '<th>%</th>';
				$n+=2;
			} else {
				echo '<th><span class="cattothdr">Weighted Total %</span></th>';
				$n++;
			}
		}
	}
	echo '</tr></thead><tbody>';
	//create student rows
	for ($i=1;$i<count($gbt);$i++) {
		if ($i%2!=0) {
			echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
		} else {
			echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
		}
		echo '<td class="locked" scope="row">';
		if ($gbt[$i][0][0]!="Averages" && $isteacher) {
			echo "<input type=\"checkbox\" name='checked[]' value='{$gbt[$i][4][0]}' />&nbsp;";
		}
		echo "<a href=\"gradebook.php?cid=$cid&stu={$gbt[$i][4][0]}\">";
		if ($gbt[$i][4][1]==1) {
			echo '<span style="text-decoration: line-through;">'.$gbt[$i][0][0].'</span>';
		} else {
			echo $gbt[$i][0][0];
		}
		echo '</a></td>';
		if ($showpics==1 && file_exists("$curdir//files/userimg_sm{$gbt[$i][4][0]}.jpg")) {
			echo "<td><img src=\"$imasroot/course/files/userimg_sm{$gbt[$i][4][0]}.jpg\"/></td>";
		} else if ($showpics==2 && file_exists("$curdir//files/userimg_{$gbt[$i][4][0]}.jpg")) {
			echo "<td><img src=\"$imasroot/course/files/userimg_{$gbt[$i][4][0]}.jpg\"/></td>";
		} else if ($showpics>0) {
			echo '<td></td>';
		}
		for ($j=($gbt[0][0][1]=='ID'?1:2);$j<count($gbt[0][0]);$j++) {
			echo '<td class="c">'.$gbt[$i][0][$j].'</td>';	
		}
		if ($totonleft && !$hidepast) {
			//total totals
			if ($catfilter<0) {
				if (isset($gbt[0][3][0])) { //using points based
					echo '<td class="c">'.$gbt[$i][3][$availshow].'</td>';
					echo '<td class="c">'.$gbt[$i][3][$availshow+3] .'%</td>';
				} else {
					echo '<td class="c">'.$gbt[$i][3][$availshow].'%</td>';
				}
			}
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers	
					if ($availshow<2 && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					if ($catfilter!=-1 && $gbt[0][2][$j][$availshow+3]>0) {
						//echo '<td class="c">'.$gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)</td>';
						echo '<td class="c">';
						if ($gbt[$i][0][0]=='Averages') {
							echo "<span onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						echo $gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)';
						if ($gbt[$i][0][0]=='Averages') {
							echo '</span>';
						}
						echo '</td>';
					} else {
						//echo '<td class="c">'.$gbt[$i][2][$j][$availshow].'</td>';
						echo '<td class="c">';
						if ($gbt[$i][0][0]=='Averages') {
							echo "<span onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						echo $gbt[$i][2][$j][$availshow];
						if ($gbt[$i][0][0]=='Averages') {
							echo '</span>';
						}
						echo '</td>';
					}
					
				}
			}
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
				if ($gbt[0][1][$j][3]>$availshow) {
					continue;
				}
				if ($hidepast && $gbt[0][1][$j][3]==0) {
					continue;
				}
				echo '<td class="c">';
				if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5]&(1<<$availshow)) && !$hidepast) {
					echo '<span style="font-style:italic">';
				}
				if ($gbt[0][1][$j][6]==0) {//online
					if (isset($gbt[$i][1][$j][0])) {
						if ($istutor && $gbt[$i][1][$j][4]=='average') {
							
						} else if ($gbt[$i][1][$j][4]=='average') {
							echo "<a href=\"gb-itemanalysis.php?stu=$stu&cid=$cid&asid={$gbt[$i][1][$j][4]}&aid={$gbt[0][1][$j][7]}\" "; 
							echo "onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
							echo ">";
						} else {
							echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$gbt[$i][1][$j][4]}&uid={$gbt[$i][4][0]}\">";
						}
						echo $gbt[$i][1][$j][0];
						if ($gbt[$i][1][$j][3]==1) {
							echo ' (NC)';
						} else if ($gbt[$i][1][$j][3]==2) {
							echo ' (IP)';
						} else if ($gbt[$i][1][$j][3]==3) {
							echo ' (OT)';
						} else if ($gbt[$i][1][$j][3]==4) {
							echo ' (PT)';
						} 
						if ($istutor && $gbt[$i][1][$j][4]=='average') {
						} else {
							echo '</a>';
						}
						if ($gbt[$i][1][$j][1]==1) {
							echo '<sup>*</sup>';
						}
						if (isset($gbt[$i][1][$j][6]) ) {
							echo '<sup>e</sup>';
						}
					} else { //no score
						if ($gbt[$i][0][0]=='Averages') {
							echo '-';
						} else if ($isteacher) {
							echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid=new&aid={$gbt[0][1][$j][7]}&uid={$gbt[$i][4][0]}\">-</a>";
						} else {
							echo '-';
						}
					}
				} else if ($gbt[0][1][$j][6]==1) { //offline
					if ($isteacher) {
						if ($gbt[$i][0][0]=='Averages') {
							echo "<a href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$j][7]}\" ";
							echo "onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
							echo ">";
						} else {
							echo "<a href=\"addgrades.php?stu=$stu&cid=$cid&grades={$gbt[$i][4][0]}&gbitem={$gbt[0][1][$j][7]}\">";
						}
					} else if ($istutor && $gbt[0][1][$j][8]==1) {
						if ($gbt[$i][0][0]=='Averages') {
							echo "<a href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$j][7]}\">";
						} else {
							echo "<a href=\"addgrades.php?stu=$stu&cid=$cid&grades={$gbt[$i][4][0]}&gbitem={$gbt[0][1][$j][7]}\">";
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
						echo $gbt[$i][1][$j][0];
					} else {
						echo '-';
					}
				}
				if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5]&(1<<$availshow)) && !$hidepast) {
					echo '<sub>d</sub></span>';
				}
				echo '</td>';
			}
		}
		if (!$totonleft && !$hidepast) {
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers	
					if ($availshow<2 && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					if ($catfilter!=-1 && $gbt[0][2][$j][$availshow+3]>0) {
						echo '<td class="c">';
						if ($gbt[$i][0][0]=='Averages') {
							echo "<span onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						echo $gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)';
						if ($gbt[$i][0][0]=='Averages') {
							echo '</span>';
						}
						echo '</td>';
					} else {
						echo '<td class="c">';
						if ($gbt[$i][0][0]=='Averages') {
							echo "<span onmouseover=\"tipshow(this,'5-number summary: {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						echo $gbt[$i][2][$j][$availshow];
						if ($gbt[$i][0][0]=='Averages') {
							echo '</span>';
						}
						echo '</td>';
					}
				}
			}
			
			//total totals
			if ($catfilter<0) {
				if (isset($gbt[0][3][0])) { //using points based
					echo '<td class="c">'.$gbt[$i][3][$availshow].'</td>';
					echo '<td class="c">'.$gbt[$i][3][$availshow+3] .'%</td>';
				} else {
					echo '<td class="c">'.$gbt[$i][3][$availshow].'%</td>';
				}
			}
		}
		echo '</tr>';
	}
	echo "</tbody></table>";
	if ($n>1) {
		$sarr = array_fill(0,$n-1,"'N'");
	} else {
		$sarr = array();
	}
	array_unshift($sarr,"'S'");
	
	$sarr = implode(",",$sarr);
	echo "<script>initSortTable('myTable',Array($sarr),true,false);</script>\n";
		
	
}

?>
