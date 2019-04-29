<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');

use IMathAS\assess2\questions\models\ScoreQuestionParams;

class IntervalScorePart implements ScorePart
{
    private $scoreQuestionParams;

    public function __construct(ScoreQuestionParams $scoreQuestionParams)
    {
        $this->scoreQuestionParams = $scoreQuestionParams;
    }

    public function getScore(): int
    {
        global $mathfuncs;

        $RND = $this->scoreQuestionParams->getRandWrapper();
        $options = $this->scoreQuestionParams->getVarsForScorePart();
        $qn = $this->scoreQuestionParams->getQuestionNumber();
        $givenans = $this->scoreQuestionParams->getGivenAnswer();
        $multi = $this->scoreQuestionParams->getIsMultiPartQuestion();
        $partnum = $this->scoreQuestionParams->getQuestionPartNumber();

        $defaultreltol = .0015;

        if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}
        if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$partnum];} else {$reltolerance = $options['reltolerance'];}}
        if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$partnum];} else {$abstolerance = $options['abstolerance'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['requiretimes'])) {if (is_array($options['requiretimes'])) {$requiretimes = $options['requiretimes'][$partnum];} else {$requiretimes = $options['requiretimes'];}}
        if (isset($options['variables'])) {if (is_array($options['variables'])) {$variables = $options['variables'][$partnum];} else {$variables = $options['variables'];}}
        if (!isset($variables)) { $variables = 'x';}
        if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $ansformats = array_map('trim',explode(',',$answerformat));

        $givenans = normalizemathunicode($givenans);

        if (in_array('nosoln',$ansformats)) {
            list($givenans, $_POST["tc$qn"], $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt, in_array('inequality',$ansformats)?'inequality':'interval');
        }

        $givenans = str_replace('u', 'U', $givenans);
        $GLOBALS['partlastanswer'] = $givenans;
        if ($hasNumVal) {
            $GLOBALS['partlastanswer'] .= '$#$' . $givenansval;
        }
        if ($anstype == 'calcinterval') {
            //test for correct format, if specified
            if (checkreqtimes($givenans,$requiretimes)==0) {
                return 0;
            }
            if (in_array('inequality',$ansformats)) {
                $givenans = str_replace('or', ' or ', $givenans);
                if (preg_match('/all\s*real/i', $givenans)) {
                    $givenans = '(-oo,oo)';
                } else if (preg_match('/empty\s*set/i', $givenans)) {
                    $givenans = 'DNE';
                } else {
                    if ($variables != 'var') {
                        $givenans = str_replace('var','',$givenans);
                    }
                    //for simplicity, we're going to replace correct $variables with var
                    $givenans = str_ireplace($variables, 'var', $givenans);

                    // now it'll be easier to see if there's something unintended
                    preg_match_all('/([a-zA-Z]+)/',$givenans,$matches);
                    foreach ($matches[0] as $var) {
                        if (in_array($var,$mathfuncs)) { continue;}
                        if ($var!= 'or' && $var!='and' && $var!='DNE' && $var!='oo' &&
                            strtolower($var) != 'var') {
                            return 0;
                        }
                    }
                    $orarr = explode(' or ', $givenans);
                    foreach ($orarr as $opt) {
                        $opt = trim($opt);
                        if ($opt=='DNE' || $givenans=='(-oo,oo)') {continue;} //DNE or all real numbers
                        $opts = preg_split('/(<=?|>=?)/',$opt);
                        foreach ($opts as $optp) {
                            $optp = trim($optp);
                            if (strtolower($optp)=='var' || $optp=='oo' || $optp=='-oo') {continue;}
                            if (!checkanswerformat($optp,$ansformats)) {
                                return 0;
                            }
                        }
                    }
                    // convert it to an interval for scoring
                    if (!$hasNumVal) {
                        $givenans = ineqtointerval($givenans, 'var');
                    }
                }
            } else {
                if (in_array('list',$ansformats)) {
                    $orarr = preg_split('/(?<=[\)\]]),(?=[\(\[])/', $givenans);
                } else {
                    $orarr = explode('U', $givenans);
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

        if ($hasNumVal) {
            $gaarr = parseInterval($givenansval, in_array('list',$ansformats));
        } else {
            $gaarr = parseInterval($givenans, in_array('list',$ansformats));
        }

        if ($anstype == 'calcinterval' && !$hasNumVal) {
            $GLOBALS['partlastanswer'] .= '$#$' . parsedIntervalToSTring($gaarr);
        }
        if ($gaarr === false) {
            return 0;
        }
        foreach($ansar as $anans) {
            $answer = str_replace(' ','',$anans);

            if ($anans==='DNE') {
                if ($givenans==='DNE') {
                    $correct = 1; break;
                } else {
                    continue;
                }
            } else if ($givenans==='DNE') {
                continue;
            }

            $aarr = parseInterval($anans, in_array('list',$ansformats));
            if ($aarr === false) {
                return 0; //uh oh
            }

            if (count($aarr)!=count($gaarr)) {
                continue;
            }

            foreach ($aarr as $ansint) {
                $foundloc = -1;
                foreach ($gaarr as $k=>$gansint) {
                    // check brackets
                    if ($ansint['lb']!=$gansint['lb'] || $ansint['rb']!=$gansint['rb']) {
                        continue;
                    }
                    list($anssn, $ansen) = $ansint['vals'];
                    list($ganssn, $gansen) = $gansint['vals'];
                    if (!is_numeric($anssn) || !is_numeric($ganssn)) {
                        if ($anssn !== $ganssn) {
                            continue;
                        }
                    } else if (isset($abstolerance)) {
                        if (abs($anssn-$ganssn) < $abstolerance + 1E-12) {} else {continue;}
                    } else {
                        if (abs($anssn - $ganssn)/(abs($anssn)+.0001) < $reltolerance+ 1E-12) {} else {continue;}
                    }
                    if (!is_numeric($ansen) || !is_numeric($gansen)) {
                        if ($ansen !== $gansen) {
                            continue;
                        }
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
                continue;
            }
            $correct = 1;
            break;
        }
        return $correct;
    }
}
