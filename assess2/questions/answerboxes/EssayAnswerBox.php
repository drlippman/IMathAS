<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class EssayAnswerBox implements AnswerBox
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

        if (isset($options['answerboxsize'])) {if (is_array($options['answerboxsize'])) {$sz = $options['answerboxsize'][$partnum];} else {$sz = $options['answerboxsize'];}}
        if (isset($options['displayformat'])) {if (is_array($options['displayformat'])) {$displayformat = $options['displayformat'][$partnum];} else {$displayformat = $options['displayformat'];}}
        if (isset($options['scoremethod'])) {if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$partnum];} else {$scoremethod = $options['scoremethod'];}}
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        if (!isset($sz)) {
    			$rows = 5;
    			$cols = 50;
    		} else if (strpos($sz,',')>0) {
    			list($rows,$cols) = explode(',',$sz);
    		} else {
    			$cols = 50;
    			$rows = $sz;
    		}
    		if ($displayformat=='editor') {
    			$rows += 5;
    		}
    		if ($GLOBALS['useeditor']=='review' || ($GLOBALS['useeditor']=='reviewifneeded' && trim($la)=='')) {
    			$la = str_replace('&quot;','"',$la);

    			if ($displayformat!='editor') {
    				$la = preg_replace('/\n/','<br/>',$la);
    			}
    			if ($colorbox=='') {
    				$out .= '<div class="intro" id="qnwrap'.$qn.'">';
    			} else {
    				$out .= '<div class="intro '.$colorbox.'" id="qnwrap'.$qn.'">';
    			}
    			if (isset($GLOBALS['questionscoreref'])) {
    				if ($multi==0) {
    					$el = $GLOBALS['questionscoreref'][0];
    					$sc = $GLOBALS['questionscoreref'][1];
    				} else {
    					$el = $GLOBALS['questionscoreref'][0].'-'.($qn%1000);
    					$sc = $GLOBALS['questionscoreref'][1][$qn%1000];
    				}
    				$out .= '<span style="float:right;">';
    				$out .= '<img class="scoreicon" src="'.$imasroot.'/img/q_fullbox.gif" alt="Set score full credit" ';
    				$out .= "onclick=\"quicksetscore('$el',$sc)\" />";
    				$out .= '<img class="scoreicon" src="'.$imasroot.'/img/q_halfbox.gif" alt="Set score half credit" ';
    				$out .= "onclick=\"quicksetscore('$el',.5*$sc)\" />";
    				$out .= '<img class="scoreicon" src="'.$imasroot.'/img/q_emptybox.gif" alt="Set score no credit" ';
    				$out .= "onclick=\"quicksetscore('$el',0)\" /></span>";

    				$la = preg_replace_callback('/<a[^>]*href="(.*?)"[^>]*>(.*?)<\/a>/', function ($m) {
    					global $gradededessayexpandocnt;
    					if (!isset($gradededessayexpandocnt)) {
    						$gradededessayexpandocnt = 0;
    					}
    					if (strpos($m[0],'target=')===false) {
    						$ret = '<a target="_blank" '.substr($m[0], 2);
    					} else {
    						//force links to open in a new window to prevent manual scores and feedback from being lost
    						$ret = preg_replace('/target=".*?"/','target="_blank"',$m[0]);
    					}
    					$url = $m[1];
    					$extension = substr($url,strrpos($url,'.')+1,3);
    					if (in_array(strtolower($extension),array('jpg','gif','png','bmp','jpe'))) {
    						$ret .= " <span aria-expanded=\"false\" aria-controls=\"essayimg$gradededessayexpandocnt\" class=\"clickable\" id=\"essaytog$gradededessayexpandocnt\" onclick=\"toggleinlinebtn('essayimg$gradededessayexpandocnt','essaytog$gradededessayexpandocnt');\">[+]</span>";
    						$ret .= " <br/><img id=\"essayimg$gradededessayexpandocnt\" style=\"display:none;max-width:80%;\" aria-hidden=\"true\" src=\"$url\" alt=\"Student uploaded image\" />";
    					} else if (in_array(strtolower($extension),array('doc','docx','pdf','xls','xlsx','ppt','pptx'))) {
    						$ret .= " <span aria-expanded=\"false\" aria-controls=\"essayfileprev$gradededessayexpandocnt\" class=\"clickable\" id=\"essaytog$gradededessayexpandocnt\" onclick=\"toggleinlinebtn('essayfileprev$gradededessayexpandocnt','essaytog$gradededessayexpandocnt');\">[+]</span>";
    						$ret .= " <br/><iframe id=\"essayfileprev$gradededessayexpandocnt\" style=\"display:none;\" aria-hidden=\"true\" src=\"https://docs.google.com/viewer?url=".Sanitize::encodeUrlParam($url)."&embedded=true\" width=\"80%\" height=\"600px\"></iframe>";
    					}
    					$gradededessayexpandocnt++;
    					return $ret;
    				   }, $la);
    			}

    			$out .= filter($la);
    			$out .= "</div>";
    		} else {

    			if ($displayformat=='editor' && $GLOBALS['useeditor']==1) {
    				$la = str_replace('&quot;','"',$la);
    			}
    			if ($rows<2) {
    				$out .= "<input type=\"text\" class=\"text $colorbox\" size=\"$cols\" name=\"qn$qn\" id=\"qn$qn\" value=\"".Sanitize::encodeStringForDisplay($la)."\" /> ";
    			} else {
    				if ($colorbox!='') { $out .= '<div class="'.$colorbox.'">';}
    				$out .= "<textarea rows=\"$rows\" name=\"qn$qn\" id=\"qn$qn\" ";
    				if ($displayformat=='editor' && $GLOBALS['useeditor']==1) {
    					$out .= "style=\"width:98%;\" class=\"mceEditor\" ";
    				} else {
    					$out .= "cols=\"$cols\" ";
    				}
    				$out .= sprintf(">%s</textarea>\n", Sanitize::encodeStringForDisplay($la, true));
    				if ($colorbox!='') { $out .= '</div>';}
    			}
    			if ($displayformat=='editor' && $GLOBALS['useeditor']==1) {
            $params['usetinymce'] = 1;
          }
    		}
    		$tip .= _('Enter your answer as text.  This question is not automatically graded.');
    		$sa .= $answer;

        if ($scoremethod == 'takeanythingorblank' && trim($la) == '') {
          $params['submitblank'] = 1;
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
