<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
use app\components\AppConstant;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */
$this->title = 'Add Offline Grades';
?>
<div>
    <fieldset>
        <legend>Change Student Information</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => 'add-grades',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-3 select-text-margin'],
            ],
        ]); ?>
        <?= $form->field($model, 'Name')->textInput(); ?>
        <?= $form->field($model, 'Points')->textInput(); ?>
        <?= $form->field($model, 'ShowGrade')->radioList([AppConstant::NUMERIC_ONE => 'Always', AppConstant::NUMERIC_TWO => 'At']); ?>
        <?= $form->field($model, 'GradeBookCategory')->dropDownList([AppConstant::NUMERIC_ONE => 'Default']); ?>
        <?= $form->field($model, 'Count')->radioList([AppConstant::NUMERIC_ONE => 'Count in Gradebook', AppConstant::NUMERIC_TWO => 'Do not count in grade total and hide from students',AppConstant::NUMERIC_THREE => 'Do not count in grade total
        ', AppConstant::NUMERIC_FOUR => 'Count as Extra Credit']); ?>
        <?= $form->field($model, 'ScoringRubric')->dropDownList([AppConstant::NUMERIC_ONE => 'None']); ?>

        <div class="col-lg-offset-3 ">  <?= $form->field($model, 'UploadGrades')-> checkbox(); ?>
          <a class="btn btn-primary col-lg-offset-2"  href="#">Submit</a>
        </div>

        <div class="col-lg-offset-3 ">    <?= $form->field($model, 'AssessmentSnapshot')-> checkbox(); ?></div>

        <div> <a class="btn btn-primary back-button-change-student-info"  href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$courseId) ?>">Expand Feedback Boxes</a>
        <a class="btn btn-primary back-button-change-student-info"  href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$courseId) ?>">Use Quicksearch Entry</a>
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
        <table class="student-data table table-bordered table-striped table-hover data-table" id="student-data-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Section</th>
                <th>Grades</th>
                <th>Feedback</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach($studentInformation as $singleStudentInformation){ ?>
                <tr>
                    <td><?php echo $singleStudentInformation['Name']?></td>
                    <td><?php echo $singleStudentInformation['Section']?></td>
                    <td><input type="text" class="latepass-text-id" size="4"/></td>
                    <td><input type="text" class="feedback-text-id"/> </td>
                </tr>
            <?php }?>
            <tbody>
        </table>
    <br>
    </fieldset>
    <div class="form-group">
        <div class=" col-lg-8 display_field">
            <?= Html::submitButton('Save', ['class' => 'btn btn-primary col-lg-offset-3','id' => 'update-btn', 'name' => 'login-button']) ?>
            <a class="btn btn-primary back-button-change-student-info"  href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$courseId) ?>">Back</a>
        </div>
    </div>
</div>
    <?php ActiveForm::end(); ?>
