<?php

use app\components\AppUtility;

use app\components\filehandler;

$aname = $assessmentData['name'];$defpoints = $assessmentData['defpoints'];
$isgroup = $assessmentData['isgroup']; $groupsetid = $assessmentData['groupsetid']; $deffbtext = $assessmentData['deffeedbacktext'];
$points = $questionData['points'];

$qcontrol = $questionData['control'];
$rubric = $questionData['rubric'];
$qtype = $questionData['qtype'];
if ($points==9999) {
    $points = $defpoints;
}
	$useeditor='review';
	$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js?v=120311"></script>';
	$placeinhead .= "<script type=\"text/javascript\">";
	$placeinhead .= 'function jumptostu() { ';
	$placeinhead .= '       var stun = document.getElementById("stusel").value; ';
	$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradeallq.php?stu=$stu&cid=$cid&gbmode=$gbmode&aid=$aid&qid=$qid&ver=$ver";
	$placeinhead .= "       var toopen = '$address&page=' + stun;\n";
	$placeinhead .= "  	window.location = toopen; \n";
	$placeinhead .= "}\n";
	$placeinhead .= '</script>';

	$sessiondata['coursetheme'] = $coursetheme;

	echo "<style type=\"text/css\">p.tips {	display: none;}\n .hideongradeall { display: none;}</style>\n";
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
	echo "&gt; <a href=\"gb-itemanalysis.php?stu=$stu&cid=$cid&aid=$aid\">Item Analysis</a> ";
	echo "&gt; Grading a Question</div>";
	echo "<div id=\"headergradeallq\" class=\"pagetitle\"><h2>Grading a Question in $aname</h2></div>";
	echo "<p><b>Warning</b>: This page may not work correctly if the question selected is part of a group of questions</p>";
	echo '<div class="cpmid">';
	if ($page==-1)
    { ?>
         <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/grade-all-question?stu='.$stu.'&gbmode='.$gbmode.'&cid='.$cid.'&aid='.$aid.'&qid='.$qid.'&page=0') ?>">Grade one student at a time</a> (Do not use for group assignments)
    <?php } else { ?>
         <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/grade-all-question?stu='.$stu.'&gbmode='.$gbmode.'&cid='.$cid.'&aid='.$aid.'&qid='.$qid.'&page=-1')?>">Grade all students at once</a>
    <?php }
	echo '</div>';
	echo "<p>Note: Feedback is for whole assessment, not the individual question.</p>";
	echo '
	<script type="text/javascript">
	function hidecorrect() {
		var butn = $("#hctoggle");
		if (!butn.hasClass("hchidden")) {
			butn.html("'._('Show Questions with Perfect Scores').'");
			butn.addClass("hchidden");
		} else {
			butn.html("'._('Hide Questions with Perfect Scores').'");
			butn.removeClass("hchidden");
		}
		$(".iscorrect").toggle();
	}
	function hidenonzero() {
		var butn = $("#nztoggle");
		if (!butn.hasClass("nzhidden")) {
			butn.html("'._('Show Nonzero Score Questions').'");
			butn.addClass("nzhidden");
		} else {
			butn.html("'._('Hide Nonzero Score Questions').'");
			butn.removeClass("nzhidden");
		}
		$(".isnonzero").toggle();
	}
	function hideNA() {
		var butn = $("#hnatoggle");
		if (!butn.hasClass("hnahidden")) {
			butn.html("'._('Show Unanswered Questions').'");
			butn.addClass("hnahidden");
		} else {
			butn.html("'._('Hide Unanswered Questions').'");
			butn.removeClass("hnahidden");
		}
		$(".notanswered").toggle();
	}';
?>

function preprint()
{
$("span[id^='ans']").removeClass("hidden");
$(".sabtn").replaceWith("<span>Answer: </span>");
$('input[value="Preview"]').trigger('click').remove();
document.getElementById("preprint").style.display = "none";
}
function hidegroupdup(el) {  //el.checked = one per group
var divs = document.getElementsByTagName("div");
for (var i=0;i<divs.length;i++) {
if (divs[i].className=="groupdup") {
if (el.checked) {
divs[i].style.display = "none";
} else { divs[i].style.display = "block"; }
}
}
var hfours = document.getElementsByTagName("h4");
for (var i=0;i<hfours.length;i++) {
if (hfours[i].className=="person") {
hfours[i].style.display = el.checked?"none":"";
} else if (hfours[i].className=="group") {
hfours[i].style.display = el.checked?"":"none";
}
}
var spans = document.getElementsByTagName("span");
for (var i=0;i<spans.length;i++) {
if (spans[i].className=="person") {
spans[i].style.display = el.checked?"none":"";
} else if (spans[i].className=="group") {
spans[i].style.display = el.checked?"":"none";
}
}
}
function clearfeedback() {
var els=document.getElementsByTagName("textarea");
for (var i=0;i<els.length;i++) {
if (els[i].id.match(/feedback/)) {
els[i].value = '';
}
}
}
function cleardeffeedback() {
var els=document.getElementsByTagName("textarea");
for (var i=0;i<els.length;i++) {
if (els[i].value=='<?php echo str_replace("'","\\'",$deffbtext); ?>') {
els[i].value = '';
}
}
}
function showallans() {
$("span[id^=\'ans\']").removeClass("hidden");
$(".sabtn").replaceWith("<span>Answer: </span>");
}
</script>
<?php

	if (count($rubricFinalData)>0) {
		echo printrubrics(array($rubricFinalData));
	}
	if ($page==-1)
	{
		echo '<button type=button id="hctoggle" onclick="hidecorrect()">'._('Hide Questions with Perfect Scores').'</button>';
		echo '<button type=button id="nztoggle" onclick="hidenonzero()">'._('Hide Nonzero Score Questions').'</button>';
		echo ' <button type=button id="hnatoggle" onclick="hideNA()">'._('Hide Unanswered Questions').'</button>';
		echo ' <button type="button" id="preprint" onclick="preprint()">'._('Prepare for Printing (Slow)').'</button>';
		echo ' <button type="button" id="showanstoggle" onclick="showallans()">'._('Show All Answers').'</button>';
	}
	echo ' <input type="button" id="clrfeedback" value="Clear all feedback" onclick="clearfeedback()" />';
	if ($deffbtext != '')
	{
		echo ' <input type="button" id="clrfeedback" value="Clear default feedback" onclick="cleardeffeedback()" />';
	}
	echo "<form id=\"mainform\" method=post action=\"grade-all-question?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&qid=$qid&page=$page&update=true\">\n";
	if ($isgroup>0) {
		echo '<p><input type="checkbox" name="onepergroup" value="1" onclick="hidegroupdup(this)" /> Grade one per group</p>';
	}
	echo "<p>";
	if ($ver=='graded') {
		echo "<b>Showing Graded Attempts.</b>  ";
        echo "<a href=\"grade-all-question?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&qid=$qid&ver=last\">Show Last Attempts</a>";

  } else if ($ver=='last') {
		echo "<a href=\"grade-all-question?stu=$stu&gbmode=$gbmode&cid=$cid&aid=$aid&qid=$qid&ver=graded\">Show Graded Attempts</a>.  ";
		echo "<b>Showing Last Attempts.</b>  ";
		echo "<br/><b>Note:</b> Grades and number of attempts used are for the Graded Attempt.  Part points might be inaccurate.";
	 }
	echo "</p>";

	$cnt = 0;
	$onepergroup = array();

	if (count($assessmentSessionData)>0)
	{

	foreach($assessmentSessionData as $line)
	{

			if ($page != -1) {
			echo '<input type="hidden" name="userid" value="'.$line['userid'].'"/>';
		}
		$asid = $line['id'];
		$groupdup = false;
		if ($line['agroupid']>0) {
			$s3asid = 'grp'.$line['agroupid'].'/'.$aid;
			if (isset($onepergroup[$line['agroupid']])) {
				$groupdup = true;
			} else {
				echo "<input type=\"hidden\" name=\"groupasid[{$line['agroupid']}]\" value=\"{$line['id']}\" />";
				$onepergroup[$line['agroupid']] = $line['id'];
			}
		} else {
			if ($isgroup) {
				$groupdup = true;
			}
			$s3asid = $asid;
		}
		if (strpos($line['questions'],';')===false) {
			$questions = explode(",",$line['questions']);
			$bestquestions = $questions;
		} else {
			list($questions,$bestquestions) = explode(";",$line['questions']);
			$questions = explode(",",$questions);
			$bestquestions = explode(",",$bestquestions);
		}
		$sp = explode(';', $line['bestscores']);
		$scores = explode(",",$sp[0]);
		$attempts = explode(",",$line['bestattempts']);
		if ($ver=='graded')
		{
			$seeds = explode(",",$line['bestseeds']);
			$la = explode("~",$line['bestlastanswers']);
			$questions = $bestquestions;
		} else if ($ver=='last')
		{
			$seeds = explode(",",$line['seeds']);
			$la = explode("~",$line['lastanswers']);
		}

		//$loc = array_search($qid,$questions);
		$lockeys = array_keys($questions,$qid);

		foreach ($lockeys as $loc) {
			if ($groupdup) {
				echo '<div class="groupdup">';
			}
			echo "<p><span class=\"person\"><b>".$line['LastName'].', '.$line['FirstName'].'</b></span>';
			if ($page != -1) {
				echo '.  Jump to <select id="stusel" onchange="jumptostu()">';
				foreach ($stulist as $i=>$st) {
					echo '<option value="'.$i.'" ';
					if ($i==$page) {echo 'selected="selected"';}
					echo '>'.$st.'</option>';
				}
				echo '</select>';
			}
			echo '</p>';
			if (!$groupdup) {
				echo '<h4 class="group" style="display:none">'.$groupnames[$line['agroupid']];
				if (isset($groupmembers[$line['agroupid']]) && count($groupmembers[$line['agroupid']])>0) {
					echo ' ('.implode(', ',$groupmembers[$line['agroupid']]).')</h4>';
				} else {
					echo ' (empty)</h4>';
				}
			}
			echo "<div ";
			if (getpts($scores[$loc])==$points) {
				echo 'class="iscorrect"';
			} else if ($scores[$loc]>0) {
				echo 'class="isnonzero"';
			} else if ($scores[$loc]==-1) {
				echo 'class="notanswered"';
			} else {
				echo 'class="iswrong"';
			}
			echo '>';
			$lastanswers[$cnt] = $la[$loc];
			$teacherreview = $line['userid'];

			if ($qtype=='multipart') {
				/*if (($p = strpos($qcontrol,'answeights'))!==false) {
					$p = strpos($qcontrol,"\n",$p);
					$answeights = getansweights($loc,substr($qcontrol,0,$p));
				} else {
					preg_match('/anstypes(.*)/',$qcontrol,$match);
					$n = substr_count($match[1],',')+1;
					if ($n>1) {
						$answeights = array_fill(0,$n-1,round(1/$n,3));
						$answeights[] = 1-array_sum($answeights);
					} else {
						$answeights = array(1);
					}
				}
				*/
				$answeights = getansweights($loc,$qcontrol);
				for ($i=0; $i<count($answeights)-1; $i++) {
					$answeights[$i] = round($answeights[$i]*$points,2);
				}
				//adjust for rounding
				$diff = $points - array_sum($answeights);
				$answeights[count($answeights)-1] += $diff;
			}

			if ($qtype=='multipart') {
				$GLOBALS['questionscoreref'] = array("ud-{$line['id']}-$loc",$answeights);
			} else {
				$GLOBALS['questionscoreref'] = array("ud-{$line['id']}-$loc",$points);
			}
            $temp = array();
            global $temp;
			$qtypes = displayq($cnt,$qsetid,$seeds[$loc],true,false,$attempts[$loc]);
            echo $temp;
			echo '</div>';

			echo "<div class=review>";
			echo '<span class="person">'.$line['LastName'].', '.$line['FirstName'].': </span>';
			if (!$groupdup) {
				echo '<span class="group" style="display:none">'.$groupnames[$line['agroupid']].': </span>';
			}
			if ($isgroup) {

			}
			list($pt,$parts) = printscore($scores[$loc]);

			if ($parts=='') {
				if ($pt==-1) {
					$pt = 'N/A';
				}
				echo "<input type=text size=4 id=\"ud-{$line['id']}-$loc\" name=\"ud-{$line['id']}-$loc\" value=\"$pt\">";
				if ($rubric != 0) {
					echo printrubriclink($rubric,$points,"ud-{$line['id']}-$loc","feedback-{$line['id']}",($loc+1));
				}
			}
			if ($parts!='') {
				echo " Parts: ";
				$prts = explode(', ',$parts);
				for ($j=0;$j<count($prts);$j++) {
					if ($prts[$j]==-1) {
						$prts[$j] = 'N/A';
					}
					echo "<input type=text size=2 id=\"ud-{$line['id']}-$loc-$j\" name=\"ud-{$line['id']}-$loc-$j\" value=\"{$prts[$j]}\">";
					if ($rubric != 0) {
						echo printrubriclink($rubric,$answeights[$j],"ud-{$line['id']}-$loc-$j","feedback-{$line['id']}",($loc+1).' pt '.($j+1));
					}
					echo ' ';
				}

			}
			echo " out of $points ";

			if ($parts!='') {
				$answeights = implode(', ',$answeights);
				echo "(parts: $answeights) ";
			}
			echo "in {$attempts[$loc]} attempt(s)\n";
			if ($parts!='') {
				$togr = array();
				foreach ($qtypes as $k=>$t) {
					if ($t=='essay' || $t=='file') {
						$togr[] = $k;
					}
				}
				echo '<br/>Quick grade: <a href="#" onclick="quickgrade('.$loc.',0,\'ud-'.$line['id'].'-\','.count($prts).',['.$answeights.']);return false;">Full credit all parts</a>';
				if (count($togr)>0) {
					$togr = implode(',',$togr);
					echo ' | <a href="#" onclick="quickgrade('.$loc.',1,\'ud-'.$line['id'].'-\',['.$togr.'],['.$answeights.']);return false;">Full credit all manually-graded parts</a>';
				}
			} else {
				echo '<br/>Quick grade: <a href="#" onclick="quicksetscore(\'ud-'.$line['id'].'-'.$loc.'\','.$points.');return false;">Full credit</a>';
			}
			$laarr = explode('##',$la[$loc]);
			if (count($laarr)>1) {
				echo "<br/>Previous Attempts:";
				$cntb =1;
				for ($k=0;$k<count($laarr)-1;$k++) {
					if ($laarr[$k]=="ReGen") {
						echo ' ReGen ';
					} else {
						echo "  <b>$cntb:</b> " ;
						if (preg_match('/@FILE:(.+?)@/',$laarr[$k],$match)) {
							$url =  filehandler::getasidfileurl($match[1]);
							echo "<a href=\"$url\" target=\"_new\">".basename($match[1])."</a>";
						} else {
							if (strpos($laarr[$k],'$f$')) {
								if (strpos($laarr[$k],'&')) { //is multipart q
									$laparr = explode('&',$laarr[$k]);
									foreach ($laparr as $lk=>$v) {
										if (strpos($v,'$f$')) {
											$tmp = explode('$f$',$v);
											$laparr[$lk] = $tmp[0];
										}
									}
									$laarr[$k] = implode('&',$laparr);
								} else {
									$tmp = explode('$f$',$laarr[$k]);
									$laarr[$k] = $tmp[0];
								}
							}
							if (strpos($laarr[$k],'$!$')) {
								if (strpos($laarr[$k],'&')) { //is multipart q
									$laparr = explode('&',$laarr[$k]);
									foreach ($laparr as $lk=>$v) {
										if (strpos($v,'$!$')) {
											$tmp = explode('$!$',$v);
											$laparr[$lk] = $tmp[0];
										}
									}
									$laarr[$k] = implode('&',$laparr);
								} else {
									$tmp = explode('$!$',$laarr[$k]);
									$laarr[$k] = $tmp[0];
								}
							}
							if (strpos($laarr[$k],'$#$')) {
								if (strpos($laarr[$k],'&')) { //is multipart q
									$laparr = explode('&',$laarr[$k]);
									foreach ($laparr as $lk=>$v) {
										if (strpos($v,'$#$')) {
											$tmp = explode('$#$',$v);
											$laparr[$lk] = $tmp[0];
										}
									}
									$laarr[$k] = implode('&',$laparr);
								} else {
									$tmp = explode('$#$',$laarr[$k]);
									$laarr[$k] = $tmp[0];
								}
							}
							echo str_replace(array('&','%nbsp;','%%','<','>'),array('; ','&nbsp;','&','&lt;','&gt;'),strip_tags($laarr[$k]));
						}
						$cntb++;
					}
				}
			}
			echo "<br/>Feedback: <textarea cols=50 rows=".($page==-1?1:3)." id=\"feedback-{$line['id']}\" name=\"feedback-{$line['id']}\">{$line['feedback']}</textarea>";
			echo '<br/>Question #'.($loc+1);
			echo ". <a target=\"_blank\" href=\"#\">Use in Msg</a>";
			echo "</div>\n";
			if ($groupdup)
			{
				echo '</div>';
			}
			$cnt++;
		}
	}
	echo "<input type=\"submit\" value=\"Save Changes\"/> ";
	} ?>
	 </form>
	<?php echo '<p>&nbsp;</p>';
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
	function printscore($sc) {
		if (strpos($sc,'~')===false) {

			return array($sc,'');
		} else {
			$pts = getpts($sc);
			$sc = str_replace('-1','N/A',$sc);
			$sc = str_replace('~',', ',$sc);
			return array($pts,$sc);
		}
	}
function getansweights($qi,$code) {
	global $seeds,$questions;
	if (preg_match('/scoremethod\s*=\s*"(singlescore|acct|allornothing)"/', $code)) {
		return array(1);
	}
	$i = array_search($qi,$questions);
	return sandboxgetweights($code,$seeds[$i]);
}

function sandboxgetweights($code,$seed) {
	srand($seed);
	$code = interpret('control','multipart',$code);
	if (($p=strrpos($code,'answeights'))!==false) {
		$np = strpos($code,"\n",$p);
		if ($np !== false) {
			$code = substr($code,0,$np).';if(isset($answeights)){return;};'.substr($code,$np);
		} else {
			$code .= ';if(isset($answeights)){return;};';
		}
		//$code = str_replace("\n",';if(isset($answeights)){return;};'."\n",$code);
	} else {
		$p=strrpos($code,'answeights');
		$np = strpos($code,"\n",$p);
		if ($np !== false) {
			$code = substr($code,0,$np).';if(isset($anstypes)){return;};'.substr($code,$np);
		} else {
			$code .= ';if(isset($answeights)){return;};';
		}
		//$code = str_replace("\n",';if(isset($anstypes)){return;};'."\n",$code);
	}
	eval($code);
	if (!isset($answeights)) {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}
		$n = count($anstypes);
		if ($n>1) {
			$answeights = array_fill(0,$n-1,round(1/$n,3));
			$answeights[] = 1-array_sum($answeights);
		} else {
			$answeights = array(1);
		}
	} else if (!is_array($answeights)) {
		$answeights =  explode(',',$answeights);
	}
	$sum = array_sum($answeights);
	if ($sum==0) {$sum = 1;}
	foreach ($answeights as $k=>$v) {
		$answeights[$k] = $v/$sum;
	}
	return $answeights;
}


function printrubrics($rubricarray)
{

    $out = '<script type="text/javascript">';
    $out .= 'var imasrubrics = new Array();';
    foreach ($rubricarray as $info) {
        $out .= "imasrubrics[{$info[0]}] = {'type':{$info[1]},'data':[";
        $data = unserialize($info[2]);
        if($data)
         {
          foreach ($data as $i => $rubline) {
            if ($i != 0) {
                $out .= ',';
            }
            $out .= '["' . str_replace('"', '\\"', $rubline[0]) . '",';
            $out .= '"' . str_replace('"', '\\"', $rubline[1]) . '"';
            $out .= ',' . $rubline[2];
            $out .= ']';
        }
         }

        $out .= ']};';
    }
    $out .= '</script>';
    return $out;
}

function printrubriclink($rubricid, $points, $scorebox, $feedbackbox, $qn = 'null', $width = 600)
{
    $out = "<a onclick=\"imasrubric_show($rubricid,$points,'$scorebox','$feedbackbox','$qn',$width); return false;\" href=\"#\">";
    $out .= "<img border=0 src='../../img/assess.png' alt=\"rubric\"></a>";
    return $out;
}
?>
