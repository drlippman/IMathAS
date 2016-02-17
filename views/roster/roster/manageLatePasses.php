<?php
use app\components\AppUtility;
use yii\widgets\ActiveForm;
$this->title = AppUtility::t('Manage Late Passes', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>[AppUtility::t('Home', false),$course->name,AppUtility::t('Roster',false)], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id, AppUtility::getHomeURL().'roster/roster/student-roster?cid='.$course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content padding-top-one-em">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'roster']);?>
</div>
<div class="tab-content shadowBox"">
<?php echo $this->render("_toolbarRoster", ['course' => $course]);?>
<div class="inner-content col-md-12 col-sm-12">
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'action' => '',
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-md-6\">{input}</div>\n<div class=\"col-md-8 col-md-offset-4\">{error}</div>",
            'labelOptions' => ['class' => 'col-md-4'],
        ],
    ]); ?>
    <p><?php AppUtility::t('Students can redeem LatePasses for automatic extensions to assessments where allowed by the instructor.
            Students must redeem the LatePass before the Due Date, unless you opt in your assessment settings to allow
            use after the due date (but within 1 LatePass period, specified below)');?></p>
    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
        <span class="floatleft padding-top-pt-five-em"><?php AppUtility::t('Late Passes extend the due date by');?></span>
        <?php
        $isCheck=false;
        foreach($studentInformation as $singleStudentInformation){
        if($isCheck==false)
        {
        $isCheck=true;
        ?>
        <span class="col-md-1 col-sm-2">
                <input class="form-control" type=text size=3 value="<?php echo $singleStudentInformation['latePassHrs']?>" name="passhours"/>
            </span>
            <span class="floatleft padding-top-pt-five-em">
                <?php AppUtility::t('hours');?>
            </span>

    </div>
<?php }
} ?>
    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
        <span class="floatleft padding-top-pt-five-em"><?php AppUtility::t('To all students');?></span>
        <span class="col-md-1 col-sm-2">
            <input class="form-control" type="text" size="3" id="txt_add" name="addpass" value="1"/>
        </span>
        <span class="padding-left-pt-five-em"><input type="button" class="btn btn-primary" value="<?php AppUtility::t('Add')?>" onclick="addReplaceMultiplyTextValue(1)"/></span>
        <span class="padding-left-one-em"><input type="button" class="btn btn-primary" value="<?php AppUtility::t('Replace')?>" onclick="addReplaceMultiplyTextValue(2)"/></span>
    </div>


    <table class="padding-top-one-em student-data table table-bordered table-striped table-hover data-table" bPaginate="false" id="student-data-table">
        <thead>
        <tr>
            <th><?php AppUtility::t('Name')?></th>
            <th><?php AppUtility::t('Section')?></th>
            <th><?php AppUtility::t('LatePasses Remaining')?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach($studentInformation as $singleStudentInformation){ ?>
            <tr>
                <td><?php echo $singleStudentInformation['Name']?></td>
                <td><?php echo $singleStudentInformation['Section']?> </td>
                <td><input type="text" class="width-fifteen-per form-control latepass-text-id" size="4" value="<?php echo $singleStudentInformation['Latepass']?>"name='code[<?php echo $singleStudentInformation['userid']?>]'> </td>
            </tr>
        <?php }?>
        <tbody>
    </table>
    <div class="col-md-6 col-sm-6 padding-left-zero">
        <input type="submit" class="btn btn-primary" id="change-button" value="<?php AppUtility::t('Save Changes')?>">
    <span class="padding-left-one-em">
        <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>"><?php AppUtility::t('Back')?>
        </a></span>
    </div>

    <?php ActiveForm::end(); ?>
</div>
</div>
