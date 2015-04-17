<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */

$this->title = 'Add new user';

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">

    <?php if (Yii::$app->session->hasFlash('error')): ?>

        <div class="alert alert-danger">
            <?php echo Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <fieldset>
        <legend>New User</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-5\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-2 control-label'],
            ],
        ]); ?>


        <?= $form->field($model, 'SID')->textInput() ?>

        <?= $form->field($model, 'FirstName')->textInput() ?>

        <?= $form->field($model, 'LastName')->textInput() ?>

        <?= $form->field($model, 'email')->textInput() ?>

        <?= $form->field($model, 'password')->passwordInput() ?>

        <?= $form->field($model, 'SetUserRights')->inline()->radioList(['1' => 'Guest User','<>', '2' => 'Student','3' => 'Limited Course Creator','4' => 'Diagnostic Creator ','5' => 'Group Admin ','6' => 'Full Admin',]) ?>

        <?= $form->field($model, 'AssignToGroup')->dropDownList(array(''),['prompt'=>'Default']) ?>

        <div class="form-group">
            <div class="col-lg-offset-1 col-lg-11">
                <?= Html::submitButton('Update Info', ['class' => 'btn btn-primary', 'name' => 'Submit']) ?>
            </div>
        </div>
    </fieldset>


    <?php ActiveForm::end(); ?>


</div>
