<?php
namespace app\components;
use Yii;
use yii\base\Component;
require_once("diff.php");
require_once("JSON.php");
require_once("../filter/filter.php");

class WikiUtility extends Component
{

    public static function getWikiRevision()
    {
        $overwriteBody = 0;
        $body = "";
        $responseBody = " ";

        if (1 == 0) {
            $overwriteBody = 1;
            $body = "Error - need course id";
        } else if (7 == 0) {
            $overwriteBody = 1;
            $body = "Error - need wiki id";
        } else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
            $connection = Yii::$app->getDb();
            $query = "SELECT name,startdate,enddate,editbydate,avail FROM imas_wikis WHERE id= 7";
            $qdata = $connection->createCommand($query)->queryOne();

            $wikiname = $qdata['name'];
            $now = time();
            if ($qdata['avail'] == 0 || ($qdata['avail'] == 1 && ($now < $qdata['startdate'] || $now > $qdata['enddate']))) {
                $overwriteBody = 1;
                $body = "Error - not available for viewing";
            } else {
                require_once("../filter/filter.php");

                if ($now < $qdata['editbydate']) {
                    $canedit = true;
                } else {
                    $canedit = false;
                }
                $connection = Yii::$app->getDb();
                $query = "SELECT i_w_r.id as revision_id,i_w_r.revision,i_w_r.time,i_u.LastName,i_u.FirstName,i_u.id as user_id FROM ";
                $query .= "imas_wiki_revisions as i_w_r JOIN imas_users as i_u ON i_u.id=i_w_r.userid ";
                $query .= "WHERE i_w_r.wikiid= 7 AND i_w_r.stugroupid= 0 ORDER BY i_w_r.id DESC";
                $allRevisions = $connection->createCommand($query)->queryAll();
                $numrevisions = array();
                $revisions = $allRevisions[0];
                foreach ($revisions as $key => $revision) {
                    $numrevisions[] = $revision;
                }
                $countRevision = count($numrevisions);

                $text = $numrevisions[1];

                if (strlen($text) > 6 && substr($text, 0, 6) == '**wver') {
                    $wikiver = substr($text, 6, strpos($text, '**', 6) - 6);
                    $text = substr($text, strpos($text, '**', 6) + 2);
                } else {
                    $wikiver = 1;
                }
                $lastedittime = AppUtility::tzdate("F j, Y, g:i a", $numrevisions[2]);
                $lasteditedby = $numrevisions[3] . ', ' . $numrevisions[4];
                $revisionusers = array();
                $revisionusers[$numrevisions[5]] = $numrevisions[3] . ', ' . $numrevisions[4];
                $revisionhistory = array(array('u' => $numrevisions[5], 't' => $lastedittime, 'id' => $numrevisions[0]));

                if ($countRevision > 1) {
                    $revisions = $allRevisions;
                    $i = 0;
                    foreach ($revisions as $key => $revision) {
                        if ($i == 0) {
                            $i++;
                            continue;
                        }
                        $numrevisions = array();
                        foreach ($revision as $revisionOne) {
                            $numrevisions[] = $revisionOne;
                        }

                        $revisionusers[$numrevisions[5]] = $numrevisions[3] . ', ' . $numrevisions[4];

                        if (function_exists('json_encode')) {
                            $numrevisions[1] = json_decode($numrevisions[1]);

                        } else {
                            require_once("../components/JSON.php");
                            $jsonser = new \Services_JSON();
                            $numrevisions[1] = $jsonser->decode($numrevisions[1]);
                        }
                        $revisionhistory[] = array('u' => $numrevisions[5], 'c' => $numrevisions[1], 't' => AppUtility::tzdate("F j, Y, g:i a", $numrevisions[2]), 'id' => $numrevisions[0]);
                        $i++;
                    }

                    $keys = array_keys($revisionusers);
                    $i = 0;
                    $users = array();
                    foreach ($keys as $uid) {

                        $users[$uid] = $revisionusers[$uid];
                        $i++;
                    }

                } else {
                    $users = array();
                    $revisionhistory = array();
                }
                $text = diffstringsplit($text);
                foreach ($text as $k => $v) {
                    $text[$k] = filter($v);
                }
            }
        }

        if ($overwriteBody == 1) {
            $responseBody =  $body;
        } else { // general JSON
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