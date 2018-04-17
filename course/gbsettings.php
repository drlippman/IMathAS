<?php
//IMathAS:  Add/modify gradebook categories
//(c) 2006 David Lippman
	require("../init.php");
	require("../includes/htmlutil.php");


	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = Sanitize::courseId($_GET['cid']);

	/*if (isset($_POST['addnew'])) {
		$query = "INSERT INTO imas_gbcats (courseid) VALUES ('$cid')";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}*/
	if (isset($_POST['remove'])) {  //via ajax post
		//DB $query = "UPDATE imas_assessments SET gbcategory=0 WHERE gbcategory='{$_GET['remove']}'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_assessments SET gbcategory=0 WHERE gbcategory=:gbcategory");
		$stm->execute(array(':gbcategory'=>$_POST['remove']));
		//DB $query = "UPDATE imas_gbitems SET gbcategory=0 WHERE gbcategory='{$_GET['remove']}'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_gbitems SET gbcategory=0 WHERE gbcategory=:gbcategory");
		$stm->execute(array(':gbcategory'=>$_POST['remove']));
		//DB $query = "DELETE FROM imas_gbcats WHERE id='{$_GET['remove']}'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("DELETE FROM imas_gbcats WHERE id=:id");
		$stm->execute(array(':id'=>$_POST['remove']));
		if ($stm->rowCount()>0) {
			echo "OK";
		} else {
			echo "ERROR";
		}
		exit;
	}

	if (isset($_POST['submit']) ) {  //|| isset($_POST['addnew'])

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
			$calctype = intval($_POST['calctype'][$id]);
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
					//DB $query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight,hidden,calctype) VALUES ";
					//DB $query .= "('$cid','$name','$scale','$st','$chop','$drop','$weight',$hide,$calctype)";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight,hidden,calctype) VALUES ";
					$query .= "(:courseid, :name, :scale, :scaletype, :chop, :dropn, :weight, :hidden, :calctype)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':courseid'=>$cid, ':name'=>$name, ':scale'=>$scale, ':scaletype'=>$st, ':chop'=>$chop, ':dropn'=>$drop,
						':weight'=>$weight, ':hidden'=>$hide, ':calctype'=>$calctype));
				}
			} else if ($id==0) {
				$defaultcat = "$scale,$st,$chop,$drop,$weight,$hide,$calctype";
			} else {
				//DB $query = "UPDATE imas_gbcats SET name='$name',scale='$scale',scaletype='$st',chop='$chop',dropn='$drop',weight='$weight',hidden=$hide,calctype=$calctype WHERE id='$id'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_gbcats SET name=:name,scale=:scale,scaletype=:scaletype,chop=:chop,dropn=:dropn,weight=:weight,hidden=:hidden,calctype=:calctype WHERE id=:id");
				$stm->execute(array(':name'=>$name, ':scale'=>$scale, ':scaletype'=>$st, ':chop'=>$chop, ':dropn'=>$drop, ':weight'=>$weight, ':hidden'=>$hide, ':calctype'=>$calctype, ':id'=>$id));
			}
		}
		$defgbmode = $_POST['gbmode1'] + 10*$_POST['gbmode10'] + 100*($_POST['gbmode100']+$_POST['gbmode200']) + 1000*$_POST['gbmode1000'] + 1000*$_POST['gbmode1002'];
		if (isset($_POST['gbmode4000'])) {$defgbmode += 4000;}
		if (isset($_POST['gbmode400'])) {$defgbmode += 400;}
		if (isset($_POST['gbmode40'])) {$defgbmode += 40;}
		if (!isset($_POST['gbmode100000'])) {$defgbmode += 100000;}
		if (!isset($_POST['gbmode200000'])) {$defgbmode += 200000;}
		$stugbmode = $_POST['stugbmode1'] + $_POST['stugbmode2'] + $_POST['stugbmode4'] + $_POST['stugbmode8'];
		//DB $query = "UPDATE imas_gbscheme SET useweights='$useweights',orderby='$orderby',usersort='$usersort',defaultcat='$defaultcat',defgbmode='$defgbmode',stugbmode='$stugbmode',colorize='{$_POST['colorize']}' WHERE courseid='$cid'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_gbscheme SET useweights=:useweights,orderby=:orderby,usersort=:usersort,defaultcat=:defaultcat,defgbmode=:defgbmode,stugbmode=:stugbmode,colorize=:colorize WHERE courseid=:courseid");
		$stm->execute(array(':useweights'=>$useweights, ':orderby'=>$orderby, ':usersort'=>$usersort, ':defaultcat'=>$defaultcat, ':defgbmode'=>$defgbmode, ':stugbmode'=>$stugbmode, ':colorize'=>$_POST['colorize'], ':courseid'=>$cid));
		if (isset($_POST['submit'])) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?cid=".Sanitize::courseId($_GET['cid'])."&refreshdef=true"."&r=".Sanitize::randomQueryStringParam());
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
		td.innerHTML = \'Calc total: <select name="calctype[new\'+addrowcnt+\']" id="calctypenew\'+addrowcnt+\'">\' +
			\'<option value="0" selected="selected">point total</option>\' +
			\'<option value="1">averaged percents</option></select><br/>\' +
			\'<input name="droptype[new\'+addrowcnt+\']" value="0" checked="1" type="radio" onclick="calctypechange(\\\'new\'+addrowcnt+\'\\\',0)">Keep All<br/>\' +
			\'<input name="droptype[new\'+addrowcnt+\']" value="1" type="radio" onclick="calctypechange(\\\'new\'+addrowcnt+\'\\\',1)">Drop lowest \' +
			\'<input size="2" name="dropl[new\'+addrowcnt+\']" value="0" type="text"> scores<br/> \' +
			\'<input name="droptype[new\'+addrowcnt+\']" value="2" type="radio" onclick="calctypechange(\\\'new\'+addrowcnt+\'\\\',1)">Keep highest \' +
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
			$.ajax({
				type: "POST",
				url: "gbsettings.php?cid='.$cid.'",
				data: "remove="+id
			}).done(function(msg) {
				if (msg=="OK") {
					var torem = document.getElementById("catrow"+id);
					document.getElementById("cattbody").removeChild(torem);
				} else {
					alert("Error removing category");
				}
			});
			return false;
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
		$("select:disabled").prop("disabled",false);
	}
	function calctypechange(id,val) {
		$("#calctype"+id).val(val);
		$("#calctype"+id).prop("disabled", val>0);
	}

	</script>';

	$placeinhead = $sc;
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"gradebook.php?gbmode=$gbmode&cid=$cid\">Gradebook</a> &gt; Settings</div>";
	echo "<div id=\"headergbsettings\" class=\"pagetitle\"><h2>Grade Book Settings <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=gradebooksettings','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h2></div>\n";


	//DB $query = "SELECT useweights,orderby,defaultcat,defgbmode,usersort,stugbmode,colorize FROM imas_gbscheme WHERE courseid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB list($useweights,$orderby,$defaultcat,$defgbmode,$usersort,$stugbmode,$colorize) = mysql_fetch_row($result);
	$stm = $DBH->prepare("SELECT useweights,orderby,defaultcat,defgbmode,usersort,stugbmode,colorize FROM imas_gbscheme WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	list($useweights,$orderby,$defaultcat,$defgbmode,$usersort,$stugbmode,$colorize) = $stm->fetch(PDO::FETCH_NUM);
	$hidesection = (((floor($defgbmode/100000)%10)&1)==1);
	$hidecode = (((floor($defgbmode/100000)%10)&2)==2);
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
			$colorlabel[] = "red &lt; $j%, green &ge; $k%";
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
		<input type="checkbox" name="gbmode100000" value="1" id="secshow" <?php writeHtmlChecked($hidesection,false);?>/><label for="secshow">Section column (if used)</label><br/>
		<input type="checkbox" name="gbmode200000" value="2" id="codeshow" <?php writeHtmlChecked($hidecode,false);?>/><label for="codeshow">Code column (if used)</label><br/>
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
	$r = explode(',',$defaultcat);
	$row['name'] = 'Default';
	$row['scale'] = $r[0];
	$row['scaletype'] = $r[1];
	$row['chop'] = $r[2];
	$row['dropn'] = $r[3];
	$row['weight'] = $r[4];
	$row['hidden'] = $r[5];
	if (isset($r[6])) {
		$row['calctype'] = $r[6];
	} else {
		$row['calctype'] = $row['dropn']==0?0:1;
	}

	echo "<table class=gb><thead>";
	echo "<tr><th>Category Name</th><th>Display<sup>*</sup></th><th>Scale (optional)</th><th>Drops &amp; Category total</th><th id=weighthdr>";
	if ($useweights==0) {
		echo "Fixed Category Point Total (optional)<br/>Blank to use point sum";
	} else if ($useweights==1) {
		echo "Category Weight (%)";
	}
	echo '</th><th>Remove</th></tr></thead><tbody id="cattbody">';

	disprow(0,$row);
	//DB $query = "SELECT id,name,scale,scaletype,chop,dropn,weight,hidden,calctype FROM imas_gbcats WHERE courseid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_assoc($result)) {
	$stm = $DBH->prepare("SELECT id,name,scale,scaletype,chop,dropn,weight,hidden,calctype FROM imas_gbcats WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$id = $row['id'];
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
	echo '<p class="small"><sup>*</sup>If you drop any items, a calc type of "average percents" is required. If you are using a points earned / possible ';
	echo 'scoring system and use the "average percents" method in a category, the points for the category may be a somewhat arbitrary value.</p>';

	//echo "<p><a href=\"gbsettings.php?cid=$cid&addnew=1\">Add New Category</a></p>";

	function disprow($id,$row) {
		global $cid, $hidelabel, $hideval;
		//name,scale,scaletype,chop,drop,weight
		echo "<tr class=grid id=\"catrow$id\"><td>";
		if ($id>0) {
			echo "<input type=text name=\"name[$id]\" value=\"" . Sanitize::encodeStringForDisplay($row['name']) . "\"/>";
		} else {
			echo Sanitize::encodeStringForDisplay($row['name']);
		}
		"</td>";

		echo '<td>';
		writeHtmlSelect("hide[$id]",$hideval,$hidelabel,$row['hidden']);
		echo '</td>';
		//echo "<td><input type=\"checkbox\" name=\"hide[$id]\" value=\"1\" ";
		//writeHtmlChecked($row['hidden'],1);
		//echo "/></td>";
		echo "<td>Scale <input type=text size=3 name=\"scale[$id]\" value=\"";
		if ($row['scale']>0) {
			echo Sanitize::encodeStringForDisplay($row['scale']);
		}
		echo "\"/> (<input type=radio name=\"st[$id]\" value=0 ";
		if ($row['scaletype']==0) {
			echo "checked=1 ";
		}
		echo "/>points ";
		echo "<input type=radio name=\"st[$id]\" value=1 ";
		if ($row['scaletype']==1) {
			echo "checked=1 ";
		}
		echo "/>percent)<br/>to perfect score<br/>";
		echo "<input type=checkbox name=\"chop[$id]\" value=1 ";
		if ($row['chop']>0) {
			echo "checked=1 ";
		}
		echo "/> no total over <input type=text size=3 name=\"chopto[$id]\" value=\"";
		if ($row['chop']>0) {
			echo round($row['chop']*100);
		} else {
			echo "100";
		}
		echo "\"/>%</td>";
		echo "<td>";
		echo 'Calc total: <select name="calctype['.$id.']" id="calctype'.$id.'" ';
		if ($row['dropn']!=0) { echo 'disabled="true"';}
		echo '><option value="0" ';
		if ($row['calctype']==0) {echo 'selected="selected"';}
		echo '>point total</option><option value="1" ';
		if ($row['calctype']==1) {echo 'selected="selected"';}
		echo '>averaged percents</option></select><br/>';

		echo "<input type=radio name=\"droptype[$id]\" value=0 onclick=\"calctypechange($id,0)\" ";
		if ($row['dropn']==0) {
			echo "checked=1 ";
		}
		echo "/>Keep All<br/><input type=radio name=\"droptype[$id]\" value=1 onclick=\"calctypechange($id,1)\" ";
		if ($row['dropn']>0) {
			echo "checked=1 ";
		}
		$absr4=abs($row['dropn']);
		echo "/>Drop lowest <input type=text size=2 name=\"dropl[$id]\" value=\"".Sanitize::encodeStringForDisplay($absr4)."\"/> scores<br/> <input type=radio name=\"droptype[$id]\" value=2 onclick=\"calctypechange($id,1)\" ";
		if ($row['dropn']<0) {
			echo "checked=1 ";
		}
		echo "/>Keep highest <input type=text size=2 name=\"droph[$id]\" value=\"" . Sanitize::encodeStringForDisplay($absr4) . "\"/> scores</td>";
		echo "<td><input type=text size=3 name=\"weight[$id]\" value=\"";
		if ($row['weight']>-1) {
			echo Sanitize::encodeStringForDisplay($row['weight']);
		}
		echo "\"/></td>";
		if ($id!=0) {
			echo "<td><a href=\"#\" onclick=\"removeexistcat($id);return false;\">Remove</a></td></tr>";
		} else {
			echo "<td></td></tr>";
		}

	}
	require("../footer.php");
?>
