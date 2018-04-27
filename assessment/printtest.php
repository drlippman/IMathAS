<?php
	require("../init.php");
	$isteacher = (isset($teacherid) || $sessiondata['isteacher']==true);
	if (!isset($sessiondata['sessiontestid']) && !$isteacher) {
		echo "<html><body>Error. </body></html>\n";
		exit;
	}
	if (isset($teacherid) && isset($_GET['scored'])) {
		$scoredtype = $_GET['scored'];
		$scoredview = true;
		$showcolormark = true;
	} else {
		$scoredtype = 'last';
		$scoredview = false;
	}

	include("displayq2.php");
	include("testutil.php");
	$flexwidth = true; //tells header to use non _fw stylesheet
	if ($scoredview) {
		$placeinhead = '<script type="text/javascript">
			$(function() {
				$(\'input[value="Preview"]\').click().hide();
			});
			</script>';
	}
	$sessiondata['coursetheme'] = $coursetheme;
	require("header.php");
	echo "<style type=\"text/css\" media=\"print\">.hideonprint {display:none;} p.tips {display: none;}\n input.btn, button.btn {display: none;}\n textarea {display: none;}\n input.sabtn {display: none;} .question, .review {background-color:#fff;}</style>\n";
	echo "<style type=\"text/css\">p.tips {	display: none;}\n </style>\n";
	echo '<script type="text/javascript">function rendersa() { ';
	echo '  el = document.getElementsByTagName("span"); ';
	echo '   for (var i=0;i<el.length;i++) {';
	echo '     if (el[i].className=="hidden") { ';
	echo '         el[i].className = "shown";';
	//echo '		 AMprocessNode(el)';
	echo '     }';
	echo '    }';
	echo '}';
	echo '
			var introshowing = true;
			function toggleintros() {
				if (introshowing) {
					$(".intro").slideUp();
					$("#introtoggle").text("'._('Show Intro and Between-Question Text').'");
				} else {
					$(".intro").slideDown();
					$("#introtoggle").text("'._('Hide Intro and Between-Question Text').'");
				}
				introshowing = !introshowing;
			}
			</script>';

	if ($isteacher && isset($_GET['asid'])) {
		$testid = Sanitize::onlyInt($_GET['asid']);
	} else {
		//DB $testid = addslashes($sessiondata['sessiontestid']);
		$testid = $sessiondata['sessiontestid'];
	}
	//DB $query = "SELECT * FROM imas_assessment_sessions WHERE id='$testid'";
	//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
	$stm = $DBH->prepare("SELECT * FROM imas_assessment_sessions WHERE id=:id");
	$stm->execute(array(':id'=>$testid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	$GLOBALS['assessver'] = $line['ver'];
	if (strpos($line['questions'],';')===false) {
		$questions = explode(",",$line['questions']);
		$bestquestions = $questions;
	} else {
		list($questions,$bestquestions) = explode(";",$line['questions']);
		$questions = explode(",",$questions);
		$bestquestions = explode(",",$bestquestions);
	}
	if ($scoredtype=='last') {
		$seeds = explode(",",$line['seeds']);
		$sp = explode(';',$line['scores']);
		$scores = explode(",",$sp[0]);
		$rawscores = explode(',', $sp[1]);
		$attempts = explode(",",$line['attempts']);
		$lastanswers = explode("~",$line['lastanswers']);
	} else {
		$seeds = explode(",",$line['bestseeds']);
		$sp = explode(';',$line['bestscores']);
		$scores = explode(",",$sp[0]);
		$rawscores = explode(',', $sp[1]);
		$attempts = explode(",",$line['bestattempts']);
		$lastanswers = explode("~",$line['bestlastanswers']);
		$questions = $bestquestions;
	}

	$timesontask = explode("~",$line['timeontask']);

	if ($isteacher) {
		if ($line['userid']!=$userid) {
			//DB $query = "SELECT LastName,FirstName FROM imas_users WHERE id='{$line['userid']}'";
			//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT LastName,FirstName FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$line['userid']));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$userfullname = $row[1]." ".$row[0];
		}
		$userid= $line['userid'];
	}

	//DB $query = "SELECT * FROM imas_assessments WHERE id='{$line['assessmentid']}'";
	//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	//DB $testsettings = mysql_fetch_array($result, MYSQL_ASSOC);
	$stm = $DBH->prepare("SELECT * FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$line['assessmentid']));
	$testsettings = $stm->fetch(PDO::FETCH_ASSOC);
	list($testsettings['testtype'],$testsettings['showans']) = explode('-',$testsettings['deffeedback']);

	$now = time();
	$isreview = false;
	if (!$scoredview && ($now < $testsettings['startdate'] || $testsettings['enddate']<$now)) { //outside normal range for test
		//DB $query = "SELECT startdate,enddate FROM imas_exceptions WHERE userid='$userid' AND assessmentid='{$line['assessmentid']}' AND itemtype='A'";
		//DB $result2 = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result2);
		$stm2 = $DBH->prepare("SELECT startdate,enddate,islatepass FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm2->execute(array(':userid'=>$userid, ':assessmentid'=>$line['assessmentid']));
		$row = $stm2->fetch(PDO::FETCH_NUM);
		if ($row!=null) {
			require("../includes/exceptionfuncs.php");
			$exceptionfuncs = new ExceptionFuncs($userid, $cid, !$isteacher);
			$useexception = $exceptionfuncs->getCanUseAssessException($row, $testsettings, true);
		}
		if ($row!=null && $useexception) {
			if ($now<$row[0] || $row[1]<$now) { //outside exception dates
				if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
					$isreview = true;
				} else {
					if (!$isteacher) {
						echo "Assessment is closed";
						echo "<br/><a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to course page</a>";
						exit;
					}
				}
			}
		} else { //no exception
			if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
				$isreview = true;
			} else {
				if (!$isteacher) {
					echo "Assessment is closed";
					echo "<br/><a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to course page</a>";
					exit;
				}
			}
		}
	}
	if ($isreview) {
		$seeds = explode(",",$line['reviewseeds']);
		$scores = explode(",",$line['reviewscores']);
		$attempts = explode(",",$line['reviewattempts']);
		$lastanswers = explode("~",$line['reviewlastanswers']);
	}

	$qi = getquestioninfo($questions,$testsettings,true);

	echo "<h4 style=\"float:right;\">Name: " . Sanitize::encodeStringForDisplay($userfullname) . " </h4>\n";
	echo "<h3>".Sanitize::encodeStringForDisplay($testsettings['name'])."</h3>\n";


	$allowregen = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework");
	$showeachscore = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="AsGo" || $testsettings['testtype']=="Homework");
	$showansduring = (($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework") && $testsettings['showans']!='N');
	$GLOBALS['useeditor']='reviewifneeded';
	echo "<div class=breadcrumb>"._('Print Ready Version').' ';
	echo '<button type="button" onclick="window.print()">'._('Print').'</button>';
	echo '</div>';
	echo '<p><button id="introtoggle" type="button" class="btn" onclick="toggleintros()">'._('Hide Intro and Between-Question Text').'</button></p>';

	if (($introjson=json_decode($testsettings['intro'],true))!==null) { //is json intro
		$testsettings['intro'] = $introjson[0];
	} else {
		$introjson = array();
	}

	$endtext = '';  $intropieces = array();
	$testsettings['intro'] = preg_replace('/\[PAGE\s+(.*?)\]/', '<h3>$1</h3>', $testsettings['intro']);
	$intropieces = array();
	if (strpos($testsettings['intro'], '[QUESTION')!==false) {
		//embedded type
		$intro = preg_replace('/<p>((<span|<strong|<em)[^>]*>)?\[QUESTION\s+(\d+)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/','[QUESTION $3]',$testsettings['intro']);
		$introsplit = preg_split('/\[QUESTION\s+(\d+)\]/', $intro, -1, PREG_SPLIT_DELIM_CAPTURE);

		for ($i=1;$i<count($introsplit);$i+=2) {
			$intropieces[$introsplit[$i]] = $introsplit[$i-1];
		}
		//no specific start text - will just go before first question
		$testsettings['intro'] = '';
		$endtext = $introsplit[count($introsplit)-1];
	} else if (strpos($testsettings['intro'], '[Q ')!==false) {
		//question info type
		$intro = preg_replace('/<p>((<span|<strong|<em)[^>]*>)?\[Q\s+(\d+(\-(\d+))?)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/','[Q $3]',$testsettings['intro']);
		$introsplit = preg_split('/\[Q\s+(.*?)\]/', $intro, -1, PREG_SPLIT_DELIM_CAPTURE);
		$testsettings['intro'] = $introsplit[0];
		for ($i=1;$i<count($introsplit);$i+=2) {
			$p = explode('-',$introsplit[$i]);
			$intropieces[$p[0]] = $introsplit[$i+1];
		}
	} else if (count($introjson)>1) {
		$introdividers = array();
		$intropcs = array();
		$lastdisplaybefore = -1;
		$textsegcnt = -1;
		for ($i=1;$i<count($introjson);$i++) {
			if (isset($introjson[$i]['ispage']) && $introjson[$i]['ispage']==1 && $testsettings['displaymethod'] == "Embed") {
				$introjson[$i]['text'] = '<h2>'.strip_tags(str_replace(array("\n","\r","]"),array(' ',' ','&#93;'), $introjson[$i]['pagetitle'])).'</h2>'.$introjson[$i]['text'];
			}
			if ($introjson[$i]['displayBefore'] == $lastdisplaybefore) {
				$intropcs[$textsegcnt] .= $introjson[$i]['text'];
			} else {
				$textsegcnt++;
				$introdividers[$textsegcnt] = array(0,$introjson[$i]['displayBefore']+1, $introjson[$i]['displayUntil']+1);
				$intropcs[$textsegcnt] = $introjson[$i]['text'];
			}

			$lastdisplaybefore = $introjson[$i]['displayBefore'];
		}
		if ($lastdisplaybefore==count($questions)) {
			$endtext = $intropcs[$textsegcnt];
		}
		//restructure into the format needed for printtest
		foreach ($introdividers as $k=>$v) {
			$intropieces[$v[1]] = $intropcs[$k];
		}
	}

	echo '<div class=intro>'.$testsettings['intro'].'</div>';
	if ($isteacher && !$scoredview) {
		echo '<p class="hideonprint">'._('Showing Current Versions').'<br/><button type="button" class="btn" onclick="rendersa()">'._("Show Answers").'</button> <a href="printtest.php?cid='.$cid.'&asid='.$testid.'&scored=best">'._('Show Scored View').'</a> <a href="printtest.php?cid='.$cid.'&asid='.$testid.'&scored=last">'._('Show Last Attempts').'</a></p>';
	} else if ($isteacher) {
		if ($scoredtype=='last') {
			echo '<p class="hideonprint">'._('Showing Last Attempts').' <a href="printtest.php?cid='.$cid.'&asid='.$testid.'&scored=best">'._('Show Scored View').'</a></p>';
		} else {
			echo '<p class="hideonprint">'._('Showing Scored View').' <a href="printtest.php?cid='.$cid.'&asid='.$testid.'&scored=last">'._('Show Last Attempts').'</a></p>';
		}

	}
	if ($testsettings['showans']=='N') {
		$lastanswers = array_fill(0,count($questions),'');
	}
	for ($i = 0; $i < count($questions); $i++) {
		//list($qsetid,$cat) = getqsetid($questions[$i]);
		$qsetid = $qi[$questions[$i]]['questionsetid'];
		$cat = $qi[$questions[$i]]['category'];

		$showa = $isteacher;
		if (isset($intropieces[$i+1])) {
			echo '<div class="intro">'.filter($intropieces[$i+1]).'</div>';
		}
		echo '<div class="nobreak">';
		if (isset($_GET['descr'])) {
			//DB $query = "SELECT description FROM imas_questionset WHERE id='$qsetid'";
			//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			//DB echo '<div>ID:'.$qsetid.', '.mysql_result($result,0,0).'</div>';
			$stm = $DBH->prepare("SELECT description FROM imas_questionset WHERE id=:id");
			$stm->execute(array(':id'=>$qsetid));
			echo '<div>ID:'.Sanitize::onlyInt($qsetid).', '.Sanitize::encodeStringForDisplay($stm->fetchColumn(0)).'</div>';
		} else {
			//list($points,$qattempts) = getpointspossible($questions[$i],$testsettings['defpoints'],$testsettings['defattempts']);
			$points = $qi[$questions[$i]]['points'];
			$qattempts = $qi[$questions[$i]]['attempts'];
			if ($scoredview) {
				echo "<div>#".($i+1)." ";
				echo printscore(Sanitize::encodeStringForDisplay($scores[$i]), $i);
				echo "</div>";
			} else {
				echo "<div>#".($i+1)." Points possible: ".Sanitize::encodeStringForDisplay($points).".  Total attempts: ".Sanitize::encodeStringForDisplay($qattempts)."</div>";
			}
		}
		if ($scoredview) {
			//$col = scorestocolors($scores[$i], $qi[$questions[$i]]['points'], $qi[$questions[$i]]['answeights']);
			if (isset($rawscores[$i])) {
				//$colors = scorestocolors($rawscores[$i],$pts[$questions[$i]],$answeights[$questions[$i]],false);
				if (strpos($rawscores[$i],'~')!==false) {
					$colors = explode('~',$rawscores[$i]);
				} else {
					$colors = array($rawscores[$i]);
				}
			} else {
				$colors = array();
			}
			displayq($i, $qsetid,$seeds[$i],2,false,$attempts[$i],false,false,false,$colors);

			echo '<div class="review">';
			$laarr = explode('##',$lastanswers[$i]);

			if (count($laarr)>1) {
				echo "Previous Attempts:";
				$cnt =1;
				for ($k=0;$k<count($laarr)-1;$k++) {
					if ($laarr[$k]=="ReGen") {
						echo ' ReGen ';
					} else {
						echo "  <b>$cnt:</b> " ;
						if (preg_match('/@FILE:(.+?)@/',$laarr[$k],$match)) {
							$url = getasidfileurl($match[1]);
							echo "<a href=\"$url\" target=\"_new\">".Sanitize::stripHtmlTags(basename($match[1]))."</a>";
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

							echo Sanitize::encodeStringForDisplay(str_replace(array('&','%nbsp;'),array('; ','&nbsp;'),$laarr[$k]));
						}
						$cnt++;
					}

				}
				echo '. ';
			}
			if ($timesontask[$i]!='') {
				echo 'Average time per submission: ';
				$timesarr = explode('~',$timesontask[$i]);
				$avgtime = array_sum($timesarr)/count($timesarr);
				if ($avgtime<60) {
					echo round($avgtime,1) . ' seconds ';
				} else {
					echo round($avgtime/60,1) . ' minutes ';
				}
				echo '<br/>';
			}
			echo '</div>';

		} else {
			displayq($i,$qsetid,$seeds[$i],$showa,($testsettings['showhints']==1),$attempts[$i]);
		}
		echo "<hr />";
		echo '</div>';

	}
	if ($endtext != '') {
		echo '<div class="intro">'.filter($endtext).'</div>';
	}

	require("../footer.php");
?>
