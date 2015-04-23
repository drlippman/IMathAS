<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;

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
                'labelOptions' => ['class' => 'col-lg-2 '],
            ],
        ]); ?>


        <?= $form->field($model, 'username')->textInput() ?>

        <?= $form->field($model, 'FirstName')->textInput() ?>

        <?= $form->field($model, 'LastName')->textInput() ?>

        <?= $form->field($model, 'email')->textInput() ?>

        <?= $form->field($model, 'password')->passwordInput() ?>

        <?= $form->field($model, 'rights')->inline()->radioList([\app\components\AppConstant::GUEST_RIGHT => 'Guest User',
            \app\components\AppConstant::STUDENT_RIGHT => 'Student',
            \app\components\AppConstant::TEACHER_RIGHT => 'Teacher',
            \app\components\AppConstant::LIMITED_COURSE_CREATOR_RIGHT => 'Limited Course Creator',
            \app\components\AppConstant::DIAGNOSTIC_CREATOR_RIGHT => 'Diagnostic Creator ',
            \app\components\AppConstant::GROUP_ADMIN_RIGHT => 'Group Admin ',
            \app\components\AppConstant::ADMIN_RIGHT => 'Full Admin',]) ?>

        <?= $form->field($model, 'AssignToGroup')->dropDownList(array(''),['prompt'=>'Default']) ?>

        <div class="form-group">
            <div class="col-lg-offset-1 col-lg-11">
                <?= Html::submitButton('Update Info', ['class' => 'btn btn-primary', 'name' => 'Submit']) ?>
            </div>
        </div>
    </fieldset>
    <?php ActiveForm::end(); ?>
</div>
