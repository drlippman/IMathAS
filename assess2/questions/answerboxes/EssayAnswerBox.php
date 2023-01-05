<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class EssayAnswerBox implements AnswerBox
{
    private $answerBoxParams;

    private $answerBox;
    private $jsParams;
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
        $isConditional = $this->answerBoxParams->getIsConditional();

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $params = [];

        $optionkeys = ['answerboxsize', 'displayformat', 'scoremethod',
            'answer', 'readerlabel'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }

        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}

        if (empty($answerboxsize)) {
            $rows = 5;
            $cols = 50;
        } else if (strpos($answerboxsize, ',') > 0) {
            list($rows, $cols) = explode(',', $answerboxsize);
            $rows = intval($rows);
            $cols = intval($cols);
        } else {
            $cols = 50;
            $rows = intval($answerboxsize);
        }
        if ($displayformat == 'editor') {
            $rows += 5;
        }

        if (!isset($GLOBALS['useeditor'])) { // should be defined, but avoid errors if not
            $GLOBALS['useeditor'] = 1;
        }

        if ($GLOBALS['useeditor'] == 'review' || ($GLOBALS['useeditor'] == 'reviewifneeded' && trim($la) == '')) {
            $la = str_replace('&quot;', '"', $la);

            if ($displayformat != 'editor') {
                $la = preg_replace('/\n/', '<br/>', $la);
            }
            if ($colorbox == '') {
                $out .= '<div class="introtext" id="qnwrap' . $qn . '">';
            } else {
                $out .= '<div class="introtext ' . $colorbox . '" id="qnwrap' . $qn . '">';
            }
            $out .= filter($la);
            $out .= "</div>";
        } else {
            $arialabel = $this->answerBoxParams->getQuestionIdentifierString() .
                (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : '');
            if ($displayformat == 'editor' && $GLOBALS['useeditor'] == 1) {
                $la = str_replace('&quot;', '"', $la);
            }
            if ($rows < 2) {
                $out .= "<input type=\"text\" class=\"text $colorbox\" size=\"$cols\" name=\"qn$qn\" id=\"qn$qn\" value=\"" . Sanitize::encodeStringForDisplay($la) . "\" ";
                $out .= 'aria-label="' . $arialabel . '" />';
            } else {
                if ($colorbox != '') {$out .= '<div class="' . $colorbox . '">';}
                $out .= "<textarea rows=\"$rows\" name=\"qn$qn\" id=\"qn$qn\" ";
                if ($displayformat == 'editor' && $GLOBALS['useeditor'] == 1) {
                    $out .= "style=\"width:98%;\" class=\"mceEditor\" ";
                } else {
                    $out .= "cols=\"$cols\" ";
                }
                $out .= 'aria-label="' . $arialabel . '" ';
                $out .= sprintf(">%s</textarea>\n", Sanitize::encodeStringForDisplay($la, true));
                if ($colorbox != '') {$out .= '</div>';}
            }
            if ($displayformat == 'editor' && $GLOBALS['useeditor'] == 1) {
                $params['usetinymce'] = 1;
            }
        }
        $tip .= _('Enter your answer as text.  This question is not automatically graded.');
        if (is_scalar($answer) && !$isConditional) {
            $sa .= $answer;
        }

        if ($scoremethod == 'takeanythingorblank' && trim($la) == '') {
            $params['submitblank'] = 1;
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
