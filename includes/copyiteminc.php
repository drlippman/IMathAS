<?php
//IMathAS:  Copy Items utility functions
//(c) 2008 David Lippman
$reqscoretrack = array();
$assessnewid = array();
function copyitem($itemid,$gbcats) {
	global $cid, $reqscoretrack, $assessnewid, $copystickyposts,$userid;
	if (!isset($copystickyposts)) { $copystickyposts = false;}
	if ($gbcats===false) {
		$gbcats = array();
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
		$query = "SELECT title,summary,text,startdate,enddate,avail,oncal,caltag,target FROM imas_linkedtext WHERE id='$typeid'";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$row = mysql_fetch_row($result);
		$row[0] .= stripslashes($_POST['append']);
		$row = "'".implode("','",addslashes_deep($row))."'";
		$query = "INSERT INTO imas_linkedtext (courseid,title,summary,text,startdate,enddate,avail,oncal,caltag,target) ";
		$query .= "VALUES ('$cid',$row)";
		mysql_query($query) or die("Query failed :$query " . mysql_error());
		$newtypeid = mysql_insert_id();
	} else if ($itemtype == "Forum") {
		//$query = "INSERT INTO imas_forums (courseid,name,summary,startdate,enddate) ";
		//$query .= "SELECT '$cid',name,summary,startdate,enddate FROM imas_forums WHERE id='$typeid'";
		//mysql_query($query) or die("Query failed : $query" . mysql_error());
		$query = "SELECT name,description,startdate,enddate,settings,defdisplay,replyby,postby,avail,points,cntingb,gbcategory FROM imas_forums WHERE id='$typeid'";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$row = mysql_fetch_row($result);
		if (isset($gbcats[$row[11]])) {
			$row[11] = $gbcats[$row[11]];
		} else if ($_POST['ctc']!=$cid) {
			$row[11] = 0;
		}
		$row[0] .= stripslashes($_POST['append']);
		$row = "'".implode("','",addslashes_deep($row))."'";
		$query = "INSERT INTO imas_forums (courseid,name,description,startdate,enddate,settings,defdisplay,replyby,postby,avail,points,cntingb,gbcategory) ";
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
		$query = "SELECT name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,allowlate,exceptionpenalty,noprint,avail,groupmax,endmsg,deffeedbacktext,eqnhelper,caltag,calrtag,reqscore,reqscoreaid FROM imas_assessments WHERE id='$typeid'";

		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$row = mysql_fetch_row($result);
		if (isset($gbcats[$row[14]])) {
			$row[14] = $gbcats[$row[14]];
		} else if ($_POST['ctc']!=$cid) {
			$row[14] = 0;
		}
		$reqscoreaid = array_pop($row);
		$row[0] .= stripslashes($_POST['append']);
		$row = "'".implode("','",addslashes_deep($row))."'";
		$query = "INSERT INTO imas_assessments (courseid,name,summary,intro,startdate,enddate,reviewdate,timelimit,minscore,displaymethod,defpoints,defattempts,deffeedback,defpenalty,shuffle,gbcategory,password,cntingb,showcat,showhints,showtips,allowlate,exceptionpenalty,noprint,avail,groupmax,endmsg,deffeedbacktext,eqnhelper,caltag,calrtag,reqscore) ";

		$query .= "VALUES ('$cid',$row)";
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
					if (strpos($sub[0],'|')!==false) { //true except for bwards compat 
						$newsub[] = array_shift($sub);
					}
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
	} else if ($itemtype == "Calendar") {
		$newtypeid = 0;	
	}
	$query = "INSERT INTO imas_items (courseid,itemtype,typeid) ";
	$query .= "VALUES ('$cid','$itemtype',$newtypeid)";
	mysql_query($query) or die("Query failed :$query " . mysql_error());
	return (mysql_insert_id());	
}
	
function copysub($items,$parent,&$addtoarr,$gbcats) {
	global $checked,$blockcnt,$reqscoretrack,$assessnewid;
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			if (array_search($parent.'-'.($k+1),$checked)!==FALSE) { //copy block
				$newblock = array();
				$newblock['name'] = $item['name'].stripslashes($_POST['append']);
				$newblock['id'] = $blockcnt;
				$blockcnt++;
				$newblock['startdate'] = $item['startdate'];
				$newblock['enddate'] = $item['enddate'];
				$newblock['avail'] = $item['avail'];
				$newblock['SH'] = $item['SH'];
				$newblock['colors'] = $item['colors'];
				$newblock['public'] = $item['public'];
				$newblock['fixedheight'] = $item['fixedheight'];
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
	//update reqscoreaids if possible.  
	if (count($reqscoretrack)>0) {
		foreach ($reqscoretrack as $newid=>$oldreqaid) {
			//is old reqscoreaid in copied list?
			if (isset($assessnewid[$oldreqaid])) {
				$query = "UPDATE imas_assessments SET reqscoreaid='{$assessnewid[$oldreqaid]}' WHERE id='$newid'";	
				mysql_query($query) or die("Query failed : $query" . mysql_error());
			} else {
				$query = "UPDATE imas_assessments SET reqscore=0 WHERE id='$newid'";
				mysql_query($query) or die("Query failed : $query" . mysql_error());
			}
		}
	}
}	

function copyallsub($items,$parent,&$addtoarr,$gbcats) {
	global $blockcnt;
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
			$newblock['avail'] = $item['avail'];
			$newblock['SH'] = $item['SH'];
			$newblock['colors'] = $item['colors'];
			$newblock['public'] = $item['public'];
			$newblock['fixedheight'] = $item['fixedheight'];
			$newblock['items'] = array();
			if (count($item['items'])>0) {
				copyallsub($item['items'],$parent.'-'.($k+1),$newblock['items'],$gbcats);
			}
			$addtoarr[] = $newblock;
		} else {
			$addtoarr[] = copyitem($item,$gbcats);
		}
	}
}


function getiteminfo($itemid) {
	$query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
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
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$name = mysql_result($result,0,0);
	$summary = mysql_result($result,0,1);
	return array($itemtype,$name,$summary);
}

function getsubinfo($items,$parent,$pre) {
	global $ids,$types,$names,$sums,$parents;
	
	foreach($items as $k=>$item) {
		if (is_array($item)) {
			$ids[] = $parent.'-'.($k+1);
			$types[] = $pre."Block";
			$names[] = stripslashes($item['name']);
			$parents[] = $parent;
			$sums[] = '';
			if (count($item['items'])>0) {
				getsubinfo($item['items'],$parent.'-'.($k+1),$pre.'-&nbsp;');
			}
		} else {
			if ($item==null || $item=='') {
				continue;
			}
			$ids[] = $item;
			$parents[] = $parent;
			$arr = getiteminfo($item);
			$types[] = $pre.$arr[0];
			$names[] = $arr[1];
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

?>
