<?php
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;

$this->title = AppUtility::t("Shift Course Dates", false);
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid='.$course->id]]); ?>
</div>

<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course]); ?>
</div>

<div class="col-md-12">
    <br/>
</div>


<div class="tab-content shadowBox">
    <form method=post action="time-shift?cid=<?php echo $course->id ?>" >
    <div class="col-md-12">
        <div class="col-md-12">
            <?php echo "This page will change <b>ALL</b> course available dates and due dates based on changing one item. This is intended to allow you to reset all course item dates for a new term in one action.";?>
        </div>
    </div>

    <div class="col-md-12">
        <br/>
    </div>

<div class="col-md-12">
    <div class="col-md-4"> <?php echo 'Select an assessment to base the change on' ;?></div>
    <div class= 'formright col-md-4'>
			<?php AppUtility::writeHtmlSelect ("aid",$pageAssessmentList['val'],$pageAssessmentList['label'],null,null,null,$actions=" id=aid "); ?>
		</div>
</div>

    <div class="col-md-12">
        <br/>
    </div>

<div class="col-md-12">
    <div class="col-md-4"> <?php echo 'Change dates based on this assessment\'s' ;?></div>
        <div class="col-md-4">
            <div class="radio">
                <label><input type="radio" name="assessment">Available After date</label>
                <label><input type="radio" name="assessment" checked>Available Until date (Due date)</label>

            </div>
        </div>
    </div>

    <div class="col-md-12">
        <br/>
    </div>

    <div class="col-md-12">
        <div class="col-md-4"> <?php echo 'Change date to' ;?></div>
        <?php
        echo '<div class = "col-md-4  time-input">';
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

    <div class="col-md-12">
    <br/>
    </div>


    <div class="col-md-12">
        <div class="col-md-4"> </div>
        <div class="col-md-4">
            <input  class="btn btn-primary" type="submit" value="Change Dates">
        </div>
    </div>

        <div class="col-md-12">
            <div><br/></div>
        <div>
            </form>
</div>





