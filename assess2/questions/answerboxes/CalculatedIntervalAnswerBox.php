<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class CalculatedIntervalAnswerBox implements AnswerBox
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

        if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$partnum];} else {$ansprompt = $options['ansprompt'];}}
        if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$partnum];} else {$sz = $options['answerboxsize'];}}
        if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$partnum];} else {$hidepreview = $options['hidepreview'];}}
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$partnum];} else {$reqdecimals = $options['reqdecimals'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['variables'])) {if (is_array($options['variables'])) {$variables = $options['variables'][$partnum];} else {$variables = $options['variables'];}}
        if (isset($options['readerlabel'])) {if (is_array($options['readerlabel'])) {$readerlabel = $options['readerlabel'][$partnum];} else {$readerlabel = $options['readerlabel'];}}
        if (!isset($answerformat)) { $answerformat = '';}
        if (!isset($variables)) { $variables = 'x';}
        $ansformats = array_map('trim',explode(',',$answerformat));

        if (!isset($sz)) { $sz = 20;}
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        if (isset($ansprompt)) {
          $out .= $ansprompt;
        }

        if (in_array('inequality',$ansformats)) {
    			$tip = sprintf(_('Enter your answer using inequality notation.  Example: 3 &lt;= %s &lt; 4'), $variables) . " <br/>";
    			$tip .= sprintf(_('Use or to combine intervals.  Example: %s &lt; 2 or %s &gt;= 3'), $variables, $variables) . "<br/>";
    			$tip .= _('Enter <i>all real numbers</i> for solutions of that type') . "<br/>";
    			$shorttip = _('Enter an interval using inequalities');
    		} else {
    			$tip = _('Enter your answer using interval notation.  Example: [2,5)') . " <br/>";
    			if (in_array('list',$ansformats)) {
    				$tip .= _('Separate intervals by a comma.  Example: (-oo,2],[4,oo)') . "<br/>";
    				$shorttip = _('Enter a list of intervals using interval notation');
    			} else {
    				$tip .= _('Use U for union to combine intervals.  Example: (-oo,2] U [4,oo)') . "<br/>";
    				$shorttip = _('Enter an interval using interval notation');
    			}

    		}
    		//$tip .= "Enter values as numbers (like 5, -3, 2.2) or as calculations (like 5/3, 2^3, 5+4)<br/>";
    		//$tip .= "Enter DNE for an empty set, oo for Infinity";
    		$tip .= formathint(_('each value'),$ansformats,isset($reqdecimals)?$reqdecimals:null,'calcinterval');

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
    		if ($useeqnhelper) {
    			$params['helper'] = 1;
    		}
    		if (in_array('inequality',$ansformats)) {
    			$params['vars'] = $variables;
    		}

    		$params['calcformat'] = $answerformat;

    		$out .= '<input ' .
                Sanitize::generateAttributeString($attributes) .
                'class="'.implode(' ', $classes) .
                '" />';

    		if (!isset($hidepreview)) {
    			$params['preview'] = 1;
    			$preview .= "<input type=button class=btn id=\"pbtn$qn\" value=\"" . _('Preview') . "\" /> &nbsp;\n";
    		}
    		$preview .= "<span id=p$qn></span> ";

    		if (in_array('nosoln',$ansformats)) {
    			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox, in_array('inequality',$ansformats)?'inequality':'interval');
    		}

    		if (isset($answer)) {
    			if (in_array('inequality',$ansformats) && strpos($answer,'"')===false) {
    				$sa = '`'.intervaltoineq($answer,$variables).'`';
    			} else {
    				$sa = '`'.str_replace('U','uu',$answer).'`';
    			}
    		}

        // Done!
        $this->answerBox = $out;
        $this->jsParams = $params;
        $this->entryTip = $tip;
        $this->correctAnswerForPart = $sa;
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
