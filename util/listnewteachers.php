<?php

require("../init.php");

if ($myrights<100 && ($myspecialrights&(32+64))==0) {
	exit;
}

$outputFormat = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';

$now = time();
$start = $now - 60*60*24*30;
$end = $now;
if (isset($_GET['start'])) {
	$parts = explode('-',$_GET['start']);
	if (count($parts)==3) {
		$start = mktime(0,0,0,$parts[0],$parts[1],$parts[2]);
	}
} else if (isset($_GET['days'])) {
	$start = $now - 60*60*24*intval($_GET['days']);
}

if (isset($_GET['end'])) {
	$parts = explode('-',$_GET['end']);
	if (count($parts)==3) {
		$end = mktime(0,0,0,$parts[0],$parts[1],$parts[2]);
	}
}


//pull template courses
$stm = $DBH->query("SELECT id,name FROM imas_courses WHERE istemplate > 0 AND (istemplate&1)=1 OR (istemplate&2)=2 ORDER BY name");
$templates = array();
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$templates[$row[0]] = $row[1];
}
$templateids = array_keys($templates);

$stm = $DBH->prepare("SELECT time,log FROM imas_log WHERE log LIKE :log AND time>:start AND time<:end");
$stm->execute(array(':log'=>"New Instructor Request%",':start'=>$start,':end'=>$end));
$reqdates = array();
while ($reqdata = $stm->fetch(PDO::FETCH_ASSOC)) {
	$log = explode('::',substr($reqdata['log'], 24));
	$reqdates[Sanitize::onlyInt($log[0])] = $reqdata['time'];
}

$stm = $DBH->prepare("SELECT * FROM imas_instr_acct_reqs WHERE reqdate>:start AND reqdate<:end");
$stm->execute(array(':start'=>$start,':end'=>$end));
$reqdates = array();
$reqappdates = array();
$reqstatus = array();
$reqhow = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$reqdates[$row['userid']] = $row['reqdate'];
	if ($row['status']==10) {
		$reqstatus[$row['userid']] = _('Denied');
	} else if ($row['status']==11) {
		$reqstatus[$row['userid']] = _('Active');
	} else {
		$reqstatus[$row['userid']] = _('Pending');
	}
	$j = json_decode($row['reqdata'], true);
	if (isset($j['actions'][0]['via'])) {
		if ($j['actions'][0]['via']=='chgrights') {
			$reqhow[$row['userid']] = _('Manually Upgraded');
		} else if ($j['actions'][0]['via']=='batchcreate') {
			$reqhow[$row['userid']] = _('Batch Added');
		} else if ($j['actions'][0]['via']=='LTI') {
			$reqhow[$row['userid']] = _('LTI');
		} else if ($j['actions'][0]['via']=='newinstr') {
			$reqhow[$row['userid']] = _('Request Form');
		} else {
			$reqhow[$row['userid']] = _('Manually Added');
		}
	} else {
		$reqhow[$row['userid']] = _('Request Form');
	}
	$reqappdates[$row['userid']] = $j['actions'][count($j['actions'])-1]['on'];
}

if (count($reqdates)==0) {
    htmlHeader();
	echo "No requests found";
	require("../footer.php");
} else {
	$ph = Sanitize::generateQueryPlaceholders($reqdates);

	$query = "SELECT u.id,u.rights,g.name,u.LastName,u.FirstName,u.SID,u.email,u.lastaccess,";
	$query .= "COUNT(DISTINCT s.id) AS scnt,COUNT(t.id) as ccnt,GROUP_CONCAT(DISTINCT(t.ancestors)) as anc ";
	$query .= "FROM imas_users as u ";
	$query .= "LEFT JOIN imas_groups AS g ON g.id=u.groupid ";
	$query .= "LEFT JOIN imas_courses AS t ON u.id=t.ownerid ";
	$query .= "LEFT JOIN imas_students AS s ON s.courseid=t.id ";
	$query .= "WHERE u.id IN ($ph) GROUP BY u.id ORDER BY g.name,u.LastName";
	$stm = $DBH->prepare($query);
	$stm->execute(array_keys($reqdates));

	if ('html' == $outputFormat) {
		htmlHeader();
		outputHtml();
		require("../footer.php");
	} elseif ('csv' == $outputFormat) {
		outputCsv();
	}
}

function htmlHeader() {
	extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

	$placeinhead = '<script type="text/javascript" src="'.$GLOBALS['staticroot'].'/javascript/tablesorter.js"></script>';
	require("../header.php");
	echo '<div class=breadcrumb>';
	echo $GLOBALS['breadcrumbbase'] .' <a href="../admin/userreports.php">'._('User Reports').'</a> &gt; ';
	echo _('New Instructor Accounts').'</div>';


	echo '<h1>New Instructor Account Requests from ';
	echo date('M j, Y',$GLOBALS['start']).' to '.date('M j, Y',$GLOBALS['end']).'</h1>';
?>
	<a style="float: right; padding-bottom: 10px;"
       href="<?php echo $GLOBALS['basesiteurl']; ?>/util/listnewteachers.php?<?php echo generateCsvQueryArgs(); ?>">Download
        CSV file</a>
<?php
}

function outputHtml() {
	extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

	?>
    <table class="gb" id="myTable">
        <thead>
        <tr>
            <th>Group</th>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Req Date</th>
            <th>Approved Date</th>
            <th>From</th>
            <th>Last Login</th>
            <th>Status</th>
            <th>Course count</th>
            <th>Templates copied</th>
            <th>Student count</th>
        </tr>
        </thead>
        <tbody>
	<?php

	$alt = 0;
	while ($row = $GLOBALS['stm']->fetch(PDO::FETCH_ASSOC)) {
		if ($row['name']===null) {
			$row['name'] = _('Default');
		}
		if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
		echo '<td>'.Sanitize::encodeStringForDisplay($row['name']).'</td>';
		echo '<td>';
		echo '<a href="../admin/userdetails.php?id='.$row['id'].'" target="_blank"><span class="pii-full-name">';
		echo Sanitize::encodeStringForDisplay($row['LastName'].', '.$row['FirstName']).'</span></a></td>';
		echo '<td><span class="pii-username">'.Sanitize::encodeStringForDisplay($row['SID']).'</span></td>';
		echo '<td><span class="pii-email">'.Sanitize::encodeStringForDisplay($row['email']).'</span></td>';
		echo '<td>'.getFormattedRequestDate($row).'</td>';
		echo '<td>'.getFormattedApprovalDate($row).'</td>';
		echo '<td>'.Sanitize::encodeStringForDisplay($GLOBALS['reqhow'][$row['id']]).'</td>';
		echo '<td>'.getFormattedLastLogin($row).'</td>';
		echo '<td>'.Sanitize::encodeStringForDisplay($GLOBALS['reqstatus'][$row['id']]).'</td>';
		echo '<td>'.Sanitize::encodeStringForDisplay($row['ccnt']).'</td>';
		echo '<td>'.implode('<br>', array_map('Sanitize::encodeStringForDisplay',getTemplatesUsed($row))).'</td>';
		echo '<td>'.Sanitize::encodeStringForDisplay($row['scnt']).'</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '<script type="text/javascript">
			initSortTable("myTable",Array("S","S","S","S","D","S","N"),true);
		</script>';
}

function outputCsv() {
	extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="new_teacher_requests.csv"');
	$stdout = fopen('php://output', 'w');

	$headers = array(
		'userid',
		'username',
		'name',
		'group_name',
		'email',
		'request_date',
		'status',
		'student_count',
        'approved_date',
        'from',
        'last_login',
        'course_count',
        'templates_copied',
	);
	fputcsv($stdout, $headers);

	while ($row = $GLOBALS['stm']->fetch(PDO::FETCH_ASSOC)) {
		if ($row['name']===null) {
			$row['name'] = _('Default');
		}

		$data = array(
		    $row['id'],
            $row['SID'],
			$row['LastName'] . ', ' . $row['FirstName'],
			$row['name'],
			$row['email'],
			getFormattedRequestDate($row),
			$GLOBALS['reqstatus'][$row['id']],
            $row['scnt'],
			getFormattedApprovalDate($row),
			$GLOBALS['reqhow'][$row['id']],
			getFormattedLastLogin($row),
            $row['ccnt'],
			implode('|', getTemplatesUsed($row))
        );

		fputcsv($stdout, $data);
		fflush($stdout);
	}

	fclose($stdout);
}

function generateCsvQueryArgs()
{
	$args = array_merge($_GET, array('format' => 'csv'));
	return Sanitize::generateQueryStringFromMap($args);
}

function getTemplatesUsed($row) {
	extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

	$templatematches = array_unique(array_intersect(explode(',', $row['anc']), $GLOBALS['templateids']));
	$templatesused = array();
	foreach ($templatematches as $tid) {
		$templatesused[] = $GLOBALS['templates'][$tid];
	}

	return $templatesused;
}

function getFormattedRequestDate($row) {
	extract($GLOBALS, EXTR_SKIP | EXTR_REFS);
	return tzdate('n/j/y', $GLOBALS['reqdates'][$row['id']]);
}

function getFormattedApprovalDate($row) {
	extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

	$approvalDate = $GLOBALS['reqappdates'][$row['id']];
	return ($approvalDate == 0 ? _('Not approved') : tzdate('n/j/y', $approvalDate));
}

function getFormattedLastLogin($row) {
	return ($row['lastaccess'] == 0 ? _('Never') : tzdate('n/j/y', $row['lastaccess']));
}
