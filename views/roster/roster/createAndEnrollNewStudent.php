<?php
use app\components\AppUtility;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = AppUtility::t('Create and Enroll a New Student', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . 'roster/roster/student-roster?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course]); ?>
</div>

<div class="tab-content shadowBox"">
<?php echo $this->render("_toolbarRoster", ['course' => $course]); ?>
<div class="inner-content">
    <?php $form = ActiveForm::begin(
        [
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-md-3\">{input}</div>\n<div class=\"col-md-7 col-md-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-md-3 select-text-margin'],
            ],
        ]
    ) ?>
    <?php echo $form->field($model, 'username') ?>
    <?php echo $form->field($model, 'password')->passwordInput(); ?>
    <?php echo $form->field($model, 'FirstName') ?>
    <?php echo $form->field($model, 'LastName') ?>
    <?php echo $form->field($model, 'email') ?>
    <?php echo $form->field($model, 'section') ?>
    <?php echo $form->field($model, 'code') ?>
    <div class="form-group">
        <div class="col-md-offset-3 col-md-3">
            <?php echo Html::submitButton(AppUtility::t('Create and Enroll', false), ['class' => 'btn btn-primary', 'id' => 'enroll-btn', 'name' => 'enroll-button']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
</div>
