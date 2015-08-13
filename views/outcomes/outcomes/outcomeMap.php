<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Outcome Map';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Outcomes:',false);?><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content"></div>
<div class="tab-content shadowBox">

    <div style="padding: 20px">
    <?php if ($outcomeLinks==0)
    {
        echo '<p>No items have been associated with outcomes yet</p>';
        exit;

    }else{?>
    <?php
        echo '<table class="table table-bordered table-striped table-hover data-table"><thead><tr><th>'._('Outcome').'</th><th>'._('Not Graded').'</th>';
    foreach ($catNames as $cn)
    {
        echo '<th>'.$cn.'</th>';
    }
    echo '</tr></thead><tbody>';
    $n = count($catNames)+2;
    $printItems = new AppUtility();
    $cnt = 0;
    $printItems->printOutcomesForMap($outcomes,AppConstant::NUMERIC_ZERO,$outcomeAssoc,$outcomeInfo,$catNames,$n,$cnt,$items,$assessNames,$forumNames,$offNames,$linkNames,$inlineNames);
    echo '</tbody></table>';
    echo '<p>'._('Key:  L: Links, I: Inline Text, A: Assessments, F: Forums, O: Offline Grades').'</p>';
    }?>
    </div>
</div>
