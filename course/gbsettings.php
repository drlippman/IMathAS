<?php
//IMathAS:  Add/modify gradebook categories
//(c) 2006 David Lippman
	require("../validate.php");
	require("../includes/htmlutil.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = $_GET['cid'];
	
	/*if (isset($_POST['addnew'])) {
		$query = "INSERT INTO imas_gbcats (courseid) VALUES ('$cid')";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}*/
	if (isset($_GET['remove'])) {  //LEGACY
		$query = "UPDATE imas_assessments SET gbcategory=0 WHERE gbcategory='{$_GET['remove']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "UPDATE imas_gbitems SET gbcategory=0 WHERE gbcategory='{$_GET['remove']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "DELETE FROM imas_gbcats WHERE id='{$_GET['remove']}'";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	if (isset($_POST['submit']) ) {  //|| isset($_POST['addnew'])
		if (isset($_POST['deletecatonsubmit'])) {
			foreach ($_POST['deletecatonsubmit'] as $i=>$cattodel) {
				$_POST['deletecatonsubmit'][$i] = intval($cattodel);
			}
			$catlist = implode(',', $_POST['deletecatonsubmit']);
			
			$query = "UPDATE imas_assessments SET gbcategory=0 WHERE gbcategory IN ($catlist)";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "UPDATE imas_forums SET gbcategory=0 WHERE gbcategory IN ($catlist)";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "UPDATE imas_gbitems SET gbcategory=0 WHERE gbcategory IN ($catlist)";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_gbcats WHERE id IN ($catlist)";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		//WORK ON ME
		$useweights = $_POST['useweights'];
		$orderby = $_POST['orderby'];
		if (isset($_POST['grouporderby'])) {
			$orderby += 1;
		}
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
				$chop = round($_POST['chopto'][$id]/100,2);
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
			/*if (isset($_POST['hide'][$id])) {
				$hide = 1;
			} else {
				$hide = 0;
			}*/
			$hide = intval($_POST['hide'][$id]);
			
			if (substr($id,0,3)=='new') {
				if (trim($name)!='') {
					$query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight,hidden) VALUES ";
					$query .= "('$cid','$name','$scale','$st','$chop','$drop','$weight',$hide)";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else if ($id==0) {
				$defaultcat = "$scale,$st,$chop,$drop,$weight,$hide";
			} else {
				$query = "UPDATE imas_gbcats SET name='$name',scale='$scale',scaletype='$st',chop='$chop',dropn='$drop',weight='$weight',hidden=$hide WHERE id='$id'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		$defgbmode = $_POST['gbmode1'] + 10*$_POST['gbmode10'] + 100*($_POST['gbmode100']+$_POST['gbmode200']) + 1000*$_POST['gbmode1000'] + 1000*$_POST['gbmode1002'];
		if (isset($_POST['gbmode4000'])) {$defgbmode += 4000;}
		if (isset($_POST['gbmode400'])) {$defgbmode += 400;}
		if (isset($_POST['gbmode40'])) {$defgbmode += 40;}
		$stugbmode = $_POST['stugbmode1'] + $_POST['stugbmode2'] + $_POST['stugbmode4'] + $_POST['stugbmode8'];
		$query = "UPDATE imas_gbscheme SET useweights='$useweights',orderby='$orderby',usersort='$usersort',defaultcat='$defaultcat',defgbmode='$defgbmode',stugbmode='$stugbmode',colorize='{$_POST['colorize']}' WHERE courseid='$cid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		if (isset($_POST['submit'])) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?cid={$_GET['cid']}&refreshdef=true");
			exit;
		}
	}
	
	$sc = '<script type="text/javascript">
	function swapweighthdr(t) {
	  if (t==0) {
	     document.getElementById("weighthdr").innerHTML = "Fixed Category Point Total (optional)<br/>Blank to use point sum";
	  } else {
	     document.getElementById("weighthdr").innerHTML = "Category Weight (%)";
	  }
	}
	var addrowcnt = 0;
	function addcat() {
		addrowcnt++;
		var tr = document.createElement("tr");
		tr.id = \'newrow\'+addrowcnt;
		tr.className = "grid";
		var td = document.createElement("td");
		td.innerHTML = \'<input name="name[new\'+addrowcnt+\']" value="" type="text">\';
		tr.appendChild(td);
		
		var td = document.createElement("td");
		td.innerHTML = \'<select name="hide[new\'+addrowcnt+\']"> \' +
			\'<option value="1">Hidden</option>\' +
			\'<option value="0" selected="selected">Expanded</option>\' +
			\'<option value="2">Collapsed</option>\' +
			\'</select>\';
		//td.innerHTML = \'<input name="hide[new\'+addrowcnt+\']" value="1" type="checkbox">\';
		tr.appendChild(td);
		
		var td = document.createElement("td");
		td.innerHTML = \'Scale <input size="3" name="scale[new\'+addrowcnt+\']" value="" type="text"> \' +
		   \'(<input name="st[new\'+addrowcnt+\']" value="0" checked="1" type="radio">points \' +
		   \'<input name="st[new\'+addrowcnt+\']" value="1" type="radio">percent)<br/>\' +
		   \'to perfect score<br/><input name="chop[new\'+addrowcnt+\']" value="1" checked="1" type="checkbox"> \' +
		   \'no total over <input size="3" name="chopto[new\'+addrowcnt+\']" value="100" type="text">%\';
		tr.appendChild(td);
		
		var td = document.createElement("td");
		td.innerHTML = \'<input name="droptype[new\'+addrowcnt+\']" value="0" checked="1" type="radio">Keep All<br/>\' +
			\'<input name="droptype[new\'+addrowcnt+\']" value="1" type="radio">Drop lowest \' +
			\'<input size="2" name="dropl[new\'+addrowcnt+\']" value="0" type="text"> scores<br/> \' +
			\'<input name="droptype[new\'+addrowcnt+\']" value="2" type="radio">Keep highest \' +
			\'<input size="2" name="droph[new\'+addrowcnt+\']" value="0" type="text"> scores\';
		tr.appendChild(td);
		
		var td = document.createElement("td");
		td.innerHTML = \'<input size="3" name="weight[new\'+addrowcnt+\']" value="" type="text">\';
		tr.appendChild(td);
		
		var td = document.createElement("td");
		td.innerHTML = \'<a href="#" onclick="removecat(\'+addrowcnt+\'); return false;">Remove</a>\';
		tr.appendChild(td);
		
		document.getElementById("cattbody").appendChild(tr);
	}
	function removeexistcat(id) {
		if (confirm("Are you SURE you want to delete this category?")) { 
			$("#theform").append(\'<input type="hidden" name="deletecatonsubmit[]" value="\'+id+\'"/>\');
			var torem = document.getElementById("catrow"+id);
			document.getElementById("cattbody").removeChild(torem);
		}
	}
	function removecat(n) {
		var torem = document.getElementById("newrow"+n);
		document.getElementById("cattbody").removeChild(torem);
	}
	function toggleadv(el) {
		if ($("#viewfield").is(":hidden")) {
			$(el).html("Hide view settings");
			$("#viewfield").slideDown();
		} else {
			$(el).html("Edit view settings");
			$("#viewfield").slideUp();
		}
	}
	function prepforsubmit() {
		if ($("#viewfield").is(":hidden")) {
			$("#viewfield").css("visibility","hidden").css("position","absolute").show();
		}
	}
		
	</script>';
	
	$placeinhead = $sc;
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?gbmode=$gbmode&cid=$cid\">Gradebook</a> &gt; Settings</div>";
	echo "<div id=\"headergbsettings\" class=\"pagetitle\"><h2>Grade Book Settings <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=gradebooksettings','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2></div>\n";
		
	
	$query = "SELECT useweights,orderby,defaultcat,defgbmode,usersort,stugbmode,colorize FROM imas_gbscheme WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	list($useweights,$orderby,$defaultcat,$defgbmode,$usersort,$stugbmode,$colorize) = mysql_fetch_row($result);
	$totonleft = ((floor($defgbmode/1000)%10)&1) ; //0 right, 1 left
	$avgontop = ((floor($defgbmode/1000)%10)&2) ; //0 bottom, 2 top
	$lastlogin = (((floor($defgbmode/1000)%10)&4)==4) ; //0 hide, 2 show last login column
	$links = ((floor($defgbmode/100)%10)&1); //0: view/edit, 1 q breakdown
	$hidelocked = ((floor($defgbmode/100)%10)&2); //0: show 2: hide locked
	$includeduedate = (((floor($defgbmode/100)%10)&4)==4); //0: hide due date, 4: show due date
	$hidenc = (floor($defgbmode/10)%10)%3; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
	$includelastchange = (((floor($defgbmode/10)%10)&4)==4);  //: hide last change, 4: show last change
	$availshow = $defgbmode%10; //0: past, 1 past&cur, 2 all
	
	
	$colorval = array(0);
	$colorlabel = array("No Color");
	for ($j=50;$j<90;$j+=($j<70?10:5)) {
		for ($k=$j+($j<70?10:5);$k<100;$k+=($k<70?10:5)) {
			$colorval[] = "$j:$k";
			$colorlabel[] = "red &le; $j%, green &ge; $k%";
		}
	}
	$colorval[] = "-1:-1";
	$colorlabel[] = "Active";
	
	$hideval = array(1,0,2);
	$hidelabel = array(_("Hidden"),_("Expanded"),_("Collapsed"));

?>
	<form id="theform" method=post action="gbsettings.php?cid=<?php echo $cid;?>" onsubmit="prepforsubmit()">
	
	<span class=form>Calculate total using:</span>
	<span class=formright>
		<input type=radio name=useweights value="0" id="usew0" <?php writeHtmlChecked($useweights,0);?> onclick="swapweighthdr(0)"/><label for="usew0">points earned / possible</label><br/>
		<input type=radio name=useweights value="1" id="usew1" <?php writeHtmlChecked($useweights,1);?> onclick="swapweighthdr(1)"/><label for="usew1">category weights</label>
	</span><br class=form />
	
	<p><a href="#" onclick="toggleadv(this);return false">Edit view settings</a></p>
	<fieldset style="display:none;" id="viewfield"><legend>Default gradebook view:</legend>
	
	<span class=form>Gradebook display:</span>
	<span class=formright>
		<?php
		$orderval = array(0,4,6,8,2,10,12);
		$orderlabel = array('by end date, old to new', 'by end date, new to old', 'by start date, old to new', 'start date, new to old', 'alphabetically', 'by course page order, offline at end', 'by course page order reversed, offline at start');
		echo 'Order: ';
		writeHtmlSelect("orderby", $orderval, $orderlabel, $orderby&~1);
		?>
		<br/>
		<input type="checkbox" name="grouporderby" value="1" id="grouporderby" <?php writeHtmlChecked($orderby&1,1);?>/><label for="grouporderby">Group by category first</label>
	</span><br class=form />
	
	<span class=form>Default user order:</span>
	<span class=formright>
		<?php
		$orderval = array(0,1);
		$orderlabel = array('Order by section (if used), then Last name','Order by Last name');
		writeHtmlSelect("usersort", $orderval, $orderlabel, $usersort);
		?>
	</span><br class=form />
	
	<span class=form>Links show:</span>
	<span class=formright>
		<?php
		$orderval = array(0,1);
		$orderlabel = array('Full Test','Question Breakdown');
		writeHtmlSelect("gbmode100", $orderval, $orderlabel, $links);
		?>
	</span><br class=form />
	
	<span class=form>Default show by availability: </span>
	<span class=formright>
		<?php
		$orderval = array(0,3,4,1,2);
		$orderlabel = array('Past Due Items','Past &amp; Attempted Items','Available Items Only','Past &amp; Available Items','All Items');
		writeHtmlSelect("gbmode1", $orderval, $orderlabel, $availshow);
		?>
	</span><br class=form>
	
	<span class=form>Not Counted (NC) items: </span>
	<span class=formright>
		<?php
		$orderval = array(0,1,2);
		$orderlabel = array('Show NC items','Show NC items not hidden from students','Hide NC items');
		writeHtmlSelect("gbmode10", $orderval, $orderlabel, $hidenc);
		?>
	</span><br class=form>
	
	<span class=form>Locked Students:</span>
	<span class=formright>
		<input type=radio name="gbmode200" value="0"  id="lockstu0" <?php writeHtmlChecked($hidelocked,0);?>/><label for="lockstu0">Show</label> 
		<input type=radio name="gbmode200" value="2"  id="lockstu2" <?php writeHtmlChecked($hidelocked,2);?>/><label for="lockstu2">Hide</label> 
	</span><br class=form />
	
	<span class=form>Default Colorization:</span>
	<span class=formright>
	<?php writeHtmlSelect("colorize",$colorval,$colorlabel,$colorize); ?>
	</span><br class=form />
	
	</span><br class=form />
	<span class=form>Totals columns show on:</span>
	<span class=formright>
		<input type=radio name="gbmode1000" value="0" id="totside0" <?php writeHtmlChecked($totonleft,0);?>/><label for="totside0">Right</label> 
		<input type=radio name="gbmode1000" value="1" id="totside1" <?php writeHtmlChecked($totonleft,1);?>/><label for="totside1">Left</label> 
	</span><br class=form />
	
	<span class=form>Average row shows on:</span>
	<span class=formright>
		<input type=radio name="gbmode1002" value="0" id="avgloc0" <?php writeHtmlChecked($avgontop,0);?>/><label for="avgloc0">Bottom</label> 
		<input type=radio name="gbmode1002" value="2" id="avgloc2" <?php writeHtmlChecked($avgontop,2);?>/><label for="avgloc2">Top</label> 
	</span><br class=form />
	
	<span class=form>Include details:</span>
	<span class=formright>
		<input type="checkbox" name="gbmode4000" value="4" id="llcol" <?php writeHtmlChecked($lastlogin,true);?>/><label for="llcol">Last Login column</label><br/>
		<input type="checkbox" name="gbmode400" value="4" id="duedate" <?php writeHtmlChecked($includeduedate,true);?>/><label for="duedate">Due Date in column headers, and column in single-student view</label><br/>
		<input type="checkbox" name="gbmode40" value="4" id="lastchg" <?php writeHtmlChecked($includelastchange,true);?>/><label for="lastchg">Last Change column in single-student view</label>
	</span><br class=form />
	
	<span class="form">Totals to show students:</span>
	<span class=formright>
		<input type="checkbox" name="stugbmode1" value="1" id="totshow1" <?php writeHtmlChecked(($stugbmode)&1,1);?>/><label for="totshow1">Past Due</label><br/>
		<input type="checkbox" name="stugbmode2" value="2" id="totshow2" <?php writeHtmlChecked(($stugbmode)&2,2);?>/><label for="totshow2">Past Due and Attempted</label><br/>
		<input type="checkbox" name="stugbmode4" value="4" id="totshow4" <?php writeHtmlChecked(($stugbmode)&4,4);?>/><label for="totshow4">Past Due and Available</label><br/>
		<input type="checkbox" name="stugbmode8" value="8" id="totshow8" <?php writeHtmlChecked(($stugbmode)&8,8);?>/><label for="totshow8">All (including future)</label><br/>
	</span><br class="form" />
	</fieldset>
	<fieldset><legend>Gradebook Categories</legend>
<?php	
	$row = explode(',',$defaultcat);
	array_unshift($row,"Default");
	echo "<table class=gb><thead>";
	echo "<tr><th>Category Name</th><th>Display<sup>*</sup></th><th>Scale (optional)</th><th>Drops</th><th id=weighthdr>";
	if ($useweights==0) {
		echo "Fixed Category Point Total (optional)<br/>Blank to use point sum";
	} else if ($useweights==1) {
		echo "Category Weight (%)";
	}
	echo '</th><th>Remove</th></tr></thead><tbody id="cattbody">';
	
	disprow(0,$row);
	$query = "SELECT id,name,scale,scaletype,chop,dropn,weight,hidden FROM imas_gbcats WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$id = array_shift($row);
		disprow($id,$row);
	}
	
	echo "</tbody></table>";
	//echo "<p><input type=submit name=addnew value=\"Add New Category\"/></p>";
	echo '<p><input type="button" value="Add New Category" onclick="addcat()" /></p>';
	echo '</fieldset>';
	echo '<div class="submit"><input type=submit name=submit value="'._('Save Changes').'"/></div>';
	echo "</form>";
	echo '<p class="small"><sup>*</sup>When a category is set to Expanded, both the category total and all items in the category are displayed.<br/> ';
	echo 'When a category is set to Collapsed, only the category total is displayed, but all the items are still counted normally.<br/>';
	echo 'When a category is set to Hidden, nothing is displayed, and no items from the category are counted in the grade total. </p>';
	
	//echo "<p><a href=\"gbsettings.php?cid=$cid&addnew=1\">Add New Category</a></p>";
	
	function disprow($id,$row) {
		global $cid, $hidelabel, $hideval;
		//name,scale,scaletype,chop,drop,weight
		echo "<tr class=grid id=\"catrow$id\"><td>";
		if ($id>0) {
			echo "<input type=text name=\"name[$id]\" value=\"{$row[0]}\"/>";
		} else {
			echo $row[0];
		}
		"</td>";
		
		echo '<td>';
		writeHtmlSelect("hide[$id]",$hideval,$hidelabel,$row[6]);
		echo '</td>'; 
		//echo "<td><input type=\"checkbox\" name=\"hide[$id]\" value=\"1\" ";
		//writeHtmlChecked($row[6],1);
		//echo "/></td>";
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
		if ($row[3]>0) {
			echo "checked=1 ";
		}
		echo "/> no total over <input type=text size=3 name=\"chopto[$id]\" value=\"";
		if ($row[3]>0) {
			echo round($row[3]*100);
		} else {
			echo "100";
		}
		echo "\"/>%</td>";
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
			echo "<td><a href=\"#\" onclick=\"removeexistcat($id);return false;\">Remove</a></td></tr>";
		} else {
			echo "<td></td></tr>";
		}
		
	}
	
?>
