<?php
//IMathAS:  Add/modify gradebook categories
//(c) 2006 David Lippman
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = $_GET['cid'];
	
	if (isset($_POST['addnew'])) {
		$query = "INSERT INTO imas_gbcats (courseid) VALUES ('$cid')";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	if (isset($_GET['remove'])) {
		$query = "UPDATE imas_assessments SET gbcategory=0 WHERE gbcategory='{$_GET['remove']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "UPDATE imas_gbitems SET gbcategory=0 WHERE gbcategory='{$_GET['remove']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_gbcats WHERE id='{$_GET['remove']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	if (isset($_POST['submit']) || isset($_POST['addnew'])) {
		//WORK ON ME
		$useweights = $_POST['useweights'];
		$orderby = $_POST['orderby'];
		$usersort = $_POST['usersort'];
		//name,scale,scaletype,chop,drop,weight
		
		$ids = array_keys($_POST['weight']);
		foreach ($ids as $id) {
			$name = $_POST['name'][$id];
			$scale = $_POST['scale'][$id];
			if (trim($scale)=='') {
				$scale = 0;
			}
			$st = $_POST['st'][$id];
			if (isset($_POST['chop'][$id])) {
				$chop = 1;
			} else {
				$chop = 0;
			}
			if ($_POST['droptype'][$id]==0) {
				$drop = 0;
			} else if ($_POST['droptype'][$id]==1){
				$drop = $_POST['dropl'][$id];
			} else if ($_POST['droptype'][$id]==2) {
				$drop = -1*$_POST['droph'][$id];
			}
			$weight = $_POST['weight'][$id];
			if (trim($weight)=='') {
				if ($useweights==0) {
					$weight = -1;
				} else {
					$weight = 0;
				}
			}
			if ($id==0) {
				$defaultcat = "$scale,$st,$chop,$drop,$weight";
			} else {
				$query = "UPDATE imas_gbcats SET name='$name',scale='$scale',scaletype='$st',chop='$chop',dropn='$drop',weight='$weight' WHERE id='$id'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		$defgbmode = $_POST['gbmode1'] + $_POST['gbmode2'] + $_POST['gbmode4'] + $_POST['gbmode8'] + $_POST['gbmode16'];
		$query = "UPDATE imas_gbscheme SET useweights='$useweights',orderby='$orderby',usersort='$usersort',defaultcat='$defaultcat',defgbmode='$defgbmode' WHERE courseid='$cid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		if (isset($_POST['submit'])) {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?cid={$_GET['cid']}");
			exit;
		}
	}
	
	$sc = "<script type=\"text/javascript\">";
	$sc .= "function swapweighthdr(t) {";
	$sc .= "  if (t==0) {";
	$sc .= "     document.getElementById(\"weighthdr\").innerHTML = \"Fixed Category Point Total (optional)<br/>Blank to use point sum\";";
	$sc .= "  } else {";
	$sc .= "     document.getElementById(\"weighthdr\").innerHTML = \"Category Weight (%)\";";
	$sc .= "  }";
	$sc .= "}";
	$sc .= "</script>";
	
	$placeinhead = $sc;
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?gbmode=$gbmode&cid=$cid\">Gradebook</a> &gt; Settings</div>";
	echo "<h2>Grade Book Settings <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=gradebooksettings','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2>\n";
		
	
	$query = "SELECT useweights,orderby,defaultcat,defgbmode,usersort FROM imas_gbscheme WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	list($useweights,$orderby,$defaultcat,$defgbmode,$usersort) = mysql_fetch_row($result);
	
	echo "<form method=post action=\"gbsettings.php?gbmode={$_GET['gbmode']}&cid=$cid\">";
	
	echo "<span class=form>Calculate total using:</span><span class=formright><input type=radio name=useweights value=\"0\" ";
	if ($useweights==0) {echo "checked=1";}
	echo " onclick=\"swapweighthdr(0)\"/> points earned / possible<br/><input type=radio name=useweights value=\"1\" ";
	if ($useweights==1) {echo "checked=1";}
	echo " onclick=\"swapweighthdr(1)\"/> category weights</span><br class=form />";
	
	echo "<span class=form>Gradebook display:</span><span class=formright><input type=radio name=orderby value=\"0\" ";
	if ($orderby==0) {echo "checked=1";}
	echo "/> Order by date<br/> <input type=radio name=orderby value=\"1\" ";
	if ($orderby==1) {echo "checked=1";}
	echo "/> Group by category, then by order by date<br/> <input type=radio name=orderby value=\"2\" ";
	if ($orderby==2) {echo "checked=1";}
	echo "/> Order by alphabetically<br/> <input type=radio name=orderby value=\"3\" ";
	if ($orderby==3) {echo "checked=1";}
	echo "/> Group by category, then order alphabetically</span><br class=form />";
	
	echo "<span class=form>Default user order:</span><span class=formright><input type=radio name=usersort value=\"0\" ";
	if ($usersort==0) {echo "checked=1";}
	echo "/> Order by section (if used), then Last name<br/> <input type=radio name=usersort value=\"1\" ";
	if ($usersort==1) {echo "checked=1";}
	echo "/> Order by Last name</span><br class=form />";
	
	echo "<p>Default gradebook view:</p>";
	echo '<span class=form>Links show:</span><span class=formright><input type=radio name="gbmode1" value="0" ';
	if (($defgbmode&1)==0) { echo 'checked=1';}
	echo '/>Full Test <br/><input type=radio name="gbmode1" value="1" ';
	if (($defgbmode&1)==1) {echo 'checked=1';}
	echo '/>Question Breakdown</span><br class=form />';
	echo '<span class=form>Practice Tests: </span><span class=formright><input type=radio name="gbmode2" value="0" ';
	if (($defgbmode&2)==0) { echo 'checked=1';}
	echo '/>Hidden <br/><input type=radio name="gbmode2" value="2" ';
	if (($defgbmode&2)==2) {echo 'checked=1';}
	echo '/>Show</span><br class=form />';
	echo '<span class=form>Show items: </span><span class=formright><input type=radio name="gbmode4" value="0" ';
	if (($defgbmode&4)==0) { echo 'checked=1';}
	echo '/>All Items <br/><input type=radio name="gbmode4" value="4" ';
	if (($defgbmode&4)==4) {echo 'checked=1';}
	echo '/>Past and Available Items only</span><br class=form>';
	echo '<span class=form>Not Counted items: </span><span class=formright><input type=radio name="gbmode16" value="0" ';
	if (($defgbmode&16)==0) { echo 'checked=1';}
	echo '/>Show <br/><input type=radio name="gbmode16" value="16" ';
	if (($defgbmode&16)==16) {echo 'checked=1';}
	echo '/>Hide</span><br class=form>';
	echo '<span class=form>Totals columns show on:</span><span class=formright><input type=radio name="gbmode8" value="0" ';
	if (($defgbmode&8)==0) { echo 'checked=1';}
	echo '/>Right <br/><input type=radio name="gbmode8" value="8" ';
	if (($defgbmode&8)==8) {echo 'checked=1';}
	echo '/>Left</span><br class=form />';
	
	
	$row = explode(',',$defaultcat);
	array_unshift($row,"Default");
	echo "Categories";
	echo "<table class=gb><thead>";
	echo "<tr><th>Category Name</th><th>Scale (optional)</th><th>Drops</th><th id=weighthdr>";
	if ($useweights==0) {
		echo "Fixed Category Point Total (optional)";
	} else if ($useweights==1) {
		echo "Category Weight (%)";
	}
	echo "</th><th>Remove</th></tr></thead><tbody>";
	
	disprow(0,$row);
	$query = "SELECT id,name,scale,scaletype,chop,dropn,weight FROM imas_gbcats WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$id = array_shift($row);
		disprow($id,$row);
	}
	
	echo "</tbody></table>";
	echo "<p><input type=submit name=addnew value=\"Add New Category\"/></p>";
	echo "<p><input type=submit name=submit value=\"Update\"/></p>";
	echo "</form>";
	//echo "<p><a href=\"gbsettings.php?cid=$cid&addnew=1\">Add New Category</a></p>";
	
	
	function disprow($id,$row) {
		global $cid;
		//name,scale,scaletype,chop,drop,weight
		echo "<tr class=grid><td>";
		if ($id>0) {
			echo "<input type=text name=\"name[$id]\" value=\"{$row[0]}\"/>";
		} else {
			echo $row[0];
		}
		"</td>";
		echo "<td>Scale <input type=text size=3 name=\"scale[$id]\" value=\"";
		if ($row[1]>0) {
			echo $row[1];
		}
		echo "\"/> (<input type=radio name=\"st[$id]\" value=0 ";
		if ($row[2]==0) {
			echo "checked=1 ";
		}
		echo "/>points ";
		echo "<input type=radio name=\"st[$id]\" value=1 ";
		if ($row[2]==1) {
			echo "checked=1 ";
		}
		echo "/>percent)<br/>to perfect score<br/>";
		echo "<input type=checkbox name=\"chop[$id]\" value=1 ";
		if ($row[3]==1) {
			echo "checked=1 ";
		}
		echo "/> no scores over 100%</td>";
		echo "<td><input type=radio name=\"droptype[$id]\" value=0 ";
		if ($row[4]==0) {
			echo "checked=1 ";
		}
		echo "/>Keep All<br/><input type=radio name=\"droptype[$id]\" value=1 ";
		if ($row[4]>0) {
			echo "checked=1 ";
		}
		$absr4=abs($row[4]);
		echo "/>Drop lowest <input type=text size=2 name=\"dropl[$id]\" value=\"$absr4\"/> scores<br/> <input type=radio name=\"droptype[$id]\" value=2 ";
		if ($row[4]<0) {
			echo "checked=1 ";
		}
		echo "/>Keep highest <input type=text size=2 name=\"droph[$id]\" value=\"$absr4\"/> scores</td>";
		echo "<td><input type=text size=3 name=\"weight[$id]\" value=\"";
		if ($row[5]>-1) {
			echo $row[5];
		}
		echo "\"/></td>";
		if ($id!=0) {
			echo "<td><a href=\"gbsettings.php?cid=$cid&remove=$id\">Remove</a></td></tr>";
		} else {
			echo "<td></td></tr>";
		}
		
	}
	
?>
