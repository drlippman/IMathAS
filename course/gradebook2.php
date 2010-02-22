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

// TODO:
//   exceptions


require("../validate.php");
$cid = $_GET['cid'];
if (isset($teacherid)) {
	$isteacher = true;
} 
if ($isteacher) {
	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
		$sessiondata[$cid.'gbmode'] = $gbmode;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'gbmode'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		$query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$gbmodet = mysql_result($result,0,0);
		$gbmode = 2;
		if (($gbmodet&8)==8) { $gbmode += 1000;}
		if (($gbmodet&16)==16) {$gbmode += 20;}
		if (($gbmodet&4)==4) {$gbmode -= 1;}
		if (($gbmodet&1)==1) {$gbmode += 100;}
		
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
	if (isset($_GET['secfilter'])) {
		$secfilter = $_GET['secfilter'];
		$sessiondata[$cid.'secfilter'] = $secfilter;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'secfilter'])) {
		$secfilter = $sessiondata[$cid.'secfilter'];
	} else {
		$secfilter = -1;
	}
	//Gbmode : Links NC Dates
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
	$totonleft = 0;
}

if ($isteacher && isset($_GET['stu'])) {
	$stu = $_GET['stu'];
} else {
	$stu = 0;
}

//HANDLE ANY POSTS
if ($isteacher) {
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

if (!$isteacher || $stu!=0) { //show student view
	if (!$isteacher) {
		$stu = $userid;
	}
	$pagetitle = "Gradebook";
	require("../header.php");
	if (isset($_GET['from']) && $_GET['from']=="listusers") {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt Student Grade Detail</div>\n";
	} else if ($isteacher) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; Student Detail</div>";
	} else {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; Gradebook</div>";
	}
	if ($stu==-1) {
		echo "<h2>Grade Book Averages DRAFT</h2>\n";
	} else {
		echo "<h2>Grade Book Student Detail DRAFT</h2>\n";
	}
	
	gbstudisp($stu);
	require("../footer.php");
	
} else { //show instructor view
	
	$placeinhead = "<script type=\"text/javascript\">function lockcol() { \n";
	$placeinhead .= " var cont = document.getElementById(\"tbl-container\");\n";
	$placeinhead .= " if (cont.style.overflow == \"auto\") {\n";
	$placeinhead .= "   cont.style.height = \"auto\"; cont.style.overflow = \"visible\"; cont.style.border = \"0px\";";
	$placeinhead .= "document.getElementById(\"myTable\").className = \"gb\"; document.cookie = 'gblhdr-$cid=0';";
	$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Lock headers\"; } else {";
	$placeinhead .= " cont.style.height = \"75%\"; cont.style.overflow = \"auto\"; cont.style.border = \"1px solid #000\";\n";
	$placeinhead .= "document.getElementById(\"myTable\").className = \"gbl\"; document.cookie = 'gblhdr-$cid=1'; ";
	$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Unlock headers\"; }";
	$placeinhead .= "} ";
	$placeinhead .= 'function chgfilter() { ';
	$placeinhead .= '       var cat = document.getElementById("filtersel").value; ';
	$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook2.php?stu=$stu&cid=$cid";
	
	$placeinhead .= "       var toopen = '$address&catfilter=' + cat;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	$placeinhead .= 'function chgsecfilter() { ';
	$placeinhead .= '       var sec = document.getElementById("secfiltersel").value; ';
	$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook2.php?stu=$stu&cid=$cid";
	
	$placeinhead .= "       var toopen = '$address&secfilter=' + sec;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	$placeinhead .= 'function chgtoggle() { ';
	$placeinhead .= "	var altgbmode = 1000*$totonleft + 100*document.getElementById(\"toggle1\").value + 10*document.getElementById(\"toggle2\").value + 1*document.getElementById(\"toggle3\").value; ";
	$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook2.php?stu=$stu&cid=$cid&gbmode=";
	$placeinhead .= "	var toopen = '$address' + altgbmode;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	$placeinhead .= "</script>\n";
	$placeinhead .= "<style type=\"text/css\"> table.gb { margin: 0px; } </style>";
	
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; Gradebook</div>";
	echo "<form method=post action=\"gradebook.php?cid=$cid\">";
	
	echo "<span class=\"hdr1\">Grade Book DRAFT</span>";
	echo "<div class=cpmid>";
	echo "<a href=\"addgrades.php?cid=$cid&gbitem=new&grades=all\">Add Offline Grade</a> | ";
	echo "Export to <a href=\"gb-export.php?stu=$stu&cid=$cid&export=true\">File</a>, ";
	echo "<a href=\"gb-export.php?stu=$stu&cid=$cid&emailgb=me\">My Email</a>, or <a href=\"gb-export.php?stu=$stu&cid=$cid&emailgb=ask\">Other Email</a> | ";
	echo "<a href=\"gbsettings.php?cid=$cid\">GB Settings</a> | ";
	echo "<a href=\"gradebook.php?cid=$cid&stu=-1\">Averages</a> | ";
	echo "<a href=\"gbcomments.php?cid=$cid&stu=0\">Comments</a> | ";
	echo "<input type=\"button\" id=\"lockbtn\" onclick=\"lockcol()\" value=\"Lock headers\"/> (IE)<br/>\n";
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
	/*
	echo 'Toggle: <select id="toggleview" onchange="chgtoggle()">';
	echo '<option value="-1">Select toggle</option>';
	if ($nopracticet) {
		$altgbmode = $gbmode+2;
		echo "<option value=\"$altgbmode\">Practice Test: hidden -> shown</option>";
	} else {
		$altgbmode = $gbmode-2;
		echo "<option value=\"$altgbmode\">Practice Test: shown -> hidden</option>";
	} 
	if (($gbmode&1)==1) {
		$altgbmode = $gbmode-1;
		echo "<option value=\"$altgbmode\">Links: question breakdown -> edit</option>";
	} else {
		$altgbmode = $gbmode+1;
		echo "<option value=\"$altgbmode\">Links: edit -> question breakdown</option>";
	}
	if ($curonly) {
		$altgbmode = $gbmode-4;
		echo "<option value=\"$altgbmode\">Items shown: available -> all</option>";
	} else {
		$altgbmode = $gbmode+4;
		echo "<option value=\"$altgbmode\">Items shown: all -> available and past</option>";
	}
	if ($hidenc) {
		$altgbmode = $gbmode-16;
		echo "<option value=\"$altgbmode\">Items shown: counted -> all</option>";
	} else {
		$altgbmode = $gbmode+16;
		echo "<option value=\"$altgbmode\">Items shown: all -> counted</option>";
	}
	echo '</select>';
	*/
	echo "Not Counted: <select id=\"toggle2\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($hidenc,0); echo ">Show all</option>";
	echo "<option value=1 "; writeHtmlSelected($hidenc,1); echo ">Show stu view</option>";
	echo "<option value=2 "; writeHtmlSelected($hidenc,2); echo ">Hide all</option>";
	echo "</select>";
	echo " | Show: <select id=\"toggle3\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($availshow,0); echo ">Past due</option>";
	echo "<option value=1 "; writeHtmlSelected($availshow,1); echo ">Past & current</option>";
	echo "<option value=2 "; writeHtmlSelected($availshow,2); echo ">All</option></select>";
	echo " | Links: <select id=\"toggle1\" onchange=\"chgtoggle()\">";
	echo "<option value=0 "; writeHtmlSelected($links,0); echo ">View/Edit</option>";
	echo "<option value=1 "; writeHtmlSelected($links,1); echo ">Scores</option></select>";
	echo "</div>";
	
	echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca\" value=\"1\" onClick=\"chkAll(this.form, 'checked[]', this.checked)\"> \n";
	echo "With Selected:  <input type=submit name=submit value=\"E-mail\"> <input type=submit name=submit value=\"Message\"> <input type=submit name=submit value=\"Unenroll\"> <input type=submit name=submit value=\"Make Exception\"> ";
	
	$gbt = gbinstrdisp();
	echo "</form>";
	echo "</div>";
	require("../footer.php");
	echo "Meanings:  IP-In Progress, OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sup>*</sup> Has feedback\n";
	if ($isteacher) {
		echo "<div class=cp>";
		echo "<a href=\"addgrades.php?cid=$cid&gbitem=new&grades=all\">Add Offline Grade</a><br/>";
		echo "<a href=\"gradebook.php?stu=$stu&cid=$cid&export=true\">Export Gradebook</a><br/>";
		echo "Email gradebook to <a href=\"gradebook.php?stu=$stu&cid=$cid&emailgb=me\">Me</a> or <a href=\"gradebook.php?stu=$stu&cid=$cid&emailgb=ask\">to another address</a><br/>";
		echo "<a href=\"gbsettings.php?cid=$cid\">Gradebook Settings</a>";
		echo "<div class=clear></div></div>";
	}
}

function gbstudisp($stu) {
	global $hidenc,$cid,$gbmode,$availshow,$isdiag,$isteacher;

	$gbt = gbtable($stu);
	
	if ($stu>0) {
		if ($isteacher && isset($_POST['usrcomments'])) {
			$query = "UPDATE imas_students SET gbcomment='{$_POST['usrcomments']}' WHERE userid='$stu'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			echo "<p>Comment Updated</p>";
		}
		echo '<h3>' . strip_tags($gbt[1][0][0]) . '</h3>';
		$query = "SELECT imas_students.gbcomment,imas_users.email FROM imas_students,imas_users WHERE ";
		$query .= "imas_students.userid=imas_users.id AND imas_users.id='$stu' AND imas_students.courseid='{$_GET['cid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			if ($isteacher) {
				echo '<a href="mailto:'.mysql_result($result,0,1).'">Email</a> | ';
				echo "<a href=\"$imasroot/msgs/msglist.php?cid={$_GET['cid']}&add=new&to=$stu\">Message</a> | ";
				echo "<a href=\"exception.php?cid={$_GET['cid']}&uid=$stu\">Make Exception</a> | ";
				echo "<a href=\"listusers.php?cid={$_GET['cid']}&chgstuinfo=true&uid=$stu\">Change Info</a>";
			}
			$gbcomment = mysql_result($result,0,0);
		} else {
			$gbcomment = '';
		}
		if (trim($gbcomment)!='' || $isteacher) {
			if ($isteacher) {
				echo "<form method=post action=\"gradebook2.php?{$_SERVER['QUERY_STRING']}\">";
				echo "<textarea name=\"usrcomments\" rows=3 cols=60>$gbcomment</textarea><br/>";
				echo "<input type=submit value=\"Update Comment\">";
				echo "</form>";
			} else {
				echo "<div class=\"item\">$gbcomment</div>";
			}
		}
	}
	
	echo '<table class=gb>';
	echo '<thead><tr><th>Item</th><th>Possible</th><th>Grade</th><th>Percent</th>';
	if ($stu>0) {
		echo '<th>Feedback</th>';
	} 
	echo '</tr></thead><tbody>';
	for ($i=0;$i<count($gbt[0][1]);$i++) { //assessment headers
		if (!$isteacher && $gbt[0][1][$i][4]==0) { //skip if hidden 
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
		if ($gbt[0][1][$i][5]==1) {
			echo ' (PT)';
		}
		
		echo '</td><td>';
		if ($isteacher || $gbt[1][1][$i][2]==1) { //show link
			if ($gbt[0][1][$i][6]==0) {//online
				if (isset($gbt[1][1][$i][0])) { //has score
					echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$gbt[1][1][$i][4]}&uid={$gbt[1][4][0]}\">";
				} else if ($isteacher) {
					echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid=new&uid={$gbt[1][4][0]}\">";
				}
			} else if ($gbt[0][1][$i][6]==1) {//offline
				if ($isteacher) {
					echo "<a href=\"addgrades.php?stu=$stu&cid=$cid&grades={$gbt[1][4][0]}&gbitem={$gbt[0][1][$i][7]}\">";
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
				echo ' {PT)';
			}
		} else {
			echo '-';
		}
		if (($isteacher || $gbt[1][1][$i][2]==1) && ($gbt[0][1][$i][6]==0 || ($gbt[0][1][$i][6]==1 && $isteacher))) { //show link
			echo '</a>';
		}
		echo '</td><td>';
		if (isset($gbt[1][1][$i][0])) {
			echo round(100*$gbt[1][1][$i][0]/$gbt[0][1][$i][2],1).'%';
		} else {
			echo '0%';
		}
		echo '</td>';
		if ($stu>0) {
			echo '<td>'.$gbt[1][1][$i][1].'</td>';
		}
		echo '</tr>';
	}
	if (count($gbt[0][2])>1) { //want to show cat headers?
		for ($i=0;$i<count($gbt[0][2]);$i++) { //category headers	
			if ($availshow<2 && $gbt[0][2][$i][2]>1) {
				continue;
			} else if ($availshow==2 && $gbt[0][2][$i][2]==3) {
				continue;
			}
			echo '<tr class="grid">';
			echo '<td class="cat'.$gbt[0][2][$i][1].'"><stpan class="cattothdr">'.$gbt[0][2][$i][0].'</span></td>';
			echo '<td>'.$gbt[0][2][$i][3+$availshow].'&nbsp;pts</td>';
			echo '<td>'.$gbt[1][2][$i][$availshow].'</td>';
			echo '<td>'.round(100*$gbt[1][2][$i][$availshow]/$gbt[0][2][$i][3+$availshow],1).'%</td>';
			if ($stu>0) {
				echo '<td></td>';
			}
			echo '</tr>';
		}
	}
	//Totals
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
		echo '<td>Weighted Total Due %</td>'; 
		echo '<td></td>';
		echo '<td>'.$gbt[1][3][0].'%</td>';
		echo '<td></td>';
	}
	if ($stu>0) {
		echo '<td></td>';
	}
	echo '</tr>';
	
	echo '</tbody></table>';	
}

function gbinstrdisp() {
	global $hidenc,$isteacher,$cid,$gbmode,$stu,$availshow,$isdiag,$catfilter,$secfilter,$totonleft;
	$gbt = gbtable();
	//print_r($gbt);
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
	echo "<div id=\"tbl-container\">";
	echo "<table class=gb id=myTable><thead><tr>";
	$n=0;
	for ($i=0;$i<count($gbt[0][0]);$i++) { //biographical headers
		if ($i==1) { continue;}
		echo '<th>'.$gbt[0][0][$i];
		if ($gbt[0][0][$i]=='Section') {
			echo "<br/><select id=\"secfiltersel\" onchange=\"chgsecfilter()\"><option value=\"-1\" ";
			if ($secfilter==-1) {echo  'selected=1';}
			echo  '>All</option>';
			$query = "SELECT DISTINCT section FROM imas_students WHERE courseid='$cid' ORDER BY section";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
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
	if ($totonleft) {
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
			if (!$isteacher && $gbt[0][1][$i][4]==0) { //skip if hidden 
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
			if ($gbt[0][1][$i][5]==1) {
				echo ' (PT)';
			}
			//links
			if ($gbt[0][1][$i][6]==0) { //online
				echo "<br/><a class=small href=\"addassessment.php?id={$gbt[0][1][$i][7]}&cid=$cid&from=gb\">[Settings]</a>";
				echo "<br/><a class=small href=\"isolateassessgrade.php?cid=$cid&aid={$gbt[0][1][$i][7]}\">[Isolate]</a>";
			} else if ($gbt[0][1][$i][6]==1) { //offline
				echo "<br/><a class=small href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$i][7]}\">[Settings]</a>";
				echo "<br/><a class=small href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$i][7]}&isolate=true\">[Isolate]</a>";
			} else if ($gbt[0][1][$i][6]==2) { //discussion
				echo "<br/><a class=small href=\"addforum.php?id={$gbt[0][1][$i][7]}&cid=$cid&from=gb\">[Settings]</a>";
			}
			echo '</th>';
			$n++;
		}
	}
	if (!$totonleft) {
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
	//create student rows
	for ($i=1;$i<count($gbt);$i++) {
		if ($i%2!=0) {
			echo "<tr class=even onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='even'\">"; 
		} else {
			echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">"; 
		}
		echo '<td class="locked" scope="row">';
		echo "<input type=\"checkbox\" name='checked[]' value='{$gbt[$i][4][0]}' />&nbsp;";
		echo "<a href=\"gradebook2.php?cid=$cid&stu={$gbt[$i][4][0]}\">";
		echo $gbt[$i][0][0];
		echo '</a></td>';
		for ($j=2;$j<count($gbt[0][0]);$j++) {
			echo '<td class="c">'.$gbt[$i][0][$j].'</td>';	
		}
		if ($totonleft) {
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
					echo '<td class="c">'.$gbt[$i][2][$j][$availshow].'</td>';
				}
			}
		}
		//assessment values
		if ($catfilter>-2) {
			for ($j=0;$j<count($gbt[0][1]);$j++) {
				if (!$isteacher && $gbt[0][1][$j][4]==0) { //skip if hidden 
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
				echo '<td class="c">';
				if ($gbt[0][1][$j][6]==0) {//online
					if (isset($gbt[$i][1][$j][0])) {
						if ($gbt[$i][1][$j][4]=='average') {
							echo "<a href=\"gb-itemanalysis.php?stu=$stu&cid=$cid&asid={$gbt[$i][1][$j][4]}&aid={$gbt[0][1][$j][7]}\">";
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
							echo ' {PT)';
						} 
						echo '</a>';
						if ($gbt[$i][1][$j][1]==1) {
							echo '<sup>*</sup>';
						}
					} else { //no score
						echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid=new&uid={$gbt[$i][4][0]}\">-</a>";
					}
				} else if ($gbt[0][1][$j][6]==1) { //offline
					if ($gbt[$i][1][$j][4]=='average') {
						echo "<a href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$j][7]}\">";
					} else {
						echo "<a href=\"addgrades.php?stu=$stu&cid=$cid&grades={$gbt[$i][4][0]}&gbitem={$gbt[0][1][$j][7]}\">";
					}
					if (isset($gbt[$i][1][$j][0])) {
						echo $gbt[$i][1][$j][0];
						if ($gbt[$i][1][$j][3]==1) {
							echo ' (NC)';
						}
					} else {
						echo '-';
					}
					echo '</a>';
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
				echo '</td>';
			}
		}
		if (!$totonleft) {
			//category totals
			if (count($gbt[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
				for ($j=0;$j<count($gbt[0][2]);$j++) { //category headers	
					if ($availshow<2 && $gbt[0][2][$j][2]>1) {
						continue;
					} else if ($availshow==2 && $gbt[0][2][$j][2]==3) {
						continue;
					}
					echo '<td class="c">'.$gbt[$i][2][$j][$availshow].'</td>';
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
	}
		
	echo "</thead><tbody>";
	echo "</tbody></table>";
	$sarr = implode(",",array_fill(0,$n,"'S'"));
	echo "<script>initSortTable('myTable',Array($sarr),true,false);</script>\n";
		
	
}

?>
