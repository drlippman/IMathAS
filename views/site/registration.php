<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\RegistrationForm;

/* @var $this yii\web\View */
/* @var $model app\models\RegistrationForm */
/* @var $form ActiveForm */
$this->title = 'Registration';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="vcenter"><h1>Instructor Account Request</h1></div>

<div class="registration col-lg-6">


    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'action' => '',
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-6\">{input}</div>\n<div class=\"col-lg-8 col-lg-offset-4\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-4'],
        ],
    ]); ?>

    <?= $form->field($model, 'FirstName') ?>
    <?= $form->field($model, 'LastName') ?>
    <?= $form->field($model, 'email') ?>
    <?= $form->field($model, 'phoneno') ?>
    <?= $form->field($model, 'school') ?>
    <?= $form->field($model, 'username') ?>
    <?= $form->field($model, 'password')->passwordInput() ?>
    <?= $form->field($model, 'confirmPassword')->passwordInput() ?>
    <?= $form->field($model, 'terms')->checkbox(['labelOptions' => ['class' => 'register-terms']]) ?>

    <div class="form-group">
        <div class="col-lg-offset-4"><?= Html::submitButton('Request Account', ['class' => 'btn btn-primary instructor-save']) ?></div>
    </div>

    <?php ActiveForm::end(); ?>
    <?php Yii::$app->session->getFlash('error'); ?>
</div><!-- registration -->

<div class="col-lg-12">
    <h4>Terms of Use</h4>

    <p><em>This software is made available with <strong>no warranty</strong> and <strong>no guarantees</strong>. The
            server or software might crash or mysteriously lose all your data. Your account or this service may be
            terminated without warning. No official support is provided. </em></p>

    <p><em>Copyrighted materials should not be posted or used in questions without the permission of the copyright
            owner. You shall be solely responsible for your own user created content and the consequences of posting or
            publishing them. This site expressly disclaims any and all liability in connection with user created
            content.</em></p>

    <div class="clear"></div>
</div>
