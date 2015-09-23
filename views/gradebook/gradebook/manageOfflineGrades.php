<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AssessmentUtility;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */
$this->title = 'Manage Offline Grades';
?>
<div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, 'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>



<?php $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
        'action' => 'manage-offline-grades?cid=' . $course->id,
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-3 select-text-margin'],
        ],
    ]); ?>

<div class="title-container">
    <div class="row margin-bottom-ten">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?> </div>
        </div>
        <div class="pull-left header-btn">
            <div class="pull-right">
                <?= Html::submitButton('Save', ['class' => 'btn btn-primary offline-grade-save-btn']) ?>
                <a class="btn btn-primary" href="<?php echo AppUtility::getURLFromHome('gradebook/gradebook', 'gradebook?cid=' . $course->id) ?>">
                    <i class="fa fa-share header-right-btn"></i> Back
                </a>
            </div>

        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
    <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
    <?php

    $model->ShowGrade = AppUtility::getStringVal(AppConstant::NUMERIC_TWO);
    $model->Count = AppUtility::getStringVal(AppConstant::NUMERIC_ONE);
    $model->Gradetype = AppUtility::getStringVal(AppConstant::NUMERIC_ONE);

    if($gradeNames){    ?>

    <div class="offline-grade-header">
        <a class="margin-left-thirty" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/upload-multiple-grades?cid='.$course->id); ?>">Upload multiple offline grades</a>
    </div>

<div class="col-md-12 offline-grade-padding">
        <div>
            Check: <a name="check-all-box" class="check-all" href="#">All</a>
            <a name="uncheck-all-box" class="uncheck-all" href="#">None</a>
        </div>


    <table class="width-fourty-per">
        <thead>
        <tr>
            <th class="col-md-4">Check</th>
            <th class="col-md-8">Grade Names</th>
        </tr>
        </thead>
        <tbody class="grade-name-table-body">
            <?php foreach ($gradeNames as $singlegradeNames) { ?>
                <tr>
                    <td>
                        <span class="col-md-12">
                            <input type='checkbox' id='Checkbox' name='grade-name-check[<?php echo $singlegradeNames['id'] ?>]' value="<?php echo $singlegradeNames['id'] ?>">
                        </span>
                    </td>
                    <td>
                        <span class="col-md-12">
                            <?php echo $singlegradeNames['name'] ?>
                        <span class="col-md-12">
                    </td>
                </tr>
            <?php } ?>
        <tbody>
    </table>
    <div class="padding-left-zero col-md-12 margin-top-twenty">
        <span>With selected ,</span>
        <input type="button" class="margin-left-ten btn btn-primary" id="mark-delete" value="Delete">
        <span class="margin-left-ten">or make changes below</span>
    </div>
    <div class="col-md-12 padding-left-zero margin-top-ten">
        <h4><strong>Offline Grade Options</strong></h4>
    </div>
    <table class="col-md-12 margin-top-ten">
        <thead>
            <tr>
                <th class="col-md-2">Change?</th>
                <th class="col-md-2">Option</th>
                <th class="col-md-8">Settings</th>
            </tr>
        </thead>
        <tbody>
                 <tr>
                    <td>
                        <span class="col-md-12">
                            <input type='checkbox' id='Checkbox' value="1" name='Show-after-check'>
                        </span>
                    </td>
                    <td>
                        <span class="col-md-12">
                        Show after
                        </span>
                    </td>
                    <td>
                        <div class="col-md-12 padding-left-zero" id="always-replies-radio-list">
                            <span class="col-md-12 padding-left-zero margin-left-ten">
                                <input type="radio" name="Show-after" value="1">
                                <span class="margin-left-five">
                                    Always
                                </span>
                            </span>

                            <span class="col-md-12 padding-left-zero margin-left-ten margin-top-twenty">
                                <input type="radio" name="Show-after" class="end pull-left select-text-margin" checked id="always" value="2">
                                <div class="col-md-3 padding-left-zero margin-left-ten" id="datepicker-id">
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
                                   <?php
                                   echo '<label class="end pull-left  select-text-margin"> At</label>';
                                   echo '<div class="pull-left col-lg-4">';

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
                        <span class="col-md-12">
                         <input type='checkbox' id='Checkbox' value="1" name='count-check'>

                         </span>
                     </td>
                     <td>
                         <span class="col-md-12">
                            Count
                         </span>
                     </td>
                     <td>
                         <div class="col-md-12 padding-left-zero" id="always-replies-radio-list">
                             <span class="col-md-12 padding-left-zero margin-left-ten margin-top-ten">
                                 <input type="radio" name="count" checked value="1">
                                 <span class="margin-left-five">Count in Gradebook</span>
                             </span>
                             <span class="col-md-12 padding-left-zero margin-left-ten margin-top-ten">
                                <input type="radio" name="count" value="2">
                                 <span class="margin-left-five">Don't count in grade total and hide from students</span>
                             </span>
                             <span class="col-md-12 padding-left-zero margin-left-ten margin-top-ten">
                                <input type="radio" name="count" value="3">
                                 <span class="margin-left-five">Don't count in grade total</span>
                             </span>
                             <span class="col-md-12 padding-left-zero margin-left-ten margin-top-ten">
                                 <input type="radio" name="count" value="4">
                                 <span class="margin-left-five">Count as Extra Credit</span>
                             </span>
                         </div>
                     </td>
                 </tr>
                 <tr>
                    <td>
                         <span class="col-md-12">
                                <input type='checkbox' id='Checkbox' value="1" name='gradebook-category-check'>
                         </span>
                    </td>
                    <td>
                        <span class="col-md-12">
                                Gradebook category
                         </span>
                    </td>
                    <td>
                        <div class="col-md-12">
                        <span class="col-md-6 padding-left-zero margin-left-minus-five">
                            <?php AssessmentUtility::writeHtmlSelect("gbcat",$gbcatsId,$gbcatsLabel,AppConstant::NUMERIC_ZERO,"Default",0); ?>
                        </span>
                        </div>
                    </td>
                 </tr>
                 <tr>
                    <td>
                         <span class="col-md-12">
                            <input type='checkbox' value="1" id='Checkbox' name='tutor-access'>
                         </span>
                    </td>
                    <td>
                         <span class="col-md-12">
                            Tutor Access
                         </span>
                    </td>
                    <td>
                        <div class="col-md-12">
                            <?php $page_tutorSelect['label'] = array("No access to scores","View Scores","View and Edit Scores");
                            $page_tutorSelect['val'] = array(2,0,1); ?>
                            <div class="col-md-6 padding-left-zero margin-left-minus-five">
                                <?php AssessmentUtility::writeHtmlSelect("tutoredit",$page_tutorSelect['val'],$page_tutorSelect['label'],AppConstant::NUMERIC_ONE); ?>
                            </div>
                        </div>
                    </td>
                 </tr>
        </tbody>
    </table>
</div>
  <?php  }else{ ?>
        <div class="offline-grade-header">
            No offline grades.
            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-grades?cid='.$course->id); ?>"> Add one </a> or <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/upload-multiple-grades?cid='.$course->id); ?>">
                Upload multiple offline grades
            </a>
        </div>
   <?php } ?>
<?php ActiveForm::end(); ?>
</div>
