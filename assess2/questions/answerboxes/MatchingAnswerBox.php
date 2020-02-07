<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class MatchingAnswerBox implements AnswerBox
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

        // FIXME: The following code needs to be updated
        //        - $qn is always the question number (never $qn+1)
        //        - $multi is now a boolean
        //        - $partnum is now available

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';

        if (is_array($options['questions'][$partnum])) {$questions = $options['questions'][$partnum];} else {$questions = $options['questions'];}
        if (isset($options['answers'])) {if (is_array($options['answers'][$partnum])) {$answers = $options['answers'][$partnum];} else {$answers = $options['answers'];}}
        else if (isset($options['answer'])) {if (is_array($options['answer'][$partnum])) {$answers = $options['answer'][$partnum];} else {$answers = $options['answer'];}}
        if (isset($options['questiontitle'])) {if (is_array($options['questiontitle'])) {$questiontitle = $options['questiontitle'][$partnum];} else {$questiontitle = $options['questiontitle'];}}
        if (isset($options['answertitle'])) {if (is_array($options['answertitle'])) {$answertitle = $options['answertitle'][$partnum];} else {$answertitle = $options['answertitle'];}}
        if (isset($options['matchlist'])) {if (is_array($options['matchlist'])) {$matchlist = $options['matchlist'][$partnum];} else {$matchlist = $options['matchlist'];}}
        if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$partnum];} else {$noshuffle = $options['noshuffle'];}}
        if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$partnum];} else {$displayformat = $options['displayformat'];}}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        if (!is_array($questions)) {
          echo _('Eeek!  $questions is not defined or needs to be an array');
          $questions = array();
        }
        if (!is_array($answers)) {
          echo _('Eeek!  $answers is not defined or needs to be an array');
          $answers = array();
        }
        if (isset($matchlist)) { $matchlist = array_map('trim',explode(',',$matchlist));}
    		if ($noshuffle=="questions" || $noshuffle=='all') {
    			$randqkeys = array_keys($questions);
    		} else {
    			$randqkeys = $RND->array_rand($questions,count($questions));
    			$RND->shuffle($randqkeys);
    		}
    		if ($noshuffle=="answers" || $noshuffle=='all') {
    			$randakeys = array_keys($answers);
    		} else {
    			$randakeys = $RND->array_rand($answers,count($answers));
    			$RND->shuffle($randakeys);
    		}
        $_SESSION['choicemap'][$qn] = array($randqkeys, $randakeys);
        if (isset($GLOBALS['capturechoices'])) {
          $GLOBALS['choicesdata'][$qn] = array($randqkeys, $answers);
        }
    		if (isset($GLOBALS['capturechoiceslivepoll'])) {
          /* TODO
          $params['livepoll_choices'] = $questions;
          $params['livepoll_ans'] = $answer;
          $params['livepoll_randkeys'] = $randakeys;
          */
    		}

    		$ncol = 1;
    		if (substr($displayformat,1)=='columnselect') {
    			$ncol = $displayformat{0};
    			$itempercol = ceil(count($randqkeys)/$ncol);
    			$displayformat = 'select';
    		}
    		if (substr($displayformat,0,8)=="limwidth") {
    			$divstyle = 'style="max-width:'.substr($displayformat,8).'px;"';
    		} else {
    			$divstyle = '';
    		}
    		if ($colorbox != '') {$out .= '<div class="'.$colorbox.'" id="qnwrap'.$qn.'" style="display:block">';}
    		$out .= "<div class=\"match\" $divstyle>\n";
    		$out .= "<p class=\"centered\">$questiontitle</p>\n";
    		$out .= "<ul class=\"nomark\">\n";
        if ($la=='') {
          $las = array();
        } else {
    		  $las = explode("|",$la);
        }

    		$letters = array_slice(explode(',','a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,aa,ab,ac,ad,ae,af,ag,ah,ai,aj,ak,al,am,an,ao,ap,aq,ar,as,at,au,av,aw,ax,ay,az'),0,count($answers));

    		for ($i=0;$i<count($randqkeys);$i++) {
          if (isset($las[$randqkeys[$i]])) {
            $laval = $las[$randqkeys[$i]];
          } else {
            $laval = '-';
          }
    			if ($ncol>1) {
    				if ($i>0 && $i%$itempercol==0) {
    					$out .= '</ul></div><div class="match"><ul class=nomark>';
    				}
    			}
    			if (strpos($questions[$randqkeys[$i]],' ')===false || strlen($questions[$randqkeys[$i]])<12) {
    				$out .= '<li class="nowrap">';
    			} else {
    				$out .= '<li>';
    			}
    			$out .= "<select name=\"qn$qn-$i\" id=\"qn$qn-$i\">";
    			$out .= '<option value="-" ';
    			if ($laval=='-' || strcmp($laval,'')==0) {
    				$out .= 'selected="1"';
    			}
    			$out .= '>-</option>';
    			if ($displayformat=="select") {
    				for ($j=0;$j<count($randakeys);$j++) {
    					$out .= "<option value=\"".$j."\" ";
    					if (strcmp($laval, $randakeys[$j])==0) {
    						$out .= 'selected="1"';
    					}
    					$out .= ">".str_replace('`','',$answers[$randakeys[$j]])."</option>\n";
    				}
    			} else {
    				foreach ($letters as $j=>$v) {
    					//$out .= "<option value=\"$v\" ";
    					$out .= "<option value=\"$j\" ";
    					if (strcmp($laval,$randakeys[$j])==0) {
    						$out .= 'selected="1"';
    					}
    					$out .= ">$v</option>";
    				}
    			}
    			$out .= "</select>&nbsp;<label for=\"qn$qn-$i\">{$questions[$randqkeys[$i]]}</label></li>\n";
    		}
    		$out .= "</ul>\n";
    		$out .= "</div>";

    		if (!isset($displayformat) || $displayformat!="select") {
    			$out .= "<div class=\"match\" $divstyle>\n";
    			$out .= "<p class=centered>$answertitle</p>\n";

    			$out .= "<ol class=lalpha>\n";
    			for ($i=0;$i<count($randakeys);$i++) {
    				$out .= "<li>{$answers[$randakeys[$i]]}</li>\n";
    			}
    			$out .= "</ol>";
    			$out .= "</div>";
    		}
    		$out .= "<div class=spacer>&nbsp;</div>";
    		if ($colorbox != '') {$out .= '</div>';}
    		//$tip = "In each box provided, type the letter (a, b, c, etc.) of the matching answer in the right-hand column";
    		if ($displayformat=="select") {
    			$tip = _('In each pull-down, select the item that matches with the displayed item');
    		} else {
    			$tip = _('In each pull-down on the left, select the letter (a, b, c, etc.) of the matching answer in the right-hand column');
    		}
    		for ($i=0; $i<count($randqkeys);$i++) {
    			if (isset($matchlist)) {
    				$akey = array_search($matchlist[$randqkeys[$i]],$randakeys);
    			} else {
    				$akey = array_search($randqkeys[$i],$randakeys);
    			}
    			if ($displayformat == "select") {
    				$sa .= '<br/>'.$answers[$randakeys[$akey]];
    			} else {
    				$sa .= chr($akey+97)." ";
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
