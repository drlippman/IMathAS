<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AssessmentUtility;

$this->title = 'Manage Offline Grades';
?>
<div class="item-detail-header" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, 'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<form method=post action="manage-offline-grades?cid=<?php echo $course->id ?>">
<div class="title-container">
    <div class="row margin-bottom-ten">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?> </div>
        </div>
    </div>
</div>
    <div class="item-detail-content">
        <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'gradebook']); ?>
    </div>
    <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
    <?php
    if($gradeNames){    ?>
    <div class="tab-content shadowBox ">
    <div class="offline-grade-header">
        <a class="margin-left-thirty" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/upload-multiple-grades?cid='.$course->id); ?>"><?php AppUtility::t('Upload multiple offline grades');?></a>
    </div>
<div class="col-sm-12 col-md-12 offline-grade-padding">
    <table class="grade-name-table width-fourty-per">
        <thead>
        <tr>
            <th>
                <div class="checkbox pull-left override-hidden">
                    <label class="add-grade-name-left-padding">
                        <input type="checkbox" name="header-checked" value="">
                        <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                    </label>
                </div>
            </th>
            <th class="col-sm-8 col-md-8"><?php AppUtility::t('Grade Names')?></th>
        </tr>
        </thead>
        <tbody class="grade-name-table-body">
            <?php foreach ($gradeNames as $singlegradeNames) { ?>
                <tr>
                    <td>
                        <span class="col-sm-12 col-md-12">
                            <input type='checkbox' id='Checkbox' name='grade-name-check[<?php echo $singlegradeNames['id'] ?>]' value="<?php echo $singlegradeNames['id'] ?>">
                        </span>
                    </td>
                    <td>
                        <span class="col-sm-12 col-md-12">
                            <?php echo $singlegradeNames['name'] ?>
                        <span class="col-sm-12 col-md-12">
                    </td>
                </tr>
            <?php } ?>
        <tbody>
    </table>
    <div class="padding-left-zero col-sm-12 col-md-12 margin-top-twenty">
        <span><?php AppUtility::t('With selected')?> ,</span>
        <input type="button" class="margin-left-ten btn btn-primary" id="mark-delete" value="Delete">
        <span class="margin-left-ten"><?php AppUtility::t('or make changes below')?></span>
    </div>
    <div class="col-sm-12 col-md-12 padding-left-zero margin-top-ten">
        <h4><strong><?php AppUtility::t('Offline Grade Options')?></strong></h4>
    </div>
    <table class="col-sm-12 col-md-12 margin-top-ten grade-option-table table table-bordered table-striped data-table">
        <thead>
            <tr>
                <th>
                    <div class="checkbox pull-left override-hidden">
                        <label class="add-grade-name-left-padding">
                            <input type="checkbox" name="header-check-box" value="">
                            <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                        </label>
                    </div>
                </th>
                <th class="col-sm-2 col-md-2 padding-left-twenty-five"><?php AppUtility::t('Option')?></th>
                <th class="col-sm-8 col-md-8 padding-left-twenty"><?php AppUtility::t('Settings')?></th>
            </tr>
        </thead>
        <tbody class="grade-option-table-name">
                 <tr>
                    <td>
                        <span class="col-sm-12 col-md-12">
                            <input type='checkbox' id='Checkbox' value="1" name='Show-after-check'>
                        </span>
                    </td>
                    <td>
                        <span class="col-sm-12 col-md-12">
                        <?php AppUtility::t('Show after')?>
                        </span>
                    </td>
                    <td>
                        <div class="col-sm-12 col-md-12 padding-left-zero" id="always-replies-radio-list">
                            <span class="col-sm-12 col-md-12 padding-left-zero margin-left-ten padding-bottom-ten padding-top-five">
                                <input type="radio" name="Show-after" value="1">
                                <span class="margin-left-five">
                                    <?php AppUtility::t('Always')?>
                                </span>
                            </span>
                            <span class="col-sm-12 col-md-12 padding-left-zero margin-left-ten">
                                <input type="radio" name="Show-after" class="end pull-left select-text-margin" checked id="always" value="2">
                                <div class="col-md-4 col-sm-6 floatleft padding-left-zero margin-left-ten show-after-date-picker padding-bottom-ten" id="datepicker-id">
                                   <?php
                                   echo DatePicker::widget([
                                       'name' => 'endDate',
                                       'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                       'value' => date("m/d/Y"),
                                       'pluginOptions' => [
                                           'autoclose' => true,
                                           'format' => 'mm/dd/yyyy']
                                   ]);
                                   echo '</div>'; ?>
                                    <label class="end pull-left  select-text-margin"><?php AppUtility::t('At')?></label>
                                    <?php echo '<div class="pull-left col-md-3 col-sm-4 show-after-time-picker">';
                                   echo TimePicker::widget([
                                       'name' => 'startTime',
                                       'options' => ['placeholder' => 'Select operating time ...'],
                                       'convertFormat' => true,
                                       'value' => time("g:i A"),
                                       'pluginOptions' => [
                                           'format' => "g:i A",
                                           'todayHighlight' => true,
                                       ]
                                   ]);?>
                               </div>
                            </span>
                        </div>
                    </td>
                 </tr>
                 <tr>
                     <td>
                        <span class="col-sm-12 col-md-12">
                         <input type='checkbox' id='Checkbox' value="1" name='count-check'>

                         </span>
                     </td>
                     <td>
                         <span class="col-sm-12 col-md-12">
                            <?php AppUtility::t('Count')?>
                         </span>
                     </td>
                     <td>
                         <div class="col-sm-12 col-md-12 padding-left-zero" id="always-replies-radio-list">
                             <span class="col-sm-12 col-md-12 padding-left-zero margin-left-ten padding-top-ten">
                                 <input type="radio" name="count" checked value="1">
                                 <span class="margin-left-five"><?php AppUtility::t('Count in Gradebook')?></span>
                             </span>
                             <span class="col-sm-12 col-md-12 padding-left-zero margin-left-ten margin-top-ten">
                                <input type="radio" name="count" value="2">
                                 <span class="margin-left-five"><?php AppUtility::t("Don't count in grade total and hide from students")?></span>
                             </span>
                             <span class="col-sm-12 col-md-12 padding-left-zero margin-left-ten margin-top-ten">
                                <input type="radio" name="count" value="3">
                                 <span class="margin-left-five"><?php AppUtility::t("Don't count in grade total")?></span>
                             </span>
                             <span class="col-sm-12 col-md-12 padding-left-zero margin-left-ten margin-top-ten padding-bottom-ten">
                                 <input type="radio" name="count" value="4">
                                 <span class="margin-left-five"><?php AppUtility::t('Count as Extra Credit')?></span>
                             </span>
                         </div>
                     </td>
                 </tr>
                 <tr>
                    <td>
                         <span class="col-sm-12 col-md-12">
                                <input type='checkbox' id='Checkbox' value="1" name='gradebook-category-check'>
                         </span>
                    </td>
                    <td>
                        <span class="col-sm-12 col-md-12">
                                <?php AppUtility::t('Gradebook category')?>
                         </span>
                    </td>
                    <td>
                        <div class="col-sm-12 col-md-12">
                        <span class="col-sm-6 padding-left-zero margin-left-minus-five padding-top-bottom-twenty">
                            <?php AssessmentUtility::writeHtmlSelect("gbcat",$gbcatsId,$gbcatsLabel,AppConstant::NUMERIC_ZERO,"Default",0); ?>
                        </span>
                        </div>
                    </td>
                 </tr>
                 <tr>
                    <td>
                         <span class="col-sm-12 col-md-12">
                            <input type='checkbox' value="1" id='Checkbox' name='tutor-access'>
                         </span>
                    </td>
                    <td>
                         <span class="col-sm-12 col-md-12">
                            <?php AppUtility::t('Tutor Access')?>
                         </span>
                    </td>
                    <td>
                        <div class="col-sm-12 col-md-12 padding-top-bottom-twenty">
                            <?php $page_tutorSelect['label'] = array("No access to scores","View Scores","View and Edit Scores");
                            $page_tutorSelect['val'] = array(2,0,1); ?>
                            <div class="col-sm-6 col-md-6 padding-left-zero margin-left-minus-five">
                                <?php AssessmentUtility::writeHtmlSelect("tutoredit",$page_tutorSelect['val'],$page_tutorSelect['label'],AppConstant::NUMERIC_ONE); ?>
                            </div>
                        </div>
                    </td>
                 </tr>
        </tbody>
    </table>
    <div class="header-btn col-sm-6 col-md-6 padding-left-right-zero padding-top-ten padding-bottom-five">
            <?= Html::submitButton('Save', ['class' => 'btn btn-primary offline-grade-save-btn']) ?>
            <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid=' . $course->id) ?>">
                <i class="fa fa-share header-right-btn"></i> Back
            </a>
    </div>
</div>
</div>
  <?php  }else{ ?>
    <div class="tab-content shadowBox ">
        <div class="offline-grade-header">
           <div class="add-grade-name-left-margin"> <?php AppUtility::t('No offline grades')?>.
            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-grades?cid='.$course->id.'&gbitem=new&grades=all'); ?>"> <?php AppUtility::t('Add one')?> </a> <?php AppUtility::t('or')?>
            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/upload-multiple-grades?cid='.$course->id); ?>">
            <?php AppUtility::t('Upload multiple offline grades')?>
            </a>
               </div>
        </div>
    </div>
   <?php } ?>
</form>

