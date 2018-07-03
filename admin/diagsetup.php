<?php
//IMathAS:  Diagnostic setup page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Diagnostic Setup";
$diagId = Sanitize::onlyInt(trim($_POST['id']));
$courseId = Sanitize::onlyInt(trim($_POST['cid']));
$curBreadcrumb = "<div class=breadcrumb>$breadcrumbbase ";
if (!empty($_GET['from'])) {
	$from = Sanitize::simpleString($_GET['from']);
	if ($from=='home') {
		$backtrack = "$imasroot/index.php";
	} else if ($from=='ld') {
		$curBreadcrumb .= '<a href="admin2.php">'._('Admin').'</a> &gt; ';
		$curBreadcrumb .= '<a href="listdiag.php">'._('Diagnostics').'</a> &gt; ';
		$backtrack = 'listdiag.php';
	} else if (substr($from,0,3)=='ldu') {
		$uid = Sanitize::onlyInt(substr($from,3));
		$curBreadcrumb .= '<a href="admin2.php">'._('Admin').'</a> &gt; ';
		$curBreadcrumb .= '<a href="userdetails.php?id='.$uid.'">'._('User Details').'</a> &gt; ';
		$curBreadcrumb .= '<a href="listdiag.php?show=u'.$uid.'">'._('Diagnostics').'</a> &gt; ';
		$backtrack = 'listdiag.php?show=u'.$uid;
	} else if (substr($from,0,3)=='ldg') {
		$gid = Sanitize::onlyInt(substr($from,3));
		$curBreadcrumb .= '<a href="admin2.php">'._('Admin').'</a> &gt; ';
		$curBreadcrumb .= '<a href="admin2.php?groupdetails='.$gid.'">'._('Group Details').'</a> &gt; ';
		$curBreadcrumb .= '<a href="listdiag.php?show=g'.$gid.'">'._('Diagnostics').'</a> &gt; ';
		$backtrack = 'listdiag.php?show=g'.$gid;
	}
} else {
	$curBreadcrumb .= "<a href=\"$imasroot/admin/admin2.php\">Admin</a> &gt;";
	$backtrack = 'admin2.php';
}
$curBreadcrumb .= _("Diagnostic Setup").'</div>';

	// SECURITY CHECK DATA PROCESSING
if ($myrights<100 && ($myspecialrights&4)!=4) {
	$overwriteBody = 1;
	$body = "You don't have authority to access this page.";
} elseif (isset($_GET['step']) && $_GET['step']==2 && isset($_POST['termtype'])) {  // STEP 2 DATA PROCESSING

	$sel1 = array();
	$ips = array();
	$pws = array();
	$spws = array();
	foreach ($_POST as $k=>$v) {
		if (strpos($k,'selout')!==FALSE) {
			$sel1[] = $v;
		} else if (strpos($k,'ipout')!==FALSE) {
			$ips[] = $v;
		} else if (strpos($k,'pwout')!==FALSE) {
			$pws[] = $v;
		} else if (strpos($k,'pwsout')!==FALSE) {
			$spws[] = $v;
		}
	}
	if (isset($_POST['alpha'])) {
		natsort($sel1);
		$sel1 = array_values($sel1);
	}

	$sel1list = implode(',',$sel1);
	$iplist = implode(',',$ips);
	$pwlist = implode(',',$pws) . ';'. implode(',',$spws);
	$public = 1*$_POST['avail'] + 2*$_POST['public'] + 4*$_POST['reentry'];

	if ($_POST['termtype']=='mo') {
		$_POST['term'] = '*mo*';
	} else if ($_POST['termtype']=='day') {
		$_POST['term'] = '*day*';
	}

	if (isset($_POST['entrynotunique'])) {
		$_POST['entrytype'] = chr(ord($_POST['entrytype'])-2);
	}
	$entryformat = $_POST['entrytype'].$_POST['entrydig'];

	$sel2 = array();

	if (!empty($diagId)) {
		//DB $query = "SELECT sel1list,sel2name,sel2list,aidlist,forceregen FROM imas_diags WHERE id='{$_POST['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT sel1list,sel2name,sel2list,aidlist,forceregen FROM imas_diags WHERE id=:id");
		$stm->execute(array(':id'=>$diagId));
		$row = $stm->fetch(PDO::FETCH_NUM);
		$s1l = explode(',',$row[0]);
		$s2l = explode(';',$row[2]);
		for ($i=0;$i<count($s1l);$i++) {
			$sel2[$s1l[$i]] = explode('~',$s2l[$i]);
		}
		$sel2name = $row[1];
		$aids = explode(',',$row[3]);
		$page_updateId = $_POST['id'];
		$forceregen = $row[4];
	} else {
		$sel2name = "instructor";
		$aids = array();
		$page_updateId = 0;
		$forceregen = 0;
	}

	foreach($sel1 as $k=>$s1) {
		$page_selectValList[$k] = array();
		$page_selectLabelList[$k] = array();
		$page_selectName[$k] = "aid" . $k;
		$i=0;

		//DB $query = "SELECT id,name FROM imas_assessments WHERE courseid='{$_POST['cid']}'";
		//DB $result = mysql_query($query);
		$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$courseId));

		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$page_selectValList[$k][$i] = $row[0];
			$page_selectLabelList[$k][$i] = $row[1];
			if (isset($aids[$k]) && $row[0]==$aids[$k]) {
				$page_selectedOption[$k] = $aids[$k];
			}
		$i++;
		}

	}

	$page_cntScript = (isset($sel2[$s1]) && count($sel2[$s1])>0) ? "<script> cnt['out$k'] = ".count($sel2[$s1]).";</script>\n"  : "<script> cnt['out$k'] = 0;</script>\n";


} elseif (isset($_GET['step']) && $_GET['step']==3 && isset($_POST['sel1list'])) {  //STEP 3 DATA PROCESSING
	$sel1 = explode(',',$_POST['sel1list']);
	$aids = array();
	$forceregen = 0;
	for ($i=0;$i<count($sel1);$i++) {
		$aids[$i] = $_POST['aid'.$i];
		if (isset($_POST['reg'.$i]) && $_POST['reg'.$i]==1) {
			$forceregen = $forceregen ^ (1<<$i);
		}
	}
	$aidlist = implode(',',$aids);
	$sel2 = array();
	foreach ($_POST as $k=>$v) {
		if (strpos($k,'out')!==FALSE) {
			$n = substr($k,3,strpos($k,'-')-3);
			$sel2[$n][] = ucfirst($v);
		}
	}
	if (isset($_POST['useoneforall'])) { //use first sel2 for all
		if (isset($_POST['alpha'])) {
			sort($sel2[0]);
		}
		$sel2[0] = implode('~',$sel2[0]);
		for ($i=1; $i<count($sel1); $i++) {
			$sel2[$i] = $sel2[0];
		}
	} else {
		for ($i=0;$i<count($sel2);$i++) {
			if (isset($_POST['alpha'])) {
				sort($sel2[$i]);
			}
			$sel2[$i] = implode('~',$sel2[$i]);
		}
	}
	$sel2list = implode(';',$sel2);

	if (isset($_POST['id']) && $_POST['id'] != 0) {
		//DB $query = "UPDATE imas_diags SET ";
		//DB $query .= "name='{$_POST['diagname']}',cid='{$_POST['cid']}',term='{$_POST['term']}',public='{$_POST['public']}',";
		//DB $query .= "ips='{$_POST['iplist']}',pws='{$_POST['pwlist']}',idprompt='{$_POST['idprompt']}',sel1name='{$_POST['sel1name']}',";
		//DB $query .= "sel1list='{$_POST['sel1list']}',aidlist='$aidlist',sel2name='{$_POST['sel2name']}',sel2list='$sel2list',entryformat='{$_POST['entryformat']}',forceregen='$forceregen',reentrytime='{$_POST['reentrytime']}' ";
		//DB $query .= " WHERE id='{$_POST['id']}'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "UPDATE imas_diags SET ";
		$query .= "name=:name,cid=:cid,term=:term,public=:public,ips=:ips,pws=:pws,idprompt=:idprompt,sel1name=:sel1name,";
		$query .= "sel1list=:sel1list,aidlist=:aidlist,sel2name=:sel2name,sel2list=:sel2list,entryformat=:entryformat,forceregen=:forceregen,reentrytime=:reentrytime ";
		$query .= " WHERE id=:id";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':name'=>$_POST['diagname'], ':cid'=>$_POST['cid'], ':term'=>$_POST['term'], ':public'=>$_POST['public'],
			':ips'=>$_POST['iplist'], ':pws'=>$_POST['pwlist'], ':idprompt'=>$_POST['idprompt'], ':sel1name'=>$_POST['sel1name'],
			':sel1list'=>$_POST['sel1list'], ':aidlist'=>$aidlist, ':sel2name'=>$_POST['sel2name'], ':sel2list'=>$sel2list,
			':entryformat'=>$_POST['entryformat'], ':forceregen'=>$forceregen, ':reentrytime'=>$_POST['reentrytime'], ':id'=>$_POST['id']));
		$id = Sanitize::onlyInt($_POST['id']);
		$page_successMsg = "<p>Diagnostic Updated</p>\n";
	} else {
		//DB $query = "INSERT INTO imas_diags (ownerid,name,cid,term,public,ips,pws,idprompt,sel1name,sel1list,aidlist,sel2name,sel2list,entryformat,forceregen,reentrytime) VALUES ";
		//DB $query .= "('$userid','{$_POST['diagname']}','{$_POST['cid']}','{$_POST['term']}','{$_POST['public']}','{$_POST['iplist']}','{$_POST['pwlist']}','{$_POST['idprompt']}','{$_POST['sel1name']}','{$_POST['sel1list']}','$aidlist','{$_POST['sel2name']}','$sel2list','{$_POST['entryformat']}','$forceregen','{$_POST['reentrytime']}')";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $id = mysql_insert_id();
		$query = "INSERT INTO imas_diags (ownerid,name,cid,term,public,ips,pws,idprompt,sel1name,sel1list,aidlist,sel2name,sel2list,entryformat,forceregen,reentrytime) VALUES ";
		$query .= "(:ownerid, :name, :cid, :term, :public, :ips, :pws, :idprompt, :sel1name, :sel1list, :aidlist, :sel2name, :sel2list, :entryformat, :forceregen, :reentrytime)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':ownerid'=>$userid, ':name'=>$_POST['diagname'], ':cid'=>$_POST['cid'], ':term'=>$_POST['term'],
			':public'=>$_POST['public'], ':ips'=>$_POST['iplist'], ':pws'=>$_POST['pwlist'], ':idprompt'=>$_POST['idprompt'],
			':sel1name'=>$_POST['sel1name'], ':sel1list'=>$_POST['sel1list'], ':aidlist'=>$aidlist, ':sel2name'=>$_POST['sel2name'],
			':sel2list'=>$sel2list, ':entryformat'=>$_POST['entryformat'], ':forceregen'=>$forceregen, ':reentrytime'=>$_POST['reentrytime']));
		$id = $DBH->lastInsertId();
		$page_successMsg = "<p>Diagnostic Added</p>\n";
	}
	$page_diagLink = sprintf("<p>Direct link to diagnostic: <b>%s/diag/index.php?id=%d</b></p>", $GLOBALS['basesiteurl'], $id);
	$page_publicLink = ($_POST['public']&2) ? sprintf("<p>Diagnostic is listed on the public listing at: <b>%s/diag/</b></p>\n", $GLOBALS['basesiteurl']) : ""  ;

} else {  //STEP 1 DATA PROCESSING, MODIFY MODE
	if (isset($_GET['id'])) {
		//DB $query = "SELECT name,term,cid,public,idprompt,ips,pws,sel1name,sel1list,entryformat,forceregen,reentrytime,ownerid FROM imas_diags WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
		$stm = $DBH->prepare("SELECT name,term,cid,public,idprompt,ips,pws,sel1name,sel1list,entryformat,forceregen,reentrytime,ownerid FROM imas_diags WHERE id=:id");
		$stm->execute(array(':id'=>$diagId));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		$diagname = $line['name'];
		$cid = $line['cid'];
		$public = $line['public'];
		$idprompt = $line['idprompt'];
		$ips = $line['ips'];
		$pws = $line['pws'];
		$sel = $line['sel1name'];
		$sel1list=  $line['sel1list'];
		$term = $line['term'];
		$entryformat = $line['entryformat'];
		$forceregen = $line['forceregen'];
		$reentrytime = $line['reentrytime'];
		if ($myrights>=75) {
			$owner = $line['ownerid'];
		} else if ($line['ownerid']!=$userid) {
			echo "Not yours!";
			exit;
		} else {
			$owner = $userid;
		}
	} else {  //STEP 1, ADD MODE
		$diagname = '';
		$cid = 0;
		$public = 7;
		$idprompt = "Enter your student ID number";
		$ips = '';
		$pws = '';
		$sel = 'course';
		$sel1list = '';
		$term = '';
		$entryformat = 'C0';
		$forceregen = 0;
		$reentrytime = 0;
		$owner = $userid;
	}
	$entrytype = substr($entryformat,0,1); //$entryformat{0};
	$entrydig = substr($entryformat,1); //$entryformat{1};
	$entrynotunique = false;
	if ($entrytype=='A' || $entrytype=='B') {
		$entrytype = chr(ord($entrytype)+2);
		$entrynotunique = true;
	}


	//DB $query = "SELECT imas_courses.id,imas_courses.name FROM imas_courses,imas_teachers WHERE imas_courses.id=imas_teachers.courseid ";
	//DB $query .= "AND imas_teachers.userid='$owner' ORDER BY imas_courses.name";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$query = "SELECT imas_courses.id,imas_courses.name FROM imas_courses,imas_teachers WHERE imas_courses.id=imas_teachers.courseid ";
	$query .= "AND imas_teachers.userid=:userid ORDER BY imas_courses.name";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':userid'=>$owner));

	$i=0;
	$page_courseSelectList = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$page_courseSelectList['val'][$i]=$row[0];
		$page_courseSelectList['label'][$i]=$row[1];
		if ($cid==$row[0]) {
			$page_courseSelected = $row[0];
		}
		$i++;
	}

	$page_entryNums = array();
	for ($j=0;$j<15;$j++) {
		$page_entryNums['val'][$j] = $j;
		if ($j==0) {
			$page_entryNums['label'][$j] = "Any number";
		} else {
			$page_entryNums['label'][$j] = $j;
		}
		if ($entrydig==$j) {
			$page_entryNumsSelected = $j;
		}
	}
	$page_entryType = array();
	$page_entryType['val'][0] = 'C';
	$page_entryType['label'][0] = 'Letters or numbers';
	$page_entryType['val'][1] = 'D';
	$page_entryType['label'][1] = 'Numbers';
	$page_entryType['val'][2] = 'E';
	$page_entryType['label'][2] = 'Email address';
	$page_entryTypeSelected = $entrytype;



}

$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/diag.js\"></script>\n";

 /******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) { //NO AUTHORITY
	echo $body;
} else { //USER HAS ACCESS, LOAD APPROPRIATE DISPLAY

	echo $curBreadcrumb;

	if (isset($_GET['step']) && $_GET['step']==2) {  //STEP 2 DISPLAY
?>
		<div id="headerdiagsetup" class="pagetitle"><h1>Diagnostic Setup</h1></div>
		<h3>Second-level Selector - extra information</h3>
		<form method=post action="diagsetup.php?step=3&amp;from=<?php echo $from;?>">

			<input type=hidden name="sel1list" value="<?php echo Sanitize::encodeStringForDisplay($sel1list); ?>"/>
			<input type=hidden name="iplist" value="<?php echo Sanitize::encodeStringForDisplay($iplist); ?>"/>
			<input type=hidden name="pwlist" value="<?php echo Sanitize::encodeStringForDisplay($pwlist); ?>"/>
			<input type=hidden name="cid" value="<?php echo Sanitize::courseId($_POST['cid']); ?>"/>
			<input type=hidden name="term" value="<?php echo Sanitize::encodeStringForDisplay($_POST['term']); ?>"/>
			<input type=hidden name="sel1name" value="<?php echo Sanitize::encodeStringForDisplay($_POST['sel']); ?>"/>
			<input type=hidden name="diagname" value="<?php echo Sanitize::encodeStringForDisplay($_POST['diagname']); ?>"/>
			<input type=hidden name="idprompt" value="<?php echo Sanitize::encodeStringForDisplay($_POST['idprompt']); ?>"/>
			<input type=hidden name="entryformat" value="<?php echo Sanitize::encodeStringForDisplay($entryformat); ?>"/>
			<input type=hidden name="public" value="<?php echo Sanitize::encodeStringForDisplay($public); ?>"/>
			<input type=hidden name="reentrytime" value="<?php echo Sanitize::encodeStringForDisplay($_POST['reentrytime']); ?>"/>
			<input type=hidden name="id" value="<?php echo Sanitize::onlyInt($page_updateId); ?>" >
			<p>Second-level selector name:
			<input type=text name=sel2name value="<?php echo Sanitize::encodeStringForDisplay($sel2name); ?>"/>
			'Select your ______'</p>
			<p>For each of the first-level selectors, select which assessment should be delivered,
			and provide options for the second-level selector</p>
			<p>Alphabetize selectors on submit? <input type="checkbox" name="alpha" value="1" /></p>
<?php
		foreach($sel1 as $k=>$s1) {
?>
			<div>
			<p><b><?php echo Sanitize::encodeStringForDisplay($s1); ?></b>.  Deliver assessment:

<?php
			writeHtmlSelect ($page_selectName[$k],$page_selectValList[$k],$page_selectLabelList[$k],$page_selectedOption[$k]);
?>
			<br/>
			Force regen on reentry (if allowed)? <input type=checkbox name="reg<?php echo $k; ?>" value="1" <?php if (($forceregen & (1<<$k)) > 0) {echo 'checked="checked"';}?> />
		<?php
		if ($k==0 && count($sel1)>1) {
			echo '<br/>Use these second-level selectors for all first-level selectors?';
			echo '<input type=checkbox name="useoneforall" value="1" onclick="toggleonefor(this)" />';
		}
		?>
			</p>

			<div class="sel2">Add selector value:
			<input type=text id="in<?php echo $k ?>"  onkeypress="return onenter(event,'in<?php echo $k ?>','out<?php echo $k ?>')"/>
			<input type="button" value="Add" onclick="additem('in<?php echo $k ?>','out<?php echo $k ?>')"/><br/>

			<table >
			<tbody id="out<?php echo $k ?>">

<?php
			if (isset($sel2[$s1])) {
				for ($i=0;$i<count($sel2[$s1]);$i++) {
?>
				<tr id="trout<?php echo $k . "-" . $i ?>">
					<td><input type=hidden id="out<?php echo $k . "-" . $i ?>" name="out<?php echo $k . "-" . $i ?>" value="<?php echo Sanitize::encodeStringForDisplay($sel2[$s1][$i]); ?>">
					<?php echo Sanitize::encodeStringForDisplay($sel2[$s1][$i]); ?></td>
					<td><a href='#' onclick="removeitem('out<?php echo $k . "-" . $i ?>','out<?php echo $k ?>')">Remove</a>
					<a href='#' onclick="moveitemup('out<?php echo $k . "-" . $i ?>','out<?php echo $k ?>')">Move up</a>
					<a href='#' onclick="moveitemdown('out<?php echo $k . "-" . $i ?>','out<?php echo $k ?>')">Move down</a>
					</td>
				</tr>

<?php
				}
			}
?>
			</tbody>
			</table>
			</div>

<?php
			echo (isset($sel2[$s1]) && count($sel2[$s1])>0) ? "<script> cnt['out$k'] = ".count($sel2[$s1]).";</script>\n"  : "<script> cnt['out$k'] = 0;</script>\n";
?>
		</div>

<?php
		}

		echo '<input type=submit value="Continue">';
		echo '</form>';

	} elseif (isset($_GET['step']) && $_GET['step']==3) {  //STEP 3 DISPLAY
		echo $page_successMsg;
		echo $page_diagLink;
		echo $page_publicLink;
		echo "<a href=\"$backtrack\">"._('Done')."</a>\n";
	} else {
	 //STEP 1 DISPLAY
?>
<div id="headerdiagsetup" class="pagetitle"><h1>Diagnostic Setup</h1></div>
<form method=post action=diagsetup.php?step=2&amp;from=<?php echo $from; ?>>

<?php echo (isset($_GET['id'])) ? "	<input type=hidden name=id value=\"".Sanitize::encodeUrlParam($_GET['id'])."\"/>" : ""; ?>

	<p>Diagnostic Name:
	<input type=text size=50 name="diagname" value="<?php echo Sanitize::encodeStringForDisplay($diagname); ?>"/></p>

	<p>Term designator (e.g. F06):  <input type=radio name="termtype" value="mo" <?php if ($term=="*mo*") {echo 'checked="checked"';}?>>Use Month
				<input type=radio name="termtype" value="day" <?php if ($term=="*day*") {echo 'checked="checked"';}?>>Use Day
				<input type=radio name="termtype" value="cu" <?php if ($term!="*mo*" && $term!="*day*"  ) {echo 'checked="checked"';}?>>Use: <input type=text size=7 name="term" value="<?php if ($term!="*mo*" && $term!="*day*" ) {echo Sanitize::encodeStringForDisplay($term); }?>"/></p>

	<p>Linked with course:
	<?php writeHtmlSelect ("cid",$page_courseSelectList['val'],$page_courseSelectList['label'],$page_courseSelected); ?>
	</p>

	<p>Available? (Can be taken)?
	<input type=radio name="avail" value="1" <?php writeHtmlChecked(1,($public&1),0); ?> /> Yes
	<input type=radio name="avail" value="0" <?php writeHtmlChecked(1,($public&1),1); ?> /> No
	</p>
	<p>Include in public listing?
	<input type=radio name="public" value="1" <?php  writeHtmlChecked(2,($public&2),0); ?> /> Yes
	<input type=radio name="public" value="0" <?php writeHtmlChecked(2,($public&2),1); ?> /> No
	</p>
	<p>Allow reentry (continuation of test at later date)?
	<input type=radio name="reentry" value="0" <?php writeHtmlChecked(4,($public&4),1); ?> /> No

	<input type=radio name="reentry" value="1" <?php writeHtmlChecked(4,($public&4),0); ?> /> Yes, within
	  <input type="text" name="reentrytime" value="<?php echo Sanitize::encodeStringForDisplay($reentrytime); ?>" size="4" /> minutes (0 for no limit)

	</p>

	<p>Unique ID prompt: <input type=text size=60 name="idprompt" value="<?php echo Sanitize::encodeStringForDisplay($idprompt); ?>" /></p>

	<p>Attach first level selector to ID: <input type="checkbox" name="entrynotunique" value="1" <?php writeHtmlChecked($entrynotunique,true); ?> /></p>

	<p>ID entry format:
<?php
	writeHtmlSelect("entrytype",$page_entryType['val'],$page_entryType['label'],$page_entryTypeSelected);
?>
	</p>
	<p>ID entry number of characters?:
<?php
	writeHtmlSelect("entrydig",$page_entryNums['val'],$page_entryNums['label'],$page_entryNumsSelected);
?>
	</p>
	<p>
	Allow access without password from computer with these IP addresses.  Use * for wildcard, e.g. 134.39.*<br/>
	Enter IP address: <input type=text id="ipin" onkeypress="return onenter(event,'ipin','ipout')">
	<input type=button value="Add" onclick="additem('ipin','ipout')"/>

	<table>
	<tbody id="ipout">
<?php
		if (trim($ips)!='') {
			$ips= explode(',',$ips);
			for ($i=0;$i<count($ips);$i++) {
?>
		<tr id="tripout-<?php echo $i ?>">
			<td><input type=hidden id="ipout-<?php echo $i ?>" name="ipout-<?php echo $i ?>" value="<?php echo Sanitize::encodeStringForDisplay($ips[$i]); ?>">
			<?php echo Sanitize::encodeStringForDisplay($ips[$i]); ?></td>
			<td>
				<a href='#' onclick="return removeitem('ipout-<?php echo $i ?>','ipout')">Remove</a>
				<a href='#' onclick="return moveitemup('ipout-<?php echo $i ?>','ipout')">Move up</a>
				<a href='#' onclick="return moveitemdown('ipout-<?php echo $i ?>','ipout')">Move down</a>
			</td>
		</tr>
<?php
			}
		}
?>
	</tbody>
	</table>

<?php
		if (is_array($ips)) {
			echo "<script> cnt['ipout'] = ".count($ips).";</script>";
		} else {
			echo "<script> cnt['ipout'] = 0;</script>";
		}
	?>
	</p>


	<p>From other computers, a password will be required to access the diagnostic.<br/>
	Enter Password:
	<input type=text id="pwin"  onkeypress="return onenter(event,'pwin','pwout')">
	<input type=button value="Add" onclick="additem('pwin','pwout')"/>

	<table>
	<tbody id="pwout">
<?php
		$pws = explode(';',$pws);
		if (trim($pws[0])!='') {

			$pwsb= explode(',',$pws[0]);
			for ($i=0;$i<count($pwsb);$i++) {
?>
		<tr id="trpwout-<?php echo $i ?>">
			<td>
				<input type=hidden id="pwout-<?php echo $i ?>" name="pwout-<?php echo $i ?>" value="<?php echo Sanitize::encodeStringForDisplay($pwsb[$i]); ?>">
				<?php echo Sanitize::encodeStringForDisplay($pwsb[$i]); ?>
			</td>
			<td>
				<a href='#' onclick="return removeitem('pwout-<?php echo $i ?>','pwout')">Remove</a>
				<a href='#' onclick="return moveitemup('pwout-<?php echo $i ?>','pwout')">Move up</a>
				<a href='#' onclick="return moveitemdown('pwout-<?php echo $i ?>','pwout')">Move down</a>
			</td>
		</tr>
<?php
			}
		}
?>
	</tbody>
	</table>

<?php
		if (is_array($pwsb)) {
			echo "	<script> cnt['pwout'] = ".count($pwsb).";</script>";
		} else {
			echo "	<script> cnt['pwout'] = 0;</script>";
		}
?>
	</p>
	<p>Super passwords will override testing window limits.<br/>
	Enter Password:
	<input type=text id="pwsin"  onkeypress="return onenter(event,'pwsin','pwsout')">
	<input type=button value="Add" onclick="additem('pwsin','pwsout')"/>

	<table>
	<tbody id="pwsout">
<?php
		if (count($pws)>1 && trim($pws[1])!='') {

			$pwss= explode(',',$pws[1]);
			for ($i=0;$i<count($pwss);$i++) {
?>
		<tr id="trpwsout-<?php echo $i ?>">
			<td>
				<input type=hidden id="pwsout-<?php echo $i ?>" name="pwsout-<?php echo $i ?>" value="<?php echo Sanitize::encodeStringForDisplay($pwss[$i]); ?>">
				<?php echo Sanitize::encodeStringForDisplay($pwss[$i]); ?>
			</td>
			<td>
				<a href='#' onclick="return removeitem('pwsout-<?php echo $i ?>','pwsout')">Remove</a>
				<a href='#' onclick="return moveitemup('pwsout-<?php echo $i ?>','pwsout')">Move up</a>
				<a href='#' onclick="return moveitemdown('pwsout-<?php echo $i ?>','pwsout')">Move down</a>
			</td>
		</tr>
<?php
			}
		}
?>
	</tbody>
	</table>

<?php
		if (is_array($pwss)) {
			echo "	<script> cnt['pwsout'] = ".count($pwss).";</script>";
		} else {
			echo "	<script> cnt['pwsout'] = 0;</script>";
		}
?>
	</p>

	<h3>First-level selector - selects assessment to be delivered</h3>
	<p>Selector name:  <input name="sel" type=text value="<?php echo Sanitize::encodeStringForDisplay($sel); ?>"/> "Please select your _______"</p>
	<p>Alphabetize selectors on submit? <input type="checkbox" name="alpha" value="1" /></p>
	<p>Enter new selector option:
		<input type=text id="sellist"  onkeypress="return onenter(event,'sellist','selout')">
		<input type=button value="Add" onclick="additem('sellist','selout')"/>


		<table>
		<tbody id="selout">
<?php
		if (trim($sel1list)!='') {
			$sl= explode(',',$sel1list);
			for ($i=0;$i<count($sl);$i++) {
?>
				<tr id="trselout-<?php echo $i ?>">
					<td>
						<input type=hidden id="selout-<?php echo $i ?>" name="selout-<?php echo $i ?>" value="<?php echo Sanitize::encodeStringForDisplay($sl[$i]); ?>">
						<?php echo Sanitize::encodeStringForDisplay($sl[$i]); ?>
					</td>
					<td>
						<a href='#' onclick="return removeitem('selout-<?php echo $i ?>','selout')">Remove</a>
						<a href='#' onclick="return moveitemup('selout-<?php echo $i ?>','selout')">Move up</a>
						<a href='#' onclick="return moveitemdown('selout-<?php echo $i ?>','selout')">Move down</a>
					</td>
				</tr>
<?php
			}
		}
?>
		</tbody>
		</table>

<?php
		if (is_array($sl)) {
			echo "<script> cnt['selout'] = ".count($sl).";</script>";
		} else {
			echo "<script> cnt['selout'] = 0;</script>";
		}
?>
	</p>

	<p><input type=submit value="Continue Setup"/></p>
	</form>
<?php
	}
}
	require("../footer.php");
?>
