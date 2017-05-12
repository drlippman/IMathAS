<?php

//the loadItemShowData function loads item data based on a course itemarray 

/*** 
* used by loadItemShowData to determine imas_items to pull data for
*
*   	$items:  	array from imas_course.itemorder or from subblock
*	$inpublic:	Are we in a public block?
*	$viewall:	Is this a teacher/tutor able to see all items?
*	$tolookup:	Array to store imas_items.id's in
*	$onlyopen:	Only include items in expanded blocks?
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
					if ($onlyopen) {
						if (in_array($item['id'],$openblocks)) { $isopen=true;} else {$isopen=false;}
						if ($firstload && (strlen($item['SH'])==1 || $item['SH'][1]=='O')) {$isopen=true;}
					}
					if ((!$onlyopen && $item['SH'][1]!='T') || ($onlyopen && $isopen && $item['SH'][1]!='T' && $item['SH'][1]!='F')) {
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
*	$onlyopen:	Only include items in expanded blocks?
*	$viewall:	Is this a teacher/tutor able to see all items?
*	$inpublic:	Are we in a public block?
*	$ispublic:	Are we loading this from public.php? 
***/
function loadItemShowData($items,$onlyopen,$viewall,$inpublic=false,$ispublic=false) {
	global $DBH;
	$itemshowdata = array();
	
	$itemstolookup = array();
	getitemstolookup($items,$inpublic,$viewall,$itemstolookup,$onlyopen,$ispublic);
	$typelookups = array();
	if (count($itemstolookup)>0) {
		$itemlist = implode(',', array_map('intval', $itemstolookup));
		$stm = $DBH->query("SELECT itemtype,typeid,id FROM imas_items WHERE id IN ($itemlist)");
		
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			if (!isset($typelookups[$line['itemtype']])) {$typelookups[$line['itemtype']] = array();}
			if ($line['itemtype']=='Calendar') {
				$itemshowdata[$line['id']] = $line;
			} else {
				$typelookups[$line['itemtype']][$line['typeid']] = $line['id'];  //store so we can map typeid back to item id below
			}
		}
	}
	if (isset($typelookups['Assessment']) && !$ispublic) {
		$typelist = implode(',', array_keys($typelookups['Assessment']));
		$stm = $DBH->query("SELECT id,name,summary,startdate,enddate,reviewdate,deffeedback,reqscore,reqscoreaid,avail,allowlate,timelimit FROM imas_assessments WHERE id IN ($typelist)");
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$line['itemtype'] = 'Assessment';
			$itemshowdata[$typelookups['Assessment'][$line['id']]] = $line;
		}
	}
	if (isset($typelookups['InlineText'])) {
		$typelist = implode(',', array_keys($typelookups['InlineText']));
		$stm = $DBH->query("SELECT id,title,text,startdate,enddate,fileorder,avail,isplaylist FROM imas_inlinetext WHERE id IN ($typelist)");
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$line['itemtype'] = 'InlineText';
			$itemshowdata[$typelookups['InlineText'][$line['id']]] = $line;
		}
	}
	if (isset($typelookups['Drill']) && !$ispublic) {
		$typelist = implode(',', array_keys($typelookups['Drill']));
		$stm = $DBH->query("SELECT id,name,summary,startdate,enddate,avail FROM imas_drillassess WHERE id IN ($typelist)");
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$line['itemtype'] = 'Drill';
			$itemshowdata[$typelookups['Drill'][$line['id']]] = $line;
		}
	}
	if (isset($typelookups['LinkedText'])) {
		$typelist = implode(',', array_keys($typelookups['LinkedText']));
		$stm = $DBH->query("SELECT id,title,summary,text,startdate,enddate,avail,target FROM imas_linkedtext WHERE id IN ($typelist)");
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$line['itemtype'] = 'LinkedText';
			$itemshowdata[$typelookups['LinkedText'][$line['id']]] = $line;
		}
	}
	if (isset($typelookups['Forum']) && !$ispublic) {
		$typelist = implode(',', array_keys($typelookups['Forum']));
		$stm = $DBH->query("SELECT id,name,description,startdate,enddate,groupsetid,avail,postby,replyby,allowlate FROM imas_forums WHERE id IN ($typelist)");
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$line['itemtype'] = 'Forum';
			$itemshowdata[$typelookups['Forum'][$line['id']]] = $line;
		}
	}
	if (isset($typelookups['Wiki'])) {
		$typelist = implode(',', array_keys($typelookups['Wiki']));
		$stm = $DBH->query("SELECT id,name,description,startdate,enddate,editbydate,avail,settings,groupsetid FROM imas_wikis WHERE id IN ($typelist)");
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
