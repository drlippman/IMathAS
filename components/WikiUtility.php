<?php
namespace app\components;

use app\models\Wiki;
use app\models\WikiRevision;
use Yii;
use yii\base\Component;

require_once("diff.php");
require_once("JSON.php");
require_once("../filter/filter.php");

class WikiUtility extends Component
{
    public static function getWikiRevision($courseId, $wikiId)
    {
        $stuGroupId = AppConstant::NUMERIC_ZERO;
        $overWriteBody = AppConstant::NUMERIC_ZERO;
        $body = "";
        if ($courseId == AppConstant::NUMERIC_ZERO) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
            $body = "Error - need course id";
        } else if ($wikiId == AppConstant::NUMERIC_ZERO) {
            $overWriteBody = AppConstant::NUMERIC_ONE;
            $body = "Error - need wiki id";
        } else {
            $qData = Wiki::getAllDataWiki($wikiId);
            $now = time();
            if ($qData['avail'] == AppConstant::NUMERIC_ZERO || ($qData['avail'] == AppConstant::NUMERIC_ONE && ($now < $qData['startdate'] || $now > $qData['enddate']))) {
                $overWriteBody = AppConstant::NUMERIC_ONE;
                $body = "Error - not available for viewing";
            } else {
                $allRevisions = WikiRevision::getRevisionTotalData($wikiId, $stuGroupId);
                $numrevisions = array();
                $revisions = $allRevisions[AppConstant::NUMERIC_ZERO];
                foreach ($revisions as $key => $revision) {
                    $numrevisions[] = $revision;
                }
                $countRevision = count($numrevisions);
                $text = $numrevisions[AppConstant::NUMERIC_ONE];
                if (strlen($text) > AppConstant::NUMERIC_SIX && substr($text, AppConstant::NUMERIC_ONE, AppConstant::NUMERIC_SIX) == '**wver') {
                    $text = substr($text, strpos($text, '**', AppConstant::NUMERIC_SIX) + AppConstant::NUMERIC_TWO);
                }
                $lastedittime = AppUtility::tzdate("F j, Y, g:i a", $numrevisions[AppConstant::NUMERIC_TWO]);
                $revisionusers = array();
                $revisionusers[$numrevisions[AppConstant::NUMERIC_FIVE]] = $numrevisions[AppConstant::NUMERIC_TWO] . ', ' . $numrevisions[AppConstant::NUMERIC_FOUR];
                $revisionhistory = array(array('u' => $numrevisions[AppConstant::NUMERIC_FIVE], 't' => $lastedittime, 'id' => $numrevisions[AppConstant::NUMERIC_ONE]));

                if ($countRevision > AppConstant::NUMERIC_ONE) {
                    $revisions = $allRevisions;
                    $i = AppConstant::NUMERIC_ZERO;
                    foreach ($revisions as $key => $revision) {
                        if ($i == AppConstant::NUMERIC_ZERO) {
                            $i++;
                            continue;
                        }
                        $numrevisions = array();
                        foreach ($revision as $revisionOne) {
                            $numrevisions[] = $revisionOne;
                        }
                        $revisionusers[$numrevisions[AppConstant::NUMERIC_FIVE]] = $numrevisions[AppConstant::NUMERIC_THREE] . ', ' . $numrevisions[AppConstant::NUMERIC_FOUR];
                        if (function_exists('json_encode')) {
                            $numrevisions[AppConstant::NUMERIC_ONE] = json_decode($numrevisions[AppConstant::NUMERIC_ONE]);

                        } else {
                            $jsonser = new \Services_JSON();
                            $numrevisions[AppConstant::NUMERIC_ONE] = $jsonser->decode($numrevisions[AppConstant::NUMERIC_ONE]);
                        }
                        $revisionhistory[] = array('u' => $numrevisions[AppConstant::NUMERIC_FIVE], 'c' => $numrevisions[AppConstant::NUMERIC_ONE], 't' => AppUtility::tzdate("F j, Y, g:i a", $numrevisions[AppConstant::NUMERIC_TWO]), 'id' => $numrevisions[0]);
                        $i++;
                    }
                    $keys = array_keys($revisionusers);
                    $i = AppConstant::NUMERIC_ZERO;
                    $users = array();
                    foreach ($keys as $uid) {
                        $users[$uid] = $revisionusers[$uid];
                        $i++;
                    }
                } else {
                    $users = array();
                    $revisionhistory = array();
                }
                $text = diff::diffstringsplit($text);
                foreach ($text as $k => $v) {
                    $text[$k] = filter($v);
                }
            }
        }
        if ($overWriteBody == AppConstant::NUMERIC_ONE) {
            $responseBody = $body;
        } else {
            $out = array('o' => $text, 'h' => $revisionhistory, 'u' => $users);
            if (function_exists('json_encode')) {
                $responseBody = json_encode($out);
            } else {
                $jsonser = new \Services_JSON();
                $responseBody = $jsonser->encode($out);
            }
        }
        return strip_tags($responseBody);
    }
}