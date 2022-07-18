<?php
//IMathAS:  Copy Course Items course list

if (isset($_GET['loadothergroup']) || isset($_GET['loadothers']) || isset($_POST['cidlookup'])) {
	require("../init.php");
}

if (!isset($myrights) || $myrights<20) {
	exit; //cannot be called directly
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
	$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
	if ($stm->rowCount()>0) {
		$page_hasGroups=true;
		$grpnames = array();
		$grpnames[] = array('id'=>0,'name'=>_("Default Group"));
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['id']==$groupid) {continue;}
			$grpnames[] = $row;
		}
	}

} else if (isset($_GET['loadothergroup'])) {

	$query = "SELECT ic.id,ic.name,ic.copyrights,iu.LastName,iu.FirstName,iu.email,it.userid,iu.groupid,ic.termsurl,ic.istemplate FROM imas_courses AS ic,imas_teachers AS it,imas_users AS iu  WHERE ";
	$query .= "it.courseid=ic.id AND it.userid=iu.id AND iu.groupid=:groupid AND iu.id<>:userid AND ic.available<4 AND ic.copyrights>-1 ORDER BY iu.LastName,iu.FirstName,it.userid,ic.name";
	$courseGroupResults = $DBH->prepare($query);
	$courseGroupResults->execute(array(':groupid'=>$_GET['loadothergroup'], ':userid'=>$userid));

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
}

/** define utility functions **/
function printCourseOrder($order, $data, &$printed) {
	foreach ($order as $item) {
		if (is_array($item)) {
			echo '<li class="coursegroup"><span class=dd>-</span> ';
			echo '<b>'.Sanitize::encodeStringForDisplay($item['name']).'</b>';
			echo '<ul class="nomark">';
			printCourseOrder($item['courses'], $data, $printed);
			echo '</ul></li>';
		} else if (isset($data[$item])) {
			printCourseLine($data[$item]);
			$printed[] = $item;
		}
	}
}
function printCourseLine($data) {
	echo '<li><span class=dd>-</span> ';
	writeCourseInfo($data, -1);
	echo '</li>';
}
function writeCourseInfo($line, $skipcopyright=2) {
	global $imasroot;
	$itemclasses = array();
	if ($line['copyrights']<$skipcopyright) {
		$itemclasses[] = 'copyr';
	}
	if ($line['termsurl']!='') {
		$itemclasses[] = 'termsurl';
	}
	echo '<input type="radio" name="ctc" value="' . Sanitize::encodeStringForDisplay($line['id']) . '" ' . ((count($itemclasses)>0)?'class="' . implode(' ',$itemclasses) . '"':'');
	if ($line['termsurl']!='') {
		echo ' data-termsurl="'.Sanitize::url($line['termsurl']).'"';
	}
	echo '>';
	echo Sanitize::encodeStringForDisplay($line['name']);

	if ($line['copyrights']<$skipcopyright) {
		echo "&copy;\n";
	} else {
		echo " <a href=\"$imasroot/course/course.php?cid=" . Sanitize::courseId($line['id']) . "\" target=\"_blank\" class=\"small\">"._("Preview")."</a>";
	}
}

function writeOtherGrpTemplates($grptemplatelist) {
	if (count($grptemplatelist)==0) { return;}
	?>
	<li class=lihdr>
	<span class=dd>-</span>
	<span class=hdr onClick="toggle('OGT<?php echo $line['groupid'] ?>')">
		<span class=btn id="bOGT<?php echo $line['groupid'] ?>">+</span>
	</span>
	<span class=hdr onClick="toggle('OGT<?php echo $line['groupid'] ?>')">
		<span id="nOGT<?php echo $line['groupid'] ?>" ><?php echo _('Group Templates') . "\n" ?>
		</span>
	</span>
	<ul class=hide id="OGT<?php echo $line['groupid'] ?>">
	<?php
	$showncourses = array();
	foreach ($grptemplatelist as $gt) {
		if (in_array($gt['courseid'], $showncourses)) {continue;}
		echo '<li><span class=dd>-</span>';
		writeCourseInfo($gt);
		$showncourses[] = $gt['courseid'];
		echo '<li>';
	}
	echo '</ul></li>';
}

function writeEkeyField() {
?>
	<p id="ekeybox" style="display:none;">
	<?php echo _('For courses marked with &copy;, you must supply the course enrollment key to show permission to copy the course.'); ?><br/>
	<?php echo _('Enrollment key:'); ?> <input type=text name=ekey id=ekey size=30></p>

	<p id="termsbox" style="display:none;">
	<?php echo sprintf(_('This course has additional %sTerms of Use %s you must agree to before copying the course.'),'<a target="_blank" href="" id="termsurl">','</a>'); ?>'<br/>
	<input type="checkbox" name="termsagree" /> <?php echo _('I agree to the Terms of Use specified in the link above.'); ?></p>
<?php
}

/** HTML output **/
if (isset($_GET['loadothers'])) { //loading others subblock
	 if ($page_hasGroups) {
			foreach ($grpnames as $grp) {
				?>
							<li class=lihdr>
								<span class=dd>-</span>
								<span class=hdr onClick="loadothergroup('<?php echo Sanitize::encodeStringForJavascript($grp['id']); ?>')">
									<span class=btn id="bg<?php echo Sanitize::encodeStringForDisplay($grp['id']); ?>">+</span>
								</span>
								<span class=hdr onClick="loadothergroup('<?php echo Sanitize::encodeStringForJavascript($grp['id']); ?>')">
									<span id="ng<?php echo Sanitize::encodeStringForDisplay($grp['id']); ?>" ><?php echo Sanitize::encodeStringForDisplay($grp['name']); ?></span>
								</span>
								<ul class=hide id="g<?php echo Sanitize::encodeStringForDisplay($grp['id']); ?>">
									<li>Loading...</li>
								</ul>
							</li>
				<?php
			}
	 } else {
		 echo '<li>'. _('No other users').'</li>';
	 }

} else if (isset($_GET['loadothergroup'])) { //loading others subblock
	if ($courseGroupResults->rowCount()>0) {
			$lastteacher = 0;
			$grptemplatelist = array(); //writeOtherGrpTemplates($grptemplatelist);
			while ($line = $courseGroupResults->fetch(PDO::FETCH_ASSOC)) {
				if ($line['userid']!=$lastteacher) {
					if ($lastteacher!=0) {
						echo "				</ul>\n			</li>\n";
					}
?>
			<li class=lihdr>
				<span class=dd>-</span>
				<span class=hdr onClick="toggle(<?php echo Sanitize::encodeStringForJavascript($line['userid']); ?>)">
					<span class=btn id="b<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>">+</span>
				</span>
				<span class=hdr onClick="toggle(<?php echo Sanitize::encodeStringForJavascript($line['userid']); ?>)">
					<span id="n<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>" class="pii-full-name"><?php echo Sanitize::encodeStringForDisplay($line['LastName']) . ", " . Sanitize::encodeStringForDisplay($line['FirstName']) . "\n" ?>
					</span>
				</span>
                <a class="pii-email" href="mailto:<?php echo Sanitize::emailAddress($line['email']); ?>"><span class="pii-safe">Email</span></a>
				<ul class=hide id="<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>">
<?php
					$lastteacher = $line['userid'];
				}
?>
					<li>
						<span class=dd>-</span>
						<?php
						//do class for has terms.  Attach data-termsurl attribute.
						writeCourseInfo($line);
						if (($line['istemplate']&2)==2) {
							$grptemplatelist[] = $line;
						}
						?>
					</li>
<?php
			}
?>

					</ul>
				</li>
				<?php writeOtherGrpTemplates($grptemplatelist);?>

<?php
	 } else {
		 echo '<li>'._('No group members with courses').'</li>';
	 }

} else {  //display course list selection
?>
	<ul class=base>
<?php
	if (!isset($skipthiscourse)) {
?>
		<li><span class=dd>-</span>
			<input type=radio name=ctc value="<?php echo $cid ?>" checked=1><?php echo _('This Course'); ?></li>
<?php
	}
?>
		<li class=lihdr><span class=dd>-</span>
			<span class=hdr onClick="toggle('mine')">
				<span class=btn id="bmine">+</span>
			</span>
			<span class=hdr onClick="toggle('mine')">
				<span id="nmine" ><?php echo _('My Courses'); ?></span>
			</span>
			<ul class=hide id="mine">
<?php
//my items
	if (isset($userjson['courseListOrder']['teach'])) {
		$printed = array();
		printCourseOrder($userjson['courseListOrder']['teach'], $myCourses, $printed);
		$notlisted = array_diff(array_keys($myCourses), $printed);
		foreach ($notlisted as $course) {
			printCourseLine($myCourses[$course]);
		}
	} else {
		foreach ($myCoursesDefaultOrder as $course) {
			printCourseLine($myCourses[$course]);
		}
	}
?>
			</ul>
		</li>
		<li class=lihdr><span class=dd>-</span>
			<span class=hdr onClick="toggle('grp')">
				<span class=btn id="bgrp">+</span>
			</span>
			<span class=hdr onClick="toggle('grp')">
				<span id="ngrp" ><?php echo _("My Group's Courses"); ?></span>
			</span>
			<ul class=hide id="grp">

<?php
//group's courses
	if ($courseTreeResult->rowCount()>0) {
		while ($line = $courseTreeResult->fetch(PDO::FETCH_ASSOC)) {
			if ($line['userid']!=$lastteacher) {
				if ($lastteacher!=0) {
					echo "				</ul>\n			</li>\n";
				}
?>
				<li class=lihdr>
					<span class=dd>-</span>
					<span class=hdr onClick="toggle(<?php echo Sanitize::encodeStringForJavascript($line['userid']); ?>)">
						<span class=btn id="b<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>">+</span>
					</span>
					<span class=hdr onClick="toggle(<?php echo Sanitize::encodeStringForJavascript($line['userid']); ?>)">
						<span id="n<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>"><?php echo Sanitize::encodeStringForDisplay($line['LastName']) . ", " . Sanitize::encodeStringForDisplay($line['FirstName']) . "\n" ?>
						</span>
					</span>
					<a href="mailto:<?php echo Sanitize::emailAddress($line['email']); ?>">Email</a>
					<ul class=hide id="<?php echo Sanitize::encodeStringForDisplay($line['userid']); ?>">
<?php
				$lastteacher = $line['userid'];
			}
?>
						<li>
							<span class=dd>-</span>
							<?php
							writeCourseInfo($line, 1);
							?>
						</li>
<?php
		}
		echo "						</ul>\n					</li>\n";
		echo "				</ul>			</li>\n";
	} else {
		echo "				</ul>\n			</li>\n";
	}
?>
		<li class=lihdr>
			<span class=dd>-</span>
			<span class=hdr onClick="toggle('other');loadothers();">
				<span class=btn id="bother">+</span>
			</span>
			<span class=hdr onClick="toggle('other');loadothers();">
				<span id="nother" ><?php echo _("Other's Courses"); ?></span>
			</span>
			<ul class=hide id="other">

<?php
//Other's courses: loaded via AHAH when clicked
	echo "<li>Loading...</li>			</ul>\n		</li>\n";

//template courses
	if ($courseTemplateResults->rowCount()>0 && !isset($CFG['coursebrowser'])) {
?>
	<li class=lihdr>
		<span class=dd>-</span>
		<span class=hdr onClick="toggle('template')">
			<span class=btn id="btemplate">+</span>
		</span>
		<span class=hdr onClick="toggle('template')">
			<span id="ntemplate" >Template Courses</span>
		</span>
		<ul class=hide id="template">

<?php
		while ($line = $courseTemplateResults->fetch(PDO::FETCH_ASSOC)) {
?>
			<li>
				<span class=dd>-</span>
				<?php
				writeCourseInfo($line);
				?>
			</li>

<?php
		}
		echo "			</ul>\n		</li>\n";
	}
	if ($groupTemplateResults->rowCount()>0 && !isset($CFG['coursebrowser'])) {
?>
	<li class=lihdr>
		<span class=dd>-</span>
		<span class=hdr onClick="toggle('gtemplate')">
			<span class=btn id="bgtemplate">+</span>
		</span>
		<span class=hdr onClick="toggle('gtemplate')">
			<span id="ngtemplate" ><?php echo _('Group Template Courses'); ?></span>
		</span>
		<ul class=hide id="gtemplate">

<?php
		while ($line = $groupTemplateResults->fetch(PDO::FETCH_ASSOC)) {
?>
			<li>
				<span class=dd>-</span>
				<?php
				writeCourseInfo($line, 1);
				?>
			</li>

<?php
		}
		echo "			</ul>\n		</li>\n";
	}
?>
	</ul>

	<p><?php echo _('Or, lookup using course ID:'); ?>
		<input type="text" size="7" id="cidlookup" />
		<button type="button" onclick="lookupcid()"><?php echo _('Look up course'); ?></button>
		<span id="cidlookupout" style="display:none;"><br/>
			<input type=radio name=ctc value=0 id=cidlookupctc />
			<span id="cidlookupname"></span>
		</span>
		<span id="cidlookuperr"></span>
	</p>
<?php
}
