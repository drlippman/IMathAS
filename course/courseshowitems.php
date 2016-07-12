<?php
//IMathAS:  show items function for main course page
//(c) 2007 David Lippman


function beginitem($canedit,$aname=0) {
	if ($canedit) {
		echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
	 }
	 echo "<div class=item>\n";
	 if ($aname != 0) {
		 echo "<a name=\"$aname\"></a>";
	 }
}
function enditem($canedit) {
	echo '<div class="clear"></div>';
	echo "</div>\n";
	if ($canedit) {
		echo '</div>'; //itemwrapper
	}
	
}

  function showitems($items,$parent,$inpublic=false) {
	   global $teacherid,$tutorid,$studentid,$cid,$imasroot,$userid,$openblocks,$firstload,$sessiondata,$previewshift,$myrights;
	   global $hideicons,$exceptions,$latepasses,$graphicalicons,$ispublic,$studentinfo,$newpostcnts,$CFG,$latepasshrs,$toolset,$readlinkeditems, $havecalcedviewedassess, $viewedassess;
	   require_once("../includes/filehandler.php");
	   
	   if (!isset($CFG['CPS']['itemicons'])) {
	   	   $itemicons = array('folder'=>'folder2.gif', 'foldertree'=>'folder_tree.png', 'assess'=>'assess.png',
			'inline'=>'inline.png',	'web'=>'web.png', 'doc'=>'doc.png', 'wiki'=>'wiki.png',
			'drill'=>'drill.png','html'=>'html.png', 'forum'=>'forum.png', 'pdf'=>'pdf.png',
			'ppt'=>'ppt.png', 'zip'=>'zip.png', 'png'=>'image.png', 'xls'=>'xls.png',
			'gif'=>'image.png', 'jpg'=>'image.png', 'bmp'=>'image.png', 
			'mp3'=>'sound.png', 'wav'=>'sound.png', 'wma'=>'sound.png', 
			'swf'=>'video.png', 'avi'=>'video.png', 'mpg'=>'video.png', 
			'nb'=>'mathnb.png', 'mws'=>'maple.png', 'mw'=>'maple.png'); 
	   } else {
	   	   $itemicons = $CFG['CPS']['itemicons'];
	   }
	   
	   if (isset($teacherid)) {
		   $canedit = true;
		   $viewall = true;
	   } else if (isset($tutorid)) {
		    $canedit = false;
		    $viewall = true;
	   } else {
		    $canedit = false;
		    $viewall = false;
	   }
	   
	   
	   $now = time() + $previewshift;
	   $blocklist = array();
	   for ($i=0;$i<count($items);$i++) {
		   if (is_array($items[$i])) { //if is a block
			   $blocklist[] = $i+1;
		   }
	   }
	   if ($canedit) {echo generateadditem($parent,'t');}
	   for ($i=0;$i<count($items);$i++) {
		   if (is_array($items[$i])) { //if is a block
			   $turnonpublic = false;
			   if ($ispublic && !$inpublic) {
				   if (isset($items[$i]['public']) && $items[$i]['public']==1) {
					   $turnonpublic = true;
				   } else {
					   continue;
				   }
				   
			   }
			if (isset($items[$i]['grouplimit']) && count($items[$i]['grouplimit'])>0 && !$viewall) {
				if (!in_array('s-'.$studentinfo['section'],$items[$i]['grouplimit'])) {
					continue;
				}
			}  
			$items[$i]['name'] = stripslashes($items[$i]['name']);;
			if ($canedit) {
				echo generatemoveselect($i,count($items),$parent,$blocklist);
			}
			if ($items[$i]['startdate']==0) {
				$startdate = _('Always');
			} else {
				$startdate = formatdate($items[$i]['startdate']);
			}
			if ($items[$i]['enddate']==2000000000) {
				$enddate = _('Always');
			} else {
				$enddate = formatdate($items[$i]['enddate']);
			}
			
			$bnum = $i+1;
			if (in_array($items[$i]['id'],$openblocks)) { $isopen=true;} else {$isopen=false;}
			if (strlen($items[$i]['SH'])==1 || $items[$i]['SH'][1]=='O') {
				$availbeh = _('Expanded');
			} else if ($items[$i]['SH'][1]=='F') {
				$availbeh = _('as Folder');
			} else if ($items[$i]['SH'][1]=='T') {
				$availbeh = _('as TreeReader');
			} else {
				$availbeh = _('Collapsed');
			}
			if ($items[$i]['colors']=='') {
				$titlebg = '';
			} else {
				list($titlebg,$titletxt,$bicolor) = explode(',',$items[$i]['colors']);
			}
			if (!isset($items[$i]['avail'])) { //backwards compat
				$items[$i]['avail'] = 1;
			}
			if ($items[$i]['avail']==2 || ($items[$i]['avail']==1 && $items[$i]['startdate']<$now && $items[$i]['enddate']>$now)) { //if "available"
				if ($firstload && (strlen($items[$i]['SH'])==1 || $items[$i]['SH'][1]=='O')) {
					echo "<script> oblist = oblist + ',".$items[$i]['id']."';</script>\n";
					$isopen = true;
				}
				if ($items[$i]['avail']==2) {
					$show = sprintf(_('Showing %s Always'), $availbeh);
				} else {
					$show = sprintf(_('Showing %1$s %2$s until %3$s'), $availbeh, $startdate, $enddate);
				}
				if (strlen($items[$i]['SH'])>1 && $items[$i]['SH'][1]=='F') { //show as folder
					if ($canedit) {
						echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
					}
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					
					if (($hideicons&16)==0) {
						if ($ispublic) {
							echo "<span class=left><a href=\"public.php?cid=$cid&folder=$parent-$bnum\" border=0>";
						} else {
							echo "<span class=left><a href=\"course.php?cid=$cid&folder=$parent-$bnum\" border=0>";
						}
						if ($graphicalicons) {
							echo "<img alt=\"folder\" src=\"$imasroot/img/{$itemicons['folder']}\"></a></span>";
						} else {
							echo "<img alt=\"folder\" src=\"$imasroot/img/folder.gif\"></a></span>";
						}
						echo "<div class=title>";
					}
					if ($ispublic) {
						echo "<a href=\"public.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>{$items[$i]['name']}</b></a> ";
					} else {
						echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>{$items[$i]['name']}</b></a> ";
					}
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">", _('New'), "</span>";
					}
					if ($viewall) { 
						echo '<span class="instrdates">';
						echo "<br>$show ";
						echo '</span>';
					}
					if ($canedit) {
						echo '<span class="instronly">';
						echo "<a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>", _('Modify'), "</a> | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>", _('Delete'), "</a>";
						echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\" $astyle>", _('Copy'), "</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>", _('NewFlag'), "</a>";
						echo '</span>';
					}
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo '<div class="clear"></div>';
					echo "</div>";
					if ($canedit) {
						echo '</div>'; //itemwrapper
					}
				} else if (strlen($items[$i]['SH'])>1 && $items[$i]['SH'][1]=='T') { //show as tree reader
					if ($ispublic) {continue;} //public treereader not supported yet.
					if ($canedit) {
						echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
					}
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					
					if (($hideicons&16)==0) {
						if ($ispublic) {
						} else {
							echo "<span class=left><a href=\"treereader.php?cid=$cid&folder=$parent-$bnum\" border=0>";
						}
						if ($graphicalicons) {
							echo "<img alt=\"folder\" src=\"$imasroot/img/{$itemicons['foldertree']}\"></a></span>";
						} else {
							echo "<img alt=\"folder\" src=\"$imasroot/img/folder_tree.png\"></a></span>";
						}
						echo "<div class=title>";
					}
					if ($ispublic) {
					} else {
						echo "<a href=\"treereader.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>{$items[$i]['name']}</b></a> ";
					}
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">", _('New'), "</span>";
					}
					if ($viewall) { 
						echo '<span class="instrdates">';
						echo "<br>$show ";
						echo '</span>';
					}
					if ($canedit) {
						echo '<span class="instronly">';
						echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle>", _('Edit Contents'), "</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>", _('Modify'), "</a> | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>", _('Delete'), "</a>";
						echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\" $astyle>", _('Copy'), "</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>", _('NewFlag'), "</a>";
						echo '</span>';
					}
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo '<div class="clear"></div>';
					echo "</div>";
					if ($canedit) {
						echo '</div>'; //itemwrapper
					}
				} else {
					if ($canedit) {
						echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
					}
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
						echo "<img alt=\"expand/collapse\" style=\"cursor:pointer;\" id=\"img{$items[$i]['id']}\" src=\"$imasroot/img/";
						if ($isopen) {echo _('collapse');} else {echo _('expand');}
						echo ".gif\" onClick=\"toggleblock(event,'{$items[$i]['id']}','$parent-$bnum')\" /></span>";
						echo "<div class=title>";
					}
					if (!$canedit) {
						echo '<span class="right">';
						echo "<a href=\"".($ispublic?"public":"course").".php?cid=$cid&folder=$parent-$bnum\" $astyle>", _('Isolate'), "</a>";
						echo '</span>';
					}
					echo "<span class=pointer onClick=\"toggleblock(event,'{$items[$i]['id']}','$parent-$bnum')\">";
					echo "<b><a id=\"blockh{$items[$i]['id']}\" href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a></b></span> ";
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">", _('New'), "</span>";
					}
					if ($viewall) { 
						echo '<span class="instrdates">';
						echo "<br>$show ";
						echo '</span>';
					}
					if ($canedit) {
						echo '<span class="instronly">';
						echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle>", _('Isolate'), "</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>", _('Modify'), "</a> | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>", _('Delete'), "</a>";
						echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\" $astyle>", _('Copy'), "</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>", _('NewFlag'), "</a>";
						echo '</span>';
					} 
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo "</div>\n";
					if ($canedit) {
						echo '</div>'; //itemwrapper
					}
					if ($isopen) {
						echo "<div class=blockitems ";
					} else {
						echo "<div class=hidden ";
					}
					$style = '';
					if (isset($items[$i]['fixedheight']) && $items[$i]['fixedheight']>0) {
						if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 6')!==false) {
							$style .= 'overflow: auto; height: expression( this.offsetHeight > '.$items[$i]['fixedheight'].' ? \''.$items[$i]['fixedheight'].'px\' : \'auto\' );';
						} else {
							$style .= 'overflow: auto; max-height:'.$items[$i]['fixedheight'].'px;';
						}
					}
					if ($titlebg!='') {
						$style .= "background-color:$bicolor;";
					}
					if ($style != '') {
						echo "style=\"$style\" ";
					}
					echo "id=\"block{$items[$i]['id']}\">";
					if ($isopen) {
						//if (isset($teacherid)) {echo generateadditem($parent.'-'.$bnum,'t');}
						showitems($items[$i]['items'],$parent.'-'.$bnum,$inpublic||$turnonpublic);
						//if (isset($teacherid) && count($items[$i]['items'])>0) {echo generateadditem($parent.'-'.$bnum,'b');}
					} else {
						echo _('Loading content...');
					}
					
					echo "</div>";
				}
			} else if ($viewall || ($items[$i]['SH'][0]=='S' && $items[$i]['avail']>0)) { //if "unavailable"
				if ($items[$i]['avail']==0) {
					$show = _('Hidden');
				} else if ($items[$i]['SH'][0] == 'S') {
					$show = _('Currently Showing');
					if (strlen($items[$i]['SH'])>1 && $items[$i]['SH'][1]=='F') {
						$show .= _(' as Folder. ');
					} else if (strlen($items[$i]['SH'])>1 && $items[$i]['SH'][1]=='T') {
						$show .= _(' as TreeReader. ');
					} else {
						$show .= _(' Collapsed. ');
					}
					$show .= sprintf(_('Showing %1$s %2$s to %3$s'), $availbeh, $startdate, $enddate);
				} else { //currently hidden, using dates
					$show = "Currently Hidden. ";
					$show .= sprintf(_('Showing %1$s %2$s to %3$s'), $availbeh, $startdate, $enddate);
				}
				if (strlen($items[$i]['SH'])>1 && $items[$i]['SH'][1]=='F') { //show as folder
					if ($canedit) {
						echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
					}
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
						if ($graphicalicons) {
							echo "<img alt=\"folder\" src=\"$imasroot/img/{$itemicons['folder']}\"></a></span>";
						} else {
							echo "<img alt=\"folder\" src=\"$imasroot/img/folder.gif\"></a></span>";
						}
						echo "<div class=title>";
					}
					echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>";
					if ($items[$i]['SH'][0]=='S') {echo "{$items[$i]['name']}</b></a> ";} else {echo "<i>{$items[$i]['name']}</i></b></a>";}
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo " <span style=\"color:red;\">", _('New'), "</span>";
					}
					if ($viewall) { 
						echo '<span class="instrdates">';
						echo "<br><i>$show</i> ";
						echo '</span>';
					}
					if ($canedit) {
						echo '<span class="instronly">';
						echo "<a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>", _('Modify'), "</a> | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>", _('Delete'), "</a>";
						echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\">", _('Copy'), "</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>", _('NewFlag'), "</a>";
						echo '</span>';
					}
					
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo '<div class="clear"></div>';
					echo "</div>";
					if ($canedit) {
						echo '</div>'; //itemwrapper
					}
				} else if (strlen($items[$i]['SH'])>1 && $items[$i]['SH'][1]=='T') { //show as tree reader
					if ($canedit) {
						echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
					}
					echo "<div class=block ";
					if ($titlebg!='') {
						echo "style=\"background-color:$titlebg;color:$titletxt;\"";
						$astyle = "style=\"color:$titletxt;\"";
					} else {
						$astyle = '';
					}
					echo ">";
					if (($hideicons&16)==0) {
						echo "<span class=left><a href=\"treereader.php?cid=$cid&folder=$parent-$bnum\" border=0>";
						if ($graphicalicons) {
							echo "<img alt=\"folder\" src=\"$imasroot/img/{$itemicons['foldertree']}\"></a></span>";
						} else {
							echo "<img alt=\"folder\" src=\"$imasroot/img/folder_tree.png\"></a></span>";
						}
						echo "<div class=title>";
					}
					echo "<a href=\"treereader.php?cid=$cid&folder=$parent-$bnum\" $astyle><b>";
					if ($items[$i]['SH'][0]=='S') {echo "{$items[$i]['name']}</b></a> ";} else {echo "<i>{$items[$i]['name']}</i></b></a>";}
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo " <span style=\"color:red;\">", _('New'), "</span>";
					}
					if ($viewall) { 
						echo '<span class="instrdates">';
						echo "<br><i>$show</i> ";
						echo '</span>';
					}
					if ($canedit) {
						echo '<span class="instronly">';
						echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle>", _('Edit Contents'), "</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>", _('Modify'), "</a> | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>", _('Delete'), "</a>";
						echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\">", _('Copy'), "</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>", _('NewFlag'), "</a>";
						echo '</span>';
					}
					
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo '<div class="clear"></div>';
					echo "</div>";
					if ($canedit) {
						echo '</div>'; //itemwrapper
					}
				} else {
					if ($canedit) {
						echo '<div class="inactivewrapper" onmouseover="this.className=\'activewrapper\'" onmouseout="this.className=\'inactivewrapper\'">';
					}
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
						echo "<img alt=\"expand/collapse\" style=\"cursor:pointer;\" id=\"img{$items[$i]['id']}\" src=\"$imasroot/img/";
						if ($isopen) {echo _('collapse');} else {echo _('expand');}
						echo ".gif\" onClick=\"toggleblock(event,'{$items[$i]['id']}','$parent-$bnum')\" /></span>";
						echo "<div class=title>";
					}
					if (!$canedit) {
						echo '<span class="right">';
						echo "<a href=\"".($ispublic?"public":"course").".php?cid=$cid&folder=$parent-$bnum\" $astyle>", _('Isolate'), "</a>";
						echo '</span>';
					}
					echo "<span class=pointer onClick=\"toggleblock(event,'{$items[$i]['id']}','$parent-$bnum')\">";
					echo "<b>";
					if ($items[$i]['SH'][0]=='S') {
						echo "<a id=\"blockh{$items[$i]['id']}\" href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a>";
					} else {
						echo "<i><a id=\"blockh{$items[$i]['id']}\" href=\"#\" onclick=\"return false;\" $astyle>{$items[$i]['name']}</a></i>";
					}
					echo "</b></span> ";
					if (isset($items[$i]['newflag']) && $items[$i]['newflag']==1) {
						echo "<span style=\"color:red;\">", _('New'), "</span>";
					}
					if ($viewall) {
						echo '<span class="instrdates">';
						echo "<br><i>$show</i> ";
						echo '</span>';
					}
					if ($canedit) {
						echo '<span class="instronly">';
						echo "<a href=\"course.php?cid=$cid&folder=$parent-$bnum\" $astyle>", _('Isolate'), "</a> | <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\" $astyle>", _('Modify'), "</a>";
						echo " | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\" $astyle>", _('Delete'), "</a>";
						echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\">", _('Copy'), "</a>";
						echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\" $astyle>", _('NewFlag'), "</a>";
						echo '</span>';
					} 
					if (($hideicons&16)==0) {
						echo "</div>";
					}
					echo "</div>\n";
					if ($canedit) {
						echo '</div>'; //itemwrapper
					}
					if ($isopen) {
						echo "<div class=blockitems ";
					} else {
						echo "<div class=hidden ";
					}
					//if ($titlebg!='') {
					//	echo "style=\"background-color:$bicolor;\"";
					//}
					$style = '';
					if ($items[$i]['fixedheight']>0) {
						if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 6')!==false) {
							$style .= 'overflow: auto; height: expression( this.offsetHeight > '.$items[$i]['fixedheight'].' ? \''.$items[$i]['fixedheight'].'px\' : \'auto\' );';
						} else {
							$style .= 'overflow: auto; max-height:'.$items[$i]['fixedheight'].'px;';
						}
					}
					if ($titlebg!='') {
						$style .= "background-color:$bicolor;";
					}
					if ($style != '') {
						echo "style=\"$style\" ";
					}
					echo "id=\"block{$items[$i]['id']}\">";
					if ($isopen) {
						//if (isset($teacherid)) {echo generateadditem($parent.'-'.$bnum,'t');}
						showitems($items[$i]['items'],$parent.'-'.$bnum,$inpublic||$turnonpublic);
						
						//if (isset($teacherid) && count($items[$i]['items'])>0) {echo generateadditem($parent.'-'.$bnum,'b');}
					} else {
						echo _('Loading content...');
					}
					echo "</div>";	
				}
			}
			continue;
		   } else if ($ispublic && !$inpublic) {
			   continue;
		   }
		   $query = "SELECT itemtype,typeid FROM imas_items WHERE id='{$items[$i]}'";
		   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		   $line = mysql_fetch_array($result, MYSQL_ASSOC);
		   
		   if ($canedit) {
			   echo generatemoveselect($i,count($items),$parent,$blocklist);
		   }
		   if ($line['itemtype']=="Calendar") {
			   if ($ispublic) { continue;}
			   //echo "<div class=item>\n";
			   beginitem($canedit);
			   if ($canedit) {
				   echo '<span class="instronly">';
				   echo "<a href=\"addcalendar.php?id={$items[$i]}&block=$parent&cid=$cid&remove=true\">", _('Delete'), "</a>";
				   echo " | <a id=\"mcelink\" href=\"managecalitems.php?cid=$cid\">", _('Manage Events'), "</a>";
				   echo '</span>';
			   }
			   showcalendar("course");
			   enditem($canedit);// echo "</div>";
		   } else if ($line['itemtype']=="Assessment") {
			   if ($ispublic) { continue;}
			   $typeid = $line['typeid'];
			   $query = "SELECT name,summary,startdate,enddate,reviewdate,deffeedback,reqscore,reqscoreaid,avail,allowlate,timelimit FROM imas_assessments WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			   //do time limit mult
			   if (isset($studentinfo['timelimitmult'])) {
				$line['timelimit'] *= $studentinfo['timelimitmult'];
	    		   }
	    		   if (strpos($line['summary'],'<p ')!==0 && strpos($line['summary'],'<ul')!==0 && strpos($line['summary'],'<ol')!==0) {
				   $line['summary'] = '<p>'.$line['summary'].'</p>';
				   if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/',$line['summary'])) {
				   	   $line['summary'] = '';
				   }
			   }
			   if (isset($studentid) && !isset($sessiondata['stuview'])) {
			   	   $rec = "data-base=\"assesssum-$typeid\" ";
			   	   $line['summary'] = str_replace('<a ','<a '.$rec, $line['summary']);
			   }
			   
			   //check for exception
			   $canundolatepass = false;
			   $latepasscnt = 0;
			   if (isset($exceptions[$items[$i]])) {
			   	   //if latepass and it's before original due date or exception is for more than a latepass past now
			   	   if ($exceptions[$items[$i]][2]>0 && ($now < $line['enddate'] || $exceptions[$items[$i]][1] > $now + $latepasshrs*60*60)) {
			   	   	   $canundolatepass = true;
			   	   }
			   	   if ($exceptions[$items[$i]][2]>0) {
			   	   	   $latepasscnt = max(0,round(($exceptions[$items[$i]][1] - $line['enddate'])/($latepasshrs*3600)));
			   	   }
				   $line['startdate'] = $exceptions[$items[$i]][0];
				   $line['enddate'] = $exceptions[$items[$i]][1];
			   }
			   
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate =  _('Always');
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['reviewdate']==2000000000) {
				   $reviewdate = _('Always');
			   } else {
				   $reviewdate = formatdate($line['reviewdate']);
			   }
			   $nothidden = true;  $showgreyedout = false;
			   if (abs($line['reqscore'])>0 && $line['reqscoreaid']>0 && !$viewall && $line['enddate']>$now
			   	   && (!isset($exceptions[$items[$i]]) || $exceptions[$items[$i]][3]==0)) {
			   	   if ($line['reqscore']<0) {
			   	   	   $showgreyedout = true;
			   	   }
				   $query = "SELECT bestscores FROM imas_assessment_sessions WHERE assessmentid='{$line['reqscoreaid']}' AND userid='$userid'";
				   $result = mysql_query($query) or die("Query failed : " . mysql_error());
				   if (mysql_num_rows($result)==0) {
					   $nothidden = false;
				   } else {
					   $scores = explode(';',mysql_result($result,0,0));
					   if (round(getpts($scores[0]),1)+.02<abs($line['reqscore'])) {
					   	   $nothidden = false;
					   }
				   }
			   }
			   if (!$havecalcedviewedassess && $line['avail']>0 && $line['enddate']<$now && $line['allowlate']>10) {
				$havecalcedviewedassess = true;
				$viewedassess = array();
				$query = "SELECT typeid FROM imas_content_track WHERE courseid='$cid' AND userid='$userid' AND type='gbviewasid'";
				$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				while ($r = mysql_fetch_row($r2)) {
					$viewedassess[] = $r[0];
				}
			   }
			   	  
			   if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now && $nothidden) { //regular show
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&1)==0) {
					   if ($graphicalicons) {
						   echo "<img alt=\"assessment\" class=\"floatleft\" src=\"$imasroot/img/{$itemicons['assess']}\" />";
					   } else {
						  echo "<div class=icon style=\"background-color: " . makecolor2($line['startdate'],$line['enddate'],$now) . ";\">?</div>";
					   }
				   }
				   if (substr($line['deffeedback'],0,8)=='Practice') {
					   $endname = _('Available until');
				   } else {
					   $endname = _('Due');
				   }
				   $line['timelimit'] = abs($line['timelimit']);
				   if ($line['timelimit']>0) {
					   if ($line['timelimit']>3600) {
						$tlhrs = floor($line['timelimit']/3600);
						$tlrem = $line['timelimit'] % 3600;
						$tlmin = floor($tlrem/60);
						$tlsec = $tlrem % 60;
						$tlwrds = "$tlhrs " . _('hour');
						if ($tlhrs > 1) { $tlwrds .= "s";}
						if ($tlmin > 0) { $tlwrds .= ", $tlmin " . _('minute');}
						if ($tlmin > 1) { $tlwrds .= "s";}
						if ($tlsec > 0) { $tlwrds .= ", $tlsec " . _('second');}
						if ($tlsec > 1) { $tlwrds .= "s";}
					} else if ($line['timelimit']>60) {
						$tlmin = floor($line['timelimit']/60);
						$tlsec = $line['timelimit'] % 60;
						$tlwrds = "$tlmin " . _('minute');
						if ($tlmin > 1) { $tlwrds .= "s";}
						if ($tlsec > 0) { $tlwrds .= ", $tlsec " . _('second');}
						if ($tlsec > 1) { $tlwrds .= "s";}
					} else {
						$tlwrds = $line['timelimit'] . _(' second(s)');
					}
				   } else {
					   $tlwrds = '';
				   }
				   
				   echo "<div class=title><b><a href=\"../assessment/showtest.php?id=$typeid&cid=$cid\" ";
				   /*if (isset($studentid)) {
				   	   echo "data-base=\"assess-$typeid\" ";
				   }*/ //moved to showtest
				   if ($tlwrds != '') {
					   echo "onclick='return confirm(\"", sprintf(_('This assessment has a time limit of %s.  Click OK to start or continue working on the assessment.'), $tlwrds), "\")' ";
				   }
				   echo ">{$line['name']}</a></b>";
				   if ($line['enddate']!=2000000000) {
					   echo "<BR> $endname $enddate \n";
				   }
				   
				   if ($canedit) { 

					echo '<span class="instronly">';
					if ($line['allowlate']>0) {
						echo ' <span onmouseover="tipshow(this,\'', _('LatePasses Allowed'), '\')" onmouseout="tipout()">', _('LP'), '</span> |';
					}
					echo " <i><a href=\"addquestions.php?aid=$typeid&cid=$cid\">", _('Questions'), "</a></i> | <a href=\"addassessment.php?id=$typeid&block=$parent&cid=$cid\">", _('Settings'), "</a></i> \n";
					echo " | <a href=\"deleteassessment.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					echo " | <a href=\"gb-itemanalysis.php?cid=$cid&asid=average&aid=$typeid\">", _('Grades'), "</a>";
					echo " | <a href=\"contentstats.php?cid=$cid&type=A&id=$typeid\">",_('Stats'),'</a>';
					
					echo '</span>';
					
				   } else if (($line['allowlate']%10==1 || $line['allowlate']%10-1>$latepasscnt) && $latepasses>0) {
					echo " <a href=\"redeemlatepass.php?cid=$cid&aid=$typeid\">", _('Use LatePass'), "</a>";
					if ($canundolatepass) {
						 echo " | <a href=\"redeemlatepass.php?cid=$cid&aid=$typeid&undo=true\">", _('Un-use LatePass'), "</a>";
					}
				   } else if ($line['allowlate']>0 && isset($sessiondata['stuview'])) {
					echo _(' LatePass Allowed');
				   } else if ($line['allowlate']>0 && $canundolatepass) {
				   	   echo " <a href=\"redeemlatepass.php?cid=$cid&aid=$typeid&undo=true\">", _('Un-use LatePass'), "</a>";
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
				  
			   } else if ($line['avail']==1 && $line['enddate']<$now && $line['reviewdate']>$now) { //review show // && $nothidden
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&1)==0) {
					   if ($graphicalicons) {
						   echo "<img alt=\"assessment\" class=\"floatleft\" src=\"$imasroot/img/{$itemicons['assess']}\" />";
					   } else {
						  echo "<div class=icon style=\"background-color: #99f;\">?</div>";
					   }
				   }
				   echo "<div class=title><b><a href=\"../assessment/showtest.php?id=$typeid&cid=$cid\"";
				   /*if (isset($studentid)) {
				   	   echo " data-base=\"assess-$typeid\"";
				   }*/ //moved to showtest
				   
				   echo ">{$line['name']}</a></b><BR> ", sprintf(_('Past Due Date of %s.  Showing as Review'), $enddate).'.';
				   if ($line['reviewdate']!=2000000000) { 
					   echo " ", _('until'), " $reviewdate \n";
				   }
				   if ($line['allowlate']>10 && ($now - $line['enddate'])<$latepasshrs*3600 && !in_array($typeid,$viewedassess) && $latepasses>0 && !isset($sessiondata['stuview'])) { 
				   	   echo " <a href=\"redeemlatepass.php?cid=$cid&aid=$typeid\">", _('Use LatePass'), "</a>";
				   }
				   if ($canedit) { 
					echo '<span class="instronly">';
					if ($line['allowlate']>0) {
						echo ' <span onmouseover="tipshow(this,\'', _('LatePasses Allowed'), '\')" onmouseout="tipout()">LP</span> |';
					}
				   	echo " <i><a href=\"addquestions.php?aid=$typeid&cid=$cid\">", _('Questions'), "</a></i> | <a href=\"addassessment.php?id=$typeid&block=$parent&cid=$cid\">", _('Settings'), "</a>\n";
					echo " | <a href=\"deleteassessment.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					echo " | <a href=\"gb-itemanalysis.php?cid=$cid&asid=average&aid=$typeid\">", _('Grades'), "</a>";
					echo " | <a href=\"contentstats.php?cid=$cid&type=A&id=$typeid\">",_('Stats'),'</a>';
					
					echo '</span>';
					
				   } else if (isset($sessiondata['stuview']) && $line['allowlate']>10 && ($now - $line['enddate'])<$latepasshrs*3600) {
					echo _(' LatePass Allowed');
				   }
				   echo filter("<br/><i>" . _('This assessment is in review mode - no scores will be saved') . "</i></div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   } else if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now && $showgreyedout) {  //greyedout view for conditional items
			   	   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&1)==0) {
					   if ($graphicalicons) {
						   echo "<img alt=\"assessment\" class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons['assess']}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: #ccc;\">?</div>";
					   }
				   }
				   if (substr($line['deffeedback'],0,8)=='Practice') {
					   $endname = _('Available until');
				   } else {
					   $endname = _('Due');
				   }
				   
				   echo "<div class=\"title grey\"><b><i>{$line['name']}</i></b>";
				   echo '<br/><span class="small">'._('The requirements for beginning this item have not been met yet').'</span>';

				   if ($line['enddate']!=2000000000) {
					   echo "<br/> $endname $enddate \n";
				   }
				   echo filter("</div><div class=\"itemsum grey\">{$line['summary']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   	   
			   } else if ($viewall) { //not avail to stu
				   if ($line['avail']==0) {
					   $show = _('Hidden');
				   } else {
					   $show = sprintf(_('Available %1$s until %2$s'), $startdate, $enddate);
					   if ($line['reviewdate']>0 && $line['enddate']!=2000000000) {
						   $show .= sprintf(_(', Review until %s'), $reviewdate);
					   }
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&1)==0) {
					   
					   if ($graphicalicons) {
						   echo "<img alt=\"assessment\" class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons['assess']}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: #ccc;\">?</div>";
					   }
				   }
				   echo "<div class=title><i> <a href=\"../assessment/showtest.php?id=$typeid&cid=$cid\" >{$line['name']}</a></i>";
				   echo '<span class="instrdates">';
				   echo "<br/><i>$show</i>\n";
				   echo '</span>';
				   if ($canedit) {
					   
					   echo '<span class="instronly">';
					   if ($line['allowlate']>0) {
						echo ' <span onmouseover="tipshow(this,\'', _('LatePasses Allowed'), '\')" onmouseout="tipout()">', _('LP'), '</span> |';
					   }
					   echo "<a href=\"addquestions.php?aid=$typeid&cid=$cid\">", _('Questions'), "</a> | <a href=\"addassessment.php?id=$typeid&cid=$cid\">", _('Settings'), "</a> | \n";
					   echo "<a href=\"deleteassessment.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					   echo " | <a href=\"gb-itemanalysis.php?cid=$cid&asid=average&aid=$typeid\">", _('Grades'), "</a>";
					   echo " | <a href=\"contentstats.php?cid=$cid&type=A&id=$typeid\">",_('Stats'),'</a>';
					   
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); // echo "</div>\n";
			   }
			   
		   } else if ($line['itemtype']=="InlineText") {
		
			   $typeid = $line['typeid'];
			   $query = "SELECT title,text,startdate,enddate,fileorder,avail,isplaylist FROM imas_inlinetext WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			
			   $isvideo = ($line['isplaylist']>0) && (preg_match_all('/youtu/',$line['text'],$matches)>1 || preg_match_all('/google\.com\/file/',$line['text'],$matches)>1);
			   if ($isvideo) {
			   	   $json = array();
			   	   preg_match_all('/<a[^>]*(youtube\.com|youtu\.be)(.*?)"[^>]*?>(.*?)<\/a>/',$line['text'],$matches, PREG_SET_ORDER);
			   	   foreach ($matches as $k=>$m) {
			   	   	if ($m[1]=='youtube.com') {
			   	   		$p = explode('v=',$m[2]);
			   	   		$p2 = preg_split('/[#&]/',$p[1]);
			   	   	} else if ($m[1]=='youtu.be') {
			   	   		$p2 = preg_split('/[#&?]/',substr($m[2],1));
			   	   	}
			   	   	$vidid = $p2[0];
			   	   	if (preg_match('/.*[^r]t=((\d+)m)?((\d+)s)?.*/',$m[2],$tm)) {
			   	   		$start = ($tm[2]?$tm[2]*60:0) + ($tm[4]?$tm[4]*1:0);
			   	   	} else if (preg_match('/start=(\d+)/',$m[2],$tm)) {
			   	   		$start = $tm[1];
			   	   	} else {
			   	   		$start = 0;
			   	   	}
			   	   	if (preg_match('/end=(\d+)/',$m[2],$tm)) {
			   	   		$end = $tm[1];
			   	   	} else {
			   	   		$end = 0;
			   	   	}
			   	   	$json[] = '{"name":"'.str_replace('"','\\"',$m[3]).'", "vidid":"'.str_replace('"','\\"',$vidid).'", "start":'.$start.', "end":'.$end.'}';
			   	   	$line['text'] = str_replace($m[0],'<a href="#" onclick="playliststart('.$typeid.','.$k.');return false;">'.$m[3].'</a>',$line['text']);
			   	   }
			   	   preg_match_all('/<a[^>]*google\.com\/file\/d\/(.*?)\/view[^"]*?"[^>]*?>(.*?)<\/a>/',$line['text'],$matches, PREG_SET_ORDER);
			   	   foreach ($matches as $k=>$m) {
			   	   	$vidid = $m[1];
			   	   	
			   	   	$json[] = '{"name":"'.str_replace('"','\\"',$m[2]).'", "vidid":"'.str_replace('"','\\"',$vidid).'", "start":0, "end":0, "isGdrive":true}';
			   	   	$line['text'] = str_replace($m[0],'<a href="#" onclick="playliststart('.$typeid.','.$k.');return false;">'.$m[2].'</a>',$line['text']);
			   	   }
			   	   
			   	   $playlist = '<div class="playlistbar" id="playlistbar'.$typeid.'"><div class="vidtracksA"></div> <span> Playlist</span> ';
			   	   $playlist .= '<div class="vidplay" style="margin-left:1em;cursor:pointer" onclick="playliststart('.$typeid.',0)"></div>';
			   	   $playlist .= '<div class="vidrewI" style="display:none;"></div><div class="vidff" style="display:none;margin-right:1em;"></div> ';
			   	   $playlist .= '<span class="playlisttitle"></span></div>';
			   	   $playlist .= '<div class="playlistwrap" id="playlistwrap'.$typeid.'">';
			   	   $playlist .= '<div class="playlisttext">'.$line['text'].'</div><div class="playlistvid"></div></div>';
			   	   $playlist .= '<script type="text/javascript">playlist['.$typeid.'] = ['.implode(',',$json).'];</script>'; 
			   	   $line['text'] = $playlist;
			   	   
			   } else if (strpos($line['text'],'<p ')!==0 && strpos($line['text'],'<ul ')!==0 && strpos($line['text'],'<ol ')!==0) {
				   $line['text'] = '<p>'.$line['text'].'</p>';
				   if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/',$line['text'])) {
				   	   $line['text'] = '';
				   }
			   }
			   if (isset($studentid) && !isset($sessiondata['stuview'])) {
			   	   $rec = "data-base=\"inlinetext-$typeid\" ";
			   	   $line['text'] = str_replace('<a ','<a '.$rec, $line['text']);
			   } 
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = _('Always');
			   } else {
				   $enddate = formatdate($line['enddate']);
			   }
			   if ($line['avail']==2 || ($line['startdate']<$now && $line['enddate']>$now && $line['avail']==1)) {
				   if ($line['avail']==2) {
					   $show = _('Showing Always ');
					   $color = '#0f0';
				   } else {
					   $show = _('Showing until:') . " $enddate";
					   $color = makecolor2($line['startdate'],$line['enddate'],$now);
				   }
				   beginitem($canedit,$items[$i]);// echo "<div class=item>\n";
				   echo '<a name="inline'.$typeid.'"></a>';
				   if ($line['title']!='##hidden##') {
					   if (($hideicons&2)==0) {			   
						   if ($graphicalicons) {
							   echo "<img alt=\"text item\" class=\"floatleft\" src=\"$imasroot/img/{$itemicons['inline']}\" />";
						   } else {
							   echo "<div class=icon style=\"background-color: $color;\">!</div>";
						   }
					   }
					   echo "<div class=title> <b>{$line['title']}</b>\n";
					   if ($viewall) { 
						   echo '<span class="instrdates">';
						   echo "<br/>$show ";
						   echo '</span>';
					   }
					   if ($canedit) {
						   echo '<span class="instronly">';
						   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
						   echo "<a href=\"deleteinlinetext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
						   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
						   if (strpos($line['text'],'<a')!==false) {
						   	   echo " | <a href=\"contentstats.php?cid=$cid&type=I&id=$typeid\">",_('Stats'),'</a>';
						   }
						   
						   echo '</span>';
					   }
					   echo "</div>";
				   } else {
					   if ($viewall) { 
						  echo '<span class="instrdates">';
						   echo "<br/>$show ";
						   echo '</span>';
					   }
					   if ($canedit) {
						   echo '<span class="instronly">';
						   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
						   echo "<a href=\"deleteinlinetext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
						   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
						   echo '</span>';
					   } 
					   
				   }
				   echo filter("<div class=itemsum>{$line['text']}\n");
				   $query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='$typeid'";
				   $result = mysql_query($query) or die("Query failed : " . mysql_error());
				   if (mysql_num_rows($result)>0) {
					   echo '<ul class="fileattachlist">';
					   $filenames = array();
					   $filedescr = array();
					   while ($row = mysql_fetch_row($result)) {
						   $filenames[$row[0]] = $row[2];
						   $filedescr[$row[0]] = $row[1];
					   }
					   foreach (explode(',',$line['fileorder']) as $fid) {
						   //echo "<li><a href=\"$imasroot/course/files/{$filenames[$fid]}\" target=\"_blank\">{$filedescr[$fid]}</a></li>";
						   echo "<li><a href=\"".getcoursefileurl($filenames[$fid])."\" target=\"_blank\">{$filedescr[$fid]}</a></li>";
					   }
					  
					   echo "</ul>";
				   }
				   echo "</div>";
				   enditem($canedit); //echo "</div>\n";
			   } else if ($viewall) {
				   if ($line['avail']==0) {
					   $show = _('Hidden');
				   } else {
					   $show = sprintf(_('Showing %1$s until %2$s'), $startdate, $enddate);
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if ($line['title']!='##hidden##') {
					   if ($graphicalicons) {
						   echo "<img alt=\"text item\" class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons['inline']}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: #ccc;\">!</div>";
					   }
					   echo "<div class=title><i> <b>{$line['title']}</b> </i><br/>";
				   } else {
					   echo "<div class=title>";
				   }
				   echo '<span class="instrdates">';
				   echo "<i>$show</i> ";
				   echo '</span>';
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
					   echo "<a href=\"deleteinlinetext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					   if (strpos($line['text'],'<a')!==false) {
					   	   echo " | <a href=\"contentstats.php?cid=$cid&type=I&id=$typeid\">",_('Stats'),'</a>';
					   }
					   
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['text']}\n");
				   $query = "SELECT id,description,filename FROM imas_instr_files WHERE itemid='$typeid'";
				   $result = mysql_query($query) or die("Query failed : " . mysql_error());
				   if (mysql_num_rows($result)>0) {
					   echo '<ul class="fileattachlist">';
					   $filenames = array();
					   $filedescr = array();
					   while ($row = mysql_fetch_row($result)) {
						   $filenames[$row[0]] = $row[2];
						   $filedescr[$row[0]] = $row[1];
					   }
					   foreach (explode(',',$line['fileorder']) as $fid) {
						  // echo "<li><a href=\"$imasroot/course/files/{$filenames[$fid]}\" target=\"_blank\">{$filedescr[$fid]}</a></li>";
						    echo "<li><a href=\"".getcoursefileurl($filenames[$fid])."\" target=\"_blank\">{$filedescr[$fid]}</a></li>";
					  
					   }
					  
					   echo "</ul>";
				   }
				   echo "</div>";
				   enditem($canedit); //echo "</div>\n";
			   }
		   } else if ($line['itemtype']=="Drill") {
			   $typeid = $line['typeid'];
			   $query = "SELECT name,summary,startdate,enddate,avail FROM imas_drillassess WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			  
			   if (strpos($line['summary'],'<p ')!==0) {
				   $line['summary'] = '<p>'.$line['summary'].'</p>';
				   if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/',$line['summary'])) {
				   	   $line['summary'] = '';
				   }
			   }
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = _('Always');
			   } else {
				   $enddate = formatdate($line['enddate']);
			   }
			   
			   $alink = "drillassess.php?cid=$cid&daid=$typeid";
			   
			   
			   if ($line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
				   if ($line['avail']==2) {
					   $show = _('Showing Always ');
					   $color = '#0f0';
				   } else {
					   $show = _('Showing until:') . " $enddate";
					   $color = makecolor2($line['startdate'],$line['enddate'],$now);
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&4)==0) {
					   if ($graphicalicons) {
						  echo "<img alt=\"Drill\" class=\"floatleft\" src=\"$imasroot/img/{$itemicons['drill']}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: $color;\">!</div>";
					   }
				   }
				   echo "<div class=title>";
				   echo "<b><a href=\"$alink\" $target>{$line['name']}</a></b>\n";
				   if ($viewall) { 
					   echo '<span class="instrdates">';
					   echo "<br/>$show ";
					   echo '</span>';
				   } else if ($line['enddate']!=2000000000) {
					   echo "<br/>$show";
				   }
				   	   
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"adddrillassess.php?daid=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
					   echo "<a href=\"deletedrillassess.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					   echo " | <a href=\"gb-viewdrill.php?cid=$cid&daid=$typeid\">", _('Scores'), "</a>";
					
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   } else if ($viewall) {
				   if ($line['avail']==0) {
					   $show = _('Hidden');
				   } else {
					   $show = sprintf(_('Showing %1$s until %2$s'), $startdate, $enddate);
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				  if ($graphicalicons) {
					  echo "<img alt=\"Drill\" class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons['drill']}\" />";
				  } else {
					   echo "<div class=icon style=\"background-color: #ccc;\">!</div>";
				   }
				   echo "<div class=title>";
				   echo "<i> <b><a href=\"$alink\" $target>{$line['name']}</a></b> </i>";
				   echo '<span class="instrdates">';
				   echo "<br/><i>$show</i> ";
				   echo '</span>';
				   if ($canedit) {
					  echo '<span class="instronly">';
					   echo "<a href=\"adddrillassess.php?daid=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
					   echo "<a href=\"deletedrillassess.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					   echo " | <a href=\"gb-viewdrill.php?cid=$cid&daid=$typeid\">", _('Scores'), "</a>";
					
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); // echo "</div>\n";
			   }
		   } else if ($line['itemtype']=="LinkedText") {
			   $typeid = $line['typeid'];
			   $query = "SELECT title,summary,text,startdate,enddate,avail,target FROM imas_linkedtext WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			  
			   if (strpos($line['summary'],'<p ')!==0 && strpos($line['summary'],'<ul ')!==0 && strpos($line['summary'],'<ol ')!==0) {
				   $line['summary'] = '<p>'.$line['summary'].'</p>';
				   if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/',$line['summary'])) {
				   	   $line['summary'] = '';
				   } 
			   }
			   if (isset($studentid) && !isset($sessiondata['stuview'])) {
			   	   $rec = "data-base=\"linkedsum-$typeid\" ";
			   	   $line['summary'] = str_replace('<a ','<a '.$rec, $line['summary']);
			   } 
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = _('Always');
			   } else {
				   $enddate = formatdate($line['enddate']);
			   }
			   if ($line['target']==1) {
				   $target = 'target="_blank"';
			   } else {
				   $target = '';
			   }
			   if ((substr($line['text'],0,4)=="http") && (strpos(trim($line['text'])," ")===false)) { //is a web link
				   $alink = trim($line['text']);
				   $icon = 'web';
			   } else if (substr(strip_tags($line['text']),0,5)=="file:") {
				   $filename = substr(strip_tags($line['text']),5);
				   $alink = getcoursefileurl($filename);//$imasroot . "/course/files/".$filename;
				   $ext = substr($filename,strrpos($filename,'.')+1);
				   switch($ext) {
				   	  case 'xls': $icon = 'xls'; break;
					  case 'pdf': $icon = 'pdf'; break;
					  case 'html': $icon = 'html'; break;
					  case 'ppt': $icon = 'ppt'; break;
					  case 'zip': $icon = 'zip'; break;
					  case 'png':
					  case 'gif':
					  case 'jpg':
					  case 'bmp': $icon = 'image'; break;
					  case 'mp3':
					  case 'wav':
					  case 'wma': $icon = 'sound'; break;
					  case 'swf':
					  case 'avi':
					  case 'mpg': $icon = 'video'; break;
					  case 'nb': $icon = 'mathnb'; break;
					  case 'mws':
					  case 'mw': $icon = 'maple'; break;
					  default : $icon = 'doc'; break;
				   }
				   if (!isset($itemicons[$icon])) {
				   	   $icon = 'doc';
				   }
						   	   
			   } else {
				   if ($ispublic) { 
					   $alink = "showlinkedtextpublic.php?cid=$cid&id=$typeid";
				   } else {
					   $alink = "showlinkedtext.php?cid=$cid&id=$typeid";
				   }
				   $icon = 'html';
			   }
			   if (isset($studentid) && !isset($sessiondata['stuview'])) {
			   	   $rec = "data-base=\"linkedlink-$typeid\"";
			   } else {
			   	   $rec = '';
			   }
			   
			   if ($line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
				   if ($line['avail']==2) {
					   $show = _('Showing Always ');
					   $color = '#0f0';
				   } else {
					   $show = _('Showing until:') . " $enddate";
					   $color = makecolor2($line['startdate'],$line['enddate'],$now);
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&4)==0) {
					   if ($graphicalicons) {
						  echo "<img alt=\"link to $icon\" class=\"floatleft\" src=\"$imasroot/img/{$itemicons[$icon]}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: $color;\">!</div>";
					   }
				   }
				   echo "<div class=title>";
				   if (isset($readlinkeditems[$typeid])) {
				   	   echo '<b class="readitem">';
				   } else {
				   	   echo '<b>';
				   }
				   echo "<a href=\"$alink\" $rec $target>{$line['title']}</a></b>\n";
				   if ($viewall) { 
					   echo '<span class="instrdates">';
					   echo "<br/>$show ";
					   echo '</span>';
				   }
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addlinkedtext.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
					   echo "<a href=\"deletelinkedtext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					   echo " | <a href=\"contentstats.php?cid=$cid&type=L&id=$typeid\">",_('Stats'),'</a>';
					   
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   } else if ($viewall) {
				   if ($line['avail']==0) {
					   $show = _('Hidden');
				   } else {
					   $show = sprintf(_('Showing %1$s until %2$s'), $startdate, $enddate);
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				  if ($graphicalicons) {
					  echo "<img alt=\"link to $icon\" class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons[$icon]}\" />";
				  } else {
					   echo "<div class=icon style=\"background-color: #ccc;\">!</div>";
				   }
				   echo "<div class=title>";
				   echo "<i> <b><a href=\"$alink\" onclick=\"$rec\" $target>{$line['title']}</a></b> </i>";
				   echo '<span class="instrdates">';
				   echo "<br/><i>$show</i> ";
				   echo '</span>';
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addlinkedtext.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
					   echo "<a href=\"deletelinkedtext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					   echo " | <a href=\"contentstats.php?cid=$cid&type=L&id=$typeid\">",_('Stats'),'</a>';
					   
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['summary']}</div>\n");
				   enditem($canedit); // echo "</div>\n";
			   }
		   } else if ($line['itemtype']=="Forum") {
			   if ($ispublic) { continue;}
			   $typeid = $line['typeid'];
			   $query = "SELECT id,name,description,startdate,enddate,groupsetid,avail,postby,replyby FROM imas_forums WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			   /*$dofilter = false;
			   if ($line['grpaid']>0) {
				if (!$viewall) {
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
					$query = "SELECT userid FROM imas_assessment_sessions WHERE agroupid='$agroupid' AND assessmentid='{$line['grpaid']}'";
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
					$query = "SELECT userid FROM imas_tutors WHERE courseid='$cid'";
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
			   $query .= "LEFT JOIN imas_forum_posts ON imas_forum_views.threadid=imas_forum_posts.threadid AND imas_forum_views.userid='$userid' ";
			   $query .= "WHERE imas_forum_posts.forumid='$typeid' ";
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
			   */
			  
			  
			   if (strpos($line['description'],'<p ')!==0) {
				   $line['description'] = '<p>'.$line['description'].'</p>';
				   if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/',$line['description'])) {
				   	   $line['description'] = '';
				   }
			   }
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = _('Always');
			   } else {
				   $enddate = formatdate($line['enddate']);
			   }
			   if ($line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
				   if ($line['avail']==2) {
					   $show = _('Showing Always ');
					   $color = '#0f0';
				   } else {
					   $show = _('Showing until:') . " $enddate";
					   $color = makecolor2($line['startdate'],$line['enddate'],$now);
				   }
				   $duedates = "";
				   if ($line['postby']>$now && $line['postby']!=2000000000) {
					   $duedates .= sprintf(_('New Threads due %s. '), formatdate($line['postby']));
				   }
				   if ($line['replyby']>$now && $line['replyby']!=2000000000) {
					   $duedates .= sprintf(_('Replies due %s. '), formatdate($line['replyby']));
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&8)==0) {
					   if ($graphicalicons) {
						   echo "<img alt=\"forum\" class=\"floatleft\" src=\"$imasroot/img/{$itemicons['forum']}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: $color;\">F</div>";
					   }
				   }
				   echo "<div class=title> ";
				   echo "<b><a href=\"../forums/thread.php?cid=$cid&forum={$line['id']}\">{$line['name']}</a></b>\n";
				   if (isset($newpostcnts[$line['id']]) && $newpostcnts[$line['id']]>0 ) {
					   echo " <a href=\"../forums/thread.php?cid=$cid&forum={$line['id']}&page=-1\" style=\"color:red\">", sprintf(_('New Posts (%s)'), $newpostcnts[$line['id']]), "</a>";
				   }
				   if ($viewall) { 
					   echo '<span class="instrdates">';
					   echo "<br/>$show ";
					   echo '</span>';
				   }
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addforum.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
					   echo "<a href=\"deleteforum.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					   echo " | <a href=\"contentstats.php?cid=$cid&type=F&id=$typeid\">",_('Stats'),'</a>';
					   
					   echo '</span>';
				   }
				   if ($duedates!='') {echo "<br/>$duedates";}
				   echo filter("</div><div class=itemsum>{$line['description']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   } else if ($viewall) {
				   if ($line['avail']==0) {
					   $show = _('Hidden');
				   } else {
					   $show = sprintf(_('Showing %1$s until %2$s'), $startdate, $enddate);
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if ($graphicalicons) {
					   echo "<img alt=\"forum\" class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons['forum']}\" />";
				   } else {
					   echo "<div class=icon style=\"background-color: #ccc;\">F</div>";
				   }   
				   echo "<div class=title><i> <b><a href=\"../forums/thread.php?cid=$cid&forum={$line['id']}\">{$line['name']}</a></b></i> ";
				   if (isset($newpostcnts[$line['id']]) && $newpostcnts[$line['id']]>0 ) {
					   echo " <a href=\"../forums/thread.php?cid=$cid&forum={$line['id']}&page=-1\" style=\"color:red\">", sprintf(_('New Posts (%s)'), $newpostcnts[$line['id']]), "</a>";
				   }
				   echo '<span class="instrdates">';
				   echo "<br/><i>$show </i>";
				   echo '</span>';
				    
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addforum.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
					   echo "<a href=\"deleteforum.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					   echo " | <a href=\"contentstats.php?cid=$cid&type=F&id=$typeid\">",_('Stats'),'</a>';
					   
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['description']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   }
		   } else if ($line['itemtype']=="Wiki") {
		   	  // if ($ispublic) { continue;}
			   $typeid = $line['typeid'];
			   $query = "SELECT id,name,description,startdate,enddate,editbydate,avail,settings,groupsetid FROM imas_wikis WHERE id='$typeid'";
			   $result = mysql_query($query) or die("Query failed : " . mysql_error());
			   $line = mysql_fetch_array($result, MYSQL_ASSOC);
			   if ($ispublic && $line['groupsetid']>0) { continue;}
			   if (strpos($line['description'],'<p ')!==0) {
				   $line['description'] = '<p>'.$line['description'].'</p>';
				   if (preg_match('/^\s*<p[^>]*>\s*<\/p>\s*$/',$line['description'])) {
				   	   $line['description'] = '';
				   }
			   }
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = _('Always');
			   } else {
				   $enddate = formatdate($line['enddate']);
			   }
			   $hasnew = false;
			   if ($viewall || $line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
			   	   if ($line['groupsetid']>0 && !$canedit) {
			   	   	   $query = 'SELECT i_sg.id,i_sg.name FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
			   	   	   $query .= "WHERE i_sgm.userid='$userid' AND i_sg.groupsetid='{$line['groupsetid']}'";
			   	   	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			   	   	   if (mysql_num_rows($result)>0) {
			   	   	   	   $wikigroupid = mysql_result($result,0,0);
			   	   	   } else {
			   	   	   	   $wikigroupid = 0;
			   	   	   }
			   	   }
			   	   $wikilastviews = array();
			   	   $query = "SELECT stugroupid,lastview FROM imas_wiki_views WHERE userid='$userid' AND wikiid='$typeid'";
			   	   $result = mysql_query($query) or die("Query failed : " . mysql_error());
				   while ($row = mysql_fetch_row($result)) {
				   	   $wikilastviews[$row[0]] = $row[1];
				   }
				   
				   $query = "SELECT stugroupid,MAX(time) FROM imas_wiki_revisions WHERE wikiid='$typeid' ";
				   if ($line['groupsetid']>0 && !$canedit) { //if group and not instructor limit to group
				   	   $query .= "AND stugroupid='$wikigroupid' ";
				   }
				   $query .= "GROUP BY stugroupid";
				   $result = mysql_query($query) or die("Query failed : " . mysql_error());
				   while ($row = mysql_fetch_row($result)) {
				   	   if (!isset($wikilastviews[$row[0]]) || $wikilastviews[$row[0]] < $row[1]) {
				   	   	   $hasnew = true;
				   	   	   break;
				   	   }
				   }
			   }   
			   if ($line['avail']==2 || ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now)) {
				   if ($line['avail']==2) {
					   $show = _('Showing Always ');
					   $color = '#0f0';
				   } else {
					   $show = _('Showing until:') . " $enddate";
					   $color = makecolor2($line['startdate'],$line['enddate'],$now);
				   }
				   $duedates = "";
				   if ($line['editbydate']>$now && $line['editbydate']!=2000000000) {
					   $duedates .= sprintf(_('Edits due by %s. '), formatdate($line['editbydate']));
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if (($hideicons&8)==0) {
					   if ($graphicalicons) {
						   echo "<img alt=\"wiki\" class=\"floatleft\" src=\"$imasroot/img/{$itemicons['wiki']}\" />";
					   } else {
						   echo "<div class=icon style=\"background-color: $color;\">W</div>";
					   }
				   }
				   echo "<div class=title> ";
				   if ($ispublic) {
				   	   echo "<b><a href=\"../wikis/viewwikipublic.php?cid=$cid&id={$line['id']}\">{$line['name']}</a></b>\n"; 
				   } else {
				   	   if (isset($studentid) && !isset($sessiondata['stuview'])) {
						   $rec = "data-base=\"wiki-$typeid\"";
					   } else {
						   $rec = '';
					   }
				   	   echo "<b><a href=\"../wikis/viewwiki.php?cid=$cid&id={$line['id']}\" $rec>{$line['name']}</a></b>\n";
				   	   if ($hasnew) {
				   	    	    echo " <span style=\"color:red\">", _('New Revisions'), "</span>";
				   	   }
				   }
				   if ($viewall) { 
					   echo '<span class="instrdates">';
					   echo "<br/>$show ";
					   echo '</span>';
				   }
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addwiki.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
					   echo "<a href=\"deletewiki.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					   echo " | <a href=\"contentstats.php?cid=$cid&type=W&id=$typeid\">",_('Stats'),'</a>';
					   
					   echo '</span>';
				   }
				   if ($duedates!='') {echo "<br/>$duedates";}
				   echo filter("</div><div class=itemsum>{$line['description']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   } else if ($viewall) {
				   if ($line['avail']==0) {
					   $show = _('Hidden');
				   } else {
					   $show = sprintf(_('Showing %1$s until %2$s'), $startdate, $enddate);
				   }
				   beginitem($canedit,$items[$i]); //echo "<div class=item>\n";
				   if ($graphicalicons) {
					   echo "<img alt=\"wiki\" class=\"floatleft faded\" src=\"$imasroot/img/{$itemicons['wiki']}\" />";
				   } else {
					   echo "<div class=icon style=\"background-color: #ccc;\">W</div>";
				   }   
				   echo "<div class=title><i> <b><a href=\"../wikis/viewwiki.php?cid=$cid&id={$line['id']}\">{$line['name']}</a></b></i> ";
				   if ($hasnew) {
				   	   echo " <span style=\"color:red\">", _('New Revisions'), "</span>";
				   }
				   echo '<span class="instrdates">';
				   echo "<br/><i>$show </i>";
				   echo '</span>';
				   if ($canedit) {
					   echo '<span class="instronly">';
					   echo "<a href=\"addwiki.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
					   echo "<a href=\"deletewiki.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
					   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
					   echo " | <a href=\"contentstats.php?cid=$cid&type=W&id=$typeid\">",_('Stats'),'</a>';
					   
					   echo '</span>';
				   }
				   echo filter("</div><div class=itemsum>{$line['description']}</div>\n");
				   enditem($canedit); //echo "</div>\n";
			   }
		   }
	   }
	   if (count($items)>0) {
		   if ($canedit) {echo generateadditem($parent,'b');}
	   }
   }
   
   function generateadditem($blk,$tb) {
   	global $cid, $CFG,$imasroot;
   	if (isset($CFG['CPS']['additemtype']) && $CFG['CPS']['additemtype'][0]=='links') {
   		if ($tb=='BB' || $tb=='LB') {$tb = 'b';}
   		if ($tb=='t' && $blk=='0') {
   			$html = '<div id="topadditem" class="additembox"><span><b>' . _('Add here:') . '</b> ';
   		} else if ($tb=='b' && $blk=='0') {
   			$html = '<div id="bottomadditem" class="additembox"><span><b>' . _('Add here:') . '</b> ';
   		} else {
   			$html = '<div class="additembox"><span><b>' . _('Add here:') . '</b> ';
   		}
   		
		$html .= "<a href=\"addassessment.php?block=$blk&tb=$tb&cid=$cid\">";
		if (isset($CFG['CPS']['miniicons']['assess'])) {
			$html .= "<img alt=\"assessment\" class=\"mida\" src=\"$imasroot/img/{$CFG['CPS']['miniicons']['assess']}\"/> ";
		}
		$html .= _('Assessment') ."</a> | ";
		
		/*$html .= "<a href=\"adddrillassess.php?block=$blk&tb=$tb&cid=$cid\">";
		if (isset($CFG['CPS']['miniicons']['drill'])) {
			$html .= "<img alt=\"drill\" class=\"mida\" src=\"$imasroot/img/{$CFG['CPS']['miniicons']['drill']}\"/> ";
		}
		$html .= "Drill</a> | ";
		*/
		
		$html .= "<a href=\"addinlinetext.php?block=$blk&tb=$tb&cid=$cid\">";
		if (isset($CFG['CPS']['miniicons']['inline'])) {
			$html .= "<img alt=\"inline text\" class=\"mida\" src=\"$imasroot/img/{$CFG['CPS']['miniicons']['inline']}\"/> ";
		}
		$html .= _('Text') . "</a> | ";
		
		$html .= "<a href=\"addlinkedtext.php?block=$blk&tb=$tb&cid=$cid\">";
		if (isset($CFG['CPS']['miniicons']['linked'])) {
			$html .= "<img alt=\"linked text\" class=\"mida\" src=\"$imasroot/img/{$CFG['CPS']['miniicons']['linked']}\"/> ";
		}
		$html .= _('Link') . "</a> | ";
		
		$html .= "<a href=\"addforum.php?block=$blk&tb=$tb&cid=$cid\">";
		if (isset($CFG['CPS']['miniicons']['forum'])) {
			$html .= "<img alt=\"forum\" class=\"mida\" src=\"$imasroot/img/{$CFG['CPS']['miniicons']['forum']}\"/> ";
		}
		$html .= _('Forum') . "</a> | ";
		
		$html .= "<a href=\"addwiki.php?block=$blk&tb=$tb&cid=$cid\">";
		if (isset($CFG['CPS']['miniicons']['wiki'])) {
			$html .= "<img alt=\"wiki\" class=\"mida\" src=\"$imasroot/img/{$CFG['CPS']['miniicons']['wiki']}\"/> ";
		}
		$html .= _('Wiki') . "</a> | ";
		
		$html .= "<a href=\"adddrillassess.php?block=$blk&tb=$tb&cid=$cid\">";
		if (isset($CFG['CPS']['miniicons']['drill'])) {
			$html .= "<img alt=\"drill\" class=\"mida\" src=\"$imasroot/img/{$CFG['CPS']['miniicons']['drill']}\"/> ";
		}
		$html .= _('Drill') . "</a> | ";
		
		$html .= "<a href=\"addblock.php?block=$blk&tb=$tb&cid=$cid\">";
		if (isset($CFG['CPS']['miniicons']['folder'])) {
			$html .= "<img alt=\"folder\" class=\"mida\" src=\"$imasroot/img/{$CFG['CPS']['miniicons']['folder']}\"/> ";
		}
		$html .= _('Block') . "</a> | ";
		
		$html .= "<a href=\"addcalendar.php?block=$blk&tb=$tb&cid=$cid\">";
		if (isset($CFG['CPS']['miniicons']['calendar'])) {
			$html .= "<img alt=\"calendar\" class=\"mida\" src=\"$imasroot/img/{$CFG['CPS']['miniicons']['calendar']}\"/> ";
		}
		$html .= _('Calendar') . "</a>";
		$html .= '</span>';
		$html .= '</div>';
   		
   	} else {
   		$html = "<select name=addtype id=\"addtype$blk-$tb\" onchange=\"additem('$blk','$tb')\" ";
		if ($tb=='t') {
			$html .= 'style="margin-bottom:5px;"';
		}
		$html .= ">\n";
		$html .= "<option value=\"\">" . _('Add An Item...') . "</option>\n";
		$html .= "<option value=\"assessment\">" . _('Add Assessment') . "</option>\n";
		//$html .= "<option value=\"drillassess\">Add Drill</option>\n";
		$html .= "<option value=\"inlinetext\">" . _('Add Inline Text') . "</option>\n";
		$html .= "<option value=\"linkedtext\">" . _('Add Link') . "</option>\n";
		$html .= "<option value=\"forum\">" . _('Add Forum') . "</option>\n";
		$html .= "<option value=\"wiki\">" . _('Add Wiki') . "</option>\n";
		$html .= "<option value=\"drillassess\">" . _('Add Drill') . "</option>\n";
		$html .= "<option value=\"block\">" . _('Add Block') . "</option>\n";
		$html .= "<option value=\"calendar\">" . _('Add Calendar') . "</option>\n";
		$html .= "</select><BR>\n";
   		
   	}
	return $html;
   }
    
   function generatemoveselect($num,$count,$blk,$blocklist) {
   	   global $toolset;
   	   if (($toolset&4)==4) {return '';}
	$num = $num+1;  //adjust indexing
	$html = "<select class=\"mvsel\" id=\"$blk-$num\" onchange=\"moveitem($num,'$blk')\">\n";
	for ($i = 1; $i <= $count; $i++) {
		$html .= "<option value=\"$i\" ";
		if ($i==$num) { $html .= "SELECTED";}
		$html .= ">$i</option>\n";
	}
	for ($i=0; $i<count($blocklist); $i++) {
		if ($num!=$blocklist[$i]) {
			$html .= "<option value=\"B-{$blocklist[$i]}\">" . sprintf(_('Into %s'),$blocklist[$i]) . "</option>\n";
		}
	}
	if ($blk!='0') {
		$html .= '<option value="O-' . $blk . '">' . _('Out of Block') . '</option>';
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
	   if ($etime==2000000000 && $now >= $stime) {
		   return '#0f0';
	   } else if ($stime==0) {
		   return makecolor($etime,$now);
	   }
	   if ($etime==$stime) {
		   return '#ccc';
	   }
	   $r = ($etime-$now)/($etime-$stime);  //0 = etime, 1=stime; 0:#f00, 1:#0f0, .5:#ff0
	   if ($etime<$now || $stime>$now) {
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
				  if ($hasexc[0]<$items[$k]['startdate']) {
					  $items[$k]['startdate'] = $hasexc[0];
				  }
				  if ($hasexc[1]>$items[$k]['enddate']) {
					  $items[$k]['enddate'] = $hasexc[1];
				  }
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
   function getpts($scs) {
	$tot = 0;
  	foreach(explode(',',$scs) as $sc) {
		$qtot = 0;
		if (strpos($sc,'~')===false) {
			if ($sc>0) { 
				$qtot = $sc;
			} 
		} else {
			$sc = explode('~',$sc);
			foreach ($sc as $s) {
				if ($s>0) { 
					$qtot+=$s;
				}
			}
		}
		$tot += round($qtot,1);
	}
	return $tot;
   }
   
   //instructor-only tree-based quick view of full course
   function quickview($items,$parent,$showdates=false,$showlinks=true) { 
	   global $teacherid,$cid,$imasroot,$userid,$openblocks,$firstload,$sessiondata,$previewshift,$hideicons,$exceptions,$latepasses,$CFG;
	   if (!is_array($openblocks)) {$openblocks = array();}
	   $itemtypes = array();  $iteminfo = array();
	   $query = "SELECT id,itemtype,typeid FROM imas_items WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $itemtypes[$row[0]] = array($row[1],$row[2]);
	   }
	   $query = "SELECT id,name,startdate,enddate,reviewdate,avail FROM imas_assessments WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $id = array_shift($row);
		   $iteminfo['Assessment'][$id] = $row;
	   }
	   $query = "SELECT id,title,text,startdate,enddate,avail FROM imas_inlinetext WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $id = array_shift($row);
		   $iteminfo['InlineText'][$id] = $row;
	   }
	   $query = "SELECT id,title,startdate,enddate,avail FROM imas_linkedtext WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $id = array_shift($row);
		   $iteminfo['LinkedText'][$id] = $row;
	   }
	   $query = "SELECT id,name,startdate,enddate,avail FROM imas_forums WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $id = array_shift($row);
		   $iteminfo['Forum'][$id] = $row;
	   }
	   
	   $query = "SELECT id,name,startdate,enddate,avail FROM imas_wikis WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $id = array_shift($row);
		   $iteminfo['Wiki'][$id] = $row;
	   }
	   $query = "SELECT id,name,startdate,enddate,avail FROM imas_drillassess WHERE courseid='$cid'";
	   $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   while ($row = mysql_fetch_row($result)) {
		   $id = array_shift($row);
		   $iteminfo['Drill'][$id] = $row;
	   }
	   $now = time() + $previewshift;
	   for ($i=0;$i<count($items); $i++) {
		   if (is_array($items[$i])) { //is a block
			$items[$i]['name'] = stripslashes($items[$i]['name']);
			
			if ($items[$i]['startdate']==0) {
				$startdate = _('Always');
			} else {
				$startdate = formatdate($items[$i]['startdate']);
			}
			if ($items[$i]['enddate']==2000000000) {
				$enddate = _('Always');
			} else {
				$enddate = formatdate($items[$i]['enddate']);
			}
			$bnum = $i+1;
			if (strlen($items[$i]['SH'])==1 || $items[$i]['SH'][1]=='O') {
				$availbeh = _('Expanded');
			} else if ($items[$i]['SH'][1]=='F') {
				$availbeh = _('as Folder');
			} else {
				$availbeh = _('Collapsed');
			}
			if ($items[$i]['avail']==2) {
				$show = sprintf(('Showing %s Always'), $availbeh);
			} else if ($items[$i]['avail']==0) {
				$show = _('Hidden');
			} else {
				$show = sprintf(_('Showing %1$s %2$s until %3$s'), $availbeh, $startdate, $enddate);
			}
			if ($items[$i]['avail']==2) {
				$color = '#0f0';
			} else if ($items[$i]['avail']==0) {
				$color = '#ccc';
			} else {
				$color = makecolor2($items[$i]['startdate'],$items[$i]['enddate'],$now);
			}
			if (in_array($items[$i]['id'],$openblocks)) { $isopen=true;} else {$isopen=false;}
			if ($isopen || count($items[$i]['items'])==0) {
				$liclass = 'blockli';
				$qviewstyle = '';
			} else {
				$liclass = 'blockli nCollapse';
				$qviewstyle = 'style="display:none;"';
			}
			if (!isset($CFG['CPS']['miniicons']['folder'])) {
				$icon  = '<span class=icon style="background-color:'.$color.'">B</span>';
			} else {
				$icon = '<img alt="folder" src="'.$imasroot.'/img/'.$CFG['CPS']['miniicons']['folder'].'" class="mida icon" /> ';
			}
			echo '<li class="'.$liclass.'" id="'."$parent-$bnum".'" obn="'.$items[$i]['id'].'">'.$icon;
			if ($items[$i]['avail']==2 || ($items[$i]['avail']==1 && $items[$i]['startdate']<$now && $items[$i]['enddate']>$now)) {
				echo '<b><span id="B'.$parent.'-'.$bnum.'" onclick="editinplace(this)">'.$items[$i]['name']. "</span></b>";
				//echo '<b>'.$items[$i]['name'].'</b>';
			} else {
				echo '<i><b><span id="B'.$parent.'-'.$bnum.'" onclick="editinplace(this)">'.$items[$i]['name']. "</span></b></i>";
				//echo '<i><b>'.$items[$i]['name'].'</b></i>';
			}
			if ($showdates) {
				echo " $show";
			}
			if ($showlinks) {
				echo '<span class="links">';
				echo " <a href=\"addblock.php?cid=$cid&id=$parent-$bnum\">", _('Modify'), "</a> | <a href=\"deleteblock.php?cid=$cid&id=$parent-$bnum&remove=ask\">", _('Delete'), "</a>";
				echo " | <a href=\"copyoneitem.php?cid=$cid&copyid=$parent-$bnum\">", _('Copy'), "</a>";
				echo " | <a href=\"course.php?cid=$cid&togglenewflag=$parent-$bnum\">", _('NewFlag'), "</a>";
				echo '</span>';
			}
			if (count($items[$i]['items'])>0) {
				echo '<ul class=qview '.$qviewstyle.'>';
				quickview($items[$i]['items'],$parent.'-'.$bnum,$showdats,$showlinks);
				echo '</ul>';
			}
			echo '</li>';
		   } else if ($itemtypes[$items[$i]][0] == 'Calendar') {
		        if (!isset($CFG['CPS']['miniicons']['calendar'])) {
				$icon  = '<span class=icon style="background-color:#0f0;">C</span>';
			} else {
				$icon = '<img alt="calendar" src="'.$imasroot.'/img/'.$CFG['CPS']['miniicons']['calendar'].'" class="mida icon" /> ';
			}
			echo '<li id="'.$items[$i].'">'.$icon.'Calendar</li>';
			   
	   	   } else if ($itemtypes[$items[$i]][0] == 'Assessment') {
			   $typeid = $itemtypes[$items[$i]][1];
			   list($line['name'],$line['startdate'],$line['enddate'],$line['reviewdate'],$line['avail']) = $iteminfo['Assessment'][$typeid];
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = _('Always');
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['reviewdate']==2000000000) {
				   $reviewdate = _('Always');
			   } else {
				   $reviewdate = formatdate($line['reviewdate']);
			   }
			   if ($line['avail']==2) {
					$color = '#0f0';
			   } else if ($line['avail']==0) {
				   $color = '#ccc';
			   } else {
					$color = makecolor2($line['startdate'],$line['enddate'],$now);
			   }
			 if (!isset($CFG['CPS']['miniicons']['assess'])) {
				$icon  = '<span class=icon style="background-color:'.$color.'">?</span>';
			} else {
				$icon = '<img alt="assessment" src="'.$imasroot.'/img/'.$CFG['CPS']['miniicons']['assess'].'" class="mida icon" /> ';
			}
			echo '<li id="'.$items[$i].'">'.$icon;
			   if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
				   $show = sprintf(_('Available until %s'), $enddate);
				   echo '<b><span id="A'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
				   //echo '<b>'.$line['name'].'</b> ';
			   } else if ($line['avail']==1 && $line['startdate']<$now && $line['reviewdate']>$now) {
				   $show = sprintf(_('Review until %s'), $reviewdate);
				   //echo '<b>'.$line['name'].'</b> ';
				   echo '<b><span id="A'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
			   } else {
				   $show = sprintf(_('Available %1$s to %2$s'), $startdate, $enddate);
				   if ($line['reviewdate']>0 && $line['enddate']!=2000000000) {
					   $show .= sprintf(_(', review until %s'), $reviewdate);
				   }
				   //echo '<i><b>'.$line['name'].'</b></i> ';
				   echo '<i><b><span id="A'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
			   }
			   if ($showdates) {
				   echo $show;
			   }
			   if ($showlinks) {
				   echo '<span class="links">';
				    echo " <a href=\"addquestions.php?aid=$typeid&cid=$cid\">", _('Questions'), "</a> | <a href=\"addassessment.php?id=$typeid&cid=$cid\">", _('Settings'), "</a> | \n";
				   echo "<a href=\"deleteassessment.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
				   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
				   echo " | <a href=\"gb-itemanalysis.php?cid=$cid&asid=average&aid=$typeid\">", _('Grades'), "</a>";
				   echo '</span>';
			   }
			   echo "</li>";
			  
		   } else if ($itemtypes[$items[$i]][0] == 'InlineText') {
			   $typeid = $itemtypes[$items[$i]][1];
			   list($line['name'],$line['text'],$line['startdate'],$line['enddate'],$line['avail']) = $iteminfo['InlineText'][$typeid];
			   if ($line['name'] == '##hidden##') {
				   $line['name'] = strip_tags($line['text']);
			   }
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = _('Always');
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['avail']==2) {
					$color = '#0f0';
				} else if ($line['avail']==0) {
				   $color = '#ccc';
			   } else {
					$color = makecolor2($line['startdate'],$line['enddate'],$now);
				}
			   if (!isset($CFG['CPS']['miniicons']['inline'])) {
				$icon  = '<span class=icon style="background-color:'.$color.'">!</span>';
			   } else {
				$icon = '<img alt="text" src="'.$imasroot.'/img/'.$CFG['CPS']['miniicons']['inline'].'" class="mida icon" /> ';
			   }
			   echo '<li id="'.$items[$i].'">'.$icon;
			   if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
				   echo '<b><span id="I'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
				  // echo '<b>'.$line['name']. "</b>";
				   if ($showdates) {
					   printf(_(' showing until %s'), $enddate);
				   }
			   } else {
				   //echo '<i><b>'.$line['name']. "</b></i>";
				   echo '<i><b><span id="I'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
				   if ($showdates) {
					   printf(_(' showing %1$s until %2$s'), $startdate, $enddate);
				   }
			   }
			   if ($showlinks) {
				   echo '<span class="links">';
				   echo " <a href=\"addinlinetext.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
				  echo "<a href=\"deleteinlinetext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
				  echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
				  echo '</span>';
			   }
			   echo '</li>';
		   } else if ($itemtypes[$items[$i]][0] == 'LinkedText') {
			   $typeid = $itemtypes[$items[$i]][1];
			   list($line['name'],$line['startdate'],$line['enddate'],$line['avail']) = $iteminfo['LinkedText'][$typeid];
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = _('Always');
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['avail']==2) {
					$color = '#0f0';
				} else if ($line['avail']==0) {
				   $color = '#ccc';
			   } else {
					$color = makecolor2($line['startdate'],$line['enddate'],$now);
				}
			   if (!isset($CFG['CPS']['miniicons']['linked'])) {
				$icon  = '<span class=icon style="background-color:'.$color.'">!</span>';
			   } else {
				$icon = '<img alt="link" src="'.$imasroot.'/img/'.$CFG['CPS']['miniicons']['linked'].'" class="mida icon" /> ';
			   }
			   echo '<li id="'.$items[$i].'">'.$icon;
			   if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
				   //echo '<b>'.$line['name']. "</b>";
				   echo '<b><span id="L'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
				   if ($showdates) {
					   printf(_(' showing until %s'), $enddate);
				   }
			   } else {
				   //echo '<i><b>'.$line['name']. "</b></i>";
				   echo '<i><b><span id="L'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
				   if ($showdates) {
					   printf(_(' showing %1$s until %2$s'), $startdate, $enddate);
				   }
			   }
			   if ($showlinks) {
				   echo '<span class="links">';
				   echo " <a href=\"addlinkedtext.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
				  echo "<a href=\"deletelinkedtext.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
				  echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
				  echo '</span>';
			   }
			   echo '</li>';
		   } else if ($itemtypes[$items[$i]][0] == 'Forum') {
			   $typeid = $itemtypes[$items[$i]][1];
			   list($line['name'],$line['startdate'],$line['enddate'],$line['avail']) = $iteminfo['Forum'][$typeid];
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = _('Always');
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['avail']==2) {
					$color = '#0f0';
				} else if ($line['avail']==0) {
				   $color = '#ccc';
			   } else {
					$color = makecolor2($line['startdate'],$line['enddate'],$now);
				}
		           if (!isset($CFG['CPS']['miniicons']['forum'])) {
				$icon  = '<span class=icon style="background-color:'.$color.'">F</span>';
			   } else {
				$icon = '<img alt="forum" src="'.$imasroot.'/img/'.$CFG['CPS']['miniicons']['forum'].'" class="mida icon" /> ';
			   }
			   echo '<li id="'.$items[$i].'">'.$icon;
			  if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
				   //echo '<b>'.$line['name']. "</b>";
				   echo '<b><span id="F'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
				   if ($showdates) {
					   printf(_(' showing until %s'), $enddate);
				   }
			   } else {
				   echo '<i><b><span id="F'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
				   //echo '<i><b>'.$line['name']. "</b></i>";
				   if ($showdates) {
					   printf(_(' showing %1$s until %2$s'), $startdate, $enddate);
				   }
			   }
			   if ($showlinks) {
				   echo '<span class="links">';
				   echo " <a href=\"addforum.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
				  echo "<a href=\"deleteforum.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
				  echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
				  echo '</span>';
			   }
			   echo '</li>';
		   } else if ($itemtypes[$items[$i]][0] == 'Wiki') {
			   $typeid = $itemtypes[$items[$i]][1];
			   list($line['name'],$line['startdate'],$line['enddate'],$line['avail']) = $iteminfo['Wiki'][$typeid];
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = _('Always');
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['avail']==2) {
					$color = '#0f0';
				} else if ($line['avail']==0) {
				   $color = '#ccc';
			   } else {
					$color = makecolor2($line['startdate'],$line['enddate'],$now);
				}
		           if (!isset($CFG['CPS']['miniicons']['wiki'])) {
				$icon  = '<span class=icon style="background-color:'.$color.'">W</span>';
			   } else {
				$icon = '<img alt="wiki"  src="'.$imasroot.'/img/'.$CFG['CPS']['miniicons']['wiki'].'" class="mida icon" /> ';
			   }
			   echo '<li id="'.$items[$i].'">'.$icon;
			  if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
				   //echo '<b>'.$line['name']. "</b>";
				   echo '<b><span id="W'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
				   if ($showdates) {
					   printf(_(' showing until %s'), $enddate);
				   }
			   } else {
				   echo '<i><b><span id="W'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
				   //echo '<i><b>'.$line['name']. "</b></i>";
				   if ($showdates) {
					   printf(_(' showing %1$s until %2$s'), $startdate, $enddate);
				   }
			   }
			   if ($showlinks) {
				   echo '<span class="links">';
				   echo " <a href=\"addwiki.php?id=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
				  echo "<a href=\"deletewiki.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
				  echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
				  echo '</span>';
			   }
			   echo '</li>';
		   } else if ($itemtypes[$items[$i]][0] == 'Drill') {
			   $typeid = $itemtypes[$items[$i]][1];
			   list($line['name'],$line['startdate'],$line['enddate'],$line['avail']) = $iteminfo['Drill'][$typeid];
			   if ($line['startdate']==0) {
				   $startdate = _('Always');
			   } else {
				   $startdate = formatdate($line['startdate']);
			   }
			   if ($line['enddate']==2000000000) {
				   $enddate = _('Always');
			   } else {
				   $enddate =formatdate($line['enddate']);
			   }
			   if ($line['avail']==2) {
					$color = '#0f0';
				} else if ($line['avail']==0) {
				   $color = '#ccc';
			   } else {
					$color = makecolor2($line['startdate'],$line['enddate'],$now);
				}
		           if (!isset($CFG['CPS']['miniicons']['drill'])) {
				$icon  = '<span class=icon style="background-color:'.$color.'">D</span>';
			   } else {
				$icon = '<img alt="wiki"  src="'.$imasroot.'/img/'.$CFG['CPS']['miniicons']['drill'].'" class="mida icon" /> ';
			   }
			   echo '<li id="'.$items[$i].'">'.$icon;
			  if ($line['avail']==1 && $line['startdate']<$now && $line['enddate']>$now) {
				   //echo '<b>'.$line['name']. "</b>";
				   echo '<b><span id="D'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b>";
				   if ($showdates) {
					   printf(_(' showing until %s'), $enddate);
				   }
			   } else {
				   echo '<i><b><span id="D'.$typeid.'" onclick="editinplace(this)">'.$line['name']. "</span></b></i>";
				   //echo '<i><b>'.$line['name']. "</b></i>";
				   if ($showdates) {
					   printf(_(' showing %1$s until %2$s'), $startdate, $enddate);
				   }
			   }
			   if ($showlinks) {
				   echo ' <span class="links">';
				   echo "<a href=\"adddrillassess.php?daid=$typeid&block=$parent&cid=$cid\">", _('Modify'), "</a> | \n";
				   echo "<a href=\"deletedrillassess.php?id=$typeid&block=$parent&cid=$cid&remove=ask\">", _('Delete'), "</a>\n";
				   echo " | <a href=\"copyoneitem.php?cid=$cid&copyid={$items[$i]}\">", _('Copy'), "</a>";
				   echo " | <a href=\"gb-viewdrill.php?cid=$cid&daid=$typeid\">", _('Scores'), "</a>";
				  echo '</span>';
			   }
			   echo '</li>';
		   }
	   
	   }
  }
   
?>
