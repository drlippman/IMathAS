<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class ChoicesAnswerBox implements AnswerBox
{
    private $answerBoxParams;

    private $answerBox;
    private $jsParams = [];
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
        $assessmentId = $this->answerBoxParams->getAssessmentId();
        $isConditional = $this->answerBoxParams->getIsConditional();

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $style = '';
        $params = [];

        $optionkeys = ['displayformat', 'answer', 'noshuffle', 'readerlabel', 'ansprompt'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        $questions = getOptionVal($options, 'questions', $multi, $partnum, 2);

        if (!is_array($questions)) {
            echo _('Eeek!  $questions is not defined or needs to be an array');
            $questions = array();
        }

        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}
        if ($noshuffle == "last" && count($questions)>0) {
            $randkeys = (array) $RND->array_rand(array_slice($questions, 0, count($questions) - 1), count($questions) - 1);
            $RND->shuffle($randkeys);
            array_push($randkeys, count($questions) - 1);
        } else if ($noshuffle == "all") {
            $randkeys = array_keys($questions);
        } else if (strlen($noshuffle) > 4 && substr($noshuffle, 0, 4) == "last") {
            $n = intval(substr($noshuffle, 4));
            if ($n > count($questions)) {
                $n = count($questions);
            }
            $randkeys = (array) $RND->array_rand(array_slice($questions, 0, count($questions) - $n), count($questions) - $n);
            $RND->shuffle($randkeys);
            for ($i = count($questions) - $n; $i < count($questions); $i++) {
                array_push($randkeys, $i);
            }
        } else {
            $randkeys = (array) $RND->array_rand($questions, count($questions));
            $RND->shuffle($randkeys);
        }
        $_SESSION['choicemap'][$assessmentId][$qn] = $randkeys;
        if (!empty($GLOBALS['inline_choicemap'])) {
            $params['choicemap'] = encryptval($randkeys, $GLOBALS['inline_choicemap']);
        }
        if (isset($GLOBALS['capturechoices'])) {
            $GLOBALS['choicesdata'][$qn] = $questions;
        }
        if (isset($GLOBALS['capturechoiceslivepoll'])) {
            $params['livepoll_choices'] = $questions;
            $params['livepoll_ans'] = $answer;
            $params['livepoll_randkeys'] = $randkeys;
        }

        if ($displayformat == 'column') {$displayformat = '2column';}

        if (substr($displayformat, 1) == 'column') {
            $ncol = $displayformat[0];
            $itempercol = ceil(count($randkeys) / $ncol);
            $displayformat = 'column';
        }

        $arialabel = $this->answerBoxParams->getQuestionIdentifierString() .
            (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : '');

        if ($displayformat == 'inline') {
            if ($colorbox != '') {$style .= ' class="' . $colorbox . '" ';} else { $style = '';}
            $out .= "<span $style id=\"qnwrap$qn\" role=radiogroup ";
            $out .= 'aria-label="' . $arialabel . '">';
        } else if ($displayformat != 'select') {
            if ($colorbox != '') {$style .= ' class="' . $colorbox . ' clearfix" ';} else { $style = ' class="clearfix" ';}
            $out .= "<div $style id=\"qnwrap$qn\" style=\"display:block\" role=radiogroup ";
            $out .= 'aria-label="' . $arialabel . '">';
        }
        if ($displayformat == "select") {
            $msg = '?';
            foreach ($questions as $qv) {
                if (is_array($qv)) {continue;}
                if (mb_strlen(html_entity_decode($qv)) > 3) { //strlen($qv)>2 && !($qv[0]=='&' && $qv[strlen($qv)-1]==';')) {
                    if ($ansprompt != '') {
                        $msg = $ansprompt;
                    } else {
                        $msg = _('Select an answer');
                    }
                    break;
                }
            }
            if ($colorbox != '') {$style .= ' class="' . $colorbox . '" ';} else { $style = '';}
            $out = "<select name=\"qn$qn\" id=\"qn$qn\" $style ";
            $out .= 'aria-label="' . $arialabel . '">';
            $out .= "<option value=\"NA\">$msg</option>\n";
        } else if ($displayformat == "horiz") {

        } else if ($displayformat == "inline") {

        } else if ($displayformat == 'column') {

        } else {
            $out .= '<ul class=nomark>';
        }

        for ($i = 0; $i < count($randkeys); $i++) {
            if ($displayformat == "horiz") {
                $out .= "<div class=choice><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label><br/><input type=radio id=\"qn$qn-$i\" name=qn$qn value=$i ";
                if (($la != '') && ($la == $randkeys[$i])) {$out .= "CHECKED";}
                $out .= " /></div>\n";
            } else if ($displayformat == "select") {
                $out .= "<option value=$i ";
                if (($la != '') && ($la != 'NA') && ($la == $randkeys[$i])) {$out .= "selected=1";}
                $out .= ">" . str_replace('`', '', $questions[$randkeys[$i]]) . "</option>\n";
            } else if ($displayformat == "inline") {
                $out .= "<input type=radio name=qn$qn value=$i id=\"qn$qn-$i\" ";
                if (($la != '') && ($la == $randkeys[$i])) {$out .= "CHECKED";}
                $out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label>";
            } else if ($displayformat == 'column') {
                if ($i % $itempercol == 0) {
                    if ($i > 0) {
                        $out .= '</ul></div>';
                    }
                    $out .= '<div class="match"><ul class=nomark>';
                }
                $out .= "<li><input class=\"unind\" type=radio name=qn$qn value=$i id=\"qn$qn-$i\" ";
                if (($la != '') && ($la == $randkeys[$i])) {$out .= "CHECKED";}
                $out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label></li> \n";
            } else {
                $out .= "<li><input class=\"unind\" type=radio name=qn$qn value=$i id=\"qn$qn-$i\" ";
                if (($la != '') && ($la == $randkeys[$i])) {$out .= "CHECKED";}
                $out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label></li> \n";
            }
        }
        if ($displayformat == "horiz") {
            //$out .= "<div class=spacer>&nbsp;</div>\n";
        } else if ($displayformat == "select") {
            $out .= "</select>\n";
        } else if ($displayformat == 'column') {
            $out .= "</ul></div>"; //<div class=spacer>&nbsp;</div>\n";
        } else if ($displayformat == "inline") {

        } else {
            $out .= "</ul>\n";
        }
        if ($displayformat == 'inline') {
            $out .= "</span>";
        } else if ($displayformat != 'select') {
            $out .= "</div>";
        }

        $tip = _('Select the best answer');
        if ($answer !== '' && !is_array($answer) && !$isConditional) {
            $anss = explode(' or ', $answer);
            $sapt = array();
            foreach ($anss as $v) {
                if (isset($questions[intval($v)])) {
                    $sapt[] = $questions[intval($v)];
                } 
            }
            $sa = implode(' or ', $sapt); //$questions[$answer];
        }

        // Done!
        $this->answerBox = $out;
        $this->jsParams = $params;
        $this->entryTip = $tip;
        $this->correctAnswerForPart = (string) $sa;
        $this->previewLocation = $preview;
    }

    public function getAnswerBox(): string
    {
        return $this->answerBox;
    }

    public function getJsParams(): array
    {
        return $this->jsParams;
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
