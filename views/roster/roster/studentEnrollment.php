<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
$this->title = 'Enroll Student';
$this->params['breadcrumbs'][] = $this->title;
?>

<h2>Enroll an Existing user</h2>
<br>

<div class="site-login">
    <?php $form =ActiveForm::begin(
        [
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-2 control-label'],
            ],
        ]
    ) ?>

    <?= $form->field($model, 'usernameToEnroll') ?>
    <?= $form->field($model, 'section') ?>
    <?= $form->field($model, 'code') ?>
    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-11">
            <?= Html::submitButton('Enroll', ['class' => 'btn btn-primary', 'name' => 'enroll-button']) ?>
        </div>
    </div>


    <?php ActiveForm::end(); ?>
</div>
