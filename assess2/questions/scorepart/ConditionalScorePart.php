<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');

use IMathAS\assess2\questions\models\ScoreQuestionParams;

class ConditionalScorePart implements ScorePart
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
//            $qn = ($qn+1)*1000+$partnum;
            // FIXME: Does the following line ($qnt) need to be changed?
            $qnt = 1000*($qn+1)+$i;
            if (isset($_POST["tc$qnt"])) {
                if ($anst=='calculated' || $anst=='calcmatrix') {
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
            } else if (isset($_POST["qn$qnt-0"])) {
                $tmp = array();
                $spc = 0;
                while (isset($_POST["qn$qnt-$spc"])) {
                    $tmp[] = $_POST["qn$qnt-$spc"];
                    $spc++;
                }
                $la[$i] = implode('|', $tmp);
                if (isset($_POST["qn$qnt"]) && $_POST["qn$qnt"] !== 'done') {
                    $stuav = str_replace(array('(',')','[',']'),'',$_POST["qn$qnt"]);
                    $la[$i] .= '$#$'.str_replace(',','|',$stuav);
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
