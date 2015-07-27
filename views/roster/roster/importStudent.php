<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;
//use app\widgets\FileInput;
$this->title = 'Import Students';
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('../../instructor/instructor/_toolbarTeacher', ['course' => $course]);
$model->headerRow = AppConstant::ZERO_VALUE;
$model->userName = AppConstant::ZERO_VALUE;
$model->setPassword = AppConstant::ZERO_VALUE;
$model->codeNumber = AppConstant::ZERO_VALUE;
$model->sectionValue = AppConstant::ZERO_VALUE;
?>
<div class="roster-import-student-csv">
    <fieldset>
        <legend>Import Students from File</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-5 clear-both col-lg-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-3  text-align-left'],
            ],
        ]); ?>
        <div class="text-label"><?php echo "Register and enroll students from a CSV (comma separated values) file"?></div>
        <?= $form->field($model, 'file')->fileInput(); ?>
        <?= $form->field($model, 'headerRow')->radioList([AppConstant::NUMERIC_ONE => 'Yes',AppConstant::NUMERIC_ZERO=>'No']); ?>
        <?= $form->field($model, 'firstName')->textInput(); ?>
        <?= $form->field($model, 'nameFirstColumn')->dropDownList([AppConstant::NUMERIC_ZERO => 'Whole entry', AppConstant::NUMERIC_ONE => 'First word in entry', AppConstant::NUMERIC_TWO => 'Second word in entry', AppConstant::NUMERIC_THREE => 'Last word in entry',], ['prompt' => 'Select entry']) ?>
        <?= $form->field($model, 'lastName')->textInput(); ?>
        <?= $form->field($model, 'nameLastColumn')->dropDownList([AppConstant::NUMERIC_ZERO => 'Whole entry', AppConstant::NUMERIC_ONE => 'First word in entry', AppConstant::NUMERIC_TWO => 'Second word in entry', AppConstant::NUMERIC_THREE => 'Last word in entry',], ['prompt' => 'Select entry']) ?>
        <?= $form->field($model, 'emailAddress')->textInput(); ?>
        <?= $form->field($model, 'userName')->radioList([AppConstant::NUMERIC_ONE => 'Yes, Column: <input type="text" name=unloc size=4 value="2">',AppConstant::NUMERIC_ZERO=>'No, Use as username: firstname_lastname']); ?>
        <?= $form->field($model, 'setPassword')->radioList([AppConstant::NUMERIC_ZERO => 'First 4 characters of username',AppConstant::NUMERIC_ONE=>'Last 4 characters of username',AppConstant::NUMERIC_THREE=>'Use value in column: <input type="text" name="pwcol" size=4 value="1">',AppConstant::NUMERIC_TWO=>'Set to: <input type="text" name="defpw" value="password"   >']); ?>
        <?= $form->field($model, 'codeNumber')->radioList([AppConstant::NUMERIC_ZERO=>'No',AppConstant::NUMERIC_ONE => 'Yes, use value in column: <input type="text" name="code" size=4 value="1">']); ?>
        <?= $form->field($model, 'sectionValue')->radioList([AppConstant::NUMERIC_ZERO=>'No',AppConstant::NUMERIC_ONE => 'Yes, use: <input type="text" name="secval" size=6 value="">',AppConstant::NUMERIC_TWO => 'Yes, use value in column: <input type="text" name="seccol" size=4 value="4">']); ?>
<!--        <label class="col-md-3">Enroll students in</label><label class="col-md-9">This Class</label>-->
    </fieldset>
    <div class="form-group">
        <div class="col-lg-offset-3 roster-submit">
            <?= Html::submitButton('Submit and Review', ['class' => 'btn btn-primary', 'id' => 'submit_and_review', 'name' => 'Submit']) ?>
            <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>">Back</a>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>
