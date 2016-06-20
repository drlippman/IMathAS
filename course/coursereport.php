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
	    function toggleList(id) {
	    var list = document.getElementById(id);
	    
	    if (list.style.display == "none"){
	      list.style.display = "";
	    } else {
	      list.style.display = "none";
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
   Click Credit/No Credit numbers for detail.
<?
				  
// line indices for main course report
// AA
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
// end of indices for main course report


// r indices for imas_questions
$pointsI = 0;
$idI     = 1;

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
$aNoCredI     = 3;
$aCredI       = 4;
$aNoCredUI    = 5;
$aCredUI      = 6;
$aScoreI      = 7;

// explode indices
$firstI  = 0;
$secondI = 1;


// NOTE: If you add columns to the query, make sure you add them to the
// end so that indices don't get confused, then add a variable to the
// line indices mentioned in comment AA
   $query = "select FirstName, LastName, sid, ias.userid"; // 0,1,2,3
   $query .=", ia.name "; // 4
   $query .= ", ia.minscore  "; //5
   $query .= ", ias.bestscores   "; //6
   $query .= ", ia.id   "; // 7
   $query .= ", ia.defpoints   "; // 8
   $query .= ", ia.itemorder   "; // 9
// last column. Add additional columns immediately before this comment
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
  } else if( $student != $line[$sidI]) {
    if ($i%2!=0) {
    echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  } else {
    echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  }
   ?>

      <td> <? echo $st[$i][$sNameI]; ?> </td>
      <td  align="center"> <? echo $st[$i][$sAttemptsI]; ?> </td>
      <td> <? if ($st[$i][$sPossI] > 0) {
                $pc = "{$st[$i][$sPtsI]}/{$st[$i][$sPossI]}";
              } else { $pc = "NA"; }
              echo $pc; ?> </td>
       <td align="center">
       <? if ($st[$i][$sNoCredI] > 0) { ?>
	  <p onclick=<? echo "\"toggleList('{$st[$i][$sNameI]}_nc')\">"; 
		        echo $st[$i][$sNoCredI]; ?> </p>
	  <? echo "<ul style=\"display:none\" id='{$st[$i][$sNameI]}_nc'><li>";
	     echo str_replace(":","</li><li>",$st[$i][$sNoCredSI])."</li><ul>";
	  } else {
             echo $st[$i][$sNoCredI];
	  }
	  ?>
	       
       </td>
      <td align="center">
       <? if ($st[$i][$sCredI] > 0) { ?>
	  <p onclick=<? echo "\"toggleList('{$st[$i][$sNameI]}_c')\">"; 
		        echo $st[$i][$sCredI]; ?> </p>
	  <? echo "<ul style=\"display:none\" id='{$st[$i][$sNameI]}_c'><li>";
	     echo str_replace(":","</li><li>",$st[$i][$sCredSI])."</li><ul>";
          } else {
             echo $st[$i][$sCredI];
	  }
	  ?>
      </td>

			       

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
  $student = $line[$sidI];
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
      if (strpos($sub[$firstI],'|')===false) { //backwards compat
	$atofind[$n] = $sub[$firstI];
	$aitemcnt[$n] = 1;
	$n++;
      } else {
	$grpparts = explode('|',$sub[$firstI]);
	if ($grpparts[$firstI]==count($sub)-1) { //handle diff point values in group if n=count of group
	  for ($i=1;$i<count($sub);$i++) {
	    $atofind[$n] = $sub[$i];
	    $aitemcnt[$n] = 1;
	    $n++;
	  }
	} else {
	  $atofind[$n] = $sub[$secondI];
	  $aitemcnt[$n] = $grpparts[$firstI];
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
  $scores = explode(',',$sp[$firstI]);
  $query = "SELECT points,id FROM imas_questions WHERE assessmentid='{$aid}'";
  $result2 = mysql_query($query) or die("Query failed : $query: " . mysql_error());
  $totalpossible = 0;
  while ($r = mysql_fetch_row($result2)) {
    if (($m = array_search($r[$idI],$atofind))!==false) { //only use first item from grouped questions for total pts	
      if ($r[$pointsI]==9999) {
	$totalpossible += $aitemcnt[$m]*$defpoints; //use defpoints
      } else {
	$totalpossible += $aitemcnt[$m]*$r[$pointsI]; //use points from question
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
 
      <td> <? echo $st[$i][$sNameI]; ?> </td>
      <td  align="center"> <? echo $st[$i][$sAttemptsI]; ?> </td>
      <td> <? if ($st[$i][$sPossI] > 0) {
                $pc = "{$st[$i][$sPtsI]}/{$st[$i][$sPossI]}";
              } else { $pc = "NA"; }
              echo $pc; ?> </td>
       <td align="center">
       <? if ($st[$i][$sNoCredI] > 0) { ?>
	  <p onclick=<? echo "\"toggleList('{$st[$i][$sNameI]}_nc')\">"; 
		        echo $st[$i][$sNoCredI]; ?> </p>
	  <? echo "<ul style=\"display:none\" id='{$st[$i][$sNameI]}_nc'><li>";
	     echo str_replace(":","</li><li>",$st[$i][$sNoCredSI])."</li><ul>";
          } else {
             echo $st[$i][$sNoCredI];
	  }
	  ?>
	       
       </td>
      <td align="center">
       <? if ($st[$i][$sCredI] > 0) { ?>
	  <p onclick=<? echo "\"toggleList('{$st[$i][$sNameI]}_c')\">"; 
		        echo $st[$i][$sCredI]; ?> </p>
	  <? echo "<ul style=\"display:none\" id='{$st[$i][$sNameI]}_c'><li>";
	     echo str_replace(":","</li><li>",$st[$i][$sCredSI])."</li><ul>";
          } else {
             echo $st[$i][$sCredI];
	  }
	  ?>
      </td>
			       

   </tr>
      <?
$numrows = $i+1;
?>
</tbody>
</table>
<br>
<h3>   Assessment Summary: </h3>

       Click Credit/No Credit numbers for detail.
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
  $credusers = "<ul style=\"display:none\" id='{$atbl[$k][$aNameI]}_c'>";
  $nocredusers = "<ul style=\"display:none\" id='{$atbl[$k][$aNameI]}_nc'>";
  for($i = 0; $i < $numrows; $i++) {
    $snocred = explode(":",$st[$i][$sNoCredSI]);
    $scred = explode(":",$st[$i][$sCredSI]);
    if(in_array($atbl[$k][$aNameI],$snocred)) {
      $numnc++;
      $nocredusers .= "<li>";
      $nocredusers .= $st[$i][$sNameI];
      $nocredusers .= "</li>";
    } else if(in_array($atbl[$k][$aNameI],$scred)) {
      $numcred++;
      $credusers .= "<li>";
      $credusers .= $st[$i][$sNameI];
      $credusers .= "</li>";
    }
  }
  $nocredusers .= "</ul>";
  $credusers .= "</ul>";

  $atbl[$k][$aNoCredI] = $numnc;
  $atbl[$k][$aCredI] = $numcred;
  $atbl[$k][$aNoCredUI] = $nocredusers;
  $atbl[$k][$aCredUI] = $credusers;
  if($atbl[$k][$aAttemptsI] > 0) {
    $index = "'{$atbl[$k][2]}'";
    //$atbl[$k][$aScoreI] = $index;
        $atbl[$k][$aScoreI] = $asPtsArr[$index]*100/$asPossArr[$index];
  } else {
    $atbl[$k][$aScoreI] = 0;
  }
  if ($k%2!=0) {
    echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  } else {
    echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">"; 
  }

  ?>
   
    <td> <? echo $atbl[$k][$aNameI] ?> </td>
    <td  align="center"> <? echo $atbl[$k][$aAttemptsI] ?> </td>
      <td  align="center"> <? echo  $atbl[$k][$aScoreI]."%"; ?> </td>
       <td align="center">
       <? if ($atbl[$k][$aNoCredI] > 0) { ?>
	  <p onclick=<? echo "\"toggleList('{$atbl[$k][$aNameI]}_nc')\">"; 
		        echo $atbl[$k][$aNoCredI]; ?> </p>
	  <? 
	     echo $atbl[$k][$aNoCredUI];
          } else {
             echo $atbl[$k][$aNoCredI];
	  }
	  ?>
	       
       </td>
      <td align="center">
       <? if ($atbl[$k][$aCredI] > 0) { ?>
	  <p onclick=<? echo "\"toggleList('{$atbl[$k][$aNameI]}_c')\">"; 
		        echo $atbl[$k][$aCredI]; ?> </p>
	  <? echo $atbl[$k][$aCredUI];
          } else {
             echo $atbl[$k][$aCredI];
	  }
	  ?>
      </td>
   </tr>
<?
   $k++;

}

?>

</table>



    




   
<?
require("../footer.php");





?>

