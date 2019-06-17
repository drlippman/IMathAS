<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

use Sanitize;

class FileUploadAnswerBox implements AnswerBox
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
        $autosave = '';
        // if there's an autosave, then $la will be an array
        if (is_array($la)) {
          list($la, $autosave) = $la;
        }
        $options = $this->answerBoxParams->getQuestionWriterVars();
        $colorbox = $this->answerBoxParams->getColorboxKeyword();
        $assessmentId = $this->answerBoxParams->getAssessmentId();

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $params = [];

        if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$partnum];} else {$ansprompt = $options['ansprompt'];}}
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if (isset($options['scoremethod'])) {if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$partnum];} else {$scoremethod = $options['scoremethod'];}}
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        if (isset($ansprompt)) {
          $out .= "<label for=\"qn$qn\">$ansprompt</label>";
        }

    		$out .= "<input type=\"file\" name=\"qn$qn\" id=\"qn$qn\" class=\"filealt\" />\n";
        $out .= '<label for="qn'.$qn.'"><span role="button" class="filealt-btn '.$colorbox.'">';
        $out .= _('Choose File').'</span>';
        $out .= '<span class="filealt-label" data-def="'._('No file chosen').'">';
        if ($autosave != '') {
          $out .= Sanitize::encodeStringForDisplay(basename(preg_replace('/@FILE:(.+?)@/',"$1",$autosave)));
          $out .= '<input type=hidden id="qn'.$qn.'-autosave" value="1"/>';
        } else {
          $out .= _('No file chosen');
        }
        $out .= '</span></label>';
        $hasPrevSubmittedFile = false;
    		if ($la!='') {
    			if (!empty($assessmentId)) {
    				$s3asid = $assessmentId;
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
    			}
    			if (!empty($s3asid)) {
    				require_once(dirname(__FILE__)."/../../../includes/filehandler.php");

    				if (substr($la,0,5)=="Error") {
    					$out .= "<br/>$la";
    				} else {
    					$file = preg_replace('/@FILE:(.+?)@/',"$1",$la);
    					$url = getasidfileurl($file);
    					$extension = substr($url,strrpos($url,'.')+1,3);
    					$filename = basename($file);
    					$out .= "<br/>" . _('Last file submitted:') . " <a href=\"$url\" target=\"_blank\">$filename</a>";
    					$out .= "<input type=\"hidden\" name=\"lf$qn\" value=\"$file\" />";
    					if (in_array(strtolower($extension),array('jpg','gif','png','bmp','jpe'))) {
    						$out .= " <span aria-expanded=\"false\" aria-controls=\"img$qn\" class=\"pointer\" id=\"filetog$qn\" onclick=\"toggleinlinebtn('img$qn','filetog$qn');\">[+]</span>";
    						$out .= " <br/><div><img id=\"img$qn\" style=\"display:none;max-width:80%;\" aria-hidden=\"true\" onclick=\"rotateimg(this)\" src=\"$url\" alt=\"Student uploaded image\"/></div>";
    					} else if (in_array(strtolower($extension),array('doc','docx','pdf','xls','xlsx','ppt','pptx'))) {
    						$out .= " <span aria-expanded=\"false\" aria-controls=\"fileprev$qn\" class=\"pointer\" id=\"filetog$qn\" onclick=\"toggleinlinebtn('fileprev$qn','filetog$qn');\">[+]</span>";
    						$out .= " <br/><iframe id=\"fileprev$qn\" style=\"display:none;\" aria-hidden=\"true\" src=\"https://docs.google.com/viewer?url=".rawurlencode($url)."&embedded=true\" width=\"80%\" height=\"600px\"></iframe>";
    					}
              $hasPrevSubmittedFile = true;
    				}
    			} else {
    				$out .= "<br/>$la";
    			}
    		}
    		$tip .= _('Select a file to upload');
    		$sa .= $answer;

        if ($scoremethod == 'takeanythingorblank' && !$hasPrevSubmittedFile) {
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
