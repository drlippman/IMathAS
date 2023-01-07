<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class CalculatedComplexAnswerBox implements AnswerBox
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

        $optionkeys = ['ansprompt', 'answerboxsize', 'hidepreview', 'answerformat',
            'answer', 'reqdecimals', 'displayformat', 'readerlabel' ];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }

        $ansformats = array_map('trim',explode(',',$answerformat));
        $isListAnswer =  (in_array('list', $ansformats) || in_array('exactlist', $ansformats));

        if (in_array('allowplusminus', $ansformats)) {
            if (!$isListAnswer) {
                $ansformats[] = 'list';
                $answerformat = ($answerformat == '') ? 'list' : $answerformat . ',list';
                $isListAnswer = true;
            }
        } else if (isset($GLOBALS['myrights']) && $GLOBALS['myrights'] > 10 && is_string($answer) && strpos($answer,'+-')!==false) {
            echo _('Warning: For +- in an $answer to score correctly, use $answerformat="allowplusminus"');
        }

        if (empty($answerboxsize)) { $answerboxsize = 20;}
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        $la = explode('$#$',$la);
        $la = $la[0];

        if ($isListAnswer) {
            $tip = _('Enter your answer as a list of complex numbers in a+bi form separated with commas.  Example: 2+5i,-3-4i') . "<br/>";
            $shorttip = _('Enter a list of complex numbers');
        } else {
            $tip = _('Enter your answer as a complex number in a+bi form.  Example: 2+5i') . "<br/>";
            $shorttip = _('Enter a complex number');
        }
        $tip .= formathint('each value',$ansformats,($reqdecimals!=='')?$reqdecimals:null,'calccomplex');

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
            'aria-label' => $this->answerBoxParams->getQuestionIdentifierString() . 
                (!empty($readerlabel) ? ' '.Sanitize::encodeStringForDisplay($readerlabel) : '')
        ];
        $params['tip'] = $shorttip;
        $params['longtip'] = $tip;
        if ($useeqnhelper) {
            $params['helper'] = 1;
        }
        if (empty($hidepreview)) {
            $params['preview'] = !empty($_SESSION['userprefs']['livepreview']) ? 1 : 2;
        }
        $params['calcformat'] = $answerformat;

        $out .= '<input ' .
            Sanitize::generateAttributeString($attributes) .
            'class="'.implode(' ', $classes) .
            '" />';

        if (empty($hidepreview)) {
            $preview .= '<button type=button class=btn id="pbtn'.$qn.'">';
            $preview .= _('Preview') . ' <span class="sr-only">' . $this->answerBoxParams->getQuestionIdentifierString() . '</span>';
            $preview .= '</button> &nbsp;';
        }
        $preview .= "<span id=p$qn></span> ";

        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
        }

        if ($answer !== '' && !is_array($answer) && !$isConditional) {
            if (in_array('allowplusminus', $ansformats)) {
                $answer = str_replace('+-','pm',$answer);
            }
            $sa = makeprettydisp($answer);
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
