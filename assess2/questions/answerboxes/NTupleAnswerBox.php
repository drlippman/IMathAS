<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

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

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $params = [];

        if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$partnum];} else {$sz = $options['answerboxsize'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$partnum];} else {$displayformat = $options['displayformat'];}}
        if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$partnum];} else {$ansprompt = $options['ansprompt'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$partnum];} else {$reqdecimals = $options['reqdecimals'];}}
        if (isset($options['readerlabel'])) {if (is_array($options['readerlabel'])) {$readerlabel = $options['readerlabel'][$partnum];} else {$readerlabel = $options['readerlabel'];}}
        if (!isset($answerformat)) { $answerformat = '';}
        $ansformats = array_map('trim',explode(',',$answerformat));

        if (!isset($sz)) { $sz = 20;}
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

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
    		} else if ($displayformat == 'list') {
    			$tip = _('Enter your answer as a list of n-tuples of numbers separated with commas: Example: (1,2),(3.5172,4)') . "<br/>";
    			$shorttip = _('Enter a list of n-tuples');
    		} else {
    			$tip = _('Enter your answer as an n-tuple of numbers.  Example: (2,5.5172)') . "<br/>";
    			$shorttip = _('Enter an n-tuple');
    		}
    		if (isset($reqdecimals)) {
    			$tip .= sprintf(_('Each value should be accurate to %d decimal places.'), $reqdecimals).'<br/>';
    			$shorttip .= sprintf(_(", each value accurate to %d decimal places"), $reqdecimals);
    		}
    		if (!in_array('nosoln',$ansformats) && !in_array('nosolninf',$ansformats))  {
    			$tip .= _('Enter DNE for Does Not Exist');
    		}

    		$classes = ['text'];
    		if ($colorbox != '') {
    			$classes[] = $colorbox;
    		}
    		$attributes = [
    			'type' => 'text',
    			'size' => $sz,
    			'name' => "qn$qn",
    			'id' => "qn$qn",
    			'value' => $la,
    			'autocomplete' => 'off',
                'aria-label' => $this->answerBoxParams->getQuestionIdentifierString() . 
                    (!empty($readerlabel) ? ' '.Sanitize::encodeStringForDisplay($readerlabel) : '')
    		];
    		$params['tip'] = $shorttip;
            $params['longtip'] = $tip;
            $params['calcformat'] = $answerformat . ($answerformat==''?'':',') . 'decimal';

    		$out .= '<input ' .
                Sanitize::generateAttributeString($attributes) .
    						'class="'.implode(' ', $classes) .
    						'" />';
            $out .= "<span id=p$qn></span>";
    		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
    			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
    		}
    		if (isset($answer)) {
    			$sa = $answer;
    			if ($displayformat == 'vectorlist' || $displayformat == 'vector') {
    				$sa = str_replace(array('<','>'),array('(:',':)'),$sa);
    			}
    			$sa = '`'.$sa.'`';
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
