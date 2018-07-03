<?php
//IMathAS:  Drill Assess creator (rough version)
//(c) 2011 David Lippman

require("../init.php");
require("../includes/htmlutil.php");
require("../includes/parsedatetime.php");


if (!isset($teacherid)) {
	echo 'You are not authorized to view this page';
	exit;
}

$pagetitle = "Add/Modify Drill Assessment";
$cid = Sanitize::courseId($_GET['cid']);
$daid = Sanitize::onlyInt($_GET['daid']);
if (isset($_GET['tb'])) {
	$totb = $_GET['tb'];
} else {
	$totb = 'b';
}
$block = $_GET['block'];

//DB $query = "SELECT * FROM imas_drillassess WHERE id='$daid' AND courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$stm = $DBH->prepare("SELECT * FROM imas_drillassess WHERE id=:id AND courseid=:courseid");
$stm->execute(array(':id'=>$daid, ':courseid'=>$cid));
if ($stm->rowCount()==0) {
	//new to invalid
	$itemdescr = array();
	$itemids = array();
	$scoretype = 't';
	$showtype = '4';
	$n = 30;
	$showtostu = 7;
	$daid = 0;
	$drillname = "Enter title here";
	$drillsummary = "<p>Enter summary here (displays on course page)</p>";
	$startdate = time();
	$enddate = time() + 7*24*60*60;
	$avail = 1;
	$caltag = 'D';
} else {
	//DB $dadata = mysql_fetch_array($result, MYSQL_ASSOC);
	$dadata = $stm->fetch(PDO::FETCH_ASSOC);
	$n = $dadata['n'];
	$showtype = $dadata['showtype'];
	$scoretype = $dadata['scoretype'];
	$showtostu = $dadata['showtostu'];
	$startdate= $dadata['startdate'];
	$enddate= $dadata['enddate'];
	$avail= $dadata['avail'];
	$drillname= $dadata['name'];
	$drillsummary= $dadata['summary'];
	$caltag = $dadata['caltag'];

	if ($dadata['itemids']=='') {
		$itemids = array();
	} else {
		$itemids = explode(',',$dadata['itemids']);
	}
	if ($dadata['itemdescr']=='') {
		$itemdescr = array();
	} else {
		$itemdescr = explode(',',$dadata['itemdescr']);
	}
}

if (isset($_GET['clearatt'])) {
	//DB $query = "DELETE FROM imas_drillassess_sessions WHERE drillassessid=$daid";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("DELETE FROM imas_drillassess_sessions WHERE drillassessid=:drillassessid");
	$stm->execute(array(':drillassessid'=>$daid));
	header(sprintf('Location: %s/course/adddrillassess.php?cid=%s&daid=%d&r=%s', $GLOBALS['basesiteurl'], $cid, $daid, Sanitize::randomQueryStringParam()));
	exit;
}
if (isset($_GET['record'])) {
	if ($_POST['avail']==1) {
		if ($_POST['sdatetype']=='0') {
			$startdate = 0;
		} else {
			$startdate = parsedatetime($_POST['sdate'], $_POST['stime']);
		}
		if ($_POST['edatetype']=='2000000000') {
			$enddate = 2000000000;
		} else {
			$enddate = parsedatetime($_POST['edate'], $_POST['etime']);
		}
	} else {
		$startdate = 0;
		$enddate =  2000000000;
	}
	//DB $_POST['title'] = addslashes(htmlentities(stripslashes($_POST['title'])));
	$_POST['title'] = htmlentities($_POST['title']);

	require_once("../includes/htmLawed.php");
	if ($_POST['summary']=='<p>Enter summary here (displays on course page)</p>') {
		$_POST['summary'] = '';
	} else {
		//DB $_POST['summary'] = addslashes(myhtmLawed(stripslashes($_POST['summary'])));
		$_POST['summary'] = myhtmLawed($_POST['summary']);
	}

	if (isset($_POST['descr'])) {
		foreach ($_POST['descr'] as $k=>$v) {
			$itemdescr[$k] = str_replace(',','',$v);
		}
	}

	$beentaken = isset($_POST['beentaken']);

	if (!$beentaken) {
		$newitemids = array();
		$newitemdescr = array();
		if (isset($_POST['order'])) {
			asort($_POST['order']);
			foreach ($_POST['order'] as $id=>$ord) {
				if (!isset($_POST['delitem'][$id])) {
					$newitemids[] = $itemids[$id];
					$newitemdescr[] = $itemdescr[$id];
				}
			}
		}

		$itemids = array_values($newitemids);
		$itemdescr = array_values($newitemdescr);
	}
	$classbests = array();
	$updatebests = false;
	//if (isset($_POST['idstoadd']) && trim($_POST['idstoadd'])!='') {
	if (isset($_POST['nchecked'])) {
		$toadd = $_POST['nchecked'];
		//$toadd = explode(',',$_POST['idstoadd']);
		foreach ($toadd as $k=>$v) {
			$toadd[$k] = Sanitize::onlyInt($v);
			if ($toadd[$k]==0) {
				unset($toadd[$k]);
			}
		}
		$toadd_query_placeholders = Sanitize::generateQueryPlaceholders($toadd);
		//DB $query = "SELECT id,description FROM imas_questionset WHERE id IN ($toaddlist)";
		$query = "SELECT id,description FROM imas_questionset WHERE id IN ($toadd_query_placeholders)";
		$stm = $DBH->prepare($query); //pre-sanitized INTs
		$stm->execute(array_values($toadd));
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$descr = array();
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$descr[$row[0]] = str_replace(',','',$row[1]);
		}
		foreach ($toadd as $k=>$v) {
			$itemids[] = Sanitize::onlyInt($v);
			$itemdescr[] = $descr[$v];
		}
		$classbests = array_fill(0,count($itemids),-1);
		$updatebests = true;
	}

	$n = intval($_POST['n']);
	$scoretype = $_POST['scoretype'];
	$showtype = intval($_POST['showtype']);
	$showtostu = (isset($_POST['showlast'])?1:0) + (isset($_POST['showpbest'])?2:0) + (isset($_POST['showcbest'])?4:0);
	if (isset($_POST['clearbests'])) {
		$classbests = array_fill(0,count($itemids),-1);
		$updatebests = true;
	}
	$itemlist = implode(',',$itemids);
	$descrlist = implode(',',$itemdescr);
	$bestlist = implode(',',$classbests);
	if ($daid==0) {
		//DB $query = "INSERT INTO imas_drillassess (courseid,name,summary,avail,startdate,enddate,itemdescr,itemids,scoretype,showtype,n,classbests,showtostu) VALUES ";
		//DB $query .= "($cid,'{$_POST['title']}','{$_POST['summary']}','{$_POST['avail']}','$startdate','$enddate','$descrlist','$itemlist','$scoretype',$showtype,$n,'$bestlist',$showtostu)";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $daid = mysql_insert_id();
		$query = "INSERT INTO imas_drillassess (courseid,name,summary,avail,startdate,enddate,itemdescr,itemids,scoretype,showtype,n,classbests,showtostu) VALUES ";
		$query .= "(:courseid, :name, :summary, :avail, :startdate, :enddate, :itemdescr, :itemids, :scoretype, :showtype, :n, :classbests, :showtostu)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$cid, ':name'=>$_POST['title'], ':summary'=>$_POST['summary'], ':avail'=>$_POST['avail'],
			':startdate'=>$startdate, ':enddate'=>$enddate, ':itemdescr'=>$descrlist, ':itemids'=>$itemlist, ':scoretype'=>$scoretype,
			':showtype'=>$showtype, ':n'=>$n, ':classbests'=>$bestlist, ':showtostu'=>$showtostu));
		$daid = $DBH->lastInsertId();

		//DB $query = "INSERT INTO imas_items (courseid,itemtype,typeid) VALUES ";
		//DB $query .= "('$cid','Drill','$daid');";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemid = mysql_insert_id();
		$stm = $DBH->prepare("INSERT INTO imas_items (courseid,itemtype,typeid) VALUES (:courseid, 'Drill', :typeid)");
		$stm->execute(array(':courseid'=>$cid, ':typeid'=>$daid));
		$itemid = $DBH->lastInsertId();

		//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		$items = unserialize($line['itemorder']);

		$blocktree = explode('-',$block);
		$sub =& $items;
		for ($i=1;$i<count($blocktree);$i++) {
			$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
		}
		if ($totb=='b') {
			$sub[] = $itemid;
		} else if ($totb=='t') {
			array_unshift($sub,$itemid);
		}
		//DB $itemorder = addslashes(serialize($items));
		$itemorder = serialize($items);
		//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
		$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
	} else {
		if ($beentaken) {
			//DB $query = "UPDATE imas_drillassess SET itemdescr='$descrlist',showtostu=$showtostu";
			//DB $query .= ",name='{$_POST['title']}',summary='{$_POST['summary']}',avail='{$_POST['avail']}',caltag='{$_POST['caltag']}',startdate='$startdate',enddate='$enddate'";
			//DB $query .= " WHERE id=$daid";
			$query = "UPDATE imas_drillassess SET itemdescr=:itemdescr,showtostu=:showtostu,";
			$query .= "name=:name,summary=:summary,avail=:avail,caltag=:caltag,startdate=:startdate,enddate=:enddate";
			$qarr = array(':itemdescr'=>$descrlist, ':showtostu'=>$showtostu, ':name'=>$_POST['title'], ':summary'=>$_POST['summary'],
				':avail'=>$_POST['avail'], ':caltag'=>$_POST['caltag'], ':startdate'=>$startdate, ':enddate'=>$enddate);
		} else {
			//DB $query = "UPDATE imas_drillassess SET itemdescr='$descrlist',showtostu=$showtostu,itemids='$itemlist',";
			//DB $query .= "scoretype='$scoretype',showtype=$showtype,n=$n";
			//DB $query .= ",name='{$_POST['title']}',summary='{$_POST['summary']}',avail='{$_POST['avail']}',caltag='{$_POST['caltag']}',startdate='$startdate',enddate='$enddate'";
			//DB $query .= " WHERE id=$daid";
			$query = "UPDATE imas_drillassess SET itemdescr=:itemdescr,showtostu=:showtostu,";
			$query .= "name=:name,summary=:summary,avail=:avail,caltag=:caltag,startdate=:startdate,enddate=:enddate,";
			$query .= "itemids=:itemids,scoretype=:scoretype,showtype=:showtype,n=:n";
			$qarr = array(':itemdescr'=>$descrlist, ':showtostu'=>$showtostu, ':itemids'=>$itemlist, ':scoretype'=>$scoretype,
				':showtype'=>$showtype, ':n'=>$n, ':name'=>$_POST['title'], ':summary'=>$_POST['summary'], ':avail'=>$_POST['avail'],
				':caltag'=>$_POST['caltag'], ':startdate'=>$startdate, ':enddate'=>$enddate);

		}
		if ($updatebests) {
			//DB $query .= ",classbests='$bestlist'";
			$query .= ",classbests=:classbests";
			$qarr[':classbests'] = $bestlist;
		}
		$query .= " WHERE id=:id";
		$qarr[':id'] = $daid;
		$stm = $DBH->prepare($query);
		$stm->execute($qarr);
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		if (!$beentaken) {
			//Delete any instructor attempts to account for possible changes
			//DB $query = "DELETE FROM imas_drillassess_sessions WHERE drillassessid=$daid";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_drillassess_sessions WHERE drillassessid=:drillassessid");
			$stm->execute(array(':drillassessid'=>$daid));
		}
	}

	if (isset($_POST['search'])) {
		$safesearch = $_POST['search'];
		$safesearch = str_replace(' and ', ' ',$safesearch);
		//DB $search = stripslashes($safesearch);
		$search = $safesearch;
		$search = str_replace('"','&quot;',$search);
		$sessiondata['lastsearch'.$cid] = $safesearch; //str_replace(" ","+",$safesearch);
		if (isset($_POST['searchall'])) {
			$searchall = 1;
		} else {
			$searchall = 0;
		}
		$sessiondata['searchall'.$cid] = $searchall;
		if (isset($_POST['searchmine'])) {
			$searchmine = 1;
		} else {
			$searchmine = 0;
		}
		$sessiondata['searchmine'.$cid] = $searchmine;
		if (isset($_POST['newonly'])) {
			$newonly = 1;
		} else {
			$newonly = 0;
		}
		$sessiondata['searchnewonly'.$cid] = $newonly;
		writesessiondata();
	}
	if (isset($_POST['libs'])) {
		if ($_POST['libs']=='') {
			$_POST['libs'] = $userdeflib;
		}
		$searchlibs = Sanitize::encodeStringForDisplay($_POST['libs']);
		//$sessiondata['lastsearchlibs'] = implode(",",$searchlibs);
		$sessiondata['lastsearchlibs'.$aid] = $searchlibs;
		writesessiondata();
	} else if (isset($_GET['listlib'])) {
		$searchlibs = $_GET['listlib'];
		$sessiondata['lastsearchlibs'.$aid] = $searchlibs;
		$searchall = 0;
		$sessiondata['searchall'.$aid] = $searchall;
		$sessiondata['lastsearch'.$aid] = '';
		$searchlikes = '';
		$search = '';
		$safesearch = '';
		writesessiondata();
	}
	if (isset($_POST['save']) && $_POST['save']=='Save') {
		header(sprintf('Location: %s/course/course.php?cid=%s&r=%s', $GLOBALS['basesiteurl'], $cid, Sanitize::randomQueryStringParam()));
	} else {
		header(sprintf('Location: %s/course/adddrillassess.php?cid=%s&daid=%d&r=%s', $GLOBALS['basesiteurl'], $cid, $daid, Sanitize::randomQueryStringParam()));
	}
	exit;
}

//DB $query = "SELECT ias.id FROM imas_drillassess_sessions AS ias,imas_students WHERE ";
//DB $query .= "ias.drillassessid='$daid' AND ias.userid=imas_students.userid AND imas_students.courseid='$cid' LIMIT 1";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB if (mysql_num_rows($result)>0) {
$query = "SELECT ias.id FROM imas_drillassess_sessions AS ias,imas_students WHERE ";
$query .= "ias.drillassessid=:drillassessid AND ias.userid=imas_students.userid AND imas_students.courseid=:courseid LIMIT 1";
$stm = $DBH->prepare($query);
$stm->execute(array(':drillassessid'=>$daid, ':courseid'=>$cid));
if ($stm->rowCount()>0) {
	$beentaken = true;
} else {
	$beentaken = false;
}

$useeditor = "summary";
$placeinhead = "<script type=\"text/javascript\">
		var previewqaddr = '$imasroot/course/testquestion.php?cid=$cid';
		</script>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/addquestions.js\"></script>";
$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/tablesorter.js"></script>';
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";

require("../header.php");

/*  Get data for question searching */
//remember search

if (isset($sessiondata['lastsearch'.$cid])) {
	$safesearch = trim($sessiondata['lastsearch'.$cid]); //str_replace("+"," ",$sessiondata['lastsearch'.$cid]);
	//DB $search = stripslashes($safesearch);
	$search = $safesearch;
	$search = str_replace('"','&quot;',$search);
	$searchall = $sessiondata['searchall'.$cid];
	$searchmine = $sessiondata['searchmine'.$cid];
	$newonly = $sessiondata['searchnewonly'.$cid];
} else {
	$search = '';
	$searchall = 0;
	$searchmine = 0;
	$safesearch = '';
	$newonly = 0;
}

$searchlikevals = array();
if (trim($safesearch)=='') {
	$searchlikes = '';
} else {
	$searchterms = explode(" ",$safesearch);
	//DB $searchlikes = "((imas_questionset.description LIKE '%".implode("%' AND imas_questionset.description LIKE '%",$searchterms)."%') ";
	$searchlikes = "((imas_questionset.description LIKE ?".str_repeat(" AND imas_questionset.description LIKE ?",count($searchterms)-1).") ";
	foreach ($searchterms as $t) {
		$searchlikevals[] = "%$t%";
	}
	if (substr($safesearch,0,3)=='id=') {
		//DB searchlikes = "imas_questionset.id='".substr($safesearch,3)."' AND ";
		$searchlikes = "imas_questionset.id=? AND ";
		$searchlikevals = array(substr($safesearch,3));
	} else if (is_numeric($safesearch)) {
		//DB $searchlikes .= "OR imas_questionset.id='$safesearch') AND ";
		$searchlikes .= "OR imas_questionset.id=?) AND ";
		$searchlikevals[] = $safesearch;
	} else {
		$searchlikes .= ") AND";
	}
}

if (isset($sessiondata['lastsearchlibs'.$aid])) {
	//$searchlibs = explode(",",$sessiondata['lastsearchlibs']);
	$searchlibs = $sessiondata['lastsearchlibs'.$aid];
} else {
	$searchlibs = $userdeflib;
}
//DB $llist = "'".implode("','",explode(',',$searchlibs))."'";
$llist = implode(',',array_map('intval', explode(',',$searchlibs)));

echo '<script type="text/javascript">';
echo "var curlibs = '".Sanitize::encodeStringForJavascript($searchlibs)."';";
echo '</script>';

if (!$beentaken) {
	//potential questions
	$libsortorder = array();
	if (substr($searchlibs,0,1)=="0") {
		$lnamesarr[0] = "Unassigned";
		$libsortorder[0] = 0;
	}

	//DB $query = "SELECT name,id,sortorder FROM imas_libraries WHERE id IN ($llist)";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->query("SELECT name,id,sortorder FROM imas_libraries WHERE id IN ($llist)");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$lnamesarr[$row[1]] = $row[0];
		$libsortorder[$row[1]] = $row[2];
	}
	$lnames = implode(", ",$lnamesarr);

	$page_libRowHeader = ($searchall==1) ? "<th>Library</th>" : "";

	if (isset($search)) {
		$qarr = $searchlikevals;
		//DB $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype,imas_questionset.extref,imas_library_items.libid,imas_questionset.ownerid,imas_questionset.avgtime,imas_library_items.junkflag, imas_library_items.id AS libitemid,imas_users.groupid ";
		//DB $query .= "FROM imas_questionset JOIN imas_library_items ON imas_library_items.qsetid=imas_questionset.id ";
		//DB $query .= "JOIN imas_users ON imas_questionset.ownerid=imas_users.id WHERE imas_questionset.deleted=0 AND $searchlikes ";
		//DB $query .= " (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0)";
		$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.description,imas_questionset.userights,imas_questionset.qtype,imas_questionset.extref,imas_library_items.libid,imas_questionset.ownerid,imas_questionset.avgtime,imas_library_items.junkflag, imas_library_items.id AS libitemid,imas_users.groupid ";
		$query .= "FROM imas_questionset JOIN imas_library_items ON imas_library_items.qsetid=imas_questionset.id AND imas_library_items.deleted=0 ";
		$query .= "JOIN imas_users ON imas_questionset.ownerid=imas_users.id WHERE imas_questionset.deleted=0 AND $searchlikes ";
		$query .= " (imas_questionset.ownerid=? OR imas_questionset.userights>0)";
		$qarr[] = $userid;

		if ($searchall==0) {
			$query .= "AND imas_library_items.libid IN ($llist)"; //pre-sanitized
		}
		if ($searchmine==1) {
			//DB $query .= " AND imas_questionset.ownerid='$userid'";
			$query .= " AND imas_questionset.ownerid=?";
			$qarr[] = $userid;
		} else {
			$query .= " AND (imas_library_items.libid > 0 OR imas_questionset.ownerid=?) ";
			$qarr[] = $userid;
		}
		$query .= " ORDER BY imas_library_items.libid,imas_library_items.junkflag,imas_questionset.id";

		$stm = $DBH->prepare($query);
		$stm->execute($qarr);
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB if (mysql_num_rows($result)==0) {
		if ($stm->rowCount()==0) {
			$noSearchResults = true;
		} else {
			$alt=0;
			$lastlib = -1;
			$i=0;
			$page_questionTable = array();
			$page_libstouse = array();
			$page_libqids = array();
			$page_useavgtimes = false;

			//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
				if ($newonly && in_array($line['id'],$itemids)) {
					continue;
				}
				if (isset($page_questionTable[$line['id']])) {
					continue;
				}
				if ($lastlib!=$line['libid'] && isset($lnamesarr[$line['libid']])) {
					/*$page_questionTable[$i]['checkbox'] = "";
					$page_questionTable[$i]['desc'] = "<b>".$lnamesarr[$line['libid']]."</b>";
					$page_questionTable[$i]['preview'] = "";
					$page_questionTable[$i]['type'] = "";
					if ($searchall==1)
						$page_questionTable[$i]['lib'] = "";
					$page_questionTable[$i]['times'] = "";
					$page_questionTable[$i]['mine'] = "";
					$page_questionTable[$i]['add'] = "";
					$page_questionTable[$i]['src'] = "";
					$page_questionTable[$i]['templ'] = "";
					$lastlib = $line['libid'];
					$i++;
					*/
					$page_libstouse[] = $line['libid'];
					$lastlib = $line['libid'];
					$page_libqids[$line['libid']] = array();

				}

				if (isset($libsortorder[$line['libid']]) && $libsortorder[$line['libid']]==1) { //alpha
					$page_libqids[$line['libid']][$line['id']] = $line['description'];
				} else { //id
					$page_libqids[$line['libid']][] = $line['id'];
				}
				$i = $line['id'];
				$page_questionTable[$i]['checkbox'] = "<input type=checkbox name='nchecked[]' value='" . Sanitize::encodeStringForDisplay($line['id']) . "' id='qo$ln'>";
				if (in_array($i,$itemids)) {
					$page_questionTable[$i]['desc'] = '<span style="color: #999">'.Sanitize::encodeStringForDisplay(filter($line['description'])).'</span>';
				} else {
					$page_questionTable[$i]['desc'] = Sanitize::encodeStringForDisplay(filter($line['description']));
				}
				$page_questionTable[$i]['preview'] = "<input type=button value=\"Preview\" onClick=\"previewq('selform','qo$ln',". Sanitize::onlyInt($line['id']).",true,false)\"/>";
				$page_questionTable[$i]['type'] = $line['qtype'];
				if ($line['avgtime']>0) {
					$page_useavgtimes = true;
					$page_questionTable[$i]['avgtime'] = round($line['avgtime']/60,1);
				} else {
					$page_questionTable[$i]['avgtime'] = '';
				}
				if ($searchall==1) {
					$page_questionTable[$i]['lib'] = sprintf("<a href=\"addquestions.php?cid=%s&aid=%d&listlib=%s\">List lib</a>",
                        $cid, $aid, Sanitize::encodeUrlParam($line['libid']));
				} else {
					$page_questionTable[$i]['junkflag'] = $line['junkflag'];
					$page_questionTable[$i]['libitemid'] = $line['libitemid'];
				}
				if ($line['extref']!='') {
					$extref = explode('~~',$line['extref']);
					$hasvid = false;  $hasother = false;
					foreach ($extref as $v) {
						if (substr($v,0,5)=="Video" || strpos($v,'youtube.com')!==false) {
							$hasvid = true;
						} else {
							$hasother = true;
						}
					}
					$page_questionTable[$i]['extref'] = '';
					if ($hasvid) {
						$page_questionTable[$i]['extref'] .= "<img src=\"$imasroot/img/video_tiny.png\" alt=\"Video\"/>";
					}
					if ($hasother) {
						$page_questionTable[$i]['extref'] .= "<img src=\"$imasroot/img/html_tiny.png\" alt=\"Help Resource\"/>";
					}
				}

				/*$query = "SELECT COUNT(id) FROM imas_questions WHERE questionsetid='{$line['id']}'";
				$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
				$times = mysql_result($result2,0,0);
				$page_questionTable[$i]['times'] = $times;
				*/
				$page_questionTable[$i]['times'] = 0;

				if ($line['ownerid']==$userid) {
					if ($line['userights']==0) {
						$page_questionTable[$i]['mine'] = "Private";
					} else {
						$page_questionTable[$i]['mine'] = "Yes";
					}
				} else {
					$page_questionTable[$i]['mine'] = "";
				}



				if ($line['userights']>3 || ($line['userights']==3 && $line['groupid']==$groupid) || $line['ownerid']==$userid) {
					$page_questionTable[$i]['src'] = sprintf("<a href=\"moddataset.php?id=%d&daid=%d&cid=%s&frompot=1\">Edit</a>",
                        Sanitize::onlyInt($line['id']), $daid, $cid);
				} else {
					$page_questionTable[$i]['src'] = sprintf("<a href=\"viewsource.php?id=%d&daid=%d&cid=%s\">View</a>",
                        Sanitize::onlyInt($line['id']), $daid, $cid);
				}

				$page_questionTable[$i]['templ'] = sprintf("<a href=\"moddataset.php?id=%d&daid=%d&cid=%s&template=true\">Template</a>",
                        Sanitize::onlyInt($line['id']), $daid, $cid);
				//$i++;
				$ln++;

			} //end while

			//pull question useage data
			if (count($page_questionTable)>0) {
				$allusedqids = array_keys($page_questionTable); //INT vals from DB
                $allusedqids_query_placeholders = Sanitize::generateQueryPlaceholders($allusedqids);
				//DB $query = "SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allusedqids) GROUP BY questionsetid";
				$stm = $DBH->prepare("SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allusedqids_query_placeholders) GROUP BY questionsetid");
				$stm->execute($allusedqids);
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$page_questionTable[$row[0]]['times'] = $row[1];
				}
			}

			//sort alpha sorted libraries
			foreach ($page_libstouse as $libid) {
				if ($libsortorder[$libid]==1) {
					natcasesort($page_libqids[$libid]);
					$page_libqids[$libid] = array_keys($page_libqids[$libid]);
				}
			}
			if ($searchall==1) {
				$page_libstouse = array_keys($page_libqids);
			}

		}
	}

}

$hr = floor($coursedeftime/60)%12;
$min = $coursedeftime%60;
$am = ($coursedeftime<12*60)?'am':'pm';
$deftime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
$hr = floor($coursedefstime/60)%12;
$min = $coursedefstime%60;
$am = ($coursedefstime<12*60)?'am':'pm';
$defstime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;

if ($startdate!=0) {
	$sdate = tzdate("m/d/Y",$startdate);
	$stime = tzdate("g:i a",$startdate);
} else {
	$sdate = tzdate("m/d/Y",time());
	$stime = $defstime; //tzdate("g:i a",time());
}
if ($enddate!=2000000000) {
	$edate = tzdate("m/d/Y",$enddate);
	$etime = tzdate("g:i a",$enddate);
} else {
	$edate = tzdate("m/d/Y",time()+7*24*60*60);
	$etime = $deftime; //tzdate("g:i a",time()+7*24*60*60);
}


?>
<script type="text/javascript">

function updateorder(el) {
	var tomove = el.parentNode.parentNode;
	var tbl = document.getElementById("usedqtable").getElementsByTagName("tbody")[0];
	var trs = tbl.getElementsByTagName("tr");
	var n = 0;
	for (var i=0;i<trs.length;i++) {
		if (trs[i]==tomove) {
			n = i;
			break;
		}
	}
	var cnt = trs.length;
	var moveto = el.value*1;
	if (moveto<=n) {
		var dest = trs[moveto];
	} else if (moveto+1 < cnt) {
		var dest = trs[moveto+1];
	}

	tbl.removeChild(tomove);
	if (cnt==moveto+1) {
		tbl.appendChild(tomove);
	} else {
		tbl.insertBefore(tomove,dest);
	}
	var sel = tbl.getElementsByTagName("select");
	for (var i=0;i<sel.length;i++) {
		sel[i].selectedIndex = i;
	}
}
</script>
<?php

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Add/Modify Drill Assessment</div>";
echo "<h1>Add/Modify Drill Assessment</h1>";

printf("<form id=\"selform\" method=\"post\" action=\"adddrillassess.php?cid=%s&daid=%d&block=%s&tb=%s&record=true\">",
    $cid, $daid, Sanitize::encodeUrlParam($block), Sanitize::encodeUrlParam($totb));
?>
		<span class=form>Title: </span>
		<span class=formright><input type=text size=60 name="title" value="<?php echo Sanitize::encodeStringForDisplay($drillname);?>">
		</span><BR class=form>

		Summary<BR>
		<div class=editor>
			<textarea cols=60 rows=10 id=summary name=summary style="width: 100%"><?php echo Sanitize::encodeStringForDisplay($drillsummary);?></textarea>
		</div>
		<br/>
		<span class=form>Show:</span>
		<span class=formright>
			<input type=radio name="avail" value="0" <?php writeHtmlChecked($avail,0);?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='none';"/>Hide<br/>
			<input type=radio name="avail" value="1" <?php writeHtmlChecked($avail,1);?> onclick="document.getElementById('datediv').style.display='block';document.getElementById('altcaldiv').style.display='none';"/>Show by Dates<br/>
			<input type=radio name="avail" value="2" <?php writeHtmlChecked($avail,2);?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='block';"/>Show Always<br/>
		</span><br class="form"/>

		<div id="datediv" style="display:<?php echo ($avail==1)?"block":"none"; ?>">
		<span class=form>Available After:</span>
		<span class=formright>
			<input type=radio name="sdatetype" value="0" <?php writeHtmlChecked($startdate,'0',0) ?>/>
			Always until end date<br/>
			<input type=radio name="sdatetype" value="sdate" <?php writeHtmlChecked($startdate,'0',1) ?>/>
			<input type=text size=10 name=sdate value="<?php echo $sdate;?>">
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=stime value="<?php echo $stime;?>">
		</span><BR class=form>

		<span class=form>Available Until:</span><span class=formright>
			<input type=radio name="edatetype" value="2000000000" <?php writeHtmlChecked($enddate,'2000000000',0) ?>/> Always after start date<br/>
			<input type=radio name="edatetype" value="edate"  <?php writeHtmlChecked($enddate,'2000000000',1) ?>/>
			<input type=text size=10 name=edate value="<?php echo $edate;?>">
			<a href="#" onClick="displayDatePicker('edate', this, 'sdate', 'start date'); return false">
			<img src="../img/cal.gif" alt="Calendar"/></a>
			at <input type=text size=10 name=etime value="<?php echo $etime;?>">
		</span><BR class=form>

		<span class=form>Calendar Tag:</span>
		<span class=formright>
			<input name="caltag" type=text size=8 value="<?php echo Sanitize::encodeStringForDisplay($caltag); ?>"/>
		</span><BR class=form>
		</div>
		<span class=form></span>
		<span class=formright>
			<input type=submit name="save" value="Save"> now or continue below for Drill Options
		</span><br class=form>

<?php

if ($beentaken) {
	echo '<p>This drill has already been taken!  You will not be able to modify most settings unless you clear out existing attempts. ';
	echo "<button type=button onclick=\"if (confirm('Are you SURE you want to clear out existing attempts?')) {window.location='adddrillassess.php?cid=$cid&daid=$daid&clearatt=true';}\">Clear Existing Attempts</button></p>\n";
	//echo " <a href=\"adddrillassess.php?cid=$cid&daid=$daid&clearatt=true\" onclick=\"return confirm('Are you SURE you want to clear out existing attempts?');\">Clear existing attempts</a></p>";
}

echo '<p><b>Drill type</b></p>';
echo '<p>Scoring type:';
$vals = array('nat','nct','ncc','nst','nsc','t');
$lbls = array('Do N questions then stop.  Record time.','Do N questions correct.  Record time.','Do N questions correct.  Record total attempts.','Do N questions correct in a row.  Record time','Do N questions correct in a row.  Record total attempts','Do as many correct as possible in N seconds');
writeHtmlSelect('scoretype',$vals,$lbls,$scoretype,null,null,$beentaken?'disabled="disabled"':'');
echo ' where N = <input type="text" size="4" name="n" value="' . Sanitize::encodeStringForDisplay($n) . '" ' . ($beentaken ? 'disabled="disabled"' : '') . '/></p>';
echo '<p>Feedback on individual questions:';
$vals = array(0,1,4,2,3);
$lbls = array('Show score, and display answer if wrong', 'Show score, don\'t show answers, give new question if wrong','Show score, don\'t show answers, give same question if wrong','Don\'t show score','Don\'t show score, but provide show answer buttons');
writeHtmlSelect('showtype',$vals,$lbls,$showtype,null,null,$beentaken?'disabled="disabled"':'');
echo '</p>';

echo '<p>Show drill results to student: ';
echo '<input type="checkbox" name="showlast" '.getHtmlChecked($showtostu&1,1).'/> Show last score. ';
echo '<input type="checkbox" name="showpbest" '.getHtmlChecked($showtostu&2,2).'/> Show personal best score. ';
echo '<input type="checkbox" name="showcbest" '.getHtmlChecked($showtostu&4,4).'/> Show class best score.</p>';

if ($beentaken) {
	echo '<p>Reset class bests?  <input type="checkbox" name="clearbests" value="1" /></p>';
}
echo '<table id="usedqtable">';
echo '<tr>';
if (!$beentaken) {echo '<th></th>';}
echo '<th>Description</th><th>Preview</th>';
if (!$beentaken) {echo '<th>Delete?</th>';}
echo '</tr>';
function generateselect($cnt,$i) {
	echo "<select name=\"order[$i]\" onchange=\"updateorder(this)\">";
	for ($j=1;$j<$cnt+1;$j++) {
		echo "<option value=\"$j\" ";
		if ($j==$i+1) {echo 'selected="selected" ';}
		echo '>'.($j).'</option>';
	}
	echo '</select>';
}
foreach ($itemids as $k=>$id) {
	echo '<tr id="row'.$k.'">';
	if (!$beentaken) {
		echo '<td>';
		generateselect(count($itemids),$k);
		echo '</td>';
	}
	echo '<td><input type="text" size="60" name="descr['.$k.']" value="' . Sanitize::encodeStringForDisplay($itemdescr[$k]) . '"/></td>';
	echo "<td><input type=button value=\"Preview\" onClick=\"previewq(null,$k," . Sanitize::encodeStringForJavascript($itemids[$k]) . ")\"/></td>";
	if (!$beentaken) {
		echo '<td><input type="checkbox" name="delitem['.$k.']" value="1"/></td>';
	}
	echo '</tr>';
}
echo '<table>';
 echo '<input type="submit" value="Update"/>';
if (!$beentaken) {
?>

	<h2>Potential Questions</h2>

		In Libraries:
		<span id="libnames"><?php echo Sanitize::encodeStringForDisplay($lnames) ?></span>
		<input type=hidden name="libs" id="libs"  value="<?php echo Sanitize::encodeStringForDisplay($searchlibs) ?>">
		<input type="button" value="Select Libraries" onClick="GB_show('Library Select','libtree2.php?libtree=popup&libs='+curlibs,500,500)" />
		<br>
		Search:
		<input type=text size=15 name=search value="<?php echo Sanitize::encodeStringForDisplay($search) ?>">
		<span tabindex="0" data-tip="Search all libraries, not just selected ones" onmouseover="tipshow(this)" onfocus="tipshow(this)" onmouseout="tipout()" onblur="tipout()">
		<input type=checkbox name="searchall" value="1" <?php writeHtmlChecked($searchall,1,0) ?> />
		Search all libs</span>
		<span tabindex="0" data-tip="List only questions I own" onmouseover="tipshow(this)" onfocus="tipshow(this)" onmouseout="tipout()" onblur="tipout()">
		<input type=checkbox name="searchmine" value="1" <?php writeHtmlChecked($searchmine,1,0) ?> />
		Mine only</span>
		<span tabindex="0" data-tip="Exclude questions already in assessment" onmouseover="tipshow(this)" onfocus="tipshow(this)" onmouseout="tipout()" onblur="tipout()">
		<input type=checkbox name="newonly" value="1" <?php writeHtmlChecked($newonly,1,0) ?> />
		Exclude added</span>
		<input type=submit value=Search>
		<input type=button value="Add New Question" onclick="window.location='moddataset.php?aid=<?php echo $aid ?>&cid=<?php echo $cid ?>'">

	<br/>
<?php
			if ($searchall==1 && trim($search)=='') {
				echo "Must provide a search term when searching all libraries";
			} elseif (isset($search)) {
				if ($noSearchResults) {
					echo "<p>No Questions matched search</p>\n";
				} else {
?>


		Check: <a href="#" onclick="return chkAllNone('selform','nchecked[]',true)">All</a> <a href="#" onclick="return chkAllNone('selform','nchecked[]',false)">None</a>
		<input name="add" type=submit value="Add Selected" />

		<table cellpadding="5" id="myTable" class="gb" style="clear:both; position:relative;">
			<thead>
				<tr><th></th><th>Description</th><th></th><th>ID</th><th>Preview</th><th>Type</th>
					<?php echo $page_libRowHeader ?>
					<th>Times Used</th>
					<?php if ($page_useavgtimes) {?><th><span onmouseover="tipshow(this,'Average time, in minutes, this question has taken students')" onmouseout="tipout()">Avg Time</span></th><?php } ?>
					<th>Mine</th><th>Source</th><th>Use as Template</th>
					<?php if ($searchall==0) { echo '<th><span onmouseover="tipshow(this,\'Flag a question if it is in the wrong library\')" onmouseout="tipout()">Wrong Lib</span></th>';} ?>
				</tr>
			</thead>
			<tbody>
<?php
				$alt=0;
				for ($j=0; $j<count($page_libstouse); $j++) {

					if ($searchall==0) {
						if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
						echo '<td></td>';
						echo '<td>';
						echo '<b>' . Sanitize::encodeStringForDisplay($lnamesarr[$page_libstouse[$j]]) . '</b>';
						echo '</td>';
						for ($k=0;$k<9;$k++) {echo '<td></td>';}
						echo '</tr>';
					}

					for ($i=0;$i<count($page_libqids[$page_libstouse[$j]]); $i++) {
						$qid =$page_libqids[$page_libstouse[$j]][$i];
						if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}

?>
					<td><?php echo $page_questionTable[$qid]['checkbox'] ?></td>
					<td><?php echo $page_questionTable[$qid]['desc'] ?></td>
					<td class="nowrap"><?php echo $page_questionTable[$qid]['extref'] ?></td>
					<td><?php echo Sanitize::encodeStringForDisplay($qid) ?></td>
					<td><?php echo $page_questionTable[$qid]['preview'] ?></td>
					<td><?php echo Sanitize::encodeStringForDisplay($page_questionTable[$qid]['type']) ?></td>
<?php
						if ($searchall==1) {
?>
					<td><?php echo $page_questionTable[$qid]['lib'] ?></td>
<?php
						}
?>
					<td class=c><?php echo Sanitize::encodeStringForDisplay($page_questionTable[$qid]['times']) ?></td>
					<?php if ($page_useavgtimes) {?><td class="c"><?php echo Sanitize::encodeStringForDisplay($page_questionTable[$qid]['avgtime']) ?></td> <?php }?>
					<td><?php echo Sanitize::encodeStringForDisplay($page_questionTable[$qid]['mine']) ?></td>
					<td><?php echo $page_questionTable[$qid]['src'] ?></td>
					<td class=c><?php echo $page_questionTable[$qid]['templ'] ?></td>
					<?php if ($searchall==0) {
						if ($page_questionTable[$qid]['junkflag']==1) {
							echo "<td class=c><img class=\"pointer\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" alt=\"Flagged\"/></td>";
						} else {
							echo "<td class=c><img class=\"pointer\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" alt=\"Not flagged\"/></td>";
						}
					} ?>
				</tr>
<?php
					}
				}
?>
			</tbody>
		</table>
		<p>Questions <span style="color:#999">in gray</span> have been added to the assessment.</p>
		<script type="text/javascript">
			initSortTable('myTable',Array(false,'S','N',false,'S',<?php echo ($searchall==1) ? "false, " : ""; ?>'N','S',false,false,false<?php echo ($searchall==0) ? ",false" : ""; ?>),true);
		</script>


<?php
				}
			}
} else {
	echo '<input type="hidden" name="beentaken" value="1" />';
}
/*if (!$beentaken) {
	echo '<p>Add more questions (list ids separated by commas): <input type="text" name="idstoadd" value="" /></p>';
}

echo '<input type="submit" value="Update"/>';
*/
echo '</form>';
/*if ($daid>0) {
	$url = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/drillassess.php?cid=$cid&amp;daid=$daid";
	echo "<p>Link to drill assessment: <a href=\"$url\">$url</a></p>" ;
	$url = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gb-viewdrill.php?cid=$cid&amp;daid=$daid";
	echo "<p>Link to view results: <a href=\"$url\">$url</a></p>" ;

}*/
require('../footer.php');

?>
