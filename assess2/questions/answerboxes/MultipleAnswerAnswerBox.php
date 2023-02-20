<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class MultipleAnswerAnswerBox implements AnswerBox
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
        $isConditional = $this->answerBoxParams->getIsConditional();
        $partnum = $this->answerBoxParams->getQuestionPartNumber();
        $la = $this->answerBoxParams->getStudentLastAnswers();
        $options = $this->answerBoxParams->getQuestionWriterVars();
        $colorbox = $this->answerBoxParams->getColorboxKeyword();
        $assessmentId = $this->answerBoxParams->getAssessmentId();

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $style = '';
        $params = [];

        $optionkeys = ['answers', 'noshuffle', 'displayformat', 'readerlabel','answerformat'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        $questions = getOptionVal($options, 'questions', $multi, $partnum, 2);
        if (is_array($answers)) {
            echo 'Eek! $answers in multans should be a list, not an array';
            $answers = implode(',', $answers);
        }
        $answers = trim($answers, ' ,');
        
        if (!is_array($questions)) {
            echo _('Eeek!  $questions is not defined or needs to be an array');
            $questions = array();
        }

        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}

        if ($noshuffle == "last") {
            $randkeys = (array) $RND->array_rand(array_slice($questions, 0, count($questions) - 1), count($questions) - 1);
            $RND->shuffle($randkeys);
            array_push($randkeys, count($questions) - 1);
        } else if ($noshuffle == "all" || count($questions) == 1) {
            $randkeys = array_keys($questions);
        } else {
            $randkeys = (array) $RND->array_rand($questions, count($questions));
            $RND->shuffle($randkeys);
        }
        $hasNoneOfThese = '';
        if ((count($questions) > 1 && trim($answers) == "") || $answerformat == 'addnone') {
            $qstr = strtolower($questions[count($questions) - 1]);
            if (strpos($qstr, 'none of') === false) {
                $questions[] = _('None of these');
                array_push($randkeys, count($questions) - 1);
                if (count($questions) > 1 && trim($answers) == "") {
                    $answers = count($questions) - 1;
                }
            }
            $hasNoneOfThese = 'data-multans="hasnone"';
        } else if (count($questions) > 1 && ($noshuffle == 'all' || $noshuffle == 'last')) {
            if (preg_match('/^none\s*of/i', $questions[count($questions) - 1])) {
                $hasNoneOfThese = 'data-multans="hasnone"';
            }
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
            $params['livepoll_ans'] = $answers;
            $params['livepoll_randkeys'] = $randkeys;
        }

        if ($la == '') {
            $labits = array();
        } else {
            $labits = explode('|', $la);
        }
        if (empty($displayformat)) {
            $displayformat = 'list';
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
            $out .= "<span $style $hasNoneOfThese id=\"qnwrap$qn\" role=group aria-label=\"" . $arialabel . ' ' . _('Select one or more answers') . "\">";
        } else {
            if ($colorbox != '') {$style .= ' class="' . $colorbox . ' clearfix" ';} else { $style = ' class="clearfix" ';}
            $out .= "<div $style $hasNoneOfThese id=\"qnwrap$qn\" style=\"display:block\" role=group aria-label=\"" . $arialabel . ' ' . _('Select one or more answers') . "\">";
        }
        if ($displayformat == "horiz") {

        } else if ($displayformat == "inline") {

        } else if ($displayformat == 'column') {

        } else {
            $out .= "<ul class=nomark>";
        }

        for ($i = 0; $i < count($randkeys); $i++) {
            if ($displayformat == "horiz") {
                $out .= "<div class=choice><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label><br/>";
                $out .= "<input type=checkbox name=\"qn$qn" . "[$i]\" value=$i id=\"qn$qn-$i\" ";
                if (in_array($randkeys[$i], $labits)) {$out .= 'checked';}
                $out .= " /></div> \n";
            } else if ($displayformat == "inline") {
                $out .= "<input type=checkbox name=\"qn$qn" . "[$i]\" value=$i id=\"qn$qn-$i\" ";
                if (in_array($randkeys[$i], $labits)) {$out .= 'checked';}
                $out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label> ";
            } else if ($displayformat == 'column') {
                if ($i % $itempercol == 0) {
                    if ($i > 0) {
                        $out .= '</ul></div>';
                    }
                    $out .= '<div class="match"><ul class=nomark>';
                }
                $out .= "<li><input type=checkbox name=\"qn$qn" . "[$i]\" value=$i id=\"qn$qn-$i\" ";
                if (in_array($randkeys[$i], $labits)) {$out .= 'checked';}
                $out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label></li> \n";
            } else {
                $out .= "<li><input class=\"unind\" type=checkbox name=\"qn$qn" . "[$i]\" value=$i id=\"qn$qn-$i\" ";
                if (in_array($randkeys[$i], $labits)) {$out .= 'checked';}
                $out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label></li> \n";
            }
        }
        if ($displayformat == "horiz") {
            //$out .= "<div class=spacer>&nbsp;</div>\n";
        } else if ($displayformat == "inline") {

        } else if ($displayformat == 'column') {
            $out .= "</ul></div>"; //<div class=spacer>&nbsp;</div>\n";
        } else {
            $out .= "</ul>\n";
        }
        if ($displayformat == 'inline') {
            $out .= "</span>";
        } else {
            $out .= "</div>";
        }
        $tip = _('Select all correct answers');
        if ($answers !== '' && !$isConditional) {
            $ansor = explode(' or ', $answers);
            foreach ($ansor as $k => $answers) {
                $akeys = array_map('trim', explode(',', $answers));
                if ($k > 0) {
                    $sa .= '<br/><em>' . _('or') . '</em>';
                }
                foreach ($akeys as $akey) {
                    if (isset($questions[$akey])) {
                        $sa .= '<br/>' . $questions[$akey];
                    } else {
                        echo "Invalid answer key $akey";
                    }
                }
            }
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
