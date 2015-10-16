<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Reset Password';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-md-3\">{input}</div>\n<div class=\"col-md-8 col-md-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-md-2'],
        ],
    ]); ?>
    <?= $form->field($model, 'newPassword')->passwordInput() ?>
    <?= $form->field($model, 'confirmPassword')->passwordInput() ?>

    <div class="form-group">
        <div class="col-md-offset-2 col-md-8 display_field">
            <?= Html::submitButton('Submit', ['id' =>'reset-password-submit','class' => 'btn btn-primary', 'name' => 'resetpassword-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
