<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use DOMDocument;
use ZipArchive;

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class FileScorePart implements ScorePart
{
    private $scoreQuestionParams;

    public function __construct(ScoreQuestionParams $scoreQuestionParams)
    {
        $this->scoreQuestionParams = $scoreQuestionParams;
    }

    public function getResult(): ScorePartResult
    {
        global $mathfuncs;

        $scorePartResult = new ScorePartResult();

        $RND = $this->scoreQuestionParams->getRandWrapper();
        $options = $this->scoreQuestionParams->getVarsForScorePart();
        $qn = $this->scoreQuestionParams->getQuestionNumber();
        $givenans = $this->scoreQuestionParams->getGivenAnswer();
        $multi = $this->scoreQuestionParams->getIsMultiPartQuestion();
        $partnum = $this->scoreQuestionParams->getQuestionPartNumber();
        $assessmentId = $this->scoreQuestionParams->getAssessmentId();

        $defaultreltol = .0015;

        $optionkeys = ['answer', 'answerformat', 'scoremethod'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        $filename = basename(str_replace('\\','/',$_FILES["qn$qn"]['name']));
        $filename = preg_replace('/[^\w\.]/','',$filename);
        $hasfile = false;
        require_once(dirname(__FILE__)."/../../../includes/filehandler.php");
        if (trim($filename)=='') {
            if (is_string($givenans) && substr($givenans,0,5) === '@FILE') { // has an autosaved file
                $scorePartResult->setLastAnswerAsGiven($givenans);
                if ($answerformat=='excel') {
                  // TODO if we want to resurrect this
                    /*$zip = new ZipArchive;
                    if ($zip->open(getasidfilepath($_POST["lf$qn"]))) {
                        $doc = new DOMDocument();
                        $doc->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
                        $zip->close();
                    } else {
                        $scorePartResult->addScoreMessage(_(' Unable to open Excel file'));
                        $scorePartResult->setRawScore(0);
                        return $scorePartResult;
                    }*/
                }
                $hasfile = true;
            } else {
                $scorePartResult->setLastAnswerAsGiven('');
                if (!empty($scoremethod) && $scoremethod=='takeanythingorblank') {
                    $scorePartResult->setRawScore(1);
                    return $scorePartResult;
                } else {
                    $scorePartResult->setRawScore(0);
                    return $scorePartResult;
                }
            }
        }
        if (!$hasfile) {
            $extension = strtolower(strrchr($filename,"."));
            $badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p",".exe");
            $scorePartResult->addScoreMessage(sprintf(_('Upload of %s: '), $filename));
            if (in_array($extension,$badextensions)) {
                $scorePartResult->setLastAnswerAsGiven(_('Error - Invalid file type'));
                $scorePartResult->addScoreMessage(_('Error - Invalid file type'));
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
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
                $scorePartResult->setLastAnswerAsGiven(_('Error - no asid'));
                $scorePartResult->addScoreMessage(_('Error - no asid'));
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
            if (is_numeric($s3asid) && $s3asid==0) {  //set in testquestion for preview
                $scorePartResult->setLastAnswerAsGiven(_('Error - File not uploaded in preview'));
                $scorePartResult->addScoreMessage(_('Error - File not uploaded in preview'));
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
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
                        $scorePartResult->addScoreMessage(_(' Unable to open Excel file'));
                        $scorePartResult->setRawScore(0);
                        return $scorePartResult;
                    }
                }

                $s3object = "adata/$s3asid/$filename";
                if (storeuploadedfile("qn$qn",$s3object)) {
                    $scorePartResult->setLastAnswerAsGiven("@FILE:$s3asid/$filename@");
                    $scorePartResult->addScoreMessage(_("Successful"));
                    $hasfile = true;
                } else {
                    //echo "Error storing file";
                    $scorePartResult->setLastAnswerAsGiven(_('Error storing file'));
                    $scorePartResult->addScoreMessage(_('Error storing file'));
                    $scorePartResult->setRawScore(0);
                    return $scorePartResult;
                }

            } else {
                //echo "Error uploading file";
                if ($_FILES["qn$qn"]['error']==2 || $_FILES["qn$qn"]['error']==1) {
                    $scorePartResult->setLastAnswerAsGiven(_('Error uploading file - file too big'));
                    $scorePartResult->addScoreMessage(_('Error uploading file - file too big'));
                } else {
                    $scorePartResult->setLastAnswerAsGiven(_('Error uploading file'));
                    $scorePartResult->addScoreMessage(_('Error uploading file'));
                }
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        }
        if (!empty($scoremethod) && ($scoremethod=='takeanything' || $scoremethod=='takeanythingorblank')) {
            $scorePartResult->setRawScore(1);
            return $scorePartResult;
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
                            $scorePartResult->addScoreMessage("Cell $cell incorrect");
                        }
                    } else {
                        if (trim($val)==trim($doccells[$cell])) {
                            $pts++;
                        }
                    }
                }
                $scorePartResult->setRawScore($pts/count($answer));
                return $scorePartResult;
            } else {
                $scorePartResult->setRequiresManualGrading(true);
                $scorePartResult->setRawScore(-2);
                return $scorePartResult;
            }
        }
    }
}
