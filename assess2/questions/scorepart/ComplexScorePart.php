<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class ComplexScorePart implements ScorePart
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

        if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}
        if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$partnum];} else {$reltolerance = $options['reltolerance'];}}
        if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$partnum];} else {$abstolerance = $options['abstolerance'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['requiretimes'])) {if (is_array($options['requiretimes'])) {$requiretimes = $options['requiretimes'][$partnum];} else {$requiretimes = $options['requiretimes'];}}
        if (isset($options['requiretimeslistpart'])) {if (is_array($options['requiretimeslistpart'])) {$requiretimeslistpart = $options['requiretimeslistpart'][$partnum];} else {$requiretimeslistpart = $options['requiretimeslistpart'];}}
        if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$partnum];} else {$ansprompt = $options['ansprompt'];}}

        if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $hasNumVal = !empty($_POST["qn$qn-val"]);
        if ($hasNumVal) {
          $givenansval = $_POST["qn$qn-val"];
        }

        if (!isset($answerformat)) { $answerformat = '';}
        $ansformats = array_map('trim',explode(',',$answerformat));

        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
            if ($givenans === 'DNE' || $givenans === 'oo') {
              $_POST["qn$qn-val"] = $givenans;
            }
        }
        $givenans = normalizemathunicode($givenans);
        $scorePartResult->setLastAnswerAsGiven($givenans);
        if ($anstype=='calccomplex' && $hasNumVal) {
            $scorePartResult->setLastAnswerAsNumber($givenansval);
        }
        if ($givenans == null) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        $answer = str_replace(' ','',makepretty($answer));
        $givenans = trim($givenans);

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
        }

        $gaarr = array_map('trim',explode(',',$givenans));

        if ($anstype=='calccomplex') {
            //test for correct format, if specified
            if (($answer!='DNE'&&$answer!='oo') && checkreqtimes($givenans,$requiretimes)==0) {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
            foreach ($gaarr as $i=>$tchk) {
                if (in_array('sloppycomplex',$ansformats)) {
                    $tchk = str_replace(array('sin','pi'),array('s$n','p$'),$tchk);
                    if (substr_count($tchk,'i')>1) {
                        $scorePartResult->setRawScore(0);
                        return $scorePartResult;
                    }
                    $tchk = str_replace(array('s$n','p$'),array('sin','pi'),$tchk);
                } else {
                    // TODO: rewrite using mathparser
                    $cpts = $this->parsecomplex($tchk);

                    if (!is_array($cpts)) {
                        $scorePartResult->setRawScore(0);
                        return $scorePartResult;
                    }
                    $cpts[1] = ltrim($cpts[1], '+');
                    $cpts[1] = rtrim($cpts[1], '*');

                    //echo $cpts[0].','.$cpts[1].'<br/>';
                    if ($answer!='DNE'&&$answer!='oo' && (!checkanswerformat($cpts[0],$ansformats) || !checkanswerformat($cpts[1],$ansformats))) {
                        //return 0;
                        unset($gaarr[$i]);
                    }
                    if ($answer!='DNE'&&$answer!='oo' && isset($requiretimeslistpart) && checkreqtimes($tchk,$requiretimeslistpart)==0) {
                        //return 0;
                        unset($gaarr[$i]);
                    }
                }
            }
        }

        $ganumarr = array();
        foreach ($gaarr as $j=>$givenans) {
            $gaparts = $this->parsesloppycomplex($givenans);
            if ($gaparts === false) {  //invalid - skip it
              continue;
            }
            if (!in_array('exactlist',$ansformats)) {
                // don't add if we already have it in the list
                foreach ($ganumarr as $prevvals) {
                    if (abs($gaparts[0]-$prevvals[0])<1e-12 && abs($gaparts[1]-$prevvals[1])<1e-12) {
                        continue 2; //skip adding it to the list
                    }
                }
            }
            $ganumarr[] = $gaparts;
        }

        $anarr = array_map('trim',explode(',',$answer));
        $annumarr = array();
        foreach ($anarr as $i=>$answer) {
            $ansparts = $this->parsesloppycomplex($answer);
            if ($ansparts === false) {  //invalid - skip it
              continue;
            }
            if (!in_array('exactlist',$ansformats)) {
                foreach ($annumarr as $prevvals) {
                    if (abs($ansparts[0]-$prevvals[0])<1e-12 && abs($ansparts[1]-$prevvals[1])<1e-12) {
                        continue 2; //skip adding it to the list
                    }
                }
            }
            $annumarr[] = $ansparts;
        }

        if (count($ganumarr)==0) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        $extrapennum = count($ganumarr)+count($annumarr);
        $correct = 0;
        foreach ($annumarr as $i=>$ansparts) {
            $foundloc = -1;

            foreach ($ganumarr as $j=>$gaparts) {
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
                array_splice($ganumarr,$foundloc,1); // remove from list
                if (count($ganumarr)==0) {
                    break;
                }
            }
        }
        $score = $correct/count($annumarr) - count($ganumarr)/$extrapennum;
        if ($score<0) {
            $scorePartResult->setRawScore(0);
        } else {
            $scorePartResult->setRawScore($score);
        }
        return $scorePartResult;
    }

    private function parsesloppycomplex($v) {
        $func = makeMathFunction($v, 'i');
        if ($func===false) {
          return false;
        }
        $a = $func(['i'=>0]);
        $apb = $func(['i'=>4]);
        return array($a,($apb-$a)/4);
    }

    /**
	  * parses complex numbers.  Can handle anything, but only with
      * one i in it.
      */
    private function parsecomplex($v) {
        $v = str_replace(' ','',$v);
        $v = str_replace(array('sin','pi'),array('s$n','p$'),$v);
        $v = preg_replace('/\((\d+\*?i|i)\)\/(\d+)/','$1/$2',$v);
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
                    $c = $v[$L];
                    if ($c==')') {
                        $nd++;
                    } else if ($c=='(') {
                        $nd--;
                    } else if (($c=='+' || $c=='-') && $nd==0) {
                        break;
                    }
                }
                if ($L<0) {$L=0;}
                if ($nd != 0) {
                    return _('error - invalid form');
                }
                //look right
                $nd = 0;

                for ($R=$p+1;$R<$len;$R++) {
                    $c = $v[$R];
                    if ($c=='(') {
                        $nd++;
                    } else if ($c==')') {
                        $nd--;
                    } else if (($c=='+' || $c=='-') && $nd==0) {
                        break;
                    }
                }
                if ($nd != 0) {
                    return _('error - invalid form');
                }
                //which is bigger?
                if ($p-$L>0 && $R-$p>0 && ($R==$len || $L==0)) {
                    //return _('error - invalid form');
                    if ($R==$len) {// real + AiB
                        $real = substr($v,0,$L);
                        $imag = substr($v,$L,$p-$L);
                        $imag .= '*'.substr($v,$p+1+($v[$p+1]=='*'?1:0),$R-$p-1);
                    } else if ($L==0) { //AiB + real
                        $real = substr($v,$R);
                        $imag = substr($v,0,$p);
                        $imag .= '*'.substr($v,$p+1+($v[$p+1]=='*'?1:0),$R-$p-1);
                    } else {
                        return _('error - invalid form');
                    }
                    $imag = str_replace('-*','-1*',$imag);
                    $imag = str_replace('+*','+1*',$imag);
                } else if ($p-$L>1) {
                    $imag = substr($v,$L,$p-$L);
                    $real = substr($v,0,$L) . substr($v,$p+1);
                } else if ($R-$p>1) {
                    if ($p>0) {
                        if ($v[$p-1]!='+' && $v[$p-1]!='-') {
                            return _('error - invalid form');
                        }
                        $imag = $v[$p-1].substr($v,$p+1+($v[$p+1]=='*'?1:0),$R-$p-1);
                        $real = substr($v,0,$p-1) . substr($v,$R);
                    } else {
                        $imag = substr($v,$p+1,$R-$p-1);
                        $real = substr($v,0,$p) . substr($v,$R);
                    }
                } else { //i or +i or -i or 3i  (one digit)
                    if ($v[$L]=='+') {
                        $imag = 1;
                    } else if ($v[$L]=='-') {
                        $imag = -1;
                    } else if ($p==0) {
                        $imag = 1;
                    } else {
                        $imag = $v[$L];
                    }
                    $real = ($p>0?substr($v,0,$L):'') . substr($v,$p+1);
                }
                if ($real=='') {
                    $real = 0;
                }
                if ($imag[0]=='/') {
                    $imag = '1'.$imag;
                } else if (($imag[0]=='+' || $imag[0]=='-') && $imag[1]=='/') {
                    $imag = $imag[0].'1'.substr($imag,1);
                }
                $imag = str_replace('*/','/',$imag);
                if (substr($imag,-1)=='*') {
                    $imag = substr($imag,0,-1);
                }
            }
            $real = str_replace(array('s$n','p$'),array('sin','pi'),$real);
            $imag = str_replace(array('s$n','p$'),array('sin','pi'),$imag);
            return array($real,$imag);
        }
    }

}
