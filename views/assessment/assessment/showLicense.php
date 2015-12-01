<?php

use app\components\AppUtility;
use app\components\AppConstant;

$this->title = AppUtility::t('Show License', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<input type="hidden" class="" value="<?php echo $courseId ?>">
<!--<div class="item-detail-header">-->
<!--    --><?php //echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course['name']], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id], 'page_title' => $this->title]); ?>
<!--</div>-->
<div class="title-container padding-bottom-two-em">
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="col-md-12 col-sm-12" style="right: 30px;">
                <div class="vertical-align title-page">Show License</div>
            </div>
            <div class="col-sm-6">
                <div class="col-sm-2 pull-right">
                </div>
            </div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox col-md-12 col-sm-12 show-license-shadowbox">
<?php



foreach ($licenseData as $row) {
    echo "<div class='col-md-12 col-sm-12 show-license-question'><p>Question ID ".$row['id'].' (Universal ID '.$row['uniqueid'].')</p>';
    echo \app\components\AssessmentUtility::getquestionlicense($row);
    echo '</div>';
}

?>
</div>