<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

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

        $optionkeys = ['ansprompt', 'answer', 'scoremethod',
            'answerformat', 'readerlabel'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }

        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}
        if (!empty($ansprompt)) {
            $out .= "$ansprompt ";
        }
        if ($GLOBALS['useeditor'] !== 'review') {
            $out .= "<input type=\"file\" name=\"qn$qn\" id=\"qn$qn\" class=\"filealt\" ";
            if (!empty($answerformat)) {
                $answerformat = str_replace('images', '.jpg,.jpeg,.gif,.png', $answerformat);
                $answerformat = str_replace('canpreview', '.doc,.docx,.pdf,.xls,.xlsx,.ppt,.pptx,.jpg,.gif,.png,.jpeg', $answerformat);
                $out .= 'accept="' . preg_replace('/[^\w\.,\/\*\-]/', '', $answerformat) . '"';
            }
            $out .= "/>\n";
            $out .= '<label for="qn' . $qn . '"><span role="button" class="filealt-btn ' . $colorbox . '">';
            $out .= '<span class="sr-only">' . $this->answerBoxParams->getQuestionIdentifierString() .
                (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : '') . '</span>';
            $out .= _('Choose File') . '</span>';
            $out .= '<span class="filealt-label" data-def="' . _('No file chosen') . '">';
            if ($autosave != '') {
                $out .= Sanitize::encodeStringForDisplay(basename(preg_replace('/@FILE:(.+?)@/', "$1", $autosave)));
                $out .= '<input type=hidden id="qn' . $qn . '-autosave" value="1"/>';
            } else {
                $out .= _('No file chosen');
            }
            $out .= '</span></label>';
        }
        $hasPrevSubmittedFile = false;
        if ($la != '') {
            if (!empty($assessmentId)) {
                $s3asid = $assessmentId;
            }

            if (!empty($s3asid)) {
                require_once dirname(__FILE__) . "/../../../includes/filehandler.php";

                if (substr($la, 0, 5) == "Error") {
                    $out .= "<br/>$la";
                } else {
                    $file = preg_replace('/@FILE:(.+?)@/', "$1", $la);
                    $url = getasidfileurl($file);
                    $extension = substr($url, strrpos($url, '.') + 1, 3);
                    $filename = basename($file);
                    $out .= "<br/><span class=\"lastfilesub\">" . _('Last file submitted:') . " <a href=\"$url\" target=\"_blank\" class=\"attach\">$filename</a></span>";
                    $out .= "<input type=\"hidden\" name=\"lf$qn\" value=\"$file\" />";
                    /*if (in_array(strtolower($extension),array('jpg','gif','png','bmp','jpe'))) {
                    $out .= " <span aria-expanded=\"false\" aria-controls=\"img$qn\" class=\"pointer clickable\" id=\"filetog$qn\" onclick=\"toggleinlinebtn('img$qn','filetog$qn');\">[+]</span>";
                    $out .= " <br/><div><img id=\"img$qn\" style=\"display:none;max-width:80%;\" aria-hidden=\"true\" onclick=\"rotateimg(this)\" src=\"$url\" alt=\"Student uploaded image\"/></div>";
                    } */
                    $hasPrevSubmittedFile = true;
                }
            } else {
                $out .= "<br/>$la";
            }
        } else if ($GLOBALS['useeditor'] === 'review') {
            $out .= _('No file submitted');
        }
        $tip .= _('Select a file to upload');
        if ($scoremethod != 'filesize') {
            $sa .= $answer;
        }

        if ($scoremethod == 'takeanythingorblank' && !$hasPrevSubmittedFile) {
            $params['submitblank'] = 1;
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
