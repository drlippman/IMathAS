<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Forget Password';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-10 col-lg-offset-1\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-1 control-label'],
        ],
    ]); ?>

    <p>Enter your User Name below and click Submit. An email will be sent to your email address on file. A link in that
        email will reset your password.</p>
    <?= $form->field($model, 'username') ?>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Submit', ['class' => 'btn btn-primary', 'name' => 'forgetpassword-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>