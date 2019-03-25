<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class CalculatedIntervalAnswerBox implements AnswerBox
{
    private $answerBoxParams;

    private $answerBox;
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

        // FIXME: The following code needs to be updated
        //        - $qn is always the question number (never $qn+1)
        //        - $multi is now a boolean
        //        - $partnum is now available

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';

        if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$partnum];} else {$ansprompt = $options['ansprompt'];}}
        if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$partnum];} else {$sz = $options['answerboxsize'];}}
        if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$partnum];} else {$hidepreview = $options['hidepreview'];}}
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$partnum];} else {$reqdecimals = $options['reqdecimals'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['variables'])) {if (is_array($options['variables'])) {$variables = $options['variables'][$partnum];} else {$variables = $options['variables'];}}
        if (!isset($answerformat)) { $answerformat = '';}
        if (!isset($variables)) { $variables = 'x';}
        $ansformats = array_map('trim',explode(',',$answerformat));

        if (!isset($sz)) { $sz = 20;}
        if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}
        if ($multi>0) { $qn = $multi*1000+$qn;}

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

        $out .= "<input class=\"text $colorbox\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"".Sanitize::encodeStringForDisplay($la)."\" autocomplete=\"off\"  ";
        if ($showtips==2) { //eqntips: work in progress
            if ($multi==0) {
                $qnref = "$qn-0";
            } else {
                $qnref = ($multi-1).'-'.($qn%1000);
            }
            if ($useeqnhelper) {
                $out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper,". (in_array('inequality',$ansformats)?"'ineq'":"'int'") .");showehdd('tc$qn','$shorttip','$qnref');\" onblur=\"hideee();hideeedd();hideeh();\" onclick=\"reshrinkeh('tc$qn')\" ";
            } else {
                $out .= "onfocus=\"showehdd('tc$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" onclick=\"reshrinkeh('tc$qn')\" ";
            }
            $out .= 'aria-describedby="tips'.$qnref.'" ';
        } else if ($useeqnhelper) {
            $out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper,". (in_array('inequality',$ansformats)?"'ineq'":"'int'") .")\" onblur=\"hideee();hideeedd();\" ";
        }
        if (!isset($hidepreview) && $GLOBALS['sessiondata']['userprefs']['livepreview']==1) {
            $out .= 'onKeyUp="updateLivePreview(this)" ';
        }
        $out .= '/>';
        $out .= "<input type=\"hidden\" id=\"qn$qn\" name=\"qn$qn\" />";
        $out .= getcolormark($colorbox);
        if (!isset($hidepreview)) {
            $preview .= "<input type=button class=btn id=\"pbtn$qn\" value=\"" . _('Preview') . "\" onclick=\"intcalculate('tc$qn','p$qn','$answerformat')\" /> &nbsp;\n";
        }
        $preview .= "<span id=p$qn></span> ";
        $out .= "<script type=\"text/javascript\">intcalctoproc[$qn] = 1 ; calcformat[$qn] = '$answerformat';</script>\n";

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
        $this->entryTip = $tip;
        $this->correctAnswerForPart = $sa;
        $this->previewLocation = $preview;
    }

    public function getAnswerBox(): string
    {
        return $this->answerBox;
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
