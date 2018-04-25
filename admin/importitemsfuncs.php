<?php
//IMathAS:  Course item import processing funcs
//JSON edition
//(c) 2017 David Lippman

require_once("../includes/htmLawed.php");
require_once("../includes/updateptsposs.php");

//used during confirmation step
function getsubinfo($items,$parent,$pre) {
	global $ids,$types,$names,$data,$parents;
	foreach($items as $k=>$anitem) {
		if (is_array($anitem)) {
			$ids[] = $parent.'-'.($k+1);
			$types[] = $pre."Block";
			$names[] = $anitem['name'];
			$parents[] = $parent;
			getsubinfo($anitem['items'],$parent.'-'.($k+1),$pre.'--');
		} else {
			$ids[] = $anitem;
			$parents[] = $parent;
			$types[] = $pre.$data['items'][$anitem]['type'];
			if (isset($data['items'][$anitem]['data']['name'])) {
				$names[] = $data['items'][$anitem]['data']['name'];
			} else {
				$names[] = $data['items'][$anitem]['data']['title'];
			}
		}
	}
}

class ImportItemClass
{

private $data = array();
private $cid = 0;
private $checked = true;
private $options = array();
private $blockcnt = 0;
private $itemmap = array();
private $itemstoimport = array();
private $gbmap = array(0=>0);
private $qsmap = array();
private $qmap = array();
private $typemap = array();
private $toimportbytype = array();
private $importowner = 0;
private $now = 0;
private $qmodcnt = 0;
private $qsadded = 0;
private $isadmin = false;

//do the data import
//$data:  parsed JSON array
//$cid:	course ID to import into
//$checked:  The array of checked items to import, or boolean TRUE to import all
//$options:  Array of options:
//   	ownerid: set to provide import ownerid. Otherwise executing user is owner
//	importcourseopt: import course settings (overwrites existing)
//	importgbsetup: import gb scheme and cats (overwrites existing)
//	update: for questions; 1 for update if newer, 2 for force update, 0 for no update
//	userights: userights for imported q's.  -1 to use rights in export file
//	importlib: library for imported q's
//	importstickyposts: import sticky posts
//	importoffline: import offline grade items
//	importcalitems: import calendar items
public function importdata($data, $cid, $checked, $options) {
	global $userid, $db_fields, $CFG, $DBH, $myrights;

	$this->data = $data;
	$this->cid = $cid;
	$this->checked = $checked;
	$this->options = $options;

	//clear out class variables
	$this->itemmap = array();
	$this->itemstoimport = array();
	$this->gbmap = array(0=>0);
	$this->qsmap = array();
	$this->qmap = array();
	$this->typemap = array();
	$this->toimportbytype = array();
	$this->now = time();

	$DBH->beginTransaction();

	if (!empty($this->options['ownerid'])) {
		$this->importowner = $this->options['ownerid'];
	} else {
		$this->importowner = $userid;
	}
	if (!isset($this->options['userights'])) {
		$this->options['userights'] = -1;
	}
	if (!isset($this->options['importlib'])) {
		$this->options['importlib'] = 0;
	}

	$stm = $DBH->prepare("SELECT itemorder,blockcnt,ownerid FROM imas_courses WHERE id=?");
	$stm->execute(array($this->cid));
	list($itemorder, $this->blockcnt, $courseowner) = $stm->fetch(PDO::FETCH_NUM);
	$courseitems = unserialize($itemorder);

	if (!empty($this->options['usecourseowner'])) {
		$this->importowner = $courseowner;
	}
	if ($myrights==100 && empty($this->options['usecourseowner'])) {
		$this->isadmin = true;
	}

	//set course options
	if (!empty($this->options['importcourseopt'])) {
		$this->importCourseOpt();
	}

	//import gbscheme and gbcats if importgbsetup is set
	//  we'll overwrite gbscheme, and delete any existing gbcats
	if (!empty($this->options['importgbsetup'])) {
		$this->importGBsetup();
	}

	//figure out which items to import; establishes $this->itemstoimport
	$this->extractItemsToImport($this->data['course']['itemorder']);

	//import questionset (question code)
	$this->importQuestionSet();

	//group items to export by type
	foreach ($this->itemstoimport as $itemtoimport) {
		if (!isset($this->toimportbytype[$this->data['items'][$itemtoimport]['type']])) {
			$this->toimportbytype[$this->data['items'][$itemtoimport]['type']] = array();
		}
		$this->toimportbytype[$this->data['items'][$itemtoimport]['type']][] = $itemtoimport;
	}

	//insert the inlinetext items
	if (isset($this->toimportbytype['InlineText'])) {
		$this->insertInline();
	}

	//insert the linkedtext items
	if (isset($this->toimportbytype['LinkedText'])) {
		$this->insertLinked();
	}

	//insert the Forum items
	if (isset($this->toimportbytype['Forum'])) {
		$this->insertForum();
	}

	//insert the wiki items
	if (isset($this->toimportbytype['Wiki'])) {
		$this->insertWiki();
	}


	//insert the Drill items
	if (isset($this->toimportbytype['Drill'])) {
		$this->insertDrill();
	}

	//insert the Assessment items
	if (isset($this->toimportbytype['Assessment'])) {
		$this->insertAssessment();
	}

	//add imas_items
	$exarr = array();
	foreach ($this->itemstoimport as $item) {
		$type = $this->data['items'][$item]['type'];
		$exarr[] = $this->cid;
		$exarr[] = $type;
		if ($type=='Calendar') {
			$exarr[] = 0;
		} else {
			$exarr[] = $this->typemap[$type][$item];
		}
	}
	$this->itemmap = array();
	if (count($exarr)>0) {
		$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,3);
		$stm = $DBH->prepare("INSERT INTO imas_items (courseid,itemtype,typeid) VALUES $ph");
		$stm->execute($exarr);
		$firstinsid = $DBH->lastInsertId();
		foreach ($this->itemstoimport as $k=>$tomapid) {
			$this->itemmap[$tomapid] = $firstinsid+$k;
		}
	}
	//add checked items from $this->data itemorder into courseitems and update blockcnt
	$db_fields['block'] = explode(',', $db_fields['block']);
	$this->copysub($this->data['course']['itemorder'], '0', $courseitems);

	//record new itemorder
	$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=?,blockcnt=? WHERE id=?");
	$stm->execute(array(serialize($courseitems), $this->blockcnt, $this->cid));

	//import sticky posts, if present
	if (!empty($this->options['importstickyposts']) && isset($this->data['stickyposts']) && count($this->data['stickyposts'])>0) {
		$this->addStickyPosts();
	}

	//import offline, if present
	if (!empty($this->options['importoffline']) && isset($this->data['offline']) && count($this->data['offline'])>0) {
		$this->addOffline();
	}

	//import calendar items, if present
	if (!empty($this->options['importcalitems']) && isset($this->data['calitems']) && count($this->data['calitems'])>0) {
		$this->addCalitems();
	}

	$DBH->commit();

	return array(
		'Questions Added'=>$this->qsadded,
		'Questions Updated'=>$this->qmodcnt,
		'InlineText Imported'=>count($this->typemap['InlineText']),
		'Linked Imported'=>count($this->typemap['LinkedText']),
		'Forums Imported'=>count($this->typemap['Forum']),
		'Assessments Imported'=>count($this->typemap['Assessment']),
		'Drills Imported'=>count($this->typemap['Drill']),
		'Wikis Imported'=>count($this->typemap['Wiki'])
		);
}

//make a list of items to import
private function extractItemsToImport($items) {
	foreach ($items as $k=>$anitem) {
		if (is_array($anitem)) {
			$this->extractItemsToImport($anitem['items']);
		} else {
			if ($this->checked===true || array_search($anitem,$this->checked)!==FALSE) {
				$this->itemstoimport[] = $anitem;
			}
		}
	}
}
//get a list of questions from an assessment
private function getAssessQids($arr) {
	$qs = array();
	foreach ($arr as $v) {
		if (is_array($v)) {
			for ($i=(strpos($v[0],'|')!==false)?1:0;$i<count($v);$i++) {
				$qs[] = $v[$i];
			}
		} else {
			$qs[] = $v;
		}
	}
	return $qs;
}

//copy over checked items, remapped, into new course itemorder
private function copysub($items,$parent,&$addtoarr) {
	global $db_fields;
	foreach ($items as $k=>$anitem) {
		if (is_array($anitem)) {
			if ($this->checked===true || array_search($parent.'-'.($k+1),$this->checked)!==FALSE) { //copy block
				$newblock = array();
				$newblock['id'] = $this->blockcnt;
				$this->blockcnt++;
				foreach ($db_fields['block'] as $field) {
					$newblock[$field] = $anitem[$field];
				}
				$newblock['items'] = array();
				$this->copysub($anitem['items'],$parent.'-'.($k+1),$newblock['items']);
				$addtoarr[] = $newblock;
			} else {
				$this->copysub($anitem['items'],$parent.'-'.($k+1),$addtoarr);
			}
		} else {
			if ($this->checked===true || array_search($anitem,$this->checked)!==FALSE) {
				$addtoarr[] = $this->itemmap[$anitem];
			}
		}
	}
}

//if the global $mapusers is set, use it to map question owners
private function getMappedOwnerid($listedowner) {
	if (isset($GLOBALS['mapusers']) && isset($GLOBALS['mapusers'][$this->data['sourceinstall']][$listedowner])) {
		return $GLOBALS['mapusers'][$this->data['sourceinstall']][$listedowner];
	} else {
		return $this->importowner;
	}
}

private function importCourseOpt() {
	global $DBH, $db_fields, $CFG;

	$db_fields['course'] = explode(',', $db_fields['course']);
	$sets = array();
	$exarr = array();
	if (!isset($CFG['CPS'])) { $CFG['CPS'] = array();}
	foreach ($db_fields['course'] as $field) {
		//check if in export, and if CFG allows setting
		if (isset($this->data['course'][$field]) && (!isset($CFG['CPS'][$field]) || $CFG['CPS'][$field][1]!=0)) {
			$sets[] = $field.'=?';
			$exarr[] = $this->data['course'][$field];
		}
	}
	if (count($sets)>0) {
		$exarr[] = $this->cid;
		$stm = $DBH->prepare("UPDATE imas_courses SET ".implode(',',$sets)." WHERE id=?");
		$stm->execute($exarr);
	}
}

private function importGBsetup() {
	global $DBH, $db_fields;

	//clear any existing gbcats
	$stm = $DBH->prepare("DELETE FROM imas_gbcats WHERE courseid=?");
	$stm->execute(array($this->cid));

	if (count($this->data['gbcats'])>0) {
		//unset any fields we don't have
		$db_fields['gbcats'] = explode(',', $db_fields['gbcats']);
		//only keep values in db_fields that are also keys of first gb_cat
		$db_fields['gbcats'] = array_values(array_intersect($db_fields['gbcats'], array_keys($this->data['gbcats'][1])));
		$exarr = array();
		foreach ($this->data['gbcats'] as $i=>$row) {
			$exarr[] = $this->cid;
			foreach ($db_fields['gbcats'] as $field) {
				$exarr[] = $row[$field];
			}
		}
		$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,count($db_fields['gbcats'])+1);
		$stm = $DBH->prepare("INSERT INTO imas_gbcats (courseid,".implode(',',$db_fields['gbcats']).") VALUES $ph");
		$stm->execute($exarr);
		$firstgbcat = $DBH->lastInsertId();
		//first gbcat has an index of 1 to distinguish it from default, so need to offset incrementer
		foreach ($this->data['gbcats'] as $i=>$row) {
			$this->gbmap[$i] = $firstgbcat+($i-1);
		}
	}

	//replace gbscheme
	$db_fields['gbscheme'] = explode(',', $db_fields['gbscheme']);
	$sets = array();
	$exarr = array();
	foreach ($db_fields['gbscheme'] as $field) {
		if (isset($this->data['gbscheme'][$field])) {
			$sets[] = $field.'=?';
			$exarr[] = $this->data['gbscheme'][$field];
		}
	}
	if (count($sets)>0) {
		$exarr[] = $this->cid;
		$stm = $DBH->prepare("UPDATE imas_gbscheme SET ".implode(',',$sets)." WHERE courseid=?");
		$stm->execute($exarr);
	}
}

private function importQuestionSet() {
	global $DBH, $db_fields, $myrights;

	if (!isset($this->data['questionset']) || count($this->data['questionset'])==0) {
		return;
	}
	//figure out what questionsets we're importing by looping through items
	$qstoimport = array();
	foreach ($this->itemstoimport as $item) {
		if ($this->data['items'][$item]['type']=='Assessment') {
			$qids = $this->getAssessQids($this->data['items'][$item]['data']['itemorder']);
			foreach ($qids as $qid) {
				$qsid = $this->data['questions'][$qid]['questionsetid'];
				$qstoimport[] = $qsid;
				if (isset($this->data['questionset'][$qsid]['dependencies'])) {
					$qstoimport = array_merge($qstoimport, $this->data['questionset'][$qsid]['dependencies']);
				}
			}
		} else if ($this->data['items'][$item]['type']=='Drill') {
			$qstoimport = array_merge($qstoimport, $this->data['items'][$item]['data']['itemids']);
		}
	}
	if (count($qstoimport)==0) {
		return;
	}
	$qstoimport = array_unique($qstoimport);
	$qsuidmap = array();
	foreach ($qstoimport as $qsid) {
		$qsuidmap[$this->data['questionset'][$qsid]['uniqueid']] = $qsid;
	}

	//prep DB fields
	$db_fields['questionset'] = explode(',', $db_fields['questionset']);
	//only keep values in db_fields that are also keys of first questionset
	if (count($this->data['questionset'])>0) {
		$db_fields['questionset'] = array_values(array_intersect($db_fields['questionset'], array_keys($this->data['questionset'][0])));
	}
	$questionset_sets = implode('=?,', $db_fields['questionset']).'=?';
	$update_qset_stm = $DBH->prepare("UPDATE imas_questionset SET $questionset_sets WHERE id=?");

	//now pull existing questions to setup qsmap. Update as appropriate
	$ph = Sanitize::generateQueryPlaceholders($qsuidmap);
	$stm = $DBH->prepare("SELECT id,uniqueid,lastmoddate,deleted,ownerid,userights FROM imas_questionset WHERE uniqueid IN ($ph)");
	$stm->execute(array_keys($qsuidmap));
	$toresolve = array();
	$qimgs = array();

	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		//set up map of export id => local id
		$exportqid = $qsuidmap[$row['uniqueid']];
		$this->qsmap[$exportqid] = $row['id'];
		$exportlastmod = $this->data['questionset'][$exportqid]['lastmoddate'];
		if ($row['deleted']==1 || ($this->options['update']==2 && $this->isadmin) ||
			($this->options['update']==1 && $exportlastmod>$row['lastmoddate'] && ($row['ownerid']==$this->importowner || $row['userights']>3 || $this->isadmin))) {
			//update question
			$exarr = array();
			if ($row['deleted']==0) {
				//don't change owner unless undeleting
				$this->data['questionset'][$exportqid]['ownerid'] = $row['ownerid'];
			} else {
				$this->data['questionset'][$exportqid]['ownerid'] = $this->getMappedOwnerid($this->data['questionset'][$exportqid]['ownerid']);
			}
			foreach ($db_fields['questionset'] as $field) {
				$exarr[] = $this->data['questionset'][$exportqid][$field];
			}
			$exarr[] = $row['id'];
			$update_qset_stm->execute($exarr);
			$this->qmodcnt++;
			if (isset($this->data['questionset'][$exportqid]['dependencies'])) {
				$toresolve[] = $exportqid;
			}
			if ($this->data['questionset'][$exportqid]['hasimg']==1 && count($this->data['questionset'][$exportqid]['qimgs'])>0) {
				$qimgs[$exportqid] = $this->data['questionset'][$exportqid]['qimgs'];
			}
		}
	}

	//figure out which questions we need to add and add them
	$qstoadd = array_diff($qstoimport, array_keys($this->qsmap));
	$this->qsadded = count($qstoadd);
	$exarr = array();
	$tomap = array();
	foreach ($qstoadd as $exportqid) {
		$tomap[] = $exportqid;
		$this->data['questionset'][$exportqid]['ownerid'] = $this->getMappedOwnerid($this->data['questionset'][$exportqid]['ownerid']);
		$this->data['questionset'][$exportqid]['adddate'] = $this->now;
		$this->data['questionset'][$exportqid]['lastmoddate'] = $this->now;
		if (isset($this->data['questionset'][$exportqid]['dependencies'])) {
			$toresolve[] = $exportqid;
		}
		if ($this->options['userights']>-1) {
			$this->data['questionset'][$exportqid]['userights'] = $this->options['userights'];
		}
		foreach ($db_fields['questionset'] as $field) {
			$exarr[] = $this->data['questionset'][$exportqid][$field];
		}
		if ($this->data['questionset'][$exportqid]['hasimg']==1 && count($this->data['questionset'][$exportqid]['qimgs'])>0) {
			$qimgs[$exportqid] = $this->data['questionset'][$exportqid]['qimgs'];
		}
		if (count($exarr)>2000) { //do a batch add
			$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,count($db_fields['questionset']));
			$stm = $DBH->prepare("INSERT INTO imas_questionset (".implode(',',$db_fields['questionset']).") VALUES $ph");
			$stm->execute($exarr);
			$firstqsid = $DBH->lastInsertId();
			foreach ($tomap as $k=>$tomapeqid) {
				$this->qsmap[$tomapeqid] = $firstqsid+$k;
			}
			$tomap = array();
			$exarr = array();
		}
	}
	if (count($exarr)>0) { //final batch add
		$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,count($db_fields['questionset']));
		$stm = $DBH->prepare("INSERT INTO imas_questionset (".implode(',',$db_fields['questionset']).") VALUES $ph");
		$stm->execute($exarr);
		$firstqsid = $DBH->lastInsertId();
		foreach ($tomap as $k=>$tomapeqid) {
			$this->qsmap[$tomapeqid] = $firstqsid+$k;
		}
	}

	//add question images
	$todelqimg = array();
	$exarr = array();
	foreach ($qimgs as $eqsid=>$qimgarr) {
		$todelqimg[] = $this->qsmap[$eqsid];
		foreach ($qimgarr as $v) {
			//rehost image.  prepend with question ID to prevent conflicts
			$newfn = rehostfile($v['filename'], 'qimages', 'public', $this->qsmap[$eqsid].'-');
			if ($newfn!==false) {
				$exarr[] = $this->qsmap[$eqsid];
				$exarr[] = $v['var'];
				$exarr[] = $newfn;
				$exarr[] = $v['alttext'];
			}
		}
	}
	if (count($exarr)>0) {
		//we'll be lazy and delete any existing qimages for these questions
		$ph = Sanitize::generateQueryPlaceholders($todelqimg);
		$stm = $DBH->prepare("DELETE FROM imas_qimages WHERE qsetid IN ($ph)");
		$stm->execute($todelqimg);
		//insert new qimage records
		$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,4);
		$stm = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename,alttext) VALUES $ph");
		$stm->execute($exarr);
	}

	//add library items for inserted questions
	$exarr = array();
	foreach ($qstoadd as $exportqid) {
		$exarr[] = $this->options['importlib'];
		$exarr[] = $this->qsmap[$exportqid];
		//already remapped ownerid in $this->data for question; use it for lib item too
		$exarr[] = $this->data['questionset'][$exportqid]['ownerid'];
		$exarr[] = $this->now;
	}
	if (count($exarr)>0) {
		$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,4);
		$stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid,lastmoddate) VALUES $ph");
		$stm->execute($exarr);
	}

	//resolve include___from dependencies by updating
	$upd_qset_include = $DBH->prepare("UPDATE imas_questionset SET control=?,qtext=? WHERE id=?");
	foreach ($toresolve as $exportqid) {
		$this->data['questionset'][$exportqid]['control'] = preg_replace_callback('/includecodefrom\(EID(\d+)\)/',
			function($matches) {
				  return "includecodefrom(".$this->qsmap[$matches[1]].")";
			}, $this->data['questionset'][$exportqid]['control']);
		$this->data['questionset'][$exportqid]['qtext'] = preg_replace_callback('/includeqtextfrom\(EID(\d+)\)/',
			function($matches) {
				  return "includeqtextfrom(".$this->qsmap[$matches[1]].")";
			}, $this->data['questionset'][$exportqid]['qtext']);
		$upd_qset_include->execute(array($this->data['questionset'][$exportqid]['control'], $this->data['questionset'][$exportqid]['qtext'], $this->qsmap[$exportqid]));
	}
}

private function insertInline() {
	global $DBH, $db_fields;

	$this->typemap['InlineText'] = array();
	$exarr = array();
	$toresolve = array();
	$db_fields['inlinetext'] = array_values(array_intersect(explode(',', $db_fields['inlinetext']), array_keys($this->data['items'][$this->toimportbytype['InlineText'][0]]['data'])));
	foreach ($this->toimportbytype['InlineText'] as $toimport) {
		$thisinline = $this->data['items'][$toimport]['data'];
		if (is_array($thisinline['fileorder'])) {
			$toresolve[] = $toimport;
			$thisinline['fileorder'] = '';
		}
		//sanitize html fields
		foreach ($db_fields['html']['inlinetext'] as $field) {
			$thisinline[$field] = Sanitize::incomingHtml($thisinline[$field]);
		}
		$exarr[] = $this->cid;
		foreach ($db_fields['inlinetext'] as $field) {
			$exarr[] = $thisinline[$field];
		}
	}
	$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,count($db_fields['inlinetext'])+1);
	$stm = $DBH->prepare("INSERT INTO imas_inlinetext (courseid,".implode(',',$db_fields['inlinetext']).") VALUES $ph");
	$stm->execute($exarr);
	$firstinsid = $DBH->lastInsertId();
	foreach ($this->toimportbytype['InlineText'] as $k=>$toimport) {
		$this->typemap['InlineText'][$toimport] = $firstinsid+$k;
	}

	//resolve any fileorders
	$exarr = array();
	$inlinefilecnt = array();
	foreach ($toresolve as $tohandle) {
		$inlinefilecnt[$tohandle] = 0;
		foreach($this->data['items'][$tohandle]['data']['fileorder'] as $filearr) {
			//rehost file
			$newfn = rehostfile($filearr[1], 'cfiles/'.$this->cid);
			if ($newfn!==false) {
				$exarr[] = $filearr[0]; //description
				$exarr[] = $this->cid.'/'.$newfn; //filename
				$exarr[] = $this->typemap['InlineText'][$tohandle];
				$inlinefilecnt[$tohandle]++;
			}
		}
	}
	if (count($exarr)>0) {
		$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,3);
		$stm = $DBH->prepare("INSERT INTO imas_instr_files (description,filename,itemid) VALUES $ph");
		$stm->execute($exarr);
		$firstinsid = $DBH->lastInsertId();
		$fcnt = 0;
		$inline_file_upd_stm = $DBH->prepare("UPDATE imas_inlinetext SET fileorder=? WHERE id=?");
		foreach ($toresolve as $tohandle) {
			$thisfileorder = array();
			for ($i=0;$i<$inlinefilecnt[$tohandle];$i++) {
				$thisfileorder[] = $firstinsid+$fcnt;
				$fcnt++;
			}
			$inline_file_upd_stm->execute(array(implode(',', $thisfileorder), $this->typemap['InlineText'][$tohandle]));
		}
	}
}

private function insertLinked() {
	global $DBH, $db_fields;

	$this->typemap['LinkedText'] = array();
	$exarr = array();
	$db_fields['linkedtext'] = array_values(array_intersect(explode(',', $db_fields['linkedtext']), array_keys($this->data['items'][$this->toimportbytype['LinkedText'][0]]['data'])));
	foreach ($this->toimportbytype['LinkedText'] as $toimport) {
		if ($this->data['items'][$toimport]['rehostfile']==true && substr($this->data['items'][$toimport]['data']['text'],0,4)=='http') {
			//rehost file and change weblink to file:
			$newfn = rehostfile($this->data['items'][$toimport]['data']['text'], 'cfiles/'.$this->cid);
			if ($newfn!==false) {
				$this->data['items'][$toimport]['data']['text'] = 'file:'.$this->cid.'/'.$newfn;
			}else {
				echo "fail on rehost";
				exit;
			}
		} else if (substr($this->data['items'][$toimport]['data']['text'],0,8)=='exttool:') {
			//remap gbcategory
			$parts = explode('~~',substr($this->data['items'][$toimport]['data']['text'],8));
			if (isset($parts[3])) { //has gbcategory
				if (isset($this->gbmap[$parts[3]])) {
					$parts[3] = $this->gbmap[$parts[3]];
				} else {
					$parts[3] = 0;
				}
				$this->data['items'][$toimport]['data']['text'] = 'exttool:'.implode('~~',$parts);
			}
		}
		//sanitize html fields
		foreach ($db_fields['html']['linkedtext'] as $field) {
			$this->data['items'][$toimport]['data'][$field] = Sanitize::incomingHtml($this->data['items'][$toimport]['data'][$field]);
		}
		$exarr[] = $this->cid;
		foreach ($db_fields['linkedtext'] as $field) {
			$exarr[] = $this->data['items'][$toimport]['data'][$field];
		}
	}
	$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,count($db_fields['linkedtext'])+1);
	$stm = $DBH->prepare("INSERT INTO imas_linkedtext (courseid,".implode(',',$db_fields['linkedtext']).") VALUES $ph");
	$stm->execute($exarr);
	$firstinsid = $DBH->lastInsertId();
	foreach ($this->toimportbytype['LinkedText'] as $k=>$toimport) {
		$this->typemap['LinkedText'][$toimport] = $firstinsid+$k;
	}
}

private function insertForum() {
	global $DBH, $db_fields;

	$this->typemap['Forum'] = array();
	$exarr = array();
	$db_fields['forum'] = array_values(array_intersect(explode(',', $db_fields['forum']), array_keys($this->data['items'][$this->toimportbytype['Forum'][0]]['data'])));
	foreach ($this->toimportbytype['Forum'] as $toimport) {
		if (isset($this->gbmap[$this->data['items'][$toimport]['data']['gbcategory']])) {
			$this->data['items'][$toimport]['data']['gbcategory'] = $this->gbmap[$this->data['items'][$toimport]['data']['gbcategory']];
		} else {
			$this->data['items'][$toimport]['data']['gbcategory'] = 0;
		}
		//sanitize html fields
		foreach ($db_fields['html']['forum'] as $field) {
			$this->data['items'][$toimport]['data'][$field] = Sanitize::incomingHtml($this->data['items'][$toimport]['data'][$field]);
		}
		$exarr[] = $this->cid;
		foreach ($db_fields['forum'] as $field) {
			$exarr[] = $this->data['items'][$toimport]['data'][$field];
		}
	}
	$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,count($db_fields['forum'])+1);
	$stm = $DBH->prepare("INSERT INTO imas_forums (courseid,".implode(',',$db_fields['forum']).") VALUES $ph");
	$stm->execute($exarr);
	$firstfid = $DBH->lastInsertId();
	foreach ($this->toimportbytype['Forum'] as $k=>$toimport) {
		$this->typemap['Forum'][$toimport] = $firstfid+$k;
	}
}

private function insertWiki() {
	global $DBH, $db_fields;

	$this->typemap['Wiki'] = array();
	$exarr = array();
	$db_fields['wiki'] = array_values(array_intersect(explode(',', $db_fields['wiki']), array_keys($this->data['items'][$this->toimportbytype['Wiki'][0]]['data'])));
	foreach ($this->toimportbytype['Wiki'] as $toimport) {
		$exarr[] = $this->cid;
		//sanitize html fields
		foreach ($db_fields['html']['wiki'] as $field) {
			$this->data['items'][$toimport]['data'][$field] = Sanitize::incomingHtml($this->data['items'][$toimport]['data'][$field]);
		}
		foreach ($db_fields['wiki'] as $field) {
			$exarr[] = $this->data['items'][$toimport]['data'][$field];
		}
	}
	$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,count($db_fields['wiki'])+1);
	$stm = $DBH->prepare("INSERT INTO imas_wikis (courseid,".implode(',',$db_fields['wiki']).") VALUES $ph");
	$stm->execute($exarr);
	$firstinsid = $DBH->lastInsertId();
	foreach ($this->toimportbytype['Wiki'] as $k=>$toimport) {
		$this->typemap['Wiki'][$toimport] = $firstinsid+$k;
	}
}

private function insertDrill() {
	global $DBH, $db_fields;

	$this->typemap['Drill'] = array();
	$exarr = array();
	$db_fields['drill'] = array_values(array_intersect(explode(',', $db_fields['drill']), array_keys($this->data['items'][$this->toimportbytype['Drill'][0]]['data'])));
	foreach ($this->toimportbytype['Drill'] as $toimport) {
		//map itemids then implode
		$newitems = array();
		foreach ($this->data['items'][$toimport]['data']['itemids'] as $eqsid) {
			$newitems[] = $this->qsmap[$eqsid];
		}
		$this->data['items'][$toimport]['data']['itemids'] = implode(',', $newitems);
		//sanitize html fields
		foreach ($db_fields['html']['drill'] as $field) {
			$this->data['items'][$toimport]['data'][$field] = Sanitize::incomingHtml($this->data['items'][$toimport]['data'][$field]);
		}
		$exarr[] = $this->cid;
		foreach ($db_fields['drill'] as $field) {
			$exarr[] = $this->data['items'][$toimport]['data'][$field];
		}
	}
	$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,count($db_fields['drill'])+1);
	$stm = $DBH->prepare("INSERT INTO imas_drillassess (courseid,".implode(',',$db_fields['drill']).") VALUES $ph");
	$stm->execute($exarr);
	$firstinsid = $DBH->lastInsertId();
	foreach ($this->toimportbytype['Drill'] as $k=>$toimport) {
		$this->typemap['Drill'][$toimport] = $firstinsid+$k;
	}
}

private function insertAssessment() {
	global $DBH, $db_fields;

	$this->typemap['Assessment'] = array();
	$exarr = array();
	$db_fields['assessment'] = array_values(array_intersect(explode(',', $db_fields['assessment']), array_keys($this->data['items'][$this->toimportbytype['Assessment'][0]]['data'])));
	$contentlen = 0;
	$tomap = array();
	foreach ($this->toimportbytype['Assessment'] as $toimport) {
		$tomap[] = $toimport;
		$thisitemdata = $this->data['items'][$toimport]['data'];

		//map gbcategory
		if (isset($this->gbmap[$thisitemdata['gbcategory']])) {
			$thisitemdata['gbcategory'] = $this->gbmap[$thisitemdata['gbcategory']];
		} else {
			$thisitemdata['gbcategory'] = 0;
		}
		//map posttoforum
		if ($thisitemdata['posttoforum']>0) {
			if (isset($this->typemap['Forum'][$thisitemdata['posttoforum']])) {
				$thisitemdata['posttoforum'] = $this->typemap['Forum'][$thisitemdata['posttoforum']];
			} else {
				$thisitemdata['posttoforum'] = 0;
			}
		}
		//sanitize html fields
		foreach ($db_fields['html']['assessment'] as $field) {
			$thisitemdata[$field] = Sanitize::incomingHtml($thisitemdata[$field]);
		}
		//sanitize intro field, which may be json
		$introjson = json_decode($thisitemdata['intro'], true);
		if ($introjson===null) {
			//regular intro
			$thisitemdata['intro'] = Sanitize::incomingHtml($thisitemdata['intro']);
		} else {
			$introjson[0] = Sanitize::incomingHtml($introjson[0]);
			for ($i=1;$i<count($introjson);$i++) {
				$introjson[$i]['text'] = Sanitize::incomingHtml($introjson[$i]['text']);
			}
			$thisitemdata['intro'] = json_encode($introjson);
		}
		//Sanitize endmsg
		if (is_array($thisitemdata['endmsg'])) {
			$endmsgdata = $thisitemdata['endmsg'];
			$endmsgdata['commonmsg'] = Sanitize::incomingHtml($endmsgdata['commonmsg']);
			$endmsgdata['def'] = Sanitize::incomingHtml($endmsgdata['def']);
			foreach (array_keys($endmsgdata['msgs']) as $k) {
				$endmsgdata['msgs'][$k] = Sanitize::incomingHtml($endmsgdata['msgs'][$k]);
			}
			$thisitemdata['endmsg'] = serialize($endmsgdata);
		} else {
			$thisitemdata['endmsg'] = '';
		}
		//we'll resolve these later
		$thisitemdata['reqscoreaid'] = 0;
		$thisitemdata['itemorder'] = '';
		$contentlen += strlen($thisitemdata['intro']);
		$exarr[] = $this->cid;
		foreach ($db_fields['assessment'] as $field) {
			$exarr[] = $thisitemdata[$field];
		}
		if ($contentlen>5E5) { //do a batch add if more than 500,000 chars in intro
			$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,count($db_fields['assessment'])+1);
			$stm = $DBH->prepare("INSERT INTO imas_assessments (courseid,".implode(',',$db_fields['assessment']).") VALUES $ph");
			$stm->execute($exarr);
			$firstaid = $DBH->lastInsertId();
			foreach ($tomap as $k=>$tomapid) {
				$this->typemap['Assessment'][$tomapid] = $firstaid+$k;
			}
			$tomap = array();
			$exarr = array();
		}
	}
	if (count($exarr)>0) { //do final batch
		$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,count($db_fields['assessment'])+1);
		$stm = $DBH->prepare("INSERT INTO imas_assessments (courseid,".implode(',',$db_fields['assessment']).") VALUES $ph");
		$stm->execute($exarr);
		$firstaid = $DBH->lastInsertId();
		foreach ($tomap as $k=>$tomapid) {
			$this->typemap['Assessment'][$tomapid] = $firstaid+$k;
		}
	}

	//now, insert questions
	$db_fields['questions'] = explode(',', $db_fields['questions']);
	//only keep values in db_fields that are also keys of first question
	if (count($this->data['questions'])>0) {
		$db_fields['questions'] = array_values(array_intersect($db_fields['questions'], array_keys($this->data['questions'][0])));
	}
	$this->qmap = array();
	$qpoints = array();
	foreach ($this->toimportbytype['Assessment'] as $toimport) {
		$tomap = array();
		$qids = $this->getAssessQids($this->data['items'][$toimport]['data']['itemorder']);
		$exarr = array();
		foreach ($qids as $qid) {
			$tomap[] = $qid;
			//remap questionsetid
			$this->data['questions'][$qid]['questionsetid'] = $this->qsmap[$this->data['questions'][$qid]['questionsetid']];
			//record points if needed
			if ($this->data['questions'][$qid]['points']<9999) {
				$qpoints[$qid] = data['questions'][$qid]['points'];
			}
			//add in assessmentid
			$exarr[] = $this->typemap['Assessment'][$toimport];
			foreach ($db_fields['questions'] as $field) {
				$exarr[] = $this->data['questions'][$qid][$field];
			}
		}
		if (count($exarr)>0) {
			$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr,count($db_fields['questions'])+1);
			$stm = $DBH->prepare("INSERT INTO imas_questions (assessmentid,".implode(',',$db_fields['questions']).") VALUES $ph");
			$stm->execute($exarr);
			$firstqid = $DBH->lastInsertId();
			foreach ($tomap as $k=>$tomapid) {
				$this->qmap[$tomapid] = $firstqid+$k;
			}
		}
	}

	//resolve itemorder and reqscoreaid
	$a_upd_stm = $DBH->prepare("UPDATE imas_assessments SET reqscoreaid=?,itemorder=?,ptsposs=? WHERE id=?");
	foreach ($this->toimportbytype['Assessment'] as $toimport) {
		$thisitemdata = $this->data['items'][$toimport]['data'];
		//remap reqscoreaid
		if ($thisitemdata['reqscoreaid']>0) {
			$rsaid = $this->typemap['Assessment'][$thisitemdata['reqscoreaid']];
		} else {
			$rsaid = 0;
		}
		//remap itemorder and collapse
		$mappedqpoints = array();
		$aitems = $thisitemdata['itemorder'];
		foreach ($aitems as $i=>$q) {
			if (is_array($q)) {
				foreach ($q as $k=>$subq) {
					if ($k==0 && strpos($subq,'|')!==false) {continue;}
					if (isset($qpoints[$subq])) {
						$mappedqpoints[$this->qmap[$subq]] = $qpoints[$subq];
					}
					$q[$k] = $this->qmap[$q[$k]];
				}
				$aitems[$i] = implode('~',$q);
			} else {
				$aitems[$i] = $this->qmap[$q];
				if (isset($qpoints[$q])) {
					$mappedqpoints[$this->qmap[$q]] = $qpoints[$q];
				}
			}
		}
		$aitemorder = implode(',', $aitems);
		if (!isset($thisitemdata['ptsposs']) || $thisitemdata['ptsposs']==-1) {
			$thisitemdata['ptsposs'] = calcPointsPossible($aitemorder, $mappedqpoints, $thisitemdata['defpoints']);
		}
		$a_upd_stm->execute(array($rsaid, $aitemorder, $thisitemdata['ptsposs'], $this->typemap['Assessment'][$toimport]));
	}
}

private function addStickyPosts() {
	global $DBH, $db_fields;

	$db_fields['forum_posts'] = explode(',', $db_fields['forum_posts']);
	//only keep values in db_fields that are also keys of first question
	$db_fields['forum_posts'] = array_values(array_intersect($db_fields['forum_posts'], array_keys($this->data['stickyposts'][0])));
	$exarr = array();
	foreach ($this->data['stickyposts'] as $toimport) {
		//skip if didn't import forum
		if (!isset($this->typemap['Forum'][$toimport['forumid']])) {
			continue;
		}
		//remap forumid
		$toimport['forumid'] = $this->typemap['Forum'][$toimport['forumid']];
		//sanitize html fields
		foreach ($db_fields['html']['forum_posts'] as $field) {
			$toimport[$field] = Sanitize::incomingHtml($toimport[$field]);
		}
		//rehost files
		if (is_array($toimport['files'])) {
			$newfiles = array();
			for ($i=0;$i<count($toimport['files'])/2;$i++) {
				$newfn = rehostfile($toimport['files'][2*$i+1], 'ffiles/'.$this->typemap['Forum'][$toimport['forumid']]);
				if ($newfn!==false) {
					$newfiles[2*$i] = $toimport['files'][2*$i];
					$newfiles[2*$i+1] = $newfn;
				}
			}
			$toimport['files'] = implode('@@', $newfiles);
		} else {
			$toimport['files'] = '';
		}
		//add in owner and postdate, then rest of fields
		$exarr[] = $this->importowner;
		$exarr[] = $this->now;
		foreach ($db_fields['forum_posts'] as $field) {
			$exarr[] = $toimport[$field];
		}
	}
	if (count($exarr)==0) {
		return;
	}
	$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr, count($db_fields['forum_posts'])+2);
	$stm = $DBH->prepare("INSERT INTO imas_forum_posts (userid,postdate,".implode(',',$db_fields['forum_posts']).") VALUES $ph");
	$stm->execute($exarr);
	$firstinsid = $DBH->lastInsertId();

	//now insert corresponding imas_forum_threads entries
	$exarr = array();
	foreach ($this->data['stickyposts'] as $k=>$toimport) {
		array_push($exarr, $firstinsid+$k, $this->typemap['Forum'][$toimport['forumid']], $this->now, $this->importowner);
	}
	$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr, 4);
	$stm = $DBH->prepare("INSERT INTO imas_forum_threads (id,forumid,lastposttime,lastpostuser) VALUES $ph");
	$stm->execute($exarr);

	//resolve threadid in imas_forum_posts in a lazy global way
	$stm = $DBH->query("UPDATE imas_forum_posts SET threadid=id WHERE threadid=0 AND parent=0");
}

private function addOffline() {
	global $DBH, $db_fields;

	$db_fields['offline'] = explode(',', $db_fields['offline']);
	//only keep values in db_fields that are also keys of first question
	$db_fields['offline'] = array_values(array_intersect($db_fields['offline'], array_keys($this->data['offline'][0])));
	$exarr = array();
	foreach ($this->data['offline'] as $toimport) {
		//remap gbcat
		if (isset($this->gbmap[$toimport['gbcategory']])) {
			$toimport['gbcategory'] = $this->gbmap[$toimport['gbcategory']];
		} else {
			$toimport['gbcategory'] = 0;
		}
		$exarr[] = $this->cid;
		foreach ($db_fields['offline'] as $field) {
			$exarr[] = $toimport[$field];
		}
	}
	$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr, count($db_fields['offline'])+1);
	$stm = $DBH->prepare("INSERT INTO imas_gbitems (courseid,".implode(',',$db_fields['offline']).") VALUES $ph");
	$stm->execute($exarr);
}

private function addCalitems() {
	global $DBH, $db_fields;
	$db_fields['calitems'] = explode(',', $db_fields['calitems']);
	//only keep values in db_fields that are also keys of first question
	$db_fields['calitems'] = array_values(array_intersect($db_fields['calitems'], array_keys($this->data['calitems'][0])));
	$exarr = array();
	foreach ($this->data['calitems'] as $toimport) {
		$exarr[] = $this->cid;
		foreach ($db_fields['calitems'] as $field) {
			$exarr[] = $toimport[$field];
		}
	}
	$ph = Sanitize::generateQueryPlaceholdersGrouped($exarr, count($db_fields['calitems'])+1);
	$stm = $DBH->prepare("INSERT INTO imas_calitems (courseid,".implode(',',$db_fields['calitems']).") VALUES $ph");
	$stm->execute($exarr);
}

} //end class
