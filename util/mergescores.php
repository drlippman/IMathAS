<?php

require("../init.php");

if ($myrights<100) {
    exit;
}

$cid = intval($_GET['cid']);
if ($cid==0) {
    exit;
}

require("../header.php");
if (isset($_POST['assess'])) {
    $dc = 0;
    $source = array();
    foreach ($_POST['assess'] as $aid=>$mark) {
        if ($mark=='*') {
            $dc++;
            $dest = intval($aid);
        } else if ($mark=='-') {
            $source[] = intval($aid);
        }
    }
    $err = false;
    if ($dc==0) {
        echo 'no main course designated';
        $err = true;
    } else if ($dc>1) {
        echo 'too many courses marked with *; only mark one';
        $err = true;
    } else if (count($source)==0) {
        echo 'no courses to merge FROM were marked';
        $err = true;
    } else {
        $query = "SELECT itemorder FROM imas_assessments WHERE id=$dest";
        //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
        $stm = $DBH->query($query); //presanitized
        //DB $sourceitemord = mysql_result($result,0,0);
        $sourceitemord = $stm->fetchColumn(0);
        $query = "SELECT itemorder,name FROM imas_assessments WHERE id IN (".implode(',',$source).")";
        //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
        $stm = $DBH->query($query); //presanitized
        //DB while ($row = mysql_fetch_row($result)) {
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            if (substr_count($row[0],',') != substr_count($sourceitemord,',')) {
                echo 'one of this things is not like the others.... '.Sanitize::encodeStringForDisplay($row[1]).' does not match same number of questions.   assessments cannot be merged';
                $err = true;
                break;
            }
        }
    }
    if (!$err) {
        $query = "SELECT userid,bestseeds,bestscores,bestattempts,bestlastanswers FROM imas_assessment_sessions WHERE assessmentid=$dest";
        //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
        $stm = $DBH->query($query); //presanitized
      	$adata = array();
        //DB while ($row = mysql_fetch_row($result)) {
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
      		$adata[$row[0]] = array();
      		$adata[$row[0]]['seeds'] = explode(',',$row[1]);
      		$sp = explode(';', $row[2]);
      		$adata[$row[0]]['scores'] = explode(',',$sp[0]);
      		if (count($sp)>1) {
      			$adata[$row[0]]['rawscore'] = explode(',',$sp[1]);
      			$adata[$row[0]]['firstscore'] = explode(',',$sp[1]);
      		}
      		$adata[$row[0]]['attempts'] = explode(',',$row[3]);
      		$adata[$row[0]]['la'] = explode('~',$row[4]);
      	}

      	$query = "SELECT userid,bestseeds,bestscores,bestattempts,bestlastanswers FROM imas_assessment_sessions WHERE assessmentid IN (".implode(',',$source).")";
        //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
        $stm = $DBH->query($query); //presanitized
      	//DB while ($row = mysql_fetch_row($result)) {
      	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
      		$seeds = explode(',',$row[1]);
      		$sp = explode(';', $row[2]);
      		$scores = explode(',',$sp[0]);
      		if (count($sp)>1) {
      			$rawscore = explode(',',$sp[1]);
      			$firstscore = explode(',',$sp[1]);
      		}
      		$att = explode(',',$row[3]);
      		$la = explode(',',$row[4]);
      		foreach ($scores as $k=>$v) {
      			if (getpts($v)>getpts($adata[$row[0]]['scores'][$k])) {
      				$adata[$row[0]]['scores'][$k] = $scores[$k];
      				if (isset($rawscore) && isset($rawscore[$k])) {
      					$adata[$row[0]]['rawscore'][$k] = $rawscore[$k];
      					$adata[$row[0]]['firstscore'][$k] = $firstscore[$k];
      				}
      				$adata[$row[0]]['seeds'][$k] = $seeds[$k];
      				$adata[$row[0]]['attempts'][$k] = $attempts[$k];
      				$adata[$row[0]]['la'][$k] = $la[$k];
      			}
      		}
      	}
        $query = "UPDATE imas_assessment_sessions SET bestseeds=:bestseeds,bestattempts=:bestattempts,bestscores=:bestscores,bestlastanswers=:bestlastanswers ";
        $query .= "WHERE userid=:userid AND assessmentid=:assessmentid";
        $stm = $DBH->prepare($query);
      	foreach ($adata as $uid=>$val) {
      		$bestscorelist = implode(',',$val['scores']);
      		if (isset($val['rawscore'])) {
      			$bestscorelist .= ';'.implode(',',$val['rawscore']).';'.implode(',',$val['firstscore']);
      		}
      		$bestattemptslist = implode(',',$val['attempts']);
      		$bestseedslist = implode(',',$val['seeds']);
      		$bestlalist = implode('~',$val['la']);
      		//DB $bestlalist = addslashes(stripslashes($bestlalist));
      		//DB $query = "UPDATE imas_assessment_sessions SET bestseeds='$bestseedslist',bestattempts='$bestattemptslist',bestscores='$bestscorelist',bestlastanswers='$bestlalist' ";
      		//DB $query .= "WHERE userid='$uid' AND assessmentid='$dest'";

      		$stm->execute(array(':bestseeds'=>$bestseedslist, ':bestattempts'=>$bestattemptslist, ':bestscores'=>$bestscorelist, ':bestlastanswers'=>$bestlalist, ':userid'=>$uid, ':assessmentid'=>$dest));
      		//DB mysql_query($query) or die("Query failed : " . mysql_error());
      	}
      	echo "Merge complete";
    }


} else {
    echo '<p>This page will merge scores from multiple copies of the same assessment.';
    echo '  Place a * in the box next to the assessment you want to designate as the main ';
    echo 'assessment.  Place a - in the boxes next to the assessments whose scores you want ';
    echo 'to copy TO the main assessment.  These assessments will not be deleted in this process; ';
    echo 'their scores will simply be transfered to the main assessment.</p>';

    echo '<form method="post" action="mergescores.php?cid='.$cid.'">';
    $query = "SELECT id,name FROM imas_assessments WHERE courseid=$cid ORDER BY name";
    //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
    $stm = $DBH->query($query); //presanitized
    echo '<p>';
    //DB while ($row = mysql_fetch_row($result)) {
    while ($row = $stm->fetch(PDO::FETCH_NUM)) {
        echo '<input type="input" size="1" name="assess['.Sanitize::encodeStringForDisplay($row[0]).']" />'.Sanitize::encodeStringForDisplay($row[1]).'<br/>';
    }
    echo '</p>';
    echo '<p><input type="submit" value="Submit"/></p>';
    echo '</form>';
}
require("../footer.php");

function getpts($sc) {
	if (strpos($sc,'~')===false) {
		if ($sc>0) {
			return $sc;
		} else {
			return 0;
		}
	} else {
		$sc = explode('~',$sc);
		$tot = 0;
		foreach ($sc as $s) {
			if ($s>0) {
				$tot+=$s;
			}
		}
		return round($tot,1);
	}
}
?>
