<?php
//IMathAS:  Copy Course Items course list

if (isset($_GET['loadothergroup']) || isset($_GET['loadothers']) || isset($_POST['cidlookup'])) {
	require_once "../init.php";
}

if (!isset($myrights) || $myrights<20) {
	exit; //cannot be called directly
}
/** Utility tree-building functions **/
$grpcnt = 0;
function getCourseTree($order, $data, &$printed, &$out, $skipcopyright=2) {
	global $grpcnt;
	foreach ($order as $item) {
		if (is_array($item)) { 
			//course grouping
			$sub = [];
			getCourseTree($item['courses'], $data, $printed, $sub);
			$out[] = [
				'id'=>'grp'.$grpcnt, 
				'label'=>$item['name'],
				'children'=>$sub
			];
			$grpcnt++;
		} else if (isset($data[$item])) {
			$out[] = getCourseTreeitem($data[$item], $skipcopyright);
			$printed[] = $item;
		} 
	}
}
function getCourseTreeitem($line, $skipcopyright=2) {
	global $imasroot; 

	$item = [];
	$item['id'] = 'c'.$line['id'];
	$item['label'] = $line['name'];
	
	if ($line['copyrights']<$skipcopyright) {
		$item['label'] .= "©";
	} else {
		$item['links'] =[['label'=>_('Preview'), 'href' => "$imasroot/course/course.php?cid=" . Sanitize::courseId($line['id']), 'newtab'=>true]];
	}
	$itemclasses = array();
	if ($line['copyrights']<$skipcopyright) {
		$item['copyr'] = true;
	}
	if ($line['termsurl']!='') {
		$item['termsurl'] = Sanitize::url($line['termsurl']);
	}
	return $item;
}
function getGroupTree($result, $skipcopyright=2) {
	$grouptree = [];
	$lastteacher = 0;
	if ($result->rowCount()>0) {
		while ($line = $result->fetch(PDO::FETCH_ASSOC)) {
			if ($line['userid']!=$lastteacher) {
				if ($lastteacher!=0) {
					$grouptree[] = $curteachertree;
				}
				$lastteacher = $line['userid'];
				$curteachertree = [
					'id'=>'gu'.$line['userid'],
					'label'=> $line['LastName'].', '.$line['FirstName'],
					'links'=> [['label'=>_('Email'), 'href'=>'mailto:'.Sanitize::emailAddress($line['email'])]],
					'children'=>[]
				];
			}
			$curteachertree['children'][] = getCourseTreeitem($line, $skipcopyright);
		}
		$grouptree[] = $curteachertree;
	}
	return $grouptree;
}

/** load data **/

if (isset($_POST['cidlookup'])) {
	$query = "SELECT ic.id,ic.name,ic.enrollkey,ic.copyrights,ic.termsurl,iu.groupid,iu.LastName,iu.FirstName FROM imas_courses AS ic ";
	$query .= "JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ic.id=:id AND ic.copyrights>-1";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':id'=>Sanitize::onlyInt($_POST['cidlookup'])));
	if ($stm->rowCount()==0) {
		echo '{}';
	} else {
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		$out = array(
			"id"=>Sanitize::onlyInt($row['id']),
			"name"=>Sanitize::encodeStringForDisplay($row['name'] . ' ('.$row['LastName'].', '.$row['FirstName'].')'),
			"termsurl"=>Sanitize::url($row['termsurl']));
		$out['needkey'] = !($row['copyrights'] == 2 || ($row['copyrights'] == 1 && $row['groupid']==$groupid));
		echo json_encode($out, JSON_INVALID_UTF8_IGNORE);
	}
	exit;
} else if (isset($_GET['loadothers'])) {
	$grpout = [];
	$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
	if ($stm->rowCount()>0) {
		$page_hasGroups=true;
		$grpout[] = [
			'id'=>'grpid0',
			'label'=>_("Default Group"),
			'childrenUrl'=>"$imasroot/includes/coursecopylist.php?cid=$cid&loadothergroup=0"
		];
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['id']==$groupid) {continue;}
				$grpout[] = [
				'id'=>'grpid'.$row['id'],
				'label'=>$row['name'],
				'childrenUrl'=>"$imasroot/includes/coursecopylist.php?cid=$cid&loadothergroup=".$row['id']
			];
		}
	}
	echo json_encode($grpout, JSON_INVALID_UTF8_IGNORE);
	exit;

} else if (isset($_GET['loadothergroup'])) {

	$query = "SELECT ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid,iu.groupid,ic.termsurl,ic.istemplate FROM imas_courses AS ic,imas_teachers AS it,imas_users AS iu  WHERE ";
	$query .= "it.courseid=ic.id AND it.userid=iu.id AND iu.groupid=:groupid AND iu.id<>:userid AND ic.available<4 AND ic.copyrights>-1 ORDER BY iu.LastName,iu.FirstName,it.userid,ic.name";
	$courseGroupResults = $DBH->prepare($query);
	$courseGroupResults->execute(array(':groupid'=>$_GET['loadothergroup'], ':userid'=>$userid));

	$grptree = getGroupTree($courseGroupResults);
	if (count($grptree)==0) {
		$grptree[] = [
			'id'=>'none'.$_GET['loadothergroup'],
			'label'=>_('No Courses'),
			'notselectable'=>true
		];
	}
	echo json_encode($grptree, JSON_INVALID_UTF8_IGNORE);
	exit;

} else {
	$stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=:id");
	$stm->execute(array(':id'=>$userid));
	$userjson = json_decode($stm->fetchColumn(0), true);

	$myCourseResult = $DBH->prepare("SELECT ic.id,ic.name,ic.termsurl,ic.copyrights FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid=:userid and ic.id<>:cid AND ic.available<4 ORDER BY ic.name");
	$myCourseResult->execute(array(':userid'=>$userid, ':cid'=>$cid));
	$myCourses = array();
	$myCoursesDefaultOrder = array();
	while ($line = $myCourseResult->fetch(PDO::FETCH_ASSOC)) {
		$myCourses[$line['id']] = $line;
		$myCoursesDefaultOrder[] = $line['id'];
	}

	$query = "SELECT ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid,ic.termsurl FROM imas_courses AS ic,imas_teachers AS it,imas_users AS iu WHERE ";
	$query .= "it.courseid=ic.id AND it.userid=iu.id AND iu.groupid=:groupid AND iu.id<>:userid AND ic.available<4 AND ic.copyrights>-1 ORDER BY iu.LastName,iu.FirstName,it.userid,ic.name";
	$courseTreeResult = $DBH->prepare($query);
	$courseTreeResult->execute(array(':groupid'=>$groupid, ':userid'=>$userid));
	$lastteacher = 0;


	//$query = "SELECT ic.id,ic.name,ic.copyrights FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$templateuser' AND ic.available<4 ORDER BY ic.name";
	$courseTemplateResults = $DBH->query("SELECT id,name,copyrights,termsurl FROM imas_courses WHERE istemplate > 0 AND (istemplate&1)=1 AND copyrights=2 AND available<4 ORDER BY name");
	$query = "SELECT ic.id,ic.name,ic.copyrights,ic.termsurl FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ";
	$query .= "iu.groupid=:groupid AND ic.istemplate > 0 AND (ic.istemplate&2)=2 AND ic.copyrights>0 AND ic.available<4 ORDER BY ic.name";
	$groupTemplateResults = $DBH->prepare($query);
	$groupTemplateResults->execute(array(':groupid'=>$groupid));

	// build tree data
	$treedata = [];
	if (!isset($skipthiscourse)) {
		$treedata[] = getCourseTreeitem(['id'=>$cid, 'name'=>'This Course', 'copyrights'=>2, 'termsurl'=>''], -1);
	}
	// my courses
	$mycoursetree = [];
	if (isset($userjson['courseListOrder']['teach'])) {
		$printed = [];
		getCourseTree($userjson['courseListOrder']['teach'], $myCourses, $printed, $mycoursetree, -1);
		$notlisted = array_diff(array_keys($myCourses), $printed);
		foreach ($notlisted as $course) {
			$mycoursetree[] = getCourseTreeitem($myCourses[$course], -1);
		}
	} else {
		foreach ($myCoursesDefaultOrder as $course) {
			$mycoursetree[] = getCourseTreeitem($myCourses[$course], -1);
		}
	}
	if (count($mycoursetree)>0) {
		$treedata[] = [
			'id'=>'mycourses',
			'label'=>_('My Courses'),
			'children'=>$mycoursetree
		];
	}
	// group's courses
	if ($courseTreeResult->rowCount()>0) {
		$treedata[] = [
			'id'=>'grpcourses',
			'label'=>_('My Group\'s Courses'),
			'children'=>getGroupTree($courseTreeResult, 1)
		];
	}
	// others
	$treedata[] = [
		'id'=>'others', 
		'label'=>_("Other's Courses"),
		'childrenUrl'=> "$imasroot/includes/coursecopylist.php?cid=$cid&loadothers=true"
	];
	// templates
	if ($courseTemplateResults->rowCount()>0 && !isset($CFG['coursebrowser'])) {
		$templates = [];
		while ($line = $courseTemplateResults->fetch(PDO::FETCH_ASSOC)) {
			$templates[] = getCourseTreeitem($line);
		}
		$treedata[] = ['id'=>'templatecourses','label'=>_('Template Courses'),'children'=>$templates];
	}
	// grp templates
	if ($groupTemplateResults->rowCount()>0 && !isset($CFG['coursebrowser'])) {
		$templates = [];
		while ($line = $groupTemplateResults->fetch(PDO::FETCH_ASSOC)) {
			$templates[] = getCourseTreeitem($line);
		}
		$treedata[] = ['id'=>'grptemplatecourses','label'=>_('Group Template Courses'),'children'=>$templates];
	}
}

function writeEkeyField() {
?>
	<p id="ekeybox" style="display:none;">
	<?php echo _('For courses marked with &copy;, you must supply the course enrollment key to show permission to copy the course.'); ?><br/>
	<label><?php echo _('Enrollment key:'); ?> <input type=text name=ekey id=ekey size=30></label></p>

	<p id="termsbox" style="display:none;">
	<?php echo sprintf(_('This course has additional %sTerms of Use %s you must agree to before copying the course.'),'<a target="_blank" href="" id="termsurl">','</a>'); ?>'<br/>
	<label><input type="checkbox" name="termsagree" /> <?php echo _('I agree to the Terms of Use specified in the link above.'); ?></label></p>
<?php
}

/** HTML output **/
//display course list selection
?>
	<div id="treecontainer"></div>
 	<input class=hidden type=radio name=ctc value=0 id=treeselected>
	<script>var treedata = <?php echo json_encode($treedata, JSON_INVALID_UTF8_IGNORE);?>;</script>
	<script>

	var treeWidget;
	$(function() {
		const container = document.getElementById("treecontainer");
		treeWidget = new AccessibleTreeWidget(container, treedata, {
			selectionMode: "single",
			selectableItems: "children",
			showCounts: false,
			onSelectionChange: (selectedIds, selectedLabels) => {
				let treeselected = document.getElementById("treeselected");
				if (selectedIds.length>0) {
					treeselected.value = selectedIds[0].replace(/c/g,"");
					let data = treeWidget.renderedItems.get(selectedIds[0])?.data;
					if (data.copyr) {
						treeselected.classList.add("copyr");
					} else {
						treeselected.classList.remove("copyr");
					}
					if (data.termsurl) {
						treeselected.classList.add("termsurl");
						$(treeselected).data("termsurl", data.termsurl);
					} else {
						treeselected.classList.remove("termsurl");
					}
					$(treeselected).prop("checked", true).trigger("change");
				}
			},
			onLoadError: (error, item) => {
				alert(`Failed to load children for "${item.label}": ${error.message}`);
			}
		});
	});
	</script>
	<p><?php echo _('Or, lookup using <label for=cidlookup>course ID</label>:'); ?>
		<input type="text" size="7" id="cidlookup" />
		<button type="button" onclick="lookupcid()"><?php echo _('Look up course'); ?></button>
		<span id="cidlookupout" style="display:none;"><br/>
			<input type=radio name=ctc value=0 id=cidlookupctc />
			<span id="cidlookupname"></span>
		</span>
		<span id="cidlookuperr"></span>
	</p>
<?php

