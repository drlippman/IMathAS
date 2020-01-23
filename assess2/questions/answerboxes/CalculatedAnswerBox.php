<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class CalculatedAnswerBox implements AnswerBox
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
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$partnum];} else {$reqdecimals = $options['reqdecimals'];}}
        if (isset($options['reqsigfigs'])) {if (is_array($options['reqsigfigs'])) {$reqsigfigs = $options['reqsigfigs'][$partnum];} else {$reqsigfigs = $options['reqsigfigs'];}}
        if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$partnum];} else {$displayformat = $options['displayformat'];}}

        if (!isset($sz)) { $sz = 20;}
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        // TODO: fix this
        $lap = explode('$f$',$la);
        if (isset($lap[1]) && (!isset($GLOBALS['noformatfeedback']) || $GLOBALS['noformatfeedback']==false)) {
            $rightanswrongformat = true;
            if ($colorbox=='ansred') {
                $colorbox = 'ansorg';
            }
        }
        $la = $lap[0];

        if (!isset($answerformat)) { $answerformat = '';}
    		$ansformats = array_map('trim',explode(',',$answerformat));

    		if (isset($ansprompt) && !in_array('nosoln',$ansformats) && !in_array('nosolninf',$ansformats))  {
    			$out .= "<label for=\"qn$qn\">$ansprompt</label>";
    		}
    		if ($displayformat=="point") {
    			$leftb = "(";
    			$rightb = ")";
    		} else if ($displayformat=="vector") {
    			$leftb = "&lt;";
    			$rightb = "&gt;";
    		} else {
    			$leftb = '';
    			$rightb = '';
    		}

    		if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
    			$tip = _('Enter your answer as a list of values separated by commas: Example: -4, 3, 2') . "<br/>";
    			$eword = _('each value');
    		} else if (in_array('set',$ansformats) || in_array('exactset',$ansformats)) {
    			$tip = _('Enter your answer as a set of values separated with commas: Example: {-4, 3, 2}') . "<br/>";
    			$eword = _('each value');
    		} else {
    			$tip = '';
    			$eword = _('your answer');
    		}
    		list($longtip,$shorttip) = formathint($eword,$ansformats,isset($reqdecimals)?$reqdecimals:null,'calculated',(in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats) || in_array('set',$ansformats) || in_array('exactset',$ansformats)), 1);
    		$tip .= $longtip;
    		if (isset($reqsigfigs) && !in_array("scinot",$ansformats) && !in_array("scinotordec",$ansformats) && !in_array("decimal",$ansformats)) {
    			unset($reqsigfigs);
    		}
    		if (isset($reqsigfigs)) {
    			if ($reqsigfigs{0}=='=') {
    				$reqsigfigs = substr($reqsigfigs,1);
    				if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
    					$answer = implode(',', prettysigfig(explode(',', $answer), $reqsigfigs,'',false,in_array("scinot",$ansformats)||in_array("scinotordec",$ansformats)));
    				} else {
    					$answer = prettysigfig($answer,$reqsigfigs,'',false,in_array("scinot",$ansformats)||in_array("scinotordec",$ansformats));
    				}
    				$tip .= "<br/>" . sprintf(_('Your answer should have exactly %d significant figures.'), $reqsigfigs);
    				$shorttip .= sprintf(_(', with exactly %d significant figures'), $reqsigfigs);
    			} else if ($reqsigfigs{0}=='[') {
    				$reqsigfigparts = explode(',',substr($reqsigfigs,1,-1));
    				$tip .= "<br/>" . sprintf(_('Your answer should have between %d and %d significant figures.'), $reqsigfigparts[0], $reqsigfigparts[1]);
    				$shorttip .= sprintf(_(', with %d - %d significant figures'), $reqsigfigparts[0], $reqsigfigparts[1]);
    			} else {
    				if ($answer!=0) {
    					$v = -1*floor(-log10(abs($answer))-1e-12) - $reqsigfigs;
    				}
    				if ($answer!=0  && $v < 0 && strlen($answer) - strpos($answer,'.')-1 + $v < 0) {
    					if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
    						$answer = implode(',', prettysigfig(explode(',', $answer), $reqsigfigs,'',false,in_array("scinot",$ansformats)||in_array("scinotordec",$ansformats)));
    					} else {
    						$answer = prettysigfig($answer,$reqsigfigs,'',false,in_array("scinot",$ansformats)||in_array("scinotordec",$ansformats));
    					}
    				}
    				$tip .= "<br/>" . sprintf(_('Your answer should have at least %d significant figures.'), $reqsigfigs);
    				$shorttip .= sprintf(_(', with at least %d significant figures'), $reqsigfigs);
    			}
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
    			'autocomplete' => 'off'
    		];

    		$params['tip'] = $shorttip;
        $params['longtip'] = $tip;
    		if ($useeqnhelper) {
    			$params['helper'] = 1;
    		}
    		if (!isset($hidepreview) && $GLOBALS['sessiondata']['userprefs']['livepreview']==1) {
    			$params['preview'] = 1;
    		}
    		$params['calcformat'] = $answerformat;

    		$out .= $leftb .
    						'<input ' .
    						Sanitize::generateAttributeString($attributes) .
    						'class="'.implode(' ', $classes) .
    						'" />' .
    						$rightb;

    		if (!isset($GLOBALS['nocolormark']) && isset($rightanswrongformat) && (!isset($GLOBALS['noformatfeedback']) || $GLOBALS['noformatfeedback']==false)) {
    			if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
    				$out .= ' '.formhoverover('<span style="color:#f60;font-size:80%">(Format)</span>','One or more of your answers is equivalent to the correct answer, but is not simplified or is in the wrong format');
    			} else {
    				$out .= ' '.formhoverover('<span style="color:#f60;font-size:80%">(Format)</span>','Your answer is equivalent to the correct answer, but is not simplified or is in the wrong format');
    			}
    		}

    		if (!isset($hidepreview)) {
    			$preview .= "<input type=button class=btn id=\"pbtn$qn\" value=\"" . _('Preview') . "\"/> &nbsp;\n";
    		}
    		$preview .= "$leftb<span id=p$qn></span>$rightb ";

    		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
    			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
    		}

    		if (isset($answer)) {
    			if (!is_numeric($answer)) {
    				$sa = '`'.$answer.'`';
    			} else if (in_array('mixednumber',$ansformats) || in_array("sloppymixednumber",$ansformats) || in_array("mixednumberorimproper",$ansformats)) {
    				$sa = '`'.decimaltofraction($answer,"mixednumber").'`';
    			} else if (in_array("fraction",$ansformats) || in_array("reducedfraction",$ansformats)) {
    				$sa = '`'.decimaltofraction($answer).'`';
    			} else if (in_array("scinot",$ansformats) || (in_array("scinotordec",$ansformats) && (abs($answer)>1000 || abs($answer)<.001))) {
    				$sa = '`'.makescinot($answer,-1,'*').'`';
    			} else if (is_numeric($answer) && $answer!=0 && abs($answer)<.001 && abs($answer)>1e-9) {
    				$sa = prettysmallnumber($answer);
    			} else {
    				$sa = $answer;
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
