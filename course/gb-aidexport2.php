<?php
//IMathAS: Pull Student responses on an assessment (assess2)
//(c) 2019 David Lippman

require("../init.php");
require("../assess2/AssessInfo.php");
require("../assess2/AssessRecord.php");

$isteacher = isset($teacherid);
$cid = Sanitize::courseId($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if (!$isteacher) {
	echo "This page not available to students";
	exit;
}
$doemail = $dopts = $doptpts = $doraw = $doptraw = $doba = $dobca = $dola = false;
if (isset($_POST['options'])) {
	//ready to output
	$outcol = 0;
    if (isset($_POST['email'])) { $doemail = true;}
	if (isset($_POST['pts'])) { $dopts = true; $outcol++;}
    if (isset($_POST['ptpts'])) { $doptpts = true; $outcol++;}
    if (isset($_POST['raw'])) { $doraw = true; $outcol++;}
	if (isset($_POST['ptraw'])) { $doptraw = true; $outcol++;}
	if (isset($_POST['ba'])) { $doba = true; $outcol++;}
	if (isset($_POST['bca'])) { $dobca = true; $outcol++;}
	if (isset($_POST['la'])) { $dola = true; $outcol++;}

	//get assessment info
	$assess_info = new AssessInfo($DBH, $aid, $cid, false);
	$assess_info->loadQuestionSettings('all', $dobca, false); // only load code if we need answers

	$itemorder = $assess_info->getSetting('itemorder');
	$itemarr = array();
	$itemnum = array();
	foreach ($itemorder as $k=>$itel) {
		if (is_array($itel)) {
			foreach ($itel['qids'] as $j=>$itsub) {
				$itemarr[] = $itsub;
				$itemnum[$itsub] = ($k+1).'-'.($j+1);
			}
		} else {
			$itemarr[] = $itel;
			$itemnum[$itel] = ($k+1);
		}
	}

	$query = "SELECT COUNT(imas_users.id) FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid ";
	$query .= "AND imas_students.courseid=:courseid AND imas_students.section IS NOT NULL";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	if ($stm->fetchColumn(0)>0) {
		$hassection = true;
	} else {
		$hassection = false;
	}

	$gb = array();
	//create headers
	$gb[0][0] = "Name";
	$gb[1][0] = "";
	if ($hassection) {
		$gb[0][1] = "Section";
		$gb[1][1] = "";
		$initoffset = 2;
	} else {
		$initoffset = 1;
	}
    if ($doemail) {
        $gb[0][$initoffset] = "Email";
        $gb[1][$initoffset] = "";
        $initoffset++;
    }

	$qpts = $assess_info->getAllQuestionPoints();
	$qcol = array();
	foreach ($itemarr as $k=>$q) {
		$qcol[$q] = $initoffset + $outcol*$k;
		$offset = 0;
		if ($dopts) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Points (".$qpts[$q]." possible)";
			$offset++;
		}
		if ($doptpts) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Part Points (".$qpts[$q]." possible)";
			$offset++;
        }
        if ($doraw) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Raw";
			$offset++;
		}
		if ($doptraw) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Part Raw";
			$offset++;
		}
		if ($doba) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Scored Answer";
			$offset++;
		}
		if ($dobca) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Scored Correct Answer";
			$offset++;
		}
		if ($dola) {
			$gb[0][$initoffset + $outcol*$k + $offset] = "Question ".$itemnum[$q];
			$gb[1][$initoffset + $outcol*$k + $offset] = "Last Answer";
			$offset++;
		}
	}

	//create row headers
	$query = "SELECT iu.id,iu.FirstName,iu.LastName,imas_students.section,iu.email FROM imas_users AS iu JOIN ";
	$query .= "imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid=:courseid ";
	if ($hassection) {
		$query .= "ORDER BY imas_students.section,iu.LastName, iu.FirstName";
	} else {
		$query .= "ORDER BY iu.LastName, iu.FirstName";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	$r = 2;
	$sturow = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$gb[$r] = array_fill(0,count($gb[0]),'');
		$gb[$r][0] = $row[2].', '.$row[1];
		if ($hassection) {
			$gb[$r][1] = $row[3];
		}
        if ($doemail) {
            $gb[$r][$hassection? 2 : 1] = $row[4];
        }
		$sturow[$row[0]] = $r;
		$r++;
	}

	//pull assessment data
    $query = "SELECT iar.* FROM imas_assessment_records AS iar
                JOIN imas_students ON imas_students.userid = iar.userid
              WHERE iar.assessmentid = :assessmentid
                AND imas_students.courseid = :courseid";
    $stm = $DBH->prepare($query);
    $stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid));
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $GLOBALS['assessver'] = $row['ver'];

        $assess_record = new AssessRecord($DBH, $assess_info, false);
        $assess_record->setRecord($row);
        $assess_record->setTeacherInGb(true);

        if (!isset($sturow[$row['userid']])) {
            continue;
        }

        $r = $sturow[$row['userid']];

        // Get question objects.  This returns a lot more than we need.
        // The 2 for generate_html tells it to tack on the 'ans' and 'stuans'
        // to jsparams.
        $question_objects = $assess_record->getAllQuestionObjects(true, true, $dobca ? 2 : false, 'scored');
        list($questionIds, $toloadqids) = $assess_record->getQuestionIds(range(0,count($question_objects)-1), 'scored');
        if (!$dobca && $doba) {
            list($stuanswers, $stuanswersval) = $assess_record->getStuanswers('scored');
        }
        for ($qn = 0; $qn < count($question_objects); $qn++) {
            $question_object = $question_objects[$qn];

            if ($dobca) {
                $correctAns = $question_object['jsparams']['ans'];
                $stuAns = $question_object['jsparams']['stuans'];
            } else if ($doba) {
                $stuAns = $stuanswers[$qn+1];
            }

            $qscore = array();
            $qatt = array();


            for ($pn = 0; $pn < count($question_object['parts']); $pn++) {
                $partinfo = $question_object['parts'][$pn];
                if (isset($partinfo['score']) && $partinfo['score']>=0) {
                    $qscore[$pn] = $partinfo['score'];
                } else {
                    $qscore[$pn] = 0;
                }
                if (isset($partinfo['rawscore']) && $partinfo['rawscore']>=0) {
                    $raw[$pn] = $partinfo['rawscore'];
                } else {
                    $raw[$pn] = 0;
                }
            }
            

            $c = $qcol[$questionIds[$qn]];
            $offset = 0;
            if ($dopts) {
                $gb[$r][$c + $offset] = array_sum($qscore);
                $offset++;
            }
            if ($doptpts) {
                $gb[$r][$c + $offset] = implode('~', $qscore);
                $offset++;
            }
            if ($doraw) {
                $gb[$r][$c + $offset] = $question_object['rawscore'];
                $offset++;
            }
            if ($doptraw) {
                $gb[$r][$c + $offset] = implode('~', $raw);
                $offset++;
            }
            if ($doba) {
                $gb[$r][$c + $offset] = is_array($stuAns) ? implode('&', $stuAns) : $stuAns;
                $offset++;
            }
            if ($dobca) {
                $gb[$r][$c + $offset] = is_array($correctAns) ? implode('&', $correctAns) : $correctAns;
            }
        }
    }

	header('Content-type: text/csv');
	header("Content-Disposition: attachment; filename=\"aexport-$aid.csv\"");
	foreach ($gb as $gbline) {
		$line = '';
        if (empty($gbline)) { 
            continue;
        }
		foreach ($gbline as $val) {
            if (is_null($val)) {
                $val = '';
            }
			 # remove any windows new lines, as they interfere with the parsing at the other end
			  $val = str_replace("\r\n", "\n", $val);
			  $val = str_replace("\n", " ", $val);
			  $val = str_replace(array("<BR>",'<br>','<br/>'), ' ',$val);
			  $val = str_replace("&nbsp;"," ",$val);
              $val = Sanitize::stripHtmlTags($val);
              
			  # if a deliminator char, a double quote char or a newline are in the field, add quotes
			  if(preg_match("/[\,\"\n\r]/", $val)) {
				  $val = '"'.str_replace('"', '""', $val).'"';
			  }
			  $line .= $val.',';
		}
		# strip the last deliminator
		$line = substr($line, 0, -1);
		$line .= "\n";
		echo $line;
	}
	exit;
} else {
	//ask for options
	$pagetitle = "Assessment Export";
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; <a href=\"gb-itemanalysis2.php?aid=$aid&cid=$cid\">Item Analysis</a> ";
	echo '&gt; Assessment Export</div>';
	echo '<div id="headergb-aidexport" class="pagetitle"><h1>Assessment Results Export</h1></div>';

	echo "<form method=\"post\" action=\"gb-aidexport2.php?aid=$aid&cid=$cid\">";
	echo 'What do you want to include in the export:<br/>';
	echo '<input type="checkbox" name="pts" value="1"/> Points earned<br/>';
    echo '<input type="checkbox" name="ptpts" value="1"/> Multipart broken-down Points earned<br/>';
    echo '<input type="checkbox" name="raw" value="1"/> Raw score<br/>';
	echo '<input type="checkbox" name="ptraw" value="1"/> Multipart broken-down raw score<br/>';
	echo '<input type="checkbox" name="ba" value="1"/> Scored Attempt<br/>';
	echo '<input type="checkbox" name="bca" value="1"/> Correct Answers for Scored Attempt<br/>';
    echo '<input type="checkbox" name="email" value="1"/> Email Addresses<br/>';
	echo '<input type="submit" name="options" value="Export" />';
	echo '<p>Export will be a commas separated values (.CSV) file, which can be opened in Excel</p>';
	//echo '<p class="red"><b>Note</b>: Attempt information from shuffled multiple choice, multiple answer, and matching questions will NOT be correct</p>';
	echo '</form>';

	require("../footer.php");

}
?>
