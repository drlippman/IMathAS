<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class NumberAnswerBox implements AnswerBox
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
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$partnum];} else {$reqdecimals = $options['reqdecimals'];}}
        if (isset($options['reqsigfigs'])) {if (is_array($options['reqsigfigs'])) {$reqsigfigs = $options['reqsigfigs'][$partnum];} else {$reqsigfigs = $options['reqsigfigs'];}}
        if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$partnum];} else {$displayformat = $options['displayformat'];}} else {$displayformat='';}
        if (isset($options['scoremethod']))if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$partnum];} else {$scoremethod = $options['scoremethod'];}
        if (isset($options['readerlabel'])) {if (is_array($options['readerlabel'])) {$readerlabel = $options['readerlabel'][$partnum];} else {$readerlabel = $options['readerlabel'];}}
        if (!isset($answerformat)) { $answerformat = '';}
        $ansformats = array_map('trim',explode(',',$answerformat));

        if (!isset($sz)) { $sz = 20;}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        if (isset($ansprompt) && !in_array('nosoln',$ansformats) && !in_array('nosolninf',$ansformats))  {
            $out .= $ansprompt;
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
            if (in_array('units', $ansformats)) {
                if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) ||  in_array('orderedlist',$ansformats)) {
                    if (in_array('integer',$ansformats)) {
                        $tip = _('Enter your answer as a list of integers with units, separated with commas. Example: -4 cm, 3 m') . "<br/>";
    				    $shorttip = _('Enter a list of integers with units');
                    } else {
                        $tip = _('Enter your answer as a list of integer or decimal numbers with units, separated with commas. Example: -4.2 cm, 3E6 m') . "<br/>";
    				    $shorttip = _('Enter a list of integer or decimal numbers with units');
                    } 
                } else {
                    if (in_array('integer',$ansformats)) {
                        $tip = _('Enter your answer as an integer with units. Examples: -4 cm, 5 m/s^2') . "<br/>";
    				    $shorttip = _('Enter an integer with units');
                    } else {
                        $tip = _('Enter your answer as an integer or decimal number with units. Examples: -4.2 cm, 3E6 m/s^2') . "<br/>";
    				    $shorttip = _('Enter an integer or decimal number with units');
                    } 
                }
            } else if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) ||  in_array('orderedlist',$ansformats)) {
    			if (in_array('integer',$ansformats)) {
    				$tip = _('Enter your answer as a list of integers separated with commas: Example: -4, 3, 2') . "<br/>";
    				$shorttip = _('Enter a list of integers');
    			} else {
    				$tip = _('Enter your answer as a list of integer or decimal numbers separated with commas: Examples: -4, 3, 2.5172') . "<br/>";
    				$shorttip = _('Enter a list of integer or decimal numbers');
    			}
    		} else if (in_array('set',$ansformats) || in_array('exactset',$ansformats)) {
    			if (in_array('integer',$ansformats)) {
    				$tip = _('Enter your answer as a set of integers separated with commas: Example: {-4, 3, 2}') . "<br/>";
    				$shorttip = _('Enter a set of integers');
    			} else {
    				$tip = _('Enter your answer as a set of integer or decimal numbers separated with commas: Example: {-4, 3, 2.5172}') . "<br/>";
    				$shorttip = _('Enter a set of integer or decimal numbers');
    			}
    		} else {
    			if (in_array('integer',$ansformats)) {
    				$tip = _('Enter your answer as an integer.  Examples: 3, -4, 0') . "<br/>";
    				$shorttip = _('Enter an integer');
    			} else {
    				$tip = _('Enter your answer as an integer or decimal number.  Examples: 3, -4, 5.5172') . "<br/>";
    				$shorttip = _('Enter an integer or decimal number');
    			}
    		}
    		if (!in_array('nosoln',$ansformats) && !in_array('nosolninf',$ansformats))  {
    			$tip .= _('Enter DNE for Does Not Exist, oo for Infinity');
    		}
    		if (isset($reqdecimals)) {
                list($reqdecimals, $exactreqdec, $reqdecoffset, $reqdecscoretype) = parsereqsigfigs($reqdecimals);
    			if ($exactreqdec) {
    				$exactdec = true;
    				$tip .= "<br/>" . sprintf(_('Your answer should include exactly %d decimal places.'), $reqdecimals);
                    $shorttip .= sprintf(_(", with %d decimal places"), $reqdecimals);
                    if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
    					$answer = implode(',', prettyreal(explode(',', $answer), $reqdecimals));
    				} else {
    					$answer = prettyreal($answer, $reqdecimals);
    				}
    			} else {
    				$tip .= "<br/>" . sprintf(_('Your answer should be accurate to at least %d decimal places.'), $reqdecimals);
    				$shorttip .= sprintf(_(", accurate to at least %d decimal places"), $reqdecimals);
    			}
    		}
    		if (isset($reqsigfigs)) {
                list($reqsigfigs, $exactsigfig, $reqsigfigoffset, $sigfigscoretype) = parsereqsigfigs($reqsigfigs);

    			if ($exactsigfig) {
    				if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
    					$answer = implode(',', prettysigfig(explode(',', $answer), $reqsigfigs));
    				} else {
    					$answer = prettysigfig($answer,$reqsigfigs);
    				}
    				$tip .= "<br/>" . sprintf(_('Your answer should have exactly %d significant figures.'), $reqsigfigs);
    				$shorttip .= sprintf(_(', with exactly %d significant figures'), $reqsigfigs);
    			} else if ($reqsigfigoffset>0) {
    				$tip .= "<br/>" . sprintf(_('Your answer should have between %d and %d significant figures.'), $reqsigfigs, $reqsigfigs+$reqsigfigoffset);
    				$shorttip .= sprintf(_(', with %d - %d significant figures'), $reqsigfigs, $reqsigfigs+$reqsigfigoffset);
    			} else {
    				if ($answer!=0) {
    					$v = -1*floor(-log10(abs($answer))-1e-12) - $reqsigfigs;
    				}
    				if ($answer!=0  && $v < 0 && strlen($answer) - strpos($answer,'.')-1 + $v < 0) {
    					if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
    						$answer = implode(',', prettysigfig(explode(',', $answer), $reqsigfigs));
    					} else {
    						$answer = prettysigfig($answer,$reqsigfigs);
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
            $params['calcformat'] = $answerformat;

    		$params['tip'] = $shorttip;
    		$params['longtip'] = $tip;
    		if ($useeqnhelper && $useeqnhelper>2 && !(isset($scoremethod) && $scoremethod=='acct') &&
          !in_array('nosoln',$ansformats) && !in_array('nosolninf',$ansformats)
        ) {
    			$params['helper'] = 1;
    		}

    		$out .= $leftb .
    						'<input ' .
    						Sanitize::generateAttributeString($attributes) .
    						'class="'.implode(' ', $classes) .
    						'" />' .
    						$rightb;

            $out .= "<span id=p$qn></span>";
    		if ($displayformat=='hidden') {
    			//TODO: What's this for? Maybe virtual manipulatives?
    			$out .= '<script type="text/javascript">imasprevans['.$qstr.'] = "'.$la.'";</script>';
    		}

    		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
    			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
    			$answer = str_replace('"','',$answer);
    		}
    		if (isset($answer)) {
    			if (in_array('parenneg',$ansformats) && $answer < 0) {
    				$sa = '('.(-1*$answer).')';
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
