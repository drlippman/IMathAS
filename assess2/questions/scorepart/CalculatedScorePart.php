<?php

namespace IMathAS\assess2\questions\scorepart;

require_once __DIR__ . '/ScorePart.php';
require_once __DIR__ . '/../models/ScorePartResult.php';

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class CalculatedScorePart implements ScorePart
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

        $defaultreltol = .0015;

        $optionkeys = ['answer', 'reltolerance', 'abstolerance', 'reqsigfigs', 
            'requiretimeslistpart', 'ansprompt', 'formatfeedbackon'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        $optionkeys = ['partialcredit'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum, 2);
        }
        $optionkeys = ['answerformat','requiretimes'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum, 1);
        }

        if ($reltolerance === '' && $abstolerance === '') { $reltolerance = $defaultreltol;}
        
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $hasNumVal = !empty($_POST["qn$qn-val"]);

        $givenans = normalizemathunicode($givenans);
        $answer = normalizemathunicode($answer);
        if (is_array($answerformat)) {
            $ansformats = array_map('trim',explode(',',$answerformat[0]));
        } else {
            $ansformats = array_map('trim',explode(',',$answerformat));
        }

        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
            if ($givenans === 'DNE' || $givenans === 'oo') {
              $_POST["qn$qn-val"] = $givenans;
            }
        }
        $givenans = trim($givenans," ,");
        $scorePartResult->setLastAnswerAsGiven($givenans);
        if ($hasNumVal) {
          $givenansval = trim($_POST["qn$qn-val"]," ,");
          $scorePartResult->setLastAnswerAsNumber($givenansval);
        }
        if ($answer==='') {
            if (trim($givenans)==='') {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            } else {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        }

        if ($givenans == null) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        // rewrite +-
        if (in_array('allowplusminus', $ansformats)) {
            if (!in_array('list', $ansformats)) {
                $ansformats[] = 'list';
            }
            $answer = rewritePlusMinus($answer);
            $givenans = rewritePlusMinus($givenans);    
        }

        $isListAnswer = (in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats) || in_array('list',$ansformats));
        $formatok = "all";
        // check requiretimes on whole now if it's a list. 
        // if not a list, we'll check it later
        if ($isListAnswer && !empty($requiretimes) && checkreqtimes($givenans, $requiretimes)==0) {
            //return 0;
            $formatok = "nowhole";
        }

        if ($reqsigfigs !== '') {
            if (!in_array("scinot",$ansformats) && !in_array("scinotordec",$ansformats) && !in_array("decimal",$ansformats)) {
                $reqsigfigs = '';
            } else {
                list($reqsigfigs, $exactsigfig, $reqsigfigoffset, $sigfigscoretype) = parsereqsigfigs($reqsigfigs);
            }
        }

        if (!empty($requiretimeslistpart) && strpos($requiretimeslistpart,';')!==false) {
            $requiretimeslistpart = explode(';', $requiretimeslistpart);
        }

        $checkSameform = (in_array('sameform',$ansformats));

        if (in_array("scinot",$ansformats) || in_array("scinotordec",$ansformats)) {
            $answer = str_replace('xx','*',$answer);
            $givenans = str_replace('xx','*',$givenans);
        }
        if (in_array("allowxtimes",$ansformats)) {
            $answer = str_replace('x','*',$answer);
            $givenans = str_replace('x','*',$givenans);
        }
        if (in_array('set',$ansformats) || in_array('exactset',$ansformats)) {
            $answer = str_replace(array('{','}'),'', $answer);
            $givenans = str_replace(array('{','}'),'', $givenans);
            $ansformats = array_map('trim',explode(',', str_replace('set','list',$answerformat)));
        }

        $ansnorm = array();

        //pre-evaluate all instructor expressions - preg match all intervals.  Return array of or options
        if ($isListAnswer) {
            $anarr = array_map('trim',explode(',',$answer));
            foreach ($anarr as $k=>$ananswer) {
                $aarr = explode(' or ',$ananswer);
                foreach ($aarr as $j=>$anans) {
                    if ($anans=='') {
                        if (isset($GLOBALS['teacherid'])) {
                            echo '<p>', _('Debug info: empty, missing or invalid $answer'), ' </p>';
                        }
                        $scorePartResult->setRawScore(0);
                        return $scorePartResult;
                    }
                    if ((!is_numeric($anans) || $checkSameform) && $anans!='DNE' && $anans!='oo' && $anans!='+oo' && $anans!='-oo') {
                        if ((in_array("mixednumber",$ansformats) || in_array("sloppymixednumber",$ansformats) || in_array("mixednumberorimproper",$ansformats) || in_array("allowmixed",$ansformats)) && preg_match('/^\s*(\-?\s*\d+)\s*(_|\s)\s*(\d+)\s*\/\s*(\d+)\s*$/',$anans,$mnmatches)) {
                            $aarr[$j] = $mnmatches[1] + (($mnmatches[1]<0)?-1:1)*($mnmatches[3]/$mnmatches[4]);
                            if ($checkSameform) {
                                $anfunc = parseMathQuiet($anans);
                                if ($anfunc !== false) {
                                    $ansnorm[$k][$j] = $anfunc->normalizeTreeString();
                                }
                            }
                        } else {
                            $anfunc = parseMathQuiet($anans);
                            if ($anfunc !== false) {
                                $aarr[$j] = $anfunc->evaluateQuiet();
                                if ($checkSameform) {
                                    $ansnorm[$k][$j] = $anfunc->normalizeTreeString();
                                }
                            } else {
                                $aarr[$j] = '';
                            }
                        }
                    } 
                }
                $anarr[$k] = $aarr;
            }
        } else {
            $aarr = array_map('trim',explode(' or ',$answer));
            $partialpts = array_fill(0, count($aarr), 1);
            $origanscnt = count($aarr);
            if (!empty($partialcredit) && !$isListAnswer) { // partial credit only works for non-list answers
                if (!is_array($partialcredit)) {$partialcredit = explode(',',$partialcredit);}
                $removeReqTimesOnDup = (is_array($requiretimes) && 2*count($requiretimes) > count($partialcredit));
                $removeAnsFmtOnDup = (is_array($answerformat) && 2*count($answerformat) > count($partialcredit));
                for ($i=0;$i<count($partialcredit);$i+=2) {
                    if (!in_array($partialcredit[$i], $aarr) || $partialcredit[$i+1]<1) {
                        $aarr[] = $partialcredit[$i];
                        $partialpts[] = $partialcredit[$i+1];
                    } else {
                        // ignoring element; need to remove corresponding from requiretimes and answerformat
                        if ($removeAnsFmtOnDup) {
                            unset($answerformat[1+$i/2]);
                        }
                        if ($removeReqTimesOnDup) {
                            unset($requiretimes[1+$i/2]);
                        }
                    }
                } 
                if (is_array($answerformat)) {
                    $answerformat = array_values($answerformat);
                }
                if (is_array($requiretimes)) {
                    $requiretimes = array_values($requiretimes);
                }
            }
            foreach ($aarr as $j=>$anans) {
                if ($anans=='') {
                    if (isset($GLOBALS['teacherid'])) {
                        echo '<p>', _('Debug info: empty, missing, or invalid $answer'), ' </p>';
                    }
                    $scorePartResult->setRawScore(0);
                    return $scorePartResult;
                }
                if (preg_match('/(\(|\[)\s*(.+?)\s*\,\s*(.+?)\s*(\)|\])/',$anans,$matches)) {
                    if ($matches[2]=='-oo') {$matches[2] = -1e99;}
                    if ($matches[3]=='oo') {$matches[3] = 1e99;}
                    if (!is_numeric($matches[2])) {
                        $matches[2] = evalMathParser($matches[2]);
                    }
                    if (!is_numeric($matches[3])) {
                        $matches[3] = evalMathParser($matches[3]);
                    }
                    $aarr[$j] = $matches;
                } else if ((!is_numeric($anans) || $checkSameform) && $anans!='DNE' && $anans!='oo' && $anans!='+oo' && $anans!='-oo') {
                    if ((in_array("mixednumber",$ansformats) || in_array("sloppymixednumber",$ansformats) || in_array("mixednumberorimproper",$ansformats) || in_array("allowmixed",$ansformats)) && preg_match('/^\s*(\-?\s*\d+)\s*(_|\s)\s*(\d+)\s*\/\s*(\d+)\s*$/',$anans,$mnmatches)) {
                        $aarr[$j] = $mnmatches[1] + (($mnmatches[1]<0)?-1:1)*($mnmatches[3]/$mnmatches[4]);
                        if ($checkSameform) {
                            $anfunc = parseMathQuiet($anans);
                            if ($anfunc !== false) {
                                $ansnorm[0][$j] = $anfunc->normalizeTreeString();
                            }
                        }
                    } else {
                        $anfunc = parseMathQuiet(str_replace(',','',$anans));
                        if ($anfunc !== false) {
                            $aarr[$j] = $anfunc->evaluateQuiet();
                            if ($checkSameform) {
                                $ansnorm[0][$j] = $anfunc->normalizeTreeString();
                            }
                        } else {
                            $aarr[$j] = '';
                        }
                    }
                }
            }
            $answer = $aarr;
        }

        if (!$hasNumVal) {
            if ($isListAnswer) {
                $gaarr = array_map('trim',explode(',',$givenans));
            } else {
                $gaarr = array(trim(str_replace(',','', $givenans)));
            }
            $numvalarr = array();
            foreach ($gaarr as $j=>$v) {
                if ($v!='DNE' && $v!='oo' && $v!='+oo' && $v!='-oo') {
                    if ((in_array("mixednumber",$ansformats) || in_array("sloppymixednumber",$ansformats) || in_array("mixednumberorimproper",$ansformats) || in_array("allowmixed",$ansformats)) && preg_match('/^\s*(\-?)\s*(\d+)\s*(_|\s)\s*(\d+)\s*\/\s*(\d+)\s*$/',$v,$mnmatches)) {
                        $numvalarr[$j] = ($mnmatches[1]=='-'?-1:1)*($mnmatches[2] + ($mnmatches[4]/$mnmatches[5]));
                    } else {
                        if ($v[strlen($v)-1]=='%') {//single percent
                            $val = substr($v,0,-1);
                            if (is_numeric($val)) { // if is number%, eval
                                $numvalarr[$j] = $val/100;
                                continue;
                            }
                        }
                        $numvalarr[$j] = evalMathParser($v);
                        if (!is_numeric($numvalarr[$j]) || !is_finite($numvalarr[$j])) {
                          $numvalarr[$j] = '';
                        }
                    }
                } else {
                    $numvalarr[$j] = $v;
                }
            }
            $givenansval = implode(',', $numvalarr);
            $scorePartResult->setLastAnswerAsNumber($givenansval);
        } else {
          $numvalarr = explode(',', $givenansval);
        }

        if (in_array('exactlist',$ansformats)) {
            $gaarr = array_map('trim',explode(',',$givenans));
        } else if (in_array('orderedlist',$ansformats)) {
            $gamasterarr = array_map('trim',explode(',',$givenans));
            $gaarr = $gamasterarr;
            //$anarr = explode(',',$answer);
        } else if (in_array('list',$ansformats)) {
            $tmp = array_map('trim',explode(',',$givenans));
            $tmpnumval = $numvalarr;
            $numvalarr = [];
            $gaarr = [];
            asort($tmpnumval);
            $lastval = null;
            foreach ($tmpnumval as $i=>$v) {
                if ($lastval===null || !is_numeric($lastval)) {
                    $gaarr[] = $tmp[$i];
                    $numvalarr[] = $tmpnumval[$i];
                } else if (is_numeric($v)) {
                    if (abs($v-$lastval)>1E-12) {
                        $gaarr[] = $tmp[$i];
                        $numvalarr[] = $tmpnumval[$i];
                    }
                }
                $lastval = $v;
            }

            $tmp = $anarr;
            asort($tmp);
            $anarr = [];
            if (!empty($requiretimeslistpart) && is_array($requiretimeslistpart)) {
                $tmprtlp = $requiretimeslistpart;
                $requiretimeslistpart = [];
            }
            if ($checkSameform) {
                $tmpansnorm = $ansnorm;
                $ansnorm = [];
            }

            $lastkey = -1;
            foreach ($tmp as $key=>$v) {
                if ($lastkey == -1 || !is_numeric($tmp[$key][0]) || !is_numeric($tmp[$lastkey][0]) || count($tmp[$key])>1 || count($tmp[$lastkey])>1 || abs($tmp[$key][0]-$tmp[$lastkey][0])>1E-12) {
                    $anarr[] = $v;
                    if (!empty($requiretimeslistpart) && is_array($requiretimeslistpart)) {
                        $requiretimeslistpart[] = $tmprtlp[$key];
                    }
                    if ($checkSameform) {
                        $ansnorm[] = $tmpansnorm[$key];
                    }
                }
                $lastkey = $key;
            }

        } else {
            $gaarr = array(str_replace(',','',$givenans));
            $anarr = array($answer);
        }

        if ($checkSameform) {
            $ganorm = array();
            foreach ($gaarr as $toevalGivenans) {
                if ($toevalGivenans=='DNE' || $toevalGivenans=='oo' || $toevalGivenans=='+oo' || $toevalGivenans=='-oo') {
                    $ganorm[] =  $toevalGivenans;
                } else {
                    $givenansfunc = parseMathQuiet($toevalGivenans, '', [], '', true);
                    if ($givenansfunc !== false) {
                        $ganorm[] = $givenansfunc->normalizeTreeString();
                    } else {
                        $ganorm[] = '';
                    }
                }
            }
        }

        $extrapennum = count($gaarr)+count($anarr);
        $gaarrcnt = count($gaarr);

        if (in_array('orderedlist',$ansformats)) {
            if (count($gamasterarr)!=count($anarr)) {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        }

        $correct = 0;
        $correctanyformat = 0;
        foreach($anarr as $i=>$anss) {
            $foundloc = -1;
            if (in_array('orderedlist',$ansformats)) {
                $gaarr = array($gamasterarr[$i]);
            }
            foreach($gaarr as $j=>$givenans) {
                $numericans = $numvalarr[$j];
                
                //removed - done above already
                //$anss = explode(' or ',$answer);
                foreach ($anss as $k=>$anans) {
                    if (is_array($requiretimes)) {
                        if ($k<$origanscnt) {
                            $thisreqtimes = $requiretimes[0] ?? '';
                        } else {
                            $thisreqtimes = $requiretimes[$k-$origanscnt+1] ?? '';
                        }
                    } else {
                        $thisreqtimes = $requiretimes;
                    }
                    if (is_array($answerformat)) {
                        if ($k<$origanscnt) {
                            $thisansfmt = explode(',', $answerformat[0] ?? '');
                        } else {
                            $thisansfmt = explode(',', $answerformat[$k-$origanscnt+1] ?? '');
                        }
                        $thischecksameform = in_array('sameform', $thisansfmt);
                    } else {
                        $thisansfmt = $ansformats;
                        $thischecksameform = $checkSameform;
                    }
                    $partformatok = true;
                    if (!$isListAnswer) {
                        $formatok = "all";
                    }
                    if (!$isListAnswer && !empty($thisreqtimes) && checkreqtimes($givenans, $thisreqtimes)==0) {
                        //return 0;
                        $formatok = "nowhole"; $partformatok = false;
                    }
                    if (!checkanswerformat($givenans,$thisansfmt)) {
                        $formatok = "nopart";  $partformatok = false;
                        //continue;
                    }
                    if (!empty($requiretimeslistpart) && !is_array($requiretimeslistpart) && checkreqtimes($givenans,$requiretimeslistpart)==0) {
                        $formatok = "nopart";  $partformatok = false;
                        //continue;
                    }
                    if (!is_numeric($anans)) {
                        if (is_array($anans)) {
                            if (($anans[1]=="(" && $numericans>$anans[2]) || ($anans[1]=="[" && $numericans>=$anans[2])) {
                                if (($anans[4]==")" && $numericans<$anans[3]) || ($anans[4]=="]" && $numericans<=$anans[3])) {
                                    if ($partformatok) {$correct += $partialpts[$k] ?? 1;}; $correctanyformat++; $foundloc = $j; break 2;
                                }
                            }
                        } else if ($anans=="DNE" && strtoupper($givenans)=="DNE") {
                            if ($partformatok) {$correct += $partialpts[$k] ?? 1;}; $correctanyformat++; $foundloc = $j; break 2;
                        } else if (($anans=="+oo" || $anans=="oo") && ($givenans=="+oo" || $givenans=="oo")) {
                            if ($partformatok) {$correct += $partialpts[$k] ?? 1;}; $correctanyformat++; $foundloc = $j; break 2;
                        } else if ($anans=="-oo" && $givenans=="-oo") {
                            if ($partformatok) {$correct += $partialpts[$k] ?? 1;}; $correctanyformat++; $foundloc = $j; break 2;
                        }
                    } else if (is_numeric($numericans)) {
                        //echo "{$ganorm[$j]} vs {$ansnorm[$i][$k]}";
                        if ($reqsigfigs !== '') {
                            $tocheck = preg_replace('/\s*(\*|x|X|×|✕)\s*10\s*\^/','E',$givenans);
                            if (checksigfigs($tocheck, $anans, $reqsigfigs, $exactsigfig, $reqsigfigoffset, $sigfigscoretype)) {
                                if (!empty($requiretimeslistpart) && is_array($requiretimeslistpart) && checkreqtimes($givenans,$requiretimeslistpart[$i])==0) {
                                    $formatok = "nopart";  $partformatok = false;
                                } else if ($thischecksameform && $ganorm[$j] != $ansnorm[$i][$k]) {
                                    $formatok = "nopart";  $partformatok = false;
                                }
                                if ($partformatok) {$correct += $partialpts[$k] ?? 1;}; $correctanyformat++; $foundloc = $j; break 2;
                            } else if ($exactsigfig && checksigfigs($tocheck, $anans, $reqsigfigs, false, $reqsigfigoffset, $sigfigscoretype)) {
                                //see if it'd be right aside from exact sigfigs
                                $formatok = "nopart";  $partformatok = false; $correctanyformat++; $foundloc = $j; break 2;
                            } 
                        } else if ($abstolerance !== '') {
                            if (abs($anans-$numericans) < $abstolerance+(($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12))) {
                                if (!empty($requiretimeslistpart) && is_array($requiretimeslistpart) && checkreqtimes($givenans,$requiretimeslistpart[$i])==0) {
                                    $formatok = "nopart";  $partformatok = false;
                                } else if ($thischecksameform && $ganorm[$j] != $ansnorm[$i][$k]) {
                                    $formatok = "nopart";  $partformatok = false;
                                }
                                if ($partformatok) {$correct += $partialpts[$k] ?? 1;}; $correctanyformat++; $foundloc = $j; break 2;
                            }
                        } else {
                            if ($anans==0) {
                                if (abs($anans - $numericans) < $reltolerance/1000 + 1E-12) {
                                    if (!empty($requiretimeslistpart) && is_array($requiretimeslistpart) && checkreqtimes($givenans,$requiretimeslistpart[$i])==0) {
                                        $formatok = "nopart";  $partformatok = false;
                                    } else if ($thischecksameform && $ganorm[$j] != $ansnorm[$i][$k]) {
                                        $formatok = "nopart";  $partformatok = false;
                                    }
                                    if ($partformatok) {$correct += $partialpts[$k] ?? 1;}; $correctanyformat++; $foundloc = $j; break 2;
                                }
                            } else {
                                if (abs($anans - $numericans)/(abs($anans)+(abs($anans)>1?1E-12:(abs($anans)*1E-12))) < $reltolerance+1E-12) {
                                    if (!empty($requiretimeslistpart) && is_array($requiretimeslistpart) && checkreqtimes($givenans,$requiretimeslistpart[$i])==0) {
                                        $formatok = "nopart";  $partformatok = false;
                                    } else if ($thischecksameform && $ganorm[$j] != $ansnorm[$i][$k]) {
                                        $formatok = "nopart";  $partformatok = false;
                                    }
                                    if ($partformatok) {$correct += $partialpts[$k] ?? 1;}; $correctanyformat++; $foundloc = $j; break 2;
                                }
                            }
                        }
                    }
                }
            }
            if ($foundloc>-1) {
                array_splice($gaarr,$foundloc,1); //remove from list
                if ($thischecksameform) {
                  array_splice($ganorm,$foundloc,1);
                }
                array_splice($numvalarr,$foundloc,1);
                if (count($gaarr)==0 && !in_array('orderedlist',$ansformats)) {
                    break; //stop if no student answers left
                }
            }
        }
        if (count($anarr) == 0) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        if (in_array('orderedlist',$ansformats)) {
            $score = $correct/count($anarr);
            $scorePartResult->setRawScore($score);
        } else {
            //$score = $correct/count($anarr) - count($gaarr)/$extrapennum;  //take off points for extranous stu answers
            if ($gaarrcnt<=count($anarr)) {
                $score = $correct/count($anarr);
                $scorePartResult->setRawScore($score);
            } else {
                $score = $correct/count($anarr) - ($gaarrcnt-count($anarr))/$extrapennum;  //take off points for extranous stu answers
                $scorePartResult->setRawScore($score);
            }
        }
        if ($score<0) { $scorePartResult->setRawScore(0); }
        if ($formatok != "all" && $correctanyformat>0) {
            if (!empty($formatfeedbackon)) {
                $scorePartResult->setCorrectAnswerWrongFormat(true);
            }
            if ($formatok == 'nowhole') {
                $scorePartResult->setRawScore(0);
            }
        }
        return $scorePartResult;
    }
}
