<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class NTupleScorePart implements ScorePart
{
    private $scoreQuestionParams;

    public function __construct(ScoreQuestionParams $scoreQuestionParams)
    {
        $this->scoreQuestionParams = $scoreQuestionParams;
    }

    public function getResult(): ScorePartResult
    {
        global $mathfuncs;

        $scorePartResult = new ScorePartResult();

        $RND = $this->scoreQuestionParams->getRandWrapper();
        $options = $this->scoreQuestionParams->getVarsForScorePart();
        $qn = $this->scoreQuestionParams->getQuestionNumber();
        $givenans = $this->scoreQuestionParams->getGivenAnswer();
        $multi = $this->scoreQuestionParams->getIsMultiPartQuestion();
        $partnum = $this->scoreQuestionParams->getQuestionPartNumber();
        $anstype = $this->scoreQuestionParams->getAnswerType();

        $defaultreltol = .0015;

        $optionkeys = ['answer', 'reltolerance', 'abstolerance', 
            'answerformat', 'requiretimes', 'ansprompt', 'scoremethod'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        if ($reltolerance === '' && $abstolerance === '') { $reltolerance = $defaultreltol;}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $hasNumVal = !empty($_POST["qn$qn-val"]);
        if ($hasNumVal) {
          $givenansval = $_POST["qn$qn-val"];
        }

        $givenans = normalizemathunicode($givenans);
        $givenans = str_replace(array('(:',':)','<<','>>'), array('<','>','<','>'), $givenans);
        $answer = normalizemathunicode($answer);
        
        $ansformats = array_map('trim',explode(',',$answerformat));
        $answer = str_replace(' ','',$answer);

        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
            if ($givenans === 'DNE' || $givenans === 'oo') {
              $_POST["qn$qn-val"] = $givenans;
            }
        }

        $scorePartResult->setLastAnswerAsGiven($givenans);
        if ($anstype=='ntuple') {
            $gaarr = $this->parseNtuple($givenans, false, true);
        } else if ($anstype=='calcntuple') {
            // parse and evaluate
            if ($hasNumVal) {
                $gaarr = $this->parseNtuple($givenansval, false, true);
                $scorePartResult->setLastAnswerAsNumber($givenansval);
            } else {
                $gaarr = $this->parseNtuple($givenans, false, true);
                $scorePartResult->setLastAnswerAsNumber($this->ntupleToString($gaarr));
            }
        }
        if ($givenans == null) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        $givenans = str_replace(' ','',$givenans);

        if ($answer=='DNE') {
            if (strtoupper($givenans)=='DNE') {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            } else {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        } else if ($answer=='oo') {
            if ($givenans=='oo') {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            } else {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        } else if (strtoupper($givenans)=='DNE' || $givenans=='oo') {
          $scorePartResult->setRawScore(0);
          return $scorePartResult;
        }

        // check formats for calcntuple
        if ($anstype=='calcntuple') {

            //test for correct format, if specified
            if (checkreqtimes($givenans,$requiretimes)==0) {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }

            //parse the ntuple without evaluating
            $tocheck = $this->parseNtuple($givenans, false, false);

            if ($answer != 'DNE' && $answer != 'oo') {
                foreach($tocheck as $chkme) {
                    foreach ($chkme['vals'] as $chkval) {
                        if ($chkval != 'oo' && $chkval != '-oo') {
                            if (!checkanswerformat($chkval,$ansformats)) {
                                //perhaps should just elim bad answer rather than all?
                                $scorePartResult->setRawScore(0);
                                return $scorePartResult;
                            }
                        }
                    }
                }
            }
        }

        if (count($gaarr)==0) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        $answer = makepretty($answer);
        // parse and evaluate the answer, capturing "or"s
        $anarr = $this->parseNtuple($answer, true, true);

        if (in_array('scalarmult',$ansformats)) {
            //normalize the vectors
            foreach ($anarr as $k=>$listans) {
                foreach ($listans as $ork=>$orv) {
                    $mag = sqrt(array_sum(array_map(function($x) {return $x*$x;}, $orv['vals'])));
                    foreach ($orv['vals'] as $j=>$v) {
                        if (abs($v)>1e-10) {
                            if ($v<0) {
                                $mag *= -1;
                            }
                            break;
                        }
                    }
                    if (abs($mag)>0) {
                        foreach ($orv['vals'] as $j=>$v) {
                            $anarr[$k][$ork]['vals'][$j] = $v/$mag;
                        }
                    }
                }
            }
            foreach ($gaarr as $k=>$givenans) {
                $mag = sqrt(array_sum(array_map(function($x) {return $x*$x;}, $givenans['vals'])));
                foreach ($givenans['vals'] as $j=>$v) {
                    if (abs($v)>1e-10) {
                        if ($v<0) {
                            $mag *= -1;
                        }
                        break;
                    }
                }
                if (abs($mag)>0) {
                    foreach ($givenans['vals'] as $j=>$v) {
                        $gaarr[$k]['vals'][$j] = $v/$mag;
                    }
                }
            }
        }

        $gaarrcnt = count($gaarr);
        $extrapennum = count($gaarr)+count($anarr);
        $correct = 0;
        $partialmatches = array();
        $matchedans = array();
        $matchedgivenans = array();
        foreach ($anarr as $ai=>$ansors) {
            $foundloc = -1;
            foreach ($ansors as $answer) {  //each of the "or" options
                foreach ($gaarr as $j=>$givenans) {
                    if (isset($matchedgivenans[$j])) {continue;}

                    if ($answer['lb']!=$givenans['lb'] || $answer['rb']!=$givenans['rb']) {
                        break;
                    }

                    if (count($answer['vals'])!=count($givenans['vals'])) {
                        break;
                    }
                    $matchedparts = 0;
                    foreach ($answer['vals'] as $i=>$ansval) {
                        $gansval = $givenans['vals'][$i];
                        if (is_numeric($ansval) && is_numeric($gansval)) {
                            if ($abstolerance !== '') {
                                if (abs($ansval-$gansval) < $abstolerance + 1E-12) {
                                    $matchedparts++;
                                }
                            } else {
                                if (abs($ansval-$gansval)/(abs($ansval)+.0001) < $reltolerance+ 1E-12) {
                                    $matchedparts++;
                                }
                            }
                        } else if (($ansval=='oo' && $gansval=='oo') || ($ansval=='-oo' && $gansval=='-oo')) {
                            $matchedparts++;
                            //is ok
                        }
                    }

                    if ($matchedparts==count($answer['vals'])) { //if totally correct
                        $correct += 1; $foundloc = $j; break 2;
                    } else if ($scoremethod=='byelement' && $matchedparts>0) { //if partially correct
                        $fraccorrect = $matchedparts/count($answer['vals']);
                        if (!isset($partialmatches["$ai-$j"]) || $fraccorrect>$partialmatches["$ai-$j"]) {
                            $partialmatches["$ai-$j"] = $fraccorrect;
                        }
                    }
                }
            }
            if ($foundloc>-1) {
                //array_splice($gaarr,$foundloc,1); // remove from list
                $matchedgivenans[$foundloc] = 1;
                $matchedans[$ai] = 1;
                if (count($gaarr)==count($matchedgivenans)) {
                    break;
                }
            }
        }
        if ($scoremethod=='byelement') {
            arsort($partialmatches);
            foreach ($partialmatches as $k=>$v) {
                $kp = explode('-', $k);
                if (isset($matchedans[$kp[0]]) || isset($matchedgivenans[$kp[1]])) {
                    //already used this ans or stuans
                    continue;
                } else {
                    $correct += $v;
                    $matchedans[$kp[0]] = 1;
                    $matchedgivenans[$kp[1]] = 1;
                    if (count($gaarr)==count($matchedgivenans)) {
                        break;
                    }
                }
            }
        }
        if (count($anarr)==0) { // no answers
            if ($GLOBALS['myrights']>10) {
              echo _('Eeek: No valid $answer values provided');
            }
            $score = 0;
        } else if ($gaarrcnt<=count($anarr)) {
            $score = $correct/count($anarr);
        } else {
            $score = $correct/count($anarr) - ($gaarrcnt-count($anarr))/$extrapennum;  //take off points for extranous stu answers
        }

        //$score = $correct/count($anarr) - count($gaarr)/$extrapennum;
        if ($score<0) { $score = 0; }
        $scorePartResult->setRawScore($score);
        return $scorePartResult;
    }

    /**
	 * Parses a list of string ntuples
	 * do_or: for each element in list, create an array of "or" alternatives
	 * eval: true to eval non-numeric values
	 */
    private function parseNtuple($str, $do_or = false, $do_eval = true) {
        if ($str == 'DNE' || $str == 'oo' || $str == '-oo') {
            return $str;
        }
        $ntuples = [];
        $NCdepth = 0;
        $lastcut = 0;
        $inor = false;
        $str = makepretty($str);
        $matchbracket = array(
            '(' => ')',
            '[' => ']',
            '<' => '>',
            '{' => '}'
        );
        $closebracket = '';
    		$openbracket = '';
    		for ($i=0; $i<strlen($str); $i++) {
    				$dec = false;
    				if ($str[$i]=='(' || $str[$i]=='[' || $str[$i]=='<' || $str[$i]=='{') {
    						if ($NCdepth==0) {
    								$lastcut = $i;
    								$closebracket = $matchbracket[$str[$i]];
    								$openbracket = $str[$i];
    						}
    						if ($openbracket == '' || $str[$i] == $openbracket) {
    							$NCdepth++;
    						}
    				} else if ($str[$i]==$closebracket) {
    						$NCdepth--;
    						if ($NCdepth==0) {
    								$thisTuple = array(
    										'lb' => $str[$lastcut],
    										'rb' => $str[$i],
    										'vals' => explode(',', substr($str,$lastcut+1,$i-$lastcut-1))
    								);
    								if ($do_eval) {
    										for ($j=0; $j < count($thisTuple['vals']); $j++) {
    												if ($thisTuple['vals'][$j] != 'oo' && $thisTuple['vals'][$j] != '-oo') {
    														$thisTuple['vals'][$j] = evalMathParser($thisTuple['vals'][$j]);
    												}
    										}
    								}
    								if ($do_or && $inor) {
    										$ntuples[count($ntuples)-1][] = $thisTuple;
    								} else if ($do_or) {
    										$ntuples[] = array($thisTuple);
    								} else {
    										$ntuples[] = $thisTuple;
    								}
    								$inor = ($do_or && substr($str, $i+1, 2)==='or');
    								$openbracket = '';
    								$closebracket = '';
    						}
    				}
    		}
    		return $ntuples;
    }

    private function ntupleToString($ntuples) {
        if (!is_array($ntuples)) {
            return $ntuples;
        }
        $out = array();
        foreach ($ntuples as $ntuple) {
            if (isset($ntuple['lb'])) {
                $out[] = $ntuple['lb'] . implode(',', $ntuple['vals']) . $ntuple['rb'];
            } else if (is_array($ntuple[0])) {
                $sub = array();
                foreach ($ntuple as $subtuple) {
                    $sub[] = $subtuple['lb'] . implode(',', $subtuple['vals']) . $subtuple['rb'];
                }
                $out[] = implode(' or ', $sub);
            }
        }
        implode(',', $out);
    }
}
