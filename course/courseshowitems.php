<?php
//IMathAS:  show items function for main course page
//(c) 2007 David Lippman

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
				$startdate = formatdate($items[$i]['startdate']);
			}
			if ($items[$i]['enddate']==2000000000) {
				$enddate = "Always";
			} else {
				$enddate = formatdate($items[$i]['enddate']);
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
						echo "<div class=title>";
					}
					echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>{$items[$i]['name']}</b></a> ";
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">New</span>";
					}
					if (isset($teacherid)) { 
						echo "<br>Showing $availbeh $startdate until $enddate <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>Modify</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>Delete</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>NewFlag</a>";
					
					}
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo "</div>";
				} else {
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					
					//echo "<input class=\"floatright\" type=button id=\"but{$items[$i]['id']}\" value=\"";
					//if ($isopen) {echo "Collapse";} else {echo "Expand";}
					//echo "\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">\n";
					
					if (($hideicons&16)==0) {
						echo "<span class=left>";
						echo "<img style=\"cursor:pointer;\" id=\"img{$items[$i]['id']}\" src=\"$imasroot/img/";
						if ($isopen) {echo "collapse";} else {echo "expand";}
						echo ".gif\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\" /></span>";
						echo "<div class=title>";
					}
					
					echo "<span class=pointer onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">";
					echo "<b><a href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a></b></span> ";
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">New</span>";
					}
					if (isset($teacherid)) { 
						echo "<br>Showing $availbeh $startdate until $enddate <a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle>Isolate</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>Modify</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>Delete</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>NewFlag</a>";
					}
					if (($hideicons&16)==0) {
						echo "</div>";
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
						echo "<div class=title>";
					}
					echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>";
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
					
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo "</div>";
				} else {
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					
					//echo "<input class=\"floatright\" type=button id=\"but{$items[$i]['id']}\" value=\"";
					//if ($isopen) {echo "Collapse";} else {echo "Expand";}
					//echo "\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">\n";
					if (($hideicons&16)==0) {
						echo "<span class=left>";
						echo "<img style=\"cursor:pointer;\" id=\"img{$items[$i]['id']}\" src=\"$imasroot/img/";
						if ($isopen) {echo "collapse";} else {echo "expand";}
						echo ".gif\" onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\" /></span>";
						echo "<div class=title>";
					}
					
					echo "<span class=pointer onClick=\"toggleblock('{$items[$i]['id']}','$parent-$bnum')\">";
					echo "<b>";
					if ($items[$i]['SH'][0]=='S') {
						echo "<a href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a>";
					} else {
						echo "<i><a href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a></i>";
					}
					echo "</b></span> ";
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
					if (($hideicons&16)==0) {
						echo "</div>";
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
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Never";
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['reviewdate']==2000000000) {
				   $reviewdate = "Always";
			   } else {
				   $reviewdate = formatdate($line['reviewdate']);
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
				   	echo " <i><a href=\"addquestions.php?aid=$typeid&cid=$cid\">Questions</a></i> | <a href=\"addassessment.php?id=$typeid&block=$parent&cid=$cid\">Settings</a>\n";
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
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate = formatdate($line['enddate']);
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
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate = formatdate($line['enddate']);
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
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = "Always";
			   } else {
				   $enddate = formatdate($line['enddate']);
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
   
   function formatdate($date) {
	return tzdate("D n/j/y, g:i a",$date);   
	//return tzdate("M j, Y, g:i a",$date);   
   }
   
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
   
?>
