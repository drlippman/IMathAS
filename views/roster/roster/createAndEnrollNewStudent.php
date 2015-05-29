<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
$this->title = 'Create And Enroll New Student';
$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => ['/instructor/instructor/index?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;
?>
<h2>Enroll a New Student</h2>
<br>

<div class="site-login">
    <?php $form =ActiveForm::begin(
        [
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-2 select-text-margin'],
            ],
        ]
    ) ?>

    <?= $form->field($model, 'username') ?>
    <?= $form->field($model, 'password') ?>
    <?= $form->field($model, 'FirstName') ?>
    <?= $form->field($model, 'LastName') ?>
    <?= $form->field($model, 'email') ?>
    <?= $form->field($model, 'section') ?>
    <?= $form->field($model, 'code') ?>
    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-11">
            <?= Html::submitButton('Create and Enroll', ['class' => 'btn btn-primary','id'=>'enroll-btn', 'name' => 'enroll-button']) ?>
            <a class="btn btn-primary back-button" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>">Back</a>
        </div>
    </div>


    <?php ActiveForm::end(); ?>
</div>
