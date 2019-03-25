<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class CalculatedNTupleAnswerBox implements AnswerBox
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
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$partnum];} else {$displayformat = $options['displayformat'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$partnum];} else {$hidepreview = $options['hidepreview'];}}
        if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$partnum];} else {$reqdecimals = $options['reqdecimals'];}}
        if (!isset($answerformat)) { $answerformat = '';}
        $ansformats = array_map('trim',explode(',',$answerformat));

        $la = explode('$#$',$la);
        $la = $la[0];

        if (!isset($sz)) { $sz = 20;}
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        if ($displayformat == 'point') {
            $tip = _('Enter your answer as a point.  Example: (2,5.5172)') . "<br/>";
            $shorttip = _('Enter a point');
        } else if ($displayformat == 'pointlist') {
            $tip = _('Enter your answer a list of points separated with commas.  Example: (1,2), (3.5172,5)') . "<br/>";
            $shorttip = _('Enter a list of points');
        } else if ($displayformat == 'vector') {
            $tip = _('Enter your answer as a vector.  Example: <2,5.5172>') . "<br/>";
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
        $tip .= formathint('each value',$ansformats,isset($reqdecimals)?$reqdecimals:null,'calcntuple');

        $out .= "<input class=\"text $colorbox\" type=\"text\"  size=\"$sz\" name=tc$qn id=tc$qn value=\"".Sanitize::encodeStringForDisplay($la)."\" autocomplete=\"off\" ";
        if ($showtips==2) { //eqntips: work in progress
            if ($multi==0) {
                $qnref = "$qn-0";
            } else {
                $qnref = ($multi-1).'-'.($qn%1000);
            }
            if ($useeqnhelper) {
                $out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper);showehdd('tc$qn','$shorttip','$qnref');\" onblur=\"hideee();hideeedd();hideeh();\" onclick=\"reshrinkeh('tc$qn')\" ";
            } else {
                $out .= "onfocus=\"showehdd('tc$qn','$shorttip','$qnref')\" onblur=\"hideeh()\" onclick=\"reshrinkeh('tc$qn')\" ";
            }
            $out .= 'aria-describedby="tips'.$qnref.'" ';
        } else if ($useeqnhelper) {
            $out .= "onfocus=\"showeedd('tc$qn',$useeqnhelper)\" onblur=\"hideee();hideeedd();\" ";
        }
        if (!isset($hidepreview) && $GLOBALS['sessiondata']['userprefs']['livepreview']==1) {
            $out .= 'onKeyUp="updateLivePreview(this)" ';
        }
        $out .= "/>";
        $out .= "<input type=\"hidden\" id=\"qn$qn\" name=\"qn$qn\" />";
        $out .= getcolormark($colorbox);
        if (!isset($hidepreview)) {
            $preview .= "<input type=button class=btn id=\"pbtn$qn\" value=\"" . _('Preview') . "\" onclick=\"ntuplecalc('tc$qn','p$qn','$answerformat')\" /> &nbsp;\n";
        }
        $preview .= "<span id=p$qn></span> ";
        $out .= "<script type=\"text/javascript\">ntupletoproc[$qn] = 1; calcformat[$qn] = '$answerformat';</script>\n";
        //$tip .= "Enter DNE for Does Not Exist";

        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
        }
        if (isset($answer)) {
            $sa = makeprettydisp($answer);
            if ($displayformat == 'vector') {
                $sa = str_replace(array('<','>'), array('(:',':)'), $sa);
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
