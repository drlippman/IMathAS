<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class MultipleAnswerAnswerBox implements AnswerBox
{
    private $answerBoxParams;

    private $answerBox;
    private $jsParams = [];
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

		if (is_array($options['questions'][$partnum])) {$questions = $options['questions'][$partnum];} else {$questions = $options['questions'];}
		if (isset($options['answers'])) {if (is_array($options['answers'])) {$answers = $options['answers'][$partnum];} else {$answers = $options['answers'];}}
		else if (isset($options['answer'])) {if (is_array($options['answer'])) {$answers = $options['answer'][$partnum];} else {$answers = $options['answer'];}}
		if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$partnum];} else {$noshuffle = $options['noshuffle'];}}
		if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$partnum];} else {$displayformat = $options['displayformat'];}}

    if (!is_array($questions)) {
      echo _('Eeek!  $questions is not defined or needs to be an array');
      $questions = array();
    }

    if ($multi) { $qn = ($qn+1)*1000+$partnum; }


    if ($noshuffle == "last") {
			$randkeys = $RND->array_rand(array_slice($questions,0,count($questions)-1),count($questions)-1);
			$RND->shuffle($randkeys);
			array_push($randkeys,count($questions)-1);
		} else if ($noshuffle == "all" || count($questions)==1) {
			$randkeys = array_keys($questions);
		} else {
			$randkeys = $RND->array_rand($questions,count($questions));
			$RND->shuffle($randkeys);
		}
    if (count($questions) > 1 && trim($answers) == "") {
      $qstr = strtolower(implode(' ', $questions));
      if (strpos($qstr, 'none of') === false) {
        $questions[] = _('None of these');
        array_push($randkeys,count($questions)-1);
        $answers = count($questions)-1;
      }
    }
		$_SESSION['choicemap'][$qn] = $randkeys;
    if (isset($GLOBALS['capturechoices'])) {
      $GLOBALS['choicesdata'][$qn] = $questions;
    }
		if (isset($GLOBALS['capturechoiceslivepoll'])) {
      $params['livepoll_choices'] = $questions;
      $params['livepoll_ans'] = $answers;
      $params['livepoll_randkeys'] = $randkeys;
		}

    if ($la=='') {
      $labits = array();
    } else {
		  $labits = explode('|',$la);
    }
    if (!isset($displayformat)) {
      $displayformat = 'list';
    }

		if ($displayformat == 'column') { $displayformat = '2column';}

		if (substr($displayformat,1)=='column') {
			$ncol = $displayformat{0};
			$itempercol = ceil(count($randkeys)/$ncol);
			$displayformat = 'column';
		}

		if ($displayformat == 'inline') {
			if ($colorbox != '') {$style .= ' class="'.$colorbox.'" ';} else {$style='';}
			$out .= "<span $style id=\"qnwrap$qn\" role=group aria-label=\""._('Select one, none, or multiple answers')."\">";
		} else  {
			if ($colorbox != '') {$style .= ' class="'.$colorbox.' clearfix" ';} else {$style=' class="clearfix" ';}
			$out .= "<div $style id=\"qnwrap$qn\" style=\"display:block\" role=group aria-label=\""._('Select one, none, or multiple answers')."\">";
		}
		if ($displayformat == "horiz") {

		} else if ($displayformat == "inline") {

		} else if ($displayformat == 'column') {

		} else {
			$out .= "<ul class=nomark>";
		}

		for ($i=0; $i < count($randkeys); $i++) {
			if ($displayformat == "horiz") {
				$out .= "<div class=choice><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label><br/>";
				$out .= "<input type=checkbox name=\"qn$qn"."[$i]\" value=$i id=\"qn$qn-$i\" ";
        if (in_array($randkeys[$i], $labits)) { $out .= 'checked';}
				$out .= " /></div> \n";
			} else if ($displayformat == "inline") {
				$out .= "<input type=checkbox name=\"qn$qn"."[$i]\" value=$i id=\"qn$qn-$i\" ";
				if (in_array($randkeys[$i], $labits)) { $out .= 'checked';}
				$out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label> ";
			} else if ($displayformat == 'column') {
				if ($i%$itempercol==0) {
					if ($i>0) {
						$out .= '</ul></div>';
					}
					$out .= '<div class="match"><ul class=nomark>';
				}
				$out .= "<li><input type=checkbox name=\"qn$qn"."[$i]\" value=$i id=\"qn$qn-$i\" ";
				if (in_array($randkeys[$i], $labits)) { $out .= 'checked';}
				$out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label></li> \n";
			} else {
				$out .= "<li><input class=\"unind\" type=checkbox name=\"qn$qn"."[$i]\" value=$i id=\"qn$qn-$i\" ";
				if (in_array($randkeys[$i], $labits)) { $out .= 'checked';}
				$out .= " /><label for=\"qn$qn-$i\">{$questions[$randkeys[$i]]}</label></li> \n";
			}
		}
		if ($displayformat == "horiz") {
			//$out .= "<div class=spacer>&nbsp;</div>\n";
		} else if ($displayformat == "inline") {

		} else if ($displayformat == 'column') {
			$out .= "</ul></div>";//<div class=spacer>&nbsp;</div>\n";
		} else {
			$out .= "</ul>\n";
		}
		if ($displayformat == 'inline') {
			$out .= "</span>";
		} else {
			$out .= "</div>";
		}
		$tip = _('Select all correct answers');
		if (isset($answers)) {
			$akeys = array_map('trim',explode(',',$answers));
			foreach($akeys as $akey) {
				$sa .= '<br/>'.$questions[$akey];
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
