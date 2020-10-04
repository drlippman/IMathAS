<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class IntervalAnswerBox implements AnswerBox
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
        global $RND, $myrights, $useeqnhelper, $showtips, $imasroot, $staticroot;

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
        if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$partnum];} else {$reqdecimals = $options['reqdecimals'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['readerlabel'])) {if (is_array($options['readerlabel'])) {$readerlabel = $options['readerlabel'][$partnum];} else {$readerlabel = $options['readerlabel'];}}

        if (!isset($sz)) { $sz = 20;}
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        if (isset($ansprompt)) {
          $out .= $ansprompt;
        }

        $ansformats = array_map('trim',explode(',',$answerformat));

    		if (in_array('normalcurve',$ansformats) && $_SESSION['graphdisp']!=0) {
    			$top = _('Enter your answer by selecting the shade type, and by clicking and dragging the sliders on the normal curve');
    			$shorttip = _('Adjust the sliders');
    		} else {

    			$tip = _('Enter your answer using interval notation.  Example: [2.1,5.6172)') . " <br/>";
    			$tip .= _('Use U for union to combine intervals.  Example: (-oo,2] U [4,oo)') . "<br/>";
    			if (!in_array('nosoln',$ansformats) && !in_array('nosolninf',$ansformats))  {
    				$tip .= _('Enter DNE for an empty set. Use oo to enter Infinity.');
    			} else {
    				$tip .= _('Use oo to enter Infinity.');
    			}
    			if (isset($reqdecimals)) {
    				$tip .= "<br/>" . sprintf(_('Your numbers should be accurate to %d decimal places.'), $reqdecimals);
    			}
    			$shorttip = _('Enter an interval using interval notation');
    		}
    		if (in_array('normalcurve',$ansformats) && $_SESSION['graphdisp']!=0) {
          $out .= '<div id="qnwrap'.$qn.'" class="'.$colorbox.'">';
    			$out .=  '<div style="background:#fff;padding:10px;">';
    			$out .=  '<p style="margin:0px";>Shade: <select id="shaderegions'.$qn.'" onchange="imathasDraw.chgnormtype(this.id.substring(12));"><option value="1L">' . _('Left of a value') . '</option><option value="1R">' . _('Right of a value') . '</option>';
    			$out .=  '<option value="2B">' . _('Between two values') . '</option><option value="2O">' . _('2 regions') . '</option></select>. ' . _('Click and drag the arrows to adjust the values.');

    			$out .=  '<div style="position: relative; width: 500px; height:200px;padding:0px;">';
    			//for future development of non-standard normal
    			//for ($i=0;$i<9;$i++) {
    			//	$out .= '<div style="position: absolute; left:'.(60*$i).'px; top:150px; height:20px; width:20px; background:#fff;z-index:2;text-align:center">'.($mu+($i-4)*$sig).'</div>';
    			//}
    			$out .=  '<div style="position: absolute; left:0; top:0; height:200px; width:0px; background:#00f;" id="normleft'.$qn.'">&nbsp;</div>';
    			$out .=  '<div style="position: absolute; right:0; top:0; height:200px; width:0px; background:#00f;" id="normright'.$qn.'">&nbsp;</div>';
    			$out .=  '<img style="position: absolute; left:0; top:0;z-index:1;width:100%;max-width:100%" src="'.$staticroot.'/img/normalcurve.gif" alt="Normal curve" />';
    			$out .=  '<img style="position: absolute; top:142px;left:0px;cursor:pointer;z-index:3;" id="slid1'.$qn.'" src="'.$staticroot.'/img/uppointer.gif" alt="Interval pointer"/>';
    			$out .=  '<img style="position: absolute; top:142px;left:0px;cursor:pointer;z-index:3;" id="slid2'.$qn.'" src="'.$staticroot.'/img/uppointer.gif" alt="Interval pointer"/>';
    			$out .=  '<div style="position: absolute; top:170px;left:0px;z-index:3;" id="slid1txt'.$qn.'"></div>';
    			$out .=  '<div style="position: absolute; top:170px;left:0px;z-index:3;" id="slid2txt'.$qn.'"></div>';
    			$out .=  '</div></div></div>';
    		} else if (in_array('normalcurve',$ansformats)) {
    			$out .= _('Enter an interval corresponding to the region to be shaded');
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
            $params['calcformat'] = 'decimal';
    		if ($useeqnhelper) {
    			$params['helper'] = 1;
    		}
    		if (in_array('normalcurve',$ansformats) && $_SESSION['graphdisp']!=0) {
    			$classes[] = 'hidden';
    			$params['format'] = 'normslider';
          $params['helper'] = 0;
    		}

    		$out .= '<input ' .
                Sanitize::generateAttributeString($attributes) .
    						'class="'.implode(' ', $classes) .
    						'" />';

        $preview .= "<span id=p$qn></span> ";

    		if (in_array('nosoln',$ansformats))  {
    			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox, 'interval');
    			$answer = str_replace('"','',$answer);
    		}
    		if (isset($answer)) {
    			if (in_array('normalcurve',$ansformats) && $_SESSION['graphdisp']!=0) {
    				$sa .=  '<div style="position: relative; width: 500px; height:200px;padding:0px;background:#fff;">';
    				$answer = preg_replace('/\s/','',$answer);
    				if (preg_match('/\(-oo,([\-\d\.]+)\)U\(([\-\d\.]+),oo\)/',$answer,$matches)) {
    					$sa .=  '<div style="position: absolute; left:0; top:0; height:200px; width:'.(250+60*$matches[1]+1).'px; background:#00f;">&nbsp;</div>';
    					$sa .=  '<div style="position: absolute; right:0; top:0; height:200px; width:'.(250-60*$matches[2]).'px; background:#00f;">&nbsp;</div>';
    				} else if (preg_match('/\(-oo,([\-\d\.]+)\)/',$answer,$matches)) {
    					$sa .=  '<div style="position: absolute; left:0; top:0; height:200px; width:'.(250+60*$matches[1]+1).'px; background:#00f;">&nbsp;</div>';
    				} else if (preg_match('/\(([\-\d\.]+),oo\)/',$answer,$matches)) {
    					$sa .=  '<div style="position: absolute; right:0; top:0; height:200px; width:'.(250-60*$matches[1]).'px; background:#00f;">&nbsp;</div>';
    				} else if (preg_match('/\(([\-\d\.]+),([\-\d\.]+)\)/',$answer,$matches)) {
    					$sa .=  '<div style="position: absolute; left:'.(250+60*$matches[1]).'px; top:0; height:200px; width:'.(60*($matches[2]-$matches[1])+1).'px; background:#00f;">&nbsp;</div>';
    				}
    				$sa .=  '<img style="position: absolute; left:0; top:0;z-index:1;width:100%;max-width:100%" src="'.$staticroot.'/img/normalcurve.gif" alt="Normal Curve"/>';
    				$sa .=  '</div>';
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
