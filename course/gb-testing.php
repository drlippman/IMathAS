<?php
//IMathAS:  GB view for testing center staff
//(c) 2008 David Lippman

require("../init.php");


$cid = Sanitize::courseId($_GET['cid']);
if (isset($teacherid)) {
	$isteacher = true;
}
if (isset($tutorid)) {
	$istutor = true;
}
if ($isteacher || $istutor) {
	$canviewall = true;
} else {
	$canviewall = false;
}
if ($isteacher || $istutor) {

	if (isset($_GET['timefilter'])) {
		$timefilter = $_GET['timefilter'];
		$sessiondata[$cid.'timefilter'] = $timefilter;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'timefilter'])) {
		$timefilter = $sessiondata[$cid.'timefilter'];
	} else {
		$timefilter = 2;
	}
	if (isset($_GET['lnfilter'])) {
		$lnfilter = trim($_GET['lnfilter']);
		$sessiondata[$cid.'lnfilter'] = $lnfilter;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'lnfilter'])) {
		$lnfilter = $sessiondata[$cid.'lnfilter'];
	} else {
		$lnfilter = '';
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
	$totonleft = 0 ;
	$links = 0;
	$hidenc = 2;
	$availshow = 1;
	$catfilter = -1;

} else {
	echo "Go away";
	exit;
}


//DISPLAY
require("gbtable2.php");
require("../includes/htmlutil.php");

$placeinhead = '';

$placeinhead .= "<script type=\"text/javascript\">";
$placeinhead .= 'function chgtimefilter() { ';
$placeinhead .= '       var tm = document.getElementById("timetoggle").value; ';
$address = $GLOBALS['basesiteurl'] . "/course/gb-testing.php?stu=$stu&cid=$cid";

$placeinhead .= "       var toopen = '$address&timefilter=' + tm;\n";
$placeinhead .= "  	window.location = toopen; \n";
$placeinhead .= "}\n";

$placeinhead .= 'function chglnfilter() { ';
$placeinhead .= '       var ln = document.getElementById("lnfilter").value; ';
$address = $GLOBALS['basesiteurl'] . "/course/gb-testing.php?stu=$stu&cid=$cid";

$placeinhead .= "       var toopen = '$address&lnfilter=' + ln;\n";
$placeinhead .= "  	window.location = toopen; \n";
$placeinhead .= "}\n";

$placeinhead .= 'function chgsecfilter() { ';
$placeinhead .= '       var sec = document.getElementById("secfiltersel").value; ';
$address = $GLOBALS['basesiteurl'] . "/course/gb-testing.php?stu=$stu&cid=$cid";

$placeinhead .= "       var toopen = '$address&secfilter=' + sec;\n";
$placeinhead .= "  	window.location = toopen; \n";
$placeinhead .= "}\n";
$placeinhead .= 'function showendmsgs() {
	$(".endmsg").each(function() {
		var short = $(this).find(".short").text();
		if (short!="") {
			$(this).html("<br/>"+short);
		}
	});
	$(".endmsg").show();}';
$placeinhead .= "</script>";



 //show instructor view

$placeinhead .= "<script type=\"text/javascript\">function lockcol() { \n";
$placeinhead .= " var cont = document.getElementById(\"tbl-container\");\n";
$placeinhead .= " if (cont.style.overflow == \"auto\") {\n";
$placeinhead .= "   cont.style.height = \"auto\"; cont.style.overflow = \"visible\"; cont.style.border = \"0px\";";
$placeinhead .= "document.getElementById(\"myTable\").className = \"gb\"; document.cookie = 'gblhdr-$cid=0';";
$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Lock headers\"; } else {";
$placeinhead .= " cont.style.height = \"75%\"; cont.style.overflow = \"auto\"; cont.style.border = \"1px solid #000\";\n";
$placeinhead .= "document.getElementById(\"myTable\").className = \"gbl\"; document.cookie = 'gblhdr-$cid=1'; ";
$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Unlock headers\"; }";
$placeinhead .= "} ";
$placeinhead .= "</script>\n";
$placeinhead .= "<style type=\"text/css\"> table.gb { margin: 0px; } .endmsg {display:none;}</style>";

require("../header.php");
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
echo "&gt; Diagnostic Gradebook</div>";
echo "<form method=post action=\"gradebook.php?cid=$cid\">";

echo '<div id="headergb-testing" class="pagetitle"><h1>Diagnostic Grade Book</h1></div>';
echo "<a href=\"gradebook.php?cid=$cid\">View regular gradebook</a>";
echo "<div class=cpmid>";

echo "Students starting in: <select id=\"timetoggle\" onchange=\"chgtimefilter()\">";
echo "<option value=1 "; writeHtmlSelected($timefilter,1); echo ">last 1 hour</option>";
echo "<option value=2 "; writeHtmlSelected($timefilter,2); echo ">last 2 hours</option>";
echo "<option value=4 "; writeHtmlSelected($timefilter,4); echo ">last 4 hours</option>";
echo "<option value=24 "; writeHtmlSelected($timefilter,24); echo ">last day</option>";
echo "<option value=168 "; writeHtmlSelected($timefilter,168); echo ">last week</option>";
echo "<option value=720 "; writeHtmlSelected($timefilter,720); echo ">last month</option>";
echo "<option value=8760 "; writeHtmlSelected($timefilter,8760); echo ">last year</option>";
echo "<option value=0 "; writeHtmlSelected($timefilter,0); echo ">all time</option>";
echo "</select>";

echo " Last name: <input type=text id=\"lnfilter\" value=\"" . Sanitize::encodeStringForDisplay($lnfilter) . "\" />";
echo "<input type=button value=\"Filter by name\" onclick=\"chglnfilter()\" />";
echo ' <button type="button" id="endmsgbtn" onclick="showendmsgs()" style="display:none;">Show End Messages</button>';
echo "</div>";

$gbt = gbinstrdisp();
echo "</form>";
echo "</div>";
require("../footer.php");
//echo "Meanings:  IP-In Progress, OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sup>*</sup> Has feedback, <sub>d</sub> Dropped score\n";
echo "Meanings:   NC-no credit";
/*if ($isteacher) {
	echo "<div class=cp>";
	echo "<a href=\"addgrades.php?cid=$cid&gbitem=new&grades=all\">Add Offline Grade</a><br/>";
	echo "<a href=\"gradebook.php?stu=$stu&cid=$cid&export=true\">Export Gradebook</a><br/>";
	echo "Email gradebook to <a href=\"gradebook.php?stu=$stu&cid=$cid&emailgb=me\">Me</a> or <a href=\"gradebook.php?stu=$stu&cid=$cid&emailgb=ask\">to another address</a><br/>";
	echo "<a href=\"gbsettings.php?cid=$cid\">Gradebook Settings</a>";
	echo "<div class=clear></div></div>";
}
*/



function gbinstrdisp() {
	global $DBH,$isteacher,$istutor,$cid,$stu,$isdiag,$catfilter,$secfilter,$imasroot,$tutorsection,$includeendmsg;
	$hidenc = 1;
	$includeendmsg = true;
	$hasendmsg = false;
	$gbt = gbtable();
	//print_r($gbt);
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
	echo "<div id=\"tbl-container\">";
	echo "<table class=gb id=myTable><thead><tr>";
	$n=0;


	for ($i=0;$i<count($gbt[0][0]);$i++) { //biographical headers
		if ($i==1 && $gbt[0][0][1]!='ID') { continue;}
		echo '<th>'.$gbt[0][0][$i];
		if (($gbt[0][0][$i]=='Section' || ($isdiag && $i==4)) && (!$istutor || $tutorsection=='')) {
			echo "<br/><select id=\"secfiltersel\" onchange=\"chgsecfilter()\"><option value=\"-1\" ";
			if ($secfilter==-1) {echo  'selected=1';}
			echo  '>All</option>';
			//DB $query = "SELECT DISTINCT section FROM imas_students WHERE courseid='$cid' ORDER BY section";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
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
			echo '<br/><span class="small">N='.(count($gbt)-2).'</span>';
		}
		echo '</th>';

		$n++;
	}


	for ($i=0;$i<count($gbt[0][1]);$i++) { //assessment headers
		if (!$isteacher && $gbt[0][1][$i][4]==0) { //skip if hidden
			continue;
		}
		if ($hidenc==1 && $gbt[0][1][$i][4]==0) { //skip NC
			continue;
		} else if ($hidenc==2 && ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3)) {//skip all NC
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
		if ($isteacher) {
			if ($gbt[0][1][$i][6]==0) { //online
				echo "<br/><a class=small href=\"addassessment.php?id={$gbt[0][1][$i][7]}&cid=$cid&from=gb\">[Settings]</a>";
				echo "<br/><a class=small href=\"isolateassessgrade.php?cid=$cid&aid={$gbt[0][1][$i][7]}\">[Isolate]</a>";
			} else if ($gbt[0][1][$i][6]==1) { //offline
				echo "<br/><a class=small href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$i][7]}\">[Settings]</a>";
				echo "<br/><a class=small href=\"addgrades.php?stu=$stu&cid=$cid&grades=all&gbitem={$gbt[0][1][$i][7]}&isolate=true\">[Isolate]</a>";
			} else if ($gbt[0][1][$i][6]==2) { //discussion
				echo "<br/><a class=small href=\"addforum.php?id={$gbt[0][1][$i][7]}&cid=$cid&from=gb\">[Settings]</a>";
			}
		}

		echo '</th>';
		$n++;
	}

	echo '</tr></thead><tbody>';
	//create student rows
	for ($i=1;$i<count($gbt)-1;$i++) {
		if ($i%2!=0) {
			echo "<tr class=even onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='even'\">";
		} else {
			echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">";
		}
		echo '<td class="locked" scope="row">';

		echo "<a href=\"gradebook.php?cid=$cid&stu={$gbt[$i][4][0]}\">";
		echo $gbt[$i][0][0];
		echo '</a></td>';

		for ($j=($gbt[0][0][1]=='ID'?1:2);$j<count($gbt[0][0]);$j++) {
			echo '<td class="c">'.$gbt[$i][0][$j].'</td>';
		}

		//assessment values

		for ($j=0;$j<count($gbt[0][1]);$j++) {
			if ($gbt[0][1][$j][4]==0) { //skip if hidden
				continue;
			}
			if ($hidenc==1 && $gbt[0][1][$j][4]==0) { //skip NC
				continue;
			} else if ($hidenc==2 && ($gbt[0][1][$j][4]==0 || $gbt[0][1][$j][4]==3)) {//skip all NC
				continue;
			}


			echo '<td class="c">';
			if (isset($gbt[$i][1][$j][5])) {
				echo '<span style="font-style:italic">';
			}
			if ($gbt[0][1][$j][6]==0) {//online
				if (isset($gbt[$i][1][$j][0])) {
					if ($gbt[$i][1][$j][4]=='average') {
						echo "<a href=\"gb-itemanalysis.php?stu=$stu&cid=$cid&asid={$gbt[$i][1][$j][4]}&aid={$gbt[0][1][$j][7]}\">";
					} else {
						echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$gbt[$i][1][$j][4]}&uid={$gbt[$i][4][0]}&from=gbtesting\">";
					}
					echo $gbt[$i][1][$j][0];
					if ($gbt[$i][1][$j][3]==1) {
						echo ' (NC)';
					}
					/*else if ($gbt[$i][1][$j][3]==2) {
						echo ' (IP)';
					} else if ($gbt[$i][1][$j][3]==3) {
						echo ' (OT)';
					} else if ($gbt[$i][1][$j][3]==4) {
						echo ' (PT)';
					} */
					echo '</a>';
					if ($gbt[$i][1][$j][1]==1) {
						echo '<sup>*</sup>';
					}
					if (isset($gbt[$i][1][$j][11])) {
						echo '<span class="endmsg"><br/>'.$gbt[$i][1][$j][11].'</span>';
						$hasendmsg = true;
					}
				} else { //no score
					if ($gbt[$i][0][0]=='Averages') {
						echo '-';
					} else {
						echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid=new&aid={$gbt[0][1][$j][7]}&uid={$gbt[$i][4][0]}\">-</a>";
					}
				}
			} else if ($gbt[0][1][$j][6]==1) { //offline
				if ($isteacher) {
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
				if ($isteacher) {
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
			if (isset($gbt[$i][1][$j][5])) {
				echo '<sub>d</sub></span>';
			}
			echo '</td>';
		}

	}
	echo "</tbody></table>";
	if ($n>0) {
		$sarr = array_fill(0,$n-1,"'N'");
	} else {
		$sarr = array();
	}
	array_unshift($sarr,"'S'");

	$sarr = implode(",",$sarr);
	if (count($gbt)<500) {
		echo "<script>initSortTable('myTable',Array($sarr),true,false);</script>\n";
	}
	if ($hasendmsg) {
		echo '<script type="text/javascript">$(function(){ $("#endmsgbtn").show(); });</script>';
	}


}

?>
