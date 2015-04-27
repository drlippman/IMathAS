<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Forget Username';
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

    <p>If you can't remember your username, enter your email address below. An email will be sent to your email address with your username. </p>
    <?= $form->field($model, 'email') ?>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Submit', ['class' => 'btn btn-primary', 'name' => 'forgetusername-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
