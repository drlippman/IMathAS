<?php

//IMathAS:  Core of the testing engine.  Displays and grades questions
//(c) 2006 David Lippman
//quadratic inequalities contributed by Cam Joyce
$GLOBALS['noformatfeedback'] = true;
$mathfuncs = array("sin","cos","tan","sinh","cosh","tanh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
$allowedmacros = $mathfuncs;
//require_once("mathphp.php");
require_once("mathphp2.php");
require("interpret5.php");
require("macros.php");
function displayq($qnidx,$qidx,$seed,$doshowans,$showhints,$attemptn,$returnqtxt=false,$clearla=false,$seqinactive=false,$qcolors=array()) {
	//$starttime = microtime(true);
	global $imasroot, $myrights, $showtips, $urlmode, $CFG;

	if (!isset($_SESSION['choicemap'])) { $_SESSION['choicemap'] = array(); }
	$GLOBALS['inquestiondisplay'] = true;

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

	$query = "SELECT qtype,control,qcontrol,qtext,answer,hasimg,extref,solution,solutionopts FROM imas_questionset WHERE id='$qidx'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$qdata = mysql_fetch_array($result, MYSQL_ASSOC);

	if ($qdata['hasimg']>0) {
		$query = "SELECT var,filename,alttext FROM imas_qimages WHERE qsetid='$qidx'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				${$row[0]} = "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/qimages/{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
			} else {
				${$row[0]} = "<img src=\"$imasroot/assessment/qimages/{$row[1]}\" alt=\"".htmlentities($row[2],ENT_QUOTES)."\" />";
			}
		}
	}
	if (isset($GLOBALS['lastanswers'])) {
		foreach ($GLOBALS['lastanswers'] as $iidx=>$ar) {
			$arv = explode('##',$ar);
			$arv = $arv[count($arv)-1];
			$arv = explode('&',$arv);
			if (count($arv)==1) {
				$arv = $arv[0];
			}
			if (is_array($arv)) {
				foreach ($arv as $kidx=>$arvp) {
					//if (is_numeric($arvp)) {
					if ($arvp==='') {
						$stuanswers[$iidx+1][$kidx] = null;
					} else {
						if (strpos($arvp,'$f$')!==false) {
							$tmp = explode('$f$',$arvp);
							$arvp = $tmp[0];
						}
						if (strpos($arvp,'$!$')!==false) {
							$arvp = explode('$!$',$arvp);
							$arvp = $arvp[1];
							if (is_numeric($arvp)) { $arvp = intval($arvp);}
						}
						if (strpos($arvp,'$#$')!==false) {
							$tmp = explode('$#$',$arvp);
							$arvp = $tmp[0];
							$stuanswersval[$iidx+1][$kidx] = $tmp[1];
						}
						$stuanswers[$iidx+1][$kidx] = $arvp;
					}
					//} else {
					//	$stuanswers[$iidx+1][$kidx] = preg_replace('/\W+/','',$arvp);
					//}
				}
			} else {
				//if (is_numeric($arv)) {
				if ($arv==='' || $arv==='ReGen') {
					$stuanswers[$iidx+1] = null;
				} else {
					if (strpos($arvp,'$f$')!==false) {
						$tmp = explode('$f$',$arv);
						$arv = $tmp[0];
					}
					if (strpos($arv,'$!$')!==false) {
						$arv = explode('$!$',$arv);
						$arv = $arv[1];
						if (is_numeric($arv)) { $arv = intval($arv);}
					}
					if (strpos($arv,'$#$')!==false) {
						$tmp = explode('$#$',$arv);
						$arv = $tmp[0];
						$stuanswersval[$iidx+1] = $tmp[1];
					}
					$stuanswers[$iidx+1] = $arv;
				}
				//} else {
				//	$stuanswers[$iidx+1] = preg_replace('/\W+/','',$arv);
				//}
			}


		}
		$thisq = $qnidx+1;
	}
	if (isset($GLOBALS['scores'])) {
		$scorenonzero = getscorenonzero();
	}
	if (isset($GLOBALS['rawscores'])) {
		$scoreiscorrect = getiscorrect();
	}

	eval(interpret('control',$qdata['qtype'],$qdata['control']));
	eval(interpret('qcontrol',$qdata['qtype'],$qdata['qcontrol']));
	$toevalqtxt = interpret('qtext',$qdata['qtype'],$qdata['qtext']);
	$toevalqtxt = str_replace('\\','\\\\',$toevalqtxt);
	$toevalqtxt = str_replace(array('\\\\n','\\\\"','\\\\$','\\\\{'),array('\\n','\\"','\\$','\\{'),$toevalqtxt);

	$toevalsoln = interpret('qtext',$qdata['qtype'],$qdata['solution']);
	$toevalsoln = str_replace('\\','\\\\',$toevalsoln);
	$toevalsoln = str_replace(array('\\\\n','\\\\"','\\\\$','\\\\{'),array('\\n','\\"','\\$','\\{'),$toevalsoln);

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
	if (isset($requestclearla) && !isset($GLOBALS['questionscoreref'])) { $la = ''; $qcolors = array();}

	//$la = $GLOBALS['lastanswers'][$qnidx];

	if (isset($choices) && !isset($questions)) {
		$questions =& $choices;
	}
	if (isset($variable) && !isset($variables)) {
		$variables =& $variable;
	}

	if (isset($formatfeedbackon)) {
		unset($GLOBALS['noformatfeedback']);
	}
	if (isset($anstypes)) {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}
		$anstypes = array_map('trim', $anstypes);
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
	if (isset($strflags)) {$options['strflags'] = $strflags;}
	if (isset($domain)) {$options['domain'] = $domain;}
	if (isset($answerboxsize)) {$options['answerboxsize'] = $answerboxsize;}
	if (isset($hidepreview)) {$options['hidepreview'] = $hidepreview;}
	if (isset($matchlist)) {$options['matchlist'] = $matchlist;}
	if (isset($noshuffle)) {$options['noshuffle'] = $noshuffle;}
	if (isset($reqdecimals)) {$options['reqdecimals'] = $reqdecimals;}
	if (isset($reqsigfigs)) {$options['reqsigfigs'] = $reqsigfigs;}
	if (isset($grid)) {$options['grid'] = $grid;}
	if (isset($snaptogrid)) {$options['snaptogrid'] = $snaptogrid;}
	if (isset($background)) {$options['background'] = $background;}
	if (isset($scoremethod)) {$options['scoremethod'] = $scoremethod;}

	if (isset($GLOBALS['nocolormark'])) {  //no colors
		$qcolors = array();
	}
	if ($qdata['qtype']=="multipart" || $qdata['qtype']=='conditional') {
		if ($qdata['qtype']=="multipart") {
			if (isset($answeights)) {
				if (!is_array($answeights)) {
					$answeights = explode(",",$answeights);
				}
				$answeights = array_map('trim', $answeights);
				$localsum = array_sum($answeights);
				if ($localsum==0) {$localsum = 1;}
				foreach ($answeights as $kidx=>$vval) {
					$answeights[$kidx] = $vval/$localsum;
				}
			} else {
				if (count($anstypes)>1) {
					if ($qnpointval==0) {$qnpointval=1;}
					$answeights = array_fill(0,count($anstypes)-1,round($qnpointval/count($anstypes),3));
					$answeights[] = $qnpointval-array_sum($answeights);
					foreach ($answeights as $kidx=>$vval) {
						$answeights[$kidx] = $vval/$qnpointval;
					}
				} else {
					$answeights = array(1);
				}
			}
		}
		$laparts = explode("&",$la);

		foreach ($anstypes as $kidx=>$anstype) {
			$qcol = ($qdata['qtype']=="multipart" && isset($qcolors[$kidx]))?(is_numeric($qcolors[$kidx])?rawscoretocolor($qcolors[$kidx],$answeights[$kidx]):$qcolors[$kidx]):'';
			list($answerbox[$kidx],$entryTips[$kidx],$shanspt[$kidx],$previewloc[$kidx]) = makeanswerbox($anstype,$kidx,$laparts[$kidx],$options,$qnidx+1,$qcol);
		}
	} else {
		$qcol = isset($qcolors[0])?(is_numeric($qcolors[0])?rawscoretocolor($qcolors[0],1):$qcolors[0]):'';
		list($answerbox,$entryTips[0],$shanspt[0],$previewloc) = makeanswerbox($qdata['qtype'],$qnidx,$la,$options,0,$qcol);
	}
	if ($qdata['qtype']=='conditional') {
		$qcol = isset($qcolors[0])?(is_numeric($qcolors[0])?rawscoretocolor($qcolors[0],1):$qcolors[0]):'';
		if ($qcol!='') {
			$toevalqtxt = '<div class=\\"'.$qcol.'\\">'.$toevalqtxt.str_replace('"','\\"',getcolormark($qcol)).'</div>';
		}
		if (!isset($showanswer)) {
			$showanswer = _('Answers may vary');
		}
	}


	if ($returnqtxt) {
		//$toevalqtxt = preg_replace('/\$answerbox(\[\d+\])?/','',$toevalqtxt);
	}


	//create hintbuttons
	if (isset($hints) && $showhints) {
		//$hintkeys = array_keys($hints);
		//$lastkey = array_pop($hintkeys);
		$lastkey = max(array_keys($hints));
		if ($qdata['qtype']=="multipart" && is_array($hints[$lastkey])) { //individual part hints
			foreach ($hints as $iidx=>$hintpart) {
				if (isset($scoreiscorrect) && $scoreiscorrect[$thisq][$iidx]==1) {continue;}
				$lastkey = max(array_keys($hintpart));
				if ($attemptn>$lastkey) {
					$usenum = $lastkey;
				} else {
					$usenum = $attemptn;
				}
				if ($hintpart[$usenum]!='') {
					if (strpos($hintpart[$usenum],'</div>')!==false) {
						$hintloc[$iidx] = $hintpart[$usenum];
					} else if (strpos($hintpart[$usenum],'button"')!==false) {
						$hintloc[$iidx] = "<p>{$hintpart[$usenum]}</p>\n";
					} else if (isset($hintlabel)) {
						$hintloc[$iidx] = "<p>$hintlabel {$hintpart[$usenum]}</p>\n";
					} else {
						$hintloc[$iidx] = "<p><i>" . _('Hint:') . "</i> {$hintpart[$usenum]}</p>\n";
					}
				}

			}
		} else if (!isset($scoreiscorrect) || $scoreiscorrect[$thisq]!=1) { //one hint for question
			//$lastkey = end(array_keys($hints));

			if ($attemptn>$lastkey) {
				$usenum = $lastkey;
			} else {
				$usenum = $attemptn;
			}
			if ($hints[$usenum]!='') {
				if (strpos($hints[$usenum],'</div>')!==false) {
					$hintloc = $hints[$usenum];
				} else if (strpos($hints[$usenum],'button"')!==false) {
					$hintloc = "<p>{$hints[$usenum]}</p>\n";
				} else if (isset($hintlabel)) {
					$hintloc = "<p>$hintlabel {$hints[$usenum]}</p>\n";
				} else {
					$hintloc = "<p><i>" . _('Hint:') . "</i> {$hints[$usenum]}</p>\n";
				}
			}

		}
	}
	if (is_array($answerbox)) {
		foreach($answerbox as $iidx=>$abox) {
			if ($seqinactive) {
				$answerbox[$iidx] = str_replace('<input','<input disabled="disabled"',$abox);
				$answerbox[$iidx] = str_replace('<textarea','<textarea disabled="disabled"',$answerbox[$iidx]);
				$answerbox[$iidx] = str_replace('style="width:98%;" class="mceEditor"','',$answerbox[$iidx]);
				$answerbox[$iidx] = str_replace('<select','<select disabled="disabled"',$answerbox[$iidx]);
			}
			if (strpos($toevalqtxt,"\$previewloc[$iidx]")===false) {
				if (strpos($answerbox[$iidx],"previewloctemp$iidx")!==false) {
					$answerbox[$iidx] = str_replace('<span id="previewloctemp'.$iidx.'"></span>', $previewloc[$iidx], $answerbox[$iidx]);
				} else {
					$answerbox[$iidx] .= $previewloc[$iidx];
				}
			}
			if (isset($hideanswerboxes) && $hideanswerboxes==true) {
				$answerbox[$iidx] = '';
			}
		}
	} else {
		if ($seqinactive) {
			$answerbox = str_replace('<input','<input disabled="disabled"',$answerbox);
			$answerbox = str_replace('<textarea','<textarea disabled="disabled"',$answerbox);
			$answerbox = str_replace('style="width:98%;" class="mceEditor"','',$answerbox);
			$answerbox = str_replace('<select','<select disabled="disabled"',$answerbox);
		}
		if (strpos($toevalqtxt,'$previewloc')===false) {
			if (strpos($answerbox,"previewloctemp$qnidx")!==false) {
				$answerbox = str_replace('<span id="previewloctemp'.$qnidx.'"></span>', $previewloc, $answerbox);
			} else {
				$answerbox .= $previewloc;
			}
		}
		if (isset($hideanswerboxes) && $hideanswerboxes==true) {
			$answerbox = '';
		}
	}

	if ($doshowans && isset($showanswer) && !is_array($showanswer)) {  //single showanswer defined
		$showanswerloc = (isset($showanswerstyle) && $showanswerstyle=='inline')?'<span>':'<div>';
		if ($nosabutton) {
			$showanswerloc .= filter(_('Answer:') . " $showanswer\n");
		} else {
			$showanswerloc .= "<input class=\"sabtn\" type=button value=\""._('Show Answer')."\" onClick='javascript:document.getElementById(\"ans$qnidx\").className=\"shown\"; rendermathnode(document.getElementById(\"ans$qnidx\"));' />";
			$showanswerloc .= filter(" <span id=\"ans$qnidx\" class=\"hidden\">$showanswer </span>\n");
		}
		$showanswerloc .= (isset($showanswerstyle) && $showanswerstyle=='inline')?'</span>':'</div>';
	} else if ($doshowans) {
		$showanswerloc = array();
		foreach($entryTips as $iidx=>$entryTip) {
			$showanswerloc[$iidx] = (isset($showanswerstyle) && $showanswerstyle=='inline')?'<span>':'<div>';
			if ($doshowans && (!isset($showanswer) || (is_array($showanswer) && !isset($showanswer[$iidx]))) && $shanspt[$iidx]!=='') {
				if ($nosabutton) {
					$showanswerloc[$iidx] .= "<span id=\"showansbtn$qnidx-$iidx\">".filter(_('Answer:') . " {$shanspt[$iidx]}</span>\n");
				} else {
					$showanswerloc[$iidx] .= "<input id=\"showansbtn$qnidx-$iidx\" class=\"sabtn\" type=button value=\"". _('Show Answer'). "\" onClick='javascript:document.getElementById(\"ans$qnidx-$iidx\").className=\"shown\";' />"; //AMprocessNode(document.getElementById(\"ans$qnidx-$iidx\"));'>";
					$showanswerloc[$iidx] .= filter(" <span id=\"ans$qnidx-$iidx\" class=\"hidden\">{$shanspt[$iidx]}</span>\n");
				}
			} else if ($doshowans && isset($showanswer) && is_array($showanswer)) { //use part specific showanswer
				if (isset($showanswer[$iidx])) {
					if ($nosabutton) {
						$showanswerloc[$iidx] .= "<span id=\"showansbtn$qnidx-$iidx\">".filter(_('Answer:') . " {$showanswer[$iidx]}</span>\n");
					} else {
						$showanswerloc[$iidx] .= "<input id=\"showansbtn$qnidx-$iidx\" class=\"sabtn\" type=button value=\""._('Show Answer')."\" onClick='javascript:document.getElementById(\"ans$qnidx-$iidx\").className=\"shown\";' />";// AMprocessNode(document.getElementById(\"ans$qnidx-$iidx\"));'>";
						$showanswerloc[$iidx] .= filter(" <span id=\"ans$qnidx-$iidx\" class=\"hidden\">{$showanswer[$iidx]}</span>\n");
					}
				}
			}
			$showanswerloc[$iidx] .= (isset($showanswerstyle) && $showanswerstyle=='inline')?'</span>':'</div>';
		}
		if (!is_array($answerbox) && count($showanswerloc)==1) { //not a multipart question
			$showanswerloc = $showanswerloc[0];
		}
	}

	//echo $toevalqtext;
	eval("\$evaledqtext = \"$toevalqtxt\";");
	eval("\$evaledsoln = \"$toevalsoln\";");
	if ($returnqtxt===2) {
		return '<div id="writtenexample" class="review">'.$evaledsoln.'</div>';
	} else if ($returnqtxt===3) {
		return '<div class="question">'.$evaledqtext.'</div><div id="writtenexample" class="review">'.$evaledsoln.'</div>';
	}
	if (($qdata['solutionopts']&1)==0) {
		$evaledsoln = '<i>'._('This solution is for a similar problem, not your specific version').'</i><br/>'.$evaledsoln;
	}

	if (strpos($evaledqtext,'[AB')!==false) {
		if (is_array($answerbox)) {
			foreach($answerbox as $iidx=>$abox) {
				if (strpos($evaledqtext,'[AB'.$iidx.']')!==false) {
					$evaledqtext = str_replace('[AB'.$iidx.']', $abox, $evaledqtext);
					$toevalqtxt .= '$answerbox['.$iidx.']';  //to prevent autoadd
				}
			}
		} else {
			$evaledqtext = str_replace('[AB]', $answerbox, $evaledqtext);
			$toevalqtxt .= '$answerbox';
		}
	}
	if (strpos($evaledqtext,'[SAB')!==false) {
		if (!isset($showanswerloc)) {
			$evaledqtext = preg_replace('/\[SAB\d*\]/','',$evaledqtext);
		} else if (is_array($showanswerloc)) {
			foreach($showanswerloc as $iidx=>$saloc) {
				if (strpos($evaledqtext,'[SAB'.$iidx.']')!==false) {
					$evaledqtext = str_replace('[SAB'.$iidx.']', $saloc, $evaledqtext);
					$toevalqtxt .= '$showanswerloc['.$iidx.']';  //to prevent autoadd
				}
			}
		} else {
			$evaledqtext = str_replace('[SAB]', $showanswerloc, $evaledqtext);
			$toevalqtxt .= '$showanswerloc';
		}
	}
	if ($returnqtxt) {
		$returntxt = $evaledqtext;
	} else if ($seqinactive) {
		echo "<div class=inactive>";
		echo filter($evaledqtext);
	} else {
		echo "<div class=\"question\"><div>\n";
		echo filter($evaledqtext);
		echo "</div>\n";
	}

	if (strpos($toevalqtxt,'$answerbox')===false) {
		if (is_array($answerbox)) {
			foreach($answerbox as $iidx=>$abox) {
				if ($seqinactive) {
					$answerbox[$iidx] = str_replace('<input','<input disabled="disabled"',$abox);
					$answerbox[$iidx] = str_replace('<select','<select disabled="disabled"',$answerbox[$iidx]);
				}
				if ($returnqtxt) {
					$returntxt .= "<p>$abox</p>";
				} else {
					echo filter("<div class=\"toppad\">$abox</div>\n");
					echo "<div class=spacer>&nbsp;</div>\n";
				}
			}
		} else {  //one question only
			if ($seqinactive) {
				$answerbox = str_replace('<input','<input disabled="disabled"',$answerbox);
				$answerbox = str_replace('<select','<select disabled="disabled"',$answerbox);
			}
			if ($returnqtxt) {
				$returntxt .= "<p>$answerbox</p>";
			} else {
				echo filter("<div class=\"toppad\">$answerbox</div>\n");
			}
		}
	}

	if ($returnqtxt) {
		return $returntxt;
	}
	if (isset($helptext) &&  $showhints) {
		echo '<div><p class="tips">'.filter($helptext).'</p></div>';
	}
	if ($showhints && ($qdata['extref']!='' || (($qdata['solutionopts']&2)==2 && $qdata['solution']!=''))) {
		echo '<div><p class="tips">', _('Get help: ');
		if ($qdata['extref']!= '') {
			$extref = explode('~~',$qdata['extref']);

			if (isset($GLOBALS['questions']) && (!isset($GLOBALS['sessiondata']['isteacher']) || $GLOBALS['sessiondata']['isteacher']==false) && !isset($GLOBALS['sessiondata']['stuview'])) {
				$qref = $GLOBALS['questions'][$qnidx].'-'.($qnidx+1);
			} else {
				$qref = '';
			}
			for ($i=0;$i<count($extref);$i++) {
				$extrefpt = explode('!!',$extref[$i]);
				if ($extrefpt[0]=='video' || strpos($extrefpt[1],'youtube.com/watch')!==false) {
					$extrefpt[1] = $urlmode . $_SERVER['HTTP_HOST'] . "$imasroot/assessment/watchvid.php?url=".urlencode($extrefpt[1]);
					if ($extrefpt[0]=='video') {$extrefpt[0]='Video';}
					echo formpopup($extrefpt[0],$extrefpt[1],660,530,"button",true,"video",$qref);
				} else if ($extrefpt[0]=='read') {
					echo formpopup("Read",$extrefpt[1],730,500,"button",true,"text",$qref);
				} else {
					echo formpopup($extrefpt[0],$extrefpt[1],730,500,"button",true,"text",$qref);
				}
			}
		}
		if (($qdata['solutionopts']&2)==2 && $qdata['solution']!='') {
			$addr = $urlmode. $_SERVER['HTTP_HOST'] . "$imasroot/assessment/showsoln.php?id=".$qidx.'&sig='.md5($qidx.$GLOBALS['sessiondata']['secsalt']);
			$addr .= '&t='.($qdata['solutionopts']&1).'&cid='.$GLOBALS['cid'];
			echo formpopup(_("Written Example"),$addr,730,500,"button",true,"soln",$qref);
		}
		echo '</p></div>';
	}

	echo "<div>";

	foreach($entryTips as $iidx=>$entryTip) {
		if ((!isset($hidetips) || (is_array($hidetips) && !isset($hidetips[$iidx])))&& !$seqinactive && $showtips>0) {
			echo "<p class=\"tips\" ";
			if ($showtips!=1) { echo 'style="display:none;" ';}
			echo ">", _('Box'), " ".($iidx+1).": <span id=\"tips$qnidx-$iidx\">".filter($entryTip)."</span></p>";
		}
		if ($doshowans && strpos($toevalqtxt,'$showanswerloc')===false && is_array($showanswerloc) && isset($showanswerloc[$iidx])) {
			echo '<div>'.$showanswerloc[$iidx].'</div>';
		}
		/*if ($doshowans && (!isset($showanswer) || (is_array($showanswer) && !isset($showanswer[$iidx]))) && $shanspt[$iidx]!=='') {
			if ($nosabutton) {
				echo filter("<div>" . _('Answer:') . " {$shanspt[$iidx]} </div>\n");
			} else {
				echo "<div><input class=\"sabtn\" type=button value=\"", _('Show Answer'), "\" onClick='javascript:document.getElementById(\"ans$qnidx-$iidx\").className=\"shown\";' />"; //AMprocessNode(document.getElementById(\"ans$qnidx-$iidx\"));'>";
				echo filter(" <span id=\"ans$qnidx-$iidx\" class=\"hidden\">{$shanspt[$iidx]}</span></div>\n");
			}
		} else if ($doshowans && isset($showanswer) && is_array($showanswer)) { //use part specific showanswer
			if (isset($showanswer[$iidx])) {
				if ($nosabutton) {
					echo filter("<div>" .  _('Answer:') . " {$showanswer[$iidx]} </div>\n");
				} else {
					echo "<div><input class=\"sabtn\" type=button value=\""._('Show Answer')."\" onClick='javascript:document.getElementById(\"ans$qnidx-$iidx\").className=\"shown\";' />";// AMprocessNode(document.getElementById(\"ans$qnidx-$iidx\"));'>";
					echo filter(" <span id=\"ans$qnidx-$iidx\" class=\"hidden\">{$showanswer[$iidx]}</span></div>\n");
				}
			}
		}*/
	}
	echo "</div>\n";

	if ($doshowans && !is_array($showanswerloc) && strpos($toevalqtxt,'$showanswerloc')===false) {  //single showanswer defined
		/*if ($nosabutton) {
			echo filter("<div>" . _('Answer:') . " $showanswer </div>\n");
		} else {
			echo "<div><input class=\"sabtn\" type=button value=\""._('Show Answer')."\" onClick='javascript:document.getElementById(\"ans$qnidx\").className=\"shown\"; rendermathnode(document.getElementById(\"ans$qnidx\"));' />";
			echo filter(" <span id=\"ans$qnidx\" class=\"hidden\">$showanswer </span></div>\n");
		}*/
		echo '<div>'.$showanswerloc.'</div>';

	}
	if ($doshowans && ($qdata['solutionopts']&4)==4 && $qdata['solution']!='') {
		if ($nosabutton) {
			echo filter("<div><p>" . _('Detailed Solution').'</p>'. $evaledsoln .'</div>');
		} else {
			echo "<div><input class=\"sabtn\" type=button value=\""._('Show Detailed Solution')."\" onClick='javascript:$(\"#soln$qnidx\").removeClass(\"hidden\"); rendermathnode(document.getElementById(\"soln$qnidx\"));' />";
			echo filter(" <div id=\"soln$qnidx\" class=\"hidden review\" style=\"margin-top:5px;margin-bottom:5px;\">$evaledsoln </div></div>\n");
		}
	}

	echo "</div>\n";
	//echo 'time: '.(microtime(true) - $starttime);
	if ($qdata['qtype']=="multipart" ) {
		return $anstypes;
	} else {
		return array($qdata['qtype']);
	}
}


//inputs: Question number, Question id, rand seed, given answer
function scoreq($qnidx,$qidx,$seed,$givenans,$attemptn=0,$qnpointval=1) {
	unset($abstolerance);
	srand($seed);
	$GLOBALS['inquestiondisplay'] = false;
	$query = "SELECT qtype,control,answer FROM imas_questionset WHERE id='$qidx'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$qdata = mysql_fetch_array($result, MYSQL_ASSOC);

	if (isset($GLOBALS['lastanswers'])) {
		foreach ($GLOBALS['lastanswers'] as $iidx=>$ar) {
			$arv = explode('##',$ar);
			$arv = $arv[count($arv)-1];
			$arv = explode('&',$arv);
			if (count($arv)==1) {
				$arv = $arv[0];
			}
			if (is_array($arv)) {
				foreach ($arv as $kidx=>$arvp) {
					//if (is_numeric($arvp)) {
					if ($arvp==='') {
						$stuanswers[$iidx+1][$kidx] = null;
					} else {
						if (strpos($arvp,'$!$')!==false) {
							$arvp = explode('$!$',$arvp);
							$arvp = $arvp[1];
							if (is_numeric($arvp)) { $arvp = intval($arvp);}
						}
						if (strpos($arvp,'$#$')!==false) {
							$tmp = explode('$#$',$arvp);
							$arvp = $tmp[0];
							$stuanswersval[$iidx+1][$kidx] = $tmp[1];
						}
						$stuanswers[$iidx+1][$kidx] = $arvp;
					}
					//} else {
					//	$stuanswers[$iidx+1][$kidx] = preg_replace('/\W+/','',$arvp);
					//}
				}
			} else {
				//if (is_numeric($arv)) {
				if ($arv==='' || $arv==='ReGen') {
					$stuanswers[$iidx+1] = null;
				} else {
					if (strpos($arv,'$!$')!==false) {
						$arv = explode('$!$',$arv);
						$arv = $arv[1];
						if (is_numeric($arv)) { $arv = intval($arv);}
					}
					if (strpos($arv,'$#$')!==false) {
						$tmp = explode('$#$',$arv);
						$arv = $tmp[0];
						$stuanswersval[$iidx+1] = $tmp[1];
					}
					$stuanswers[$iidx+1] = $arv;
				}
				//} else {
				//	$stuanswers[$iidx+1] = preg_replace('/\W+/','',$arv);
				//}
			}
		}
	}
	$thisq = $qnidx+1;
	unset($stuanswers[$thisq]);  //unset old stuanswer for this question

	if ($qdata['qtype']=="multipart" || $qdata['qtype']=='conditional') {
		$postpartstoprocess = array();
		foreach ($_POST as $postk=>$postv) {
			$prefix = substr($postk,0,2);
			if ($prefix=='tc' || $prefix=='qn') {
				$partnum = intval(substr($postk,2));
				if (floor($partnum/1000)==$thisq) {
					$kidx = round($partnum - 1000*floor($partnum/1000));
					$postpartstoprocess[$partnum] = $kidx;
				}
			}
		}

		foreach ($postpartstoprocess as $partnum=>$kidx) {

		//for ($kidx=0;$kidx<count($_POST);$kidx++) {
		//	$partnum = ($qnidx+1)*1000 + $kidx;
			if (isset($_POST["tc$partnum"])) {
				$stuanswers[$thisq][$kidx] = stripslashes($_POST["tc$partnum"]);
				if ($_POST["qn$partnum"]==='') {
					$stuanswersval[$thisq][$kidx] = null;
					$stuanswers[$thisq][$kidx] = null;
				} else if (is_numeric($_POST["qn$partnum"])) {
					$stuanswersval[$thisq][$kidx] = floatval($_POST["qn$partnum"]);
				} else if (substr($_POST["qn$partnum"],0,2)=='[(') { //calcmatrix
					$stuav = stripslashes(str_replace(array('(',')','[',']'),'',$_POST["qn$partnum"]));
					$stuanswersval[$thisq][$kidx] = str_replace(',','|',$stuav);
				}
			} else if (isset($_POST["qn$partnum"])) {
				if (isset($_POST["qn$partnum-0"])) { //calcmatrix with matrixsize
					$tmp = array();
					$spc = 0;
					while (isset($_POST["qn$partnum-$spc"])) {
						$tmp[] = stripslashes($_POST["qn$partnum-$spc"]);
						$spc++;
					}
					$stuanswers[$thisq][$kidx] = implode('|',$tmp);
					$stuav = stripslashes(str_replace(array('(',')','[',']'),'',$_POST["qn$partnum"]));
					$stuanswersval[$thisq][$kidx] = str_replace(',','|',$stuav);
				} else {
					$stuanswers[$thisq][$kidx] = stripslashes_deep($_POST["qn$partnum"]); //preg_replace('/\W+/','',stripslashes($_POST["qn$partnum"]));
					if ($_POST["qn$partnum"]==='') {
						$stuanswersval[$thisq][$kidx] = null;
						$stuanswers[$thisq][$kidx] = null;
					} else if (is_numeric($_POST["qn$partnum"])) {
						$stuanswersval[$thisq][$kidx] = floatval($_POST["qn$partnum"]);
					}
					if (isset($_SESSION['choicemap'][$partnum])) {
						if (is_array($stuanswers[$thisq][$kidx])) { //multans
							foreach ($stuanswers[$thisq][$kidx] as $k=>$v) {
								$stuanswers[$thisq][$kidx][$k] = $_SESSION['choicemap'][$partnum][$v];
							}
							$stuanswers[$thisq][$kidx] = implode('|',$stuanswers[$thisq][$kidx]);
						} else {
							$stuanswers[$thisq][$kidx] = $_SESSION['choicemap'][$partnum][$stuanswers[$thisq][$kidx]];
							if ($stuanswers[$thisq][$kidx]===null) {
								$stuanswers[$thisq][$kidx] = 'NA';
							}
						}
					}
				}
			} else if (isset($_POST["qn$partnum-0"])) {
				$tmp = array();
				$spc = 0;
				while (isset($_POST["qn$partnum-$spc"])) {
					$tmp[] = stripslashes($_POST["qn$partnum-$spc"]);
					$spc++;
				}
				$stuanswers[$thisq][$kidx] = implode('|',$tmp);
			}
		}
	} else {
		if (isset($_POST["tc$qnidx"])) {
			$stuanswers[$thisq] = stripslashes($_POST["tc$qnidx"]);
			if (is_numeric($_POST["qn$qnidx"])) {
				$stuanswersval[$thisq] = floatval($_POST["qn$qnidx"]);
			} else if (substr($_POST["qn$qnidx"],0,2)=='[(') { //calcmatrix
				$stuav = stripslashes(str_replace(array('(',')','[',']'),'',$_POST["qn$qnidx"]));
				$stuanswersval[$thisq] = str_replace(',','|',$stuav);
			}
		} else if (isset($_POST["qn$qnidx"])) {
			if (isset($_POST["qn$qnidx-0"])) { //calcmatrix with matrixsize
				$tmp = array();
				$spc = 0;
				while (isset($_POST["qn$qnidx-$spc"])) {
					$tmp[] = stripslashes($_POST["qn$qnidx-$spc"]);
					$spc++;
				}
				$stuanswers[$thisq] = implode('|',$tmp);
				$stuav = stripslashes(str_replace(array('(',')','[',']'),'',$_POST["qn$qnidx"]));
				$stuanswersval[$thisq] = str_replace(',','|',$stuav);
			} else {
				$stuanswers[$thisq] = stripslashes_deep($_POST["qn$qnidx"]); //preg_replace('/\W+/','',stripslashes($_POST["qn$qnidx"]));
				if (is_numeric($_POST["qn$qnidx"])) {
					$stuanswersval[$thisq] = floatval($_POST["qn$qnidx"]);
				}
				if (isset($_SESSION['choicemap'][$qnidx])) {
					if (is_array($stuanswers[$thisq])) { //multans
						foreach ($stuanswers[$thisq] as $k=>$v) {
							$stuanswers[$thisq][$k] = $_SESSION['choicemap'][$qnidx][$v];
						}
						$stuanswers[$thisq] = implode('|',$stuanswers[$thisq]);
					} else {
						$stuanswers[$thisq] = $_SESSION['choicemap'][$qnidx][$stuanswers[$thisq]];
					}
				}
			}
		} else if (isset($_POST["qn$qnidx-0"])) {
			$tmp = array();
			$spc = 0;
			while (isset($_POST["qn$qnidx-$spc"])) {
				$tmp[] = stripslashes($_POST["qn$qnidx-$spc"]);
				$spc++;
			}
			$stuanswers[$thisq] = implode('|',$tmp);
		}
	}

	eval(interpret('control',$qdata['qtype'],$qdata['control']));
	srand($seed+1);
	eval(interpret('answer',$qdata['qtype'],$qdata['answer']));

	if (isset($choices) && !isset($questions)) {
		$questions =& $choices;
	}
	if (isset($variable) && !isset($variables)) {
		$variables =& $variable;
	}
	if (isset($anstypes)) {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}
		$anstypes = array_map('trim', $anstypes);
	}

	if (isset($reqdecimals)) {
		if (is_array($reqdecimals)) {
			foreach ($reqdecimals as $kidx=>$vval) {
				if (!isset($abstolerance[$kidx]) && !isset($reltolerance[$kidx])) {
					$abstolerance[$kidx] = 0.5/(pow(10,$vval));
				}
			}
		} else {
			if (!isset($abstolerance) && !isset($reltolerance)) { //set global abstol
				$abstolerance = 0.5/(pow(10,$reqdecimals));
			} else if (isset($anstypes)) {
				foreach ($anstypes as $kidx=>$vval) {
					if (!isset($abstolerance[$kidx]) && !isset($reltolerance[$kidx])) {
						$abstolerance[$kidx] = 0.5/(pow(10,$reqdecimals));
					}
				}
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
	if (isset($requiretimeslistpart)) {$options['requiretimeslistpart'] = $requiretimeslistpart;}
	if (isset($scoremethod)) {$options['scoremethod'] = $scoremethod;}
	if (isset($strflags)) {$options['strflags'] = $strflags;}
	if (isset($matchlist)) {$options['matchlist'] = $matchlist;}
	if (isset($noshuffle)) {$options['noshuffle'] = $noshuffle;}
	if (isset($reqsigfigs)) {$options['reqsigfigs'] = $reqsigfigs;}
	if (isset($grid)) {$options['grid'] = $grid;}
	if (isset($snaptogrid)) {$options['snaptogrid'] = $snaptogrid;}
	if (isset($partweights)) {$options['partweights'] = $partweights;}
	if (isset($partialcredit)) {$options['partialcredit'] = $partialcredit;}
	if (isset($ansprompt)) {$options['ansprompt'] = $ansprompt;}
	if (isset($anstypes)) {$options['anstypes'] = $anstypes;}

	$score = 0;
	if ($qdata['qtype']=="multipart") {
		if (in_array('essay',$anstypes) || in_array('file',$anstypes)) {
			$GLOBALS['questionmanualgrade'] = true;
		}
		$partla = array();
		if (isset($answeights)) {
			if (!is_array($answeights)) {
				$answeights = explode(",",$answeights);
			}
			$answeights = array_map('trim',$answeights);
			$localsum = array_sum($answeights);
			if ($localsum==0) {$localsum = 1;}
			foreach ($answeights as $kidx=>$vval) {
				$answeights[$kidx] = $vval/$localsum;
			}
		} else {
			if (count($anstypes)>1) {
				if ($qnpointval==0) {$qnpointval=1;}
				$answeights = array_fill(0,count($anstypes)-1,round($qnpointval/count($anstypes),2));
				$answeights[] = $qnpointval-array_sum($answeights);
				foreach ($answeights as $kidx=>$vval) {
					$answeights[$kidx] = $vval/$qnpointval;
				}
			} else {
				$answeights = array(1);
			}
		}
		$scores = array();  $raw = array(); $accpts = 0;
		foreach ($anstypes as $kidx=>$anstype) {
			$partnum = ($qnidx+1)*1000 + $kidx;
			$raw[$kidx] = scorepart($anstype,$kidx,$_POST["qn$partnum"],$options,$qnidx+1);
			if (isset($scoremethod) && $scoremethod=='acct') {
				if ($anstype=='string' && $answer[$kidx]==='') {
					$scores[$kidx] = $raw[$kidx]-1;  //0 if correct, -1 if wrong
				} else {
					$scores[$kidx] = $raw[$kidx];
					$accpts++;
				}
			} else {
				$scores[$kidx] = ($raw[$kidx]<0)?0:round($raw[$kidx]*$answeights[$kidx],4);
			}
			$raw[$kidx] = round($raw[$kidx],2);
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
		if (isset($scoremethod) && $scoremethod == "singlescore") {
			return array(round(array_sum($scores),3),implode('~',$raw));
		} else if (isset($scoremethod) && $scoremethod == "allornothing") {
			if (array_sum($scores)<.98) { return array(0,implode('~',$raw)); } else { return array(1,implode('~',$raw));}
		} else if (isset($scoremethod) && $scoremethod == "acct") {
			$sc = round(array_sum($scores)/$accpts,3);
			return (array($sc, implode('~',$raw)));
		} else {
			return array(implode('~',$scores),implode('~',$raw));
		}
	} else {
		if ($qdata['qtype']=='essay' || $qdata['qtype']=='file') {
			$GLOBALS['questionmanualgrade'] = true;
		}
		$score = scorepart($qdata['qtype'],$qnidx,$givenans,$options,0);
		if (isset($scoremethod) && $scoremethod == "allornothing") {
			if ($score<.98) {$score=0;}
		}
		if ($qdata['qtype']!='conditional') {
			$GLOBALS['partlastanswer'] = str_replace('&','',$GLOBALS['partlastanswer']);
			$GLOBALS['partlastanswer'] = preg_replace('/#+/','#',$GLOBALS['partlastanswer']);
		}
		if ($GLOBALS['lastanswers'][$qnidx]=='') {
			$GLOBALS['lastanswers'][$qnidx] = $GLOBALS['partlastanswer'];
		} else {
			$GLOBALS['lastanswers'][$qnidx] .= '##'.$GLOBALS['partlastanswer'];
		}
		return array(round($score,3),round($score,2));
	}




}




function makeanswerbox($anstype, $qn, $la, $options,$multi,$colorbox='') {
	global $myrights, $useeqnhelper, $showtips, $imasroot;
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
		if (isset($options['reqsigfigs'])) {if (is_array($options['reqsigfigs'])) {$reqsigfigs = $options['reqsigfigs'][$qn];} else {$reqsigfigs = $options['reqsigfigs'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}} else {$displayformat='';}
		if (isset($options['scoremethod']))if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$qn];} else {$scoremethod = $options['scoremethod'];}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = array_map('trim',explode(',',$answerformat));

		if (!isset($sz)) { $sz = 20;}
		if (isset($ansprompt) && !in_array('nosoln',$ansformats) && !in_array('nosolninf',$ansformats))  {
			$out .= "<label for=\"qn$qn\">$ansprompt</label>";
		}
		if ($multi>0) { $qn = $multi*1000+$qn; $qstr = ($multi-1).'-'.$qn;} else { $qstr = $qn;}

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
		if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) ||  in_array('orderedlist',$ansformats)) {
			$tip = _('Enter your answer as a list of whole or decimal numbers separated with commas: Examples: -4, 3, 2.5') . "<br/>";
			$shorttip = _('Enter a list of whole or decimal numbers');
		} else if (in_array('set',$ansformats) || in_array('exactset',$ansformats)) {
			$tip = _('Enter your answer as a set of whole or decimal numbers separated with commas: Example: {-4, 3, 2.5}') . "<br/>";
			$shorttip = _('Enter a set of whole or decimal numbers');
		} else {
			$tip = _('Enter your answer as a whole or decimal number.  Examples: 3, -4, 5.5') . "<br/>";
			$shorttip = _('Enter a whole or decimal number');
		}
		$tip .= _('Enter DNE for Does Not Exist, oo for Infinity');
		if (isset($reqdecimals)) {
			$tip .= "<br/>" . sprintf(_('Your answer should be accurate to %d decimal places.'), $reqdecimals);
			$shorttip .= sprintf(_(", accurate to %d decimal places"), $reqdecimals);
		}
		if (isset($reqsigfigs)) {
			if ($reqsigfigs{0}=='=') {
				$reqsigfigs = substr($reqsigfigs,1);
				$answer = prettysigfig($answer,$reqsigfigs);
				$tip .= "<br/>" . sprintf(_('Your answer should have exactly %d significant figures.'), $reqsigfigs);
				$shorttip .= sprintf(_(', with exactly %d significant figures'), $reqsigfigs);
			} else if ($reqsigfigs{0}=='[') {
				$reqsigfigparts = explode(',',substr($reqsigfigs,1,-1));
				$tip .= "<br/>" . sprintf(_('Your answer should have between %d and %d significant figures.'), $reqsigfigparts[0], $reqsigfigparts[1]);
				$shorttip .= sprintf(_(', with %d - %d significant figures'), $reqsigfigparts[0], $reqsigfigparts[1]);
			} else {
				if ($answer!=0) {
					$v = -1*floor(-log10(abs($answer))-1e-12) - $reqsigfigs;
				}
				if ($answer!=0  && $v < 0 && strlen($answer) - strpos($answer,'.')-1 + $v < 0) {
					$answer = prettysigfig($answer,$reqsigfigs);
				}
				$tip .= "<br/>" . sprintf(_('Your answer should have at least %d significant figures.'), $reqsigfigs);
				$shorttip .= sprintf(_(', with at least %d significant figures'), $reqsigfigs);
			}
		}
		$out .= "$leftb<input ";
		$addlclass = '';
		if ($displayformat=='alignright') { $out .= 'style="text-align: right;" ';}
		else if ($displayformat=='hidden') { $out .= 'style="position: absolute; visibility: hidden; left: -5000px;" ';}
		else if ($displayformat=='debit') { $out .= 'onkeyup="editdebit(this)" style="text-align: right;" ';}
		else if ($displayformat=='credit') { $out .= 'onkeyup="editcredit(this)" style="text-align: right;" '; $addlclass=' creditbox';}

		/*if ($showtips==2) { //eqntips: work in progress
			if ($multi==0) {
				$qnref = "$qn-0";
			} else {
				$qnref = ($multi-1).'-'.($qn%1000);
			}
			$out .= "onfocus=\"showehdd('qn$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" ";
		}*/

		if ($showtips==2) { //eqntips: work in progress
			if ($multi==0) {
				$qnref = "$qn-0";
			} else {
				$qnref = ($multi-1).'-'.($qn%1000);
			}
			if ($useeqnhelper && $useeqnhelper>2 && !(isset($scoremethod) && $scoremethod=='acct')) {
				$out .= "onfocus=\"showeebasicdd('qn$qn',0);showehdd('qn$qn','$shorttip','$qnref');\" onblur=\"hideebasice();hideebasicedd();hideeh();\" onclick=\"reshrinkeh('qn$qn')\" ";
			} else {
				$out .= "onfocus=\"showehdd('qn$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" onclick=\"reshrinkeh('qn$qn')\" ";
			}
		} else if ($useeqnhelper && !(isset($scoremethod) && $scoremethod=='acct') ) {
			$out .= "onfocus=\"showeebasicdd('qn$qn',0)\" onblur=\"hideebasice();hideebasicedd();\" ";
		}

		$out .= "class=\"text $colorbox$addlclass\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\" autocomplete=\"off\" />$rightb";
		$out .= getcolormark($colorbox);
		if ($displayformat=='hidden') { $out .= '<script type="text/javascript">imasprevans['.$qstr.'] = "'.$la.'";</script>';}

		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
			$answer = str_replace('"','',$answer);
		}
		if (isset($answer)) {
			if (in_array('parenneg',$ansformats) && $answer < 0) {
				$sa = '('.(-1*$answer).')';
			} else {
				$sa = $answer;
			}
		}
	} else if ($anstype == "choices") {
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}} else {$displayformat="vert";}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}} else {$noshuffle = "none";}

		if (!is_array($questions)) {
			echo _('Eeek!  $questions is not defined or needs to be an array');
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
		$_SESSION['choicemap'][$qn] = $randkeys;
		if (isset($GLOBALS['capturechoices'])) {
			if (!isset($GLOBALS['choicesdata'])) {
				$GLOBALS['choicesdata'] = array();
			}
			if ($GLOBALS['capturechoices']=='shuffled') {
				$GLOBALS['choicesdata'][$qn] = array($anstype, $questions, $answer, $randkeys);
			} else {
				$GLOBALS['choicesdata'][$qn] = array($anstype, $questions);
			}
		}

		//trim out unshuffled showans
		$la = explode('$!$',$la);
		$la = $la[0];

		if (isset($GLOBALS['lastanspretty'])) {  //generate nice display version of
			if ($multi>0) {
				$laarr = explode('##',$GLOBALS['lastanspretty'][$multi-1]);
				foreach ($laarr as $k=>$v) {
					if ($v=='ReGen') { continue;}
					$laparts = explode('&',$v);
					$laparts[$qn%1000] = str_replace(array('##','&'),'',$questions[$randkeys[$laparts[$qn%1000]]]);
					$laarr[$k] = implode('&',$laparts);
				}
				$GLOBALS['lastanspretty'][$multi-1] = implode('##',$laarr);
			} else {
				$laarr = explode('##',$GLOBALS['lastanspretty'][$qn]);
				foreach ($laarr as $k=>$v) {
					if ($v=='ReGen') { continue;}
					$laarr[$k] = str_replace(array('##','&'),'',$questions[$randkeys[$v]]);
				}
				$GLOBALS['lastanspretty'][$qn] = implode('##',$laarr);
			}
		}

		if ($displayformat == 'column') { $displayformat = '2column';}

		if (substr($displayformat,1)=='column') {
			$ncol = $displayformat{0};
			$itempercol = ceil(count($randkeys)/$ncol);
			$displayformat = 'column';
		}

		if ($displayformat == 'inline') {
			if ($colorbox != '') {$style .= ' class="'.$colorbox.'" ';} else {$style='';}
			$out .= "<span $style id=\"qnwrap$qn\">";
		} else if ($displayformat != 'select') {
			if ($colorbox != '') {$style .= ' class="'.$colorbox.' clearfix" ';} else {$style=' class="clearfix" ';}
			$out .= "<div $style id=\"qnwrap$qn\" style=\"display:block\">";
		}
		if ($displayformat == "select") {
			$msg = '?';
			foreach ($questions as $qv) {
				if (strlen($qv)>2 && !($qv{0}=='&' && $qv{strlen($qv)-1}==';')) {
					$msg = _('Select an answer');
					break;
				}
			}
			if ($colorbox != '') {$style .= ' class="'.$colorbox.'" ';} else {$style='';}
			$out = "<select name=\"qn$qn\" id=\"qn$qn\" $style><option value=\"NA\">$msg</option>\n";
		} else if ($displayformat == "horiz") {

		} else if ($displayformat == "inline") {

		} else if ($displayformat == 'column') {

		}  else {
			$out .= "<ul class=nomark>";
		}


		for ($i=0; $i < count($randkeys); $i++) {
			if ($displayformat == "horiz") {
				$out .= "<div class=choice ><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label><br/><input type=radio id=\"qn$qn-$i\" name=qn$qn value=$i ";
				if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
				$out .= " /></div>\n";
			} else if ($displayformat == "select") {
				$out .= "<option value=$i ";
				if (($la!='') && ($la!='NA') && ($la == $i)) { $out .= "selected=1";}
				$out .= ">".str_replace('`','',$questions[$randkeys[$i]])."</option>\n";
			} else if ($displayformat == "inline") {
				$out .= "<input type=radio name=qn$qn value=$i id=\"qn$qn-$i\" ";
				if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
				$out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label>";
			} else if ($displayformat == 'column') {
				if ($i%$itempercol==0) {
					if ($i>0) {
						$out .= '</ul></div>';
					}
					$out .= '<div class="match"><ul class=nomark>';
				}
				$out .= "<li><input type=radio name=qn$qn value=$i id=\"qn$qn-$i\" ";
				if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
				$out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}<label></li> \n";
			} else {
				$out .= "<li><input class=\"unind\" type=radio name=qn$qn value=$i id=\"qn$qn-$i\" ";
				if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
				$out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}<label></li> \n";
			}
		}
		if ($displayformat == "horiz") {
			//$out .= "<div class=spacer>&nbsp;</div>\n";
		} else if ($displayformat == "select") {
			$out .= "</select>\n";
		} else if ($displayformat == 'column') {
			$out .= "</ul></div>";//<div class=spacer>&nbsp;</div>\n";
		} else if ($displayformat == "inline") {

		} else {
			$out .= "</ul>\n";
		}
		$out .= getcolormark($colorbox);
		if ($displayformat == 'inline') {
			$out .= "</span>";
		} else if ($displayformat != 'select') {
			$out .= "</div>";
		}

		$tip = _('Select the best answer');
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
			else if (isset($options['answer'])) {if (is_array($options['answer'])) {$answers = $options['answer'][$qn];} else {$answers = $options['answer'];}}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}

		if (!is_array($questions)) {
			echo _('Eeek!  $questions is not defined or needs to be an array');
			return false;
		}

		if ($multi>0) { $qn = $multi*1000+$qn;}

		//trim out unshuffled showans
		$la = explode('$!$',$la);
		$la = $la[0];

		if ($noshuffle == "last") {
			$randkeys = array_rand(array_slice($questions,0,count($questions)-1),count($questions)-1);
			shuffle($randkeys);
			array_push($randkeys,count($questions)-1);
		} else if ($noshuffle == "all" || count($questions)==1) {
			$randkeys = array_keys($questions);
		} else {
			$randkeys = array_rand($questions,count($questions));
			shuffle($randkeys);
		}
		$_SESSION['choicemap'][$qn] = $randkeys;
		if (isset($GLOBALS['capturechoices'])) {
			if (!isset($GLOBALS['choicesdata'])) {
				$GLOBALS['choicesdata'] = array();
			}
			if ($GLOBALS['capturechoices']=='shuffled') {
				$GLOBALS['choicesdata'][$qn] = array($anstype, $questions, $answers, $randkeys);
			} else {
				$GLOBALS['choicesdata'][$qn] = array($anstype, $questions);
			}
		}

		$labits = explode('|',$la);
		if ($displayformat == 'column') { $displayformat = '2column';}

		if (substr($displayformat,1)=='column') {
			$ncol = $displayformat{0};
			$itempercol = ceil(count($randkeys)/$ncol);
			$displayformat = 'column';
		}

		if ($displayformat == 'inline') {
			if ($colorbox != '') {$style .= ' class="'.$colorbox.'" ';} else {$style='';}
			$out .= "<span $style id=\"qnwrap$qn\">";
		} else  {
			if ($colorbox != '') {$style .= ' class="'.$colorbox.' clearfix" ';} else {$style=' class="clearfix" ';}
			$out .= "<div $style id=\"qnwrap$qn\" style=\"display:block\">";
		}
		if ($displayformat == "horiz") {

		} else if ($displayformat == "inline") {

		} else if ($displayformat == 'column') {

		} else {
			$out .= "<ul class=nomark>";
		}

		for ($i=0; $i < count($randkeys); $i++) {
			if ($displayformat == "horiz") {
				$out .= "<div class=choice><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label><br/>";
				$out .= "<input type=checkbox name=\"qn$qn"."[$i]\" value=$i id=\"qn$qn-$i\" ";
				if (isset($labits[$i]) && ($labits[$i]!='') && ($labits[$i] == $i)) { $out .= "CHECKED";}
				$out .= " /></div> \n";
			} else if ($displayformat == "inline") {
				$out .= "<input type=checkbox name=\"qn$qn"."[$i]\" value=$i id=\"qn$qn-$i\" ";
				if (isset($labits[$i]) && ($labits[$i]!='') && ($labits[$i] == $i)) { $out .= "CHECKED";}
				$out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label> ";
			} else if ($displayformat == 'column') {
				if ($i%$itempercol==0) {
					if ($i>0) {
						$out .= '</ul></div>';
					}
					$out .= '<div class="match"><ul class=nomark>';
				}
				$out .= "<li><input type=checkbox name=\"qn$qn"."[$i]\" value=$i id=\"qn$qn-$i\" ";
				if (isset($labits[$i]) && ($labits[$i]!='') && ($labits[$i] == $i)) { $out .= "CHECKED";}
				$out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label></li> \n";
			} else {
				$out .= "<li><input class=\"unind\" type=checkbox name=\"qn$qn"."[$i]\" value=$i id=\"qn$qn-$i\" ";
				if (isset($labits[$i]) && ($labits[$i]!='') && ($labits[$i] == $i)) { $out .= "CHECKED";}
				$out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label></li> \n";
			}
		}
		if ($displayformat == "horiz") {
			//$out .= "<div class=spacer>&nbsp;</div>\n";
		} else if ($displayformat == "inline") {

		} else if ($displayformat == 'column') {
			$out .= "</ul></div>";//<div class=spacer>&nbsp;</div>\n";
		} else {
			$out .= "</ul>\n";
		}
		$out .= getcolormark($colorbox);
		if ($displayformat == 'inline') {
			$out .= "</span>";
		} else {
			$out .= "</div>";
		}
		$tip = _('Select all correct answers');
		if (isset($answers)) {
			$akeys = array_map('trim',explode(',',$answers));
			foreach($akeys as $akey) {
				$sa .= '<br/>'.$questions[$akey];
			}
		}
	} else if ($anstype == "matching") {
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (isset($options['answers'])) {if (is_array($options['answers'][$qn])) {$answers = $options['answers'][$qn];} else {$answers = $options['answers'];}}
			else if (isset($options['answer'])) {if (is_array($options['answer'][$qn])) {$answers = $options['answer'][$qn];} else {$answers = $options['answer'];}}
		if (isset($options['questiontitle'])) {if (is_array($options['questiontitle'])) {$questiontitle = $options['questiontitle'][$qn];} else {$questiontitle = $options['questiontitle'];}}
		if (isset($options['answertitle'])) {if (is_array($options['answertitle'])) {$answertitle = $options['answertitle'][$qn];} else {$answertitle = $options['answertitle'];}}
		if (isset($options['matchlist'])) {if (is_array($options['matchlist'])) {$matchlist = $options['matchlist'][$qn];} else {$matchlist = $options['matchlist'];}}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}

		if ($multi>0) { $qn = $multi*1000+$qn;}

		//trim out unshuffled showans
		$la = explode('$!$',$la);
		$la = $la[0];

		if (!is_array($questions) || !is_array($answers)) {
			echo _('Eeek!  $questions or $answers is not defined or needs to be an array');
			return false;
		}
		if (isset($matchlist)) { $matchlist = array_map('trim',explode(',',$matchlist));}
		if ($noshuffle=="questions" || $noshuffle=='all') {
			$randqkeys = array_keys($questions);
		} else {
			$randqkeys = array_rand($questions,count($questions));
			shuffle($randqkeys);
		}
		if ($noshuffle=="answers" || $noshuffle=='all') {
			$randakeys = array_keys($answers);
		} else {
			$randakeys = array_rand($answers,count($answers));
			shuffle($randakeys);
		}

		if (isset($GLOBALS['capturechoices'])) {
			if (!isset($GLOBALS['choicesdata'])) {
				$GLOBALS['choicesdata'] = array();
			}
			$GLOBALS['choicesdata'][$qn] = array($anstype, $randakeys);
		}

		$ncol = 1;
		if (substr($displayformat,1)=='columnselect') {
			$ncol = $displayformat{0};
			$itempercol = ceil(count($randqkeys)/$ncol);
			$displayformat = 'select';
		}
		if (substr($displayformat,0,8)=="limwidth") {
			$divstyle = 'style="max-width:'.substr($displayformat,8).'px;"';
		} else {
			$divstyle = '';
		}
		if ($colorbox != '') {$out .= '<div class="'.$colorbox.'" id="qnwrap'.$qn.'" style="display:block">';}
		$out .= "<div class=\"match\" $divstyle>\n";
		$out .= "<p class=\"centered\">$questiontitle</p>\n";
		$out .= "<ul class=\"nomark\">\n";
		$las = explode("|",$la);
		$letters = array_slice(explode(',','a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z'),0,count($answers));

		for ($i=0;$i<count($randqkeys);$i++) {
			//$out .= "<li><input class=\"text\" type=\"text\"  size=3 name=\"qn$qn-$i\" value=\"{$las[$i]}\" /> {$questions[$randqkeys[$i]]}</li>\n";
			if ($ncol>1) {
				if ($i>0 && $i%$itempercol==0) {
					$out .= '</ul></div><div class="match"><ul class=nomark>';
				}
			}
			if (strpos($questions[$randqkeys[$i]],' ')===false || strlen($questions[$randqkeys[$i]])<12) {
				$out .= '<li class="nowrap">';
			} else {
				$out .= '<li>';
			}
			$out .= "<select name=\"qn$qn-$i\">";
			$out .= '<option value="-" ';
			if ($las[$i]=='-' || $las[$i]=='') {
				$out .= 'selected="1"';
			}
			$out .= '>-</option>';
			if ($displayformat=="select") {
				for ($j=0;$j<count($randakeys);$j++) {
					$out .= "<option value=\"".$letters[$j]."\" ";
					if ($las[$i]==$letters[$j]) {
						$out .= 'selected="1"';
					}
					$out .= ">".str_replace('`','',$answers[$randakeys[$j]])."</option>\n";
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
			$out .= "</select>&nbsp;{$questions[$randqkeys[$i]]}</li>\n";
		}
		$out .= "</ul>\n";
		$out .= "</div>";

		if (!isset($displayformat) || $displayformat!="select") {
			$out .= "<div class=\"match\" $divstyle>\n";
			$out .= "<p class=centered>$answertitle</p>\n";

			$out .= "<ol class=lalpha>\n";
			for ($i=0;$i<count($randakeys);$i++) {
				$out .= "<li>{$answers[$randakeys[$i]]}</li>\n";
			}
			$out .= "</ol>";
			$out .= "</div>";
		}
		$out .= "<input type=hidden name=\"qn$qn\" value=\"done\" /><div class=spacer>&nbsp;</div>";
		$out .= getcolormark($colorbox);
		if ($colorbox != '') {$out .= '</div>';}
		//$tip = "In each box provided, type the letter (a, b, c, etc.) of the matching answer in the right-hand column";
		if ($displayformat=="select") {
			$tip = _('In each pull-down, select the item that matches with the displayed item');
		} else {
			$tip = _('In each pull-down on the left, select the letter (a, b, c, etc.) of the matching answer in the right-hand column');
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

		$lap = explode('$f$',$la);
		if (isset($lap[1]) && (!isset($GLOBALS['noformatfeedback']) || $GLOBALS['noformatfeedback']==false)) {
			$rightanswrongformat = true;
			if ($colorbox=='ansred') {
				$colorbox = 'ansorg';
			}
		}
		$la = $lap[0];

		$la = explode('$#$',$la);
		$la = $la[0];

		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = array_map('trim',explode(',',$answerformat));

		if (isset($ansprompt) && !in_array('nosoln',$ansformats) && !in_array('nosolninf',$ansformats))  {
			$out .= "<label for=\"tc$qn\">$ansprompt</label>";
		}
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
		$out .= "$leftb<input class=\"text $colorbox\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\" autocomplete=\"off\" ";


		if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
			$tip = _('Enter your answer as a list of values separated by commas: Example: -4, 3, 2') . "<br/>";
			$eword = _('each value');
		} else if (in_array('set',$ansformats) || in_array('exactset',$ansformats)) {
			$tip = _('Enter your answer as a set of values separated with commas: Example: {-4, 3, 2}') . "<br/>";
			$eword = _('each value');
		} else {
			$tip = '';
			$eword = _('your answer');
		}
		list($longtip,$shorttip) = formathint($eword,$ansformats,'calculated',(in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats) || in_array('set',$ansformats) || in_array('exactset',$ansformats)), 1);
		$tip .= $longtip;

		if ($showtips==2) { //eqntips: work in progress
			if ($multi==0) {
				$qnref = "$qn-0";
			} else {
				$qnref = ($multi-1).'-'.($qn%1000);
			}
			if ($useeqnhelper) {
				$out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper);showehdd('tc$qn','$shorttip','$qnref');\" onblur=\"hideee();hideeedd();hideeh();\" onclick=\"reshrinkeh('tc$qn')\" ";
			} else {
				$out .= "onfocus=\"showehdd('tc$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" onclick=\"reshrinkeh('tc$qn')\" ";
			}
		} else if ($useeqnhelper) {
			$out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper)\" onblur=\"hideee();hideeedd();\" ";
		}
		if (!isset($hidepreview)) {
			$out .= 'onKeyUp="updateLivePreview(this)" ';
		}
		$out .= "/>$rightb";
		$out .= getcolormark($colorbox);

		if (!isset($GLOBALS['nocolormark']) && isset($rightanswrongformat) && (!isset($GLOBALS['noformatfeedback']) || $GLOBALS['noformatfeedback']==false)) {
			if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
				$out .= ' '.formhoverover('<span style="color:#f60;font-size:80%">(Format)</span>','One or more of your answers is equivalent to the correct answer, but is not simplified or is in the wrong format');
			} else {
				$out .= ' '.formhoverover('<span style="color:#f60;font-size:80%">(Format)</span>','Your answer is equivalent to the correct answer, but is not simplified or is in the wrong format');
			}
		}

		$out .= "<input type=\"hidden\" id=\"qn$qn\" name=\"qn$qn\" />\n";
		if (!isset($hidepreview)) {
			$preview .= "<input type=button class=btn id=\"pbtn$qn\" value=\"" . _('Preview') . "\" onclick=\"calculate('tc$qn','p$qn','$answerformat')\" /> &nbsp;\n";
		}
		$preview .= "$leftb<span id=p$qn></span>$rightb ";

		$out .= "<script type=\"text/javascript\">calctoproc[$qn] = 1; calcformat[$qn] = '$answerformat';</script>\n";

		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
		}
		if (isset($answer)) {
			if (!is_numeric($answer)) {
				$sa = '`'.$answer.'`';
			} else if (in_array('mixednumber',$ansformats) || in_array("sloppymixednumber",$ansformats) || in_array("mixednumberorimproper",$ansformats)) {
				$sa = '`'.decimaltofraction($answer,"mixednumber").'`';
			} else if (in_array("fraction",$ansformats) || in_array("reducedfraction",$ansformats)) {
				$sa = '`'.decimaltofraction($answer).'`';
			} else if (in_array("scinot",$ansformats)) {
				$sa = '`'.makescinot($answer,-1,'*').'`';
			} else {
				$sa = $answer;
			}
		}
	} else if ($anstype == "matrix") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answersize'])) {if (is_array($options['answersize'])) {$answersize = $options['answersize'][$qn];} else {$answersize = $options['answersize'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}



		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (isset($ansprompt)) {$out .= $ansprompt;}
		if (isset($answersize)) {
			if (!isset($sz)) { $sz = 3;}
			if ($colorbox=='') {
				$out .= '<table id="qnwrap'.$qn.'">';
			} else {
				$out .= '<table class="'.$colorbox.'" id="qnwrap'.$qn.'">';
			}
			$out .= '<tr><td class="matrixleft">&nbsp;</td><td>';
			$answersize = explode(",",$answersize);
			$out .= "<table>";
			$count = 0;
			$las = explode("|",$la);
			for ($row=0; $row<$answersize[0]; $row++) {
				$out .= "<tr>";
				for ($col=0; $col<$answersize[1]; $col++) {
					$out .= "<td><input class=\"text\" type=\"text\" size=\"$sz\" name=\"qn$qn-$count\" value=\"{$las[$count]}\"  autocomplete=\"off\" /></td>\n";
					$count++;
				}
				$out .= "</tr>";
			}
			$out .= "</table>\n";
			$out .= getcolormark($colorbox);
			$out .= '</td><td class="matrixright">&nbsp;</td></tr></table>';
			$tip = _('Enter each element of the matrix as  number (like 5, -3, 2.2)');
		} else {
			if (!isset($sz)) { $sz = 20;}
			$out .= "<input class=\"text $colorbox\" type=\"text\" size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\" autocomplete=\"off\" />\n";
			$out .= getcolormark($colorbox);
			$out .= "<input type=button class=btn value=\"" . _('Preview') . "\" onclick=\"AMmathpreview('qn$qn','p$qn')\" /> &nbsp;\n";
			$out .= "<span id=p$qn></span> ";
			$tip = _('Enter your answer as a matrix filled with numbers, like ((2,3,4),(3,4,5))');
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
		$ansformats = array_map('trim',explode(',',$answerformat));

		$la = explode('$#$',$la);
		$la = $la[0];

		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (isset($ansprompt)) {$out .= $ansprompt;}
		if (isset($answersize)) {
			if (!isset($sz)) { $sz = 3;}
			$answersize = explode(",",$answersize);
			if ($colorbox=='') {
				$out .= '<table id="qnwrap'.$qn.'">';
			} else {
				$out .= '<table class="'.$colorbox.'" id="qnwrap'.$qn.'">';
			}
			$out .= '<tr><td class="matrixleft">&nbsp;</td><td>';
			$out .= "<table>";
			$count = 0;
			$las = explode("|",$la);
			for ($row=0; $row<$answersize[0]; $row++) {
				$out .= "<tr>";
				for ($col=0; $col<$answersize[1]; $col++) {
					$out .= "<td><input class=\"text\" type=\"text\" size=\"$sz\" name=\"qn$qn-$count\" id=\"qn$qn-$count\" value=\"{$las[$count]}\" autocomplete=\"off\" /></td>\n";
					$count++;
				}
				$out .= "</tr>";
			}
			$out .= "</table>\n";
			$out .= '</td><td class="matrixright">&nbsp;</td></tr></table>';
			$out .= getcolormark($colorbox);
			if (!isset($hidepreview)) {$preview .= "<input type=button id=\"pbtn$qn\" class=btn value=\"" . _('Preview') . "\" onclick=\"matrixcalc('qn$qn','p$qn',{$answersize[0]},{$answersize[1]})\" /> &nbsp;\n";}
			$preview .= "<span id=p$qn></span>\n";
			$out .= "<script type=\"text/javascript\">matcalctoproc[$qn] = 1; matsize[$qn]='{$answersize[0]},{$answersize[1]}';</script>\n";
			$tip .= formathint(_('each element of the matrix'),$ansformats,'calcmatrix');
			//$tip = "Enter each element of the matrix as  number (like 5, -3, 2.2) or as a calculation (like 5/3, 2^3, 5+4)";
		} else {
			if (!isset($sz)) { $sz = 20;}
			$out .= "<input class=\"text $colorbox\" type=\"text\" size=\"$sz\" name=\"tc$qn\" id=\"tc$qn\" value=\"$la\" autocomplete=\"off\" />\n";
			$out .= getcolormark($colorbox);
			if (!isset($hidepreview)) {
				$preview .= "<input type=button value=\"" . _('Preview') . "\" onclick=\"matrixcalc('tc$qn','p$qn')\" /> &nbsp;\n";
			}
			$out .= "<span id=p$qn></span> \n";
			$out .= "<script type=\"text/javascript\">matcalctoproc[$qn] = 1;</script>\n";
			$tip = _('Enter your answer as a matrix, like ((2,3,4),(1,4,5))');
			$tip .= '<br/>'.formathint(_('each element of the matrix'),$ansformats,'calcmatrix');
		}
		$out .= "<input type=\"hidden\" id=\"qn$qn\" name=\"qn$qn\" />";

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

		$lap = explode('$f$',$la);
		if (isset($lap[1]) && (!isset($GLOBALS['noformatfeedback']) || $GLOBALS['noformatfeedback']==false)) {
			$rightanswrongformat = true;
			if ($colorbox=='ansred') {
				$colorbox = 'ansorg';
			}
		}
		$la = $lap[0];

		if (isset($ansprompt)) {$out .= "<label for=\"tc$qn\">$ansprompt</label>";}

		if ($answerformat=="equation") {
			$shorttip = _('Enter an algebraic equation');
		} else {
			$shorttip = _('Enter an algebraic expression');
		}
		$out .= "<input class=\"text $colorbox\" type=\"text\"  size=\"$sz\" name=\"tc$qn\" id=\"tc$qn\" value=\"$la\" autocomplete=\"off\" ";
		if ($showtips==2) { //eqntips: work in progress
			if ($multi==0) {
				$qnref = "$qn-0";
			} else {
				$qnref = ($multi-1).'-'.($qn%1000);
			}
			if ($useeqnhelper) {
				$out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper);showehdd('tc$qn','$shorttip','$qnref');\" onblur=\"hideee();hideeedd();hideeh();\" onclick=\"reshrinkeh('tc$qn')\" ";
			} else {
				$out .= "onfocus=\"showehdd('tc$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" onclick=\"reshrinkeh('tc$qn')\" ";
			}
		} else if ($useeqnhelper) {
			$out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper)\" onblur=\"hideee();hideeedd();\" ";
		}
		if (!isset($hidepreview)) {
			$out .= 'onKeyUp="updateLivePreview(this)" ';
		}
		$out .= "/>\n";
		$out .= "<input type=\"hidden\" id=\"qn$qn\" name=\"qn$qn\" />";
		$out .= "<input type=\"hidden\" id=\"qn$qn-vals\" name=\"qn$qn-vals\" />";
		$out .= getcolormark($colorbox);
		if (!isset($GLOBALS['nocolormark']) && isset($rightanswrongformat) && (!isset($GLOBALS['noformatfeedback']) || $GLOBALS['noformatfeedback']==false)) {
			$out .= ' '.formhoverover('<span style="color:#f60;font-size:80%">(Format)</span>','Your answer is equivalent to the correct answer, but is not simplified or is in the wrong format');
		}
		if (!isset($hidepreview)) {$preview .= "<input type=button class=btn id=\"pbtn$qn\" value=\"" . _('Preview') . "\" onclick=\"AMpreview('tc$qn','p$qn')\" /> &nbsp;\n";}
		$preview .= "<span id=p$qn></span>\n";

		if (!isset($variables)) { $variables = "x";}
		$variables = array_map('trim',explode(",",$variables));
		$ovar = array();
		$ofunc = array();
		for ($i = 0; $i < count($variables); $i++) {
			$variables[$i] = trim($variables[$i]);
			if (strpos($variables[$i],'(')===false) {
				$ovar[] = $variables[$i];
			} else {
				$ofunc[] = substr($variables[$i],0,strpos($variables[$i],'('));
				$variables[$i] = substr($variables[$i],0,strpos($variables[$i],'('));
			}
		}
		if (($v = array_search('E', $variables))!==false) {
			$variables[$v] = 'varE';
		}
		if (count($ovar)==0) {
			$ovar[] = "x";
		}
		$vlist = implode("|",$variables);
		$flist = implode('|',$ofunc);
		$out .= "<script type=\"text/javascript\">functoproc[$qn] = 1; vlist[$qn]=\"$vlist\"; flist[$qn]=\"$flist\";</script>\n";
		if (isset($domain)) {$fromto = array_map('trim',explode(",",$domain));} else {$fromto[0]=-10; $fromto[1]=10;}

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
			$tip = _('Enter your answer as an equation.  Example: y=3x^2+1, 2+x+y=3') . "\n<br/>" . _('Be sure your variables match those in the question');
		} else {
			$tip = _('Enter your answer as an expression.  Example: 3x^2+1, x/5, (a+b)/c') . "\n<br/>" . _('Be sure your variables match those in the question');
		}
		if (isset($answer)) {
			$sa = makeprettydisp($answer);
			$greekletters = array('alpha','beta','chi','delta','epsilon','gamma','phi','psi','sigma','rho','theta','lambda','mu','nu','omega');

			for ($i = 0; $i < count($variables); $i++) {
				if (strlen($variables[$i])>1 && $variables[$i]!='varE') {
					$isgreek = false;
					$varlower = strtolower($variables[$i]);
					for ($j = 0; $j< count($greekletters);$j++) {
						if ($varlower==$greekletters[$j]) {
							$isgreek = true;
							break;
						}
					}
					if (!$isgreek && preg_match('/^(\w+)_(\w+)$/',$variables[$i],$matches)) {
						if (strlen($matches[1])>1) {
							$matches[1] = '"'.$matches[1].'"';
						}
						if (strlen($matches[2])>1) {
							$matches[2] = '"'.$matches[2].'"';
						}
						$sa = str_replace($matches[0], $matches[1].'_'.$matches[2], $sa);
					} else if (!$isgreek && $variables[$i]!='varE') {
						$sa = str_replace($variables[$i], '"'.$variables[$i].'"', $sa);
					}
				}
			}
		}
	} else if ($anstype == "ntuple") {
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = array_map('trim',explode(',',$answerformat));

		if (!isset($sz)) { $sz = 20;}
		if ($multi>0) { $qn = $multi*1000+$qn;}

		if ($displayformat == 'point') {
			$tip = _('Enter your answer as a point.  Example: (2,5.5)') . "<br/>";
			$shorttip = _('Enter a point');
		} else if ($displayformat == 'pointlist') {
			$tip = _('Enter your answer a list of points separated with commas.  Example: (1,2), (3.5,5)') . "<br/>";
			$shorttip = _('Enter a list of points');
		} else if ($displayformat == 'vector') {
			$tip = _('Enter your answer as a vector.  Example: <2,5.5>') . "<br/>";
			$shorttip = _('Enter a vector');
		} else if ($displayformat == 'vectorlist') {
			$tip = _('Enter your answer a list of vectors separated with commas.  Example: <1,2>, <3.5,5>') . "<br/>";
			$shorttip = _('Enter a list of vectors');
		} else if ($displayformat == 'set') {
			$tip = _('Enter your answer as a set of numbers.  Example: {1,2,3}') . "<br/>";
			$shorttip = _('Enter a set');
		} else if ($displayformat == 'list') {
			$tip = _('Enter your answer as a list of n-tuples of numbers separated with commas: Example: (1,2),(3.5,4)') . "<br/>";
			$shorttip = _('Enter a list of n-tuples');
		} else {
			$tip = _('Enter your answer as an n-tuple of numbers.  Example: (2,5.5)') . "<br/>";
			$shorttip = _('Enter an n-tuple');
		}
		$tip .= _('Enter DNE for Does Not Exist');

		$out .= "<input class=\"text $colorbox\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\" autocomplete=\"off\" ";
		if ($showtips==2) { //eqntips: work in progress
			if ($multi==0) {
				$qnref = "$qn-0";
			} else {
				$qnref = ($multi-1).'-'.($qn%1000);
			}
			$out .= "onfocus=\"showehdd('qn$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" ";
		}
		$out .= '/>';
		$out .= getcolormark($colorbox);

		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
		}
		if (isset($answer)) {
			$sa = $answer;
		}
	} else if ($anstype == "calcntuple") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$qn];} else {$hidepreview = $options['hidepreview'];}}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = array_map('trim',explode(',',$answerformat));

		$la = explode('$#$',$la);
		$la = $la[0];

		if (!isset($sz)) { $sz = 20;}
		if ($multi>0) { $qn = $multi*1000+$qn;}

		if ($displayformat == 'point') {
			$tip = _('Enter your answer as a point.  Example: (2,5.5)') . "<br/>";
			$shorttip = _('Enter a point');
		} else if ($displayformat == 'pointlist') {
			$tip = _('Enter your answer a list of points separated with commas.  Example: (1,2), (3.5,5)') . "<br/>";
			$shorttip = _('Enter a list of points');
		} else if ($displayformat == 'vector') {
			$tip = _('Enter your answer as a vector.  Example: <2,5.5>') . "<br/>";
			$shorttip = _('Enter a vector');
		} else if ($displayformat == 'vectorlist') {
			$tip = _('Enter your answer a list of vectors separated with commas.  Example: <1,2>, <3.5,5>') . "<br/>";
			$shorttip = _('Enter a list of vectors');
		} else if ($displayformat == 'set') {
			$tip = _('Enter your answer as a set of numbers.  Example: {1,2,3}') . "<br/>";
			$shorttip = _('Enter a set');
		} else if ($displayformat == 'list') {
			$tip = _('Enter your answer as a list of n-tuples of numbers separated with commas: Example: (1,2),(3.5,4)') . "<br/>";
			$shorttip = _('Enter a list of n-tuples');
		} else {
			$tip = _('Enter your answer as an n-tuple of numbers.  Example: (2,5.5)') . "<br/>";
			$shorttip = _('Enter an n-tuple');
		}
		$tip .= formathint('each value',$ansformats,'calcntuple');

		$out .= "<input class=\"text $colorbox\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\" autocomplete=\"off\" ";
		if ($showtips==2) { //eqntips: work in progress
			if ($multi==0) {
				$qnref = "$qn-0";
			} else {
				$qnref = ($multi-1).'-'.($qn%1000);
			}
			if ($useeqnhelper) {
				$out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper);showehdd('tc$qn','$shorttip','$qnref');\" onblur=\"hideee();hideeedd();hideeh();\" onclick=\"reshrinkeh('tc$qn')\" ";
			} else {
				$out .= "onfocus=\"showehdd('tc$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" onclick=\"reshrinkeh('tc$qn')\" ";
			}
		} else if ($useeqnhelper) {
			$out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper)\" onblur=\"hideee();hideeedd();\" ";
		}
		if (!isset($hidepreview)) {
			$out .= 'onKeyUp="updateLivePreview(this)" ';
		}
		$out .= "/>";
		$out .= "<input type=\"hidden\" id=\"qn$qn\" name=\"qn$qn\" />";
		$out .= getcolormark($colorbox);
		if (!isset($hidepreview)) {
			$preview .= "<input type=button class=btn id=\"pbtn$qn\" value=\"" . _('Preview') . "\" onclick=\"ntuplecalc('tc$qn','p$qn','$answerformat')\" /> &nbsp;\n";
		}
		$preview .= "<span id=p$qn></span> ";
		$out .= "<script type=\"text/javascript\">ntupletoproc[$qn] = 1; calcformat[$qn] = '$answerformat';</script>\n";
		//$tip .= "Enter DNE for Does Not Exist";

		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
		}
		if (isset($answer)) {
			$sa = makeprettydisp($answer);
		}
	} else if ($anstype == "complex") {
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = array_map('trim',explode(',',$answerformat));
		if (!isset($sz)) { $sz = 20;}
		if ($multi>0) { $qn = $multi*1000+$qn;}


		if (in_array('list',$ansformats)) {
			$tip = _('Enter your answer as a list of complex numbers in a+bi form separated with commas.  Example: 2+5.5i,-3-4i') . "<br/>";
			$shorttip = _('Enter a list of complex numbers');
		} else {
			$tip = _('Enter your answer as a complex number in a+bi form.  Example: 2+5.5i') . "<br/>";
			$shorttip = _('Enter a complex number');
		}

		$tip .= _('Enter DNE for Does Not Exist');

		$out .= "<input class=\"text $colorbox\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\" autocomplete=\"off\"  ";
		if ($showtips==2) { //eqntips: work in progress
			if ($multi==0) {
				$qnref = "$qn-0";
			} else {
				$qnref = ($multi-1).'-'.($qn%1000);
			}
			$out .= "onfocus=\"showehdd('qn$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" ";
		}
		$out .= '/>';
		$out .= getcolormark($colorbox);
		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
		}
		if (isset($answer)) {
			$sa = makepretty($answer);
		}
	} else if ($anstype == "calccomplex") {
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$qn];} else {$hidepreview = $options['hidepreview'];}}
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = array_map('trim',explode(',',$answerformat));

		if (!isset($sz)) { $sz = 20;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = array_map('trim',explode(',',$answerformat));

		if (in_array('list',$ansformats)) {
			$tip = _('Enter your answer as a list of complex numbers in a+bi form separated with commas.  Example: 2+5i,-3-4i') . "<br/>";
			$shorttip = _('Enter a list of complex numbers');
		} else {
			$tip = _('Enter your answer as a complex number in a+bi form.  Example: 2+5i') . "<br/>";
			$shorttip = _('Enter a complex number');
		}
		$tip .= formathint('each value',$ansformats,'calccomplex');

		$out .= "<input class=\"text $colorbox\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\" autocomplete=\"off\"  ";
		if ($showtips==2) { //eqntips: work in progress
			if ($multi==0) {
				$qnref = "$qn-0";
			} else {
				$qnref = ($multi-1).'-'.($qn%1000);
			}
			if ($useeqnhelper) {
				$out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper);showehdd('tc$qn','$shorttip','$qnref');\" onblur=\"hideee();hideeedd();hideeh();\" onclick=\"reshrinkeh('tc$qn')\" ";
			} else {
				$out .= "onfocus=\"showehdd('tc$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" onclick=\"reshrinkeh('tc$qn')\" ";
			}
		} else if ($useeqnhelper) {
			$out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper)\" onblur=\"hideee();hideeedd();\" ";
		}
		if (!isset($hidepreview)) {
			$out .= 'onKeyUp="updateLivePreview(this)" ';
		}
		$out .= "/>";
		$out .= "<input type=\"hidden\" id=\"qn$qn\" name=\"qn$qn\" />";
		$out .= getcolormark($colorbox);
		if (!isset($hidepreview)) {
			$preview .= "<input type=button class=btn id=\"pbtn$qn\" value=\"" . _('Preview') . "\" onclick=\"complexcalc('tc$qn','p$qn','$answerformat')\" /> &nbsp;\n";
		}
		$preview .= "<span id=p$qn></span> ";
		$out .= "<script type=\"text/javascript\">complextoproc[$qn] = 1;</script>\n";

		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
		}

		//$tip .= "Enter DNE for Does Not Exist";
		if (isset($answer)) {
			$sa = makeprettydisp( $answer);
		}
	} else if ($anstype == "string") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['strflags'])) {if (is_array($options['strflags'])) {$strflags = $options['strflags'][$qn];} else {$strflags = $options['strflags'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$qn];} else {$displayformat = $options['displayformat'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (!isset($answerformat)) { $answerformat = '';}

		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($sz)) { $sz = 20;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}

		if ($answerformat=='list') {
			$tip = _('Enter your answer as a list of text separated by commas.  Example:  dog, cat, rabbit.') . "<br/>";
			$shorttip = _('Enter a list of text');
		} else {
			$tip .= _('Enter your answer as letters.  Examples: A B C, linear, a cat');
			$shorttip = _('Enter text');
		}
		if ($displayformat=='select') {
			$out .= "<select name=\"qn$qn\" id=\"qn$qn\" style=\"margin-right:20px\" class=\"$colorbox\"><option value=\"\"> </option>";
			foreach ($questions as $i=>$v) {
				$out .= '<option value="'.htmlentities($v).'"';
				//This is a hack.  Need to figure a better way to deal with & in answers
				if (str_replace('&','',$v)==$la) {
					$out .= ' selected="selected"';
				}
				$out .= '>'.htmlentities($v).'</option>';
			}
			$out .= '</select>';
			$out .= getcolormark($colorbox);
		} else if ($answerformat=='MQexperimental') {
			$out .= "<input type=\"text\" style=\"position:absolute;visibility:hidden\" name=\"qn$qn\" id=\"qn$qn\" value=\"$la\" />";
			$out .= "<span class=\"$colorbox mathquill-embedded-latex MQE$qn\">";
			if ($displayformat != '') {
				$laprts = explode(';',$la);
				$laptcnt = 0;
				while (($p=strpos($displayformat, '[AB]'))!==false) {
					if (isset($laprts[$laptcnt])) {
						$lav = $laprts[$laptcnt];
						$laptcnt++;
					} else {
						$lav = '';
					}
					$displayformat = substr($displayformat,0,$p).'\editable{'.$lav.'}'.substr($displayformat,$p+4);
					//$out .= str_replace('[AB]', '\editable{'.$lav.'}', $displayformat, 1);
				}
				$out .= $displayformat;
			} else {
				$out .= '\editable{'.$la.'}';
			}
			$out .= "</span>";
			$out .= getcolormark($colorbox);
			$out .= '<script type="text/javascript">$(function() {
				 $(".MQE'.$qn.'").on("keypress keyup", function() {
				     var latexvals = [];
				     var latex = $(".MQE'.$qn.'").find(".mathquill-editable").each(function(i,el) {
				            latexvals.push($(el).mathquill("latex"));
				         });
				     $("#qn'.$qn.'").val(MQtoAM(latexvals.join(";")));
				   });
				   setTimeout(function(){$(".MQE'.$qn.'").find("textarea").blur();}, 25);
				});</script>';
		} else {
			$out .= "<input type=\"text\"  size=\"$sz\" name=\"qn$qn\" id=\"qn$qn\" value=\"$la\" autocomplete=\"off\"  ";

			if ($showtips==2) { //eqntips: work in progress
				if ($multi==0) {
					$qnref = "$qn-0";
				} else {
					$qnref = ($multi-1).'-'.($qn%1000);
				}
				if ($useeqnhelper && $displayformat == 'usepreview') {
					$out .= "onfocus=\"showeedd('qn$qn',$useeqnhelper);showehdd('qn$qn','$shorttip','$qnref');\" onblur=\"hideee();hideeedd();hideeh();\" onclick=\"reshrinkeh('qn$qn')\" ";
				} else {
					$out .= "onfocus=\"showehdd('qn$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" onclick=\"reshrinkeh('qn$qn')\" ";
				}
			} else if ($useeqnhelper && $displayformat == 'usepreview') {
				$out .= "onfocus=\"showeedd('qn$qn',$useeqnhelper)\" onblur=\"hideee();hideeedd();\" ";
			}
			if ($displayformat == 'usepreview') {
				$out .= 'onKeyUp="updateLivePreview(this)" ';
			}
			$addlclass = '';
			if ($displayformat=='debit') { $out .= 'onkeyup="editdebit(this)" style="text-align: right;" ';}
			else if ($displayformat=='credit') { $out .= 'onkeyup="editcredit(this)" style="text-align: right;" '; $addlclass=' creditbox';}
			else if ($displayformat=='alignright') { $out .= 'style="text-align: right;" ';}
			$out .= "class=\"text $colorbox$addlclass\"";
			$out .= '/>';
			$out .= getcolormark($colorbox);

			if ($displayformat == 'usepreview') {
				$preview .= "<input type=button class=btn id=\"pbtn$qn\" value=\"" . _('Preview') . "\" onclick=\"stringqpreview('qn$qn','p$qn','$answerformat')\" /> &nbsp;\n";
				$preview .= "<span id=p$qn></span> ";
			} else if ($displayformat == 'typeahead') {
				if (!is_array($questions)) {
					echo _('Eeek!  $questions is not defined or needs to be an array');
				} else {
					foreach ($questions as $i=>$v) {
						$questions[$i] = htmlentities(trim($v));
					}

					$out .= '<script type="text/javascript">';
					$autosugglist = '["'.implode('","',$questions).'"]';
					if (!isset($GLOBALS['autosuggestlists'])) {
						$GLOBALS['autosuggestlists'] = array();
					}
					if (($k = array_search($autosugglist, $GLOBALS['autosuggestlists']))!==false) {
						$asvar = 'autosuggestlist'.$k;
					} else {
						$GLOBALS['autosuggestlists'][] = $autosugglist;
						$ascnt = count($GLOBALS['autosuggestlists'])-1;
						$out .= 'var autosuggestlist'.$ascnt.' = '.$autosugglist.';';
						$asvar = 'autosuggestlist'.$ascnt;
					}
					$out .= 'initstack.push(function(){ autosugg'.$qn.' = new AutoSuggest(document.getElementById("qn'.$qn.'"),'.$asvar.');});</script>';
				}
			}
		}
		if (strpos($strflags,'regex')!==false) {
			$sa .= _('The answer must match a specified pattern');
		} else {
			$sa .= $answer;
		}
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
		if ($GLOBALS['useeditor']=='review' || ($GLOBALS['useeditor']=='reviewifneeded' && trim($la)=='')) {
			$la = str_replace('&quot;','"',$la);
			$la = preg_replace('/%(\w+;)/',"&$1",$la);
			//$la = str_replace('nbsp;','&nbsp;',$la);
			if ($displayformat!='editor') {
				$la = preg_replace('/\n/','<br/>',$la);
			}
			if ($colorbox=='') {
				$out .= '<div class="intro" id="qnwrap'.$qn.'">';
			} else {
				$out .= '<div class="intro '.$colorbox.'" id="qnwrap'.$qn.'">';
			}
			if (isset($GLOBALS['questionscoreref'])) {
				if ($multi==0) {
					$el = $GLOBALS['questionscoreref'][0];
					$sc = $GLOBALS['questionscoreref'][1];
				} else {
					$el = $GLOBALS['questionscoreref'][0].'-'.($qn%1000);
					$sc = $GLOBALS['questionscoreref'][1][$qn%1000];
				}
				$out .= '<span style="float:right;">';
				$out .= '<img class="scoreicon" src="'.$imasroot.'/img/q_fullbox.gif" ';
				$out .= "onclick=\"quicksetscore('$el',$sc)\" />";
				$out .= '<img class="scoreicon" src="'.$imasroot.'/img/q_halfbox.gif" ';
				$out .= "onclick=\"quicksetscore('$el',.5*$sc)\" />";
				$out .= '<img class="scoreicon" src="'.$imasroot.'/img/q_emptybox.gif" ';
				$out .= "onclick=\"quicksetscore('$el',0)\" /></span>";

				$la = preg_replace_callback('/<a[^>]*href="(.*)?"[^>]*>(.*?)<\/a>/', function ($m) {
					global $gradededessayexpandocnt;
					if (!isset($gradededessayexpandocnt)) {
						$gradededessayexpandocnt = 0;
					}
					if (strpos($m[0],'target=')===false) {
						$ret = '<a target="_blank" '.substr($m[0], 2);
					} else {
						//force links to open in a new window to prevent manual scores and feedback from being lost
						$ret = preg_replace('/target=".*?"/','target="_blank"',$m[0]);
					}
					$url = $m[1];
					$extension = substr($url,strrpos($url,'.')+1,3);
					if (in_array(strtolower($extension),array('jpg','gif','png','bmp','jpe'))) {
						$ret .= " <span class=\"clickable\" id=\"essaytog$gradededessayexpandocnt\" onclick=\"toggleinlinebtn('essayimg$gradededessayexpandocnt','essaytog$gradededessayexpandocnt');\">[+]</span>";
						$ret .= " <br/><img id=\"essayimg$gradededessayexpandocnt\" style=\"display:none;max-width:80%;\" src=\"$url\" />";
					} else if (in_array(strtolower($extension),array('doc','docx','pdf','xls','xlsx','ppt','pptx'))) {
						$ret .= " <span class=\"clickable\" id=\"essaytog$gradededessayexpandocnt\" onclick=\"toggleinlinebtn('essayfileprev$gradededessayexpandocnt','essaytog$gradededessayexpandocnt');\">[+]</span>";
						$ret .= " <br/><iframe id=\"essayfileprev$gradededessayexpandocnt\" style=\"display:none;\" src=\"https://docs.google.com/viewer?url=".urlencode($url)."&embedded=true\" width=\"80%\" height=\"600px\"></iframe>";
					}
					return $ret;
				   }, $la);
			}

			$out .= filter($la);
			$out .= getcolormark($colorbox);
			$out .= "</div>";
		} else {
			$la = stripslashes($la);
			$la = preg_replace('/%(\w+;)/',"&$1",$la);
			if ($displayformat=='editor' && $GLOBALS['useeditor']==1) {
				$la = str_replace('&quot;','"',$la);

				$la = htmlentities($la);
			}
			if ($rows<2) {
				$out .= "<input type=\"text\" class=\"text $colorbox\" size=\"$cols\" name=\"qn$qn\" id=\"qn$qn\" value=\"$la\" /> ";
				$out .= getcolormark($colorbox);
			} else {
				if ($colorbox!='') { $out .= '<div class="'.$colorbox.'">';}
				$out .= "<textarea rows=\"$rows\" name=\"qn$qn\" id=\"qn$qn\" ";
				if ($displayformat=='editor' && $GLOBALS['useeditor']==1) {
					$out .= "style=\"width:98%;\" class=\"mceEditor\" ";
				} else {
					$out .= "cols=\"$cols\" ";
				}
				$out .= ">$la</textarea>\n";
				$out .= getcolormark($colorbox);
				if ($colorbox!='') { $out .= '</div>';}
			}
			if ($displayformat=='editor' && $GLOBALS['useeditor']==1) {
				//$out .= "<script type=\"text/javascript\">editornames[editornames.length] = \"qn$qn\";</script>";
			}
		}
		$tip .= _('Enter your answer as text.  This question is not automatically graded.');
		$sa .= $answer;
	} else if ($anstype == 'interval') {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$qn];} else {$reqdecimals = $options['reqdecimals'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}

		if (!isset($sz)) { $sz = 20;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}
		if ($multi>0) { $qn = $multi*1000+$qn;}

		if ($answerformat=='normalcurve' && $GLOBALS['sessiondata']['graphdisp']!=0) {
			$top = _('Enter your answer by selecting the shade type, and by clicking and dragging the sliders on the normal curve');
			$shorttip = _('Adjust the sliders');
		} else {

			$tip = _('Enter your answer using interval notation.  Example: [2.1,5.6)') . " <br/>";
			$tip .= _('Use U for union to combine intervals.  Example: (-oo,2] U [4,oo)') . "<br/>";
			$tip .= _('Enter DNE for an empty set, oo for Infinity');
			if (isset($reqdecimals)) {
				$tip .= "<br/>" . sprintf(_('Your numbers should be accurate to %d decimal places.'), $reqdecimals);
			}
			$shorttip = _('Enter an interval using interval notation');
		}
		if ($answerformat=='normalcurve' && $GLOBALS['sessiondata']['graphdisp']!=0) {
			$out .=  '<div style="background:#fff;padding:10px;">';
			$out .=  '<p style="margin:0px";>Shade: <select id="shaderegions'.$qn.'" onchange="imathasDraw.chgnormtype(this.id.substring(12));"><option value="1L">' . _('Left of a value') . '</option><option value="1R">' . _('Right of a value') . '</option>';
			$out .=  '<option value="2B">' . _('Between two values') . '</option><option value="2O">' . _('2 regions') . '</option></select>. ' . _('Click and drag and arrows to adjust the values.');

			$out .=  '<div style="position: relative; width: 500px; height:200px;padding:0px;">';
			//for future development of non-standard normal
			//for ($i=0;$i<9;$i++) {
			//	$out .= '<div style="position: absolute; left:'.(60*$i).'px; top:150px; height:20px; width:20px; background:#fff;z-index:2;text-align:center">'.($mu+($i-4)*$sig).'</div>';
			//}
			$out .=  '<div style="position: absolute; left:0; top:0; height:200px; width:0px; background:#00f;" id="normleft'.$qn.'">&nbsp;</div>';
			$out .=  '<div style="position: absolute; right:0; top:0; height:200px; width:0px; background:#00f;" id="normright'.$qn.'">&nbsp;</div>';
			$out .=  '<img style="position: absolute; left:0; top:0;z-index:1;width:100%;max-width:100%" src="'.$imasroot.'/img/normalcurve.gif"/>';
			$out .=  '<img style="position: absolute; top:142px;left:0px;cursor:pointer;z-index:3;" id="slid1'.$qn.'" src="'.$imasroot.'/img/uppointer.gif"/>';
			$out .=  '<img style="position: absolute; top:142px;left:0px;cursor:pointer;z-index:3;" id="slid2'.$qn.'" src="'.$imasroot.'/img/uppointer.gif"/>';
			$out .=  '<div style="position: absolute; top:170px;left:0px;z-index:3;" id="slid1txt'.$qn.'"></div>';
			$out .=  '<div style="position: absolute; top:170px;left:0px;z-index:3;" id="slid2txt'.$qn.'"></div>';
			$out .=  '</div></div>';
			$out .=  '<script type="text/javascript">imathasDraw.addnormslider('.$qn.');</script>';
		} else if ($answerformat=='normalcurve') {
			$out .= _('Enter an interval corresponding to the region to be shaded');
		}
		$out .= "<input class=\"text $colorbox\" type=\"text\"  size=\"$sz\" name=qn$qn id=qn$qn value=\"$la\" autocomplete=\"off\"  ";
		if ($answerformat=='normalcurve' && $GLOBALS['sessiondata']['graphdisp']!=0) {
			$out .= 'style="position:absolute;visibility:hidden;" ';
		}
		/*if ($showtips==2) { //eqntips: work in progress
			if ($multi==0) {
				$qnref = "$qn-0";
			} else {
				$qnref = ($multi-1).'-'.($qn%1000);
			}
			$out .= "onfocus=\"showehdd('qn$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" ";
		}*/
		if ($showtips==2) { //eqntips: work in progress
			if ($multi==0) {
				$qnref = "$qn-0";
			} else {
				$qnref = ($multi-1).'-'.($qn%1000);
			}
			if ($useeqnhelper && $useeqnhelper>2) {
				$out .= "onfocus=\"showeebasicdd('qn$qn',1);showehdd('qn$qn','$shorttip','$qnref');\" onblur=\"hideebasice();hideebasicedd();hideeh();\" onclick=\"reshrinkeh('qn$qn')\" ";
			} else {
				$out .= "onfocus=\"showehdd('qn$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" onclick=\"reshrinkeh('qn$qn')\" ";
			}
		} else if ($useeqnhelper) {
			$out .= "onfocus=\"showeebasicdd('qn$qn',1)\" onblur=\"hideebasice();hideebasicedd();\" ";
		}
		$out .= '/>';
		$out .= getcolormark($colorbox);
		if (isset($answer)) {
			if ($answerformat=='normalcurve' && $GLOBALS['sessiondata']['graphdisp']!=0) {
				$sa .=  '<div style="position: relative; width: 500px; height:200px;padding:0px;background:#fff;">';
				if (preg_match('/\(-oo,([\-\d\.]+)\)U\(([\-\d\.]+),oo\)/',$answer,$matches)) {
					$sa .=  '<div style="position: absolute; left:0; top:0; height:200px; width:'.(250+60*$matches[1]+1).'px; background:#00f;">&nbsp;</div>';
					$sa .=  '<div style="position: absolute; right:0; top:0; height:200px; width:'.(250-60*$matches[2]).'px; background:#00f;">&nbsp;</div>';
				} else if (preg_match('/\(-oo,([\-\d\.]+)\)/',$answer,$matches)) {
					$sa .=  '<div style="position: absolute; left:0; top:0; height:200px; width:'.(250+60*$matches[1]+1).'px; background:#00f;">&nbsp;</div>';
				} else if (preg_match('/\(([\-\d\.]+),oo\)/',$answer,$matches)) {
					$sa .=  '<div style="position: absolute; right:0; top:0; height:200px; width:'.(250-60*$matches[1]).'px; background:#00f;">&nbsp;</div>';
				} else if (preg_match('/\(([\-\d\.]+),([\-\d\.]+)\)/',$answer,$matches)) {
					$sa .=  '<div style="position: absolute; left:'.(250+60*$matches[1]).'px; top:0; height:200px; width:'.(60*($matches[2]-$matches[1])+1).'px; background:#00f;">&nbsp;</div>';
				}
				$sa .=  '<img style="position: absolute; left:0; top:0;z-index:1;width:100%;max-width:100%" src="'.$imasroot.'/img/normalcurve.gif"/>';
				$sa .=  '</div>';
			} else {
				$sa = $answer;
			}
		}
	} else if ($anstype == 'calcinterval') {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$qn];} else {$sz = $options['answerboxsize'];}}
		if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$qn];} else {$hidepreview = $options['hidepreview'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$qn];} else {$reqdecimals = $options['reqdecimals'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['variables'])) {if (is_array($options['variables'])) {$variables = $options['variables'][$qn];} else {$variables = $options['variables'];}}
		if (!isset($answerformat)) { $answerformat = '';}
		if (!isset($variables)) { $variables = 'x';}
		$ansformats = array_map('trim',explode(',',$answerformat));

		if (!isset($sz)) { $sz = 20;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}
		if ($multi>0) { $qn = $multi*1000+$qn;}

		if (in_array('inequality',$ansformats)) {
			$tip = sprintf(_('Enter your answer using inequality notation.  Example: 3 &lt;= %s &lt; 4'), $variables) . " <br/>";
			$tip .= sprintf(_('Use or to combine intervals.  Example: %s &lt; 2 or %s &gt;= 3'), $variables, $variables) . "<br/>";
			$tip .= _('Enter <i>all real numbers</i> for solutions of that type') . "<br/>";
			$shorttip = _('Enter an interval using inequalities');
		} else {
			$tip = _('Enter your answer using interval notation.  Example: [2.1,5.6)') . " <br/>";
			if (in_array('list',$ansformats)) {
				$tip .= _('Separate intervals by a comma.  Example: (-oo,2],[4,oo)') . "<br/>";
				$shorttip = _('Enter a list of intervals using interval notation');
			} else {
				$tip .= _('Use U for union to combine intervals.  Example: (-oo,2] U [4,oo)') . "<br/>";
				$shorttip = _('Enter an interval using interval notation');
			}

		}
		//$tip .= "Enter values as numbers (like 5, -3, 2.2) or as calculations (like 5/3, 2^3, 5+4)<br/>";
		//$tip .= "Enter DNE for an empty set, oo for Infinity";
		$tip .= formathint(_('each value'),$ansformats,'calcinterval');
		if (isset($reqdecimals)) {
			$tip .= "<br/>" . sprintf(_('Your numbers should be accurate to %d decimal places.'), $reqdecimals);
		}

		$out .= "<input class=\"text $colorbox\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"$la\" autocomplete=\"off\"  ";
		if ($showtips==2) { //eqntips: work in progress
			if ($multi==0) {
				$qnref = "$qn-0";
			} else {
				$qnref = ($multi-1).'-'.($qn%1000);
			}
			if ($useeqnhelper) {
				$out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper,". (in_array('inequality',$ansformats)?"'ineq'":"'int'") .");showehdd('tc$qn','$shorttip','$qnref');\" onblur=\"hideee();hideeedd();hideeh();\" onclick=\"reshrinkeh('tc$qn')\" ";
			} else {
				$out .= "onfocus=\"showehdd('tc$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" onclick=\"reshrinkeh('tc$qn')\" ";
			}
		} else if ($useeqnhelper) {
			$out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper,". (in_array('inequality',$ansformats)?"'ineq'":"'int'") .")\" onblur=\"hideee();hideeedd();\" ";
		}
		if (!isset($hidepreview)) {
			$out .= 'onKeyUp="updateLivePreview(this)" ';
		}
		$out .= '/>';
		$out .= "<input type=\"hidden\" id=\"qn$qn\" name=\"qn$qn\" />";
		$out .= getcolormark($colorbox);
		if (!isset($hidepreview)) {
			$preview .= "<input type=button class=btn id=\"pbtn$qn\" value=\"" . _('Preview') . "\" onclick=\"intcalculate('tc$qn','p$qn','$answerformat')\" /> &nbsp;\n";
		}
		$preview .= "<span id=p$qn></span> ";
		$out .= "<script type=\"text/javascript\">intcalctoproc[$qn] = 1 ; calcformat[$qn] = '$answerformat';</script>\n";

		if (isset($answer)) {
			if (in_array('inequality',$ansformats)) {
				$sa = '`'.intervaltoineq($answer,$variables).'`';
			} else {
				$sa = '`'.str_replace('U','uu',$answer).'`';
			}
		}

	} else if ($anstype == 'draw') {
		if ($multi>0) {
			if (isset($options['grid'])) {
				if (is_array($options['grid']) && isset($options['grid'][$qn])) {
					$grid = $options['grid'][$qn];
				} else if (!is_array($options['grid'])) {
					$grid = $options['grid'];
				}
			}
			if (isset($options['snaptogrid'])) {
				if (is_array($options['snaptogrid']) && isset($options['snaptogrid'][$qn])) {
					$snaptogrid = $options['snaptogrid'][$qn];
				} else if (!is_array($options['snaptogrid'])) {
					$snaptogrid = $options['snaptogrid'];
				}
			}
			if (isset($options['background'])) {
				if (is_array($options['background']) && isset($options['background'][$qn])) {
					$backg = $options['background'][$qn];
				} else if (!is_array($options['background'])) {
					$backg = $options['background'];
				}
			}
			if (isset($options['answers'][$qn])) {$answers = $options['answers'][$qn];}
				else if (isset($options['answer'][$qn])) {$answers = $options['answer'][$qn];}
		} else {
			if (isset($options['grid'])) { $grid = $options['grid'];}
			if (isset($options['snaptogrid'])) { $snaptogrid = $options['snaptogrid'];}
			if (isset($options['background'])) { $backg = $options['background'];}
			if (isset($options['answers'])) {$answers = $options['answers'];}
				else if (isset($options['answer'])) {$answers = $options['answer'];}

		}

		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (!is_array($answers)) {
			settype($answers,"array");
		}
		if (!isset($snaptogrid)) {
			$snaptogrid = 0;
		}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$imgborder = 5;

		if (!isset($answerformat)) {
			$answerformat = array('line','dot','opendot');
		} else if (!is_array($answerformat)) {
			$answerformat = array_map('trim',explode(',',$answerformat));
		}

		if ($answerformat[0]=='numberline') {
			$settings = array(-5,5,0,0,1,0,300,50,"","");
			$locky = 1;
			if (count($answerformat)==1) {
				$answerformat[] = "lineseg";
				$answerformat[] = "dot";
				$answerformat[] = "opendot";
			}
		} else {
			$settings = array(-5,5,-5,5,1,1,300,300,"","");
			$locky = 0;
		}
		if (isset($grid)) {
			if (!is_array($grid)) {
				$grid = array_map('trim',explode(',',$grid));
			} else if (strpos($grid[0],',')!==false) {//forgot to set as multipart?
				$grid = array();
			}
			for ($i=0; $i<count($grid); $i++) {
				if ($grid[$i]!='') {
					if (strpos($grid[$i],':')!==false) {
						$pts = explode(':',$grid[$i]);
						foreach ($pts as $k=>$v) {
							$pts[$k] = evalbasic($v);
						}
						$settings[$i] = implode(':',$pts);
					} else {
						$settings[$i] = evalbasic($grid[$i]);
					}
				}
			}
			$origxmin = $settings[0];
			if (strpos($settings[0],'0:')===0) {
				$settings[0] = substr($settings[0],2);
			}
			$origymin = $settings[2];
			if (strpos($settings[2],'0:')===0) {
				$settings[2] = substr($settings[2],2);
			}
			if (strpos($grid[4],'pi')!==false) {
				$settings[4] = 2*($settings[1] - $settings[0]).':'.$settings[4];
			}
		} else {
			$origxmin = $settings[0];
			$origymin = $settings[2];
		}
		if (!isset($backg)) { $backg = '';}

		if ($answerformat[0]=='numberline') {
			$settings[2] = 0;
			$origymin = 0;
			$settings[3] = 0;
			if (strpos($settings[4],':')!==false) {
				$settings[4] = explode(':',$settings[4]);
				if ($settings[4][0]{0}=='h') {
					$sclinglbl = substr($settings[4][0],1).':0:1';
				} else {
					$sclinglbl = $settings[4][0];
				}
				$sclinggrid = $settings[4][1];
			} else {
				$sclinglbl = $settings[4];
				if ($sclinglbl>1 && $sclinglbl<6 && ($settings[1]-$settings[0])<10*$sclinglbl) {
					$sclinggrid = 1;
				} else {
					$sclinggrid = 0;
				}

			}
		} else {
			if (strpos($settings[4],':')!==false) {
				$settings[4] = explode(':',$settings[4]);
				$xlbl = $settings[4][0];
				$xgrid = $settings[4][1];
			} else {
				$xlbl = $settings[4];
				$xgrid = $settings[4];
			}
			if (strpos($settings[5],':')!==false) {
				$settings[5] = explode(':',$settings[5]);
				$ylbl = $settings[5][0];
				$ygrid = $settings[5][1];
			} else {
				$ylbl = $settings[5];
				$ygrid = $settings[5];
			}
			$sclinglbl = "$xlbl:$ylbl";
			$sclinggrid = "$xgrid:$ygrid";
		}
		if ($snaptogrid>0) {
			list($newwidth,$newheight) = getsnapwidthheight($settings[0],$settings[1],$settings[2],$settings[3],$settings[6],$settings[7],$snaptogrid);
			if (($newwidth - $settings[6])/$settings[6]<.1) {
				$settings[6] = $newwidth;
			}
			if (($newheight- $settings[7])/$settings[7]<.1) {
				$settings[7] = $newheight;
			}
		}
		if (!is_array($backg) && substr($backg,0,5)=="draw:") {
			$plot = showplot("",$origxmin,$settings[1],$origymin,$settings[3],$sclinglbl,$sclinggrid,$settings[6],$settings[7]);
			$insat = strpos($plot,');',strpos($plot,'axes'))+2;
			$plot = substr($plot,0,$insat).str_replace("'",'"',substr($backg,5)).substr($plot,$insat);
		} else if (!is_array($backg) && $backg=='none') {
			$plot = showasciisvg("initPicture(0,10,0,10);",$settings[6],$settings[7]);
		} else {
			$plot = showplot($backg,$origxmin,$settings[1],$origymin,$settings[3],$sclinglbl,$sclinggrid,$settings[6],$settings[7]);
		}
		if (is_array($settings[4]) && count($settings[4]>2)) {
			$plot = addlabel($plot,$settings[1],0,$settings[4][2],"black","aboveleft");
		}
		if (is_array($settings[5]) && count($settings[5]>2)) {
			$plot = addlabel($plot,0,$settings[3],$settings[5][2],"black","belowright");
		}
		if (isset($grid) && strpos($grid[4],'pi')!==false) {
			$plot = addfractionaxislabels($plot,$grid[4]);
		}


		if ($settings[8]!="") {
		}

		$bg = getgraphfilename($plot);

		$dotline = 0;
		if ($colorbox!='') { $out .= '<div class="'.$colorbox.'" id="qnwrap'.$qn.'">';}
		if (isset($GLOBALS['hidedrawcontrols'])) {
			$out .= $plot;
		} else {
			/*
			someday: overlay canvas over SVG.  Sizing not working in mobile and don't feel like figuring it out yet
			$out .= '<div class="drawcanvas" style="position:relative;background-color:#fff;width:'.$settings[6].'px;height:'.$settings[7].'px;">';
			$out .= '<div class="canvasbg" style="position:absolute;top:0;left:0;">'.$plot.'</div><div class="drawcanvasholder" style="position:absolute;top:0;left:0;z-index:2">';
			$out .= "<canvas id=\"canvas$qn\" width=\"{$settings[6]}\" height=\"{$settings[7]}\"></canvas>";
			$out .= '</div></div>';
			*/
			$out .= "<canvas class=\"drawcanvas\" id=\"canvas$qn\" width=\"{$settings[6]}\" height=\"{$settings[7]}\"></canvas>";

			$out .= "<div><span id=\"drawtools$qn\" class=\"drawtools\">";
			$out .= "<span onclick=\"imathasDraw.clearcanvas($qn)\">" . _('Clear All') . "</span> " . _('Draw:') . " ";
			if ($answerformat[0]=='inequality') {
				if (in_array('both',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpineq.gif\" onclick=\"imathasDraw.settool(this,$qn,10)\" class=\"sel\"/>";
					$out .= "<img src=\"$imasroot/img/tpineqdash.gif\" onclick=\"imathasDraw.settool(this,$qn,10.2)\"/>";
					$out .= "<img src=\"$imasroot/img/tpineqparab.gif\" onclick=\"imathasDraw.settool(this,$qn,10.3)\"/>";
					$out .= "<img src=\"$imasroot/img/tpineqparabdash.gif\" onclick=\"imathasDraw.settool(this,$qn,10.4)\"/>";
					$def = 10;
				}
				else if (in_array('parab',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpineqparab.gif\" onclick=\"imathasDraw.settool(this,$qn,10.3)\" class=\"sel\"/>";
					$out .= "<img src=\"$imasroot/img/tpineqparabdash.gif\" onclick=\"imathasDraw.settool(this,$qn,10.4)\"/>";
					$def = 10.3;
				}
				else {
					$out .= "<img src=\"$imasroot/img/tpineq.gif\" onclick=\"imathasDraw.settool(this,$qn,10)\" class=\"sel\"/>";
					$out .= "<img src=\"$imasroot/img/tpineqdash.gif\" onclick=\"imathasDraw.settool(this,$qn,10.2)\"/>";
					$def = 10;
				}
			} else if ($answerformat[0]=='twopoint') {
				if (count($answerformat)==1 || in_array('line',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpline.gif\" onclick=\"imathasDraw.settool(this,$qn,5)\" ";
					if (count($answerformat)==1 || $answerformat[1]=='line') { $out .= 'class="sel" '; $def = 5;}
					$out .= '/>';
				}
				//$out .= "<img src=\"$imasroot/img/tpline2.gif\" onclick=\"settool(this,$qn,5.2)\"/>";
				if (in_array('lineseg',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpline3.gif\" onclick=\"imathasDraw.settool(this,$qn,5.3)\" ";
					if (count($answerformat)>1 && $answerformat[1]=='lineseg') { $out .= 'class="sel" '; $def = 5.3;}
					$out .= "/>";
				}
				if (in_array('ray',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpline2.gif\" onclick=\"imathasDraw.settool(this,$qn,5.2)\" ";
					if (count($answerformat)>1 && $answerformat[1]=='ray') { $out .= 'class="sel" '; $def = 5.2;}
					$out .= "/>";
				}
				if (count($answerformat)==1 || in_array('parab',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpparab.png\" onclick=\"imathasDraw.settool(this,$qn,6)\" ";
					if (count($answerformat)>1 && $answerformat[1]=='parab') { $out .= 'class="sel" '; $def = 6;}
					$out .= '/>';
				}
				if (in_array('sqrt',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpsqrt.png\" onclick=\"imathasDraw.settool(this,$qn,6.5)\" ";
					if (count($answerformat)>1 && $answerformat[1]=='sqrt') { $out .= 'class="sel" '; $def = 6.5;}
					$out .= '/>';
				}
				if (count($answerformat)==1 || in_array('abs',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpabs.gif\" onclick=\"imathasDraw.settool(this,$qn,8)\" ";
					if (count($answerformat)>1 && $answerformat[1]=='abs') { $out .= 'class="sel" '; $def = 8;}
					$out .= '/>';
				}
				if (in_array('rational',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tprat.png\" onclick=\"imathasDraw.settool(this,$qn,8.2)\" ";
					if (count($answerformat)>1 && $answerformat[1]=='rational') { $out .= 'class="sel" '; $def = 8.2;}
					$out .= '/>';
				}
				if (in_array('exp',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpexp.png\" onclick=\"imathasDraw.settool(this,$qn,8.3)\" ";
					if (count($answerformat)>1 && $answerformat[1]=='exp') { $out .= 'class="sel" '; $def = 8.3;}
					$out .= '/>';
				}
				if ($settings[6]*($settings[3]-$settings[2]) == $settings[7]*($settings[1]-$settings[0])) {
					//only circles if equal spacing in x and y
					if (count($answerformat)==1 || in_array('circle',$answerformat)) {
						$out .= "<img src=\"$imasroot/img/tpcirc.png\" onclick=\"imathasDraw.settool(this,$qn,7)\" ";
						if (count($answerformat)>1 && $answerformat[1]=='circle') { $out .= 'class="sel" '; $def = 7;}
						$out .= '/>';
					}
				}
				if (count($answerformat)==1 || in_array('dot',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpdot.gif\" onclick=\"imathasDraw.settool(this,$qn,1)\" ";
					if (count($answerformat)>1 && $answerformat[1]=='dot') { $out .= 'class="sel" '; $def = 1;}
					$out .= '/>';
				}
				if (in_array('opendot',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpodot.gif\" onclick=\"imathasDraw.settool(this,$qn,2)\" ";
					if (count($answerformat)>1 && $answerformat[1]=='opendot') { $out .= 'class="sel" '; $def = 2;}
					$out .= '/>';
				}
				if (in_array('trig',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpcos.png\" onclick=\"imathasDraw.settool(this,$qn,9)\" ";
					if (count($answerformat)>1 && $answerformat[1]=='trig') { $out .= 'class="sel" '; $def = 9;}
					$out .= '/>';
					$out .= "<img src=\"$imasroot/img/tpsin.png\" onclick=\"imathasDraw.settool(this,$qn,9.1)\"/>";
				}
				if (in_array('vector',$answerformat)) {
					$out .= "<img src=\"$imasroot/img/tpvec.gif\" onclick=\"imathasDraw.settool(this,$qn,5.4)\" ";
					if (count($answerformat)>1 && $answerformat[1]=='vector') { $out .= 'class="sel" '; $def = 5.4;}
					$out .= '/>';
				}
			} else {
				if ($answerformat[0]=='numberline') {
					array_shift($answerformat);
				}
				for ($i=0; $i<count($answerformat); $i++) {
					if ($i==0) {
						$out .= '<span class="sel" ';
					} else {
						$out .= '<span ';
					}
					if ($answerformat[$i]=='line') {
						$out .= "onclick=\"imathasDraw.settool(this,$qn,0)\">" . _('Line') . "</span>";
					} else if ($answerformat[$i]=='lineseg') {
						$out .= "onclick=\"imathasDraw.settool(this,$qn,0.5)\">" . _('Line Segment') . "</span>";
					} else if ($answerformat[$i]=='freehand') {
						$out .= "onclick=\"imathasDraw.settool(this,$qn,0.7)\">" . _('Freehand Draw') . "</span>";
					} else if ($answerformat[$i]=='dot') {
						$out .= "onclick=\"imathasDraw.settool(this,$qn,1)\">" . _('Dot') . "</span>";
					} else if ($answerformat[$i]=='opendot') {
						$out .= "onclick=\"imathasDraw.settool(this,$qn,2)\">" . _('Open Dot') . "</span>";
					} else if ($answerformat[$i]=='polygon') {
						$out .= "onclick=\"imathasDraw.settool(this,$qn,0)\">" . _('Polygon') . "</span>";
						$dotline = 1;
					} else if ($answerformat[$i]=='closedpolygon') {
						$out .= "onclick=\"imathasDraw.settool(this,$qn,0)\">" . _('Polygon') . "</span>";
						$dotline = 2;
						$answerformat[$i] = 'polygon';
					}
				}
				if ($answerformat[0]=='line') {
					$def = 0;
				} else if ($answerformat[0]=='lineseg') {
					$def = 0.5;
				} else if ($answerformat[0]=='freehand') {
					$def = 0.7;
				} else if ($answerformat[0]=='dot') {
					$def = 1;
				} else if ($answerformat[0]=='opendot') {
					$def = 2;
				} else if ($answerformat[0]=='polygon') {
					$def = 0;
				}
			}



			if (strpos($snaptogrid,':')!==false) { $snaptogrid = "'$snaptogrid'";}
			$out .= '</span></div>';
			$out .= getcolormark($colorbox);
			if ($colorbox!='') { $out .= '</div>';}
			$out .= "<input type=\"hidden\" name=\"qn$qn\" id=\"qn$qn\" value=\"$la\" />";
			$out .= "<script type=\"text/javascript\">canvases[$qn] = [$qn,'$bg',{$settings[0]},{$settings[1]},{$settings[2]},{$settings[3]},5,{$settings[6]},{$settings[7]},$def,$dotline,$locky,$snaptogrid];";
			if (isset($GLOBALS['capturedrawinit'])) {
				if (!isset($GLOBALS['drawinitdata'])) {
					$GLOBALS['drawinitdata'] = array();
				}
				$GLOBALS['drawinitdata'][$qn] = "'$bg',{$settings[0]},{$settings[1]},{$settings[2]},{$settings[3]},5,{$settings[6]},{$settings[7]},$def,$dotline,$locky,$snaptogrid";
			}
			$la = str_replace(array('(',')'),array('[',']'),$la);
			$la = explode(';;',$la);
			if ($la[0]!='') {
				$la[0] = '['.str_replace(';','],[',$la[0]).']';
			}
			$la = '[['.implode('],[',$la).']]';

			$out .= "drawla[$qn] = $la;</script>";
		}
		$tip = _('Enter your answer by drawing on the graph.');
		if (isset($answers)) {
			$saarr = array();
			$ineqcolors = array("blue","red","green");
			$k = 0;
			foreach($answers as $ans) {
				if (is_array($ans)) { continue;} //shouldn't happen, unless user forgot to set question to multipart
				if ($ans=='') { continue;}
				$function = array_map('trim',explode(',',$ans));
				if ($answerformat[0]=='inequality') {
					if ($function[0]{2}=='=') {
						$type = 10;
						$c = 3;
					} else {
						$type = 10.2;
						$c = 2;
					}
					$dir = $function[0]{1};
					$saarr[$k]  = makepretty($function[0]).','.$ineqcolors[$k%3];
				} else {
					if (count($function)==2 || (count($function)==3 && ($function[2]=='open' || $function[2]=='closed'))) { //is dot
						$saarr[$k] = $function[1].',blue,'.$function[0].','.$function[0];
						if (count($function)==2 || $function[2]=='closed') {
							$saarr[$k] .= ',closed';
						} else {
							$saarr[$k] .= ',open';
						}
						if ($locky==1) {
							$saarr[$k] .=',,2';
						}
					} else if ($function[0]=='vector') {
						if (count($function)>4) {
							$dx = $function[3] - $function[1];
							$dy = $function[4] - $function[2];
							$xs = $function[1];
							$ys = $function[2];
						} else {
							$dx = $function[1];
							$dy = $function[2];
							$xs = 0; $ys = 0;
						}
						$saarr[$k] = "[$xs + ($dx)*t, $ys + ($dy)*t],blue,0,1,,arrow";
					} else if ($function[0]=='circle') { //is circle
						$saarr[$k] = "[{$function[3]}*cos(t)+{$function[1]},{$function[3]}*sin(t)+{$function[2]}],blue,0,6.31";
					} else if (substr($function[0],0,2)=='x=') {
						$saarr[$k] = '['.substr($function[0],2).',t],blue,'.($settings[2]-1).','.($settings[3]+1);
					} else { //is function
						$saarr[$k] = $function[0].',blue';
						if (count($function)>2) {
							if ($function[1] == '-oo') { $function[1] = $settings[0]-.1*($settings[1]-$settings[0]);}
							if ($function[2] == 'oo') { $function[2] = $settings[1]+.1*($settings[1]-$settings[0]);}
							$saarr[$k] .= ','.$function[1].','.$function[2];
							if ($locky==1) {
								$saarr[$k] .=',,,3';
							}
						} else if ($locky==1) {
							$saarr[$k] .=',,,,,3';
						}
						//add asymptotes for rational function graphs
						if (strpos($function[0],'/x')!==false || preg_match('|/\([^\)]*x|', $function[0])) {
							$func = makepretty($function[0]);
							$func = mathphp($func,'x');
							$func = str_replace("(x)",'($x)',$func);
							$func = create_function('$x', 'return ('.$func.');');
							$x1 = 1/4*$settings[1] + 3/4*$settings[0];
							$x2 = 1/2*$settings[1] + 1/2*$settings[0];
							$x3 = 3/4*$settings[1] + 1/4*$settings[0];

							$y1 = @$func($x1);
							$y2 = @$func($x2);
							$y3 = @$func($x3);

							if ($y1===false) {
								$x1 = $x1+.2*($x2-$x1);
								$y1 = @$func($x1);
								$y1p = $settings[7] - ($y1-$settings[2])*$pixelspery - $imgborder;
							} else if ($y2===false) {
								$x2 = $x2+.2*($x2-$x1);
								$y2 = @$func($x2);
								$y2p = $settings[7] - ($y2-$settings[2])*$pixelspery - $imgborder;
							} else if ($y3===false) {
								$x3 = $x3-.2*($x2-$x1);
								$y3 = @$func($x3);
								$y3p = $settings[7] - ($y3-$settings[2])*$pixelspery - $imgborder;
							}
							$va = ($x1*$x2*$y1-$x1*$x2*$y2-$x1*$x3*$y1+$x1*$x3*$y3+$x2*$x3*$y2-$x2*$x3*$y3)/(-$x1*$y2+$x1*$y3+$x2*$y1-$x2*$y3-$x3*$y1+$x3*$y2);
							$ha = (($x1*$y1-$x2*$y2)-$va*($y1-$y2))/($x1-$x2);

							$k++;
							$saarr[$k] = "$ha,green,,,,,,dash";
							$k++;
							$saarr[$k] = "[$va,t],green,,,,,,dash";
						}
					}
				}
				$k++;
			}

			if ($backg!='') {
				if (!is_array($backg) && substr($backg,0,5)=="draw:") {
					$sa = showplot($saarr,$origxmin,$settings[1],$origymin,$settings[3],$sclinglbl,$sclinggrid,$settings[6],$settings[7]);
					$insat = strpos($sa,');',strpos($sa,'axes'))+2;
					$sa = substr($sa,0,$insat).str_replace("'",'"',substr($backg,5)).substr($sa,$insat);
				} else {
					if (!is_array($backg)) {
						settype($backg,"array");
					}
					$saarr = array_merge($saarr,$backg);
					$sa = showplot($saarr,$origxmin,$settings[1],$origymin,$settings[3],$sclinglbl,$sclinggrid,$settings[6],$settings[7]);
					if (isset($grid) && strpos($grid[4],'pi')!==false) {
						$sa = addfractionaxislabels($sa,$grid[4]);
					}
				}

			} else {
				$sa = showplot($saarr,$origxmin,$settings[1],$origymin,$settings[3],$sclinglbl,$sclinggrid,$settings[6],$settings[7]);
				if (isset($grid) && strpos($grid[4],'pi')!==false) {
					$sa = addfractionaxislabels($sa,$grid[4]);
				}
			}
			if ($answerformat[0]=="polygon") {
				if ($dotline==2) {
					$cmd = 'fill="transblue";path([['.implode('],[',$answers).']]);fill="blue";';
				} else {
					$cmd = 'stroke="blue";path([['.implode('],[',$answers).']]);';
				}
				for($i=0;$i<count($answers)-1;$i++) {
					$cmd .= 'dot(['.$answers[$i].']);';
				}
				$sa = adddrawcommand($sa,$cmd);
			}
		}
	} else if ($anstype == "file") {
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}
		if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}

		if ($colorbox!='') { $out .= '<span class="'.$colorbox.'">';}
		$out .= "<input type=\"file\" name=\"qn$qn\" id=\"qn$qn\" />\n";
		$out .= getcolormark($colorbox);
		if ($colorbox!='') { $out .= '</span>';}
		if ($la!='') {
			if (isset($GLOBALS['testsettings']) && isset($GLOBALS['sessiondata']['groupid']) && $GLOBALS['testsettings']>0 && $GLOBALS['sessiondata']['groupid']>0) {
				$s3asid = 'grp'.$GLOBALS['sessiondata']['groupid'].'/'.$GLOBALS['testsettings']['id'];
			} else if (isset($GLOBALS['asid'])) {
				$s3asid = $GLOBALS['asid'];
			}
			if (isset($GLOBALS['questionscoreref'])) {
				if ($multi==0) {
					$el = $GLOBALS['questionscoreref'][0];
					$sc = $GLOBALS['questionscoreref'][1];
				} else {
					$el = $GLOBALS['questionscoreref'][0].'-'.($qn%1000);
					$sc = $GLOBALS['questionscoreref'][1][$qn%1000];
				}
				$out .= '<span style="float:right;">';
				$out .= '<img class="scoreicon" src="'.$imasroot.'/img/q_fullbox.gif" ';
				$out .= "onclick=\"quicksetscore('$el',$sc)\" />";
				$out .= '<img class="scoreicon" src="'.$imasroot.'/img/q_halfbox.gif" ';
				$out .= "onclick=\"quicksetscore('$el',.5*$sc)\" />";
				$out .= '<img class="scoreicon" src="'.$imasroot.'/img/q_emptybox.gif" ';
				$out .= "onclick=\"quicksetscore('$el',0)\" /></span>";
			}
			if (!empty($s3asid)) {
				require_once(dirname(__FILE__)."/../includes/filehandler.php");

				if (substr($la,0,5)=="Error") {
					$out .= "<br/>$la";
				} else {
					$file = preg_replace('/@FILE:(.+?)@/',"$1",$la);
					$url = getasidfileurl($file);
					$extension = substr($url,strrpos($url,'.')+1,3);
					$filename = basename($file);
					$out .= "<br/>" . _('Last file uploaded:') . " <a href=\"$url\" target=\"_new\">$filename</a>";
					$out .= "<input type=\"hidden\" name=\"lf$qn\" value=\"$file\"/>";
					if (in_array(strtolower($extension),array('jpg','gif','png','bmp','jpe'))) {
						$out .= " <span class=\"clickable\" id=\"filetog$qn\" onclick=\"toggleinlinebtn('img$qn','filetog$qn');\">[+]</span>";
						$out .= " <br/><div><img id=\"img$qn\" style=\"display:none;max-width:80%;\" onclick=\"rotateimg(this)\" src=\"$url\" /></div>";
					} else if (in_array(strtolower($extension),array('doc','docx','pdf','xls','xlsx','ppt','pptx'))) {
						$out .= " <span class=\"clickable\" id=\"filetog$qn\" onclick=\"toggleinlinebtn('fileprev$qn','filetog$qn');\">[+]</span>";
						$out .= " <br/><iframe id=\"fileprev$qn\" style=\"display:none;\" src=\"https://docs.google.com/viewer?url=".urlencode($url)."&embedded=true\" width=\"80%\" height=\"600px\"></iframe>";
					}

				}
			} else {
				$out .= "<br/>$la";
			}
		}
		$tip .= _('Select a file to upload');
		$sa .= $answer;
	}

	return array($out,$tip,$sa,$preview);
}




function scorepart($anstype,$qn,$givenans,$options,$multi) {
	$defaultreltol = .0015;
	global $mathfuncs;
	if ($anstype == "number") {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['reqsigfigs'])) {if (is_array($options['reqsigfigs'])) {$reqsigfigs = $options['reqsigfigs'][$qn];} else {$reqsigfigs = $options['reqsigfigs'];}}
		if (isset($options['requiretimes'])) {if (is_array($options['requiretimes'])) {$requiretimes = $options['requiretimes'][$qn];} else {$requiretimes = $options['requiretimes'];}}
		if (isset($options['requiretimeslistpart'])) {if (is_array($options['requiretimeslistpart'])) {$requiretimeslistpart = $options['requiretimeslistpart'][$qn];} else {$requiretimeslistpart = $options['requiretimeslistpart'];}}
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}

		if (is_array($options['partialcredit'][$qn]) || ($multi>0 && is_array($options['partialcredit']))) {$partialcredit = $options['partialcredit'][$qn];} else {$partialcredit = $options['partialcredit'];}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = array_map('trim',explode(',',$answerformat));

		$givenans = normalizemathunicode($givenans);
		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
			list($givenans, $_POST["tc$qn"], $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
		}

		$GLOBALS['partlastanswer'] = $givenans;



		if (isset($requiretimes) && checkreqtimes($givenans,$requiretimes)==0) {
			return 0;
		}

		if (isset($partialcredit)) {
			if (!is_array($partialcredit)) {
				$partialcredit = array_map('trim',explode(',',$partialcredit));
			}
			$altanswers = array(); $altweights = array();
			for ($i=0;$i<count($partialcredit);$i+=2) {
				$altanswers[] = $partialcredit[$i];
				$altweights[] = floatval($partialcredit[$i+1]);
			}
		}

		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
		if (isset($reqsigfigs)) {
			$reqsigfigoffset = 0;
			if ($reqsigfigs{0}=='=') {
				$exactsigfig = true;
				$reqsigfigs = substr($reqsigfigs,1);
			} else if ($reqsigfigs{0}=='[') {
				$exactsigfig = false;
				$reqsigfigparts = explode(',',substr($reqsigfigs,1,-1));
				$reqsigfigs = $reqsigfigparts[0];
				$reqsigfigoffset = $reqsigfigparts[1] - $reqsigfigparts[0];
			} else {
				$exactsigfig = false;
			}
		}

		if ($multi>0) { $qn = $multi*1000+$qn;}

		if ($answer==='') {
			if (trim($givenans)==='') { return 1;} else { return 0;}
		}
		if ($answer==='0 or ') {
			if (trim($givenans)==='' || trim($givenans)==='0') { return 1;} else { return 0;}
		}
		if ($givenans == null) {return 0;}
		if (in_array('set',$ansformats) || in_array('exactset',$ansformats)) {
			$givenans = trim($givenans);
			if ($givenans{0}!='{' || substr($givenans,-1)!='}') { return 0; }
			$answer = str_replace(array('{','}'),'', $answer);
			$givenans = str_replace(array('{','}'),'', $givenans);
			$answerformat = str_replace('set','list',$answerformat);
			$ansformats = array_map('trim',explode(',',$answerformat));
		}
		if (in_array('exactlist',$ansformats)) {
			$gaarr = array_map('trim', explode(',',$givenans));
			$gaarrcnt = count($gaarr);
			$anarr = explode(',',$answer);
			$islist = true;
		} else if (in_array('orderedlist',$ansformats)) {
			$gamasterarr = array_map('trim', explode(',',$givenans));
			$gaarr = $gamasterarr;
			$anarr = explode(',',$answer);
			$islist = true;
		} else if (in_array('list',$ansformats)) {
			$tmp = array_map('trim', explode(',',$givenans));
			sort($tmp);
			$gaarr = array($tmp[0]);
			for ($i=1;$i<count($tmp);$i++) {
				if ($tmp[$i]-$tmp[$i-1]>1E-12) {
					$gaarr[] = $tmp[$i];
				}
			}
			$gaarrcnt = count($gaarr);
			$tmp = explode(',',$answer);
			sort($tmp);
			$anarr = array($tmp[0]);
			for ($i=1;$i<count($tmp);$i++) {
				if ($tmp[$i]-$tmp[$i-1]>1E-12) {
					$anarr[] = $tmp[$i];
				}
			}
			$islist = true;
		} else {
			$givenans = preg_replace('/(\d)\s*,\s*(?=\d{3}\b)/','$1',$givenans);
			$givenans = str_replace(',','99999999',$givenans); //force wrong ans on lingering commas

			$gaarr = array(str_replace(array('$',',',' ','/','^','*'),'',$givenans));

			if (strpos($answer,'[')===false && strpos($answer,'(')===false) {
				$anarr = array(str_replace(',','',$answer));
			} else {
				$anarr = array($answer);
			}
			$islist = false;
		}


		$extrapennum = count($gaarr)+count($anarr);

		if (in_array('orderedlist',$ansformats)) {
			if (count($gamasterarr)!=count($anarr)) {
				return 0;
			}
		}
		if (in_array('parenneg',$ansformats)) {
			foreach ($gaarr as $k=>$v) {
				if ($v{0}=='(') {
					$gaarr[$k] = -1*substr($v,1,-1);
				}
			}
		}

		$correct = 0;
		foreach($anarr as $i=>$answer) {
			$foundloc = -1;
			if (in_array('orderedlist',$ansformats)) {
				$gaarr = array($gamasterarr[$i]);
			}

			foreach($gaarr as $j=>$givenans) {
				$givenans = trim($givenans);
				if (isset($requiretimeslistpart) && checkreqtimes($givenans,$requiretimeslistpart)==0) {
					continue;
				}
				$anss = explode(' or ',$answer);
				foreach ($anss as $anans) {
					if (!is_numeric($anans)) {
						if (preg_match('/(\(|\[)(-?[\d\.]+|-oo)\,(-?[\d\.]+|oo)(\)|\])/',$anans,$matches)) {
							if ($matches[2]=='-oo') {$matches[2] = -1e99;}
							if ($matches[3]=='oo') {$matches[3] = 1e99;}
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
						//$givenans = preg_replace('/[^\-\d\.eE]/','',$givenans); //strip out units, dollar signs, whatever
						$givenans = preg_replace('/^((-|\+)?\d*\.?\d*E?\-?\d*)[^+\-]*$/','$1',trim($givenans)); //strip out units
						if (is_numeric($givenans)) {

							if (isset($reqsigfigs)) {
								if ($givenans*$anans < 0) { continue;} //move on if opposite signs
								if ($anans!=0) {
									$v = -1*floor(-log10(abs($anans))-1e-12) - $reqsigfigs;
								}
								$epsilon = (($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12));
								if (strpos($givenans,'E')!==false) {  //handle computer-style scientific notation
									preg_match('/^-?[1-9]\.?(\d*)E/', $givenans, $matches);
									$gasigfig = 1+strlen($matches[1]);
									if ($exactsigfig) {
										if ($gasigfig != $reqsigfigs) {continue;}
									} else {
										if ($gasigfig < $reqsigfigs) {continue;}
										if ($reqsigfigoffset>0 && $gasigfig-$reqsigfigs>$reqsigfigoffset) {continue;}
									}
									if (abs($anans-$givenans)< pow(10,$v)/2+$epsilon) {$correct += 1; $foundloc = $j; break 2;}
								} else {
									if (!$exactsigfig) {
										//this line will reject 0.25 if the answer is 0.250 with 3 sigfigs
										$gadploc = strpos($givenans,'.');
										if ($gadploc===false) {$gadploc = strlen($givenans);}
										if ($anans != 0 && $v < 0 && strlen($givenans) - $gadploc-1 + $v < 0) { continue; } //not enough decimal places
										if ($anans != 0 && $reqsigfigoffset>0 && $v<0 && strlen($givenans) - $gadploc-1 + $v>$reqsigfigoffset) {continue;} //too many sigfigs
										if (abs($anans-$givenans)< pow(10,$v)/2+$epsilon) {$correct += 1; $foundloc = $j; break 2;}
									} else {
										if (ltrim(prettysigfig($anans,$reqsigfigs,''),'0')===ltrim($givenans,'0')) {
											$correct += 1; $foundloc = $j; break 2;
										}
									}
								}

							} else if (isset($abstolerance)) {
								if (abs($anans-$givenans) < $abstolerance + (($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12))) {$correct += 1; $foundloc = $j; break 2;}
							} else {
								if ($anans==0) {
									if (abs($anans - $givenans) < $reltolerance/1000 + 1E-12) {$correct += 1; $foundloc = $j; break 2;}
								} else {
									if (abs($anans - $givenans)/(abs($anans)+(abs($anans)>1?1E-12:(abs($anans)*1E-12))) < $reltolerance+ 1E-12) {$correct += 1; $foundloc = $j; break 2;}
								}
							}
						}
					}
				}
			}
			if ($foundloc>-1) {
				array_splice($gaarr,$foundloc,1); //remove from list
				if (count($gaarr)==0 && !in_array('orderedlist',$ansformats)) {
					break; //stop if no student answers left
				}
			}
		}
		if (!in_array('orderedlist',$ansformats)) {
			if ($gaarrcnt<=count($anarr)) {
				$score = $correct/count($anarr);
			} else {
				$score = $correct/count($anarr) - ($gaarrcnt-count($anarr))/$extrapennum;  //take off points for extranous stu answers
			}
		} else {
			$score = $correct/count($anarr);
		}
		if ($score<0) { $score = 0; }
		if ($score==0 && isset($partialcredit) && !$islist && is_numeric($givenans)) {
			foreach ($altanswers as $i=>$anans) {
				if (isset($reqsigfigs)) {
					if ($givenans*$anans < 0) { continue;} //move on if opposite signs
					if ($anans!=0) {
						$v = -1*floor(-log10(abs($anans))-1e-12) - $reqsigfigs;
					}
					if (strpos($givenans,'E')!==false) {  //handle computer-style scientific notation
						preg_match('/^-?[1-9]\.?(\d*)E/', $givenans, $matches);
						$gasigfig = 1+strlen($matches[1]);
						if ($exactsigfig) {
							if ($gasigfig != $reqsigfigs) {continue;}
						} else {
							if ($gasigfig < $reqsigfigs) {continue;}
							if ($reqsigfigoffset>0 && $gasigfig-$reqsigfigs>$reqsigfigoffset) {continue;}
						}
						if (abs($anans-$givenans)< pow(10,$v)/2+1E-12) {$score = $altweights[$i]; break;}
					} else {
						if (!$exactsigfig) {
							//this line will reject 0.25 if the answer is 0.250 with 3 sigfigs
							if ($anans != 0 && $v < 0 && strlen($givenans) - strpos($givenans,'.')-1 + $v < 0) { continue; } //not enough decimal places
							if ($anans != 0 && $reqsigfigoffset>0 && $v<0 && strlen($givenans) - strpos($givenans,'.')-1 + $v>$reqsigfigoffset) {continue;} //too many sigfigs

							if (abs($anans-$givenans)< pow(10,$v)/2+1E-12) {$score = $altweights[$i]; break;}
						} else {
							if (ltrim(prettysigfig($anans,$reqsigfigs,''),'0')===ltrim($givenans,'0')) {
								$score = $altweights[$i]; break;
							}
						}
					}

				} else if (isset($abstolerance)) {
					if (abs($anans-$givenans) < $abstolerance + (($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12))) {$score = $altweights[$i]; break;}
				} else {
					if ($anans==0) {
						if (abs($anans - $givenans) < $reltolerance/1000 + 1E-12) {$score = $altweights[$i]; break;}
					} else {
						if (abs($anans - $givenans)/(abs($anans)+(abs($anans)>1?1E-12:(abs($anans)*1E-12))) < $reltolerance+ 1E-12) {$score = $altweights[$i]; break;}
					}
				}
			}
		}
		return ($score);

	} else if ($anstype == "choices") {
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}} else {$noshuffle = "none";}
		if (is_array($options['partialcredit'][$qn]) || ($multi>0 && is_array($options['partialcredit']))) {$partialcredit = $options['partialcredit'][$qn];} else {$partialcredit = $options['partialcredit'];}

		if (isset($partialcredit)) {
			if (!is_array($partialcredit)) {
				$partialcredit = explode(',',$partialcredit);
			}
			$creditweight = array();
			for ($i=0;$i<count($partialcredit);$i+=2) {
				$creditweight[$partialcredit[$i]] = floatval($partialcredit[$i+1]);
			}
		}

		if (!is_array($questions)) {
			echo _('Eeek!  $questions is not defined or needs to be an array.  Make sure $questions is defined in the Common Control section.');
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
		if ($givenans==='NA' || $givenans === null) {
			$GLOBALS['partlastanswer'] = $givenans;
		} else {
			$GLOBALS['partlastanswer'] = $givenans.'$!$'.$randkeys[$givenans];
		}
		if ($givenans == null) {return 0;}

		if ($givenans=='NA') { return 0; }
		$anss = explode(' or ',$answer);
		foreach ($anss as $k=>$v) {
			$anss[$k] = intval($v);
		}
		//if ($randkeys[$givenans] == $answer) {return 1;} else { return 0;}
		if (in_array($randkeys[$givenans],$anss)) {
			return 1;
		} else if (isset($partialcredit) && isset($creditweight[$randkeys[$givenans]])) {
			return $creditweight[$randkeys[$givenans]];
		} else {
			return 0;
		}
	} else if ($anstype == "multans") {
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (isset($options['answers'])) {if (is_array($options['answers'])) {$answers = $options['answers'][$qn];} else {$answers = $options['answers'];}}
			else if (isset($options['answer'])) {if (is_array($options['answer'])) {$answers = $options['answer'][$qn];} else {$answers = $options['answer'];}}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}}

		if (isset($options['scoremethod']))if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$qn];} else {$scoremethod = $options['scoremethod'];}

		if (!is_array($questions)) {
			echo _('Eeek!  $questions is not defined or needs to be an array.  Make sure $questions is defined in the Common Control section.');
			return false;
		}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$score = 1.0;
		if ($noshuffle == "last") {
			$randqkeys = array_rand(array_slice($questions,0,count($questions)-1),count($questions)-1);
			shuffle($randqkeys);
			array_push($randqkeys,count($questions)-1);
		} else if ($noshuffle == "all" || count($questions)==1) {
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
		$origla = array();
		for ($i=0;$i<count($questions);$i++) {
			if ($i>0) {$GLOBALS['partlastanswer'] .= "|"; } else {$GLOBALS['partlastanswer']='';}
			$GLOBALS['partlastanswer'] .= $_POST["qn$qn"][$i];
			if (isset($_POST["qn$qn"][$i])) {
				$origla[] = $randqkeys[$i];
			}

			if (isset($_POST["qn$qn"][$i])!==(in_array($randqkeys[$i],$akeys))) {
				$score -= $deduct;
			}
		}
		$GLOBALS['partlastanswer'] .= '$!$'.implode('|',$origla);
		if (isset($scoremethod)) {
			if ($scoremethod=='allornothing' && $score<1) {
				$score = 0;
			} else if ($scoremethod == 'takeanything') {
				$score = 1;
			}
		}
		if ($score < 0) {
			$score = 0;
		}
		return $score;
	} else if ($anstype == "matching") {
		if (is_array($options['questions'][$qn])) {$questions = $options['questions'][$qn];} else {$questions = $options['questions'];}
		if (isset($options['answers'])) {if (is_array($options['answers'][$qn])) {$answers = $options['answers'][$qn];} else {$answers = $options['answers'];}}
			else if (isset($options['answer'])) {if (is_array($options['answer'][$qn])) {$answers = $options['answer'][$qn];} else {$answers = $options['answer'];}}
		if (is_array($options['matchlist'])) {$matchlist = $options['matchlist'][$qn];} else {$matchlist = $options['matchlist'];}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$qn];} else {$noshuffle = $options['noshuffle'];}}

		if (!is_array($questions) || !is_array($answers)) {
			echo _('Eeek!  $questions or $answers is not defined or needs to be an array.  Make sure both are defined in the Common Control section.');
			return 0;
		}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$score = 1.0;
		$deduct = 1.0/count($questions);
		if ($noshuffle=="questions" || $noshuffle=='all') {
			$randqkeys = array_keys($questions);
		} else {
			$randqkeys = array_rand($questions,count($questions));
			shuffle($randqkeys);
		}
		if ($noshuffle=="answers" || $noshuffle=='all') {
			$randakeys = array_keys($answers);
		} else {
			$randakeys = array_rand($answers,count($answers));
			shuffle($randakeys);
		}
		if (isset($matchlist)) {$matchlist = array_map('trim',explode(',',$matchlist));}

		$origla = array();
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
				$origla[$randqkeys[$i]] = $randakeys[$qa];
				if (isset($matchlist)) {
					if ($matchlist[$randqkeys[$i]]!=$randakeys[$qa]) {
						$score -= $deduct;
					}
				} else {
					if ($randqkeys[$i]!=$randakeys[$qa]) {
						$score -= $deduct;
					}
				}
			} else {$origla[$randqkeys[$i]] = '';$score -= $deduct;}
		}
		ksort($origla);
		$GLOBALS['partlastanswer'] .= '$!$'.implode('|',$origla);
		return $score;
	} else if ($anstype=="matrix") {
		if (is_array($options['answer']) && isset($options['answer'][$qn])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
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
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
		if (isset($options['answersize'])) {if (is_array($options['answersize'])) {$answersize = $options['answersize'][$qn];} else {$answersize = $options['answersize'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}

		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = array_map('trim',explode(',',$answerformat));

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
			}
			$GLOBALS['partlastanswer'] = implode("|",$givenanslist);
			$GLOBALS['partlastanswer'] .= '$#$'.str_replace(',','|',str_replace(array('(',')','[',']'),'',$givenans));

			for ($i=0; $i<count($answerlist); $i++) {
				if (!checkanswerformat($givenanslist[$i],$ansformats)) {
					return 0; //perhaps should just elim bad answer rather than all?
				}
			}

		} else {
			$_POST["tc$qn"] = preg_replace('/\)\s*,\s*\(/','),(',$_POST["tc$qn"]);
			$GLOBALS['partlastanswer'] = $_POST["tc$qn"].'$#$'.$givenans;
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
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}

		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($answerformat)) { $answerformat = '';}
		$givenans = normalizemathunicode($givenans);
		$ansformats = array_map('trim',explode(',',$answerformat));
		$answer = str_replace(' ','',$answer);

		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
			list($givenans, $_POST["tc$qn"], $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
		}

		if ($anstype=='ntuple') {
			$GLOBALS['partlastanswer'] = $givenans;
		} else if ($anstype=='calcntuple') {
			$_POST["tc$qn"] = normalizemathunicode($_POST["tc$qn"]);
			$GLOBALS['partlastanswer'] = $_POST["tc$qn"].'$#$'.$givenans;
			//test for correct format, if specified
			if (checkreqtimes($_POST["tc$qn"],$requiretimes)==0) {
				return 0;
			}
			$tocheck = str_replace(' ','',$_POST["tc$qn"]);
			$tocheck = str_replace(array('],[','),(','>,<'),',',$tocheck);
			$tocheck = substr($tocheck,1,strlen($tocheck)-2);
			$tocheck = explode(',',$tocheck);

			if ($answer != 'DNE' && $answer != 'oo') {
				foreach($tocheck as $chkme) {
					if (!checkanswerformat($chkme,$ansformats)) {
						return 0; //perhaps should just elim bad answer rather than all?
					}
				}
			}
		}
		if ($givenans == null) {return 0;}

		$givenans = str_replace(' ','',$givenans);

		if ($answer=='DNE') {
			if (strtoupper($givenans)=='DNE') {
				return 1;
			} else {
				return 0;
			}
		} else if ($answer=='oo') {
			if ($givenans=='oo') {
				return 1;
			} else {
				return 0;
			}
		}

		preg_match_all('/([\(\[\<\{])(.*?)([\)\]\>\}])/', $givenans, $gaarr, PREG_SET_ORDER);
		//preg_match_all('/([\(\[\<\{])(.*?)([\)\]\>\}])/', $answer, $anarr, PREG_SET_ORDER);
		//replaced with string-based approach below.  Allows eval as needed

		$anarr = array();
		$NCdepth = 0;
		$lastcut = 0;
		$answer = makepretty($answer);
		for ($i=0; $i<strlen($answer); $i++) {
			$dec = false;
			if ($answer{$i}=='(' || $answer{$i}=='[' || $answer{$i}=='<' || $answer{$i}=='{') {
				if ($NCdepth==0) {
					$lastcut = $i;
				}
				$NCdepth++;
			} else if ($answer{$i}==')' || $answer{$i}==']' || $answer{$i}=='>' || $answer{$i}=='}') {
				$NCdepth--;
				if ($NCdepth==0) {
					$anarr[] = array('',$answer{$lastcut},substr($answer,$lastcut+1,$i-$lastcut-1),$answer{$i});
				}
			}
		}
		foreach ($anarr as $k=>$v) {
			$ansparts = explode(',',$v[2]);
			foreach ($ansparts as $j=>$v) {
				if (!is_numeric($v)) {
					$ansparts[$j] = eval('return('.mathphp($v,null).');');
				}
			}
			$anarr[$k][2] = $ansparts;
		}

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
				//$ansparts = explode(',',$answer[2]);
				$ansparts = $answer[2];
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
		if (isset($options['requiretimeslistpart'])) {if (is_array($options['requiretimeslistpart'])) {$requiretimeslistpart = $options['requiretimeslistpart'][$qn];} else {$requiretimeslistpart = $options['requiretimeslistpart'];}}
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}

		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		if (!isset($answerformat)) { $answerformat = '';}
		$ansformats = array_map('trim',explode(',',$answerformat));

		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
			list($givenans, $_POST["tc$qn"], $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
		}

		if ($anstype=='complex') {
			$GLOBALS['partlastanswer'] = $givenans;
		} else if ($anstype=='calccomplex') {
			$GLOBALS['partlastanswer'] = $_POST["tc$qn"];
			//test for correct format, if specified
			if (($answer!='DNE'&&$answer!='oo') && checkreqtimes($_POST["tc$qn"],$requiretimes)==0) {
				return 0;
			}
			$tocheck = explode(',',$_POST["tc$qn"]);
			foreach ($tocheck as $tchk) {
				if (in_array('sloppycomplex',$ansformats)) {
					$tchk = str_replace(array('sin','pi'),array('s$n','p$'),$tchk);
					if (substr_count($tchk,'i')>1) {
						return 0;
					}
					$tchk = str_replace(array('s$n','p$'),array('sin','pi'),$tchk);
				} else {
					$cpts = parsecomplex($tchk);

					if (!is_array($cpts)) {
						return 0;
					}
					if ($cpts[1]{0}=='+') {
						$cpts[1] = substr($cpts[1],1);
					}
					//echo $cpts[0].','.$cpts[1].'<br/>';
					if ($answer!='DNE'&&$answer!='oo' && (!checkanswerformat($cpts[0],$ansformats) || !checkanswerformat($cpts[1],$ansformats))) {
						return 0;
					}
					if ($answer!='DNE'&&$answer!='oo' && isset($requiretimeslistpart) && checkreqtimes($tchk,$requiretimeslistpart)==0) {
						return 0;
					}
				}
			}
		}

		if ($givenans == null) {return 0;}
		$answer = str_replace(' ','',makepretty($answer));
		$givenans = str_replace(' ','',$givenans);

		if ($answer=='DNE') {
			if (strtoupper($givenans)=='DNE') {
				return 1;
			} else {
				return 0;
			}
		} else if ($answer=='oo') {
			if ($givenans=='oo') {
				return 1;
			} else {
				return 0;
			}
		}

		$gaarr = array_map('trim',explode(',',$givenans));
		$anarr = array_map('trim',explode(',',$answer));

		if (count($gaarr)==0) {
			return 0;
		}
		$extrapennum = count($gaarr)+count($anarr);
		$correct = 0;
		foreach ($anarr as $i=>$answer) {
			$cparts = parsecomplex($answer);
			if (!is_array($cparts)) {
				$ansparts = parsesloppycomplex($answer);
			} else {
				$ansparts[0] = eval('return ('.mathphp($cparts[0],null).');');
				$ansparts[1] = eval('return ('.mathphp($cparts[1],null).');');
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
		if (isset($options['requiretimeslistpart'])) {if (is_array($options['requiretimeslistpart'])) {$requiretimeslistpart = $options['requiretimeslistpart'][$qn];} else {$requiretimeslistpart = $options['requiretimeslistpart'];}}
		if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$qn];} else {$ansprompt = $options['ansprompt'];}}

		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$givenans = normalizemathunicode($givenans);
		$ansformats = array_map('trim',explode(',',$answerformat));


		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
			list($givenans, $_POST["tc$qn"], $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
		}

		$GLOBALS['partlastanswer'] = $_POST["tc$qn"].'$#$'.$givenans;
		$_POST["tc$qn"] = normalizemathunicode($_POST["tc$qn"]);
		if ($answer==='') {
			if (trim($_POST["tc$qn"])==='') { return 1;} else { return 0;}
		}

		if ($givenans == null) {return 0;}

		$formatok = "all";
		if (checkreqtimes($_POST["tc$qn"],$requiretimes)==0) {
			//return 0;
			$formatok = "nowhole";
		}



		if (in_array("scinot",$ansformats)) {
			$answer = str_replace('xx','*',$answer);
		}
		if (in_array('set',$ansformats) || in_array('exactset',$ansformats)) {
			$answer = str_replace(array('{','}'),'', $answer);
			$givenans = str_replace(array('{','}'),'', $givenans);
			$_POST["tc$qn"] = str_replace(array('{','}'),'', $_POST["tc$qn"]);
			$ansformats = array_map('trim',explode(',', str_replace('set','list',$answerformat)));
		}
		//pre-evaluate all instructor expressions - preg match all intervals.  Return array of or options
		if (in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats) || in_array('list',$ansformats)) {
			$anarr = array_map('trim',explode(',',$answer));
			foreach ($anarr as $k=>$ananswer) {
				$aarr = explode(' or ',$ananswer);
				foreach ($aarr as $j=>$anans) {
					if ($anans=='') {
						if (isset($GLOBALS['teacherid'])) {
							echo '<p>', _('Debug info: empty, missing or invalid $answer'), ' </p>';
						}
						return 0;
					}
					if (preg_match('/(\(|\[)([\d\.]+)\,([\d\.]+)(\)|\])/',$anans,$matches)) {
						$aarr[$j] = $matches;
					} else if (!is_numeric($anans) && $anans!='DNE' && $anans!='oo' && $anans!='+oo' && $anans!='-oo') {
						if ((in_array("mixednumber",$ansformats) || in_array("sloppymixednumber",$ansformats) || in_array("mixednumberorimproper",$ansformats) || in_array("allowmixed",$ansformats)) && preg_match('/^\s*(\-?\s*\d+)\s*(_|\s)\s*(\d+)\s*\/\s*(\d+)\s*$/',$anans,$mnmatches)) {
							$aarr[$j] = $mnmatches[1] + (($mnmatches[1]<0)?-1:1)*($mnmatches[3]/$mnmatches[4]);
						} else {
							$aarr[$j] = eval('return('.mathphp($anans,null).');');
						}
					}
				}
				$anarr[$k] = $aarr;
			}
		} else {
			$aarr = array_map('trim',explode(' or ',$answer));
			foreach ($aarr as $j=>$anans) {
				if ($anans=='') {
					if (isset($GLOBALS['teacherid'])) {
						echo '<p>', _('Debug info: empty, missing, or invalid $answer'), ' </p>';
					}
					return 0;
				}
				if (preg_match('/(\(|\[)([\d\.]+)\,([\d\.]+)(\)|\])/',$anans,$matches)) {
					$aarr[$j] = $matches;
				} else if (!is_numeric($anans) && $anans!='DNE' && $anans!='oo' && $anans!='+oo' && $anans!='-oo') {
					if ((in_array("mixednumber",$ansformats) || in_array("sloppymixednumber",$ansformats) || in_array("mixednumberorimproper",$ansformats) || in_array("allowmixed",$ansformats)) && preg_match('/^\s*(\-?\s*\d+)\s*(_|\s)\s*(\d+)\s*\/\s*(\d+)\s*$/',$anans,$mnmatches)) {
						$aarr[$j] = $mnmatches[1] + (($mnmatches[1]<0)?-1:1)*($mnmatches[3]/$mnmatches[4]);
					} else {
						$aarr[$j] = eval('return('.mathphp($anans,null).');');
					}
				}
			}
			$answer = $aarr;
		}

		if (in_array('exactlist',$ansformats)) {
			$gaarr = array_map('trim',explode(',',$givenans));
			//$anarr = explode(',',$answer);
			$orarr = array_map('trim',explode(',',$_POST["tc$qn"]));
		} else if (in_array('orderedlist',$ansformats)) {
			$gamasterarr = array_map('trim',explode(',',$givenans));
			$gaarr = $gamasterarr;
			//$anarr = explode(',',$answer);
			$orarr = explode(',',$_POST["tc$qn"]);
		} else if (in_array('list',$ansformats)) {
			$tmp = array_map('trim',explode(',',$givenans));
			$tmpor = array_map('trim',explode(',',$_POST["tc$qn"]));
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

			$tmp = $anarr;
			sort($tmp);
			$anarr = array($tmp[0]);
			for ($i=1;$i<count($tmp);$i++) {
				if (!is_numeric($tmp[$i]) || !is_numeric($tmp[$i-1]) || $tmp[$i]-$tmp[$i-1]>1E-12) {
					$anarr[] = $tmp[$i];
				}
			}

		} else {
			$gaarr = array(str_replace(',','',$givenans));
			$anarr = array($answer);
			$orarr = array($_POST["tc$qn"]);
		}
		$extrapennum = count($gaarr)+count($anarr);
		$gaarrcnt = count($gaarr);

		if (in_array('orderedlist',$ansformats)) {
			if (count($gamasterarr)!=count($anarr)) {
				return 0;
			}
		}

		$correct = 0;
		foreach($anarr as $i=>$anss) {
			$foundloc = -1;
			if (in_array('orderedlist',$ansformats)) {
				$gaarr = array($gamasterarr[$i]);
			}
			foreach($gaarr as $j=>$givenans) {
				$partformatok = true;
				if (!checkanswerformat($orarr[$j],$ansformats)) {
					$formatok = "nopart";  $partformatok = false;
					//continue;
				}
				if (isset($requiretimeslistpart) && checkreqtimes($orarr[$j],$requiretimeslistpart)==0) {
					$formatok = "nopart";  $partformatok = false;
					//continue;
				}
				//removed - done above already
				//$anss = explode(' or ',$answer);
				foreach ($anss as $anans) {
					if (!is_numeric($anans)) {
						$givenans = trim($givenans);
						/* moved to preprocessing
						if (preg_match('/(\(|\[)([\d\.]+)\,([\d\.]+)(\)|\])/',$anans,$matches)) {
							if (($matches[1]=="(" && $givenans>$matches[2]) || ($matches[1]=="[" && $givenans>=$matches[2])) {
								if (($matches[4]==")" && $givenans<$matches[3]) || ($matches[4]=="]" && $givenans<=$matches[3])) {
									$correct += 1; $foundloc = $j; break 2;
								}
							}
						} */
						if (is_array($anans)) {
							if (($anans[1]=="(" && $givenans>$anans[2]) || ($anans[1]=="[" && $givenans>=$anans[2])) {
								if (($anans[4]==")" && $givenans<$anans[3]) || ($anans[4]=="]" && $givenans<=$anans[3])) {
									if ($partformatok) {$correct += 1;}; $foundloc = $j; break 2;
								}
							}
						} else if ($anans=="DNE" && strtoupper($givenans)=="DNE") {
							if ($partformatok) {$correct += 1;}; $foundloc = $j; break 2;
						} else if (($anans=="+oo" || $anans=="oo") && ($givenans=="+oo" || $givenans=="oo")) {
							if ($partformatok) {$correct += 1;}; $foundloc = $j; break 2;
						} else if ($anans=="-oo" && $givenans=="-oo") {
							if ($partformatok) {$correct += 1;}; $foundloc = $j; break 2;
						}/* moved to preprocessing
						else if (is_numeric($givenans)) {
							//try evaling answer
							$eanans = eval('return('.mathphp($anans,null).');');
							if (isset($abstolerance)) {
								if (abs($eanans-$givenans) < $abstolerance+1E-12) {$correct += 1; $foundloc = $j; break 2;}
							} else {
								if (abs($eanans - $givenans)/(abs($eanans)+.0001) < $reltolerance+1E-12) {$correct += 1; $foundloc = $j; break 2;}
							}
						}*/
					} else if (is_numeric($givenans)) {
						if (isset($abstolerance)) {
							if (abs($anans-$givenans) < $abstolerance+(($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12))) {if ($partformatok) {$correct += 1;}; $foundloc = $j; break 2;}
						} else {
							if ($anans==0) {
								if (abs($anans - $givenans) < $reltolerance/1000 + 1E-12) {if ($partformatok) {$correct += 1;}; $foundloc = $j; break 2;}
							} else {
								if (abs($anans - $givenans)/(abs($anans)+(abs($anans)>1?1E-12:(abs($anans)*1E-12))) < $reltolerance+1E-12) {if ($partformatok) {$correct += 1;}; $foundloc = $j; break 2;}
							}
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
			//$score = $correct/count($anarr) - count($gaarr)/$extrapennum;  //take off points for extranous stu answers
			if ($gaarrcnt<=count($anarr)) {
				$score = $correct/count($anarr);
			} else {
				$score = $correct/count($anarr) - ($gaarrcnt-count($anarr))/$extrapennum;  //take off points for extranous stu answers
			}
		}
		if ($score<0) { $score = 0; }
		if ($formatok != "all") {
			$GLOBALS['partlastanswer'] .= '$f$1';
			if ($formatok == 'nowhole') {
				$score = 0;
			}
		}
		return ($score);
	} else if ($anstype == "numfunc") {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}

		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
		if (is_array($options['variables'])) {$variables = $options['variables'][$qn];} else {$variables = $options['variables'];}

		if (isset($options['domain'])) {if (is_array($options['domain'])) {$domain = $options['domain'][$qn];} else {$domain= $options['domain'];}}
		if (isset($options['requiretimes'])) {if (is_array($options['requiretimes'])) {$requiretimes = $options['requiretimes'][$qn];} else {$requiretimes = $options['requiretimes'];}}

		if (isset($options['requiretimes'])) {
			if (is_array($options['requiretimes'])) {
				if (is_array($options['requiretimes'][$qn]) || $multi>0) {
					$requiretimes = $options['requiretimes'][$qn];
				} else {
					$requiretimes = $options['requiretimes'];
				}
			} else {
				$requiretimes = $options['requiretimes'];
			}
		}

		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (is_array($options['partialcredit'][$qn]) || ($multi>0 && is_array($options['partialcredit']))) {$partialcredit = $options['partialcredit'][$qn];} else {$partialcredit = $options['partialcredit'];}

		if ($multi>0) { $qn = $multi*1000+$qn;}

		$GLOBALS['partlastanswer'] = $_POST["tc$qn"];
		$correct = true;

		if (!isset($variables)) { $variables = "x";}
		$variables = array_map('trim',explode(",",$variables));
		$ofunc = array();
		for ($i = 0; $i < count($variables); $i++) {
			$variables[$i] = trim($variables[$i]);
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
		if (($v = array_search('E', $variables))!==false) {
			$variables[$v] = 'varE';
			$answer = str_replace('E','varE',$answer);
		}
		$vlist = implode("|",$variables);

		if (isset($domain)) {$fromto = array_map('trim',explode(",",$domain));} else {$fromto[0]=-10; $fromto[1]=10;}

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

		if ($answerformat!="equation" && strpos($answer,'=')!==false) {
			echo 'Your $answer contains an equal sign, but you do not have $answerformat="equation" set. This question probably will not work right.';
		}

		$ansarr = array_map('trim',explode(' or ',$answer));
		$partialpts = array_fill(0, count($ansarr), 1);
		$origanscnt = count($ansarr);
		if (isset($partialcredit)) {
			if (!is_array($partialcredit)) {$partialcredit = explode(',',$partialcredit);}
			for ($i=0;$i<count($partialcredit);$i+=2) {
				if (!in_array($partialcredit[$i], $ansarr) || $partialcredit[$i+1]<1) {
					$ansarr[] = $partialcredit[$i];
					$partialpts[] = $partialcredit[$i+1];
				}
			}
		}

		$rightanswrongformat = -1;
		foreach ($ansarr as $ansidx=>$answer) {
			if (is_array($requiretimes)) {
				if ($ansidx<$origanscnt) {
					$thisreqtimes = $requiretimes[0];
				} else {
					$thisreqtimes = $requiretimes[$ansidx-$origanscnt+1];
				}
			} else {
				$thisreqtimes = $requiretimes;
			}
			$correct = true;
			$answer = preg_replace('/[^\w\*\/\+\=\-\(\)\[\]\{\}\,\.\^\$\!\s]+/','',$answer);

			if ($answerformat=="equation") {
				if (substr_count($_POST["tc$qn"], '=')!=1) {
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

			for($i=0; $i < count($variables); $i++) {
				$answer = str_replace("(".$variables[$i].")",'($tp['.$i.'])',$answer);
			}

			$myans = explode(",",$_POST["qn$qn-vals"]);

			$cntnan = 0;
			$cntzero = 0;
			$cntbothzero = 0;
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

				//echo "$answer, real: $realans, my: {$myans[$i]},rel: ". (abs($myans[$i]-$realans)/abs($realans))  ."<br/>";
				if (isNaN($realans)) {$cntnan++; continue;} //avoid NaN problems
				if ($answerformat=="equation") {  //if equation, store ratios
					if (abs($realans)>.000001 && is_numeric($myans[$i])) {
						$ratios[] = $myans[$i]/$realans;
						if (abs($myans[$i])<=.00000001 && $realans!=0) {
							$cntzero++;
						}
					} else if (abs($realans)<=.000001 && is_numeric($myans[$i]) && abs($myans[$i])<=.00000001) {
						$cntbothzero++;
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
				echo "<p>", _('Debug info: function evaled to Not-a-number at all test points.  Check $domain'), "</p>";
			}
			if ($stunan>1) { //if more than 1 student NaN response
				$correct = false; continue;
			}
			if ($answerformat=="equation") {
				if ($cntbothzero>18) {
					$correct = true;
				} else if (count($ratios)>1) {
					if (count($ratios)==$cntzero) {
						$correct = false; continue;
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
					$correct=false; continue;
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
			if ($correct == true) {
				//test for correct format, if specified
				if ($thisreqtimes!='' && checkreqtimes(str_replace(',','',$_POST["tc$qn"]),$thisreqtimes)==0) {
					$rightanswrongformat = $ansidx;
					continue;
					//$correct = false;
				}
				return $partialpts[$ansidx];
			}
		}
		if ($rightanswrongformat!=-1) {
			$GLOBALS['partlastanswer'] .= '$f$1';
		}

		return 0;

	} else if ($anstype == "string") {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['strflags'])) {if (is_array($options['strflags'])) {$strflags = $options['strflags'][$qn];} else {$strflags = $options['strflags'];}}
		if (isset($options['scoremethod']))if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$qn];} else {$scoremethod = $options['scoremethod'];}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}

		if ($multi>0) { $qn = $multi*1000+$qn;}
		$GLOBALS['partlastanswer'] = $givenans;

		if (isset($scoremethod) && $scoremethod=='takeanything' && trim($givenans)!='') {
			return 1;
		}
		$givenans = stripslashes($givenans);

		if (!isset($answerformat)) { $answerformat = "normal";}
		if ($answerformat=='list') {
			$gaarr = array_map('trim',explode(',',$givenans));
			$anarr = array_map('trim',explode(',',$answer));
		} else {
			$gaarr = array($givenans);
			$anarr = array($answer);
		}
		$strflags = str_replace(' ','',$strflags);
		$strflags = explode(",",$strflags);
		$torem = array();
		foreach($strflags as $flag) {
			$pc = array_map('trim',explode('=',$flag));
			if ($pc[0]=='ignore_symbol') {
				$torem[] = $pc[1];
				continue;
			}
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


		$correct = 0;
		foreach($anarr as $i=>$answer) {
			$foundloc = -1;
			if (count($torem)>0) {
				$answer = str_replace($torem,' ',$answer);
			}
			foreach($gaarr as $j=>$givenans) {
				$givenans = trim($givenans);

				if (count($torem)>0) {
					$givenans = str_replace($torem,' ',$givenans);
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
				if ($flags['ignore_case']===true && !isset($flags['regex'])) {
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
					if ($flags['partial_credit']===true && $answerformat!='list') {
						$poss = strlen($anans);
						$dist = levenshtein($anans,$givenans);
						$score = ($poss - $dist)/$poss;
						if ($score>$correct) { $correct = $score;}
					} else if (isset($flags['allow_diff'])) {
						if (levenshtein($anans,$givenans) <= 1*$flags['allow_diff']) {
							$correct += 1;
							$foundloc = $j;
							break 2;
						}
					} else if (isset($flags['in_answer'])) {
						if (strpos($givenans,$anans)!==false) {
							$correct += 1;
							$foundloc = $j;
							break 2;
						}
					} else if (isset($flags['regex'])) {
						$regexstr = '/'.str_replace('/','\/',$anans).'/'.($flags['ignore_case']?'i':'');
						if (preg_match($regexstr,$givenans)) {
							$correct += 1;
							$foundloc = $j;
							break 2;
						}
					} else {
						if (!strcmp($anans,$givenans)) {
							$correct += 1;
							$foundloc = $j;
							break 2;
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
		$score = $correct/count($anarr);
		if ($score<0) { $score = 0; }
		return ($score);
		//return $correct;
	} else if ($anstype == "essay") {
		require_once(dirname(__FILE__)."/../includes/htmLawed.php");
		$givenans = addslashes(myhtmLawed(stripslashes($givenans)));
		$givenans = preg_replace('/&(\w+;)/',"%$1",$givenans);
		$GLOBALS['partlastanswer'] = $givenans;
		if (isset($options['scoremethod']))if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$qn];} else {$scoremethod = $options['scoremethod'];}
		if (isset($scoremethod) && $scoremethod=='takeanything'  && trim($givenans)!='') {
			return 1;
		} else if (trim($givenans)=='') {
			return 0;
		} else {
			return -2;
		}
	} else if ($anstype == 'interval' || $anstype == 'calcinterval') {
		if (is_array($options['answer'])) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}
		if (isset($options['requiretimes'])) {if (is_array($options['requiretimes'])) {$requiretimes = $options['requiretimes'][$qn];} else {$requiretimes = $options['requiretimes'];}}
		if (isset($options['variables'])) {if (is_array($options['variables'])) {$variables = $options['variables'][$qn];} else {$variables = $options['variables'];}}
		if (!isset($variables)) { $variables = 'x';}
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
		if ($multi>0) { $qn = $multi*1000+$qn;}
		$ansformats = array_map('trim',explode(',',$answerformat));

		$givenans = normalizemathunicode($givenans);
		$_POST["tc$qn"] = normalizemathunicode($_POST["tc$qn"]);

		if ($anstype == 'interval') {
			$GLOBALS['partlastanswer'] = $givenans;
			$givenans = str_replace('u', 'U', $givenans);
		} else if ($anstype == 'calcinterval') {
			$GLOBALS['partlastanswer'] = $_POST["tc$qn"];
			//test for correct format, if specified
			if (checkreqtimes($_POST["tc$qn"],$requiretimes)==0) {
				return 0;
			}
			if (in_array('inequality',$ansformats)) {
				$_POST["tc$qn"] = str_replace('or', ' or ', $_POST["tc$qn"]);

				preg_match_all('/([a-zA-Z]\(\s*[a-zA-Z]\s*\)|[a-zA-Z]+)/',$_POST["tc$qn"],$matches);
				foreach ($matches[0] as $var) {
					$var = str_replace(' ','',$var);
					if (in_array($var,$mathfuncs)) { continue;}
					if ($var!= 'or' && $var!='and' && $var != $variables && $_POST["qn$qn"]!="(-oo,oo)") {
						return 0;
					}
				}
				$orarr = explode(' or ',$_POST["tc$qn"]);
				foreach ($orarr as $opt) {
					$opt = trim($opt);
					if ($opt=='DNE' || $givenans=='(-oo,oo)') {continue;} //DNE or all real numbers
					$opts = preg_split('/(<=?|>=?)/',$opt);
					foreach ($opts as $optp) {
						$optp = trim($optp);
						if ($optp==$variables) {continue;}
						if (!checkanswerformat($optp,$ansformats)) {
							return 0;
						}
					}
				}
			} else {
				$givenans = str_replace('u', 'U', $givenans);
				$_POST["tc$qn"] = str_replace('u', 'U', $_POST["tc$qn"]);
				if (in_array('list',$ansformats)) {
					$orarr = preg_split('/(?<=[\)\]]),(?=[\(\[])/',$_POST["tc$qn"]);
				} else {
					$orarr = explode('U',$_POST["tc$qn"]);
				}
				foreach ($orarr as $opt) {
					$opt = trim($opt);
					if ($opt=='DNE') {continue;}
					$opts = explode(',',substr($opt,1,strlen($opt)-2));
					if (strpos($opts[0],'oo')===false &&  !checkanswerformat($opts[0],$ansformats)) {
						return 0;
					}
					if (strpos($opts[1],'oo')===false &&  !checkanswerformat($opts[1],$ansformats)) {
						return 0;
					}
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
			if (in_array('list',$ansformats)) {
				$aarr = preg_split('/(?<=[\)\]]),(?=[\(\[])/',$anans);
				$gaarr = preg_split('/(?<=[\)\]]),(?=[\(\[])/',$givenans);
			} else {
				$aarr = explode('U',$anans);
				$gaarr = explode('U',$givenans);
			}
			if (count($aarr)!=count($gaarr)) {
				continue;
			}

			foreach ($aarr as $ansint) {
				$ansint = trim($ansint);
				$anssm = substr($ansint,0,1);
				$ansem = substr($ansint,-1);
				$ansint = substr($ansint,1,strlen($ansint)-2);
				list($anssn,$ansen) = explode(',',$ansint);
				if (!is_numeric($anssn) && strpos($anssn,'oo')===false) {
					$anssn = eval('return('.mathphp($anssn,null).');');
				}
				if (!is_numeric($ansen) && strpos($ansen,'oo')===false) {
					$ansen = eval('return('.mathphp($ansen,null).');');
				}
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
					if (strpos($anssn,'oo')!==false || !is_numeric($ganssn)) {
						$anssn = trim($anssn);
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
					if (strpos($ansen,'oo')!==false || !is_numeric($gansen)) {
						$ansen = trim($ansen);
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
			if (isset($options['grid'])) {
				if (is_array($options['grid']) && isset($options['grid'][$qn])) {
					$grid = $options['grid'][$qn];
				} else if (!is_array($options['grid'])) {
					$grid = $options['grid'];
				}
			}
			if (isset($options['snaptogrid'])) {
				if (is_array($options['snaptogrid']) && isset($options['snaptogrid'][$qn])) {
					$snaptogrid = $options['snaptogrid'][$qn];
				} else if (!is_array($options['snaptogrid'])) {
					$snaptogrid = $options['snaptogrid'];
				}
			}
			if (isset($options['answers'][$qn])) {$answers = $options['answers'][$qn];}
				else if (isset($options['answer'][$qn])) {$answers = $options['answer'][$qn];}
			if (isset($options['partweights'][$qn])) {$partweights = $options['partweights'][$qn];}
		} else {
			if (isset($options['grid'])) { $grid = $options['grid'];}
			if (isset($options['snaptogrid'])) { $snaptogrid = $options['snaptogrid'];}
			if (isset($options['answers'])) {$answers = $options['answers'];}
				else if (isset($options['answer'])) {$answers = $options['answer'];}
			if (isset($options['partweights'])) {$partweights = $options['partweights'];}
		}
		if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$qn];} else {$reltolerance = $options['reltolerance'];}}
		if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$qn];} else {$abstolerance = $options['abstolerance'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}

		if (!isset($reltolerance)) {
			if (isset($GLOBALS['CFG']['AMS']['defaultdrawtol'])) {
				$reltolerance =  $GLOBALS['CFG']['AMS']['defaultdrawtol'];
			} else {
				$reltolerance = 1;
			}
		}

		if ($multi>0) { $qn = $multi*1000+$qn;}
		$GLOBALS['partlastanswer'] = $givenans;

		$imgborder = 5; $step = 5;
		if (!isset($answerformat)) {
			$answerformat = array('line','dot','opendot');
		} else if (!is_array($answerformat)) {
			$answerformat = explode(',',$answerformat);
		}
		if ($answerformat[0]=='numberline') {
			$settings = array(-5,5,-0.5,0.5,1,0,300,50);
		} else {
			$settings = array(-5,5,-5,5,1,1,300,300);
		}
		if (isset($grid)) {
			if (!is_array($grid)) {
				$grid = array_map('trim',explode(',',$grid));
			} else if (strpos($grid[0],',')!==false) {//forgot to set as multipart?
				$grid = array();
			}
			for ($i=0; $i<count($grid); $i++) {
				if ($grid[$i]!='') {
					if (strpos($grid[$i],':')!==false) {
						$pts = explode(':',$grid[$i]);
						foreach ($pts as $k=>$v) {
							$pts[$k] = evalbasic($v);
						}
						$settings[$i] = implode(':',$pts);
					} else {
						$settings[$i] = evalbasic($grid[$i]);
					}
				}
			}
			if (strpos($settings[0],'0:')===0) {
				$settings[0] = substr($settings[0],2);
			}
			if (strpos($settings[2],'0:')===0) {
				$settings[2] = substr($settings[2],2);
			}
		}
		if ($answerformat[0]=='numberline') {
			$settings[2] = -0.5;
			$settings[3] = 0.5;
		}
		if ($snaptogrid>0) {
			list($newwidth,$newheight) = getsnapwidthheight($settings[0],$settings[1],$settings[2],$settings[3],$settings[6],$settings[7],$snaptogrid);
			if (($newwidth - $settings[6])/$settings[6]<.1) {
				$settings[6] = $newwidth;
			}
			if (($newheight- $settings[7])/$settings[7]<.1) {
				$settings[7] = $newheight;
			}
		}
		$pixelsperx = ($settings[6] - 2*$imgborder)/($settings[1]-$settings[0]);
		$pixelspery = ($settings[7] - 2*$imgborder)/($settings[3]-$settings[2]);

		$xtopix = create_function('$x',"return ((\$x - ({$settings[0]}))*($pixelsperx) + ($imgborder));");
		$ytopix = create_function('$y',"return (({$settings[7]}) - (\$y- ({$settings[2]}))*($pixelspery) - ($imgborder));");

		$anslines = array();
		$ansdots = array();
		$ansodots = array();
		$anslineptcnt = array();
		$types = array();
		$extrastuffpenalty = 0;
		$linepts = 0;
		if ((is_array($answers) && count($answers)==0) || (!is_array($answers) && $answers=='')) {
			if ($givenans==';;;;;;;;') {
				return 1;
			} else {
				return 0;
			}
		}
		if (!is_array($answers)) {
			settype($answers,"array");
		}
		if ($answerformat[0]=="polygon" || $answerformat[0]=='closedpolygon') {
			foreach ($answers as $key=>$function) {
				$function = array_map('trim',explode(',',$function));
				$pixx = (evalbasic($function[0]) - $settings[0])*$pixelsperx + $imgborder;
				$pixy = $settings[7] - (evalbasic($function[1])-$settings[2])*$pixelspery - $imgborder;
				$ansdots[$key] = array($pixx,$pixy);
			}
			$isclosed = false;
			if (abs($ansdots[0][0]-$ansdots[count($ansdots)-1][0])<.01 && abs($ansdots[0][1]-$ansdots[count($ansdots)-1][1])<.01) {
				$isclosed = true;
				array_pop($ansdots);
			}
			list($lines,$dots,$odots,$tplines,$ineqlines) = explode(';;',$givenans);
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

		} else if ($answerformat[0]=="twopoint") {
			$anscircs = array();
			$ansparabs = array();
			$ansabs = array();
			$anssqrts = array();
			$ansexps = array();
			$anscoss = array();
			$ansvecs = array();
			$ansrats = array();
			$x0 = $settings[0];
			$x1 = 1/4*$settings[1] + 3/4*$settings[0];
			$x2 = 1/2*$settings[1] + 1/2*$settings[0];
			$x3 = 3/4*$settings[1] + 1/4*$settings[0];
			$x4 = $settings[1];
			$x0p = $imgborder;
			$x1p = $xtopix($x1); //($x1 - $settings[0])*$pixelsperx + $imgborder;
			$x2p = $xtopix($x2); //($x2 - $settings[0])*$pixelsperx + $imgborder;
			$x3p = $xtopix($x3); //($x3 - $settings[0])*$pixelsperx + $imgborder;
			$x4p = $xtopix($x4); //($x4 - $settings[0])*$pixelsperx + $imgborder;
			$ymid = ($settings[2]+$settings[3])/2;
			$ymidp = $ytopix($ymid); //$settings[7] - ($ymid-$settings[2])*$pixelspery - $imgborder;
			foreach ($answers as $key=>$function) {
				if ($function=='') { continue; }
				$function = array_map('trim',explode(',',$function));
				//curves: function
				//	  function, xmin, xmax
				//dot:  x,y
				//	x,y,"closed" or "open"
				//form: function, color, xmin, xmax, startmaker, endmarker
				if (count($function)==2 || (count($function)==3 && ($function[2]=='open' || $function[2]=='closed'))) { //is dot
					$pixx = (evalbasic($function[0]) - $settings[0])*$pixelsperx + $imgborder;
					$pixy = $settings[7] - (evalbasic($function[1])-$settings[2])*$pixelspery - $imgborder;
					if (count($function)==2 || $function[2]=='closed') {
						$ansdots[$key] = array($pixx,$pixy);
					} else {
						$ansodots[$key] = array($pixx,$pixy);
					}
					continue;
				} else if ($function[0]=='vector') {
					if (count($function)>4) { // form "vector, x_start, y_start, x_end, y_end"
						$ansvecs[$key] = array('p', $xtopix($function[1]), $ytopix($function[2]), $xtopix($function[3]), $ytopix($function[4]));
					} else if (count($function)>2) {  //form "vector, dx, dy"
						$ansvecs[$key] = array('d', $function[1]*$pixelsperx, -1*$function[2]*$pixelspery);
					}
				} else if ($function[0]=='circle') {  // form "circle,x_center,y_center,radius"
					$anscircs[$key] = array(($function[1] - $settings[0])*$pixelsperx + $imgborder,$settings[7] - ($function[2]-$settings[2])*$pixelspery - $imgborder,$function[3]*$pixelsperx);
				} else if (substr($function[0],0,2)=='x=') {
					$anslines[$key] = array('x',10000,(substr($function[0],2)- $settings[0])*$pixelsperx + $imgborder );
				} else if (count($function)==3) { //line segment or ray
					$func = makepretty($function[0]);
					$func = mathphp($func,'x');
					$func = str_replace("(x)",'($x)',$func);
					$func = create_function('$x', 'return ('.$func.');');
					if ($function[1]=='-oo') { //ray to left
						$y1p = $ytopix($func(floatval($function[2])-1));
						$y2p = $ytopix($func(floatval($function[2])));
						$ansvecs[$key] = array('r', $xtopix($function[2]), $y2p, $xtopix(floatval($function[2])-1), $y1p);
					} else if ($function[2]=='oo') { //ray to right
						$y1p = $ytopix($func(floatval($function[1])));
						$y2p = $ytopix($func(floatval($function[1])+1));
						$ansvecs[$key] = array('r', $xtopix($function[1]), $y1p, $xtopix(floatval($function[1])+1), $y2p);
					} else { //line seg
						$y1p = $ytopix($func(floatval($function[1])));
						$y2p = $ytopix($func(floatval($function[2])));
						$ansvecs[$key] = array('ls', $xtopix($function[1]), $y1p, $xtopix($function[2]), $y2p);
					}
				} else {
					$func = makepretty($function[0]);
					$func = mathphp($func,'x');
					$func = str_replace("(x)",'($x)',$func);
					$func = create_function('$x', 'return ('.$func.');');

					$y1 = @$func($x1);
					$y2 = @$func($x2);
					$y3 = @$func($x3);
					$y1p = $settings[7] - ($y1-$settings[2])*$pixelspery - $imgborder;
					$y2p = $settings[7] - ($y2-$settings[2])*$pixelspery - $imgborder;
					$y3p = $settings[7] - ($y3-$settings[2])*$pixelspery - $imgborder;
					$yop = $imgborder + $settings[3]*$pixelspery;
					if ($settings[0]<0 && $settings[1]>0) {
						$xop = $xtopix(0);
					} else {
						$xop = $x2p;
					}
					$settings[7] - ($y1-$settings[2])*$pixelspery - $imgborder;
					if (strpos($function[0],'abs')!==false) { //is abs
						$y0 = $func($x0);
						$y4 = $func($x4);
						$y0p = $settings[7] - ($y0-$settings[2])*$pixelspery - $imgborder;
						$y4p = $settings[7] - ($y4-$settings[2])*$pixelspery - $imgborder;
						if (abs(($y2-$y1)-($y1-$y0))<1e-9) { //if first 3 points are colinear
							$slope = ($y2p-$y1p)/($x2p-$x1p);
						} else if (abs(($y4-$y3)-($y3-$y2))<1e-9) { //if last 3 points are colinear
							$slope = -1*($y4p-$y3p)/($x4p-$x3p);  //mult by -1 to get slope on left
						}
						if ($slope==0) {
							$anslines[$key] = array('y',$slope,$y2p);
						} else {
							$xip = ($slope*($x4p+$x0p)+$y4p-$y0p)/(2*$slope);  //x value of "vertex"
							$ansabs[$key] = array($xip,$slope*($xip-$x0p)+$y0p, $slope);
						}
					} else if (($p = strpos($function[0],'sqrt('))!==false) { //is sqrt
						$nested = 1;
						for ($i=$p+5;$i<strlen($function[0]);$i++) {
							if ($function[0][$i]=='(') {$nested++;}
							else if ($function[0][$i]==')') {$nested--;}
							if ($nested==0) {break;}
						}
						if ($nested==0) {
							$infunc = makepretty(substr($function[0],$p+5,$i-$p-5));
							$infunc = mathphp($infunc,'x');
							$infunc = str_replace("(x)",'($x)',$infunc);
							$infunc = create_function('$x', 'return ('.$infunc.');');
							$y0 = $infunc(0);
							$y1 = $infunc(1);
							$xint = -$y0/($y1-$y0);
							$xintp = ($xint - $settings[0])*$pixelsperx + $imgborder;
							$yint = $func($xint);
							$yintp = $settings[7] - ($yint-$settings[2])*$pixelspery - $imgborder;
							$secx = $xint + ($x4-$x0)/5*(($y1>$y0)?1:-1);  //over 1/5 of grid width
							$secy = $func($secx);
							$secyp = $settings[7] - ($secy-$settings[2])*$pixelspery - $imgborder;
							$anssqrts[$key] = array($xintp,$yintp,$secyp);
						}
					} else if (($p = strpos($function[0],'cos'))!==false || ($q = strpos($function[0],'sin'))!==false) { //is sin/cos
						if ($p===false) { $p = $q;}
						$nested = 1;
						for ($i=$p+4;$i<strlen($function[0]);$i++) {
							if ($function[0][$i]=='(') {$nested++;}
							else if ($function[0][$i]==')') {$nested--;}
							if ($nested==0) {break;}
						}
						if ($nested==0) {
							$infunc = makepretty(substr($function[0],$p+4,$i-$p-4));
							$infunc = mathphp($infunc,'x');
							$infunc = str_replace("(x)",'($x)',$infunc);
							$infunc = create_function('$x', 'return ('.$infunc.');');
							$y0 = $infunc(0);
							$y1 = $infunc(1);
							$period = 2*M_PI/($y1-$y0); //slope of inside function
							$xint = -$y0/($y1-$y0);
							if (strpos($function[0],'sin')!==false) {
								$xint += $period/4;
							}
							$secx = $xint + $period/2;
							$xintp = ($xint - $settings[0])*$pixelsperx + $imgborder;
							$secxp = ($secx - $settings[0])*$pixelsperx + $imgborder;
							$yint = $func($xint);
							$yintp = $settings[7] - ($yint-$settings[2])*$pixelspery - $imgborder;
							$secy = $func($secx);
							$secyp = $settings[7] - ($secy-$settings[2])*$pixelspery - $imgborder;
							if ($yintp>$secyp) {
								$anscoss[$key] = array($xintp,$secxp,$yintp,$secyp);
							} else {
								$anscoss[$key] = array($secxp,$xintp,$secyp,$yintp);
							}
						}
					} else if (preg_match('/\^[^2]/',$function[0])) { //exponential

						$base = safepow(($yop-$y3p)/($yop-$y1p), 1/($x3p-$x1p));
						$str = ($yop-$y3p)/safepow($base,$x3p-$xop);
						$ansexps[$key] = array($str,$base);

					} else if (strpos($function[0],'/x')!==false || preg_match('|/\([^\)]*x|', $function[0])) {
						if ($y1===false) {
							$x1 = $x1+.2*($x2-$x1);
							$y1 = @$func($x1);
							$y1p = $settings[7] - ($y1-$settings[2])*$pixelspery - $imgborder;
						} else if ($y2===false) {
							$x2 = $x2+.2*($x2-$x1);
							$y2 = @$func($x2);
							$y2p = $settings[7] - ($y2-$settings[2])*$pixelspery - $imgborder;
						} else if ($y3===false) {
							$x3 = $x3-.2*($x2-$x1);
							$y3 = @$func($x3);
							$y3p = $settings[7] - ($y3-$settings[2])*$pixelspery - $imgborder;
						}
						$h = ($x1*$x2*$y1-$x1*$x2*$y2-$x1*$x3*$y1+$x1*$x3*$y3+$x2*$x3*$y2-$x2*$x3*$y3)/(-$x1*$y2+$x1*$y3+$x2*$y1-$x2*$y3-$x3*$y1+$x3*$y2);
						$k = (($x1*$y1-$x2*$y2)-$h*($y1-$y2))/($x1-$x2);
						$c = ($y1-$k)*($x1-$h);

						$hp = ($h - $settings[0])*$pixelsperx + $imgborder;
						$kp = $settings[7] - ($k-$settings[2])*$pixelspery - $imgborder;
						//eval at point on graph closest to (h,k), at h+sqrt(c)
						$np = $settings[7] - (@$func($h+sqrt(abs($c)))-$settings[2])*$pixelspery - $imgborder;
						$ansrats[$key] = array($hp,$kp,$np);

					} else if (abs(($y3-$y2)-($y2-$y1))<1e-9) {
						//colinear
						$slope = ($y2p-$y1p)/($x2p-$x1p);
						if (abs($slope)>1.4) {
							//use x value at ymid
							$anslines[$key] = array('x',$slope,$x1p+($ymidp-$y1p)/$slope);
						} else {
							//use y value at x2
							$anslines[$key] = array('y',$slope,$y2p);
						}
					} else {
						//assume parabolic for now
						$denom = ($x1p - $x2p)*($x1p - $x3p)*($x2p - $x3p);
						$A = ($x3p * ($y2p - $y1p) + $x2p * ($y1p - $y3p) + $x1p * ($y3p - $y2p)) / $denom;
						$B = ($x3p*$x3p * ($y1p - $y2p) + $x2p*$x2p * ($y3p - $y1p) + $x1p*$x1p * ($y2p - $y3p)) / $denom;
						$C = ($x2p * $x3p * ($x2p - $x3p) * $y1p + $x3p * $x1p * ($x3p - $x1p) * $y2p + $x1p * $x2p * ($x1p - $x2p) * $y3p) / $denom;
						$xv = -$B/(2*$A);
						$yv = $C-$B*$B/(4*$A);
						//TODO:  adjust 20px to be based on drawing window and grid
						//   maybe ~1 grid units?
						$xt = -$B/(2*$A)+20;
						$yatxt = $A*$xt*$xt+$B*$xt+$C;
						if (abs($yatxt - $yv)<20) {
							$xatyt = sign($A)*sqrt(abs(20/$A))+$xv;
							$ansparabs[$key] = array('x', $xv, $yv, $xatyt);
						} else {
							//use vertex and y value at x of vertex + 20 pixels
							$ansparabs[$key] = array('y', $xv, $yv, $yatxt);
						}
						//**finish me!!
					}
				}
			}
			list($lines,$dots,$odots,$tplines,$ineqlines) = explode(';;',$givenans);
			$lines = array();
			$parabs = array();
			$circs = array();
			$abs = array();
			$sqrts = array();
			$coss = array();
			$exps = array();
			$vecs = array();
			$rats = array();
			if ($tplines=='') {
				$tplines = array();
			} else {
				$tplines = explode('),(', substr($tplines,1,strlen($tplines)-2));
				foreach ($tplines as $k=>$val) {
					$pts = explode(',',$val);
					if ($pts[0]==5) {
						//line
						if ($pts[3]==$pts[1]) {
							$lines[] = array('x',10000,$pts[1]);
						} else {
							$slope = ($pts[4]-$pts[2])/($pts[3]-$pts[1]);
							if (abs($slope)>100) {$slope = 10000;}
							if (abs($slope)>1) {
								$lines[] = array('x',$slope,$pts[1]+($ymidp-$pts[2])/$slope,$pts[2]+($x2p-$pts[1])*$slope);
							} else {
								$lines[] = array('y',$slope,$pts[2]+($x2p-$pts[1])*$slope);
							}
						}
					} else if ($pts[0]==5.2) {
						$vecs[] = array($pts[1],$pts[2],$pts[3],$pts[4],'r');
					} else if ($pts[0]==5.3) {
						$vecs[] = array($pts[1],$pts[2],$pts[3],$pts[4],'ls');
					} else if ($pts[0]==5.4) {
						$vecs[] = array($pts[1],$pts[2],$pts[3],$pts[4],'v');
					} else if ($pts[0]==6) {
						//parab
						//for y at x+20, y=a(x-h)^2+k is y=a*20^2+k
						//for x at y+-20, y=a(x-h)^2+k
						//                20 = a(x-h)^2
						//                abs(20/a) = (x-h)^2
						if ($pts[4]==$pts[2]) {
							$lines[] = array('y',0,$pts[4]);
						} else if ($pts[3]!=$pts[1]) {
							$a = ($pts[4]-$pts[2])/(($pts[3]-$pts[1])*($pts[3]-$pts[1]));
							$y = $pts[2]+$a*400;
							$x = $pts[1]+sign($a)*sqrt(abs(20/$a));
							$parabs[] = array($pts[1],$pts[2],$y,$x);
						}
					} else if ($pts[0]==6.5) {//sqrt
						$flip = ($pts[3] < $pts[1])?-1:1;
						$stretch = ($pts[4] - $pts[2])/sqrt($flip*($pts[3]-$pts[1]));

						$secxp = $pts[1] + ($x4p-$x0p)/5*$flip;  //over 1/5 of grid width
						$secyp = $stretch*sqrt($flip*($secxp - $pts[1]))+($pts[2]);
						$sqrts[] = array($pts[1],$pts[2],$secyp);
					} else if ($pts[0]==7) {
						//circle
						$circs[] = array($pts[1],$pts[2],sqrt(($pts[3]-$pts[1])*($pts[3]-$pts[1]) + ($pts[4]-$pts[2])*($pts[4]-$pts[2])));
					} else if ($pts[0]==8) {
						//abs
						if ($pts[1]==$pts[3]) {
							if ($pts[4]>$pts[2]) {
								$slope = -10000000000;
							} else {
								$slope = 10000000000;
							}
						} else {
							$slope = ($pts[4]-$pts[2])/($pts[3]-$pts[1]);
							if ($pts[3]>$pts[1]) {//we just found slope on right, so reverse for slope on left
								$slope *= -1;
							}
						}
						$abs[] = array($pts[1],$pts[2], $slope);
					} else if ($pts[0]==8.3) {
						$adjy2 = $yop - $pts[4];
						$adjy1 = $yop - $pts[2];
						if ($adjy1*$adjy2>0 && $pts[1]!=$pts[3]) {
							$base = safepow($adjy2/$adjy1,1/($pts[3]-$pts[1]));
							if (abs($pts[1]-$xop)<abs($pts[3]-$xop)) {
								$str = $adjy1/safepow($base,$pts[1]-$xop);
							} else {
								$str = $adjy2/safepow($base,$pts[3]-$xop);
							}
							//$exps[] = array($str,$base);
							$exps[] = array($pts[1]-$xop, $adjy1, $pts[3]-$xop, $adjy2, $base);
						}
					} else if ($pts[0]==8.2) { //rational
						if ($pts[1]!=$pts[3] && $pts[2]!=$pts[4]) {
							$stretch = ($pts[3]-$pts[1])*($pts[4]-$pts[2]);
							$yp = $pts[2]+(($stretch>0)?1:-1)*sqrt(abs($stretch));

							$rats[] = array($pts[1],$pts[2],$yp);
						}
					} else if ($pts[0]==9 || $pts[0]==9.1) {
						if ($pts[0]==9.1) {
							$pts[1] -= ($pts[3] - $pts[1]);
							$pts[2] -= ($pts[4] - $pts[2]);
						}
						if ($pts[4]>$pts[2]) {
							$coss[] = array($pts[3],$pts[1],$pts[4],$pts[2]);
						} else {
							$coss[] = array($pts[1],$pts[3],$pts[2],$pts[4]);
						}
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

			$scores = array();
			if ((count($dots)+count($odots))==0) {
				$extradots = 0;
			} else {
				$extradots = max((count($dots) + count($odots) - count($ansdots) - count($ansodots))/(count($dots)+count($odots)),0);
			}

			foreach ($ansdots as $key=>$ansdot) {
				$scores[$key] = 0;
				for ($i=0; $i<count($dots); $i++) {
					if (($dots[$i][0]-$ansdot[0])*($dots[$i][0]-$ansdot[0]) + ($dots[$i][1]-$ansdot[1])*($dots[$i][1]-$ansdot[1]) <= 25*max(1,$reltolerance)) {
						$scores[$key] = 1-$extradots;
						break;
					}
				}
			}
			foreach ($ansodots as $key=>$ansodot) {
				$scores[$key] = 0;
				for ($i=0; $i<count($odots); $i++) {
					if (($odots[$i][0]-$ansodot[0])*($odots[$i][0]-$ansodot[0]) + ($odots[$i][1]-$ansodot[1])*($odots[$i][1]-$ansodot[1]) <= 25*max(1,$reltolerance)) {
						$scores[$key] = 1-$extradots;
						break;
					}
				}
			}
			$deftol = .1;
			$defpttol = 5;
			foreach ($anslines as $key=>$ansline) {
				$scores[$key] = 0;
				for ($i=0; $i<count($lines); $i++) {
					//check slope
					$toladj = pow(10,-1-6*abs($ansline[1]));
					if (abs($ansline[1]-$lines[$i][1])/(abs($ansline[1])+$toladj)>$deftol*$reltolerance) {
						continue;
					}
					if ($ansline[0]!=$lines[$i][0]) {
						if (abs(abs($ansline[1])-1)<.4) {
							//check intercept
							if (abs($ansline[2]-$lines[$i][3])>$defpttol*$reltolerance) {
								continue;
							}
						} else {
							continue;
						}
					} else {
						if (abs($ansline[2]-$lines[$i][2])>$defpttol*$reltolerance) {
							continue;
						}
					}
					$scores[$key] = 1;
					break;
				}
			}
			foreach ($anscircs as $key=>$anscirc) {
				$scores[$key] = 0;
				for ($i=0; $i<count($circs); $i++) {
					if (abs($anscirc[0]-$circs[$i][0])>$defpttol*$reltolerance) {
						continue;
					}
					if (abs($anscirc[1]-$circs[$i][1])>$defpttol*$reltolerance) {
						continue;
					}
					if (abs($anscirc[2]-$circs[$i][2])>$defpttol*$reltolerance) {
						continue;
					}
					$scores[$key] = 1;
					break;
				}
			}

			foreach ($ansvecs as $key=>$ansvec) {
				$scores[$key] = 0;
				if ($ansvec[0]=='p') {  //point
					for ($i=0; $i<count($vecs); $i++) {
						if ($vecs[$i][4]!='v') {continue;}
						if (abs($ansvec[1]-$vecs[$i][0])>$defpttol*$reltolerance) {
							continue;
						}
						if (abs($ansvec[2]-$vecs[$i][1])>$defpttol*$reltolerance) {
							continue;
						}
						if (abs($ansvec[3]-$vecs[$i][2])>$defpttol*$reltolerance) {
							continue;
						}
						if (abs($ansvec[4]-$vecs[$i][3])>$defpttol*$reltolerance) {
							continue;
						}
						$scores[$key] = 1;
						break;
					}
				} else if ($ansvec[0]=='r') {  //ray
					for ($i=0; $i<count($vecs); $i++) {
						if ($vecs[$i][4]!='r') {continue;}
						//make sure base point matches
						if (abs($ansvec[1]-$vecs[$i][0])>$defpttol*$reltolerance) {
							continue;
						}
						if (abs($ansvec[2]-$vecs[$i][1])>$defpttol*$reltolerance) {
							continue;
						}

						//compare slopes
						$correctdx = $ansvec[3] - $ansvec[1];
						$correctdy = $ansvec[4] - $ansvec[2];
						$studx = $vecs[$i][2] - $vecs[$i][0];
						$study = $vecs[$i][3] - $vecs[$i][1];

						//find angle between correct ray and stu ray
						$cosang = ($studx*$correctdx+$study*$correctdy)/(sqrt($studx*$studx+$study*$study)*sqrt($correctdx*$correctdx+$correctdy*$correctdy));
						$ang = acos($cosang)*57.2957795;
						if (abs($ang)>1.4*$reltolerance) {
							continue;
						}

						/*
						slope based grading
						if (abs($correctdy)>abs($correctdx)) {
							$m = $correctdx/$correctdy;
							$stum = $studx/$study;
						} else {
							$m = $correctdy/$correctdx;
							$stum =$study/$studx;
						}
						$toladj = pow(10,-1-6*abs($m));
						if (abs($m-$stum)/abs($m+$toladj)>$deftol*$reltolerance) {
							continue;
						}
						*/
						$scores[$key] = 1;
						break;
					}
				} else if ($ansvec[0]=='ls') { //line segment
					for ($i=0; $i<count($vecs); $i++) {
						if ($vecs[$i][4]!='ls') {continue;}
						if (abs($ansvec[1]-$vecs[$i][0])>$defpttol*$reltolerance && abs($ansvec[1]-$vecs[$i][2])>$defpttol*$reltolerance) {
							continue; //ans x1 doesn't match either vec x
						}
						if (abs($ansvec[1]-$vecs[$i][0])<=$defpttol*$reltolerance) { //x1 of ans matched first vec x
							if (abs($ansvec[2]-$vecs[$i][1])>$defpttol*$reltolerance) {
								continue;
							}
							if (abs($ansvec[3]-$vecs[$i][2])>$defpttol*$reltolerance) {
								continue;
							}
							if (abs($ansvec[4]-$vecs[$i][3])>$defpttol*$reltolerance) {
								continue;
							}
						} else {
							if (abs($ansvec[2]-$vecs[$i][3])>$defpttol*$reltolerance) {
								continue;
							}
							if (abs($ansvec[3]-$vecs[$i][0])>$defpttol*$reltolerance) {
								continue;
							}
							if (abs($ansvec[4]-$vecs[$i][1])>$defpttol*$reltolerance) {
								continue;
							}
						}
						$scores[$key] = 1;
						break;
					}
				} else {  //direction vector
					for ($i=0; $i<count($vecs); $i++) {
						if ($vecs[$i][4]!='v') {continue;}
						if (abs($ansvec[1]-($vecs[$i][2] - $vecs[$i][0]))>$defpttol*$reltolerance) {
							continue;
						}
						if (abs($ansvec[2]-($vecs[$i][3] - $vecs[$i][1]))>$defpttol*$reltolerance) {
							continue;
						}
						$scores[$key] = 1;
						break;
					}
				}
			}

			foreach ($ansparabs as $key=>$ansparab) {
				$scores[$key] = 0;
				for ($i=0; $i<count($parabs); $i++) {
					if (abs($ansparab[1]-$parabs[$i][0])>$defpttol*$reltolerance) {
						continue;
					}
					if (abs($ansparab[2]-$parabs[$i][1])>$defpttol*$reltolerance) {
						continue;
					}
					if ($ansparab[0]=='x') { //compare x at yv+-20
						if (abs($ansparab[3]-$parabs[$i][3])>$defpttol*$reltolerance) {
							continue;
						}
					} else { //compare y at xv+20
						if (abs($ansparab[3]-$parabs[$i][2])>$defpttol*$reltolerance) {
							continue;
						}
					}
					$scores[$key] = 1;
					break;
				}
			}
			foreach ($anssqrts as $key=>$anssqrt) {
				$scores[$key] = 0;
				for ($i=0; $i<count($sqrts); $i++) {
					if (abs($anssqrt[0]-$sqrts[$i][0])>$defpttol*$reltolerance) {
						continue;
					}
					if (abs($anssqrt[1]-$sqrts[$i][1])>$defpttol*$reltolerance) {
						continue;
					}
					if (abs($anssqrt[2]-$sqrts[$i][2])>$defpttol*$reltolerance) {
						continue;
					}
					$scores[$key] = 1;
					break;
				}
			}
			foreach ($ansrats as $key=>$ansrat) {
				$scores[$key] = 0;
				for ($i=0; $i<count($rats); $i++) {
					if (abs($ansrat[0]-$rats[$i][0])>$defpttol*$reltolerance) {
						continue;
					}
					if (abs($ansrat[1]-$rats[$i][1])>$defpttol*$reltolerance) {
						continue;
					}
					if (sqrt(2)*abs($ansrat[2]-$rats[$i][2])>$defpttol*$reltolerance) {
						continue;
					}
					$scores[$key] = 1;
					break;
				}
			}
			foreach ($ansexps as $key=>$ansexp) {
				$scores[$key] = 0;
				for ($i=0; $i<count($exps); $i++) {
					//if (abs($ansexp[0]-$exps[$i][0])>$defpttol*$reltolerance) {
					//	continue;
					//}
					if (abs($ansexp[1]-$exps[$i][4])/(abs($ansexp[1]-1)+1e-18)>$deftol*$reltolerance) {
						continue;
					}
					//check left point if base>1
					if ($ansexp[1]>1 && abs($ansexp[0]*safepow($ansexp[1],$exps[$i][0]) - $exps[$i][1]) >$defpttol*$reltolerance) {
						continue;
					}
					//check right point if base<=
					if ($ansexp[1]<=1 && abs($ansexp[0]*safepow($ansexp[1],$exps[$i][2]) - $exps[$i][3]) >$defpttol*$reltolerance) {
						continue;
					}

					$scores[$key] = 1;
					break;
				}
			}
			foreach ($anscoss as $key=>$anscos) {
				$scores[$key] = 0;
				for ($i=0; $i<count($coss); $i++) {
					$per = abs($anscos[0] - $anscos[1])*2;
					$adjdiff = abs($anscos[0]-$coss[$i][0]);
					$adjdiff = abs($adjdiff - $per*round($adjdiff/$per));
					if ($adjdiff>$defpttol*$reltolerance) {
						continue;
					}
					$adjdiff = abs($anscos[1]-$coss[$i][1]);
					$adjdiff = abs($adjdiff - $per*round($adjdiff/$per));
					if ($adjdiff>$defpttol*$reltolerance) {
						continue;
					}
					if (abs($anscos[2]-$coss[$i][2])>$defpttol*$reltolerance) {
						continue;
					}
					if (abs($anscos[3]-$coss[$i][3])>$defpttol*$reltolerance) {
						continue;
					}
					$scores[$key] = 1;
					break;
				}
			}
			foreach ($ansabs as $key=>$aabs) {
				$scores[$key] = 0;
				for ($i=0; $i<count($abs); $i++) {
					if (abs($aabs[0]-$abs[$i][0])>$defpttol*$reltolerance) {
						continue;
					}
					if (abs($aabs[1]-$abs[$i][1])>$defpttol*$reltolerance) {
						continue;
					}
					//check slope
					$toladj = pow(10,-1-6*abs($aabs[2]));
					if (abs($aabs[2]-$abs[$i][2])/(abs($aabs[2])+$toladj)>$deftol*$reltolerance) {
						continue;
					}
					$scores[$key] = 1;
					break;
				}
			}
			$extrastuffpenalty = max((count($tplines)-count($answers))/(max(count($answers),count($tplines))),0);

		} else if ($answerformat[0]=="inequality") {
			list($lines,$dots,$odots,$tplines,$ineqlines) = explode(';;',$givenans);
			/*$x1 = 1/3*$settings[0] + 2/3*$settings[1];
			$x2 = 2/3*$settings[0] + 1/3*$settings[1];
			$x1p = ($x1 - $settings[0])*$pixelsperx + $imgborder;
			$x2p = ($x2 - $settings[0])*$pixelsperx + $imgborder;
			$ymid = ($settings[2]+$settings[3])/2;
			$ymidp = $settings[7] - ($ymid-$settings[2])*$pixelspery - $imgborder;*/
			$x1 = 1/4*$settings[1] + 3/4*$settings[0];
			$x2 = 1/2*$settings[1] + 1/2*$settings[0];
			$x3 = 3/4*$settings[1] + 1/4*$settings[0];
			$x1p = ($x1 - $settings[0])*$pixelsperx + $imgborder;
			$x2p = ($x2 - $settings[0])*$pixelsperx + $imgborder;
			$x3p = ($x3 - $settings[0])*$pixelsperx + $imgborder;
			$ymid = ($settings[2]+$settings[3])/2;
			$ymidp = $settings[7] - ($ymid-$settings[2])*$pixelspery - $imgborder;
			foreach ($answers as $key=>$function) {
				if ($function=='') { continue; }
				$function = array_map('trim',explode(',',$function));
				if ($function[0]{0}=='x' && ($function[0]{1}=='<' || $function[0]{1}=='>')) {
					$isxequals = true;
					$function[0] = substr($function[0],1);
				} else {
					$isxequals = false;
				}
				if ($function[0]{1}=='=') {
					$type = 10;
					$c = 2;
				} else {
					$type = 10.2;
					$c = 1;
				}
				$dir = $function[0]{0};
				if ($isxequals) {
					$anslines[$key] = array('x',$dir,$type,-10000,(substr($function[0],$c)- $settings[0])*$pixelsperx + $imgborder );
				} else {
					$func = makepretty(substr($function[0],$c));
					$func = mathphp($func,'x');
					$func = str_replace("(x)",'($x)',$func);
					$func = create_function('$x', 'return ('.$func.');');
					$y1 = $func($x1);
					$y2 = $func($x2);
					$y3 = $func($x3);
					$y1p = $settings[7] - ($y1-$settings[2])*$pixelspery - $imgborder;
					$y2p = $settings[7] - ($y2-$settings[2])*$pixelspery - $imgborder;
					$y3p = $settings[7] - ($y3-$settings[2])*$pixelspery - $imgborder;
					$denom = ($x1p - $x2p)*($x1p - $x3p)*($x2p - $x3p);
					$A = ($x3p * ($y2p - $y1p) + $x2p * ($y1p - $y3p) + $x1p * ($y3p - $y2p)) / $denom;
					if(abs($A)>1e-5){//quadratic inequality:  Contributed by Cam Joyce
						if($type == 10){//switch to quadratic
							$type = 10.3;
						}
						else{
							$type = 10.4;
						}
						$B = ($x3p*$x3p * ($y1p - $y2p) + $x2p*$x2p * ($y3p - $y1p) + $x1p*$x1p * ($y2p - $y3p)) / $denom;
						$C = ($x2p * $x3p * ($x2p - $x3p) * $y1p + $x3p * $x1p * ($x3p - $x1p) * $y2p + $x1p * $x2p * ($x1p - $x2p) * $y3p) / $denom;
						$anslines[$key] = array('y',$dir,$type,$A,-$B/(2*$A),$C-$B*$B/(4*$A));
					} else{//linear inequality
						$slope = ($y2p-$y1p)/($x2p-$x1p);
						if (abs($slope)>1.4) {
							//use x value at ymid
							$anslines[$key] = array('x',$dir,$type,$slope,$x1p+($ymidp-$y1p)/$slope);
						} else {
							//use y value at x2
							$anslines[$key] = array('y',$dir,$type,$slope,$y2p);
						}
					}
				}
			}
			if ($ineqlines=='') {
				$ineqlines = array();
			} else {
				$ineqlines = explode('),(', substr($ineqlines,1,strlen($ineqlines)-2));
				foreach ($ineqlines as $k=>$val) {
					$pts = explode(',',$val);
					if($pts[0]<10.3){//linear
						if ($pts[3]==$pts[1]) {
							$slope = 10000;
						} else {
							$slope = ($pts[4]-$pts[2])/($pts[3]-$pts[1]);
						}
						if (abs($slope)>50) {
							if ($pts[5]>$pts[3]) {
								$dir = '>';
							} else {
								$dir = '<';
							}
							$ineqlines[$k] = array('x',$dir,$pts[0],-10000,$pts[1]);

						} else {

							$yatpt5 = $slope*($pts[5] - $pts[1]) + $pts[2];
							if ($yatpt5 < $pts[6]) {
								$dir = '<';
							} else {
								$dir = '>';
							}
							if (abs($slope)>50) {$slope = -10000;}
							if (abs($slope)>1) {
								$ineqlines[$k] = array('x',$dir,$pts[0],$slope,$pts[1]+($ymidp-$pts[2])/$slope,$pts[2]+($x2p-$pts[1])*$slope);
							} else {
								$ineqlines[$k] = array('y',$dir,$pts[0],$slope,$pts[2]+($x2p-$pts[1])*$slope);
							}
						}
					} else{//quadratic
						$aUser = ($pts[4] - $pts[2])/(($pts[3]-$pts[1])*($pts[3]-$pts[1]));
						$yatpt5 = $aUser*($pts[5]-$pts[1])*($pts[5]-$pts[1])+$pts[2];
						if($yatpt5 < $pts[6]){
							$dir = '<';
						} else {
							$dir = '>';
						}
						$ineqlines[$k] = array('y',$dir,$pts[0],$aUser,$pts[1],$pts[2]);
					}
				}
			}
			$scores = array();
			$deftol = .1;
			$defpttol = 5;

			foreach ($anslines as $key=>$ansline) {
				$scores[$key] = 0;
				for ($i=0; $i<count($ineqlines); $i++) {
					if ($ansline[2]!=$ineqlines[$i][2]) { continue;}
					if ($ansline[1]!=$ineqlines[$i][1]) { continue;}
					if($ansline[2] < 10.3){//linear inequality
						//check slope
						$toladj = pow(10,-1-6*abs($ansline[3]));
						$relerr = abs($ansline[3]-$ineqlines[$i][3])/(abs($ansline[3])+$toladj);
						if ($relerr>$deftol*$reltolerance) {
							continue;
						}
						if ($ansline[0]!=$ineqlines[$i][0]) {
							if (abs(abs($ansline[3])-1)<.4) {
								//check intercept
								if (abs($ansline[4]-$ineqlines[$i][5])>$defpttol*$reltolerance) {
									continue;
								}
							} else {
								continue;
							}
						} else {
							if (abs($ansline[4]-$ineqlines[$i][4])>$defpttol*$reltolerance) {
								continue;
							}
						}
						$scores[$key] = 1;
						break;
					} else {//quadratic inequality
						//check values in y = a(x-p)+q
						$toladj = pow(10,-1-6*abs($ansline[3]));
						$relerr = abs($ansline[3]-$ineqlines[$i][3])/(abs($ansline[3])+$toladj);
						if ($relerr>$deftol*$reltolerance) {
							continue;
						}
						if (abs($ansline[4]-$ineqlines[$i][4])>$defpttol*$reltolerance) {
							continue;
						}
						if (abs($ansline[5]-$ineqlines[$i][5])>$defpttol*$reltolerance) {
							continue;
						}
						$scores[$key] = 1;
						break;
					}
				}
			}
			$extrastuffpenalty = max((count($ineqlines)-count($answers))/(max(count($answers),count($ineqlines))),0);

		} else if ($answerformat[0]=='numberline') {
			foreach ($answers as $key=>$function) {
				if ($function=='') { continue; }
				$function = array_map('trim',explode(',',$function));
				if (count($function)==2 || (count($function)==3 && ($function[2]=='open' || $function[2]=='closed'))) { //is dot
					$pixx = ($function[0] - $settings[0])*$pixelsperx + $imgborder;
					$pixy = $settings[7] - ($function[1]-$settings[2])*$pixelspery - $imgborder;
					if (count($function)==2 || $function[2]=='closed') {
						$ansdots[$key] = array($pixx,$pixy);
					} else {
						$ansodots[$key] = array($pixx,$pixy);
					}
				} else {
					$xminpix = round(max(1,($function[1] - $settings[0])*$pixelsperx + $imgborder));
					$xmaxpix = round(min($settings[6]-1,($function[2] - $settings[0])*$pixelsperx + $imgborder));
					$anslines[$key] = array($xminpix,$xmaxpix);
				}
			}

			list($lines,$dots,$odots,$tplines,$ineqlines) = explode(';;',$givenans);

			if ($lines=='') {
				$lines = array();
			} else {
				$lines = explode(';',$lines);
				foreach ($lines as $k=>$line) {
					$lines[$k] = explode('),(',substr($line,1,strlen($line)-2));
					$minp = explode(',', $lines[$k][0]);
					$maxp = explode(',', $lines[$k][count($lines[$k])-1]);
					$lines[$k] = array(min($minp[0], $maxp[0]), max($minp[0], $maxp[0]));
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

			$scores = array();
			if ((count($dots)+count($odots))==0) {
				$extradots = 0;
			} else {
				$extradots = max((count($dots) + count($odots) - count($ansdots) - count($ansodots))/(count($dots)+count($odots)),0);
			}
			$defpttol = 5;
			foreach ($ansdots as $key=>$ansdot) {
				$scores[$key] = 0;
				for ($i=0; $i<count($dots); $i++) {
					if (($dots[$i][0]-$ansdot[0])*($dots[$i][0]-$ansdot[0]) + ($dots[$i][1]-$ansdot[1])*($dots[$i][1]-$ansdot[1]) <= $defpttol*$defpttol*max(1,$reltolerance)) {
						$scores[$key] = 1-$extradots;
						break;
					}
				}
			}
			foreach ($ansodots as $key=>$ansodot) {
				$scores[$key] = 0;
				for ($i=0; $i<count($odots); $i++) {
					if (($odots[$i][0]-$ansodot[0])*($odots[$i][0]-$ansodot[0]) + ($odots[$i][1]-$ansodot[1])*($odots[$i][1]-$ansodot[1]) <= $defpttol*$defpttol*max(1,$reltolerance)) {
						$scores[$key] = 1-$extradots;
						break;
					}
				}
			}

			foreach ($anslines as $key=>$ansline) {
				$scores[$key] = 0;
				for ($i=0; $i<count($lines); $i++) {
					if (abs($ansline[0]-$lines[$i][0])>$defpttol*$reltolerance) {
						continue;
					}
					if (abs($ansline[1]-$lines[$i][1])>$defpttol*$reltolerance) {
						continue;
					}
					$scores[$key] = 1;
					break;
				}
			}
			$extrastuffpenalty = max((count($lines)-count($anslines))/(max(count($answers),count($anslines))),0);

		} else {
			//not polygon or twopoint, continue with regular grading
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
					$pixx = (evalbasic($function[0]) - $settings[0])*$pixelsperx + $imgborder;
					$pixy = $settings[7] - (evalbasic($function[1])-$settings[2])*$pixelspery - $imgborder;
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
				$xminpix = round(max(2*$imgborder,($function[1] - $settings[0])*$pixelsperx + $imgborder));
				$xmaxpix = round(min($settings[6]-2*$imgborder,($function[2] - $settings[0])*$pixelsperx + $imgborder));
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
			list($lines,$dots,$odots,$tplines,$ineqlines) = explode(';;',$givenans);
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
					$leftx = round(max(min($line[$i][0],$line[$i-1][0]), 2*$imgborder));
					$rightx = round(min(max($line[$i][0],$line[$i-1][0]), $settings[6]-2*$imgborder));
					if ($line[$i][0]==$line[$i-1][0]) {
						$m = 9999;
					} else {
						$m = ($line[$i][1] - $line[$i-1][1])/($line[$i][0]-$line[$i-1][0]);
					}
					for ($k = ceil($leftx/$step); $k*$step<=$rightx; $k++) {
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
			//if ($GLOBALS['myrights']==100) {
			//	print_r($anslines);
			//	print_r($linedata);
			//}
			foreach ($anslines as $key=>$answerline) {
				if ($stcnts[$key]<2) {
					$stdevs[$key] = 0;
				} else {
					$stdevs[$key] = sqrt($stdevs[$key]/($stcnts[$key]-1));
				}
				$stdevpen = max(8*($stdevs[$key]-5)/($settings[7]),0);
				if (count($answerline)==0) {
					$percentunmatchedans = 1;
				} else {
					$percentunmatchedans = max((count($answerline)-$stcnts[$key])/(count($answerline)),0);
				}
				if ($percentunmatchedans<.05*$reltolerance) {
					$percentunmatchedans = 0;
				}
				$scores[$key] = 1-($stdevpen + $percentunmatcheddrawn + $percentunmatchedans)/$reltolerance;
				//if ($GLOBALS['myrights']==100) {
				 //echo "Line: $key, stdev: {$stdevs[$key]}, unmatchedrawn: $percentunmatcheddrawn, unmatchedans: $percentunmatchedans <br/>";
				//}
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
					if (($dots[$i][0]-$ansdot[0])*($dots[$i][0]-$ansdot[0]) + ($dots[$i][1]-$ansdot[1])*($dots[$i][1]-$ansdot[1]) <= 25*max(1,$reltolerance)) {
						$scores[$key] = 1 - $extradots;
						break;
					}
				}
			}
			//and open dots
			foreach ($ansodots as $key=>$ansdot) {
				$scores[$key] = 0;
				for ($i=0; $i<count($odots); $i++) {
					if (($odots[$i][0]-$ansdot[0])*($odots[$i][0]-$ansdot[0]) + ($odots[$i][1]-$ansdot[1])*($odots[$i][1]-$ansdot[1]) <= 25*max(1,$reltolerance)) {
						$scores[$key] = 1 - $extradots;
						break;
					}
				}
			}

		}
		if (!isset($partweights)) {
			$partweights = array_fill(0,count($scores),1/count($scores));
		} else {
			if (!is_array($partweights)) {
				$partweights = array_map('trim',explode(',',$partweights));
			}
		}
		$totscore = 0;
		foreach ($scores as $key=>$score) {
			$totscore += $score*$partweights[$key];
		}
		if ($extrastuffpenalty>0) {
			$totscore = max($totscore*(1-$extrastuffpenalty),0);
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
		if (isset($options['scoremethod']))if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$qn];} else {$scoremethod = $options['scoremethod'];}
		if (isset($options['answer'])) {if ($multi>0) {$answer = $options['answer'][$qn];} else {$answer = $options['answer'];}}
		if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$qn];} else {$answerformat = $options['answerformat'];}}

		if ($multi>0) { $qn = $multi*1000+$qn;}
		$filename = basename($_FILES["qn$qn"]['name']);
		$filename = preg_replace('/[^\w\.]/','',$filename);
		$hasfile = false;
		require_once(dirname(__FILE__)."/../includes/filehandler.php");
		if (trim($filename)=='') {
			$found = false;
			if ($_POST["lf$qn"]!='') {
				if ($multi>0) {
					if (strpos($GLOBALS['lastanswers'][$multi-1],'@FILE:'.$_POST["lf$qn"].'@')!==false) {
						$found = true;
					}
				} else {
					if (strpos($GLOBALS['lastanswers'][$qn],'@FILE:'.$_POST["lf$qn"].'@')!==false) {
						$found = true;
					}
				}
			}
			if ($found) {
				$GLOBALS['partlastanswer'] = '@FILE:'.$_POST["lf$qn"].'@';
				if ($answerformat=='excel') {
					$zip = new ZipArchive;
					if ($zip->open(getasidfilepath($_POST["lf$qn"]))) {
						$doc = new DOMDocument();
						$doc->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
						$zip->close();
					} else {
						$GLOBALS['scoremessages'] .= _(' Unable to open Excel file');
						return 0;
					}
				}
				$hasfile = true;
			} else {
				$GLOBALS['partlastanswer'] = '';
				return 0;
			}
		}
		if (!$hasfile) {
			$extension = strtolower(strrchr($filename,"."));
			$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p",".exe");
			if ($GLOBALS['scoremessages'] != '') {
				$GLOBALS['scoremessages'] .= '<br/>';
			}
			$GLOBALS['scoremessages'] .= sprintf(_('Upload of %s: '), $filename);
			if (in_array($extension,$badextensions)) {
				$GLOBALS['partlastanswer'] = _('Error - Invalid file type');
				$GLOBALS['scoremessages'] .= _('Error - Invalid file type');
				return 0;
			}
			//if($GLOBALS['isreview']) {echo 'TRUE';}
			if (isset($GLOBALS['asid'])) { //going to use assessmentid/random
				$randstr = '';
				for ($i=0; $i<6; $i++) {
					$n = rand(0,61);
					if ($n<10) { $randstr .= chr(48+$n);}
					else if ($n<36) { $randstr .= chr(65 + $n-10);}
					else { $randstr .= chr(97 + $n-36);}
				}
				//in case "same random seed" is selected, students can overwrite their own
				//files. Avoid this.
				if (($GLOBALS['testsettings']['shuffle']&4)==4 || ($GLOBALS['testsettings']['shuffle']&2)==2) {
					//if same random seed is set, need to check for duplicates
					$n = 0;
					do {
						$n++;
						$s3asid = $GLOBALS['testsettings']['id']."/$n";
					} while (doesfileexist('assess',"adata/$s3asid/$filename"));
				} else {
					$s3asid = $GLOBALS['testsettings']['id']."/$randstr";
				}
			} else {
				$GLOBALS['partlastanswer'] = _('Error - no asid');
				$GLOBALS['scoremessages'] .= _('Error - no asid');
				return 0;
			}
			if (is_numeric($s3asid) && $s3asid==0) {  //set in testquestion for preview
				$GLOBALS['partlastanswer'] = _('Error - File not uploaded in preview');
				$GLOBALS['scoremessages'] .= _('Error - File not uploaded in preview');
				return 0;
			}

			if (is_uploaded_file($_FILES["qn$qn"]['tmp_name'])) {
				if ($answerformat=='excel') {
					$zip = new ZipArchive;
					if ($zip->open($_FILES["qn$qn"]['tmp_name'])) {
						echo "opened excel";
						$doc = new DOMDocument();
						if ($doc->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'))) {
							echo "read into doc";
						}

						$zip->close();
					} else {
						$GLOBALS['scoremessages'] .= _(' Unable to open Excel file');
						return 0;
					}
				}

				$s3object = "adata/$s3asid/$filename";
				if (storeuploadedfile("qn$qn",$s3object)) {
					$GLOBALS['partlastanswer'] = "@FILE:$s3asid/$filename@";
					$GLOBALS['scoremessages'] .= _("Successful");
					$hasfile = true;
				} else {
					//echo "Error storing file";
					$GLOBALS['partlastanswer'] = _('Error storing file');
					$GLOBALS['scoremessages'] .= _('Error storing file');
					return 0;
				}

			} else {
				//echo "Error uploading file";
				if ($_FILES["qn$qn"]['error']==2 || $_FILES["qn$qn"]['error']==1) {
					$GLOBALS['partlastanswer'] = _('Error uploading file - file too big');
					$GLOBALS['scoremessages'] .= _('Error uploading file - file too big');
				} else {
					$GLOBALS['partlastanswer'] = _('Error uploading file');
					$GLOBALS['scoremessages'] .= _('Error uploading file');
				}
				return 0;
			}
		}
		if (isset($scoremethod) && $scoremethod=='takeanything') {
			return 1;
		} else {
			if ($answerformat=='excel') {
				$doccells = array();
				$els = $doc->getElementsByTagName('c');
				foreach ($els as $el) {
					$doccells[$el->getAttribute('r')] = $el->getElementsByTagName('v')->item(0)->nodeValue;
				}
				$pts = 0;

				foreach ($answer as $cell=>$val) {
					if (!isset($doccells[$cell])) {continue;}
					if (is_numeric($val)) {
						if (abs($val-$doccells[$cell])<.01) {
							$pts++;
						} else {
							$GLOBALS['scoremessages'] .= "<br/>Cell $cell incorrect";
							echo "<br/>Cell $cell incorrect";
						}
					} else {
						if (trim($val)==trim($doccells[$cell])) {
							$pts++;
						}
					}
				}
				return $pts/count($answer);
			} else {
				return -2;
			}
		}
	} else if ($anstype == "conditional") {
		$answer = $options['answer'];
		if (isset($options['abstolerance'])) {$abstolerance = $options['abstolerance'];}
		if (isset($options['reltolerance'])) {$reltolerance = $options['reltolerance'];}
		if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
		if (isset($options['domain'])) {$domain = $options['domain'];} else { $domain = "-10,10";}
		if (isset($options['variables'])) {$variables = $options['variables'];} else { $variables = "x";}
		$anstypes = $options['anstypes'];
		if (!is_array($anstypes)) {
			$anstypes = array_map('trim',explode(',',$anstypes));
		}
		$la = array();
		foreach ($anstypes as $i=>$anst) {
			$qnt = 1000*($qn+1)+$i;
			if (isset($_POST["tc$qnt"])) {
				if ($anst=='calculated') {
					$la[$i] = $_POST["tc$qnt"].'$#$'.$_POST["qn$qnt"];
				} else {
					$la[$i] = $_POST["tc$qnt"];
				}
			} else if (isset($_SESSION['choicemap'][$qnt])) {
				if (is_array($_POST["qn$qnt"])) { //multans
					$origmala = array();
					$mappedpost = array();
					foreach ($_SESSION['choicemap'][$qnt] as $k=>$v) {
						if (isset($_POST["qn$qnt"][$k])) {
							$origmala[$k] = $_POST["qn$qnt"][$k];
							$mappedpost[$k] = $_SESSION['choicemap'][$qnt][$_POST["qn$qnt"][$k]];
						} else {
							$origmala[$k] = "";
						}
					}
					$la[$i] = implode('|',$origmala) . '$!$' . implode('|', $mappedpost);
				} else if (isset($_SESSION['choicemap'][$qnt][$_POST["qn$qnt"]])) {
					$la[$i] = $_POST["qn$qnt"] . '$!$' . $_SESSION['choicemap'][$qnt][$_POST["qn$qnt"]];
				}
			} else {
				$la[$i] = $_POST["qn$qnt"];
			}
			$la[$i] = str_replace('&','',$la[$i]);
			$la[$i] = preg_replace('/#+/','#',$la[$i]);
		}

		$GLOBALS['partlastanswer'] = implode('&',$la);
		if (isset($abstolerance)) {
			$tol = '|'.$abstolerance;
		} else {
			$tol = $reltolerance;
		}
		$correct = true;
		if (!is_array($answer)) { //single boolean
			if ($answer===true) {
				return 1;
			} else if ($answer===false) {
				return 0;
			} else {
				return $answer;
			}
		}
		if (is_array($answer) && is_string($answer[0])) {  //if single {'function',$f,$g) type, make array
			$answer = array($answer);
		}
		foreach ($answer as $ans) {
			if (is_array($ans)) {
				if ($ans[0]{0}=='!') {
					$flip = true;
					$ans[0] = substr($ans[0],1);
				} else {
					$flip = false;
				}
				if ($ans[0]=='number') {
					$pt = comparenumbers($ans[1],$ans[2],$tol);
				} else if ($ans[0]=='function') {
					$pt = comparefunctions($ans[1],$ans[2],$variables,$tol,$domain);
				}
				if ($flip) {
					$pt = !$pt;
				}
			} else {
				$pt = $ans;
			}
			if ($pt==false) {
				return 0;
				break;
			}
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

function checkreqtimes($tocheck,$rtimes) {
	global $mathfuncs;
	if ($tocheck=='DNE' || $tocheck=='oo' || $tocheck=='+oo' || $tocheck=='-oo') {
		return 1;
	}
	$cleanans = preg_replace('/[^\w\*\/\+\-\(\)\[\],\.\^=]+/','',$tocheck);
	//if entry used pow or exp, we want to replace them with their asciimath symbols for requiretimes purposes
	$cleanans = str_replace("pow","^",$cleanans);
	$cleanans = str_replace("exp","e",$cleanans);
	if (is_numeric($cleanans) && $cleanans>0 && $cleanans<1) {
		$cleanans = ltrim($cleanans,'0');
	}
	$ignore_case = false;
	if ($rtimes != '') {
		$list = array_map('trim',explode(",",$rtimes));
		for ($i=0;$i < count($list);$i+=2) {
			if ($list[$i]=='' || strlen($list[$i+1])<2) {continue;}
			$list[$i+1] = trim($list[$i+1]);
			if ($list[$i]=='ignore_case') {
				$ignore_case = ($list[$i+1]=='1' || $list[$i+1]=='true' || $list[$i+1]=='=1');
				continue;
			}
			$comp = substr($list[$i+1],0,1);
			$num = intval(substr($list[$i+1],1));

			if ($list[$i]=='#') {
				$nummatch = preg_match_all('/[\d\.]+/',$cleanans,$m);
			} else if (strlen($list[$i])>6 && substr($list[$i],0,6)=='regex:') {
				$regex = str_replace('/','\\/',substr($list[$i],6));
				echo '/'.$regex.'/'.($ignore_case?'i':'');
				$nummatch = preg_match_all('/'.$regex.'/'.($ignore_case?'i':''),$cleanans,$m);
			} else {
				if ($ignore_case || in_array($list[$i], $mathfuncs)) {
					$nummatch = substr_count(strtolower($cleanans),strtolower($list[$i]));
				} else {
					$nummatch = substr_count($cleanans,$list[$i]);
				}
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
			} else if ($comp == "!") {
				if ($nummatch==$num) {
					return 0;
				}
			}
		}
	}
	return 1;
}

function parsesloppycomplex($v) {
	$v = mathphp($v,'i');
	$v = str_replace('(i)','($i)',$v);
	$a = eval('$i=0;return ('.$v.');');
	$apb = eval('$i=1;return ('.$v.');');
	return array($a,$apb-$a);
}

//parses complex numbers.  Can handle anything, but only with
//one i in it.
function parsecomplex($v) {
	$v = str_replace(' ','',$v);
	$v = str_replace(array('sin','pi'),array('s$n','p$'),$v);
	$len = strlen($v);
	//preg_match_all('/(\bi|i\b)/',$v,$matches,PREG_OFFSET_CAPTURE);
	//if (count($matches[0])>1) {
	if (substr_count($v,'i')>1) {
		return _('error - more than 1 i in expression');
	} else {
		//$p = $matches[0][0][1];
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
			if ($p-$L>0 && $R-$p>0 && ($R==$len || $L==0)) {
				//return _('error - invalid form');
				if ($R==$len) {// real + AiB
					$real = substr($v,0,$L);
					$imag = substr($v,$L,$p-$L);
					$imag .= '*'.substr($v,$p+1+($v{$p+1}=='*'?1:0),$R-$p-1);
				} else if ($L==0) { //AiB + real
					$real = substr($v,$R);
					$imag = substr($v,0,$p);
					$imag .= '*'.substr($v,$p+1+($v{$p+1}=='*'?1:0),$R-$p-1);
				} else {
					return _('error - invalid form');
				}
				$imag = str_replace('-*','-',$imag);
				$imag = str_replace('+*','+',$imag);
			} else if ($p-$L>1) {
				$imag = substr($v,$L,$p-$L);
				$real = substr($v,0,$L) . substr($v,$p+1);
			} else if ($R-$p>1) {
				if ($p>0) {
					if ($v{$p-1}!='+' && $v{$p-1}!='-') {
						return _('error - invalid form');
					}
					$imag = $v{$p-1}.substr($v,$p+1+($v{$p+1}=='*'?1:0),$R-$p-1);
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
			if (substr($imag,-1)=='*') {
				$imag = substr($imag,0,-1);
			}
		}
		$real = str_replace(array('s$n','p$'),array('sin','pi'),$real);
		$imag = str_replace(array('s$n','p$'),array('sin','pi'),$imag);
		return array($real,$imag);
	}
}


//checks the format of a value
//tocheck:  string to check
//ansformats:  array of answer formats.  Currently supports:
//   fraction, reducedfraction, fracordec, notrig, nolongdec, scinot, mixednumber, nodecimal
//returns:  false: bad format, true: good format
function checkanswerformat($tocheck,$ansformats) {
	$tocheck = trim($tocheck);
	$tocheck = str_replace(',','',$tocheck);
	if (!is_array($ansformats)) {$ansformats = explode(',',$ansformats);}
	if (strtoupper($tocheck)=='DNE' || $tocheck=='oo' || $tocheck=='+oo' || $tocheck=='-oo') {
		return true;
	}
	if (in_array("fraction",$ansformats) || in_array("reducedfraction",$ansformats) || in_array("fracordec",$ansformats)) {
		$tocheck = preg_replace('/\s/','',$tocheck);
		if (!preg_match('/^\(?\-?\(?\d+\)?\/\(?\d+\)?$/',$tocheck) && !preg_match('/^\(?\d+\)?\/\(?\-?\d+\)?$/',$tocheck) && !preg_match('/^\s*?\-?\d+\s*$/',$tocheck) && (!in_array("fracordec",$ansformats) || !preg_match('/^\s*?\-?\d*?\.\d*?\s*$/',$tocheck))) {
			return false;
		} else {
			if (in_array("reducedfraction",$ansformats) && strpos($tocheck,'/')!==false) {
				$tocheck = str_replace(array('(',')'),'',$tocheck);
				$tmpa = explode("/",$tocheck);
				if (gcd(abs($tmpa[0]),abs($tmpa[1]))!=1 || $tmpa[1]==1) {
					return false;
				}
			}
		}
	}
	if (in_array("notrig",$ansformats)) {
		if (preg_match('/(sin|cos|tan|cot|csc|sec)/i',$tocheck)) {
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
		if (!preg_match('/^\-?[1-9](\.\d*)?(\*|x|X|×)10\^(\(?\-?\d+\)?)$/',$totest)) {
			return false;
		}
	}

	if (in_array("mixednumber",$ansformats) || in_array("sloppymixednumber",$ansformats) || in_array("mixednumberorimproper",$ansformats)) {
		if (!preg_match('/^\s*\-?\s*\d+\s*(_|\s)\s*(\d+)\s*\/\s*(\d+)\s*$/',$tocheck,$mnmatches) && !preg_match('/^\s*?\-?\d+\s*$/',$tocheck) && !preg_match('/^\s*\-?\d+\s*\/\s*\-?\d+\s*$/',$tocheck)) {
			//if doesn't match any format, exit
			return false;
		} else {
			if (preg_match('/^\s*\-?\d+\s*\/\s*\-?\d+\s*$/',$tocheck)) {   //if a fraction
				$tmpa = explode("/",$tocheck);
				if (in_array("mixednumber",$ansformats)) {
					if ((gcd(abs($tmpa[0]),abs($tmpa[1]))!=1) || abs($tmpa[0])>=abs($tmpa[1])) {
						return false;
					}
				} else if (in_array("mixednumberorimproper",$ansformats)) {
					if ((gcd(abs($tmpa[0]),abs($tmpa[1]))!=1)) {
						return false;
					}
				}
			} else	if (!preg_match('/^\s*?\-?\d+\s*$/',$tocheck)) {  //is in mixed number format
				if (in_array("mixednumber",$ansformats)) {
					if ($mnmatches[2]>=$mnmatches[3] || gcd($mnmatches[2],$mnmatches[3])!=1) {
						return false;
					}
				} else if (in_array("mixednumberorimproper",$ansformats)) {
					if (gcd($mnmatches[2],$mnmatches[3])!=1) {
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

function formathint($eword,$ansformats,$calledfrom, $islist=false,$doshort=false) {
	$tip = '';
	$shorttip = '';
	if (in_array('set',$ansformats) || in_array('exactset',$ansformats)) {
		$listtype = "set";
	} else {
		$listtype = "list";
	}
	if (in_array('fraction',$ansformats)) {
		$tip .= sprintf(_('Enter %s as a fraction (like 3/5 or 10/4) or as a whole number (like 4 or -2)'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of fractions or whole numbers'), $listtype):_('Enter a fraction or whole number');
	} else if (in_array('reducedfraction',$ansformats)) {
		if (in_array('fracordec',$ansformats)) {
			$tip .= sprintf(_('Enter %s as a reduced fraction (like 5/3, not 10/6), as a whole number (like 4 or -2), or as an exact decimal (like 0.5 or 1.25)'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of reduced fractions, whole numbers, or exact decimals'), $listtype):_('Enter a reduced fraction, whole number, or exact decimal');
		} else {
			$tip .= sprintf(_('Enter %s as a reduced fraction (like 5/3, not 10/6) or as a whole number (like 4 or -2)'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of reduced fractions or whole numbers'), $listtype):_('Enter a reduced fraction or whole number');
		}
	} else if (in_array('mixednumber',$ansformats)) {
		$tip .= sprintf(_('Enter %s as a reduced mixed number or as a whole number.  Example: 2 1/2 = 2 &frac12;'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of mixed numbers or whole numbers'), $listtype):_('Enter a mixed number or whole number');
	} else if (in_array('mixednumberorimproper',$ansformats)) {
		$tip .= sprintf(_('Enter %s as a reduced mixed number, reduced proper or improper fraction, or as a whole number.  Example: 2 1/2 = 2 &frac12;'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of mixed numbers or whole numbers'), $listtype):_('Enter a reduced mixed number, proper or improper fraction, or whole number');
	} else if (in_array('fracordec',$ansformats)) {
		$tip .= sprintf(_('Enter %s as a fraction (like 3/5 or 10/4), a whole number (like 4 or -2), or exact decimal (like 0.5 or 1.25)'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of fractions or exact decimals'), $listtype):_('Enter a fraction or exact decimal');
	} else if (in_array('scinot',$ansformats)) {
		$tip .= sprintf(_('Enter %s as in scientific notation.  Example: 3*10^2 = 3 &middot; 10<sup>2</sup>'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of numbers using scientific notation'), $listtype):_('Enter a number using scientific notation');
	} else {
		$tip .= sprintf(_('Enter %s as a number (like 5, -3, 2.2) or as a calculation (like 5/3, 2^3, 5+4)'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of mathematical expressions'), $listtype):_('Enter a mathematical expression');
	}
	if ($calledfrom != 'calcmatrix') {
		$tip .= "<br/>" . _('Enter DNE for Does Not Exist, oo for Infinity');
	}
	if (in_array('nodecimal',$ansformats)) {
		$tip .= "<br/>" . _('Decimal values are not allowed');
	} else if (isset($reqdecimals)) {
		if ($reqdecimals == 0) {
			$tip .= "<br/>" . _('Your answer should be accurate to the nearest whole number.');
		} else {
			$tip .= "<br/>" . sprintf(_('Your answer should be accurate to %d decimal places.'), $reqdecimals);
		}
	}
	if (in_array('notrig',$ansformats)) {
		$tip .= "<br/>" . _('Trig functions (sin,cos,etc.) are not allowed');
	}
	if ($doshort) {
		return array($tip,$shorttip);
	} else {
		return $tip;
	}
}

function getcolormark($c,$wrongformat=false) {
	global $imasroot;

	if (isset($GLOBALS['nocolormark'])) { return '';}

	if ($c=='ansred') {
		return '<img class="scoreboxicon" src="'.$imasroot.'/img/redx.gif" width="6" height="6"/>';
	} else if ($c=='ansgrn') {
		return '<img class="scoreboxicon" src="'.$imasroot.'/img/gchk.gif" width="8" height="6"/>';
	} else if ($c=='ansorg') {
		return '<img class="scoreboxicon" src="'.$imasroot.'/img/orgx.gif" width="6" height="6"/>';
	} else if ($c=='ansyel') {
		return '<img class="scoreboxicon" src="'.$imasroot.'/img/ychk.gif" width="8" height="6"/>';
	} else {
		return '';
	}
}

function setupnosolninf($qn, $answerbox, $answer, $ansformats, $la, $ansprompt, $colorbox) {
	$answerbox = preg_replace('/<label.*?<\/label>/','',$answerbox);  //remove existing ansprompt
	$nosoln = _('No solution');
	$infsoln = _('Infinite number of solutions');
	if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
		$specsoln = _('One or more solutions: ');
	} else {
		$specsoln = _('One solution: ');
	}

	if (isset($ansprompt)) {
		$anspromptp = explode(';', $ansprompt);
		unset($ansprompt);
		$specsoln = $anspromptp[0];
		if (count($anspromptp)>1) {
			$nosoln = $anspromptp[1];
		}
		if (count($anspromptp)>2) {
			$infsoln = $anspromptp[2];
		}
	}
	$out .= '<div class="'.$colorbox.'">';

	$out .= '<label><input type="radio" id="qs'.$qn.'" name="qs'.$qn.'" value="spec" '.(($la!='DNE'&&$la!='oo')?'checked':'').'>'.$specsoln;
	if ($la=='DNE' || $la=='oo') {
		$laqs = $la;
		$answerbox = str_replace('value="'.$la.'"','value=""',$answerbox);
	} else {
		$laqs = '';
	}

	$out .= str_replace(getcolormark($colorbox),'',$answerbox);

	$out .= '<span id="previewloctemp'.$qn.'"></span>';
	$out .= '</label><br/>';

	$out .= '<label><input type="radio" id="qs'.$qn.'" name="qs'.$qn.'" value="DNE" '.($laqs=='DNE'?'checked':'').'>'.$nosoln.'</label><br/>';
	if (in_array('nosolninf',$ansformats)) {
		$out .= '<label><input type="radio" id="qs'.$qn.'" name="qs'.$qn.'" value="inf" '.($laqs=='oo'?'checked':'').'>'.$infsoln.'</label>';
	}
	$out .= '<span class="floatright">'.getcolormark($colorbox).'</span>';
	$out .= '</div>';

	if (preg_match('/^inf/',$answer) || $answer==='oo' || $answer===$infsoln) {
		$answer = '"'.$infsoln.'"';
	}
	if (preg_match('/^no\s*solution/',$answer) || $answer==='DNE' || $answer===$nosoln) {
		$answer = '"'.$nosoln.'"';
	}

	return array($out,$answer);
}

function scorenosolninf($qn, $givenans, $answer, $ansprompt) {
	$nosoln = _('No solution');
	$infsoln = _('Infinite number of solutions');
	if (isset($ansprompt)) {
		$anspromptp = explode(';', $ansprompt);
		unset($ansprompt);
		$specsoln = $anspromptp[0];
		if (count($anspromptp)>1) {
			$nosoln = $anspromptp[1];
		}
		if (count($anspromptp)>2) {
			$infsoln = $anspromptp[2];
		}
	}
	if (preg_match('/^inf/',$answer) || $answer==$infsoln) {
		$answer = 'oo';
	}
	if (preg_match('/^no\s*solution/',$answer) || $answer==$nosoln) {
		$answer = 'DNE';
	}
	$qs = $_POST["qs$qn"];
	if ($qs=='DNE') {
		$givenans = "DNE";
		$newpost = "DNE";
	} else if ($qs=='inf') {
		$givenans = "oo";
		$newpost = "oo";
	} else {
		$newpost = $_POST["tc$qn"];
	}

	return array($givenans, $newpost, $answer);
}

function rawscoretocolor($sc,$aw) {
	if ($aw==0) {
		return '';
	} else if ($sc<0) {
		return '';
	} else if ($sc==0) {
		return 'ansred';
	} else if ($sc>.98) {
		return 'ansgrn';
	} else {
		return 'ansyel';
	}
}

function normalizemathunicode($str) {
	$str = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
	$str = str_replace(array('‒','–','—','―','−'),'-',$str);
	$str = str_replace(array('⁄','∕','⁄ ','÷'),'/',$str);
	$str = str_replace(array('（','）','∞','∪','≤','≥','⋅'), array('(',')','oo','U','<=','>=','*'), $str);
	$str = preg_replace('/\bOO\b/i','oo', $str);
	return $str;
}

if (!function_exists('stripslashes_deep')) {
	function stripslashes_deep($value) {
		return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
	}
}
?>
