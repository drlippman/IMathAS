<?php
//IMathAS:  Outcomes report generator
//(c) 2013 David Lippman for Lumen Learning

require("../init.php");


if (!isset($teacherid) && !isset($studentid)) {
	echo "You're not validated to view this page."; exit;
}

require("outcometable.php");
$canviewall = true;
$catfilter = -1;
$secfilter = -1;

//load outcomes
//DB $query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB $row = mysql_fetch_row($result);
$stm = $DBH->prepare("SELECT outcomes FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$row = $stm->fetch(PDO::FETCH_NUM);
if ($row[0]=='') {
	$outcomes = array();
} else {
	$outcomes = unserialize($row[0]);
	if (!is_array($outcomes)) {
		$outcomes = array();
	}
}

$outcomeinfo = array();
//DB $query = "SELECT id,name FROM imas_outcomes WHERE courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$outcomeinfo[$row[0]] = $row[1];
}

if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
	$gbmode = $_GET['gbmode'];
} else if (isset($sessiondata[$cid.'gbmode'])) {
	$gbmode =  $sessiondata[$cid.'gbmode'];
} else {
	//DB $query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $gbmode = mysql_result($result,0,0);
	$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$gbmode = $stm->fetchColumn(0);
}
$hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked


$outc = array();
$outcomegroups = array();
function flattenout($arr,$level) {
	global $outc,$outcomegroups;
	foreach ($arr as $k=>$oi) {
		if (is_array($oi)) {
			$outcomegroups[$level.'-'.$k] = $oi['name'];
			flattenout($oi['outcomes'],$level.'-'.$k);
		} else {
			$outc[] = $oi;
		}
	}
}
flattenout($outcomes,'0');

if (isset($studentid)) {
	$stu = intval($userid);
	$report = 'onestu';
	$qs = '&stu='.$stu;
} else if (isset($_GET['stu'])) {
	$stu = intval($_GET['stu']);
	$report = 'onestu';
	$qs = '&stu='.$stu;
} else if (isset($_GET['outcome'])) {
	$outcome = intval($_GET['outcome']);
	$report = 'oneoutcome';
	$qs = '&outcome='.$outcome;
} else if (isset($_GET['export'])) {
	$report = 'export';
} else {
	$report = 'overview';
	$qs = '';
}
if (isset($_GET['type'])) {
	$type = intval($_GET['type']);
} else {
	$type = 1;  //0 past, 1 attempted
}
$typesel = _('Show for scores: ').'<select id="typesel" onchange="chgtype()">';
$typesel .= '<option value="0" '.($type==0?'selected="selected"':'').'>'._('Past Due scores').'</option>';
$typesel .= '<option value="1" '.($type==1?'selected="selected"':'').'>'._('Past Due and Attempted scores').'</option>';
$typesel .= '</select>';

$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
if ($report == 'overview') {
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablescroller2.js?v=012514\"></script>\n";
	$placeinhead .= "<script type=\"text/javascript\">\n";
	$placeinhead .= 'var ts = new tablescroller("myTable",';
	if (isset($_COOKIE["ocrhdr-$cid"]) && $_COOKIE["ocrhdr-$cid"]==1) {
		$placeinhead .= 'true,false);';
		$headerslocked = true;
	} else {
		if (!isset($_COOKIE["ocrhdr-$cid"]) && isset($CFG['GBS']['lockheader']) && $CFG['GBS']['lockheader']==true) {
			$placeinhead .= 'true,false);';
			$headerslocked = true;
		} else {
			$placeinhead .= 'false,false);';
			$headerslocked = false;
			$usefullwidth = true;
		}
	}
	$placeinhead .= "\nfunction lockcol() { \n";
	$placeinhead .= "var tog = ts.toggle(); ";
	$placeinhead .= "document.cookie = 'ocrhdr-$cid=1';\n document.getElementById(\"lockbtn\").value = \"" . _('Unlock headers') . "\"; ";
	$placeinhead .= "if (tog==1) { "; //going to locked
	$placeinhead .= "} else {";
	$placeinhead .= "document.cookie = 'ocrhdr-$cid=0';\n document.getElementById(\"lockbtn\").value = \"" . _('Lock headers') . "\"; ";
	$placeinhead .= "}}\n ";
	$placeinhead .= "function cancellockcol() {document.cookie = 'ocrhdr-$cid=0';\n document.getElementById(\"lockbtn\").value = \"" . _('Lock headers') . "\";}\n";
	$placeinhead .= '</script>';
}

$address = $GLOBALS['basesiteurl'] . "/course/outcomereport.php?cid=$cid".$qs;

$placeinhead .= '<script type="text/javascript"> var selfaddr = "'.$address.'";
	function chgtype() {
		var type = document.getElementById("typesel").value;
		window.location = selfaddr+"&type="+type;
	}
	</script>';

if ($report != 'export') {
	require("../header.php");

	$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> ".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	if (isset($teacherid)) {
		$curBreadcrumb .= "<a href=\"addoutcomes.php?cid=$cid\">"._("Course Outcomes")."</a>\n";
	} else if (isset($studentid)) {
		$curBreadcrumb .= "<a href=\"gradebook.php?cid=$cid\">"._("Gradebook")."</a>\n";
	}
}


if ($report=='overview') {
	$gcnt = -1;
	function printOutcomeRow($arr,$isheader,$level,$stu=0) {
		global $outcomeinfo,$ot,$gcnt,$type,$cid,$sarr;
		$level = Sanitize::simpleString($level);

		$tots = array();
		$html = '';
		foreach ($arr as $k=>$oi) {
			if (is_array($oi)) { //is outcome group
				$gcnt++;
				if ($isheader) {
					$html .= '<th class="cat'.Sanitize::onlyInt($gcnt).'"><div><span class="cattothdr">'.Sanitize::encodeStringForDisplay($oi['name']).'</span></div></th>';
					$sarr .= ',"N"';
					list($subhtml,$subtots) = printOutcomeRow($oi['outcomes'],$isheader,$level.'-'.$k,$stu);
					$html .= $subhtml;

				} else {
					list($subhtml,$subtots) = printOutcomeRow($oi['outcomes'],$isheader,$level.'-'.$k,$stu);
					$tots = array_merge($tots, $subtots);
					if (count($subtots)>0) {
						$html .= '<td><div>'.round(array_sum($subtots)/count($subtots),1).'%</div></td>';
					} else {
						$html .= '<td><div>-</div></td>';
					}
					$html .= $subhtml;
				}
			} else { //is outcome
				if ($isheader) {
					$html .= '<th class="cat'.Sanitize::onlyInt($gcnt).'"><div>'.Sanitize::encodeStringForDisplay($outcomeinfo[$oi]).'<br/><a class="small" href="outcomereport.php?cid='.Sanitize::courseId($cid).'&amp;outcome='.Sanitize::encodeUrlParam($oi).'&amp;type='.Sanitize::encodeUrlParam($type).'">[Details]</a></div></th>';
					$sarr .= ',"N"';
				} else {
					if (isset($ot[$stu][3][$type]) && isset($ot[$stu][3][$type][$oi])) {
						$html .= '<td><div>'.round(100*$ot[$stu][3][$type][$oi],1).'%</div></td>';
						$tots[] = round(100*$ot[$stu][3][$type][$oi],1);
					} else {
						$html .= '<td><div>-</div></td>';
					}
				}

			}
		}
		return array($html, $tots);
	}


	echo '<div class=breadcrumb>'.$curBreadcrumb.' &gt; '._("Outcomes Report").'</div>';
	echo "<div id=\"headercourse\" class=\"pagetitle\"><h2>"._("Outcomes Report")."</h2></div>\n";

	if (count($outcomes)==0) {
		echo '<p>'._('No outcomes are defined in this course.').'</p>';
	} else {

		echo '<div class="cpmid">'.$typesel.' ';
		echo "<input type=\"button\" id=\"lockbtn\" onclick=\"lockcol()\" value=\"";
		if ($headerslocked) {
			echo _('Unlock headers');
		} else {
			echo _('Lock headers');
		}
		echo "\"/>";
		echo ' <a href="outcomereport.php?cid='.$cid.'&amp;export=true&amp;type='.$type.'">Export to CSV</a> ';
		echo '</div>';
		echo "<div id=\"tbl-container\">";
		echo '<div id="bigcontmyTable"><div id="tblcontmyTable">';

		echo '<table id="myTable" class="gb"><thead><tr><th><div>'._('Name').'</div></th><th class="cat0"><div></div></th>';
		$sarr = '"S",false';
		list($html,$tots) = printOutcomeRow($outcomes,true,'0');
		echo $html;
		/*foreach ($outc as $oc) {
			echo '<th>'.$outcomeinfo[$oc].'<br/><a class="small" href="outcomereport.php?cid='.$cid.'&amp;outcome='.$oc.'&amp;type='.$type.'">[Details]</a></th>';
			$sarr .= ',"N"';
		}*/
		echo '</tr></thead><tbody>';

		$ot = outcometable();

		for ($i=1;$i<count($ot);$i++) {
			echo '<tr class="'.($i%2==0?'even':'odd').'">';
			echo '<td><div class="trld"><a href="outcomereport.php?cid='.Sanitize::encodeUrlParam($cid).'&amp;stu='.Sanitize::encodeUrlParam($ot[$i][0][1]).'&amp;type='.Sanitize::encodeUrlParam($type).'">'.Sanitize::encodeStringForDisplay($ot[$i][0][0]).'</a></div></td>';
			echo '<td><div></div></td>';
			/*foreach ($outc as $oc) {
				if (isset($ot[$i][3][$type]) && isset($ot[$i][3][$type][$oc])) {
					echo '<td>'.round(100*$ot[$i][3][$type][$oc],1).'%</td>';
				} else {
					echo '<td>-</td>';
				}
			}*/
			list($html,$tots) = printOutcomeRow($outcomes,false,'0',$i);
			echo $html;
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '</div></div>';
		echo "<script>initSortTable('myTable',Array($sarr),true,false);</script>\n";
		echo '<p>'._('Note:  The outcome performance in each gradebook category is weighted based on gradebook weights to produce these overview scores').'</p>';
	}
} else if ($report=='oneoutcome') {

	echo '<div class=breadcrumb>'.$curBreadcrumb.' &gt; <a href="outcomereport.php?cid='.$cid.'&amp;type='.$type.'">'._("Outcomes Report").'</a> &gt; '._("Outcome Detail").'</div>';

	$ot = outcometable();

	echo "<div id=\"headercourse\" class=\"pagetitle\"><h2>"._("Outcomes Detail on Outcome: ").Sanitize::encodeStringForDisplay($outcomeinfo[$outcome])."</h2></div>\n";
	echo '<div class="cpmid">'.$typesel.'</div>';
	echo '<table id="myTable" class="gb"><thead><tr><th>'._('Name').'</th>';
	echo '<th>'._('Total').'</th>';
	$sarr = '"S","N"';
	$catstolist = array();
	$itemstolist = array();
	for ($i=1;$i<count($ot);$i++) {
		for ($j=0;$j<count($ot[$i][1]);$j++) {
			if (isset($itemstolist[$j])) {continue;} //already got it
			if ($type==0 && $ot[0][1][$j][2]==1) {continue;} //only want past items
			if (isset($ot[$i][1][$j][1][$outcome])) { //using outcome
				$itemstolist[$j] = 1; //use it
			}
		}
		for ($j=0;$j<count($ot[$i][2]);$j++) {
			if (isset($ot[$i][2][$j][2*$type+1][$outcome]) && $ot[$i][2][$j][2*$type+1][$outcome]>0) { //using outcome
				$catstolist[$j] = 1; //use it
			}
		}
	}

	$catstolist = array_keys($catstolist);
	$itemstolist = array_keys($itemstolist);

	foreach ($catstolist as $cat) {
		echo '<th class="cat'.Sanitize::encodeStringForDisplay($ot[0][2][$cat][1]).'"><span class="cattothdr">'.Sanitize::encodeStringForDisplay($ot[0][2][$cat][0]).'</span></th>';
		$sarr .= ',"N"';
	}
	foreach ($itemstolist as $col) {
		echo '<th class="cat'.Sanitize::encodeStringForDisplay($ot[0][1][$col][1]).'">'.Sanitize::encodeStringForDisplay($ot[0][1][$col][0]).'</th>';
		$sarr .= ',"N"';
	}

	echo '</tr></thead><tbody>';
	for ($i=1;$i<count($ot);$i++) {
		echo '<tr class="'.($i%2==0?'even':'odd').'">';
		echo '<td>'.Sanitize::encodeStringForDisplay($ot[$i][0][0]).'</td>';
		if (isset($ot[$i][3][$type]) && isset($ot[$i][3][$type][$outcome])) {
			echo '<td>'.round(100*$ot[$i][3][$type][$outcome],1).'%</td>';
		} else {
			echo '<td>-</td>';
		}

		foreach ($catstolist as $col) {
			if (isset($ot[$i][2][$col]) && isset($ot[$i][2][$col][2*$type][$outcome]) && $ot[$i][2][$col][2*$type+1][$outcome]>0) {
				echo '<td>'.round(100*$ot[$i][2][$col][2*$type][$outcome]/$ot[$i][2][$col][2*$type+1][$outcome],1).'%</td>';
			} else {
				echo '<td>-</td>';
			}
		}
		foreach ($itemstolist as $col) {
			if (isset($ot[$i][1][$col]) && isset($ot[$i][1][$col][0][$outcome])) {
				echo '<td>'.round(100*$ot[$i][1][$col][0][$outcome]/$ot[$i][1][$col][1][$outcome],1).'%</td>';
			} else {
				echo '<td>-</td>';
			}
		}
		echo '</tr>';
	}
	echo '</tbody></table>';

	echo "<script>initSortTable('myTable',Array($sarr),true,false);</script>\n";
} else if ($report=='onestu') {
	if (isset($teacherid)) {
		echo '<div class=breadcrumb>'.$curBreadcrumb.' &gt; <a href="outcomereport.php?cid='.$cid.'&amp;type='.$type.'">'._("Outcomes Report").'</a> &gt; '._("Student Detail").'</div>';
	} else {
		echo '<div class=breadcrumb>'.$curBreadcrumb.' &gt; '._("Outcomes Detail").'</div>';
	}

	$ot = outcometable($stu);
	echo "<div id=\"headercourse\" class=\"pagetitle\"><h2>"._("Outcomes Student Detail for: ").Sanitize::encodeStringForDisplay($ot[1][0][0])."</h2></div>\n";
	echo '<div class="cpmid">'.$typesel.'</div>';
	echo '<table class="gb"><thead><tr><th>'._('Outcome').'</th>';

	echo '<th>'._('Total').'</th>';
	$n = 2;
	for ($i=0;$i<count($ot[0][2]);$i++) {
		echo '<th class="cat'.Sanitize::encodeStringForDisplay($ot[0][2][$i][1]).'"><span class="cattothdr">'.Sanitize::encodeStringForDisplay($ot[0][2][$i][0]).'</span></th>';
		$n++;
	}
	echo '</tr></thead><tbody>';

	$cnt = 0;
	function printoutcomestu($arr,$ind) {
		$html = '';
		global $outcomeinfo, $cnt, $ot, $n, $type;
		$tots = array();
		for ($i=0;$i<count($ot[0][2])+1;$i++) {
			$tots[$i] = array();
		}
		foreach ($arr as $k=>$oi) {
			if ($cnt%2==0) {
				$class = "even";
			} else {
				$class = "odd";
			}
			$cnt++;
			if (is_array($oi)) { //is outcome group
				list($subhtml,$subtots) = printoutcomestu($oi['outcomes'],$ind+1);
				//$html .= '<tr class="'.$class.'"><td colspan="'.$n.'"><span class="ind'.$ind.'"><b>'.$oi['name'].'</b></span></td></tr>';
				$html .= '<tr class="'.$class.'"><td><span class="ind'.Sanitize::onlyInt($ind).'"><b>'.Sanitize::encodeStringForDisplay($oi['name']).'</b></span></td>';
				for ($i=0;$i<count($ot[0][2])+1;$i++) {
					if (count($subtots[$i])>0) {
						$html .= '<td><b>'.round(array_sum($subtots[$i])/count($subtots[$i]),1).'%</b></td>';
					} else {
						$html .= '<td>-</td>';
					}
				}
				$html .= '</tr>';
				$html .= $subhtml;
				$tots = $tots + $subtots;
			} else {
				$html .= '<tr class="'.$class.'">';
				$html .= '<td><span class="ind'.Sanitize::onlyInt($ind).'">'.Sanitize::encodeStringForDisplay($outcomeinfo[$oi]).'</span></td>';
				if (isset($ot[1][3][$type]) && isset($ot[1][3][$type][$oi])) {
					$html .= '<td>'.round(100*$ot[1][3][$type][$oi],1).'%</td>';
					$tots[0][] = round(100*$ot[1][3][$type][$oi],1);
				} else {
					$html .= '<td>-</td>';
				}
				for ($i=0;$i<count($ot[0][2]);$i++) {
					if (isset($ot[1][2][$i]) && isset($ot[1][2][$i][2*$type+1][$oi])) {
						if ($ot[1][2][$i][2*$type+1][$oi]>0) {
							$html .= '<td>'.round(100*$ot[1][2][$i][2*$type][$oi]/$ot[1][2][$i][2*$type+1][$oi],1).'%</td>';
							$tots[$i+1][] = round(100*$ot[1][2][$i][2*$type][$oi]/$ot[1][2][$i][2*$type+1][$oi],1);
						} else {
							$html .= '<td>0%</td>';
							$tots[$i+1][] = 0;
						}
					} else {
						$html .= '<td>-</td>';
					}
				}
				$html .= '</tr>';
			}
		}
		return array($html, $tots);
	}
	list($html,$tots) = printoutcomestu($outcomes,0);
	echo $html;

} else if ($report=='export') {
	header('Content-type: text/csv');
	header("Content-Disposition: attachment; filename=\"outcomes-$cid.csv\"");
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	$gcnt = -1;
	function outputCSV($arr) {
		$line = '';
		foreach ($arr as $val) {
			 # remove any windows new lines, as they interfere with the parsing at the other end
			  $val = str_replace("\r\n", "\n", $val);
			  $val = str_replace("\n", " ", $val);
			  $val = str_replace(array("<BR>",'<br>','<br/>'), ' ',$val);
			  $val = str_replace("&nbsp;"," ",$val);

			  # if a deliminator char, a double quote char or a newline are in the field, add quotes
			  if(preg_match("/[\,\"\n\r]/", $val)) {
				  $val = '"'.str_replace('"', '""', $val).'"';
			  }
			  $line .= $val.',';
		}
		# strip the last deliminator
		$line = substr($line, 0, -1);
		$line .= "\n";
		return $line;
	}
	function getOutcomeRow($arr,$isheader,$level,$stu=0) {
		global $outcomeinfo,$ot,$gcnt,$type,$cid,$sarr;
		$tots = array();
		$html = array();
		foreach ($arr as $k=>$oi) {
			if (is_array($oi)) { //is outcome group
				$gcnt++;
				if ($isheader) {
				    $html[] = Sanitize::encodeStringForDisplay($oi['name']);
					list($subhtml,$subtots) = getOutcomeRow($oi['outcomes'],$isheader,$level.'-'.$k,$stu);
					$html = array_merge($html, $subhtml);
				} else {
					list($subhtml,$subtots) = getOutcomeRow($oi['outcomes'],$isheader,$level.'-'.$k,$stu);
					$tots = array_merge($tots, $subtots);
					if (count($subtots)>0) {
						$html[] = round(array_sum($subtots)/count($subtots),1).'%';
					} else {
						$html[] = '-';
					}
					$html = array_merge($html, $subhtml);
				}
			} else { //is outcome
				if ($isheader) {
					$html[] = Sanitize::encodeStringForDisplay($outcomeinfo[$oi]);
				} else {
					if (isset($ot[$stu][3][$type]) && isset($ot[$stu][3][$type][$oi])) {
						$html[] = round(100*$ot[$stu][3][$type][$oi],1).'%';
						$tots[] = round(100*$ot[$stu][3][$type][$oi],1);
					} else {
						$html[] = '-';
					}
				}

			}
		}
		return array($html, $tots);
	}

	list($html,$tots) = getOutcomeRow($outcomes,true,'0');
	array_unshift($html, "Name");
	echo outputCSV($html);

	$ot = outcometable();

	for ($i=1;$i<count($ot);$i++) {
		list($html,$tots) = getOutcomeRow($outcomes,false,'0',$i);
		array_unshift($html, $ot[$i][0][0]);
		echo outputCSV($html);
	}
	exit;

}

require("../footer.php");
?>
