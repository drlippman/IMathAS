<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class ChemEquationAnswerBox implements AnswerBox
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

        $optionkeys = ['ansprompt', 'answerboxsize', 'answer', 'answerformat', 
            'scoremethod', 'readerlabel', 'variables'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        $questions = getOptionVal($options, 'questions', $multi, $partnum, 2);

        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}

        if (empty($answerboxsize)) {$answerboxsize = 20;}
        if (!empty($ansprompt)) {$out .= $ansprompt;}

        $arialabel = $this->answerBoxParams->getQuestionIdentifierString() .
            (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : '');

        if ($answerformat == 'reaction') {
            $shorttip = _('Enter a chemical equation');
            $tip = _('Enter a chemical equation, like 2H_2+O_2->2H_2O. Answers are case sensitive.');
        } else {
            $shorttip = _('Enter a chemical formula');
            $tip = _('Enter a chemical formula, like H_2O. Answers are case sensitive.');
        }

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

        $params['tip'] = $shorttip;
        $params['longtip'] = $tip;
        if ($useeqnhelper) {
            $params['helper'] = 1;
        }
        $params['preview'] = $_SESSION['userprefs']['livepreview'] ? 1 : 2;
        $params['vars'] = $variables;

        $params['calcformat'] = $answerformat . ($answerformat==''?'':',') . 'chem';

        $out .= '<input ' .
        Sanitize::generateAttributeString($attributes) .
        'class="' . implode(' ', $classes) .
            '" />';

        $preview .= '<button type=button class=btn id="pbtn' . $qn . '">';
        $preview .= _('Preview') . ' <span class="sr-only">' . $this->answerBoxParams->getQuestionIdentifierString() . '</span>';
        $preview .= '</button> &nbsp;';
        $preview .= "<span id=p$qn></span> ";

        $sa .= '`' . $answer . '`';
        $sa = str_replace(['<->','<=>','leftrightharpoons'], 'rightleftharpoons', $sa);

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
