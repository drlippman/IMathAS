<?php
//IMathAS:  Add an assessment/ change settings
//(c) 2006 David Lippman
	require("../validate.php");
	$cid = $_GET['cid'];
	
	if (isset($_GET['action'])) {
		$query = "SELECT imas_courses.id FROM imas_courses,imas_teachers WHERE imas_courses.id=imas_teachers.courseid";
		$query .= " AND imas_teachers.userid='$userid' AND imas_courses.id='{$_POST['ctc']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)==0) {
			$query = "SELECT enrollkey,copyrights FROM imas_courses WHERE id='{$_POST['ctc']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$copyrights = mysql_result($result,0,1)*1;
			if ($copyrights<2) {
				$oktocopy = 0;
				if ($copyrights==1) {
					$query = "SELECT imas_users.groupid FROM imas_courses,imas_users,imas_teachers WHERE imas_courses.id=imas_teachers.courseid ";
					$query .= "AND imas_teachers.userid=imas_users.id AND imas_courses.id='{$_POST['ctc']}'";
					$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($r2)) {
						if ($row[0]==$groupid) {
							$oktocopy=1;
							break;
						}
					}
				}
				if ($oktocopy==0) {
					$ekey = mysql_result($result,0,0);
					if (!isset($_POST['ekey']) || $ekey != $_POST['ekey']) {
						echo "Invalid enrollment key entered.  <a href=\"copyitems.php?cid=$cid\">Try Again</a>";
						exit;
					}
				}
			}
		}
	}
	if (isset($_GET['action']) && $_GET['action']=="copy") {
		
		if (isset($_POST['copycourseopt'])) {
			$query = "SELECT hideicons,allowunenroll,copyrights,msgset,topbar,cploc FROM imas_courses WHERE id='{$_POST['ctc']}'";
			$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
			$row = mysql_fetch_row($result);
			$query = "UPDATE imas_courses SET hideicons='{$row[0]}',allowunenroll='{$row[1]}',copyrights='{$row[2]}',";
			$query .= "msgset='{$row[3]}',topbar='{$row[4]}',cploc='{$row[5]}' WHERE id='$cid'";
			mysql_query($query) or die("Query failed :$query " . mysql_error());
		}
		if (isset($_POST['copygbsetup'])) {
			$query = "SELECT useweights,orderby,defaultcat,defgbmode FROM imas_gbscheme WHERE courseid='{$_POST['ctc']}'";
			$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
			$row = mysql_fetch_row($result);
			$query = "UPDATE imas_gbscheme SET useweights='{$row[0]}',orderby='{$row[1]}',defaultcat='{$row[2]}',defgbmode='{$row[3]}' WHERE courseid='$cid'";
			mysql_query($query) or die("Query failed :$query " . mysql_error());
			
			$query = "SELECT id,name,scale,scaletype,chop,dropn,weight FROM imas_gbcats WHERE courseid='{$_POST['ctc']}'";
			$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$query = "SELECT id FROM imas_gbcats WHERE courseid='$cid' AND name='{$row[1]}'";
				$r2 = mysql_query($query) or die("Query failed :$query " . mysql_error());
				if (mysql_num_rows($r2)==0) {
					$query = "INSERT INTO imas_gbcats (courseid,name,scale,scaletype,chop,dropn,weight) VALUES ";
					$frid = array_shift($row);
					$irow = "'".implode("','",addslashes_deep($row))."'";
					$query .= "('$cid',$irow)";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
					$gbcats[$frid] = mysql_insert_id();
				} else {
					$rpid = mysql_result($r2,0,0);
					$query = "UPDATE imas_gbcats SET scale='{$row[2]}',scaletype='{$row[3]}',chop='{$row[4]}',dropn='{$row[5]}',weight='{$row[6]}' ";
					$query .= "WHERE id='$rpid'";
					$gbcats[$row[0]] = $rpid;
				}
			}
		} else {
			$gbcats = array();
		}
		function copyitem($itemid,$gbcats) {
			global $cid;
			if ($gbcats===false) {
				$gbcats = array();
			}
			$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
			$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
			list($itemtype,$typeid) = mysql_fetch_row($result);
			if ($itemtype == "InlineText") {
				//$query = "INSERT INTO imas_inlinetext (courseid,title,text,startdate,enddate) ";
				//$query .= "SELECT '$cid',title,text,startdate,enddate FROM imas_inlinetext WHERE id='$typeid'";
				//mysql_query($query) or die("Query failed :$query " . mysql_error());
				$query = "SELECT title,text,startdate,enddate,fileorder FROM imas_inlinetext WHERE id='$typeid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$row = mysql_fetch_row($result);
				$row[0] .= stripslashes($_POST['append']);
				$fileorder = $row[4];
				array_pop($row);
				$row = "'".implode("','",addslashes_deep($row))."'";
				$query = "INSERT INTO imas_inlinetext (courseid,title,text,startdate,enddate) ";
				$query .= "VALUES ('$cid',$row)";
				mysql_query($query) or die("Query failed :$query " . mysql_error());
				$newtypeid = mysql_insert_id();
				
				$query = "SELECT description,filename,id FROM imas_instr_files WHERE itemid='$typeid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$addedfiles = array();
				while ($row = mysql_fetch_row($result)) {
					$curid = $row[2];
					array_pop($row);
					$row = "'".implode("','",addslashes_deep($row))."'";
					$query = "INSERT INTO imas_instr_files (description,filename,itemid) VALUES ($row,$newtypeid)";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
					$addedfiles[$curid] = mysql_insert_id(); 
				}
				if (count($addedfiles)>0) {
					$addedfilelist = array();
					foreach (explode(',',$fileorder) as $fid) {
						$addedfilelist[] = $addedfiles[$fid];
					}
					$addedfilelist = implode(',',$addedfilelist);
					$query = "UPDATE imas_inlinetext SET fileorder='$addedfilelist' WHERE id=$newtypeid";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
				}
				
			} else if ($itemtype == "LinkedText") {
				//$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate) ";
				//$query .= "SELECT '$cid',title,summary,text,startdate,enddate FROM imas_linkedtext WHERE id='$typeid'";
				//mysql_query($query) or die("Query failed :$query " . mysql_error());
				$query = "SELECT title,summary,text,startdate,enddate FROM imas_linkedtext WHERE id='$typeid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$row = mysql_fetch_row($result);
				$row[0] .= stripslashes($_POST['append']);
				$row = "'".implode("','",addslashes_deep($row))."'";
				$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate) ";
				$query .= "VALUES ('$cid',$row)";
				mysql_query($query) or die("Query failed :$query " . mysql_error());
				$newtypeid = mysql_insert_id();
			} else if ($itemtype == "Forum") {
				//$query = "INSERT INTO imas_forums (courseid,name,summary,startdate,enddate) ";
				//$query .= "SELECT '$cid',name,summary,startdate,enddate FROM imas_forums WHERE id='$typeid'";
				//mysql_query($query) or die("Query failed : $query" . mysql_error());
				$query = "SELECT name,description,startdate,enddate,settings,defdisplay,replyby,postby FROM imas_forums WHERE id='$typeid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$row = mysql_fetch_row($result);
				$row[0] .= stripslashes($_POST['append']);
				$row = "'".implode("','",addslashes_deep($row))."'";
				$query = "INSERT INTO imas_forums (courseid,name,description,startdate,enddate,settings,defdisplay,replyby,postby) ";
				$query .= "VALUES ('$cid',$row)";
				mysql_query($query) or die("Query failed :$query " . mysql_error());
				$newtypeid = mysql_insert_id();
			} else if ($itemtype == "Assessment") {
				//$query = "INSERT INTO imas_assessments (courseid,name,summary,intro,startdate,enddate,timelimit,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle) ";
				//$query .= "SELECT '$cid',name,summary,intro,startdate,enddate,timelimit,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle FROM imas_assessments WHERE id='$typeid'";
				//mysql_query($query) or die("Query failed : $query" . mysql_error());
				$query = "SELECT name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,isgroup FROM imas_assessments WHERE id='$typeid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				$row = mysql_fetch_row($result);
				if (isset($gbcats[$row[13]])) {
					$row[13] = $gbcats[$row[13]];
				} else if ($_POST['ctc']!=$cid) {
					$row[13] = 0;
				}
				$row[0] .= stripslashes($_POST['append']);
				$row = "'".implode("','",addslashes_deep($row))."'";
				$query = "INSERT INTO imas_assessments (courseid,name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,isgroup) ";
				$query .= "VALUES ('$cid',$row)";
				mysql_query($query) or die("Query failed : $query" . mysql_error());
				$newtypeid = mysql_insert_id();
				$query = "SELECT itemorder FROM imas_assessments WHERE id='$typeid'";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				if (trim(mysql_result($result,0,0))!='') {
					$aitems = explode(',',mysql_result($result,0,0));
					$newaitems = array();
					foreach ($aitems as $k=>$aitem) {
						if (strpos($aitem,'~')==FALSE) {
							///$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
							///$query .= "SELECT '$newtypeid',questionsetid,points,attempts,penalty,category FROM imas_questions WHERE id='$aitem'";
							//mysql_query($query) or die("Query failed :$query " . mysql_error());
							$query = "SELECT questionsetid,points,attempts,penalty,category FROM imas_questions WHERE id='$aitem'";
							$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
							$row = "'".implode("','",addslashes_deep(mysql_fetch_row($result)))."'";
							$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
							$query .= "VALUES ('$newtypeid',$row)";
							mysql_query($query) or die("Query failed : $query" . mysql_error());
							$newaitems[] = mysql_insert_id();
						} else {
							$sub = explode('~',$aitem);
							$newsub = array();
							foreach ($sub as $subi) {
								//$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
								//$query .= "SELECT '$newtypeid',questionsetid,points,attempts,penalty,category FROM imas_questions WHERE id='$subi'";
								//mysql_query($query) or die("Query failed : $query" . mysql_error());
								$query = "SELECT questionsetid,points,attempts,penalty,category FROM imas_questions WHERE id='$subi'";
								$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
								$row = "'".implode("','",addslashes_deep(mysql_fetch_row($result)))."'";
								$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
								$query .= "VALUES ('$newtypeid',$row)";
								mysql_query($query) or die("Query failed : $query" . mysql_error());
								$newsub[] = mysql_insert_id();
							}
							$newaitems[] = implode('~',$newsub);
						}
					}
					$newitemorder = implode(',',$newaitems);
					$query = "UPDATE imas_assessments SET itemorder='$newitemorder' WHERE id='$newtypeid'";
					mysql_query($query) or die("Query failed : $query" . mysql_error());
				}
			}
			$query = "INSERT INTO imas_items (courseid,itemtype,typeid) ";
			$query .= "VALUES ('$cid','$itemtype',$newtypeid)";
			mysql_query($query) or die("Query failed :$query " . mysql_error());
			return (mysql_insert_id());	
		}
		
		$checked = $_POST['checked'];
		$query = "SELECT blockcnt FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		$blockcnt = mysql_result($result,0,0);
		
		$query = "SELECT itemorder FROM imas_courses WHERE id='{$_POST['ctc']}'";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
		$newitems = array();
		function copysub($items,$parent,&$addtoarr,$gbcats) {
			global $checked,$blockcnt;
			foreach ($items as $k=>$item) {
				if (is_array($item)) {
					if (array_search($parent.'-'.($k+1),$checked)!==FALSE) { //copy block
						$newblock = array();
						$newblock['name'] = $item['name'].stripslashes($_POST['append']);
						$newblock['id'] = $blockcnt;
						$blockcnt++;
						$newblock['startdate'] = $item['startdate'];
						$newblock['enddate'] = $item['enddate'];
						$newblock['SH'] = $item['SH'];
						$newblock['colors'] = $item['colors'];
						$newblock['items'] = array();
						if (count($item['items'])>0) {
							copysub($item['items'],$parent.'-'.($k+1),$newblock['items'],$gbcats);
						}
						$addtoarr[] = $newblock;
					} else {
						if (count($item['items'])>0) {
							copysub($item['items'],$parent.'-'.($k+1),$addtoarr,$gbcats);
						}
					}
				} else {
					if (array_search($item,$checked)!==FALSE) {
						$addtoarr[] = copyitem($item,$gbcats);
					}
				}
			}
		}
		copysub($items,'0',$newitems,$gbcats);
		
		$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
		if ($_POST['addto']=="none") {
			array_splice($items,count($items),0,$newitems);
		} else {
			$blocktree = explode('-',$_POST['addto']);
			$sub =& $items;
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
			array_splice($sub,count($sub),0,$newitems);
		}
		$itemorder = addslashes(serialize($items));
		$query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt' WHERE id='$cid'";
		mysql_query($query) or die("Query failed : $query" . mysql_error());
		
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");

		exit;	
	}
	if (isset($_GET['action']) && $_GET['action']=="select") {
		require("../header.php");
		
		echo <<<END
<script type="text/javascript">
function chkAll(frm, arr, mark) {
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if(frm.elements[i].name == arr) {
       frm.elements[i].checked = mark;
     }
   } catch(er) {}
  }
}
</script>
END;
		
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> &gt; Copy Course Items</div>\n";
		echo "<h3>Copy Course Items</h3>\n";
		
	
		echo "<form method=post action=\"copyitems.php?cid=$cid&action=copy\">\n";
		echo "<input type=hidden name=ekey id=ekey value=\"{$_POST['ekey']}\">\n";
		echo "<input type=hidden name=ctc id=ctc value=\"{$_POST['ctc']}\">\n";
		echo "<h4>Select Items to Copy</h4>\n";
		echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca\" value=\"1\" onClick=\"chkAll(this.form, 'checked[]', this.checked)\" checked=checked>\n";
	
		$query = "SELECT itemorder FROM imas_courses WHERE id='{$_POST['ctc']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
	
		$items = unserialize(mysql_result($result,0,0));
		function getiteminfo($itemid) {
			$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)==0) {
				echo "Uh oh, item #$itemid doesn't appear to exist";
			}
			$itemtype = mysql_result($result,0,0);
			$typeid = mysql_result($result,0,1);
			switch($itemtype) {
				case ($itemtype==="InlineText"):
					$query = "SELECT title FROM imas_inlinetext WHERE id=$typeid";
					break;
				case ($itemtype==="LinkedText"):
					$query = "SELECT title FROM imas_linkedtext WHERE id=$typeid";
					break;
				case ($itemtype==="Forum"):
					$query = "SELECT name FROM imas_forums WHERE id=$typeid";
					break;
				case ($itemtype==="Assessment"):
					$query = "SELECT name FROM imas_assessments WHERE id=$typeid";
					break;
			}
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$name = mysql_result($result,0,0);
			return array($itemtype,$name);
		}
		$ids = array();
		$types = array();
		$names = array();
		function getsubinfo($items,$parent,$pre) {
			global $ids,$types,$names;
			foreach($items as $k=>$item) {
				if (is_array($item)) {
					$ids[] = $parent.'-'.($k+1);
					$types[] = $pre."Block";
					$names[] = stripslashes($item['name']);
					if (count($item['items'])>0) {
						getsubinfo($item['items'],$parent.'-'.($k+1),$pre.'--');
					}
				} else {
					if ($item==null || $item=='') {
						continue;
					}
					$ids[] = $item;
					$arr = getiteminfo($item);
					$types[] = $pre.$arr[0];
					$names[] = $arr[1];
				}
			}
		}
		getsubinfo($items,'0','');
		
		$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$items = unserialize(mysql_result($result,0,0));
		$existblocks = array();
		function buildexistblocks($items,$parent) {
			global $existblocks;
			foreach ($items as $k=>$item) {
				if (is_array($item)) {
					$existblocks[$parent.'-'.($k+1)] = $item['name'];
					if (count($item['items'])>0) {
						buildexistblocks($item['items'],$parent.'-'.($k+1));
					}
				}
			}
		}
		buildexistblocks($items,'0');
		
		
		echo "<table cellpadding=5 class=gb>\n";
		echo "<thead><tr><th></th><th>Type</th><th>Title</th></tr></thead><tbody>\n";
		$alt=0;
		for ($i = 0 ; $i<(count($ids)); $i++) {
			if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
			echo "<td>";
			echo "<input type=checkbox name='checked[]' value='{$ids[$i]}' checked=checked>";
			echo "</td><td>{$types[$i]}</td><td>{$names[$i]}</td>\n";
			echo "</tr>\n";
		}
		echo "</tbody></table>\n";
		echo "<p>Copy course settings? <input type=checkbox name=\"copycourseopt\"  value=\"1\"/></p>";
		echo "<p>Copy gradebook scheme and categories (<i>will overwrite current scheme</i>)? <input type=checkbox name=\"copygbsetup\" value=\"1\"/></p>";
		echo "<p>Append text to titles?: <input type=\"text\" name=\"append\"></p>";
		echo "<p>Add to block: <br/><select name=\"addto\"><option value=\"none\">Main Course Page</option>";
		foreach ($existblocks as $k=>$name) {
			echo "<option value=\"$k\">$name</option>";
		}
		echo "</select></p>\n";
		echo "<p><input type=submit value=\"Copy Items\"></p>\n";
		echo "</form>\n";
		require("../footer.php");
		exit;
	}
	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/libtree.js\"></script>\n";
	$placeinhead .= "<style type=\"text/css\">\n<!--\n@import url(\"$imasroot/course/libtree.css\");\n-->\n</style>\n";
	require("../header.php");
	
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> &gt; Copy Course Items</div>\n";
	echo "<h3>Copy Course Items</h3>\n";
	echo "<h4>Select a course to copy items from</h4>\n";
	
	echo "<form method=post action=\"copyitems.php?cid=$cid&action=select\">\n";
	echo "Course List";
	echo "<ul class=base>";
	echo "<li><span class=dd>-</span><input type=radio name=ctc value=\"$cid\" checked=1>This Course</li>\n";
	echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle('mine')\"><span class=btn id=\"bmine\">+</span> ";
	echo "</span><span class=hdr onClick=\"toggle('mine')\">";
	echo "<span id=\"nmine\" >My Courses</span> </span>";
	echo "<ul class=hide id=\"mine\">";
	$query = "SELECT ic.id,ic.name FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$userid' and ic.id<>'$cid' ORDER BY ic.name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "<li><span class=dd>-</span><input type=radio name=ctc value=\"{$row[0]}\">{$row[1]}</li>\n";
	}
	echo "</ul></li>\n";
	
	echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle('grp')\"><span class=btn id=\"bgrp\">+</span> ";
	echo "</span><span class=hdr onClick=\"toggle('grp')\">";
	echo "<span id=\"ngrp\" >My Group's Courses</span> </span>";
	echo "<ul class=hide id=\"grp\">";
	$query = "SELECT ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid FROM imas_courses AS ic,imas_teachers AS it,imas_users AS iu WHERE ";
	$query .= "it.courseid=ic.id AND it.userid=iu.id AND iu.groupid='$groupid' AND iu.id<>'$userid' ORDER BY iu.LastName,ic.name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$lastteacher = 0;
	if (mysql_num_rows($result)>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($line['userid']!=$lastteacher) {
				if ($lastteacher!=0) {
					echo "</ul></li>\n";
				}
				echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle({$line['userid']})\"><span class=btn id=\"b{$line['userid']}\">+</span> ";
				echo "</span><span class=hdr onClick=\"toggle({$line['userid']})\">";
				echo "<span id=\"n{$line['userid']}\" >{$line['LastName']}, {$line['FirstName']}</span> </span> <a href=\"mailto:{$line['email']}\">Email</a>";
				echo "<ul class=hide id=\"{$line['userid']}\">";
				$lastteacher = $line['userid'];
			}
			echo "<li><span class=dd>-</span><input type=radio name=ctc value=\"{$line['id']}\">{$line['name']} ";
			if ($line['copyrights']<1) {echo '&copy;'; }
			echo "</li>\n";
		}
		echo "</ul></li>\n"; 
		echo "</ul></li>\n";
	} else {
		echo "</ul></li>\n";
	}
	
	echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle('other')\"><span class=btn id=\"bother\">+</span> ";
	echo "</span><span class=hdr onClick=\"toggle('other')\">";
	echo "<span id=\"nother\" >Other's Courses</span> </span>";
	echo "<ul class=hide id=\"other\">";
	
	$query = "SELECT id,name FROM imas_groups";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_num_rows($result)>0) {
		$grpnames = array();
		$grpnames[0] = "Default Group";
		while ($row = mysql_fetch_row($result)) {
			$grpnames[$row[0]] = $row[1];
		}
			
		
		$query = "SELECT ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid,iu.groupid FROM imas_courses AS ic,imas_teachers AS it,imas_users AS iu WHERE ";
		$query .= "it.courseid=ic.id AND it.userid=iu.id AND iu.groupid<>'$groupid' AND iu.id<>'$userid' ORDER BY iu.groupid,iu.LastName,ic.name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$lastteacher = 0;
		$lastgroup = -1;
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($line['groupid']!=$lastgroup) {
				if ($lastgroup!=-1) {
					echo "</ul></li>\n";
					echo "</ul></li>\n";
					$lastteacher = 0;
				}
				echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle('g{$line['groupid']}')\"><span class=btn id=\"bg{$line['groupid']}\">+</span> ";
				echo "</span><span class=hdr onClick=\"toggle('g{$line['groupid']}')\">";
				echo "<span id=\"ng{$line['groupid']}\" >{$grpnames[$line['groupid']]}</span> </span>";
				echo "<ul class=hide id=\"g{$line['groupid']}\">";
				$lastgroup = $line['groupid'];
			}
			if ($line['userid']!=$lastteacher) {
				if ($lastteacher!=0) {
					echo "</ul></li>\n";
				}
				echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle({$line['userid']})\"><span class=btn id=\"b{$line['userid']}\">+</span> ";
				echo "</span><span class=hdr onClick=\"toggle({$line['userid']})\">";
				echo "<span id=\"n{$line['userid']}\" >{$line['LastName']}, {$line['FirstName']}</span> </span> <a href=\"mailto:{$line['email']}\">Email</a>";
				echo "<ul class=hide id=\"{$line['userid']}\">";
				$lastteacher = $line['userid'];
			}
			echo "<li><span class=dd>-</span><input type=radio name=ctc value=\"{$line['id']}\">{$line['name']} ";
			if ($line['copyrights']<2) {echo '&copy;';}
			echo "</li>\n";
		}
		echo "</ul></li>\n";
		echo "</ul></li>\n"; 
		echo "</ul></li>\n";
	} else {
		echo "</ul></li>\n";
	}
	
	if (isset($templateuser)) {
		echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle('template')\"><span class=btn id=\"btemplate\">+</span> ";
		echo "</span><span class=hdr onClick=\"toggle('template')\">";
		echo "<span id=\"ntemplate\" >Template Courses</span> </span>";
		echo "<ul class=hide id=\"template\">";
		$query = "SELECT ic.id,ic.name,ic.copyrights FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$templateuser' ORDER BY ic.name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo "<li><span class=dd>-</span><input type=radio name=ctc value=\"{$row[0]}\">{$row[1]} ";
			if ($row[2]<2) {echo '&copy;'; }
			echo "</li>\n";
		}
		echo "</ul></li>\n";
	}
	
	echo "</ul>\n";
	
	echo "<p>For courses marked with &copy;, you must supply the course enrollment key.<br/>\n";
	echo "Enrollment key: <input type=text name=ekey id=ekey size=30></p>\n";
	
	echo "<input type=submit value=\"Select Course Items\">\n";
	echo "</form>\n";
	require ("../footer.php");
	exit;
	
	
?>
