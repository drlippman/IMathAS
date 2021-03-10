<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class StringScorePart implements ScorePart
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

        if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}
        if (isset($options['strflags'])) {if (is_array($options['strflags'])) {$strflags = $options['strflags'][$partnum];} else {$strflags = $options['strflags'];}}
        if (isset($options['scoremethod']))if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$partnum];} else {$scoremethod = $options['scoremethod'];}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $givenans = normalizemathunicode($givenans);

        $scorePartResult->setLastAnswerAsGiven($givenans);

        if (isset($scoremethod) &&
            (($scoremethod=='takeanything' && trim($givenans)!='') ||
                $scoremethod=='takeanythingorblank')
        ) {
            $scorePartResult->setRawScore(1);
            return $scorePartResult;
        }

        if (!isset($answerformat)) { $answerformat = "normal";}
        if ($answerformat=='list') {
            $gaarr = array_map('trim',explode(',',$givenans));
            $anarr = array_map('trim',explode(',',$answer));
            $gaarrcnt = count($gaarr);
        } else {
            $gaarr = array($givenans);
            $anarr = array($answer);
        }
        $strflags = str_replace(' ','',$strflags);
        $strflags = explode(",",$strflags);
        $torem = array();
        foreach($strflags as $flag) {
            $pc = array_map('trim',explode('=',$flag,2));
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
                    if ($flags['partial_credit']===true && $answerformat!='list' && strlen($givenans)<250) {
                        $poss = strlen($anans);
                        $dist = levenshtein($anans,$givenans);
                        $score = ($poss - $dist)/$poss;
                        if ($score>$correct) { $correct = $score;}
                    } else if (isset($flags['allow_diff']) && strlen($givenans)<250) {
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
        if ($gaarrcnt <= count($anarr)) {
            $score = $correct/count($anarr);
        } else {
            $score = $correct/count($anarr) - ($gaarrcnt-count($anarr))/($gaarrcnt+count($anarr));
        }
        if ($score<0) { $score = 0; }
        $scorePartResult->setRawScore($score);
        return $scorePartResult;
        //return $correct;
    }
}
