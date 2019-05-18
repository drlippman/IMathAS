<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');

use DOMDocument;
use ZipArchive;

use IMathAS\assess2\questions\models\ScoreQuestionParams;

class FileScorePart implements ScorePart
{
    private $scoreQuestionParams;

    public function __construct(ScoreQuestionParams $scoreQuestionParams)
    {
        $this->scoreQuestionParams = $scoreQuestionParams;
    }

    public function getScore(): int
    {
        global $mathfuncs;

        $RND = $this->scoreQuestionParams->getRandWrapper();
        $options = $this->scoreQuestionParams->getVarsForScorePart();
        $qn = $this->scoreQuestionParams->getQuestionNumber();
        $givenans = $this->scoreQuestionParams->getGivenAnswer();
        $multi = $this->scoreQuestionParams->getIsMultiPartQuestion();
        $partnum = $this->scoreQuestionParams->getQuestionPartNumber();
        $assessmentId = $this->scoreQuestionParams->getAssessmentId();

        $defaultreltol = .0015;

        if (isset($options['scoremethod']))if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$partnum];} else {$scoremethod = $options['scoremethod'];}
        if (isset($options['answer'])) {if ($multi) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $filename = basename(str_replace('\\','/',$_FILES["qn$qn"]['name']));
        $filename = preg_replace('/[^\w\.]/','',$filename);
        $hasfile = false;
        require_once(dirname(__FILE__)."/../../../includes/filehandler.php");
        if (trim($filename)=='') {
            $found = false;
            if ($_POST["lf$qn"]!='') {
                if ($multi>0) {
                    if (strpos($GLOBALS['lastanswers'][$multi-1],'@FILE:'.$_POST["lf$qn"].'@')!==false) {
                        $found = true;
                    }
                } else {
                    if (strpos($GLOBALS['lastanswers'][$qn],'@FILE:'.$_POST["lf$qn"].'@')!==false) {
                        $found = true;
                    }
                }
            }
            if ($found) {
                $GLOBALS['partlastanswer'] = '@FILE:'.$_POST["lf$qn"].'@';
                if ($answerformat=='excel') {
                    $zip = new ZipArchive;
                    if ($zip->open(getasidfilepath($_POST["lf$qn"]))) {
                        $doc = new DOMDocument();
                        $doc->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
                        $zip->close();
                    } else {
                        $GLOBALS['scoremessages'] .= _(' Unable to open Excel file');
                        return 0;
                    }
                }
                $hasfile = true;
            } else {
                $GLOBALS['partlastanswer'] = '';
                if (isset($scoremethod) && $scoremethod=='takeanythingorblank') {
                    return 1;
                } else {
                    return 0;
                }
            }
        }
        if (!$hasfile) {
            $extension = strtolower(strrchr($filename,"."));
            $badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p",".exe");
            if ($GLOBALS['scoremessages'] != '') {
                $GLOBALS['scoremessages'] .= '<br/>';
            }
            $GLOBALS['scoremessages'] .= sprintf(_('Upload of %s: '), $filename);
            if (in_array($extension,$badextensions)) {
                $GLOBALS['partlastanswer'] = _('Error - Invalid file type');
                $GLOBALS['scoremessages'] .= _('Error - Invalid file type');
                return 0;
            }
            if (!empty($assessmentId)) { //going to use assessmentid/random
                $randstr = '';

                $chars = 'abcdefghijklmnopqrstuwvxyzABCDEFGHIJKLMNOPQRSTUWVXYZ0123456789';
                $m = microtime(true);
                $res = '';
                $in = floor($m)%1000000000;
                while ($in>0) {
                    $i = $in % 62;
                    $in = floor($in/62);
                    $randstr .= $chars[$i];
                }
                $in = floor(10000*($m-floor($m)));
                while ($in>0) {
                    $i = $in % 62;
                    $in = floor($in/62);
                    $randstr .= $chars[$i];
                }

                $s3asid = $assessmentId."/$randstr";

            } else {
                $GLOBALS['partlastanswer'] = _('Error - no asid');
                $GLOBALS['scoremessages'] .= _('Error - no asid');
                return 0;
            }
            if (is_numeric($s3asid) && $s3asid==0) {  //set in testquestion for preview
                $GLOBALS['partlastanswer'] = _('Error - File not uploaded in preview');
                $GLOBALS['scoremessages'] .= _('Error - File not uploaded in preview');
                return 0;
            }

            if (is_uploaded_file($_FILES["qn$qn"]['tmp_name'])) {
                if ($answerformat=='excel') {
                    $zip = new ZipArchive;
                    if ($zip->open($_FILES["qn$qn"]['tmp_name'])) {
                        echo "opened excel";
                        $doc = new DOMDocument();
                        if ($doc->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'))) {
                            echo "read into doc";
                        }

                        $zip->close();
                    } else {
                        $GLOBALS['scoremessages'] .= _(' Unable to open Excel file');
                        return 0;
                    }
                }

                $s3object = "adata/$s3asid/$filename";
                if (storeuploadedfile("qn$qn",$s3object)) {
                    $GLOBALS['partlastanswer'] = "@FILE:$s3asid/$filename@";
                    $GLOBALS['scoremessages'] .= _("Successful");
                    $hasfile = true;
                } else {
                    //echo "Error storing file";
                    $GLOBALS['partlastanswer'] = _('Error storing file');
                    $GLOBALS['scoremessages'] .= _('Error storing file');
                    return 0;
                }

            } else {
                //echo "Error uploading file";
                if ($_FILES["qn$qn"]['error']==2 || $_FILES["qn$qn"]['error']==1) {
                    $GLOBALS['partlastanswer'] = _('Error uploading file - file too big');
                    $GLOBALS['scoremessages'] .= _('Error uploading file - file too big');
                } else {
                    $GLOBALS['partlastanswer'] = _('Error uploading file');
                    $GLOBALS['scoremessages'] .= _('Error uploading file');
                }
                return 0;
            }
        }
        if (isset($scoremethod) && ($scoremethod=='takeanything' || $scoremethod=='takeanythingorblank')) {
            return 1;
        } else {
            if ($answerformat=='excel') {
                $doccells = array();
                $els = $doc->getElementsByTagName('c');
                foreach ($els as $el) {
                    $doccells[$el->getAttribute('r')] = $el->getElementsByTagName('v')->item(0)->nodeValue;
                }
                $pts = 0;

                foreach ($answer as $cell=>$val) {
                    if (!isset($doccells[$cell])) {continue;}
                    if (is_numeric($val)) {
                        if (abs($val-$doccells[$cell])<.01) {
                            $pts++;
                        } else {
                            $GLOBALS['scoremessages'] .= "<br/>Cell $cell incorrect";
                            echo "<br/>Cell $cell incorrect";
                        }
                    } else {
                        if (trim($val)==trim($doccells[$cell])) {
                            $pts++;
                        }
                    }
                }
                return $pts/count($answer);
            } else {
                $GLOBALS['questionmanualgrade'] = true;
                return -2;
            }
        }
    }
}
