<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Enroll in a course';
$this->params['breadcrumbs'][] = $this->title;
?>
<link rel="stylesheet" href="../../web/css/studEnrollCourse.css"/>
<div class="site-login">
    <?= $this->render('../../site/_flashMessage') ?>
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-5\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-2 control-label'],
        ],
    ]); ?>

    <?= $form->field($model, 'selectCourse')->dropDownList(['1' => 'Self study courses'],['prompt'=>'My teacher gave me a course ID (enter below)'], ['class' => 'form-alignment-dropDown-list']) ?>
    <?= $form->field($model, 'courseId') ?>
    <?= $form->field($model, 'enrollmentKey') ?>
    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-11">
            <?= Html::submitButton('Sign Up', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

