<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once(__DIR__ . '/AnswerBox.php');

class FileUploadAnswerBox implements AnswerBox
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
        if (isset($options['answer'])) {if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        if (isset($ansprompt)) {$out .= "<label for=\"qn$qn\">$ansprompt</label>";}

        if ($colorbox!='') { $out .= '<span class="'.$colorbox.'">';}
        $out .= "<input type=\"file\" name=\"qn$qn\" id=\"qn$qn\" />\n";
        $out .= getcolormark($colorbox);
        if ($colorbox!='') { $out .= '</span>';}
        if ($la!='') {
            if (isset($GLOBALS['testsettings']) && isset($GLOBALS['sessiondata']['groupid']) && $GLOBALS['testsettings']>0 && $GLOBALS['sessiondata']['groupid']>0) {
                $s3asid = 'grp'.$GLOBALS['sessiondata']['groupid'].'/'.$GLOBALS['testsettings']['id'];
            } else if (isset($GLOBALS['asid'])) {
                $s3asid = $GLOBALS['asid'];
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
                require_once(dirname(__FILE__)."/../includes/filehandler.php");

                if (substr($la,0,5)=="Error") {
                    $out .= "<br/>$la";
                } else {
                    $file = preg_replace('/@FILE:(.+?)@/',"$1",$la);
                    $url = getasidfileurl($file);
                    $extension = substr($url,strrpos($url,'.')+1,3);
                    $filename = basename($file);
                    $out .= "<br/>" . _('Last file uploaded:') . " <a href=\"$url\" target=\"_new\">$filename</a>";
                    $out .= "<input type=\"hidden\" name=\"lf$qn\" value=\"$file\"/>";
                    if (in_array(strtolower($extension),array('jpg','gif','png','bmp','jpe'))) {
                        $out .= " <span aria-expanded=\"false\" aria-controls=\"img$qn\" class=\"clickable\" id=\"filetog$qn\" onclick=\"toggleinlinebtn('img$qn','filetog$qn');\">[+]</span>";
                        $out .= " <br/><div><img id=\"img$qn\" style=\"display:none;max-width:80%;\" aria-hidden=\"true\" onclick=\"rotateimg(this)\" src=\"$url\" alt=\"Student uploaded image\"/></div>";
                    } else if (in_array(strtolower($extension),array('doc','docx','pdf','xls','xlsx','ppt','pptx'))) {
                        $out .= " <span aria-expanded=\"false\" aria-controls=\"fileprev$qn\" class=\"clickable\" id=\"filetog$qn\" onclick=\"toggleinlinebtn('fileprev$qn','filetog$qn');\">[+]</span>";
                        $out .= " <br/><iframe id=\"fileprev$qn\" style=\"display:none;\" aria-hidden=\"true\" src=\"https://docs.google.com/viewer?url=".rawurlencode($url)."&embedded=true\" width=\"80%\" height=\"600px\"></iframe>";
                    }

                }
            } else {
                $out .= "<br/>$la";
            }
        }
        $tip .= _('Select a file to upload');
        $sa .= $answer;

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
