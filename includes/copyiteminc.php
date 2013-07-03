<?php

//boost operation time
@set_time_limit(0);
ini_set("max_input_time", "900");
ini_set("max_execution_time", "900");
ini_set("memory_limit", "104857600");

//IMathAS:  Copy Items utility functions
//(c) 2008 David Lippman
$reqscoretrack = array();
$qrubrictrack = array();
$assessnewid = array();
$exttooltrack = array();
function copyitem($itemid,$gbcats,$sethidden=false) {
	global $cid, $reqscoretrack, $assessnewid, $qrubrictrack, $copystickyposts,$userid, $exttooltrack, $outcomes;
	if (!isset($copystickyposts)) { $copystickyposts = false;}
	if ($gbcats===false) {
		$gbcats = array();
	}
	if (!isset($outcomes)) {
		$outcomes = array();
	}
	if (strlen($_POST['append'])>0 && $_POST['append']{0}!=' ') {
		$_POST['append'] = ' '.$_POST['append'];
	}
	$now = time();
	$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	list($itemtype,$typeid) = mysql_fetch_row($result);
	if ($itemtype == "InlineText") {
		//$query = "INSERT INTO imas_inlinetext (courseid,title,text,startdate,enddate) ";
		//$query .= "SELECT '$cid',title,text,startdate,enddate FROM imas_inlinetext WHERE id='$typeid'";
		//mysql_query($query) or die("Query failed :$query " . mysql_error());
		$query = "SELECT title,text,startdate,enddate,avail,oncal,caltag,fileorder FROM imas_inlinetext WHERE id='$typeid'";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$row = mysql_fetch_row($result);
		if ($sethidden) {$row[4] = 0;}
		$row[0] .= stripslashes($_POST['append']);
		$fileorder = $row[7];
		array_pop($row);
		$row = "'".implode("','",addslashes_deep($row))."'";
		$query = "INSERT INTO imas_inlinetext (courseid,title,text,startdate,enddate,avail,oncal,caltag) ";
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
		$query = "SELECT title,summary,text,startdate,enddate,avail,oncal,caltag,target,outcomes FROM imas_linkedtext WHERE id='$typeid'";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$row = mysql_fetch_row($result);
		$istool = (substr($row[2],0,8)=='exttool:');
		if ($istool) {
			$tool = explode('~~',substr($row[2],8));
		}
		if ($sethidden) {$row[5] = 0;}
		$row[0] .= stripslashes($_POST['append']);
		if ($row[9]!='') {
			$curoutcomes = explode(',',$row[9]);
			$newoutcomes = array();
			foreach ($curoutcomes as $o) {
				if (isset($outcomes[$o])) {
					$newoutcomes[] = $outcomes[$o];
				}
			}
			$row[9] = implode(',',$newoutcomes);
		}
		$row = "'".implode("','",addslashes_deep($row))."'";
		$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate,avail,oncal,caltag,target,outcomes) ";
		$query .= "VALUES ('$cid',$row)";
		mysql_query($query) or die("Query failed :$query " . mysql_error());
		$newtypeid = mysql_insert_id();
		if ($istool) {
			$exttooltrack[$newtypeid] = intval($tool[0]);
		}
	} else if ($itemtype == "Forum") {
		//$query = "INSERT INTO imas_forums (courseid,name,summary,startdate,enddate) ";
		//$query .= "SELECT '$cid',name,summary,startdate,enddate FROM imas_forums WHERE id='$typeid'";
		//mysql_query($query) or die("Query failed : $query" . mysql_error());
		$query = "SELECT name,description,startdate,enddate,settings,defdisplay,replyby,postby,avail,points,cntingb,gbcategory,forumtype,taglist,outcomes FROM imas_forums WHERE id='$typeid'";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$row = mysql_fetch_row($result);
		if ($sethidden) {$row[8] = 0;}
		if (isset($gbcats[$row[11]])) {
			$row[11] = $gbcats[$row[11]];
		} else if ($_POST['ctc']!=$cid) {
			$row[11] = 0;
		}
		$row[0] .= stripslashes($_POST['append']);
		if ($row[14]!='') {
			$curoutcomes = explode(',',$row[14]);
			$newoutcomes = array();
			foreach ($curoutcomes as $o) {
				if (isset($outcomes[$o])) {
					$newoutcomes[] = $outcomes[$o];
				}
			}
			$row[14] = implode(',',$newoutcomes);
		}
		$row = "'".implode("','",addslashes_deep($row))."'";
		$query = "INSERT INTO imas_forums (courseid,name,description,startdate,enddate,settings,defdisplay,replyby,postby,avail,points,cntingb,gbcategory,forumtype,taglist) ";
		$query .= "VALUES ('$cid',$row)";
		mysql_query($query) or die("Query failed :$query " . mysql_error());
		$newtypeid = mysql_insert_id();
		if ($copystickyposts) {
			//copy instructor sticky posts
			$query = "SELECT subject,message,posttype,isanon,replyby FROM imas_forum_posts WHERE forumid='$typeid' AND posttype>0";
			$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$row = addslashes_deep($row);
				$query = "INSERT INTO imas_forum_posts (forumid,userid,parent,postdate,subject,message,posttype,isanon,replyby) VALUES ";
				if (is_null($row[4]) || trim($row[4])=='') {
					$query .= "('$newtypeid','$userid',0,$now,'{$row[0]}','{$row[1]}','{$row[2]}','{$row[3]}',NULL)";
				} else {
					$query .= "('$newtypeid','$userid',0,$now,'{$row[0]}','{$row[1]}','{$row[2]}','{$row[3]}','{$row[4]}')";
				}
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				$threadid = mysql_insert_id();
				$query = "UPDATE imas_forum_posts SET threadid='$threadid' WHERE id='$threadid'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				$query = "INSERT INTO imas_forum_threads (id,forumid,lastposttime,lastpostuser) VALUES ('$threadid','$newtypeid',$now,'$userid')";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ('$userid','$threadid',$now)";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
		}
	} else if ($itemtype == "Wiki") {
		$query = "SELECT name,description,startdate,enddate,editbydate,avail,settings,groupsetid FROM imas_wikis WHERE id='$typeid'";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$row = mysql_fetch_row($result);
		if ($sethidden) {$row[5] = 0;}
		$row[0] .= stripslashes($_POST['append']);
		$row = "'".implode("','",addslashes_deep($row))."'";
		$query = "INSERT INTO imas_wikis (courseid,name,description,startdate,enddate,editbydate,avail,settings,groupsetid) ";
		$query .= "VALUES ('$cid',$row)";
		mysql_query($query) or die("Query failed :$query " . mysql_error());
		$newtypeid = mysql_insert_id();
	} else if ($itemtype == "Assessment") {
		//$query = "INSERT INTO imas_assessments (courseid,name,summary,intro,startdate,enddate,timelimit,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle) ";
		//$query .= "SELECT '$cid',name,summary,intro,startdate,enddate,timelimit,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle FROM imas_assessments WHERE id='$typeid'";
		//mysql_query($query) or die("Query failed : $query" . mysql_error());
		$query = "SELECT name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,allowlate,exceptionpenalty,noprint,avail,groupmax,endmsg,deffeedbacktext,eqnhelper,caltag,calrtag,msgtoinstr,istutorial,viddata,reqscore,reqscoreaid,ancestors,defoutcome FROM imas_assessments WHERE id='$typeid'";

		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$row = mysql_fetch_assoc($result);
		if ($sethidden) {$row['avail'] = 0;}
		if (isset($gbcats[$row['gbcategory']])) {
			$row['gbcategory'] = $gbcats[$row['gbcategory']];
		} else {
			$row['gbcategory'] = 0;
		}
		if (isset($outcomes[$row['defoutcome']])) {
			$row['defoutcome'] = $outcomes[$row['defoutcome']];
		} else {
			$row['defoutcome'] = 0;
		}
		if ($row['ancestors']=='') {
			$row['ancestors'] = $typeid;
		} else {
			$row['ancestors'] = $typeid.','.$row['ancestors'];
		}
		$reqscoreaid = $row['reqscoreaid'];
		unset($row['reqscoreaid']);
		$row['name'] .= stripslashes($_POST['append']);
		
		$fields = implode(",",array_keys($row));
		$vals = "'".implode("','",addslashes_deep(array_values($row)))."'";
		
		$query = "INSERT INTO imas_assessments (courseid,$fields) VALUES ('$cid',$vals)";
		/*$row = mysql_fetch_row($result);
		if ($sethidden) {$row[23] = 0;}
		if (isset($gbcats[$row[14]])) {
			$row[14] = $gbcats[$row[14]];
		} else if ($_POST['ctc']!=$cid) {
			$row[14] = 0;
		}
		
		$reqscoreaid = array_pop($row);
		$row[0] .= stripslashes($_POST['append']);
		$row = "'".implode("','",addslashes_deep($row))."'";
		$query = "INSERT INTO imas_assessments (courseid,name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,allowlate,exceptionpenalty,noprint,avail,groupmax,endmsg,deffeedbacktext,eqnhelper,caltag,calrtag,msgtoinstr,istutorial,viddata,reqscore) ";

		$query .= "VALUES ('$cid',$row)";
		*/
		mysql_query($query) or die("Query failed : $query" . mysql_error());
		$newtypeid = mysql_insert_id();
		if ($reqscoreaid>0) {
			$reqscoretrack[$newtypeid] = $reqscoreaid;
		}
		$assessnewid[$typeid] = $newtypeid;
		$query = "SELECT itemorder FROM imas_assessments WHERE id='$typeid'";
		$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		if (trim(mysql_result($result,0,0))!='') {
			$aitems = explode(',',mysql_result($result,0,0));
			$newaitems = array();
			foreach ($aitems as $k=>$aitem) {
				if (strpos($aitem,'~')===FALSE) {
					///$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
					///$query .= "SELECT '$newtypeid',questionsetid,points,attempts,penalty,category FROM imas_questions WHERE id='$aitem'";
					//mysql_query($query) or die("Query failed :$query " . mysql_error());
					$query = "SELECT questionsetid,points,attempts,penalty,category,regen,showans,showhints,rubric FROM imas_questions WHERE id='$aitem'";
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					$row = mysql_fetch_row($result);
					if (is_numeric($row[4])) {
						if (isset($outcomes[$row[4]])) {
							$row[4] = $outcomes[$row[4]];
						} else {
							$row[4] = 0;
						}
					}
					$rubric = array_pop($row);
					$row = "'".implode("','",addslashes_deep($row))."'";
					$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category,regen,showans,showhints) ";
					$query .= "VALUES ('$newtypeid',$row)";
					mysql_query($query) or die("Query failed : $query" . mysql_error());
					$newid = mysql_insert_id();
					if ($rubric != 0) {
						$qrubrictrack[$newid] = $rubric;
					}
					$newaitems[] = $newid;
				} else {
					$sub = explode('~',$aitem);
					$newsub = array();
					if (strpos($sub[0],'|')!==false) { //true except for bwards compat 
						$newsub[] = array_shift($sub);
					}
					foreach ($sub as $subi) {
						//$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category) ";
						//$query .= "SELECT '$newtypeid',questionsetid,points,attempts,penalty,category FROM imas_questions WHERE id='$subi'";
						//mysql_query($query) or die("Query failed : $query" . mysql_error());
						$query = "SELECT questionsetid,points,attempts,penalty,category,regen,showans,showhints,rubric FROM imas_questions WHERE id='$subi'";
						$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
						$row = mysql_fetch_row($result);
						if (is_numeric($row[4])) {
							if (isset($outcomes[$row[4]])) {
								$row[4] = $outcomes[$row[4]];
							} else {
								$row[4] = 0;
							}
						}
						$rubric = array_pop($row);
						$row = "'".implode("','",addslashes_deep($row))."'";
						$query = "INSERT INTO imas_questions (assessmentid,questionsetid,points,attempts,penalty,category,regen,showans,showhints) ";
						$query .= "VALUES ('$newtypeid',$row)";
						mysql_query($query) or die("Query failed : $query" . mysql_error());
						$newid = mysql_insert_id();
						if ($rubric != 0) {
							$qrubrictrack[$newid] = $rubric;
						}
						$newsub[] = $newid;
					}
					$newaitems[] = implode('~',$newsub);
				}
			}
			$newitemorder = implode(',',$newaitems);
			$query = "UPDATE imas_assessments SET itemorder='$newitemorder' WHERE id='$newtypeid'";
			mysql_query($query) or die("Query failed : $query" . mysql_error());
		}
	} else if ($itemtype == "Calendar") {
		$newtypeid = 0;	
	} 
	
	$query = "INSERT INTO imas_items (courseid,itemtype,typeid) ";
	$query .= "VALUES ('$cid','$itemtype',$newtypeid)";
	mysql_query($query) or die("Query failed :$query " . mysql_error());
	return (mysql_insert_id());	
}
	
function copysub($items,$parent,&$addtoarr,$gbcats,$sethidden=false) {
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
				$newblock['avail'] = $sethidden?0:$item['avail'];
				$newblock['SH'] = $item['SH'];
				$newblock['colors'] = $item['colors'];
				$newblock['public'] = $item['public'];
				$newblock['fixedheight'] = $item['fixedheight'];
				$newblock['grouplimit'] = $item['grouplimit'];
				$newblock['items'] = array();
				if (count($item['items'])>0) {
					copysub($item['items'],$parent.'-'.($k+1),$newblock['items'],$gbcats,$sethidden);
				}
				$addtoarr[] = $newblock;
			} else {
				if (count($item['items'])>0) {
					copysub($item['items'],$parent.'-'.($k+1),$addtoarr,$gbcats,$sethidden);
				}
			}
		} else {
			if (array_search($item,$checked)!==FALSE) {
				$addtoarr[] = copyitem($item,$gbcats,$sethidden);
			}
		}
	}
	
}	

function doaftercopy($sourcecid) {
	global $cid,$reqscoretrack,$assessnewid;
	if (intval($cid)==intval($sourcecid)) {
		$samecourse = true;
	} else {
		$samecourse = false;
	}
	//update reqscoreaids if possible.  
	if (count($reqscoretrack)>0) {
		foreach ($reqscoretrack as $newid=>$oldreqaid) {
			//is old reqscoreaid in copied list?
			if (isset($assessnewid[$oldreqaid])) {
				$query = "UPDATE imas_assessments SET reqscoreaid='{$assessnewid[$oldreqaid]}' WHERE id='$newid'";	
				mysql_query($query) or die("Query failed : $query" . mysql_error());
			} else if (!$samecourse) {
				$query = "UPDATE imas_assessments SET reqscore=0 WHERE id='$newid'";
				mysql_query($query) or die("Query failed : $query" . mysql_error());
			}
		}
	}
	if (!$samecourse) {
		handleextoolcopy($sourcecid);
	}
}

function copyallsub($items,$parent,&$addtoarr,$gbcats,$sethidden=false) {
	global $blockcnt,$reqscoretrack,$assessnewid;;
	if (strlen($_POST['append'])>0 && $_POST['append']{0}!=' ') {
		$_POST['append'] = ' '.$_POST['append'];
	}
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			$newblock = array();
			$newblock['name'] = $item['name'].stripslashes($_POST['append']);
			$newblock['id'] = $blockcnt;
			$blockcnt++;
			$newblock['startdate'] = $item['startdate'];
			$newblock['enddate'] = $item['enddate'];
			$newblock['avail'] = $sethidden?0:$item['avail'];
			$newblock['SH'] = $item['SH'];
			$newblock['colors'] = $item['colors'];
			$newblock['public'] = $item['public'];
			$newblock['fixedheight'] = $item['fixedheight'];
			$newblock['grouplimit'] = $item['grouplimit'];
			$newblock['items'] = array();
			if (count($item['items'])>0) {
				copyallsub($item['items'],$parent.'-'.($k+1),$newblock['items'],$gbcats,$sethidden);
			}
			$addtoarr[] = $newblock;
		} else {
			if ($item != null && $item != 0) {
				$addtoarr[] = copyitem($item,$gbcats,$sethidden);
			}
		}
	}
	
}


function getiteminfo($itemid) {
	$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)==0) {
		echo "Uh oh, item #$itemid doesn't appear to exist";
	}
	$itemtype = mysql_result($result,0,0);
	$typeid = mysql_result($result,0,1);
	if ($itemtype==='Calendar') {
		return array($itemtype,'Calendar','');
	}
	switch($itemtype) {
		case ($itemtype==="InlineText"):
			$query = "SELECT title,text FROM imas_inlinetext WHERE id=$typeid";
			break;
		case ($itemtype==="LinkedText"):
			$query = "SELECT title,summary FROM imas_linkedtext WHERE id=$typeid";
			break;
		case ($itemtype==="Forum"):
			$query = "SELECT name,description FROM imas_forums WHERE id=$typeid";
			break;
		case ($itemtype==="Assessment"):
			$query = "SELECT name,summary FROM imas_assessments WHERE id=$typeid";
			break;
		case ($itemtype==="Wiki"):
			$query = "SELECT name,description FROM imas_wikis WHERE id=$typeid";
			break;
	}
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$name = mysql_result($result,0,0);
	$summary = mysql_result($result,0,1);
	return array($itemtype,$name,$summary,$typeid);
}

function getsubinfo($items,$parent,$pre,$itemtypelimit=false) {
	global $ids,$types,$names,$sums,$parents,$gitypeids;
	if (!isset($gitypeids)) {
		$gitypeids = array();
	}
	foreach($items as $k=>$item) {
		if (is_array($item)) {
			$ids[] = $parent.'-'.($k+1);
			$types[] = $pre."Block";
			$names[] = stripslashes($item['name']);
			$parents[] = $parent;
			$gitypeids[] = '';
			$sums[] = '';
			if (count($item['items'])>0) {
				getsubinfo($item['items'],$parent.'-'.($k+1),$pre.'-&nbsp;',$itemtypelimit);
			}
		} else {
			if ($item==null || $item=='') {
				continue;
			}
			$arr = getiteminfo($item);
			if ($itemtypelimit!==false && $arr[0]!=$itemtypelimit) {
				continue;
			}
			$ids[] = $item;
			$parents[] = $parent;
			$types[] = $pre.$arr[0];
			$names[] = $arr[1];
			$gitypeids[] = $arr[3];
			$arr[2] = strip_tags($arr[2]);
			if (strlen($arr[2])>100) {
				$arr[2] = substr($arr[2],0,97).'...';
			}
			$sums[] = $arr[2];
		}
	}
}	

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

function copyrubrics($offlinerubrics=array()) {
	global $userid,$groupid,$qrubrictrack;
	if (count($qrubrictrack)==0 && count($offlinerubrics)==0) { return;}
	$list = implode(',',array_merge($qrubrictrack,$offlinerubrics));
	
	//handle rubrics which I already have access to
	$query = "SELECT id FROM imas_rubrics WHERE id IN ($list) AND (ownerid='$userid' OR groupid='$groupid')";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	while ($row = mysql_fetch_row($result)) { 
		$qfound = array_keys($qrubrictrack,$row[0]);
		if (count($qfound)>0) {
			foreach ($qfound as $qid) {
				$query = "UPDATE imas_questions SET rubric={$row[0]} WHERE id=$qid";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		$ofound = array_keys($offlinerubrics,$row[0]);
		if (count($ofound)>0) {
			foreach ($ofound as $oid) {
				$query = "UPDATE imas_gbitems SET rubric={$row[0]} WHERE id=$oid";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
	}
	
	//handle rubrics which I don't already have access to - need to copy them
	$query = "SELECT id FROM imas_rubrics WHERE id IN ($list) AND NOT (ownerid='$userid' OR groupid='$groupid')";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		//echo "handing {$row[0]} which I don't have access to<br/>";
		$query = "SELECT name,rubrictype,rubric FROM imas_rubrics WHERE id={$row[0]}";
		$r = mysql_query($query) or die("Query failed : " . mysql_error());
		$rubrow = addslashes_deep(mysql_fetch_row($r));
		$query = "SELECT id FROM imas_rubrics WHERE rubric='{$rubrow[2]}' AND (ownerid=$userid OR groupid=$groupid)";
		$rr = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($rr)>0) {
			$newid = mysql_result($rr,0,0);
			//echo "found existing of mine, $newid<br/>";
		} else {
			$rub = "'".implode("','",$rubrow)."'";
			$query = "INSERT INTO imas_rubrics (ownerid,groupid,name,rubrictype,rubric) VALUES ";
			$query .= "($userid,-1,$rub)";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$newid = mysql_insert_id();
			//echo "created $newid<br/>";
		}
		
		$qfound = array_keys($qrubrictrack,$row[0]);
		if (count($qfound)>0) {
			foreach ($qfound as $qid) {
				$query = "UPDATE imas_questions SET rubric=$newid WHERE id=$qid";
				//echo "updating imas_questions on qid $qid<br/>";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		$ofound = array_keys($offlinerubrics,$row[0]);
		if (count($ofound)>0) {
			foreach ($ofound as $oid) {
				$query = "UPDATE imas_gbitems SET rubric=$newid WHERE id=$oid";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
	}	
}

function handleextoolcopy($sourcecid) {
	//assumes this is a copy into a different course
	global $cid,$userid,$groupid,$exttooltrack;
	if (count($exttooltrack)==0) {return;}
	//$exttooltrack is linked text id => tool id	
	$toolmap = array();
	$query = "SELECT id FROM imas_teachers WHERE courseid='$sourcecid' AND userid='$userid'";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	if (mysql_num_rows($result)>0) {
		$oktocopycoursetools = true;
	}
	$toolidlist = implode(',',$exttooltrack);
	$query = "SELECT id,courseid,groupid,name,url,ltikey,secret,custom,privacy FROM imas_external_tools ";
	$query .= "WHERE id IN ($toolidlist)";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$doremap = false;
		if (!isset($toolmap[$row[0]])) {
			//try url matching of existing tools in the destination course
			$query = "SELECT id FROM imas_external_tools WHERE url='".addslashes($row[4])."' AND courseid='$cid'";
			$res = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($res)>0) {
				$toolmap[$row[0]] = mysql_result($res,0,0);
			}
		}
		if (isset($toolmap[$row[0]])) {
			//already have remapped this tool - need to update linkedtext item
			$doremap = true;
		} else if ($row[1]>0 && $oktocopycoursetools) {
			//do copy
			$rowsub = array_slice($row,3);
			$rowsub = addslashes_deep($rowsub);
			$rowlist = implode("','",$rowsub);
			$query = "INSERT INTO imas_external_tools (courseid,groupid,name,url,ltikey,secret,custom,privacy) ";
			$query .= "VALUES ('$cid','$groupid','$rowlist')";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$toolmap[$row[0]] = mysql_insert_id();
			$doremap = true;
		} else if ($row[1]==0 && ($row[2]==0 || $row[2]==$groupid)) {
			//no need to copy anything - tool will just work
		} else {
			//not OK to copy; must disable tool in linked text item	
			$toupdate = implode(",",array_keys($exttooltrack, $row[0]));
			$query = "UPDATE imas_linkedtext SET text='<p>Unable to copy tool</p>' WHERE id IN ($toupdate)";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($doremap) {
			//update the linkedtext item with the new tool id
			$toupdate = implode(",",array_keys($exttooltrack, $row[0]));
			$query = "SELECT id,text FROM imas_linkedtext WHERE id IN ($toupdate)";
			$res = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($r = mysql_fetch_row($res)) {
				$text = str_replace('exttool:'.$row[0].'~~','exttool:'.$toolmap[$row[0]].'~~',$r[1]);
				$query = "UPDATE imas_linkedtext SET text='".addslashes($text)."' WHERE id={$r[0]}";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
	}
}

?>
