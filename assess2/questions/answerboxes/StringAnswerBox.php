<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class StringAnswerBox implements AnswerBox
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

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $params = [];

        $optionkeys = ['ansprompt', 'answerboxsize', 'answer', 'strflags', 
            'displayformat', 'answerformat', 'scoremethod', 'readerlabel', 'variables'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        $questions = getOptionVal($options, 'questions', $multi, $partnum, 2);

        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}

        if (empty($answerboxsize)) {$answerboxsize = 20;}
        if (!empty($ansprompt)) {$out .= $ansprompt;}

        $arialabel = $this->answerBoxParams->getQuestionIdentifierString() .
            (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : '');

        if ($answerformat == 'list') {
            $tip = _('Enter your answer as a list of text separated by commas.  Example:  dog, cat, rabbit.') . "<br/>";
            $shorttip = _('Enter a list of text');
        } else if ($answerformat == 'matrix') {
            $shorttip = _('Enter your answer as a matrix');
            $tip = $shorttip . _(', like [(2,3,4),(1,4,5)]');
        } else if ($answerformat == 'logic') {
            $shorttip = _('Enter a logic statement');
            $tip = _('Enter a logic statement using the editor buttons, or use "and", "or", "implies", and "iff"');
        } else {
            $tip .= _('Enter your answer as letters.  Examples: A B C, linear, a cat');
            $shorttip = _('Enter text');
        }
        if ($displayformat == 'select') {
            $out .= "<select name=\"qn$qn\" id=\"qn$qn\" style=\"margin-right:20px\" class=\"$colorbox\" ";
            $out .= 'aria-label="' . $arialabel . '">';
            $out .= '<option value=""> </option>';
            foreach ($questions as $i => $v) {
                $out .= '<option value="' . htmlentities($v) . '"';
                if ($v == $la) {
                    $out .= ' selected="selected"';
                }
                $out .= '>' . htmlentities($v) . '</option>';
            }
            $out .= '</select>';
        } else {
            $classes = ['text'];
            if ($colorbox != '') {
                $classes[] = $colorbox;
            }
            $attributes = [
                'type' => 'text',
                'size' => $answerboxsize,
                'name' => "qn$qn",
                'id' => "qn$qn",
                'value' => $la,
                'autocomplete' => 'off',
                'aria-label' => $arialabel,
            ];

            if ($displayformat == 'alignright') {
                $classes[] = 'textright';
            } else if ($displayformat == 'hidden') {
                $classes[] = 'pseudohidden';
            } else if ($displayformat == 'debit') {
                $params['format'] = 'debit';
                $classes[] = 'textright';
            } else if ($displayformat == 'credit') {
                $params['format'] = 'credit';
                $classes[] = 'textright';
                $classes[] = 'creditbox';
            }

            $params['tip'] = $shorttip;
            $params['longtip'] = $tip;
            if ($useeqnhelper && ($displayformat == 'usepreview' || $answerformat == 'logic')) {
                $params['helper'] = 1;
            }
            if (empty($hidepreview) && ($displayformat == 'usepreview' || $displayformat == 'usepreviewnomq')) {
                $params['preview'] = $_SESSION['userprefs']['livepreview'] ? 1 : 2;
            }
            if ($answerformat == 'logic') {
                $params['vars'] = $variables;
            }

            $params['calcformat'] = $answerformat;

            if ($displayformat == 'typeahead') {
                if (!is_array($questions)) {
                    echo _('Eeek!  $questions is not defined or needs to be an array');
                } else {
                    foreach ($questions as $i => $v) {
                        $questions[$i] = htmlentities(trim($v));
                    }

                    $autosugglist = '["' . implode('","', $questions) . '"]';
                    if (!isset($GLOBALS['autosuggestlists'])) {
                        $GLOBALS['autosuggestlists'] = array();
                    }
                    if (($k = array_search($autosugglist, $GLOBALS['autosuggestlists'])) !== false) {
                        $asvar = 'autosuggestlist' . $k;
                    } else {
                        $GLOBALS['autosuggestlists'][] = $autosugglist;
                        $ascnt = count($GLOBALS['autosuggestlists']) - 1;
                        $asvar = 'autosuggestlist' . $ascnt;

                        $params[$asvar] = $questions;
                    }
                    $params['autosuggest'] = $asvar;
                }
            }

            $out .= '<input ' .
            Sanitize::generateAttributeString($attributes) .
            'class="' . implode(' ', $classes) .
                '" />';

            if ($displayformat == 'usepreview' || $displayformat == 'usepreviewnomq') {
                $preview .= '<button type=button class=btn id="pbtn' . $qn . '">';
                $preview .= _('Preview') . ' <span class="sr-only">' . $this->answerBoxParams->getQuestionIdentifierString() . '</span>';
                $preview .= '</button> &nbsp;';
                $preview .= "<span id=p$qn></span> ";
            }
        }
        if (strpos($strflags, 'regex') !== false) {
            $sa .= _('The answer must match a specified pattern');
        } else if ($answerformat == "logic") {
            $sa = '`' . str_replace(['and', 'or', 'implies', 'iff'], ['^^', 'vv', '=>', '<=>'], $answer) . '`';
        } else {
            $sa .= $answer;
        }

        if (($scoremethod == 'takeanythingorblank' && trim($la) == '') ||
            $scoremethod == 'submitblank' ||
            trim($answer) == ''
        ) {
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
