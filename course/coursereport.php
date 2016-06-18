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
// line indices
   $firstNameI  = 0;
   $lastNameI   = 1;
   $sidI        = 2;
   $uidI        = 3;
   $testNameI   = 4;
   $minScoreI   = 5;
   $bestScoreI  = 6;
   $testIdI     = 7;
   $defPointsI  = 8;
   $itemOrderI  = 9;

// $st indices
$sNameI       = 0;
$sAttemptsI   = 1;
$sCumulativeI = 2;
$sNoCredI     = 3;
$sCredI       = 4;
$sNoCredSI    = 5;
$sCredSI      = 6;
$sPossI     = 7;
$sPtsI      = 8;

// $atbl indices
$aNameI       = 0;
$aAttemptsI   = 1;
$aidI         = 2;
$aScoreI      = 3;
$aNoCredI     = 4;
$aCredI       = 5;
$aNoCredUI    = 6;
$aCredUI      = 7;


   $query = "select FirstName, LastName, sid, ias.userid"; // 0,1,2
   $query .=", ia.name "; // 3
   $query .= ", ia.minscore  "; //4
   $query .= ", ias.bestscores   "; //5
   $query .= ", ia.id   "; // 6
   $query .= ", ia.defpoints   "; // 7
   $query .= ", ia.itemorder   "; // 8

   $query .= " from imas_users as iu";
$query .= " join imas_students as stu on iu.id = stu.userid "; // no teachers
   $query .= " left join imas_assessment_sessions as ias ";
   $query .= " on iu.id = ias.userid";
   $query .=" left join imas_assessments as ia ";
   $query .= " on assessmentid=ia.id  ";
   $query .= " where iu.id = stu.userid";
   $query .= " or (ia.courseid = '$cid'  ";
   $query .=  "and endtime > $rangestart ) ";
   $query .=" order by iu.sid ";
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
$asIdxArr = array();
$asNextIdx = 0; 
$i = 0;

$atbl = array();

while($line = mysql_fetch_row($result)) {
  if(!isset($student)) {
    $st[$i][$sAttemptsI] = 0;
    $st[$i][$sNoCredI]   = 0;
    $st[$i][$sCredI]     = 0;
    $st[$i][$sNoCredSI]  = "";
    $st[$i][$sCredSI]    = "";
    $st[$i][$sCPossI]    = 0;  
    $st[$i][$sCPtsI]     = 0;  
  } else if( $student != $line[2]) {
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
    $st[$i][$sAttemptsI] = 0;
    $st[$i][$sNoCredI]   = 0;
    $st[$i][$sCredI]     = 0;
    $st[$i][$sNoCredSI]  = "";
    $st[$i][$sCredSI]    = "";
    $st[$i][$sCPossI]    = 0;  
    $st[$i][$sCPtsI]     = 0;  
    $ncc = "";
    $cc = "";

  }
  $student = $line[2];
  $st[$i][$sNameI]  = $line[$firstNameI];
  $st[$i][$sNameI] .= " ";
  $st[$i][$sNameI] .= $line[$lastNameI];

  if(!is_null($line[$testNameI])) {
  $st[$i][$sAttemptsI]++;
  $assess = $line[$testNameI];
  $minscore = $line[$minScoreI];
  $bestscores = $line[$bestScoreI];
  $aid = $line[$testIdI];
  $defpoints = $line[$defPointsI];
  $aitems = explode(',',$line[$itemOrderI]);
  
  if (!isset($asIdxArr["'{$aid}'"])) {
    $k = $asNextIndex;
    $asIdxArr["'{$aid}'"] = $k;
    $asNextIndex++;
    $atbl[$k][$aNameI]     = $assess;
    $atbl[$k][$aAttemptsI] = 1;
    $atbl[$k][$aidI]       = $aid;
  } else {
    $k = $asIdxArr["'{$aid}'"];
    $atbl[$k][$aAttemptsI]++;
  }

  
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
  
  $sp = explode(';',$bestscores);
  $scores = explode(',',$sp[0]);
  $query = "SELECT points,id FROM imas_questions WHERE assessmentid='{$aid}'";
  $result2 = mysql_query($query) or die("Query failed : $query: " . mysql_error());
  $totalpossible = 0;
  while ($r = mysql_fetch_row($result2)) {
    if (($m = array_search($r[1],$atofind))!==false) { //only use first item from grouped questions for total pts	
      if ($r[0]==9999) {
	$totalpossible += $aitemcnt[$m]*$defpoints; //use defpoints
      } else {
	$totalpossible += $aitemcnt[$m]*$r[0]; //use points from question
      }
    }
    }
    $possible = $totalpossible;
    $asPossArr["'{$aid}'"] = $possible;
    if (!isset($asPtsArr["'{$aid}'"])) {
      $asPtsArr["'{$aid}'"] = 0;
    }

    $pts = 0;
    for ($l=0;$l<count($scores);$l++) {
      $pts += getpts($scores[$l]);
    }
    if (($minscore<10000 && $pts<$minscore) || ($minscore>10000 && $pts<($minscore-10000)/100*$possible)) {
    //if ($pts<(0.5*$possible)) {     
      $st[$i][$sNoCredI]++;
      $st[$i][$sNoCredSI] .= $ncc;
      $st[$i][$sNoCredSI] .= $assess;
      $ncc = ":";
    } else {
      $st[$i][$sCredI]++;        
      $st[$i][$sCredSI] .= $cc;
      $st[$i][$sCredSI] .= $assess;
      $cc = ":";
    }
    $st[$i][$sPossI] = $st[$i][$sPossI] + $possible;
    $st[$i][$sPtsI] = $st[$i][$sPtsI] + $pts;
    $asPtsArr["'{$aid}'"] += $pts;
    
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
$numrows = $i+1;
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
  <td> <? echo "<ul><li>".str_replace(":","</li><li>",$st[$i][5])."</li><ul>"; ?> </td>
  <td> <? echo "<ul><li>".str_replace(":","</li><li>",$st[$i][6])."</li><ul>"; ?> </td>

   </tr>
<? }      ?>
</table>
<h3>   Assessment Summary: </h3>
<table class="gb">
<thead>
   <th> Assessment </th>
   <th> Num Attempts </th>
   <th> Average Score </th>
   <th> No Credit </th>
   <th> Credit </th>
   </thead>
<?
foreach ($asIdxArr as $k) {
  $numnc = 0;
  $numcred = 0;
  $credusers = "<ul>";
  $nocredusers = "<ul>";
  for($i = 0; $i < $numrows; $i++) {
    $snocred = explode(":",$st[$i][5]);
    $scred = explode(":",$st[$i][6]);
    if(in_array($atbl[$k][0],$snocred)) {
      $numnc++;
      $nocredusers .= "<li>";
      $nocredusers .= $st[$i][0];
      $nocredusers .= "</li>";
    } else if(in_array($atbl[$k][0],$scred)) {
      $numcred++;
      $credusers .= "<li>";
      $credusers .= $st[$i][0];
      $credusers .= "</li>";
    }
  }
  $nocredusers .= "</ul>";
  $credusers .= "</ul>";
  
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
   
    <td> <? echo $atbl[$k][0] ?> </td>
    <td  align="center"> <? echo $atbl[$k][1] ?> </td>
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

