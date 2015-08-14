<?php
namespace app\components;
use \yii\base\Component;
use Yii;
use app\components\AppUtility;
use app\components\AppConstant;

class CategoryScoresUtility extends Component
{

//IMathAS:  Function used to show category breakdown of scores
//Called from showtest and gradebook
//(c) 2006 David Lippman
public static function catscores($quests,$scores,$defptsposs,$defoutcome=0,$cid) {
    $qlist = "'" . implode("','",$quests) . "'";

    $connection = Yii::$app->getDb();
    $query = "SELECT id,category,points FROM imas_questions WHERE id IN ($qlist)";
    $result = $connection->createCommand($query)->queryAll();
    $cat = array();
    $pospts = array();
    $tolookup = array($defoutcome);
    foreach($result as $row){
        if (is_numeric($row[1]) && $row[1]==0 && $defoutcome!=0) {
            $cat[$row[0]] = $defoutcome;
        } else {
            $cat[$row[0]] = $row[1];
        }

        if (is_numeric($row[1]) && $row[1]>0) {
            $tolookup[] = $row[1];
        }
        if ($row[2] == 9999) {
            $pospts[$row[0]] = $defptsposs;
        } else {
            $pospts[$row[0]] = $row[2];
        }
    }

    $outcomenames = array();
    $outcomenames[0] = "Uncategorized";
    if (count($tolookup)>0) {
        $lulist = "'".implode("','",$tolookup)."'";

        $connection = Yii::$app->getDb();
        $query = "SELECT id,name FROM imas_outcomes WHERE id IN ($lulist)";
        $result = $connection->createCommand($query)->queryAll();
        foreach($result as $row){
            $outcomenames[$row[0]] = $row[1];
        }
        $connection = Yii::$app->getDb();
        $query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
        $result = $connection->createCommand($query)->queryOne();
        if ($result['outcomes']=='') {
            $outcomes = array();
        } else {
            $outcomes = unserialize($result['outcomes']);
        }
    }

    $catscore = array();
    $catposs = array();
    for ($i=0; $i<count($quests); $i++) {
        $pts = getpts($scores[$i]);
        if ($pts<0) { $pts = 0;}
        $catscore[$cat[$quests[$i]]] += $pts;
        $catposs[$cat[$quests[$i]]] += $pospts[$quests[$i]];
    }
    echo "<h4>", _('Categorized Score Breakdown'), "</h4>\n";
    echo "<table cellpadding=5 class=gb><thead><tr><th>", _('Category'), "</th><th>", _('Points Earned / Possible (Percent)'), "</th></tr></thead><tbody>\n";
    $alt = 0;
    function printoutcomes($arr,$ind,&$outcomenames, &$catscore, &$catposs) {
        $out = '';
        foreach ($arr as $oi) {
            if (is_array($oi)) {
                $outc = printoutcomes($oi['outcomes'],$ind+1,$outcomenames,$catscore, $catposs);
                if ($outc!='') {
                    $out .= '<tr><td colspan="2"><span class="ind'.$ind.'"><b>'.$oi['name'].'</b></span></td></tr>';
                    $out .= $outc;
                }
            } else {
                if (isset($catscore[$oi])) {
                    $out .= '<tr><td><span class="ind'.$ind.'">'.$outcomenames[$oi].'</span></td>';
                    $pc = round(100*$catscore[$oi]/$catposs[$oi],1);
                    $out .= "<td>{$catscore[$oi]} / {$catposs[$oi]} ($pc %)</td></tr>\n";
                }
            }
        }
        return $out;
    }
    if (count($tolookup)>0) {
        $outc = preg_split('/<tr/',printoutcomes($outcomes, 0, $outcomenames, $catscore, $catposs));
        for ($i=1;$i<count($outc);$i++) {
            if ($alt==0) {echo '<tr class="even"'; $alt=1;} else {echo '<tr class="odd"'; $alt=0;}
            echo $outc[$i];
        }
    }
    foreach (array_keys($catscore) as $category) {
        if (is_numeric($category)) {
            continue;
        } else {
            $catname = $category;
        }
        if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
        $pc = round(100*$catscore[$category]/$catposs[$category],1);
        echo "<td>$catname</td><td>{$catscore[$category]} / $catposs[$category] ($pc %)</td></tr>\n";
    }
    echo "</tbody></table>\n";

}




}
?>