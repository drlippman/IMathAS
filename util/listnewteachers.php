<?php

require("../init.php");

if ($myrights<100 && ($myspecialrights&(32+64))==0) {
	exit;
}
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

$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/tablesorter.js"></script>';
require("../header.php");
echo '<div class=breadcrumb>';
echo $breadcrumbbase .' <a href="../admin/userreports.php">'._('User Reports').'</a> &gt; ';
echo _('New Instructor Accounts').'</div>';
  	

echo '<h1>New Instructor Account Requests from ';
echo date('M j, Y',$start).' to '.date('M j, Y',$end).'</h1>';

//pull template courses
$stm = $DBH->query("SELECT id,name FROM imas_courses WHERE (istemplate&1)=1 OR (istemplate&2)=2 ORDER BY name");
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
	echo "No requests found";
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
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['name']===null) {
				$row['name'] = _('Default');
			}
			if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
			echo '<td>'.$row['name'].'</td>';
			echo '<td>';
			echo '<a href="../admin/userdetails.php?id='.$row['id'].'" target="_blank">';
			echo $row['LastName'].', '.$row['FirstName'].'</a></td>';
			echo '<td>'.$row['SID'].'</td>';
			echo '<td>'.$row['email'].'</td>';
			echo '<td>'.tzdate('n/j/y', $reqdates[$row['id']]).'</td>';
			echo '<td>'.tzdate('n/j/y', $reqappdates[$row['id']]).'</td>';
			echo '<td>'.$reqhow[$row['id']].'</td>';
			echo '<td>'.($row['lastaccess']==0?_('Never'):tzdate('n/j/y', $row['lastaccess'])).'</td>';
			echo '<td>'.$reqstatus[$row['id']].'</td>';
			echo '<td>'.$row['ccnt'].'</td>';
			$templatematches = array_unique(array_intersect(explode(',', $row['anc']), $templateids));
			$templatesused = array();
			foreach ($templatematches as $tid) {
				$templatesused[] = $templates[$tid];
			}
			echo '<td>'.implode('<br>', $templatesused).'</td>';
			echo '<td>'.$row['scnt'].'</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		echo '<script type="text/javascript">
			initSortTable("myTable",Array("S","S","S","S","D","S","N"),true);
		</script>';
	}

	require("../footer.php");
