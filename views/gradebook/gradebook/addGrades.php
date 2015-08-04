<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */
$this->title = 'Add Offline Grades';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
//$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<fieldset xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
        <legend>Add Offline Grades</legend>
        <?php $form = ActiveForm::begin([
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => 'add-grades?cid='.$course->id,
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-3 select-text-margin'],
            ],
        ]); ?>
<!--<form action=add-grades?cid='.$course['id']>-->
    <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
        <?php
        $model->Points = AppUtility::getStringVal(AppConstant::NUMERIC_ZERO);
        $model->ShowGrade = AppUtility::getStringVal(AppConstant::NUMERIC_TWO);
        $model->Count = AppUtility::getStringVal(AppConstant::NUMERIC_ONE);
        $model->Gradetype = AppUtility::getStringVal(AppConstant::NUMERIC_ONE);

        ?>
        <?= $form->field($model, 'Name')->textInput(); ?>
        <?= $form->field($model, 'Points')->textInput(); ?>

        <?= $form->field($model, 'ShowGrade')->radioList([AppConstant::NUMERIC_ONE => 'Always', AppConstant::NUMERIC_TWO => 'At']); ?>

        <div><?= $form->field($model, 'GradeBookCategory')->dropDownList([AppConstant::NUMERIC_ONE => 'Default']); ?></div>
        <?= $form->field($model, 'Count')->radioList([AppConstant::NUMERIC_ONE => 'Count in Gradebook', AppConstant::NUMERIC_TWO => 'Do not count in grade total and hide from students',AppConstant::NUMERIC_THREE => 'Do not count in grade total
        ', AppConstant::NUMERIC_FOUR => 'Count as Extra Credit']); ?>
        <?= $form->field($model, 'TutorAccess')->dropDownList([AppConstant::NUMERIC_TWO => 'No Access',AppConstant::NUMERIC_ZERO => 'View Scores',AppConstant::NUMERIC_ONE => 'View and edit Scores']); ?>

    <label class="col-lg-3 select-text-margin pull-left">Use Scoring Rubric</label>
        <div class="col-lg-4">
            <select name="rubric" class="form-control"  >
                <option value="0" selected>None</option>
                <?php foreach ($rubricsData as $single) { ?>
                    <option
                    value="<?php echo $single['id'] ?>"><?php echo $single['name']; ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="select-text-margin"><a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-rubric?cid='.$course->id) ?>" >Add new rubric</a> | <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/edit-rubric?cid='.$course->id) ?>"  >Edit rubrics</a><div>

        <br><div class="col-lg-offset-3 ">  <?= $form->field($model, 'UploadGrades')-> checkbox(); ?>
                <?= Html::submitButton('Save', ['class' => 'btn btn-primary col-lg-offset-3']) ?>
<!--          <a class="btn btn-primary col-lg-offset-2"  href="--><?php //echo AppUtility::getURLFromHome('gradebook', 'gradebook/upload-grades') ?><!--">Submit</a>-->
<!--                --><?//= Html::submitButton('Save', ['class' => 'btn btn-primary col-lg-offset-2']) ?>
            </div>

        <div class="col-lg-offset-3 ">
            <?= $form->field($model, 'AssessmentSnapshot')-> checkbox(['class' => 'assessment_snapshot']) ?>
        </div>

        <div class="change-assessment-snapshot-content">

            <label class="col-lg-3 select-text-margin pull-left">Assessment To Snapshot</label>
            <div class="col-lg-4">
                <select name="rubric" class="form-control"  >
<!--                    <option value="0" selected>None</option>-->
                    <?php foreach ($assessmentData as $single) { ?>
                        <option
                            value="<?php echo $single['id'] ?>"><?php echo $single['name']; ?></option>
                    <?php } ?>
                </select>
            </div>
           <br><br> <?= $form->field($model, 'Gradetype')->radioList([AppConstant::NUMERIC_ONE =>'Current score',AppConstant::NUMERIC_TWO =>'Participation: give full credit if ≥ <input type="text" size="4" value="100"> % of problems attempted and ≥ <input type="text" size="4" value="0"> points earned.' ]) ?>

        </div>

       <div class="change-non-assessment-snapshot-content">
        <div>
       <input type="button" size="1" value="Expand Feedback Boxes" id="expand-button" class="btn btn-primary back-button-change-student-info"  onclick="togglefeedbackTextFields(-1)" />
        <input type="button" value="Shrink Feedback Boxes" id="shrink-button" class="btn btn-primary back-button-change-student-info"  onclick="togglefeedbackTextFields(0)" />
        <a class="btn btn-primary back-button-change-student-info"  onclick="togglequickadd(this)" >Use Quicksearch Entry</a>
<!--            <button type="button" id="useqa" onclick="togglequickadd(this)"></button>-->
        </div>

        <br><div>  <label>Add/Replace to all grades:</label><input type="text" size="3" id="txt_add" class="col-lg-offset-1"  value="1"/>
            <input type="button" value="Add" class="btn btn-primary" onclick="addReplaceMultiplyTextValue(1)" />
            <input type="button" value="Replace" class="btn btn-primary" onclick="addReplaceMultiplyTextValue(2)"/>
            <input type="button" value="Multiply" class="btn btn-primary" onclick="addReplaceMultiplyTextValue(3)"/>
        </div>

        <br><div>  <label>Add/Replace to all feedback:</label><input type="text" id="feedback_txt" class="col-lg-offset-1"/>
            <input type="button"  value="Append" class="btn btn-primary" onclick="appendPrependReplaceText(1)" />
            <input type="button" value="Prepend" class="btn btn-primary" onclick="appendPrependReplaceText(3)"/>
            <input type="button" value="Replace" class="btn btn-primary" onclick="appendPrependReplaceText(2)"/>
        </div>
     <br>
           <div class="clear"></div>
        <table class="student-data table table-bordered table-striped table-hover data-table" id="student-data-table" bPaginate="false">
            <thead>
            <tr>
                <th>Name</th>
                <th>Section</th>
                <th>Grades</th>
                <th>Feedback</th>
            </tr>
            </thead>
            <tbody>

            <tr id="quickadd" style="display:none;"><td><input type="text" id="project" /></td>
                <td></td>

                <td><input type="text" id="project-id" size="3" onblur="this.value = doonblur(this.value);" onkeydown="return qaonenter(event,this);" /></td>
                <td><textarea id="qafeedback" rows="1" cols="40"></textarea>';
                    <input type="button" value="Next" onfocus="addsuggest()" /></td></tr>





            <?php
            foreach($studentInformation as $singleStudentInformation){ ?>
                <tr>
                    <td><?php echo $singleStudentInformation['Name']?></td>
                    <td><?php echo $singleStudentInformation['Section']?></td>
                    <td><input type="text" name="grade_text[<?php echo $singleStudentInformation['StudentId'] ?>]"  class="latepass-text-id" size="4"/></td>
                    <td><textarea type="text" name="feedback_text[<?php echo $singleStudentInformation['StudentId']?>]" class="feedback-text-id"></textarea> </td>
                </tr>
            <?php }?>
            <tbody>
        </table>
    <br>
    </fieldset>
    <div class="form-group">
        <div class=" col-lg-8 display_field">
            <?= Html::submitButton('Save', ['class' => 'btn btn-primary col-lg-offset-3']) ?>
            <a class="btn btn-primary back-button-change-student-info"  href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$courseId) ?>">Back</a>
        </div>
    </div>
</div>
    <?php ActiveForm::end(); ?>
