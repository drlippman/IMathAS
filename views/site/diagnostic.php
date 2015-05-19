<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */

$this->title = 'Diagnostic Setup';

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">

    <fieldset>
        <legend>Course Settings</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-3 control-label'],
            ],
        ]); ?>


        <?= $form->field($model, 'diagnosticName')->textInput() ?>



        <?= $form->field($model, 'termDesignator')->inline()->radioList([AppConstant::NUMERIC_ONE => 'Use Month', AppConstant::NUMERIC_TWO => 'Use Day', AppConstant::NUMERIC_THREE => 'Use']) ?>
        <?= $form->field($model, 'linkedWithCourse')->dropDownList(array('', ['prompt' => 'Enter course name here'])) ?>
        <?= $form->field($model, 'available')->checkboxList([AppConstant::NUMERIC_ONE => 'Yes', AppConstant::NUMERIC_TWO => 'No',]) ?>
        <?= $form->field($model, 'includeInPublicListing')->checkboxList([AppConstant::NUMERIC_ONE => 'Yes', AppConstant::NUMERIC_TWO => 'No',]) ?>
        <?= $form->field($model, 'reEntry')->checkboxList([AppConstant::NUMERIC_ONE => 'Yes', AppConstant::NUMERIC_TWO => 'No',]) ?>
        <?= $form->field($model, 'uniqueIdPrompt')->textInput() ?>
        <?= $form->field($model, 'firstLevelSelector')->checkboxList(['' => '']) ?>
        <?= $form->field($model, 'idEntryFormat')->dropDownList(array('Letter or Number', 'Numbers', 'Email address')) ?>
        <?= $form->field($model, 'idEntryNumber')->dropDownList(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, ['prompt' => 'Any Number'])) ?>
        <?= $form->field($model, 'enterIp')->textInput() ?>
        <?= $form->field($model, 'enterPasswordOther')->textInput() ?>
        <?= $form->field($model, 'enterPasswordSuper')->textInput() ?>
        <?= $form->field($model, 'selectorName')->textInput() ?>
        <?= $form->field($model, 'selectorOnSubmit')->checkboxList(['']) ?>
        <?= $form->field($model, 'selectorOption')->textInput() ?>


    </fieldset>


    <?php ActiveForm::end(); ?>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Update Info', ['class' => 'btn btn-primary', 'name' => 'Submit']) ?>
        </div>
    </div>


</div>
