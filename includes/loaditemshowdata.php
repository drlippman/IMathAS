<?php

//the loadItemShowData function loads item data based on a course itemarray

/***
* used by loadItemShowData to determine imas_items to pull data for
*
*   	$items:  	array from imas_course.itemorder or from subblock
*	$inpublic:	Are we in a public block?
*	$viewall:	Is this a teacher/tutor able to see all items?
*	$tolookup:	Array to store imas_items.id's in
*	$onlyopen:	Only include items in expanded blocks? 1: only open,
*   0: include non-open, -1: include non-open but exclude treereder contents
*	$ispublic:	Are we loading this from public.php?
***/

function getitemstolookup($items,$inpublic,$viewall,&$tolookup,$onlyopen,$ispublic) {
	 global $studentinfo,$openblocks,$firstload;
	 $now = time();
	 foreach ($items as $item) {
		 if (is_array($item)) { //only add content from open blocks
			 $turnonpublic = false;
			 if ($ispublic && !$inpublic) {
			 	 if (isset($item['public']) && $item['public']==1) {
			 	 	 $turnonpublic = true;
			 	 } else {
			 	 	 continue;
			 	 }
			 }
			 if (!$viewall && isset($item['grouplimit']) && count($item['grouplimit'])>0) {
				 if (!in_array('s-'.$studentinfo['section'],$item['grouplimit'])) {
					 continue;
				 }
			 }
			 if (($item['avail']==2 || ($item['avail']==1 && $item['startdate']<$now && $item['enddate']>$now)) ||
				($viewall || ($item['SH'][0]=='S' && $item['avail']>0))) {
					if ($onlyopen==1) {
						if (in_array($item['id'],$openblocks)) { $isopen=true;} else {$isopen=false;}
						if ($firstload && (strlen($item['SH'])==1 || $item['SH'][1]=='O')) {$isopen=true;}
					}
					if ($onlyopen==0 || ($onlyopen==-1 && $item['SH'][1]!='T') || ($onlyopen==1 && $isopen && $item['SH'][1]!='T' && $item['SH'][1]!='F')) {
						getitemstolookup($item['items'],$inpublic||$turnonpublic,$viewall,$tolookup,$onlyopen,$ispublic);
					}
			 }
		} else {
			$tolookup[] = $item;
		}
	}
}

/***
* Loads data for items
*
*	Returns:	array(imas_items.id => associative array of item data)
*
*   	$items:  	array from imas_course.itemorder or from subblock
*	$onlyopen:	Only include items in expanded blocks?  1: only open,
*   0: include non-open, -1: include non-open but exclude treereder contents
*	$viewall:	Is this a teacher/tutor able to see all items?
*	$inpublic:	Are we in a public block?
*	$ispublic:	Are we loading this from public.php?
* 	$limittype: 	Limit item type loaded
*	$limited:	True to only return ids and names/summaries
***/
function loadItemShowData($items,$onlyopen,$viewall,$inpublic=false,$ispublic=false,$limittype=false,$limited=false) {
	global $DBH;
	$itemshowdata = array();
	if ($onlyopen===true) {$onlyopen = 1;} else if ($onlyopen===false) {$onlyopen = 0;}
	$itemstolookup = array();
	getitemstolookup($items,$inpublic,$viewall,$itemstolookup,$onlyopen,$ispublic);
	$typelookups = array();
	if (count($itemstolookup)>0) {
		$placeholders = Sanitize::generateQueryPlaceholders($itemstolookup);
		$query = "SELECT itemtype,typeid,id FROM imas_items WHERE id IN ($placeholders)";
		if ($limittype!==false) {
			$query .= " AND itemtype=?";
			$stm = $DBH->prepare($query);
			$stm->execute(array_merge($itemstolookup, array($limittype)));
		} else {
			$stm = $DBH->prepare($query);
			$stm->execute($itemstolookup);
		}

		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			if (!isset($typelookups[$line['itemtype']])) {$typelookups[$line['itemtype']] = array();}
			if ($line['itemtype']=='Calendar') {
				$itemshowdata[$line['id']] = $line;
			} else {
				$typelookups[$line['itemtype']][$line['typeid']] = $line['id'];  //store so we can map typeid back to item id below
			}
		}
	}
	$assessPreReqsToLookup = array();
	if (isset($typelookups['Assessment']) && !$ispublic) {
		$placeholders = Sanitize::generateQueryPlaceholders($typelookups['Assessment']);
		if ($limited) {
			$tosel = 'id,name,summary';
		} else {
			$tosel = 'id,name,summary,startdate,enddate,reviewdate,deffeedback,reqscore,reqscoreaid,reqscoretype,avail,allowlate,timelimit,ptsposs,date_by_lti';
		}
		$stm = $DBH->prepare("SELECT $tosel FROM imas_assessments WHERE id IN ($placeholders)");
		$stm->execute(array_keys($typelookups['Assessment']));
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$line['itemtype'] = 'Assessment';
			$itemshowdata[$typelookups['Assessment'][$line['id']]] = $line;
			if ($line['reqscoreaid']>0 && ($line['reqscore']<0 || $line['reqscoretype']&1)) {
				$assessPreReqsToLookup[$line['reqscoreaid']] = $line['id'];
			}
		}
	}
	if (count($assessPreReqsToLookup)>0 && !$limited) {
		$typelookups['AssessPrereq'] = array();
		$placeholders = Sanitize::generateQueryPlaceholders($assessPreReqsToLookup);
		$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE id IN ($placeholders)");
		$stm->execute(array_keys($assessPreReqsToLookup));
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$refaid = $assessPreReqsToLookup[$line['id']];
			$itemshowdata[$typelookups['Assessment'][$refaid]]['reqscorename'] = $line['name'];
		}
	}
	if (isset($typelookups['InlineText'])) {
		$placeholders = Sanitize::generateQueryPlaceholders($typelookups['InlineText']);
		if ($limited) {
			$tosel = 'id,title,text';
		} else {
			$tosel = 'id,title,text,startdate,enddate,fileorder,avail,isplaylist';
		}
		$stm = $DBH->prepare("SELECT $tosel FROM imas_inlinetext WHERE id IN ($placeholders)");
		$stm->execute(array_keys($typelookups['InlineText']));
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$line['itemtype'] = 'InlineText';
			$itemshowdata[$typelookups['InlineText'][$line['id']]] = $line;
		}
	}
	if (isset($typelookups['Drill']) && !$ispublic) {
		$placeholders = Sanitize::generateQueryPlaceholders($typelookups['Drill']);
		if ($limited) {
			$tosel = 'id,name,summary';
		} else {
			$tosel = 'id,name,summary,startdate,enddate,avail';
		}
		$stm = $DBH->prepare("SELECT $tosel FROM imas_drillassess WHERE id IN ($placeholders)");
		$stm->execute(array_keys($typelookups['Drill']));
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$line['itemtype'] = 'Drill';
			$itemshowdata[$typelookups['Drill'][$line['id']]] = $line;
		}
	}
	if (isset($typelookups['LinkedText'])) {
		$placeholders = Sanitize::generateQueryPlaceholders($typelookups['LinkedText']);
		if ($limited) {
			$tosel = 'id,title,summary';
		} else {
			$tosel = 'id,title,summary,text,startdate,enddate,avail,target';
		}
		$stm = $DBH->prepare("SELECT $tosel FROM imas_linkedtext WHERE id IN ($placeholders)");
		$stm->execute(array_keys($typelookups['LinkedText']));
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$line['itemtype'] = 'LinkedText';
			$itemshowdata[$typelookups['LinkedText'][$line['id']]] = $line;
		}
	}
	if (isset($typelookups['Forum']) && !$ispublic) {
		$placeholders = Sanitize::generateQueryPlaceholders($typelookups['Forum']);
		if ($limited) {
			$tosel = 'id,name,description';
		} else {
			$tosel = 'id,name,description,startdate,enddate,groupsetid,avail,postby,replyby,allowlate';
		}
		$stm = $DBH->prepare("SELECT $tosel FROM imas_forums WHERE id IN ($placeholders)");
		$stm->execute(array_keys($typelookups['Forum']));
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$line['itemtype'] = 'Forum';
			$itemshowdata[$typelookups['Forum'][$line['id']]] = $line;
		}
	}
	if (isset($typelookups['Wiki'])) {
		$placeholders = Sanitize::generateQueryPlaceholders($typelookups['Wiki']);
		if ($limited) {
			$tosel = 'id,name,description';
		} else {
			$tosel = 'id,name,description,startdate,enddate,editbydate,avail,settings,groupsetid';
		}
		$stm = $DBH->prepare("SELECT $tosel FROM imas_wikis WHERE id IN ($placeholders)");
		$stm->execute(array_keys($typelookups['Wiki']));
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$line['itemtype'] = 'Wiki';
			$itemshowdata[$typelookups['Wiki'][$line['id']]] = $line;
		}
	}

	return $itemshowdata;
}

function loadExceptions($cid, $userid) {
	global $DBH;

	$exceptions = array();
	$query = "SELECT items.id,ex.startdate,ex.enddate,ex.islatepass,ex.waivereqscore,ex.itemtype FROM ";
	$query .= "imas_exceptions AS ex,imas_items as items,imas_assessments as i_a WHERE ex.userid=:userid AND ";
	$query .= "ex.assessmentid=i_a.id AND (items.typeid=i_a.id AND items.itemtype='Assessment' AND items.courseid=:courseid) ";
	$query .= "UNION SELECT items.id,ex.startdate,ex.enddate,ex.islatepass,ex.waivereqscore,ex.itemtype FROM ";
	$query .= "imas_exceptions AS ex,imas_items as items,imas_forums as i_f WHERE ex.userid=:userid2 AND ";
	$query .= "ex.assessmentid=i_f.id AND (items.typeid=i_f.id AND items.itemtype='Forum' AND items.courseid=:courseid2) ";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':userid2'=>$userid, ':courseid2'=>$cid));
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$exceptions[$line['id']] = array($line['startdate'],$line['enddate'],$line['islatepass'],$line['waivereqscore'],$line['itemtype']);
	}
	return $exceptions;
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
			   if (isset($exceptions[$item]) && $exceptions[$item][4]=='A') {
				  // return ($exceptions[$item]);
				   if ($exceptions[$item][0]<$minsdate) { $minsdate = $exceptions[$item][0];}
				   if ($exceptions[$item][1]>$maxedate) { $maxedate = $exceptions[$item][1];}
			   } else if (isset($exceptions[$item]) && ($exceptions[$item][4]=='F' || $exceptions[$item][4]=='P' || $exceptions[$item][4]=='R')) {
			   	   //extend due date if replyby or postby bigger than enddate
			   	   if ($exceptions[$item][0]>$maxedate) { $maxedate = $exceptions[$item][0];}
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

?>
