<?php
//IMathAS:  Core of the testing engine.  Displays and grades questions
//(c) 2006 David Lippman
$mathfuncs = array("sin","cos","tan","sinh","cosh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
$allowedmacros = $mathfuncs;
require_once("mathphp.php");
require("interpret.php");
require("macros.php");
function displayq($qnidx,$qidx,$seed,$doshowans,$showhints,$attemptn,$returnqtxt=false,$clearla=false,$seqinactive=false) {
	srand($seed);
	
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
					}
				}
			} else {
				if (is_numeric($arv)) {
					$stuanswers[$i+1] = $arv;
				}
			}
				
			
		}
		$thisq = $qnidx+1;
	}
	
	eval(interpret('control',$qdata['qtype'],$qdata['control']));
	eval(interpret('qcontrol',$qdata['qtype'],$qdata['qcontrol']));
	$toevalqtxt = interpret('qtext',$qdata['qtype'],$qdata['qtext']);
	//$toevalqtxt = str_replace('\\','\\\\',$toevalqtxt);
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
	if (isset($answerformat)) {$options['answerformat'] = $answerformat;}
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
	} if ($seqinactive) {
		echo "<div>";
		//$evaledqtext = str_replace('<input','<input disabled="disabled"',$evaledqtext);
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
					echo filter("<div>$abox</div>\n");
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
				echo filter("<div>$answerbox</div>\n");
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

			echo "<div><input type=button value=\"Show Answer\" onClick='javascript:document.getElementById(\"ans$qnidx-$iidx\").className=\"shown\";'>"; //AMprocessNode(document.getElementById(\"ans$qnidx-$iidx\"));'>";
			echo filter(" <span id=\"ans$qnidx-$iidx\" class=\"hidden\">{$shans[$iidx]}</span></div>\n");
		} else if ($doshowans && isset($showanswer) && is_array($showanswer)) { //use part specific showanswer
			if (isset($showanswer[$iidx])) {
				echo "<div><input type=button value=\"Show Answer\" onClick='javascript:document.getElementById(\"ans$qnidx-$iidx\").className=\"shown\";'>";// AMprocessNode(document.getElementById(\"ans$qnidx-$iidx\"));'>";
				echo filter(" <span id=\"ans$qnidx-$iidx\" class=\"hidden\">{$showanswer[$iidx]}</span></div>\n");
			}
		}
	}
	echo "</div>\n";
	
	if ($doshowans && isset($showanswer) && !is_array($showanswer)) {  //single showanswer defined
		echo "<div><input type=button value=\"Show Answer\" onClick='javascript:document.getElementById(\"ans$qnidx\").className=\"shown\"; AMprocessNode(document.getElementById(\"ans$qnidx\"));'>";
		echo filter(" <span id=\"ans$qnidx\" class=\"hidden\">$showanswer </span></div>\n");
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
					}
				}
			} else {
				if (is_numeric($arv)) {
					$stuanswers[$i+1] = $arv;
				}
			}
		}
	}
	if ($qdata['qtype']=="multipart") {
		for ($kidx=0;$kidx<count($_POST);$kidx++) {
			$partnum = ($qnidx+1)*1000 + $kidx;
			if (isset($_POST["qn$partnum"]) && is_numeric($_POST["qn$partnum"])) {
				$stuanswers[$qnidx+1][$kidx] = floatval($_POST["qn$partnum"]);
			}
		}
		
	} else {
		if (isset($_POST["qn$qnidx"]) && is_numeric($_POST["qn$qnidx"])) {
			$stuanswers[$qnidx+1] = floatval($_POST["qn$qnidx"]);
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
	if (isset($reqdecimals) && !isset($abstolerance) && !isset($reltolerance)) {
		$abstolerance = 0.5/(pow(10,$reqdecimals));
	}
	srand($seed+2);
	//pack options from eval
	if (isset($answer)) {$options['answer'] = $answer;}
	if (isset($reltolerance)) {$options['reltolerance'] = $reltolerance;} 
	if (isset($abstolerance)) {$options['abstolerance'] = $abstolerance;}
	if (isset($answerformat)) {$options['answerformat'] = $answerformat;}
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
				$answeights = array_fill(0,count($anstypes)-1,round(1/count($anstypes),3));
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
		
		if (!isset($sz)) { $sz = 20;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\">";
		if ($answerformat=='list' || $answerformat=='exactlist') {
			$tip = "Enter your answer as a list of numbers separated with commas: Example: -4, 3, 2.5<br/>";
		} else {
			$tip = "Enter your answer as a number.  Examples: 3, -4, 5.5<BR>";
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
			array_push($randkeys,count($questions)-1);
		} else if ($noshuffle == "all") {
			$randkeys = array_keys($questions);
		} else {
			$randkeys = array_rand($questions,count($questions));
		}
		if ($displayformat == "select") { 
			$out = "<select name=\"qn$qn\"><option value=\"NA\">Select an answer</option>\n";
		} else if ($displayformat == "horiz") {
			
		} else if ($displayformat == "inline") {
			
		} else {
			$out .= "<ul class=nomark>";
		}
		
		for ($i=0; $i < count($randkeys); $i++) {
			if ($displayformat == "horiz") {
				$out .= "<div class=choice>{$questions[$randkeys[$i]]}<br/><input type=radio name=qn$qn value=$i ";
				if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
				$out .= "></div>\n";
			} else if ($displayformat == "select") {
				$out .= "<option value=$i ";
				if (($la!='') && ($la!='NA') && ($la == $i)) { $out .= "selected=1";}
				$out .= ">{$questions[$randkeys[$i]]}</option>\n";
			} else if ($displayformat == "inline") {
				$out .= "<input type=radio name=qn$qn value=$i ";
				if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
				$out .= ">{$questions[$randkeys[$i]]}";
			} else {
				$out .= "<li><input type=radio name=qn$qn value=$i ";
				if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
				$out .= ">{$questions[$randkeys[$i]]}</li> \n";
			}
		}
		if ($displayformat == "horiz") {
			$out .= "<div class=spacer>&nbsp;</div>\n";
		} else if ($displayformat == "select") {
			$out .= "</select>\n";
		} else if ($displayformat == "inline") {
			
		} else {
			$out .= "</ul>\n";
		}
		$tip = "Select the best answer";
		if (isset($answer)) {
			$sa = $questions[$answer];
		}
	} else if ($anstype == "multans") {
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (isset($options['answers'])) {if (is_array($options['answers'][$qn])) {$answers = $options['answers'][$qn];} else {$answers = $options['answers'];}}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}}
		
		if (!is_array($questions)) {
			echo "Eeek!  \$questions is not defined or needs to be an array";
			return false;
		}
		
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		if (isset($noshuffle)) {
			$randkeys = array_keys($questions);
		} else {
			$randkeys = array_rand($questions,count($questions));
		}
		$labits = explode('|',$la);
		$out .= "<ul class=nomark>";
		for ($i=0; $i < count($randkeys); $i++) {
			$out .= "<li><input type=checkbox radio name=\"qn$qn"."[$i]\" value=$i ";
			if (($labits[$i]!='') && ($labits[$i] == $i)) { $out .= "CHECKED";}
			$out .= ">{$questions[$randkeys[$i]]}</li> \n";
		}
		$out .= "</ul>";
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
		}
		$out .= "<div class=match>\n";
		$out .= "<p class=centered>$questiontitle</p>\n";
		$out .= "<ul class=nomark>\n";
		$las = explode("|",$la);
		for ($i=0;$i<count($randqkeys);$i++) {
			$out .= "<li><input class=\"text\" type=\"text\"  size=3 name=\"qn$qn-$i\" value=\"{$las[$i]}\"> {$questions[$randqkeys[$i]]}</li>\n";
		}
		$out .= "</ul>\n";
		$out .= "</div><div class=match>\n";
		$out .= "<p class=centered>$answertitle</p>\n";
		if ($noshuffle=="answers") {
			$randakeys = array_keys($answers);
		} else {
			$randakeys = array_rand($answers,count($answers));
		}
		$out .= "<ol class=lalpha>\n";
		for ($i=0;$i<count($randakeys);$i++) {
			$out .= "<li>{$answers[$randakeys[$i]]}</li>\n";
		}
		$out .= "</ol><input type=hidden name=\"qn$qn\" value=\"done\"></div><div class=spacer>&nbsp;</div>";
		$tip = "In each box provided, type the letter (a, b, c, etc.) of the matching answer in the right-hand column";
		for ($i=0; $i<count($randqkeys);$i++) {
			if (isset($matchlist)) {
				$akey = array_search($matchlist[$randqkeys[$i]],$randakeys);
			} else {
				$akey = array_search($randqkeys[$i],$randakeys);
			}
			$sa .= chr($akey+97)." ";

		}
	} else if ($anstype == "calculated") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$qn];} else {$hidepreview = $options['hidepreview'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$qn];} else {$reqdecimals = $options['reqdecimals'];}}
		
		if (!isset($sz)) { $sz = 20;}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		if (!isset($answerformat)) { $answerformat = '';}
		if (isset($ansprompt)) {$out .= "<label for=\"tc$qn\">$ansprompt</label>";}
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\">\n";
		if (!isset($hidepreview)) {
			$preview .= "<input type=button class=btn value=Preview onclick=\"calculate('tc$qn','p$qn','$answerformat')\"> &nbsp;\n";
		}
		$preview .= "<span id=p$qn></span> ";
		$out .= "<script>calctoproc[calctoproc.length] = $qn; calcformat[$qn] = '$answerformat';</script>\n";
		$ansformats = explode(',',$answerformat);
		if (in_array('list',$ansformats) || in_array('exactlist',$ansformats)) {
			$tip = "Enter your answer as a list of values separated by commas: Example: -4, 3, 2<br/>";
			$eword = "each value";
		} else {
			$tip = '';
			$eword = "your answer";
		}
		if (in_array('fraction',$ansformats)) {
			$tip .= "Enter $eword as a fraction (like 3/5 or 10/4) or as a whole number (like 4 or -2)";
		} else if (in_array('reducedfraction',$ansformats)) {
			$tip .= "Enter $eword as a reduced fraction (like 5/3, not 10/6) or as a whole number (like 4 or -2)";
		} else if (in_array('mixednumber',$ansformats)) {
			$tip .= "Enter $eword as a reduced mixed number or as a whole number.  Example: 2_1/2 = `2 1/2`";
		} else {
			$tip .= "Enter $eword as a number (like 5, -3, 2.2) or as a calculation (like 5/3, 2^3, 5+4)<BR>";
			$tip .= "Enter DNE for Does Not Exist, oo for Infinity";
		}
		if (in_array('nodecimal',$ansformats)) {
			$tip .= "<br/>Decimal values are not allowed";
		} else if (isset($reqdecimals)) {
			$tip .= "<br/>Your answer should be accurate to $reqdecimals decimal places.";
		}
		if (in_array('notrig',$ansformats)) {
			$tip .= "<br/>Trig functions (sin,cos,etc.) are not allowed";
		} 
		if (isset($answer)) {
			$sa = $answer;
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
			$answersize = explode(",",$answersize);
			$out .= "<table>";
			$count = 0;
			$las = explode("|",$la);
			for ($row=0; $row<$answersize[0]; $row++) {
				$out .= "<tr>";
				for ($col=0; $col<$answersize[1]; $col++) {
					$out .= "<td><input class=\"text\" type=\"text\"  size=3 name=\"qn$qn-$count\" value=\"{$las[$count]}\"></td>\n";
					$count++;
				}
				$out .= "</tr>";
			}
			$out .= "</table></p>\n";
			$tip = "Enter each element of the matrix as  number (like 5, -3, 2.2)";
		} else {
			$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\">\n";
			$out .= "<input type=button class=btn value=Preview onclick=\"AMmathpreview('qn$qn','p$qn')\"> &nbsp;\n";
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
		
		if (!isset($sz)) { $sz = 20;}
		
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		if (isset($ansprompt)) {$out .= $ansprompt;}
		if (isset($answersize)) {
			$answersize = explode(",",$answersize);
			$out .= "<table>";
			$count = 0;
			$las = explode("|",$la);
			for ($row=0; $row<$answersize[0]; $row++) {
				$out .= "<tr>";
				for ($col=0; $col<$answersize[1]; $col++) {
					$out .= "<td><input class=\"text\" type=\"text\"  size=3 name=\"qn$qn-$count\" id=\"qn$qn-$count\" value=\"{$las[$count]}\"></td>\n";
					$count++;
				}
				$out .= "</tr>";
			}
			$out .= "</table>\n";
			if (!isset($hidepreview)) {$preview .= "<input type=button class=btn value=Preview onclick=\"matrixcalc('qn$qn','p$qn',{$answersize[0]},{$answersize[1]})\"> &nbsp;\n";}
			$preview .= "<span id=p$qn></span>\n";
			$out .= "<script>matcalctoproc[matcalctoproc.length] = $qn; matsize[$qn]='{$answersize[0]},{$answersize[1]}';</script>\n";
			$tip = "Enter each element of the matrix as  number (like 5, -3, 2.2) or as a calculation (like 5/3, 2^3, 5+4)";
		} else {
			$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\">\n";
			$out .= "<input type=button value=Preview onclick=\"matrixcalc('tc$qn','p$qn')\"> &nbsp;\n";
			$out .= "<span id=p$qn></span> \n";
			$out .= "<script>matcalctoproc[matcalctoproc.length] = $qn;</script>\n";
			$tip = "Enter your answer as a matrix filled with numbers or calculations, like ((2,3,4/5),(3^2,4,5))";
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
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\">\n";
		if (!isset($hidepreview)) {$preview .= "<input type=button class=btn value=Preview onclick=\"AMpreview('tc$qn','p$qn')\"> &nbsp;\n";}
		$preview .= "<span id=p$qn></span>\n";
		
		if (!isset($variables)) { $variables = "x";}
		$variables = explode(",",$variables);
		$vlist = implode("|",$variables);
		$out .= "<script>functoproc[functoproc.length] = $qn; vlist[$qn]=\"$vlist\";</script>\n";
		if (isset($domain)) {$fromto = explode(",",$domain);} else {$fromto[0]=-10; $fromto[1]=10;}
		
		for ($i = 0; $i < 20; $i++) {
			for($j=0; $j < count($variables); $j++) {
				if (isset($fromto[2]) && $fromto[2]=="integers") {
					$tp[$j] = rand($fromto[0],$fromto[1]);
				} else {
					$tp[$j] = $fromto[0] + ($fromto[1]-$fromto[0])*rand(0,32000)/32000.0;
				}
			}
			$pts[$i] = implode("~",$tp);
		}
		$points = implode(",",$pts);
		$out .= "<script>pts[$qn]=\"$points\";</script>\n";
		if ($answerformat=="equation") {
			$out .= "<script>iseqn[$qn] = 1;</script>\n";
			$tip = "Enter your answer as an equation.  Example: y=3x^2+1, 2+x+y=3\n<br>Be sure your variables match those in the question";
		} else {
			$tip = "Enter your answer as an expression.  Example: 3x^2+1, x/5, (a+b)/c\n<br>Be sure your variables match those in the question";
		}
		if (isset($answer)) {
			$sa = makeprettydisp($answer);
		}
	} else if ($anstype == "string") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($sz)) { $sz = 20;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}
		
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=\"qn$qn\" id=\"qn$qn\" value=\"$la\">\n";
		$tip .= "Enter your answer as letters.  Examples: A B C, linear, a cat";
		$sa .= $answer;
	} else if ($anstype == "essay") {
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
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
		
		$out .= "<textarea rows=\"$rows\" cols=\"$cols\" name=\"qn$qn\" id=\"qn$qn\">$la</textarea>\n";
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
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\">";
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
		
		if (!isset($sz)) { $sz = 20;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}
		if ($multi>0) { $qn = $multi*1000+$qn;} 
		$out .= "<input class=\"text\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\">\n";
		if (!isset($hidepreview)) {
			$preview .= "<input type=button class=btn value=Preview onclick=\"intcalculate('tc$qn','p$qn')\"> &nbsp;\n";
		}
		$preview .= "<span id=p$qn></span> ";
		$out .= "<script>intcalctoproc[intcalctoproc.length] = $qn;</script>\n";
		$tip = "Enter your answer using interval notation.  Example: [2.1,5.6) <br/>";
		$tip .= "Use U for union to combine intervals.  Example: (-oo,2] U [4,oo)<br/>";
		$tip .= "Enter values as numbers (like 5, -3, 2.2) or as calculations (like 5/3, 2^3, 5+4)<br/>";
		$tip .= "Enter DNE for an empty set, oo for Infinity";
		if (isset($reqdecimals)) {
			$tip .= "<br/>Your numbers should be accurate to $reqdecimals decimal places.";
		}
		if (isset($answer)) {
			$sa = $answer;
		}
		
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
		$extrapennum = count($gaarr)+count($anarr);
		
		$correct = 0;
		foreach($anarr as $i=>$answer) {
			$foundloc = -1;
			foreach($gaarr as $j=>$givenans) {
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
						} else if ($anans=="oo" && $givenans=="oo") {
							$correct += 1; $foundloc = $j; break 2;
						} else if ($anans=="-oo" && $givenans=="-oo") {
							$correct += 1; $foundloc = $j; break 2;
						} 
					} else if (is_numeric($givenans)) {
						if (isset($abstolerance)) {
							if (abs($anans-$givenans) < $abstolerance + 1E-12) {$correct += 1; $foundloc = $j; break 2;} 	
						} else {
							if (abs($anans - $givenans)/(abs($anans)+.0001) < $reltolerance+ 1E-12) {$correct += 1; $foundloc = $j; break 2;} 
						}
					}
				}
			}
			if ($foundloc>-1) {
				array_splice($gaarr,$foundloc,1); //remove from list
				if (count($gaarr)==0) {
					break; //stop if no student answers left
				}
			}
		}
		$score = $correct/count($anarr) - count($gaarr)/$extrapennum;  //take off points for extranous stu answers
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
			array_push($randkeys,count($questions)-1);
		} else if ($noshuffle == "all") {
			$randkeys = array_keys($questions);
		} else {
			$randkeys = array_rand($questions,count($questions));
		}
		if ($givenans == null) {return 0;}


		if ($randkeys[$givenans] == $answer) {return 1;} else { return 0;}
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
		if (isset($noshuffle)) {
			$randqkeys = array_keys($questions);
		} else {
			$randqkeys = array_rand($questions,count($questions));
		}
		$akeys = explode(",",$answers);
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
		}
		if ($noshuffle=="answers") {
			$randakeys = array_keys($answers);
		} else {
			$randakeys = array_rand($answers,count($answers));
		}
		if (isset($matchlist)) {$matchlist = explode(',',$matchlist);}
		
		for ($i=0;$i<count($questions);$i++) {
			if ($i>0) {$GLOBALS['partlastanswer'] .= "|";} else {$GLOBALS['partlastanswer']='';}
			$GLOBALS['partlastanswer'] .= $_POST["qn$qn-$i"];
			if ($_POST["qn$qn-$i"]!="") {
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
		
		$answer = preg_replace_callback('/([^\[\(\)\]\,]+)/',"preg_mathphp_callback",$answer);
		$answerlist = explode(",",preg_replace('/[^\d\.,\-]/','',$answer));
		
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
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$correct = true;
		$answer = preg_replace_callback('/([^\[\(\)\]\,]+)/',"preg_mathphp_callback",$answer);
		$answerlist = explode(",",preg_replace('/[^\d\.,\-E]/','',$answer));
		if (isset($answersize)) {
			for ($i=0; $i<count($answerlist); $i++) {
				$givenanslist[$i] = $_POST["qn$qn-$i"];
			}
			$GLOBALS['partlastanswer'] = implode("|",$givenanslist);
		} else {
			$_POST["tc$qn"] = preg_replace('/\)\s*,\s*\(/','),(',$_POST["tc$qn"]);
			$GLOBALS['partlastanswer'] = $_POST["tc$qn"];
			if (substr_count($answer,'),(')!=substr_count($_POST["tc$qn"],'),(')) {$correct = false;}
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
		
		$cleanans = preg_replace('/[^\w\*\/\+\-\(\)\[\],\.\^]+/','',$_POST["tc$qn"]);
		//if entry used pow or exp, we want to replace them with their asciimath symbols for requiretimes purposes
		$cleanans = str_replace("pow","^",$cleanans);
		$cleanans = str_replace("exp","e",$cleanans);
		if ($requiretimes != '') {
			$list = explode(",",$requiretimes);
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
		
		$ansformats = explode(',',$answerformat);
		if (in_array('exactlist',$ansformats)) {
			$gaarr = explode(',',$givenans);
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
		$correct = 0;
		foreach($anarr as $i=>$answer) {
			$foundloc = -1;
			foreach($gaarr as $j=>$givenans) {
				if (in_array("fraction",$ansformats) || in_array("reducedfraction",$ansformats)) {
					if (!preg_match('/^\s*\-?\d+\s*\/\s*\-?\d+\s*$/',$orarr[$j]) && !preg_match('/^\s*?\-?\d+\s*$/',$orarr[$j])) {
						continue;
					} else {
						if (in_array("reducedfraction",$ansformats) && strpos($orarr[$j],'/')!==false) {
							$tmpa = explode("/",$orarr[$j]);
							if (gcd(abs($tmpa[0]),abs($tmpa[1]))!=1) {
								continue;
							}
						}
					}
				} 
				if (in_array("notrig",$ansformats)) {
					if (preg_match('/(sin|cos|tan|cot|csc|sec)/',$orarr[$j])) {
						continue;
					}
				} 
				if (in_array("mixednumber",$ansformats)) {
					if (!preg_match('/^\s*\-?\s*\d+\s*_\s*(\d+)\s*\/\s*(\d+)$\s*$/',$orarr[$j],$mnmatches) && !preg_match('/^\s*?\-?\d+\s*$/',$orarr[$j]) && !preg_match('/^\s*\-?\d+\s*\/\s*\-?\d+\s*$/',$orarr[$j])) {
						continue;
					} else {
						if (preg_match('/^\s*\-?\d+\s*\/\s*\-?\d+\s*$/',$orarr[$j])) {
							$tmpa = explode("/",$orarr[$j]);
							if ((gcd(abs($tmpa[0]),abs($tmpa[1]))!=1) || $tmpa[0]>=$tmpa[1]) {
								continue;
							}
						} else	if (!preg_match('/^\s*?\-?\d+\s*$/',$orarr[$j])) {
							if ($mnmatches[1]>=$mnmatches[2] || gcd($mnmatches[1],$mnmatches[2])!=1) {
								continue;
							}
						}
					}
				}
				if (in_array("nodecimal",$ansformats)) {
					if (strpos($orarr[$j],'.')!==false) {
						continue;
					}
					if (strpos($orarr[$j],'E-')!==false) {
						continue;
					}
					if (preg_match('/10\^\(?\-/',$orarr[$j])) {
						continue;
					}
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
						} else if ($anans=="oo" && $givenans=="oo") {
							$correct += 1; $foundloc = $j; break 2;
						} else if ($anans=="-oo" && $givenans=="-oo") {
							$correct += 1; $foundloc = $j; break 2;
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
				if (count($gaarr)==0) {
					break; //stop if no student answers left
				}
			}
		}
		$score = $correct/count($anarr) - count($gaarr)/$extrapennum;  //take off points for extranous stu answers
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
		
		//clean out any non-math junk
		$cleanans = preg_replace('/[^\w\*\/\+\-\(\)\[\],\.\^]+/','',$_POST["tc$qn"]);
		//if entry used pow or exp, we want to replace them with their asciimath symbols for requiretimes purposes
		$cleanans = str_replace("pow","^",$cleanans);
		$cleanans = str_replace("exp","e",$cleanans);
		
		$answer = preg_replace('/[^\w\*\/\+\=\-\(\)\[\]\{\}\,\.\^\$\!]+/','',$answer);

		if ($answerformat=="equation") {
			$answer = preg_replace('/(.*)=(.*)/','$1-($2)',$answer);
			unset($ratios);
		} else if ($answerformat=="toconst") {
			unset($diffs);
		}


		//test for correct format, if specified
		if ($requiretimes != '') {
			$list = explode(",",$requiretimes);
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
						$correct = false;
					}
				} else if ($comp == "<") {
					if ($nummatch>=$num) {
						$correct = false;
					}
				} else if ($comp == ">") {
					if ($nummatch<=$num) {
						$correct = false;
					}
				}
			}
		}
		if (!isset($variables)) { $variables = "x";}
		$variables = explode(",",$variables);
		$vlist = implode("|",$variables);
		
		if ($answer == '') {
			return 0;
		}
		$answer = mathphppre($answer);
		$answer = makepretty($answer);
		$answer = mathphp($answer,$vlist);
		
	
		for($i=0; $i < count($variables); $i++) {
			//$cleanans = str_replace("(".$variables[$i].")",'($tp['.$i.'])',$cleanans);
			$answer = str_replace("(".$variables[$i].")",'($tp['.$i.'])',$answer);
		}


		$myans = explode(",",$_POST["qn$qn-vals"]);


		if (isset($domain)) {$fromto = explode(",",$domain);} else {$fromto[0]=-10; $fromto[1]=10;}
		
		for ($i = 0; $i < 20; $i++) {
			for($j=0; $j < count($variables); $j++) {
				if (isset($fromto[2]) && $fromto[2]=="integers") {
					$tps[$i][$j] = rand($fromto[0],$fromto[1]);
				} else {
					$tps[$i][$j] = $fromto[0] + ($fromto[1]-$fromto[0])*rand(0,32000)/32000.0;
				}
			}
		}
		$cntnan = 0;
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
			
			if (isNaN($realans)) {$cntnan++; continue;} //avoid NaN problems
			if ($answerformat=="equation") {  //if equation, store ratios
				if (abs($realans)>.000001) {
					$ratios[] = $myans[$i]/$realans;
				}
			} else if ($answerformat=="toconst") {
				$diffs[] = $myans[$i] - $realans;
			} else { //otherwise, compare points
				if (isset($abstolerance)) {
					
					if (abs($myans[$i]-$realans) > $abstolerance-1E-12) {$correct = false; break;}	
				} else {
					if ((abs($myans[$i]-$realans)/(abs($realans)+.0001) > $reltolerance-1E-12)) {$correct = false; break;}
				}
			}
		}
		if ($cntnan==20 && isset($GLOBALS['teacherid'])) {
			echo "<p>Debug info: function evaled to Not-a-number at all test points.  Check \$domain</p>";
		}
		if ($answerformat=="equation") {
			if (count($ratios)>0) {
				$meanratio = array_sum($ratios)/count($ratios);
				for ($i=0; $i<count($ratios); $i++) {
					if (isset($abstolerance)) {
						if (abs($ratios[$i]-$meanratio) > $abstolerance-1E-12) {$correct = false; break;}	
					} else {
						if ((abs($ratios[$i]-$meanratio)/(abs($meanratio)+.0001) > $reltolerance-1E-12)) {$correct = false; break;}
					}
				}
			}
		} else if ($answerformat=="toconst") {
			$meandiff = array_sum($diffs)/count($diffs);
			if (is_nan($meandiff)) {
				$correct=false; return 0;
			} 
			for ($i=0; $i<count($diffs); $i++) {
				if (isset($abstolerance)) {

					if (abs($diffs[$i]-$meandiff) > $abstolerance-1E-12) {$correct = false; break;}	
				} else {
					if ((abs($diffs[$i]-$meandiff)/(abs($meandiff)+0.0001) > $reltolerance-1E-12)) {$correct = false; break;}
				}
			}
		}
		if ($correct == true) {return 1;} else { return 0;}
		
	} else if ($anstype == "string") {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (is_array($options['strflags'])) {$strflags = $options['strflags'][$qn];} else {$strflags = $options['strflags'];}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$GLOBALS['partlastanswer'] = $givenans;


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
		
		if ($flags['ignore_case']===true) {
			$givenans = strtoupper($givenans);
			$answer = strtoupper($answer);
			$anss = explode(' OR ',$answer);
		} else {
			$anss = explode(' or ',$answer);
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
			if ($flags['remove_whitespace']===true) {
				$anans = trim(preg_replace('/\s+/','',$anans));
			}
			if (!strcmp($anans,$givenans)) {
				$correct = 1;
				break;
			}
		}
		return $correct;
	} else if ($anstype == "essay") {
		$GLOBALS['partlastanswer'] = $givenans;
		return 0;
	} else if ($anstype == 'interval' || $anstype == 'calcinterval') {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = .001;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if ($anstype == 'interval') {
			$GLOBALS['partlastanswer'] = $givenans;
		} else if ($anstype == 'calcinterval') {
			$GLOBALS['partlastanswer'] = $_POST["tc$qn"];
		}
		if ($givenans == null) {return 0;}
		$correct = 0;
		$answer = str_replace(' ','',$answer);
		$givenans = str_replace(' ','',$givenans);
		if ($answer==='DNE') {
			if ($givenans==='DNE') {
				return 1;
			} else {
				return 0;
			}
		}
		$aarr = explode('U',$answer);
		$gaarr = explode('U',$givenans);
		if (count($aarr)!=count($gaarr)) {
			return 0;
		}
		
		foreach ($aarr as $ansint) {
			$anssm = substr($ansint,0,1);
			$ansem = substr($ansint,-1);
			$ansint = substr($ansint,1,strlen($ansint)-2);
			list($anssn,$ansen) = explode(',',$ansint);
			$foundloc = -1;
			foreach ($gaarr as $k=>$gansint) {
				$ganssm = substr($gansint,0,1);
				$gansem = substr($gansint,-1);
				$gansint = substr($gansint,1,strlen($gansint)-2);
				list($ganssn,$gansen) = explode(',',$gansint);
				if ($anssm!=$ganssm || $ansem!=$gansem) {
					continue;
				}
				if (strpos($anssn,'oo')) {
					if ($anssn===$ganssn) {} else {continue;}
				} else if (isset($abstolerance)) {
					if (abs($anssn-$ganssn) < $abstolerance + 1E-12) {} else {continue;} 	
				} else {
					if (abs($anssn - $ganssn)/(abs($anssn)+.0001) < $reltolerance+ 1E-12) {} else {continue;}
				}
				if (strpos($anssn,'oo')) {
					if ($ansen===$gansen) {} else {continue;}
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
				return 0;
			}
		}
		if (count($gaarr)>0) { //extraneous student intervals?
			return 0;
		}
		return 1;
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

?>
