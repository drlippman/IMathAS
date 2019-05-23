<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class CalculatedMatrixAnswerBox implements AnswerBox
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
        if (isset($options['answersize'])) {if (is_array($options['answersize'])) {$answersize = $options['answersize'][$partnum];} else {$answersize = $options['answersize'];}}
        if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$partnum];} else {$sz = $options['answerboxsize'];}}
        if (isset($options['hidepreview'])) {if (is_array($options['hidepreview'])) {$hidepreview = $options['hidepreview'][$partnum];} else {$hidepreview = $options['hidepreview'];}}
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['reqdecimals'])) {if (is_array($options['reqdecimals'])) {$reqdecimals = $options['reqdecimals'][$partnum];} else {$reqdecimals = $options['reqdecimals'];}}
        if (!isset($answerformat)) { $answerformat = '';}
        $ansformats = array_map('trim',explode(',',$answerformat));


        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        if (isset($ansprompt) && !in_array('nosoln',$ansformats) && !in_array('nosolninf',$ansformats))  {
    			$out .= "<label for=\"qn$qn\">$ansprompt</label>";
    		}
    		if (isset($answersize)) {
    			list($tip,$shorttip) = formathint(_('each element of the matrix'),$ansformats,isset($reqdecimals)?$reqdecimals:null,'calcmatrix',false,true);
    			//$tip = "Enter each element of the matrix as  number (like 5, -3, 2.2) or as a calculation (like 5/3, 2^3, 5+4)";

    			if (!isset($sz)) { $sz = 3;}
    			$answersize = explode(",",$answersize);
    			if ($colorbox=='') {
    				$out .= '<table id="qnwrap'.$qn.'">';
    			} else {
    				$out .= '<table class="'.$colorbox.'" id="qnwrap'.$qn.'">';
    			}
    			$out .= '<tr><td class="matrixleft">&nbsp;</td><td>';
    			$out .= "<table>";
    			$count = 0;
    			$las = explode("|",$la);
    			for ($row=0; $row<$answersize[0]; $row++) {
    				$out .= "<tr>";
    				for ($col=0; $col<$answersize[1]; $col++) {
    					$out .= "<td>";

    					$attributes = [
    						'type' => 'text',
    						'size' => $sz,
    						'name' => "qn$qn-$count",
    						'id' => "qn$qn-$count",
    						'value' => $las[$count],
    						'autocomplete' => 'off'
    					];

    					$params['matrixsize'] = $answersize;

    					$out .= '<input ' .
    									Sanitize::generateAttributeString($attributes) .
    									'" />';

    					$out .= "</td>\n";
    					$count++;
    				}
    				$out .= "</tr>";
    			}
    			$out .= "</table>\n";
    			$out .= '</td><td class="matrixright">&nbsp;</td></tr></table>';
    		} else {
    			if ($multi==0) {
    				$qnref = "$qn-0";
    			} else {
    				$qnref = ($multi-1).'-'.($qn%1000);
    			}
    			$shorttip = _('Enter your answer as a matrix, like ((2,3,4),(1,4,5))');
    			$tip = $shorttip.'<br/>'.formathint(_('each element of the matrix'),$ansformats,isset($reqdecimals)?$reqdecimals:null,'calcmatrix');
    			if (!isset($sz)) { $sz = 20;}

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

    			$out .= '<input ' .
    							Sanitize::generateAttributeString($attributes) .
    							'class="'.implode(' ', $classes) .
    							'" />';
    		}
    		if (!isset($hidepreview)) {
    			$params['preview'] = 1;
    			$preview .= "<input type=button class=btn id=\"pbtn$qn\" value=\"" . _('Preview') . "\"/> &nbsp;\n";
    		}
    		$preview .= "<span id=p$qn></span> ";
    		$params['tip'] = $shorttip;
        $params['longtip'] = $tip;
    		$params['calcformat'] = $answerformat;
    		if ($useeqnhelper) {
    			$params['helper'] = 1;
    		}

    		if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
    			list($out,$answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
    		}

    		if (isset($answer)) {
    			$sa = '`'.$answer.'`';
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
