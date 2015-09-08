<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\RegisterModel */

$this->title = 'Categorize Questions';
$this->params['breadcrumbs'][] = $this->title;
//echo '<div id="headercategorize" class="pagetitle"><h2>Categorize Questions</h2></div>';
?>
 <div class="item-detail-header">
     <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Add/Remove Questions'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id,AppUtility::getHomeURL() . 'question/question/add-questions?aid='.$aid.'&cid='.$cid]]); ?>
<!--        --><?php //echo $this->render("../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index']]); ?>
 </div>
 <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
        </div>
 </div>
   <div class="col-md-12"> <input type="button" class="reset-btn secondarybtn" value="Reset" onclick="resetcat()"/></div>
<?php
    echo '<div class="tab-content shadowBox non-nav-tab-item">';
    echo "<form class='margin-thirty categorize-question-form' method=post action=\"categorize?aid=$aid&cid=$cid&record=true\">";

    echo 'Check: <a href="#" onclick="$(\'input[type=checkbox]\').prop(\'checked\',true);return false;">All</a> ';
    echo '<a href="#" onclick="$(\'input[type=checkbox]\').prop(\'checked\',false);return false;">None</a>';
    echo '<table class="width-hundread-per"><thead><tr><th class="text-align-left"><input type="checkbox" name="" value=""></th><th class="text-align-left">Description</th><th class="text-align-left">Category</th></tr></thead><tbody>';
    $alt = AppConstant::NUMERIC_ZERO;
    foreach($itemarr as $qid) {
        if ($alt == AppConstant::NUMERIC_ZERO) {
        echo "<tr class='even'><td><input type=\"checkbox\" id=\"c$qid\"/></td>";
            $alt = AppConstant::NUMERIC_ONE;
        }else{
            echo "<tr class='odd'><td><input type=\"checkbox\" id=\"c$qid\"/></td>";
            $alt = AppConstant::NUMERIC_ZERO;
        }


        echo "<td>{$descriptions[$qid]}</td><td>";
        echo "<select id=\"$qid\" name=\"$qid\" class=\"form-control width-thirty-per qsel\">";
        echo "<option value=\"0\" ";
        if ($category[$qid] == 0) {
            echo "selected=1";
        }
        echo ">Uncategorized or Default</option>\n";
        if (count($outcomes)>0) {
            echo '<optgroup label="Outcomes"></optgroup>';
        }
        $ingrp = false;
        $issel = false;
        if($outcomes){
            foreach ($outcomes as $oc) {
                if ($oc[1]==1) {//is group
                    if ($ingrp) {
                        echo '</optgroup>';
                    }
                    echo '<optgroup label="'.htmlentities($oc[0]).'">';
                    $ingrp = true;
                } else {
                    echo '<option value="'.$oc[0].'" ';
                    if ($category[$qid] == $oc[0]) {
                        echo "selected=1"; $issel = true;
                    }
                    echo '>'.$outcomenames[$oc[0]].'</option>';
                }
            }
        }

        if ($ingrp) {
            echo '</optgroup>';
        }
        echo '<optgroup label="Libraries">';
        if($questionlibs[$qid]){
            foreach ($questionlibs[$qid] as $qlibid) {
                echo "<option value=\"{$libnames[$qlibid]}\" ";
                if ($category[$qid] == $libnames[$qlibid] && !$issel) {
                    echo "selected=1"; $issel= true;
                }
                echo ">{$libnames[$qlibid]}</option>\n";
            }
        }
        echo '</optgroup><optgroup label="Custom">';
        foreach ($extracats as $cat) {
            echo "<option value=\"$cat\" ";
            if ($category[$qid] == $cat && !$issel) {
                echo "selected=1";$issel = true;
            }
            echo ">$cat</option>\n";
        }
        echo '</optgroup>';
        echo "</select></td></tr>\n";
    }
    echo "</tbody></table>\n";
    if (count($outcomes)>0) {
        echo '<p>Apply outcome to selected: <select id="masssel">';
        $ingrp = false;
        $issel = false;
        foreach ($outcomes as $oc) {
            if ($oc[1]==1) {//is group
                if ($ingrp) { echo '</optgroup>';}
                echo '<optgroup label="'.htmlentities($oc[0]).'">';
                $ingrp = true;
            } else {
                echo '<option value="'.$oc[0].'">'.$outcomenames[$oc[0]].'</option>';
            }
        }
        if ($ingrp) {
            echo '</optgroup>';
        }
        echo '</select> <input type="button" value="Assign" onclick="massassign()"/></p>';
    }
    echo "<p>Select first listed library for all uncategorized questions: <input type=button value=\"Quick Pick\" onclick=\"quickpick()\"></p>\n";
    echo "<p>Add new category to lists: <input type=type id=\"newcat\" size=40> ";
    echo "<input type=button value=\"Add Category\" onclick=\"addcategory()\"></p>\n";
    echo '<p><input type=submit value="Record Categorizations"> and return to the course page.</p>';
    echo "</form>\n";
echo"</div>";
?>