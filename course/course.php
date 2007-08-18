<?php
//IMathAS:  Main course page
//(c) 2006 David Lippman
   require("../validate.php");
   if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	   require("../header.php");
	   echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	   require("../footer.php");
	   exit;
   }
   $cid = $_GET['cid'];
   
   if (isset($teacherid) && isset($_GET['from']) && isset($_GET['to'])) {
	   $from = $_GET['from'];
	   $to = $_GET['to'];
	   $block = $_GET['block'];
	   $query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
	   $result = mysql_query($query) or die("Query failed : " . mysql_error());
	   $items = unserialize(mysql_result($result,0,0));
	   
	   $blocktree = explode('-',$block);
	   $sub =& $items;
	   for ($i=1;$i<count($blocktree)-1;$i++) {
		   $sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	   }
	   if (count($blocktree)>1) {
		   $curblock =& $sub[$blocktree[$i]-1]['items'];
		   $blockloc = $blocktree[$i]-1;
	   } else {
		   $curblock =& $sub;
	   }
	   	   
	   $blockloc = $blocktree[count($blocktree)-1]-1; 
	   //$sub[$blockloc]['items'] is block with items
	   
	   if (strpos($to,'-')!==false) {  //in or out of block
		   if ($to[0]=='O') {  //out of block
			  $itemtomove = $curblock[$from-1];  //+3 to adjust for other block params
			  //$to = substr($to,2);
			  array_splice($curblock,$from-1,1);
			  if (is_array($itemtomove)) {
				  array_splice($sub,$blockloc+1,0,array($itemtomove));
			  } else {
				  array_splice($sub,$blockloc+1,0,$itemtomove);
			  }
		   } else {  //in to block
			  $itemtomove = $curblock[$from-1];  //-1 to adjust for 0 indexing vs 1 indexing
			  array_splice($curblock,$from-1,1);
			  $to = substr($to,2);
			  if ($from<$to) {$adj=1;} else {$adj=0;}
			  array_push($curblock[$to-1-$adj]['items'],$itemtomove);
		   }
	   } else { //move inside block
		   $itemtomove = $curblock[$from-1];  //-1 to adjust for 0 indexing vs 1 indexing
		   array_splice($curblock,$from-1,1);
		   if (is_array($itemtomove)) {
			   array_splice($curblock,$to-1,0,array($itemtomove));
		   } else {
			   array_splice($curblock,$to-1,0,$itemtomove);
		   }
	   }
	   $itemlist = addslashes(serialize($items));
	   $query = "UPDATE imas_courses SET itemorder='$itemlist' WHERE id='{$_GET['cid']}'";
	   mysql_query($query) or die("Query failed : " . mysql_error()); 
	   header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
   }
   $query = "SELECT name,itemorder,hideicons,allowunenroll,msgset,topbar,cploc FROM imas_courses WHERE id='$cid'";
   $result = mysql_query($query) or die("Query failed : " . mysql_error());
   $line = mysql_fetch_array($result, MYSQL_ASSOC);
   if ($line == null) {
	   echo "Course does not exist.  <a href=\"../index.php\">Return to main page</a></body></html>\n";
	   exit;
   }
   $allowunenroll = $line['allowunenroll'];
   $hideicons = $line['hideicons'];
   $pagetitle = $line['name'];
   $items = unserialize($line['itemorder']);
   $msgset = $line['msgset'];
   $useleftbar = ($line['cploc']==1);
   $topbar = explode('|',$line['topbar']);
   $topbar[0] = explode(',',$topbar[0]);
   $topbar[1] = explode(',',$topbar[1]);
   if ($topbar[0][0] == null) {unset($topbar[0][0]);}
   if ($topbar[1][0] == null) {unset($topbar[1][0]);}
   
   if (isset($_GET['togglenewflag'])) { //handle toggle of NewFlag
	$sub =& $items;
	$blocktree = explode('-',$_GET['togglenewflag']);
	if (count($blocktree)>1) {
		for ($i=1;$i<count($blocktree)-1;$i++) {
			$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
		}
	}
	$sub =& $sub[$blocktree[$i]-1];
	if (!isset($sub['newflag']) || $sub['newflag']==0) {
		$sub['newflag']=1;
	} else {
		$sub['newflag']=0;
	}
	$itemlist = addslashes(serialize($items));
	$query = "UPDATE imas_courses SET itemorder='$itemlist' WHERE id='$cid'";
	mysql_query($query) or die("Query failed : " . mysql_error()); 	   
   }
   
   
   if ((!isset($_GET['folder']) || $_GET['folder']=='') && !isset($sessiondata['folder'.$cid])) {
	   $_GET['folder'] = '0';  
	   $sessiondata['folder'.$cid] = '0';
	   writesessiondata();
   } else if ((isset($_GET['folder']) && $_GET['folder']!='') && $sessiondata['folder'.$cid]!=$_GET['folder']) {
	   $sessiondata['folder'.$cid] = $_GET['folder'];
	   writesessiondata();
   } else if ((!isset($_GET['folder']) || $_GET['folder']=='') && isset($sessiondata['folder'.$cid])) {
	   $_GET['folder'] = $sessiondata['folder'.$cid];
   }
   if ($_GET['folder']!='0') {
	   $now = time() + $previewshift;
	   $blocktree = explode('-',$_GET['folder']);
	   $backtrack = array();
	   for ($i=1;$i<count($blocktree);$i++) {
		$backtrack[] = array($items[$blocktree[$i]-1]['name'],implode('-',array_slice($blocktree,0,$i+1)));
		if (!isset($teacherid) && $items[$blocktree[$i]-1]['SH'][0]!='S' &&($now<$items[$blocktree[$i]-1]['startdate'] || $now>$items[$blocktree[$i]-1]['enddate'])) {
			$_GET['folder'] = 0;
			$items = unserialize($line['itemorder']);
			unset($backtrack);
			unset($blocktree);
			break;
		}
		$items = $items[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	   }
   }
  
   $placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/course.js\"></script>";
   require("../header.php");
   if (isset($teacherid)) {
	   echo "<script type=\"text/javascript\">\n";
	   echo "function moveitem(from,blk) { \n";
	   echo "  var to = document.getElementById(blk+'-'+from).value; \n";
	   $address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}";
	   echo "  if (to != from) {\n";
	   echo "  	var toopen = '$address&block=' + blk + '&from=' + from + '&to=' + to;\n";
	   echo "  	window.location = toopen; \n";
	   echo "  }\n";
	   echo "}\n";
	   echo "function additem(blk) { \n";
	   echo "  var type = document.getElementById('addtype'+blk).value; \n";
	   echo "  if (type!='') {\n";
	   $address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	   echo "    var toopen = '$address/add' + type + '.php?block='+blk+'&cid={$_GET['cid']}';\n";
	   echo "    window.location = toopen; \n";
	   echo "  } \n";
	   echo "}\n";
	   echo "</script>\n";
   }
   $openblocks = Array(0);
   if (isset($_COOKIE['openblocks-'.$cid]) && $_COOKIE['openblocks-'.$cid]!='') {$openblocks = explode(',',$_COOKIE['openblocks-'.$cid]); $firstload=false;} else {$firstload=true;}
   $oblist = implode(',',$openblocks);
   
   echo "<script>\n";
   echo "var getbiaddr = 'getblockitems.php?cid=$cid&folder=';\n";
   echo "var oblist = '$oblist';\n";
   echo "var cid = '$cid';\n";
   /*
   echo 'function arraysearch(needle,hay) {';
   echo '   for (var i=0; i<hay.length;i++) {';
   echo '         if (hay[i]==needle) {';
   echo '               return i;';
   echo '         }';
   echo '   }';
   echo '   return -1;';
   echo '}';
   echo "function toggleblock(bnum) {\n";
   echo "   var node = document.getElementById('block'+bnum);\n";
   echo "   var butn = document.getElementById('but'+bnum);\n";
   echo "   oblist = oblist.split(',');\n";
   echo "   var loc = arraysearch(bnum,oblist);\n";
   echo "   if (node.className == 'blockitems') {\n";
   echo "       node.className = 'hidden';\n";
   echo "       butn.value = 'Expand';\n";
   echo "       if (loc>-1) {oblist.splice(loc,1);}\n";
   echo "   } else { ";
   echo "      	node.className = 'blockitems';\n";
   echo "       butn.value = 'Collapse';\n";
   echo "       if (loc==-1) {oblist.push(bnum);} \n";
   echo "   }\n";
   echo "   oblist = oblist.join(',');\n";
   echo "   document.cookie = 'openblocks-$cid=' + oblist;\n";
   echo "}\n";
   */
   echo "</script>\n";
   
   
   echo "<div class=breadcrumb><span class=\"padright\">$userfullname</span><a href=\"../index.php\">Home</a> &gt; ";
   if (isset($backtrack) && count($backtrack)>0) {
	   echo "<a href=\"course.php?cid=$cid&folder=0\">$coursename</a> ";
	   for ($i=0;$i<count($backtrack);$i++) {
		   echo "&gt; ";
		   if ($i!=count($backtrack)-1) {
			   echo "<a href=\"course.php?cid=$cid&folder={$backtrack[$i][1]}\">";
		   }
		   echo $backtrack[$i][0];
		   if ($i!=count($backtrack)-1) {
			   echo "</a>";
		   }
	   }
	   $curname = $backtrack[count($backtrack)-1][0];
   } else {
	   echo $coursename;
	   $curname = $coursename;
   }
   echo "<div class=clear></div></div>\n";
   
   if ($msgset<3) {
	   $query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto='$userid' AND (isread=0 OR isread=4)";
	   $result = mysql_query($query) or die("Query failed : " . mysql_error());
	   if (mysql_result($result,0,0)>0) {
		   $newmsgs = " <span style=\"color:red\">New Messages</span>";
	   } else {
		   $newmsgs = '';
	   }
   }
   
   if ($useleftbar && isset($teacherid)) {
	echo "<div id=\"leftcontent\">";
	echo "<p>".generateadditem($_GET['folder']). '</p>';
	echo "<p><b>Show:</b><br/>";
	echo "<a href=\"$imasroot/msgs/msglist.php?cid=$cid&folder={$_GET['folder']}\">Messages</a>$newmsgs<br/>";
	echo "<a href=\"listusers.php?cid=$cid\">Students</a><br/>\n";
	echo "<a href=\"gradebook.php?cid=$cid\">Gradebook</a><br/>\n";
	echo "<a href=\"course.php?cid=$cid&stuview=0\">Student View</a></p>\n";
	echo "<p><b>Manage:</b><br/>";
	echo "<a href=\"manageqset.php?cid=$cid\">Question Set</a><br>\n";
	echo "<a href=\"managelibs.php?cid=$cid\">Libraries</a><br/>";
	echo "<a href=\"managestugrps.php?cid=$cid\">Groups</a></p>";
	if ($allowcourseimport) {
		echo "<p><b>Export/Import:</b><br/>";
		echo "<a href=\"../admin/export.php?cid=$cid\">Export Question Set<br/></a>\n";
		echo "<a href=\"../admin/import.php?cid=$cid\">Import Question Set<br/></a>\n";
		echo "<a href=\"../admin/exportlib.php?cid=$cid\">Export Libraries<br/></a>\n";
		echo "<a href=\"../admin/importlib.php?cid=$cid\">Import Libraries</p></a>\n";
	} 
	echo "<p><b>Course Items:</b><br/>";
	echo "<a href=\"copyitems.php?cid=$cid\">Copy</a><br/>\n";
	echo "<a href=\"../admin/exportitems.php?cid=$cid\">Export</a><br/>\n";
	echo "<a href=\"../admin/importitems.php?cid=$cid\">Import</a></p>\n";
	echo "<p><b>Change:</b><br/>";
	//echo "<a href=\"timeshift.php?cid=$cid\">Shift all Course Dates</a><br/>\n";
	echo "<a href=\"chgassessments.php?cid=$cid\">Assessments</a><br/>\n";
	echo "<a href=\"masschgdates.php?cid=$cid\">Dates</a><br/>";
	echo "<a href=\"../admin/forms.php?action=modify&id=$cid&cid=$cid\">Course Settings</a>";
	echo "</p>";   
	echo "<p><a href=\"$imasroot/help.php?section=coursemanagement\">Help</a><br/>\n";
	echo "<a href=\"../actions.php?action=logout\">Log Out</a></p>\n";
	echo "</div>";
	echo "<div id=\"centercontent\">";
   }
   
   if ($previewshift>-1) {
	echo '<script type="text/javascript">';
	echo 'function changeshift() {';
	
	$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid";
	echo '  var shift = document.getElementById("pshift").value;'. "\n";
	echo "  var toopen = '$address&stuview='+shift;\n";
	echo " 	window.location = toopen; \n";
	echo '}';
	echo '</script>';
   }
   
   if (isset($teacherid) && count($topbar[1])>0) {
	echo '<div class=breadcrumb>';
	if (in_array(0,$topbar[1]) && $msgset<3) { //messages
		echo "<a href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a>$newmsgs &nbsp; ";
	}
	if (in_array(1,$topbar[1])) { //Stu view
		echo "<a href=\"course.php?cid=$cid&stuview=0\">Student View</a> &nbsp; ";
	}
	if (in_array(2,$topbar[1])) { //Gradebook
		echo "<a href=\"gradebook.php?cid=$cid\">Show Gradebook</a> &nbsp; ";
	}
	if (in_array(3,$topbar[1])) { //List stu
		echo "<a href=\"listusers.php?cid=$cid\">List Students</a> &nbsp; \n";
	}
	if (in_array(9,$topbar[1])) { //Log out
		echo "<a href=\"../actions.php?action=logout\">Log Out</a>";
	}
	echo '<div class=clear></div></div>';
   } else if (!isset($teacherid) && (count($topbar[0])>0 || $previewshift>-1)) {
	echo '<div class=breadcrumb>';
	if (in_array(0,$topbar[0]) && $msgset<3) { //messages
		echo "<a href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a>$newmsgs &nbsp; ";
	}
	if (in_array(1,$topbar[0])) { //Gradebook
		echo "<a href=\"gradebook.php?cid=$cid\">Show Gradebook</a> &nbsp; ";
	}
	if (in_array(9,$topbar[0])) { //Log out
		echo "<a href=\"../actions.php?action=logout\">Log Out</a>";
	}
	if ($previewshift>-1 && count($topbar[0])>0) { echo '<br />';}
	if ($previewshift>-1) {
		echo 'Showing student view. Show view: <select id="pshift" onchange="changeshift()">';
		echo '<option value="0" ';
		if ($previewshift==0) {echo "selected=1";}
		echo '>Now</option>';
		echo '<option value="3600" ';
		if ($previewshift==3600) {echo "selected=1";}
		echo '>1 hour from now</option>';
		echo '<option value="14400" ';
		if ($previewshift==14400) {echo "selected=1";}
		echo '>4 hours from now</option>';
		echo '<option value="86400" ';
		if ($previewshift==86400) {echo "selected=1";}
		echo '>1 day from now</option>';
		echo '<option value="604800" ';
		if ($previewshift==604800) {echo "selected=1";}
		echo '>1 week from now</option>';
		echo '</select>';
		echo " <a href=\"course.php?cid=$cid&teachview=1\">Back to instructor view</a>";
	   }
	echo '<div class=clear></div></div>';
	   
   }
   	
   echo "<h2>$curname</h2>\n";
   
   //get exceptions
   $now = time() + $previewshift;
   $exceptions = array();
   if (!isset($teacherid)) {
	   $query = "SELECT items.id,ex.startdate,ex.enddate FROM ";
	   $query .= "imas_exceptions AS ex,imas_items as items,imas_assessments as i_a WHERE ex.userid='$userid' AND ";
	   $query .= "ex.assessmentid=i_a.id AND (items.typeid=i_a.id AND items.itemtype='Assessment') ";
	   $query .= "AND (($now<i_a.startdate AND ex.startdate<$now) OR ($now>i_a.enddate AND $now<ex.enddate))";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		   $exceptions[$line['id']] = array($line['startdate'],$line['enddate']);
	   }
   }
   
   if (count($items)>0) {
	   //update block start/end dates to show blocks containing items with exceptions
	   function upsendexceptions(&$items) {
		   global $exceptions;
		   $minsdate = 9999999999;
		   $maxedate = 0;
		   foreach ($items as $k=>$item) {
			   if (is_array($item)) {
				  $hasexc = upsendexceptions($items[$k]['items']);
				  if ($hasexc!=FALSE) {
					$items[$k]['startdate'] = $hasexc[0];
					$items[$k]['enddate'] = $hasexc[1];
					//return ($hasexc);
					if ($hasexc[0]<$minsdate) { $minsdate = $hasexc[0];}
					if ($hasexc[1]>$maxedate) { $maxedate = $hasexc[1];}
				  }
			   } else {
				   if (isset($exceptions[$item])) {
					  // return ($exceptions[$item]);
					   if ($exceptions[$item][0]<$minsdate) { $minsdate = $exceptions[$item][0];}
					   if ($exceptions[$item][1]>$maxedate) { $maxedate = $exceptions[$item][1];}
				   }
			   }
		   }
		   if ($minsdate<9999999999 || $maxedate>0) {
			   return (array($minsdate,$maxedate));
		   } else {
			   return false;
		   }
	   }
	   if (count($exceptions)>0) {
		   upsendexceptions($items);
	   }
	   	   
	   showitems($items,$_GET['folder']);
   }
   
   function showitems($items,$parent) {
	   global $teacherid,$cid,$imasroot,$userid,$openblocks,$firstload,$sessiondata,$previewshift,$hideicons,$exceptions;
	   $now = time() + $previewshift;
	   $blocklist = array();
	   for ($i=0;$i<count($items);$i++) {
		   if (is_array($items[$i])) { //if is a block
			   $blocklist[] = $i+1;
		   }
	   }
	   for ($i=0;$i<count($items);$i++) {
		   if (is_array($items[$i])) { //if is a block
			$items[$i]['name'] = stripslashes($items[$i]['name']);
			if (isset($teacherid)) {
				echo generatemoveselect($i,count($items),$parent,$blocklist);
			}
			if ($items[$i]['startdate']==0) {
				$startdate = "Always";
			} else {
				$startdate = tzdate("M j, Y, g:i a",$items[$i]['startdate']);
			}
			if ($items[$i]['enddate']==2000000000) {
				$enddate = "Always";
			} else {
				$enddate = tzdate("M j, Y, g:i a",$items[$i]['enddate']);
			}
			
			$bnum = $i+1;
			if (in_array($items[$i]['id'],$openblocks)) { $isopen=true;} else {$isopen=false;}
			if (strlen($items[$i]['SH'])==1 || $items[$i]['SH'][1]=='O') {
				$availbeh = "Expanded";
			} else if ($items[$i]['SH'][1]=='F') {
				$availbeh = "as Folder";
			} else {
				$availbeh = "Collapsed";
			}
			if ($items[$i]['colors']=='') {
				$titlebg = '';
			} else {
				list($titlebg,$titletxt,$bicolor) = explode(',',$items[$i]['colors']);
			}
			if (($items[$i]['startdate']<$now && $items[$i]['enddate']>$now)) { //if "available"
				if ($firstload && (strlen($items[$i]['SH'])==1 || $items[$i]['SH'][1]=='O')) {
					echo "<script> oblist = oblist + ',".$items[$i]['id']."';</script>\n";
					$isopen = true;
				}
				if (strlen($items[$i]['SH'])>1 && $items[$i]['SH'][1]=='F') { //show as folder
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					if (($hideicons&16)==0) {
						echo "<span class=left><a href=\"course.php?cid=$cid&folder=$parent-$bnum\" border=0>";
						echo "<img src=\"$imasroot/img/folder.gif\"></a></span>";
					}
					echo "<div class=title><a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>{$items[$i]['name']}</b></a> ";
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">New</span>";
					}
					if (isset($teacherid)) { 
						echo "<br>Showing $availbeh $startdate until $enddate <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>Modify</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>Delete</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>NewFlag</a>";
					
					}
					echo "</div></div>";
				} else {
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					echo "<input class=\"floatright\" type=button id=\"but{$items[$i]['id']}\" value=\"";
					if ($isopen) {echo "Collapse";} else {echo "Expand";}
					echo "\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">\n";
					echo "<span class=pointer onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\"><b>{$items[$i]['name']}</b></span> ";
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">New</span>";
					}
					if (isset($teacherid)) { 
						echo "<br>Showing $availbeh $startdate until $enddate <a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle>Isolate</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>Modify</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>Delete</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>NewFlag</a>";
					}
					
					echo "</div>\n";
					
					if ($isopen) {
						echo "<div class=blockitems ";
					} else {
						echo "<div class=hidden ";
					}
					if ($titlebg!='') {
						echo "style=\"background-color:$bicolor;\"";
					}
					echo "id=\"block{$items[$i]['id']}\">";
					if ($isopen) {
						showitems($items[$i]['items'],$parent.'-'.$bnum);
						if (isset($teacherid)) {echo generateadditem($parent.'-'.$bnum);}
					} else {
						echo "Loading content...";
					}
					
					echo "</div>";
				}
			} else if (isset($teacherid) || $items[$i]['SH'][0]=='S') { //if "unavailable"
				if (strlen($items[$i]['SH'])>1 && $items[$i]['SH'][1]=='F') { //show as folder
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					if (($hideicons&16)==0) {
						echo "<span class=left><a href=\"course.php?cid=$cid&folder=$parent-$bnum\" border=0>";
						echo "<img src=\"$imasroot/img/folder.gif\"></a></span>";
					}
					echo "<div class=title><a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>";
					if ($items[$i]['SH'][0]=='S') {echo "{$items[$i]['name']}</b></a> ";} else {echo "<i>{$items[$i]['name']}</i></b></a>";}
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo " <span style=\"color:red;\">New</span>";
					}
					if (isset($teacherid)) { 
						if ($items[$i]['SH'][0] == 'S') {
							$curbeh = "Showing";
						} else {
							$curbeh = "Hidden";
						}
						echo "<br><i>Currently $curbeh.  Showing as Folder $startdate until $enddate</i> <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>Modify</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>Delete</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>NewFlag</a>";
					
					}
					
					echo "</div></div>";
				} else {
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					echo "<input class=\"floatright\" type=button id=\"but{$items[$i]['id']}\" value=\"";
					if ($isopen) {echo "Collapse";} else {echo "Expand";}
					echo "\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">\n";
					echo "<span class=pointer onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\"><b>";
					if ($items[$i]['SH'][0]=='S') {echo "{$items[$i]['name']}</b></span> ";} else {echo "<i>{$items[$i]['name']}</i></b></span> ";}
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">New</span>";
					}
					if (isset($teacherid)) {
						if ($items[$i]['SH'][0] == 'S') {
							$curbeh = "Showing Collapsed";
						} else {
							$curbeh = "Hidden";
						}
						echo "<br><i>Currently $curbeh.  Showing $availbeh $startdate to $enddate</i> <a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle>Isolate</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>Modify</a>";
						echo " | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>Delete</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>NewFlag</a>";
					
					}
					echo "</div>\n";
					if ($isopen) {
						echo "<div class=blockitems ";
					} else {
						echo "<div class=hidden ";
					}
					if ($titlebg!='') {
						echo "style=\"background-color:$bicolor;\"";
					}
					echo "id=\"block{$items[$i]['id']}\">";
					if ($isopen) {
						showitems($items[$i]['items'],$parent.'-'.$bnum);
						if (isset($teacherid)) {echo generateadditem($parent.'-'.$bnum);}
					} else {
						echo "Loading content...";
					}
					echo "</div>";	
				}
			}
			continue;
		   }
		   $query = "SELECT itemtype,typeid FROM imas_items WHERE id='{$items[$i]}'";
		   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		   $line = mysql_fetch_array($result, MYSQL_ASSOC);
		   
		   if (isset($teacherid)) {
			   echo generatemoveselect($i,count($items),$parent,$blocklist);
		   }
		   if ($line['itemtype']=="Assessment") {
			   $typeid = $line['typeid'];
			   $query = "SELECT name,summary,startdate,enddate,reviewdate,deffeedback,reqscore,reqscoreaid FROM imas_assessments WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			  
			   if (strpos($line['summary'],'<p>')!==0) {
				   $line['summary'] = '<p>'.$line['summary'].'</p>';
			   }
			   //check for exception
			   if (isset($exceptions[$items[$i]])) {
				   $line['startdate'] = $exceptions[$items[$i]][0];
				   $line['enddate'] = $exceptions[$items[$i]][1];
			   }
			   
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = tzdate("M j, Y, g:i a",$line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Never";
			   } else {
				   $enddate = tzdate("M j, Y, g:i a",$line['enddate']);
			   }
			   if ($line['reviewdate']==2000000000) {
				   $reviewdate = "Always";
			   } else {
				   $reviewdate = tzdate("M j, Y, g:i a",$line['reviewdate']);
			   }
			   $nothidden = true;
			   if ($line['reqscore']>0 && $line['reqscoreaid']>0 && !isset($teacherid)) {
				   $query = "SELECT scores FROM imas_assessment_sessions WHERE assessmentid='{$line['reqscoreaid']}' AND userid='$userid'";
				   $result = mysql_query($query) or die("Query failed : " . mysql_error());
				   if (mysql_num_rows($result)==0) {
					   $nothidden = false;
				   } else {
					   $scores = mysql_result($result,0,0);
					   if (getpts($scores)<$line['reqscore']) {
						   $nothidden = false;
					   }
				   }
			   }
			   if ($line['startdate']<$now && $line['enddate']>$now && $nothidden) {
				   echo "<div class=item>\n";
				   if (($hideicons&1)==0) {
					   echo "<div class=icon style=\"background-color: " . makecolor2($line['startdate'],$line['enddate'],$now) . ";\">?</div>";
				   }
				   if (substr($line['deffeedback'],0,8)=='Practice') {
					   $endname = "Available until";
				   } else {
					   $endname = "Due";
				   }
				   echo "<div class=title><b><a href=\"../assessment/showtest.php?id=$typeid&cid=$cid\">{$line['name']}</a></b>";
				   if ($line['enddate']!=2000000000) {
					   echo "<BR> $endname $enddate \n";
				   }
				   if (isset($teacherid)) { 
					echo " <i><a href=\"addquestions.php?aid=$typeid&cid=$cid\">Questions</a></i> | <a href=\"addassessment.php?id=$typeid&block=$parent&cid=$cid\">Settings</a></i> \n";
					echo " | <a href=\"gradebook.php?cid=$cid&asid=average&aid=$typeid\">Grades</a>";
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   echo "</div>\n";
			   } else if ($line['startdate']<$now && $line['reviewdate']>$now && $nothidden) {
				   echo "<div class=item>\n";
				   if (($hideicons&1)==0) {
					   echo "<div class=icon style=\"background-color: #99f;\">R</div>";
				   }
				   echo "<div class=title><b><a href=\"../assessment/showtest.php?id=$typeid&cid=$cid\">{$line['name']}</a></b><BR> Past Due Date.  Showing as Review";
				   if ($line['reviewdate']!=2000000000) { 
					   echo " until $reviewdate \n";
				   }
				   if (isset($teacherid)) { 
				   	echo "<i><a href=\"addquestions.php?aid=$typeid&cid=$cid\">Questions</a></i> | <a href=\"addassessment.php?id=$typeid&block=$parent&cid=$cid\">Settings</a>\n";
					echo " | <a href=\"gradebook.php?cid=$cid&asid=average&aid=$typeid\">Grades</a>";
				   }
				   echo filter("<br/><i>This assessment is in review mode - no scores will be saved</i></div><div class=itemsum>{$line['summary']}</div>\n");
				   echo "</div>\n";
			   } else if (isset($teacherid)) {
				   echo "<div class=item>\n";
				   if (($hideicons&1)==0) {
					   echo "<div class=icon style=\"background-color: #ccc;\">?</div>";
				   }
				   echo "<div class=title><i> <a href=\"../assessment/showtest.php?id=$typeid&cid=$cid\">{$line['name']}</a><BR> Available $startdate until $enddate";
				   if ($line['reviewdate']>0 && $line['enddate']!=2000000000) {
					   echo ", Review until $reviewdate";
				   }
				   echo "</i> \n";
				   echo "<a href=\"addquestions.php?aid=$typeid&cid=$cid\">Questions</a> | <a href=\"addassessment.php?id=$typeid&cid=$cid\">Settings</a> | \n";
				   echo "<a href=\"addassessment.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
				   echo " | <a href=\"gradebook.php?cid=$cid&asid=average&aid=$typeid\">Grades</a>";
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   echo "</div>\n";
			   }
			   
		   } else if ($line['itemtype']=="InlineText") {
		
			   $typeid = $line['typeid'];
			   $query = "SELECT title,text,startdate,enddate,fileorder FROM imas_inlinetext WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			
			   if (strpos($line['text'],'<p>')!==0) {
				   $line['text'] = '<p>'.$line['text'].'</p>';
			   }
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = tzdate("M j, Y, g:i a",$line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate = tzdate("M j, Y, g:i a",$line['enddate']);
			   }
			   if ($line['startdate']<$now && $line['enddate']>$now) {
				   echo "<div class=item>\n";
				   if ($line['title']!='##hidden##') {
					   if (($hideicons&2)==0) {
						   echo "<div class=icon style=\"background-color: " . makecolor2($line['startdate'],$line['enddate'],$now) . ";\">!</div>";
					   }
					   echo "<div class=title> <b>{$line['title']}</b>\n";
					   if (isset($teacherid)) { 
						   echo "<BR>Showing until: $enddate "; 
						   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
						   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
					   }
					   echo "</div>";
				   } else {
					   if (isset($teacherid)) { 
						   echo "Showing until: $enddate "; 
						   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
						   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a><br/>\n";
					   } 
					   
				   }
				   echo filter("<div class=itemsum>{$line['text']}\n");
				   $query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='$typeid'";
				   $result = mysql_query($query) or die("Query failed : " . mysql_error());
				   if (mysql_num_rows($result)>0) {
					   echo "<ul>";
					   $filenames = array();
					   $filedescr = array();
					   while ($row = mysql_fetch_row($result)) {
						   $filenames[$row[0]] = $row[2];
						   $filedescr[$row[0]] = $row[1];
					   }
					   foreach (explode(',',$line['fileorder']) as $fid) {
						   echo "<li><a href=\"$imasroot/course/files/{$filenames[$fid]}\" target=\"_blank\">{$filedescr[$fid]}</a></li>";
					   }
					  
					   echo "</ul>";
				   }
				   echo "</div>";
				   echo "</div>\n";
			   } else if (isset($teacherid)) {
				   echo "<div class=item>\n";
				   if ($line['title']!='##hidden##') {
					   echo "<div class=icon style=\"background-color: #ccc;\">";
					   echo "!</div><div class=title><i> <b>{$line['title']}</b> <BR>";
				   } else {
					   echo "<div class=title><i>";
				   }
				   echo "Showing $startdate until $enddate</i> \n";
				   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
				   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
				   echo filter("</div><div class=itemsum>{$line['text']}\n");
				   $query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='$typeid'";
				   $result = mysql_query($query) or die("Query failed : " . mysql_error());
				   if (mysql_num_rows($result)>0) {
					   echo "<ul>";
					   while ($row = mysql_fetch_row($result)) {
						echo "<li><a href=\"$imasroot/course/files/{$row[2]}\" target=\"_blank\">{$row[1]}</a></li>";
					   }
					   echo "</ul>";
				   }
				   echo "</div>";
				   echo "</div>\n";
			   }
		   } else if ($line['itemtype']=="LinkedText") {
			   $typeid = $line['typeid'];
			   $query = "SELECT title,summary,text,startdate,enddate FROM imas_linkedtext WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			  
			   if (strpos($line['summary'],'<p>')!==0) {
				   $line['summary'] = '<p>'.$line['summary'].'</p>';
			   }
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = tzdate("M j, Y, g:i a",$line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate = tzdate("M j, Y, g:i a",$line['enddate']);
			   }
			   if ((substr($line['text'],0,4)=="http") && (strpos($line['text']," ")===false)) { //is a web link
				   $alink = trim($line['text']);
			   } else if (substr($line['text'],0,5)=="file:") {
				   $filename = substr($line['text'],5);
				   $alink = $imasroot . "/course/files/".$filename;
			   } else {
				   $alink = "showlinkedtext.php?cid=$cid&id=$typeid";
			   }
			   
			   if ($line['startdate']<$now && $line['enddate']>$now) {
				   echo "<div class=item>\n";
				   if (($hideicons&4)==0) {
					echo "<div class=icon style=\"background-color: " . makecolor2($line['startdate'],$line['enddate'],$now) . ";\">!</div>";
				   }
				   echo "<div class=title>";
				   echo "<b><a href=\"$alink\">{$line['title']}</a></b>\n";
				   if (isset($teacherid)) { 
					   echo "<BR>Showing until: $enddate "; 
					   echo "<a href=\"addlinkedtext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
					   echo "<a href=\"addlinkedtext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   echo "</div>\n";
			   } else if (isset($teacherid)) {
				   echo "<div class=item>\n";
				   echo "<div class=icon style=\"background-color: #ccc;\">";
				   echo "!</div><div class=title>";
				   echo "<i> <b><a href=\"$alink\">{$line['title']}</a></b> ";
				   echo "<BR>Showing $startdate until $enddate</i> \n";
				   echo "<a href=\"addlinkedtext.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
				   echo "<a href=\"addlinkedtext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   echo "</div>\n";
			   }
		   } else if ($line['itemtype']=="Forum") {
			   $typeid = $line['typeid'];
			   $query = "SELECT id,name,description,startdate,enddate,grpaid FROM imas_forums WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			   $dofilter = false;
			   if ($line['grpaid']>0) {
				if (!isset($teacherid)) {
					$query = "SELECT agroupid FROM imas_assessment_sessions WHERE assessmentid='{$line['grpaid']}' AND userid='$userid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					if (mysql_num_rows($result)>0) {
						$agroupid = mysql_result($result,0,0);
					} else {
						$agroupid=0;
					}
					$dofilter = true;
				} 
				if ($dofilter) {
					$query = "SELECT userid FROM imas_assessment_sessions WHERE agroupid='$agroupid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					$limids = array();
					while ($row = mysql_fetch_row($result)) {
						$limids[] = $row[0];
					}
					$query = "SELECT userid FROM imas_teachers WHERE courseid='$cid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$limids[] = $row[0];
					}
					$limids = "'".implode("','",$limids)."'";
				}   
			   }
			   
			   $query = "SELECT COUNT( DISTINCT threadid )FROM imas_forum_posts WHERE forumid='$typeid'";
			   if ($dofilter) {
				   $query .= " AND userid IN ($limids)";
			   }
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $numthread = mysql_result($result,0,0);
			   $query = "SELECT imas_forum_views.lastview,MAX(imas_forum_posts.postdate) FROM imas_forum_views ";
			   $query .= "LEFT JOIN imas_forum_posts ON imas_forum_views.threadid=imas_forum_posts.threadid ";
			   $query .= "WHERE imas_forum_posts.forumid='$typeid' AND imas_forum_views.userid='$userid'";
			   if ($dofilter) {
				   $query .= " AND imas_forum_posts.userid IN ($limids) ";
			   }
			   $query .= "GROUP BY imas_forum_posts.threadid";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $hasnewitems = false;
			   if (mysql_num_rows($result)<$numthread) {
				   $hasnewitems = true;
			   } else {
				   while ($row = mysql_fetch_row($result)) {
					   if ($row[0]<$row[1]) {
						   $hasnewitems = true;
						   break;
					   }
				   }
			   }
			  
			  
			   if (strpos($line['description'],'<p>')!==0) {
				   $line['description'] = '<p>'.$line['description'].'</p>';
			   }
			   if ($line['startdate']==0) {
				   $startdate = "Always";
			   } else {
				   $startdate = tzdate("M j, Y, g:i a",$line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate = tzdate("M j, Y, g:i a",$line['enddate']);
			   }
			   if ($line['startdate']<$now && $line['enddate']>$now) {
				   echo "<div class=item>\n";
				   if (($hideicons&8)==0) {
					   echo "<div class=icon style=\"background-color: " . makecolor2($line['startdate'],$line['enddate'],$now) . ";\">F</div>";
				   }
				   echo "<div class=title> ";
				   echo "<b><a href=\"../forums/thread.php?cid=$cid&forum={$line['id']}\">{$line['name']}</a></b>\n";
				   if ($hasnewitems) {
					   echo " <a href=\"../forums/thread.php?cid=$cid&forum={$line['id']}&page=-1\" style=\"color:red\">New Posts</a>";
				   }
				   if (isset($teacherid)) { 
					   echo "<BR>Showing until: $enddate "; 
					   echo "<a href=\"addforum.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
					   echo "<a href=\"addforum.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
				   }
				   echo filter("</div><div class=itemsum>{$line['description']}</div>\n");
				   echo "</div>\n";
			   } else if (isset($teacherid)) {
				   echo "<div class=item>\n";
				   echo "<div class=icon style=\"background-color: #ccc;\">";
				   echo "F</div><div class=title><i> <b><a href=\"../forums/thread.php?cid=$cid&forum={$line['id']}\">{$line['name']}</a></b> <BR>Showing $startdate until $enddate</i> \n";
				   if ($hasnewitems) {
					   echo " <span style=\"color:red\">New Posts</span>";
				   }
				   echo "<a href=\"addforum.php?id=$typeid&block=$parent&cid=$cid\">Modify</a> | \n";
				   echo "<a href=\"addforum.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">Delete</a>\n";
				   echo filter("</div><div class=itemsum>{$line['description']}</div>\n");
				   echo "</div>\n";
			   }
		   }   
	   }
   }
   if ($useleftbar && isset($teacherid)) {
	   echo "</div>";
   } else {
	   if ($msgset<3) {
		   echo "<div class=cp>\n";
		   echo "<span class=column>";
		   echo "<a href=\"$imasroot/msgs/msglist.php?cid=$cid&folder={$_GET['folder']}\">Messages</a>$newmsgs ";
		   echo "</span>";
		   echo "<div class=clear></div></div>\n";
	   }
	   
	   
	   if (isset($teacherid)) {
		echo "<div class=cp>\n";
		echo "<span class=column>";
		echo generateadditem($_GET['folder']);
		echo "<a href=\"listusers.php?cid=$cid\">List Students</a><br/>\n";
		echo "<a href=\"gradebook.php?cid=$cid\">Show Gradebook</a><br/>\n";
		echo "<a href=\"course.php?cid=$cid&stuview=0\">Student View</a></span>\n";
		echo "<span class=column><a href=\"manageqset.php?cid=$cid\">Manage Question Set<br></a>\n";
		if ($allowcourseimport) {
			echo "<a href=\"../admin/export.php?cid=$cid\">Export Question Set<br></a>\n";
			echo "<a href=\"../admin/import.php?cid=$cid\">Import Question Set</span></a>\n";
			echo "<span class=column><a href=\"managelibs.php?cid=$cid\">Manage Libraries</a><br>";
			echo "<a href=\"../admin/exportlib.php?cid=$cid\">Export Libraries</a><br/>\n";
			echo "<a href=\"../admin/importlib.php?cid=$cid\">Import Libraries</span></a>\n";
			echo "<span class=column><a href=\"copyitems.php?cid=$cid\">Copy Course Items</a><br/>\n";
			echo "<a href=\"managestugrps.php?cid=$cid\">Student Groups</a></span>\n";
		} else {
			echo "<a href=\"managelibs.php?cid=$cid\">Manage Libraries</a><br>";
			echo "<a href=\"copyitems.php?cid=$cid\">Copy Course Items</a></span>\n";
			echo "<span class=column><a href=\"managestugrps.php?cid=$cid\">Student Groups</a><br/>";
			echo "<a href=\"../admin/forms.php?action=modify&id=$cid&cid=$cid\">Course Settings</a></span>\n";
		}
		echo "<div class=clear></div></div>\n";
	   }
	   echo "<div class=cp>\n";
	   
	   if (!isset($teacherid)) {
		echo "<a href=\"../actions.php?action=logout\">Log Out</a><BR>\n";
		echo "<a href=\"gradebook.php?cid=$cid\">Show Gradebook</a><br/>\n";   
		echo "<a href=\"$imasroot/help.php?section=usingimas\">Help Using IMathAS</a><br/>\n";   
		if ($myrights > 5 && $allowunenroll==1) {
			echo "<p><a href=\"../forms.php?action=unenroll&cid=$cid\">Unenroll From Course</a></p>\n";
		}
		
	   } else {
		echo "<span class=column>";
		echo "<a href=\"../actions.php?action=logout\">Log Out</a><BR>\n";
		if ($allowcourseimport) {
			echo "<a href=\"copyitems.php?cid=$cid\">Copy Course Items</a><br/>\n";
		}
		echo "<a href=\"../admin/exportitems.php?cid=$cid\">Export Course Items</a><br/>\n";
		echo "<a href=\"../admin/importitems.php?cid=$cid\">Import Course Items</a><br/>\n";
		echo "</span><span class=column>";
		echo "<a href=\"$imasroot/help.php?section=coursemanagement\">Help</a><br/>\n";
		echo "<a href=\"timeshift.php?cid=$cid\">Shift all Course Dates</a><br/>\n";
		echo "<a href=\"chgassessments.php?cid=$cid\">Mass Change Assessments</a>\n";
		echo "</span>";
		echo "<span class=column>";
		echo "<a href=\"masschgdates.php?cid=$cid\">Mass Change Dates</a>";
		echo "</span>";
	   }
	   echo "<div class=clear></div></div>\n";
   }
   if ($firstload) {
	   echo "<script>document.cookie = 'openblocks-$cid=' + oblist;</script>\n";
   }
   require("../footer.php");
   
   function generateadditem($blk) {
	$html = "<select name=addtype id=addtype$blk onchange=\"additem('$blk')\">\n";
	$html .= "<option value=\"\">Add An Item</option>\n";
	$html .= "<option value=\"assessment\">Add Assessment</option>\n";
	$html .= "<option value=\"inlinetext\">Add Inline Text</option>\n";
	$html .= "<option value=\"linkedtext\">Add Linked Text</option>\n";
	$html .= "<option value=\"forum\">Add Forum</option>\n";
	$html .= "<option value=\"block\">Add Block</option>\n";
	$html .= "</select><BR>\n";
	return $html;
   }
   
   function generatemoveselect($num,$count,$blk,$blocklist) {
	$num = $num+1;  //adjust indexing
	$html = "<select id=\"$blk-$num\" onchange=\"moveitem($num,'$blk')\">\n";
	for ($i = 1; $i <= $count; $i++) {
		$html .= "<option value=\"$i\" ";
		if ($i==$num) { $html .= "SELECTED";}
		$html .= ">$i</option>\n";
	}
	for ($i=0; $i<count($blocklist); $i++) {
		if ($num!=$blocklist[$i]) {
			$html .= "<option value=\"B-{$blocklist[$i]}\">Into {$blocklist[$i]}</option>\n";
		}
	}
	if ($blk!='0') {
		$html .= '<option value="O-' . $blk . '">Out of Block</option>';
	}
	$html .= "</select>\n";
	return $html;
   }
   
   function makecolor($etime,$now) {
	   if (!$GLOBALS['colorshift']) {
		   return "#ff0";
	   }
	   //$now = time();
	   if ($etime<$now) {
		   $color = "#ccc";
	   } else if ($etime-$now < 605800) {  //due within a week
		   $color = "#f".dechex(floor(16*($etime-$now)/605801))."0";
	   } else if ($etime-$now < 1211600) { //due within two weeks
		   $color = "#". dechex(floor(16*(1-($etime-$now-605800)/605801))) . "f0";
	   } else {
		   $color = "#0f0";
	   }
	   return $color;
   }
   function makecolor2($stime,$etime,$now) {
	   if (!$GLOBALS['colorshift']) {
		   return "#ff0";
	   }
	   if ($etime==2000000000) {
		   return '#0f0';
	   } else if ($stime==0) {
		   return makecolor($etime,$now);
	   }
	   $r = ($etime-$now)/($etime-$stime);  //0 = etime, 1=stime; 0:#f00, 1:#0f0, .5:#ff0
	   if ($etime<$now) {
		   $color = '#ccc';
	   } else if ($r<.5) {
		   $color = '#f'.dechex(floor(32*$r)).'0';
	   } else if ($r<1) {
		   $color = '#'.dechex(floor(32*(1-$r))).'f0';
	   } else {
		   $color = '#0f0';
	   }
	    return $color;
   }
   function getpts($scs) {
	   $tot = 0;
	   foreach(explode(',',$scs) as $sc) {
		if (strpos($sc,'~')===false) {
			if ($sc>0) { 
				$tot += $sc;
			} 
		} else {
			$sc = explode('~',$sc);
			foreach ($sc as $s) {
				if ($s>0) { 
					$tot+=$s;
				}
			}
		}
	   }
	   return $tot;
   }
?>

