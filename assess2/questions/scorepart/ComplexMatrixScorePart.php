<?php

namespace IMathAS\assess2\questions\scorepart;

require_once __DIR__ . '/ScorePart.php';
require_once __DIR__ . '/../models/ScorePartResult.php';
require_once __DIR__ . '/matrix_common.php';

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class ComplexMatrixScorePart implements ScorePart
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

        $anstype = $this->scoreQuestionParams->getAnswerType();
        $RND = $this->scoreQuestionParams->getRandWrapper();
        $options = $this->scoreQuestionParams->getVarsForScorePart();
        $qn = $this->scoreQuestionParams->getQuestionNumber();
        $givenans = $this->scoreQuestionParams->getGivenAnswer();
        $multi = $this->scoreQuestionParams->getIsMultiPartQuestion();
        $partnum = $this->scoreQuestionParams->getQuestionPartNumber();
        $isRescore = $this->scoreQuestionParams->getIsRescore();

        $defaultreltol = .0015;

        $optionkeys = ['answer', 'reltolerance', 'abstolerance', 'answerformat',
            'answersize', 'scoremethod', 'ansprompt'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        if ($reltolerance === '' && $abstolerance === '') { $reltolerance = $defaultreltol;}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        $givenanslistvals = [];

        $givenans = normalizemathunicode($givenans);

        $ansformats = array_map('trim',explode(',',$answerformat));

        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
            if ($givenans === 'DNE' || $givenans === 'oo') {
              $_POST["qn$qn-val"] = $givenans;
            }
        }
        //store answers
        if ($givenans==='oo' || $givenans==='DNE') {
            $scorePartResult->setLastAnswerAsGiven($givenans);
        } else if (!empty($answersize)) {
            $sizeparts = explode(',',$answersize);
            $N = $sizeparts[0];
            $givenanslist = array();
            
            if ($isRescore) {
              $givenanslist = explode('|', $givenans);
            } else {
              for ($i=0; $i<$sizeparts[0]*$sizeparts[1]; $i++) {
                  $givenanslist[$i] = $_POST["qn$qn-$i"];
              }
            }
            $scorePartResult->setLastAnswerAsGiven(implode('|',$givenanslist));
        } else {
            list($givenanslist, $N) = parseMatrixToArray($givenans);
    
            //this may not be backwards compatible
            $scorePartResult->setLastAnswerAsGiven($givenans);
            if ($givenanslist === false) { // invalid answer
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        }
        
        list($answerlist, $ansN) = parseMatrixToArray($answer);

        //handle nosolninf case
        if ($givenans==='oo' || $givenans==='DNE') {
            if ($answer==$givenans) {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            } else {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        } else if ($answer==='DNE' || $answer==='oo') {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        $correct = true;
        $incorrect = array();

        foreach ($givenanslist as $i => $ga) {
            if (in_array('allowjcomplex', $ansformats)) {
                $ga = str_replace('j','i', $ga);
            }
            if ($anstype === 'calccomplexmatrix') {
                if (in_array('generalcomplex', $ansformats)) {
                    // skip format checks
                } else if (in_array('sloppycomplex', $ansformats)) {
                    $ga = str_replace(array('sin', 'pi'), array('s$n', 'p$'), $ga);
                    if (substr_count($ga, 'i') > 1) {
                        $givenanslistvals[$i] = false;
                        continue;
                    }
                    $ga= str_replace(array('s$n', 'p$'), array('sin', 'pi'), $ga);
                } else {
                    $cpts = parsecomplex($ga);
                    if (!is_array($cpts)) {
                        $givenanslistvals[$i] = false;
                        continue;
                    }
                    $cpts[1] = ltrim($cpts[1], '+');
                    $cpts[1] = rtrim($cpts[1], '*');
                    if (!checkanswerformat($cpts[0], $ansformats) || 
                        !checkanswerformat($cpts[1], $ansformats) 
                    ) {
                        $givenanslistvals[$i] = false;
                        continue;
                    }
                }
                if (in_array('generalcomplex', $ansformats)) {
                    $gaparts = parseGeneralComplex($ga);
                } else {
                    $gaparts = parsesloppycomplex($ga);
                }
                $givenanslistvals[] = $gaparts;
            } else { // $anstype === 'complexmatrix'
                // check for correct format
                $cpts = parsecomplex($ga);
                if (is_array($cpts) && is_numeric($cpts[0]) && is_numeric($cpts[1])) {
                    $givenanslistvals[] = $cpts;
                } else {
                    $givenanslistvals[] = false; // invalid
                }
            }
        }

        if ($anstype === 'calccomplexmatrix') {
            $numarr = [];
            foreach ($givenanslistvals as $v) {
                if (is_array($v)) {
                    $numarr[] = complexarr2str($v); 
                } else {
                    $numarr[] = 'NaN';
                }
            }
            $scorePartResult->setLastAnswerAsNumber(implode('|', $numarr));
        }

        foreach ($answerlist as $k=>$v) {
            if (in_array('allowjcomplex', $ansformats)) {
                $v = str_replace('j','i', $v);
            }
            $answerlist[$k] = parsesloppycomplex($v);
            if ($answerlist[$k] === false) {
                if (isset($GLOBALS['teacherid'])) {
                    echo _('Debug info: invalid $answer');
                }
            }
        }

        if (count($answerlist) != count($givenanslist) || $answerlist[0]==='' || $givenanslist[0]==='') {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        for ($i=0; $i<count($answerlist); $i++) {
            if (!isset($givenanslistvals[$i]) || $givenanslistvals[$i] === false || isNaN($givenanslistvals[$i])) {
                $incorrect[$i] = 1;
                continue;
            } else if ($answerlist[$i] === false) {
                continue;
            } else if ($abstolerance !== '') {
                if (abs($answerlist[$i][0] - $givenanslistvals[$i][0]) > $abstolerance+1E-12 ||
                    abs($answerlist[$i][1] - $givenanslistvals[$i][1]) > $abstolerance+1E-12) {
                    $incorrect[$i] = 1;
                    continue;
                }
            } else {
                if (abs($answerlist[$i][0] - $givenanslistvals[$i][0])/(abs($answerlist[$i][0])+.0001) > $reltolerance+1E-12 ||
                    abs($answerlist[$i][1] - $givenanslistvals[$i][1])/(abs($answerlist[$i][1])+.0001) > $reltolerance+1E-12) {
                    $incorrect[$i] = 1;
                    continue;
                }
            }
        }
        if ($correct && count($incorrect)==0) {
            $scorePartResult->setRawScore(1);
            return $scorePartResult;
        } else if ($correct && $scoremethod == 'byelement') {
            $score = (count($answerlist) - count($incorrect))/count($answerlist);
            $scorePartResult->setRawScore($score);
            return $scorePartResult;
        } else {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
    }
}
