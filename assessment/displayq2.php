<?php
//IMathAS:  Core of the testing engine.  Displays and grades questions
//(c) 2006 David Lippman
$mathfuncs = array("sin","cos","tan","sinh","cosh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
$allowedmacros = $mathfuncs;
//require_once("mathphp.php");
require_once("mathphp2.php");
require("interpret5.php");
require("macros.php");
function displayq($qnidx,$qidx,$seed,$doshowans,$showhints,$attemptn,$returnqtxt=false,$clearla=false,$seqinactive=false) {
	global $imasroot, $myrights;
	srand($seed);
	if (is_int($doshowans) && $doshowans==2) {
		$doshowans = true;
		$nosabutton = true;
	} else {
		$nosabutton = false;
	}
	
	/*if (func_num_args()>5 && func_get_arg(5)==true) {
		$returnqtxt = true;
	} else {
		$returnqtxt = false;
	}
	if (func_num_args()>6 && func_get_arg(6)==true) {
		$clearla = true;
	} else {
		$clearla = false;
	}
	if (func_num_args()>7 && func_get_arg(7)==true) {
		$seqinactive = true;
	} else {
		$seqinactive = false;
	}*/
	
	$query = "SELECT qtype,control,qcontrol,qtext,answer,hasimg FROM imas_questionset WHERE id='$qidx'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$qdata = mysql_fetch_array($result, MYSQL_ASSOC);
	
	if ($qdata['hasimg']>0) {
		$query = "SELECT var,filename,alttext FROM imas_qimages WHERE qsetid='$qidx'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			${$row[0]} = "<img src=\"$imasroot/assessment/qimages/{$row[1]}\" alt=\"{$row[2]}\" />";	
		}
	}
	if (isset($GLOBALS['lastanswers'])) {
		foreach ($GLOBALS['lastanswers'] as $i=>$ar) {
			$arv = explode('##',$ar);
			$arv = $arv[count($arv)-1];
			$arv = explode('&',$arv);
			if (count($arv)==1) {
				$arv = $arv[0];
			}
			if (is_array($arv)) {
				foreach ($arv as $k=>$arvp) {
					if (is_numeric($arvp)) {
						$stuanswers[$i+1][$k] = $arvp;
					} else {
						$stuanswers[$i+1][$k] = preg_replace('/\W+/','',$arvp);
					}
				}
			} else {
				if (is_numeric($arv)) {
					$stuanswers[$i+1] = $arv;
				} else {
					$stuanswers[$i+1] = preg_replace('/\W+/','',$arv);
				}
			}
				
			
		}
		$thisq = $qnidx+1;
	}
	eval(interpret('control',$qdata['qtype'],$qdata['control']));
	eval(interpret('qcontrol',$qdata['qtype'],$qdata['qcontrol']));
	$toevalqtxt = interpret('qtext',$qdata['qtype'],$qdata['qtext']);
	$toevalqtxt = str_replace('\\','\\\\',$toevalqtxt);
	$toevalqtxt = str_replace(array('\\\\n','\\\\"','\\\\$','\\\\{'),array('\\n','\\"','\\$','\\{'),$toevalqtxt);
	//$toevalqtxt = str_replace('"','\\"',$toevalqtxt);
	//echo "toeval: $toevalqtxt";
	if ($doshowans) {
		srand($seed+1);
		eval(interpret('answer',$qdata['qtype'],$qdata['answer']));
	}
	srand($seed+2);
	$laarr = explode('##',$GLOBALS['lastanswers'][$qnidx]);
	$la = $laarr[count($laarr)-1];
	if ($la=="ReGen") {$la = '';}
	if ($clearla) {$la = '';}
	//$la = $GLOBALS['lastanswers'][$qnidx];
	
	if (isset($choices) && !isset($questions)) {
		$questions =& $choices;
	}
	if (isset($variable) && !isset($variables)) {
		$variables =& $variable;
	}
	
	//pack options
	
	if (isset($ansprompt)) {$options['ansprompt'] = $ansprompt;}
	if (isset($displayformat)) {$options['displayformat'] = $displayformat;}
	if (isset($answerformat)) {$answerformat = str_replace(' ','',$answerformat); $options['answerformat'] = $answerformat;}
	if (isset($questions)) {$options['questions'] = $questions;}
	if (isset($answers)) {$options['answers'] = $answers;}
	if (isset($answer)) {$options['answer'] = $answer;}
	if (isset($questiontitle)) {$options['questiontitle'] = $questiontitle;}
	if (isset($answertitle)) {$options['answertitle'] = $answertitle;}
	if (isset($answersize)) {$options['answersize'] = $answersize;}
	if (isset($variables)) {$options['variables'] = $variables;}
	if (isset($domain)) {$options['domain'] = $domain;}	
	if (isset($answerboxsize)) {$options['answerboxsize'] = $answerboxsize;}
	if (isset($hidepreview)) {$options['hidepreview'] = $hidepreview;}
	if (isset($matchlist)) {$options['matchlist'] = $matchlist;}
	if (isset($noshuffle)) {$options['noshuffle'] = $noshuffle;}
	if (isset($reqdecimals)) {$options['reqdecimals'] = $reqdecimals;}
	if (isset($grid)) {$options['grid'] = $grid;}
	if (isset($background)) {$options['background'] = $background;}
	
	if ($qdata['qtype']=="multipart") {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}

		$laparts = explode("&",$la);
		foreach ($anstypes as $kidx=>$anstype) {
			list($answerbox[$kidx],$tips[$kidx],$shans[$kidx],$previewloc[$kidx]) = makeanswerbox($anstype,$kidx,$laparts[$kidx],$options,$qnidx+1);
		}
	} else {
		list($answerbox,$tips[0],$shans[0],$previewloc) = makeanswerbox($qdata['qtype'],$qnidx,$la,$options,0);
	}
	
	
	if ($returnqtxt) {
		$toevalqtxt = preg_replace('/\$answerbox(\[\d+\])?/','',$toevalqtxt);
	}
	
	//create hintbuttons
	if (isset($hints) && $showhints) {
		$lastkey = end(array_keys($hints));
		if ($qdata['qtype']=="multipart" && is_array($hints[$lastkey])) { //individual part hints
			foreach ($hints as $iidx=>$hintpart) {
				$lastkey = end(array_keys($hintpart));
				if ($attemptn>$lastkey) {
					$usenum = $lastkey;
				} else {
					$usenum = $attemptn;
				}
				if ($hintpart[$usenum]!='') {
					$hintloc[$iidx] = "<p><i>Hint:</i> {$hintpart[$usenum]}</p>\n";
				}
				
			}
		} else { //one hint for question
			//$lastkey = end(array_keys($hints));
			if ($attemptn>$lastkey) {
				$usenum = $lastkey;
			} else {
				$usenum = $attemptn;
			}
			if ($hints[$usenum]!='') {
				$hintloc = "<p><i>Hint:</i> {$hints[$usenum]}</p>\n";
			}
			
		}
	}
	if (is_array($answerbox)) {
		foreach($answerbox as $iidx=>$abox) {
			if (strpos($toevalqtxt,"\$previewloc[$iidx]")===false) {
				$answerbox[$iidx] .= $previewloc[$iidx];
			}
		}
	} else {
		if (strpos($toevalqtxt,'$previewloc')===false) {
			$answerbox .= $previewloc;
		}
	}
	
	//echo $toevalqtext;
	eval("\$evaledqtext = \"$toevalqtxt\";");
	if ($returnqtxt) {
		$returntxt = $evaledqtext;
	} else if ($seqinactive) {
		echo "<div class=inactive>";
		echo filter($evaledqtext);
	} else {
		echo "<div class=question><div>\n";
		echo filter($evaledqtext);
		echo "</div>\n";
	}
	
	if (strpos($toevalqtxt,'$answerbox')===false) {  
		if (is_array($answerbox)) {
			foreach($answerbox as $iidx=>$abox) {
				if ($seqinactive) {
					//$abox = str_replace('<input','<input disabled="disabled"',$abox);
				}
				if ($returnqtxt) {
					//$returntxt .= "<p>$abox</p>";
				} else {
					echo filter("<div class=\"toppad\">$abox</div>\n");
					echo "<div class=spacer>&nbsp;</div>\n";
				}
			}
		} else {  //one question only
			if ($seqinactive) {
				//$answerbox = str_replace('<input','<input disabled="disabled"',$answerbox);
			}
			if ($returnqtxt) {
				//$returntxt .= "<p>$answerbox</p>";
			} else {
				echo filter("<div class=\"toppad\">$answerbox</div>\n");
			}
		}	
	} 
	if ($returnqtxt) {
		return $returntxt;
	}
	echo "<div>";
	foreach($tips as $iidx=>$tip) {
		if (!isset($hidetips) && !$seqinactive) {
			echo "<p class=\"tips\">Box ".($iidx+1).": $tip</p>";
		}
		if ($doshowans && (!isset($showanswer) || (is_array($showanswer) && !isset($showanswer[$iidx]))) && $shans[$iidx]!=='') {
			if ($nosabutton) {
				echo filter("<div>Answer: {$shans[$iidx]} </div>\n");
			} else {
				echo "<div><input class=\"sabtn\" type=button value=\"Show Answer\" onClick='javascript:document.getElementById(\"ans$qnidx-$iidx\").className=\"shown\";' />"; //AMprocessNode(document.getElementById(\"ans$qnidx-$iidx\"));'>";
				echo filter(" <span id=\"ans$qnidx-$iidx\" class=\"hidden\">{$shans[$iidx]}</span></div>\n");
			}
		} else if ($doshowans && isset($showanswer) && is_array($showanswer)) { //use part specific showanswer
			if (isset($showanswer[$iidx])) {
				if ($nosabutton) {
					echo filter("<div>Answer: {$showanswer[$iidx]} </div>\n");
				} else {
					echo "<div><input class=\"sabtn\" type=button value=\"Show Answer\" onClick='javascript:document.getElementById(\"ans$qnidx-$iidx\").className=\"shown\";' />";// AMprocessNode(document.getElementById(\"ans$qnidx-$iidx\"));'>";
					echo filter(" <span id=\"ans$qnidx-$iidx\" class=\"hidden\">{$showanswer[$iidx]}</span></div>\n");
				}
			}
		}
	}
	echo "</div>\n";
	
	if ($doshowans && isset($showanswer) && !is_array($showanswer)) {  //single showanswer defined
		if ($nosabutton) {
			echo filter("<div>Answer: $showanswer </div>\n");	
		} else {
			echo "<div><input class=\"sabtn\" type=button value=\"Show Answer\" onClick='javascript:document.getElementById(\"ans$qnidx\").className=\"shown\"; AMprocessNode(document.getElementById(\"ans$qnidx\"));' />";
			echo filter(" <span id=\"ans$qnidx\" class=\"hidden\">$showanswer </span></div>\n");
		}
	}
	echo "</div>\n";
}


//inputs: Question number, Question id, rand seed, given answer
function scoreq($qnidx,$qidx,$seed,$givenans) {
	unset($abstolerance);
	srand($seed);
		
	$query = "SELECT qtype,control,answer FROM imas_questionset WHERE id='$qidx'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$qdata = mysql_fetch_array($result, MYSQL_ASSOC);

	if (isset($GLOBALS['lastanswers'])) {
		foreach ($GLOBALS['lastanswers'] as $i=>$ar) {
			$arv = explode('##',$ar);
			$arv = $arv[count($arv)-1];
			$arv = explode('&',$arv);
			if (count($arv)==1) {
				$arv = $arv[0];
			}
			if (is_array($arv)) {
				foreach ($arv as $k=>$arvp) {
					if (is_numeric($arvp)) {
						$stuanswers[$i+1][$k] = $arvp;
					} else {
						$stuanswers[$i+1][$k] = preg_replace('/\W+/','',$arvp);
					}
				}
			} else {
				if (is_numeric($arv)) {
					$stuanswers[$i+1] = $arv;
				} else {
					$stuanswers[$i+1] = preg_replace('/\W+/','',$arv);
				}
			}
		}
	}
	if ($qdata['qtype']=="multipart") {
		for ($kidx=0;$kidx<count($_POST);$kidx++) {
			$partnum = ($qnidx+1)*1000 + $kidx;
			if (isset($_POST["qn$partnum"]) && is_numeric($_POST["qn$partnum"])) {
				$stuanswers[$qnidx+1][$kidx] = floatval($_POST["qn$partnum"]);
			} else {
				$stuanswers[$qnidx+1][$kidx] = preg_replace('/\W+/','',stripslashes($_POST["qn$partnum"]));
			}
		}
		
	} else {
		if (isset($_POST["qn$qnidx"]) && is_numeric($_POST["qn$qnidx"])) {
			$stuanswers[$qnidx+1] = floatval($_POST["qn$qnidx"]);
		} else {
			$stuanswers[$qnidx+1] = preg_replace('/\W+/','',stripslashes($_POST["qn$qnidx"]));
		}
	}
	$thisq = $qnidx+1;
		
		
	eval(interpret('control',$qdata['qtype'],$qdata['control']));
	srand($seed+1);
	eval(interpret('answer',$qdata['qtype'],$qdata['answer']));
	
	if (isset($choices) && !isset($questions)) {
		$questions =& $choices;
	}
	if (isset($variable) && !isset($variables)) {
		$variables =& $variable;
	}
	if (isset($reqdecimals) && !is_array($reqdecimals) && !isset($abstolerance) && !isset($reltolerance)) {
		$abstolerance = 0.5/(pow(10,$reqdecimals));
	} else if (isset($reqdecimals) && is_array($reqdecimals)) {
		foreach ($reqdecimals as $k=>$v) {
			if (!isset($abstolerance[$k]) && !isset($reltolerance[$k])) {
				$abstolerance[$k] = 0.5/(pow(10,$v));
			}
		}
	}
	srand($seed+2);
	//pack options from eval
	if (isset($answer)) {$options['answer'] = $answer;}
	if (isset($reltolerance)) {$options['reltolerance'] = $reltolerance;} 
	if (isset($abstolerance)) {$options['abstolerance'] = $abstolerance;}
	if (isset($answerformat)) {$answerformat = str_replace(' ','',$answerformat); $options['answerformat'] = $answerformat;}
	if (isset($questions)) {$options['questions'] = $questions;}
	if (isset($answers)) {$options['answers'] = $answers;}
	if (isset($answersize)) {$options['answersize'] = $answersize;}
	if (isset($variables)) {$options['variables'] = $variables;}
	if (isset($domain)) {$options['domain'] = $domain;}
	if (isset($requiretimes)) {$options['requiretimes'] = $requiretimes;}	
	if (isset($scoremethod)) {$options['scoremethod'] = $scoremethod;}	
	if (isset($strflags)) {$options['strflags'] = $strflags;}
	if (isset($matchlist)) {$options['matchlist'] = $matchlist;}
	if (isset($noshuffle)) {$options['noshuffle'] = $noshuffle;}
	if (isset($grid)) {$options['grid'] = $grid;}
	if (isset($partweights)) {$options['partweights'] = $partweights;}
	
	$score = 0;
	if ($qdata['qtype']=="multipart") {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}
		$partla = Array();
		if (isset($answeights)) {
			if (!is_array($answeights)) {
				$answeights = explode(",",$answeights);
			}
		} else {
			if (count($anstypes)>1) {
				$answeights = array_fill(0,count($anstypes)-1,round(1/count($anstypes),5));
				$answeights[] = 1-array_sum($answeights);
			} else {
				$answeights = array(1);
			}
		}
		$scores = Array();
		foreach ($anstypes as $kidx=>$anstype) {
			$partnum = ($qnidx+1)*1000 + $kidx;
			$scores[$kidx] = round(scorepart($anstype,$kidx,$_POST["qn$partnum"],$options,$qnidx+1)*$answeights[$kidx],3);
			$partla[$kidx] = $GLOBALS['partlastanswer'];
		}
		$partla = str_replace('&','',$partla);
		$partla = preg_replace('/#+/','#',$partla);
		if ($GLOBALS['lastanswers'][$qnidx]=='') {
			$GLOBALS['lastanswers'][$qnidx] = implode("&",$partla);
		} else {
			$GLOBALS['lastanswers'][$qnidx] .= '##'.implode("&",$partla);
		}
		//return array_sum($scores);
		return implode('~',$scores);
	} else {
		$score = scorepart($qdata['qtype'],$qnidx,$givenans,$options,0);
		$GLOBALS['partlastanswer'] = str_replace('&','',$GLOBALS['partlastanswer']);
		$GLOBALS['partlastanswer'] = preg_replace('/#+/','#',$GLOBALS['partlastanswer']);
		if ($GLOBALS['lastanswers'][$qnidx]=='') {
			$GLOBALS['lastanswers'][$qnidx] = $GLOBALS['partlastanswer'];
		} else {
			$GLOBALS['lastanswers'][$qnidx] .= '##'.$GLOBALS['partlastanswer'];
		}
		return round($score,3);
	}
	
	
	
	
}




function makeanswerbox($anstype, $qn, $la, $options,$multi) {
	global $myrights, $useeqnhelper;
	$out = '';
	$tip = '';
	$sa = '';
	$preview = '';
	$la = str_replace('"','&quot;',$la);
	if ($anstype == "number") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$qn];} else {$reqdecimals = $options['reqdecimals'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		
		if (!isset($sz)) { $sz = 20;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		
		if ($displayformat=="point") {
			$leftb = "(";
			$rightb = ")";
		} else if ($displayformat=="vector") {
			$leftb = "&lt;";
			$rightb = "&gt;";
		} else {
			$leftb = '';
			$rightb = '';
		}
		$out .= "$leftb<input class=\"text\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\" />$rightb";
		if ($answerformat=='list' || $answerformat=='exactlist' ||  $answerformat=='orderedlist') {
			$tip = "Enter your answer as a list of numbers separated with commas: Example: -4, 3, 2.5<br/>";
		} else {
			$tip = "Enter your answer as a number.  Examples: 3, -4, 5.5<br/>";
		}
		$tip .= "Enter DNE for Does Not Exist, oo for Infinity";
		if (isset($reqdecimals)) {
			$tip .= "<br/>Your answer should be accurate to $reqdecimals decimal places.";
		}
		if (isset($answer)) {
			$sa = $answer;
		}
	} else if ($anstype == "choices") {
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}}
		
		if (!is_array($questions)) {
			echo "Eeek!  \$questions is not defined or needs to be an array";
			return false;
		}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		if ($noshuffle == "last") {
			$randkeys = array_rand(array_slice($questions,0,count($questions)-1),count($questions)-1);
			shuffle($randkeys);
			array_push($randkeys,count($questions)-1);
		} else if ($noshuffle == "all") {
			$randkeys = array_keys($questions);
		} else {
			$randkeys = array_rand($questions,count($questions));
			shuffle($randkeys);
		}
		
		if (substr($displayformat,1)=='column') {
			$ncol = $displayformat{0};
			$itempercol = ceil(count($randkeys)/$ncol);
			$displayformat = 'column';
		}
		
		if ($displayformat == "select") { 
			$out = "<select name=\"qn$qn\"><option value=\"NA\">Select an answer</option>\n";
		} else if ($displayformat == "horiz") {
			
		} else if ($displayformat == "inline") {
			
		} else if ($displayformat == 'column') {
			
		}  else {
			$out .= "<ul class=nomark>";
		}
		
		for ($i=0; $i < count($randkeys); $i++) {
			if ($displayformat == "horiz") {
				$out .= "<div class=choice>{$questions[$randkeys[$i]]}<br/><input type=radio name=qn$qn value=$i ";
				if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
				$out .= " /></div>\n";
			} else if ($displayformat == "select") {
				$out .= "<option value=$i ";
				if (($la!='') && ($la!='NA') && ($la == $i)) { $out .= "selected=1";}
				$out .= ">{$questions[$randkeys[$i]]}</option>\n";
			} else if ($displayformat == "inline") {
				$out .= "<input type=radio name=qn$qn value=$i ";
				if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
				$out .= " />{$questions[$randkeys[$i]]}";
			} else if ($displayformat == 'column') {
				if ($i%$itempercol==0) {
					if ($i>0) {
						$out .= '</ul></div>';
					}
					$out .= '<div class="match"><ul class=nomark>';
				}
				$out .= "<li><input type=radio name=qn$qn value=$i ";
				if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
				$out .= " />{$questions[$randkeys[$i]]}</li> \n";
			} else {
				$out .= "<li><input type=radio name=qn$qn value=$i ";
				if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
				$out .= " />{$questions[$randkeys[$i]]}</li> \n";
			}
		}
		if ($displayformat == "horiz") {
			$out .= "<div class=spacer>&nbsp;</div>\n";
		} else if ($displayformat == "select") {
			$out .= "</select>\n";
		} else if ($displayformat == 'column') {
			$out .= "</ul></div><div class=spacer>&nbsp;</div>\n";
		} else if ($displayformat == "inline") {
			
		} else {
			$out .= "</ul>\n";
		}
		$tip = "Select the best answer";
		if (isset($answer)) {
			$anss = explode(' or ',$answer);
			$sapt = array();
			foreach ($anss as $v) {
				$sapt[] = $questions[intval($v)];
			}
			$sa = implode(' or ',$sapt); //$questions[$answer];
		}
	} else if ($anstype == "multans") {
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (isset($options['answers'])) {if (is_array($options['answers'])) {$answers = $options['answers'][$qn];} else {$answers = $options['answers'];}}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		
		if (!is_array($questions)) {
			echo "Eeek!  \$questions is not defined or needs to be an array";
			return false;
		}
		
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		if ($noshuffle == "last") {
			$randkeys = array_rand(array_slice($questions,0,count($questions)-1),count($questions)-1);
			shuffle($randkeys);
			array_push($randkeys,count($questions)-1);
		} else if ($noshuffle == "all") {
			$randkeys = array_keys($questions);
		} else {
			$randkeys = array_rand($questions,count($questions));
			shuffle($randkeys);
		}
		$labits = explode('|',$la);
		if (substr($displayformat,1)=='column') {
			$ncol = $displayformat{0};
			$itempercol = ceil(count($randkeys)/$ncol);
			$displayformat = 'column';
		}
		if ($displayformat == "horiz") {
			
		} else if ($displayformat == "inline") {
			
		} else if ($displayformat == 'column') {
			
		} else {
			$out .= "<ul class=nomark>";
		}
		
		for ($i=0; $i < count($randkeys); $i++) {
			if ($displayformat == "horiz") {
				$out .= "<div class=choice>{$questions[$randkeys[$i]]}<br/>";
				$out .= "<input type=checkbox name=\"qn$qn"."[$i]\" value=$i ";
				if (($labits[$i]!='') && ($labits[$i] == $i)) { $out .= "CHECKED";}
				$out .= " /></div> \n";
			} else if ($displayformat == "inline") {
				$out .= "<input type=checkbox name=\"qn$qn"."[$i]\" value=$i ";
				if (($labits[$i]!='') && ($labits[$i] == $i)) { $out .= "CHECKED";}
				$out .= " />{$questions[$randkeys[$i]]} ";
			} else if ($displayformat == 'column') {
				if ($i%$itempercol==0) {
					if ($i>0) {
						$out .= '</ul></div>';
					}
					$out .= '<div class="match"><ul class=nomark>';
				}
				$out .= "<li><input type=checkbox name=\"qn$qn"."[$i]\" value=$i ";
				if (($labits[$i]!='') && ($labits[$i] == $i)) { $out .= "CHECKED";}
				$out .= " />{$questions[$randkeys[$i]]}</li> \n";
			} else {
				$out .= "<li><input type=checkbox name=\"qn$qn"."[$i]\" value=$i ";
				if (($labits[$i]!='') && ($labits[$i] == $i)) { $out .= "CHECKED";}
				$out .= " />{$questions[$randkeys[$i]]}</li> \n";
			}
		}
		if ($displayformat == "horiz") {
			$out .= "<div class=spacer>&nbsp;</div>\n";
		} else if ($displayformat == "inline") {
			
		} else if ($displayformat == 'column') {
			$out .= "</ul></div><div class=spacer>&nbsp;</div>\n";
		} else {
			$out .= "</ul>\n";
		}
		$tip = "Select all correct answers";
		if (isset($answers)) {
			$akeys = explode(',',$answers);
			foreach($akeys as $akey) {
				$sa .= $questions[$akey]." ";
			}
		}
	} else if ($anstype == "matching") {
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (is_array($options['answers'][$qn])) {$answers = $options['answers'][$qn];} else {$answers = $options['answers'];}
		if (isset($options['questiontitle'])) {if (is_array($options['questiontitle'])) {$questiontitle = $options['questiontitle'][$qn];} else {$questiontitle = $options['questiontitle'];}}
		if (isset($options['answertitle'])) {if (is_array($options['answertitle'])) {$answertitle = $options['answertitle'][$qn];} else {$answertitle = $options['answertitle'];}}
		if (isset($options['matchlist'])) {if (is_array($options['matchlist'])) {$matchlist = $options['matchlist'][$qn];} else {$matchlist = $options['matchlist'];}}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		if (!is_array($questions) || !is_array($answers)) {
			echo "Eeek!  \$questions or \$answers is not defined or needs to be an array";
			return false;
		}
		if (isset($matchlist)) { $matchlist = explode(',',$matchlist);}
		if ($noshuffle=="questions") {
			$randqkeys = array_keys($questions);
		} else {
			$randqkeys = array_rand($questions,count($questions));
			shuffle($randqkeys);
		}
		if ($noshuffle=="answers") {
			$randakeys = array_keys($answers);
		} else {
			$randakeys = array_rand($answers,count($answers));
			shuffle($randakeys);
		}
		$ncol = 1;
		if (substr($displayformat,1)=='columnselect') {
			$ncol = $displayformat{0};
			$itempercol = ceil(count($randqkeys)/$ncol);
			$displayformat = 'select';
		}
		$out .= "<div class=match>\n";
		$out .= "<p class=centered>$questiontitle</p>\n";
		$out .= "<ul class=nomark>\n";
		$las = explode("|",$la);
		$letters = array_slice(explode(',','a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z'),0,count($answers));
		
		for ($i=0;$i<count($randqkeys);$i++) {
			//$out .= "<li><input class=\"text\" type=\"text\"  size=3 name=\"qn$qn-$i\" value=\"{$las[$i]}\" /> {$questions[$randqkeys[$i]]}</li>\n";
			if ($ncol>1) {
				if ($i>0 && $i%$itempercol==0) {
					$out .= '</ul></div><div class="match"><ul class=nomark>';
				}
			}
			$out .= '<li>';
			$out .= "<select name=\"qn$qn-$i\">";
			$out .= '<option value="-" ';
			if ($las[$i]=='-' || $las[$i]=='') {
				$out .= 'selected="1"';
			}
			$out .= '>-</option>';
			if ($displayformat=="select") {
				for ($j=0;$j<count($randakeys);$j++) {
					$out .= "<option value=\"".$letters[$j]."\" ";
					if (is_numeric($las[$i]) && $las[$i]==$j) {
						$out .= 'selected="1"';
					}
					$out .= ">{$answers[$randakeys[$j]]}</option>\n";
				}
			} else {
				foreach ($letters as $v) {
					$out .= "<option value=\"$v\" ";
					if ($las[$i]==$v) {
						$out .= 'selected="1"';
					}
					$out .= ">$v</option>";
				}
			}
			$out .= "</select> {$questions[$randqkeys[$i]]}</li>\n";
		}
		$out .= "</ul>\n";
		$out .= "</div>";
		
		if (!isset($displayformat) || $displayformat!="select") {
			$out .= "<div class=match>\n";
			$out .= "<p class=centered>$answertitle</p>\n";
			
			$out .= "<ol class=lalpha>\n";
			for ($i=0;$i<count($randakeys);$i++) {
				$out .= "<li>{$answers[$randakeys[$i]]}</li>\n";
			}
			$out .= "</ol>";
			$out .= "</div>";
		}
		$out .= "<input type=hidden name=\"qn$qn\" value=\"done\" /><div class=spacer>&nbsp;</div>";
		//$tip = "In each box provided, type the letter (a, b, c, etc.) of the matching answer in the right-hand column";
		if ($displayformat=="select") {
			$tip = "In each pull-down, select the item that matches with the displayed item";
		} else {
			$tip = "In each pull-down on the left, select the letter (a, b, c, etc.) of the matching answer in the right-hand column";
		}
		for ($i=0; $i<count($randqkeys);$i++) {
			if (isset($matchlist)) {
				$akey = array_search($matchlist[$randqkeys[$i]],$randakeys);
			} else {
				$akey = array_search($randqkeys[$i],$randakeys);
			}
			if ($displayformat == "select") {
				$sa .= $answers[$randakeys[$akey]].' ';	
			} else {
				$sa .= chr($akey+97)." ";
			}

		}
	} else if ($anstype == "calculated") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$qn];} else {$hidepreview = $options['hidepreview'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$qn];} else {$reqdecimals = $options['reqdecimals'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		
		if (!isset($sz)) { $sz = 20;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($answerformat)) { $answerformat = '';}
		if (isset($ansprompt)) {$out .= "<label for=\"tc$qn\">$ansprompt</label>";}
		if ($displayformat=="point") {
			$leftb = "(";
			$rightb = ")";
		} else if ($displayformat=="vector") {
			$leftb = "&lt;";
			$rightb = "&gt;";
		} else {
			$leftb = '';
			$rightb = '';
		}
		$out .= "$leftb<input class=\"text\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\" ";
		if ($useeqnhelper) {
			$out .= "onfocus=\"showeedd('tc$qn')\" onblur=\"hideee()\" ";
		}
		$out .= "/>$rightb\n";
		if (!isset($hidepreview)) {
			$preview .= "<input type=button class=btn value=Preview onclick=\"calculate('tc$qn','p$qn','$answerformat')\" /> &nbsp;\n";
		}
		$preview .= "$leftb<span id=p$qn></span>$rightb ";
		$out .= "<script type=\"text/javascript\">calctoproc[calctoproc.length] = $qn; calcformat[$qn] = '$answerformat';</script>\n";
		$ansformats = explode(',',$answerformat);
		if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
			$tip = "Enter your answer as a list of values separated by commas: Example: -4, 3, 2<br/>";
			$eword = "each value";
		} else {
			$tip = '';
			$eword = "your answer";
		}
		$tip .= formathint($eword,$ansformats,'calculated');
		if (isset($answer)) {
			if (!is_numeric($answer)) {
				$sa = '`'.$answer.'`';
			} else if (in_array('mixednumber',$ansformats) || in_array("sloppymixednumber",$ansformats)) {
				$sa = '`'.decimaltofraction($answer,"mixednumber").'`';
			} else if (in_array("fraction",$ansformats) || in_array("reducedfraction",$ansformats)) {
				$sa = '`'.decimaltofraction($answer).'`';
			} else {
				$sa = $answer;
			}
		}
	} else if ($anstype == "matrix") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answersize'])) {if (is_array($options['answersize'])) {$answersize = $options['answersize'][$qn];} else {$answersize = $options['answersize'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		
		if (!isset($sz)) { $sz = 20;}
		
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		if (isset($ansprompt)) {$out .= $ansprompt;}
		if (isset($answersize)) {
			$out .= '<table><tr><td class="matrixleft">&nbsp;</td><td>';
			$answersize = explode(",",$answersize);
			$out .= "<table>";
			$count = 0;
			$las = explode("|",$la);
			for ($row=0; $row<$answersize[0]; $row++) {
				$out .= "<tr>";
				for ($col=0; $col<$answersize[1]; $col++) {
					$out .= "<td><input class=\"text\" type=\"text\"  size=3 name=\"qn$qn-$count\" value=\"{$las[$count]}\" /></td>\n";
					$count++;
				}
				$out .= "</tr>";
			}
			$out .= "</table>\n";
			$out .= '</td><td class="matrixright">&nbsp;</td></tr></table>';
			$tip = "Enter each element of the matrix as  number (like 5, -3, 2.2)";
		} else {
			$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\" />\n";
			$out .= "<input type=button class=btn value=Preview onclick=\"AMmathpreview('qn$qn','p$qn')\" /> &nbsp;\n";
			$out .= "<span id=p$qn></span> ";
			$tip = "Enter your answer as a matrix filled with numbers, like ((2,3,4),(3,4,5))";
		}
		if (isset($answer)) {
			$sa = '`'.$answer.'`';
		}
			
	} else if ($anstype == "calcmatrix") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answersize'])) {if (is_array($options['answersize'])) {$answersize = $options['answersize'][$qn];} else {$answersize = $options['answersize'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$qn];} else {$hidepreview = $options['hidepreview'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = explode(',',$answerformat);
		
		if (!isset($sz)) { $sz = 20;}
		
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		if (isset($ansprompt)) {$out .= $ansprompt;}
		if (isset($answersize)) {
			$answersize = explode(",",$answersize);
			$out .= '<table><tr><td class="matrixleft">&nbsp;</td><td>';
			$out .= "<table>";
			$count = 0;
			$las = explode("|",$la);
			for ($row=0; $row<$answersize[0]; $row++) {
				$out .= "<tr>";
				for ($col=0; $col<$answersize[1]; $col++) {
					$out .= "<td><input class=\"text\" type=\"text\"  size=3 name=\"qn$qn-$count\" id=\"qn$qn-$count\" value=\"{$las[$count]}\" /></td>\n";
					$count++;
				}
				$out .= "</tr>";
			}
			$out .= "</table>\n";
			$out .= '</td><td class="matrixright">&nbsp;</td></tr></table>';
			if (!isset($hidepreview)) {$preview .= "<input type=button class=btn value=Preview onclick=\"matrixcalc('qn$qn','p$qn',{$answersize[0]},{$answersize[1]})\" /> &nbsp;\n";}
			$preview .= "<span id=p$qn></span>\n";
			$out .= "<script type=\"text/javascript\">matcalctoproc[matcalctoproc.length] = $qn; matsize[$qn]='{$answersize[0]},{$answersize[1]}';</script>\n";
			$tip .= formathint('each element of the matrix',$ansformats,'calcmatrix');
			//$tip = "Enter each element of the matrix as  number (like 5, -3, 2.2) or as a calculation (like 5/3, 2^3, 5+4)";
		} else {
			$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\" />\n";
			$out .= "<input type=button value=Preview onclick=\"matrixcalc('tc$qn','p$qn')\" /> &nbsp;\n";
			$out .= "<span id=p$qn></span> \n";
			$out .= "<script type=\"text/javascript\">matcalctoproc[matcalctoproc.length] = $qn;</script>\n";
			$tip = "Enter your answer as a matrix, like ((2,3,4),(1,4,5))";
			$tip .= '<br/>'.formathint('each element of the matrix',$ansformats,'calcmatrix');
		}
		if (isset($answer)) {
			$sa = '`'.$answer.'`';
		}
	} else if ($anstype == "numfunc") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['variables'])) {if (is_array($options['variables'])) {$variables = $options['variables'][$qn];} else {$variables = $options['variables'];}}
		if (isset($options['domain'])) {if (is_array($options['domain'])) {$domain = $options['domain'][$qn];} else {$domain = $options['domain'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$qn];} else {$hidepreview = $options['hidepreview'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}	
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (!isset($sz)) { $sz = 20;}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		if (isset($ansprompt)) {$out .= "<label for=\"tc$qn\">$ansprompt</label>";}
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=\"tc$qn\" id=\"tc$qn\" value=\"$la\" ";
		if ($useeqnhelper) {
			$out .= "onfocus=\"showeedd('tc$qn')\" onblur=\"hideee()\" ";
		}
		$out .= "/>\n";
		if (!isset($hidepreview)) {$preview .= "<input type=button class=btn value=Preview onclick=\"AMpreview('tc$qn','p$qn')\" /> &nbsp;\n";}
		$preview .= "<span id=p$qn></span>\n";
		
		if (!isset($variables)) { $variables = "x";}
		$variables = explode(",",$variables);
		$ovar = array();
		$ofunc = array();
		for ($i = 0; $i < count($variables); $i++) {
			if (strpos($variables[$i],'(')===false) {
				$ovar[] = $variables[$i];
			} else {
				$ofunc[] = substr($variables[$i],0,strpos($variables[$i],'('));
				$variables[$i] = substr($variables[$i],0,strpos($variables[$i],'('));
			}
		}
		
		if (count($ovar)==0) {
			$ovar[] = "x";
		}
		$vlist = implode("|",$ovar);
		$flist = implode('|',$ofunc);
		$out .= "<script type=\"text/javascript\">functoproc[functoproc.length] = $qn; vlist[$qn]=\"$vlist\"; flist[$qn]=\"$flist\";</script>\n";
		if (isset($domain)) {$fromto = explode(",",$domain);} else {$fromto[0]=-10; $fromto[1]=10;}
		
		for ($i = 0; $i < 20; $i++) {
			for($j=0; $j < count($variables); $j++) {
				if (isset($fromto[2]) && $fromto[2]=="integers") {
					$tp[$j] = rand($fromto[0],$fromto[1]);
				} else if (isset($fromto[2*$j+1])) {
					$tp[$j] = $fromto[2*$j] + ($fromto[2*$j+1]-$fromto[2*$j])*rand(0,499)/500.0 + 0.001;
				} else {
					$tp[$j] = $fromto[0] + ($fromto[1]-$fromto[0])*rand(0,499)/500.0 + 0.001;
				}
			}
			$pts[$i] = implode("~",$tp);
		}
		$points = implode(",",$pts);
		$out .= "<script type=\"text/javascript\">pts[$qn]=\"$points\";</script>\n";
		if ($answerformat=="equation") {
			$out .= "<script type=\"text/javascript\">iseqn[$qn] = 1;</script>\n";
			$tip = "Enter your answer as an equation.  Example: y=3x^2+1, 2+x+y=3\n<br/>Be sure your variables match those in the question";
		} else {
			$tip = "Enter your answer as an expression.  Example: 3x^2+1, x/5, (a+b)/c\n<br/>Be sure your variables match those in the question";
		}
		if (isset($answer)) {
			$sa = makeprettydisp($answer);
		}
	} else if ($anstype == "ntuple") {
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		
		if (!isset($sz)) { $sz = 20;}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\" />";
		if ($displayformat == 'point') {
			$tip = "Enter your answer as a point.  Example: (2,5.5)<br/>";
		} else if ($displayformat == 'pointlist') {
			$tip = "Enter your answer a list of points separated with commas.  Example: (1,2), (3.5,5)<br/>";
		} else if ($displayformat == 'vector') {
			$tip = "Enter your answer as a vector.  Example: <2,5.5><br/>";
		} else if ($displayformat == 'vectorlist') {
			$tip = "Enter your answer a list of vectors separated with commas.  Example: <1,2>, <3.5,5><br/>";
		} else if ($displayformat == 'list') {
			$tip = "Enter your answer as a list of n-tuples of numbers separated with commas: Example: (1,2),(3.5,4)<br/>";
		} else {
			$tip = "Enter your answer as an n-tuple of numbers.  Example: (2,5.5)<br/>";
		}
		$tip .= "Enter DNE for Does Not Exist";
		
		if (isset($answer)) {
			$sa = $answer;
		}
	} else if ($anstype == "calcntuple") {
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = explode(',',$answerformat);
		
		if (!isset($sz)) { $sz = 20;}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\" />";
		if (!isset($hidepreview)) {
			$preview .= "<input type=button class=btn value=Preview onclick=\"ntuplecalc('tc$qn','p$qn','$qn')\" /> &nbsp;\n";
		}
		$preview .= "<span id=p$qn></span> ";
		$out .= "<script type=\"text/javascript\">ntupletoproc[ntupletoproc.length] = $qn; calcformat[$qn] = '$answerformat';</script>\n";
		
		if ($displayformat == 'point') {
			$tip = "Enter your answer as a point.  Example: (2,5.5)<br/>";
		} else if ($displayformat == 'pointlist') {
			$tip = "Enter your answer a list of points separated with commas.  Example: (1,2), (3.5,5)<br/>";
		} else if ($displayformat == 'vector') {
			$tip = "Enter your answer as a vector.  Example: <2,5.5><br/>";
		} else if ($displayformat == 'vectorlist') {
			$tip = "Enter your answer a list of vectors separated with commas.  Example: <1,2>, <3.5,5><br/>";
		} else if ($displayformat == 'list') {
			$tip = "Enter your answer as a list of n-tuples of numbers separated with commas: Example: (1,2),(3.5,4)<br/>";
		} else {
			$tip = "Enter your answer as an n-tuple of numbers.  Example: (2,5.5)<br/>";
		}
		$tip .= formathint('each value',$ansformats,'calcntuple');
		//$tip .= "Enter DNE for Does Not Exist";
		if (isset($answer)) {
			$sa = $answer;
		}
	} else if ($anstype == "complex") {
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		
		if (!isset($sz)) { $sz = 20;}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\" />";
		if ($answerformat == "list") {
			$tip = "Enter your answer as a list of complex numbers in a+bi form separated with commas.  Example: 2+5.5i,-3-4i<br/>";
		} else {
			$tip = "Enter your answer as a complex number in a+bi form.  Example: 2+5.5i<br/>";
		}
		
		$tip .= "Enter DNE for Does Not Exist";
		if (isset($answer)) {
			$sa = makepretty($answer);
		}
	} else if ($anstype == "calccomplex") {
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = explode(',',$answerformat);
		
		if (!isset($sz)) { $sz = 20;}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = explode(',',$answerformat);
		
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\" />";
		if (!isset($hidepreview)) {
			$preview .= "<input type=button class=btn value=Preview onclick=\"complexcalc('tc$qn','p$qn')\" /> &nbsp;\n";
		}
		$preview .= "<span id=p$qn></span> ";
		$out .= "<script type=\"text/javascript\">complextoproc[complextoproc.length] = $qn;</script>\n";
		
		if (in_array('list',$ansformats)) {
			$tip = "Enter your answer as a list of complex numbers in a+bi form separated with commas.  Example: 2+5i,-3-4i<br/>";
		} else {
			$tip = "Enter your answer as a complex number in a+bi form.  Example: 2+5i<br/>";
		}
		$tip .= formathint('each value',$ansformats,'calcntuple');
		//$tip .= "Enter DNE for Does Not Exist";
		if (isset($answer)) {
			$sa = makeprettydisp( $answer);
		}
	} else if ($anstype == "string") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($sz)) { $sz = 20;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}
		
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=\"qn$qn\" id=\"qn$qn\" value=\"$la\" />\n";
		$tip .= "Enter your answer as letters.  Examples: A B C, linear, a cat";
		$sa .= $answer;
	} else if ($anstype == "essay") {
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($sz)) { 
			$rows = 5; 
			$cols = 50;
		} else if (strpos($sz,',')>0) {
			list($rows,$cols) = explode(',',$sz);
		} else {
			$cols = 50;
			$rows = $sz;
		}
		if ($displayformat=='editor') {
			$rows += 5;
		}
		if ($GLOBALS['useeditor']=='review') {
			$la = str_replace('&quot;','"',$la);
			$la = preg_replace('/%(\w+;)/',"&$1",$la);
			//$la = str_replace('nbsp;','&nbsp;',$la);
			if ($displayformat!='editor') {
				$la = preg_replace('/\n/','<br/>',$la);
			} 
			$out .= "<div class=intro>".filter($la)."</div>";
		} else {
			$la = stripslashes($la);
			$la = preg_replace('/%(\w+;)/',"&$1",$la);
			if ($displayformat=='editor' && $GLOBALS['useeditor']==1) {
				//$la = str_replace('&quot;','"',$la);
				
				//$la = preg_replace('/%(\w+;)/',"&$1",$la);
				$la = htmlentities($la);
			}
			$out .= "<textarea rows=\"$rows\" name=\"qn$qn\" id=\"qn$qn\" ";
			if ($displayformat=='editor' && $GLOBALS['useeditor']==1) {
				$out .= "style=\"width:98%;\" class=\"mceEditor\" ";
			} else {
				$out .= "cols=\"$cols\" ";
			}
			$out .= ">$la</textarea>\n";
			if ($displayformat=='editor' && $GLOBALS['useeditor']==1) {
				//$out .= "<script type=\"text/javascript\">editornames[editornames.length] = \"qn$qn\";</script>";
			}
		} 
		$tip .= "Enter your answer as text.  This question is not automatically graded.";
		$sa .= $answer;
	} else if ($anstype == 'interval') {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$qn];} else {$reqdecimals = $options['reqdecimals'];}}
		
		if (!isset($sz)) { $sz = 20;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\" />";
		$tip = "Enter your answer using interval notation.  Example: [2.1,5.6) <br/>";
		$tip .= "Use U for union to combine intervals.  Example: (-oo,2] U [4,oo)<br/>";
		$tip .= "Enter DNE for an empty set, oo for Infinity";
		if (isset($reqdecimals)) {
			$tip .= "<br/>Your numbers should be accurate to $reqdecimals decimal places.";
		}
		if (isset($answer)) {
			$sa = $answer;
		}
	} else if ($anstype == 'calcinterval') {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$qn];} else {$hidepreview = $options['hidepreview'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$qn];} else {$reqdecimals = $options['reqdecimals'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = explode(',',$answerformat);
		
		if (!isset($sz)) { $sz = 20;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\" />\n";
		if (!isset($hidepreview)) {
			$preview .= "<input type=button class=btn value=Preview onclick=\"intcalculate('tc$qn','p$qn')\" /> &nbsp;\n";
		}
		$preview .= "<span id=p$qn></span> ";
		$out .= "<script type=\"text/javascript\">intcalctoproc[intcalctoproc.length] = $qn;</script>\n";
		$tip = "Enter your answer using interval notation.  Example: [2.1,5.6) <br/>";
		$tip .= "Use U for union to combine intervals.  Example: (-oo,2] U [4,oo)<br/>";
		//$tip .= "Enter values as numbers (like 5, -3, 2.2) or as calculations (like 5/3, 2^3, 5+4)<br/>";
		//$tip .= "Enter DNE for an empty set, oo for Infinity";
		$tip .= formathint('each value',$ansformats,'calcinterval');
		if (isset($reqdecimals)) {
			$tip .= "<br/>Your numbers should be accurate to $reqdecimals decimal places.";
		}
		if (isset($answer)) {
			$sa = $answer;
		}
		
	} else if ($anstype == 'draw') {
		if ($multi>0) {
			if (isset($options['grid'][$qn])) { $grid = $options['grid'][$qn];}
			if (isset($options['background'][$qn])) { $backg = $options['background'][$qn];}
			if (isset($options['answers'][$qn])) {$answers = $options['answers'][$qn];}
		} else {
			if (isset($options['grid'])) { $grid = $options['grid'];}
			if (isset($options['background'])) { $backg = $options['background'];}
			if (isset($options['answers'])) {$answers = $options['answers'];}
		
		}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (!is_array($answers)) {
			settype($answers,"array");
		}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		
		$settings = array(-5,5,-5,5,1,1,300,300,"","");
		if (isset($grid)) {
			if (!is_array($grid)) {
				$grid = explode(',',$grid);
			}
			for ($i=0; $i<count($grid); $i++) {
				if ($grid[$i]!='') {
					$settings[$i] = evalbasic($grid[$i]);
				}
			}
		}
		if (!isset($backg)) { $backg = '';}
		$scling = $settings[4].':'.$settings[5];
		$plot = showplot($backg,$settings[0],$settings[1],$settings[2],$settings[3],$scling,$scling,$settings[6],$settings[7]);
		if ($settings[8]!="") {
		}
		$bg = getgraphfilename($plot);
		if (!isset($answerformat)) {
			$answerformat = array('line','dot','opendot');
		} else if (!is_array($answerformat)) {
			$answerformat = explode(',',$answerformat);
		}
		$dotline = 0;
		$out .= "<canvas class=\"drawcanvas\" id=\"canvas$qn\" width=\"{$settings[6]}\" height=\"{$settings[7]}\"></canvas>";
		$out .= "<div><span id=\"drawtools$qn\" class=\"drawtools\">";
		$out .= "<span onclick=\"clearcanvas($qn)\">Clear All</span> Draw: ";
		for ($i=0; $i<count($answerformat); $i++) {
			if ($i==0) {
				$out .= '<span class="sel" ';
			} else {
				$out .= '<span ';
			}
			if ($answerformat[$i]=='line') {
				$out .= "onclick=\"settool(this,$qn,0)\">Line</span>";
			} else if ($answerformat[$i]=='dot') {
				$out .= "onclick=\"settool(this,$qn,1)\">Dot</span>";
			} else if ($answerformat[$i]=='opendot') {
				$out .= "onclick=\"settool(this,$qn,2)\">Open Dot</span>";
			} else if ($answerformat[$i]=='polygon') {
				$out .= "onclick=\"settool(this,$qn,0)\">Polygon</span>";
				$dotline = 1;
			} 
		}
		if ($answerformat[0]=='line') {
			$def = 0;
		} else if ($answerformat[0]=='dot') {
			$def = 1;
		} else if ($answerformat[0]=='opendot') {
			$def = 2;
		} else if ($answerformat[0]=='polygon') {
			$def = 0;
		}
		
		$out .= '</span></div>';
		$out .= "<input type=\"hidden\" name=\"qn$qn\" id=\"qn$qn\" value=\"$la\" />";
		$out .= "<script type=\"text/javascript\">canvases[canvases.length] = [$qn,'$bg',{$settings[0]},{$settings[1]},{$settings[2]},{$settings[3]},5,{$settings[6]},{$settings[7]},$def,$dotline];";
		
		$la = str_replace(array('(',')'),array('[',']'),$la);
		$la = explode(';;',$la);
		$la[0] = '['.str_replace(';','],[',$la[0]).']';
		$la = '[['.implode('],[',$la).']]';
		
		$out .= "drawla[drawla.length] = $la;</script>";
		$tip = "Enter your answer by drawing on the graph.";
		if (isset($answers)) {
			$saarr = array();
			foreach($answers as $k=>$ans) {
				$function = explode(',',$ans);
				if (count($function)==2 || (count($function)==3 && ($function[2]=='open' || $function[2]=='closed'))) { //is dot
					$saarr[$k] = $function[1].',blue,'.$function[0].','.$function[0];
					if (count($function)==2 || $function[2]=='closed') {
						$saarr[$k] .= ',closed';
					} else {
						$saarr[$k] .= ',open';
					}
				} else { //is function
					$saarr[$k] = $function[0].',blue';
					if (count($function)>2) {
						$saarr[$k] .= ','.$function[1].','.$function[2];
					}
				}
			}
			if ($answerformat[0]=="polygon") {
				for($i=0;$i<count($answers)-1;$i++) {
					$pt1 = explode(',',$answers[$i]);
					$pt2 = explode(',',$answers[$i+1]);
					$saarr[] = "[{$pt1[0]}+t*({$pt2[0]}-({$pt1[0]})),{$pt1[1]}+t*({$pt2[1]}-({$pt1[1]}))],blue,0,1";
				}
			}
			if ($backg!='') {
				if (!is_array($backg)) {
					settype($backg,"array");
				}
				$saarr = array_merge($saarr,$backg);
			}
			$sa = showplot($saarr,$settings[0],$settings[1],$settings[2],$settings[3],$scling,$scling,$settings[6],$settings[7]);
		}
	} else if ($anstype == "file") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}
		
		$out .= "<input type=\"file\" name=\"qn$qn\" id=\"qn$qn\" />\n";
		if ($la!='') {
			if (isset($GLOBALS['testsettings']) && isset($GLOBALS['sessiondata']['groupid']) && $GLOBALS['testsettings']>0 && $GLOBALS['sessiondata']['groupid']>0) {
				$s3asid = $GLOBALS['sessiondata']['groupid'];
			} else if (isset($GLOBALS['asid'])) {
				$s3asid = $GLOBALS['asid'];
			} 
			if (!empty($s3asid)) {
				require_once("../includes/filehandler.php");
				
				if (substr($la,0,5)=="Error") {
					$out .= "<br/>$la";
				} else {
					$file = preg_replace('/@FILE:(.+?)@/',"$1",$la);
					$url = getasidfileurl($s3asid,$file);
					$out .= "<br/>Last file uploaded: <a href=\"$url\" target=\"_new\">$file</a>";
				}
			} else {
				$out .= "<br/>$la";
			}
		}
		$tip .= "Select a file to upload";
		$sa .= $answer;
	}
	
	return array($out,$tip,$sa,$preview);
}




function scorepart($anstype,$qn,$givenans,$options,$multi) {
	if ($anstype == "number") {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = .001;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$GLOBALS['partlastanswer'] = $givenans;
		if ($givenans == null) {return 0;}
		if ($answerformat=='exactlist') {
			$gaarr = explode(',',$givenans);
			$anarr = explode(',',$answer);
		} else if ($answerformat=='orderedlist') {
			$gamasterarr = explode(',',$givenans);
			$gaarr = $gamasterarr;
			$anarr = explode(',',$answer);
		} else if ($answerformat=='list') {
			$tmp = explode(',',$givenans);
			sort($tmp);
			$gaarr = array($tmp[0]);
			for ($i=1;$i<count($tmp);$i++) {
				if ($tmp[$i]-$tmp[$i-1]>1E-12) {
					$gaarr[] = $tmp[$i];
				}
			}
			$tmp = explode(',',$answer);
			sort($tmp);
			$anarr = array($tmp[0]);
			for ($i=1;$i<count($tmp);$i++) {
				if ($tmp[$i]-$tmp[$i-1]>1E-12) {
					$anarr[] = $tmp[$i];
				}
			}
		} else {
			$gaarr = array(str_replace(',','',$givenans));
			$anarr = array($answer);
		}
		/*  should students get an answer right by leaving it blank?
		if ($answerformat=='exactlist' || $answerformat=='orderedlist' || $answerformat=='list') {
			if (trim($answer)=='') {
				if (trim($givenans)=='') {
					return 1;
				} else {
					return 0;
				}
			}
		}*/
		$extrapennum = count($gaarr)+count($anarr);
		
		if ($answerformat=='orderedlist') {
			if (count($gamasterarr)!=count($anarr)) {
				return 0;
			}
		}
		
		$correct = 0;
		foreach($anarr as $i=>$answer) {
			$foundloc = -1;
			if ($answerformat=='orderedlist') {
				$gaarr = array($gamasterarr[$i]);
			}
			
			foreach($gaarr as $j=>$givenans) {
				$givenans = trim($givenans);
				$anss = explode(' or ',$answer);
				foreach ($anss as $anans) {
					if (!is_numeric($anans)) {
						if (preg_match('/(\(|\[)([\d\.]+)\,([\d\.]+)(\)|\])/',$anans,$matches)) {
							if (($matches[1]=="(" && $givenans>$matches[2]) || ($matches[1]=="[" && $givenans>=$matches[2])) {
								if (($matches[4]==")" && $givenans<$matches[3]) || ($matches[4]=="]" && $givenans<=$matches[3])) {
									$correct += 1;
									$foundloc = $j;
									break 2;
								} 
							} 
						} else	if ($anans=="DNE" && strtoupper($givenans)=="DNE") {
							$correct += 1; $foundloc = $j; break 2;   
						} else if (($anans=="+oo" || $anans=="oo") && ($givenans=="+oo" || $givenans=="oo")) {
							$correct += 1; $foundloc = $j; break 2;
						} else if ($anans=="-oo" && $givenans=="-oo") {
							$correct += 1; $foundloc = $j; break 2;
						} else if (strtoupper($anans)==strtoupper($givenans)) {
							$correct += 1; $foundloc = $j; break 2;
						}
					} else {//{if (is_numeric($givenans)) {
						$givenans = preg_replace('/[^\-\d\.eE]/','',$givenans); //strip out units, dollar signs, whatever
						if (is_numeric($givenans)) {
							if (isset($abstolerance)) {
								if (abs($anans-$givenans) < $abstolerance + 1E-12) {$correct += 1; $foundloc = $j; break 2;} 	
							} else {
								if (abs($anans - $givenans)/(abs($anans)+.0001) < $reltolerance+ 1E-12) {$correct += 1; $foundloc = $j; break 2;} 
							}
						}
					}
				}
			}
			if ($foundloc>-1) {
				array_splice($gaarr,$foundloc,1); //remove from list
				if (count($gaarr)==0 && $answerformat!='orderedlist') {
					break; //stop if no student answers left
				}
			}
		}
		if ($answerformat!='orderedlist') {
			$score = $correct/count($anarr) - count($gaarr)/$extrapennum;  //take off points for extranous stu answers
		} else {
			$score = $correct/count($anarr);
		}
		if ($score<0) { $score = 0; }
		return ($score);
		
	} else if ($anstype == "choices") {
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}}
		
		if (!is_array($questions)) {
			echo "Eeek!  \$questions is not defined or needs to be an array.  Make sure \$questions is defined in the Common Control section.";
			return false;
		}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$GLOBALS['partlastanswer'] = $givenans;
		if ($noshuffle == "last") {
			$randkeys = array_rand(array_slice($questions,0,count($questions)-1),count($questions)-1);
			shuffle($randkeys);
			array_push($randkeys,count($questions)-1);
		} else if ($noshuffle == "all") {
			$randkeys = array_keys($questions);
		} else {
			$randkeys = array_rand($questions,count($questions));
			shuffle($randkeys);
		}
		if ($givenans == null) {return 0;}

		if ($givenans=='NA') { return 0; }
		$anss = explode(' or ',$answer);
		foreach ($anss as $k=>$v) {
			$anss[$k] = intval($v);
		}
		//if ($randkeys[$givenans] == $answer) {return 1;} else { return 0;}
		if (in_array($randkeys[$givenans],$anss)) {return 1;} else { return 0;}
	} else if ($anstype == "multans") {
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (is_array($options['answers'])) {$answers = $options['answers'][$qn];} else {$answers = $options['answers'];}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}}
		
		if (isset($options['scoremethod']))if (is_array($options['scoremethod'][$qn])) {$scoremethod = $options['scoremethod'][$qn];} else {$scoremethod = $options['scoremethod'];}
		
		if (!is_array($questions)) {
			echo "Eeek!  \$questions is not defined or needs to be an array.  Make sure \$questions is defined in the Common Control section.";
			return false;
		}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$score = 1.0;
		if ($noshuffle == "last") {
			$randqkeys = array_rand(array_slice($questions,0,count($questions)-1),count($questions)-1);
			shuffle($randqkeys);
			array_push($randqkeys,count($questions)-1);
		} else if ($noshuffle == "all") {
			$randqkeys = array_keys($questions);
		} else {
			$randqkeys = array_rand($questions,count($questions));
			shuffle($randqkeys);
		}
		if (trim($answers)=='') {
			$akeys = array();
		} else {
			$akeys = explode(",",$answers);
		}
		if (isset($scoremethod) && $scoremethod=='answers') {
			$deduct = 1.0/count($akeys);
		} else {
			$deduct = 1.0/count($questions);
		}
		for ($i=0;$i<count($questions);$i++) {
			if ($i>0) {$GLOBALS['partlastanswer'] .= "|";} else {$GLOBALS['partlastanswer']='';}
			$GLOBALS['partlastanswer'] .= $_POST["qn$qn"][$i];
			
			if (isset($_POST["qn$qn"][$i])!==(in_array($randqkeys[$i],$akeys))) {
				$score -= $deduct;
			}
		}
		if (isset($scoremethod) && $scoremethod=='allornothing' && $score<1) {
			$score = 0;
		}
		if ($score < 0) {
			$score = 0;
		}
		return $score;
	} else if ($anstype == "matching") {
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (is_array($options['answers'][$qn])) {$answers = $options['answers'][$qn];} else {$answers = $options['answers'];}
		if (is_array($options['matchlist'])) {$matchlist = $options['matchlist'][$qn];} else {$matchlist = $options['matchlist'];}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}}
		
		if (!is_array($questions) || !is_array($answers)) {
			echo "Eeek!  \$questions or \$answers is not defined or needs to be an array.  Make sure both are defined in the Common Control section.";
			return 0;
		}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$score = 1.0;
		$deduct = 1.0/count($questions);
		if ($noshuffle=="questions") {
			$randqkeys = array_keys($questions);
		} else {
			$randqkeys = array_rand($questions,count($questions));
			shuffle($randqkeys);
		}
		if ($noshuffle=="answers") {
			$randakeys = array_keys($answers);
		} else {
			$randakeys = array_rand($answers,count($answers));
			shuffle($randakeys);
		}
		if (isset($matchlist)) {$matchlist = explode(',',$matchlist);}
		
		for ($i=0;$i<count($questions);$i++) {
			if ($i>0) {$GLOBALS['partlastanswer'] .= "|";} else {$GLOBALS['partlastanswer']='';}
			$GLOBALS['partlastanswer'] .= $_POST["qn$qn-$i"];
			if ($_POST["qn$qn-$i"]!="" && $_POST["qn$qn-$i"]!="-") {
				$qa = ord($_POST["qn$qn-$i"]);
				if ($qa<97) { //if uppercase answer
					$qa -= 65;  //shift A to 0
				} else { //if lower case
					$qa -= 97;  //shift a to 0
				}
				if (isset($matchlist)) {
					if ($matchlist[$randqkeys[$i]]!=$randakeys[$qa]) {
						$score -= $deduct;
					}
				} else {
					if ($randqkeys[$i]!=$randakeys[$qa]) {
						$score -= $deduct;
					}
				}
			} else {$score -= $deduct;}
		}
		return $score;
	} else if ($anstype=="matrix") {
		if (is_array($options['answer']) && isset($options['answer'][$qn])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = .001;}
		if (isset($options['answersize'])) {if (is_array($options['answersize'])) {$answersize = $options['answersize'][$qn];} else {$answersize = $options['answersize'];}}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$correct = true;
		
		$ansr = substr($answer,2,-2);
		$ansr = preg_replace('/\)\s*\,\s*\(/',',',$ansr);
		$answerlist = explode(',',$ansr);
		foreach ($answerlist as $k=>$v) {
			$v = eval('return ('.mathphp($v,null).');');
			$answerlist[$k] = preg_replace('/[^\d\.,\-E]/','',$v);
		}
		//$answer = preg_replace_callback('/([^\[\(\)\]\,]+)/',"preg_mathphp_callback",$answer);
		//$answerlist = explode(",",preg_replace('/[^\d\.,\-]/','',$answer));
		
		if (isset($answersize)) {
			for ($i=0; $i<count($answerlist); $i++) {
				$givenanslist[$i] = $_POST["qn$qn-$i"];
			}
			$GLOBALS['partlastanswer'] = implode("|",$givenanslist);
		} else {
			$givenans = preg_replace('/\)\s*,\s*\(/','),(',$givenans);
			$GLOBALS['partlastanswer'] = $givenans;
			$givenanslist = explode(",",preg_replace('/[^\d,\.\-]/','',$givenans));
			if (substr_count($answer,'),(')!=substr_count($_POST["qn$qn"],'),(')) {$correct = false;}
		}


		for ($i=0; $i<count($answerlist); $i++) {
			if (isset($abstolerance)) {
				if (abs($answerlist[$i] - $givenanslist[$i]) > $abstolerance-1E-12) {
					$correct = false;
					break;
				}	
			} else {
				if (abs($answerlist[$i] - $givenanslist[$i])/(abs($answerlist[$i])+.0001) > $reltolerance-1E-12) {
					$correct = false;
					break;
				}

			}
		}
		
		if ($correct) {return 1;} else {return 0;}
	} else if ($anstype=="calcmatrix") {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = .001;}
		if (isset($options['answersize'])) {if (is_array($options['answersize'])) {$answersize = $options['answersize'][$qn];} else {$answersize = $options['answersize'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = explode(',',$answerformat);
		
		$correct = true;
		$ansr = substr($answer,2,-2);
		$ansr = preg_replace('/\)\s*\,\s*\(/',',',$ansr);
		$answerlist = explode(',',$ansr);
		foreach ($answerlist as $k=>$v) {
			$v = eval('return ('.mathphp($v,null).');');
			$answerlist[$k] = preg_replace('/[^\d\.,\-E]/','',$v);
		}
		//$answer = preg_replace_callback('/([^\[\(\)\]\,]+)/',"preg_mathphp_callback",$answer);
		//$answerlist = explode(",",preg_replace('/[^\d\.,\-E]/','',$answer));
		if (isset($answersize)) {
			for ($i=0; $i<count($answerlist); $i++) {
				$givenanslist[$i] = $_POST["qn$qn-$i"];
				if (!checkanswerformat($givenanslist[$i],$ansformats)) {
					return 0; //perhaps should just elim bad answer rather than all?
				} 
			}
			$GLOBALS['partlastanswer'] = implode("|",$givenanslist);
		} else {
			$_POST["tc$qn"] = preg_replace('/\)\s*,\s*\(/','),(',$_POST["tc$qn"]);
			$GLOBALS['partlastanswer'] = $_POST["tc$qn"];
			if (substr_count($answer,'),(')!=substr_count($_POST["tc$qn"],'),(')) {$correct = false;}
			$tocheck = str_replace(' ','',$_POST["tc$qn"]);
			$tocheck = str_replace(array('],[','),(','>,<'),',',$tocheck);
			$tocheck = substr($tocheck,2,strlen($tocheck)-4);
			$tocheck = explode(',',$tocheck);
			foreach($tocheck as $chkme) {
				if (!checkanswerformat($chkme,$ansformats)) {
					return 0; //perhaps should just elim bad answer rather than all?
				} 
			}
		}
		$givenanslist = explode(",",preg_replace('/[^\d\.,\-]/','',$givenans));


		for ($i=0; $i<count($answerlist); $i++) {
			if (isset($abstolerance)) {
				if (abs($answerlist[$i] - $givenanslist[$i]) > $abstolerance-1E-12) {
					$correct = false;
					break;
				}	
			} else {
				if (abs($answerlist[$i] - $givenanslist[$i])/(abs($answerlist[$i])+.0001) > $reltolerance-1E-12) {
					$correct = false;
					break;
				}
			}
		}
		if ($correct) {return 1;} else {return 0;}
	} else if ($anstype == "ntuple" || $anstype== 'calcntuple') {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['requiretimes'])) {if (is_array($options['requiretimes'])) {$requiretimes = $options['requiretimes'][$qn];} else {$requiretimes = $options['requiretimes'];}}
		
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = .001;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = explode(',',$answerformat);
		
		if ($anstype=='ntuple') {
			$GLOBALS['partlastanswer'] = $givenans;
		} else if ($anstype=='calcntuple') {
			$GLOBALS['partlastanswer'] = $_POST["tc$qn"];
			//test for correct format, if specified
			if (checkreqtimes($_POST["tc$qn"],$requiretimes)==0) {
				return 0;
			}
			$tocheck = str_replace(' ','',$_POST["tc$qn"]);
			$tocheck = str_replace(array('],[','),(','>,<'),',',$tocheck);
			$tocheck = substr($tocheck,1,strlen($tocheck)-2);
			$tocheck = explode(',',$tocheck);
			
			foreach($tocheck as $chkme) {
				if (!checkanswerformat($chkme,$ansformats)) {
					return 0; //perhaps should just elim bad answer rather than all?
				} 
			}
		}
		if ($givenans == null) {return 0;}
		$answer = str_replace(' ','',$answer);
		$givenans = str_replace(' ','',$givenans);
		
		if ($answer=='DNE' && strtoupper($givenans)=='DNE') {
			return 1;
		}
		
		preg_match_all('/([\(\[\<\{])(.*?)([\)\]\>\}])/', $givenans, $gaarr, PREG_SET_ORDER);
		preg_match_all('/([\(\[\<\{])(.*?)([\)\]\>\}])/', $answer, $anarr, PREG_SET_ORDER);
		
		if (count($gaarr)==0) {
			return 0;
		}
		$extrapennum = count($gaarr)+count($anarr);
		$correct = 0;
		foreach ($anarr as $i=>$answer) {
			$foundloc = -1;
			foreach ($gaarr as $j=>$givenans) {
				if ($answer[1]!=$givenans[1] || $answer[3]!=$givenans[3]) {
					break;
				}
				$ansparts = explode(',',$answer[2]);
				$gaparts = explode(',',$givenans[2]);

				if (count($ansparts)!=count($gaparts)) {
					break;
				}
				for ($i=0; $i<count($ansparts); $i++) {
					if (is_numeric($ansparts[$i]) && is_numeric($gaparts[$i])) {
						if (isset($abstolerance)) {
							if (abs($ansparts[$i]-$gaparts[$i]) >= $abstolerance + 1E-12) {break;} 	
						} else {
							if (abs($ansparts[$i]-$gaparts[$i])/(abs($ansparts[$i])+.0001) >= $reltolerance+ 1E-12) {break;} 
						}
					} else {
						break;
					}
				}
				if ($i==count($ansparts)) {
					$correct += 1; $foundloc = $j; break;
				}
			}
			if ($foundloc>-1) {
				array_splice($gaarr,$foundloc,1); // remove from list
				if (count($gaarr)==0) {
					break;
				}
			}
		}
		$score = $correct/count($anarr) - count($gaarr)/$extrapennum;
		if ($score<0) { $score = 0; }
		return ($score);
		
	} else if ($anstype == "complex" || $anstype== 'calccomplex') {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['requiretimes'])) {if (is_array($options['requiretimes'])) {$requiretimes = $options['requiretimes'][$qn];} else {$requiretimes = $options['requiretimes'];}}
		
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = .001;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = explode(',',$answerformat);
		if ($anstype=='complex') {
			$GLOBALS['partlastanswer'] = $givenans;
		} else if ($anstype=='calccomplex') {
			$GLOBALS['partlastanswer'] = $_POST["tc$qn"];
			//test for correct format, if specified
			if (checkreqtimes($_POST["tc$qn"],$requiretimes)==0) {
				return 0;
			}
			$tocheck = explode(',',$_POST["tc$qn"]);
			foreach ($tocheck as $tchk) {
				$cpts = parsecomplex($tchk);
				if ($cpts[1]{0}=='+') {
					$cpts[1] = substr($cpts[1],1);
				}
				//echo $cpts[0].','.$cpts[1].'<br/>';
				if (!checkanswerformat($cpts[0],$ansformats) || !checkanswerformat($cpts[1],$ansformats)) {
					return 0;
				}
			}
		}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = explode(',',$answerformat);
		
		if ($givenans == null) {return 0;}
		$answer = str_replace(' ','',makepretty($answer));
		$givenans = str_replace(' ','',$givenans);
		
		if ($answer=='DNE' && strtoupper($givenans)=='DNE') {
			return 1;
		}
		
		$gaarr = explode(',',$givenans);
		$anarr = explode(',',$answer);
		
		if (count($gaarr)==0) {
			return 0;
		}
		$extrapennum = count($gaarr)+count($anarr);
		$correct = 0;
		foreach ($anarr as $i=>$answer) {
			$cparts = parsecomplex($answer);
			if (!is_array($cparts)) {
				//echo $cparts;
			} else {
				eval('$ansparts[0] = '.$cparts[0].';');
				eval('$ansparts[1] = '.$cparts[1].';');
			}
			$foundloc = -1;
			foreach ($gaarr as $j=>$givenans) {
				$cparts = parsecomplex($givenans);
				if (!is_array($cparts)) {
					return 0;
				} else {
					$gaparts[0] = floatval($cparts[0]);
					$gaparts[1] = floatval($cparts[1]);
				}
				
				if (count($ansparts)!=count($gaparts)) {
					break;
				}
				for ($i=0; $i<count($ansparts); $i++) {
					if (is_numeric($ansparts[$i]) && is_numeric($gaparts[$i])) {
						if (isset($abstolerance)) {
							if (abs($ansparts[$i]-$gaparts[$i]) >= $abstolerance + 1E-12) {break;} 	
						} else {
							if (abs($ansparts[$i]-$gaparts[$i])/(abs($ansparts[$i])+.0001) >= $reltolerance+ 1E-12) {break;} 
						}
					}
				}
				if ($i==count($ansparts)) {
					$correct += 1; $foundloc = $j; break;
				}
			}
			if ($foundloc>-1) {
				array_splice($gaarr,$foundloc,1); // remove from list
				if (count($gaarr)==0) {
					break;
				}
			}
		}
		$score = $correct/count($anarr) - count($gaarr)/$extrapennum;
		if ($score<0) { $score = 0; }
		return ($score);
		
	} else if ($anstype == "calculated") {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['requiretimes'])) {if (is_array($options['requiretimes'])) {$requiretimes = $options['requiretimes'][$qn];} else {$requiretimes = $options['requiretimes'];}}
		
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = .001;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$GLOBALS['partlastanswer'] = $_POST["tc$qn"];
		if ($givenans == null) {return 0;}
		
		if (checkreqtimes($_POST["tc$qn"],$requiretimes)==0) {
			return 0;
		}
		
		$ansformats = explode(',',$answerformat);
		if (in_array('exactlist',$ansformats)) {
			$gaarr = explode(',',$givenans);
			$anarr = explode(',',$answer);
			$orarr = explode(',',$_POST["tc$qn"]);
		} else if (in_array('orderedlist',$ansformats)) {
			$gamasterarr = explode(',',$givenans);
			$gaarr = $gamasterarr;
			$anarr = explode(',',$answer);
			$orarr = explode(',',$_POST["tc$qn"]);
		} else if (in_array('list',$ansformats)) {
			$tmp = explode(',',$givenans);
			$tmpor = explode(',',$_POST["tc$qn"]);
			asort($tmp);
			$lastval = null;
			foreach ($tmp as $i=>$v) {
				if ($lastval===null) {
					$gaarr[] = $tmp[$i];
					$orarr[] = $tmpor[$i];
				} else {
					if ($v-$lastval>1E-12) {
						$gaarr[] = $tmp[$i];
						$orarr[] = $tmpor[$i];
					}
				}
				$lastval = $v;
			}
			$tmp = explode(',',$answer);
			sort($tmp);
			$anarr = array($tmp[0]);
			for ($i=1;$i<count($tmp);$i++) {
				if ($tmp[$i]-$tmp[$i-1]>1E-12) {
					$anarr[] = $tmp[$i];
				}
			}
		} else {
			$gaarr = array(str_replace(',','',$givenans));
			$anarr = array($answer);
			$orarr = array($_POST["tc$qn"]);
		}
		$extrapennum = count($gaarr)+count($anarr);
		
		if (in_array('orderedlist',$ansformats)) {
			if (count($gamasterarr)!=count($anarr)) {
				return 0;
			}
		}
		
		$correct = 0;
		foreach($anarr as $i=>$answer) {
			$foundloc = -1;
			if (in_array('orderedlist',$ansformats)) {
				$gaarr = array($gamasterarr[$i]);
			}
			foreach($gaarr as $j=>$givenans) {
				if (!checkanswerformat($orarr[$j],$ansformats)) {
					continue;
				} 
				
				$anss = explode(' or ',$answer);
				foreach ($anss as $anans) {
					if (!is_numeric($anans)) {
						$givenans = trim($givenans);
						if (preg_match('/(\(|\[)([\d\.]+)\,([\d\.]+)(\)|\])/',$anans,$matches)) {
							if (($matches[1]=="(" && $givenans>$matches[2]) || ($matches[1]=="[" && $givenans>=$matches[2])) {
								if (($matches[4]==")" && $givenans<$matches[3]) || ($matches[4]=="]" && $givenans<=$matches[3])) {
									$correct += 1; $foundloc = $j; break 2; 
								} 
							} 
						} else	if ($anans=="DNE" && strtoupper($givenans)=="DNE") {
							$correct += 1; $foundloc = $j; break 2;
						} else if (($anans=="+oo" || $anans=="oo") && ($givenans=="+oo" || $givenans=="oo")) {
							$correct += 1; $foundloc = $j; break 2;
						} else if ($anans=="-oo" && $givenans=="-oo") {
							$correct += 1; $foundloc = $j; break 2;
						} else if (is_numeric($givenans)) {
							//try evaling answer
							$eanans = eval('return('.mathphp($anans,null).');');
							if (isset($abstolerance)) {
								if (abs($eanans-$givenans) < $abstolerance+1E-12) {$correct += 1; $foundloc = $j; break 2;} 	
							} else {
								if (abs($eanans - $givenans)/(abs($eanans)+.0001) < $reltolerance+1E-12) {$correct += 1; $foundloc = $j; break 2;} 
							}
						}
					} else if (is_numeric($givenans)) {
						if (isset($abstolerance)) {
							if (abs($anans-$givenans) < $abstolerance+1E-12) {$correct += 1; $foundloc = $j; break 2;} 	
						} else {
							if (abs($anans - $givenans)/(abs($anans)+.0001) < $reltolerance+1E-12) {$correct += 1; $foundloc = $j; break 2;} 
						}
					}
				}
			}
			if ($foundloc>-1) {
				array_splice($gaarr,$foundloc,1); //remove from list
				array_splice($orarr,$foundloc,1);
				if (count($gaarr)==0 && !in_array('orderedlist',$ansformats)) {
					break; //stop if no student answers left
				}
			}
		}
		if (in_array('orderedlist',$ansformats)) {
			$score = $correct/count($anarr);
		} else {
			$score = $correct/count($anarr) - count($gaarr)/$extrapennum;  //take off points for extranous stu answers
		}
		if ($score<0) { $score = 0; }
		return ($score);
	} else if ($anstype == "numfunc") {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = .001;}
		if (is_array($options['variables'])) {$variables = $options['variables'][$qn];} else {$variables = $options['variables'];}

		if (isset($options['domain'])) {if (is_array($options['domain'])) {$domain = $options['domain'][$qn];} else {$domain= $options['domain'];}}
		if (isset($options['requiretimes'])) {if (is_array($options['requiretimes'])) {$requiretimes = $options['requiretimes'][$qn];} else {$requiretimes = $options['requiretimes'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		
		if ($multi>0) { $qn = $multi*1000+$qn;}
		
		$GLOBALS['partlastanswer'] = $_POST["tc$qn"];
		$correct = true;
		
		if (!isset($variables)) { $variables = "x";}
		$variables = explode(",",$variables);
		$ofunc = array();
		for ($i = 0; $i < count($variables); $i++) {
			//find f() function variables
			if (strpos($variables[$i],'(')!==false) {
				$ofunc[] = substr($variables[$i],0,strpos($variables[$i],'('));
				$variables[$i] = substr($variables[$i],0,strpos($variables[$i],'('));
			}
		}
		if (count($ofunc)>0) {
			$flist = implode("|",$ofunc);
			$answer = preg_replace('/('.$flist.')\(/',"$1*sin($1+",$answer);
		}
		$vlist = implode("|",$variables);
		
		if (isset($domain)) {$fromto = explode(",",$domain);} else {$fromto[0]=-10; $fromto[1]=10;}
		
		for ($i = 0; $i < 20; $i++) {
			for($j=0; $j < count($variables); $j++) {
				if (isset($fromto[2]) && $fromto[2]=="integers") {
					$tps[$i][$j] = rand($fromto[0],$fromto[1]);
				} else if (isset($fromto[2*$j+1])) {
					$tps[$i][$j] = $fromto[2*$j] + ($fromto[2*$j+1]-$fromto[2*$j])*rand(0,499)/500.0 + 0.001;
				} else {
					$tps[$i][$j] = $fromto[0] + ($fromto[1]-$fromto[0])*rand(0,499)/500.0 + 0.001;
				}
			}
		}
		
		//test for correct format, if specified
		if (checkreqtimes($_POST["tc$qn"],$requiretimes)==0) {
			return 0; //$correct = false;
		}
		
		$answer = preg_replace('/[^\w\*\/\+\=\-\(\)\[\]\{\}\,\.\^\$\!]+/','',$answer);

		if ($answerformat=="equation") {
			if (strpos($_POST["tc$qn"],'=')===false) {
				return 0;
			}
			$answer = preg_replace('/(.*)=(.*)/','$1-($2)',$answer);
			unset($ratios);
		} else if ($answerformat=="toconst") {
			unset($diffs);
			unset($realanss);
		}
		
		
		if ($answer == '') {
			return 0;
		}
		$answer = mathphppre($answer);
		$answer = makepretty($answer);
		$answer = mathphp($answer,$vlist);
		//echo $answer;
		for($i=0; $i < count($variables); $i++) {
			$answer = str_replace("(".$variables[$i].")",'($tp['.$i.'])',$answer);
		}

		$myans = explode(",",$_POST["qn$qn-vals"]);
		
		$cntnan = 0;
		$cntzero = 0;
		$stunan = 0;
		$ysqrtot = 0;
		$reldifftot = 0;
		for ($i = 0; $i < 20; $i++) {
			for($j=0; $j < count($variables); $j++) {

			//causing problems on multipart - breaking messed up rand order
			/*	if (isset($fromto[2]) && $fromto[2]=="integers") {
					$tp[$j] = rand($fromto[0],$fromto[1]);
				} else {
					$tp[$j] = $fromto[0] + ($fromto[1]-$fromto[0])*rand(0,32000)/32000.0;
				}
			*/
				$tp[$j] = $tps[$i][$j];
			}
			$realans = eval("return ($answer);");
			//echo "real: $realans, my: {$myans[$i]},rel: ". (abs($myans[$i]-$realans)/abs($realans))  ."<br/>";
			if (isNaN($realans)) {$cntnan++; continue;} //avoid NaN problems
			if ($answerformat=="equation") {  //if equation, store ratios
				if (abs($realans)>.000001 && is_numeric($myans[$i])) {
					$ratios[] = $myans[$i]/$realans;
					if ($myans[$i]==0 && $realans!=0) {
						$cntzero++;
					}
				}
			} else if ($answerformat=="toconst") {
				$diffs[] = $myans[$i] - $realans;
				$realanss[] = $realans;
				$ysqr = $realans*$realans;
				$ysqrtot += 1/($ysqr+.0001);
				$reldifftot += ($myans[$i] - $realans)/($ysqr+.0001);
			} else { //otherwise, compare points
				if (isNaN($myans[$i])) {
					$stunan++;
				} else if (isset($abstolerance)) {
					
					if (abs($myans[$i]-$realans) > $abstolerance-1E-12) {$correct = false; break;}	
				} else {
					if ((abs($myans[$i]-$realans)/(abs($realans)+.0001) > $reltolerance-1E-12)) {$correct = false; break;}
				}
			}
		}
		if ($cntnan==20 && isset($GLOBALS['teacherid'])) {
			echo "<p>Debug info: function evaled to Not-a-number at all test points.  Check \$domain</p>";
		}
		if ($stunan>1) { //if more than 1 student NaN response
			return 0;
		}
		if ($answerformat=="equation") {
			if (count($ratios)>0) {
				if (count($ratios)==$cntzero) {
					$correct = false; return 0;
				} else {
					$meanratio = array_sum($ratios)/count($ratios);
					for ($i=0; $i<count($ratios); $i++) {
						if (isset($abstolerance)) {
							if (abs($ratios[$i]-$meanratio) > $abstolerance-1E-12) {$correct = false; break;}	
						} else {
							if ((abs($ratios[$i]-$meanratio)/(abs($meanratio)+.0001) > $reltolerance-1E-12)) {$correct = false; break;}
						}
					}
				}
			} else {
				$correct = false;
			}
		} else if ($answerformat=="toconst") {
			if (isset($abstolerance)) {
				//if abs, use mean diff - will minimize error in abs diffs
				$meandiff = array_sum($diffs)/count($diffs);
			} else {
				//if relative tol, use meandiff to minimize relative error
				$meandiff = $reldifftot/$ysqrtot;
			}
			if (is_nan($meandiff)) {
				$correct=false; return 0;
			} 
			for ($i=0; $i<count($diffs); $i++) {
				if (isset($abstolerance)) {
					if (abs($diffs[$i]-$meandiff) > $abstolerance-1E-12) {$correct = false; break;}	
				} else {
					//if ((abs($diffs[$i]-$meandiff)/(abs($meandiff)+0.0001) > $reltolerance-1E-12)) {$correct = false; break;}
					if ((abs($diffs[$i]-$meandiff)/(abs($realanss[$i])+0.0001) > $reltolerance-1E-12)) {$correct = false; break;}
				}
			}
		}
		if ($correct == true) {return 1;} else { return 0;}
		
	} else if ($anstype == "string") {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (is_array($options['strflags'])) {$strflags = $options['strflags'][$qn];} else {$strflags = $options['strflags'];}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$GLOBALS['partlastanswer'] = $givenans;
		$givenans = stripslashes($givenans);

		$strflags = str_replace(' ','',$strflags);
		$strflags = explode(",",$strflags);
		foreach($strflags as $flag) {
			$pc = explode('=',$flag);
			if ($pc[1]==='true' || $pc[1]==='1' || $pc[1]===1) {
				$pc[1] = true;
			}
			$flags[$pc[0]] = $pc[1];
		}
		
		if (!isset($flags['compress_whitespace'])) {
			$flags['compress_whitespace']=true;
		} 
		if (!isset($flags['ignore_case'])) {
			$flags['ignore_case']=true;
		}
		
		if ($flags['ignore_commas']===true) {
			$givenans = str_replace(',','',$givenans);
			$answer = str_replace(',','',$answer);
		}
		if ($flags['compress_whitespace']===true) {
			$givenans = preg_replace('/\s+/',' ',$givenans);
			$answer = preg_replace('/\s+/',' ',$answer);
		}
		if ($flags['trim_whitespace']===true || $flags['compress_whitespace']===true) {
			$givenans = trim($givenans);
			$answer = trim($answer);
		}
		if ($flags['remove_whitespace']===true) {
			$givenans = trim(preg_replace('/\s+/','',$givenans));
		}
		$specialor = false;
		if ($flags['special_or']===true) {
			$specialor = true;
		}
		
		if ($flags['ignore_case']===true) {
			$givenans = strtoupper($givenans);
			$answer = strtoupper($answer);
			if ($specialor) {
				$anss = explode(' *OR* ',$answer);
			} else {
				$anss = explode(' OR ',$answer);
			}
		} else {
			if ($specialor) {
				$anss = explode(' *or* ',$answer);
			} else {
				$anss = explode(' or ',$answer);
			}
		}
				
		$correct = 0;
		if ($flags['ignore_order']) {
			$givenans = explode("\n",chunk_split($givenans,1,"\n"));
			sort($givenans,SORT_STRING);
			$givenans = implode('',$givenans);
		}
		
		foreach ($anss as $anans) {
			if ($flags['ignore_order']===true) {
				$anans = explode("\n",chunk_split($anans,1,"\n"));
				sort($anans,SORT_STRING);
				$anans = implode('',$anans);
			}
			if ($flags['trim_whitespace']===true || $flags['compress_whitespace']===true) {
				$anans = trim($anans);
			}
			if ($flags['remove_whitespace']===true) {
				$anans = trim(preg_replace('/\s+/','',$anans));
			}
			if ($flags['partial_credit']===true) {
				$poss = strlen($anans);
				$dist = levenshtein($anans,$givenans);
				$score = ($poss - $dist)/$poss;
				if ($score>$correct) { $correct = $score;}
			} else {
				if (!strcmp($anans,$givenans)) {
					$correct = 1;
					break;
				}
			}
		}
		return $correct;
	} else if ($anstype == "essay") {
		require_once("../includes/htmLawed.php");
		$htmlawedconfig = array('elements'=>'*-script');
		$givenans = addslashes(htmLawed(stripslashes($givenans),$htmlawedconfig));
		$givenans = preg_replace('/&(\w+;)/',"%$1",$givenans);
		$GLOBALS['partlastanswer'] = $givenans;
		return 0;
	} else if ($anstype == 'interval' || $anstype == 'calcinterval') {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['requiretimes'])) {if (is_array($options['requiretimes'])) {$requiretimes = $options['requiretimes'][$qn];} else {$requiretimes = $options['requiretimes'];}}
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = .001;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$ansformats = explode(',',$answerformat);
		
		if ($anstype == 'interval') {
			$GLOBALS['partlastanswer'] = $givenans;
		} else if ($anstype == 'calcinterval') {
			$GLOBALS['partlastanswer'] = $_POST["tc$qn"];
			//test for correct format, if specified
			if (checkreqtimes($_POST["tc$qn"],$requiretimes)==0) {
				return 0;
			}
			$orarr = explode('U',$_POST["tc$qn"]);
			foreach ($orarr as $opt) {
				$opts = explode(',',substr($opt,1,strlen($opt)-2));
				if (strpos($opts[0],'oo')===false &&  !checkanswerformat($opts[0],$ansformats)) {
					return 0;
				}
				if (strpos($opts[1],'oo')===false &&  !checkanswerformat($opts[1],$ansformats)) {
					return 0;
				}
			}
			
		}
		if ($givenans == null) {return 0;}
		$correct = 0;
		$ansar = explode(' or ',$answer);
		$givenans = str_replace(' ','',$givenans);
		
		foreach($ansar as $anans) {
			$answer = str_replace(' ','',$anans);
			
			if ($anans==='DNE') {
				if ($givenans==='DNE') {
					$correct = 1; break;
				} else {
					continue;
				}
			}
			$aarr = explode('U',$anans);
			$gaarr = explode('U',$givenans);
			if (count($aarr)!=count($gaarr)) {
				continue;
			}
			
			foreach ($aarr as $ansint) {
				$ansint = trim($ansint);
				$anssm = substr($ansint,0,1);
				$ansem = substr($ansint,-1);
				$ansint = substr($ansint,1,strlen($ansint)-2);
				list($anssn,$ansen) = explode(',',$ansint);
				$foundloc = -1;
				foreach ($gaarr as $k=>$gansint) {
					$gansint = trim($gansint);
					$ganssm = substr($gansint,0,1);
					$gansem = substr($gansint,-1);
					$gansint = substr($gansint,1,strlen($gansint)-2);
					list($ganssn,$gansen) = explode(',',$gansint);
					if ($anssm!=$ganssm || $ansem!=$gansem) {
						continue;
					}
					if (strpos($anssn,'oo')!==false) {
						if (($anssn=='oo' || $anssn=='+oo') && ($ganssn=='oo' || $ganssn=='+oo')) {
							
						} else if ($anssn=='-oo' && $ganssn=='-oo') {
							
						} else {
							continue;
						}
						//if ($anssn===$ganssn) {} else {continue;}
					} else if (isset($abstolerance)) {
						if (abs($anssn-$ganssn) < $abstolerance + 1E-12) {} else {continue;} 	
					} else {
						if (abs($anssn - $ganssn)/(abs($anssn)+.0001) < $reltolerance+ 1E-12) {} else {continue;}
					}
					if (strpos($ansen,'oo')!==false) {
						if (($ansen=='oo' || $ansen=='+oo') && ($gansen=='oo' || $gansen=='+oo')) {
							
						} else if ($ansen=='-oo' && $gansen=='-oo') {
							
						} else {
							continue;
						}
						//if ($ansen===$gansen) {} else {continue;}
					} else if (isset($abstolerance)) {
						if (abs($ansen-$gansen) < $abstolerance + 1E-12) {} else {continue;} 	
					} else {
						if (abs($ansen - $gansen)/(abs($ansen)+.0001) < $reltolerance+ 1E-12) {} else {continue;}
					}
					
					$foundloc = $k;
					break;
				}
				if ($foundloc>-1) {
					array_splice($gaarr,$foundloc,1);
				} else {
					continue 2;
				}
			}
			if (count($gaarr)>0) { //extraneous student intervals?
				continue 2;
			}
			$correct = 1;
			break;
		}
		return $correct;
	} else if ($anstype=='draw') {
		if ($multi>0) {
			if (isset($options['grid'][$qn])) { $grid = $options['grid'][$qn];}
			if (isset($options['answers'][$qn])) {$answers = $options['answers'][$qn];}
			if (isset($options['partweights'][$qn])) {$partweights = $options['partweights'][$qn];}
		} else {
			if (isset($options['grid'])) { $grid = $options['grid'];}
			if (isset($options['answers'])) {$answers = $options['answers'];}
			if (isset($options['partweights'])) {$partweights = $options['partweights'];}
		}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (!isset($reltolerance)) { $reltolerance = 1; }
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$GLOBALS['partlastanswer'] = $givenans;
		$imgborder = 5; $step = 5;
		$settings = array(-5,5,-5,5,1,1,300,300);
		if (isset($grid)) {
			if (!is_array($grid)) {
				$grid = explode(',',$grid);
			}
			for ($i=0; $i<count($grid); $i++) {
				if ($grid[$i]!='') {
					$settings[$i] = evalbasic($grid[$i]);
				}
			}
		}
		$pixelsperx = ($settings[6] - 2*$imgborder)/($settings[1]-$settings[0]);
		$pixelspery = ($settings[7] - 2*$imgborder)/($settings[3]-$settings[2]);
		
		$anslines = array();
		$ansdots = array();
		$ansodots = array();
		$anslineptcnt = array();
		$types = array();
		$linepts = 0;
		if (!is_array($answers)) {
			settype($answers,"array");
		}
		if ($answerformat=="polygon") {
			foreach ($answers as $key=>$function) {
				$function = explode(',',$function);
				$pixx = ($function[0] - $settings[0])*$pixelsperx + $imgborder;
				$pixy = $settings[7] - ($function[1]-$settings[2])*$pixelspery - $imgborder;	
				$ansdots[$key] = array($pixx,$pixy);
			}
			$isclosed = false;
			if (abs($ansdots[0][0]-$ansdots[count($ansdots)-1][0])<.01 && abs($ansdots[0][1]-$ansdots[count($ansdots)-1][1])<.01) {
				$isclosed = true;
				array_pop($ansdots);
			}
			list($lines,$dots,$odots) = explode(';;',$givenans);
			if ($lines=='') {
				$line = array();
			} else {
				$lines = explode(';',$lines);
				$line = $lines[0]; //only use first line
				$line = explode('),(',substr($line,1,strlen($line)-2));
				foreach ($line as $j=>$pt) {
					$line[$j] = explode(',',$pt);
				}
				if ($isclosed && ($line[0][0]-$line[count($line)-1][0])*($line[0][0]-$line[count($line)-1][0]) + ($line[0][1]-$line[count($line)-1][1])*($line[0][1]-$line[count($line)-1][1]) <=25*max(1,$reltolerance)) {
					array_pop($line);
				}
			}
			
			
			$matchstu = array();
			for ($i=0; $i<count($ansdots); $i++) {
				for ($j=0;$j<count($line);$j++) {
					if (($ansdots[$i][0]-$line[$j][0])*($ansdots[$i][0]-$line[$j][0]) + ($ansdots[$i][1]-$line[$j][1])*($ansdots[$i][1]-$line[$j][1]) <=25*max(1,$reltolerance)) {
						$matchstu[$i] = $j;
					}
				}
			}
			if ($isclosed && isset($matchstu[0])) {
				$matchstu[count($ansdots)] = $matchstu[0];
			}
			
			$totaladj = 0;  $correctadj = 0;
			for ($i =0;$i<count($ansdots) - ($isclosed?0:1);$i++) {
				$totaladj++;
				/*if ($i==count($ansdots)-1) {
					if (!isset($matchstu[$i]) || !isset($matchstu[0])) {
						$diff = -1;
					} else {
						$diff = abs($matchstu[0]-$matchstu[$i]);
					}
				} else {
				*/
					if (!isset($matchstu[$i]) || !isset($matchstu[$i+1])) {
						$diff = -1;
					} else {
						$diff = abs($matchstu[$i]-$matchstu[$i+1]);
					}
					
				//}
				if ($diff==1 || ($isclosed && $diff == count($matchstu)-2 && count($matchstu)!=0)) {
					$correctadj++;
				}
			}
			//echo "Total adjacencies: $totaladj.  Correct: $correctadj <br/>";
			
			if ($isclosed && isset($matchstu[0])) {
				$vals = (count($matchstu)-1)/max(count($line),count($ansdots));
			} else {
				$vals = (count($matchstu))/max(count($line),count($ansdots));
			}

			$adjv = $correctadj/$totaladj;
			
			$totscore = ($vals+$adjv)/2;
			//echo "Vals score: $vals, adj score: $adjv. </p>";

			if (isset($abstolerance)) {
				if ($totscore<$abstolerance) {
					return 0;
				} else {
					return 1;
				}
			} else {
				return $totscore;
			}
			
		}
		//not polygon, continue
		
		//evaluate all the functions in $answers
		foreach ($answers as $key=>$function) {
			if ($function=='') { continue; }
			$function = explode(',',$function);
			//curves: function
			//	  function, xmin, xmax
			//dot:  x,y
			//	x,y,"closed" or "open"
			//form: function, color, xmin, xmax, startmaker, endmarker
			if (count($function)==2 || (count($function)==3 && ($function[2]=='open' || $function[2]=='closed'))) { //is dot
				$pixx = ($function[0] - $settings[0])*$pixelsperx + $imgborder;
				$pixy = $settings[7] - ($function[1]-$settings[2])*$pixelspery - $imgborder;	
				if (count($function)==2 || $function[2]=='closed') {
					$ansdots[$key] = array($pixx,$pixy);
				} else {
					$ansodots[$key] = array($pixx,$pixy);
				}
				continue;
			}
			$anslines[$key] = array();
			$func = makepretty($function[0]);
			$func = mathphp($func,'x');
			$func = str_replace("(x)",'($x)',$func);
			$func = create_function('$x', 'return ('.$func.');');
			if (!isset($function[1])) {
				$function[1] = $settings[0];
			}
			if (!isset($function[2])) {
				$function[2] = $settings[1];
			}
			$xminpix = max(2*$imgborder,($function[1] - $settings[0])*$pixelsperx + $imgborder);
			$xmaxpix = min($settings[6]-2*$imgborder,($function[2] - $settings[0])*$pixelsperx + $imgborder);
			for ($k=ceil($xminpix/$step); $k*$step <= $xmaxpix; $k++) {
				$x = $k*$step;
				$coordx = ($x - $imgborder)/$pixelsperx + $settings[0]+1E-10;
				$coordy = $func($coordx);
				if ($coordy>$settings[2] && $coordy<$settings[3]) {
					$anslines[$key][$k] = $settings[7] - ($coordy-$settings[2])*$pixelspery - $imgborder;
					if (!isset($anslineptcnt[$k])) {
						$anslineptcnt[$k] =1;
					} else {
						$anslineptcnt[$k]++;
					}
					$linepts++;
				}
			}
			$linecnt++;
		}
		//break apart student entry
		list($lines,$dots,$odots) = explode(';;',$givenans);
		if ($lines=='') {
			$lines = array();
		} else {
			$lines = explode(';',$lines);
			foreach ($lines as $k=>$line) {
				$lines[$k] = explode('),(',substr($line,1,strlen($line)-2));
				foreach ($lines[$k] as $j=>$pt) {
					$lines[$k][$j] = explode(',',$pt);
				}
			}
		}
		if ($dots=='') {
			$dots = array();
		} else {
			$dots = explode('),(', substr($dots,1,strlen($dots)-2));
			foreach ($dots as $k=>$pt) {
				$dots[$k] = explode(',',$pt);
			}
		}
		if ($odots=='') {
			$odots = array();
		} else {
			$odots = explode('),(', substr($odots,1,strlen($odots)-2));
			foreach ($odots as $k=>$pt) {
				$odots[$k] = explode(',',$pt);
			}
		}
		
		//interp the lines
		$linedata = array();
		$totinterp = 0;
		foreach ($lines as $k=>$line) {
			for ($i=1;$i<count($line);$i++) {
				$leftx = min($line[$i][0],$line[$i-1][0]);
				$rightx = max($line[$i][0],$line[$i-1][0]);
				if ($line[$i][0]==$line[$i-1][0]) {
					$m = 9999;
				} else {
					$m = ($line[$i][1] - $line[$i-1][1])/($line[$i][0]-$line[$i-1][0]);
				}
				for ($k = ceil($leftx/$step); $k*$step<$rightx; $k++) {
					$x = $k*$step;
					$y = $line[$i-1][1] + $m*($x-$line[$i-1][0]);
					if ($y>$imgborder && $y<($settings[7]-$imgborder)) {
						$linedata[$k][] = $y;
						$totinterp++;
					}
				}
			}
		}
		
		$stdevs = array();
		$stcnts = array();
		$scores = array();
		$unmatchedanspts = array();
		$unmatchedanskeys = array();
		//compare lines
		foreach ($anslines as $key=>$answerline) {
			$unmatchedptcnt = 0;
			$stdevs[$key] = 0;
			$stcnts[$key] = 0;
			foreach($answerline as $k=>$ansy) {
				//if there are more ans pts than drawn, want to match up better than this; 
				//mark it for coming back to
				//if less ans pts than drawn, that's already accounted for in $percentoffpts
				if ($anslineptcnt[$k]>count($linedata[$k])) {
					$unmatchedanspts[$k] = 1;
					continue;
				}
				$minerr = $settings[7];
				for ($i=0; $i<count($linedata[$k]);$i++) {
					if (abs($ansy-$linedata[$k][$i])<$minerr) {
						$minerr = abs($ansy-$linedata[$k][$i]);
					}
				}
				if ($minerr<$settings[7]) {
					$stdevs[$key] += $minerr*$minerr;
					$stcnts[$key]++;
				}
			}	
		}
		//go back and match up drawn points with unmatched answer points 
		//we have more answer points than drawn points here
		foreach (array_keys($unmatchedanspts) as $k) {
			for ($i=0; $i<count($linedata[$k]); $i++) {
				$minerr = $settings[7];
				$minerrkey = -1;
				foreach ($anslines as $key=>$answerline) {
					if (abs($answerline[$k]-$linedata[$k][$i])<$minerr) {
						$minerr = abs($answerline[$k]-$linedata[$k][$i]);
						$minerrkey = $key;
					}
				}
				if ($minerrkey>-1) {
					$stdevs[$minerrkey] += $minerr*$minerr;
					$stcnts[$minerrkey]++;
				}
			}
		}
		//time to grade!
		$percentunmatcheddrawn = 0; //counts extra drawn points: percent of drawn that are extras
		if ($totinterp>0) {
			$percentunmatcheddrawn = max(($totinterp-$linepts)/$totinterp-.05*$reltolerance,0);
		}
		//divide up over all the lines
		$percentunmatcheddrawn = $percentunmatcheddrawn;
		foreach ($anslines as $key=>$answerline) {
			if ($stcnts[$key]<2) {
				$stdevs[$key] = 0;
			} else {
				$stdevs[$key] = sqrt($stdevs[$key]/($stcnts[$key]-1));
			}
			$stdevpen = max(8*($stdevs[$key]-5)/($settings[7]),0);
			$percentunmatchedans = max((count($answerline)-$stcnts[$key])/(count($answerline)),0);
			if ($percentunmatchedans<.05*$reltolerance) {
				$percentunmatchedans = 0;
			}
			$scores[$key] = 1-($stdevpen + $percentunmatcheddrawn + $percentunmatchedans)/$reltolerance;
			//echo "Line: $key, stdev: {$stdevs[$key]}, unmatchedrawn: $percentunmatcheddrawn, unmatchedans: $percentunmatchedans <br/>";
			if ($scores[$key]<0) { 
				$scores[$key] = 0;
			} else if ($scores[$key]>1) {
				$scores[$key] = 1;
			}
		}
		//go through dots
		//echo count($dots) .','.count($odots).','.count($ansdots).','.count($ansodots).'<br/>';
		if ((count($dots)+count($odots))==0) {
			$extradots = 0;
		} else {
			$extradots = max((count($dots) + count($odots) - count($ansdots) - count($ansodots))/(count($dots)+count($odots)),0);
		}
		foreach ($ansdots as $key=>$ansdot) {
			$scores[$key] = 0;
			for ($i=0; $i<count($dots); $i++) {
				if (($dots[$i][0]-$ansdot[0])*($dots[$i][0]-$ansdot[0]) + ($dots[$i][1]-$ansdot[1])*($dots[$i][1]-$ansdot[1]) <= 25) {
					$scores[$key] = 1 - $extradots;
					break;
				}
			}
		}
		//and open dots
		foreach ($ansodots as $key=>$ansdot) {
			$scores[$key] = 0;
			for ($i=0; $i<count($odots); $i++) {
				if (($odots[$i][0]-$ansdot[0])*($odots[$i][0]-$ansdot[0]) + ($odots[$i][1]-$ansdot[1])*($odots[$i][1]-$ansdot[1]) <= 25) {
					$scores[$key] = 1 - $extradots;
					break;
				}
			}
		}
		
		if (!isset($partweights)) {
			$partweights = array_fill(0,count($scores),1/count($scores));
		} else {
			if (!is_array($partweights)) {
				$partweights = explode(',',$partweights);
			}
		}
		$totscore = 0;
		foreach ($scores as $key=>$score) {
			$totscore += $score*$partweights[$key];
		}
		if (isset($abstolerance)) {
			if ($totscore<$abstolerance) {
				return 0;
			} else {
				return 1;
			}
		} else {
			return $totscore;
		}
			
	} else if ($anstype == "file") {
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$filename = basename($_FILES["qn$qn"]['name']);
		$filename = preg_replace('/[^\w\.]/','',$filename);
		$extension = strtolower(strrchr($filename,"."));
		$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p",".exe");
		if (in_array($extension,$badextensions)) {
			$GLOBALS['partlastanswer'] = "Error - Invalid file type";
			return 0;
		}
		if (isset($GLOBALS['testsettings']) && isset($GLOBALS['sessiondata']['groupid']) && $GLOBALS['testsettings']>0 && $GLOBALS['sessiondata']['groupid']>0) {
			$s3asid = $GLOBALS['sessiondata']['groupid'];
		} else if (isset($GLOBALS['asid'])) {
			$s3asid = $GLOBALS['asid'];
		} else {
			$GLOBALS['partlastanswer'] = "Error - no asid";
			return 0;
		}
		if ($s3asid==0) {
			$GLOBALS['partlastanswer'] = "Error - File not uploaded in preview";
			return 0;
		}
		if (isset($GLOBALS['isreview']) && $GLOBALS['isreview']==true) {
			$filename = 'rev-'.$filename;
		}
		if (is_uploaded_file($_FILES["qn$qn"]['tmp_name'])) {
			require_once("../includes/filehandler.php");

			$s3object = "adata/$s3asid/$filename";
			if (storeuploadedfile("qn$qn",$s3object)) {
				$GLOBALS['partlastanswer'] = "@FILE:$filename@";
			} else {
				//echo "Error storing file";
				$GLOBALS['partlastanswer'] = "Error storing file";
				
			}
			return 0;
		} else {
			//echo "Error uploading file";
			$GLOBALS['partlastanswer'] = "Error uploading file";
			return 0;
		}
	}
	
}


function getqsetid($questionid) {
	$query = "SELECT imas_questions.questionsetid,imas_questions.category,imas_libraries.name FROM imas_questions LEFT JOIN imas_libraries ";
	$query .= "ON imas_questions.category=imas_libraries.id WHERE imas_questions.id='$questionid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$row = mysql_fetch_row($result);
	if ($row[2]==null) {
		return (array($row[0],$row[1]));
	} else {
		return (array($row[0],$row[2]));
	}
	//return mysql_fetch_row($result);	
}

function getallqsetid($questions) {
	$qids = "'".implode("','",$questions)."'";
	$order = array_flip($questions);
	$out = array();
	$query = "SELECT imas_questions.questionsetid,imas_questions.category,imas_libraries.name,imas_questions.id FROM imas_questions LEFT JOIN imas_libraries ";
	$query .= "ON imas_questions.category=imas_libraries.id WHERE imas_questions.id IN ($qids)";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$out[0][$order[$row[3]]] = $row[0];// = array($row[0],$row[1]);
		if ($row[2]==null) {
			$out[1][$order[$row[3]]] = $row[1];
		} else {
			$out[1][$order[$row[3]]] = $row[2];
		}
	}
	return $out;
}

function isNaN( $var ) {
     return !preg_match('/^[-]?[0-9]+([\.][0-9]+)?([eE][+\-]?[0-9]+)?$/', $var);
     //possible alternative:
     //return ($var!==$var || $var*2==$var);
}

function checkreqtimes($tocheck,$rtimes) {
	$cleanans = preg_replace('/[^\w\*\/\+\-\(\)\[\],\.\^]+/','',$tocheck);
	//if entry used pow or exp, we want to replace them with their asciimath symbols for requiretimes purposes
	$cleanans = str_replace("pow","^",$cleanans);
	$cleanans = str_replace("exp","e",$cleanans);
	if (is_numeric($cleanans) && $cleanans>0 && $cleanans<1) {
		$cleanans = ltrim($cleanans,'0');
	}
	if ($rtimes != '') {
		$list = explode(",",$rtimes);
		for ($i=0;$i < count($list);$i+=2) {
			$comp = substr($list[$i+1],0,1);
			$num = intval(substr($list[$i+1],1));
			
			if ($list[$i]=='#') {
				$nummatch = preg_match_all('/[\d\.]+/',$cleanans,$m);
			} else {
				$nummatch = substr_count($cleanans,$list[$i]);
			}
			
			if ($comp == "=") {
				if ($nummatch!=$num) {
					return 0;
				}
			} else if ($comp == "<") {
				if ($nummatch>=$num) {
					return 0;
				}
			} else if ($comp == ">") {
				if ($nummatch<=$num) {
					return 0;
				}
			}
		}
	}
	return 1;
}

//parses complex numbers.  Can handle anything, but only with
//one i in it.
function parsecomplex($v) {
	$v = str_replace(' ','',$v);
	$len = strlen($v);
	if (substr_count($v,'i')>1) {
		return 'error - more than 1 i in expression';
	} else {
		$p = strpos($v,'i');
		if ($p===false) {
			$real = $v;
			$imag = 0;
		} else {
			//look left
			$nd = 0;
			for ($L=$p-1;$L>0;$L--) {
				$c = $v{$L};
				if ($c==')') {
					$nd++;
				} else if ($c=='(') {
					$nd--;
				} else if (($c=='+' || $c=='-') && $nd==0) {
					break;	
				}
			}
			//look right
			$nd = 0;
			
			for ($R=$p+1;$R<$len;$R++) {
				$c = $v{$R};
				if ($c=='(') {
					$nd++;
				} else if ($c==')') {
					$nd--;
				} else if (($c=='+' || $c=='-') && $nd==0) {
					break;	
				}
			}
			//which is bigger?
			if ($p-$L>1 && $R-$p>1) {
				return 'error - invalid form';
			} else if ($p-$L>1) {
				$imag = substr($v,$L,$p-$L);
				$real = substr($v,0,$L) . substr($v,$p+1);
			} else if ($R-$p>1) {
				if ($p>0) {
					$imag = $v{$p-1}.substr($v,$p+1,$R-$p-1);
					$real = substr($v,0,$p-1) . substr($v,$R);
				} else {
					$imag = substr($v,$p+1,$R-$p-1);
					$real = substr($v,0,$p) . substr($v,$R);
				}
			} else { //i or +i or -i or 3i  (one digit)
				if ($v{$L}=='+') {
					$imag = 1;
				} else if ($v{$L}=='-') {
					$imag = -1;
				} else if ($p==0) {
					$imag = 1;	
				} else {
					$imag = $v{$L};
				}
				$real = ($p>0?substr($v,0,$L):'') . substr($v,$p+1);
			}
			if ($real=='') {
				$real = 0;
			}
			if ($imag{0}=='/') {
				$imag = '1'.$imag;
			} else if (($imag{0}=='+' || $imag{0}=='-') && $imag{1}=='/') {
				$imag = $imag{0}.'1'.substr($imag,1);
			}
		}
		return array($real,$imag);
	}
}


//checks the format of a value 
//tocheck:  string to check
//ansformats:  array of answer formats.  Currently supports:
//   fraction, reducedfraction, fracordec, notrig, nolongdec, scinot, mixednumber, nodecimal
//returns:  false: bad format, true: good format
function checkanswerformat($tocheck,$ansformats) {
	if (in_array("fraction",$ansformats) || in_array("reducedfraction",$ansformats) || in_array("fracordec",$ansformats)) {
		if (!preg_match('/^\s*\-?\(?\d+\s*\/\s*\-?\d+\)?\s*$/',$tocheck) && !preg_match('/^\s*?\-?\d+\s*$/',$tocheck) && (!in_array("fracordec",$ansformats) || !preg_match('/^\s*?\-?\d*?\.\d*?\s*$/',$tocheck))) {
			return false;
		} else {
			if (in_array("reducedfraction",$ansformats) && strpos($tocheck,'/')!==false) {
				$tocheck = str_replace(array('(',')'),'',$tocheck);
				$tmpa = explode("/",$tocheck);
				if (gcd(abs($tmpa[0]),abs($tmpa[1]))!=1) {
					return false;
				}
			}
		}
	} 
	if (in_array("notrig",$ansformats)) {
		if (preg_match('/(sin|cos|tan|cot|csc|sec)/',$tocheck)) {
			return false;
		}
	} 
	if (in_array("nolongdec",$ansformats)) {
		if (preg_match('/\.\d{6}/',$tocheck)) {
			return false;
		}
	}
	if (in_array("scinot",$ansformats)) {
		$totest = str_replace(' ','',$tocheck);
		if (!preg_match('/^\-?\d(\.\d*)?(\*|x)10\^(\-?\d+)$/',$totest)) {
			return false;
		} 
	}
	
	if (in_array("mixednumber",$ansformats) || in_array("sloppymixednumber",$ansformats) ) {
		if (!preg_match('/^\s*\-?\s*\d+\s*(_|\s)\s*(\d+)\s*\/\s*(\d+)\s*$/',$tocheck,$mnmatches) && !preg_match('/^\s*?\-?\d+\s*$/',$tocheck) && !preg_match('/^\s*\-?\d+\s*\/\s*\-?\d+\s*$/',$tocheck)) {
			//if doesn't match any format, exit
			return false;
		} else {
			if (preg_match('/^\s*\-?\d+\s*\/\s*\-?\d+\s*$/',$tocheck)) {
				$tmpa = explode("/",$tocheck);
				if (in_array("mixednumber",$ansformats)) {
					if ((gcd(abs($tmpa[0]),abs($tmpa[1]))!=1) || $tmpa[0]>=$tmpa[1]) {
						return false;
					}
				}
			} else	if (!preg_match('/^\s*?\-?\d+\s*$/',$tocheck)) {
				if (in_array("mixednumber",$ansformats)) {
					if ($mnmatches[2]>=$mnmatches[3] || gcd($mnmatches[2],$mnmatches[3])!=1) {
						return false;
					}
				}
			}
		}
	}
	
	if (in_array("nodecimal",$ansformats)) {
		if (strpos($tocheck,'.')!==false) {
			return false;
		}
		if (strpos($tocheck,'E-')!==false) {
			return false;
		}
		if (preg_match('/10\^\(?\-/',$tocheck)) {
			return false;
		}
	}
	return true;			
}

function formathint($eword,$ansformats,$calledfrom) {
	$tip = '';
	if (in_array('fraction',$ansformats)) {
		$tip .= "Enter $eword as a fraction (like 3/5 or 10/4) or as a whole number (like 4 or -2)";
	} else if (in_array('reducedfraction',$ansformats)) {
		$tip .= "Enter $eword as a reduced fraction (like 5/3, not 10/6) or as a whole number (like 4 or -2)";
	} else if (in_array('mixednumber',$ansformats)) {
		$tip .= "Enter $eword as a reduced mixed number or as a whole number.  Example: 2 1/2 = `2 1/2`, or 2_1/2 = `2 1/2`";
	} else if (in_array('fracordec',$ansformats)) {
		$tip .= "Enter $eword as a fraction (like 3/5 or 10/4), a whole number (like 4 or -2), or exact decimal (like 0.5 or 1.25)";
	} else if (in_array('scinot',$ansformats)) {
		$tip .= "Enter $eword as in scientific notation.  Example: 3*10^2 = `3*10^2`";
	} else {
		$tip .= "Enter $eword as a number (like 5, -3, 2.2) or as a calculation (like 5/3, 2^3, 5+4)";
	}
	if ($calledfrom != 'calcmatrix') {
		$tip .= "<br/>Enter DNE for Does Not Exist, oo for Infinity";
	}
	if (in_array('nodecimal',$ansformats)) {
		$tip .= "<br/>Decimal values are not allowed";
	} else if (isset($reqdecimals)) {
		$tip .= "<br/>Your answer should be accurate to $reqdecimals decimal places.";
	}
	if (in_array('notrig',$ansformats)) {
		$tip .= "<br/>Trig functions (sin,cos,etc.) are not allowed";
	} 	
	return $tip;
}
?>
