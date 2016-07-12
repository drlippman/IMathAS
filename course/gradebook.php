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
		$sessiondata[$cid.'gbmode'] = $gbmode;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'gbmode']) && !isset($_GET['refreshdef'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		$query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$gbmode = mysql_result($result,0,0);
		$sessiondata[$cid.'gbmode'] = $gbmode;
		writesessiondata();
	}
	if (isset($_COOKIE["colorize-$cid"]) && !isset($_GET['refreshdef'])) {
		$colorize = $_COOKIE["colorize-$cid"];
	} else {
		$query = "SELECT colorize FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$colorize = mysql_result($result,0,0);
		setcookie("colorize-$cid",$colorize);
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
	if (isset($_GET['refreshdef']) && isset($sessiondata[$cid.'catcollapse'])) {
		unset($sessiondata[$cid.'catcollapse']);
		writesessiondata();
	}
	if (isset($sessiondata[$cid.'catcollapse'])) {
		$overridecollapse = $sessiondata[$cid.'catcollapse'];
	} else {
		$overridecollapse = array();
	}
	if (isset($_GET['catcollapse'])) {
		$overridecollapse[$_GET['cat']] = $_GET['catcollapse'];
		$sessiondata[$cid.'catcollapse'] = $overridecollapse;
		writesessiondata();
	}
	
	//Gbmode : Links NC Dates
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
	$lastlogin = false;
	$includeduedate = false;
	$includelastchange = false;
}

if ($canviewall && isset($_GET['stu'])) {
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
	if ((isset($_POST['posted']) && $_POST['posted']=="Unenroll") || (isset($_GET['action']) && $_GET['action']=="unenroll" )) {
		$calledfrom='gb';
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		$curBreadcrumb .= "&gt; <a href=\"gradebook.php?cid=$cid\">Gradebook</a> &gt; Confirm Change";
		$pagetitle = _('Unenroll Students');
		include("unenroll.php");
		include("../footer.php");
		exit;
	}
	if ((isset($_POST['posted']) && $_POST['posted']=="Lock") || (isset($_GET['action']) && $_GET['action']=="lock" )) {
		$calledfrom='gb';
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
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
		echo '<a href="gradebook.php?'.$_SERVER['QUERY_STRING'].'">', _('Back to Gradebook'), '</a></div>';
		if( isset($_POST['checked']) ) {
			echo "<div id=\"tbl-container\">";
			echo '<div id="bigcontmyTable"><div id="tblcontmyTable">';
			$value = $_POST['checked'];
			$last = count($value)-1;
			for($i = 0; $i < $last; $i++){
				gbstudisp($value[$i]);
				echo "<div style=\"page-break-after:always\"></div>";
			}
			gbstudisp($value[$last]);//no page break after last report
	
			echo "</div></div></div>";
		}
		require("../footer.php");
		exit;
		
	}
	if (isset($_POST['usrcomments']) && $stu>0) {
			$query = "UPDATE imas_students SET gbcomment='{$_POST['usrcomments']}' WHERE userid='$stu' AND courseid='$cid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			//echo "<p>Comment Updated</p>";
	}
	if (isset($_POST['score']) && $stu>0) {
		foreach ($_POST['score'] as $id=>$val) {
			if (trim($val)=='') {
				$query = "DELETE FROM imas_grades WHERE userid='$stu' AND gradetypeid='$id' AND gradetype='offline'";
			} else {
				$query = "UPDATE imas_grades SET score='$val',feedback='{$_POST['feedback'][$id]}' WHERE userid='$stu' AND gradetypeid='$id' AND gradetype='offline'";
			}
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
	}
	if (isset($_POST['newscore']) && $stu>0) {
		$toins = array();
		foreach ($_POST['newscore'] as $id=>$val) {
			if (trim($val)=="") {continue;}
			$toins[] = "('$id','offline','$stu','$val','{$_POST['feedback'][$id]}')";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if (count($toins)>0) {
			$query = "INSERT INTO imas_grades (gradetypeid,gradetype,userid,score,feedback) VALUES ".implode(',',$toins);
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
	}
	if (isset($_POST['usrcomments']) || isset($_POST['score']) || isset($_POST['newscore'])) {
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?{$_SERVER['QUERY_STRING']}");
		exit;
	}
}



//DISPLAY
require_once("gbtable2.php");
require("../includes/htmlutil.php");

$placeinhead = '';
if ($canviewall) {
	$placeinhead .= "<script type=\"text/javascript\">";
	$placeinhead .= 'function chgfilter() { ';
	$placeinhead .= '       var cat = document.getElementById("filtersel").value; ';
	$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid";
	
	$placeinhead .= "       var toopen = '$address&catfilter=' + cat;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	if ($isteacher) { 
		$placeinhead .= 'function chgsecfilter() { ';
		$placeinhead .= '       var sec = document.getElementById("secfiltersel").value; ';
		$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid";
		
		$placeinhead .= "       var toopen = '$address&secfilter=' + sec;\n";
		$placeinhead .= "  	window.location = toopen; \n";
		$placeinhead .= "}\n";
		$placeinhead .= 'function chgnewflag() { ';
		$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid&togglenewflag=true";
		
		$placeinhead .= "       basicahah('$address','newflag','Recording...');\n";
		$placeinhead .= "}\n";
	}
	$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?cid=$cid&stu=";
	$placeinhead .= "function chgstu(el) { 	\$('#updatingicon').show(); window.location = '$address' + el.value;}\n";
	$placeinhead .= 'function chgtoggle() { ';
	$placeinhead .= "	var altgbmode = 10000*document.getElementById(\"toggle4\").value + 1000*($totonleft+$avgontop) + 100*(document.getElementById(\"toggle1\").value*1+ document.getElementById(\"toggle5\").value*1) + 10*document.getElementById(\"toggle2\").value + 1*document.getElementById(\"toggle3\").value; ";
	if ($includelastchange) {
		$placeinhead .= "     altgbmode += 40;";
	}
	if ($lastlogin) {
		$placeinhead .= "     altgbmode += 4000;";
	}
	if ($includeduedate) {
		$placeinhead .= "     altgbmode += 400;\n";
	}
	
	$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid&gbmode=";
	$placeinhead .= "	var toopen = '$address' + altgbmode;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	$placeinhead .= '$(function() { $("th a, th select").bind("click", function(e) { e.stopPropagation(); }); });';
	if ($isteacher) {
		$placeinhead .= 'function chgexport() { ';
		$placeinhead .= "	var type = document.getElementById(\"exportsel\").value; ";
		$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gb-export.php?stu=$stu&cid=$cid&";
		$placeinhead .= "	var toopen = '$address';";
		$placeinhead .= "	if (type==1) { toopen = toopen+'export=true';}\n";
		$placeinhead .= "	if (type==2) { toopen = toopen+'emailgb=me';}\n";
		$placeinhead .= "	if (type==3) { toopen = toopen+'emailgb=ask';}\n";
		$placeinhead .= "	if (type==0) { return false;}\n";
		$placeinhead .= "  	window.location = toopen; \n";
		$placeinhead .= "}\n";
		$placeinhead .= 'function makeofflineeditable(el) {
					var anchors = document.getElementsByTagName("a");
					for (var i=0;i<anchors.length;i++) {
						if (bits=anchors[i].href.match(/addgrades.*gbitem=(\d+)/)) {
							if (anchors[i].innerHTML.match("-")) {
							    type = "newscore";
							} else {
							    type = "score";
							}
							anchors[i].style.display = "none";
							var newinp = document.createElement("input");
							newinp.size = 4;
							if (type=="newscore") {
							    newinp.name = "newscore["+bits[1]+"]";
							} else {
							    newinp.name = "score["+bits[1]+"]";
							    newinp.value = anchors[i].innerHTML;
							}
							anchors[i].parentNode.appendChild(newinp);
							var newtxta = document.createElement("textarea");
							newtxta.name = "feedback["+bits[1]+"]";
							newtxta.cols = 50;
							var feedbtd = anchors[i].parentNode.nextSibling.nextSibling.nextSibling;
							newtxta.value = feedbtd.innerHTML;
							feedbtd.innerHTML = "";
							feedbtd.appendChild(newtxta);
						}					
					}
					document.getElementById("savechgbtn").style.display = "";
					el.onclick = null;
				}';
	}
	
	
	$placeinhead .= "</script>";
	$placeinhead .= '<script type="text/javascript">function conditionalColor(table,type,low,high) {
	var tbl = document.getElementById(table);
	if (type==0) {  //instr gb view
		var poss = [];
		var startat = 2;
		var ths = tbl.getElementsByTagName("thead")[0].getElementsByTagName("th");
		for (var i=0;i<ths.length;i++) {
			if (k = ths[i].innerHTML.match(/(\d+)(&nbsp;|\u00a0)pts/)) {
				poss[i] = k[1]*1;
				if (poss[i]==0) {poss[i]=.0000001;}
			} else {
				poss[i] = 100;
				if(ths[i].className.match(/nocolorize/)) {
					startat++;
				}
			}
		}
		var trs = tbl.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
		for (var j=0;j<trs.length;j++) {
			var tds = trs[j].getElementsByTagName("td");
			for (var i=startat;i<tds.length;i++) {
				if (low==-1) {
					if (tds[i].className.match("isact")) {
						tds[i].style.backgroundColor = "#99ff99";
					} else {
						tds[i].style.backgroundColor = "#ffffff";
					}
				} else {
					if (tds[i].innerText) {
						var v = tds[i].innerText;
					} else {
						var v = tds[i].textContent;
					}
					if (k = v.match(/\(([\d\.]+)%\)/)) {
						var perc = k[1]/100;
					} else if (k = v.match(/([\d\.]+)\/(\d+)/)) {
						if (k[2]==0) { var perc = 0;} else { var perc= k[1]/k[2];}
					} else {
						v = v.replace(/[^\d\.]/g,"");
						var perc = v/poss[i];
					}
					
					if (perc<low/100) {
						tds[i].style.backgroundColor = "#ff9999";
						
					} else if (perc>high/100) {
						tds[i].style.backgroundColor = "#99ff99";
					} else {
						tds[i].style.backgroundColor = "#ffffff";
					}
				}
			}
		}
	} else {
		var trs = tbl.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
		for (var j=0;j<trs.length;j++) {
			var tds = trs[j].getElementsByTagName("td");
			if (tds[1].innerText) {
				var poss = tds[1].innerText.replace(/[^\d\.]/g,"");
				var v = tds[2].innerText.replace(/[^\d\.]/g,"");
			} else {
				var poss = tds[1].textContent.replace(/[^\d\.]/g,"");
				var v = tds[2].textContent.replace(/[^\d\.]/g,"");
			}
			if (v/poss<low/100) {
				tds[2].style.backgroundColor = "#ff6666";
				
			} else if (v/poss>high/100) {
				tds[2].style.backgroundColor = "#66ff66";
			} else {
				tds[2].style.backgroundColor = "#ffffff";
				
			}
			
		}
	}
}
function updateColors(el) {
	if (el.value==0) {
		var tds=document.getElementById("myTable").getElementsByTagName("td");
		for (var i=0;i<tds.length;i++) {
			tds[i].style.backgroundColor = "";
		}
	} else {
		var s = el.value.split(/:/);
		conditionalColor("myTable",0,s[0],s[1]);
	}
	document.cookie = "colorize-'.$cid.'="+el.value;
}
function copyemails() {
	var ids = [];
	$("#myTable input:checkbox:checked").each(function(i) {
		ids.push(this.value);
	});
	GB_show("Emails","viewemails.php?cid='.$cid.'&ids="+ids.join("-"),500,500);
}
	
</script>';
}



		
			

if (isset($studentid) || $stu!=0) { //show student view
	if (isset($studentid)) {
		$stu = $userid;
	}
	$pagetitle = _('Gradebook');
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
	$placeinhead .= '<script type="text/javascript">
		function showhidefb(el,n) {
			el.style.display="none";
			document.getElementById("feedbackholder"+n).style.display = "inline";
			return false;
			}
		function showhideallfb(s) {
			s.style.display="none";
			var els = document.getElementsByTagName("a");
			for (var i=0;i<els.length;i++) {
				if (els[i].className.match("feedbacksh")) {
					els[i].style.display="none";
				}
			}
			var els = document.getElementsByTagName("span");
			for (var i=0;i<els.length;i++) {
				if (els[i].id.match("feedbackholder")) {
					els[i].style.display="inline";
				}
			}
			return false;
		}</script>';
	
	require("../header.php");
	
	if (isset($_GET['from']) && $_GET['from']=="listusers") {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt ", _('Student Grade Detail'), "</div>\n";
	} else if ($isteacher || $istutor) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; ", _('Student Detail'), "</div>";
	} else {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; ", _('Gradebook'), "</div>";
	}
	if ($stu==-1) {
		echo '<div id="headergradebook" class="pagetitle"><h2>', _('Grade Book Averages'), ' </h2></div>';
	} else {
		echo '<div id="headergradebook" class="pagetitle"><h2>', _('Grade Book Student Detail'), '</h2></div>';
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
		$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid' ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo '<option value="'.$row[0].'"';
			if ($catfilter==$row[0]) {echo "selected=1";}
			echo '>'.$row[1].'</option>';
		}
		echo '<option value="-2" ';
		if ($catfilter==-2) {echo "selected=1";}
		echo '>', _('Category Totals'), '</option>';
		echo '</select> | ';
		echo _('Not Counted:'), " <select id=\"toggle2\" onchange=\"chgtoggle()\">";
		echo "<option value=0 "; writeHtmlSelected($hidenc,0); echo ">", _('Show all'), "</option>";
		echo "<option value=1 "; writeHtmlSelected($hidenc,1); echo ">", _('Show stu view'), "</option>";
		echo "<option value=2 "; writeHtmlSelected($hidenc,2); echo ">", _('Hide all'), "</option>";
		echo "</select>";
		echo " | ", _('Show:'), " <select id=\"toggle3\" onchange=\"chgtoggle()\">";
		echo "<option value=0 "; writeHtmlSelected($availshow,0); echo ">", _('Past due'), "</option>";
		echo "<option value=3 "; writeHtmlSelected($availshow,3); echo ">", _('Past &amp; Attempted'), "</option>";
		echo "<option value=4 "; writeHtmlSelected($availshow,4); echo ">", _('Available Only'), "</option>";
		echo "<option value=1 "; writeHtmlSelected($availshow,1); echo ">", _('Past &amp; Available'), "</option>";
		echo "<option value=2 "; writeHtmlSelected($availshow,2); echo ">", _('All'), "</option></select>";
		echo " | ", _('Links:'), " <select id=\"toggle1\" onchange=\"chgtoggle()\">";
		echo "<option value=0 "; writeHtmlSelected($links,0); echo ">", _('View/Edit'), "</option>";
		echo "<option value=1 "; writeHtmlSelected($links,1); echo ">", _('Scores'), "</option></select>";
		echo '<input type="hidden" id="toggle4" value="'.$showpics.'" />';
		echo '<input type="hidden" id="toggle5" value="'.$hidelocked.'" />';
		echo "</div>";
	}
	gbstudisp($stu);
	echo "<p>", _('Meanings: IP-In Progress (some unattempted questions), OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sub>d</sub> Dropped score.  <sup>e</sup> Has exception <sup>LP</sup> Used latepass'), "  </p>\n";
	
	require("../footer.php");
	
} else { //show instructor view
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js?v=012811\"></script>\n";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablescroller2.js?v=012514\"></script>\n";
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
	$placeinhead .= "\nfunction lockcol() { \n";
	$placeinhead .= "var tog = ts.toggle(); ";
	$placeinhead .= "document.cookie = 'gblhdr-$cid=1';\n document.getElementById(\"lockbtn\").value = \"" . _('Unlock headers') . "\"; ";
	$placeinhead .= "if (tog==1) { "; //going to locked
	$placeinhead .= "} else {";
	$placeinhead .= "document.cookie = 'gblhdr-$cid=0';\n document.getElementById(\"lockbtn\").value = \"" . _('Lock headers') . "\"; ";
	//$placeinhead .= " var cont = document.getElementById(\"tbl-container\");\n";
	//$placeinhead .= " if (cont.style.overflow == \"auto\") {\n";
	//$placeinhead .= "   cont.style.height = \"auto\"; cont.style.overflow = \"visible\"; cont.style.border = \"0px\";";
	//$placeinhead .= "document.getElementById(\"myTable\").className = \"gb\"; document.cookie = 'gblhdr-$cid=0';";
	//$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Lock headers\"; } else {";
	//$placeinhead .= " cont.style.height = \"75%\"; cont.style.overflow = \"auto\"; cont.style.border = \"1px solid #000\";\n";
	//$placeinhead .= "document.getElementById(\"myTable\").className = \"gbl\"; document.cookie = 'gblhdr-$cid=1'; ";
	//$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Unlock headers\"; }";
	$placeinhead .= "}}\n ";
	$placeinhead .= "function cancellockcol() {document.cookie = 'gblhdr-$cid=0';\n document.getElementById(\"lockbtn\").value = \"" . _('Lock headers') . "\";}\n"; 
	$placeinhead .= 'function highlightrow(el) { el.setAttribute("lastclass",el.className); el.className = "highlight";}';
	$placeinhead .= 'function unhighlightrow(el) { el.className = el.getAttribute("lastclass");}';
	$placeinhead .= "</script>\n";
	$placeinhead .= "<style type=\"text/css\"> table.gb { margin: 0px; } table.gb tr.highlight { border-bottom:1px solid #333;} table.gb tr {border-bottom:1px solid #fff; } td.trld {display:table-cell;vertical-align:middle;} </style>";
	
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; ", _('Gradebook'), "</div>";
	echo "<form id=\"qform\" method=post action=\"gradebook.php?cid=$cid\">";
	
	echo '<div id="headergradebook" class="pagetitle"><h2>', _('Gradebook'), ' <span class="red" id="newflag" style="font-size: 70%" >';
	if (($coursenewflag&1)==1) {
		echo _('New');
	}
	echo '</span></h2></div>';
	if ($isdiag) {
		echo "<a href=\"gb-testing.php?cid=$cid\">", _('View diagnostic gradebook'), "</a>";
	}
	echo "<div class=cpmid>";
	if ($isteacher) {
		echo _('Offline Grades:'), " <a href=\"addgrades.php?cid=$cid&gbitem=new&grades=all\">", _('Add'), "</a>, ";
		echo "<a href=\"chgoffline.php?cid=$cid\">", _('Manage'), "</a> | ";
		echo '<select id="exportsel" onchange="chgexport()">';
		echo '<option value="0">', _('Export to...'), '</option>';
		echo '<option value="1">', _('... file'), '</option>';
		echo '<option value="2">', _('... my email'), '</option>';
		echo '<option value="3">', _('... other email'), '</option></select> | ';
		//echo "Export to <a href=\"gb-export.php?stu=$stu&cid=$cid&export=true\">File</a>, ";
		//echo "<a href=\"gb-export.php?stu=$stu&cid=$cid&emailgb=me\">My Email</a>, or <a href=\"gb-export.php?stu=$stu&cid=$cid&emailgb=ask\">Other Email</a> | ";
		echo "<a href=\"gbsettings.php?cid=$cid\">", _('GB Settings'), "</a> | ";
		echo "<a href=\"gradebook.php?cid=$cid&stu=-1\">", _('Averages'), "</a> | ";
		echo "<a href=\"gbcomments.php?cid=$cid&stu=0\">", _('Comments'), "</a> | ";
		echo "<input type=\"button\" id=\"lockbtn\" onclick=\"lockcol()\" value=\"";
		if ($headerslocked) {
			echo _('Unlock headers');
		} else {
			echo _('Lock headers');
		}
		echo "\"/>";
		echo ' | ', _('Color:'), ' <select id="colorsel" onchange="updateColors(this)">';
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
		echo '</select>';
		echo ' | <a href="#" onclick="chgnewflag(); return false;">', _('NewFlag'), '</a>';
		//echo '<input type="button" value="Pics" onclick="rotatepics()" />';
		
		echo "<br/>\n";
		
	}
	
	echo _('Category:'), ' <select id="filtersel" onchange="chgfilter()">';
	echo '<option value="-1" ';
	if ($catfilter==-1) {echo "selected=1";}
	echo '>', _('All'), '</option>';
	echo '<option value="0" ';
	if ($catfilter==0) { echo "selected=1";}
	echo '>', _('Default'), '</option>';
	$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid' ORDER BY name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo '<option value="'.$row[0].'"';
		if ($catfilter==$row[0]) {echo "selected=1";}
		echo '>'.$row[1].'</option>';
	}
	echo '<option value="-2" ';
	if ($catfilter==-2) {echo "selected=1";}
	echo '>', ('Category Totals'), '</option>';
	echo '</select> | ';
	echo _('Not Counted:'), " <select id=\"toggle2\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($hidenc,0); echo ">", _('Show all'), "</option>";
	echo "<option value=1 "; writeHtmlSelected($hidenc,1); echo ">", _('Show stu view'), "</option>";
	echo "<option value=2 "; writeHtmlSelected($hidenc,2); echo ">", _('Hide all'), "</option>";
	echo "</select>";
	echo " | ", _('Show:'), " <select id=\"toggle3\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($availshow,0); echo ">", _('Past due'), "</option>";
	echo "<option value=3 "; writeHtmlSelected($availshow,3); echo ">", _('Past &amp; Attempted'), "</option>";
	echo "<option value=4 "; writeHtmlSelected($availshow,4); echo ">", _('Available Only'), "</option>";
	echo "<option value=1 "; writeHtmlSelected($availshow,1); echo ">", _('Past &amp; Available'), "</option>";
	echo "<option value=2 "; writeHtmlSelected($availshow,2); echo ">", _('All'), "</option></select>";
	echo " | ", _('Links:'), " <select id=\"toggle1\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($links,0); echo ">", _('View/Edit'), "</option>";
	echo "<option value=1 "; writeHtmlSelected($links,1); echo ">", _('Scores'), "</option></select>";
	echo " | ", _('Pics:'), " <select id=\"toggle4\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($showpics,0); echo ">", _('None'), "</option>";
	echo "<option value=1 "; writeHtmlSelected($showpics,1); echo ">", _('Small'), "</option>";
	echo "<option value=2 "; writeHtmlSelected($showpics,2); echo ">", _('Big'), "</option></select>";
	if (!$isteacher) {
	
		echo " | <input type=\"button\" id=\"lockbtn\" onclick=\"lockcol()\" value=\"";
		if ($headerslocked) {
			echo _('Unlock headers');
		} else {
			echo _('Lock headers');
		}
		echo "\"/>\n";	
	}
	
	echo "</div>";
	
	if ($isteacher) {
		echo _('Check:'), ' <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',true)">', _('All'), '</a> <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',false)">', _('None'), '</a> ';
		echo _('With Selected:'), '  <button type="submit" name="posted" value="Print Report" title="',_("Generate printable grade reports"),'">',_('Print Report'),'</button> ';
		echo '<button type="submit" name="posted" value="E-mail" title="',_("Send e-mail to the selected students"),'">',_('E-mail'),'</button> ';
		echo '<button type="button" name="posted" value="Copy E-mails" title="',_("Copy e-mail addresses of the selected students"),'" onclick="copyemails()">',_('Copy E-mails'),'</button> ';
		echo '<button type="submit" name="posted" value="Message" title="',_("Send a message to the selected students"),'">',_('Message'),'</button> ';

		if (!isset($CFG['GEN']['noInstrUnenroll'])) {
			echo '<button type="submit" name="posted" value="Unenroll" title="',_("Unenroll the selected students"),'">',_('Unenroll'),'</button> ';
		}
		echo '<button type="submit" name="posted" value="Lock" title="',_("Lock selected students out of the course"),'">',_('Lock'),'</button> ';
		echo '<button type="submit" name="posted" value="Make Exception" title="',_("Make due date exceptions for selected students"),'">',_('Make Exception'),'</button> ';
	}
	$includelastchange = false;  //don't need it for instructor view
	$gbt = gbinstrdisp();
	echo "</form>";
	echo "</div>";
	echo _('Meanings:  IP-In Progress (some unattempted questions), OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sup>*</sup> Has feedback, <sub>d</sub> Dropped score,  <sup>e</sup> Has exception <sup>LP</sup> Used latepass'), "\n";
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
	global $hidenc,$cid,$gbmode,$availshow,$isteacher,$istutor,$catfilter,$imasroot,$canviewall,$urlmode,$includeduedate, $includelastchange,$latepasshrs;
	if ($availshow==4) {
		$availshow=1;
		$hidepast = true;
	}
	if ($stu>0) {
		$query = "SELECT showlatepass,latepasshrs FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		list($showlatepass,$latepasshrs) = mysql_fetch_row($result);
	}
	$curdir = rtrim(dirname(__FILE__), '/\\');
	$gbt = gbtable($stu);
	
	if ($stu>0) {
		echo '<div style="font-size:1.1em;font-weight:bold">';
		if ($isteacher || $istutor) {
			if ($gbt[1][0][1] != '') {
				$query = "SELECT usersort FROM imas_gbscheme WHERE courseid='$cid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$usersort = mysql_result($result,0,0);
			} else {
				$usersort = 1;
			}
			
			if ($gbt[1][4][2]==1) {
				if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
					echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$gbt[1][4][0]}.jpg\" onclick=\"togglepic(this)\" class=\"mida\"/> ";
				} else {
					echo "<img src=\"$imasroot/course/files/userimg_sm{$gbt[1][4][0]}.jpg\" style=\"float: left; padding-right:5px;\" onclick=\"togglepic(this)\" class=\"mida\"/>";
				}
			} 
			$query = "SELECT iu.id,iu.FirstName,iu.LastName,istu.section FROM imas_users AS iu JOIN imas_students as istu ON iu.id=istu.userid WHERE istu.courseid='$cid' ";
			if ($usersort==0) {
				$query .= "ORDER BY istu.section,iu.LastName,iu.FirstName";
			} else {
				$query .= "ORDER BY iu.LastName,iu.FirstName";
			}
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			echo '<select id="userselect" style="border:0;font-size:1.1em;font-weight:bold" onchange="chgstu(this)">';
			$lastsec = '';
			while ($row = mysql_fetch_row($result)) {
				if ($row[3]!='' && $row[3]!=$lastsec && $usersort==0) {
					if ($lastsec=='') {echo '</optgroup>';}
					echo '<optgroup label="Section '.htmlentities($row[3]).'">';
					$lastsec = $row[3];
				}
				echo '<option value="'.$row[0].'"';
				if ($row[0]==$stu) {
					echo ' selected="selected"';
				}
				echo '>'.$row[2].', '.$row[1].'</option>';
			}
			if ($lastsec!='') {echo '</optgroup>';}
			echo '</select>';
			echo '<img id="updatingicon" style="display:none" src="'.$imasroot.'/img/updating.gif"/>';
			echo ' <span class="small">('.$gbt[1][0][1].')</span>';
		} else {
			echo strip_tags($gbt[1][0][0]) . ' <span class="small">('.$gbt[1][0][1].')</span>';
			
			$viewedassess = array();
			$query = "SELECT typeid FROM imas_content_track WHERE courseid='$cid' AND userid='$stu' AND type='gbviewasid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$viewedassess[] = $row[0];
			}
			$now = time();
		}
		$query = "SELECT imas_students.gbcomment,imas_users.email,imas_students.latepass,imas_students.section,imas_students.lastaccess FROM imas_students,imas_users WHERE ";
		$query .= "imas_students.userid=imas_users.id AND imas_users.id='$stu' AND imas_students.courseid='{$_GET['cid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)==0) { //shouldn't happen
			echo 'Invalid student id';
			require("../footer.php");
			exit;
		}
		list($gbcomment,$stuemail,$latepasses,$stusection,$lastaccess) = mysql_fetch_row($result);
		if ($stusection!='') {
			echo ' <span class="small">Section: '.$stusection.'.</span>';
		}
		echo ' <span class="small">'._('Last Login: ').tzdate('D n/j/y g:ia', $lastaccess).'.</span>';
		echo '</div>';
		if ($isteacher) {
			echo '<div style="clear:both;display:inline-block" class="cpmid secondary">';
			//echo '<a href="mailto:'.$stuemail.'">', _('Email'), '</a> | ';
			echo "<a href=\"#\" onclick=\"GB_show('Send Email','$imasroot/course/sendmsgmodal.php?to=$stu&sendtype=email&cid=$cid',800,'auto')\" title=\"Send Email\">", _('Email'), "</a> | ";
			
			//echo "<a href=\"$imasroot/msgs/msglist.php?cid={$_GET['cid']}&add=new&to=$stu\">", _('Message'), "</a> | ";
			echo "<a href=\"#\" onclick=\"GB_show('Send Message','$imasroot/course/sendmsgmodal.php?to=$stu&sendtype=msg&cid=$cid',800,'auto')\" title=\"Send Message\">", _('Message'), "</a> | ";
			//remove since redundant with Make Exception button "with selected"
			//echo "<a href=\"gradebook.php?cid={$_GET['cid']}&uid=$stu&massexception=1\">", _('Make Exception'), "</a> | ";
			echo "<a href=\"listusers.php?cid={$_GET['cid']}&chgstuinfo=true&uid=$stu\">", _('Change Info'), "</a> | ";
			echo "<a href=\"viewloginlog.php?cid={$_GET['cid']}&uid=$stu&from=gb\">", _('Login Log'), "</a> | ";
			echo "<a href=\"viewactionlog.php?cid={$_GET['cid']}&uid=$stu&from=gb\">", _('Activity Log'), "</a> | ";
			echo "<a href=\"#\" onclick=\"makeofflineeditable(this); return false;\">", _('Edit Offline Scores'), "</a>";
			echo '</div>';	
		} else if ($istutor) {
			echo '<div style="clear:both;display:inline-block" class="cpmid">';
			echo "<a href=\"viewloginlog.php?cid={$_GET['cid']}&uid=$stu&from=gb\">", _('Login Log'), "</a> | ";
			echo "<a href=\"viewactionlog.php?cid={$_GET['cid']}&uid=$stu&from=gb\">", _('Activity Log'), "</a>";
			echo '</div>';	
		}
		
		if (trim($gbcomment)!='' || $isteacher) {
			if ($isteacher) {
				echo "<form method=post action=\"gradebook.php?{$_SERVER['QUERY_STRING']}\">";
				echo _('Gradebook Comment').': '.  "<input type=submit value=\"", _('Update Comment'), "\"><br/>";
				echo "<textarea name=\"usrcomments\" rows=3 cols=60>$gbcomment</textarea>";
				echo '</form>';
			} else {
				echo "<div style=\"clear:both;display:inline-block\" class=\"cpmid\">$gbcomment</div><br/>";
			}
		}
		//TODO i18n
		if ($showlatepass==1) {
			if ($latepasses==0) { $latepasses = 'No';}
			if ($isteacher || $istutor) {echo '<br/>';}
			$lpmsg = "$latepasses LatePass".($latepasses!=1?"es":"").' available';
		}
		if (!$isteacher && !$istutor) {
			echo $lpmsg;
		}
		
	}
	echo "<form method=\"post\" id=\"qform\" action=\"gradebook.php?{$_SERVER['QUERY_STRING']}&uid=$stu\">";
	//echo "<input type='button' onclick='conditionalColor(\"myTable\",1,50,80);' value='Color'/>";
	if ($isteacher && $stu>0) {
		echo '<button type="submit" value="Save Changes" style="display:none"; id="savechgbtn">', _('Save Changes'), '</button> ';
		echo _('Check:'), ' <a href="#" onclick="return chkAllNone(\'qform\',\'assesschk[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'assesschk[]\',false)">', _('None'), '</a> ';
		echo _('With selected:'), ' <button type="submit" value="Make Exception" name="posted">',_('Make Exception'),'</button> '.$lpmsg.'';
	}
	echo '<table id="myTable" class="gb" style="position:relative;">';
	echo '<thead><tr>';
	$sarr = array();
	if ($stu>0 && $isteacher) {
		echo '<th></th>';
	}
	echo '<th>', _('Item'), '</th><th>', _('Possible'), '</th><th>', _('Grade'), '</th><th>', _('Percent'), '</th>';
	if ($stu>0 && $isteacher) {
		echo '<th>', _('Time Spent (In Questions)'), '</th>';
		$sarr = "false,'S','N','N','N','N'";
		if ($includelastchange) {
			echo '<th>'._('Last Changed').'</th>';
			$sarr .= ",'D'";
		}
		if ($includeduedate) {
			echo '<th>'._('Due Date').'</th>';
			$sarr .= ",'D'";
		}
	} else if ($stu==-1) {
		echo '<th>', _('Time Spent (In Questions)'), '</th>';
		$sarr = "'S','N','N','N','N'";
	} else {
		$sarr = "'S','N','N','N'";
	}
	if ($stu>0) {
		echo '<th>', _('Feedback'), '<br/><a href="#" class="small pointer" onclick="return showhideallfb(this);">', _('[Show Feedback]'), '</a></th>';
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
			if ($gbt[0][1][$i][3]>$availshow) {
				continue;
			}
			if ($hidepast && $gbt[0][1][$i][3]==0) {
				continue;
			}
			
			echo '<tr class="grid">';
			if ($stu>0 && $isteacher) { 
				if ($gbt[0][1][$i][6]==0) {
					echo '<td><input type="checkbox" name="assesschk[]" value="'.$gbt[0][1][$i][7] .'" /></td>';
				} else {
					echo '<td></td>';
				}
			}
			
			echo '<td class="cat'.$gbt[0][1][$i][1].'">'.$gbt[0][1][$i][0];
			$afterduelatepass = false;
			if (!$isteacher && !$istutor && $latepasses>0  &&	(
				(isset($gbt[1][1][$i][10]) && $gbt[1][1][$i][10]>0 && !in_array($gbt[0][1][$i][7],$viewedassess)) ||  //started, and already figured it's ok
				(!isset($gbt[1][1][$i][10]) && $now<$gbt[0][1][$i][11]) || //not started, before due date
				(!isset($gbt[1][1][$i][10]) && $gbt[0][1][$i][12]>10 && $now-$gbt[0][1][$i][11]<$latepasshrs*3600 && !in_array($gbt[0][1][$i][7],$viewedassess)) //not started, within one latepass
			    )) {
				echo ' <span class="small"><a href="redeemlatepass.php?cid='.$cid.'&aid='.$gbt[0][1][$i][7].'">[';
				echo _('Use LatePass').']</a></span>';
				if ($now>$gbt[0][1][$i][11]) {
					$afterduelatepass = true;
				}
				
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
							echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$gbt[1][1][$i][4]}&uid={$gbt[1][4][0]}\"";
							if ($afterduelatepass) {
								echo ' onclick="return confirm(\''._('If you view this assignment, you will not be able to use a LatePass on it').'\');"';
							}
							echo ">";
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
				} else if ($gbt[0][1][$i][6]==2) {//discuss
					if ($stu != -1) {
						echo "<a href=\"viewforumgrade.php?cid=$cid&stu=$stu&uid={$gbt[1][4][0]}&fid={$gbt[0][1][$i][7]}\">";
						$haslink = true;
					}
				} else if ($gbt[0][1][$i][6]==3) {//exttool
					if ($isteacher || ($istutor && $gbt[0][1][$i][8]==1)) {
						echo "<a href=\"edittoolscores.php?cid=$cid&stu=$stu&uid={$gbt[1][4][0]}&lid={$gbt[0][1][$i][7]}\">";
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
				if ($gbt[1][1][$i][6]>1) {
					if ($gbt[1][1][$i][6]>2) {
						echo '<sup>LP ('.($gbt[1][1][$i][6]-1).')</sup>';
					} else {
						echo '<sup>LP</sup>';
					}
				} else {
					echo '<sup>e</sup>';
				}
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
				if ($includeduedate) {
					if ($gbt[0][1][$i][11]<2000000000) {
						echo '<td>'.tzdate('n/j/y g:ia',$gbt[0][1][$i][11]);
					} else {
						echo '<td>-</td>';
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
				if ($gbt[1][1][$i][1]=='') {
					echo '<td></td>';
				} else {
					echo '<td><a href="#" class="small feedbacksh pointer" onclick="return showhidefb(this,'.$i.')">', _('[Show Feedback]'), '</a><span style="display:none;" id="feedbackholder'.$i.'">'.$gbt[1][1][$i][1].'</span></td>';
				}
			}
			echo '</tr>';
		}
	}
	echo '</tbody></table><br/>';	
	if (!$hidepast) {
		$query = "SELECT stugbmode FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$show = mysql_result($result,0,0);
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
				if ($availshow<2 && $gbt[0][2][$i][2]>1) {
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
				echo '<td class="cat'.$gbt[0][2][$i][1].'"><span class="cattothdr">'.$gbt[0][2][$i][0].'</span>';
				if (isset($gbt[0][2][$i][11])) {  //category weight
					echo ' ('.$gbt[0][2][$i][11].'%'.')';
				}
				echo '</td>';
				if (($show&1)==1) {
					echo '<td>';
					//show points if not averaging or if points possible scoring
					if ($gbt[0][2][$i][13]==0 || isset($gbt[0][3][0])) {
						echo $gbt[1][2][$i][0].'/'.$gbt[0][2][$i][3].' (';
					}
					if ($gbt[0][2][$i][3]>0) {
						echo round(100*$gbt[1][2][$i][0]/$gbt[0][2][$i][3],1).'%';
					} else {
						echo '0%';
					}
					if ($gbt[0][2][$i][13]==0 || isset($gbt[0][3][0])) {
						echo ')</td>';
					} else {
						echo '</td>';
					}
				}
				if (($show&2)==2) {
					echo '<td>';
					if ($gbt[0][2][$i][13]==0 || isset($gbt[0][3][0])) {
						echo $gbt[1][2][$i][3].'/'.$gbt[1][2][$i][4].' (';
					}
					if ($gbt[1][2][$i][4]>0) {
						echo round(100*$gbt[1][2][$i][3]/$gbt[1][2][$i][4],1).'%';
					} else {
						echo '0%';
					}
					if ($gbt[0][2][$i][13]==0 || isset($gbt[0][3][0])) {
						echo ')</td>';
					} else {
						echo '</td>';
					}
				}
				if (($show&4)==4) {
					echo '<td>';
					if ($gbt[0][2][$i][13]==0 || isset($gbt[0][3][0])) {
						echo $gbt[1][2][$i][1].'/'.$gbt[0][2][$i][4].' (';
					}
					if ($gbt[0][2][$i][4]>0) {
						echo round(100*$gbt[1][2][$i][1]/$gbt[0][2][$i][4],1).'%';
					} else {
						echo '0%';
					}
					if ($gbt[0][2][$i][13]==0 || isset($gbt[0][3][0])) {
						echo ')</td>';
					} else {
						echo '</td>';
					}
				}
				if (($show&8)==8) {
					echo '<td>';
					if ($gbt[0][2][$i][13]==0 || isset($gbt[0][3][0])) {
						echo $gbt[1][2][$i][2].'/'.$gbt[0][2][$i][5].' (';
					}
					if ($gbt[0][2][$i][5]>0) {
						echo round(100*$gbt[1][2][$i][2]/$gbt[0][2][$i][5],1).'%';
					} else {
						echo '0%';
					}
					if ($gbt[0][2][$i][13]==0 || isset($gbt[0][3][0])) {
						echo ')</td>';
					} else {
						echo '</td>';
					}
				}
				
				echo '</tr>';
			}
		}
		//Totals
		if ($catfilter<0) {
			echo '<tr class="grid">';
			if (isset($gbt[0][3][0])) { //using points based
				echo '<td>', _('Total'), '</td>';
				if (($show&1)==1) {
					echo '<td>'.$gbt[1][3][0].'/'.$gbt[0][3][0].' ('.$gbt[1][3][3].'%)</td>';
				}
				if (($show&2)==2) {
					echo '<td>'.$gbt[1][3][6].'/'.$gbt[1][3][7].' ('.$gbt[1][3][8].'%)</td>';
				}
				if (($show&4)==4) {
					echo '<td>'.$gbt[1][3][1].'/'.$gbt[0][3][1].' ('.$gbt[1][3][4].'%)</td>';
				}
				if (($show&8)==8) {
					echo '<td>'.$gbt[1][3][2].'/'.$gbt[0][3][2].' ('.$gbt[1][3][5].'%)</td>';
				}
				
			} else {
				echo '<td>', _('Weighted Total'), '</td>';
				if (($show&1)==1) { echo '<td>'.$gbt[1][3][0].'%</td>';}
				if (($show&2)==2) {echo '<td>'.$gbt[1][3][6].'%</td>';}
				if (($show&4)==4) {echo '<td>'.$gbt[1][3][1].'%</td>';}
				if (($show&8)==8) {echo '<td>'.$gbt[1][3][2].'%</td>';}
			}
			echo '</tr>';
			/*if ($availshow==2) {
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
				echo '<td></td>';
			}
			echo '</tr>';
			
			echo '</tr>';
			echo '<tr class="grid">';
			if (isset($gbt[0][3][0])) { //using points based
				echo '<td>Total Past &amp; Attempted</td>';
				echo '<td>'.$gbt[1][3][7].'&nbsp;pts</td>';
				echo '<td>'.$gbt[1][3][6].'</td>';
				echo '<td>'.$gbt[1][3][8] .'%</td>';
			} else {
				echo '<td>Weighted Total ast &amp; Attempted</td>'; 
				echo '<td></td>';
				echo '<td>'.$gbt[1][3][6].'%</td>';
				echo '<td></td>';
			}
			if ($stu>0) {
				echo '<td></td>';
				echo '<td></td>';
			}
			echo '</tr>';
			*/
			
			
		}
		echo '</tbody></table><br/>';
		echo '<p>';
		if (($show&1)==1) {
			echo _('<b>Past Due</b> total only includes items whose due date has passed.  Current assignments are not counted in this total.'), '<br/>';
		}
		if (($show&2)==2) {
			echo _('<b>Past Due and Attempted</b> total includes items whose due date has passed, as well as currently available items you have started working on.'), '<br/>';
		} 
		if (($show&4)==4) {
			echo _('<b>Past Due and Available</b> total includes items whose due date has passed as well as currently available items, even if you haven\'t starting working on them yet.'), '<br/>';
		}
		if (($show&8)==8) {
			echo _('<b>All</b> total includes all items: past, current, and future to-be-done items.');
		}
		echo '</p>';
	}
	
	if ($hidepast && $isteacher && $stu>0) {
		echo '<p><button type="submit" value="Save Changes" style="display:none"; id="savechgbtn">', _('Save Changes'), '</button>';
		echo '<button type="submit" value="Make Exception" name="massexception" >', _('Make Exception'), '</button> ', _('for selected assessments'), '</p>';
	}
	
	echo "</form>";
	
	echo "<script>initSortTable('myTable',Array($sarr),false);</script>\n";
	/*
	if ($hidepast) {
		echo "<script>initSortTable('myTable',Array($sarr),false);</script>\n";
	} else if ($availshow==2) {
		echo "<script>initSortTable('myTable',Array($sarr),false,-3);</script>\n";
	} else {
		echo "<script>initSortTable('myTable',Array($sarr),false,-2);</script>\n";
	}
	*/
}

function gbinstrdisp() {
	global $hidenc,$showpics,$isteacher,$istutor,$cid,$gbmode,$stu,$availshow,$catfilter,$secfilter,$totonleft,$imasroot,$isdiag,$tutorsection,$avgontop,$hidelocked,$colorize,$urlmode,$overridecollapse,$includeduedate,$lastlogin;

	$curdir = rtrim(dirname(__FILE__), '/\\');
	if ($availshow==4) {
		$availshow=1;
		$hidepast = true;
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
			echo '<br/><span class="small">N='.(count($gbt)-2).'</span><br/>';
			echo "<select id=\"toggle5\" onchange=\"chgtoggle()\">";
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
		//total totals
		if ($catfilter<0) {
			if (isset($gbt[0][3][0])) { //using points based
				echo '<th><div><span class="cattothdr">', _('Total'), '<br/>'.$gbt[0][3][$availshow].'&nbsp;', _('pts'), '</span></div></th>';
				echo '<th><div>%</div></th>';
				$n+=2;
			} else {
				echo '<th><div><span class="cattothdr">', _('Weighted Total %'), '</span></div></th>';
				$n++;
			}
		}
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers	
				if (($availshow<2 || $availshow==3) && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				echo '<th class="cat'.$gbt[0][2][$i][1].'"><div><span class="cattothdr">';
				if ($availshow<3) {
					echo $gbt[0][2][$i][0].'<br/>';
					if (isset($gbt[0][3][0])) { //using points based
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
			if ($collapsegbcat[$gbt[0][1][$i][1]]==2) {
				continue;
			}
			//name and points
			echo '<th class="cat'.$gbt[0][1][$i][1].'"><div>'.$gbt[0][1][$i][0].'<br/>';
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
					echo "<br/><a class=small href=\"addassessment.php?id={$gbt[0][1][$i][7]}&amp;cid=$cid&amp;from=gb\">", _('[Settings]'), "</a>";
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
		if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
			for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers	
				if (($availshow<2 || $availshow==3) && $gbt[0][2][$i][2]>1) {
					continue;
				} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
					continue;
				}
				echo '<th class="cat'.$gbt[0][2][$i][1].'"><div><span class="cattothdr">';
				if ($availshow<3) {
					echo $gbt[0][2][$i][0].'<br/>';
					if (isset($gbt[0][3][0])) { //using points based
						echo $gbt[0][2][$i][3+$availshow].'&nbsp;', _('pts');
					} else {
						echo $gbt[0][2][$i][11].'%';
					}
				} else if ($availshow==3) { //past and attempted
					echo $gbt[0][2][$i][0];
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
		//total totals
		if ($catfilter<0) {
			if (isset($gbt[0][3][0])) { //using points based
				echo '<th><div><span class="cattothdr">', _('Total'), '<br/>'.$gbt[0][3][$availshow].'&nbsp;', _('pts'), '</span></div></th>';
				echo '<th><div>%</div></th>';
				$n+=2;
			} else {
				echo '<th><div><span class="cattothdr">', _('Weighted Total %'), '</span></div></th>';
				$n++;
			}
		}
	}
	echo '</tr></thead><tbody>';
	//create student rows
	if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
		$userimgbase = $urlmode."s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles";
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
			echo $gbt[$i][0][0];
		}
		echo '</a>';
		if ($gbt[$i][4][3]==1) {
			echo '<sup>*</sup>';
		}
		echo '</div></td>';
		if ($showpics==1 && $gbt[$i][4][2]==1) { //file_exists("$curdir//files/userimg_sm{$gbt[$i][4][0]}.jpg")) {
			echo "<td>{$insdiv}<div class=\"trld\"><img src=\"$userimgbase/userimg_sm{$gbt[$i][4][0]}.jpg\"/></div></td>";
		} else if ($showpics==2 && $gbt[$i][4][2]==1) {
			echo "<td>{$insdiv}<div class=\"trld\"><img src=\"$userimgbase/userimg_{$gbt[$i][4][0]}.jpg\"/></div></td>";
		} else {
			echo '<td>'.$insdiv.'<div class="trld">&nbsp;</div></td>';
		}
		for ($j=($gbt[0][0][1]=='ID'?1:2);$j<count($gbt[0][0]);$j++) {
			echo '<td class="c">'.$insdiv.$gbt[$i][0][$j].$enddiv .'</td>';	
		}
		
		if ($totonleft && !$hidepast) {
			//total totals
			if ($catfilter<0) {
				if ($availshow==3) {
					if ($gbt[$i][0][0]=='Averages') { 
						if (isset($gbt[$i][3][8])) { //using points based
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
						}
						echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
					} else {
						if (isset($gbt[$i][3][8])) { //using points based
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'/'.$gbt[$i][3][7].$enddiv.'</td>';
							echo '<td class="c">'.$insdiv.$gbt[$i][3][8] .'%'.$enddiv .'</td>';
							
						} else {
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
						}
					}
				} else {
					if (isset($gbt[0][3][0])) { //using points based
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].$enddiv .'</td>';
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow+3] .'%'.$enddiv .'</td>';
					} else {
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].'%'.$enddiv .'</td>';
					}
				}
			}
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers	
					if (($availshow<2 || $availshow==3) && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					if ($catfilter!=-1 && $availshow<3 && $gbt[0][2][$j][$availshow+3]>0) {
						//echo '<td class="c">'.$gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)</td>';
						echo '<td class="c">'.$insdiv;
						if ($gbt[$i][0][0]=='Averages' && $availshow!=3) {
							echo "<span onmouseover=\"tipshow(this,'", _('5-number summary:'), " {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						echo $gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)';
	
						if ($gbt[$i][0][0]=='Averages' && $availshow!=3) {
							echo '</span>';
						}
						echo $enddiv .'</td>';
					} else {
						//echo '<td class="c">'.$gbt[$i][2][$j][$availshow].'</td>';
						echo '<td class="c">'.$insdiv;
						if ($gbt[$i][0][0]=='Averages') {
							echo "<span onmouseover=\"tipshow(this,'", _('5-number summary:'), " {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						if ($availshow==3) {
							if ($gbt[$i][0][0]=='Averages') {
								echo $gbt[$i][2][$j][3].'%';//echo '-';
							} else {
								echo $gbt[$i][2][$j][3].'/'.$gbt[$i][2][$j][4];
							}
						} else {
							if (isset($gbt[$i][3][8])) { //using points based
								echo $gbt[$i][2][$j][$availshow];
							} else {
								if ($gbt[0][2][$j][3+$availshow]>0) {
									echo round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][3+$availshow],1).'%';
								} else {
									echo '0%';
								}
							}
						}
						if ($gbt[$i][0][0]=='Averages') {
							echo '</span>';
						}
						echo $enddiv .'</td>';
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
						if ($istutor && $gbt[$i][1][$j][4]=='average') {
							
						} else if ($gbt[$i][1][$j][4]=='average') {
							echo "<a href=\"gb-itemanalysis.php?stu=$stu&amp;cid=$cid&amp;asid={$gbt[$i][1][$j][4]}&amp;aid={$gbt[0][1][$j][7]}\" "; 
							echo "onmouseover=\"tipshow(this,'", _('5-number summary:'), " {$gbt[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
							echo ">";
						} else {
							echo "<a href=\"gb-viewasid.php?stu=$stu&amp;cid=$cid&amp;asid={$gbt[$i][1][$j][4]}&amp;uid={$gbt[$i][4][0]}\">";
						}
						if ($gbt[$i][1][$j][3]>9) {
							$gbt[$i][1][$j][3] -= 10;
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
						
					} else { //no score
						if ($gbt[$i][0][0]=='Averages') {
							echo '-';
						} else if ($isteacher) {
							echo "<a href=\"gb-viewasid.php?stu=$stu&amp;cid=$cid&amp;asid=new&amp;aid={$gbt[0][1][$j][7]}&amp;uid={$gbt[$i][4][0]}\">-</a>";
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
							echo "<a href=\"addgrades.php?stu=$stu&amp;cid=$cid&amp;grades=all&amp;gbitem={$gbt[0][1][$j][7]}\" ";
							echo "onmouseover=\"tipshow(this,'", _('5-number summary:'), " {$gbt[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
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
							echo "<span onmouseover=\"tipshow(this,'", _('5-number summary:'), " {$gbt[0][1][$j][9]}')\" onmouseout=\"tipout()\"> ";
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
							echo "<a href=\"edittoolscores.php?stu=$stu&amp;cid=$cid&amp;uid=all&amp;lid={$gbt[0][1][$j][7]}\" ";
							echo "onmouseover=\"tipshow(this,'", _('5-number summary:'), " {$gbt[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
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
				if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5]&(1<<$availshow)) && !$hidepast) {
					echo '<sub>d</sub></span>';
				}
				echo $enddiv .'</td>';
			}
		}
		if (!$totonleft && !$hidepast) {
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers	
					if (($availshow<2 || $availshow==3) && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					if ($catfilter!=-1 && $availshow<3 && $gbt[0][2][$j][$availshow+3]>0) {
						//echo '<td class="c">'.$gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)</td>';
						echo '<td class="c">'.$insdiv;
						if ($gbt[$i][0][0]=='Averages' && $availshow!=3) {
							echo "<span onmouseover=\"tipshow(this,'", _('5-number summary:'), " {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						echo $gbt[$i][2][$j][$availshow].' ('.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][$availshow+3])  .'%)';
	
						if ($gbt[$i][0][0]=='Averages' && $availshow!=3) {
							echo '</span>';
						}
						echo $enddiv .'</td>';
					} else {
						//echo '<td class="c">'.$gbt[$i][2][$j][$availshow].'</td>';
						echo '<td class="c">'.$insdiv;
						if ($gbt[$i][0][0]=='Averages' && $availshow<3) {
							echo "<span onmouseover=\"tipshow(this,'", _('5-number summary:'), " {$gbt[0][2][$j][6+$availshow]}')\" onmouseout=\"tipout()\" >";
						}
						if ($availshow==3) {
							if ($gbt[$i][0][0]=='Averages') {
								echo $gbt[$i][2][$j][3].'%';
							} else {
								echo $gbt[$i][2][$j][3].'/'.$gbt[$i][2][$j][4];
							}
						} else {
							if (isset($gbt[$i][3][8])) { //using points based
								echo $gbt[$i][2][$j][$availshow];
							} else {
								if ($gbt[0][2][$j][3+$availshow]>0) {
									echo round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][3+$availshow],1).'%';
								} else {
									echo '0%';
								}
							}
						}
						if ($gbt[$i][0][0]=='Averages' && $availshow<3) {
							echo '</span>';
						}
						echo $enddiv .'</td>';
					}
					
				}
			}
			
			//total totals
			if ($catfilter<0) {
				if ($availshow==3) {
					if ($gbt[$i][0][0]=='Averages') { 
						if (isset($gbt[$i][3][8])) { //using points based
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
						}
						echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
					} else {
						if (isset($gbt[$i][3][8])) { //using points based
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'/'.$gbt[$i][3][7].$enddiv .'</td>';
							echo '<td class="c">'.$insdiv.$gbt[$i][3][8] .'%'.$enddiv .'</td>';
							
						} else {
							echo '<td class="c">'.$insdiv.$gbt[$i][3][6].'%'.$enddiv .'</td>';
						}
					}
				} else {
					if (isset($gbt[0][3][0])) { //using points based
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].$enddiv .'</td>';
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow+3] .'%'.$enddiv .'</td>';
					} else {
						echo '<td class="c">'.$insdiv.$gbt[$i][3][$availshow].'%'.$enddiv .'</td>';
					}
				}
			}
		}
		echo '</tr>';
	}
	echo "</tbody></table></div></div>";
	if ($n>1) {
		$sarr = array_merge($sortarr, array_fill(0,$n,"'N'"));
	} else {
		$sarr = array();
	}
	
	$sarr = implode(",",$sarr);
	if (count($gbt)<500) {
		if ($avgontop) {
			echo "<script>initSortTable('myTable',Array($sarr),true,true,false);</script>\n";
		} else {
			echo "<script>initSortTable('myTable',Array($sarr),true,false);</script>\n";
		}
	}
	if ($colorize != '0') {
		echo '<script type="text/javascript">addLoadEvent( function() {updateColors(document.getElementById("colorsel"));} );</script>';
	}
		
	
}

?>
