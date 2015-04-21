<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Reset Password';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <?= $this->render('_flashMessage')?>
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-5\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-1 control-label'],
        ],
    ]); ?>

    <?= $form->field($model, 'newPassword')->passwordInput() ?>
    <?= $form->field($model, 'confirmPassword')->passwordInput() ?>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Submit', ['class' => 'btn btn-primary', 'name' => 'resetpassword-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
