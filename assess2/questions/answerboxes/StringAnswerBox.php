<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

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

        if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$partnum];} else {$ansprompt = $options['ansprompt'];}}
        if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$partnum];} else {$sz = $options['answerboxsize'];}}
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if (isset($options['strflags'])) {if (is_array($options['strflags'])) {$strflags = $options['strflags'][$partnum];} else {$strflags = $options['strflags'];}}
        if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$partnum];} else {$displayformat = $options['displayformat'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['scoremethod'])) {if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$partnum];} else {$scoremethod = $options['scoremethod'];}}
        if (isset($options['readerlabel'])) {if (is_array($options['readerlabel'])) {$readerlabel = $options['readerlabel'][$partnum];} else {$readerlabel = $options['readerlabel'];}}
        if (is_array($options['questions'][$partnum])) {$questions = $options['questions'][$partnum];} else {$questions = $options['questions'];}
        if (!isset($answerformat)) { $answerformat = '';}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        if (!isset($sz)) { $sz = 20;}
    		if (isset($ansprompt)) {$out .= $ansprompt;}

    		if ($answerformat=='list') {
    			$tip = _('Enter your answer as a list of text separated by commas.  Example:  dog, cat, rabbit.') . "<br/>";
    			$shorttip = _('Enter a list of text');
    		} else if ($answerformat=='matrix') {
                $shorttip = _('Enter your answer as a matrix');  
                $tip = $shorttip._(', like [(2,3,4),(1,4,5)]');
            } else {
    			$tip .= _('Enter your answer as letters.  Examples: A B C, linear, a cat');
    			$shorttip = _('Enter text');
    		}
    		if ($displayformat=='select') {
    			$out .= "<select name=\"qn$qn\" id=\"qn$qn\" style=\"margin-right:20px\" class=\"$colorbox\" ";
                $out .= 'aria-label="'.$this->answerBoxParams->getQuestionIdentifierString().'">';
                $out .= '<option value=""> </option>';
    			foreach ($questions as $i=>$v) {
    				$out .= '<option value="'.htmlentities($v).'"';
    				if ($v == $la) {
    					$out .= ' selected="selected"';
    				}
    				$out .= '>'.htmlentities($v).'</option>';
    			}
    			$out .= '</select>';
    		} else {
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

    			if ($displayformat=='alignright') {
    				$classes[] = 'textright';
    			}	else if ($displayformat=='hidden') {
    				$classes[] = 'pseudohidden';
    			}	else if ($displayformat=='debit') {
    				$params['format'] = 'debit';
    				$classes[] = 'textright';
    			} else if ($displayformat=='credit') {
    				$params['format'] = 'credit';
    				$classes[] = 'textright';
    				$classes[] = 'creditbox';
    			}

    			$params['tip'] = $shorttip;
    			$params['longtip'] = $tip;
    			if ($useeqnhelper && $displayformat == 'usepreview') {
    				$params['helper'] = 1;
    			}
                if (!isset($hidepreview) && $displayformat == 'usepreview') {
                    $params['preview'] = $_SESSION['userprefs']['livepreview'] ? 1 : 2;
                }

    			$params['calcformat'] = $answerformat;

    			if ($displayformat == 'typeahead') {
    				if (!is_array($questions)) {
    					echo _('Eeek!  $questions is not defined or needs to be an array');
    				} else {
    					foreach ($questions as $i=>$v) {
    						$questions[$i] = htmlentities(trim($v));
    					}

    					$autosugglist = '["'.implode('","',$questions).'"]';
    					if (!isset($GLOBALS['autosuggestlists'])) {
    						$GLOBALS['autosuggestlists'] = array();
    					}
    					if (($k = array_search($autosugglist, $GLOBALS['autosuggestlists']))!==false) {
    						$asvar = 'autosuggestlist'.$k;
    					} else {
    						$GLOBALS['autosuggestlists'][] = $autosugglist;
    						$ascnt = count($GLOBALS['autosuggestlists'])-1;
    						$asvar = 'autosuggestlist'.$ascnt;

    						$params[$asvar] = $questions;
    					}
    					$params['autosuggest'] = $asvar;
    				}
    			}

    			$out .= '<input ' .
                  Sanitize::generateAttributeString($attributes) .
    							'class="'.implode(' ', $classes) .
    							'" />';

    			if ($displayformat == 'usepreview') {
                    $preview .= '<button type=button class=btn id="pbtn'.$qn.'">';
                    $preview .= _('Preview') . ' <span class="sr-only">' . $this->answerBoxParams->getQuestionIdentifierString() . '</span>';
                    $preview .= '</button> &nbsp;';
    				$preview .= "<span id=p$qn></span> ";
    			}
    		}
    		if (strpos($strflags,'regex')!==false) {
    			$sa .= _('The answer must match a specified pattern');
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
