<?php
namespace app\components;

use \yii\base\Component;
use Yii;
use app\models\Questions;
class CategoryScoresUtility extends Component
{

    public static function catscores($quests, $scores, $defptsposs, $defoutcome = AppConstant::NUMERIC_ZERO, $cid)
    {
        $qlist = "'" . implode("','", $quests) . "'";

        $result = Questions::getIdCatPoints($quests);
        $cat = array();
        $pospts = array();
        $tolookup = array($defoutcome);
        foreach ($result as $row) {
            if (is_numeric($row['category']) && $row['category'] == AppConstant::NUMERIC_ZERO && $defoutcome != AppConstant::NUMERIC_ZERO) {
                $cat[$row['id']] = $defoutcome;
            } else {
                $cat[$row['id']] = $row['category'];
            }

            if (is_numeric($row['category']) && $row['category'] > AppConstant::NUMERIC_ZERO) {
                $tolookup[] = $row['category'];
            }
            if ($row['points'] == AppConstant::QUARTER_NINE) {
                $pospts[$row['id']] = $defptsposs;
            } else {
                $pospts[$row['id']] = $row['points'];
            }
        }

        $outcomenames = array();
        $outcomenames[0] = "Uncategorized";
        if (count($tolookup) > AppConstant::NUMERIC_ZERO) {
            $lulist = "'" . implode("','", $tolookup) . "'";

            $connection = Yii::$app->getDb();
            $query = "SELECT id,name FROM imas_outcomes WHERE id IN ($lulist)";
            $result = $connection->createCommand($query)->queryAll();
            foreach ($result as $row) {
                $outcomenames[$row[0]] = $row[1];
            }
            $connection = Yii::$app->getDb();
            $query = "SELECT outcomes FROM imas_courses WHERE id='$cid'";
            $result = $connection->createCommand($query)->queryOne();
            if ($result['outcomes'] == '') {
                $outcomes = array();
            } else {
                $outcomes = unserialize($result['outcomes']);
            }
        }

        $catscore = array();
        $catposs = array();
        for ($i = AppConstant::NUMERIC_ZERO; $i < count($quests); $i++) {
            $pts = getpts($scores[$i]);
            if ($pts < AppConstant::NUMERIC_ZERO) {
                $pts = AppConstant::NUMERIC_ZERO;
            }
            $catscore[$cat[$quests[$i]]] += $pts;
            $catposs[$cat[$quests[$i]]] += $pospts[$quests[$i]];
        }
        echo "<h4>", _('Categorized Score Breakdown'), "</h4>\n";
        echo "<table cellpadding=5 class=gb><thead><tr><th>", _('Category'), "</th><th>", _('Points Earned / Possible (Percent)'), "</th></tr></thead><tbody>\n";
        $alt = AppConstant::NUMERIC_ZERO;
        function printoutcomes($arr, $ind, &$outcomenames, &$catscore, &$catposs)
        {
            $out = '';
            foreach ($arr as $oi) {
                if (is_array($oi)) {
                    $outc = printoutcomes($oi['outcomes'], $ind + AppConstant::NUMERIC_ONE, $outcomenames, $catscore, $catposs);
                    if ($outc != '') {
                        $out .= '<tr><td colspan="2"><span class="ind' . $ind . '"><b>' . $oi['name'] . '</b></span></td></tr>';
                        $out .= $outc;
                    }
                } else {
                    if (isset($catscore[$oi])) {
                        $out .= '<tr><td><span class="ind' . $ind . '">' . $outcomenames[$oi] . '</span></td>';
                        $pc = round(AppConstant::NUMERIC_HUNDREAD * $catscore[$oi] / $catposs[$oi], AppConstant::NUMERIC_ONE);
                        $out .= "<td>{$catscore[$oi]} / {$catposs[$oi]} ($pc %)</td></tr>\n";
                    }
                }
            }
            return $out;
        }

        if (count($tolookup) > AppConstant::NUMERIC_ZERO) {
            $outc = preg_split('/<tr/', printoutcomes($outcomes, AppConstant::NUMERIC_ZERO, $outcomenames, $catscore, $catposs));
            for ($i = AppConstant::NUMERIC_ONE; $i < count($outc); $i++) {
                if ($alt == AppConstant::NUMERIC_ZERO) {
                    echo '<tr class="even"';
                    $alt = AppConstant::NUMERIC_ONE;
                } else {
                    echo '<tr class="odd"';
                    $alt = AppConstant::NUMERIC_ZERO;
                }
                echo $outc[$i];
            }
        }
        foreach (array_keys($catscore) as $category) {
            if (is_numeric($category)) {
                continue;
            } else {
                $catname = $category;
            }
            if ($alt == AppConstant::NUMERIC_ZERO) {
                echo "<tr class=even>";
                $alt = AppConstant::NUMERIC_ONE;
            } else {
                echo "<tr class=odd>";
                $alt = AppConstant::NUMERIC_ZERO;
            }
            $pc = round(AppConstant::NUMERIC_HUNDREAD * $catscore[$category] / $catposs[$category], AppConstant::NUMERIC_ONE);
            echo "<td>$catname</td><td>{$catscore[$category]} / $catposs[$category] ($pc %)</td></tr>\n";
        }
        echo "</tbody></table>\n";

    }


}

?>