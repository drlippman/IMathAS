<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

class ChoicesAnswerBox implements AnswerBox
{
    private $answerBoxParams;

    private $answerBox;
    private $entryTip;
    private $correctAnswerForPart;
    private $previewLocation;

    public function __construct(AnswerBoxParams $answerBoxParams)
    {
        $this->answerBoxParams = $answerBoxParams;
    }

    public function generate(): void
    {
        global $RND, $myrights, $useeqnhelper, $showtips, $imasroot;

        $anstype = $this->answerBoxParams->getAnswerType();
        $qn = $this->answerBoxParams->getQuestionNumber();
        $multi = $this->answerBoxParams->getIsMultiPartQuestion();
        $partnum = $this->answerBoxParams->getQuestionPartNumber();
        $la = $this->answerBoxParams->getStudentLastAnswers();
        $options = $this->answerBoxParams->getQuestionWriterVars();
        $colorbox = $this->answerBoxParams->getColorboxKeyword();

        // FIXME: The following code needs to be updated
        //        - $qn is always the question number (never $qn+1)
        //        - $multi is now a boolean
        //        - $partnum is now available

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';

        if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$partnum];} else {$displayformat = $options['displayformat'];}} else {$displayformat="vert";}
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if (is_array($options['questions'][$partnum])) {$questions = $options['questions'][$partnum];} else {$questions = $options['questions'];}
        if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$partnum];} else {$noshuffle = $options['noshuffle'];}} else {$noshuffle = "none";}

        if (!is_array($questions)) {
            throw new RuntimeException(_('Eeek!  $questions is not defined or needs to be an array'));
            return;
        }

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        if ($noshuffle == "last") {
            $randkeys = $RND->array_rand(array_slice($questions,0,count($questions)-1),count($questions)-1);
            $RND->shuffle($randkeys);
            array_push($randkeys,count($questions)-1);
        } else if ($noshuffle == "all") {
            $randkeys = array_keys($questions);
        } else if (strlen($noshuffle)>4 && substr($noshuffle,0,4)=="last") {
            $n = intval(substr($noshuffle,4));
            if ($n>count($questions)) {
                $n = count($questions);
            }
            $randkeys = $RND->array_rand(array_slice($questions,0,count($questions)-$n),count($questions)-$n);
            $RND->shuffle($randkeys);
            for ($i=count($questions)-$n;$i<count($questions);$i++) {
                array_push($randkeys,$i);
            }
        } else {
            $randkeys = $RND->array_rand($questions,count($questions));
            $RND->shuffle($randkeys);
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
            $out .= "<span $style id=\"qnwrap$qn\" role=radiogroup aria-label=\""._('Select an answer')."\">";
        } else if ($displayformat != 'select') {
            if ($colorbox != '') {$style .= ' class="'.$colorbox.' clearfix" ';} else {$style=' class="clearfix" ';}
            $out .= "<div $style id=\"qnwrap$qn\" style=\"display:block\" role=radiogroup aria-label=\""._('Select an answer')."\">";
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
            $out = "<select name=\"qn$qn\" id=\"qn$qn\" $style aria-label=\""._('Select an answer')."\"><option value=\"NA\">$msg</option>\n";
        } else if ($displayformat == "horiz") {

        } else if ($displayformat == "inline") {

        } else if ($displayformat == 'column') {

        } else {
            $out .= '<ul class=nomark>';
        }


        for ($i=0; $i < count($randkeys); $i++) {
            if ($displayformat == "horiz") {
                $out .= "<div class=choice><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label><br/><input type=radio id=\"qn$qn-$i\" name=qn$qn value=$i ";
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
                $out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label></li> \n";
            } else {
                $out .= "<li><input class=\"unind\" type=radio name=qn$qn value=$i id=\"qn$qn-$i\" ";
                if (($la!='') && ($la == $i)) { $out .= "CHECKED";}
                $out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label></li> \n";
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


        // Done!
        $this->answerBox = $out;
        $this->entryTip = $tip;
        $this->correctAnswerForPart = $sa;
        $this->previewLocation = $preview;
    }

    public function getAnswerBox(): string
    {
        return $this->answerBox;
    }

    public function getEntryTip(): string
    {
        return $this->entryTip;
    }

    public function getCorrectAnswerForPart(): string
    {
        return $this->correctAnswerForPart;
    }

    public function getPreviewLocation(): string
    {
        return $this->previewLocation;
    }
}
