<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Enroll in a course';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-2 control-label'],
        ],
    ]); ?>

    <?= $form->field($model, 'selectCourse')->dropDownList(['1' => 'Self study courses'],['prompt'=>'My teacher gave me a course ID (enter below)'], ['class' => 'form-alignment-dropDown-list']) ?>
    <?= $form->field($model, 'courseId') ?>
    <?= $form->field($model, 'enrollmentKey') ?>

    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-11">
            <?= Html::submitButton('Sign Up', ['class' => 'btn btn-primary','id'=>'enroll-btn', 'name' => 'login-button']) ?>
            <a class="btn btn-primary back-button" href="<?php echo AppUtility::getURLFromHome('site', 'dashboard')  ?>">Back</a>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
