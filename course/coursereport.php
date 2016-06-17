<?php 
//IMathAS:  Course Recent Report
//(c) 2016 David Cooper, David Lippman

/*** master php includes *******/
require("../validate.php");

// this gets points from the scores string
// warning this code was copied from courseshowitems.php
// if something changes with the way scores are stored,
// then this will need to be updated.
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


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($guestid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = _("You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n");
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$cid = $_GET['cid'];
   $oneweekago = strtotime("1 week ago");
   $sincemonday = strtotime("Monday this week");
   $rangestart = $oneweekago;
	
   
		
	$query = "SELECT name,itemorder,hideicons,picicons,allowunenroll,msgset,toolset,chatset,topbar,cploc,latepasshrs FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	if ($line == null) {
		$overwriteBody = 1;
		$body = _("Course does not exist.  <a hre=\"../index.php\">Return to main page</a>") . "</body></html>\n";
	}	


	$query = "select count(distinct userid) as usercount,count(distinct assessmentid) as assessmentcount,count(userid) as totalcount from imas_assessment_sessions join imas_assessments on assessmentid=imas_assessments.id where courseid ='$cid' and endtime > $rangestart";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);

	$usercount = $line['usercount'];
	$assessmentcount = $line['assessmentcount'];
	$totalcount = $line['totalcount'];

	$query = "select count(userid) as totalstudents from imas_students where courseid ='$cid' ";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);

	$totalstudents = $line['totalstudents'];


	//DEFAULT DISPLAY PROCESSING
	$jsAddress1 = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}";
	$jsAddress2 = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	
	
	$curBreadcrumb = $breadcrumbbase;
	$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">$coursename</a>   ";
	$curname = $coursename;

	



}

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {

	if (isset($teacherid)) {
 ?>  
	<script type="text/javascript">
		function moveitem(from,blk) { 
			var to = document.getElementById(blk+'-'+from).value;
			
			if (to != from) {
				var toopen = '<?php echo $jsAddress1 ?>&block=' + blk + '&from=' + from + '&to=' + to;
				window.location = toopen;
			}
		}
		
		function additem(blk,tb) {
			var type = document.getElementById('addtype'+blk+'-'+tb).value;
			if (tb=='BB' || tb=='LB') { tb = 'b';}
			if (type!='') {
				var toopen = '<?php echo $jsAddress2 ?>/add' + type + '.php?block='+blk+'&tb='+tb+'&cid=<?php echo $_GET['cid'] ?>';
				window.location = toopen;
			}
		}


		function highlightrow(el) {
		  el.setAttribute("lastclass",el.className);
		  el.className = "highlight";
		}
		function unhighlightrow(el) {
		  el.className = el.getAttribute("lastclass");
		}

	</script>

<?php
	}	
?>
	<script type="text/javascript">
		var getbiaddr = 'getblockitems.php?cid=<?php echo $cid ?>&folder=';
		var oblist = '<?php echo $oblist ?>';
		var plblist = '<?php echo $plblist ?>';
		var cid = '<?php echo $cid ?>';
	</script> 
	
<?php
	//check for course layout
	if (isset($CFG['GEN']['courseinclude'])) {
		require($CFG['GEN']['courseinclude']);
		if ($firstload) {
			echo "<script>document.cookie = 'openblocks-$cid=' + oblist;\n";
			echo "document.cookie = 'loadedblocks-$cid=0';</script>\n";
		}
		require("../footer.php");
		exit;
	}
?>
	<div class=breadcrumb>
		<?php 
		if (isset($CFG['GEN']['logopad'])) {
			echo '<span class="padright hideinmobile" style="padding-right:'.$CFG['GEN']['logopad'].'">';
		} else {
			echo '<span class="padright hideinmobile">';
		}
		if (isset($guestid)) {
			echo '<span class="red">', _('Instructor Preview'), '</span> ';
		}
		if (!isset($usernameinheader)) {
			echo $userfullname;
		} else { echo '&nbsp;';}
		?>
		</span>
		<?php echo $curBreadcrumb ?>
		<div class=clear></div>
	</div>
<?
   }
?>
   <div>
   
<h3>   In the last week... </h3>
   <table class="gb">
   <tr> <td><? echo $usercount; ?> 
   (out of <? echo $totalstudents ?>) </td><td> Students attempted at least one assessment. </td></tr>
   <tr> <td> <? echo $assessmentcount; ?> </td><td> Assessments were attempted. </td></tr>
   <tr> <td> <? echo $totalcount; ?> </td><td> Total attempts were made. </td></tr>
</table>
   </div>
   <div>
<h3>   Student Summary: </h3>
<?


   $query = "select sid, count(ias.userid)"; // 1,2
   $query .=", group_concat(ia.name) "; // 3
   $query .= ", group_concat(ia.minscore SEPARATOR '#') "; //4
   $query .= ", group_concat(ias.bestscores  SEPARATOR '#') "; //5
   $query .= ", group_concat(ia.id  SEPARATOR '#') "; // 6
   $query .= ", group_concat(ia.defpoints  SEPARATOR '#') "; // 7
   $query .= ", group_concat(ia.itemorder  SEPARATOR '#') "; // 8

   $query .= " from imas_users as iu";
   $query .= " join imas_students as stu on iu.id = stu.userid ";
   $query .= " left join imas_assessment_sessions as ias ";
   $query .= " on iu.id = ias.userid";
   $query .=" left join imas_assessments as ia ";
   $query .= " on assessmentid=ia.id  ";
   $query .= " where iu.id = stu.userid";
   $query .= " or (ia.courseid = '$cid'  ";
   $query .=  "and endtime > $rangestart ) ";
   $query .=" group by iu.sid ";
   $result = mysql_query($query) or die("Query failed : " . mysql_error());
?>
<table class="gb">
<thead><tr>
   <th> Student </th>
   <th> Num Attempts </th>
   <th> Cumulative Score </th>
   <th> No Credit </th>
   <th> Credit </th>
</tr>   </thead><tbody>
   
<?
$st = array();
$asPtsArr = array();
$asPossArr = array();
$i = 0;
while($line = mysql_fetch_row($result)) {
  $st[$i][0] = $line[0];
  $st[$i][1] = $line[1];
  $st[$i][2] = $line[2];
  $st[$i][3] = 0;
  $st[$i][4] = 0;  
  $st[$i][5] = "";
  $st[$i][6] = "";
  $st[$i][7] = 0;  
  $st[$i][8] = 0;  
  $st[$i][9] = "";

  if($st[$i][1] > 0) {
  $assess = explode(',',$st[$i][2]);
  $minscores = explode('#',$line[3]);
  $bestscoresArr = explode('#',$line[4]);
  $aids = explode('#',$line[5]);
  $defpointsArr = explode('#',$line[6]);
  $itemorderArr = explode('#',$line[7]);








  
  $ncc = "";
  $cc = "";
  for($k = 0; $k < count($minscores); $k++) {


    $aitems = explode(',',$itemorderArr[$k]);
    $n = 0;
    $atofind = array();
    foreach ($aitems as $v) {
      if (strpos($v,'~')!==FALSE) {
	$sub = explode('~',$v);
	if (strpos($sub[0],'|')===false) { //backwards compat
	  $atofind[$n] = $sub[0];
	  $aitemcnt[$n] = 1;
	  $n++;
	} else {
	  $grpparts = explode('|',$sub[0]);
	  if ($grpparts[0]==count($sub)-1) { //handle diff point values in group if n=count of group
	    for ($i=1;$i<count($sub);$i++) {
	      $atofind[$n] = $sub[$i];
	      $aitemcnt[$n] = 1;
	      $n++;
	    }
	  } else {
	    $atofind[$n] = $sub[1];
	    $aitemcnt[$n] = $grpparts[0];
	    $n++;
	  }
	}
      } else {
	$atofind[$n] = $v;
	$aitemcnt[$n] = 1;
	$n++;
      }
    }









    $sp = explode(';',$bestscoresArr[$k]);
    $scores = explode(',',$sp[0]);
    $query = "SELECT points,id FROM imas_questions WHERE assessmentid='{$aids[$k]}'";
    $result2 = mysql_query($query) or die("Query failed : $query: " . mysql_error());
    $totalpossible = 0;
    while ($r = mysql_fetch_row($result2)) {
      if (($m = array_search($r[1],$atofind))!==false) { //only use first item from grouped questions for total pts	
	if ($r[0]==9999) {
	  $totalpossible += $aitemcnt[$m]*$defpointsArr[$k]; //use defpoints
	} else {
	  $totalpossible += $aitemcnt[$m]*$r[0]; //use points from question
	}
      }
    }
    $possible[$k] = $totalpossible;
    $asPossArr["'{$aids[$k]}'"] = $possible[$k];
    if (!isset($asPtsArr["'{$aids[$k]}'"])) {
      $asPtsArr["'{$aids[$k]}'"] = 0;
    }

    //    $possible[$k] = $pointsArr[$k];
    $pts = 0;
    for ($l=0;$l<count($scores);$l++) {
      $pts += getpts($scores[$l]);
    }
    if (($minscores[$k]<10000 && $pts<$minscores[$k]) || ($minscores[$k]>10000 && $pts<($minscores[$k]-10000)/100*$possible[$k])) {
    //if ($pts<(0.5*$possible[$k])) {     
      $st[$i][3]++;
      $st[$i][5] .= $ncc;
      $st[$i][5] .= $assess[$k];
      $ncc = ":";
    } else {
      $st[$i][4]++;        
      $st[$i][6] .= $cc;
      $st[$i][6] .= $assess[$k];
      $cc = ":";
    }
    $st[$i][7] = $st[$i][7] + $possible[$k];
    $st[$i][8] = $st[$i][8] + $pts;
    $asPtsArr["'{$aids[$k]}'"] += $pts;
    
  }
  }
  if ($i%2!=0) {
    echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  } else {
    echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  }
   ?>

      <td> <? echo $st[$i][0]; ?> </td>
      <td  align="center"> <? echo $st[$i][1]; ?> </td>
      <td> <? if ($st[$i][7] > 0) {
                $pc = "{$st[$i][8]}/{$st[$i][7]}";
              } else { $pc = "NA"; }
              echo $pc; ?> </td>
      <td align="center"> <? echo $st[$i][3]; ?> </td>
      <td align="center"> <? echo $st[$i][4]; ?> </td>
			       

   </tr>
<?
      
  $i++;
}
$numrows = $i;
?>
</tbody>
</table>
<br>
<table class="gb">
<thead>
   <th> Student </th>
   <th> Num Attempts </th>
   <th> No Credit </th>
<th> Credit </th>
   </thead>
<?
for($i = 0; $i < $numrows; $i++) {
  if ($i%2!=0) {
    echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  } else {
    echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  }

?>
   
      <td> <? echo $st[$i][0]; ?> </td>
      <td align="center"> <? echo $st[$i][1]; ?> </td>
  <td> <? echo "[".str_replace(":","]:[",$st[$i][5])."]"; ?> </td>
  <td> <? echo "[".str_replace(":","]:[",$st[$i][6])."]"; ?> </td>

   </tr>
<? }      ?>
</table>
<h3>   Assessment Summary: </h3>
<?
   $query = "select ia.name, count(userid),ia.id ";
   $query .= " from imas_assessment_sessions join imas_users as iu";
   $query .= " on iu.id = userid join imas_assessments as ia ";
   $query .= " on assessmentid=ia.id where courseid = '$cid' ";
   $query .= " and endtime > $rangestart group by ia.id; ";
   $result = mysql_query($query) or die("Query failed : " . mysql_error());
?>
<table class="gb">
<thead>
   <th> Assessment </th>
   <th> Num Attempts </th>
   <th> Average Score </th>
   <th> No Credit </th>
   <th> Credit </th>
   </thead>
<?
   $atbl = array();
   $k = 0;
while($line = mysql_fetch_row($result)) {
  for ($j = 0; $j < count($line); $j++) {
      $atbl[$k][$j] =  $line[$j];
  }
  $numnc = 0;
  $numcred = 0;
  $credusers = "[";
  $nocredusers = "[";
  for($i = 0; $i < $numrows; $i++) {
    $snocred = explode(":",$st[$i][5]);
    $scred = explode(":",$st[$i][6]);
    if(in_array($line[0],$snocred)) {
      $numnc++;
      $nocredusers .= $st[$i][0];
    } else if(in_array($line[0],$scred)) {
      $numcred++;
      $credusers .= $st[$i][0];
    }
  }
  $nocredusers .= "]";
  $credusers .= "]";
  
  $atbl[$k][3] = $numnc;
  $atbl[$k][4] = $numcred;
  $atbl[$k][5] = $nocredusers;
  $atbl[$k][6] = $credusers;
  if($atbl[$k][1] > 0) {
    $index = "'{$atbl[$k][2]}'";
    //$atbl[$k][7] = $index;
        $atbl[$k][7] = $asPtsArr[$index]*100/$asPossArr[$index];
  } else {
    $atbl[$k][7] = 0;
  }
  if ($k%2!=0) {
    echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  } else {
    echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  }

  ?>
   
    <td> <? echo $line[0] ?> </td>
    <td  align="center"> <? echo $line[1] ?> </td>
      <td  align="center"> <? echo  $atbl[$k][7]."%"; ?> </td>
    <td  align="center"> <? echo $numnc ?> </td>
    <td align="center"> <? echo $numcred ?> </td>
   </tr>
<?
   $k++;

}

?>

</table>
<br>
    <table class="gb">
<thead>
   <th> Assessment </th>
   <th> Num Attempts </th>
<th> No Credit </th>
<th> Credit </th>
   </thead>
<?
    $numrows = $k;
for($i = 0; $i < $numrows; $i++) {
  if ($i%2!=0) {
    echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  } else {
    echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  }

?>
   
      <td> <? echo $atbl[$i][0]; ?> </td>
      <td  align="center"> <? echo $atbl[$i][1]; ?> </td>
      <td> <? echo $atbl[$i][5]; ?> </td>
      <td> <? echo $atbl[$i][6]; ?> </td>

   </tr>
<? }      ?>
</table>



    




   
<?
require("../footer.php");





?>

