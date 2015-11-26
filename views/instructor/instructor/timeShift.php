<?php
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
$this->title = AppUtility::t("Shift Course Dates", false);
?>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid='.$course->id]]); ?>
</div>
<form method=post action="time-shift?cid=<?php echo $course->id ?>" >
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?> </div>
        </div>
    </div>
</div>

    <div class="tab-content shadowBox">
    <?php
if ($overWriteBody==1) {
    echo $body;
} else {
?>

    <div class="col-md-12 col-sm-12 padding-top-one-em padding-bottom-one-em">
            <?php AppUtility::t("This page will change <b>ALL</b> course available dates and due dates based on changing one item. This is intended to allow you to reset all course item dates for a new term in one action.");?>
    </div>


<div class="col-sm-12 col-md-12 padding-bottom-one-em">
    <div class="col-md-4 col-sm-6"> <?php AppUtility::t("Select an assessment to base the change on");?></div>
    <div class= 'col-md-4 col-sm-6'>
			<?php AppUtility::writeHtmlSelect ("aid",$pageAssessmentList['val'],$pageAssessmentList['label'],null,null,null,$actions=" id=aid "); ?>
		</div>
</div>


<div class="col-md-12 col-sm-12 padding-bottom-one-em">
    <div class="col-md-4 col-sm-6"> <?php AppUtility::t('Change dates based on this assessment\'s');?></div>
        <div class="col-md-4 col-sm-6">
            <div class="radio">
                <label><input type="radio" name="base" value="0"><?php AppUtility::t('Available After date');?></label>
                <label><input type="radio" name="base" value="1" checked=1><?php AppUtility::t('Available Until date (Due date)');?></label>

            </div>
        </div>
    </div>


    <div class="padding-bottom-one-em col-md-12 col-sm-12">
        <div class="col-md-4 col-sm-6"> <?php AppUtility::t('Change date to');?></div>
        <?php
        echo '<div class = "col-md-3  col-sm-6 time-input">';
        echo DatePicker::widget([
            'name' => 'sdate',
            'type' => DatePicker::TYPE_COMPONENT_APPEND,
            'value' => $date,
            'removeButton' => false,
            'pluginOptions' => [
                'autoclose' => true,
                'format' => 'mm/dd/yyyy']
        ]);
        echo '</div>'; ?>
    </div>

    <div class="col-sm-6 col-md-4 col-md-offset-4 header-btn col-sm-offset-6 padding-top-ten padding-left-twenty">
        <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo 'Change Dates' ?></button>
    </div>

<?php } ?>
</div>
</form>