<?php
//IMathAS:  Course item export
//JSON edition
//(c) 2017 David Lippman

//boost operation time
@set_time_limit(0);
ini_set("max_input_time", "900");
ini_set("max_execution_time", "900");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

/*** master php includes *******/
require("../init.php");
require_once("../includes/filehandler.php");
require("../includes/copyiteminc.php");
require("../includes/loaditemshowdata.php");
require("itemexportfields.php");

$db_fields['block'] = explode(',', $db_fields['block']);


/*** pre-html data manipulation, including function code *******/

//create item structure based on selected items
function exportcopysub($items,$parent,&$addtoarr) {
	global $itemcnt,$toexport,$itembackmap,$db_fields;
	global $checked;
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			if (array_search($parent.'-'.($k+1),$checked)!==FALSE) { //copy block
				$newblock = array();
				foreach ($db_fields['block'] as $field) {
					$newblock[$field] = $item[$field];
				}
				$newblock['items'] = array();
				exportcopysub($item['items'],$parent.'-'.($k+1),$newblock['items']);
				$addtoarr[] = $newblock;
			} else {
				exportcopysub($item['items'],$parent.'-'.($k+1),$addtoarr);
			}
		} else {
			if (array_search($item,$checked)!==FALSE) {
				$toexport[$itemcnt] = $item;
				$itembackmap[$item] = $itemcnt;
				$addtoarr[] = $itemcnt;
				$itemcnt++;
			}
		}
	}
}

$overwriteBody = 0;
$body = "";
$pagetitle = $installname . " Item Export";
$cid = Sanitize::courseId($_GET['cid']);
$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">"
	. Sanitize::encodeStringForDisplay($coursename) . "</a> &gt; Export Course Items</div>\n";

if (!(isset($teacherid))) {   //NO PERMISSIONS
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_POST['export'])) { //STEP 2 DATA PROCESSING, OUTPUT FILE HERE
	header('Content-type: text/imas');
	header("Content-Disposition: attachment; filename=\"imasitemexport-$cid.imas\"");

	$checked = $_POST['checked'];
	$output = array();
	$output['sourceinstall'] = $installname;

	//get gbcats
	$gbcats = array(); $gbmap = array(0=>0);
	if (isset($_POST['exportgbsetup'])) {
		$stm = $DBH->prepare("SELECT id,".$db_fields['gbcats']." FROM imas_gbcats WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbcnt = 1;
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$gbmap[$row['id']] = $gbcnt;
			unset($row['id']);
			$gbcats[$gbcnt] = $row;
			$gbcnt++;
		}
		$output['gbcats'] = $gbcats;

		$stm = $DBH->prepare("SELECT ".$db_fields['gbscheme']." FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		$output['gbscheme'] = $row;
	}

	//get itemorder
	if (isset($_POST['exportcourseopt'])) {
		$stm = $DBH->prepare("SELECT itemorder,ownerid,name,".$db_fields['course']." FROM imas_courses WHERE id=:id");
	} else {
		$stm = $DBH->prepare("SELECT itemorder,ownerid,name FROM imas_courses WHERE id=:id");
	}
	$stm->execute(array(':id'=>$cid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	$items = unserialize($line['itemorder']);
	$itemcnt = 1;
	$toexport = array();
	$itembackmap = array();
	$qcnt = 0;

	//build new itemorder only including checked items
	exportcopysub($items,'0',$newitems);

	//save into line, and into output
	$line['itemorder'] = $newitems;
	$output['course'] = $line;
	$output['items'] = array();
	//ref item info back to reference in output itemorder
	$itemtypebackref = array();
	$stm = $DBH->prepare("SELECT id,itemtype,typeid FROM imas_items WHERE courseid=:id");
	$stm->execute(array(':id'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($itembackmap[$row['id']])) { continue; } //not exporting it
		if ($row['itemtype'] == 'Calendar') {
			$output['items'][$itembackmap[$row['id']]] = array('type'=>'Calendar');
		} else {
			if (!isset($itemtypebackref[$row['itemtype']])) {
				$itemtypebackref[$row['itemtype']] = array();
			}
			$itemtypebackref[$row['itemtype']][$row['typeid']] = $itembackmap[$row['id']];
		}
	}

	//get instr_files
	$query = "SELECT iif.id,iif.description,iif.filename FROM imas_instr_files AS iif ";
	$query .= "JOIN imas_inlinetext ON iif.itemid=imas_inlinetext.id WHERE imas_inlinetext.courseid=:cid";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':cid'=>$cid));
	$instr_file_data = array();
	while ($frow = $stm->fetch(PDO::FETCH_ASSOC)) {
		$instr_file_data[$frow['id']] = array($frow['description'], getcoursefileurl($frow['filename'],true));
	}

	//get inlinetexts
	if (isset($itemtypebackref['InlineText'])) {
		$toget = array_keys($itemtypebackref['InlineText']);
		$ph = Sanitize::generateQueryPlaceholders($toget);
		$stm = $DBH->prepare("SELECT id,".$db_fields['inlinetext']." FROM imas_inlinetext WHERE id IN ($ph)");
		$stm->execute($toget);
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$output_item_id = $itemtypebackref['InlineText'][$line['id']];
			unset($line['id']);
			//replace fileorder with array of instructor file info if set
			if ($line['fileorder']!='') {
				$newfileorder = array();
				foreach (explode(',',$line['fileorder']) as $fid) {
					if (!isset($instr_file_data[$fid])) {continue;}
					$newfileorder[] = $instr_file_data[$fid];
				}
				$line['fileorder'] = $newfileorder;
			}
			$output['items'][$output_item_id] = array('type'=>'InlineText', 'data'=>$line);
		}
	}

	//get linkedtexts
	if (isset($itemtypebackref['LinkedText'])) {
		$toget = array_keys($itemtypebackref['LinkedText']);
		$ph = Sanitize::generateQueryPlaceholders($toget);
		$stm = $DBH->prepare("SELECT id,".$db_fields['linkedtext']." FROM imas_linkedtext WHERE id IN ($ph)");
		$stm->execute($toget);
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$output_item_id = $itemtypebackref['LinkedText'][$line['id']];
			unset($line['id']);
			$rehostfile = false;
			//replace file with URL
			if (substr($line['text'],0,5)=='file:') {
				$line['text'] = getcoursefileurl(trim(substr(strip_tags($line['text']),5)),true);
				$rehostfile = true;
			} else if (substr($line['text'],0,8)=='exttool:') {  //remap gbcat
				$parts = explode('~~',substr($line['text'],8));
				if (isset($parts[3])) { //has gbcategory
					if (isset($gbmap[$parts[3]])) {
						$parts[3] = $gbmap[$parts[3]];
					} else {
						$parts[3] = 0;
					}
					$line['text'] = 'exttool:'.implode('~~',$parts);
				}
			}
			$output['items'][$output_item_id] = array('type'=>'LinkedText', 'data'=>$line, 'rehostfile'=>$rehostfile);
		}
	}

	//get forums
	if (isset($itemtypebackref['Forum'])) {
		$toget = array_keys($itemtypebackref['Forum']);
		$ph = Sanitize::generateQueryPlaceholders($toget);
		$stm = $DBH->prepare("SELECT id,".$db_fields['forum']." FROM imas_forums WHERE id IN ($ph)");
		$stm->execute($toget);
		$forummap = array();
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$output_item_id = $itemtypebackref['Forum'][$line['id']];
			$forummap[$line['id']] = $output_item_id;
			unset($line['id']);
			//map gbcategory
			if (isset($gbmap[$line['gbcategory']])) {
				$line['gbcategory'] = $gbmap[$line['gbcategory']];
			} else {
				$line['gbcategory'] = 0;
			}
			$output['items'][$output_item_id] = array('type'=>'Forum', 'data'=>$line);
		}

		//export "sticky" forum posts
		if (isset($_POST['exportstickyposts'])) {
			$output['stickyposts'] = array();
			$stm = $DBH->prepare("SELECT ".$db_fields['forum_posts']." FROM imas_forum_posts WHERE forumid in ($ph) AND posttype>0");
			$stm->execute($toget);
			while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
				//handle files: convert to array and get abs URLs
				if ($line['files']!='') {
					$files = explode('@@',$line['files']);
					for ($i=0;$i<count($files)/2;$i++) {
						$files[2*$i+1] = getuserfileurl('ffiles/'.$line['forumid'].'/'.$files[2*$i+1]);
					}
					$line['files'] = $files;
				}
				//remap forum id
				$line['forumid'] = $forummap[$line['forumid']];
				$output['stickyposts'][] = $line;
			}
		}
	}


	//get wikis
	if (isset($itemtypebackref['Wiki'])) {
		$toget = array_keys($itemtypebackref['Wiki']);
		$ph = Sanitize::generateQueryPlaceholders($toget);
		$stm = $DBH->prepare("SELECT id,".$db_fields['wiki']." FROM imas_wikis WHERE id IN ($ph)");
		$stm->execute($toget);
		$wikimap = array();
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$output_item_id = $itemtypebackref['Wiki'][$line['id']];
			unset($line['id']);
			$output['items'][$output_item_id] = array('type'=>'Wiki', 'data'=>$line);
		}
	}

	//get imas_questions
	if (isset($itemtypebackref['Assessment'])) {
		$toget = array_keys($itemtypebackref['Assessment']);
		$ph = Sanitize::generateQueryPlaceholders($toget);
		$db_fields['questions'] = explode(',',$db_fields['questions']);
		foreach ($db_fields['questions'] as $k=>$v) {
			$db_fields['questions'][$k] = 'imas_questions.'.$v;
		}
		$db_fields['questions'] = implode(',',$db_fields['questions']);
		$query = "SELECT imas_questions.id,imas_questions.withdrawn,".$db_fields['questions']." FROM imas_questions ";
		$query .= "JOIN imas_assessments ON imas_questions.assessmentid=imas_assessments.id ";
		$query .= "WHERE imas_assessments.id IN ($ph)";
		$stm = $DBH->prepare($query);
		$stm->execute($toget);
		$qmap = array();
		$qsmap = array();
		$qscnt = 0; $qcnt = 0;
		$output['questions'] = array();
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$qid = $line['id'];
			unset($line['id']);
			//skip if withdrawn
			if ($line['withdrawn']==1) {
				continue;
			} else {
				unset($line['withdrawn']);
			}
			//unset category if outcome or aid
			if ($line['category']!='' && (is_numeric($line['category']) || 0==strncmp($line['category'],"AID-",4))) {
				$line['category'] = '';
			}
			//remap questionsetid
			if (isset($qsmap[$line['questionsetid']])) {
				//already have this qsetid - use existing map value
				$line['questionsetid'] = $qsmap[$line['questionsetid']];
			} else {
				//add to map and output
				$qsmap[$line['questionsetid']] = $qscnt;
				$line['questionsetid'] = $qscnt;
				$qscnt++;
			}
			$output['questions'][$qcnt] = $line;
			$qmap[$qid] = $qcnt;
			$qcnt++;
		}

		//get imas_assessments
		$stm = $DBH->prepare("SELECT id,".$db_fields['assessment']." FROM imas_assessments WHERE id IN ($ph)");
		$stm->execute($toget);
		$assessmap = array();
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$output_item_id = $itemtypebackref['Assessment'][$line['id']];
			$assessmap[$line['id']] = $output_item_id;
			//check for ptsposs
			if (isset($line['ptsposs']) && $line['ptsposs']==-1) {
				$line['ptsposs'] = updatePointsPossible($line['id'], $line['itemorder'], $line['defpoints']);	
			}
			
			unset($line['id']);
			//map gbcategory
			if (isset($gbmap[$line['gbcategory']])) {
				$line['gbcategory'] = $gbmap[$line['gbcategory']];
			} else {
				$line['gbcategory'] = 0;
			}
			//map posttoforum
			if ($line['posttoforum']>0) {
				if (isset($forummap[$line['posttoforum']])) {
					$line['posttoforum'] = $forummap[$line['posttoforum']];
				} else {
					$line['posttoforum'] = 0;
				}
			}
			//unserialize endmsg
			if ($line['endmsg']!='') {
				$line['endmsg'] = unserialize($line['endmsg']);
			}
			//change itemorder into an array and remap question id
			if ($line['itemorder']=='') {
				$qs = array();
			} else {
				$qs = explode(',',$line['itemorder']);
			}
			$neworder = array();
			foreach ($qs as $q) {
				if (strpos($q,'~')===FALSE) {
					if (isset($qmap[$q])) { //account for removal of withdrawn
						$neworder[] = $qmap[$q];
					}
				} else {
					$newsub = array();
					$subs = explode('~',$q);
					$typeshift = 0;
					if (strpos($subs[0],'|')!==false) {
						$newsub[] = $subs[0];
						array_shift($subs);
						$typeshift = 1;
					}
					foreach($subs as $subq) {
						if (isset($qmap[$subq])) { //account for removal of withdrawn
							$newsub[] = $qmap[$subq];
						}
					}
					if (count($newsub)==0+$typeshift) {
						//skip it entirely - no items
					} else if (count($newsub)==1+$typeshift) {
						//one item, ungroup
						$neworder[] = $newsub[0+$typeshift];
					} else {
						$neworder[] = $newsub;
					}
				}
			}
			$line['itemorder'] = $neworder;
			$output['items'][$output_item_id] = array('type'=>'Assessment', 'data'=>$line);
		}
		//remap reqscoreaid
		foreach ($assessmap as $sourceid=>$outputid) {
			if ($output['items'][$outputid]['data']['reqscoreaid']>0) {
				if (isset($assessmap[$output['items'][$outputid]['data']['reqscoreaid']])) {
					$output['items'][$outputid]['data']['reqscoreaid'] = $assessmap[$output['items'][$outputid]['data']['reqscoreaid']];
				} else {
					//unset
					$output['items'][$outputid]['data']['reqscoreaid'] = 0;
				}
			}
		}
	}

	//get drills
	if (isset($itemtypebackref['Drill'])) {
		$toget = array_keys($itemtypebackref['Drill']);
		$ph = Sanitize::generateQueryPlaceholders($toget);
		$stm = $DBH->prepare("SELECT id,".$db_fields['drill']." FROM imas_drillassess WHERE id IN ($ph)");
		$stm->execute($toget);
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$output_item_id = $itemtypebackref['Drill'][$line['id']];
			unset($line['id']);
			//go through questions
			if ($line['itemids']=='') {
				$itemids = array();
			} else {
				$itemids = explode(',',$line['itemids']);
			}
			foreach ($itemids as $k=>$qsid) {
				//remap questionsetid
				if (isset($qsmap[$qsid])) {
					//already have this qsetid - use existing map value
					$itemids[$k] = $qsmap[$qsid];
				} else {
					//add to map and output
					$qsmap[$qsid] = $qscnt;
					$itemids[$k] = $qscnt;
					$qscnt++;
				}
			}
			$line['itemids'] = $itemids;
			$output['items'][$output_item_id] = array('type'=>'Drill', 'data'=>$line);
		}
	}

	//now, get questions
	if (count($qsmap)>0) {
		//resolve any replaceby's
		$toget = array_keys($qsmap);
		$ph = Sanitize::generateQueryPlaceholders($toget);
		$stm = $DBH->prepare("SELECT id,replaceby FROM imas_questionset WHERE id IN ($ph) AND replaceby>0");
		$stm->execute($toget);
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$endpoint = $qsmap[$line['id']];
			unset($qsmap[$line['id']]);
			$qsmap[$line['replaceby']] = $endpoint;
		}
		$toget = array_keys($qsmap);
		$ph = Sanitize::generateQueryPlaceholders($toget);

		//look up any include___from's
		$query = "SELECT id,control,qtext FROM imas_questionset WHERE id IN ($ph)";
		$query .= " AND (control LIKE '%includecodefrom%' OR qtext LIKE '%includeqtextfrom%')";
		$stm = $DBH->prepare($query);
		$stm->execute($toget);
		$includedqs = array();
		$dependencies = array();
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$dependencies[$line['id']] = array();
			if (preg_match_all('/includecodefrom\((\d+)\)/',$line['control'],$matches,PREG_PATTERN_ORDER) >0) {
				$includedqs = array_merge($includedqs,$matches[1]);
				$dependencies[$line['id']] = array_merge($dependencies[$line['id']], $matches[1]);
			}
			if (preg_match_all('/includeqtextfrom\((\d+)\)/',$line['qtext'],$matches,PREG_PATTERN_ORDER) >0) {
				$includedqs = array_merge($includedqs,$matches[1]);
				$dependencies[$line['id']] = array_unique(array_merge($dependencies[$line['id']], $matches[1]));
			}
		}
		$qstoadd = array_diff(array_unique($includedqs),$toget);
		foreach ($qstoadd as $v) {
			$qsmap[$v] = $qscnt;
			$qscnt++;
		}

		$toget = array_keys($qsmap);
		$ph = Sanitize::generateQueryPlaceholders($toget);
		//get qimages
		$qimgmap = array();
		$stm = $DBH->prepare("SELECT qsetid,var,filename,alttext FROM imas_qimages WHERE qsetid IN ($ph)");
		$stm->execute($toget);
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$qsid = $line['qsetid'];
			unset($line['qsetid']);
			if (!isset($qimgmap[$qsid])) {
				$qimgmap[$qsid] = array();
			}
			$line['filename'] = getqimageurl($line['filename'],true);
			$qimgmap[$qsid][] = $line;
		}

		//now, get questions themselves
		$stm = $DBH->prepare("SELECT id,".$db_fields['questionset']." FROM imas_questionset WHERE id IN ($ph)");
		$stm->execute($toget);
		$output['questionset'] = array();
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$output_qid = $qsmap[$line['id']];
			//handle qimages
			if ($line['hasimg']==1) {
				$line['qimgs'] = $qimgmap[$line['id']];
			}
			if (isset($dependencies[$line['id']])) {
				foreach ($dependencies[$line['id']] as $k=>$v) {
					//remap dependencies
					$dependencies[$line['id']][$k] = $qsmap[$v];
				}
				$line['dependencies'] = $dependencies[$line['id']];
			}
			unset($line['id']);

			//rewrite includecodefrom
			$line['control'] = preg_replace_callback('/includecodefrom\((\d+)\)/', function($matches) use ($qsmap) {
				  return "includecodefrom(EID".$qsmap[$matches[1]].")";
				}, $line['control']);
			$line['qtext'] = preg_replace_callback('/includeqtextfrom\((\d+)\)/', function($matches) use ($qsmap) {
				  return "includeqtextfrom(EID".$qsmap[$matches[1]].")";
				}, $line['qtext']);

			$output['questionset'][$output_qid] = $line;
		}
	}

	if (isset($_POST['exportoffline'])) {
		$output['offline'] = array();
		$stm = $DBH->prepare("SELECT ".$db_fields['offline']." FROM imas_gbitems WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			//map gbcat
			$line['gbcategory'] = $gbmap[$line['gbcategory']];
			$output['offline'][] = $line;
		}
	}
	if (isset($_POST['exportcalitems'])) {
		$output['calitems'] = array();
		$stm = $DBH->prepare("SELECT ".$db_fields['calitems']." FROM imas_calitems WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$output['calitems'][] = $line;
		}
	}

	//dump it!
	echo json_encode($output, JSON_FORCE_OBJECT|JSON_HEX_TAG);
	exit;

} else { //STEP 1 DATA PROCESSING, INITIAL LOAD
	//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $items = unserialize(mysql_result($result,0,0));
	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$items = unserialize($stm->fetchColumn(0));
	$ids = array();
	$types = array();
	$names = array();
	$sums = array();
	$parents = array();
	$agbcats = array();
	$prespace = array();
	$itemshowdata = loadItemShowData($items,false,true,false,false,false,true);
	getsubinfo($items,'0','',false,'|- ');
}

require("../header.php");

if ($overwriteBody==1) {
 echo $body;
} else {
?>


	<?php echo $curBreadcrumb; ?>
	<div class="cpmid"><a href="ccexport.php?cid=<?php echo $cid ?>">Export for another Learning Management System</a></div>

	<h2>Export Course Items</h2>

	<p>This page will let you export your course items for backup or transfer to
	another server running this software.</p>

	<form id="qform" method=post action="exportitems2.php?cid=<?php echo $cid ?>">
		<p>Select items to export</p>

		Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>

		<table cellpadding=5 class=gb>
		<thead>
			<tr><th></th><th>Type</th><th>Title</th></tr>
		</thead>
		<tbody>
<?php
	$alt=0;
	for ($i = 0 ; $i<(count($ids)); $i++) {
		if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
?>
				<td>
				<input type=checkbox name='checked[]' value='<?php echo Sanitize::encodeStringForDisplay($ids[$i]); ?>' checked=checked>
				</td>
				<td><?php echo Sanitize::encodeStringForDisplay($prespace[$i].$types[$i]); ?></td>
				<td><?php echo Sanitize::encodeStringForDisplay($names[$i]); ?></td>
			</tr>
<?php
	}
?>
		</tbody>
		</table>

	<fieldset><legend>Options</legend>
	<table>
	<tbody>
	<tr class="r"><td class="r">Export course settings?</td><td><input type=checkbox name="exportcourseopt"  value="1" checked/></td></tr>
	<tr class="r"><td class="r">Export gradebook scheme and categories?</td><td>
		<input type=checkbox name="exportgbsetup" value="1" checked/></td></tr>
	<tr><td class="r">Export offline grade items?</td><td> <input type=checkbox name="exportoffline"  value="1"/></td></tr>
	<tr><td class="r">Export calendar items?</td><td> <input type=checkbox name="exportcalitems"  value="1"/></td></tr>
	<tr><td class="r">Export "display at top" instructor forum posts? </td><td><input type=checkbox name="exportstickyposts"  value="1" checked="checked"/></td></tr>
	</tbody>
	</table>
	</fieldset>

		<p><input type=submit name="export" value="Export Items"></p>
	</form>

	<p>If you were wanting to export this course to a different Learning Management System, you can try the <a href="ccexport.php?cid=<?php echo $cid;?>">
	Common Cartridge export</a></p>
<?php
}

require("../footer.php");
?>
