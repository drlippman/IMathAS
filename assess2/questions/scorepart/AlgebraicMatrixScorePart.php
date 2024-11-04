<?php

namespace IMathAS\assess2\questions\scorepart;

require_once __DIR__ . '/ScorePart.php';
require_once __DIR__ . '/../models/ScorePartResult.php';
require_once __DIR__ . '/matrix_common.php';

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class AlgebraicMatrixScorePart implements ScorePart
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
            'answersize', 'scoremethod', 'ansprompt', 'domain', 'variables'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        if ($reltolerance === '' && $abstolerance === '') { $reltolerance = $defaultreltol;}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        $givenanslistvals = [];

        $ansformats = array_map('trim',explode(',',$answerformat));

        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
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

        // clean up givenans
        foreach ($givenanslist as $k=>$v) {
            $v = normalizemathunicode(trim($v, " \n\r\t\v\x00,"));
            $v = preg_replace_callback(
                '/(arcsinh|arccosh|arctanh|arcsin|arccos|arctan|arcsec|arccsc|arccot|root|sqrt|sign|sinh|cosh|tanh|sech|csch|coth|abs|sin|cos|tan|sec|csc|cot|exp|log|ln)[\(\[]/i',
                function($m) { return strtolower($m[0]); },
                $v
            );
            $givenanslist[$k] = $v;
        }
        
        // parse ans
        $answer = normalizemathunicode($answer);
        list($answerlist, $ansN) = parseMatrixToArray($answer);


        if (count($answerlist) != count($givenanslist)) {
            // wrong dimensions
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        $correct = true;
        $incorrect = array();

        if (empty($variables)) { $variables = "x";}
        list($variables, $tps, $flist) = numfuncGenerateTestpoints($variables, $domain);
        $vlist = implode(",",$variables);
        $isComplex = in_array('generalcomplex', $ansformats);
        $checkSameform = (in_array('sameform',$ansformats));
        if ($checkSameform) {
            $normalizedGivenAnswer = [];
            $normalizedAnswer = [];
        }
        $givenanslistvals = [];
        $answerlistvals = [];
        foreach ($givenanslist as $k => $ga) {
            $ga = numfuncPrepForEval($ga, $variables);
            $aa = numfuncPrepForEval($answerlist[$k], $variables);
            $givenansvals = [];
            $ansvals = [];
            $givenansfunc = parseMathQuiet($ga, $vlist, [], $flist, true, $isComplex);
            $ansfunc = parseMathQuiet($aa, $vlist, [], $flist, true, $isComplex);
            if ($givenansfunc === false || $ansfunc === false) { //parse error
                $incorrect[$k] = 1;
                $givenanslistvals[$k] = false;
                $anslistvals[$k] = false;
                continue;
            }
            if ($checkSameform) {
                $normalizedGivenAnswer[$k] = $givenansfunc->normalizeTreeString();
                $normalizedAnswer[$k] = $ansfunc->normalizeTreeString();
                if ($normalizedGivenAnswer[$k] != $normalizedAnswer[$k]) {
                    $incorrect[$k] = 1;
                    continue;
                }
            }
            for ($i = 0; $i < 20; $i++) {
                $varvals = array();
                for($j=0; $j < count($variables); $j++) {
                    $varvals[$variables[$j]] = $tps[$i][$j];
                }
                $givenansvals[] = $givenansfunc->evaluateQuiet($varvals);
                $ansvals[] = $ansfunc->evaluateQuiet($varvals);
            }
            $givenanslistvals[$k] = $givenansvals;
            $answerlistvals[$k] = $ansvals;
        }
 
        // loop over matrix entries
        for ($i=0; $i<count($answerlist); $i++) { 
            if (!empty($incorrect[$i]) || !isset($givenanslistvals[$i]) || $givenanslistvals[$i] === false) {
                $incorrect[$i] = 1;
                continue;
            } 
            $cntnan = 0;
            $stunan = 0;
            // loop over test points
            foreach ($answerlistvals[$i] as $k=>$ansval) {
                if (isNaN($ansval)) {
                    $cntnan++; 
                    continue;
                }
                $stuval = $givenanslistvals[$i][$k];
                if ($isComplex) {
                    if (!is_array($stuval)) {
                        $stunan++; 
                    } else if ($abstolerance !== '') {
                        if (abs($stuval[0]-$ansval[0]) > $abstolerance+1E-12) { $incorrect[$i] = 1; break;}
                        if (abs($stuval[1]-$ansval[1]) > $abstolerance+1E-12) { $incorrect[$i] = 1; break;}
                    } else {
                        if ((abs($stuval[0]-$ansval[0])/(abs($ansval[0])+.0001) > $reltolerance+1E-12)) {$incorrect[$i] = 1; break;}
                        if ((abs($stuval[1]-$ansval[1])/(abs($ansval[1])+.0001) > $reltolerance+1E-12)) {$incorrect[$i] = 1; break;}
                    }
                } else {
                    if (isNaN($stuval)) {
                        $stunan++; 
                    } else if ($abstolerance !== '') {
                        if (abs($stuval-$ansval) > $abstolerance+1E-12) { $incorrect[$i] = 1; break;}
                    } else {
                        if ((abs($stuval-$ansval)/(abs($ansval)+.0001) > $reltolerance+1E-12)) { $incorrect[$i] = 1; break;}
                    }
                }
            }
            if ($cntnan==20 && isset($GLOBALS['teacherid'])) {
                echo _('Debug info: function evaled to Not-a-number at all test points.  Check $domain');
            }
            if ($stunan>1) { //if more than 1 student NaN response
                $incorrect[$i] = 1;
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
