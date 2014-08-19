<?php
	require("../validate.php");
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
	echo "<style type=\"text/css\" media=\"print\">.hideonprint {display:none;} p.tips {display: none;}\n input.btn {display: none;}\n textarea {display: none;}\n input.sabtn {display: none;} .question, .review {background-color:#fff;}</style>\n";
	echo "<style type=\"text/css\">p.tips {	display: none;}\n </style>\n";
	echo '<script type="text/javascript">function rendersa() { ';
	echo '  el = document.getElementsByTagName("span"); ';
	echo '   for (var i=0;i<el.length;i++) {';
	echo '     if (el[i].className=="hidden") { ';
	echo '         el[i].className = "shown";';
	//echo '		 AMprocessNode(el)';
	echo '     }';
	echo '    }';
	echo '} </script>';
	
	if ($isteacher && isset($_GET['asid'])) {
		$testid = $_GET['asid'];
	} else {
		$testid = addslashes($sessiondata['sessiontestid']);
	}
	$query = "SELECT * FROM imas_assessment_sessions WHERE id='$testid'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	$questions = explode(",",$line['questions']);
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
	}
	
	$timesontask = explode("~",$line['timeontask']);

	if ($isteacher) {
		if ($line['userid']!=$userid) {
			$query = "SELECT LastName,FirstName FROM imas_users WHERE id='{$line['userid']}'";
			$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			$row = mysql_fetch_row($result);
			$userfullname = $row[1]." ".$row[0];
		}
		$userid= $line['userid'];
	}
	
	$query = "SELECT * FROM imas_assessments WHERE id='{$line['assessmentid']}'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$testsettings = mysql_fetch_array($result, MYSQL_ASSOC);
	list($testsettings['testtype'],$testsettings['showans']) = explode('-',$testsettings['deffeedback']);
	
	$qi = getquestioninfo($questions,$testsettings);
	
	
	$now = time();
	$isreview = false;
	if (!$scoredview && ($now < $testsettings['startdate'] || $testsettings['enddate']<$now)) { //outside normal range for test
		$query = "SELECT startdate,enddate FROM imas_exceptions WHERE userid='$userid' AND assessmentid='{$line['assessmentid']}'";
		$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result2);
		if ($row!=null) {
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
	
	echo "<h4 style=\"float:right;\">Name: $userfullname </h4>\n";
	echo "<h3>".$testsettings['name']."</h3>\n";
	
	
	$allowregen = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework");
	$showeachscore = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="AsGo" || $testsettings['testtype']=="Homework");
	$showansduring = (($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework") && $testsettings['showans']!='N');
	$GLOBALS['useeditor']='reviewifneeded';
	echo "<div class=breadcrumb>Print Ready Version</div>";
	
	$endtext = '';  $intropieces = array();
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
	}
	
	
	echo '<div class=intro>'.$testsettings['intro'].'</div>';
	if ($isteacher && !$scoredview) {
		echo '<p>'._('Showing Current Versions').'<br/><button type="button" class="btn" onclick="rendersa()">'._("Show Answers").'</button> <a href="printtest.php?cid='.$cid.'&asid='.$testid.'&scored=best">'._('Show Scored View').'</a> <a href="printtest.php?cid='.$cid.'&asid='.$testid.'&scored=last">'._('Show Last Attempts').'</a></p>';
	} else if ($isteacher) {
		if ($scoredtype=='last') {
			echo '<p>'._('Showing Last Attempts').' <a href="printtest.php?cid='.$cid.'&asid='.$testid.'&scored=best">'._('Show Scored View').'</a></p>';
		} else {
			echo '<p>'._('Showing Scored View').' <a href="printtest.php?cid='.$cid.'&asid='.$testid.'&scored=last">'._('Show Last Attempts').'</a></p>';
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
			echo '<div class="intro">'.$intropieces[$i+1].'</div>';	
		}
		echo '<div class="nobreak">';
		if (isset($_GET['descr'])) {
			$query = "SELECT description FROM imas_questionset WHERE id='$qsetid'";
			$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			echo '<div>ID:'.$qsetid.', '.mysql_result($result,0,0).'</div>';
		} else {
			//list($points,$qattempts) = getpointspossible($questions[$i],$testsettings['defpoints'],$testsettings['defattempts']);
			$points = $qi[$questions[$i]]['points'];
			$qattempts = $qi[$questions[$i]]['attempts'];
			if ($scoredview) {
				echo "<div>#".($i+1)." ";
				echo printscore($scores[$i], $i);
				echo "</div>";
			} else {
				echo "<div>#".($i+1)." Points possible: $points.  Total attempts: $qattempts</div>";
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
							
							echo str_replace(array('&','%nbsp;'),array('; ','&nbsp;'),strip_tags($laarr[$k]));
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
		echo '<div class="intro">'.$endtext.'</div>';
	}
?>
