<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class NTupleAnswerBox implements AnswerBox
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

        $optionkeys = ['answerboxsize', 'answerformat', 'answer', 'displayformat', 
            'ansprompt', 'reqdecimals', 'readerlabel'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }

        $ansformats = array_map('trim', explode(',', $answerformat));

        if (empty($answerboxsize)) {$answerboxsize = 20;}
        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}

        if ($displayformat == 'point') {
            $tip = _('Enter your answer as a point.  Example: (2,5.5172)') . "<br/>";
            $shorttip = _('Enter a point');
        } else if ($displayformat == 'pointlist') {
            $tip = _('Enter your answer a list of points separated with commas.  Example: (1,2), (3.5172,5)') . "<br/>";
            $shorttip = _('Enter a list of points');
        } else if ($displayformat == 'vector') {
            $tip = _('Enter your answer as a vector.  Example: <2,5.5>') . "<br/>";
            $shorttip = _('Enter a vector');
        } else if ($displayformat == 'vectorlist') {
            $tip = _('Enter your answer a list of vectors separated with commas.  Example: <1,2>, <3.5172,5>') . "<br/>";
            $shorttip = _('Enter a list of vectors');
        } else if ($displayformat == 'set') {
            $tip = _('Enter your answer as a set of numbers.  Example: {1,2,3}') . "<br/>";
            $shorttip = _('Enter a set');
        } else if ($displayformat == 'setlist') {
            $tip = _('Enter your answer as a list of sets separated with commas.  Example: {1,2,3},{4,5}') . "<br/>";
            $shorttip = _('Enter a list of sets');
        } else if ($displayformat == 'list') {
            $tip = _('Enter your answer as a list of n-tuples of numbers separated with commas: Example: (1,2),(3.5172,4)') . "<br/>";
            $shorttip = _('Enter a list of n-tuples');
        } else {
            $tip = _('Enter your answer as an n-tuple of numbers.  Example: (2,5.5172)') . "<br/>";
            $shorttip = _('Enter an n-tuple');
        }
        if ($reqdecimals !== '') {
            $tip .= sprintf(_('Each value should be accurate to %d decimal places.'), $reqdecimals) . '<br/>';
            $shorttip .= sprintf(_(", each value accurate to %d decimal places"), $reqdecimals);
        }
        if (!in_array('nosoln', $ansformats) && !in_array('nosolninf', $ansformats)) {
            $tip .= _('Enter DNE for Does Not Exist');
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
            'aria-label' => $this->answerBoxParams->getQuestionIdentifierString() .
            (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : ''),
        ];
        $params['tip'] = $shorttip;
        $params['longtip'] = $tip;
        $params['calcformat'] = $answerformat . ($answerformat == '' ? '' : ',') . 'decimal';

        $out .= '<input ' .
        Sanitize::generateAttributeString($attributes) .
        'class="' . implode(' ', $classes) .
            '" />';
        $out .= "<span id=p$qn></span>";
        if (in_array('nosoln', $ansformats) || in_array('nosolninf', $ansformats)) {
            list($out, $answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
        }
        if ($answer !== '' && !is_array($answer) && !$isConditional) {
            $sa = $answer;
            if ($displayformat == 'vectorlist' || $displayformat == 'vector') {
                $sa = str_replace(array('<', '>'), array('(:', ':)'), $sa);
            }
            $sa = '`' . $sa . '`';
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
