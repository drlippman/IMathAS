<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;
//use app\widgets\FileInput;


$this->title = 'Import Students';
$this->params['breadcrumbs'][] = ['label' => '', 'url' => ['/roster/roster/student-roster']];
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="site-login">
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
        <?= $form->field($model, 'userName')->radioList([AppConstant::NUMERIC_ONE => 'Yes , Column: <input type="text">',AppConstant::NUMERIC_ZERO=>'No , Use as username: firstname_lastname']); ?>
        <?= $form->field($model, 'setPassword')->radioList([AppConstant::NUMERIC_ZERO => 'First 4 characters of username',AppConstant::NUMERIC_ONE=>'Last 4 characters of username',AppConstant::NUMERIC_THREE=>'Use value in column<input type="text">',AppConstant::NUMERIC_TWO=>'Set to:<input type="text">']); ?>
        <?= $form->field($model, 'codeNumber')->radioList([AppConstant::NUMERIC_ZERO=>'No',AppConstant::NUMERIC_ONE => 'Yes , use value in column:<input type="text">']); ?>
        <?= $form->field($model, 'sectionValue')->radioList([AppConstant::NUMERIC_ZERO=>'No',AppConstant::NUMERIC_ONE => 'Yes , use:<input type="text">',AppConstant::NUMERIC_TWO => 'Yes , use value in column:<input type="text">']); ?>
        <label class="col-md-3">Enroll students in</label><label class="col-md-9">This Class</label>
    </fieldset>
    <div class="form-group">
        <div class="col-lg-offset-3 roster-submit">
            <?= Html::submitButton('Submit and Review', ['class' => 'btn btn-primary', 'name' => 'Submit']) ?>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>
