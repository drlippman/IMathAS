<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use yii\helpers\ArrayHelper;

$this->title = 'Change Rights';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <fieldset>
        <legend>Change Rights</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3 change-rights\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-2 select-text-margin'],
            ],
        ]); ?>
        <?=
        $form->field($model, 'rights')->inline()->radioList([AppConstant::GUEST_RIGHT => 'Guest User',
            AppConstant::STUDENT_RIGHT => 'Student',
            AppConstant::TEACHER_RIGHT => 'Teacher',
            AppConstant::LIMITED_COURSE_CREATOR_RIGHT => 'Limited Course Creator',
            AppConstant::DIAGNOSTIC_CREATOR_RIGHT => 'Diagnostic Creator ',
            AppConstant::GROUP_ADMIN_RIGHT => 'Group Admin ',
            AppConstant::ADMIN_RIGHT => 'Full Admin',]) ?>
        <?= $form->field($model, 'groupid')->dropDownList(ArrayHelper::map(\app\models\_base\BaseImasGroups::find()->all(), 'id', 'name'), ['prompt' => 'Default']) ?>
        <div class="form-group">
            <div class="col-lg-offset-2">
                <?= Html::submitButton('Save', ['class' => 'btn btn-primary', 'name' => 'Submit']) ?>
            </div>
        </div>
    </fieldset>
    <?php ActiveForm::end(); ?>
</div>
